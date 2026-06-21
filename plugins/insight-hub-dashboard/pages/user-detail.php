<?php defined( 'ABSPATH' ) || exit;

/*
 * InsideHub redesigned user-detail.php
 * ADDED: Contact Requests section — admin can approve/reject from here
 */

$uid = isset($_GET['view']) ? absint($_GET['view']) : 0;
if ( ! $uid ) {
    echo '<script>window.location="' . esc_url( admin_url('admin.php?page=ih-users') ) . '";</script>';
    return;
}

global $wpdb;

$user = get_userdata( $uid );
if ( ! $user ) {
    echo '<script>window.location="' . esc_url( admin_url('admin.php?page=ih-users') ) . '";</script>';
    return;
}

$viewer_id       = get_current_user_id();
$viewer_is_owner = $viewer_id && ( (int) $viewer_id === (int) $uid );
$viewer_is_admin = current_user_can( 'manage_options' );
$notice          = '';

if ( ! function_exists( 'ihud_first_value' ) ) {
    function ihud_first_value( array $sources, array $keys, $fallback = '' ) {
        foreach ( $keys as $key ) {
            foreach ( $sources as $source ) {
                if ( is_array( $source ) && isset( $source[ $key ] ) && $source[ $key ] !== '' && $source[ $key ] !== null ) {
                    return $source[ $key ];
                }
            }
        }
        return $fallback;
    }
}
if ( ! function_exists( 'ihud_img_url' ) ) {
    function ihud_img_url( $value ) {
        if ( empty( $value ) ) return '';
        if ( is_numeric( $value ) ) {
            $url = wp_get_attachment_image_url( (int) $value, 'large' );
            return $url ? $url : '';
        }
        return esc_url_raw( $value );
    }
}
if ( ! function_exists( 'ihud_initials' ) ) {
    function ihud_initials( $name ) {
        $parts = preg_split( '/\s+/', trim( (string) $name ) );
        $letters = '';
        foreach ( $parts as $part ) {
            if ( $part !== '' ) $letters .= mb_substr( $part, 0, 1 );
            if ( mb_strlen( $letters ) >= 2 ) break;
        }
        return strtoupper( $letters ?: 'U' );
    }
}
if ( ! function_exists( 'ihud_three' ) ) {
    function ihud_three( $value, $fallback = 'XXX' ) {
        $clean = strtoupper( preg_replace( '/[^a-zA-Z0-9]/', '', (string) $value ) );
        $clean = $clean ?: $fallback;
        return str_pad( substr( $clean, 0, 3 ), 3, 'X' );
    }
}
if ( ! function_exists( 'ihud_user_code' ) ) {
    function ihud_user_code( $name, $city ) {
        $bits  = preg_split( '/[^a-zA-Z0-9]+/', (string) $name, -1, PREG_SPLIT_NO_EMPTY );
        $first = $bits[0] ?? $name;
        return ihud_three( $first ) . ihud_three( $city );
    }
}
if ( ! function_exists( 'ihud_get_unique_id' ) ) {
    function ihud_get_unique_id( $user_id, $display_name = '', $city = '' ) {
        $saved = get_user_meta( $user_id, 'ih_unique_id', true );
        if ( ! empty($saved) ) return (string) $saved;
        if ( function_exists('ih_generate_unique_id') ) {
            $uname  = $display_name ?: get_userdata($user_id)->display_name;
            $ucity  = $city ?: get_user_meta($user_id, 'city', true);
            $new_id = ih_generate_unique_id($uname, $ucity);
        } else {
            $new_id = ihud_user_code($display_name, $city);
        }
        update_user_meta($user_id, 'ih_unique_id', $new_id);
        return $new_id;
    }
}
if ( ! function_exists( 'ihud_mask_value' ) ) {
    function ihud_mask_value( $value, $type = 'text' ) {
        $value = (string) $value;
        if ( $value === '' ) return 'Not provided';
        if ( $type === 'email' )   { $parts = explode( '@', $value ); return substr( $parts[0], 0, 2 ) . '••••@' . ( $parts[1] ?? 'hidden' ); }
        if ( $type === 'phone' )   return preg_replace( '/[0-9](?=.{3})/', '•', $value );
        if ( $type === 'company' ) return 'Company name hidden';
        if ( $type === 'website' ) return 'Website hidden';
        if ( $type === 'address' ) return 'Full address hidden';
        return 'Hidden until approved';
    }
}
if ( ! function_exists( 'ihud_clean_phone' ) ) {
    function ihud_clean_phone( $raw ) { return preg_replace( '/\D+/', '', (string) $raw ); }
}
if ( ! function_exists( 'ihud_profile_access_request' ) ) {
    function ihud_profile_access_request( $requester_id, $target_user_id ) {
        global $wpdb;
        if ( ! $requester_id || ! $target_user_id ) return null;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ih_requests WHERE user_id = %d AND listing_id = %d AND listing_type = %s ORDER BY id DESC LIMIT 1",
            $requester_id, $target_user_id, 'profile_access'
        ), ARRAY_A );
    }
}
if ( ! function_exists( 'ihud_request_profile_access' ) ) {
    function ihud_request_profile_access( $requester_id, $target_user_id ) {
        global $wpdb;
        $existing = ihud_profile_access_request( $requester_id, $target_user_id );
        if ( $existing && strtolower( (string) $existing['status'] ) !== 'rejected' ) {
            return [ 'status' => $existing['status'], 'request_id' => (int) $existing['id'], 'existing' => true ];
        }
        $ok = $wpdb->insert( $wpdb->prefix . 'ih_requests', [
            'user_id' => (int) $requester_id, 'listing_id' => (int) $target_user_id,
            'listing_type' => 'profile_access', 'request_date' => current_time('Y-m-d'), 'status' => 'Pending',
        ], ['%d','%d','%s','%s','%s'] );
        if ( $ok === false ) return new WP_Error( 'ihud_request_failed', 'Could not send the access request.' );
        $request_id = (int) $wpdb->insert_id;
        $requester  = get_userdata( $requester_id );
        $target     = get_userdata( $target_user_id );
        $message    = "Sensitive data access request #{$request_id}\n\nRequester: " . ( $requester ? $requester->display_name : 'User #' . $requester_id ) . "\nTarget profile: " . ( $target ? $target->display_name : 'User #' . $target_user_id ) . "\n\nRequested fields: company name, email, phone, WhatsApp, website, full address and postcode.\nApprove or reject this request in the Message Requests table.";
        $thread_id = 0;
        if ( function_exists( 'ih_get_thread_by_user_id' ) ) $thread_id = (int) ih_get_thread_by_user_id( $requester_id );
        if ( ! $thread_id ) {
            $wpdb->insert( $wpdb->prefix . 'ih_threads', ['user_id'=>(int)$requester_id,'listing_id'=>(int)$target_user_id,'listing_type'=>'profile_access','last_message'=>$message,'last_time'=>current_time('mysql',true),'unread'=>1], ['%d','%d','%s','%s','%s','%d'] );
            $thread_id = (int) $wpdb->insert_id;
        } else {
            $wpdb->update( $wpdb->prefix . 'ih_threads', ['listing_id'=>(int)$target_user_id,'listing_type'=>'profile_access','last_message'=>$message,'last_time'=>current_time('mysql',true),'unread'=>1], ['id'=>$thread_id], ['%d','%s','%s','%s','%d'], ['%d'] );
        }
        if ( $thread_id ) $wpdb->insert( $wpdb->prefix . 'ih_chats', ['thread_id'=>$thread_id,'from_me'=>0,'message'=>$message,'sent_at'=>current_time('mysql',true)], ['%d','%d','%s','%s'] );
        return [ 'status' => 'Pending', 'request_id' => $request_id, 'existing' => false ];
    }
}

