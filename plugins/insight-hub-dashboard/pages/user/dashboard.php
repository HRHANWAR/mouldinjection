<?php

/**

 * Corporation (user) dashboard — Figma v2.5 light theme (.ih-rd.ih-corp-dash).

 * Re-skin only: listings, requests and messaging data unchanged.

 */

defined( 'ABSPATH' ) || exit;



if ( ! is_user_logged_in() ) {

	wp_redirect( wp_login_url( admin_url( 'admin.php?page=ih-user-dashboard' ) ) );

	exit;

}

if ( current_user_can( 'administrator' ) ) {

	wp_redirect( admin_url( 'admin.php?page=ih-dashboard' ) );

	exit;

}



global $wpdb;

$user_id = get_current_user_id();

$user    = wp_get_current_user();



$machines = $wpdb->get_results(

	$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ih_machines WHERE owner_id=%d ORDER BY id DESC", $user_id ),

	ARRAY_A

) ?: array();

$tools = $wpdb->get_results(

	$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ih_tools WHERE owner_id=%d ORDER BY id DESC", $user_id ),

	ARRAY_A

) ?: array();

$all_mine = array_merge( $machines, $tools );



$listing_health = function_exists( 'ih_owner_listing_health' )
	? ih_owner_listing_health( $user_id, $machines, $tools )
	: array( 'listings' => array(), 'summary' => array() );

$lh_items   = $listing_health['listings'] ?? array();

$lh_summary = wp_parse_args(
	$listing_health['summary'] ?? array(),
	array(
		'total'          => 0,
		'live'           => 0,
		'pending'        => 0,
		'expiring_soon'  => 0,
		'expired'        => 0,
		'total_requests' => 0,
		'new_requests'   => 0,
	)
);



$c_machines = count( $machines );

$c_moulds   = count( $tools );

$c_approved = 0;

$c_pending_ls = 0;

$c_completed  = 0;

$c_rejected   = 0;

$c_pending_listings = 0;



foreach ( $all_mine as $row ) {

	$meta = function_exists( 'ih_listing_status_meta' ) ? ih_listing_status_meta( $row ) : array( 'key' => ! empty( $row['available'] ) ? 'available' : 'pending' );

	$key  = $meta['key'] ?? 'pending';

	if ( $key === 'available' ) {

		$c_approved++;

	} elseif ( $key === 'completed' ) {

		$c_completed++;

	} elseif ( $key === 'rejected' ) {

		$c_rejected++;

	} else {

		$c_pending_ls++;

	}

}

foreach ( $all_mine as $listing_row ) {

	$meta = function_exists( 'ih_listing_status_meta' ) ? ih_listing_status_meta( $listing_row ) : array( 'key' => 'pending' );

	if ( ( $meta['key'] ?? '' ) === 'pending' ) {

		$c_pending_listings++;

	}

}



$c_total_listings  = count( $all_mine );

$c_active_listings = $c_total_listings;

$c_pending         = $c_pending_ls;



$ls_tot  = max( 1, $c_total_listings );

$ls_segs = array(

	array( 'v' => $c_approved / $ls_tot, 'c' => '#16a34a' ),

	array( 'v' => $c_pending_ls / $ls_tot, 'c' => '#f59e0b' ),

	array( 'v' => $c_completed / $ls_tot, 'c' => '#3b82f6' ),

	array( 'v' => $c_rejected / $ls_tot, 'c' => '#ef4444' ),

);

$ls_pct            = (int) round( ( $c_approved / $ls_tot ) * 100 );

$ls_approved_pct   = (int) round( ( $c_approved / $ls_tot ) * 100 );

$ls_center_lbl     = 'APPROVED';



$rtbl = $wpdb->prefix . 'ih_requests';

$my_requests = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $rtbl ) )

	? $wpdb->get_results(

		$wpdb->prepare(

			"SELECT r.*, m.title AS machine_title, t.title AS tool_title

			 FROM {$rtbl} r

			 LEFT JOIN {$wpdb->prefix}ih_machines m ON r.listing_id = m.id AND r.listing_type IN ('machine','machine_contact')

			 LEFT JOIN {$wpdb->prefix}ih_tools t ON r.listing_id = t.id AND r.listing_type IN ('tool','tool_contact')

			 WHERE r.user_id=%d ORDER BY r.id DESC LIMIT 12",

			$user_id

		),

		ARRAY_A

	)

	: array();



$open_requests   = 0;

$contact_pending = 0;

foreach ( $my_requests as $r ) {

	$st = strtolower( trim( (string) ( $r['status'] ?? 'pending' ) ) );

	if ( $st === 'pending' ) {

		$open_requests++;

		$intent = function_exists( 'ih_request_intent_meta' ) ? ih_request_intent_meta( $r ) : array( 'key' => 'profile' );

		if ( ( $intent['key'] ?? '' ) !== 'admin' && ! ih_request_is_owner_listing_approval( $r ) ) {

			$contact_pending++;

		}

	}

}



