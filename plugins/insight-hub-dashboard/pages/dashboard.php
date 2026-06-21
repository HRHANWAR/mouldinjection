<?php defined( 'ABSPATH' ) || exit;

$requests  = ih_db_requests();
$machines  = ih_db_machines(3);
$tools     = ih_db_tools(3);
$db_stats  = ih_db_stats();
global $wpdb;

if ( ! function_exists( 'ih_dash_figma_initials' ) ) {
    function ih_dash_figma_initials( $name ) {
        $parts = preg_split( '/\s+/', trim( (string) $name ) );
        $initials = strtoupper( substr( $parts[0] ?? 'U', 0, 1 ) . substr( $parts[1] ?? '', 0, 1 ) );
        return $initials ?: 'U';
    }
}
if ( ! function_exists('ih_dash_figma_av_color') ) {
    function ih_dash_figma_av_color( $user_id ) {
        $palette = array( '#1f3d2e', '#8c5cf5', '#3380bd', '#db731f', '#21805c' );
        $idx     = (int) $user_id % count( $palette );
        return $palette[ $idx ];
    }
}
if ( ! function_exists('ih_dash_figma_type_badge') ) {
    function ih_dash_figma_type_badge( $intent, $request = array() ) {
        $key = sanitize_key( $intent['key'] ?? '' );
        if ( $key === 'admin' ) {
            return '<span class="ih-figma-type is-contact">ADMIN MESSAGE</span>';
        }
        if ( $key === 'profile' || ! ih_request_is_owner_listing_approval( $request ) ) {
            return '<span class="ih-figma-type is-contact">CONTACT ACCESS</span>';
        }
        return '<span class="ih-figma-type is-listing">LISTING APPROVAL</span>';
    }
}

$total_requests  = (int) ( $db_stats['requests'] ?? count( (array) $requests ) );
$approved_total  = (int) ( $db_stats['approved'] ?? 0 );
$pending_total   = (int) ( $db_stats['pending'] ?? 0 );
$rejected_total  = (int) ( $db_stats['rejected'] ?? 0 );
$completed_total = (int) ( $db_stats['completed'] ?? 0 );

$year              = (int) current_time( 'Y' );
$month            = (int) current_time( 'n' );
$analytics_periods = ih_dash_analytics_periods();
$analytics_default = $analytics_periods['month'];
$monthly_counts    = $analytics_default['counts'];
$recent_requests   = array_slice( (array) $requests, 0, 10 );

$dash_status = $analytics_default['status'];
$dash_approved  = (int) ( $dash_status['approved'] ?? 0 );
$dash_pending   = (int) ( $dash_status['pending'] ?? 0 );
$dash_rejected  = (int) ( $dash_status['rejected'] ?? 0 );
$dash_completed = (int) ( $dash_status['completed'] ?? 0 );
$dash_status_total  = max( 1, $dash_approved + $dash_pending + $dash_rejected + $dash_completed );
$dash_success_count = $dash_approved + $dash_completed;
$dash_success_pct   = (int) round( ( $dash_success_count / $dash_status_total ) * 100 );

$approval_queue = array_slice(
    array_values(
        array_filter(
            (array) $requests,
            function ( $request ) {
                return strtolower( trim( (string) ( $request['status'] ?? 'pending' ) ) ) === 'pending';
            }
        )
    ),
    0,
    5
);

$latest_listings = array();
foreach ( (array) $machines as $machine_row ) {
    $machine_row['_listing_kind'] = 'machine';
    $latest_listings[] = $machine_row;
}
foreach ( (array) $tools as $tool_row ) {
    $tool_row['_listing_kind'] = 'tool';
    $latest_listings[] = $tool_row;
}
$latest_listings = array_slice( $latest_listings, 0, 4 );

$dash_users = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" );

