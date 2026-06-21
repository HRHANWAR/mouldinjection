<?php
/**
 * IH Listing Notification Helper — FIXED VERSION
 * ─────────────────────────────────────────────────────────────────────────
 * functions.php mein include karein:
 *   require_once get_template_directory() . '/ih-listing-notify.php';
 * ─────────────────────────────────────────────────────────────────────────
 */
defined( 'ABSPATH' ) || exit;

/* ── Unique Reference ID ── */
function ih_get_listing_ref( $listing_id, $listing_type = 'machine' ) {
    $prefix = ( strtolower( $listing_type ) === 'tool' ) ? 'TL' : 'MCH';
    return $prefix . '-' . str_pad( (int) $listing_id, 5, '0', STR_PAD_LEFT );
}

/* ── CORE FUNCTION ── */
function ih_notify_admin_new_listing( $user_id, $listing_id, $listing_type, $listing_title ) {
    global $wpdb;

    $user = get_userdata( $user_id );
    if ( ! $user ) return;

    $listing_ref = ih_get_listing_ref( $listing_id, $listing_type );

    /* 1. ih_requests insert — sirf existing columns use karo */
    $existing = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ih_requests WHERE user_id=%d AND listing_id=%d AND listing_type=%s LIMIT 1",
        $user_id, $listing_id, $listing_type
    ) );

    if ( ! $existing ) {
        $columns = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}ih_requests" );

        $all_fields = [
            'user_id'      => [ $user_id,                '%d' ],
            'listing_id'   => [ $listing_id,             '%d' ],
            'listing_type' => [ $listing_type,           '%s' ],
            'status'       => [ 'Pending',               '%s' ],
            'name'         => [ $user->display_name,     '%s' ],
            'email'        => [ $user->user_email,       '%s' ],
            'phone'        => [ get_user_meta( $user_id, 'ih_phone', true ) ?: '', '%s' ],
            'location'     => [ '',                      '%s' ],
            'request_date' => [ current_time('mysql'),   '%s' ],
            'created_at'   => [ current_time('mysql'),   '%s' ],
            'updated_at'   => [ current_time('mysql'),   '%s' ],
        ];

        $data = []; $fmt = [];
        foreach ( $all_fields as $col => $v ) {
            if ( in_array( $col, $columns, true ) ) {
                $data[$col] = $v[0]; $fmt[] = $v[1];
            }
        }

        $ok = $wpdb->insert( $wpdb->prefix . 'ih_requests', $data, $fmt );
        if ( false === $ok && defined('WP_DEBUG') && WP_DEBUG )
            error_log('IH: ih_requests insert failed — ' . $wpdb->last_error);
    }

    /* 2. Thread */
    $thread_id = ih_ensure_thread( $user_id, $listing_title );

    /* 3. Message */
    if ( $thread_id ) {
        $msg = "🆕 New " . ucfirst($listing_type) . " submitted for approval\n\n"
             . "🪪 ID: {$listing_ref}\n"
             . "📋 Title: {$listing_title}\n"
             . "👤 User: {$user->display_name}\n\n"
             . "✅ Approve or ❌ Reject from the listing bar above.";
        ih_insert_system_message( $thread_id, $user_id, $msg );
    }
}

/* ── Thread helper ── */
function ih_ensure_thread( $user_id, $last_message = '' ) {
    global $wpdb;
    $table   = $wpdb->prefix . 'ih_threads';
    $preview = mb_substr( $last_message, 0, 80 );
    $now     = current_time('mysql');
    $t_cols  = $wpdb->get_col("SHOW COLUMNS FROM {$table}");

    $thread_id = $wpdb->get_var( $wpdb->prepare("SELECT id FROM {$table} WHERE user_id=%d LIMIT 1", $user_id) );

    if ( $thread_id ) {
        $upd = []; $ufmt = [];
        if ( in_array('last_message',$t_cols) ) { $upd['last_message']='📋 New listing: '.$preview; $ufmt[]='%s'; }
        if ( in_array('last_time',   $t_cols) ) { $upd['last_time']   = date('g:i A',strtotime($now)); $ufmt[]='%s'; }
        if ( in_array('updated_at',  $t_cols) ) { $upd['updated_at']  = $now; $ufmt[]='%s'; }
        if ( in_array('unread',      $t_cols) ) {
            $cur = (int)$wpdb->get_var($wpdb->prepare("SELECT unread FROM {$table} WHERE id=%d",$thread_id));
            $upd['unread'] = $cur+1; $ufmt[]='%d';
        }
        if (!empty($upd)) $wpdb->update($table,$upd,['id'=>$thread_id],$ufmt,['%d']);
    } else {
        $idata=[]; $ifmt=[];
        $tmap=[
            'user_id'      =>[$user_id,'%d'],
            'last_message' =>['📋 New listing: '.$preview,'%s'],
            'last_time'    =>[date('g:i A',strtotime($now)),'%s'],
            'unread'       =>[1,'%d'],
            'created_at'   =>[$now,'%s'],
            'updated_at'   =>[$now,'%s'],
        ];
        foreach($tmap as $col=>$v){ if(in_array($col,$t_cols,true)){$idata[$col]=$v[0];$ifmt[]=$v[1];} }
        $ok=$wpdb->insert($table,$idata,$ifmt);
        if(false===$ok){ if(defined('WP_DEBUG')&&WP_DEBUG) error_log('IH: ih_threads insert failed — '.$wpdb->last_error); return 0; }
        $thread_id=$wpdb->insert_id;
    }
    return (int)$thread_id;
}

