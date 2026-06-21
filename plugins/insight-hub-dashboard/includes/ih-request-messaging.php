<?php
/**
 * ih-request-messaging.php — Requester ↔ listing-owner direct messaging.
 *
 * This is a SEPARATE subsystem from the existing user↔admin support chat
 * (ih_threads / ih_chats). A thread here exists ONLY for an APPROVED request in
 * ih_requests and is shared by exactly two participants — the requester
 * (ih_requests.user_id) and the listing owner (ih_machines/ih_tools.owner_id) —
 * plus site admins, who may always read/post.
 *
 * Data lives in the {prefix}ih_messages table (created by the plugin migration):
 *   id, request_id, listing_type, listing_id, sender_id, recipient_id,
 *   body (text), created_at, read_at (nullable).
 *
 * AJAX contract (all nonce 'ih_nonce' in `nonce`, logged-in, participant-or-admin):
 *   ih_rmsg_send        POST  request_id, body            → new message payload
 *   ih_rmsg_thread      GET   request_id, after_id?, before_id?, limit? → messages + meta
 *   ih_rmsg_mark_read   POST  request_id                  → ok
 *   ih_rmsg_unread      GET   (none)                      → { total, per_request }
 */

defined( 'ABSPATH' ) || exit;

/* ─────────────────────────────────────────────────────────────────────────
   Constants
───────────────────────────────────────────────────────────────────────── */
if ( ! defined( 'IH_RMSG_MAX_LEN' ) ) {
	define( 'IH_RMSG_MAX_LEN', 5000 );      // hard cap on message length
}
if ( ! defined( 'IH_RMSG_PAGE_SIZE' ) ) {
	define( 'IH_RMSG_PAGE_SIZE', 30 );      // default page size for thread fetch
}
if ( ! defined( 'IH_RMSG_THROTTLE_SECS' ) ) {
	define( 'IH_RMSG_THROTTLE_SECS', 2 );   // basic anti-spam: min seconds between sends
}

/* ─────────────────────────────────────────────────────────────────────────
   Schema — called from the plugin migration (ih_install / ih_run_migrations).
───────────────────────────────────────────────────────────────────────── */
if ( ! function_exists( 'ih_rmsg_table' ) ) {
	function ih_rmsg_table() {
		global $wpdb;
		return $wpdb->prefix . 'ih_messages';
	}
}

if ( ! function_exists( 'ih_rmsg_install_schema' ) ) {
	/**
	 * Create the ih_messages table. Idempotent (dbDelta). Safe to call repeatedly.
	 */
	function ih_rmsg_install_schema() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = ih_rmsg_table();
		$charset = $wpdb->get_charset_collate();
		dbDelta(
			"CREATE TABLE {$table} (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				request_id bigint(20) NOT NULL,
				listing_type varchar(20) DEFAULT 'machine',
				listing_id bigint(20) DEFAULT 0,
				sender_id bigint(20) NOT NULL,
				recipient_id bigint(20) NOT NULL,
				body text NOT NULL,
				attachment_url varchar(500) DEFAULT NULL,
				attachment_type varchar(20) DEFAULT NULL,
				attachment_name varchar(190) DEFAULT NULL,
				attachment_size bigint(20) DEFAULT NULL,
				created_at datetime DEFAULT CURRENT_TIMESTAMP,
				delivered_at datetime DEFAULT NULL,
				read_at datetime DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY request_id (request_id),
				KEY recipient_id (recipient_id),
				KEY recipient_unread (recipient_id, read_at)
			) {$charset};"
		);
	}
}

if ( ! function_exists( 'ih_rmsg_has_delivered_col' ) ) {
	/**
	 * Whether the ih_messages.delivered_at column exists yet (added via the
	 * guarded migration). Cached per-request. Lets the read/delivery code degrade
	 * gracefully if a page load races ahead of the migration.
	 */
	function ih_rmsg_has_delivered_col() {
		static $has = null;
		if ( $has !== null ) {
			return $has;
		}
		global $wpdb;
		$has = (bool) $wpdb->get_var(
			$wpdb->prepare( 'SHOW COLUMNS FROM ' . ih_rmsg_table() . ' LIKE %s', 'delivered_at' )
		);
		return $has;
	}
}

if ( ! function_exists( 'ih_rmsg_has_attachment_cols' ) ) {
	/**
	 * Whether the ih_messages attachment columns exist yet (added via the guarded
	 * migration). Cached per-request so attachment send/render degrade gracefully
	 * if a page load races ahead of the migration.
	 */
	function ih_rmsg_has_attachment_cols() {
		static $has = null;
		if ( $has !== null ) {
			return $has;
		}
		global $wpdb;
		$has = (bool) $wpdb->get_var(
			$wpdb->prepare( 'SHOW COLUMNS FROM ' . ih_rmsg_table() . ' LIKE %s', 'attachment_url' )
		);
		return $has;
	}
}

if ( ! function_exists( 'ih_rmsg_settings_table' ) ) {
	function ih_rmsg_settings_table() {
		global $wpdb;
		return $wpdb->prefix . 'ih_thread_settings';
	}
}

if ( ! function_exists( 'ih_rmsg_participants_table' ) ) {
	function ih_rmsg_participants_table() {
		global $wpdb;
		return $wpdb->prefix . 'ih_request_participants';
	}
}

/* ─────────────────────────────────────────────────────────────────────────
   Authorization model
───────────────────────────────────────────────────────────────────────── */
if ( ! function_exists( 'ih_rmsg_normalize_listing_type' ) ) {
	/** Collapse request listing_type variants to the base table type. */
	function ih_rmsg_normalize_listing_type( $type ) {
		$type = strtolower( trim( (string) $type ) );
		if ( strpos( $type, 'tool' ) !== false ) {
			return 'tool';
		}
		return 'machine';
	}
}

if ( ! function_exists( 'ih_rmsg_listing_owner_id' ) ) {
	/** Resolve the owner user id for a listing. */
	function ih_rmsg_listing_owner_id( $listing_type, $listing_id ) {
		global $wpdb;
		$listing_id = (int) $listing_id;
		if ( ! $listing_id ) {
			return 0;
		}
		$table = ih_rmsg_normalize_listing_type( $listing_type ) === 'tool'
			? $wpdb->prefix . 'ih_tools'
			: $wpdb->prefix . 'ih_machines';
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT owner_id FROM {$table} WHERE id=%d", $listing_id ) );
	}
}

if ( ! function_exists( 'ih_rmsg_get_thread_context' ) ) {
	/**
	 * Resolve a thread's context from an approved request id.
	 *
	 * @return array|WP_Error {
	 *   request_id, status, approved(bool), listing_type, listing_id,
	 *   requester_id, owner_id, participants(int[])
	 * }
	 */
	function ih_rmsg_get_thread_context( $request_id ) {
		global $wpdb;
		$request_id = (int) $request_id;
		if ( ! $request_id ) {
			return new WP_Error( 'invalid', __( 'Invalid request.', 'insight-hub-dashboard' ) );
		}
		$req = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ih_requests WHERE id=%d", $request_id ),
			ARRAY_A
		);
		if ( ! $req ) {
			return new WP_Error( 'not_found', __( 'Request not found.', 'insight-hub-dashboard' ) );
		}
		$status       = strtolower( trim( (string) ( $req['status'] ?? '' ) ) );
		$approved     = ( $status === 'approved' );
		$listing_type = ih_rmsg_normalize_listing_type( $req['listing_type'] ?? 'machine' );
		$listing_id   = (int) ( $req['listing_id'] ?? 0 );
		$requester_id = (int) ( $req['user_id'] ?? 0 );
		$owner_id     = ih_rmsg_listing_owner_id( $listing_type, $listing_id );

		$guests = ih_rmsg_guest_ids( $request_id );

		$participants = array_values( array_unique( array_filter( array_merge( array( $requester_id, $owner_id ), $guests ) ) ) );

		return array(
			'request_id'   => $request_id,
			'status'       => $status,
			'approved'     => $approved,
			'listing_type' => $listing_type,
			'listing_id'   => $listing_id,
			'requester_id' => $requester_id,
			'owner_id'     => $owner_id,
			'guests'       => $guests,
			'participants' => $participants,
		);
	}
}

if ( ! function_exists( 'ih_rmsg_user_can_access' ) ) {
	/**
	 * A thread is accessible ONLY when the request is approved AND the user is a
	 * participant (requester or owner) — admins may always access.
	 *
	 * @return array|WP_Error Thread context on success.
	 */
	function ih_rmsg_user_can_access( $request_id, $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : (int) get_current_user_id();
		if ( ! $user_id ) {
			return new WP_Error( 'auth', __( 'You must be logged in.', 'insight-hub-dashboard' ), array( 'status' => 403 ) );
		}
		$ctx = ih_rmsg_get_thread_context( $request_id );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$is_admin = user_can( $user_id, 'manage_options' );
		if ( ! $ctx['approved'] && ! $is_admin ) {
			return new WP_Error( 'gated', __( 'This conversation is not available until the request is approved.', 'insight-hub-dashboard' ), array( 'status' => 403 ) );
		}
		$is_participant = in_array( $user_id, $ctx['participants'], true );
		if ( ! $is_participant && ! $is_admin ) {
			return new WP_Error( 'forbidden', __( 'You are not a participant in this conversation.', 'insight-hub-dashboard' ), array( 'status' => 403 ) );
		}
		return $ctx;
	}
}