if ( ! function_exists( 'ih_dash_approval_row_meta' ) ) {
    /** Shared listing/user fields for approval queue rows (desktop + mobile). */
    function ih_dash_approval_row_meta( $r ) {
        $intent        = ih_request_intent_meta( $r );
        $req_type      = $r['listing_type'] ?? $r['type'] ?? 'request';
        $listing_id    = (int) ( $r['listing_id'] ?? 0 );
        $listing_ref   = ih_request_listing_ref( $req_type, $listing_id );
        $listing_title = $req_type === 'tool'
            ? ( $r['tool_title'] ?? '' )
            : ( $r['machine_title'] ?? '' );
        if ( ! $listing_title ) {
            $listing_title = $listing_id ? 'Listing #' . $listing_id : 'Admin conversation';
        }
        if ( $intent['key'] === 'profile' ) {
            $listing_title = 'Protected user profile/contact';
        } elseif ( $intent['key'] === 'admin' ) {
            $listing_title = 'Admin conversation';
        }
        $user_id   = (int) ( $r['user_id'] ?? 0 );
        $user_name = $r['name'] ?? '';
        if ( ! $user_name && $user_id ) {
            $ud = get_userdata( $user_id );
            $user_name = $ud ? $ud->display_name : 'User #' . $user_id;
        }
        $user_ref     = get_user_meta( $user_id, 'ih_unique_id', true ) ?: ( 'USER-' . $user_id );
        $request_id   = (int) ( $r['id'] ?? 0 );
        $request_date = $r['request_date'] ?? '';
        $date_short   = $request_date ? date_i18n( 'd M', strtotime( $request_date ) ) : '—';
        $initials     = ih_dash_figma_initials( $user_name );
        $av_color     = ih_dash_figma_av_color( $user_id );
        return compact( 'intent', 'req_type', 'listing_id', 'listing_ref', 'listing_title', 'user_id', 'user_name', 'user_ref', 'request_id', 'request_date', 'date_short', 'initials', 'av_color' );
    }
}

