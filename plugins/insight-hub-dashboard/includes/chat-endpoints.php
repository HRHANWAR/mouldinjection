<?php
/**
 * chat-endpoints.php — new server hooks for the boosted messaging system.
 * Adds attachment upload interceptor, presence, typing, invite, admin reactions.
 */
defined( 'ABSPATH' ) || exit;

/* ---- 1. one-time migration: attachment columns + participants + reactions + delivery ---- */
function ih_chat_run_schema_migrations() {
    $schema_v = (string) get_option( 'ih_chat_schema_v', '0' );
    if ( $schema_v === '4' ) {
        return;
    }
    global $wpdb;
    $c = $wpdb->prefix . 'ih_chats';
    $cols = array(
        'attachment_url'   => 'VARCHAR(255) NULL',
        'attachment_type'  => 'VARCHAR(20) NULL',
        'attachment_name'  => 'VARCHAR(190) NULL',
        'attachment_size'  => 'INT NULL',
        'sender_id'        => 'BIGINT NULL',
        'reaction'         => 'VARCHAR(16) NULL',
        'delivery_status'  => "VARCHAR(20) NOT NULL DEFAULT 'sent'",
        'read_at'          => 'DATETIME NULL',
    );
    foreach ( $cols as $name => $type ) {
        $exists = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM `$c` LIKE %s", $name ) );
        if ( ! $exists ) {
            $wpdb->query( "ALTER TABLE `$c` ADD COLUMN `$name` $type" );
        }
    }
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    if ( (int) $schema_v < 2 ) {
        dbDelta( "CREATE TABLE {$wpdb->prefix}ih_thread_participants (
            id BIGINT NOT NULL AUTO_INCREMENT,
            thread_id BIGINT NOT NULL,
            user_id BIGINT NOT NULL,
            role VARCHAR(20) DEFAULT 'guest',
            added_by BIGINT NULL,
            added_at DATETIME NULL,
            PRIMARY KEY (id), KEY thread_id (thread_id), KEY user_id (user_id)
        ) {$wpdb->get_charset_collate()};" );
    }
    if ( (int) $schema_v < 3 ) {
        dbDelta( "CREATE TABLE {$wpdb->prefix}ih_message_reactions (
            id BIGINT NOT NULL AUTO_INCREMENT,
            message_id BIGINT NOT NULL,
            user_id BIGINT NOT NULL,
            emoji VARCHAR(16) NOT NULL,
            created_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY msg_user_emoji (message_id, user_id, emoji),
            KEY message_id (message_id),
            KEY user_id (user_id)
        ) {$wpdb->get_charset_collate()};" );
    }
    if ( (int) $schema_v < 4 ) {
        $wpdb->query( "UPDATE `$c` SET delivery_status='delivered' WHERE delivery_status='sent' OR delivery_status='' OR delivery_status IS NULL" );
    }
    update_option( 'ih_chat_schema_v', '4' );
}
add_action( 'admin_init', 'ih_chat_run_schema_migrations' );
add_action( 'init', 'ih_chat_run_schema_migrations' );

if ( ! function_exists( 'ih_chat_delivery_columns' ) ) {
    function ih_chat_delivery_columns() {
        static $has = null;
        if ( null === $has ) {
            global $wpdb;
            $cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}ih_chats" );
            $has  = in_array( 'delivery_status', (array) $cols, true );
        }
        return $has;
    }
}

if ( ! function_exists( 'ih_chat_normalize_delivery_status' ) ) {
    function ih_chat_normalize_delivery_status( $row ) {
        if ( ! empty( $row['read_at'] ) ) {
            return 'read';
        }
        $s = strtolower( (string) ( $row['delivery_status'] ?? 'sent' ) );
        if ( in_array( $s, array( 'sending', 'sent', 'delivered', 'read', 'failed' ), true ) ) {
            return $s;
        }
        return 'sent';
    }
}

if ( ! function_exists( 'ih_chat_is_outbound_row' ) ) {
    function ih_chat_is_outbound_row( $from_me, $viewer_is_admin ) {
        return $viewer_is_admin ? (int) $from_me === 1 : (int) $from_me === 0;
    }
}

if ( ! function_exists( 'ih_chat_outbound_delivery_status' ) ) {
    function ih_chat_outbound_delivery_status( $row, $viewer_is_admin ) {
        if ( ! ih_chat_is_outbound_row( $row['from_me'] ?? 0, $viewer_is_admin ) ) {
            return null;
        }
        return ih_chat_normalize_delivery_status( $row );
    }
}

if ( ! function_exists( 'ih_chat_delivery_insert_defaults' ) ) {
    function ih_chat_delivery_insert_defaults( array $row ) {
        if ( ih_chat_delivery_columns() && ! isset( $row['delivery_status'] ) ) {
            $row['delivery_status'] = 'sent';
        }
        return $row;
    }
}