if ( ! function_exists( 'ih_rmsg_recipient_for' ) ) {
	/** Given a thread context and a sender, return the other participant id. */
	function ih_rmsg_recipient_for( $ctx, $sender_id ) {
		$sender_id = (int) $sender_id;
		if ( $sender_id === (int) $ctx['requester_id'] ) {
			return (int) $ctx['owner_id'];
		}
		if ( $sender_id === (int) $ctx['owner_id'] ) {
			return (int) $ctx['requester_id'];
		}
		// Admin (or other) posting into the thread: default recipient is the requester.
		return (int) $ctx['requester_id'];
	}
}

/* ─────────────────────────────────────────────────────────────────────────
   Per-thread access controls (the right-rail green switches) — real,
   admin-managed state persisted in ih_thread_settings. Defaults to granted so
   an approved conversation behaves exactly as before until an admin toggles it.
───────────────────────────────────────────────────────────────────────── */
if ( ! function_exists( 'ih_rmsg_default_settings' ) ) {
	function ih_rmsg_default_settings() {
		return array(
			'profile_access'    => 1,
			'listing_access'    => 1,
			'allow_attachments' => 1,
		);
	}
}

if ( ! function_exists( 'ih_rmsg_get_settings' ) ) {
	/** @return array{profile_access:int,listing_access:int,allow_attachments:int} */
	function ih_rmsg_get_settings( $request_id ) {
		global $wpdb;
		$request_id = (int) $request_id;
		$defaults   = ih_rmsg_default_settings();
		if ( ! $request_id ) {
			return $defaults;
		}
		$tbl = ih_rmsg_settings_table();
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tbl ) ) !== $tbl ) {
			return $defaults;
		}
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl} WHERE request_id=%d", $request_id ), ARRAY_A );
		if ( ! $row ) {
			return $defaults;
		}
		return array(
			'profile_access'    => (int) $row['profile_access'],
			'listing_access'    => (int) $row['listing_access'],
			'allow_attachments' => (int) $row['allow_attachments'],
		);
	}
}

if ( ! function_exists( 'ih_rmsg_set_setting' ) ) {
	/** Persist one access toggle. Returns the full updated settings array. */
	function ih_rmsg_set_setting( $request_id, $key, $value, $actor_id = 0 ) {
		global $wpdb;
		$request_id = (int) $request_id;
		$value      = $value ? 1 : 0;
		$actor_id   = (int) ( $actor_id ?: get_current_user_id() );
		$allowed    = array_keys( ih_rmsg_default_settings() );
		if ( ! $request_id || ! in_array( $key, $allowed, true ) ) {
			return ih_rmsg_get_settings( $request_id );
		}
		$current = ih_rmsg_get_settings( $request_id );
		$current[ $key ] = $value;
		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO ' . ih_rmsg_settings_table() . ' (request_id, profile_access, listing_access, allow_attachments, updated_by, updated_at)
				 VALUES (%d, %d, %d, %d, %d, %s)
				 ON DUPLICATE KEY UPDATE profile_access=VALUES(profile_access), listing_access=VALUES(listing_access), allow_attachments=VALUES(allow_attachments), updated_by=VALUES(updated_by), updated_at=VALUES(updated_at)',
				$request_id,
				$current['profile_access'],
				$current['listing_access'],
				$current['allow_attachments'],
				$actor_id,
				current_time( 'mysql', true )
			)
		);
		return $current;
	}
}

/* ─────────────────────────────────────────────────────────────────────────
   Group participants — guests an admin invites into a request thread. The
   requester + owner are always implicit; this stores the extras.
───────────────────────────────────────────────────────────────────────── */
if ( ! function_exists( 'ih_rmsg_guest_ids' ) ) {
	/** @return int[] invited guest user ids for a request thread. */
	function ih_rmsg_guest_ids( $request_id ) {
		global $wpdb;
		$request_id = (int) $request_id;
		$tbl        = ih_rmsg_participants_table();
		if ( ! $request_id || $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tbl ) ) !== $tbl ) {
			return array();
		}
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$tbl} WHERE request_id=%d ORDER BY id ASC", $request_id ) );
		return array_values( array_unique( array_map( 'intval', (array) $ids ) ) );
	}
}

if ( ! function_exists( 'ih_rmsg_is_online' ) ) {
	/** Presence (reuses the support-chat heartbeat transients). */
	function ih_rmsg_is_online( $user_id ) {
		if ( function_exists( 'ih_chat_is_user_online' ) ) {
			return (bool) ih_chat_is_user_online( $user_id );
		}
		$beat = get_transient( 'ih_presence_' . (int) $user_id );
		return $beat && ( time() - (int) $beat ) < 70;
	}
}

/* ─────────────────────────────────────────────────────────────────────────
   Display helpers
───────────────────────────────────────────────────────────────────────── */
if ( ! function_exists( 'ih_rmsg_display_name' ) ) {
	/**
	 * Within an APPROVED thread, identities are visible per existing product
	 * rules (approval is the anonymity gate). Falls back to the USR ref when a
	 * display name is unavailable.
	 */
	function ih_rmsg_display_name( $user_id ) {
		$user_id = (int) $user_id;
		if ( ! $user_id ) {
			return __( 'Unknown', 'insight-hub-dashboard' );
		}
		$u = get_userdata( $user_id );
		if ( $u && ( $u->display_name || $u->user_login ) ) {
			return $u->display_name ?: $u->user_login;
		}
		if ( function_exists( 'ih_user_ref' ) ) {
			return ih_user_ref( $user_id );
		}
		return 'USR-' . str_pad( (string) $user_id, 5, '0', STR_PAD_LEFT );
	}
}

if ( ! function_exists( 'ih_rmsg_initials' ) ) {
	function ih_rmsg_initials( $name ) {
		$p = preg_split( '/\s+/', trim( (string) ( $name ?: 'U' ) ) );
		return strtoupper( substr( $p[0] ?? 'U', 0, 1 ) . substr( $p[1] ?? '', 0, 1 ) );
	}
}

if ( ! function_exists( 'ih_rmsg_listing_title' ) ) {
	function ih_rmsg_listing_title( $listing_type, $listing_id ) {
		global $wpdb;
		$listing_id = (int) $listing_id;
		if ( ! $listing_id ) {
			return '';
		}
		$table = ih_rmsg_normalize_listing_type( $listing_type ) === 'tool'
			? $wpdb->prefix . 'ih_tools'
			: $wpdb->prefix . 'ih_machines';
		return (string) $wpdb->get_var( $wpdb->prepare( "SELECT title FROM {$table} WHERE id=%d", $listing_id ) );
	}
}

if ( ! function_exists( 'ih_rmsg_listing_ref' ) ) {
	function ih_rmsg_listing_ref( $listing_type, $listing_id ) {
		if ( function_exists( 'ih_request_listing_ref' ) ) {
			return ih_request_listing_ref( $listing_type, $listing_id );
		}
		$listing_id = (int) $listing_id;
		return ( ih_rmsg_normalize_listing_type( $listing_type ) === 'tool' ? 'TL-' : 'MCH-' )
			. str_pad( (string) $listing_id, 5, '0', STR_PAD_LEFT );
	}
}

if ( ! function_exists( 'ih_rmsg_day_label' ) ) {
	/** "Today" / "Yesterday" / "M j, Y" date-divider label for a GMT datetime. */
	function ih_rmsg_day_label( $created_gmt ) {
		if ( empty( $created_gmt ) ) {
			return '';
		}
		$day   = get_date_from_gmt( $created_gmt, 'Y-m-d' );
		$today = current_time( 'Y-m-d' );
		$yest  = gmdate( 'Y-m-d', strtotime( $today ) - DAY_IN_SECONDS );
		if ( $day === $today ) {
			return __( 'Today', 'insight-hub-dashboard' );
		}
		if ( $day === $yest ) {
			return __( 'Yesterday', 'insight-hub-dashboard' );
		}
		return get_date_from_gmt( $created_gmt, 'M j, Y' );
	}
}

if ( ! function_exists( 'ih_rmsg_format_size' ) ) {
	/** Compact human file size: 248 KB / 1.2 MB. */
	function ih_rmsg_format_size( $bytes ) {
		$bytes = (int) $bytes;
		if ( $bytes <= 0 ) {
			return '';
		}
		if ( $bytes < 1024 ) {
			return $bytes . ' B';
		}
		if ( $bytes < 1024 * 1024 ) {
			return round( $bytes / 1024 ) . ' KB';
		}
		return round( $bytes / ( 1024 * 1024 ), 1 ) . ' MB';
	}
}