$contact_incoming = 0;

$approved_week    = 0;

$profile_views_delta = 0;

$week_start       = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days', (int) current_time( 'timestamp' ) ) );

if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $rtbl ) ) ) {

	$contact_incoming = (int) $wpdb->get_var(

		$wpdb->prepare(

			"SELECT COUNT(*) FROM {$rtbl} r

			 WHERE LOWER(TRIM(r.status)) = 'pending'

			   AND r.user_id != %d

			   AND (

			     (r.listing_type IN ('profile_access','profile','user') AND r.listing_id = %d)

			     OR EXISTS (SELECT 1 FROM {$wpdb->prefix}ih_machines m WHERE m.id = r.listing_id AND m.owner_id = %d AND r.listing_type IN ('machine','machine_contact'))

			     OR EXISTS (SELECT 1 FROM {$wpdb->prefix}ih_tools t WHERE t.id = r.listing_id AND t.owner_id = %d AND r.listing_type IN ('tool','tool_contact'))

			   )",

			$user_id,

			$user_id,

			$user_id,

			$user_id

		)

	);

	$approved_week = (int) $wpdb->get_var(

		$wpdb->prepare(

			"SELECT COUNT(*) FROM {$rtbl} WHERE user_id=%d AND LOWER(TRIM(status))='approved' AND request_date >= %s",

			$user_id,

			$week_start

		)

	);

	$profile_views_delta = (int) $wpdb->get_var(

		$wpdb->prepare(

			"SELECT COUNT(*) FROM {$rtbl} r

			 WHERE r.request_date >= %s AND r.user_id != %d

			   AND (

			     EXISTS (SELECT 1 FROM {$wpdb->prefix}ih_machines m WHERE m.id = r.listing_id AND m.owner_id = %d)

			     OR EXISTS (SELECT 1 FROM {$wpdb->prefix}ih_tools t WHERE t.id = r.listing_id AND t.owner_id = %d)

			     OR (r.listing_type IN ('profile_access','profile','user') AND r.listing_id = %d)

			   )",

			$week_start,

			$user_id,

			$user_id,

			$user_id,

			$user_id

		)

	);

}



$unread_msgs = (int) $wpdb->get_var(

	$wpdb->prepare( "SELECT COALESCE(SUM(unread),0) FROM {$wpdb->prefix}ih_threads WHERE user_id=%d", $user_id )

);



$msg_threads = $wpdb->get_results(

	$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ih_threads WHERE user_id=%d ORDER BY last_time DESC LIMIT 5", $user_id ),

	ARRAY_A

) ?: array();



$online_contacts = 0;

$online_cutoff   = gmdate( 'Y-m-d H:i:s', strtotime( '-30 minutes', (int) current_time( 'timestamp' ) ) );

foreach ( $msg_threads as $th ) {

	if ( ! empty( $th['last_time'] ) && $th['last_time'] >= $online_cutoff ) {

		$online_contacts++;

	}

}

if ( $msg_threads ) {

	$online_contacts = max( $online_contacts, 1 );

}



$umeta_row = $wpdb->get_row(

	$wpdb->prepare( "SELECT company FROM {$wpdb->prefix}ih_user_meta WHERE user_id=%d", $user_id ),

	ARRAY_A

);

$company_name = get_user_meta( $user_id, 'ih_company_name', true )

	?: get_user_meta( $user_id, 'company_name', true )

	?: ( $umeta_row['company'] ?? '' )

	?: $user->display_name;

$company_initials = function_exists( 'ih_user_initials' ) ? ih_user_initials( $company_name ) : strtoupper( substr( $company_name, 0, 2 ) );

$company_color    = function_exists( 'ih_user_avatar_color' ) ? ih_user_avatar_color( $user_id ) : '#164b3f';



$browse   = function_exists( 'ih_browse_all_listings' ) ? ih_browse_all_listings( 8 ) : array();

$partials = dirname( __DIR__ ) . '/partials/';



$messages_url    = admin_url( 'admin.php?page=ih-user-messages' );

$add_listing_url = admin_url( 'admin.php?page=ih-user-add-tool' );



if ( ! function_exists( 'ih_corp_dash_time_ago' ) ) {

	function ih_corp_dash_time_ago( $mysql_time ) {

		if ( ! $mysql_time ) {

			return '—';

		}

		$ts  = strtotime( $mysql_time );

		$diff = (int) current_time( 'timestamp' ) - $ts;

		if ( $diff < 60 ) {

			return 'Just now';

		}

		if ( $diff < 3600 ) {

			return (int) floor( $diff / 60 ) . 'm ago';

		}

		if ( $diff < 86400 ) {

			return (int) floor( $diff / 3600 ) . 'h ago';

		}

		return date_i18n( 'd M', $ts );

	}

}

?>

<!DOCTYPE html>

<html <?php language_attributes(); ?>>

