<?php
/**
 * User Messaging Centre — Nexus 3-pane (Figma 202:2199)
 */
defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
	wp_redirect( wp_login_url( admin_url( 'admin.php?page=ih-user-messages' ) ) );
	exit;
}
if ( current_user_can( 'administrator' ) ) {
	wp_redirect( admin_url( 'admin.php?page=ih-messages' ) );
	exit;
}

global $wpdb;
$pfx     = $wpdb->prefix;
$uid     = get_current_user_id();
$user    = wp_get_current_user();
$nonce   = wp_create_nonce( 'ih_nonce' );

if ( ! function_exists( 'ih_um_initials' ) ) {
	function ih_um_initials( $name ) {
		$p = preg_split( '/\s+/', trim( $name ?: 'User' ) );
		return strtoupper( substr( $p[0] ?? 'U', 0, 1 ) . substr( $p[1] ?? '', 0, 1 ) );
	}
}
if ( ! function_exists( 'ih_um_reqref' ) ) {
	function ih_um_reqref( $r ) {
		return 'REQ-' . date( 'Y', strtotime( $r['request_date'] ?? 'now' ) ) . '-' . str_pad( (string) ( $r['id'] ?? 0 ), 4, '0', STR_PAD_LEFT );
	}
}
if ( ! function_exists( 'ih_um_listing_ref' ) ) {
	function ih_um_listing_ref( $type, $lid ) {
		$lid = (int) $lid;
		if ( $lid <= 0 ) {
			return '';
		}
		if ( function_exists( 'ih_request_listing_ref' ) ) {
			return ih_request_listing_ref( $type, $lid );
		}
		return ( $type === 'tool' ? 'TL-' : 'MCH-' ) . str_pad( (string) $lid, 5, '0', STR_PAD_LEFT );
	}
}
if ( ! function_exists( 'ih_um_owner_company' ) ) {
	/**
	 * Resolve the listing owner's display company/name. Only used for APPROVED
	 * requests, where the contact has already been released by the admin.
	 */
	function ih_um_owner_company( $type, $lid ) {
		global $wpdb;
		$pfx = $wpdb->prefix;
		$lid = (int) $lid;
		if ( $lid <= 0 ) {
			return '';
		}
		$tbl = ( $type === 'tool' ) ? $pfx . 'ih_tools' : $pfx . 'ih_machines';
		$oid = (int) $wpdb->get_var( $wpdb->prepare( "SELECT owner_id FROM {$tbl} WHERE id=%d", $lid ) );
		if ( ! $oid ) {
			return '';
		}
		$company = (string) ( get_user_meta( $oid, 'company_name', true ) ?: get_user_meta( $oid, 'ih_company', true ) );
		if ( $company !== '' ) {
			return $company;
		}
		return function_exists( 'ih_resolve_user_name' ) ? ih_resolve_user_name( $oid ) : '';
	}
}
if ( ! function_exists( 'ih_um_is_admin_notice' ) ) {
	/**
	 * Listing-approval notices ("New Tool submitted for approval …") are inserted
	 * into the thread with from_me=0, so the user view would otherwise paint them
	 * as the user's own purple outbound bubble. Detect them so they can be shown
	 * as a neutral, centered system card instead.
	 */
	function ih_um_is_admin_notice( $text ) {
		$text = (string) $text;
		return (bool) preg_match( '/submitted for approval/i', $text )
			|| (bool) preg_match( '/Reject from the listing/i', $text );
	}
}
if ( ! function_exists( 'ih_um_admin_notice_text' ) ) {
	/**
	 * Clean an admin approval notice for display in the USER conversation: drop the
	 * admin-only call to action ("Approve or Reject from the listing bar above"),
	 * which has no meaning on the user side, and collapse blank lines.
	 */
	function ih_um_admin_notice_text( $text ) {
		$text  = trim( (string) $text );
		$lines = preg_split( '/\r\n|\r|\n/', $text );
		$out   = array();
		foreach ( (array) $lines as $ln ) {
			$ln = trim( $ln );
			if ( $ln === '' ) {
				continue;
			}
			if ( preg_match( '/Approve or|Reject from the listing/i', $ln ) ) {
				continue;
			}
			$out[] = $ln;
		}
		return implode( "\n", $out );
	}
}