/* Handle profile access request form */
if ( isset( $_POST['ihud_request_profile_access'] ) ) {
    if ( ! is_user_logged_in() ) { wp_safe_redirect( wp_login_url( admin_url( 'admin.php?page=ih-users&view=' . $uid ) ) ); exit; }
    check_admin_referer( 'ihud_request_profile_access_' . $uid, 'ihud_nonce' );
    if ( $viewer_is_owner || $viewer_is_admin ) {
        $notice = 'You already have permission to view this information.';
    } else {
        $result = ihud_request_profile_access( $viewer_id, $uid );
        if ( is_wp_error( $result ) ) { $notice = $result->get_error_message(); }
        else { $notice = ( ! empty( $result['existing'] ) ) ? 'Your access request is already ' . strtolower( $result['status'] ) . '.' : 'Request sent. Admin can approve it in Messages.'; }
    }
}

/* Fetch user meta */
$ih_meta = function_exists( 'ih_db_user_meta' ) ? ih_db_user_meta( $uid ) : [];
$ih_meta = $ih_meta ?: [];
$wp_meta = get_user_meta( $uid );
$flat_wp_meta = [];
foreach ( $wp_meta as $key => $values ) { $flat_wp_meta[ $key ] = is_array( $values ) ? ( $values[0] ?? '' ) : $values; }

$company       = ihud_first_value( [ $ih_meta, $flat_wp_meta ], [ 'company', 'company_name', 'ih_company', 'billing_company' ] );
$address       = ihud_first_value( [ $ih_meta, $flat_wp_meta ], [ 'address', 'ih_address', 'billing_address_1' ] );
$postcode      = ihud_first_value( [ $ih_meta, $flat_wp_meta ], [ 'postcode', 'ih_postcode', 'billing_postcode' ] );
$business_role = ihud_first_value( [ $ih_meta, $flat_wp_meta ], [ 'business_role', 'ih_business_role', 'role' ] );
$job_title     = ihud_first_value( [ $ih_meta, $flat_wp_meta ], [ 'job_title', 'ih_job_title' ] );
$city          = ihud_first_value( [ $ih_meta, $flat_wp_meta ], [ 'city', 'location', 'ih_city', 'ih_location', 'billing_city' ] );
$website       = ihud_first_value( [ $ih_meta, $flat_wp_meta ], [ 'website', 'ih_website' ], $user->user_url );
$phone         = ihud_first_value( [ $ih_meta, $flat_wp_meta ], [ 'phone', 'office_number', 'ih_phone', 'billing_phone' ] );
$whatsapp      = ihud_first_value( [ $ih_meta, $flat_wp_meta ], [ 'whatsapp', 'whatsapp_number', 'ih_whatsapp' ], $phone );
$joined        = ! empty( $ih_meta['joined'] ) ? date_i18n( 'd/m/Y', strtotime( $ih_meta['joined'] ) ) : date_i18n( 'd/m/Y', strtotime( $user->user_registered ) );
$avatar_url    = get_avatar_url( $uid, [ 'size' => 160 ] );
$user_code     = ihud_get_unique_id( $uid, $user->display_name, $city );

/* Sync ih_profiles */
$profile_exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}ih_profiles WHERE user_id = %d", $uid ) );
if ( ! $profile_exists ) {
    $wpdb->insert( "{$wpdb->prefix}ih_profiles", [ 'user_id'=>$uid,'business_role'=>$business_role?:'','company_name'=>$company?:'','job_title'=>$job_title?:'','phone'=>$phone?:'','address'=>$address?:'','city'=>$city?:'','postcode'=>$postcode?:'','website'=>$website?:'','whatsapp'=>$whatsapp?:'','account_status'=>'active','unique_id'=>$user_code ] );
} else {
    $wpdb->update( "{$wpdb->prefix}ih_profiles", ['unique_id'=>$user_code], ['user_id'=>$uid], ['%s'], ['%d'] );
}

/* Access control */
$access_row = ( $viewer_id && ! $viewer_is_owner && ! $viewer_is_admin ) ? ihud_profile_access_request( $viewer_id, $uid ) : null;
$access_status = $viewer_is_admin || $viewer_is_owner ? 'Approved' : ( $access_row['status'] ?? 'None' );
$has_sensitive_access = $viewer_is_admin || $viewer_is_owner || strtolower( (string) $access_status ) === 'approved';
$access_status_key = strtolower( (string) $access_status );

$completion_fields = [ $company, $address, $postcode, $business_role, $job_title, $city, $website, $phone, $whatsapp, $user->user_email ];
$completion = (int) round( ( count( array_filter( $completion_fields ) ) / max( 1, count( $completion_fields ) ) ) * 100 );

/* Listings */
$machines = $wpdb->get_results( $wpdb->prepare( "SELECT m.*, u.display_name AS owner_name FROM {$wpdb->prefix}ih_machines m LEFT JOIN {$wpdb->users} u ON u.ID = m.owner_id WHERE m.owner_id = %d ORDER BY m.id DESC", $uid ), ARRAY_A ) ?: [];
$tools    = $wpdb->get_results( $wpdb->prepare( "SELECT t.*, u.display_name AS owner_name FROM {$wpdb->prefix}ih_tools t LEFT JOIN {$wpdb->users} u ON u.ID = t.owner_id WHERE t.owner_id = %d ORDER BY t.id DESC", $uid ), ARRAY_A ) ?: [];