if ( ! function_exists( 'ih_chat_mark_sender_messages_delivered' ) ) {
    function ih_chat_mark_sender_messages_delivered( $thread_id, $sender_from_me ) {
        if ( ! ih_chat_delivery_columns() || ! $thread_id ) {
            return;
        }
        global $wpdb;
        $c = $wpdb->prefix . 'ih_chats';
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE `$c` SET delivery_status='delivered' WHERE thread_id=%d AND from_me=%d AND delivery_status='sent'",
                (int) $thread_id,
                (int) $sender_from_me
            )
        );
    }
}

if ( ! function_exists( 'ih_chat_mark_sender_messages_read' ) ) {
    function ih_chat_mark_sender_messages_read( $thread_id, $sender_from_me ) {
        if ( ! ih_chat_delivery_columns() || ! $thread_id ) {
            return;
        }
        global $wpdb;
        $c   = $wpdb->prefix . 'ih_chats';
        $now = current_time( 'mysql', true );
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE `$c` SET delivery_status='read', read_at=%s WHERE thread_id=%d AND from_me=%d AND delivery_status IN ('sent','delivered')",
                $now,
                (int) $thread_id,
                (int) $sender_from_me
            )
        );
    }
}

if ( ! function_exists( 'ih_chat_msg_status_class' ) ) {
    function ih_chat_msg_status_class( $status ) {
        if ( ! $status || 'sent' === $status ) {
            return '';
        }
        return ' ih-msg--' . sanitize_html_class( $status );
    }
}

if ( ! function_exists( 'ih_chat_status_icon_svg' ) ) {
    function ih_chat_status_icon_svg( $status ) {
        switch ( $status ) {
            case 'sending':
                return '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';
            case 'sent':
                return '<svg width="14" height="10" viewBox="0 0 24 18" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M1 9l5 5L17 3"/></svg>';
            case 'delivered':
                return '<svg width="16" height="10" viewBox="0 0 28 18" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M1 9l5 5L17 3"/><path d="M7 14L23 3"/></svg>';
            case 'read':
                return '<svg width="16" height="10" viewBox="0 0 28 18" fill="none" stroke="#17C7C7" stroke-width="2.4" aria-hidden="true"><path d="M1 9l5 5L17 3"/><path d="M7 14L23 3"/></svg>';
            default:
                return '';
        }
    }
}

if ( ! function_exists( 'ih_chat_outbound_status_map' ) ) {
    function ih_chat_outbound_status_map( $thread_id, $viewer_is_admin ) {
        if ( ! ih_chat_delivery_columns() || ! $thread_id ) {
            return array();
        }
        global $wpdb;
        $from_me = $viewer_is_admin ? 1 : 0;
        $rows    = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, delivery_status, read_at FROM {$wpdb->prefix}ih_chats WHERE thread_id=%d AND from_me=%d ORDER BY id ASC",
                (int) $thread_id,
                $from_me
            ),
            ARRAY_A
        );
        $map = array();
        foreach ( (array) $rows as $row ) {
            $map[ (int) $row['id'] ] = ih_chat_normalize_delivery_status( $row );
        }
        return $map;
    }
}

if ( ! function_exists( 'ih_chat_render_outbound_meta_icons' ) ) {
    function ih_chat_render_outbound_meta_icons( $status ) {
        if ( ! $status || 'failed' === $status ) {
            return '';
        }
        $icon = ih_chat_status_icon_svg( $status );
        if ( ! $icon ) {
            return '';
        }
        return '<span class="ihc-status-icon ihc-status-icon--' . esc_attr( $status ) . '" aria-hidden="true">' . $icon . '</span>';
    }
}