ob_start();
?>
<div class="ih-rd ih-admin ih-dash-page" id="ihDashPage">
  <div class="ih-rd-head ih-dash-header">
    <div class="ih-eyebrow ih-dash-eyebrow">
      <span class="dot" aria-hidden="true"></span>
      <span class="ih-dash-eyebrow-long">ADMIN COMMAND CENTRE · LIVE</span>
      <span class="ih-dash-eyebrow-short">COMMAND CENTRE · LIVE</span>
    </div>
    <h1 class="ih-dashboard-title">Dashboard</h1>
    <p class="ih-dashboard-sub ih-dash-sub-long">Admin controls everything — users request listing approvals and contact access via messages.</p>
    <p class="ih-dashboard-sub ih-dash-sub-short">Approve listings &amp; contact requests inline.</p>
  </div>

  <div class="ih-grid-stats ih-admin-kpi-7 ih-dash-kpis">
    <?php
    $dash_kpis = array(
        array( 'wrap' => 'ih-dash-kpi--machines ih-dash-desktop-only', 'tile' => array( 'icon' => 'machine', 'tone' => 'blue', 'value' => sprintf( '%02d', (int) ( $db_stats['machines'] ?? 0 ) ), 'label' => 'Machines', 'href' => admin_url( 'admin.php?page=ih-machines' ) ) ),
        array( 'wrap' => 'ih-dash-kpi--tools ih-dash-desktop-only', 'tile' => array( 'icon' => 'mould', 'tone' => 'green', 'value' => sprintf( '%02d', (int) ( $db_stats['tools'] ?? 0 ) ), 'label' => 'Tools', 'href' => admin_url( 'admin.php?page=ih-tools' ) ) ),
        array( 'wrap' => 'ih-dash-kpi--users ih-dash-desktop-only', 'tile' => array( 'icon' => 'corp', 'tone' => 'violet', 'value' => (string) $dash_users, 'label' => 'Users', 'href' => admin_url( 'admin.php?page=ih-users' ) ) ),
        array( 'wrap' => 'ih-dash-kpi--requests', 'tile' => array( 'icon' => 'messages', 'tone' => 'olive', 'value' => (string) $total_requests, 'label' => 'Requests', 'href' => admin_url( 'admin.php?page=ih-requests' ) ) ),
        array( 'wrap' => 'ih-dash-kpi--pending', 'tile' => array( 'icon' => 'pending', 'tone' => 'amber', 'value' => sprintf( '%02d', $pending_total ), 'label' => 'Pending', 'href' => admin_url( 'admin.php?page=ih-requests' ) ) ),
        array( 'wrap' => 'ih-dash-kpi--approved', 'tile' => array( 'icon' => 'approve', 'tone' => 'green', 'value' => sprintf( '%02d', $approved_total ), 'label' => 'Approved', 'href' => admin_url( 'admin.php?page=ih-requests' ) ) ),
        array( 'wrap' => 'ih-dash-kpi--rejected', 'tile' => array( 'icon' => 'reject', 'tone' => 'rose', 'value' => sprintf( '%02d', $rejected_total ), 'label' => 'Rejected', 'href' => admin_url( 'admin.php?page=ih-requests' ) ) ),
    );
    foreach ( $dash_kpis as $kpi_row ) {
        echo '<div class="ih-dash-kpi ' . esc_attr( $kpi_row['wrap'] ) . '">';
        echo ih_stat_tile( $kpi_row['tile'] );
        echo '</div>';
    }
    ?>
  </div>

  <div class="ih-section ih-figma-approvals-queue ih-dash-approvals">
    <div class="ih-figma-section-head">
      <div class="ih-figma-section-copy">
        <span class="ih-figma-section-title">Approvals Queue</span>
        <p class="ih-figma-section-sub ih-dash-desktop-only">Pending listing approvals and contact-access requests — approve or reject inline.</p>
      </div>
      <?php if ( $pending_total > 0 ) : ?>
        <span class="ih-figma-pending-pill"><?php echo (int) $pending_total; ?> PENDING</span>
      <?php endif; ?>
    </div>
    <div class="ih-figma-approval-list ih-dash-approval-desktop">
      <?php if ( empty( $approval_queue ) ) : ?>
        <div class="ih-figma-approval-empty">No pending approvals. New requests will appear here first.</div>
      <?php else : ?>
        <?php foreach ( $approval_queue as $r ) :
            extract( ih_dash_approval_row_meta( $r ), EXTR_SKIP );
            ?>
          <div class="ih-figma-approval-row" data-req-id="<?php echo (int) $request_id; ?>">
            <span class="ih-figma-av" style="background:<?php echo esc_attr( $av_color ); ?>"><?php echo esc_html( $initials ); ?></span>
            <span class="ih-figma-chip is-req"><?php echo esc_html( ih_request_ref( $r ) ); ?></span>
            <span class="ih-figma-chip is-user"><?php echo esc_html( strtoupper( $user_ref ) ); ?></span>
            <span class="ih-figma-name"><?php echo esc_html( $user_name ); ?></span>
              <?php echo ih_dash_figma_type_badge( $intent, $r ); ?>
            <span class="ih-figma-listing-line"><?php echo esc_html( $listing_ref . ' · ' . $listing_title ); ?></span>
            <span class="ih-figma-date"><?php echo esc_html( $date_short ); ?></span>
            <span class="ih-figma-approval-actions-wrap">
              <button type="button" class="ih-figma-approval-btn is-approve" data-req-id="<?php echo (int) $request_id; ?>" data-request-status="approved">Approve</button>
              <button type="button" class="ih-figma-approval-btn is-reject" data-req-id="<?php echo (int) $request_id; ?>" data-request-status="rejected">Reject</button>
            </span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div class="ih-dash-approval-mobile-cards" aria-label="Pending approval cards">
      <?php if ( empty( $approval_queue ) ) : ?>
        <div class="ih-figma-approval-empty ih-dash-approval-mobile-empty">No pending approvals. New requests will appear here first.</div>
      <?php else : ?>
        <?php foreach ( $approval_queue as $r ) :
            extract( ih_dash_approval_row_meta( $r ), EXTR_SKIP );
            ?>
          <article class="ih-figma-approval-card" data-req-id="<?php echo (int) $request_id; ?>">
            <div class="ih-dash-approval-card-head">
              <span class="ih-figma-av ih-dash-approval-av" style="background:<?php echo esc_attr( $av_color ); ?>"><?php echo esc_html( $initials ); ?></span>
              <div class="ih-dash-approval-ident">
                <span class="ih-dash-approval-name"><?php echo esc_html( $user_name ); ?></span>
                <span class="ih-dash-approval-meta"><?php echo esc_html( $listing_ref . ' · ' . $listing_title ); ?></span>
              </div>
              <?php echo ih_dash_figma_type_badge( $intent, $r ); ?>
            </div>
            <div class="ih-figma-approval-actions-wrap ih-dash-approval-card-actions">
              <button type="button" class="ih-figma-approval-btn is-approve" data-req-id="<?php echo (int) $request_id; ?>" data-request-status="approved">Approve</button>
              <button type="button" class="ih-figma-approval-btn is-reject" data-req-id="<?php echo (int) $request_id; ?>" data-request-status="rejected">Reject</button>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <?php
  $rd_segs = array(
    array( 'v' => $dash_approved / $dash_status_total, 'c' => '#22c55e' ),
    array( 'v' => $dash_pending / $dash_status_total, 'c' => '#f59e0b' ),
    array( 'v' => $dash_completed / $dash_status_total, 'c' => '#3b82f6' ),
    array( 'v' => $dash_rejected / $dash_status_total, 'c' => '#ef4444' ),
  );
  ?>
  <div class="ih-grid-charts ih-analytics-grid" data-analytics-default="month">
    <script type="application/json" id="ih-analytics-data"><?php echo wp_json_encode( $analytics_periods ); ?></script>
    <div class="ih-section ih-chart-panel ih-analytics-bars">
      <div class="ih-section-head ih-analytics-head">
        <div>
          <h3 class="ih-analytics-title"><?php echo esc_html( $analytics_default['title'] ); ?></h3>
          <div class="csub ih-analytics-sub"><?php echo esc_html( $analytics_default['subtitle'] ); ?></div>
        </div>
        <div class="ih-period-filters" role="group" aria-label="Analytics period">
          <button type="button" class="ih-period-filter" data-period="week">Week</button>
          <button type="button" class="ih-period-filter is-active" data-period="month">Month</button>
          <button type="button" class="ih-period-filter" data-period="year">Year</button>
        </div>
      </div>
      <div class="ih-analytics-bars-host">
        <?php echo ih_rd_bars( $monthly_counts, $month - 1, $analytics_default['labels'] ); ?>
      </div>
    </div>
    <div class="ih-section ih-chart-panel ih-analytics-status">
      <div class="ih-section-head">
        <div>
          <h3>Request Status</h3>
          <div class="csub ih-status-sub"><?php echo esc_html( $analytics_default['status_sub'] ); ?></div>
        </div>
      </div>
      <div class="ih-donut-wrap" data-donut-r="58">
        <div class="ih-donut">
          <svg width="150" height="150" viewBox="0 0 150 150"><?php echo ih_rd_donut_svg( $rd_segs ); ?></svg>
          <div class="center"><b class="ih-donut-pct" style="color:#22c55e"><?php echo (int) $dash_success_pct; ?>%</b><span>SUCCESS</span></div>
        </div>
        <div class="ih-legend">
          <div class="lg" data-status="approved"><span class="d" style="background:#22c55e"></span>Approved<b class="ih-legend-count"><?php echo sprintf( '%02d', $dash_approved ); ?></b></div>
          <div class="lg" data-status="pending"><span class="d" style="background:#f59e0b"></span>Pending<b class="ih-legend-count"><?php echo sprintf( '%02d', $dash_pending ); ?></b></div>
          <div class="lg" data-status="completed"><span class="d" style="background:#3b82f6"></span>Completed<b class="ih-legend-count"><?php echo sprintf( '%02d', $dash_completed ); ?></b></div>
          <div class="lg" data-status="rejected"><span class="d" style="background:#ef4444"></span>Rejected<b class="ih-legend-count"><?php echo sprintf( '%02d', $dash_rejected ); ?></b></div>
        </div>
      </div>
    </div>
  </div>

  <div class="ih-section ih-dash-listings">
    <div class="ih-section-head">
      <div><h3>Latest Listings</h3><div class="csub ih-dash-desktop-only">Newest machines and tools needing admin visibility &amp; approval.</div></div>
      <a class="ih-btn ih-btn-outline ih-rd-head-btn ih-dash-desktop-only" href="<?php echo esc_url( admin_url( 'admin.php?page=ih-machines' ) ); ?>">View all</a>
    </div>
    <div class="ih-tabs">
      <button type="button" class="ih-tab active" data-filter="all">All</button>
      <button type="button" class="ih-tab" data-filter="machine">Machines</button>
      <button type="button" class="ih-tab" data-filter="tool">Tools</button>
    </div>
    <div class="ih-grid-cards">
      <?php if ( empty( $latest_listings ) ) : ?>
        <p class="csub">No listings yet.</p>
      <?php else : ?>
        <?php foreach ( $latest_listings as $listing_row ) :
            $kind = sanitize_key( $listing_row['_listing_kind'] ?? 'machine' );
            $m    = $listing_row;
            $anon = true;
            include __DIR__ . '/partials/ih-rd-card.php';
        endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="ih-section ih-dash-requests">
    <div class="ih-section-head">
      <div><h3>All Message Requests</h3><div class="csub ih-dash-desktop-only">Search, filter and audit every contact &amp; approval request from one queue.</div></div>
      <a class="ih-btn ih-btn-lime ih-rd-head-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=ih-requests' ) ); ?>">Open queue</a>
    </div>
    <div class="ih-table-wrap ih-rd-table-wrap ih-dash-req-desktop">
      <?php if ( empty( $recent_requests ) ) : ?>
        <p class="csub">No requests yet.</p>
      <?php else : ?>
      <table class="ih-table ih-table-pro ih-rd-table">
        <thead>
          <tr>
            <th class="ih-rd-table__th">Requester</th>
            <th class="ih-rd-table__th">Request</th>
            <th class="ih-rd-table__th">Target</th>
            <th class="ih-rd-table__th">Date</th>
            <th class="ih-rd-table__th ih-rd-table__th--status">Status</th>
            <th class="ih-rd-table__th ih-rd-table__th--actions right">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ( $recent_requests as $r ) :
            $intent     = ih_request_intent_meta( $r );
            $user_name  = $r['name'] ?? '';
            $user_id    = (int) ( $r['user_id'] ?? 0 );
            $req_date   = $r['request_date'] ?? $r['created_at'] ?? '';
            $date_label = $req_date ? date_i18n( 'd M y', strtotime( $req_date ) ) : '—';
            $req_ref    = ih_request_ref( $r );
            $initials   = ih_dash_figma_initials( $user_name );
            $av_color   = ih_dash_figma_av_color( $user_id );
            $type_key   = sanitize_key( $intent['key'] ?? 'admin' );
            $tcls       = in_array( $type_key, array( 'machine', 'tool', 'profile', 'admin' ), true ) ? $type_key : 'admin';
            $target     = $r['listing_ref'] ?? $r['reference'] ?? $r['listing'] ?? $r['location'] ?? $r['city'] ?? '—';
            $view_url   = admin_url( 'admin.php?page=ih-requests' );
            ?>
          <tr class="ih-rd-table__row">
            <td class="ih-rd-table__cell ih-rd-table__cell--name">
              <div class="ih-req-who">
                <span class="ih-tbl-avatar" style="background:<?php echo esc_attr( $av_color ); ?>"><?php echo esc_html( $initials ); ?></span>
                <div class="ih-req-who-text">
                  <span class="ih-req-name"><?php echo esc_html( $user_name ?: '—' ); ?></span>
                  <span class="ih-req-uid"><?php echo esc_html( ih_user_ref( $user_id ) ); ?> &middot; <?php echo esc_html( $req_ref ); ?></span>
                </div>
              </div>
            </td>
            <td class="ih-rd-table__cell">
              <span class="ih-req-type ih-req-type-<?php echo esc_attr( $tcls ); ?>"><?php echo esc_html( $intent['label'] ); ?></span>
            </td>
            <td class="ih-rd-table__cell target">
              <span class="ih-rd-table__target"><?php echo esc_html( $target ); ?></span>
            </td>
            <td class="ih-rd-table__cell ih-rd-table__cell--date date">
              <span class="ih-rd-table__date"><?php echo esc_html( $date_label ); ?></span>
            </td>
            <td class="ih-rd-table__cell ih-rd-table__cell--status">
              <?php echo ih_status_pill( $r['status'] ?? 'Pending' ); ?>
            </td>
            <td class="ih-rd-table__cell ih-rd-table__cell--actions right">
              <a class="ih-btn ih-btn-outline ih-rd-table__view" href="<?php echo esc_url( $view_url ); ?>">View</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
    <div class="ih-dash-req-mobile-cards" aria-label="Recent request cards">
      <?php if ( empty( $recent_requests ) ) : ?>
        <p class="csub ih-dash-req-mobile-empty">No requests yet.</p>
      <?php else : ?>
        <?php foreach ( $recent_requests as $r ) :
            $intent     = ih_request_intent_meta( $r );
            $user_name  = $r['name'] ?? '';
            $user_id    = (int) ( $r['user_id'] ?? 0 );
            $req_date   = $r['request_date'] ?? $r['created_at'] ?? '';
            $date_label = $req_date ? date_i18n( 'd M y', strtotime( $req_date ) ) : '—';
            $req_ref    = ih_request_ref( $r );
            $initials   = ih_dash_figma_initials( $user_name );
            $av_color   = ih_dash_figma_av_color( $user_id );
            $type_key   = sanitize_key( $intent['key'] ?? 'admin' );
            $tcls       = in_array( $type_key, array( 'machine', 'tool', 'profile', 'admin' ), true ) ? $type_key : 'admin';
            $target     = $r['listing_ref'] ?? $r['reference'] ?? $r['listing'] ?? $r['location'] ?? $r['city'] ?? '—';
            $view_url   = admin_url( 'admin.php?page=ih-requests' );
            ?>
          <article class="ih-dash-req-mobile-card">
            <div class="ih-dash-req-mobile-head">
              <span class="ih-tbl-avatar ih-dash-req-mobile-av" style="background:<?php echo esc_attr( $av_color ); ?>"><?php echo esc_html( $initials ); ?></span>
              <div class="ih-dash-req-mobile-ident">
                <span class="ih-dash-req-mobile-name"><?php echo esc_html( $user_name ?: '—' ); ?></span>
                <span class="ih-dash-req-mobile-uid"><?php echo esc_html( ih_user_ref( $user_id ) ); ?> · <?php echo esc_html( $req_ref ); ?></span>
              </div>
              <?php echo ih_status_pill( $r['status'] ?? 'Pending' ); ?>
            </div>
            <span class="ih-req-type ih-req-type-<?php echo esc_attr( $tcls ); ?> ih-dash-req-mobile-intent"><?php echo esc_html( $intent['label'] ); ?></span>
            <div class="ih-dash-req-mobile-meta">
              <span class="ih-dash-req-mobile-target"><?php echo esc_html( $target ); ?></span>
              <span class="ih-dash-req-mobile-date"><?php echo esc_html( $date_label ); ?></span>
            </div>
            <a class="ih-btn ih-btn-outline ih-dash-req-mobile-view" href="<?php echo esc_url( $view_url ); ?>">View</a>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <footer class="ih-footer">
    <span>ADMIN CONTROL · v<?php echo esc_html( IH_VERSION ); ?> · © <?php echo esc_html( date( 'Y' ) ); ?></span>
    <span><span class="dot"></span>SYSTEM STATUS · OPERATIONAL</span>
  </footer>

</div>

<?php
$content = ob_get_clean();
$title   = 'Dashboard';
include IH_DIR . 'pages/layout.php';