if ( ! function_exists( 'ih_rmsg_format_message_row' ) ) {
	/**
	 * Shape a DB row into the JSON payload sent to the client.
	 *
	 * Adds an outbound delivery `status` for the sender's own bubbles, derived
	 * from REAL columns:
	 *   read_at      → 'read'      (recipient opened the thread)
	 *   delivered_at → 'delivered' (recipient's client received it, not yet read)
	 *   otherwise    → 'sent'      (persisted)
	 * 'sending' / 'failed' are client-side only.
	 *
	 * `system` is true for system rows (sender_id === 0) — e.g. a future
	 * "Admin added X to the chat" line. No backend emits these today, so the
	 * branch stays dormant until a participants/invite backend writes them.
	 *
	 * @param bool $is_group Render with group treatment (sender-name labels).
	 */
	function ih_rmsg_format_message_row( $row, $viewer_id, $is_group = false ) {
		$viewer_id = (int) $viewer_id;
		$sender_id = (int) ( $row['sender_id'] ?? 0 );
		$created   = (string) ( $row['created_at'] ?? '' );
		$read      = ! empty( $row['read_at'] );
		$delivered = ! empty( $row['delivered_at'] );
		$status    = $read ? 'read' : ( $delivered ? 'delivered' : 'sent' );
		$att_url   = isset( $row['attachment_url'] ) ? (string) $row['attachment_url'] : '';
		return array(
			'id'         => (int) ( $row['id'] ?? 0 ),
			'request_id' => (int) ( $row['request_id'] ?? 0 ),
			'sender_id'  => $sender_id,
			'mine'       => ( $sender_id === $viewer_id ),
			'system'     => ( $sender_id === 0 ),
			'is_group'   => (bool) $is_group,
			'name'       => ih_rmsg_display_name( $sender_id ),
			'body'       => (string) ( $row['body'] ?? '' ),
			'time'       => $created ? get_date_from_gmt( $created, 'g:i A' ) : '',
			'date'       => $created ? get_date_from_gmt( $created, 'M j, Y' ) : '',
			'day_key'    => $created ? get_date_from_gmt( $created, 'Y-m-d' ) : '',
			'day_label'  => $created ? ih_rmsg_day_label( $created ) : '',
			'read'       => $read,
			'status'     => $status,
			'attachment' => $att_url ? array(
				'url'  => $att_url,
				'type' => isset( $row['attachment_type'] ) ? (string) $row['attachment_type'] : 'file',
				'name' => isset( $row['attachment_name'] ) ? (string) $row['attachment_name'] : '',
				'size' => isset( $row['attachment_size'] ) ? (int) $row['attachment_size'] : 0,
			) : null,
		);
	}
}

if ( ! function_exists( 'ih_rmsg_tick_html' ) ) {
	/** Delivery-state glyph for an outbound bubble. Mirrors the JS builder. */
	function ih_rmsg_tick_html( $status ) {
		$status = in_array( $status, array( 'sending', 'sent', 'delivered', 'read', 'failed' ), true ) ? $status : 'sent';
		if ( $status === 'sending' ) {
			$glyph = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';
		} elseif ( $status === 'sent' ) {
			$glyph = '✓';
		} elseif ( $status === 'failed' ) {
			$glyph = '!';
		} else {
			$glyph = '✓✓';
		}
		return '<span class="ih-rmsg-tick is-' . esc_attr( $status ) . '" aria-hidden="true">' . $glyph . '</span>';
	}
}

/* ─────────────────────────────────────────────────────────────────────────
   Queries — threads list / unread counts
───────────────────────────────────────────────────────────────────────── */
if ( ! function_exists( 'ih_rmsg_user_threads' ) ) {
	/**
	 * List the approved-request threads a user participates in (as requester OR
	 * owner), most-recent first, each with last message + unread count.
	 *
	 * @param int  $user_id  Participant id (ignored when $all_for_admin true).
	 * @param bool $all_for_admin When true, returns ALL threads (admin console).
	 */
	function ih_rmsg_user_threads( $user_id, $all_for_admin = false ) {
		global $wpdb;
		$user_id = (int) $user_id;
		$tbl     = ih_rmsg_table();
		$reqs    = $wpdb->prefix . 'ih_requests';

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tbl ) ) !== $tbl ) {
			return array();
		}

		// Candidate approved requests this user belongs to. For owners we must
		// match listings they own; for requesters we match user_id directly.
		$request_ids = array();

		if ( $all_for_admin ) {
			$rows = $wpdb->get_col(
				"SELECT DISTINCT request_id FROM {$tbl} ORDER BY request_id DESC"
			);
			$request_ids = array_map( 'intval', (array) $rows );
		} else {
			// Requests where the user is the requester.
			$as_requester = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT id FROM {$reqs} WHERE user_id=%d AND LOWER(TRIM(status))='approved'",
					$user_id
				)
			);
			// Requests on listings the user owns.
			$as_owner_machine = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT r.id FROM {$reqs} r
					 INNER JOIN {$wpdb->prefix}ih_machines m ON m.id = r.listing_id AND m.owner_id = %d
					 WHERE LOWER(TRIM(r.status))='approved'
					 AND LOWER(TRIM(r.listing_type)) LIKE %s",
					$user_id,
					'%machine%'
				)
			);
			$as_owner_tool = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT r.id FROM {$reqs} r
					 INNER JOIN {$wpdb->prefix}ih_tools t ON t.id = r.listing_id AND t.owner_id = %d
					 WHERE LOWER(TRIM(r.status))='approved'
					 AND LOWER(TRIM(r.listing_type)) LIKE %s",
					$user_id,
					'%tool%'
				)
			);
			$request_ids = array_values( array_unique( array_map( 'intval', array_merge(
				(array) $as_requester,
				(array) $as_owner_machine,
				(array) $as_owner_tool
			) ) ) );
		}

		if ( empty( $request_ids ) ) {
			return array();
		}

		$threads = array();
		foreach ( $request_ids as $rid ) {
			$ctx = ih_rmsg_get_thread_context( $rid );
			if ( is_wp_error( $ctx ) || ! $ctx['approved'] ) {
				continue;
			}
			$last_cols = ih_rmsg_has_attachment_cols()
				? 'id, sender_id, body, attachment_type, attachment_name, created_at'
				: 'id, sender_id, body, created_at';
			$last = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT {$last_cols} FROM {$tbl} WHERE request_id=%d ORDER BY id DESC LIMIT 1",
					$rid
				),
				ARRAY_A
			);
			$last_preview = '';
			if ( $last ) {
				$last_preview = (string) $last['body'];
				if ( '' === $last_preview && ! empty( $last['attachment_type'] ) ) {
					$last_preview = '📎 ' . ( $last['attachment_name'] ?: ucfirst( (string) $last['attachment_type'] ) );
				}
			}
			// Skip empty threads for the user inbox, but admins see all.
			if ( ! $last && ! $all_for_admin ) {
				continue;
			}
			$unread = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$tbl} WHERE request_id=%d AND recipient_id=%d AND read_at IS NULL",
					$rid,
					$user_id
				)
			);
			// The "other" party from this user's perspective.
			if ( $all_for_admin ) {
				$other_id = (int) $ctx['requester_id'];
			} else {
				$other_id = ( $user_id === (int) $ctx['owner_id'] ) ? (int) $ctx['requester_id'] : (int) $ctx['owner_id'];
			}
			$threads[] = array(
				'request_id'   => $rid,
				'listing_type' => $ctx['listing_type'],
				'listing_id'   => $ctx['listing_id'],
				'listing_ref'  => ih_rmsg_listing_ref( $ctx['listing_type'], $ctx['listing_id'] ),
				'listing_title'=> ih_rmsg_listing_title( $ctx['listing_type'], $ctx['listing_id'] ),
				'requester_id' => (int) $ctx['requester_id'],
				'owner_id'     => (int) $ctx['owner_id'],
				'other_id'     => $other_id,
				'other_name'   => ih_rmsg_display_name( $other_id ),
				'last_body'    => $last_preview,
				'last_time'    => $last && ! empty( $last['created_at'] ) ? get_date_from_gmt( $last['created_at'], 'g:i A' ) : '',
				'last_ts'      => $last && ! empty( $last['created_at'] ) ? strtotime( $last['created_at'] ) : 0,
				'unread'       => $unread,
			);
		}

		usort(
			$threads,
			static function ( $a, $b ) {
				return $b['last_ts'] <=> $a['last_ts'];
			}
		);
		return $threads;
	}
}

if ( ! function_exists( 'ih_rmsg_unread_total' ) ) {
	/** Total unread messages addressed to a user across all request threads. */
	function ih_rmsg_unread_total( $user_id ) {
		global $wpdb;
		$user_id = (int) $user_id;
		$tbl     = ih_rmsg_table();
		if ( ! $user_id || $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tbl ) ) !== $tbl ) {
			return 0;
		}
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$tbl} WHERE recipient_id=%d AND read_at IS NULL",
				$user_id
			)
		);
	}
}

if ( ! function_exists( 'ih_rmsg_unread_per_request' ) ) {
	/** @return array<int,int> request_id => unread count for this recipient. */
	function ih_rmsg_unread_per_request( $user_id ) {
		global $wpdb;
		$user_id = (int) $user_id;
		$tbl     = ih_rmsg_table();
		if ( ! $user_id || $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tbl ) ) !== $tbl ) {
			return array();
		}
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT request_id, COUNT(*) AS c FROM {$tbl} WHERE recipient_id=%d AND read_at IS NULL GROUP BY request_id",
				$user_id
			),
			ARRAY_A
		) ?: array();
		$out = array();
		foreach ( $rows as $r ) {
			$out[ (int) $r['request_id'] ] = (int) $r['c'];
		}
		return $out;
	}
}

if ( ! function_exists( 'ih_rmsg_mark_thread_read' ) ) {
	/** Mark all messages addressed to $user_id in a thread as read. */
	function ih_rmsg_mark_thread_read( $request_id, $user_id ) {
		global $wpdb;
		$request_id = (int) $request_id;
		$user_id    = (int) $user_id;
		if ( ! $request_id || ! $user_id ) {
			return 0;
		}
		return (int) $wpdb->query(
			$wpdb->prepare(
				"UPDATE " . ih_rmsg_table() . " SET read_at=%s WHERE request_id=%d AND recipient_id=%d AND read_at IS NULL",
				current_time( 'mysql', true ),
				$request_id,
				$user_id
			)
		);
	}
}

