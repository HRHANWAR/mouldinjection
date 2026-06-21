<?php defined( 'ABSPATH' ) || exit;

$view_uid = isset($_GET['view']) ? (int) $_GET['view'] : 0;
if ( $view_uid ) {
    include __DIR__ . '/user-detail.php';
    return;
}

/* ═══════════════════════════ HELPERS ══════════════════════════ */
if ( ! function_exists( 'ihur_meta' ) ) {
    function ihur_meta( $user_id, array $keys, $fallback = '' ) {
        foreach ( $keys as $key ) {
            $value = get_user_meta( $user_id, $key, true );
            if ( $value !== '' && $value !== null ) {
                return is_array($value)
                    ? implode(', ', array_map('sanitize_text_field', $value))
                    : (string) $value;
            }
        }
        return $fallback;
    }
}
if ( ! function_exists( 'ihur_val' ) ) {
    function ihur_val( $value, $fallback = 'Not provided' ) {
        $value = is_scalar($value) ? trim((string)$value) : '';
        return $value !== '' ? $value : $fallback;
    }
}
if ( ! function_exists( 'ihur_three' ) ) {
    function ihur_three( $value, $fallback = 'XXX' ) {
        $clean = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', (string)$value));
        if ($clean === '') $clean = $fallback;
        return str_pad(substr($clean, 0, 3), 3, 'X');
    }
}
if ( ! function_exists( 'ihur_uid' ) ) {
    function ihur_uid( $name, $town ) {
        $parts = preg_split('/[^a-zA-Z0-9]+/', (string)$name, -1, PREG_SPLIT_NO_EMPTY);
        $first = ! empty($parts[0]) ? $parts[0] : $name;
        return ihur_three($first) . ihur_three($town);
    }
}
if ( ! function_exists( 'ihur_saved_uid' ) ) {
    function ihur_saved_uid( $user_id, $name = '', $town = '' ) {
        $saved = get_user_meta( $user_id, 'ih_unique_id', true );
        if ( ! empty($saved) ) return (string) $saved;
        if ( function_exists('ih_generate_unique_id') ) {
            $city  = $town ?: get_user_meta($user_id, 'city', true);
            $uname = $name ?: ( ($u = get_userdata($user_id)) ? $u->display_name : '' );
            $new_id = ih_generate_unique_id($uname, $city);
        } else {
            $new_id = ihur_uid($name, $town);
        }
        update_user_meta($user_id, 'ih_unique_id', $new_id);
        return $new_id;
    }
}
if ( ! function_exists( 'ihur_completion' ) ) {
    function ihur_completion( array $u ) {
        $keys = [
            'businessRole','companyName','contactName','jobTitle',
            'address','townCity','postcode','officeNumber',
            'whatsappNumber','websiteUrl','email','confirmedEmail',
        ];
        $filled = 0;
        foreach ($keys as $k) {
            if ( ! empty($u[$k]) && $u[$k] !== 'Not provided' ) $filled++;
        }
        return (int) round(($filled / count($keys)) * 100);
    }
}
if ( ! function_exists( 'ihur_completion_tone' ) ) {
    function ihur_completion_tone( $pct ) {
        $pct = (int) $pct;
        if ( $pct >= 85 ) return 'is-green';
        if ( $pct >= 65 ) return 'is-amber';
        return 'is-rose';
    }
}
if ( ! function_exists( 'ihur_completion_tone_mobile' ) ) {
    function ihur_completion_tone_mobile( $pct ) {
        $pct = (int) $pct;
        if ( $pct >= 70 ) return 'is-green';
        if ( $pct >= 55 ) return 'is-amber';
        return 'is-rose';
    }
}
if ( ! function_exists( 'ihur_icon' ) ) {
    function ihur_icon( $name ) {
        $set = [
            'ban'     => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/></svg>',
            'send'    => '<svg viewBox="0 0 24 24"><path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4 20-7z"/></svg>',
            'edit'    => '<svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>',
            'user'    => '<svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>',
            'list'    => '<svg viewBox="0 0 24 24"><path d="m3 17 2 2 4-4M3 7l2 2 4-4M13 6h8M13 12h8M13 18h8"/></svg>',
            'more'    => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>',
            'columns' => '<svg viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18M15 3v18"/></svg>',
            'search'  => '<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>',
            'export'  => '<svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
            'listings'=> '<svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
            'close'   => '<svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
        ];
        return $set[$name] ?? '';
    }
}