/* ---- 2. attachment interceptor (runs before the original send handlers) ---- */
function ih_chat_handle_attachment() {
    if ( empty( $_FILES['attachment']['name'] ) ) {
        return;
    }
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Not logged in' ), 403 );
    }
    check_ajax_referer( 'ih_nonce', 'nonce' );
    global $wpdb;
    $uid      = get_current_user_id();
    $is_admin = current_user_can( 'manage_options' );
    $from_me  = $is_admin ? 1 : 0;

    $thread_id = intval( $_POST['thread_id'] ?? 0 );
    if ( $is_admin && ! $thread_id && ! empty( $_POST['user_id'] ) && function_exists( 'ih_get_thread_by_user_id' ) ) {
        $thread_id = (int) ih_get_thread_by_user_id( intval( $_POST['user_id'] ) );
    }
    if ( ! $is_admin ) {
        if ( $thread_id ) {
            $owner = (int) $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$wpdb->prefix}ih_threads WHERE id=%d", $thread_id ) );
            if ( $owner !== $uid ) {
                wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
            }
        } else {
            $wpdb->insert(
                $wpdb->prefix . 'ih_threads',
                array(
                    'user_id'      => $uid,
                    'listing_id'   => 0,
                    'listing_type' => 'machine',
                    'last_message' => '[attachment]',
                    'last_time'    => current_time( 'mysql', true ),
                    'unread'       => 1,
                )
            );
            $thread_id = (int) $wpdb->insert_id;
        }
    }
    if ( ! $thread_id ) {
        wp_send_json_error( array( 'message' => 'No thread' ) );
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
    if ( (int) ( $_FILES['attachment']['size'] ?? 0 ) > 25 * 1024 * 1024 ) {
        wp_send_json_error( array( 'message' => 'File too large (max 25MB)' ) );
    }
    $aid = media_handle_upload( 'attachment', 0, array(), array( 'test_form' => false, 'mimes' => $allowed ) );
    if ( is_wp_error( $aid ) ) {
        wp_send_json_error( array( 'message' => $aid->get_error_message() ) );
    }
    $url  = wp_get_attachment_url( $aid );
    $mime = get_post_mime_type( $aid );
    $type = strpos( $mime, 'image/' ) === 0 ? 'image' : ( strpos( $mime, 'video/' ) === 0 ? 'video' : 'file' );
    $name = get_the_title( $aid );
    $msg  = sanitize_textarea_field( $_POST['message'] ?? '' );

    $row = ih_chat_delivery_insert_defaults(
        array(
            'thread_id'       => $thread_id,
            'from_me'         => $from_me,
            'sender_id'       => $uid,
            'message'         => $msg,
            'sent_at'         => current_time( 'mysql', true ),
            'attachment_url'  => $url,
            'attachment_type' => $type,
            'attachment_name' => $name,
            'attachment_size' => (int) ( $_FILES['attachment']['size'] ?? 0 ),
        )
    );
    $existing = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}ih_chats" );
    $row      = array_intersect_key( $row, array_flip( $existing ) );
    $wpdb->insert( $wpdb->prefix . 'ih_chats', $row );
    $mid = (int) $wpdb->insert_id;
    $wpdb->update(
        $wpdb->prefix . 'ih_threads',
        array(
            'last_message' => $msg ?: '[' . $type . ']',
            'last_time'    => current_time( 'mysql', true ),
            'unread'       => $is_admin ? 0 : 1,
        ),
        array( 'id' => $thread_id )
    );

    wp_send_json_success(
        array(
            'id'              => $mid,
            'thread_id'       => $thread_id,
            'from_me'         => $from_me,
            'message'         => $msg,
            'attachment_url'  => $url,
            'attachment_type' => $type,
            'attachment_name' => $name,
            'attachment_size' => (int) ( $_FILES['attachment']['size'] ?? 0 ),
            'time'            => date_i18n( 'g:i A' ),
            'delivery_status' => 'sent',
        )
    );
}
add_action( 'wp_ajax_ih_send_message', 'ih_chat_handle_attachment', 1 );
add_action( 'wp_ajax_ih_user_send_message', 'ih_chat_handle_attachment', 1 );

/* ---- 3. presence heartbeat ---- */
add_action( 'wp_ajax_ih_heartbeat', function () {
    check_ajax_referer( 'ih_nonce', 'nonce' );
    $uid = get_current_user_id();
    set_transient( 'ih_presence_' . $uid, time(), 70 );
    if ( current_user_can( 'manage_options' ) ) {
        set_transient( 'ih_presence_admin', time(), 70 );
    }
    if ( current_user_can( 'manage_options' ) ) {
        $with = intval( $_GET['with'] ?? 0 );
        $beat = $with ? get_transient( 'ih_presence_' . $with ) : 0;
    } else {
        $beat = get_transient( 'ih_presence_admin' );
    }
    $online = $beat && ( time() - (int) $beat ) < 70;
    $secs   = $beat ? max( 0, time() - (int) $beat ) : null;
    $last   = $secs === null ? 'a while ago' : ( $secs < 90 ? 'just now' : ( floor( $secs / 60 ) . 'm ago' ) );
    wp_send_json_success( array( 'online' => $online, 'last_seen' => $last ) );
} );

/* ---- 4. typing flag ---- */
add_action( 'wp_ajax_ih_typing', function () {
    check_ajax_referer( 'ih_nonce', 'nonce' );
    $thread = intval( $_GET['thread_id'] ?? $_POST['thread_id'] ?? 0 );
    $on     = ! empty( $_GET['typing'] ) || ! empty( $_POST['typing'] );
    if ( $thread ) {
        if ( $on ) {
            set_transient( 'ih_typing_' . $thread, get_current_user_id(), 6 );
        } else {
            delete_transient( 'ih_typing_' . $thread );
        }
    }
    wp_send_json_success();
} );

