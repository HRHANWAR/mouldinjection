<?php
/**
 * Insight Hub — Requests Control Centre (admin)
 * Full management console for every contact/listing request on the platform.
 * URL: admin.php?page=ih-requests
 */
defined( 'ABSPATH' ) || exit;

global $wpdb;
$p = $wpdb->prefix;

/* ═══════════ DATA ═══════════ */
$counts = [
    'total'     => 0,
    'pending'   => 0,
    'approved'  => 0,
    'rejected'  => 0,
    'completed' => 0,
    'month'     => 0,
];
$status_rows = $wpdb->get_results(
    "SELECT LOWER(TRIM(status)) AS status, COUNT(*) AS cnt FROM {$p}ih_requests GROUP BY LOWER(TRIM(status))",
    ARRAY_A
) ?: [];
foreach ( $status_rows as $row ) {
    $key = strtolower( trim( (string) ( $row['status'] ?? '' ) ) );
    $cnt = (int) ( $row['cnt'] ?? 0 );
    $counts['total'] += $cnt;
    if ( isset( $counts[ $key ] ) ) {
        $counts[ $key ] = $cnt;
    }
}
$counts['month'] = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$p}ih_requests WHERE YEAR(request_date)=%d AND MONTH(request_date)=%d",
    (int) current_time( 'Y' ), (int) current_time( 'n' )
) );

$rows = $wpdb->get_results(
    "SELECT r.id, r.user_id, r.listing_id, r.listing_type, r.request_date, r.status,
            m.title AS machine_title, t.title AS tool_title
     FROM {$p}ih_requests r
     LEFT JOIN {$p}ih_machines m ON (r.listing_type IN ('machine','machine_contact') AND m.id=r.listing_id)
     LEFT JOIN {$p}ih_tools    t ON (r.listing_type IN ('tool','tool_contact')    AND t.id=r.listing_id)
     ORDER BY r.id DESC
     LIMIT 400",
    ARRAY_A
) ?: [];

/* Enrich rows with user data once per user */
$user_cache = [];
foreach ( $rows as &$r ) {
    $uid = (int) $r['user_id'];
    if ( ! isset( $user_cache[ $uid ] ) ) {
        $wp_user = get_userdata( $uid );
        $name    = $wp_user ? $wp_user->display_name : ( 'User #' . $uid );
        $avatar  = get_user_meta( $uid, 'ih_profile_image', true );
        if ( ! $avatar ) {
            $avatar = 'https://ui-avatars.com/api/?name=' . rawurlencode( $name ) . '&size=72&background=1f3d2e&color=c8e88e&bold=true&rounded=true';
        }
        $user_cache[ $uid ] = [
            'name'   => $name,
            'uid'    => get_user_meta( $uid, 'ih_unique_id', true ) ?: ( 'UID-' . $uid ),
            'email'  => $wp_user ? $wp_user->user_email : '',
            'phone'  => get_user_meta( $uid, 'phone', true ) ?: get_user_meta( $uid, 'ih_phone', true ),
            'avatar' => $avatar,
        ];
    }
    $r['user']    = $user_cache[ $uid ];
    $r['listing'] = $r['listing_type'] === 'tool'
        ? ( $r['tool_title'] ?: ( '#' . $r['listing_id'] ) )
        : ( $r['machine_title'] ?: ( '#' . $r['listing_id'] ) );
    $r['listing_ref'] = ih_request_listing_ref( $r['listing_type'], $r['listing_id'] );
    $r['intent']      = ih_request_intent_meta( $r );
    if ( $r['intent']['key'] === 'profile' ) {
        $r['listing'] = 'Protected user profile/contact';
    } elseif ( $r['intent']['key'] === 'listing_approval' ) {
        $r['listing'] = $r['listing'] ?: ( ( $r['listing_type'] === 'tool' || $r['listing_type'] === 'tool_contact' ) ? 'Tool listing' : 'Machine listing' );
    } elseif ( $r['intent']['key'] === 'admin' ) {
        $r['listing'] = 'Admin conversation';
    }
    $ts = $r['request_date'] ? strtotime( $r['request_date'] ) : 0;
    $r['date_disp'] = $ts ? date_i18n( 'd M Y', $ts ) : '—';
    $r['wait_days'] = $ts ? max( 0, (int) floor( ( current_time( 'timestamp' ) - $ts ) / DAY_IN_SECONDS ) ) : 0;
    $r['status_lc'] = strtolower( trim( $r['status'] ) ) ?: 'pending';
}
unset( $r );

