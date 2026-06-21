<?php
/**
 * Admin Messaging Centre — Nexus 3-pane (Figma 196:2199)
 */
defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Access denied', 'insight-hub-dashboard' ) );
}

global $wpdb;
$pfx      = $wpdb->prefix;
$admin_id = get_current_user_id();
$nonce    = wp_create_nonce( 'ih_nonce' );

if ( ! function_exists( 'ih_am_initials' ) ) {
	function ih_am_initials( $name ) {
		$p = preg_split( '/\s+/', trim( $name ?: 'User' ) );
		return strtoupper( substr( $p[0] ?? 'U', 0, 1 ) . substr( $p[1] ?? '', 0, 1 ) );
	}
}
if ( ! function_exists( 'ih_am_reqref' ) ) {
	function ih_am_reqref( $r ) {
		return 'REQ-' . date( 'Y', strtotime( $r['request_date'] ?? 'now' ) ) . '-' . str_pad( (string) ( $r['id'] ?? 0 ), 4, '0', STR_PAD_LEFT );
	}
}
if ( ! function_exists( 'ih_am_listing_ref' ) ) {
	function ih_am_listing_ref( $type, $id ) {
		if ( function_exists( 'ih_request_listing_ref' ) ) {
			return ih_request_listing_ref( $type, $id );
		}
		$id = (int) $id;
		return ( $type === 'tool' ? 'TL-' : 'MCH-' ) . str_pad( (string) $id, 5, '0', STR_PAD_LEFT );
	}
}
if ( ! function_exists( 'ih_am_user_name' ) ) {
	function ih_am_user_name( $uid ) {
		return function_exists( 'ih_resolve_user_name' ) ? ih_resolve_user_name( $uid ) : ( 'User #' . (int) $uid );
	}
}
if ( ! function_exists( 'ih_am_wa' ) ) {
	function ih_am_wa( $uid ) {
		$n = get_user_meta( $uid, 'ihur_whatsappNumber', true ) ?: get_user_meta( $uid, 'ih_phone', true ) ?: get_user_meta( $uid, 'whatsappNumber', true );
		return preg_replace( '/[^0-9]/', '', (string) $n );
	}
}
if ( ! function_exists( 'ih_am_date_label' ) ) {
	function ih_am_date_label( $sent_at ) {
		if ( ! $sent_at ) {
			return '';
		}
		$ts        = strtotime( get_date_from_gmt( $sent_at ) );
		$today     = strtotime( 'today', current_time( 'timestamp' ) );
		$yesterday = strtotime( 'yesterday', current_time( 'timestamp' ) );
		if ( $ts >= $today ) {
			return __( 'TODAY', 'insight-hub-dashboard' );
		}
		if ( $ts >= $yesterday ) {
			return __( 'YESTERDAY', 'insight-hub-dashboard' );
		}
		return strtoupper( date_i18n( 'M j, Y', $ts ) );
	}
}

$threads = $wpdb->get_results( "SELECT * FROM {$pfx}ih_threads ORDER BY last_time DESC", ARRAY_A ) ?: array();

$active_uid = intval( $_GET['user_id'] ?? 0 );

$active_thread = 0;
$active_thread_row = null;
foreach ( $threads as $t ) {
	if ( intval( $t['user_id'] ) === $active_uid ) {
		$active_thread     = intval( $t['id'] );
		$active_thread_row = $t;
		break;
	}
}

$active_user = $active_uid ? get_userdata( $active_uid ) : null;
$messages         = array();
$last_id          = 0;
$thread_unread    = $active_thread_row ? intval( $active_thread_row['unread'] ?? 0 ) : 0;
$first_unread_id  = 0;

if ( $active_thread ) {
	$messages = $wpdb->get_results(
		$wpdb->prepare( "SELECT * FROM {$pfx}ih_chats WHERE thread_id=%d ORDER BY id ASC", $active_thread ),
		ARRAY_A
	) ?: array();
	if ( $thread_unread > 0 && $messages ) {
		$incoming = array();
		foreach ( $messages as $m ) {
			if ( intval( $m['from_me'] ) !== 0 ) {
				continue;
			}
			$body = (string) ( $m['message'] ?? '' );
			if ( function_exists( 'ih_chat_detect_message_type' ) && ih_chat_detect_message_type( $body ) === 'system' ) {
				continue;
			}
			$incoming[] = $m;
		}
		$idx = max( 0, count( $incoming ) - $thread_unread );
		if ( ! empty( $incoming[ $idx ] ) ) {
			$first_unread_id = intval( $incoming[ $idx ]['id'] );
		}
	}
	$wpdb->update( $pfx . 'ih_threads', array( 'unread' => 0 ), array( 'id' => $active_thread ) );
	if ( $messages ) {
		$last_id = intval( $messages[ count( $messages ) - 1 ]['id'] );
	}
}

$ih_group_participants = ( $active_thread && function_exists( 'ih_chat_thread_participants' ) )
	? ih_chat_thread_participants( $active_thread )
	: array();
$ih_is_group_chat      = count( $ih_group_participants ) >= 3;
$ih_group_title        = $ih_is_group_chat && function_exists( 'ih_chat_group_title' )
	? ih_chat_group_title( $ih_group_participants )
	: '';
$ih_group_online       = 0;
$ih_participant_map    = array();
foreach ( $ih_group_participants as $ih_gp ) {
	if ( ! empty( $ih_gp['online'] ) ) {
		$ih_group_online++;
	}
	$ih_participant_map[ (int) $ih_gp['id'] ] = $ih_gp;
}
$ih_use_group_demo = empty( $threads ) || ! empty( $_GET['group_demo'] );

$requests = $active_uid ? ( $wpdb->get_results(
	$wpdb->prepare( "SELECT * FROM {$pfx}ih_requests WHERE user_id=%d ORDER BY id DESC LIMIT 12", $active_uid ),
	ARRAY_A
) ?: array() ) : array();

$pending_req = null;
$thread_listing_id   = $active_thread_row ? (int) ( $active_thread_row['listing_id'] ?? 0 ) : 0;
$thread_listing_type = $active_thread_row ? sanitize_key( (string) ( $active_thread_row['listing_type'] ?? '' ) ) : '';
if ( $thread_listing_id && $thread_listing_type ) {
	foreach ( $requests as $r ) {
		if ( strtolower( trim( $r['status'] ?? '' ) ) !== 'pending' ) {
			continue;
		}
		if ( (int) ( $r['listing_id'] ?? 0 ) === $thread_listing_id
			&& sanitize_key( (string) ( $r['listing_type'] ?? '' ) ) === $thread_listing_type ) {
			$pending_req = $r;
			break;
		}
	}
}
if ( ! $pending_req ) {
	foreach ( $requests as $r ) {
		if ( strtolower( trim( $r['status'] ?? '' ) ) === 'pending' ) {
			$pending_req = $r;
			break;
		}
	}
}