/* ─────────────────────────────────────────────────────────────────────────
   Rendering — shared bubble + listing-detail thread panel
───────────────────────────────────────────────────────────────────────── */
if ( ! function_exists( 'ih_rmsg_attachment_html' ) ) {
	/** Render an attachment inside a bubble: image thumb, video, or file card. */
	function ih_rmsg_attachment_html( $att ) {
		$url  = isset( $att['url'] ) ? (string) $att['url'] : '';
		$type = isset( $att['type'] ) ? (string) $att['type'] : 'file';
		$name = isset( $att['name'] ) ? (string) $att['name'] : '';
		$size = isset( $att['size'] ) ? (int) $att['size'] : 0;
		if ( '' === $url ) {
			return '';
		}
		if ( 'image' === $type ) {
			return '<a class="ih-rmsg-att ih-rmsg-att--img" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">'
				. '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $name ?: __( 'Image attachment', 'insight-hub-dashboard' ) ) . '" loading="lazy">'
				. ( $name ? '<span class="ih-rmsg-att__cap">' . esc_html( $name ) . '</span>' : '' )
				. '</a>';
		}
		if ( 'video' === $type ) {
			return '<video class="ih-rmsg-att ih-rmsg-att--video" src="' . esc_url( $url ) . '" controls preload="metadata"></video>';
		}
		$ext = strtoupper( pathinfo( $name ?: $url, PATHINFO_EXTENSION ) );
		return '<a class="ih-rmsg-att ih-rmsg-att--file" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer" download>'
			. '<span class="ih-rmsg-att__ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg></span>'
			. '<span class="ih-rmsg-att__tx"><b>' . esc_html( $name ?: __( 'Attachment', 'insight-hub-dashboard' ) ) . '</b>'
			. '<em>' . esc_html( trim( $ext . ( $size ? ' · ' . ih_rmsg_format_size( $size ) : '' ), ' ·' ) ) . '</em></span>'
			. '<span class="ih-rmsg-att__dl" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14"/></svg></span>'
			. '</a>';
	}
}

if ( ! function_exists( 'ih_rmsg_bubble_html' ) ) {
	/** Render one chat bubble (body escaped) matching the JS-produced markup. */
	function ih_rmsg_bubble_html( $m ) {
		// System rows (e.g. "Admin added X to the chat") render as a centred pill.
		if ( ! empty( $m['system'] ) ) {
			return '<div class="ihmsg-sysline" data-id="' . (int) $m['id'] . '"><span>' . esc_html( $m['body'] ) . '</span></div>';
		}
		$mine = ! empty( $m['mine'] );
		$att  = ! empty( $m['attachment'] ) && ! empty( $m['attachment']['url'] ) ? $m['attachment'] : null;
		$html  = '<div class="ih-rmsg-msg ' . ( $mine ? 'is-mine' : 'is-them' ) . ( $mine && ! empty( $m['status'] ) ? ' is-' . esc_attr( $m['status'] ) : '' ) . ( $att ? ' has-attachment' : '' ) . '" data-id="' . (int) $m['id'] . '">';
		// Group sender label sits above the bubble for the per-sender accent colour.
		if ( ! $mine && ! empty( $m['name'] ) && ! empty( $m['is_group'] ) ) {
			$hue = ( (int) ( $m['sender_id'] ?? 0 ) * 47 + 210 ) % 360;
			$html .= '<div class="ih-rmsg-name" style="color:hsl(' . (int) $hue . ' 64% 48%)">' . esc_html( $m['name'] ) . '</div>';
		}
		$html .= '<div class="ih-rmsg-bubble">';
		if ( $att ) {
			$html .= ih_rmsg_attachment_html( $att );
		}
		if ( '' !== (string) ( $m['body'] ?? '' ) ) {
			$html .= '<div class="ih-rmsg-text">' . esc_html( $m['body'] ) . '</div>';
		}
		$html .= '<div class="ih-rmsg-meta">' . esc_html( $m['time'] );
		if ( $mine ) {
			$html .= ih_rmsg_tick_html( isset( $m['status'] ) ? $m['status'] : ( ! empty( $m['read'] ) ? 'read' : 'sent' ) );
		}
		$html .= '</div></div></div>';
		return $html;
	}
}

if ( ! function_exists( 'ih_rmsg_day_divider_html' ) ) {
	/** Centred date divider ("Today" / "Yesterday" / "M j, Y"). */
	function ih_rmsg_day_divider_html( $label, $day_key = '' ) {
		if ( '' === (string) $label ) {
			return '';
		}
		return '<div class="ihmsg-datedivider" data-rmsg-daykey="' . esc_attr( $day_key ) . '"><span>' . esc_html( $label ) . '</span></div>';
	}
}

if ( ! function_exists( 'ih_rmsg_render_seed' ) ) {
	/** Render a run of formatted messages with date dividers between days. */
	function ih_rmsg_render_seed( $messages ) {
		$html    = '';
		$lastDay = '';
		foreach ( (array) $messages as $m ) {
			if ( empty( $m['system'] ) && ! empty( $m['day_key'] ) && $m['day_key'] !== $lastDay ) {
				$html   .= ih_rmsg_day_divider_html( $m['day_label'] ?? '', $m['day_key'] );
				$lastDay = $m['day_key'];
			}
			$html .= ih_rmsg_bubble_html( $m );
		}
		return $html;
	}
}

if ( ! function_exists( 'ih_rmsg_shared_files' ) ) {
	/**
	 * Attachments shared in a request thread (newest first) for the right-rail
	 * "Shared files" grid. Returns [] when the attachment columns are absent.
	 */
	function ih_rmsg_shared_files( $request_id, $limit = 12 ) {
		global $wpdb;
		$request_id = (int) $request_id;
		if ( ! $request_id || ! ih_rmsg_has_attachment_cols() ) {
			return array();
		}
		$limit = max( 1, min( 60, (int) $limit ) );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, attachment_url, attachment_type, attachment_name, attachment_size, created_at
				 FROM ' . ih_rmsg_table() . ' WHERE request_id=%d AND attachment_url IS NOT NULL AND attachment_url<>%s
				 ORDER BY id DESC LIMIT %d',
				$request_id,
				'',
				$limit
			),
			ARRAY_A
		) ?: array();
		$out = array();
		foreach ( $rows as $r ) {
			$out[] = array(
				'id'   => (int) $r['id'],
				'url'  => (string) $r['attachment_url'],
				'type' => (string) ( $r['attachment_type'] ?: 'file' ),
				'name' => (string) ( $r['attachment_name'] ?: '' ),
				'size' => (int) $r['attachment_size'],
			);
		}
		return $out;
	}
}

if ( ! function_exists( 'ih_rmsg_shared_files_count' ) ) {
	function ih_rmsg_shared_files_count( $request_id ) {
		global $wpdb;
		$request_id = (int) $request_id;
		if ( ! $request_id || ! ih_rmsg_has_attachment_cols() ) {
			return 0;
		}
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . ih_rmsg_table() . ' WHERE request_id=%d AND attachment_url IS NOT NULL AND attachment_url<>%s',
				$request_id,
				''
			)
		);
	}
}

if ( ! function_exists( 'ih_rmsg_detail_thread_for_viewer' ) ) {
	/**
	 * Resolve the thread to surface on a listing-detail page for a viewer.
	 *
	 * @return array|null { request_id, role, count } where count is the number of
	 *                    approved threads the owner has on this listing (1 for a
	 *                    requester). Null when the viewer has no approved thread.
	 */
	function ih_rmsg_detail_thread_for_viewer( $listing_type, $listing_id, $viewer_id ) {
		global $wpdb;
		$viewer_id  = (int) $viewer_id;
		$listing_id = (int) $listing_id;
		if ( ! $viewer_id || ! $listing_id ) {
			return null;
		}
		$base   = ih_rmsg_normalize_listing_type( $listing_type );
		$like   = '%' . $wpdb->esc_like( $base ) . '%';
		$reqs   = $wpdb->prefix . 'ih_requests';
		$is_admin = user_can( $viewer_id, 'manage_options' );
		$owner_id = ih_rmsg_listing_owner_id( $base, $listing_id );

		// Requester: the public listing-detail page must NOT expose a direct
		// requester→owner message box. Contact/enquiry for a requester goes
		// exclusively through the admin-gated "Request details" flow, so no
		// owner thread (and no owner identity) is surfaced here. The thread
		// itself still exists and remains reachable to the owner/admin and via
		// the dedicated inbox; it is only suppressed on the public detail page.
		if ( ! $is_admin && $viewer_id !== (int) $owner_id ) {
			return null;
		}

		// Owner / admin: most-recent approved request on this listing (+ count).
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$reqs} WHERE listing_id=%d AND LOWER(TRIM(listing_type)) LIKE %s AND LOWER(TRIM(status))='approved' ORDER BY id DESC",
				$listing_id,
				$like
			)
		);
		$ids = array_map( 'intval', (array) $ids );
		if ( empty( $ids ) ) {
			return null;
		}
		return array(
			'request_id' => $ids[0],
			'role'       => ( $viewer_id === (int) $owner_id ) ? 'owner' : 'admin',
			'count'      => count( $ids ),
		);
	}
}