/* ---- 5. invite users to a chat (admin only) ---- */

if ( ! function_exists( 'ih_chat_thread_excluded_user_ids' ) ) {
    /** Owner + guests already in the thread. */
    function ih_chat_thread_excluded_user_ids( $thread_id ) {
        global $wpdb;
        $thread_id = (int) $thread_id;
        $owner     = (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT user_id FROM {$wpdb->prefix}ih_threads WHERE id=%d", $thread_id )
        );
        $guests = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}ih_thread_participants WHERE thread_id=%d",
                $thread_id
            )
        );
        return array_values( array_unique( array_filter( array_merge( array( $owner ), array_map( 'intval', (array) $guests ) ) ) ) );
    }
}

if ( ! function_exists( 'ih_chat_invite_users_to_thread' ) ) {
    /**
     * @return array{invited: array, messages: array}
     */
    function ih_chat_invite_users_to_thread( $thread_id, $user_ids, $added_by = 0 ) {
        global $wpdb;
        $thread_id = (int) $thread_id;
        $added_by  = (int) ( $added_by ?: get_current_user_id() );
        $invited   = array();
        $messages  = array();
        if ( ! $thread_id || empty( $user_ids ) ) {
            return array( 'invited' => $invited, 'messages' => $messages );
        }
        $exclude   = ih_chat_thread_excluded_user_ids( $thread_id );
        $chat_cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}ih_chats" );
        foreach ( array_unique( array_map( 'intval', (array) $user_ids ) ) as $user_id ) {
            if ( ! $user_id || in_array( $user_id, $exclude, true ) ) {
                continue;
            }
            $exists = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}ih_thread_participants WHERE thread_id=%d AND user_id=%d",
                    $thread_id,
                    $user_id
                )
            );
            if ( $exists ) {
                continue;
            }
            $wpdb->insert(
                $wpdb->prefix . 'ih_thread_participants',
                array(
                    'thread_id' => $thread_id,
                    'user_id'   => $user_id,
                    'role'      => 'guest',
                    'added_by'  => $added_by,
                    'added_at'  => current_time( 'mysql', true ),
                )
            );
            $u        = get_userdata( $user_id );
            $name     = $u ? ( $u->display_name ?: $u->user_login ) : ( 'User #' . $user_id );
            $sys_text = '➕ Admin added ' . $name . ' to the chat.';
            $sys_row  = array(
                'thread_id' => $thread_id,
                'from_me'   => 1,
                'message'   => $sys_text,
                'sent_at'   => current_time( 'mysql', true ),
            );
            if ( in_array( 'sender_id', $chat_cols, true ) ) {
                $sys_row['sender_id'] = $added_by;
            }
            $wpdb->insert( $wpdb->prefix . 'ih_chats', $sys_row );
            $sys_id    = (int) $wpdb->insert_id;
            $invited[] = array( 'id' => $user_id, 'name' => $name );
            $messages[] = array(
                'id'      => $sys_id,
                'type'    => 'system',
                'message' => $sys_text,
                'text'    => ih_chat_system_message_label( $sys_text ),
                'time'    => date_i18n( 'g:i A' ),
                'from_me' => 1,
            );
            $exclude[] = $user_id;
        }
        return array( 'invited' => $invited, 'messages' => $messages );
    }
}

if ( ! function_exists( 'ih_chat_format_invite_user_row' ) ) {
    function ih_chat_format_invite_user_row( $user_id ) {
        $meta = ih_chat_participant_meta( $user_id );
        if ( empty( $meta['id'] ) ) {
            return null;
        }
        $meta['usr_id'] = function_exists( 'ih_user_ref' ) ? ih_user_ref( $user_id ) : ( 'USR-' . $user_id );
        return $meta;
    }
}

add_action( 'wp_ajax_ih_invite_to_chat', function () {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
    }
    check_ajax_referer( 'ih_nonce', 'nonce' );
    $thread = intval( $_POST['thread_id'] ?? 0 );
    $user   = intval( $_POST['user_id'] ?? 0 );
    if ( ! $thread || ! $user ) {
        wp_send_json_error( array( 'message' => 'Missing data' ) );
    }
    $result = ih_chat_invite_users_to_thread( $thread, array( $user ) );
    $name   = ! empty( $result['invited'][0]['name'] ) ? $result['invited'][0]['name'] : '';
    if ( ! $name ) {
        wp_send_json_error( array( 'message' => 'Already in chat or invalid user' ) );
    }
    $sys = $result['messages'][0] ?? array();
    wp_send_json_success(
        array(
            'message'        => 'invited',
            'name'           => $name,
            'thread_id'      => $thread,
            'participants'   => ih_chat_thread_participants( $thread ),
            'conversation'   => ih_chat_conversation_payload( $thread ),
            'system_message' => $sys,
        )
    );
} );