/* ═══════════════════════ BUILD ROWS ═══════════════════════════ */
$rows        = ih_db_users();
$panel_users = [];
$cutoff_new  = strtotime( '-30 days' );

/* Per-user control data: listings owned + request activity (admin mediates all requests). */
global $wpdb;
$machine_counts = $wpdb->get_results( "SELECT owner_id, COUNT(*) AS n FROM {$wpdb->prefix}ih_machines GROUP BY owner_id", OBJECT_K );
$tool_counts    = $wpdb->get_results( "SELECT owner_id, COUNT(*) AS n FROM {$wpdb->prefix}ih_tools GROUP BY owner_id", OBJECT_K );
$request_counts = $wpdb->get_results( "SELECT user_id, COUNT(*) AS total, SUM(CASE WHEN LOWER(TRIM(status))='pending' THEN 1 ELSE 0 END) AS pending FROM {$wpdb->prefix}ih_requests GROUP BY user_id", OBJECT_K );

foreach ( $rows as $u ) {
    $uid     = (int)($u['id'] ?? 0);
    $wp_user = $uid ? get_userdata($uid) : null;

    $name  = ihur_val( $u['name']  ?? ($wp_user ? $wp_user->display_name : '') );
    $email = ihur_val( $u['email'] ?? ($wp_user ? $wp_user->user_email   : '') );

    $town = ihur_val(
        $u['location'] ?? ihur_meta($uid, ['city', 'ih_city', 'billing_city', 'location', 'ih_location']),
        'Not provided'
    );
    $phone = ihur_val(
        $u['phone'] ?? ihur_meta($uid, ['phone', 'office_number', 'ih_phone', 'billing_phone']),
        'Not provided'
    );
    $company = ihur_val(
        $u['company'] ?? ihur_meta($uid, ['company_name', 'ih_company', 'billing_company', 'company']),
        'Not provided'
    );
    $job = ihur_val(
        $u['job_title'] ?? ihur_meta($uid, ['job_title', 'ih_job_title']),
        'Not provided'
    );
    $address = ihur_val(
        $u['address'] ?? ihur_meta($uid, ['address', 'ih_address', 'billing_address_1']),
        'Not provided'
    );
    $postcode = ihur_val(
        $u['postcode'] ?? ihur_meta($uid, ['postcode', 'ih_postcode', 'billing_postcode']),
        'Not provided'
    );
    $website = ihur_val(
        $u['website'] ?? ihur_meta($uid, ['website', 'ih_website'],
            $wp_user ? $wp_user->user_url : ''),
        'Not provided'
    );
    $role = ihur_val(
        ihur_meta($uid, ['business_role', 'ih_business_role']),
        'Not provided'
    );
    $whatsapp = ihur_val(
        ihur_meta($uid, ['whatsapp', 'whatsapp_number', 'phone', 'ih_whatsapp']),
        $phone !== 'Not provided' ? $phone : 'Not provided'
    );

    $date = ihur_val($u['date'] ?? '', 'Not provided');
    if ( $date === 'Not provided' && $wp_user ) {
        $date = date_i18n('d/m/Y', strtotime($wp_user->user_registered));
    }

    $blocked    = ! empty($u['blocked']);
    $is_new     = $wp_user && strtotime( $wp_user->user_registered ) >= $cutoff_new;
    $detail_url = admin_url('admin.php?page=ih-users&view=' . $uid);
    $unique_id  = ihur_saved_uid($uid, $name, $town);

    $p = [
        'id'             => $uid,
        'name'           => $name,
        'uniqueId'       => $unique_id,
        'platformRef'    => 'USR-' . $uid,
        'email'          => $email,
        'confirmedEmail' => $email,
        'date'           => $date,
        'status'         => $blocked ? 'Blocked' : 'Active',
        'blocked'        => $blocked,
        'isNew'          => $is_new,
        'businessRole'   => $role,
        'companyName'    => $company,
        'contactName'    => $name,
        'jobTitle'       => $job,
        'address'        => $address,
        'townCity'       => $town,
        'postcode'       => $postcode,
        'officeNumber'   => $phone,
        'whatsappNumber' => $whatsapp,
        'websiteUrl'     => $website,
        'avatar'         => esc_url_raw($u['avatar'] ?? get_avatar_url($uid, ['size' => 80])),
        'detailUrl'      => esc_url_raw($detail_url),
        'listingsUrl'    => esc_url_raw($detail_url . '&tab=listings'),
        'messageUrl'     => esc_url_raw(admin_url('admin.php?page=ih-messages&user_id=' . $uid)),
        'termsAccepted'  => (bool) ihur_meta($uid, ['terms_accepted',  'ih_terms_accepted'],  true),
        'feesAccepted'   => (bool) ihur_meta($uid, ['fees_accepted',   'ih_fees_accepted'],   false),
        'online'         => false,
        'machines'       => isset($machine_counts[$uid]) ? (int) $machine_counts[$uid]->n : 0,
        'tools'          => isset($tool_counts[$uid])    ? (int) $tool_counts[$uid]->n    : 0,
        'requests'       => isset($request_counts[$uid]) ? (int) $request_counts[$uid]->total   : 0,
        'pendingReqs'    => isset($request_counts[$uid]) ? (int) $request_counts[$uid]->pending : 0,
    ];
    $p['completion'] = ihur_completion($p);
    $p['completionTone'] = ihur_completion_tone( $p['completion'] );
    $panel_users[]   = $p;
}