if ( ! function_exists( 'ih_rmsg_render_detail_panel' ) ) {
	/**
	 * Render the compact listing-detail messaging panel for an approved thread.
	 * Returns '' when the viewer cannot access the thread.
	 *
	 * @param int   $request_id Approved request id.
	 * @param int   $viewer_id  Current viewer.
	 * @param array $opts       { inbox_url?, extra_count? }
	 */
	function ih_rmsg_render_detail_panel( $request_id, $viewer_id, $opts = array() ) {
		global $wpdb;
		$viewer_id = (int) $viewer_id;
		$ctx       = ih_rmsg_user_can_access( $request_id, $viewer_id );
		if ( is_wp_error( $ctx ) ) {
			return '';
		}
		$other_id   = ( $viewer_id === (int) $ctx['owner_id'] ) ? (int) $ctx['requester_id'] : (int) $ctx['owner_id'];
		$other_name = ih_rmsg_display_name( $other_id );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM ( SELECT * FROM " . ih_rmsg_table() . " WHERE request_id=%d ORDER BY id DESC LIMIT %d ) t ORDER BY id ASC",
				(int) $request_id,
				(int) IH_RMSG_PAGE_SIZE
			),
			ARRAY_A
		) ?: array();
		// Mark addressed-to-viewer messages read on render.
		ih_rmsg_mark_thread_read( $request_id, $viewer_id );

		$seed_msgs = array();
		foreach ( $rows as $r ) {
			$seed_msgs[] = ih_rmsg_format_message_row( $r, $viewer_id );
		}
		$seed        = ih_rmsg_render_seed( $seed_msgs );
		$has_seed    = (bool) $rows;
		$inbox_url   = isset( $opts['inbox_url'] ) ? (string) $opts['inbox_url'] : '';
		$extra_count = isset( $opts['extra_count'] ) ? (int) $opts['extra_count'] : 0;

		ob_start();
		?>
		<div class="ih-rmsg-panel" id="ihRmsgPanel">
			<div class="ih-rmsg-panel__head">
				<span class="ih-rmsg-panel__title">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
					<?php echo esc_html( sprintf( __( 'Messages with %s', 'insight-hub-dashboard' ), $other_name ) ); ?>
				</span>
				<?php if ( $extra_count > 1 && $inbox_url ) : ?>
					<a class="ih-rmsg-panel__sub" href="<?php echo esc_url( $inbox_url ); ?>"><?php echo esc_html( sprintf( _n( '%d conversation', '%d conversations', $extra_count, 'insight-hub-dashboard' ), $extra_count ) ); ?></a>
				<?php endif; ?>
			</div>
			<div class="ih-rmsg-thread-view" data-rmsg-thread-view data-request-id="<?php echo (int) $request_id; ?>">
				<div class="ih-rmsg-list" data-rmsg-list aria-live="polite">
					<button type="button" class="ih-rmsg-loadmore" data-rmsg-loadmore hidden><?php esc_html_e( 'Load earlier messages', 'insight-hub-dashboard' ); ?></button>
					<div class="ih-rmsg-empty" data-rmsg-empty<?php echo $has_seed ? ' style="display:none"' : ''; ?>><?php esc_html_e( 'No messages yet. Say hello!', 'insight-hub-dashboard' ); ?></div>
					<?php echo $seed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in ih_rmsg_bubble_html ?>
				</div>
				<div class="ih-rmsg-error" data-rmsg-error role="alert"></div>
				<form class="ih-rmsg-form" data-rmsg-form>
					<textarea class="ih-rmsg-input" data-rmsg-input rows="1" maxlength="<?php echo (int) IH_RMSG_MAX_LEN; ?>" placeholder="<?php echo esc_attr( sprintf( __( 'Message %s…', 'insight-hub-dashboard' ), $other_name ) ); ?>"></textarea>
					<button type="submit" class="ih-rmsg-send" data-rmsg-send aria-label="<?php esc_attr_e( 'Send', 'insight-hub-dashboard' ); ?>">
						<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4z"/></svg>
					</button>
				</form>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}

/* ─────────────────────────────────────────────────────────────────────────
   Request-system bridge — the redesigned inbox surfaces the existing
   ih_requests workflow (the "Request contact information" form on the user
   side, the "Access request" Approve/Decline cards on the admin side). These
   helpers read/parse/validate against the REAL request system; no new table.
───────────────────────────────────────────────────────────────────────── */
if ( ! function_exists( 'ih_rmsg_parse_listing_ref' ) ) {
	/**
	 * Parse a user-entered listing reference into a { type, id } pair.
	 * Accepts "TL-00231", "MCH-00114", "TOOL 231", or a bare "231" (in which
	 * case the toggle's $type_hint decides machine vs tool). Returns null when
	 * nothing usable can be extracted.
	 */
	function ih_rmsg_parse_listing_ref( $ref, $type_hint = 'machine' ) {
		$ref       = strtoupper( trim( (string) $ref ) );
		$type_hint = ih_rmsg_normalize_listing_type( $type_hint );
		if ( $ref === '' ) {
			return null;
		}
		if ( preg_match( '/^(?:TL|TOOL)[\s\-_]*0*(\d+)$/', $ref, $m ) ) {
			return array( 'type' => 'tool', 'id' => (int) $m[1] );
		}
		if ( preg_match( '/^(?:MCH|MC|MACHINE)[\s\-_]*0*(\d+)$/', $ref, $m ) ) {
			return array( 'type' => 'machine', 'id' => (int) $m[1] );
		}
		if ( preg_match( '/^0*(\d+)$/', $ref, $m ) ) {
			return array( 'type' => $type_hint, 'id' => (int) $m[1] );
		}
		return null;
	}
}

if ( ! function_exists( 'ih_rmsg_listing_is_active' ) ) {
	/** True when a listing row exists and is not expired. */
	function ih_rmsg_listing_is_active( $type, $id ) {
		global $wpdb;
		$id = (int) $id;
		if ( ! $id ) {
			return false;
		}
		$base  = ih_rmsg_normalize_listing_type( $type );
		$table = $base === 'tool' ? $wpdb->prefix . 'ih_tools' : $wpdb->prefix . 'ih_machines';
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT id, expiry_date FROM {$table} WHERE id=%d", $id ), ARRAY_A );
		if ( ! $row ) {
			return false;
		}
		if ( function_exists( 'ih_listing_is_expired' ) && ih_listing_is_expired( $row ) ) {
			return false;
		}
		return true;
	}
}

if ( ! function_exists( 'ih_rmsg_request_ref' ) ) {
	/** REQ-YYYY-#### display ref for a request row (defers to ih_request_ref). */
	function ih_rmsg_request_ref( $row ) {
		if ( function_exists( 'ih_request_ref' ) ) {
			return ih_request_ref( $row );
		}
		$id      = (int) ( $row['id'] ?? 0 );
		$created = (string) ( $row['request_date'] ?? $row['created_at'] ?? '' );
		$year    = $created ? date( 'Y', strtotime( $created ) ) : date( 'Y' );
		return sprintf( 'REQ-%s-%04d', $year, $id );
	}
}

if ( ! function_exists( 'ih_rmsg_request_label' ) ) {
	/** Human label for what a request is asking for. */
	function ih_rmsg_request_label( $row ) {
		$lid  = (int) ( $row['listing_id'] ?? 0 );
		$type = (string) ( $row['listing_type'] ?? '' );
		if ( $lid > 0 ) {
			return sprintf(
				/* translators: %s: listing reference such as TL-00231 */
				__( 'Contact info · %s', 'insight-hub-dashboard' ),
				ih_rmsg_listing_ref( $type, $lid )
			);
		}
		return __( 'Profile / contact access', 'insight-hub-dashboard' );
	}
}

if ( ! function_exists( 'ih_rmsg_request_stats_for_user' ) ) {
	/** Real request activity counts + recent list for a user (no fabrication). */
	function ih_rmsg_request_stats_for_user( $user_id ) {
		global $wpdb;
		$user_id = (int) $user_id;
		$reqs    = $wpdb->prefix . 'ih_requests';
		$out     = array( 'sent' => 0, 'approved' => 0, 'pending' => 0, 'rejected' => 0, 'recent' => array() );
		if ( ! $user_id ) {
			return $out;
		}
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$reqs} WHERE user_id=%d ORDER BY id DESC", $user_id ),
			ARRAY_A
		) ?: array();
		foreach ( $rows as $r ) {
			$out['sent']++;
			$s = strtolower( trim( (string) ( $r['status'] ?? '' ) ) );
			if ( $s === 'approved' ) {
				$out['approved']++;
			} elseif ( $s === 'pending' ) {
				$out['pending']++;
			} elseif ( $s === 'rejected' || $s === 'blocked' ) {
				$out['rejected']++;
			}
		}
		$out['recent'] = array_slice( $rows, 0, 6 );
		return $out;
	}
}

if ( ! function_exists( 'ih_rmsg_pending_requests' ) ) {
	/**
	 * Pending ih_requests rows — all of them for an admin, or just this user's
	 * for a requester. Used by the admin "Requests" tab and the user "Awaiting
	 * admin approval" list.
	 */
	function ih_rmsg_pending_requests( $user_id = 0, $for_admin = false, $limit = 30 ) {
		global $wpdb;
		$reqs  = $wpdb->prefix . 'ih_requests';
		$limit = max( 1, min( 100, (int) $limit ) );
		if ( $for_admin ) {
			return $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM {$reqs} WHERE LOWER(TRIM(status))='pending' ORDER BY id DESC LIMIT %d", $limit ),
				ARRAY_A
			) ?: array();
		}
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$reqs} WHERE user_id=%d AND LOWER(TRIM(status))='pending' ORDER BY id DESC LIMIT %d",
				(int) $user_id,
				$limit
			),
			ARRAY_A
		) ?: array();
	}
}

if ( ! function_exists( 'ih_rmsg_user_ids_with_pending' ) ) {
	/** @return int[] distinct requester ids that currently have a pending request. */
	function ih_rmsg_user_ids_with_pending() {
		global $wpdb;
		$reqs = $wpdb->prefix . 'ih_requests';
		$ids  = $wpdb->get_col( "SELECT DISTINCT user_id FROM {$reqs} WHERE LOWER(TRIM(status))='pending'" );
		return array_map( 'intval', (array) $ids );
	}
}