$req_stats = array(
	'approved'  => 0,
	'pending'   => 0,
	'rejected'  => 0,
	'completed' => 0,
);
foreach ( $requests as $r ) {
	$s = strtolower( trim( $r['status'] ?? '' ) );
	if ( isset( $req_stats[ $s ] ) ) {
		$req_stats[ $s ]++;
	}
}

$listing_type = ( $active_thread_row['listing_type'] ?? '' ) === 'tool' ? 'tool' : 'machine';
$listing_id   = intval( $active_thread_row['listing_id'] ?? 0 );
if ( ! $listing_id && $pending_req ) {
	$listing_type = sanitize_key( $pending_req['listing_type'] ?? 'machine' );
	$listing_id   = intval( $pending_req['listing_id'] ?? 0 );
}
$listing_ref  = $listing_id ? ih_am_listing_ref( $listing_type, $listing_id ) : '';
$listing_url  = '';
if ( $listing_id ) {
	$listing_url = $listing_type === 'tool'
		? admin_url( 'admin.php?page=ih-tool-detail&tool_id=' . $listing_id )
		: admin_url( 'admin.php?page=ih-machine-detail&machine_id=' . $listing_id );
}

$shared_files = array();
if ( $active_thread ) {
	$chat_cols = $wpdb->get_col( "SHOW COLUMNS FROM {$pfx}ih_chats" );
	if ( in_array( 'attachment_url', $chat_cols, true ) ) {
		$shared_files = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT attachment_url, attachment_type, attachment_name FROM {$pfx}ih_chats WHERE thread_id=%d AND attachment_url IS NOT NULL AND attachment_url<>'' ORDER BY id DESC LIMIT 6",
				$active_thread
			),
			ARRAY_A
		) ?: array();
	}
}

$bars = array_fill( 0, 7, 0 );
foreach ( $messages as $m ) {
	$w = ( (int) date( 'N', strtotime( $m['sent_at'] ) ) ) - 1;
	if ( $w >= 0 && $w < 7 ) {
		$bars[ $w ]++;
	}
}
$bmax = max( 1, max( $bars ) );
$dl   = array( 'M', 'T', 'W', 'T', 'F', 'S', 'S' );

$admin_sent = 0;
$user_sent  = 0;
foreach ( $messages as $m ) {
	if ( intval( $m['from_me'] ) === 1 ) {
		$admin_sent++;
	} else {
		$user_sent++;
	}
}

/* ── Conversation stats (Figma 196:2199 right rail) ──────────────────────────
 * Computed cheaply from the already-loaded $messages — no extra queries.
 *   messages_total : total chat messages in the thread (system rows excluded)
 *   avg_reply      : mean time from an inbound user message to the admin's reply
 *   requests_total : access requests this user has filed
 */
$msg_total      = 0;
$reply_deltas   = array();
$awaiting_reply = null;
foreach ( $messages as $m ) {
	$body = (string) ( $m['message'] ?? '' );
	if ( function_exists( 'ih_chat_detect_message_type' ) && ih_chat_detect_message_type( $body ) === 'system' ) {
		continue;
	}
	$msg_total++;
	$ts = strtotime( $m['sent_at'] ?? '' );
	if ( ! $ts ) {
		continue;
	}
	if ( intval( $m['from_me'] ) === 1 ) {
		if ( null !== $awaiting_reply ) {
			$reply_deltas[] = max( 0, $ts - $awaiting_reply );
			$awaiting_reply = null;
		}
	} elseif ( null === $awaiting_reply ) {
		$awaiting_reply = $ts;
	}
}
$avg_reply_label = '—';
if ( ! empty( $reply_deltas ) ) {
	$avg = array_sum( $reply_deltas ) / count( $reply_deltas );
	if ( $avg < 60 ) {
		$avg_reply_label = '<1m';
	} elseif ( $avg < 3600 ) {
		$avg_reply_label = round( $avg / 60 ) . 'm';
	} elseif ( $avg < 86400 ) {
		$avg_reply_label = round( $avg / 3600 ) . 'h';
	} else {
		$avg_reply_label = round( $avg / 86400 ) . 'd';
	}
}
$requests_total = count( $requests );

/* Access & controls (Figma) — support-chat has no per-thread access store, so
 * these mirror the design as read-only indicators (capability-gated, admin-only
 * page). Attachments are always enabled for support threads. */
$access_toggles = array(
	array(
		'label' => __( 'Profile access', 'insight-hub-dashboard' ),
		'sub'   => __( 'Admin can always view this profile', 'insight-hub-dashboard' ),
		'on'    => true,
	),
	array(
		'label' => __( 'Listing access', 'insight-hub-dashboard' ),
		'sub'   => $listing_ref ? sprintf( __( '%s linked to this conversation', 'insight-hub-dashboard' ), $listing_ref ) : __( 'No listing linked', 'insight-hub-dashboard' ),
		'on'    => (bool) $listing_id,
	),
	array(
		'label' => __( 'Allow attachments', 'insight-hub-dashboard' ),
		'sub'   => __( 'Images, docs & video enabled', 'insight-hub-dashboard' ),
		'on'    => true,
	),
);

$handled = $req_stats['approved'] + $req_stats['rejected'] + $req_stats['completed'];
$unread_total = 0;
foreach ( $threads as $t ) {
	$unread_total += intval( $t['unread'] ?? 0 );
}

$a_name   = $active_user ? ( $active_user->display_name ?: $active_user->user_login ) : __( 'Select a conversation', 'insight-hub-dashboard' );
$a_comp   = $active_uid ? ( get_user_meta( $active_uid, 'ihur_companyName', true ) ?: get_user_meta( $active_uid, 'company', true ) ) : '';
$a_wa     = $active_uid ? ih_am_wa( $active_uid ) : '';
$a_since  = $active_user ? date_i18n( 'M Y', strtotime( $active_user->user_registered ) ) : '';
$is_blocked = $active_uid && function_exists( 'ih_is_blocked' ) ? ih_is_blocked( $active_uid ) : false;