/* ── Message insert helper ── */
function ih_insert_system_message( $thread_id, $user_id, $text ) {
    global $wpdb;
    $tbl = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ih_messages'")
         ? $wpdb->prefix.'ih_messages'
         : $wpdb->prefix.'ih_chats';

    $cols = $wpdb->get_col("SHOW COLUMNS FROM {$tbl}");
    $mmap=[
        'thread_id'  =>[$thread_id,'%d'],
        'sender_id'  =>[$user_id,'%d'],
        'from_me'    =>[0,'%d'],
        'message'    =>[$text,'%s'],
        'text'       =>[$text,'%s'],
        'is_read'    =>[0,'%d'],
        'sent_at'    =>[current_time('mysql'),'%s'],
        'created_at' =>[current_time('mysql'),'%s'],
    ];
    $d=[]; $f=[];
    foreach($mmap as $col=>$v){ if(in_array($col,$cols,true)){$d[$col]=$v[0];$f[]=$v[1];} }
    $ok=$wpdb->insert($tbl,$d,$f);
    if(false===$ok && defined('WP_DEBUG')&&WP_DEBUG) error_log('IH: message insert failed — '.$wpdb->last_error);
}

/* ── AJAX handler ── */
add_action('wp_ajax_ih_listing_submit_for_approval','ih_ajax_listing_submit_for_approval');
function ih_ajax_listing_submit_for_approval() {
    if(!check_ajax_referer('ih_nonce','nonce',false)) wp_send_json_error(['message'=>'Invalid nonce']);
    $uid   = get_current_user_id();
    $id    = intval($_POST['listing_id']    ?? 0);
    $type  = sanitize_text_field($_POST['listing_type']  ?? 'machine');
    $title = sanitize_text_field($_POST['listing_title'] ?? 'New Listing');
    if(!$uid||!$id) wp_send_json_error(['message'=>'Missing data']);
    ih_notify_admin_new_listing($uid,$id,$type,$title);
    wp_send_json_success(['message'=>'Submitted','listing_ref'=>ih_get_listing_ref($id,$type)]);
}

/* ── WP action hooks ── */
add_action('ih_machine_saved',function($mid,$uid,$title){ ih_notify_admin_new_listing($uid,$mid,'machine',$title); },10,3);
add_action('ih_tool_saved',   function($tid,$uid,$title){ ih_notify_admin_new_listing($uid,$tid,'tool',$title);    },10,3);

/* ── Status change notification ── */
add_action('ih_listing_status_changed',function($req_id,$status,$user_id){
    global $wpdb;
    if(!in_array($status,['Approved','Rejected'],true)) return;
    $tid=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ih_threads WHERE user_id=%d LIMIT 1",$user_id));
    if(!$tid) return;
    $req=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ih_requests WHERE id=%d",$req_id),ARRAY_A);
    $title='';
    if($req){ $tbl=($req['listing_type']==='tool'?$wpdb->prefix.'ih_tools':$wpdb->prefix.'ih_machines'); $title=$wpdb->get_var($wpdb->prepare("SELECT title FROM {$tbl} WHERE id=%d",$req['listing_id'])); }
    $ref=$req?ih_get_listing_ref($req['listing_id'],$req['listing_type']):'';
    $emoji=$status==='Approved'?'✅':'❌';
    ih_insert_system_message($tid,0,"{$emoji} Listing {$ref} (*{$title}*) has been ".strtolower($status)." by admin.");
},10,3);