$kpi_total    = count( $panel_users );
$kpi_active   = count( array_filter( $panel_users, fn( $p ) => empty( $p['blocked'] ) ) );
$kpi_blocked  = $kpi_total - $kpi_active;
$kpi_new      = count( array_filter( $panel_users, fn( $p ) => ! empty( $p['isNew'] ) ) );

$ws_url = apply_filters(
    'ih_users_redesign_ws_url',
    get_option('ih_users_ws_url', get_option('insidehub_ws_url', ''))
);

if ( ! function_exists( 'ihur_menu' ) ) {
    function ihur_menu( array $p, $blocked ) {
        ob_start(); ?>
        <div class="ih-redesign-dropdown hidden" data-menu-for="<?php echo esc_attr($p['id']); ?>">
          <button type="button" class="ih-dropdown-item ih-block-user-btn <?php echo $blocked ? 'is-unblock' : 'is-block'; ?>"
                  data-uid="<?php echo esc_attr($p['id']); ?>"
                  data-blocked="<?php echo $blocked ? '1' : '0'; ?>">
            <?php echo ihur_icon('ban'); ?> <?php echo $blocked ? 'Unblock User' : 'Block User'; ?>
          </button>
          <a href="<?php echo esc_url($p['messageUrl']); ?>" class="ih-dropdown-item"><?php echo ihur_icon('send'); ?> Send Message</a>
          <a href="<?php echo esc_url($p['detailUrl']); ?>"  class="ih-dropdown-item"><?php echo ihur_icon('edit'); ?> Edit User Details</a>
          <a href="<?php echo esc_url($p['detailUrl']); ?>" class="ih-dropdown-item ih-view-record" data-user-row="<?php echo esc_attr($p['id']); ?>"><?php echo ihur_icon('user'); ?> View record</a>
          <a href="<?php echo esc_url($p['listingsUrl']); ?>" class="ih-dropdown-item"><?php echo ihur_icon('list'); ?> User Listings</a>
        </div>
        <?php return ob_get_clean();
    }
}