if ( ! function_exists( 'ih_chat_fetch_inviteable_users' ) ) {
    function ih_chat_fetch_inviteable_users( $thread_id, $search = '' ) {
        $thread_id = (int) $thread_id;
        if ( ! $thread_id ) {
            return array();
        }
        $search  = sanitize_text_field( (string) $search );
        $exclude = ih_chat_thread_excluded_user_ids( $thread_id );
        $args    = array(
            'role__in' => array( 'subscriber', 'customer', 'contributor', 'author' ),
            'number'   => 50,
            'exclude'  => $exclude,
            'orderby'  => 'display_name',
            'order'    => 'ASC',
            'fields'   => 'ID',
        );
        if ( $search ) {
            if ( preg_match( '/usr-?0*(\d+)/i', $search, $m ) ) {
                $uid = (int) $m[1];
                if ( $uid && ! in_array( $uid, $exclude, true ) ) {
                    $args['include'] = array( $uid );
                    unset( $args['exclude'] );
                } else {
                    return array();
                }
            } else {
                $args['search']         = '*' . $search . '*';
                $args['search_columns'] = array( 'display_name', 'user_login', 'user_nicename' );
            }
        }
        $ids   = get_users( $args );
        $users = array();
        foreach ( (array) $ids as $uid ) {
            $uid = (int) $uid;
            if ( ! $uid || user_can( $uid, 'manage_options' ) || in_array( $uid, $exclude, true ) ) {
                continue;
            }
            $row = ih_chat_format_invite_user_row( $uid );
            if ( $row ) {
                $users[] = $row;
            }
        }
        return $users;
    }
}

add_action( 'wp_ajax_ih_admin_invite_users', function () {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
    }
    check_ajax_referer( 'ih_nonce', 'nonce' );
    $thread_id = (int) ( $_GET['thread_id'] ?? $_POST['thread_id'] ?? $_GET['conversation_id'] ?? $_POST['conversation_id'] ?? 0 );
    if ( ! $thread_id ) {
        wp_send_json_error( array( 'message' => 'No thread' ) );
    }
    $search = sanitize_text_field( wp_unslash( $_GET['search'] ?? $_POST['search'] ?? '' ) );
    wp_send_json_success( array( 'users' => ih_chat_fetch_inviteable_users( $thread_id, $search ) ) );
} );

add_action( 'wp_ajax_ih_admin_invite_to_chat', function () {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
    }
    check_ajax_referer( 'ih_nonce', 'nonce' );
    global $wpdb;
    $thread_id = (int) ( $_POST['thread_id'] ?? $_POST['conversation_id'] ?? 0 );
    $user_ids  = isset( $_POST['user_ids'] ) ? array_map( 'intval', (array) wp_unslash( $_POST['user_ids'] ) ) : array();
    if ( ! $thread_id || empty( $user_ids ) ) {
        wp_send_json_error( array( 'message' => 'Missing thread or users' ) );
    }
    $exists = (int) $wpdb->get_var(
        $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}ih_threads WHERE id=%d", $thread_id )
    );
    if ( ! $exists ) {
        wp_send_json_error( array( 'message' => 'Thread not found' ) );
    }
    $result = ih_chat_invite_users_to_thread( $thread_id, $user_ids );
    if ( empty( $result['invited'] ) ) {
        wp_send_json_error( array( 'message' => 'No users were added (already in chat or invalid)' ) );
    }
    wp_send_json_success(
        array(
            'invited'         => $result['invited'],
            'participants'    => ih_chat_thread_participants( $thread_id ),
            'conversation'    => ih_chat_conversation_payload( $thread_id ),
            'system_messages' => $result['messages'],
        )
    );
} );


/* ---- 6. message reactions (normalized table) ---- */

if ( ! function_exists( 'ih_chat_reactions_table' ) ) {
    function ih_chat_reactions_table() {
        global $wpdb;
        return $wpdb->prefix . 'ih_message_reactions';
    }
}

if ( ! function_exists( 'ih_chat_sanitize_reaction_emoji' ) ) {
    function ih_chat_sanitize_reaction_emoji( $emoji ) {
        $emoji = trim( (string) $emoji );
        if ( $emoji === '' || mb_strlen( $emoji ) > 8 ) {
            return '';
        }
        return $emoji;
    }
}