if ( ! function_exists( 'ih_rmsg_human_duration' ) ) {
	/** Compact human duration: 45s / 6m / 2h / 1d. '' for non-positive. */
	function ih_rmsg_human_duration( $secs ) {
		$secs = (int) $secs;
		if ( $secs <= 0 ) {
			return '';
		}
		if ( $secs < 90 ) {
			return $secs . 's';
		}
		if ( $secs < 3600 ) {
			return round( $secs / 60 ) . 'm';
		}
		if ( $secs < 86400 ) {
			return round( $secs / 3600 ) . 'h';
		}
		return round( $secs / 86400 ) . 'd';
	}
}

if ( ! function_exists( 'ih_rmsg_thread_message_stats' ) ) {
	/**
	 * Real per-thread stats for the right-rail "Conversation stats" + bar chart.
	 * @return array { messages, requests, avg_reply, bars[7], bars_max, labels[7] }
	 */
	function ih_rmsg_thread_message_stats( $request_id, $requester_id = 0 ) {
		global $wpdb;
		$request_id = (int) $request_id;
		$out        = array(
			'messages'  => 0,
			'requests'  => 0,
			'avg_reply' => '',
			'bars'      => array_fill( 0, 7, 0 ),
			'bars_max'  => 1,
			'labels'    => array( 'M', 'T', 'W', 'T', 'F', 'S', 'S' ),
			'delta'     => 0,
			'delta_dir' => 'flat',
		);
		if ( ! $request_id ) {
			return $out;
		}
		$tbl  = ih_rmsg_table();
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT sender_id, created_at FROM {$tbl} WHERE request_id=%d ORDER BY id ASC", $request_id ),
			ARRAY_A
		) ?: array();
		$out['messages'] = count( $rows );

		$gaps = array();
		$prev = null;
		foreach ( $rows as $r ) {
			if ( empty( $r['created_at'] ) ) {
				continue;
			}
			$local = get_date_from_gmt( $r['created_at'] );
			$wday  = (int) date( 'N', strtotime( $local ) ) - 1;
			if ( $wday >= 0 && $wday < 7 ) {
				$out['bars'][ $wday ]++;
			}
			if ( $prev && (int) $prev['sender_id'] !== (int) $r['sender_id'] ) {
				$g = strtotime( $r['created_at'] ) - strtotime( $prev['created_at'] );
				if ( $g > 0 && $g < 7 * DAY_IN_SECONDS ) {
					$gaps[] = $g;
				}
			}
			$prev = $r;
		}
		$out['bars_max'] = max( 1, max( $out['bars'] ) );
		if ( $gaps ) {
			$out['avg_reply'] = ih_rmsg_human_duration( array_sum( $gaps ) / count( $gaps ) );
		}

		// Week-over-week delta (this 7 days vs the previous 7) for the chart caption.
		$now        = current_time( 'timestamp' );
		$this_week  = 0;
		$prev_week  = 0;
		foreach ( $rows as $r ) {
			if ( empty( $r['created_at'] ) ) {
				continue;
			}
			$age = $now - strtotime( get_date_from_gmt( $r['created_at'] ) );
			if ( $age < 0 ) {
				continue;
			}
			if ( $age <= 7 * DAY_IN_SECONDS ) {
				$this_week++;
			} elseif ( $age <= 14 * DAY_IN_SECONDS ) {
				$prev_week++;
			}
		}
		if ( $prev_week > 0 ) {
			$pct               = (int) round( ( ( $this_week - $prev_week ) / $prev_week ) * 100 );
			$out['delta']      = abs( $pct );
			$out['delta_dir']  = $pct > 0 ? 'up' : ( $pct < 0 ? 'down' : 'flat' );
		} elseif ( $this_week > 0 ) {
			$out['delta']     = 100;
			$out['delta_dir'] = 'up';
		}

		$requester_id = (int) $requester_id;
		if ( $requester_id ) {
			$out['requests'] = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}ih_requests WHERE user_id=%d", $requester_id )
			);
		}
		return $out;
	}
}

/* ─────────────────────────────────────────────────────────────────────────
   AJAX: user "Request contact information" — create a pending request through
   the EXISTING request system (validated). Admin reviews & approves before any
   contact details are shared, preserving the admin-brokered gating model.
───────────────────────────────────────────────────────────────────────── */
add_action( 'wp_ajax_ih_rmsg_request_contact', 'ih_rmsg_ajax_request_contact' );
if ( ! function_exists( 'ih_rmsg_ajax_request_contact' ) ) {
	function ih_rmsg_ajax_request_contact() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'insight-hub-dashboard' ) ), 403 );
		}
		check_ajax_referer( 'ih_nonce', 'nonce' );

		$user_id = (int) get_current_user_id();
		$type    = ih_rmsg_normalize_listing_type( isset( $_POST['listing_type'] ) ? sanitize_key( wp_unslash( $_POST['listing_type'] ) ) : 'machine' );
		$ref_raw = isset( $_POST['ref'] ) ? sanitize_text_field( wp_unslash( $_POST['ref'] ) ) : '';

		$parsed = ih_rmsg_parse_listing_ref( $ref_raw, $type );
		if ( ! $parsed || ! $parsed['id'] ) {
			wp_send_json_error( array( 'message' => __( 'Enter a valid Tool / Machine ID, e.g. TL-00231.', 'insight-hub-dashboard' ) ) );
		}
		if ( ! ih_rmsg_listing_is_active( $parsed['type'], $parsed['id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'That listing could not be found or is no longer available.', 'insight-hub-dashboard' ) ) );
		}
		if ( (int) ih_rmsg_listing_owner_id( $parsed['type'], $parsed['id'] ) === $user_id ) {
			wp_send_json_error( array( 'message' => __( 'This listing already belongs to you.', 'insight-hub-dashboard' ) ) );
		}

		$user   = get_userdata( $user_id );
		$result = ih_create_request_and_notify(
			array(
				'name'         => $user ? $user->display_name : '',
				'email'        => $user ? $user->user_email : '',
				'phone'        => get_user_meta( $user_id, 'phone', true ) ?: get_user_meta( $user_id, 'ih_phone', true ),
				'listing_id'   => $parsed['id'],
				'listing_type' => $parsed['type'],
				'message'      => '',
			)
		);
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$request_id = (int) ( $result['request_id'] ?? 0 );
		$status     = ucfirst( strtolower( (string) ( $result['status'] ?? 'Pending' ) ) );
		wp_send_json_success(
			array(
				'request_id'  => $request_id,
				'status'      => $status,
				'existing'    => ! empty( $result['existing'] ),
				'listing_ref' => ih_rmsg_listing_ref( $parsed['type'], $parsed['id'] ),
				'req_ref'     => ih_rmsg_request_ref( array( 'id' => $request_id, 'request_date' => current_time( 'Y-m-d' ) ) ),
				'label'       => ih_rmsg_request_label( array( 'listing_id' => $parsed['id'], 'listing_type' => $parsed['type'] ) ),
				'stats'       => ih_rmsg_request_stats_for_user( $user_id ),
			)
		);
	}
}

/* ─────────────────────────────────────────────────────────────────────────
   AJAX: send a message
───────────────────────────────────────────────────────────────────────── */
add_action( 'wp_ajax_ih_rmsg_send', 'ih_rmsg_ajax_send' );
if ( ! function_exists( 'ih_rmsg_ajax_send' ) ) {
	function ih_rmsg_ajax_send() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'insight-hub-dashboard' ) ), 403 );
		}
		check_ajax_referer( 'ih_nonce', 'nonce' );

		$user_id    = (int) get_current_user_id();
		$request_id = isset( $_POST['request_id'] ) ? (int) $_POST['request_id'] : 0;
		$raw        = isset( $_POST['body'] ) ? wp_unslash( $_POST['body'] ) : '';

		$ctx = ih_rmsg_user_can_access( $request_id, $user_id );
		if ( is_wp_error( $ctx ) ) {
			$status = (int) ( $ctx->get_error_data()['status'] ?? 400 );
			wp_send_json_error( array( 'message' => $ctx->get_error_message() ), $status );
		}

		// Sanitize (strip tags — stricter than wp_kses_post) + trim.
		$body = trim( sanitize_textarea_field( $raw ) );
		if ( $body === '' ) {
			wp_send_json_error( array( 'message' => __( 'Message is empty.', 'insight-hub-dashboard' ) ) );
		}
		if ( mb_strlen( $body ) > IH_RMSG_MAX_LEN ) {
			wp_send_json_error( array( 'message' => __( 'Message is too long.', 'insight-hub-dashboard' ) ) );
		}

		// Basic anti-spam throttle (per user).
		$throttle_key = 'ih_rmsg_last_' . $user_id;
		if ( get_transient( $throttle_key ) ) {
			wp_send_json_error( array( 'message' => __( 'You are sending messages too quickly.', 'insight-hub-dashboard' ) ), 429 );
		}
		set_transient( $throttle_key, 1, IH_RMSG_THROTTLE_SECS );

		global $wpdb;
		$recipient_id = ih_rmsg_recipient_for( $ctx, $user_id );
		$now          = current_time( 'mysql', true );
		$ok           = $wpdb->insert(
			ih_rmsg_table(),
			array(
				'request_id'   => $ctx['request_id'],
				'listing_type' => $ctx['listing_type'],
				'listing_id'   => $ctx['listing_id'],
				'sender_id'    => $user_id,
				'recipient_id' => $recipient_id,
				'body'         => $body,
				'created_at'   => $now,
				'read_at'      => null,
			),
			array( '%d', '%s', '%d', '%d', '%d', '%s', '%s', '%s' )
		);
		if ( false === $ok ) {
			wp_send_json_error( array( 'message' => __( 'Could not save message.', 'insight-hub-dashboard' ) ) );
		}
		$id  = (int) $wpdb->insert_id;
		$row = array(
			'id'         => $id,
			'request_id' => $ctx['request_id'],
			'sender_id'  => $user_id,
			'body'       => $body,
			'created_at' => $now,
			'read_at'    => null,
		);

		if ( function_exists( 'ih_log_activity' ) ) {
			ih_log_activity( 'message', 'Listing message sent on REQ #' . $ctx['request_id'], array( 'request_id' => $ctx['request_id'] ) );
		}

		wp_send_json_success( ih_rmsg_format_message_row( $row, $user_id ) );
	}
}