$cards = [];
foreach ( $machines as $m ) {
    $id = (int) $m['id'];
    $cards[] = [ 'id'=>$id,'type'=>'machine','label'=>'Machine','title'=>$m['title']??'Machine','company'=>$m['owner_name']??'','status'=>!empty($m['available'])?'Available Now':'Unavailable','type_label'=>$m['machine_type']??'','thumb'=>ihud_img_url($m['image_1']??''),'list_date'=>!empty($m['listing_date'])?date_i18n('d M Y',strtotime($m['listing_date'])):'—','exp_date'=>!empty($m['expiry_date'])?date_i18n('d M Y',strtotime($m['expiry_date'])):'—','detail_url'=>admin_url('admin.php?page='.($viewer_is_admin?'ih-machine-detail':'ih-user-view-machine').'&machine_id='.$id),'edit_url'=>admin_url('admin.php?page='.($viewer_is_admin?'ih-edit-machine':'ih-user-edit-machine').'&machine_id='.$id),'message_url'=>admin_url('admin.php?page='.($viewer_is_admin?'ih-messages':'ih-user-messages').'&user_id='.$uid) ];
}
foreach ( $tools as $t ) {
    $id = (int) $t['id'];
    $cards[] = [ 'id'=>$id,'type'=>'tool','label'=>'Tool','title'=>$t['title']??'Tool','company'=>$t['owner_name']??'','status'=>!empty($t['available'])?'Available Now':'Unavailable','type_label'=>$t['mould_type']??'','thumb'=>ihud_img_url($t['image_1']??''),'list_date'=>!empty($t['listing_date'])?date_i18n('d M Y',strtotime($t['listing_date'])):'—','exp_date'=>!empty($t['expiry_date'])?date_i18n('d M Y',strtotime($t['expiry_date'])):'—','detail_url'=>admin_url('admin.php?page='.($viewer_is_admin?'ih-tool-detail':'ih-user-view-tool').'&tool_id='.$id),'edit_url'=>admin_url('admin.php?page='.($viewer_is_admin?'ih-edit-tool':'ih-user-edit-tool').'&tool_id='.$id),'message_url'=>admin_url('admin.php?page='.($viewer_is_admin?'ih-messages':'ih-user-messages').'&user_id='.$uid) ];
}

$total_listings      = count( $cards );
$can_manage_listings = $viewer_is_admin || $viewer_is_owner;

/* ═══════════════════════════════════════════════════════════════
   CONTACT REQUESTS FOR THIS USER'S LISTINGS
   (people who want to see this user's contact info)
   ═══════════════════════════════════════════════════════════════ */
$contact_requests_received = [];
if ( $viewer_is_admin ) {
    // Machine contact requests where this user is the owner
    $mcr = $wpdb->get_results( $wpdb->prepare(
        "SELECT r.id, r.user_id AS requester_id, r.listing_id, r.listing_type, r.status, r.request_date,
                u.display_name AS requester_name, u.user_email AS requester_email,
                m.title AS listing_title
         FROM {$wpdb->prefix}ih_requests r
         INNER JOIN {$wpdb->prefix}ih_machines m ON m.id = r.listing_id
         LEFT JOIN {$wpdb->users} u ON u.ID = r.user_id
         WHERE r.listing_type = 'machine_contact' AND m.owner_id = %d
         ORDER BY r.id DESC",
        $uid
    ), ARRAY_A ) ?: [];

    // Tool contact requests where this user is the owner
    $tcr = $wpdb->get_results( $wpdb->prepare(
        "SELECT r.id, r.user_id AS requester_id, r.listing_id, r.listing_type, r.status, r.request_date,
                u.display_name AS requester_name, u.user_email AS requester_email,
                t.title AS listing_title
         FROM {$wpdb->prefix}ih_requests r
         INNER JOIN {$wpdb->prefix}ih_tools t ON t.id = r.listing_id
         LEFT JOIN {$wpdb->users} u ON u.ID = r.user_id
         WHERE r.listing_type = 'tool_contact' AND t.owner_id = %d
         ORDER BY r.id DESC",
        $uid
    ), ARRAY_A ) ?: [];

    // Profile access requests targeting this user
    $par = $wpdb->get_results( $wpdb->prepare(
        "SELECT r.id, r.user_id AS requester_id, r.listing_id, r.listing_type, r.status, r.request_date,
                u.display_name AS requester_name, u.user_email AS requester_email,
                'Profile data access' AS listing_title
         FROM {$wpdb->prefix}ih_requests r
         LEFT JOIN {$wpdb->users} u ON u.ID = r.user_id
         WHERE r.listing_type = 'profile_access' AND r.listing_id = %d
         ORDER BY r.id DESC",
        $uid
    ), ARRAY_A ) ?: [];

    $contact_requests_received = array_merge( $mcr, $tcr, $par );

    // Sort all by id DESC
    usort( $contact_requests_received, fn($a,$b) => (int)$b['id'] - (int)$a['id'] );
}

$pending_count  = count( array_filter( $contact_requests_received, fn($r) => $r['status'] === 'Pending' ) );
$approve_nonce  = wp_create_nonce('ih_admin_approve_contact');

