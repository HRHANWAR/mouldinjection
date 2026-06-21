<?php
/**
 * user-request-messages.php — Messaging inbox, redesigned to the 3-column
 * Figma layout (conversation list · thread · context rail).
 *
 * Role-aware, two registered slugs:
 *   ih-user-request-messages  → user "support" inbox (user shell)
 *   ih-request-messages       → admin console (plugin admin layout)
 *
 * Thread send/fetch/mark-read still run on the existing ih_rmsg_* AJAX and a
 * thread is only ever shown/openable for an APPROVED ih_requests row. The
 * redesign layers the real request workflow on top:
 *   • user side — the "Request contact information" form creates a PENDING
 *     ih_requests row (admin-brokered; users never message owners directly).
 *   • admin side — inline "Access request" cards Approve/Decline via the
 *     existing ih_update_request_status handler (admin nonce + capability).
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( wp_login_url() );
	exit;
}

global $wpdb;
$ihrm_uid      = (int) get_current_user_id();
$ihrm_is_admin = current_user_can( 'manage_options' );
$ihrm_nonce    = wp_create_nonce( 'ih_nonce' );
$ihrm_role     = $ihrm_is_admin ? 'admin' : 'user';

if ( ! function_exists( 'ihrm_base_slug' ) ) {
	function ihrm_base_slug() {
		return current_user_can( 'manage_options' ) ? 'ih-request-messages' : 'ih-user-request-messages';
	}
}
if ( ! function_exists( 'ihrm_avatar' ) ) {
	/** Deterministic coloured initials avatar (square, brand-tinted). */
	function ihrm_avatar( $name, $seed = 0, $size = 'md' ) {
		$initials = ih_rmsg_initials( $name ?: 'U' );
		$hue      = ( (int) $seed * 47 + 210 ) % 360;
		$style    = sprintf( 'background:linear-gradient(135deg,hsl(%1$d 70%% 58%%),hsl(%2$d 72%% 48%%));', $hue, ( $hue + 24 ) % 360 );
		return '<span class="ihmsg-ava ihmsg-ava--' . esc_attr( $size ) . '" style="' . esc_attr( $style ) . '" aria-hidden="true">' . esc_html( $initials ) . '</span>';
	}
}
if ( ! function_exists( 'ihrm_status_chip' ) ) {
	function ihrm_status_chip( $status ) {
		$key   = strtolower( trim( (string) $status ) );
		$label = ucfirst( $key ?: 'pending' );
		return '<span class="ihmsg-chip ihmsg-chip--' . esc_attr( $key ) . '">' . esc_html( $label ) . '</span>';
	}
}

/* ── Threads (approved-request conversations) ─────────────────────────────── */
$ihrm_threads = ih_rmsg_user_threads( $ihrm_uid, $ihrm_is_admin );

/* ── Active conversation ──────────────────────────────────────────────────── */
$ihrm_active_req  = isset( $_GET['request'] ) ? (int) $_GET['request'] : 0;
$ihrm_active_ctx  = null;
$ihrm_seed_msgs   = array();
$ihrm_is_group    = false;
$ihrm_seed_first_unread = 0;
$ihrm_seed_unread       = 0;
if ( $ihrm_active_req ) {
	$ctx = ih_rmsg_user_can_access( $ihrm_active_req, $ihrm_uid );
	if ( ! is_wp_error( $ctx ) ) {
		$ihrm_active_ctx = $ctx;
		// Group treatment lights up only when a thread really has >2 participants.
		// Multi-party invite has no backend yet, so this stays false in practice.
		$ihrm_is_group = count( (array) ( $ctx['participants'] ?? array() ) ) > 2;
		$rows          = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM ( SELECT * FROM " . ih_rmsg_table() . " WHERE request_id=%d ORDER BY id DESC LIMIT %d ) t ORDER BY id ASC",
				$ihrm_active_req,
				(int) IH_RMSG_PAGE_SIZE
			),
			ARRAY_A
		) ?: array();
		foreach ( $rows as $r ) {
			$ihrm_seed_msgs[] = ih_rmsg_format_message_row( $r, $ihrm_uid, $ihrm_is_group );
		}
		// Capture the "N NEW MESSAGES" divider markers BEFORE marking read on this
		// page load (the JS would otherwise see zero unread after we mark them).
		$ihrm_seed_first_unread = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT MIN(id) FROM ' . ih_rmsg_table() . ' WHERE request_id=%d AND recipient_id=%d AND read_at IS NULL',
				$ihrm_active_req,
				$ihrm_uid
			)
		);
		$ihrm_seed_unread = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . ih_rmsg_table() . ' WHERE request_id=%d AND recipient_id=%d AND read_at IS NULL',
				$ihrm_active_req,
				$ihrm_uid
			)
		);
		ih_rmsg_mark_thread_read( $ihrm_active_req, $ihrm_uid );
	}
}

/* ── Role-specific data ───────────────────────────────────────────────────── */
$ihrm_pending_admin = $ihrm_is_admin ? ih_rmsg_pending_requests( 0, true ) : array();
$ihrm_pending_uids  = $ihrm_is_admin ? ih_rmsg_user_ids_with_pending() : array();
$ihrm_req_stats     = $ihrm_is_admin ? array() : ih_rmsg_request_stats_for_user( $ihrm_uid );
$ihrm_user_pending  = $ihrm_is_admin ? array() : ih_rmsg_pending_requests( $ihrm_uid, false );

/* Threads that are NOT awaiting approval are "approved contacts" for the user. */
$ihrm_unread_threads = 0;
foreach ( $ihrm_threads as $t ) {
	if ( (int) $t['unread'] > 0 ) {
		$ihrm_unread_threads++;
	}
}
$ihrm_new_count = $ihrm_is_admin ? ( $ihrm_unread_threads + count( $ihrm_pending_admin ) ) : $ihrm_unread_threads;