ob_start();
?>
<div class="ih-rd ih-admin">
<div class="ih-users-redesign ih-users-page" id="ihUsersRedesign">
  <div class="ih-users-layout">
    <section class="ih-users-main">
      <div class="ih-rd-head ih-users-redesign-header">
        <div>
          <p class="ih-eyebrow ih-users-eyebrow"><span class="dot" aria-hidden="true"></span><span class="ih-users-eyebrow-long">User database · control centre</span><span class="ih-users-eyebrow-short">USER DATABASE</span></p>
          <h2 class="ih-dash-title">Users</h2>
          <p class="ih-dash-sub ih-users-sub-long">Search, inspect and manage every user, listing and request — admin sees everything.</p>
          <p class="ih-dash-sub ih-users-sub-short">Inspect &amp; manage every user. Admin sees everything.</p>
        </div>
        <button type="button" class="ih-btn ih-btn-outline ih-users-export ih-users-desktop-only" id="ihUsersExportCsv"><?php echo ihur_icon('export'); ?> Export CSV</button>
      </div>

      <div class="ih-grid-stats ih-users-kpis ih-users-kpis--four">
        <div class="ih-stat is-blue ih-kpi ih-users-kpi">
          <div class="ih-stat-label"><span class="ih-stat-label-long">Total users</span><span class="ih-stat-label-short">Total</span></div>
          <div class="ih-stat-value"><?php echo (int) $kpi_total; ?></div>
          <div class="ih-stat-sublabel">All registered</div>
        </div>
        <div class="ih-stat is-green ih-kpi ih-users-kpi">
          <div class="ih-stat-label"><span class="ih-stat-label-long">Active</span><span class="ih-stat-label-short">Active</span></div>
          <div class="ih-stat-value"><?php echo (int) $kpi_active; ?></div>
          <div class="ih-stat-sublabel">Unblocked</div>
        </div>
        <div class="ih-stat is-violet ih-kpi ih-users-kpi">
          <div class="ih-stat-label"><span class="ih-stat-label-long">New · 30 days</span><span class="ih-stat-label-short">New 30d</span></div>
          <div class="ih-stat-value"><?php echo (int) $kpi_new; ?></div>
          <div class="ih-stat-sublabel">Recently joined</div>
        </div>
        <div class="ih-stat is-rose ih-kpi ih-users-kpi">
          <div class="ih-stat-label"><span class="ih-stat-label-long">Blocked</span><span class="ih-stat-label-short">Blocked</span></div>
          <div class="ih-stat-value"><?php echo esc_html( sprintf( '%02d', (int) $kpi_blocked ) ); ?></div>
          <div class="ih-stat-sublabel">Restricted</div>
        </div>
      </div>

      <div class="ih-users-list-panel">
        <div class="ih-filters ih-users-controls-bar">
          <div class="ih-tabs ih-users-tabs" id="userTabGroup" role="tablist" aria-label="Filter users">
            <button class="ih-tab active" type="button" role="tab" aria-selected="true" data-tab="All">All</button>
            <button class="ih-tab" type="button" role="tab" aria-selected="false" data-tab="Active">Active</button>
            <button class="ih-tab" type="button" role="tab" aria-selected="false" data-tab="Blocked">Blocked</button>
            <button class="ih-tab" type="button" role="tab" aria-selected="false" data-tab="New">New</button>
          </div>
          <div class="ih-search-wrap ih-users-search ih-users-mobile-search">
            <?php echo ihur_icon('search'); ?>
            <input type="search" id="userSearch" placeholder="Search name, UID, email…" class="ih-search-input" aria-label="Search users">
          </div>
          <button type="button" class="ih-users-icon-btn ih-users-desktop-only" id="ihUsersColumnButton" aria-label="Adjust columns" aria-expanded="false" aria-controls="ihUsersColumnPanel"><?php echo ihur_icon('columns'); ?></button>
        </div>

        <div class="ih-users-column-panel hidden ih-users-desktop-only" id="ihUsersColumnPanel" aria-hidden="true"></div>

        <div class="ih-section ih-users-card ih-users-desktop-table-wrap">
        <div class="ih-table-wrap">
          <table class="ih-table ih-users-table" id="usersTable">
            <thead>
              <tr>
                <th data-col="select" class="ih-users-th-select"><input type="checkbox" id="ihUsersSelectAll" aria-label="Select all"></th>
                <th data-col="user">User</th>
                <th data-col="company">Company / role</th>
                <th data-col="email">Email</th>
                <th data-col="location">Location</th>
                <th data-col="listings">Listings</th>
                <th data-col="requests">Requests</th>
                <th data-col="completion">Completion</th>
                <th data-col="status">Status</th>
                <th data-col="actions" class="ih-users-th-actions">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($panel_users as $i => $p):
                $blocked = ! empty( $p['blocked'] );
                $search  = strtolower(implode(' ', [
                    $p['name'], $p['uniqueId'], $p['platformRef'], $p['email'],
                    $p['companyName'], $p['businessRole'], $p['townCity'], $p['officeNumber'],
                ]));
                $list_total = (int) $p['machines'] + (int) $p['tools'];
              ?>
              <tr class="ih-user-row"
                  data-user-row="<?php echo esc_attr( $p['id'] ); ?>"
                  data-blocked="<?php echo $blocked ? 'true' : 'false'; ?>"
                  data-new="<?php echo ! empty( $p['isNew'] ) ? 'true' : 'false'; ?>"
                  data-search="<?php echo esc_attr($search); ?>"
                  data-user="<?php echo esc_attr(wp_json_encode($p)); ?>">

                <td data-col="select" data-label="Select">
                  <input type="checkbox" class="ih-user-select" value="<?php echo esc_attr( $p['id'] ); ?>" aria-label="Select user">
                </td>

                <td data-col="user" data-label="User">
                  <div class="ih-user-cell ih-user-name-cell">
                    <?php echo function_exists( 'ih_user_avatar_html' ) ? ih_user_avatar_html( $p['id'], $p['name'], 'sm' ) : '<img src="' . esc_url( $p['avatar'] ) . '" class="ih-tbl-avatar" alt="">'; ?>
                    <div class="ih-user-name-stack">
                      <span class="ih-user-name"><?php echo esc_html($p['name']); ?></span>
                      <span class="ih-req-uid"><?php echo esc_html( function_exists( 'ih_user_uid_label' ) ? ih_user_uid_label( $p['uniqueId'], $p['id'] ) : strtoupper( $p['uniqueId'] ) . ' · ' . $p['platformRef'] ); ?></span>
                    </div>
                  </div>
                </td>

                <td data-col="company" data-label="Company">
                  <div class="ih-users-company-cell">
                    <span class="ih-user-name"><?php echo esc_html( $p['companyName'] !== 'Not provided' ? $p['companyName'] : '—' ); ?></span>
                    <?php if ( $p['businessRole'] !== 'Not provided' ) : ?>
                      <span class="ih-spec-pill"><?php echo esc_html( $p['businessRole'] ); ?></span>
                    <?php endif; ?>
                  </div>
                </td>

                <td data-col="email" data-label="Email" class="muted ih-users-email-cell">
                  <span class="ih-users-email-text"><?php echo esc_html( $p['email'] ); ?></span>
                  <?php if ( $p['email'] && $p['email'] !== 'Not provided' ) : ?><span class="ih-email-ok" title="Email on file" aria-label="Verified email">✓</span><?php endif; ?>
                </td>

                <td data-col="location" data-label="Location"><?php echo esc_html( $p['townCity'] ); ?></td>

                <td data-col="listings" data-label="Listings">
                  <span class="ih-users-metric" title="Machines + tools owned">
                    <?php echo ihur_icon('listings'); ?>
                    <strong><?php echo (int) $list_total; ?></strong>
                  </span>
                </td>

                <td data-col="requests" data-label="Requests">
                  <?php if ( $p['pendingReqs'] > 0 ) : ?>
                    <span class="ih-users-count-chip is-pending">
                      <strong><?php echo (int) $p['requests']; ?></strong>
                      <em><?php echo (int) $p['pendingReqs']; ?> pend</em>
                    </span>
                  <?php elseif ( $p['requests'] > 0 ) : ?>
                    <span class="ih-users-count-chip"><strong><?php echo (int) $p['requests']; ?></strong></span>
                  <?php else : ?>
                    <span class="ih-users-count-chip is-zero">&mdash;</span>
                  <?php endif; ?>
                </td>

                <td data-col="completion" data-label="Completion">
                  <div class="ih-users-progress ih-users-progress--stacked">
                    <span class="ih-users-progress-value <?php echo esc_attr( $p['completionTone'] ); ?>"><?php echo esc_html($p['completion']); ?>%</span>
                    <div class="ih-progress-bar">
                      <span class="ih-progress-fill <?php echo esc_attr( $p['completionTone'] ); ?>" style="width:<?php echo esc_attr($p['completion']); ?>%"></span>
                    </div>
                  </div>
                </td>

                <td data-col="status" data-label="Status"><span class="ih-users-status-pill is-<?php echo $blocked ? 'blocked' : 'active'; ?>"><?php echo esc_html( $p['status'] ); ?></span></td>
                <td data-col="actions" data-label="Actions" class="ih-action-cell">
                  <div class="ih-row-menu">
                    <button class="ih-users-action-btn" type="button" data-row-menu="<?php echo esc_attr($p['id']); ?>" aria-label="Row menu">
                      <?php echo ihur_icon('more'); ?>
                    </button>
                    <?php echo ihur_menu($p, $blocked); ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        </div>

        <div class="ih-users-mobile-results-row" aria-live="polite">
          <span class="ih-users-results-count" id="ihUsersMobileResultsCount"><?php echo (int) $kpi_total; ?> users</span>
        </div>

        <div class="ih-users-mobile-cards" id="ihUsersMobileCards" aria-label="User cards">
          <?php foreach ( $panel_users as $p ) :
            $blocked      = ! empty( $p['blocked'] );
            $search       = strtolower( implode( ' ', [
                $p['name'], $p['uniqueId'], $p['platformRef'], $p['email'],
                $p['companyName'], $p['businessRole'], $p['townCity'], $p['officeNumber'],
            ] ) );
            $list_total   = (int) $p['machines'] + (int) $p['tools'];
            $mobile_tone  = ihur_completion_tone_mobile( $p['completion'] );
            $company_role = $p['companyName'] !== 'Not provided' ? $p['companyName'] : '';
            if ( $p['businessRole'] !== 'Not provided' ) {
                $company_role .= ( $company_role ? ' · ' : '' ) . $p['businessRole'];
            }
            $uid_label = function_exists( 'ih_user_uid_label' )
                ? ih_user_uid_label( $p['uniqueId'], $p['id'] )
                : strtoupper( $p['uniqueId'] ) . ' · ' . $p['platformRef'];
          ?>
          <article class="ih-users-mobile-card ih-user-mobile-row"
                   data-user-row="<?php echo esc_attr( $p['id'] ); ?>"
                   data-blocked="<?php echo $blocked ? 'true' : 'false'; ?>"
                   data-new="<?php echo ! empty( $p['isNew'] ) ? 'true' : 'false'; ?>"
                   data-search="<?php echo esc_attr( $search ); ?>"
                   data-user="<?php echo esc_attr( wp_json_encode( $p ) ); ?>">
            <div class="ih-users-mobile-card-head">
              <?php echo function_exists( 'ih_user_avatar_html' ) ? ih_user_avatar_html( $p['id'], $p['name'], 'sm', 'ih-u-av-md' ) : '<img src="' . esc_url( $p['avatar'] ) . '" class="ih-tbl-avatar" alt="">'; ?>
              <div class="ih-users-mobile-card-ident">
                <span class="ih-users-mobile-name"><?php echo esc_html( $p['name'] ); ?></span>
                <span class="ih-users-mobile-uid"><?php echo esc_html( $uid_label ); ?></span>
              </div>
              <span class="ih-users-status-pill is-<?php echo $blocked ? 'blocked' : 'active'; ?>"><?php echo esc_html( $p['status'] ); ?></span>
            </div>
            <div class="ih-users-mobile-card-meta">
              <?php if ( $company_role ) : ?>
                <span class="ih-users-mobile-role-pill"><?php echo esc_html( $company_role ); ?></span>
              <?php endif; ?>
              <span class="ih-users-mobile-email">
                <?php echo esc_html( $p['email'] ); ?>
                <?php if ( $p['email'] && $p['email'] !== 'Not provided' ) : ?>
                  <svg class="ih-users-email-shield" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="m9 12 2 2 4-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <?php endif; ?>
              </span>
            </div>
            <div class="ih-users-mobile-card-stats">
              <span class="ih-users-mobile-stat is-listings"><strong><?php echo (int) $list_total; ?></strong> listings</span>
              <span class="ih-users-mobile-stat is-requests"><strong><?php echo (int) $p['requests']; ?></strong> requests</span>
              <div class="ih-users-mobile-profile">
                <div class="ih-users-mobile-profile-top">
                  <span>Profile</span>
                  <span class="ih-users-progress-value <?php echo esc_attr( $mobile_tone ); ?>"><?php echo esc_html( $p['completion'] ); ?>%</span>
                </div>
                <div class="ih-progress-bar ih-users-mobile-progress-bar">
                  <span class="ih-progress-fill <?php echo esc_attr( $mobile_tone ); ?>" style="width:<?php echo esc_attr( $p['completion'] ); ?>%"></span>
                </div>
              </div>
            </div>
            <div class="ih-users-mobile-card-actions">
              <button type="button" class="ih-users-mobile-view-btn ih-view-record" data-user-row="<?php echo esc_attr( $p['id'] ); ?>">View record</button>
              <a href="<?php echo esc_url( $p['messageUrl'] ); ?>" class="ih-users-mobile-msg-btn" aria-label="Send message to <?php echo esc_attr( $p['name'] ); ?>"><?php echo ihur_icon('send'); ?></a>
            </div>
          </article>
          <?php endforeach; ?>
        </div>

        <footer class="ih-users-page-footer" aria-label="Users page footer">
          <span class="ih-users-footer-meta" id="ihUsersFooterMeta">USERS · <?php echo (int) $kpi_total; ?> TOTAL</span>
          <span class="ih-users-footer-status"><span class="dot" aria-hidden="true"></span>LIVE</span>
        </footer>
      </div>
    </section>
  </div>
</div>
</div><!-- /.ih-rd.ih-admin -->

<aside class="ih-u-drawer" id="ihUserDrawer" role="dialog" aria-modal="true" aria-labelledby="ihUserDrawerTitle" aria-hidden="true">
  <div class="ih-u-drawer-bar">
    <p id="ihUserDrawerTitle" class="ih-u-drawer-label">User record · full profile</p>
    <button type="button" class="ih-u-close" id="ihUserDrawerClose" aria-label="Close profile drawer"><?php echo ihur_icon('close'); ?></button>
  </div>
  <div class="body" tabindex="-1"><p class="ih-u-empty">Select a user to view the full record.</p></div>
</aside>
<div class="ih-u-scrim" id="ihUserScrim" aria-hidden="true"></div>

<?php
$content = ob_get_clean();
$title   = 'Users';
include IH_DIR . 'pages/layout.php';