if ( ! function_exists( 'ih_chat_user_can_access_message' ) ) {
    function ih_chat_user_can_access_message( $message_id, $user_id = 0 ) {
        global $wpdb;
        $message_id = (int) $message_id;
        $user_id    = $user_id ? (int) $user_id : (int) get_current_user_id();
        if ( ! $message_id || ! $user_id ) {
            return false;
        }
        if ( user_can( $user_id, 'manage_options' ) ) {
            return true;
        }
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT c.thread_id, t.user_id AS owner_id
                 FROM {$wpdb->prefix}ih_chats c
                 INNER JOIN {$wpdb->prefix}ih_threads t ON t.id = c.thread_id
                 WHERE c.id = %d",
                $message_id
            ),
            ARRAY_A
        );
        if ( ! $row ) {
            return false;
        }
        if ( (int) $row['owner_id'] === $user_id ) {
            return true;
        }
        $guest = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}ih_thread_participants WHERE thread_id=%d AND user_id=%d LIMIT 1",
                (int) $row['thread_id'],
                $user_id
            )
        );
        return $guest > 0;
    }
}

if ( ! function_exists( 'ih_chat_reactions_for_messages' ) ) {
    /**
     * @param int[]    $message_ids Message IDs.
     * @param int|null $viewer_id   Current user for "mine" flag.
     * @return array<int, array<int, array{emoji:string,count:int,mine:bool}>>
     */
    function ih_chat_reactions_for_messages( $message_ids, $viewer_id = null ) {
        global $wpdb;
        $message_ids = array_values( array_filter( array_map( 'intval', (array) $message_ids ) ) );
        if ( ! $message_ids ) {
            return array();
        }
        $viewer_id = $viewer_id ? (int) $viewer_id : (int) get_current_user_id();
        $tbl       = ih_chat_reactions_table();
        $exists    = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tbl ) );
        if ( $exists !== $tbl ) {
            return array();
        }
        $placeholders = implode( ',', array_fill( 0, count( $message_ids ), '%d' ) );
        $rows         = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT message_id, emoji, COUNT(*) AS cnt
                 FROM {$tbl}
                 WHERE message_id IN ($placeholders)
                 GROUP BY message_id, emoji",
                ...$message_ids
            ),
            ARRAY_A
        ) ?: array();

        $mine_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT message_id, emoji FROM {$tbl} WHERE user_id=%d AND message_id IN ($placeholders)",
                $viewer_id,
                ...$message_ids
            ),
            ARRAY_A
        ) ?: array();
        $mine_map = array();
        foreach ( $mine_rows as $mr ) {
            $mine_map[ (int) $mr['message_id'] . ':' . $mr['emoji'] ] = true;
        }

        $out = array();
        foreach ( $rows as $row ) {
            $mid   = (int) $row['message_id'];
            $emoji = (string) $row['emoji'];
            if ( ! isset( $out[ $mid ] ) ) {
                $out[ $mid ] = array();
            }
            $out[ $mid ][] = array(
                'emoji' => $emoji,
                'count' => (int) $row['cnt'],
                'mine'  => ! empty( $mine_map[ $mid . ':' . $emoji ] ),
            );
        }
        return $out;
    }
}

if ( ! function_exists( 'ih_chat_attach_reactions' ) ) {
    function ih_chat_attach_reactions( array &$messages, $viewer_id = null ) {
        $ids = array();
        foreach ( $messages as $msg ) {
            if ( ! empty( $msg['id'] ) ) {
                $ids[] = (int) $msg['id'];
            }
        }
        $map = ih_chat_reactions_for_messages( $ids, $viewer_id );
        foreach ( $messages as &$msg ) {
            $mid = (int) ( $msg['id'] ?? 0 );
            $msg['reactions'] = $mid && isset( $map[ $mid ] ) ? $map[ $mid ] : array();
        }
        unset( $msg );
    }
}

if ( ! function_exists( 'ih_chat_toggle_message_reaction' ) ) {
    function ih_chat_toggle_message_reaction( $message_id, $user_id, $emoji ) {
        global $wpdb;
        $message_id = (int) $message_id;
        $user_id    = (int) $user_id;
        $emoji      = ih_chat_sanitize_reaction_emoji( $emoji );
        if ( ! $message_id || ! $user_id || $emoji === '' ) {
            return new WP_Error( 'invalid', 'Invalid reaction data' );
        }
        if ( ! ih_chat_user_can_access_message( $message_id, $user_id ) ) {
            return new WP_Error( 'forbidden', 'Unauthorized', array( 'status' => 403 ) );
        }
        $tbl    = ih_chat_reactions_table();
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$tbl} WHERE message_id=%d AND user_id=%d AND emoji=%s LIMIT 1",
                $message_id,
                $user_id,
                $emoji
            )
        );
        if ( $exists ) {
            $wpdb->delete(
                $tbl,
                array(
                    'message_id' => $message_id,
                    'user_id'    => $user_id,
                    'emoji'      => $emoji,
                )
            );
            $added = false;
        } else {
            $wpdb->insert(
                $tbl,
                array(
                    'message_id' => $message_id,
                    'user_id'    => $user_id,
                    'emoji'      => $emoji,
                    'created_at' => current_time( 'mysql', true ),
                )
            );
            $added = true;
        }
        $reactions = ih_chat_reactions_for_messages( array( $message_id ), $user_id );
        return array(
            'added'     => $added,
            'reactions' => $reactions[ $message_id ] ?? array(),
        );
    }
}