/* ─────────────────────────────────────────────────────────────────────────
   AJAX: fetch thread (paginated + poll)
───────────────────────────────────────────────────────────────────────── */
add_action( 'wp_ajax_ih_rmsg_thread', 'ih_rmsg_ajax_thread' );
if ( ! function_exists( 'ih_rmsg_ajax_thread' ) ) {
	function ih_rmsg_ajax_thread() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'insight-hub-dashboard' ) ), 403 );
		}
		check_ajax_referer( 'ih_nonce', 'nonce' );

		$user_id    = (int) get_current_user_id();
		$request_id = isset( $_REQUEST['request_id'] ) ? (int) $_REQUEST['request_id'] : 0;
		$after_id   = isset( $_REQUEST['after_id'] ) ? (int) $_REQUEST['after_id'] : 0;
		$before_id  = isset( $_REQUEST['before_id'] ) ? (int) $_REQUEST['before_id'] : 0;
		$limit      = isset( $_REQUEST['limit'] ) ? (int) $_REQUEST['limit'] : IH_RMSG_PAGE_SIZE;
		$limit      = max( 1, min( 100, $limit ) );

		$ctx = ih_rmsg_user_can_access( $request_id, $user_id );
		if ( is_wp_error( $ctx ) ) {
			$status = (int) ( $ctx->get_error_data()['status'] ?? 400 );
			wp_send_json_error( array( 'message' => $ctx->get_error_message() ), $status );
		}

		global $wpdb;
		$tbl      = ih_rmsg_table();
		$has_more = false;

		if ( $after_id > 0 ) {
			// Poll: strictly newer messages, ascending.
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$tbl} WHERE request_id=%d AND id>%d ORDER BY id ASC LIMIT %d",
					$request_id,
					$after_id,
					100
				),
				ARRAY_A
			) ?: array();
		} elseif ( $before_id > 0 ) {
			// Load older page: messages before a cursor, returned ascending.
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM ( SELECT * FROM {$tbl} WHERE request_id=%d AND id<%d ORDER BY id DESC LIMIT %d ) t ORDER BY id ASC",
					$request_id,
					$before_id,
					$limit + 1
				),
				ARRAY_A
			) ?: array();
			if ( count( $rows ) > $limit ) {
				$has_more = true;
				array_shift( $rows );
			}
		} else {
			// Initial: newest page, returned ascending.
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM ( SELECT * FROM {$tbl} WHERE request_id=%d ORDER BY id DESC LIMIT %d ) t ORDER BY id ASC",
					$request_id,
					$limit + 1
				),
				ARRAY_A
			) ?: array();
			if ( count( $rows ) > $limit ) {
				$has_more = true;
				array_shift( $rows );
			}
		}

		$is_group = count( (array) ( $ctx['participants'] ?? array() ) ) > 2;

		$messages = array();
		foreach ( $rows as $row ) {
			$messages[] = ih_rmsg_format_message_row( $row, $user_id, $is_group );
		}

		// On the INITIAL load, surface the "N NEW MESSAGES" divider data — the
		// first-unread id + count addressed to this viewer — BEFORE we mark them
		// read on fetch (so the client can place the divider).
		$first_unread_id = 0;
		$unread_count    = 0;
		if ( $after_id <= 0 && $before_id <= 0 ) {
			$first_unread_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MIN(id) FROM {$tbl} WHERE request_id=%d AND recipient_id=%d AND read_at IS NULL",
					$request_id,
					$user_id
				)
			);
			$unread_count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$tbl} WHERE request_id=%d AND recipient_id=%d AND read_at IS NULL",
					$request_id,
					$user_id
				)
			);
		}

		// Auto-mark messages addressed to this viewer as read on fetch.
		ih_rmsg_mark_thread_read( $request_id, $user_id );

		$other_id = ( $user_id === (int) $ctx['owner_id'] ) ? (int) $ctx['requester_id'] : (int) $ctx['owner_id'];

		wp_send_json_success(
			array(
				'messages'        => $messages,
				'has_more'        => $has_more,
				'request_id'      => $request_id,
				'role'            => ( $user_id === (int) $ctx['owner_id'] ) ? 'owner' : ( $user_id === (int) $ctx['requester_id'] ? 'requester' : 'admin' ),
				'listing_ref'     => ih_rmsg_listing_ref( $ctx['listing_type'], $ctx['listing_id'] ),
				'listing_title'   => ih_rmsg_listing_title( $ctx['listing_type'], $ctx['listing_id'] ),
				'other_id'        => $other_id,
				'other_name'      => ih_rmsg_display_name( $other_id ),
				'is_group'        => $is_group,
				'participants'    => count( (array) ( $ctx['participants'] ?? array() ) ),
				'first_unread_id' => $first_unread_id,
				'unread_count'    => $unread_count,
			)
		);
	}
}

/* ─────────────────────────────────────────────────────────────────────────
   AJAX: mark read
───────────────────────────────────────────────────────────────────────── */
add_action( 'wp_ajax_ih_rmsg_mark_read', 'ih_rmsg_ajax_mark_read' );
if ( ! function_exists( 'ih_rmsg_ajax_mark_read' ) ) {
	function ih_rmsg_ajax_mark_read() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'insight-hub-dashboard' ) ), 403 );
		}
		check_ajax_referer( 'ih_nonce', 'nonce' );
		$user_id    = (int) get_current_user_id();
		$request_id = isset( $_POST['request_id'] ) ? (int) $_POST['request_id'] : 0;

		$ctx = ih_rmsg_user_can_access( $request_id, $user_id );
		if ( is_wp_error( $ctx ) ) {
			$status = (int) ( $ctx->get_error_data()['status'] ?? 400 );
			wp_send_json_error( array( 'message' => $ctx->get_error_message() ), $status );
		}
		$marked = ih_rmsg_mark_thread_read( $request_id, $user_id );
		wp_send_json_success( array( 'marked' => $marked ) );
	}
}

/* ─────────────────────────────────────────────────────────────────────────
   AJAX: unread count poll
───────────────────────────────────────────────────────────────────────── */
add_action( 'wp_ajax_ih_rmsg_unread', 'ih_rmsg_ajax_unread' );
if ( ! function_exists( 'ih_rmsg_ajax_unread' ) ) {
	function ih_rmsg_ajax_unread() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'insight-hub-dashboard' ) ), 403 );
		}
		check_ajax_referer( 'ih_nonce', 'nonce' );
		$user_id = (int) get_current_user_id();

		// Delivery receipt: the recipient's client is online and has received the
		// unread notification. Stamp delivered_at for their not-yet-read, not-yet-
		// delivered messages so senders see "Delivered" (two grey ticks) distinct
		// from "Read". Guarded on the column existing (migration may be mid-flight).
		if ( $user_id && ih_rmsg_has_delivered_col() ) {
			global $wpdb;
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE " . ih_rmsg_table() . " SET delivered_at=%s WHERE recipient_id=%d AND read_at IS NULL AND delivered_at IS NULL",
					current_time( 'mysql', true ),
					$user_id
				)
			);
		}

		wp_send_json_success(
			array(
				'total'       => ih_rmsg_unread_total( $user_id ),
				'per_request' => ih_rmsg_unread_per_request( $user_id ),
			)
		);
	}
}

/* ─────────────────────────────────────────────────────────────────────────
   AJAX: set an access toggle (admin only) — the right-rail green switches.
───────────────────────────────────────────────────────────────────────── */
add_action( 'wp_ajax_ih_rmsg_set_access', 'ih_rmsg_ajax_set_access' );
if ( ! function_exists( 'ih_rmsg_ajax_set_access' ) ) {
	function ih_rmsg_ajax_set_access() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Only an admin can change access controls.', 'insight-hub-dashboard' ) ), 403 );
		}
		check_ajax_referer( 'ih_nonce', 'nonce' );
		$request_id = isset( $_POST['request_id'] ) ? (int) $_POST['request_id'] : 0;
		$key        = isset( $_POST['key'] ) ? sanitize_key( wp_unslash( $_POST['key'] ) ) : '';
		$value      = ! empty( $_POST['value'] );

		$ctx = ih_rmsg_get_thread_context( $request_id );
		if ( is_wp_error( $ctx ) || ! $ctx['approved'] ) {
			wp_send_json_error( array( 'message' => __( 'Conversation not available.', 'insight-hub-dashboard' ) ), 400 );
		}
		$settings = ih_rmsg_set_setting( $request_id, $key, $value );
		wp_send_json_success( array( 'settings' => $settings ) );
	}
}