ob_start();
?>
<style>
/* All existing styles from original file */
.ihud-page{--ih-blue:#1e5f8a;--ih-blue-dark:#164f72;--ih-green:#164b3f;--ih-green-2:#16824d;--ih-bg:#f4f8fb;--ih-border:#d9e7f7;--ih-muted:#6b8aa3;--ih-soft:#f7fbff;color:#0f172a;background:#f4f8fb;margin:-8px 0 0;}
.ihud-page *{box-sizing:border-box;}
.ihud-wrap{max-width:1720px;margin:0 auto;padding:16px 0 24px;display:grid;gap:16px;}
.ihud-breadcrumb{display:flex;align-items:center;justify-content:space-between;gap:12px;font-size:14px;color:var(--ih-muted);}
.ihud-breadcrumb a{color:var(--ih-muted);text-decoration:none;}.ihud-breadcrumb a:hover{color:var(--ih-blue);}.ihud-close{display:grid;place-items:center;width:36px;height:36px;border-radius:999px;color:#94a3b8;text-decoration:none;}.ihud-close:hover{background:#fff;color:var(--ih-blue)}
.ihud-card{position:relative;overflow:visible;border:1px solid var(--ih-border);border-radius:22px;background:#fff;box-shadow:0 10px 30px rgba(30,95,138,.06);transition:transform .22s ease,box-shadow .22s ease,border-color .22s ease;isolation:isolate;}
@keyframes ihudShake{0%,100%{transform:translate3d(0,0,0)}25%{transform:translate3d(-1px,0,0)}50%{transform:translate3d(1px,0,0)}75%{transform:translate3d(-.5px,0,0)}}
@media(hover:hover) and (prefers-reduced-motion:no-preference){.ihud-page.locked .ihud-card:hover{border-color:rgba(59,130,246,.46);box-shadow:0 14px 34px rgba(30,95,138,.13);animation:ihudShake .22s ease-in-out}.ihud-page.unlocked .ihud-card:hover{border-color:rgba(34,197,94,.46);box-shadow:0 14px 34px rgba(22,163,74,.13);animation:ihudShake .22s ease-in-out}}
.ihud-hero{overflow:hidden;border-radius:26px}.ihud-hero-grid{display:grid;grid-template-columns:minmax(0,1.2fr) 360px}.ihud-hero-main{padding:28px;background:linear-gradient(135deg,#fff 0%,<?php echo $has_sensitive_access ? '#f7fff9 48%,#e8f8ee' : '#f5fbff 48%,#eaf4ff'; ?> 100%)}.ihud-summary{padding:24px;background:linear-gradient(135deg,<?php echo $has_sensitive_access ? '#14532d,#16824d' : '#164b3f,#1e5f8a'; ?>);color:#fff}.ihud-profile-row{display:flex;justify-content:space-between;gap:20px;align-items:flex-start}.ihud-ident{display:flex;gap:16px;align-items:center;min-width:0}.ihud-avatar{width:80px;height:80px;border-radius:999px;display:grid;place-items:center;background:#e7f6ed;color:var(--ih-blue);border:4px solid #fff;overflow:hidden;font-size:24px;font-weight:500;flex:0 0 auto}.ihud-avatar img{width:100%;height:100%;object-fit:cover}.ihud-title{margin:8px 0 0;font-size:30px;line-height:1.1;font-weight:400;letter-spacing:-.02em}.ihud-sub{display:flex;flex-wrap:wrap;gap:8px 16px;margin-top:10px;color:#64748b;font-size:14px}.ihud-sub span{display:inline-flex;gap:6px;align-items:center;min-width:0}.ihud-badges{display:flex;flex-wrap:wrap;gap:8px}.ihud-badge{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 10px;font-size:12px;font-weight:400;white-space:nowrap}.ihud-badge.green{background:#dff6e8;color:#16824d}.ihud-badge.blue{background:#dbeafe;color:#1d4ed8}.ihud-badge.grey{background:#f1f5f9;color:#64748b}.ihud-badge.amber{background:#fff8e1;color:#9a6500}.ihud-badge.red{background:#ffebee;color:#c62828}.ihud-actions{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px;margin-top:22px}.ihud-action{display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:14px;border:1px solid var(--ih-border);background:#fff;color:var(--ih-blue);padding:11px 12px;text-decoration:none;font-size:14px}.ihud-action:hover{background:#eef7ff}.ihud-action.locked{pointer-events:none;color:#cbd5e1;background:#f8fafc}.ihud-request-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:14px;background:var(--ih-blue);color:#fff;padding:11px 16px;font-size:14px;text-decoration:none;cursor:pointer}.ihud-request-btn:hover{background:var(--ih-blue-dark)}.ihud-progress{height:8px;border-radius:999px;overflow:hidden;background:rgba(255,255,255,.22);margin:16px 0}.ihud-progress span{display:block;height:100%;background:#fff;border-radius:999px}.ihud-summary-list{display:grid;gap:10px;color:rgba(255,255,255,.78);font-size:14px}.ihud-summary-list div{display:flex;justify-content:space-between;gap:12px}.ihud-summary-list strong{color:#fff;font-weight:400;text-align:right}.ihud-access{display:grid;grid-template-columns:minmax(0,1fr) 260px;overflow:hidden}.ihud-access-main{padding:20px}.ihud-access-side{padding:20px;background:#f7fbff;border-left:1px solid var(--ih-border)}.ihud-access h2{margin:12px 0 4px;font-size:18px;font-weight:400}.ihud-access p{margin:0;color:var(--ih-muted);font-size:14px;line-height:1.6}.ihud-request-form button{width:100%;display:flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:14px;background:var(--ih-blue);color:#fff;padding:12px 14px;cursor:pointer}.ihud-request-form button[disabled]{background:#94a3b8;cursor:not-allowed}.ihud-approved-box{border-radius:14px;background:#dff6e8;color:#16824d;padding:13px;font-size:14px}.ihud-notice{border-radius:14px;background:#fff8e1;color:#9a6500;padding:12px 14px;font-size:14px;border:1px solid #fde68a}.ihud-detail-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.ihud-detail{padding:18px}.ihud-detail-title{display:flex;align-items:center;gap:10px;margin-bottom:12px;color:var(--ih-blue);font-size:14px}.ihud-detail-icon{display:grid;place-items:center;width:34px;height:34px;border-radius:10px;background:#eaf4ff}.ihud-line{display:grid;grid-template-columns:20px 112px minmax(0,1fr);gap:8px;align-items:start;padding:7px 8px;border-radius:12px;color:#0f172a;text-decoration:none}.ihud-line:hover{background:#f3f9ff}.ihud-line svg{color:#4f8fbf;margin-top:2px}.ihud-line.locked svg{color:#cbd5e1}.ihud-line-label{font-size:11px;letter-spacing:.04em;text-transform:uppercase;color:#6a8ca5}.ihud-line-val{font-size:14px;color:#1f2937;overflow-wrap:anywhere}.ihud-line.locked .ihud-line-val{color:#94a3b8}.ihud-listings{padding:22px}.ihud-list-head{display:flex;justify-content:space-between;gap:16px;margin-bottom:18px;align-items:flex-start}.ihud-list-head h2{margin:0;font-size:20px;font-weight:400}.ihud-list-head p{margin:4px 0 0;color:var(--ih-muted);font-size:14px}.ihud-tools{display:flex;flex-wrap:wrap;gap:8px;align-items:center}.ihud-search{height:40px;display:flex;align-items:center;gap:8px;border:1px solid var(--ih-border);border-radius:14px;background:#f7fbff;padding:0 12px}.ihud-search input{border:0;background:transparent;outline:0;min-width:210px}.ihud-tab{border:0;border-radius:14px;padding:10px 14px;background:#eaf4ff;color:var(--ih-blue);cursor:pointer}.ihud-tab.active{background:var(--ih-blue);color:#fff}.ihud-list-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;overflow:visible}.ihud-list-card{border-radius:18px;padding:0;overflow:visible}.ihud-list-img{height:145px;position:relative;border-radius:18px 18px 0 0;background:#f4f7fa;overflow:hidden}.ihud-list-img img{width:100%;height:100%;object-fit:cover}.ihud-no-img{height:100%;display:grid;place-items:center;color:#cbd5e1;text-align:center}.ihud-status{position:absolute;left:12px;top:12px;border-radius:999px;background:#22c55e;color:#fff;font-size:12px;padding:6px 11px}.ihud-status.off{background:#f59e0b}.ihud-card-menu{position:absolute;right:12px;top:12px;z-index:99999}.ih-card-menu-btn{display:grid;place-items:center;width:32px;height:32px;border-radius:999px;border:0;background:rgba(255,255,255,.95);color:#475569;box-shadow:0 4px 14px rgba(15,23,42,.12);cursor:pointer}.ih-dropdown{right:0;position:absolute;top:40px;z-index:99999;min-width:150px;background:#fff;border:1px solid var(--ih-border);border-radius:12px;box-shadow:0 18px 40px rgba(30,95,138,.24);padding:5px 0;overflow:hidden}.ih-dropdown.hidden{display:none}.ih-dropdown-item{display:flex;align-items:center;gap:8px;width:100%;padding:9px 13px;font-size:13px;color:#334155;background:#fff;border:0;text-decoration:none;cursor:pointer;text-align:left}.ih-dropdown-item:hover{background:#eef7ff;color:var(--ih-blue)}.ih-dropdown-item.danger{color:#ef4444}.ih-dropdown-item.danger:hover{background:#fef2f2;color:#ef4444}.ihud-list-body{padding:15px}.ihud-list-title{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}.ihud-list-title h3{margin:0;font-size:15px;line-height:1.35;font-weight:500;color:#0f172a;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden}.ihud-type{flex:0 0 auto;border:1px solid var(--ih-border);border-radius:8px;padding:4px 8px;font-size:12px;color:#64748b;background:#fff}.ihud-company{font-size:12px;color:#94a3b8;margin:5px 0 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.ihud-dates{display:grid;grid-template-columns:1fr 1fr;gap:0;border-top:1px solid #edf2f7;margin-top:12px;padding-top:12px}.ihud-dates div+div{border-left:1px solid #edf2f7;padding-left:12px}.ihud-dates small{display:block;color:#6a8ca5;font-size:11px}.ihud-dates span{display:block;margin-top:4px;color:#334155;font-size:13px}.ihud-card-actions{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:14px}.ihud-card-actions a{display:flex;align-items:center;justify-content:center;gap:7px;border-radius:999px;padding:10px;font-size:13px;text-decoration:none}.ihud-primary{background:var(--ih-green);color:#fff}.ihud-secondary{border:1px solid var(--ih-border);color:#334155;background:#fff}.ihud-secondary:hover{background:#eef7ff;color:var(--ih-blue)}.ihud-empty{padding:40px;text-align:center;color:#94a3b8;border:1px dashed var(--ih-border);border-radius:18px;background:#f7fbff}.ihud-dev{padding:16px}.ihud-dev button{width:100%;display:flex;justify-content:space-between;align-items:center;border:0;background:transparent;color:var(--ih-blue);cursor:pointer}.ihud-dev-body{margin-top:14px;background:#f7fbff;border-radius:14px;padding:14px;color:#64748b;font-size:14px;line-height:1.6}.ihud-hidden{display:none!important}

/* ── NEW: Contact Requests Section ── */
.ihcr-section{padding:22px;}
.ihcr-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;gap:12px;}
.ihcr-head h2{margin:0;font-size:20px;font-weight:400;color:#0f172a;}
.ihcr-head p{margin:4px 0 0;color:var(--ih-muted);font-size:14px;}
.ihcr-table{width:100%;border-collapse:collapse;}
.ihcr-table th{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6a8ca5;padding:8px 12px;border-bottom:2px solid var(--ih-border);text-align:left;}
.ihcr-table td{padding:12px 12px;border-bottom:1px solid #f3f4f6;font-size:13px;color:#334155;vertical-align:middle;}
.ihcr-table tr:last-child td{border-bottom:none;}
.ihcr-table tr:hover td{background:#f7fbff;}
.ihcr-uid{font-family:monospace;font-weight:800;font-size:12px;background:#f0fdf4;color:#153F45;border:1px solid #bbf7d0;padding:3px 8px;border-radius:999px;white-space:nowrap;}
.ihcr-status{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;padding:4px 10px;border-radius:999px;white-space:nowrap;}
.ihcr-status.pending{background:#fef3c7;color:#92400e;}
.ihcr-status.approved{background:#dcfce7;color:#15803d;}
.ihcr-status.rejected{background:#fee2e2;color:#b91c1c;}
.ihcr-listing-type{font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;white-space:nowrap;}
.ihcr-listing-type.machine{background:#e0f2fe;color:#0369a1;}
.ihcr-listing-type.tool{background:#fef3c7;color:#92400e;}
.ihcr-listing-type.profile{background:#ede9fe;color:#6d28d9;}
.ihcr-actions{display:flex;gap:6px;}
.ihcr-approve-btn{background:#153F45;color:#fff;border:none;border-radius:50px;padding:6px 14px;font-size:12px;font-weight:700;cursor:pointer;transition:.15s;white-space:nowrap;}
.ihcr-approve-btn:hover{background:#0f2e33;}
.ihcr-reject-btn{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:50px;padding:6px 14px;font-size:12px;font-weight:700;cursor:pointer;transition:.15s;white-space:nowrap;}
.ihcr-reject-btn:hover{background:#fee2e2;}
.ihcr-done-badge{font-size:12px;color:#9ca3af;font-style:italic;}
.ihcr-empty{text-align:center;padding:32px 20px;color:#9ca3af;font-size:13px;}
.ihcr-requester-info{display:flex;flex-direction:column;gap:2px;}
.ihcr-requester-name{font-weight:600;color:#0f172a;}
.ihcr-requester-email{font-size:11px;color:#9ca3af;}

@media(max-width:1300px){.ihud-list-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.ihud-detail-grid{grid-template-columns:1fr 1fr}.ihud-hero-grid{grid-template-columns:1fr}}
@media(max-width:900px){.ihud-wrap{padding:12px 0}.ihud-profile-row,.ihud-list-head{flex-direction:column}.ihud-actions{grid-template-columns:repeat(2,minmax(0,1fr))}.ihud-access{grid-template-columns:1fr}.ihud-access-side{border-left:0;border-top:1px solid var(--ih-border)}.ihud-list-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.ihud-detail-grid{grid-template-columns:1fr}.ihud-search{width:100%}.ihud-search input{min-width:0;width:100%}.ihcr-table{font-size:12px;}}
@media(max-width:560px){.ihud-hero-main,.ihud-summary,.ihud-listings,.ihcr-section{padding:16px}.ihud-ident{align-items:flex-start}.ihud-avatar{width:64px;height:64px;font-size:20px}.ihud-title{font-size:24px}.ihud-actions,.ihud-list-grid{grid-template-columns:1fr}.ihud-line{grid-template-columns:20px 1fr}.ihud-line-label,.ihud-line-val{grid-column:2}.ihud-tools{display:grid;grid-template-columns:1fr 1fr;width:100%}.ihud-search{grid-column:1 / -1}.ihud-tab{width:100%}.ihcr-actions{flex-direction:column;}}
</style>

<div class="ihud-page <?php echo $has_sensitive_access ? 'unlocked' : 'locked'; ?>">
  <div class="ihud-wrap">
    <div class="ihud-breadcrumb">
      <div style="display:flex;align-items:center;gap:8px;min-width:0;">
        <a href="<?php echo esc_url( admin_url('admin.php?page=ih-users') ); ?>">Users</a>
        <span>›</span>
        <span style="color:#334155;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">User details</span>
      </div>
      <a class="ihud-close" href="<?php echo esc_url( admin_url('admin.php?page=ih-users') ); ?>" title="Close">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </a>
    </div>

    <?php if ( $notice ) : ?><div class="ihud-notice"><?php echo esc_html( $notice ); ?></div><?php endif; ?>

    <!-- HERO -->
    <section class="ihud-card ihud-hero">
      <div class="ihud-hero-grid">
        <div class="ihud-hero-main">
          <div class="ihud-profile-row">
            <div class="ihud-ident">
              <div class="ihud-avatar">
                <?php if ( $avatar_url ) : ?><img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $user->display_name ); ?>"><?php else : ?><?php echo esc_html( ihud_initials( $user->display_name ) ); ?><?php endif; ?>
              </div>
              <div style="min-width:0;">
                <div class="ihud-badges">
                  <span class="ihud-badge" style="background:#f0fdf4;color:#153F45;font-family:monospace;font-weight:700;letter-spacing:1px;border:1px solid #bbf7d0;">🪪 <?php echo esc_html( $user_code ); ?></span>
                  <span class="ihud-badge green">Unblocked</span>
                  <?php if ( $has_sensitive_access ) : ?><span class="ihud-badge green">Sensitive data approved</span><?php elseif ( $access_status_key === 'pending' ) : ?><span class="ihud-badge amber">Access request pending</span><?php elseif ( $access_status_key === 'rejected' ) : ?><span class="ihud-badge red">Access request rejected</span><?php else : ?><span class="ihud-badge blue">Sensitive data locked</span><?php endif; ?>
                  <?php if ( $viewer_is_admin && $pending_count > 0 ) : ?>
                  <span class="ihud-badge amber">⏳ <?php echo $pending_count; ?> pending request<?php echo $pending_count > 1 ? 's' : ''; ?></span>
                  <?php endif; ?>
                </div>
                <h1 class="ihud-title"><?php echo esc_html( $user->display_name ); ?></h1>
                <div class="ihud-sub">
                  <span><?php echo $has_sensitive_access ? esc_html( $user->user_email ) : esc_html( ihud_mask_value( $user->user_email, 'email' ) ); ?></span>
                  <?php if ( $phone ) : ?><span><?php echo $has_sensitive_access ? esc_html( $phone ) : esc_html( ihud_mask_value( $phone, 'phone' ) ); ?></span><?php endif; ?>
                  <?php if ( $city ) : ?><span><?php echo esc_html( $city ); ?></span><?php endif; ?>
                </div>
              </div>
            </div>
            <?php if ( ! $has_sensitive_access && ! $viewer_is_owner && ! $viewer_is_admin ) : ?>
              <form method="post" class="ihud-request-form">
                <?php wp_nonce_field( 'ihud_request_profile_access_' . $uid, 'ihud_nonce' ); ?>
                <input type="hidden" name="ihud_request_profile_access" value="1">
                <button type="submit" <?php disabled( $access_status_key === 'pending' ); ?>><?php echo $access_status_key === 'pending' ? 'Request sent' : 'Request data'; ?></button>
              </form>
            <?php else : ?>
              <span class="ihud-request-btn" style="background:<?php echo $has_sensitive_access ? '#16824d' : '#1e5f8a'; ?>;cursor:default;">Access approved</span>
            <?php endif; ?>
          </div>

          <div class="ihud-actions">
            <?php
              $actions = [
                [ 'Email', 'mailto:' . $user->user_email ],
                [ 'Call', $phone ? 'tel:' . preg_replace('/\s+/', '', $phone ) : '' ],
                [ 'WhatsApp', $whatsapp ? 'https://wa.me/' . ihud_clean_phone( $whatsapp ) : '' ],
                [ 'Website', $website ? ( preg_match( '#^https?://#', $website ) ? $website : 'https://' . $website ) : '' ],
                [ 'Map', trim( $address . ' ' . $city . ' ' . $postcode ) ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( trim( $address . ' ' . $city . ' ' . $postcode ) ) : '' ],
              ];
              foreach ( $actions as $action ) :
                $disabled = ! $has_sensitive_access || ! $action[1];
            ?>
              <a class="ihud-action <?php echo $disabled ? 'locked' : ''; ?>" href="<?php echo $disabled ? '#' : esc_url( $action[1] ); ?>" <?php echo ( ! $disabled && strpos( $action[1], 'http' ) === 0 ) ? 'target="_blank" rel="noreferrer"' : ''; ?>><?php echo $disabled ? 'Locked' : esc_html( $action[0] ); ?></a>
            <?php endforeach; ?>
          </div>
        </div>

        <aside class="ihud-summary">
          <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;">
            <div><div style="font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.65);">Profile completion</div><div style="font-size:32px;margin-top:4px;"><?php echo (int) $completion; ?>%</div></div>
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.7)" stroke-width="2"><path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3v8z"/><path d="m9 12 2 2 4-4"/></svg>
          </div>
          <div class="ihud-progress"><span style="width:<?php echo (int) $completion; ?>%;"></span></div>
          <div class="ihud-summary-list">
            <div><span>Joined</span><strong><?php echo esc_html( $joined ); ?></strong></div>
            <div><span>Business role</span><strong><?php echo esc_html( $business_role ?: '—' ); ?></strong></div>
            <div><span>Listings</span><strong><?php echo (int) $total_listings; ?></strong></div>
            <div><span>Platform ID</span><strong style="font-family:monospace;"><?php echo esc_html( $user_code ); ?></strong></div>
            <div><span>Data access</span><strong><?php echo esc_html( ucfirst( $access_status_key ) ); ?></strong></div>
            <?php if ( $viewer_is_admin && $pending_count > 0 ) : ?>
            <div><span>Pending requests</span><strong style="color:#fbbf24;"><?php echo $pending_count; ?> ↓ see below</strong></div>
            <?php endif; ?>
          </div>
        </aside>
      </div>
    </section>

    <!-- DETAIL GRID -->
    <section class="ihud-detail-grid">
      <div class="ihud-card ihud-detail">
        <div class="ihud-detail-title"><span class="ihud-detail-icon">◎</span>Public business info</div>
        <div class="ihud-line"><span></span><span class="ihud-line-label">Business Role</span><span class="ihud-line-val"><?php echo esc_html( $business_role ?: 'Not provided' ); ?></span></div>
        <div class="ihud-line"><span></span><span class="ihud-line-label">Job Title</span><span class="ihud-line-val"><?php echo esc_html( $job_title ?: 'Not provided' ); ?></span></div>
        <div class="ihud-line"><span></span><span class="ihud-line-label">City / Town</span><span class="ihud-line-val"><?php echo esc_html( $city ?: 'Not provided' ); ?></span></div>
        <div class="ihud-line"><span></span><span class="ihud-line-label">Joined</span><span class="ihud-line-val"><?php echo esc_html( $joined ); ?></span></div>
        <div class="ihud-line"><span></span><span class="ihud-line-label">Platform ID</span><span class="ihud-line-val" style="font-family:monospace;font-weight:700;color:#153F45;"><?php echo esc_html( $user_code ); ?></span></div>
      </div>
      <div class="ihud-card ihud-detail">
        <div class="ihud-detail-title"><span class="ihud-detail-icon">⌕</span>Protected company &amp; contact</div>
        <div class="ihud-line <?php echo $has_sensitive_access ? '' : 'locked'; ?>"><span></span><span class="ihud-line-label">Company</span><span class="ihud-line-val"><?php echo esc_html( $has_sensitive_access ? ( $company ?: 'Not provided' ) : ihud_mask_value( $company, 'company' ) ); ?></span></div>
        <div class="ihud-line <?php echo $has_sensitive_access ? '' : 'locked'; ?>"><span></span><span class="ihud-line-label">Email</span><span class="ihud-line-val"><?php echo esc_html( $has_sensitive_access ? $user->user_email : ihud_mask_value( $user->user_email, 'email' ) ); ?></span></div>
        <div class="ihud-line <?php echo $has_sensitive_access ? '' : 'locked'; ?>"><span></span><span class="ihud-line-label">Office</span><span class="ihud-line-val"><?php echo esc_html( $has_sensitive_access ? ( $phone ?: 'Not provided' ) : ihud_mask_value( $phone, 'phone' ) ); ?></span></div>
        <div class="ihud-line <?php echo $has_sensitive_access ? '' : 'locked'; ?>"><span></span><span class="ihud-line-label">WhatsApp</span><span class="ihud-line-val"><?php echo esc_html( $has_sensitive_access ? ( $whatsapp ?: 'Not provided' ) : ihud_mask_value( $whatsapp, 'phone' ) ); ?></span></div>
        <div class="ihud-line <?php echo $has_sensitive_access ? '' : 'locked'; ?>"><span></span><span class="ihud-line-label">Website</span><span class="ihud-line-val"><?php echo esc_html( $has_sensitive_access ? ( $website ?: 'Not provided' ) : ihud_mask_value( $website, 'website' ) ); ?></span></div>
      </div>
      <div class="ihud-card ihud-detail">
        <div class="ihud-detail-title"><span class="ihud-detail-icon">⌖</span>Protected location</div>
        <div class="ihud-line <?php echo $has_sensitive_access ? '' : 'locked'; ?>"><span></span><span class="ihud-line-label">Address</span><span class="ihud-line-val"><?php echo esc_html( $has_sensitive_access ? ( $address ?: 'Not provided' ) : ihud_mask_value( $address, 'address' ) ); ?></span></div>
        <div class="ihud-line"><span></span><span class="ihud-line-label">City / Town</span><span class="ihud-line-val"><?php echo esc_html( $city ?: 'Not provided' ); ?></span></div>
        <div class="ihud-line <?php echo $has_sensitive_access ? '' : 'locked'; ?>"><span></span><span class="ihud-line-label">Postcode</span><span class="ihud-line-val"><?php echo esc_html( $has_sensitive_access ? ( $postcode ?: 'Not provided' ) : ihud_mask_value( $postcode, 'address' ) ); ?></span></div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;"><span class="ihud-badge green">Terms accepted</span><span class="ihud-badge green">Fees understood</span></div>
      </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         CONTACT REQUESTS SECTION (admin only)
         Shows who wants to see this user's listing contact details
    ═══════════════════════════════════════════════════════════ -->
    <?php if ( $viewer_is_admin ) : ?>
    <section class="ihud-card" id="ihContactRequestsSection">
      <div class="ihcr-section">
        <div class="ihcr-head">
          <div>
            <h2>
              Contact Requests
              <?php if ( $pending_count > 0 ) : ?>
              <span style="background:#fef3c7;color:#92400e;font-size:12px;font-weight:700;padding:3px 10px;border-radius:999px;margin-left:8px;vertical-align:middle;"><?php echo $pending_count; ?> pending</span>
              <?php endif; ?>
            </h2>
            <p>Users who want to see <?php echo esc_html($user->display_name); ?>'s listing contact details.</p>
          </div>
          <div style="font-size:12px;color:var(--ih-muted);">
            <?php echo count($contact_requests_received); ?> total request<?php echo count($contact_requests_received) !== 1 ? 's' : ''; ?>
          </div>
        </div>

        <?php if ( empty($contact_requests_received) ) : ?>
        <div class="ihcr-empty">
          <div style="font-size:32px;margin-bottom:8px;">📋</div>
          No contact requests yet for this user's listings.
        </div>
        <?php else : ?>
        <div style="overflow-x:auto;">
          <table class="ihcr-table">
            <thead>
              <tr>
                <th>Requester</th>
                <th>Listing</th>
                <th>Type</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ( $contact_requests_received as $cr ) :
                $cr_uid = get_user_meta( (int)$cr['requester_id'], 'ih_unique_id', true )
                    ?: 'ID#' . str_pad($cr['requester_id'], 6, '0', STR_PAD_LEFT);
                $cr_status     = strtolower( $cr['status'] );
                $cr_type_clean = str_replace('_contact', '', $cr['listing_type']);
                $cr_date       = !empty($cr['request_date']) ? date_i18n('d M Y', strtotime($cr['request_date'])) : '—';
                $cr_detail_url = admin_url('admin.php?page=ih-users&view=' . (int)$cr['requester_id']);
              ?>
              <tr id="ihcr-row-<?php echo (int)$cr['id']; ?>">
                <!-- Requester -->
                <td>
                  <div class="ihcr-requester-info">
                    <div class="ihcr-requester-name">
                      <a href="<?php echo esc_url($cr_detail_url); ?>" style="color:#153F45;text-decoration:none;" title="View user profile">
                        <?php echo esc_html($cr['requester_name'] ?? 'Unknown'); ?>
                      </a>
                    </div>
                    <span class="ihcr-uid"><?php echo esc_html($cr_uid); ?></span>
                    <div class="ihcr-requester-email"><?php echo esc_html($cr['requester_email'] ?? ''); ?></div>
                  </div>
                </td>
                <!-- Listing title -->
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo esc_attr($cr['listing_title']??''); ?>">
                  <?php echo esc_html($cr['listing_title'] ?? 'Unknown listing'); ?>
                </td>
                <!-- Type badge -->
                <td>
                  <span class="ihcr-listing-type <?php echo esc_attr($cr_type_clean); ?>">
                    <?php echo esc_html(ucfirst($cr_type_clean)); ?>
                  </span>
                </td>
                <!-- Date -->
                <td style="white-space:nowrap;"><?php echo esc_html($cr_date); ?></td>
                <!-- Status -->
                <td>
                  <span class="ihcr-status <?php echo esc_attr($cr_status); ?>">
                    <?php
                      echo $cr_status === 'approved' ? '✓ Approved'
                         : ($cr_status === 'rejected' ? '✕ Rejected'
                         : '⏳ Pending');
                    ?>
                  </span>
                </td>
                <!-- Action buttons -->
                <td>
                  <?php if ( $cr_status === 'pending' ) : ?>
                  <div class="ihcr-actions">
                    <button class="ihcr-approve-btn"
                            onclick="ihcrDecide(<?php echo (int)$cr['id']; ?>,'Approved',this)">
                      ✓ Approve
                    </button>
                    <button class="ihcr-reject-btn"
                            onclick="ihcrDecide(<?php echo (int)$cr['id']; ?>,'Rejected',this)">
                      ✕ Reject
                    </button>
                  </div>
                  <?php else : ?>
                  <span class="ihcr-done-badge">—</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- LISTINGS -->
    <section class="ihud-card ihud-listings">
      <div class="ihud-list-head">
        <div><h2>User listings</h2><p>Machines and tools owned by this user.</p></div>
        <div class="ihud-tools">
          <label class="ihud-search"><span>⌕</span><input id="ihudListingSearch" type="search" placeholder="Search listings..."></label>
          <button class="ihud-tab active" type="button" data-filter="all">All</button>
          <button class="ihud-tab" type="button" data-filter="machine">Machines</button>
          <button class="ihud-tab" type="button" data-filter="tool">Tools</button>
        </div>
      </div>
      <?php if ( empty( $cards ) ) : ?>
        <div class="ihud-empty">No listings found for this user.</div>
      <?php else : ?>
      <div class="ihud-list-grid" id="ihudListingsGrid">
        <?php foreach ( $cards as $item ) :
            $status_is_available = stripos($item['status'],'available')!==false||stripos($item['status'],'approved')!==false;
        ?>
        <article class="ihud-card ihud-list-card ih-listing-card" data-type="<?php echo esc_attr($item['type']); ?>" data-search="<?php echo esc_attr(strtolower($item['title'].' '.$item['company'].' '.$item['type_label'].' '.$item['status'])); ?>">
          <div class="ihud-list-img">
            <?php if ($item['thumb']): ?><img src="<?php echo esc_url($item['thumb']); ?>" alt="<?php echo esc_attr($item['title']); ?>"><?php else: ?><div class="ihud-no-img"><div><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg><div style="font-size:12px;margin-top:6px;">No image</div></div></div><?php endif; ?>
            <span class="ihud-status <?php echo $status_is_available?'':'off'; ?>"><?php echo esc_html($item['status']); ?></span>
            <div class="ihud-card-menu">
              <button class="ih-card-menu-btn" type="button"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg></button>
              <div class="ih-dropdown hidden">
                <a href="<?php echo esc_url($item['detail_url']); ?>" class="ih-dropdown-item">View</a>
                <?php if ($can_manage_listings): ?><a href="<?php echo esc_url($item['edit_url']); ?>" class="ih-dropdown-item">Edit</a><button type="button" class="ih-dropdown-item danger <?php echo $item['type']==='machine'?'ih-delete-machine':'ih-delete-tool'; ?>" data-id="<?php echo (int)$item['id']; ?>">Delete</button><?php else: ?><a href="<?php echo esc_url($item['message_url']); ?>" class="ih-dropdown-item">Message</a><?php endif; ?>
              </div>
            </div>
          </div>
          <div class="ihud-list-body">
            <div class="ihud-list-title"><h3><?php echo esc_html($item['title']); ?></h3><?php if ($item['type_label']): ?><span class="ihud-type"><?php echo esc_html($item['type_label']); ?></span><?php endif; ?></div>
            <div class="ihud-company"><?php echo esc_html($item['company']); ?></div>
            <div class="ihud-dates"><div><small>Listing Date</small><span><?php echo esc_html($item['list_date']); ?></span></div><div><small>Expiry Date</small><span><?php echo esc_html($item['exp_date']); ?></span></div></div>
            <div class="ihud-card-actions">
              <a class="ihud-primary" href="<?php echo esc_url($item['detail_url']); ?>">Details</a>
              <?php if ($can_manage_listings): ?><a class="ihud-secondary" href="<?php echo esc_url($item['edit_url']); ?>">Edit</a><?php else: ?><a class="ihud-secondary" href="<?php echo esc_url($item['message_url']); ?>">Message</a><?php endif; ?>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
      <div class="ihud-empty ihud-hidden" id="ihudNoListings">No listings match your search.</div>
      <?php endif; ?>
    </section>

  </div>
</div>

<script>
var ihcrAjaxUrl    = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
var ihcrAdminNonce = '<?php echo esc_js($approve_nonce); ?>';

/* ── Approve / Reject from user detail page ── */
function ihcrDecide(requestId, decision, btn) {
    btn.disabled = true;
    btn.textContent = decision === 'Approved' ? 'Approving…' : 'Rejecting…';

    var fd = new FormData();
    fd.append('action',     'ih_listing_contact_approve');
    fd.append('nonce',      ihcrAdminNonce);
    fd.append('request_id', requestId);
    fd.append('decision',   decision);

    fetch(ihcrAjaxUrl, {method:'POST', body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
        if (d && d.success) {
            var row = document.getElementById('ihcr-row-' + requestId);
            if (row) {
                /* Update status badge */
                var statusCell = row.querySelector('.ihcr-status');
                if (statusCell) {
                    statusCell.className = 'ihcr-status ' + decision.toLowerCase();
                    statusCell.textContent = decision === 'Approved' ? '✓ Approved' : '✕ Rejected';
                }
                /* Remove action buttons */
                var actCell = row.querySelector('.ihcr-actions');
                if (actCell) actCell.innerHTML = '<span class="ihcr-done-badge">—</span>';
            }
            /* Update summary badge in hero */
            var heroBadge = document.querySelector('.ihud-summary-list strong[style*="fbbf24"]');
            if (heroBadge) {
                var pendingNow = document.querySelectorAll('.ihcr-status.pending').length;
                if (pendingNow === 0) heroBadge.closest('div').remove();
                else heroBadge.textContent = pendingNow + ' ↓ see below';
            }
        } else {
            alert('Error: ' + (d.data || 'Could not update request.'));
            btn.disabled = false;
            btn.textContent = decision === 'Approved' ? '✓ Approve' : '✕ Reject';
        }
    })
    .catch(function(){ alert('Network error. Please try again.'); btn.disabled = false; });
}

/* ── Listing search & filter ── */
(function(){
    var grid   = document.getElementById('ihudListingsGrid');
    var search = document.getElementById('ihudListingSearch');
    var tabs   = document.querySelectorAll('.ihud-tab');
    var empty  = document.getElementById('ihudNoListings');
    var activeType = 'all';
    function applyFilter(){
        if(!grid) return;
        var q = search ? String(search.value||'').toLowerCase() : '';
        var shown = 0;
        grid.querySelectorAll('.ihud-list-card').forEach(function(card){
            var type = card.getAttribute('data-type')||'';
            var text = card.getAttribute('data-search')||'';
            var ok = (activeType==='all'||type===activeType) && (!q||text.indexOf(q)!==-1);
            card.classList.toggle('ihud-hidden', !ok);
            if(ok) shown++;
        });
        if(empty) empty.classList.toggle('ihud-hidden', shown>0);
    }
    tabs.forEach(function(btn){
        btn.addEventListener('click', function(){
            tabs.forEach(function(t){t.classList.remove('active');});
            btn.classList.add('active');
            activeType = btn.getAttribute('data-filter')||'all';
            applyFilter();
        });
    });
    if(search) search.addEventListener('input', applyFilter);

    document.addEventListener('click', function(e){
        var menuBtn = e.target.closest('.ih-card-menu-btn');
        if(menuBtn){
            e.preventDefault(); e.stopPropagation();
            var wrap = menuBtn.closest('.ihud-list-card');
            var dropdown = menuBtn.nextElementSibling;
            document.querySelectorAll('.ih-dropdown').forEach(function(d){if(d!==dropdown)d.classList.add('hidden');});
            document.querySelectorAll('.ihud-list-card').forEach(function(c){if(c!==wrap)c.style.zIndex='';});
            if(dropdown){ var open=!dropdown.classList.contains('hidden'); dropdown.classList.toggle('hidden',open); if(wrap) wrap.style.zIndex=open?'':'999'; }
            return;
        }
        if(!e.target.closest('.ih-dropdown')){
            document.querySelectorAll('.ih-dropdown').forEach(function(d){d.classList.add('hidden');});
            document.querySelectorAll('.ihud-list-card').forEach(function(c){c.style.zIndex='';});
        }
    });

    /* Scroll to contact requests if pending */
    <?php if ( $viewer_is_admin && $pending_count > 0 ) : ?>
    var crSection = document.getElementById('ihContactRequestsSection');
    if (crSection && window.location.hash !== '#ihContactRequestsSection') {
        crSection.style.border = '2px solid #fbbf24';
        crSection.style.borderRadius = '22px';
    }
    <?php endif; ?>
})();
</script>

<?php
$content = ob_get_clean();
$title   = 'User Details — ' . $user->display_name;
include IH_DIR . 'pages/layout.php';