$threads = $wpdb->get_results(
	$wpdb->prepare( "SELECT * FROM {$pfx}ih_threads WHERE user_id=%d ORDER BY last_time DESC", $uid ),
	ARRAY_A
) ?: array();

$active_thread_id = intval( $_GET['thread'] ?? 0 );
if ( ! $active_thread_id && ! empty( $threads ) ) {
	$active_thread_id = intval( $threads[0]['id'] );
}

$active_thread   = null;
$messages        = array();
$last_id         = 0;
$thread_unread   = 0;
$first_unread_id = 0;

if ( $active_thread_id ) {
	$active_thread = $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM {$pfx}ih_threads WHERE id=%d AND user_id=%d", $active_thread_id, $uid ),
		ARRAY_A
	);
}
if ( $active_thread ) {
	$thread_unread = intval( $active_thread['unread'] ?? 0 );
	$messages = $wpdb->get_results(
		$wpdb->prepare( "SELECT * FROM {$pfx}ih_chats WHERE thread_id=%d ORDER BY id ASC", $active_thread_id ),
		ARRAY_A
	) ?: array();
	if ( $thread_unread > 0 && $messages ) {
		$incoming = array();
		foreach ( $messages as $m ) {
			if ( intval( $m['from_me'] ) !== 1 ) {
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
	$wpdb->update( $pfx . 'ih_threads', array( 'unread' => 0 ), array( 'id' => $active_thread_id ) );
	if ( $messages ) {
		$last_id = intval( $messages[ count( $messages ) - 1 ]['id'] );
	}
}

$requests = $wpdb->get_results(
	$wpdb->prepare( "SELECT * FROM {$pfx}ih_requests WHERE user_id=%d ORDER BY id DESC LIMIT 12", $uid ),
	ARRAY_A
) ?: array();

/* Activity totals — counted across the whole request history (not just the
   12 most-recent rows shown in the side panel). */
$req_total    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$pfx}ih_requests WHERE user_id=%d", $uid ) );
$req_approved = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$pfx}ih_requests WHERE user_id=%d AND LOWER(TRIM(status))='approved'", $uid ) );
$req_pending  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$pfx}ih_requests WHERE user_id=%d AND LOWER(TRIM(status))='pending'", $uid ) );

/* Left-rail groups: approved contacts (owner revealed — already released) and
   pending requests (owner stays anonymised behind the listing ref). */
$approved_contacts = $wpdb->get_results(
	$wpdb->prepare( "SELECT * FROM {$pfx}ih_requests WHERE user_id=%d AND LOWER(TRIM(status))='approved' AND listing_id>0 ORDER BY id DESC LIMIT 8", $uid ),
	ARRAY_A
) ?: array();
$pending_contacts = $wpdb->get_results(
	$wpdb->prepare( "SELECT * FROM {$pfx}ih_requests WHERE user_id=%d AND LOWER(TRIM(status))='pending' ORDER BY id DESC LIMIT 8", $uid ),
	ARRAY_A
) ?: array();

$avatar_palette = array( '#6f5cf0', '#16c8c7', '#f59e0b', '#ec4899', '#22c55e', '#4896fe' );

$admin_wa = function_exists( 'ih_get_admin_whatsapp_number' ) ? ih_get_admin_whatsapp_number() : '';
$me_name  = $user->display_name ?: $user->user_login;
$me_init  = ih_um_initials( $me_name );

$thread_listing_map = array();
foreach ( $threads as $t ) {
	$tl_id   = intval( $t['listing_id'] ?? 0 );
	$tl_type = ( $t['listing_type'] ?? '' ) === 'tool' ? 'tool' : 'machine';
	$tl_lbl  = '';
	if ( $tl_id ) {
		$tl_tbl = $tl_type === 'tool' ? $pfx . 'ih_tools' : $pfx . 'ih_machines';
		$tl_lbl = (string) $wpdb->get_var( $wpdb->prepare( "SELECT title FROM {$tl_tbl} WHERE id=%d", $tl_id ) );
	}
	$thread_listing_map[ intval( $t['id'] ) ] = array(
		'type'  => $tl_type,
		'title' => $tl_lbl,
	);
}