if ( ! function_exists( 'ih_req_kpi_num' ) ) {
    function ih_req_kpi_num( $n ) {
        $n = (int) $n;
        return $n < 10 ? sprintf( '%02d', $n ) : (string) $n;
    }
}

$kpis = [
    [ 'key' => 'all',      'label' => 'Total Requests', 'label_short' => 'Total',     'count' => $counts['total'],    'color' => '#3b82f5' ],
    [ 'key' => 'pending',  'label' => 'Pending',        'label_short' => 'Pending',   'count' => $counts['pending'],  'color' => '#f59e0a' ],
    [ 'key' => 'approved', 'label' => 'Approved',       'label_short' => 'Approved',  'count' => $counts['approved'], 'color' => '#21c45e' ],
    [ 'key' => 'rejected', 'label' => 'Rejected',       'label_short' => 'Rejected',  'count' => $counts['rejected'], 'color' => '#f04545' ],
    [ 'key' => 'month',    'label' => 'This Month',     'label_short' => 'This month', 'count' => $counts['month'],    'color' => '#a88cfa', 'desktop_only' => true ],
];

if ( ! function_exists( 'ih_req_mobile_avatar' ) ) {
    /** Initials-only avatar for mobile request cards (Figma 120-1344). */
    function ih_req_mobile_avatar( $user_id, $name ) {
        $initials = function_exists( 'ih_user_initials' ) ? ih_user_initials( $name ) : '?';
        $color    = function_exists( 'ih_user_avatar_color' ) ? ih_user_avatar_color( $user_id ?: $name ) : '#634feb';
        return '<span class="ih-req-mobile-avatar ih-u-avatar ih-u-av-sm" style="--ih-av-color:' . esc_attr( $color ) . '">'
            . '<span class="ih-u-avatar-fallback">' . esc_html( $initials ) . '</span></span>';
    }
}