<head>

<meta charset="<?php bloginfo( 'charset' ); ?>">

<meta name="viewport" content="width=device-width,initial-scale=1">

<title><?php esc_html_e( 'My Dashboard', 'insight-hub-dashboard' ); ?> — <?php bloginfo( 'name' ); ?></title>

<?php wp_head(); ?>

</head>

<body class="ih-user-portal ih-user-page ih-corp-dash-active">

<?php
$ih_shell_class = 'ih-shell ih-figma-dashboard is-user ih-shell--float-nav ih-rd ih-user ih-corp-dash-page';
$ih_shell_extra = 'data-ih-figma-screen="user-corp-dashboard-v20260614"';
include IH_DIR . 'pages/partials/ih-user-shell-start.php';
?>

<?php

$ih_header_search_placeholder = __( 'Search listings…', 'insight-hub-dashboard' );

include IH_DIR . 'pages/partials/ih-user-shell-header.php';

?>



<div class="ih-body">

	<main class="ih-main">

		<div class="ih-content ih-rd ih-corp-dash ih-corp-dash-page" id="ihCorpDash">



			<header class="ih-rd-head ih-corp-head">

				<div class="ih-corp-head-copy">

					<div class="ih-eyebrow ih-corp-eyebrow"><span class="dot"></span>CORPORATION WORKSPACE · LIVE</div>

					<h1 class="ih-dashboard-title">Dashboard</h1>

					<p class="ih-dashboard-sub ih-corp-sub-long">Your moulds, machines and requests at a glance — admin approves every listing &amp; contact request.</p>

					<p class="ih-dashboard-sub ih-corp-sub-short">Admin approves every listing &amp; contact request.</p>

				</div>

				<div class="ih-corp-head-actions ih-corp-desktop-only">

					<label class="ih-corp-search" for="ihCorpSearch">

						<span class="ih-corp-search-icon" aria-hidden="true"><?php echo ih_icon( 'browse', 16 ); ?></span>

						<input type="search" id="ihCorpSearch" class="ih-corp-search-input" placeholder="<?php esc_attr_e( 'Search listings…', 'insight-hub-dashboard' ); ?>" autocomplete="off">

					</label>

					<a class="ih-btn ih-btn-primary ih-corp-add-btn" href="<?php echo esc_url( $add_listing_url ); ?>"><?php echo ih_icon( 'add', 15, '#fff' ); ?>Add Listing</a>

				</div>

			</header>



			<div class="ih-corp-quick-row ih-corp-desktop-only">

				<a class="ih-corp-quick" href="#ihBrowseListings"><span class="ih-corp-quick-icon"><?php echo ih_icon( 'browse', 18 ); ?></span><span class="ih-corp-quick-label">Browse Listings</span><span class="ih-corp-quick-pct is-up">12%</span></a>

				<a class="ih-corp-quick" href="#ihMyListings" data-filter="machine"><span class="ih-corp-quick-icon"><?php echo ih_icon( 'machine', 18 ); ?></span><span class="ih-corp-quick-label">My Machines</span><span class="ih-corp-quick-pct is-up">8%</span></a>

				<a class="ih-corp-quick" href="<?php echo esc_url( admin_url( 'admin.php?page=ih-user-add-machine' ) ); ?>"><span class="ih-corp-quick-icon"><?php echo ih_icon( 'add', 18 ); ?></span><span class="ih-corp-quick-label">Add Machine</span></a>

				<a class="ih-corp-quick" href="<?php echo esc_url( admin_url( 'admin.php?page=ih-user-add-tool' ) ); ?>"><span class="ih-corp-quick-icon"><?php echo ih_icon( 'tool', 18 ); ?></span><span class="ih-corp-quick-label">Add Tool</span><span class="ih-corp-quick-pct is-up">5%</span></a>

			</div>



			<nav class="ih-corp-nav-pills ih-corp-desktop-only" aria-label="<?php esc_attr_e( 'Dashboard sections', 'insight-hub-dashboard' ); ?>">

				<a class="ih-corp-pill" href="#ihMyListings">My Tools</a>

				<a class="ih-corp-pill" href="<?php echo esc_url( $messages_url ); ?>">Messages<?php if ( $unread_msgs > 0 ) : ?><span class="ih-corp-pill-count"><?php echo (int) $unread_msgs; ?></span><?php endif; ?></a>

				<a class="ih-corp-pill" href="#ihEnquiriesSection">Requests</a>

				<a class="ih-corp-pill is-active" href="#ihMyListings">My Listings</a>

			</nav>



			<div class="ih-grid-stats ih-corp-kpis">

				<div class="ih-corp-kpi ih-corp-kpi--moulds">

				<?php

				echo ih_stat_tile( array(

					'icon'  => 'mould',

					'tone'  => 'green',

					'value' => sprintf( '%02d', $c_moulds ),

					'label' => 'My Moulds',

					'href'  => '#ihMyListings',

					'pct'   => '+2 wk',

				) );

				?>

				</div>

				<div class="ih-corp-kpi ih-corp-kpi--machines">

				<?php

				echo ih_stat_tile( array(

					'icon'  => 'machine',

					'tone'  => 'blue',

					'value' => sprintf( '%02d', $c_machines ),

					'label' => 'My Machines',

					'href'  => '#ihMyListings',

					'pct'   => '+1 wk',

				) );

				?>

				</div>

				<div class="ih-corp-kpi ih-corp-kpi--active ih-corp-desktop-only">

				<?php

				echo ih_stat_tile( array(

					'icon'  => 'listings',

					'tone'  => 'olive',

					'value' => sprintf( '%02d', $c_active_listings ),

					'label' => 'Active Listings',

				) );

				?>

				</div>

				<div class="ih-corp-kpi ih-corp-kpi--pending">

				<?php

				echo ih_stat_tile( array(

					'icon'  => 'pending',

					'tone'  => 'amber',

					'value' => sprintf( '%02d', $c_pending ),

					'label' => 'Pending Approval',

				) );

				?>

				</div>

				<div class="ih-corp-kpi ih-corp-kpi--messages">

				<?php

				echo ih_stat_tile( array(

					'icon'  => 'messages',

					'tone'  => 'violet',

					'value' => sprintf( '%02d', $unread_msgs ),

					'label' => 'Unread Messages',

					'href'  => $messages_url,

				) );

				?>

				</div>

			</div>



			<section class="ih-section ih-corp-health" id="ihListingHealth" aria-labelledby="ihListingHealthTitle">

				<div class="ih-section-head">

					<div>

						<h3 id="ihListingHealthTitle">Listing Health</h3>

						<div class="csub ih-corp-desktop-only">Completeness, status, expiry &amp; requests for every listing you own.</div>

					</div>

					<a class="ih-link" href="#ihMyListings">Manage all →</a>

				</div>



				<div class="ih-corp-health-summary" role="list" aria-label="<?php esc_attr_e( 'Listing health totals', 'insight-hub-dashboard' ); ?>">

					<div class="ih-corp-health-sum is-live" role="listitem">

						<span class="ih-corp-health-sum-num"><?php echo (int) $lh_summary['live']; ?></span>

						<span class="ih-corp-health-sum-lab">Live</span>

					</div>

					<div class="ih-corp-health-sum is-pending" role="listitem">

						<span class="ih-corp-health-sum-num"><?php echo (int) $lh_summary['pending']; ?></span>

						<span class="ih-corp-health-sum-lab">Pending</span>

					</div>

					<div class="ih-corp-health-sum is-soon" role="listitem">

						<span class="ih-corp-health-sum-num"><?php echo (int) $lh_summary['expiring_soon']; ?></span>

						<span class="ih-corp-health-sum-lab">Expiring soon</span>

					</div>

					<div class="ih-corp-health-sum is-req" role="listitem">

						<span class="ih-corp-health-sum-num"><?php echo (int) $lh_summary['total_requests']; ?></span>

						<span class="ih-corp-health-sum-lab">Total requests<?php if ( (int) $lh_summary['new_requests'] > 0 ) : ?> · <?php echo (int) $lh_summary['new_requests']; ?> new<?php endif; ?></span>

					</div>

				</div>



				<?php if ( $lh_items ) : ?>

				<ul class="ih-corp-health-list">

					<?php foreach ( $lh_items as $lh ) :

						$lh_exp   = $lh['expiry'];

						$lh_kind  = $lh['type'] === 'machine' ? 'Machine' : 'Mould';

						$lh_icon  = $lh['type'] === 'machine' ? 'machine' : 'mould';

						?>

					<li class="ih-corp-health-row" data-kind="<?php echo esc_attr( $lh['type'] ); ?>" data-status="<?php echo esc_attr( $lh['status_key'] ); ?>">

						<div class="ih-corp-health-main">

							<span class="ih-corp-health-ico" aria-hidden="true"><?php echo ih_icon( $lh_icon, 18 ); ?></span>

							<div class="ih-corp-health-id">

								<a class="ih-corp-health-title" href="<?php echo esc_url( $lh['view_url'] ); ?>"><?php echo esc_html( $lh['title'] ); ?></a>

								<span class="ih-ref-chip ih-corp-health-ref"><?php echo esc_html( $lh['ref'] ); ?></span>

							</div>

						</div>



						<div class="ih-corp-health-meter">

							<div class="ih-corp-health-meter-top">

								<span class="ih-corp-health-meter-lab">Completeness</span>

								<b class="ih-corp-health-meter-pct <?php echo esc_attr( $lh['comp_tone'] ); ?>"><?php echo (int) $lh['completeness']; ?>%</b>

							</div>

							<div class="ih-progress-bar ih-corp-health-bar" role="progressbar" aria-valuenow="<?php echo (int) $lh['completeness']; ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?php echo esc_attr( sprintf( __( 'Listing %s is %d%% complete', 'insight-hub-dashboard' ), $lh['ref'], (int) $lh['completeness'] ) ); ?>">

								<span class="ih-progress-fill <?php echo esc_attr( $lh['comp_tone'] ); ?>" style="width:<?php echo (int) $lh['completeness']; ?>%"></span>

							</div>

						</div>



						<div class="ih-corp-health-badges">

							<?php echo ih_status_badge( $lh['status_key'] ); ?>

							<?php if ( $lh_exp['state'] === 'expired' ) : ?>

								<span class="ih-corp-health-flag is-expired"><?php echo esc_html( $lh_exp['label'] ); ?></span>

							<?php elseif ( $lh_exp['state'] === 'soon' ) : ?>

								<span class="ih-corp-health-flag is-soon"><?php echo esc_html( $lh_exp['label'] ); ?></span>

							<?php endif; ?>

						</div>



						<div class="ih-corp-health-req">

							<span class="ih-corp-health-req-num"><?php echo (int) $lh['req_total']; ?></span>

							<span class="ih-corp-health-req-lab"><?php echo esc_html( _n( 'request', 'requests', (int) $lh['req_total'], 'insight-hub-dashboard' ) ); ?><?php if ( (int) $lh['req_pending'] > 0 ) : ?> <span class="ih-corp-health-req-new"><?php echo (int) $lh['req_pending']; ?> new</span><?php endif; ?></span>

						</div>



						<div class="ih-corp-health-actions">

							<a class="ih-btn ih-btn-outline ih-corp-health-btn" href="<?php echo esc_url( $lh['edit_url'] ); ?>"><?php echo ih_icon( 'add', 14 ); ?><span>Edit</span></a>

							<a class="ih-btn ih-btn-primary ih-corp-health-btn" href="<?php echo esc_url( $lh['view_url'] ); ?>"><?php echo ih_icon( 'eye', 14, '#fff' ); ?><span>View</span></a>

						</div>

					</li>

					<?php endforeach; ?>

				</ul>

				<?php else : ?>

				<p class="csub ih-corp-health-empty">No listings yet. Use <a href="<?php echo esc_url( admin_url( 'admin.php?page=ih-user-add-tool' ) ); ?>">Add Tool</a> or <a href="<?php echo esc_url( admin_url( 'admin.php?page=ih-user-add-machine' ) ); ?>">Add Machine</a> to create your first.</p>

				<?php endif; ?>

			</section>



			<div class="ih-grid-2 ih-corp-main-grid">

				<div class="ih-section ih-corp-listings" id="ihMyListings">

					<div class="ih-section-head">

						<div><h3>My Listings</h3><div class="csub ih-corp-desktop-only">Moulds &amp; machines you&rsquo;ve listed. Pending items await admin approval.</div></div>

						<a class="ih-link" href="#ihBrowseListings">View all →</a>

					</div>

					<div class="ih-tabs ih-corp-listing-tabs" role="tablist">

						<button type="button" class="ih-tab active" data-filter="all">All</button>

						<button type="button" class="ih-tab" data-filter="tool">Moulds</button>

						<button type="button" class="ih-tab" data-filter="machine">Machines</button>

						<button type="button" class="ih-tab" data-filter="pending">Pending</button>

						<button type="button" class="ih-tab ih-corp-tab-desktop-only" data-filter="approved">Approved</button>

					</div>

					<div class="ih-grid-cards ih-corp-listing-grid">

						<?php

						$shown = 0;

						foreach ( $tools as $m ) {

							$kind          = 'tool';

							$anon          = false;

							$rd_detail_url = admin_url( 'admin.php?page=ih-user-view-tool&id=' . (int) $m['id'] );

							$rd_msg_url    = $messages_url;

							include $partials . 'ih-rd-card.php';

							$shown++;

						}

						foreach ( $machines as $m ) {

							$kind          = 'machine';

							$anon          = false;

							$rd_detail_url = admin_url( 'admin.php?page=ih-user-view-machine&id=' . (int) $m['id'] );

							$rd_msg_url    = $messages_url;

							include $partials . 'ih-rd-card.php';

							$shown++;

						}

						if ( ! $shown ) {

							echo '<p class="csub">No listings yet. Use <a href="' . esc_url( admin_url( 'admin.php?page=ih-user-add-tool' ) ) . '">Add Tool</a> or <a href="' . esc_url( admin_url( 'admin.php?page=ih-user-add-machine' ) ) . '">Add Machine</a> to create your first.</p>';

						}

						?>

					</div>

				</div>



				<div class="ih-rail ih-corp-rail">

					<div class="ih-section ih-corp-status-panel">

						<div class="ih-section-head"><div><h3>Listing Status</h3><div class="csub">Live split across your <?php echo (int) $c_total_listings; ?> listings.</div></div></div>

						<div class="ih-donut-wrap" data-donut-r="58">

							<div class="ih-donut">

								<svg width="150" height="150" viewBox="0 0 150 150"><?php echo ih_rd_donut_svg( $ls_segs ); ?></svg>

								<div class="center">

									<b class="ih-donut-pct ih-corp-donut-pct ih-corp-donut-desktop"><?php echo (int) $ls_pct; ?>%</b>

									<span class="ih-corp-donut-label ih-corp-donut-desktop"><?php echo esc_html( $ls_center_lbl ); ?></span>

									<b class="ih-donut-pct ih-corp-donut-pct ih-corp-donut-mobile"><?php echo (int) $ls_approved_pct; ?>%</b>

									<span class="ih-corp-donut-label ih-corp-donut-mobile">APPROVED</span>

								</div>

							</div>

							<div class="ih-legend">

								<div class="lg" data-status="approved"><span class="d"></span>Approved<b class="ih-legend-count"><?php echo sprintf( '%02d', $c_approved ); ?></b></div>

								<div class="lg" data-status="pending"><span class="d"></span>Pending<b class="ih-legend-count"><?php echo sprintf( '%02d', $c_pending_ls ); ?></b></div>

								<div class="lg" data-status="completed"><span class="d"></span>Completed<b class="ih-legend-count"><?php echo sprintf( '%02d', $c_completed ); ?></b></div>

								<div class="lg" data-status="rejected"><span class="d"></span>Rejected<b class="ih-legend-count"><?php echo sprintf( '%02d', $c_rejected ); ?></b></div>

							</div>

						</div>

					</div>



					<div class="ih-section ih-corp-approvals" id="ihEnquiriesSection">

						<div class="ih-section-head"><div><h3>Requests &amp; Approvals</h3><div class="csub ih-corp-desktop-only">Admin reviews every request you raise.</div></div></div>

						<div class="ih-meta-row ih-corp-meta-row ih-corp-approval-row">

							<span class="ih-corp-approval-icon" aria-hidden="true"><?php echo ih_icon( 'listings', 16 ); ?></span>

							<span class="ih-corp-approval-copy"><strong>Listing approvals</strong><br><span class="csub"><?php echo (int) $c_pending_listings; ?> listing<?php echo $c_pending_listings === 1 ? '' : 's'; ?> awaiting sign-off</span></span>

							<?php if ( $c_pending_listings > 0 ) : ?>

								<span class="ih-ref-chip ih-corp-pending-chip"><?php echo (int) $c_pending_listings; ?> PENDING</span>

							<?php else : ?>

								<?php echo ih_status_badge( 'approved' ); ?>

							<?php endif; ?>

						</div>

						<div class="ih-meta-row ih-corp-meta-row ih-corp-approval-row">

							<span class="ih-corp-approval-icon" aria-hidden="true"><?php echo ih_icon( 'user', 16 ); ?></span>

							<span class="ih-corp-approval-copy"><strong>Contact access</strong><br><span class="csub">Users requesting your details</span></span>

							<?php if ( $contact_incoming > 0 ) : ?>

								<span class="ih-ref-chip ih-corp-new-chip"><?php echo (int) $contact_incoming; ?> NEW</span>

							<?php else : ?>

								<span class="ih-dash-status is-approved">Clear</span>

							<?php endif; ?>

						</div>

						<div class="ih-corp-approval-stats ih-corp-desktop-only">

							<?php if ( $profile_views_delta > 0 ) : ?>

								<span class="ih-corp-stat-chip"><span class="ih-corp-stat-up">+<?php echo (int) $profile_views_delta; ?></span> Profile views</span>

							<?php endif; ?>

							<?php if ( $approved_week > 0 ) : ?>

								<span class="ih-corp-stat-chip">Approved by admin this week · <strong><?php echo (int) $approved_week; ?></strong></span>

							<?php endif; ?>

						</div>

					</div>

				</div>

			</div>



			<div class="ih-section ih-corp-messages ih-corp-desktop-only" id="ihRecentMessages">

				<div class="ih-section-head">

					<div>

						<h3>Recent Messages</h3>

						<div class="csub"><?php echo (int) $unread_msgs; ?> unread · <?php echo (int) $online_contacts; ?> contact<?php echo $online_contacts === 1 ? '' : 's'; ?> online</div>

					</div>

					<a class="ih-link" href="<?php echo esc_url( $messages_url ); ?>">Open inbox →</a>

				</div>

				<div class="ih-corp-msg-list">

					<?php if ( $msg_threads ) : ?>

						<?php foreach ( $msg_threads as $thread ) :

							$thread_id   = (int) ( $thread['id'] ?? 0 );

							$unread      = (int) ( $thread['unread'] ?? 0 );

							$preview     = wp_trim_words( wp_strip_all_tags( (string) ( $thread['last_message'] ?? '' ) ), 14, '…' );

							$time_label  = ih_corp_dash_time_ago( $thread['last_time'] ?? '' );

							$thread_url  = add_query_arg( 'thread', $thread_id, $messages_url );

							$is_online   = ! empty( $thread['last_time'] ) && $thread['last_time'] >= $online_cutoff;

							?>

							<a class="ih-corp-msg-item<?php echo $unread ? ' is-unread' : ''; ?>" href="<?php echo esc_url( $thread_url ); ?>">

								<span class="ih-corp-msg-av" style="--ih-corp-av-bg:<?php echo esc_attr( $company_color ); ?>">A</span>

								<span class="ih-corp-msg-body">

									<span class="ih-corp-msg-top">

										<strong class="ih-corp-msg-name">Administrator</strong>

										<span class="ih-corp-msg-time"><?php echo esc_html( $time_label ); ?></span>

									</span>

									<span class="ih-corp-msg-preview"><?php echo esc_html( $preview ?: 'No messages yet' ); ?></span>

								</span>

								<?php if ( $unread > 0 ) : ?>

									<span class="ih-corp-msg-badge"><?php echo (int) $unread; ?></span>

								<?php elseif ( $is_online ) : ?>

									<span class="ih-corp-msg-online" title="<?php esc_attr_e( 'Online', 'insight-hub-dashboard' ); ?>"></span>

								<?php endif; ?>

							</a>

						<?php endforeach; ?>

					<?php else : ?>

						<p class="csub ih-corp-msg-empty">No messages yet. <a href="<?php echo esc_url( $messages_url ); ?>">Start a conversation</a> with admin.</p>

					<?php endif; ?>

				</div>

			</div>



			<div class="ih-section ih-corp-browse ih-corp-desktop-only" id="ihBrowseListings">

				<div class="ih-section-head">

					<div>

						<div class="ih-corp-browse-title">

							<h3 class="ih-corp-browse-heading">Browse All Listings</h3>

							<span class="ih-ref-chip ih-corp-hidden-chip">NAMES HIDDEN</span>

						</div>

						<div class="csub">Every corporation&rsquo;s moulds &amp; machines by Tool / Machine / User ID — no names exposed.</div>

					</div>

				</div>

				<div class="ih-tabs ih-corp-browse-tabs" role="tablist">

					<button type="button" class="ih-tab active" data-browse-filter="all">All</button>

					<button type="button" class="ih-tab" data-browse-filter="tool">Moulds</button>

					<button type="button" class="ih-tab" data-browse-filter="machine">Machines</button>

				</div>

				<div class="ih-grid-cards ih-corp-browse-grid">

					<?php

					foreach ( $browse as $row ) {

						$m          = $row;

						$kind       = sanitize_key( $row['_type'] ?? 'tool' );

						$anon       = true;

						$listing_id = (int) ih_val( $row, 'id', 0 );

						if ( $kind === 'machine' ) {

							$rd_detail_url = admin_url( 'admin.php?page=ih-user-view-machine&id=' . $listing_id );

						} else {

							$rd_detail_url = admin_url( 'admin.php?page=ih-user-view-tool&id=' . $listing_id );

						}

						$rd_msg_url = $messages_url;

						include $partials . 'ih-rd-card.php';

					}

					if ( ! $browse ) {

						echo '<p class="csub">No public listings to browse yet.</p>';

					}

					?>

				</div>

			</div>



			<div class="ih-section ih-corp-requests">

				<div class="ih-section-head">

					<div><h3>My Requests</h3><div class="csub">Every request gets a unique ID — track approval status here.</div></div>

					<span class="ih-ref-chip"><?php echo count( $my_requests ); ?> TOTAL · <?php echo (int) $open_requests; ?> OPEN</span>

				</div>

				<div class="ih-table-wrap ih-rd-table-wrap ih-corp-req-desktop">

					<table class="ih-table ih-table-pro ih-rd-table">

						<thead>

							<tr>

								<th class="ih-rd-table__th">Request ID</th>

								<th class="ih-rd-table__th">Type</th>

								<th class="ih-rd-table__th">Target</th>

								<th class="ih-rd-table__th">Date</th>

								<th class="ih-rd-table__th ih-rd-table__th--status right">Status</th>

							</tr>

						</thead>

						<tbody>

						<?php

						foreach ( $my_requests as $r ) :

							$intent   = function_exists( 'ih_request_intent_meta' ) ? ih_request_intent_meta( $r ) : array( 'label' => 'Request' );

							$req_type = $r['listing_type'] ?? 'request';

							$listing  = ih_request_listing_ref( $req_type, (int) ( $r['listing_id'] ?? 0 ) );

							$title    = $req_type === 'tool' ? ( $r['tool_title'] ?? '' ) : ( $r['machine_title'] ?? '' );

							if ( ! $title && ( $intent['key'] ?? '' ) === 'profile' ) {

								$title = 'Protected profile';

							}

							$target   = trim( $listing . ( $title ? ' · ' . $title : '' ) ) ?: '—';

							$req_date = $r['request_date'] ?? $r['created_at'] ?? '';

							?>

							<tr class="ih-rd-table__row">

								<td class="ih-rd-table__cell"><span class="ih-ref-chip"><?php echo esc_html( ih_request_ref( $r ) ); ?></span></td>

								<td class="ih-rd-table__cell"><strong><?php echo esc_html( $intent['label'] ?? 'Request' ); ?></strong></td>

								<td class="ih-rd-table__cell target"><span class="ih-rd-table__target"><?php echo esc_html( $target ); ?></span></td>

								<td class="ih-rd-table__cell date"><span class="ih-rd-table__date"><?php echo $req_date ? esc_html( date_i18n( 'd M y', strtotime( $req_date ) ) ) : '—'; ?></span></td>

								<td class="ih-rd-table__cell ih-rd-table__cell--status right"><?php echo ih_status_badge( $r['status'] ?? 'pending' ); ?></td>

							</tr>

						<?php endforeach; ?>

						<?php if ( ! $my_requests ) : ?>

							<tr><td colspan="5" class="csub ih-corp-req-empty">No requests yet.</td></tr>

						<?php endif; ?>

						</tbody>

					</table>

				</div>

				<div class="ih-corp-req-mobile-cards" aria-label="<?php esc_attr_e( 'Request cards', 'insight-hub-dashboard' ); ?>">

					<?php if ( ! $my_requests ) : ?>

						<p class="csub">No requests yet.</p>

					<?php else : ?>

						<?php foreach ( $my_requests as $r ) :

							$intent   = function_exists( 'ih_request_intent_meta' ) ? ih_request_intent_meta( $r ) : array( 'label' => 'Request' );

							$req_type = $r['listing_type'] ?? 'request';

							$listing  = ih_request_listing_ref( $req_type, (int) ( $r['listing_id'] ?? 0 ) );

							$title    = $req_type === 'tool' ? ( $r['tool_title'] ?? '' ) : ( $r['machine_title'] ?? '' );

							if ( ! $title && ( $intent['key'] ?? '' ) === 'profile' ) {

								$title = 'Protected profile';

							}

							$target   = trim( $listing . ( $title ? ' · ' . $title : '' ) ) ?: '—';

							$req_date = $r['request_date'] ?? $r['created_at'] ?? '';

							?>

							<article class="ih-corp-req-card">

								<div class="ih-corp-req-card-head">

									<span class="ih-ref-chip"><?php echo esc_html( ih_request_ref( $r ) ); ?></span>

									<?php echo ih_status_badge( $r['status'] ?? 'pending' ); ?>

								</div>

								<strong class="ih-corp-req-type"><?php echo esc_html( $intent['label'] ?? 'Request' ); ?></strong>

								<div class="ih-corp-req-target"><?php echo esc_html( $target ); ?></div>

								<div class="ih-corp-req-date"><?php echo $req_date ? esc_html( date_i18n( 'd M y', strtotime( $req_date ) ) ) : '—'; ?></div>

							</article>

						<?php endforeach; ?>

					<?php endif; ?>

				</div>

			</div>



			<footer class="ih-footer ih-corp-footer">

				<div class="ih-corp-footer-brand ih-corp-desktop-only">

					<span class="ih-corp-footer-av" style="--ih-corp-av-bg:<?php echo esc_attr( $company_color ); ?>"><?php echo esc_html( $company_initials ); ?></span>

					<span class="ih-corp-footer-co"><?php echo esc_html( $company_name ); ?></span>

				</div>

				<span class="ih-corp-footer-copy ih-corp-desktop-only">CORPORATION DASHBOARD · FIGMA NODE 16:6 · v<?php echo esc_html( IH_VERSION ); ?> · © <?php echo esc_html( date( 'Y' ) ); ?></span>

				<div class="ih-corp-footer-mobile ih-corp-mobile-only">

					<span class="ih-corp-footer-mobile-line">CORPORATION · v<?php echo esc_html( IH_VERSION ); ?></span>

					<span class="ih-corp-footer-mobile-line"><span class="dot"></span>STATUS · OPERATIONAL</span>

					<time class="ih-corp-footer-clock" id="ihCorpFooterClock" datetime="<?php echo esc_attr( current_time( 'c' ) ); ?>"><?php echo esc_html( date_i18n( 'D, j M' ) ); ?> · <?php echo esc_html( date_i18n( 'h:i A' ) ); ?></time>

				</div>

				<div class="ih-corp-footer-status ih-corp-desktop-only">

					<span><span class="dot"></span>SYSTEM STATUS · OPERATIONAL</span>

					<span class="ih-corp-footer-role">Administrator</span>

				</div>

			</footer>



		</div><!-- /.ih-content.ih-rd -->

	</main>

</div><!-- /.ih-body -->



</div><!-- /.ih-shell -->

<?php wp_footer(); ?>

</body>

</html>