$chat_cols = $wpdb->get_col( "SHOW COLUMNS FROM {$pfx}ih_chats" );
$has_att   = in_array( 'attachment_url', $chat_cols, true );

$reactions_map = array();
if ( ! empty( $messages ) && function_exists( 'ih_chat_reactions_for_messages' ) ) {
	$reactions_map = ih_chat_reactions_for_messages(
		array_map(
			static function ( $m ) {
				return (int) ( $m['id'] ?? 0 );
			},
			$messages
		),
		$uid
	);
}

$ih_header_search_placeholder = __( 'Search your conversations…', 'insight-hub-dashboard' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php esc_html_e( 'Messages', 'insight-hub-dashboard' ); ?> — Injection Moulding</title>
<?php wp_head(); ?>
</head>
<body>
<?php
$ih_shell_class = 'ih-shell ih-figma-dashboard is-user ih-shell--float-nav ih-user-messages-page';
$ih_shell_extra = 'data-ih-figma-screen="user-messages-v20260615b"';
include IH_DIR . 'pages/partials/ih-user-shell-start.php';
include IH_DIR . 'pages/partials/ih-user-shell-header.php';
?>

<div class="ih-body">
	<main class="ih-main">
		<div class="ih-content">
			<div class="ih-user-messages-root">
			<div class="ihc">
				<div class="ihc-area<?php echo $active_thread ? ' in-chat' : ''; ?>" id="ihcArea">

					<aside class="ihc-p1">
						<div class="ihc-p1-head">
							<div class="ihc-p1-title"><h2><?php esc_html_e( 'Messages', 'insight-hub-dashboard' ); ?></h2></div>
							<div class="ihc-search">
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#9aa0b4" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
								<input type="search" id="ihcThreadSearch" placeholder="<?php esc_attr_e( 'Search', 'insight-hub-dashboard' ); ?>" autocomplete="off">
							</div>
						</div>
						<div class="ihc-threads">
							<?php
							$support_thread = $active_thread ?: ( $threads[0] ?? null );
							$support_tid    = $support_thread ? intval( $support_thread['id'] ) : 0;
							$support_href   = $support_tid
								? admin_url( 'admin.php?page=ih-user-messages&thread=' . $support_tid )
								: admin_url( 'admin.php?page=ih-user-messages' );
							?>
							<a class="ihc-thread ihc-contact on<?php echo $thread_unread ? ' unread' : ''; ?>" href="<?php echo esc_url( $support_href ); ?>" data-enter-chat>
								<span class="ihc-ava sq" style="width:44px;height:44px;font-size:16px">M<span class="ihc-online off" data-uid="0"></span></span>
								<span class="tx">
									<span class="nm"><?php esc_html_e( 'MouldHub Support', 'insight-hub-dashboard' ); ?></span>
									<span class="sub"><?php esc_html_e( 'Admin team · replies in ~5 min', 'insight-hub-dashboard' ); ?></span>
								</span>
								<?php if ( $thread_unread > 0 ) : ?><span class="ihc-unb"><?php echo (int) $thread_unread; ?></span><?php endif; ?>
							</a>

							<?php if ( ! empty( $approved_contacts ) ) : ?>
								<div class="ihc-thread-group"><?php esc_html_e( 'Approved contacts · via your requests', 'insight-hub-dashboard' ); ?></div>
								<?php foreach ( $approved_contacts as $ci => $r ) :
									$lt    = ( ( $r['listing_type'] ?? '' ) === 'tool' ) ? 'tool' : 'machine';
									$lid   = intval( $r['listing_id'] ?? 0 );
									$ref   = ih_um_listing_ref( $lt, $lid );
									$label = ih_um_owner_company( $lt, $lid );
									if ( $label === '' ) {
										/* translators: %s: listing reference such as TL-00231 */
										$label = sprintf( __( 'Approved contact · %s', 'insight-hub-dashboard' ), $ref );
									}
									$avc = $avatar_palette[ $ci % count( $avatar_palette ) ];
									?>
									<a class="ihc-thread ihc-contact" href="<?php echo esc_url( $support_href ); ?>" data-enter-chat>
										<span class="ihc-ava" style="width:44px;height:44px;font-size:15px;background:<?php echo esc_attr( $avc ); ?>"><?php echo esc_html( ih_um_initials( $label ) ); ?></span>
										<span class="tx">
											<span class="nm"><?php echo esc_html( $label ); ?></span>
											<span class="sub">
												<?php
												/* translators: %s: listing reference such as TL-00231 */
												echo esc_html( sprintf( __( 'via %s · contact approved', 'insight-hub-dashboard' ), $ref ) );
												?>
											</span>
										</span>
										<span class="ihc-contact-check" aria-label="<?php esc_attr_e( 'Approved', 'insight-hub-dashboard' ); ?>">
											<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
										</span>
									</a>
								<?php endforeach; ?>
							<?php endif; ?>

							<?php if ( ! empty( $pending_contacts ) ) : ?>
								<div class="ihc-thread-group"><?php esc_html_e( 'Awaiting admin approval', 'insight-hub-dashboard' ); ?></div>
								<?php foreach ( $pending_contacts as $r ) :
									$lt  = ( ( $r['listing_type'] ?? '' ) === 'tool' ) ? 'tool' : 'machine';
									$lid = intval( $r['listing_id'] ?? 0 );
									$ref = ih_um_listing_ref( $lt, $lid );
									/* translators: %s: listing reference such as TL-00188 */
									$label = $ref ? sprintf( __( 'Listing owner · %s', 'insight-hub-dashboard' ), $ref ) : __( 'Profile access request', 'insight-hub-dashboard' );
									?>
									<a class="ihc-thread ihc-contact ihc-contact--pending" href="<?php echo esc_url( $support_href ); ?>" data-enter-chat>
										<span class="ihc-ava ihc-ava--muted" style="width:44px;height:44px">
											<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
										</span>
										<span class="tx">
											<span class="nm"><?php echo esc_html( $label ); ?></span>
											<span class="sub"><?php esc_html_e( 'via your request · pending review', 'insight-hub-dashboard' ); ?></span>
										</span>
										<span class="ihc-statuspill pending"><?php esc_html_e( 'Pending', 'insight-hub-dashboard' ); ?></span>
									</a>
								<?php endforeach; ?>
							<?php endif; ?>

							<?php if ( ! $support_thread && empty( $approved_contacts ) && empty( $pending_contacts ) ) : ?>
								<div class="ihc-empty"><?php esc_html_e( 'No conversations yet. Start chatting with MouldHub Support below.', 'insight-hub-dashboard' ); ?></div>
							<?php endif; ?>
						</div>
					</aside>

					<section class="ihc-p2">
						<header class="ihc-chat-head">
							<button type="button" class="ihc-back" id="ihcBack" aria-label="<?php esc_attr_e( 'Back to conversations', 'insight-hub-dashboard' ); ?>">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1a1c2b" stroke-width="2.2" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
							</button>
							<span class="ihc-ava sq" style="width:42px;height:42px;font-size:16px">M<span class="ihc-online off" data-uid="0"></span></span>
							<div class="ci">
								<div class="nm"><?php esc_html_e( 'MouldHub Support', 'insight-hub-dashboard' ); ?></div>
								<div class="ihc-presence">
									<span class="pd"></span><span><?php esc_html_e( 'Admin online · replies in ~5 min', 'insight-hub-dashboard' ); ?></span>
								</div>
							</div>
							<?php /* WhatsApp shortcut intentionally omitted on the user view (not in Figma 202:2199). */ ?>
							<button type="button" class="ihc-iconbtn" id="ihcInfoBtn" style="background:transparent;border:0" aria-label="<?php esc_attr_e( 'Request details', 'insight-hub-dashboard' ); ?>">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6b7185" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
							</button>
						</header>

						<div class="ihc-msgs" id="ihcMsgs" aria-live="polite">
							<p class="ih-msg-reactions-hint ihc-hide" id="ihMsgReactionsHint">
								<?php esc_html_e( 'Hover a message to react, or tap ⋯ for more.', 'insight-hub-dashboard' ); ?>
								<button type="button" class="ih-msg-reactions-hint__dismiss" id="ihMsgReactionsHintDismiss" aria-label="<?php esc_attr_e( 'Dismiss', 'insight-hub-dashboard' ); ?>">×</button>
							</p>
							<div class="ihc-div"><span><?php esc_html_e( 'Conversation', 'insight-hub-dashboard' ); ?></span></div>
							<?php if ( empty( $messages ) ) : ?>
								<div class="ihc-row them">
									<div class="ihc-bubble">
										<div class="bt">👋 <?php esc_html_e( 'Welcome to MouldHub Support. Ask us anything, or request access to a listing — an admin will respond shortly.', 'insight-hub-dashboard' ); ?></div>
										<div class="ihc-meta"><?php esc_html_e( 'now', 'insight-hub-dashboard' ); ?></div>
									</div>
								</div>
							<?php else : ?>
								<?php foreach ( $messages as $m ) :
									$me       = intval( $m['from_me'] ) === 0;
									$tm       = date_i18n( 'g:i A', strtotime( get_date_from_gmt( $m['sent_at'] ) ) );
									$mid      = intval( $m['id'] );
									$msg_body = (string) ( $m['message'] ?? '' );
									$msg_type = function_exists( 'ih_chat_detect_message_type' ) ? ih_chat_detect_message_type( $msg_body ) : 'chat';
									if ( $msg_type === 'system' ) :
										$sys_label = function_exists( 'ih_chat_system_message_label' ) ? ih_chat_system_message_label( $msg_body ) : $msg_body;
										?>
										<div class="ih-msg-system-row" data-id="<?php echo $mid; ?>" data-msg-type="system">
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
										$rm_ref    = ih_um_listing_ref( $req_marker['listing_type'], $req_marker['listing_id'] );
										$rm_meta   = function_exists( 'ih_chat_request_card_meta' )
											? ih_chat_request_card_meta( $req_marker['id'], $uid )
											: array( 'status' => 'Pending', 'ref' => '' );
										$rm_status = $rm_meta['status'] ?: 'Pending';
										$rm_reqref = $rm_meta['ref'] ?: ( 'REQ-' . date_i18n( 'Y', strtotime( get_date_from_gmt( $m['sent_at'] ) ) ) . '-' . str_pad( (string) $req_marker['id'], 4, '0', STR_PAD_LEFT ) );
										if ( $rm_ref ) {
											$rm_action = ( $req_marker['listing_type'] === 'tool' )
												/* translators: %s: tool reference such as TL-00231 */
												? sprintf( __( 'View access to tool %s', 'insight-hub-dashboard' ), $rm_ref )
												/* translators: %s: machine reference such as MCH-00114 */
												: sprintf( __( 'View access to machine %s', 'insight-hub-dashboard' ), $rm_ref );
										} else {
											$rm_action = __( 'Contact access request', 'insight-hub-dashboard' );
										}
										?>
										<div class="ih-msg-system-row" data-id="<?php echo $mid; ?>" data-msg-type="request">
											<div class="ihc-reqmsg" role="note" aria-label="<?php esc_attr_e( 'Access request', 'insight-hub-dashboard' ); ?>">
												<span class="ihc-reqmsg-ico" aria-hidden="true">
													<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
												</span>
												<div class="tx">
													<?php /* translators: %s: request reference such as REQ-2026-0203 */ ?>
													<b><?php echo esc_html( sprintf( __( 'Your request · %s', 'insight-hub-dashboard' ), $rm_reqref ) ); ?></b>
													<span><?php echo esc_html( $rm_action ); ?></span>
												</div>
												<span class="ihc-statuspill <?php echo esc_attr( strtolower( $rm_status ) ); ?>"><?php echo esc_html( $rm_status ); ?></span>
											</div>
										</div>
										<?php
										continue;
									endif;
									if ( ih_um_is_admin_notice( $msg_body ) ) :
										$notice_lines = preg_split( '/\r\n|\r|\n/', ih_um_admin_notice_text( $msg_body ) );
										?>
										<div class="ih-msg-system-row" data-id="<?php echo $mid; ?>" data-msg-type="notice">
											<div class="ih-msg-notice">
												<span class="ih-msg-notice-ico" aria-hidden="true">
													<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
												</span>
												<div class="tx">
													<?php foreach ( (array) $notice_lines as $ni => $nline ) : ?>
														<?php if ( $ni === 0 ) : ?>
															<b><?php echo esc_html( $nline ); ?></b>
														<?php else : ?>
															<span><?php echo esc_html( $nline ); ?></span>
														<?php endif; ?>
													<?php endforeach; ?>
												</div>
											</div>
										</div>
										<?php
										continue;
									endif;
									$msg_reactions = $reactions_map[ $mid ] ?? array();
									$out_status    = ( $me && function_exists( 'ih_chat_outbound_delivery_status' ) ) ? ih_chat_outbound_delivery_status( $m, false ) : null;
									$row_cls       = ( $me ? 'me' : 'them' ) . ( $me && $out_status && function_exists( 'ih_chat_msg_status_class' ) ? ih_chat_msg_status_class( $out_status ) : '' );
									?>
									<div class="ihc-row <?php echo esc_attr( $row_cls ); ?>" data-id="<?php echo $mid; ?>" data-msg-type="chat">
										<div class="ihc-msg-wrap">
										<div class="ihc-bubble">
											<?php if ( $has_att && ! empty( $m['attachment_url'] ) ) :
												$at = $m['attachment_type'] ?? 'file';
												if ( $at === 'image' ) : ?>
													<a class="ihc-att-img" href="<?php echo esc_url( $m['attachment_url'] ); ?>" target="_blank" rel="noopener">
														<img src="<?php echo esc_url( $m['attachment_url'] ); ?>" alt="">
													</a>
												<?php else : ?>
													<a class="ihc-att-file" href="<?php echo esc_url( $m['attachment_url'] ); ?>" target="_blank" rel="noopener" download>
														<span class="fi"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg></span>
														<span><b class="fn"><?php echo esc_html( $m['attachment_name'] ?? 'Attachment' ); ?></b><span class="fs"><?php esc_html_e( 'File', 'insight-hub-dashboard' ); ?></span></span>
													</a>
												<?php endif;
											endif; ?>
											<?php if ( ! empty( $m['message'] ) ) : ?>
												<div class="bt"><?php echo esc_html( $m['message'] ); ?></div>
											<?php endif; ?>
											<div class="ihc-meta">
												<?php echo esc_html( $tm ); ?>
												<?php if ( $me && $out_status && function_exists( 'ih_chat_render_outbound_meta_icons' ) ) : ?>
													<?php echo ih_chat_render_outbound_meta_icons( $out_status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
												<?php endif; ?>
											</div>
										</div>
										<div class="ihc-reactions"<?php echo empty( $msg_reactions ) ? ' style="display:none"' : ''; ?>>
											<?php echo function_exists( 'ih_chat_render_reaction_chips' ) ? ih_chat_render_reaction_chips( $msg_reactions ) : ''; ?>
										</div>
										</div>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>

							<div class="ihc-row them ihc-hide" id="ihcTyping">
								<div class="ihc-typing"><i></i><i></i><i></i></div>
							</div>
						</div>

						<div class="ihc-composer">
							<div class="ihc-previews" id="ihcPreviews"></div>
							<div class="ihc-bar">
								<div class="ihc-attach">
									<button type="button" class="ihc-rbtn-icon" id="ihcAttachBtn" aria-haspopup="true" aria-expanded="false" aria-controls="ihcAttachMenu" aria-label="<?php esc_attr_e( 'Attach file', 'insight-hub-dashboard' ); ?>">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#5347ce" stroke-width="2" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
									</button>
									<div class="ihc-attmenu" id="ihcAttachMenu" role="menu" aria-hidden="true" aria-label="<?php esc_attr_e( 'Attachment type', 'insight-hub-dashboard' ); ?>">
										<button type="button" role="menuitem" data-pick="image"><?php esc_html_e( 'Photo', 'insight-hub-dashboard' ); ?></button>
										<button type="button" role="menuitem" data-pick="video"><?php esc_html_e( 'Video', 'insight-hub-dashboard' ); ?></button>
										<button type="button" role="menuitem" data-pick="file"><?php esc_html_e( 'Document', 'insight-hub-dashboard' ); ?></button>
									</div>
								</div>
								<button type="button" class="ihc-rbtn-icon ihc-imgbtn" id="ihcImageBtn" aria-label="<?php esc_attr_e( 'Attach photo', 'insight-hub-dashboard' ); ?>">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#5347ce" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.6"/><path d="M21 15l-5-5L5 21"/></svg>
								</button>
								<div class="ihc-input">
									<textarea id="ihcInput" rows="1" placeholder="<?php esc_attr_e( 'Message MouldHub Support…', 'insight-hub-dashboard' ); ?>"></textarea>
									<span class="emoji" id="ihcEmojiBtn" role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Emoji', 'insight-hub-dashboard' ); ?>">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01"/></svg>
										<div class="ihc-emoji" id="ihcEmoji">
											<?php foreach ( array( '😀', '😁', '👍', '🙏', '✅', '📎', '🔧', '⚙️', '🏭', '📦', '💬', '🔥', '👏', '🤝', '📐', '✔️' ) as $em ) : ?>
												<span><?php echo esc_html( $em ); ?></span>
											<?php endforeach; ?>
										</div>
									</span>
								</div>
								<button type="button" class="ihc-send" id="ihcSend" aria-label="<?php esc_attr_e( 'Send', 'insight-hub-dashboard' ); ?>">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="m22 2-7 20-4-9-9-4z"/></svg>
								</button>
							</div>
							<input type="file" id="ihcFileImage" accept="image/*" hidden>
							<input type="file" id="ihcFileDoc" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip" hidden>
							<input type="file" id="ihcFileVideo" accept="video/*" hidden>
						</div>
					</section>

					<aside class="ihc-p3">
						<div class="ihc-supcard">
							<span class="ihc-ava sq" style="width:42px;height:42px;font-size:16px">M<span class="ihc-online off" data-uid="0" style="width:12px;height:12px"></span></span>
							<div class="tx">
								<b><?php esc_html_e( 'MouldHub Support', 'insight-hub-dashboard' ); ?></b>
								<span class="ihc-presence">
									<span class="pd"></span>
									<span><?php esc_html_e( 'Admin online · ~5 min reply', 'insight-hub-dashboard' ); ?></span>
								</span>
							</div>
						</div>

						<div class="ihc-slabel"><?php esc_html_e( 'Request contact information', 'insight-hub-dashboard' ); ?></div>
						<form class="ihc-reqform" id="ihcReqForm" novalidate>
							<p class="ihc-reqform-note"><?php esc_html_e( 'Only available from another user’s listing — enter its ID to ask the admin for an introduction.', 'insight-hub-dashboard' ); ?></p>

							<div class="ihc-seg" role="tablist" aria-label="<?php esc_attr_e( 'Listing type', 'insight-hub-dashboard' ); ?>">
								<button type="button" class="ihc-seg-btn on" role="tab" id="ihcReqTabTool" aria-selected="true" data-type="tool">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18v3h3l6.3-6.3a4 4 0 0 0 5.4-5.4l-2.3 2.3-2-2z"/></svg>
									<?php esc_html_e( 'Tool', 'insight-hub-dashboard' ); ?>
								</button>
								<button type="button" class="ihc-seg-btn" role="tab" id="ihcReqTabMachine" aria-selected="false" data-type="machine">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="8" width="18" height="11" rx="2"/><path d="M7 8V6a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2M7 13h.01M11 13h2"/></svg>
									<?php esc_html_e( 'Machine', 'insight-hub-dashboard' ); ?>
								</button>
							</div>
							<input type="hidden" id="ihcReqType" name="listing_type" value="tool">

							<label class="ihc-reqform-label" for="ihcReqId"><?php esc_html_e( 'Tool / Machine ID', 'insight-hub-dashboard' ); ?> <span class="req">*</span></label>
							<div class="ihc-reqinput">
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#9aa0b4" stroke-width="2" aria-hidden="true"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M7 12h10"/></svg>
								<input type="text" id="ihcReqId" placeholder="<?php esc_attr_e( 'e.g. TL-00231', 'insight-hub-dashboard' ); ?>" autocomplete="off" inputmode="text">
							</div>

							<button type="submit" class="ihc-btn primary ihc-reqsubmit" id="ihcReqSubmit">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4z"/></svg>
								<span><?php esc_html_e( 'Request contact info', 'insight-hub-dashboard' ); ?></span>
							</button>
							<p class="ihc-reqform-hint">
								<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
								<span><?php esc_html_e( 'Admin reviews & approves before any contact details are shared.', 'insight-hub-dashboard' ); ?></span>
							</p>
						</form>

						<div class="ihc-slabel ihc-slabel--row">
							<span><?php esc_html_e( 'Your requests', 'insight-hub-dashboard' ); ?></span>
							<a class="ihc-viewall" href="<?php echo esc_url( admin_url( 'admin.php?page=ih-user-messages' ) ); ?>"><?php esc_html_e( 'View all', 'insight-hub-dashboard' ); ?> &rarr;</a>
						</div>
						<div class="ihc-req-list">
							<?php if ( ! $requests ) : ?>
								<div class="ihc-req-empty"><?php esc_html_e( 'No requests yet.', 'insight-hub-dashboard' ); ?></div>
							<?php endif; ?>
							<?php foreach ( array_slice( $requests, 0, 5 ) as $r ) :
								$rst    = strtolower( $r['status'] ?? 'pending' );
								$rlt    = ( ( $r['listing_type'] ?? '' ) === 'tool' ) ? 'tool' : 'machine';
								$rlid   = intval( $r['listing_id'] ?? 0 );
								$rref   = ih_um_listing_ref( $rlt, $rlid );
								$rlabel = $rref
									/* translators: %s: listing reference such as MCH-00114 */
									? sprintf( __( 'Contact info · %s', 'insight-hub-dashboard' ), $rref )
									: __( 'Profile access', 'insight-hub-dashboard' );
								?>
								<div class="ihc-reqrow">
									<div class="tx">
										<em><?php echo esc_html( ih_um_reqref( $r ) ); ?></em>
										<b><?php echo esc_html( $rlabel ); ?></b>
									</div>
									<span class="ihc-statuspill <?php echo esc_attr( $rst ); ?>"><?php echo esc_html( ucfirst( $rst ) ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>

						<div class="ihc-slabel"><?php esc_html_e( 'Your activity', 'insight-hub-dashboard' ); ?></div>
						<div class="ihc-stats">
							<div class="ihc-stat"><b><?php echo (int) $req_total; ?></b><span><?php esc_html_e( 'Sent', 'insight-hub-dashboard' ); ?></span></div>
							<div class="ihc-stat"><b><?php echo (int) $req_approved; ?></b><span><?php esc_html_e( 'Approved', 'insight-hub-dashboard' ); ?></span></div>
							<div class="ihc-stat"><b><?php echo (int) $req_pending; ?></b><span><?php esc_html_e( 'Pending', 'insight-hub-dashboard' ); ?></span></div>
						</div>

						<div class="ihc-infocard">
							<span class="ihc-infocard-ico" aria-hidden="true">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
							</span>
							<div class="tx">
								<b><?php esc_html_e( 'Admin controls every request', 'insight-hub-dashboard' ); ?></b>
								<span><?php esc_html_e( 'Contact details are only released after the MouldHub team approves your request.', 'insight-hub-dashboard' ); ?></span>
							</div>
						</div>
					</aside>

				</div><!-- /.ihc-area — p1 + p2 + p3 must stay inside this row -->
			</div>
			</div><!-- /.ih-user-messages-root -->
		</div>
	</main>
</div>
</div>

<div class="ihc-toasts" id="ihcToasts"></div>

<script>
window.IHCHAT = {
	ajax: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
	nonce: <?php echo wp_json_encode( $nonce ); ?>,
	role: 'user',
	meId: <?php echo (int) $uid; ?>,
	activeThread: <?php echo (int) $active_thread_id; ?>,
	lastId: <?php echo (int) $last_id; ?>,
	unreadCount: <?php echo (int) $thread_unread; ?>,
	firstUnreadId: <?php echo (int) $first_unread_id; ?>,
	sendAction: 'ih_user_send_message',
	getAction: 'ih_user_get_messages',
	reqAction: 'ih_submit_request',
	reqNonce: <?php echo wp_json_encode( wp_create_nonce( 'ih_request_submit' ) ); ?>,
	meEmail: <?php echo wp_json_encode( $user->user_email ); ?>,
	meName: <?php echo wp_json_encode( $me_name ); ?>
};
</script>
<?php wp_footer(); ?>
</body>
</html>