ob_start();
?>
<div class="ih-rd ih-admin">
<div class="ih-req-page" id="ihRequestsRedesign">

  <div class="ih-rd-head ih-req-header">
    <div>
      <p class="ih-eyebrow ih-req-eyebrow"><span class="dot" aria-hidden="true"></span><span class="ih-req-eyebrow-long">Requests · control centre · live</span><span class="ih-req-eyebrow-short">Requests · live</span></p>
      <h2 class="ih-dash-title">Requests Control Centre</h2>
      <p class="ih-dash-sub ih-req-sub-long">Every user request on the platform — approve or reject access here. Admin controls all data.</p>
      <p class="ih-dash-sub ih-req-sub-short">Approve or reject every request. Admin controls all data.</p>
    </div>
  </div>

  <div class="ih-req-kpis ih-req-kpis--four" id="ihReqKpis" aria-label="Request statistics">
    <?php foreach ( $kpis as $k ) :
        $kpi_classes = 'ih-req-kpi';
        if ( ! empty( $k['desktop_only'] ) ) {
            $kpi_classes .= ' ih-req-desktop-only';
        }
    ?>
    <button type="button" class="<?php echo esc_attr( $kpi_classes ); ?>" data-kpi="<?php echo esc_attr( $k['key'] ); ?>" style="--kpi:<?php echo esc_attr( $k['color'] ); ?>;">
      <span class="ih-req-kpi-accent" aria-hidden="true"></span>
      <span class="ih-req-kpi-body">
        <strong class="ih-req-kpi-num" data-count="<?php echo (int) $k['count']; ?>"><?php echo esc_html( ih_req_kpi_num( $k['count'] ) ); ?></strong>
        <span class="ih-req-kpi-label ih-req-kpi-label-long"><?php echo esc_html( strtoupper( $k['label'] ) ); ?></span>
        <span class="ih-req-kpi-label ih-req-kpi-label-short"><?php echo esc_html( strtoupper( $k['label_short'] ?? $k['label'] ) ); ?></span>
      </span>
    </button>
    <?php endforeach; ?>
  </div>

  <div class="ih-card ih-req-card">
    <div class="ih-req-toolbar">
      <div class="ih-req-tabs" id="ihReqTabs">
        <button class="ih-req-tab active" data-status="all">All</button>
        <button class="ih-req-tab" data-status="pending">Pending</button>
        <button class="ih-req-tab" data-status="approved">Approved</button>
        <button class="ih-req-tab" data-status="rejected">Rejected</button>
      </div>
      <select id="ihReqType" class="ih-req-select ih-req-desktop-only">
        <option value="">All types</option>
        <option value="machine">Machines</option>
        <option value="tool">Tools</option>
      </select>
      <div class="ih-req-search-wrap ih-req-mobile-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="search" id="ihReqSearch" placeholder="Search name, ID, email, listing…" class="ih-req-search-input" aria-label="Search requests">
      </div>
    </div>

    <div class="ih-req-table-wrap ih-req-desktop-table-wrap">
      <table class="ih-req-table" id="ihReqTable">
        <colgroup>
          <col class="ih-req-col-requester">
          <col class="ih-req-col-intent">
          <col class="ih-req-col-ids">
          <col class="ih-req-col-listing">
          <col class="ih-req-col-date">
          <col class="ih-req-col-wait">
          <col class="ih-req-col-status">
          <col class="ih-req-col-actions">
        </colgroup>
        <thead>
          <tr>
            <th>Requester</th>
            <th>Request intent</th>
            <th>IDs</th>
            <th>Listing / target</th>
            <th>Date</th>
            <th>Waiting</th>
            <th class="ih-req-th-status">Status</th>
            <th class="ih-req-th-actions right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $rows as $r ) :
              $u = $r['user'];
              $request_id      = (int) $r['id'];
              $user_id         = (int) $r['user_id'];
              $profile_url     = admin_url( 'admin.php?page=ih-users&view=' . $user_id );
              $message_url     = admin_url( 'admin.php?page=ih-messages&user_id=' . $user_id );
              $listing_url     = ih_request_listing_detail_url( $r['listing_type'], $r['listing_id'] );
              $email_url       = ! empty( $u['email'] ) ? 'https://mail.google.com/mail/?view=cm&fs=1&to=' . rawurlencode( sanitize_email( $u['email'] ) ) : '';
              $phone_href      = preg_replace( '/[^\d\+]/', '', (string) ( $u['phone'] ?? '' ) );
              $wa_number       = preg_replace( '/\D/', '', (string) ( $u['phone'] ?? '' ) );
          ?>
          <tr data-status="<?php echo esc_attr( $r['status_lc'] ); ?>"
              data-type="<?php echo esc_attr( $r['listing_type'] ); ?>"
              data-id="<?php echo $request_id; ?>"
              data-search="<?php echo esc_attr( strtolower( $u['name'] . ' ' . $u['uid'] . ' ' . $u['email'] . ' ' . $r['intent']['label'] . ' ' . $r['intent']['help'] . ' ' . $r['listing_ref'] . ' ' . $r['listing'] . ' #' . $request_id ) ); ?>">
            <td data-label="Requester">
              <a class="ih-req-who ih-req-profile-link" href="<?php echo esc_url( $profile_url ); ?>" title="Open user profile">
                <img class="ih-req-avatar" src="<?php echo esc_url( $u['avatar'] ); ?>" alt="" loading="lazy">
                <div class="ih-req-who-text">
                  <span class="ih-req-name"><?php echo esc_html( $u['name'] ); ?></span>
                  <span class="ih-req-uid"><?php echo esc_html( $u['uid'] ); ?> · REQ-<?php echo $request_id; ?></span>
                </div>
              </a>
            </td>
            <td class="ih-req-intent-cell" data-label="Request Intent">
              <div class="ih-req-cell-stack">
                <span class="ih-req-type ih-req-type-<?php echo esc_attr( $r['intent']['key'] ); ?>"><?php echo esc_html( $r['intent']['label'] ); ?></span>
                <small><?php echo esc_html( $r['intent']['help'] ); ?></small>
              </div>
            </td>
            <td class="ih-req-id-cell" data-label="IDs">
              <div class="ih-req-cell-stack">
                <?php if ( $listing_url ) : ?>
                  <a class="ih-id-chip ih-id-chip-link is-<?php echo esc_attr( ( $r['listing_type'] ?? '' ) === 'tool' ? 'tool' : 'machine' ); ?>" href="<?php echo esc_url( $listing_url ); ?>" title="Open listing"><?php echo esc_html( $r['listing_ref'] ); ?></a>
                <?php else : ?>
                  <strong class="ih-id-chip is-<?php echo esc_attr( ( $r['listing_type'] ?? '' ) === 'tool' ? 'tool' : 'machine' ); ?>"><?php echo esc_html( $r['listing_ref'] ); ?></strong>
                <?php endif; ?>
                <span class="ih-req-id-row"><i class="ih-id-chip is-user">USER-<?php echo $user_id; ?></i> <i class="ih-id-chip is-req">REQ-<?php echo $request_id; ?></i></span>
              </div>
            </td>
            <td class="ih-req-listing" data-label="Listing / Target" title="<?php echo esc_attr( $r['listing'] ); ?>">
              <?php if ( $listing_url ) : ?>
                <a class="ih-req-listing-link" href="<?php echo esc_url( $listing_url ); ?>"><?php echo esc_html( $r['listing'] ); ?></a>
              <?php else : ?>
                <?php echo esc_html( $r['listing'] ); ?>
              <?php endif; ?>
            </td>
            <td class="ih-req-date" data-label="Date"><?php echo esc_html( $r['date_disp'] ); ?></td>
            <td class="ih-req-wait" data-label="Waiting"><?php echo $r['status_lc'] === 'pending' ? (int) $r['wait_days'] . 'd' : '—'; ?></td>
            <td class="ih-req-status-cell" data-label="Status"><span class="ih-req-chip is-<?php echo esc_attr( $r['status_lc'] ); ?>"><?php echo esc_html( ucfirst( $r['status_lc'] ) ); ?></span></td>
            <td class="ih-req-actions-cell" data-label="Actions">
              <div class="ih-req-actions" aria-label="Request actions">
                <div class="ih-req-action-group is-status" aria-label="Request decision actions">
                  <button type="button" class="ih-req-btn is-approve" onclick="ihReqStatus(this,'approved')">Approve</button>
                  <button type="button" class="ih-req-btn is-reject" onclick="ihReqStatus(this,'rejected')">Reject</button>
                </div>
                <div class="ih-req-action-group is-open" aria-label="Open related records">
                  <a class="ih-req-btn is-profile" href="<?php echo esc_url( $profile_url ); ?>">Open profile</a>
                  <a class="ih-req-btn is-msg" href="<?php echo esc_url( $message_url ); ?>">Message</a>
                  <?php if ( $listing_url ) : ?>
                    <a class="ih-req-btn is-listing" href="<?php echo esc_url( $listing_url ); ?>">View listing</a>
                  <?php endif; ?>
                </div>
                <div class="ih-req-action-group is-contact" aria-label="Contact actions">
                  <?php if ( $email_url ) : ?>
                    <a class="ih-req-btn is-email" href="<?php echo esc_url( $email_url ); ?>" target="_blank" rel="noopener">Email (Gmail)</a>
                  <?php endif; ?>
                  <?php if ( $phone_href ) : ?>
                    <a class="ih-req-btn is-call" href="<?php echo esc_url( 'tel:' . $phone_href ); ?>">Call</a>
                    <a class="ih-req-btn is-sms" href="<?php echo esc_url( 'sms:' . $phone_href ); ?>">SMS</a>
                  <?php endif; ?>
                  <?php if ( strlen( $wa_number ) >= 7 ) : ?>
                    <a class="ih-req-btn is-wa" href="<?php echo esc_url( 'https://wa.me/' . $wa_number ); ?>" target="_blank" rel="noopener">WhatsApp</a>
                  <?php endif; ?>
                  <button type="button" class="ih-req-btn is-del" onclick="ihReqDelete(this)">Delete request</button>
                </div>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="ih-req-empty" id="ihReqEmpty" <?php echo $rows ? 'hidden' : ''; ?>>
        <?php echo $rows ? 'No requests match the current filters.' : 'No requests yet — they will appear here as soon as users submit them.'; ?>
      </div>
    </div>

    <div class="ih-req-mobile-results-row" aria-live="polite">
      <span class="ih-req-results-count" id="ihReqMobileResultsCount"><?php echo (int) $counts['total']; ?> requests</span>
    </div>

    <div class="ih-req-mobile-cards" id="ihReqMobileCards" aria-label="Request cards">
      <?php foreach ( $rows as $r ) :
          $u               = $r['user'];
          $request_id      = (int) $r['id'];
          $user_id         = (int) $r['user_id'];
          $profile_url     = admin_url( 'admin.php?page=ih-users&view=' . $user_id );
          $message_url     = admin_url( 'admin.php?page=ih-messages&user_id=' . $user_id );
          $listing_url     = ih_request_listing_detail_url( $r['listing_type'], $r['listing_id'] );
          $email_url       = ! empty( $u['email'] ) ? 'https://mail.google.com/mail/?view=cm&fs=1&to=' . rawurlencode( sanitize_email( $u['email'] ) ) : '';
          $phone_href      = preg_replace( '/[^\d\+]/', '', (string) ( $u['phone'] ?? '' ) );
          $wa_number       = preg_replace( '/\D/', '', (string) ( $u['phone'] ?? '' ) );
          $search_blob     = strtolower( $u['name'] . ' ' . $u['uid'] . ' ' . $u['email'] . ' ' . $r['intent']['label'] . ' ' . $r['intent']['help'] . ' ' . $r['listing_ref'] . ' ' . $r['listing'] . ' #' . $request_id );
      ?>
      <article class="ih-req-mobile-card"
               data-status="<?php echo esc_attr( $r['status_lc'] ); ?>"
               data-type="<?php echo esc_attr( $r['listing_type'] ); ?>"
               data-id="<?php echo $request_id; ?>"
               data-search="<?php echo esc_attr( $search_blob ); ?>">
        <div class="ih-req-mobile-card-head">
          <?php echo ih_req_mobile_avatar( $user_id, $u['name'] ); ?>
          <div class="ih-req-mobile-ident">
            <span class="ih-req-mobile-name"><?php echo esc_html( $u['name'] ); ?></span>
            <span class="ih-req-mobile-uid"><?php echo esc_html( $u['uid'] ); ?> · REQ-<?php echo $request_id; ?></span>
          </div>
          <span class="ih-req-chip is-<?php echo esc_attr( $r['status_lc'] ); ?>"><?php echo esc_html( ucfirst( $r['status_lc'] ) ); ?></span>
        </div>
        <span class="ih-req-type ih-req-type-<?php echo esc_attr( $r['intent']['key'] ); ?> ih-req-mobile-intent"><?php echo esc_html( $r['intent']['label'] ); ?></span>
        <div class="ih-req-mobile-meta-pills">
          <?php if ( $listing_url ) : ?>
            <a class="ih-id-chip ih-id-chip-link is-<?php echo esc_attr( ( $r['listing_type'] ?? '' ) === 'tool' ? 'tool' : 'machine' ); ?>" href="<?php echo esc_url( $listing_url ); ?>"><?php echo esc_html( $r['listing_ref'] ); ?></a>
          <?php else : ?>
            <strong class="ih-id-chip is-<?php echo esc_attr( ( $r['listing_type'] ?? '' ) === 'tool' ? 'tool' : 'machine' ); ?>"><?php echo esc_html( $r['listing_ref'] ); ?></strong>
          <?php endif; ?>
          <span class="ih-id-chip is-user">USER-<?php echo $user_id; ?></span>
          <span class="ih-id-chip is-req">REQ-<?php echo $request_id; ?></span>
        </div>
        <div class="ih-req-mobile-listing-row">
          <?php if ( $listing_url ) : ?>
            <a class="ih-req-listing-link" href="<?php echo esc_url( $listing_url ); ?>"><?php echo esc_html( $r['listing'] ); ?></a>
          <?php else : ?>
            <span class="ih-req-mobile-listing-text"><?php echo esc_html( $r['listing'] ); ?></span>
          <?php endif; ?>
          <span class="ih-req-mobile-date"><?php echo esc_html( $r['date_disp'] ); ?></span>
        </div>
        <div class="ih-req-actions ih-req-mobile-actions" aria-label="Request actions">
          <div class="ih-req-action-group is-status" aria-label="Request decision actions">
            <button type="button" class="ih-req-btn is-approve" onclick="ihReqStatus(this,'approved')">Approve</button>
            <button type="button" class="ih-req-btn is-reject" onclick="ihReqStatus(this,'rejected')">Reject</button>
          </div>
          <div class="ih-req-action-group is-open" aria-label="Open related records">
            <a class="ih-req-btn is-profile" href="<?php echo esc_url( $profile_url ); ?>">Open profile</a>
            <a class="ih-req-btn is-msg" href="<?php echo esc_url( $message_url ); ?>">Message</a>
            <?php if ( $listing_url ) : ?>
              <a class="ih-req-btn is-listing" href="<?php echo esc_url( $listing_url ); ?>">View listing</a>
            <?php endif; ?>
          </div>
          <div class="ih-req-action-group is-contact" aria-label="Contact actions">
            <?php if ( $email_url ) : ?>
              <a class="ih-req-btn is-email" href="<?php echo esc_url( $email_url ); ?>" target="_blank" rel="noopener">Email (Gmail)</a>
            <?php endif; ?>
            <?php if ( $phone_href ) : ?>
              <a class="ih-req-btn is-call" href="<?php echo esc_url( 'tel:' . $phone_href ); ?>">Call</a>
              <a class="ih-req-btn is-sms" href="<?php echo esc_url( 'sms:' . $phone_href ); ?>">SMS</a>
            <?php endif; ?>
            <?php if ( strlen( $wa_number ) >= 7 ) : ?>
              <a class="ih-req-btn is-wa" href="<?php echo esc_url( 'https://wa.me/' . $wa_number ); ?>" target="_blank" rel="noopener">WhatsApp</a>
            <?php endif; ?>
            <button type="button" class="ih-req-btn is-del" onclick="ihReqDelete(this)">Delete request</button>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>

    <div class="ih-req-empty ih-req-mobile-empty" id="ihReqMobileEmpty" <?php echo $rows ? 'hidden' : ''; ?>>
      <?php echo $rows ? 'No requests match the current filters.' : 'No requests yet — they will appear here as soon as users submit them.'; ?>
    </div>
  </div>

  <footer class="ih-footer ih-req-footer ih-req-footer-desktop" aria-label="Requests page footer">
    <span>Admin control · requests · v2.0 · © <?php echo esc_html( date( 'Y' ) ); ?></span>
    <span><span class="dot" aria-hidden="true"></span>System status · operational</span>
  </footer>

  <footer class="ih-req-mobile-footer" aria-label="Requests page footer">
    <span class="ih-req-footer-meta">REQUESTS · v2.0</span>
    <span class="ih-req-footer-status"><span class="dot" aria-hidden="true"></span>OPERATIONAL</span>
  </footer>
</div>
</div>

<?php
$content = ob_get_clean();
$title   = 'Requests';
include IH_DIR . 'pages/layout.php';