/* ── Active-conversation derived bits ─────────────────────────────────────── */
$ihrm_other_id = $ihrm_other_name = $ihrm_other_ref = $ihrm_ctx_ref = $ihrm_ctx_title = '';
$ihrm_inline_requests = array();
$ihrm_stats           = array();
if ( $ihrm_active_ctx ) {
	$ihrm_other_id   = ( $ihrm_uid === (int) $ihrm_active_ctx['owner_id'] ) ? (int) $ihrm_active_ctx['requester_id'] : (int) $ihrm_active_ctx['owner_id'];
	if ( $ihrm_is_admin ) {
		$ihrm_other_id = (int) $ihrm_active_ctx['requester_id'];
	}
	$ihrm_other_name = ih_rmsg_display_name( $ihrm_other_id );
	$ihrm_other_ref  = function_exists( 'ih_user_ref' ) ? ih_user_ref( $ihrm_other_id ) : ( 'USR-' . $ihrm_other_id );
	$ihrm_ctx_ref    = ih_rmsg_listing_ref( $ihrm_active_ctx['listing_type'], $ihrm_active_ctx['listing_id'] );
	$ihrm_ctx_title  = ih_rmsg_listing_title( $ihrm_active_ctx['listing_type'], $ihrm_active_ctx['listing_id'] );
	if ( $ihrm_is_admin ) {
		$ihrm_inline_requests = ih_rmsg_pending_requests( (int) $ihrm_active_ctx['requester_id'], false );
		$ihrm_stats           = ih_rmsg_thread_message_stats( $ihrm_active_req, (int) $ihrm_active_ctx['requester_id'] );
	}
}

/* Active-thread shared state used by the chat header + right rail. */
$ihrm_settings      = $ihrm_active_ctx ? ih_rmsg_get_settings( $ihrm_active_req ) : ih_rmsg_default_settings();
$ihrm_shared        = $ihrm_active_ctx ? ih_rmsg_shared_files( $ihrm_active_req, 8 ) : array();
$ihrm_shared_count  = $ihrm_active_ctx ? ih_rmsg_shared_files_count( $ihrm_active_req ) : 0;
$ihrm_other_online  = ( $ihrm_active_ctx && $ihrm_other_id ) ? ih_rmsg_is_online( (int) $ihrm_other_id ) : false;
$ihrm_pcount        = $ihrm_active_ctx ? count( (array) ( $ihrm_active_ctx['participants'] ?? array() ) ) : 0;