if ( ! function_exists( 'ih_chat_render_reaction_chips' ) ) {
    function ih_chat_render_reaction_chips( $reactions ) {
        if ( empty( $reactions ) || ! is_array( $reactions ) ) {
            return '';
        }
        $html = '';
        foreach ( $reactions as $r ) {
            $emoji = esc_attr( $r['emoji'] ?? '' );
            $count = (int) ( $r['count'] ?? 0 );
            $mine  = ! empty( $r['mine'] ) ? ' is-mine' : '';
            if ( $emoji === '' || $count < 1 ) {
                continue;
            }
            $html .= '<button type="button" class="ih-msg-reaction-chip' . $mine . '" data-emoji="' . $emoji . '">'
                . '<span class="e">' . esc_html( $r['emoji'] ) . '</span>'
                . '<span class="n">' . esc_html( (string) $count ) . '</span></button>';
        }
        return $html;
    }
}

add_action( 'wp_ajax_ih_toggle_message_reaction', function () {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Not logged in' ), 403 );
    }
    check_ajax_referer( 'ih_nonce', 'nonce' );
    $message_id = intval( $_POST['message_id'] ?? $_POST['msg_id'] ?? 0 );
    $emoji      = sanitize_text_field( wp_unslash( $_POST['emoji'] ?? $_POST['reaction'] ?? '' ) );
    $result     = ih_chat_toggle_message_reaction( $message_id, get_current_user_id(), $emoji );
    if ( is_wp_error( $result ) ) {
        $status = (int) ( $result->get_error_data()['status'] ?? 400 );
        wp_send_json_error( array( 'message' => $result->get_error_message() ), $status );
    }
    wp_send_json_success( $result );
} );

/* ---- 7. group chat helpers + conversation meta ---- */

if ( ! function_exists( 'ih_chat_is_user_online' ) ) {
    function ih_chat_is_user_online( $user_id ) {
        $user_id = (int) $user_id;
        if ( ! $user_id ) {
            return false;
        }
        if ( current_user_can( 'manage_options' ) && $user_id === (int) get_current_user_id() ) {
            return true;
        }
        $beat = get_transient( 'ih_presence_' . $user_id );
        return $beat && ( time() - (int) $beat ) < 70;
    }
}

if ( ! function_exists( 'ih_chat_short_name' ) ) {
    function ih_chat_short_name( $name ) {
        $name  = trim( (string) $name );
        $parts = preg_split( '/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY );
        if ( count( $parts ) >= 2 ) {
            return $parts[0] . ' ' . strtoupper( substr( $parts[1], 0, 1 ) ) . '.';
        }
        return $name;
    }
}

if ( ! function_exists( 'ih_chat_participant_meta' ) ) {
    function ih_chat_participant_meta( $user_id ) {
        $user_id = (int) $user_id;
        $u       = $user_id ? get_userdata( $user_id ) : false;
        $name    = $u ? ( $u->display_name ?: $u->user_login ) : ( 'User #' . $user_id );
        $initials = function_exists( 'ih_user_initials' ) ? ih_user_initials( $name ) : strtoupper( substr( $name, 0, 2 ) );
        $color    = function_exists( 'ih_user_avatar_color' ) ? ih_user_avatar_color( $user_id ?: $name ) : '#8c5cf5';
        return array(
            'id'           => $user_id,
            'name'         => $name,
            'short_name'   => ih_chat_short_name( $name ),
            'initials'     => $initials,
            'avatar_color' => $color,
            'online'       => ih_chat_is_user_online( $user_id ),
            'is_admin'     => $user_id && user_can( $user_id, 'manage_options' ),
        );
    }
}

if ( ! function_exists( 'ih_chat_thread_participants' ) ) {
    /** Thread owner + invited guests (unique, owner first). */
    function ih_chat_thread_participants( $thread_id ) {
        global $wpdb;
        $thread_id = (int) $thread_id;
        if ( ! $thread_id ) {
            return array();
        }
        $owner_id = (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT user_id FROM {$wpdb->prefix}ih_threads WHERE id=%d", $thread_id )
        );
        $guest_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}ih_thread_participants WHERE thread_id=%d ORDER BY id ASC",
                $thread_id
            )
        );
        $ids = array();
        if ( $owner_id ) {
            $ids[] = $owner_id;
        }
        foreach ( (array) $guest_ids as $gid ) {
            $gid = (int) $gid;
            if ( $gid && ! in_array( $gid, $ids, true ) ) {
                $ids[] = $gid;
            }
        }
        $admin_id = (int) get_current_user_id();
        if ( $admin_id && current_user_can( 'manage_options' ) && ! in_array( $admin_id, $ids, true ) ) {
            $ids[] = $admin_id;
        }
        $out = array();
        foreach ( $ids as $uid ) {
            $out[] = ih_chat_participant_meta( $uid );
        }
        return $out;
    }
}