$has_att = in_array( 'attachment_url', $wpdb->get_col( "SHOW COLUMNS FROM {$pfx}ih_chats" ), true );

$reactions_map = array();
if ( ! empty( $messages ) && function_exists( 'ih_chat_reactions_for_messages' ) ) {
	$reactions_map = ih_chat_reactions_for_messages(
		array_map(
			static function ( $m ) {
				return (int) ( $m['id'] ?? 0 );
			},
			$messages
		),
		$admin_id
	);
}

$thread_meta = array();
foreach ( $threads as $t ) {
	$uid   = intval( $t['user_id'] );
	$pend  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$pfx}ih_requests WHERE user_id=%d AND LOWER(TRIM(status))='pending'", $uid ) );
	$tl_id = intval( $t['listing_id'] ?? 0 );
	$thread_meta[ $uid ] = array(
		'pending' => $pend,
		'support' => $tl_id <= 0 && $pend <= 0,
	);
}

ob_start();
?>
<div class="ih-admin-messages-page">
<div class="ihc">
	<div class="ihc-area<?php echo $active_uid ? ' in-chat' : ''; ?>" id="ihcArea">

		<aside class="ihc-p1">
			<div class="ihc-p1-head">
				<div class="ihc-p1-title">
					<h2><?php esc_html_e( 'Messages', 'insight-hub-dashboard' ); ?></h2>
					<?php if ( $unread_total > 0 ) : ?>
						<span class="ihc-pill"><?php echo (int) $unread_total; ?> <?php esc_html_e( 'new', 'insight-hub-dashboard' ); ?></span>
					<?php endif; ?>
				</div>
				<div class="ihc-search">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#9aa0b4" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
					<input type="search" id="ihcThreadSearch" placeholder="<?php esc_attr_e( 'Search conversations', 'insight-hub-dashboard' ); ?>" autocomplete="off">
				</div>
				<div class="ihc-tabs" role="tablist">
					<button type="button" class="ihc-tab on" data-filter="all"><?php esc_html_e( 'All', 'insight-hub-dashboard' ); ?></button>
					<button type="button" class="ihc-tab" data-filter="unread"><?php esc_html_e( 'Unread', 'insight-hub-dashboard' ); ?></button>
					<button type="button" class="ihc-tab" data-filter="requests"><?php esc_html_e( 'Requests', 'insight-hub-dashboard' ); ?></button>
					<button type="button" class="ihc-tab" data-filter="support"><?php esc_html_e( 'Support', 'insight-hub-dashboard' ); ?></button>
				</div>
			</div>
			<div class="ihc-threads" id="ihcThreads">
				<?php if ( empty( $threads ) ) : ?>
					<div class="ihc-empty"><?php esc_html_e( 'No conversations yet. Users will appear here when they message support.', 'insight-hub-dashboard' ); ?></div>
				<?php else : ?>
					<?php foreach ( $threads as $t ) :
						$uid     = intval( $t['user_id'] );
						$nm      = ih_am_user_name( $uid );
						$unread  = intval( $t['unread'] ?? 0 );
						$meta    = $thread_meta[ $uid ] ?? array( 'pending' => 0, 'support' => true );
						$preview = wp_trim_words( $t['last_message'] ?? '', 8, '…' );
						$t_time  = ! empty( $t['last_time'] ) ? date_i18n( 'g:i A', strtotime( get_date_from_gmt( $t['last_time'] ) ) ) : '';
						$active  = ( $uid === $active_uid );
						$href    = admin_url( 'admin.php?page=ih-messages&user_id=' . $uid );
						?>
						<a class="ihc-thread<?php echo $active ? ' on' : ''; ?><?php echo $unread ? ' unread' : ''; ?>"
							href="<?php echo esc_url( $href ); ?>"
							data-enter-chat
							data-name="<?php echo esc_attr( strtolower( $nm ) ); ?>"
							data-unread="<?php echo $unread ? '1' : '0'; ?>"
							data-req="<?php echo ! empty( $meta['pending'] ) ? '1' : '0'; ?>"
							data-support="<?php echo ! empty( $meta['support'] ) ? '1' : '0'; ?>">
							<span class="ihc-ava" style="width:44px;height:44px;font-size:16px"><?php echo esc_html( ih_am_initials( $nm ) ); ?><span class="ihc-online off" data-uid="<?php echo (int) $uid; ?>"></span></span>
							<span class="tx">
								<span class="r1">
									<span class="nm"><?php echo esc_html( $nm ); ?> · USR-<?php echo (int) $uid; ?></span>
									<span class="tm"><?php echo esc_html( $t_time ); ?></span>
								</span>
								<span class="r2">
									<span class="last"><?php echo esc_html( $preview ?: __( 'Start a conversation', 'insight-hub-dashboard' ) ); ?></span>
									<?php if ( ! empty( $meta['pending'] ) ) : ?>
										<span class="ihc-reqtag"><?php esc_html_e( 'Request', 'insight-hub-dashboard' ); ?></span>
									<?php elseif ( $unread > 0 ) : ?>
										<span class="ihc-unb"><?php echo (int) $unread; ?></span>
									<?php endif; ?>
								</span>
							</span>
						</a>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</aside>

		<section class="ihc-p2 ih-msg-group-card<?php echo $active_uid ? '' : ' is-empty'; ?>" id="ihcP2">
			<div class="ih-admin-msg-empty" id="ihAdminMsgEmpty"<?php echo $active_uid ? ' hidden' : ''; ?>>
				<?php include IH_DIR . 'pages/partials/ih-admin-msg-empty.php'; ?>
			</div>
			<header class="ihc-chat-head<?php echo $ih_is_group_chat ? ' is-group' : ''; ?><?php echo $active_uid ? '' : ' ihc-hide'; ?>" id="ihcChatHead">
				<button type="button" class="ihc-back" id="ihcBack" aria-label="<?php esc_attr_e( 'Back to conversations', 'insight-hub-dashboard' ); ?>">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1a1c2b" stroke-width="2.2" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
				</button>
				<div class="ihc-chat-head-identity">
					<span class="ihc-chat-avatar-single" id="ihcAvatarSingle"<?php echo $ih_is_group_chat ? ' hidden' : ''; ?>>
						<span class="ihc-ava" style="width:42px;height:42px;font-size:16px"><?php echo esc_html( ih_am_initials( $a_name ) ); ?><span class="ihc-online off" data-uid="<?php echo (int) $active_uid; ?>"></span></span>
					</span>
					<div class="ih-msg-avatar-stack" id="ihcAvatarStack"<?php echo $ih_is_group_chat ? '' : ' hidden'; ?>>
						<?php
						$stack = array_slice( $ih_group_participants, 0, 3 );
						foreach ( $stack as $si => $sp ) :
							?>
						<span class="ih-msg-avatar-stack-item" style="--ih-av-color:<?php echo esc_attr( $sp['avatar_color'] ); ?>;z-index:<?php echo esc_attr( 3 - $si ); ?>;" title="<?php echo esc_attr( $sp['name'] ); ?>">
							<?php echo esc_html( $sp['initials'] ); ?>
						</span>
						<?php endforeach; ?>
					</div>
					<div class="ci">
						<div class="nm" id="ihcChatTitle"><?php echo esc_html( $ih_is_group_chat ? $ih_group_title : ( $a_name . ( $active_uid ? ' · USR-' . $active_uid : '' ) ) ); ?></div>
						<div class="ihc-presence<?php echo $ih_is_group_chat ? ' ihc-hide' : ' off'; ?>" data-presence id="ihcPresenceSingle">
							<span class="pd"></span><span data-presence-text><?php esc_html_e( 'Checking presence…', 'insight-hub-dashboard' ); ?></span>
						</div>
						<div class="ih-group-status" id="ihcGroupStatus"<?php echo $ih_is_group_chat ? '' : ' hidden'; ?>>
							<span class="ih-group-status-dot"></span>
							<span id="ihcGroupStatusText"><?php echo esc_html( count( $ih_group_participants ) . ' participants · ' . $ih_group_online . ' online' ); ?></span>
						</div>
						<?php if ( $listing_ref && ! $ih_is_group_chat ) : ?>
						<div class="ihc-listing-ctx">
							<?php if ( $listing_url ) : ?>
								<a href="<?php echo esc_url( $listing_url ); ?>"><?php echo esc_html( $listing_ref ); ?></a>
							<?php else : ?>
								<span><?php echo esc_html( $listing_ref ); ?></span>
							<?php endif; ?>
						</div>
						<?php endif; ?>
					</div>
				</div>
				<?php if ( $a_wa && ! $ih_is_group_chat ) : ?>
					<a class="ihc-btn-wa" href="https://wa.me/<?php echo esc_attr( $a_wa ); ?>?text=<?php echo rawurlencode( 'Hi ' . $a_name . ', this is MouldHub admin.' ); ?>" target="_blank" rel="noopener">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15l-1.4 5 5.1-1.3A10 10 0 1 0 12 2zm5.5 14.2c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .1-1.7-.1-.4-.1-.9-.3-1.6-.6-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.8 0-1.3.7-2 .9-2.2.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.3 0 .5l-.4.5c-.2.2-.3.4-.1.7.2.3.9 1.4 1.9 2.3 1.3 1.1 2.3 1.5 2.6 1.6.2.1.4.1.6-.1l.7-.9c.2-.2.4-.2.6-.1l1.9.9c.3.1.4.2.5.3.1.2.1.7-.1 1.3z"/></svg>
						<span>WhatsApp</span>
					</a>
				<?php endif; ?>
				<button type="button" class="ihc-btn-soft" id="ihcInviteBtn">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#5347ce" stroke-width="2" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
					<?php esc_html_e( 'Invite', 'insight-hub-dashboard' ); ?>
				</button>
				<button type="button" class="ihc-iconbtn ih-chat-menu-pill" id="ihcInfoBtn" aria-label="<?php esc_attr_e( 'Conversation details', 'insight-hub-dashboard' ); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/></svg>
				</button>
			</header>

			<div class="ihc-msgs" id="ihcMsgs" aria-live="polite">
				<p class="ih-msg-reactions-hint">
					<?php esc_html_e( 'Hover a message to react, or tap ⋯ for more.', 'insight-hub-dashboard' ); ?>
					<br><b><?php esc_html_e( 'Hover / long-press a bubble → react. Reactions show as a chip under the message:', 'insight-hub-dashboard' ); ?></b>
				</p>
				<?php
				$last_label = '';
				if ( empty( $messages ) && $active_uid ) :
					?>
					<div class="ihc-div"><span><?php esc_html_e( 'Conversation', 'insight-hub-dashboard' ); ?></span></div>
					<div class="ihc-row them">
						<div class="ihc-bubble">
							<div class="bt"><?php esc_html_e( 'No messages yet. Send a reply to start the conversation.', 'insight-hub-dashboard' ); ?></div>
						</div>
					</div>
				<?php else : ?>
					<?php foreach ( $messages as $m ) :
						$label = ih_am_date_label( $m['sent_at'] ?? '' );
						if ( $label && $label !== $last_label ) :
							$last_label = $label;
							?>
							<div class="ihc-div"><span><?php echo esc_html( $label ); ?></span></div>
						<?php endif;
						$me       = intval( $m['from_me'] ) === 1;
						$tm       = date_i18n( 'g:i A', strtotime( get_date_from_gmt( $m['sent_at'] ) ) );
						$mid      = intval( $m['id'] );
						$msg_body = (string) ( $m['message'] ?? '' );
						$msg_type = function_exists( 'ih_chat_detect_message_type' ) ? ih_chat_detect_message_type( $msg_body ) : 'chat';
						if ( $msg_type === 'system' ) :
							$sys_label = function_exists( 'ih_chat_system_message_label' ) ? ih_chat_system_message_label( $msg_body ) : $msg_body;
							?>
						<div class="ih-msg-system-row" data-id="<?php echo $mid; ?>" data-type="system">
							<div class="ih-msg-system-pill">
								<span class="ih-msg-system-icon" aria-hidden="true">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
								</span>
								<span><?php echo esc_html( $sys_label ); ?></span>
							</div>
						</div>
							<?php
							continue;
						endif;
						$req_marker = function_exists( 'ih_chat_parse_request_marker' ) ? ih_chat_parse_request_marker( $msg_body ) : null;
						if ( $req_marker ) :
							$rm_ref    = function_exists( 'ih_am_listing_ref' ) ? ih_am_listing_ref( $req_marker['listing_type'], $req_marker['listing_id'] ) : '';
							$rm_meta   = function_exists( 'ih_chat_request_card_meta' ) ? ih_chat_request_card_meta( $req_marker['id'] ) : array( 'status' => 'Pending', 'ref' => '' );
							$rm_status = $rm_meta['status'] ?: 'Pending';
							$rm_reqref = $rm_meta['ref'] ?: ( 'REQ-' . date_i18n( 'Y', strtotime( get_date_from_gmt( $m['sent_at'] ) ) ) . '-' . str_pad( (string) $req_marker['id'], 4, '0', STR_PAD_LEFT ) );
							$rm_from   = $req_marker['requester_id'] ? 'USR-' . (int) $req_marker['requester_id'] : '';
							$rm_listing = $rm_ref
								? ( ( $req_marker['listing_type'] === 'tool' ? __( 'Tool', 'insight-hub-dashboard' ) : __( 'Machine', 'insight-hub-dashboard' ) ) . ' ' . $rm_ref )
								: __( 'Contact access', 'insight-hub-dashboard' );
							$rm_sub = $rm_from ? ( $rm_from . ' · ' . $rm_listing ) : $rm_listing;
							?>
							<div class="ih-msg-system-row" data-id="<?php echo $mid; ?>" data-msg-type="request">
								<div class="ihc-reqmsg" role="note" aria-label="<?php esc_attr_e( 'Access request', 'insight-hub-dashboard' ); ?>">
									<span class="ihc-reqmsg-ico" aria-hidden="true">
										<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
									</span>
									<div class="tx">
										<?php /* translators: %s: request reference such as REQ-2026-0203 */ ?>
										<b><?php echo esc_html( sprintf( __( 'Access request · %s', 'insight-hub-dashboard' ), $rm_reqref ) ); ?></b>
										<span><?php echo esc_html( $rm_sub ); ?></span>
									</div>
									<span class="ihc-statuspill <?php echo esc_attr( strtolower( $rm_status ) ); ?>"><?php echo esc_html( $rm_status ); ?></span>
								</div>
							</div>
							<?php
							continue;
						endif;
						$sender_id = isset( $m['sender_id'] ) && $m['sender_id'] ? (int) $m['sender_id'] : ( $me ? $admin_id : $active_uid );
						$sender    = $ih_participant_map[ $sender_id ] ?? null;
						if ( ! $sender && $sender_id ) {
							$sender = function_exists( 'ih_chat_participant_meta' ) ? ih_chat_participant_meta( $sender_id ) : null;
						}
						$row_cls = $me ? 'me' : 'them';
						if ( $ih_is_group_chat && ! $me ) {
							$row_cls .= ' group';
						}
						$out_status = ( $me && function_exists( 'ih_chat_outbound_delivery_status' ) ) ? ih_chat_outbound_delivery_status( $m, true ) : null;
						if ( $me && $out_status && function_exists( 'ih_chat_msg_status_class' ) ) {
							$row_cls .= ih_chat_msg_status_class( $out_status );
						}
						?>
						<div class="ihc-row <?php echo esc_attr( $row_cls ); ?>" data-id="<?php echo $mid; ?>" data-msg-type="chat">
							<?php if ( $ih_is_group_chat && ! $me && $sender ) : ?>
							<span class="ih-msg-sender-av" style="--ih-av-color:<?php echo esc_attr( $sender['avatar_color'] ); ?>"><?php echo esc_html( $sender['initials'] ); ?></span>
							<?php endif; ?>
							<div class="ihc-msg-wrap">
							<div class="ihc-bubble ih-msg-bubble--<?php echo $me ? 'outgoing' : 'incoming'; ?>">
								<?php if ( $ih_is_group_chat && ! $me && $sender ) : ?>
									<div class="ih-msg-sender-name" style="color:<?php echo esc_attr( $sender['avatar_color'] ); ?>"><?php echo esc_html( $sender['short_name'] ?? $sender['name'] ); ?></div>
								<?php endif; ?>
								<?php if ( $has_att && ! empty( $m['attachment_url'] ) ) :
									$at = $m['attachment_type'] ?? 'file';
									if ( $at === 'image' ) : ?>
										<a class="ihc-att-img" href="<?php echo esc_url( $m['attachment_url'] ); ?>" target="_blank" rel="noopener" data-img>
											<img src="<?php echo esc_url( $m['attachment_url'] ); ?>" alt="">
										</a>
									<?php else : ?>
										<a class="ihc-att-file" href="<?php echo esc_url( $m['attachment_url'] ); ?>" target="_blank" rel="noopener" download>
											<span class="fi"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg></span>
											<span><b class="fn"><?php echo esc_html( $m['attachment_name'] ?? 'Attachment' ); ?></b><span class="fs"><?php esc_html_e( 'File', 'insight-hub-dashboard' ); ?></span></span>
										</a>
									<?php endif;
								endif; ?>
								<?php if ( $msg_body !== '' ) : ?>
									<div class="bt"><?php echo esc_html( $msg_body ); ?></div>
								<?php endif; ?>
								<div class="ihc-meta">
									<?php echo esc_html( $tm ); ?>
									<?php if ( $me && $out_status && function_exists( 'ih_chat_render_outbound_meta_icons' ) ) : ?>
										<?php echo ih_chat_render_outbound_meta_icons( $out_status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<?php endif; ?>
								</div>
							</div>
							<?php
							$msg_reactions = $reactions_map[ $mid ] ?? array();
							?>
							<div class="ihc-reactions"<?php echo empty( $msg_reactions ) ? ' style="display:none"' : ''; ?>>
								<?php echo function_exists( 'ih_chat_render_reaction_chips' ) ? ih_chat_render_reaction_chips( $msg_reactions ) : ''; ?>
							</div>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>

				<?php foreach ( $requests as $r ) :
					$st = ucfirst( strtolower( $r['status'] ?? 'Pending' ) );
					$lref = ! empty( $r['listing_id'] ) ? ih_am_listing_ref( $r['listing_type'] ?? 'machine', $r['listing_id'] ) : '';
					$intent = function_exists( 'ih_request_intent_meta' ) ? ih_request_intent_meta( $r ) : array( 'key' => 'profile', 'label' => 'Request' );
					$is_listing_approval = ( $intent['key'] ?? '' ) === 'listing_approval';
					if ( $is_listing_approval && $lref ) {
						$card_title = sprintf( __( 'Listing approval · %s', 'insight-hub-dashboard' ), ih_am_reqref( $r ) ) . ' · ' . $lref;
						$card_desc  = sprintf( __( '%s submitted %s for marketplace approval.', 'insight-hub-dashboard' ), $a_name, $lref );
					} elseif ( $lref ) {
						$card_title = sprintf( __( 'Contact info request · %s', 'insight-hub-dashboard' ), ih_am_reqref( $r ) ) . ' · ' . $lref;
						$card_desc  = sprintf( __( '%1$s requested contact information for %2$s.', 'insight-hub-dashboard' ), $a_name, $lref );
					} else {
						$card_title = sprintf( __( '%s · %s', 'insight-hub-dashboard' ), $intent['label'] ?? 'Request', ih_am_reqref( $r ) );
						$card_desc  = sprintf( __( '%s submitted a request for admin review.', 'insight-hub-dashboard' ), $a_name );
					}
					?>
					<div class="ihc-reqcard<?php echo $st !== 'Pending' ? ' done' : ''; ?>" data-req="<?php echo (int) $r['id']; ?>">
						<div class="rh">
							<span class="ri">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
							</span>
							<span class="rt">
								<b><?php echo esc_html( $card_title ); ?></b>
								<span><?php echo esc_html( $card_desc ); ?></span>
							</span>
							<?php if ( $st !== 'Pending' ) : ?>
								<span class="ihc-statuspill <?php echo esc_attr( strtolower( $st ) ); ?>"><?php echo esc_html( $st ); ?></span>
							<?php endif; ?>
						</div>
						<?php if ( $st === 'Pending' ) : ?>
							<div class="rb">
								<button type="button" class="ihc-rbtn ok">
									<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.4" aria-hidden="true"><path d="M5 12l5 5L20 7"/></svg>
									<?php esc_html_e( 'Approve', 'insight-hub-dashboard' ); ?>
								</button>
								<button type="button" class="ihc-rbtn no"><?php esc_html_e( 'Decline', 'insight-hub-dashboard' ); ?></button>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>

				<div class="ihc-row them ihc-hide" id="ihcTyping">
					<div class="ihc-typing"><i></i><i></i><i></i></div>
				</div>
			</div>

			<div class="ihc-composer<?php echo $active_uid ? '' : ' ihc-hide'; ?>" id="ihcComposer">
				<div class="ihc-previews" id="ihcPreviews"></div>
				<div class="ihc-bar">
					<button type="button" class="ihc-rbtn-icon" id="ihcAttachBtn" aria-label="<?php esc_attr_e( 'Attach file', 'insight-hub-dashboard' ); ?>">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#5347ce" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
						<div class="ihc-attmenu" id="ihcAttachMenu">
							<button type="button" data-pick="image"><?php esc_html_e( 'Photo', 'insight-hub-dashboard' ); ?></button>
							<button type="button" data-pick="video"><?php esc_html_e( 'Video', 'insight-hub-dashboard' ); ?></button>
							<button type="button" data-pick="file"><?php esc_html_e( 'Document', 'insight-hub-dashboard' ); ?></button>
						</div>
					</button>
					<div class="ihc-input">
						<textarea id="ihcInput" rows="1" placeholder="<?php echo esc_attr( $active_uid ? sprintf( __( 'Message %s…', 'insight-hub-dashboard' ), $a_name ) : __( 'Select a conversation…', 'insight-hub-dashboard' ) ); ?>"<?php echo $active_uid ? '' : ' disabled'; ?>></textarea>
						<span class="emoji" id="ihcEmojiBtn" role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Emoji', 'insight-hub-dashboard' ); ?>">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01"/></svg>
							<div class="ihc-emoji" id="ihcEmoji">
								<?php foreach ( array( '😀', '😁', '👍', '🙏', '✅', '📎', '🔧', '⚙️', '🏭', '📦', '💬', '🔥', '👏', '🤝', '📐', '✔️' ) as $em ) : ?>
									<span><?php echo esc_html( $em ); ?></span>
								<?php endforeach; ?>
							</div>
						</span>
					</div>
					<button type="button" class="ihc-send" id="ihcSend" aria-label="<?php esc_attr_e( 'Send', 'insight-hub-dashboard' ); ?>"<?php echo $active_uid ? '' : ' disabled'; ?>>
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4z"/></svg>
					</button>
				</div>
				<input type="file" id="ihcFileImage" accept="image/*" hidden>
				<input type="file" id="ihcFileDoc" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip" hidden>
				<input type="file" id="ihcFileVideo" accept="video/*" hidden>
			</div>
		</section>

		<aside class="ihc-p3">
			<div class="ihc-prof">
				<span class="ihc-ava" style="width:64px;height:64px;font-size:22px"><?php echo esc_html( ih_am_initials( $a_name ) ); ?><span class="ihc-online off" data-uid="<?php echo (int) $active_uid; ?>" style="width:15px;height:15px"></span></span>
				<div style="text-align:center">
					<div class="nm"><?php echo esc_html( $a_name ); ?></div>
					<div class="uid"><?php echo esc_html( 'USR-' . $active_uid . ( $a_comp ? ' · ' . strtoupper( $a_comp ) : '' ) ); ?></div>
					<?php if ( $a_since ) : ?>
						<div class="ihc-member"><?php printf( esc_html__( 'Member since %s', 'insight-hub-dashboard' ), esc_html( $a_since ) ); ?></div>
					<?php endif; ?>
				</div>
				<span class="ihc-prespill off" data-presence>
					<span class="pd" style="width:7px;height:7px;border-radius:50%;background:currentColor"></span>
					<span data-presence-text><?php esc_html_e( 'Checking…', 'insight-hub-dashboard' ); ?></span>
				</span>
			</div>

			<?php if ( $pending_req ) :
				$plref = ! empty( $pending_req['listing_id'] ) ? ih_am_listing_ref( $pending_req['listing_type'] ?? 'machine', $pending_req['listing_id'] ) : '';
				?>
				<div class="ihc-pending-card" data-req="<?php echo (int) $pending_req['id']; ?>">
					<div class="ihc-slabel"><?php esc_html_e( 'Pending request', 'insight-hub-dashboard' ); ?></div>
					<b><?php echo esc_html( ih_am_reqref( $pending_req ) ); ?><?php echo $plref ? ' · ' . esc_html( $plref ) : ''; ?></b>
					<div class="rb">
						<button type="button" class="ihc-rbtn ok"><?php esc_html_e( 'Approve', 'insight-hub-dashboard' ); ?></button>
						<button type="button" class="ihc-rbtn no"><?php esc_html_e( 'Decline', 'insight-hub-dashboard' ); ?></button>
					</div>
				</div>
			<?php endif; ?>

			<div class="ihc-slabel"><?php esc_html_e( 'Quick actions', 'insight-hub-dashboard' ); ?></div>
			<button type="button" class="ihc-btn" id="ihcInviteSidebarBtn"<?php echo $active_thread ? '' : ' disabled'; ?>>
				<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#5347ce" stroke-width="2" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
				<?php esc_html_e( 'Invite to chat', 'insight-hub-dashboard' ); ?>
			</button>
			<?php if ( $pending_req ) : ?>
				<button type="button" class="ihc-btn primary" data-approve-pending="<?php echo (int) $pending_req['id']; ?>"><?php esc_html_e( 'Approve contact', 'insight-hub-dashboard' ); ?></button>
			<?php endif; ?>
			<button type="button" class="ihc-btn<?php echo $is_blocked ? ' danger' : ''; ?>" data-block="<?php echo (int) $active_uid; ?>" data-blocked="<?php echo $is_blocked ? '1' : '0'; ?>">
				<?php echo $is_blocked ? esc_html__( 'Unblock user', 'insight-hub-dashboard' ) : esc_html__( 'Block user', 'insight-hub-dashboard' ); ?>
			</button>
			<?php if ( $listing_url ) : ?>
				<a class="ihc-btn" href="<?php echo esc_url( $listing_url ); ?>"><?php esc_html_e( 'View listing', 'insight-hub-dashboard' ); ?></a>
			<?php endif; ?>
			<a class="ihc-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=ih-requests' ) ); ?>"><?php esc_html_e( 'View all requests', 'insight-hub-dashboard' ); ?></a>
			<?php if ( $active_uid ) : ?>
				<a class="ihc-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=ih-users&user_id=' . $active_uid ) ); ?>"><?php esc_html_e( 'View profile', 'insight-hub-dashboard' ); ?></a>
			<?php endif; ?>

			<?php if ( ! empty( $shared_files ) ) : ?>
				<div class="ihc-slabel"><?php esc_html_e( 'Shared files', 'insight-hub-dashboard' ); ?></div>
				<div class="ihc-files">
					<?php foreach ( $shared_files as $sf ) : ?>
						<a class="f" href="<?php echo esc_url( $sf['attachment_url'] ); ?>" target="_blank" rel="noopener" title="<?php echo esc_attr( $sf['attachment_name'] ?? '' ); ?>">
							<?php if ( ( $sf['attachment_type'] ?? '' ) === 'image' ) : ?>
								<img src="<?php echo esc_url( $sf['attachment_url'] ); ?>" alt="">
							<?php else : ?>
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5347ce" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
							<?php endif; ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $active_uid ) : ?>
				<div class="ihc-slabel"><?php esc_html_e( 'Conversation stats', 'insight-hub-dashboard' ); ?></div>
				<div class="ihc-stats">
					<div class="ihc-stat"><b><?php echo (int) $msg_total; ?></b><span><?php esc_html_e( 'Messages', 'insight-hub-dashboard' ); ?></span></div>
					<div class="ihc-stat"><b><?php echo esc_html( $avg_reply_label ); ?></b><span><?php esc_html_e( 'Avg reply', 'insight-hub-dashboard' ); ?></span></div>
					<div class="ihc-stat"><b><?php echo (int) $requests_total; ?></b><span><?php esc_html_e( 'Requests', 'insight-hub-dashboard' ); ?></span></div>
				</div>

				<div class="ihc-slabel"><?php esc_html_e( 'Access & controls', 'insight-hub-dashboard' ); ?></div>
				<div class="ihc-toggles" aria-describedby="ihcAccessNote">
					<?php foreach ( $access_toggles as $tg ) : ?>
						<div class="ihc-toggle">
							<div class="tx"><b><?php echo esc_html( $tg['label'] ); ?></b><span><?php echo esc_html( $tg['sub'] ); ?></span></div>
							<span class="ihc-switch" role="switch" aria-checked="<?php echo $tg['on'] ? 'true' : 'false'; ?>" aria-disabled="true" aria-readonly="true" tabindex="-1" title="<?php esc_attr_e( 'Read-only — manage access from the user’s profile', 'insight-hub-dashboard' ); ?>" aria-label="<?php echo esc_attr( $tg['label'] ); ?>"></span>
						</div>
					<?php endforeach; ?>
				</div>
				<p id="ihcAccessNote" class="ihc-access-note"><?php esc_html_e( 'Read-only here — access is managed from the user’s profile.', 'insight-hub-dashboard' ); ?></p>

				<div class="ihc-slabel"><?php esc_html_e( 'Participants', 'insight-hub-dashboard' ); ?></div>
				<div class="ihc-participants">
					<?php if ( $ih_is_group_chat ) : ?>
						<?php foreach ( $ih_group_participants as $gp ) : ?>
							<div class="ihc-participant">
								<span class="ihc-ava" style="width:34px;height:34px;font-size:13px;background:<?php echo esc_attr( $gp['avatar_color'] ?? '#5347ce' ); ?>"><?php echo esc_html( $gp['initials'] ?? '' ); ?></span>
								<div class="tx"><b><?php echo esc_html( $gp['name'] ?? '' ); ?></b><span><?php echo ! empty( $gp['is_admin'] ) ? esc_html__( 'Admin · controls the chat', 'insight-hub-dashboard' ) : esc_html__( 'Participant', 'insight-hub-dashboard' ); ?></span></div>
							</div>
						<?php endforeach; ?>
					<?php else : ?>
						<div class="ihc-participant">
							<span class="ihc-ava" style="width:34px;height:34px;font-size:13px"><?php echo esc_html( ih_am_initials( wp_get_current_user()->display_name ?: 'Admin' ) ); ?></span>
							<div class="tx"><b><?php esc_html_e( 'You (Admin)', 'insight-hub-dashboard' ); ?></b><span><?php esc_html_e( 'Owner · controls the chat', 'insight-hub-dashboard' ); ?></span></div>
						</div>
						<div class="ihc-participant">
							<span class="ihc-ava" style="width:34px;height:34px;font-size:13px"><?php echo esc_html( ih_am_initials( $a_name ) ); ?></span>
							<div class="tx"><b><?php echo esc_html( $a_name ); ?></b><span><?php echo esc_html( 'USR-' . (int) $active_uid ); ?></span></div>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="ihc-card">
				<div class="ch"><span><?php esc_html_e( 'Messages · last 7 days', 'insight-hub-dashboard' ); ?></span></div>
				<div class="ihc-bars">
					<?php for ( $i = 0; $i < 7; $i++ ) : ?>
						<div class="col">
							<div class="bar<?php echo $bars[ $i ] === $bmax ? ' hi' : ''; ?>" style="height:<?php echo max( 6, (int) round( $bars[ $i ] / $bmax * 56 ) ); ?>px"></div>
							<em><?php echo esc_html( $dl[ $i ] ); ?></em>
						</div>
					<?php endfor; ?>
				</div>
			</div>

			<?php if ( ! empty( $requests ) ) : ?>
				<div class="ihc-card">
					<div class="ch"><span><?php esc_html_e( 'Request status', 'insight-hub-dashboard' ); ?></span></div>
					<div class="ihc-donut-wrap">
						<?php
						$total_r = max( 1, count( $requests ) );
						$pct_a   = (int) round( $req_stats['approved'] / $total_r * 100 );
						$pct_p   = (int) round( $req_stats['pending'] / $total_r * 100 );
						$pct_r   = (int) round( $req_stats['rejected'] / $total_r * 100 );
						$pct_c   = max( 0, 100 - $pct_a - $pct_p - $pct_r );
						?>
						<div class="ihc-donut" style="--a:<?php echo $pct_a; ?>;--p:<?php echo $pct_p; ?>;--r:<?php echo $pct_r; ?>;--c:<?php echo $pct_c; ?>"></div>
						<div class="ihc-donut-legend">
							<span><i class="a"></i><?php esc_html_e( 'Approved', 'insight-hub-dashboard' ); ?> <?php echo (int) $req_stats['approved']; ?></span>
							<span><i class="p"></i><?php esc_html_e( 'Pending', 'insight-hub-dashboard' ); ?> <?php echo (int) $req_stats['pending']; ?></span>
							<span><i class="r"></i><?php esc_html_e( 'Rejected', 'insight-hub-dashboard' ); ?> <?php echo (int) $req_stats['rejected']; ?></span>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $requests ) : ?>
				<div class="ihc-slabel"><?php esc_html_e( 'Requests from this user', 'insight-hub-dashboard' ); ?></div>
				<div class="ihc-req-list">
					<?php foreach ( array_slice( $requests, 0, 4 ) as $r ) :
						$st = ucfirst( strtolower( $r['status'] ?? 'Pending' ) );
						$lref = ! empty( $r['listing_id'] ) ? ih_am_listing_ref( $r['listing_type'] ?? 'machine', $r['listing_id'] ) : __( 'Contact details', 'insight-hub-dashboard' );
						?>
						<div class="ihc-reqrow">
							<div class="tx">
								<em><?php echo esc_html( ih_am_reqref( $r ) ); ?></em>
								<b><?php echo esc_html( $lref ); ?></b>
							</div>
							<span class="ihc-statuspill <?php echo esc_attr( strtolower( $st ) ); ?>"><?php echo esc_html( $st ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</aside>

	</div>
</div>
</div>

<?php include IH_DIR . 'pages/partials/ih-invite-chat-modal.php'; ?>

<div class="ihc-toasts" id="ihcToasts"></div>

<script>
window.ihAjaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
window.ihNonce = <?php echo wp_json_encode( $nonce ); ?>;
window.ihAdminId = <?php echo (int) $admin_id; ?>;
window.IHCHAT = {
	ajax: window.ihAjaxUrl,
	nonce: window.ihNonce,
	role: 'admin',
	meId: <?php echo (int) $admin_id; ?>,
	activeUid: <?php echo (int) $active_uid; ?>,
	activeThread: <?php echo (int) $active_thread; ?>,
	lastId: <?php echo (int) $last_id; ?>,
	unreadCount: <?php echo (int) $thread_unread; ?>,
	firstUnreadId: <?php echo (int) $first_unread_id; ?>,
	sendAction: 'ih_send_message',
	getAction: 'ih_get_messages',
	requestsUrl: <?php echo wp_json_encode( admin_url( 'admin.php?page=ih-requests' ) ); ?>,
	isGroup: <?php echo $ih_is_group_chat ? 'true' : 'false'; ?>,
	participants: <?php echo wp_json_encode( $ih_group_participants ); ?>,
	groupTitle: <?php echo wp_json_encode( $ih_group_title ); ?>,
	onlineCount: <?php echo (int) $ih_group_online; ?>,
	useGroupDemo: <?php echo $ih_use_group_demo ? 'true' : 'false'; ?>,
	groupDemo: <?php
	echo wp_json_encode(
		$ih_use_group_demo ? array(
			'participants' => array(
				array( 'id' => 101, 'name' => 'Sara Lee', 'short_name' => 'Sara Lee', 'initials' => 'SL', 'avatar_color' => '#8c5cf5', 'online' => true ),
				array( 'id' => 102, 'name' => 'Jon D.', 'short_name' => 'Jon D.', 'initials' => 'JD', 'avatar_color' => '#17c7c7', 'online' => true ),
				array( 'id' => (int) $admin_id, 'name' => 'You', 'short_name' => 'You', 'initials' => 'YO', 'avatar_color' => '#5347ce', 'online' => true, 'is_admin' => true ),
			),
			'group_title' => 'Sara Lee, Jon D. & you',
			'messages'    => array(
				array( 'id' => 9001, 'type' => 'system', 'system_label' => 'Admin added Jon D. to the chat', 'text' => 'Admin added Jon D. to the chat', 'from_me' => 1, 'time' => '9:10 AM' ),
				array( 'id' => 9002, 'type' => 'chat', 'from_me' => 0, 'sender_id' => 101, 'text' => 'Hi everyone — can we confirm the mould specs before Friday?', 'time' => '9:12 AM' ),
				array( 'id' => 9003, 'type' => 'chat', 'from_me' => 0, 'sender_id' => 102, 'text' => 'I have the CAD files ready to share.', 'time' => '9:14 AM' ),
				array( 'id' => 9004, 'type' => 'chat', 'from_me' => 1, 'sender_id' => (int) $admin_id, 'text' => 'Perfect — I will review and get back to you both shortly.', 'time' => '9:15 AM', 'is_read' => 1 ),
			),
		) : null
	);
	?>
};
</script>
<?php
$content = ob_get_clean();
$title   = __( 'Messages', 'insight-hub-dashboard' );
include IH_DIR . 'pages/layout.php';