/* ════════════════════════════════════════════════════════════════════════════
   Build the inner markup (shared by both wrappers)
═══════════════════════════════════════════════════════════════════════════ */
ob_start();
?>
<div class="ihmsg ihmsg--<?php echo esc_attr( $ihrm_role ); ?><?php echo $ihrm_active_ctx ? ' has-active' : ''; ?>" data-ihmsg data-ihmsg-role="<?php echo esc_attr( $ihrm_role ); ?>">

	<!-- ════ Column 1 — conversation list ════ -->
	<aside class="ihmsg-col ihmsg-list" data-ihmsg-list-col>
		<div class="ihmsg-list__head">
			<h2 class="ihmsg-list__title"><?php esc_html_e( 'Messages', 'insight-hub-dashboard' ); ?></h2>
			<?php if ( $ihrm_new_count > 0 ) : ?>
				<span class="ihmsg-newbadge"><?php echo esc_html( sprintf( _n( '%d new', '%d new', $ihrm_new_count, 'insight-hub-dashboard' ), $ihrm_new_count ) ); ?></span>
			<?php endif; ?>
		</div>

		<div class="ihmsg-search">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
			<input type="search" data-ihmsg-search placeholder="<?php esc_attr_e( 'Search conversations', 'insight-hub-dashboard' ); ?>" autocomplete="off" aria-label="<?php esc_attr_e( 'Search conversations', 'insight-hub-dashboard' ); ?>">
		</div>

		<?php if ( $ihrm_is_admin ) : ?>
			<div class="ihmsg-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Filter conversations', 'insight-hub-dashboard' ); ?>">
				<button type="button" class="ihmsg-tab is-active" data-ihmsg-tab="all" role="tab" aria-selected="true"><?php esc_html_e( 'All', 'insight-hub-dashboard' ); ?></button>
				<button type="button" class="ihmsg-tab" data-ihmsg-tab="unread" role="tab" aria-selected="false"><?php esc_html_e( 'Unread', 'insight-hub-dashboard' ); ?></button>
				<button type="button" class="ihmsg-tab" data-ihmsg-tab="requests" role="tab" aria-selected="false">
					<?php esc_html_e( 'Requests', 'insight-hub-dashboard' ); ?>
					<?php if ( $ihrm_pending_admin ) : ?><span class="ihmsg-tab__count"><?php echo (int) count( $ihrm_pending_admin ); ?></span><?php endif; ?>
				</button>
			</div>
		<?php endif; ?>

		<div class="ihmsg-threads" data-ihmsg-threads>

			<!-- Conversation rows (All / Unread) -->
			<div class="ihmsg-pane" data-ihmsg-pane="threads">
				<?php
				if ( ! $ihrm_is_admin ) {
					echo '<div class="ihmsg-grouplabel">' . esc_html__( 'Support', 'insight-hub-dashboard' ) . '</div>';
					$support_url = admin_url( 'admin.php?page=ih-user-messages' );
					?>
					<a class="ihmsg-thread ihmsg-thread--support" href="<?php echo esc_url( $support_url ); ?>">
						<span class="ihmsg-ava ihmsg-ava--md ihmsg-ava--brand" aria-hidden="true">M</span>
						<span class="ihmsg-thread__body">
							<span class="ihmsg-thread__r1">
								<span class="ihmsg-thread__name"><?php esc_html_e( 'MouldHub Support', 'insight-hub-dashboard' ); ?></span>
							</span>
							<span class="ihmsg-thread__sub"><span class="ihmsg-dot ihmsg-dot--on"></span><?php esc_html_e( 'Admin team · replies in ~5 min', 'insight-hub-dashboard' ); ?></span>
						</span>
						<span class="ihmsg-thread__chev" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
						</span>
					</a>
					<?php
					echo '<div class="ihmsg-grouplabel">' . esc_html__( 'Approved contacts · via your requests', 'insight-hub-dashboard' ) . '</div>';
				}

				if ( empty( $ihrm_threads ) ) {
					echo '<div class="ihmsg-empty ihmsg-empty--list">' . esc_html__( 'No approved conversations yet. They appear here once an admin approves a request.', 'insight-hub-dashboard' ) . '</div>';
				} else {
					foreach ( $ihrm_threads as $t ) :
						$rid    = (int) $t['request_id'];
						$href   = admin_url( 'admin.php?page=' . ihrm_base_slug() . '&request=' . $rid );
						$active = ( $rid === $ihrm_active_req );
						$nm     = $t['other_name'] ?: __( 'Participant', 'insight-hub-dashboard' );
						$ref    = $ihrm_is_admin && function_exists( 'ih_user_ref' ) ? ih_user_ref( (int) $t['other_id'] ) : '';
						$ctxlbl = trim( $t['listing_ref'] . ( $t['listing_title'] ? ' · ' . $t['listing_title'] : '' ) );
						$has_pending = $ihrm_is_admin && in_array( (int) $t['other_id'], $ihrm_pending_uids, true );
						?>
						<a class="ihmsg-thread<?php echo $active ? ' is-active' : ''; ?><?php echo (int) $t['unread'] > 0 ? ' is-unread' : ''; ?>"
							href="<?php echo esc_url( $href ); ?>"
							data-ihmsg-thread
							data-unread="<?php echo (int) $t['unread'] > 0 ? '1' : '0'; ?>"
							data-name="<?php echo esc_attr( strtolower( $nm . ' ' . $ref . ' ' . $ctxlbl ) ); ?>">
							<?php echo ihrm_avatar( $nm, (int) $t['other_id'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span class="ihmsg-thread__body">
								<span class="ihmsg-thread__r1">
									<span class="ihmsg-thread__name"><?php echo esc_html( $nm ); ?><?php echo $ref ? ' <em>· ' . esc_html( $ref ) . '</em>' : ''; ?></span>
									<span class="ihmsg-thread__time"><?php echo esc_html( $t['last_time'] ); ?></span>
								</span>
								<span class="ihmsg-thread__last"><?php echo esc_html( $t['last_body'] ? wp_trim_words( $t['last_body'], 8, '…' ) : __( 'No messages yet', 'insight-hub-dashboard' ) ); ?></span>
							</span>
							<span class="ihmsg-thread__aside">
								<?php if ( $has_pending ) : ?><span class="ihmsg-tag"><?php esc_html_e( 'Request', 'insight-hub-dashboard' ); ?></span><?php endif; ?>
								<span class="ihmsg-badge" data-rmsg-badge="<?php echo $rid; ?>"<?php echo (int) $t['unread'] > 0 ? '' : ' hidden style="display:none"'; ?>><?php echo (int) $t['unread']; ?></span>
							</span>
						</a>
					<?php endforeach;
				}

				/* User: awaiting-approval pending requests (no thread yet). */
				if ( ! $ihrm_is_admin && $ihrm_user_pending ) {
					echo '<div class="ihmsg-grouplabel">' . esc_html__( 'Awaiting admin approval', 'insight-hub-dashboard' ) . '</div>';
					foreach ( $ihrm_user_pending as $r ) :
						$lbl = ih_rmsg_request_label( $r );
						?>
						<div class="ihmsg-thread ihmsg-thread--pending" aria-disabled="true">
							<span class="ihmsg-ava ihmsg-ava--md ihmsg-ava--muted" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
							</span>
							<span class="ihmsg-thread__body">
								<span class="ihmsg-thread__r1"><span class="ihmsg-thread__name"><?php echo esc_html( $lbl ); ?></span></span>
								<span class="ihmsg-thread__last"><?php echo esc_html( ih_rmsg_request_ref( $r ) ); ?> · <?php esc_html_e( 'pending review', 'insight-hub-dashboard' ); ?></span>
							</span>
							<span class="ihmsg-thread__aside"><?php echo ihrm_status_chip( 'pending' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						</div>
					<?php endforeach;
				}
				?>
			</div>

			<?php if ( $ihrm_is_admin ) : ?>
				<!-- Requests tab pane -->
				<div class="ihmsg-pane" data-ihmsg-pane="requests" hidden>
					<?php if ( empty( $ihrm_pending_admin ) ) : ?>
						<div class="ihmsg-empty ihmsg-empty--list"><?php esc_html_e( 'No pending requests. New access requests will appear here for review.', 'insight-hub-dashboard' ); ?></div>
					<?php else : ?>
						<?php foreach ( $ihrm_pending_admin as $r ) :
							$ruid = (int) $r['user_id'];
							$rnm  = ih_rmsg_display_name( $ruid );
							?>
							<div class="ihmsg-reqcard" data-ihmsg-reqcard data-request-id="<?php echo (int) $r['id']; ?>">
								<div class="ihmsg-reqcard__top">
									<?php echo ihrm_avatar( $rnm, $ruid, 'sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<div class="ihmsg-reqcard__meta">
										<b><?php echo esc_html( $rnm ); ?></b>
										<span><?php echo esc_html( ih_rmsg_request_ref( $r ) . ' · ' . ih_rmsg_request_label( $r ) ); ?></span>
									</div>
								</div>
								<div class="ihmsg-reqcard__actions" data-ihmsg-reqactions>
									<button type="button" class="ihmsg-btn ihmsg-btn--approve" data-ihmsg-approve data-request-id="<?php echo (int) $r['id']; ?>">
										<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
										<?php esc_html_e( 'Approve', 'insight-hub-dashboard' ); ?>
									</button>
									<button type="button" class="ihmsg-btn ihmsg-btn--decline" data-ihmsg-decline data-request-id="<?php echo (int) $r['id']; ?>"><?php esc_html_e( 'Decline', 'insight-hub-dashboard' ); ?></button>
								</div>
								<div class="ihmsg-reqcard__result" data-ihmsg-reqresult hidden></div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>

		</div>
	</aside>

	<!-- ════ Column 2 — thread ════ -->
	<section class="ihmsg-col ihmsg-chat" data-ihmsg-thread-col>
		<?php if ( ! $ihrm_active_ctx ) : ?>
			<div class="ihmsg-placeholder">
				<div class="ihmsg-placeholder__icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
				</div>
				<p class="ihmsg-placeholder__title"><?php echo $ihrm_is_admin ? esc_html__( 'No conversation selected', 'insight-hub-dashboard' ) : esc_html__( 'MouldHub Support', 'insight-hub-dashboard' ); ?></p>
				<p class="ihmsg-placeholder__sub">
					<?php
					echo $ihrm_is_admin
						? esc_html__( 'Pick a conversation on the left to start chatting, share files, or approve a contact request.', 'insight-hub-dashboard' )
						: esc_html__( 'Use “Request contact information” on the right to ask an admin for access — they broker every request. Approved contacts appear in the list.', 'insight-hub-dashboard' );
					?>
				</p>
				<?php if ( $ihrm_is_admin ) : ?>
					<div class="ihmsg-placeholder__note">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
						<span><?php esc_html_e( 'Users request contact details from a listing — they’ll appear here for approval.', 'insight-hub-dashboard' ); ?></span>
					</div>
				<?php else : ?>
					<a class="ihmsg-btn ihmsg-btn--primary" href="<?php echo esc_url( admin_url( 'admin.php?page=ih-user-messages' ) ); ?>"><?php esc_html_e( 'Open MouldHub Support', 'insight-hub-dashboard' ); ?></a>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<header class="ihmsg-chat__head">
				<button type="button" class="ihmsg-back" data-ihmsg-back aria-label="<?php esc_attr_e( 'Back to conversations', 'insight-hub-dashboard' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
				</button>
				<?php if ( $ihrm_is_group ) :
					$ihrm_pnames = array();
					$ihrm_pids   = array();
					foreach ( (array) $ihrm_active_ctx['participants'] as $ihrm_pid ) {
						if ( (int) $ihrm_pid === $ihrm_uid ) { continue; }
						$ihrm_pnames[] = ih_rmsg_display_name( $ihrm_pid );
						$ihrm_pids[]   = (int) $ihrm_pid;
					}
					?>
					<span class="ihmsg-avastack" aria-hidden="true">
						<?php foreach ( array_slice( $ihrm_pids, 0, 3 ) as $ihrm_sp ) {
							echo ihrm_avatar( ih_rmsg_display_name( $ihrm_sp ), $ihrm_sp, 'sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} ?>
					</span>
					<div class="ihmsg-chat__who">
						<span class="ihmsg-chat__title"><?php echo esc_html( implode( ', ', $ihrm_pnames ) ); ?> <em><?php esc_html_e( '& you', 'insight-hub-dashboard' ); ?></em></span>
						<span class="ihmsg-chat__sub"><span class="ihmsg-dot ihmsg-dot--on"></span><?php echo esc_html( sprintf( _n( '%d participant', '%d participants', $ihrm_pcount, 'insight-hub-dashboard' ), $ihrm_pcount ) ); ?></span>
					</div>
				<?php else : ?>
					<?php echo ihrm_avatar( $ihrm_other_name, $ihrm_other_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<div class="ihmsg-chat__who">
						<span class="ihmsg-chat__title"><?php echo esc_html( $ihrm_other_name ); ?><?php echo $ihrm_other_ref ? ' <em>· ' . esc_html( $ihrm_other_ref ) . '</em>' : ''; ?></span>
						<span class="ihmsg-chat__sub" data-ihmsg-presence>
							<span class="ihmsg-dot<?php echo $ihrm_other_online ? ' ihmsg-dot--on' : ''; ?>"></span>
							<span data-ihmsg-presence-text><?php echo $ihrm_other_online ? esc_html__( 'Online now', 'insight-hub-dashboard' ) : esc_html__( 'Offline', 'insight-hub-dashboard' ); ?></span>
						</span>
					</div>
				<?php endif; ?>
				<div class="ihmsg-chat__actions">
					<button type="button" class="ihmsg-iconbtn ihmsg-iconbtn--ghost" data-ihmsg-call disabled title="<?php esc_attr_e( 'Voice call', 'insight-hub-dashboard' ); ?>" aria-label="<?php esc_attr_e( 'Voice call', 'insight-hub-dashboard' ); ?>">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
					</button>
					<button type="button" class="ihmsg-iconbtn ihmsg-iconbtn--ghost" data-ihmsg-video disabled title="<?php esc_attr_e( 'Video call', 'insight-hub-dashboard' ); ?>" aria-label="<?php esc_attr_e( 'Video call', 'insight-hub-dashboard' ); ?>">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m23 7-7 5 7 5z"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
					</button>
					<?php if ( $ihrm_is_admin ) : ?>
						<button type="button" class="ihmsg-btn ihmsg-btn--invite" data-ihmsg-invite-open data-request-id="<?php echo (int) $ihrm_active_req; ?>">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M3 21a6 6 0 0 1 12 0M19 8v6M22 11h-6"/></svg>
							<?php esc_html_e( 'Invite', 'insight-hub-dashboard' ); ?>
						</button>
					<?php endif; ?>
					<div class="ihmsg-overflow" data-ihmsg-overflow>
						<button type="button" class="ihmsg-iconbtn" data-ihmsg-overflow-btn aria-haspopup="true" aria-expanded="false" aria-label="<?php esc_attr_e( 'More options', 'insight-hub-dashboard' ); ?>">
							<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
						</button>
						<div class="ihmsg-overflow__menu" data-ihmsg-overflow-menu hidden role="menu">
							<?php if ( $ihrm_is_admin ) : ?>
								<a role="menuitem" href="<?php echo esc_url( admin_url( 'admin.php?page=ih-users&view=' . $ihrm_other_id ) ); ?>"><?php esc_html_e( 'View profile', 'insight-hub-dashboard' ); ?></a>
							<?php endif; ?>
							<button type="button" role="menuitem" data-ihmsg-rail-toggle><?php esc_html_e( 'Conversation details', 'insight-hub-dashboard' ); ?></button>
						</div>
					</div>
				</div>
			</header>

			<div class="ihmsg-chat__scroll">
				<div class="ih-rmsg-thread-view" data-rmsg-thread-view data-request-id="<?php echo (int) $ihrm_active_req; ?>" data-seed-firstunread="<?php echo (int) $ihrm_seed_first_unread; ?>" data-seed-unread="<?php echo (int) $ihrm_seed_unread; ?>">
					<div class="ih-rmsg-list ihmsg-msgs" data-rmsg-list aria-live="polite">
						<button type="button" class="ih-rmsg-loadmore" data-rmsg-loadmore hidden><?php esc_html_e( 'Load earlier messages', 'insight-hub-dashboard' ); ?></button>
						<div class="ih-rmsg-empty" data-rmsg-empty<?php echo ! empty( $ihrm_seed_msgs ) ? ' style="display:none"' : ''; ?>><?php esc_html_e( 'No messages yet. Say hello!', 'insight-hub-dashboard' ); ?></div>
						<?php echo ih_rmsg_render_seed( $ihrm_seed_msgs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside helper ?>

						<?php if ( $ihrm_is_admin && $ihrm_inline_requests ) : ?>
							<div class="ihmsg-inline-requests" data-ihmsg-inline-requests>
								<?php foreach ( $ihrm_inline_requests as $r ) : ?>
									<div class="ihmsg-reqcard ihmsg-reqcard--inline" data-ihmsg-reqcard data-request-id="<?php echo (int) $r['id']; ?>">
										<div class="ihmsg-reqcard__top">
											<span class="ihmsg-reqcard__icon" aria-hidden="true">
												<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
											</span>
											<div class="ihmsg-reqcard__meta">
												<b><?php echo esc_html( sprintf( __( 'Access request · %s', 'insight-hub-dashboard' ), ih_rmsg_request_ref( $r ) ) ); ?></b>
												<span><?php echo esc_html( sprintf( __( '%1$s requested %2$s.', 'insight-hub-dashboard' ), $ihrm_other_name, ih_rmsg_request_label( $r ) ) ); ?></span>
											</div>
										</div>
										<div class="ihmsg-reqcard__actions" data-ihmsg-reqactions>
											<button type="button" class="ihmsg-btn ihmsg-btn--approve" data-ihmsg-approve data-request-id="<?php echo (int) $r['id']; ?>">
												<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
												<?php esc_html_e( 'Approve', 'insight-hub-dashboard' ); ?>
											</button>
											<button type="button" class="ihmsg-btn ihmsg-btn--decline" data-ihmsg-decline data-request-id="<?php echo (int) $r['id']; ?>"><?php esc_html_e( 'Decline', 'insight-hub-dashboard' ); ?></button>
										</div>
										<div class="ihmsg-reqcard__result" data-ihmsg-reqresult hidden></div>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
					<button type="button" class="ihmsg-scrolldown" data-rmsg-scrolldown hidden aria-label="<?php esc_attr_e( 'Jump to latest messages', 'insight-hub-dashboard' ); ?>">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>
						<span class="ihmsg-scrolldown__dot" data-rmsg-scrolldown-dot hidden></span>
					</button>
					<div class="ih-rmsg-error" data-rmsg-error role="alert"></div>
					<?php $ihrm_attach_ok = ! empty( $ihrm_settings['allow_attachments'] ); ?>
					<form class="ih-rmsg-form ihmsg-composer" data-rmsg-form data-attach-allowed="<?php echo $ihrm_attach_ok ? '1' : '0'; ?>">
						<div class="ihmsg-composer__chips" data-ihmsg-chips hidden></div>
						<div class="ihmsg-composer__row">
							<input type="file" data-ihmsg-file-any hidden accept=".pdf,.doc,.docx,.xls,.xlsx,.zip,image/*,video/mp4,video/webm,video/quicktime">
							<input type="file" data-ihmsg-file-img hidden accept="image/*">
							<button type="button" class="ihmsg-composer__icon" data-ihmsg-attach <?php disabled( ! $ihrm_attach_ok ); ?> title="<?php echo $ihrm_attach_ok ? esc_attr__( 'Attach a file', 'insight-hub-dashboard' ) : esc_attr__( 'Attachments are disabled', 'insight-hub-dashboard' ); ?>" aria-label="<?php esc_attr_e( 'Attach a file', 'insight-hub-dashboard' ); ?>">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a5 5 0 0 1-7.07-7.07l9.19-9.19a3.5 3.5 0 0 1 4.95 4.95l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
							</button>
							<button type="button" class="ihmsg-composer__icon" data-ihmsg-image <?php disabled( ! $ihrm_attach_ok ); ?> title="<?php echo $ihrm_attach_ok ? esc_attr__( 'Send a photo', 'insight-hub-dashboard' ) : esc_attr__( 'Attachments are disabled', 'insight-hub-dashboard' ); ?>" aria-label="<?php esc_attr_e( 'Send a photo', 'insight-hub-dashboard' ); ?>">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
							</button>
							<textarea class="ih-rmsg-input ihmsg-composer__input" data-rmsg-input rows="1" maxlength="<?php echo (int) IH_RMSG_MAX_LEN; ?>" placeholder="<?php echo esc_attr( sprintf( __( 'Type a message to %s…', 'insight-hub-dashboard' ), $ihrm_other_name ) ); ?>"></textarea>
							<div class="ihmsg-emoji" data-ihmsg-emoji>
								<button type="button" class="ihmsg-composer__icon" data-ihmsg-emoji-btn aria-label="<?php esc_attr_e( 'Insert emoji', 'insight-hub-dashboard' ); ?>" aria-haspopup="true" aria-expanded="false">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01"/></svg>
								</button>
								<div class="ihmsg-emoji__pop" data-ihmsg-emoji-pop hidden role="menu"></div>
							</div>
							<button type="submit" class="ih-rmsg-send ihmsg-composer__send" data-rmsg-send aria-label="<?php esc_attr_e( 'Send', 'insight-hub-dashboard' ); ?>">
								<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4z"/></svg>
							</button>
						</div>
					</form>
				</div>
			</div>
		<?php endif; ?>
	</section>

	<!-- ════ Column 3 — context rail ════ -->
	<aside class="ihmsg-col ihmsg-rail" data-ihmsg-rail-col>
		<?php if ( $ihrm_is_admin ) : ?>
			<?php if ( $ihrm_active_ctx ) : ?>
				<div class="ihmsg-rail__profile">
					<?php echo ihrm_avatar( $ihrm_other_name, $ihrm_other_id, 'lg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<b class="ihmsg-rail__name"><?php echo esc_html( $ihrm_other_name ); ?></b>
					<span class="ihmsg-rail__ref"><?php echo esc_html( trim( $ihrm_other_ref . ( $ihrm_ctx_title ? ' · ' . $ihrm_ctx_title : '' ) ) ); ?></span>
					<span class="ihmsg-rail__status"><span class="ihmsg-dot<?php echo $ihrm_other_online ? ' ihmsg-dot--on' : ''; ?>"></span><?php echo $ihrm_other_online ? esc_html__( 'Online now', 'insight-hub-dashboard' ) : esc_html__( 'Offline', 'insight-hub-dashboard' ); ?></span>
					<div class="ihmsg-rail__profile-actions">
						<a class="ihmsg-btn ihmsg-btn--primary" href="<?php echo esc_url( admin_url( 'admin.php?page=ih-users&view=' . $ihrm_other_id ) ); ?>"><?php esc_html_e( 'View profile', 'insight-hub-dashboard' ); ?></a>
						<button type="button" class="ihmsg-iconbtn ihmsg-iconbtn--danger" data-ihmsg-block disabled title="<?php esc_attr_e( 'Block user', 'insight-hub-dashboard' ); ?>" aria-label="<?php esc_attr_e( 'Block user', 'insight-hub-dashboard' ); ?>">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m5.6 5.6 12.8 12.8"/></svg>
						</button>
					</div>
				</div>

				<div class="ihmsg-rail__label"><?php esc_html_e( 'Conversation stats', 'insight-hub-dashboard' ); ?></div>
				<div class="ihmsg-statgrid">
					<div class="ihmsg-stat"><b><?php echo (int) ( $ihrm_stats['messages'] ?? 0 ); ?></b><span><?php esc_html_e( 'Messages', 'insight-hub-dashboard' ); ?></span></div>
					<?php if ( ! empty( $ihrm_stats['avg_reply'] ) ) : ?>
						<div class="ihmsg-stat"><b><?php echo esc_html( $ihrm_stats['avg_reply'] ); ?></b><span><?php esc_html_e( 'Avg reply', 'insight-hub-dashboard' ); ?></span></div>
					<?php endif; ?>
					<div class="ihmsg-stat"><b><?php echo (int) ( $ihrm_stats['requests'] ?? 0 ); ?></b><span><?php esc_html_e( 'Requests', 'insight-hub-dashboard' ); ?></span></div>
				</div>

				<?php if ( ! empty( $ihrm_stats['messages'] ) ) : ?>
					<div class="ihmsg-card">
						<div class="ihmsg-card__head">
							<span><?php esc_html_e( 'Messages · last 7 days', 'insight-hub-dashboard' ); ?></span>
							<?php
							$ihrm_delta_dir = $ihrm_stats['delta_dir'] ?? 'flat';
							$ihrm_delta_val = (int) ( $ihrm_stats['delta'] ?? 0 );
							if ( 'flat' !== $ihrm_delta_dir && $ihrm_delta_val > 0 ) : ?>
								<span class="ihmsg-delta ihmsg-delta--<?php echo esc_attr( $ihrm_delta_dir ); ?>"><?php echo ( 'up' === $ihrm_delta_dir ? '▲' : '▼' ); ?> <?php echo (int) $ihrm_delta_val; ?>%</span>
							<?php endif; ?>
						</div>
						<div class="ihmsg-bars">
							<?php for ( $i = 0; $i < 7; $i++ ) :
								$v   = (int) ( $ihrm_stats['bars'][ $i ] ?? 0 );
								$max = max( 1, (int) ( $ihrm_stats['bars_max'] ?? 1 ) );
								$h   = max( 6, (int) round( $v / $max * 56 ) );
								?>
								<div class="ihmsg-bars__col">
									<div class="ihmsg-bars__bar<?php echo ( $v > 0 && $v === $max ) ? ' is-hi' : ''; ?>" style="height:<?php echo (int) $h; ?>px"></div>
									<em><?php echo esc_html( $ihrm_stats['labels'][ $i ] ?? '' ); ?></em>
								</div>
							<?php endfor; ?>
						</div>
					</div>
				<?php endif; ?>

				<div class="ihmsg-rail__label"><?php esc_html_e( 'Access & controls', 'insight-hub-dashboard' ); ?></div>
				<div class="ihmsg-toggles" data-ihmsg-toggles data-request-id="<?php echo (int) $ihrm_active_req; ?>">
					<?php
					$toggles = array(
						array( 'profile_access', __( 'Profile access', 'insight-hub-dashboard' ), __( 'Granted — this user can view the profile', 'insight-hub-dashboard' ) ),
						array( 'listing_access', __( 'Listing access', 'insight-hub-dashboard' ), sprintf( __( '%s visible to this user', 'insight-hub-dashboard' ), $ihrm_ctx_ref ?: __( 'Listing', 'insight-hub-dashboard' ) ) ),
						array( 'allow_attachments', __( 'Allow attachments', 'insight-hub-dashboard' ), __( 'Images, docs & video enabled', 'insight-hub-dashboard' ) ),
					);
					foreach ( $toggles as $tg ) :
						$on = ! empty( $ihrm_settings[ $tg[0] ] );
						?>
						<div class="ihmsg-toggle">
							<div class="ihmsg-toggle__tx"><b><?php echo esc_html( $tg[1] ); ?></b><span><?php echo esc_html( $tg[2] ); ?></span></div>
							<button type="button" class="ihmsg-switch" role="switch" data-ihmsg-toggle="<?php echo esc_attr( $tg[0] ); ?>" aria-checked="<?php echo $on ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( $tg[1] ); ?>"></button>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="ihmsg-rail__label ihmsg-rail__label--row">
					<span><?php esc_html_e( 'Shared files', 'insight-hub-dashboard' ); ?></span>
					<?php if ( $ihrm_shared_count > count( $ihrm_shared ) ) : ?>
						<span class="ihmsg-rail__count"><?php echo (int) $ihrm_shared_count; ?> →</span>
					<?php endif; ?>
				</div>
				<?php if ( empty( $ihrm_shared ) ) : ?>
					<div class="ihmsg-shared ihmsg-shared--empty" data-ihmsg-shared><?php esc_html_e( 'No shared files yet.', 'insight-hub-dashboard' ); ?></div>
				<?php else : ?>
					<div class="ihmsg-shared" data-ihmsg-shared>
						<?php foreach ( $ihrm_shared as $sf ) :
							$is_img = ( 'image' === $sf['type'] );
							?>
							<a class="ihmsg-shared__item ihmsg-shared__item--<?php echo esc_attr( $sf['type'] ); ?>" href="<?php echo esc_url( $sf['url'] ); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr( $sf['name'] ); ?>">
								<?php if ( $is_img ) : ?>
									<img src="<?php echo esc_url( $sf['url'] ); ?>" alt="<?php echo esc_attr( $sf['name'] ); ?>" loading="lazy">
								<?php else : ?>
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
								<?php endif; ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<div class="ihmsg-rail__label"><?php esc_html_e( 'Participants', 'insight-hub-dashboard' ); ?></div>
				<div class="ihmsg-participants" data-ihmsg-participants>
					<div class="ihmsg-participant">
						<?php echo ihrm_avatar( wp_get_current_user()->display_name, $ihrm_uid, 'sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<div class="ihmsg-participant__tx"><b><?php esc_html_e( 'You (Admin)', 'insight-hub-dashboard' ); ?></b><span><?php esc_html_e( 'Owner · controls the chat', 'insight-hub-dashboard' ); ?></span></div>
					</div>
					<div class="ihmsg-participant">
						<?php echo ihrm_avatar( $ihrm_other_name, $ihrm_other_id, 'sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<div class="ihmsg-participant__tx"><b><?php echo esc_html( $ihrm_other_name ); ?></b><span><?php echo esc_html( $ihrm_other_ref ); ?></span></div>
					</div>
					<?php
					foreach ( (array) ( $ihrm_active_ctx['guests'] ?? array() ) as $ihrm_guest_id ) :
						$ihrm_guest_id = (int) $ihrm_guest_id;
						if ( ! $ihrm_guest_id ) { continue; }
						$ihrm_guest_name = ih_rmsg_display_name( $ihrm_guest_id );
						$ihrm_guest_ref  = function_exists( 'ih_user_ref' ) ? ih_user_ref( $ihrm_guest_id ) : ( 'USR-' . $ihrm_guest_id );
						?>
						<div class="ihmsg-participant" data-guest-id="<?php echo (int) $ihrm_guest_id; ?>">
							<?php echo ihrm_avatar( $ihrm_guest_name, $ihrm_guest_id, 'sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<div class="ihmsg-participant__tx"><b><?php echo esc_html( $ihrm_guest_name ); ?></b><span><?php echo esc_html( $ihrm_guest_ref ); ?> · <?php esc_html_e( 'Guest', 'insight-hub-dashboard' ); ?></span></div>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="ihmsg-btn ihmsg-btn--ghost ihmsg-btn--block" data-ihmsg-invite-open data-request-id="<?php echo (int) $ihrm_active_req; ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M3 21a6 6 0 0 1 12 0M19 8v6M22 11h-6"/></svg>
					<?php esc_html_e( 'Invite a user to this chat', 'insight-hub-dashboard' ); ?>
				</button>
			<?php else : ?>
				<div class="ihmsg-rail__empty"><?php esc_html_e( 'Open a conversation to see profile, stats and controls.', 'insight-hub-dashboard' ); ?></div>
			<?php endif; ?>

		<?php else : /* ── USER rail ── */ ?>
			<div class="ihmsg-rail__support">
				<span class="ihmsg-ava ihmsg-ava--md ihmsg-ava--brand" aria-hidden="true">M</span>
				<div class="ihmsg-rail__support-tx">
					<b><?php esc_html_e( 'MouldHub Support', 'insight-hub-dashboard' ); ?></b>
					<span><span class="ihmsg-dot ihmsg-dot--on"></span><?php esc_html_e( 'Admin online · ~5 min reply', 'insight-hub-dashboard' ); ?></span>
				</div>
			</div>

			<div class="ihmsg-rail__label"><?php esc_html_e( 'Request contact information', 'insight-hub-dashboard' ); ?></div>
			<form class="ihmsg-reqform" data-ihmsg-reqform>
				<p class="ihmsg-reqform__note"><?php esc_html_e( 'Only available from another user’s listing — enter its ID to ask an admin for contact details.', 'insight-hub-dashboard' ); ?></p>
				<div class="ihmsg-segment" role="radiogroup" aria-label="<?php esc_attr_e( 'Listing type', 'insight-hub-dashboard' ); ?>">
					<button type="button" class="ihmsg-segment__opt is-active" data-ihmsg-type="tool" role="radio" aria-checked="true"><?php esc_html_e( 'Tool', 'insight-hub-dashboard' ); ?></button>
					<button type="button" class="ihmsg-segment__opt" data-ihmsg-type="machine" role="radio" aria-checked="false"><?php esc_html_e( 'Machine', 'insight-hub-dashboard' ); ?></button>
				</div>
				<input type="hidden" data-ihmsg-type-value value="tool">
				<label class="ihmsg-field">
					<span class="ihmsg-field__label"><?php esc_html_e( 'Tool / Machine ID', 'insight-hub-dashboard' ); ?> *</span>
					<input type="text" data-ihmsg-ref placeholder="<?php esc_attr_e( 'e.g. TL-00231', 'insight-hub-dashboard' ); ?>" autocomplete="off" required>
				</label>
				<button type="submit" class="ihmsg-btn ihmsg-btn--primary ihmsg-btn--block" data-ihmsg-reqsubmit>
					<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4z"/></svg>
					<?php esc_html_e( 'Request contact info', 'insight-hub-dashboard' ); ?>
				</button>
				<div class="ihmsg-reqform__msg" data-ihmsg-reqmsg role="status" hidden></div>
				<p class="ihmsg-reqform__hint"><?php esc_html_e( 'Admin reviews & approves before any contact details are shared.', 'insight-hub-dashboard' ); ?></p>
			</form>

			<div class="ihmsg-rail__label ihmsg-rail__label--row">
				<span><?php esc_html_e( 'Your requests', 'insight-hub-dashboard' ); ?></span>
			</div>
			<div class="ihmsg-reqlist" data-ihmsg-reqlist>
				<?php if ( empty( $ihrm_req_stats['recent'] ) ) : ?>
					<div class="ihmsg-empty"><?php esc_html_e( 'No requests yet.', 'insight-hub-dashboard' ); ?></div>
				<?php else : ?>
					<?php foreach ( $ihrm_req_stats['recent'] as $r ) : ?>
						<div class="ihmsg-reqrow">
							<div class="ihmsg-reqrow__tx">
								<em><?php echo esc_html( ih_rmsg_request_ref( $r ) ); ?></em>
								<b><?php echo esc_html( ih_rmsg_request_label( $r ) ); ?></b>
							</div>
							<?php echo ihrm_status_chip( $r['status'] ?? 'pending' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<div class="ihmsg-rail__label"><?php esc_html_e( 'Your activity', 'insight-hub-dashboard' ); ?></div>
			<div class="ihmsg-statgrid" data-ihmsg-activity>
				<div class="ihmsg-stat"><b data-ihmsg-stat="sent"><?php echo (int) ( $ihrm_req_stats['sent'] ?? 0 ); ?></b><span><?php esc_html_e( 'Sent', 'insight-hub-dashboard' ); ?></span></div>
				<div class="ihmsg-stat"><b data-ihmsg-stat="approved"><?php echo (int) ( $ihrm_req_stats['approved'] ?? 0 ); ?></b><span><?php esc_html_e( 'Approved', 'insight-hub-dashboard' ); ?></span></div>
				<div class="ihmsg-stat"><b data-ihmsg-stat="pending"><?php echo (int) ( $ihrm_req_stats['pending'] ?? 0 ); ?></b><span><?php esc_html_e( 'Pending', 'insight-hub-dashboard' ); ?></span></div>
			</div>

			<div class="ihmsg-note">
				<b><?php esc_html_e( 'Admin controls every request', 'insight-hub-dashboard' ); ?></b>
				<span><?php esc_html_e( 'Contact details are only released after the MouldHub team approves.', 'insight-hub-dashboard' ); ?></span>
			</div>
		<?php endif; ?>
	</aside>

	<?php if ( $ihrm_is_admin && $ihrm_active_ctx ) : ?>
		<div class="ihmsg-modal" data-ihmsg-invite-modal hidden>
			<div class="ihmsg-modal__backdrop" data-ihmsg-invite-close></div>
			<div class="ihmsg-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="ihmsgInviteTitle">
				<div class="ihmsg-modal__head">
					<h3 id="ihmsgInviteTitle"><?php esc_html_e( 'Invite a user to this chat', 'insight-hub-dashboard' ); ?></h3>
					<button type="button" class="ihmsg-modal__x" data-ihmsg-invite-close aria-label="<?php esc_attr_e( 'Close', 'insight-hub-dashboard' ); ?>">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
					</button>
				</div>
				<div class="ihmsg-modal__body">
					<div class="ihmsg-search ihmsg-search--modal">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
						<input type="search" data-ihmsg-invite-search placeholder="<?php esc_attr_e( 'Search by name or USR-id…', 'insight-hub-dashboard' ); ?>" autocomplete="off" aria-label="<?php esc_attr_e( 'Search users to invite', 'insight-hub-dashboard' ); ?>">
					</div>
					<div class="ihmsg-invite-results" data-ihmsg-invite-results aria-live="polite"></div>
					<div class="ihmsg-invite-msg" data-ihmsg-invite-msg role="status" hidden></div>
				</div>
			</div>
		</div>
	<?php endif; ?>

</div>
<?php
$ihrm_inner = ob_get_clean();

/* ════ Wrappers ══════════════════════════════════════════════════════════════
 * Admin → plugin admin layout. User → user shell (full HTML document). */
if ( $ihrm_is_admin ) {
	$content = '<div class="ih-request-messages-page ih-rmsg-redesign">' . $ihrm_inner . '</div>';
	$title   = __( 'Messages', 'insight-hub-dashboard' );
	include IH_DIR . 'pages/layout.php';
	return;
}
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
$ih_shell_class = 'ih-shell ih-figma-dashboard is-user ih-shell--float-nav ih-request-messages-page ih-rmsg-redesign';
$ih_shell_extra = 'data-ih-figma-screen="user-request-messages-v2"';
include IH_DIR . 'pages/partials/ih-user-shell-start.php';
include IH_DIR . 'pages/partials/ih-user-shell-header.php';
?>
<div class="ih-body">
	<main class="ih-main">
		<div class="ih-content">
			<?php
			echo $ihrm_inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- assembled above with escaping
			?>
		</div>
	</main>
</div>
</div><!-- /.ih-shell -->
<?php wp_footer(); ?>
</body>
</html>