if ( ! function_exists( 'ih_chat_is_group_thread' ) ) {
    function ih_chat_is_group_thread( $thread_id ) {
        return count( ih_chat_thread_participants( $thread_id ) ) >= 3;
    }
}

if ( ! function_exists( 'ih_chat_detect_message_type' ) ) {
    function ih_chat_detect_message_type( $text ) {
        $text = trim( (string) $text );
        if ( preg_match( '/^(?:➕\s*)?Admin added .+ to the chat\.?$/iu', $text ) ) {
            return 'system';
        }
        return 'chat';
    }
}

if ( ! function_exists( 'ih_chat_system_message_label' ) ) {
    function ih_chat_system_message_label( $text ) {
        $text = trim( (string) $text );
        if ( preg_match( '/^(?:➕\s*)?Admin added (.+?) to the chat\.?$/iu', $text, $m ) ) {
            return 'Admin added ' . trim( $m[1] ) . ' to the chat';
        }
        return preg_replace( '/^➕\s*/u', '', $text );
    }
}

if ( ! function_exists( 'ih_chat_group_title' ) ) {
    function ih_chat_group_title( $participants, $viewer_id = 0 ) {
        $viewer_id = $viewer_id ?: (int) get_current_user_id();
        $names     = array();
        $has_you   = false;
        foreach ( (array) $participants as $p ) {
            $pid = (int) ( $p['id'] ?? 0 );
            if ( $pid === $viewer_id ) {
                $has_you = true;
                continue;
            }
            $names[] = $p['short_name'] ?? $p['name'] ?? 'User';
        }
        if ( empty( $names ) ) {
            return 'Group chat';
        }
        if ( count( $names ) === 1 ) {
            return $has_you ? ( $names[0] . ' & you' ) : $names[0];
        }
        if ( count( $names ) === 2 ) {
            return $has_you
                ? ( $names[0] . ', ' . $names[1] . ' & you' )
                : ( $names[0] . ', ' . $names[1] );
        }
        $last = array_pop( $names );
        if ( ! $has_you ) {
            return implode( ', ', $names ) . ', ' . $last;
        }
        return implode( ', ', $names ) . ', ' . $last . ' & you';
    }
}

if ( ! function_exists( 'ih_chat_conversation_payload' ) ) {
    function ih_chat_conversation_payload( $thread_id ) {
        $participants = ih_chat_thread_participants( $thread_id );
        $online       = 0;
        foreach ( $participants as $p ) {
            if ( ! empty( $p['online'] ) ) {
                $online++;
            }
        }
        return array(
            'thread_id'    => (int) $thread_id,
            'is_group'     => count( $participants ) >= 3,
            'participants' => $participants,
            'online_count' => $online,
            'group_title'  => ih_chat_group_title( $participants ),
        );
    }
}

add_action( 'wp_ajax_ih_get_thread_participants', function () {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
    }
    check_ajax_referer( 'ih_nonce', 'nonce' );
    $thread_id = (int) ( $_GET['thread_id'] ?? $_POST['thread_id'] ?? 0 );
    $user_id   = (int) ( $_GET['user_id'] ?? $_POST['user_id'] ?? 0 );
    if ( ! $thread_id && $user_id && function_exists( 'ih_get_thread_by_user_id' ) ) {
        $thread_id = (int) ih_get_thread_by_user_id( $user_id );
    }
    if ( ! $thread_id ) {
        wp_send_json_success( ih_chat_conversation_payload( 0 ) );
    }
    wp_send_json_success( ih_chat_conversation_payload( $thread_id ) );
} );

add_action( 'wp_ajax_ih_get_invite_users', function () {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
    }
    check_ajax_referer( 'ih_nonce', 'nonce' );
    $thread_id = (int) ( $_GET['thread_id'] ?? $_POST['thread_id'] ?? 0 );
    $user_id   = (int) ( $_GET['user_id'] ?? $_POST['user_id'] ?? 0 );
    if ( ! $thread_id && $user_id && function_exists( 'ih_get_thread_by_user_id' ) ) {
        $thread_id = (int) ih_get_thread_by_user_id( $user_id );
    }
    $search = sanitize_text_field( wp_unslash( $_GET['search'] ?? $_POST['search'] ?? '' ) );
    wp_send_json_success( array( 'users' => ih_chat_fetch_inviteable_users( $thread_id, $search ) ) );
} );