/* ─────────────────────────────────────────────────────────────────────────
   AJAX: upload an attachment into a thread (image / file / video). Gated on
   the same participant-or-admin access AND the per-thread allow_attachments
   control. Reuses WP's media handler for safe MIME/extension validation.
───────────────────────────────────────────────────────────────────────── */
add_action( 'wp_ajax_ih_rmsg_upload', 'ih_rmsg_ajax_upload' );
if ( ! function_exists( 'ih_rmsg_ajax_upload' ) ) {
	function ih_rmsg_ajax_upload() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'insight-hub-dashboard' ) ), 403 );
		}
		check_ajax_referer( 'ih_nonce', 'nonce' );

		$user_id    = (int) get_current_user_id();
		$request_id = isset( $_POST['request_id'] ) ? (int) $_POST['request_id'] : 0;

		$ctx = ih_rmsg_user_can_access( $request_id, $user_id );
		if ( is_wp_error( $ctx ) ) {
			$status = (int) ( $ctx->get_error_data()['status'] ?? 400 );
			wp_send_json_error( array( 'message' => $ctx->get_error_message() ), $status );
		}
		if ( ! ih_rmsg_has_attachment_cols() ) {
			wp_send_json_error( array( 'message' => __( 'Attachments are not available yet.', 'insight-hub-dashboard' ) ) );
		}
		$settings = ih_rmsg_get_settings( $request_id );
		if ( empty( $settings['allow_attachments'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Attachments are disabled for this conversation.', 'insight-hub-dashboard' ) ), 403 );
		}
		if ( empty( $_FILES['attachment']['name'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file received.', 'insight-hub-dashboard' ) ) );
		}
		$max = (int) apply_filters( 'ih_rmsg_max_upload_bytes', 25 * 1024 * 1024 );
		if ( (int) ( $_FILES['attachment']['size'] ?? 0 ) > $max ) {
			wp_send_json_error( array( 'message' => __( 'File too large (max 25MB).', 'insight-hub-dashboard' ) ) );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		$allowed = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'webp'         => 'image/webp',
			'gif'          => 'image/gif',
			'pdf'          => 'application/pdf',
			'doc'          => 'application/msword',
			'docx'         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'xls'          => 'application/vnd.ms-excel',
			'xlsx'         => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'zip'          => 'application/zip',
			'mp4'          => 'video/mp4',
			'mov'          => 'video/quicktime',
			'webm'         => 'video/webm',
		);
		$aid = media_handle_upload( 'attachment', 0, array(), array( 'test_form' => false, 'mimes' => $allowed ) );
		if ( is_wp_error( $aid ) ) {
			wp_send_json_error( array( 'message' => $aid->get_error_message() ) );
		}
		$url  = wp_get_attachment_url( $aid );
		$mime = (string) get_post_mime_type( $aid );
		$type = strpos( $mime, 'image/' ) === 0 ? 'image' : ( strpos( $mime, 'video/' ) === 0 ? 'video' : 'file' );
		$name = get_the_title( $aid );
		$body = isset( $_POST['body'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['body'] ) ) ) : '';
		if ( mb_strlen( $body ) > IH_RMSG_MAX_LEN ) {
			$body = mb_substr( $body, 0, IH_RMSG_MAX_LEN );
		}

		global $wpdb;
		$recipient_id = ih_rmsg_recipient_for( $ctx, $user_id );
		$now          = current_time( 'mysql', true );
		$ok           = $wpdb->insert(
			ih_rmsg_table(),
			array(
				'request_id'      => $ctx['request_id'],
				'listing_type'    => $ctx['listing_type'],
				'listing_id'      => $ctx['listing_id'],
				'sender_id'       => $user_id,
				'recipient_id'    => $recipient_id,
				'body'            => $body,
				'attachment_url'  => $url,
				'attachment_type' => $type,
				'attachment_name' => $name,
				'attachment_size' => (int) ( $_FILES['attachment']['size'] ?? 0 ),
				'created_at'      => $now,
				'read_at'         => null,
			),
			array( '%d', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);
		if ( false === $ok ) {
			wp_send_json_error( array( 'message' => __( 'Could not save attachment.', 'insight-hub-dashboard' ) ) );
		}
		$id  = (int) $wpdb->insert_id;
		$row = array(
			'id'              => $id,
			'request_id'      => $ctx['request_id'],
			'sender_id'       => $user_id,
			'body'            => $body,
			'attachment_url'  => $url,
			'attachment_type' => $type,
			'attachment_name' => $name,
			'attachment_size' => (int) ( $_FILES['attachment']['size'] ?? 0 ),
			'created_at'      => $now,
			'read_at'         => null,
		);
		$is_group = count( (array) ( $ctx['participants'] ?? array() ) ) > 2;
		wp_send_json_success( ih_rmsg_format_message_row( $row, $user_id, $is_group ) );
	}
}

/* ─────────────────────────────────────────────────────────────────────────
   AJAX: group invite (admin only) — search inviteable users + add a guest to
   a request thread, writing a system "Admin added X to the chat" row.
───────────────────────────────────────────────────────────────────────── */
add_action( 'wp_ajax_ih_rmsg_invite_search', 'ih_rmsg_ajax_invite_search' );
if ( ! function_exists( 'ih_rmsg_ajax_invite_search' ) ) {
	function ih_rmsg_ajax_invite_search() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden.', 'insight-hub-dashboard' ) ), 403 );
		}
		check_ajax_referer( 'ih_nonce', 'nonce' );
		$request_id = isset( $_REQUEST['request_id'] ) ? (int) $_REQUEST['request_id'] : 0;
		$search     = isset( $_REQUEST['search'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['search'] ) ) : '';

		$ctx = ih_rmsg_get_thread_context( $request_id );
		if ( is_wp_error( $ctx ) ) {
			wp_send_json_error( array( 'message' => $ctx->get_error_message() ), 400 );
		}
		$exclude = $ctx['participants'];
		$args    = array(
			'number'  => 30,
			'exclude' => $exclude,
			'orderby' => 'display_name',
			'order'   => 'ASC',
			'fields'  => array( 'ID', 'display_name', 'user_login' ),
		);
		if ( $search ) {
			if ( preg_match( '/usr-?0*(\d+)/i', $search, $m ) ) {
				$args['include'] = array( (int) $m[1] );
				unset( $args['exclude'] );
			} else {
				$args['search']         = '*' . $search . '*';
				$args['search_columns'] = array( 'display_name', 'user_login', 'user_email', 'user_nicename' );
			}
		}
		$found = get_users( $args );
		$out   = array();
		foreach ( (array) $found as $u ) {
			$uid = (int) $u->ID;
			if ( ! $uid || in_array( $uid, $exclude, true ) ) {
				continue;
			}
			$nm    = $u->display_name ?: $u->user_login;
			$out[] = array(
				'id'       => $uid,
				'name'     => $nm,
				'ref'      => function_exists( 'ih_user_ref' ) ? ih_user_ref( $uid ) : ( 'USR-' . $uid ),
				'initials' => ih_rmsg_initials( $nm ),
			);
		}
		wp_send_json_success( array( 'users' => $out ) );
	}
}

add_action( 'wp_ajax_ih_rmsg_invite', 'ih_rmsg_ajax_invite' );
if ( ! function_exists( 'ih_rmsg_ajax_invite' ) ) {
	function ih_rmsg_ajax_invite() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden.', 'insight-hub-dashboard' ) ), 403 );
		}
		check_ajax_referer( 'ih_nonce', 'nonce' );
		$request_id = isset( $_POST['request_id'] ) ? (int) $_POST['request_id'] : 0;
		$invite_id  = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : 0;

		$ctx = ih_rmsg_get_thread_context( $request_id );
		if ( is_wp_error( $ctx ) || ! $ctx['approved'] ) {
			wp_send_json_error( array( 'message' => __( 'Conversation not available.', 'insight-hub-dashboard' ) ), 400 );
		}
		if ( ! $invite_id || ! get_userdata( $invite_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Select a valid user to invite.', 'insight-hub-dashboard' ) ) );
		}
		if ( in_array( $invite_id, $ctx['participants'], true ) ) {
			wp_send_json_error( array( 'message' => __( 'That user is already in the conversation.', 'insight-hub-dashboard' ) ) );
		}

		global $wpdb;
		$actor_id = (int) get_current_user_id();
		$ins      = $wpdb->insert(
			ih_rmsg_participants_table(),
			array(
				'request_id' => $request_id,
				'user_id'    => $invite_id,
				'role'       => 'guest',
				'added_by'   => $actor_id,
				'added_at'   => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%s', '%d', '%s' )
		);
		if ( false === $ins ) {
			wp_send_json_error( array( 'message' => __( 'Could not add the user.', 'insight-hub-dashboard' ) ) );
		}

		// System event row (sender_id 0) — "Admin added X to the chat".
		$name     = ih_rmsg_display_name( $invite_id );
		$sys_text = sprintf( __( 'Admin added %s to the chat', 'insight-hub-dashboard' ), $name );
		$now      = current_time( 'mysql', true );
		$wpdb->insert(
			ih_rmsg_table(),
			array(
				'request_id'   => $request_id,
				'listing_type' => $ctx['listing_type'],
				'listing_id'   => $ctx['listing_id'],
				'sender_id'    => 0,
				'recipient_id' => 0,
				'body'         => $sys_text,
				'created_at'   => $now,
			),
			array( '%d', '%s', '%d', '%d', '%d', '%s', '%s' )
		);
		$sys_id = (int) $wpdb->insert_id;

		wp_send_json_success(
			array(
				'invited'      => array( 'id' => $invite_id, 'name' => $name ),
				'participants' => count( ih_rmsg_get_thread_context( $request_id )['participants'] ),
				'system'       => array(
					'id'     => $sys_id,
					'system' => true,
					'body'   => $sys_text,
					'time'   => get_date_from_gmt( $now, 'g:i A' ),
				),
			)
		);
	}
}
