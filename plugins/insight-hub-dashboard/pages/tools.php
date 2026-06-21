<?php defined( 'ABSPATH' ) || exit;

$listings = ih_db_tools();
if ( ! is_array( $listings ) ) {
	$listings = array();
}

if ( ! function_exists( 'ih_tool_page_get_value' ) ) {
	function ih_tool_page_get_value( $item, $keys, $fallback = '' ) {
		foreach ( (array) $keys as $key ) {
			if ( is_array( $item ) && isset( $item[ $key ] ) && $item[ $key ] !== '' && $item[ $key ] !== null ) {
				return $item[ $key ];
			}
			if ( is_object( $item ) && isset( $item->{$key} ) && $item->{$key} !== '' && $item->{$key} !== null ) {
				return $item->{$key};
			}
		}
		return $fallback;
	}
}

if ( ! function_exists( 'ih_tool_page_materials' ) ) {
	function ih_tool_page_materials( $item ) {
		$materials = array();
		$mat       = trim( (string) ih_tool_page_get_value( $item, array( 'material' ), '' ) );
		if ( $mat !== '' ) {
			$materials[] = $mat;
		}
		if ( ! empty( $item['tolerance_pp'] ) ) {
			$materials[] = 'PP';
		}
		if ( ! empty( $item['tolerance_pe'] ) ) {
			$materials[] = 'PE';
		}
		return array_values( array_unique( $materials ) );
	}
}

if ( ! function_exists( 'ih_tool_page_grades' ) ) {
	function ih_tool_page_grades( $item ) {
		$grades = array();
		if ( ( $item['medical_grade'] ?? '' ) === 'Yes' ) {
			$grades[] = 'medical grade';
		}
		if ( ( $item['food_grade'] ?? '' ) === 'Yes' ) {
			$grades[] = 'food grade';
		}
		if ( stripos( (string) ( $item['material_grade'] ?? '' ), 'recycled' ) !== false ) {
			$grades[] = 'recycled';
		}
		return $grades;
	}
}

$total_tools     = is_array( $listings ) ? count( $listings ) : 0;
$available_tools = 0;
$multi_cavity    = 0;

if ( ! empty( $listings ) && is_array( $listings ) ) {
	foreach ( $listings as $tool_row ) {
		$status = function_exists( 'ih_listing_status_meta' )
			? ( ih_listing_status_meta( $tool_row )['key'] ?? 'pending' )
			: strtolower( (string) ih_tool_page_get_value( $tool_row, array( 'status', 'availability', 'listing_status', 'available' ), '' ) );

		if ( $status === 'available' || strpos( $status, 'approved' ) !== false || $status === 'active' || $status === '1' ) {
			$available_tools++;
		}

		$cavities = (int) ih_tool_page_get_value( $tool_row, array( 'num_cavities_spec' ), 0 );
		if ( $cavities > 1 ) {
			$multi_cavity++;
		}
	}
}

$ih_tools_current_user_id = get_current_user_id();
$ih_tools_is_admin        = current_user_can( 'manage_options' ) || current_user_can( 'edit_users' );

$ih_tools_filter_types     = array();
$ih_tools_filter_cavities  = array();
$ih_tools_filter_materials = array();
$ih_tools_filter_grades    = array();
$ih_tools_filter_statuses  = array();
$ih_tools_filter_more      = array();

if ( ! empty( $listings ) && is_array( $listings ) ) {
	foreach ( $listings as $tool_row ) {
		$type_val = trim( (string) ih_tool_page_get_value( $tool_row, array( 'mould_type', 'tool_type', 'type', 'runner_type' ), '' ) );
		if ( $type_val !== '' ) {
			$ih_tools_filter_types[ strtolower( $type_val ) ] = $type_val;
		}

		$cavity_val = (int) ih_tool_page_get_value( $tool_row, array( 'num_cavities_spec' ), 0 );
		if ( $cavity_val > 0 ) {
			$cavity_label = $cavity_val . ' Cavities';
			$ih_tools_filter_cavities[ (string) $cavity_val ] = $cavity_label;
		}

		foreach ( ih_tool_page_materials( $tool_row ) as $mat ) {
			$mat = trim( (string) $mat );
			if ( $mat !== '' ) {
				$ih_tools_filter_materials[ strtolower( $mat ) ] = $mat;
			}
		}

		foreach ( ih_tool_page_grades( $tool_row ) as $grade_key ) {
			$grade_label = ucwords( $grade_key );
			$ih_tools_filter_grades[ $grade_key ] = $grade_label;
		}

		$status_val = function_exists( 'ih_listing_status_meta' )
			? ( ih_listing_status_meta( $tool_row )['label'] ?? '' )
			: (string) ih_tool_page_get_value( $tool_row, array( 'status', 'availability', 'listing_status' ), '' );
		if ( $status_val !== '' ) {
			$ih_tools_filter_statuses[ strtolower( $status_val ) ] = $status_val;
		}

		$location_val = trim( (string) ih_tool_page_get_value( $tool_row, array( 'location', 'city', 'country' ), '' ) );
		if ( $location_val !== '' ) {
			$ih_tools_filter_more[ 'loc:' . strtolower( $location_val ) ] = $location_val;
		}

		$condition_val = trim( (string) ih_tool_page_get_value( $tool_row, array( 'mould_condition' ), '' ) );
		if ( $condition_val !== '' ) {
			$ih_tools_filter_more[ 'cond:' . strtolower( $condition_val ) ] = $condition_val;
		}
	}
}

asort( $ih_tools_filter_types );
ksort( $ih_tools_filter_cavities, SORT_NUMERIC );
asort( $ih_tools_filter_materials );
asort( $ih_tools_filter_grades );
asort( $ih_tools_filter_statuses );
asort( $ih_tools_filter_more );

ob_start();
?>
<div class="ih-rd ih-admin ih-tools-listings">
<div class="ih-page-header ih-tools-page-header">
  <div>
    <p class="ih-tools-eyebrow"><span class="dot" aria-hidden="true"></span>ALL LISTINGS · TOOLS</p>
    <h2 class="ih-page-title">Tools</h2>
    <p class="ih-page-sub">See and manage all listed mould tools.</p>
  </div>
</div>

<div class="ih-tools-hero">
  <div class="ih-tool-stat-card">
    <span><span class="ih-stat-label-long">Total Listings</span><span class="ih-stat-label-short">Total</span></span>
    <strong><?php echo (int) $total_tools; ?></strong>
    <small>All tool cards</small>
  </div>
  <div class="ih-tool-stat-card is-green">
    <span><span class="ih-stat-label-long">Available</span><span class="ih-stat-label-short">Available</span></span>
    <strong><?php echo (int) $available_tools; ?></strong>
    <small>Ready or approved</small>
  </div>
  <div class="ih-tool-stat-card is-blue">
    <span><span class="ih-stat-label-long">Multi-Cavity</span><span class="ih-stat-label-short">Multi-Cav</span></span>
    <strong><?php echo (int) $multi_cavity; ?></strong>
    <small>High-output moulds</small>
  </div>
  <div class="ih-tool-stat-card is-rose">
    <span><span class="ih-stat-label-long">Favourites</span><span class="ih-stat-label-short">Favourites</span></span>
    <strong id="toolFavouriteCount">0</strong>
    <small>Saved by viewer</small>
  </div>
</div>

<div class="ih-card ih-tools-page">
  <div class="ih-card-head ih-tools-head">
    <span class="ih-card-title">Listed Tools <span class="ih-count-badge" id="toolVisibleCount"><?php echo (int) $total_tools; ?></span></span>
    <div class="ih-head-actions">
      <div class="ih-search-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" id="toolSearch" placeholder="Search tools…" class="ih-search-input">
      </div>
      <div class="ih-custom-select" id="ihToolDateWrap">
        <button type="button" class="ih-custom-select-btn" onclick="ihToggleCustomSelect('ihToolDateWrap')">
          <span id="ihToolDateLabel">All Time</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="ih-custom-select-menu hidden">
          <div class="ih-custom-select-option" data-value="month" onclick="ihPickCustomSelect('ihToolDateWrap','month','This Month')">
            <span>This Month</span>
            <svg class="ih-check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <div class="ih-custom-select-option" data-value="year" onclick="ihPickCustomSelect('ihToolDateWrap','year','This Year')">
            <span>This Year</span>
            <svg class="ih-check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <div class="ih-custom-select-option selected" data-value="all" onclick="ihPickCustomSelect('ihToolDateWrap','all','All Time')">
            <span>All Time</span>
            <svg class="ih-check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
        </div>
      </div>
      <a href="<?php echo esc_url( admin_url( 'admin.php?page=ih-add-tool' ) ); ?>" class="ih-btn ih-btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Tool
      </a>
    </div>
  </div>

  <div class="ih-tools-filters" id="ihToolsFilters">
    <div class="ih-tools-mobile-filter-bar" id="ihToolsMobileFilterBar">
      <button type="button" class="ih-tools-mobile-filters-btn" id="ihToolsMobileFiltersBtn" aria-expanded="false" aria-controls="ihToolsDesktopFilters">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15" aria-hidden="true"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
        <span>Filters</span>
        <span class="ih-tools-mobile-filters-count" id="ihToolsMobileFiltersCount" hidden>0</span>
      </button>
      <div class="ih-tools-mobile-filter-scroll" role="group" aria-label="Quick filters">
        <button type="button" class="ih-tools-filter-pill is-active" data-quick-filter="all">All</button>
        <?php
        $ih_tools_hot_runner_key = '';
        foreach ( $ih_tools_filter_types as $type_key => $type_label ) {
          if ( strpos( $type_key, 'hot runner' ) !== false || strpos( strtolower( $type_label ), 'hot runner' ) !== false ) {
            $ih_tools_hot_runner_key = $type_key;
            break;
          }
        }
        if ( $ih_tools_hot_runner_key !== '' ) :
        ?>
        <button type="button" class="ih-tools-filter-pill" data-quick-filter="type:<?php echo esc_attr( $ih_tools_hot_runner_key ); ?>">Hot Runner</button>
        <?php else : ?>
        <button type="button" class="ih-tools-filter-pill" data-quick-filter="type:hot runner">Hot Runner</button>
        <?php endif; ?>
        <button type="button" class="ih-tools-filter-pill" data-quick-filter="multicav:1">Multi-cav</button>
        <button type="button" class="ih-tools-filter-pill" data-quick-filter="status:available">Available</button>
        <?php foreach ( $ih_tools_filter_grades as $grade_key => $grade_label ) : ?>
        <button type="button" class="ih-tools-filter-pill" data-quick-filter="grade:<?php echo esc_attr( $grade_key ); ?>"><?php echo esc_html( $grade_label ); ?></button>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="ih-tools-filter-row ih-tools-desktop-filters" id="ihToolsDesktopFilters">
      <div class="ih-tools-filter-label">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" aria-hidden="true"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
        <span>Filters</span>
      </div>
      <div class="ih-custom-select ih-tools-filter-select" id="ihToolFilterType" data-filter-key="type">
        <button type="button" class="ih-custom-select-btn ih-tools-filter-btn" onclick="ihToggleCustomSelect('ihToolFilterType')">
          <span>Type</span>
          <span class="ih-tools-filter-count" id="ihToolFilterTypeCount" hidden>0</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="ih-custom-select-menu hidden">
          <?php foreach ( $ih_tools_filter_types as $type_key => $type_label ) : ?>
          <label class="ih-custom-select-option ih-tools-filter-option">
            <input type="checkbox" class="ih-tools-filter-input" data-filter="type" value="<?php echo esc_attr( $type_key ); ?>">
            <span><?php echo esc_html( $type_label ); ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="ih-custom-select ih-tools-filter-select" id="ihToolFilterCavities" data-filter-key="cavities">
        <button type="button" class="ih-custom-select-btn ih-tools-filter-btn" onclick="ihToggleCustomSelect('ihToolFilterCavities')">
          <span>Cavities</span>
          <span class="ih-tools-filter-count" id="ihToolFilterCavitiesCount" hidden>0</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="ih-custom-select-menu hidden">
          <?php foreach ( $ih_tools_filter_cavities as $cavity_key => $cavity_label ) : ?>
          <label class="ih-custom-select-option ih-tools-filter-option">
            <input type="checkbox" class="ih-tools-filter-input" data-filter="cavities" value="<?php echo esc_attr( $cavity_key ); ?>">
            <span><?php echo esc_html( $cavity_label ); ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="ih-custom-select ih-tools-filter-select" id="ihToolFilterMaterial" data-filter-key="material">
        <button type="button" class="ih-custom-select-btn ih-tools-filter-btn" onclick="ihToggleCustomSelect('ihToolFilterMaterial')">
          <span>Material</span>
          <span class="ih-tools-filter-count" id="ihToolFilterMaterialCount" hidden>0</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="ih-custom-select-menu hidden">
          <?php foreach ( $ih_tools_filter_materials as $mat_key => $mat_label ) : ?>
          <label class="ih-custom-select-option ih-tools-filter-option">
            <input type="checkbox" class="ih-tools-filter-input" data-filter="material" value="<?php echo esc_attr( $mat_key ); ?>">
            <span><?php echo esc_html( $mat_label ); ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="ih-custom-select ih-tools-filter-select" id="ihToolFilterGrade" data-filter-key="grade">
        <button type="button" class="ih-custom-select-btn ih-tools-filter-btn" onclick="ihToggleCustomSelect('ihToolFilterGrade')">
          <span>Grade</span>
          <span class="ih-tools-filter-count" id="ihToolFilterGradeCount" hidden>0</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="ih-custom-select-menu hidden">
          <?php foreach ( $ih_tools_filter_grades as $grade_key => $grade_label ) : ?>
          <label class="ih-custom-select-option ih-tools-filter-option">
            <input type="checkbox" class="ih-tools-filter-input" data-filter="grade" value="<?php echo esc_attr( $grade_key ); ?>">
            <span><?php echo esc_html( $grade_label ); ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="ih-custom-select ih-tools-filter-select" id="ihToolFilterStatus" data-filter-key="status">
        <button type="button" class="ih-custom-select-btn ih-tools-filter-btn" onclick="ihToggleCustomSelect('ihToolFilterStatus')">
          <span>Status</span>
          <span class="ih-tools-filter-count" id="ihToolFilterStatusCount" hidden>0</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="ih-custom-select-menu hidden">
          <?php foreach ( $ih_tools_filter_statuses as $status_key => $status_label ) : ?>
          <label class="ih-custom-select-option ih-tools-filter-option">
            <input type="checkbox" class="ih-tools-filter-input" data-filter="status" value="<?php echo esc_attr( $status_key ); ?>">
            <span><?php echo esc_html( $status_label ); ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="ih-custom-select ih-tools-filter-select" id="ihToolFilterMore" data-filter-key="more">
        <button type="button" class="ih-custom-select-btn ih-tools-filter-btn" onclick="ihToggleCustomSelect('ihToolFilterMore')">
          <span>More</span>
          <span class="ih-tools-filter-count" id="ihToolFilterMoreCount" hidden>0</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="ih-custom-select-menu hidden">
          <?php foreach ( $ih_tools_filter_more as $more_key => $more_label ) : ?>
          <label class="ih-custom-select-option ih-tools-filter-option">
            <input type="checkbox" class="ih-tools-filter-input" data-filter="more" value="<?php echo esc_attr( $more_key ); ?>">
            <span><?php echo esc_html( $more_label ); ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="ih-custom-select ih-tools-filter-select ih-tools-filter-sort" id="ihToolSortWrap">
        <button type="button" class="ih-custom-select-btn ih-tools-filter-btn" onclick="ihToggleCustomSelect('ihToolSortWrap')">
          <span id="ihToolSortLabel">Sort: Newest</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="ih-custom-select-menu hidden">
          <div class="ih-custom-select-option selected" data-value="newest" onclick="ihPickToolSort('newest','Sort: Newest')"><span>Newest</span></div>
          <div class="ih-custom-select-option" data-value="oldest" onclick="ihPickToolSort('oldest','Sort: Oldest')"><span>Oldest</span></div>
          <div class="ih-custom-select-option" data-value="name" onclick="ihPickToolSort('name','Sort: Name')"><span>Name</span></div>
        </div>
      </div>
    </div>
    <div class="ih-tools-active-row" id="ihToolsActiveRow" hidden>
      <span class="ih-tools-active-label">Active:</span>
      <div class="ih-tools-active-chips" id="ihToolsActiveChips"></div>
      <button type="button" class="ih-tools-clear-all" id="ihToolsClearAll">Clear all</button>
      <span class="ih-tools-results-count ih-tools-results-count-inline" id="ihToolsResultsCount"></span>
    </div>
    <div class="ih-tools-mobile-results-row">
      <div class="ih-tools-active-chips ih-tools-mobile-active-chips" id="ihToolsMobileActiveChips"></div>
      <span class="ih-tools-results-count" id="ihToolsMobileResultsCount"></span>
    </div>
  </div>

  <div class="ih-listing-grid ih-tool-grid-boost" id="toolsGrid">
    <?php foreach ( $listings as $m ) : ?>
      <?php
        $tool_id_for_card = ih_tool_page_get_value( $m, array( 'id', 'tool_id', 'listing_id', 'ID' ), '' );
        $tool_owner_id    = (int) ih_tool_page_get_value( $m, array( 'user_id', 'owner_id', 'author_id', 'created_by', 'submitted_by', 'wp_user_id', 'post_author' ), 0 );
        $can_manage_tool  = $ih_tools_is_admin || ( $tool_owner_id && $tool_owner_id === (int) $ih_tools_current_user_id );
        $tool_type_val    = strtolower( trim( (string) ih_tool_page_get_value( $m, array( 'mould_type', 'tool_type', 'type', 'runner_type' ), '' ) ) );
        $tool_cavity_val  = (string) (int) ih_tool_page_get_value( $m, array( 'num_cavities_spec' ), 0 );
        $tool_status_meta = function_exists( 'ih_listing_status_meta' ) ? ih_listing_status_meta( $m ) : array( 'label' => '' );
        $tool_status_val  = strtolower( (string) ( $tool_status_meta['label'] ?? '' ) );
        $tool_materials     = ih_tool_page_materials( $m );
        $tool_materials_val = implode( ',', array_map( 'strtolower', array_map( 'trim', $tool_materials ) ) );
        $tool_grades        = ih_tool_page_grades( $m );
        $tool_grades_val    = implode( ',', $tool_grades );
        $tool_location      = strtolower( trim( (string) ih_tool_page_get_value( $m, array( 'location', 'city', 'country' ), '' ) ) );
        $tool_condition     = strtolower( trim( (string) ih_tool_page_get_value( $m, array( 'mould_condition' ), '' ) ) );
        $tool_more_val      = implode( ',', array_filter( array(
          $tool_location ? 'loc:' . $tool_location : '',
          $tool_condition ? 'cond:' . $tool_condition : '',
        ) ) );
        $tool_listing_ts  = ! empty( $m['listing_date'] ) ? strtotime( (string) $m['listing_date'] ) : 0;
        $tool_title_val   = strtolower( trim( (string) ih_tool_page_get_value( $m, array( 'title', 'part_name', 'name' ), '' ) ) );
      ?>
      <div class="ih-tool-listing-shell"
           data-tool-id="<?php echo esc_attr( $tool_id_for_card ); ?>"
           data-owner-id="<?php echo esc_attr( $tool_owner_id ); ?>"
           data-can-manage="<?php echo $can_manage_tool ? '1' : '0'; ?>"
           data-is-admin="<?php echo $ih_tools_is_admin ? '1' : '0'; ?>"
           data-tool-type="<?php echo esc_attr( $tool_type_val ); ?>"
           data-tool-cavities="<?php echo esc_attr( $tool_cavity_val ); ?>"
           data-tool-status="<?php echo esc_attr( $tool_status_val ); ?>"
           data-tool-materials="<?php echo esc_attr( $tool_materials_val ); ?>"
           data-tool-grades="<?php echo esc_attr( $tool_grades_val ); ?>"
           data-tool-more="<?php echo esc_attr( $tool_more_val ); ?>"
           data-listing-ts="<?php echo esc_attr( (string) $tool_listing_ts ); ?>"
           data-tool-title="<?php echo esc_attr( $tool_title_val ); ?>"
           data-edit-url="<?php echo esc_url( admin_url( 'admin.php?page=ih-edit-tool&tool_id=' . (int) $tool_id_for_card ) ); ?>">
        <?php include __DIR__ . '/partials/tool-card.php'; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="ih-tools-pagination" id="toolsPagination" aria-label="Tools pagination">
    <div class="ih-tools-page-info" id="toolsPageInfo">Showing tools</div>
    <div class="ih-tools-page-controls">
      <button type="button" class="ih-tools-page-btn" id="toolsPrevPage" aria-label="Previous page"><span class="ih-page-btn-short">‹</span><span class="ih-page-btn-long">‹ Prev</span></button>
      <div class="ih-tools-page-numbers" id="toolsPageNumbers"></div>
      <button type="button" class="ih-tools-page-btn" id="toolsNextPage" aria-label="Next page"><span class="ih-page-btn-short">›</span><span class="ih-page-btn-long">Next ›</span></button>
    </div>
  </div>

  <div class="ih-tools-empty" id="toolsEmptyState" hidden>
    <div class="ih-tools-empty-icon">🔍</div>
    <strong>No tools found</strong>
    <span>Try another tool name, owner, material, location or type.</span>
  </div>
</div>

<footer class="ih-tools-page-footer" aria-label="Tools page footer">
  <span class="ih-tools-footer-meta">TOOLS · v<?php echo esc_html( IH_VERSION ); ?></span>
  <span class="ih-tools-footer-status"><span class="dot" aria-hidden="true"></span>OPERATIONAL</span>
</footer>

<script id="ih-tools-redesign-v2-js">
(function(){
  var search = document.getElementById('toolSearch');
  var grid = document.getElementById('toolsGrid');
  var count = document.getElementById('toolVisibleCount');
  var empty = document.getElementById('toolsEmptyState');
  var pagination = document.getElementById('toolsPagination');
  var pageInfo = document.getElementById('toolsPageInfo');
  var prevBtn = document.getElementById('toolsPrevPage');
  var nextBtn = document.getElementById('toolsNextPage');
  var pageNumbers = document.getElementById('toolsPageNumbers');
  var favCount = document.getElementById('toolFavouriteCount');
  var activeRow = document.getElementById('ihToolsActiveRow');
  var activeChips = document.getElementById('ihToolsActiveChips');
  var mobileActiveChips = document.getElementById('ihToolsMobileActiveChips');
  var clearAllBtn = document.getElementById('ihToolsClearAll');
  var resultsCount = document.getElementById('ihToolsResultsCount');
  var mobileResultsCount = document.getElementById('ihToolsMobileResultsCount');
  var mobileFiltersBtn = document.getElementById('ihToolsMobileFiltersBtn');
  var mobileFiltersCount = document.getElementById('ihToolsMobileFiltersCount');
  var desktopFilters = document.getElementById('ihToolsDesktopFilters');
  var sortLabel = document.getElementById('ihToolSortLabel');
  var sortMode = 'newest';
  var dateMode = 'all';

  function isMobileLayout(){
    return (window.innerWidth || document.documentElement.clientWidth || 1400) <= 768;
  }

  function totalActiveFilterCount(){
    return Object.keys(activeFilters).reduce(function(sum, key){ return sum + activeFilters[key].length; }, 0);
  }

  var activeFilters = { type: [], cavities: [], material: [], grade: [], status: [], more: [] };
  var filterLabels = { type: {}, cavities: {}, material: {}, grade: {}, status: {}, more: {} };
  var virtualAvailableFilter = false;
  var virtualMultiCavFilter = false;

  function renderActiveChips(container){
    if(!container) return;
    container.innerHTML = '';
    Object.keys(activeFilters).forEach(function(key){
      activeFilters[key].forEach(function(val){
        var chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'ih-tools-active-chip';
        chip.setAttribute('data-filter', key);
        chip.setAttribute('data-value', val);
        chip.innerHTML = '<span>' + (filterLabels[key][val] || val) + '</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="11" height="11" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>';
        chip.addEventListener('click', function(){
          var selector = '.ih-tools-filter-input[data-filter="' + key + '"][value="' + val.replace(/"/g, '\\"') + '"]';
          var input = document.querySelector(selector);
          if(input) input.checked = false;
          syncFilterState();
          applyToolSearch(true);
        });
        container.appendChild(chip);
      });
    });
    if(virtualAvailableFilter){
      var availChip = document.createElement('button');
      availChip.type = 'button';
      availChip.className = 'ih-tools-active-chip';
      availChip.setAttribute('data-filter', 'status');
      availChip.setAttribute('data-value', 'available');
      availChip.innerHTML = '<span>Available</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="11" height="11" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>';
      availChip.addEventListener('click', function(){
        virtualAvailableFilter = false;
        syncFilterState();
        applyToolSearch(true);
      });
      container.appendChild(availChip);
    }
    if(virtualMultiCavFilter){
      var cavChip = document.createElement('button');
      cavChip.type = 'button';
      cavChip.className = 'ih-tools-active-chip';
      cavChip.setAttribute('data-filter', 'multicav');
      cavChip.setAttribute('data-value', '1');
      cavChip.innerHTML = '<span>Multi-cav</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="11" height="11" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>';
      cavChip.addEventListener('click', function(){
        virtualMultiCavFilter = false;
        syncFilterState();
        applyToolSearch(true);
      });
      container.appendChild(cavChip);
    }
  }

  function syncQuickFilterPills(){
    var totalActive = totalActiveFilterCount() + (virtualAvailableFilter ? 1 : 0) + (virtualMultiCavFilter ? 1 : 0);
    document.querySelectorAll('.ih-tools-filter-pill').forEach(function(pill){
      var quick = pill.getAttribute('data-quick-filter') || '';
      if(quick === 'all'){
        pill.classList.toggle('is-active', totalActive === 0);
        return;
      }
      if(quick === 'multicav:1'){
        pill.classList.toggle('is-active', virtualMultiCavFilter);
        return;
      }
      var parts = quick.split(':');
      if(parts.length !== 2) return;
      var key = parts[0];
      var val = parts[1];
      if(key === 'status' && val === 'available' && virtualAvailableFilter){
        pill.classList.toggle('is-active', true);
        return;
      }
      pill.classList.toggle('is-active', activeFilters[key] && activeFilters[key].some(function(activeVal){
        return activeVal === val || (key === 'status' && val === 'available' && activeVal.indexOf('available') !== -1);
      }));
    });
  }

  function updateMobileFiltersCount(){
    var count = totalActiveFilterCount() + (virtualAvailableFilter ? 1 : 0) + (virtualMultiCavFilter ? 1 : 0);
    if(mobileFiltersCount){
      mobileFiltersCount.textContent = count;
      mobileFiltersCount.hidden = count === 0;
    }
    if(mobileFiltersBtn){
      mobileFiltersBtn.classList.toggle('has-active', count > 0);
    }
  }

  document.querySelectorAll('.ih-tools-filter-input').forEach(function(input){
    var key = input.getAttribute('data-filter');
    var val = input.value;
    var label = input.closest('label') ? (input.closest('label').querySelector('span') || {}).textContent : val;
    if(key && val){ filterLabels[key] = filterLabels[key] || {}; filterLabels[key][val] = label || val; }
    input.addEventListener('change', function(){ syncFilterState(); applyToolSearch(true); });
  });

  function syncFilterState(){
    activeFilters = { type: [], cavities: [], material: [], grade: [], status: [], more: [] };
    document.querySelectorAll('.ih-tools-filter-input:checked').forEach(function(input){
      var key = input.getAttribute('data-filter');
      if(!key || !activeFilters[key]) return;
      activeFilters[key].push(input.value);
    });

    ['type','cavities','material','grade','status','more'].forEach(function(key){
      var idMap = { type:'Type', cavities:'Cavities', material:'Material', grade:'Grade', status:'Status', more:'More' };
      var countEl = document.getElementById('ihToolFilter' + idMap[key] + 'Count');
      var wrap = document.getElementById('ihToolFilter' + idMap[key]);
      var n = activeFilters[key].length;
      if(countEl){ countEl.textContent = n; countEl.hidden = n === 0; }
      if(wrap){ wrap.classList.toggle('has-active', n > 0); }
    });

    var hasActive = Object.keys(activeFilters).some(function(k){ return activeFilters[k].length > 0; });
    if(activeRow) activeRow.hidden = !hasActive || isMobileLayout();
    renderActiveChips(activeChips);
    renderActiveChips(mobileActiveChips);
    syncQuickFilterPills();
    updateMobileFiltersCount();
  }

  if(clearAllBtn){
    clearAllBtn.addEventListener('click', function(){
      virtualAvailableFilter = false;
      virtualMultiCavFilter = false;
      document.querySelectorAll('.ih-tools-filter-input').forEach(function(input){ input.checked = false; });
      syncFilterState();
      applyToolSearch(true);
    });
  }

  document.querySelectorAll('.ih-tools-filter-pill').forEach(function(pill){
    pill.addEventListener('click', function(){
      var quick = pill.getAttribute('data-quick-filter') || '';
      if(quick === 'all'){
        virtualAvailableFilter = false;
        virtualMultiCavFilter = false;
        document.querySelectorAll('.ih-tools-filter-input').forEach(function(input){ input.checked = false; });
        syncFilterState();
        applyToolSearch(true);
        return;
      }
      if(quick === 'multicav:1'){
        virtualMultiCavFilter = !virtualMultiCavFilter;
        syncFilterState();
        applyToolSearch(true);
        return;
      }
      var parts = quick.split(':');
      if(parts.length !== 2) return;
      var key = parts[0];
      var val = parts[1];
      var selector = '.ih-tools-filter-input[data-filter="' + key + '"][value="' + val.replace(/"/g, '\\"') + '"]';
      var input = document.querySelector(selector);
      if(key === 'status' && val === 'available' && !input){
        input = Array.prototype.slice.call(document.querySelectorAll('.ih-tools-filter-input[data-filter="status"]')).find(function(el){
          return el.value.indexOf('available') !== -1;
        }) || null;
      }
      if(key === 'status' && val === 'available' && !input){
        virtualAvailableFilter = !virtualAvailableFilter;
        syncFilterState();
        applyToolSearch(true);
        return;
      }
      if(!input) return;
      if(key === 'status' && val === 'available') virtualAvailableFilter = false;
      input.checked = !input.checked;
      syncFilterState();
      applyToolSearch(true);
    });
  });

  if(mobileFiltersBtn && desktopFilters){
    mobileFiltersBtn.addEventListener('click', function(){
      var expanded = desktopFilters.classList.toggle('is-expanded');
      mobileFiltersBtn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    });
  }

  window.ihPickToolSort = function(value, label){
    sortMode = value || 'newest';
    if(sortLabel) sortLabel.textContent = label || 'Sort: Newest';
    ihPickCustomSelect('ihToolSortWrap', value, label);
    applyToolSearch(false);
  };

  function matchesDate(card){
    if(dateMode === 'all') return true;
    var ts = parseInt(card.getAttribute('data-listing-ts') || '0', 10) || 0;
    if(!ts) return true;
    var now = new Date();
    if(dateMode === 'year') return new Date(ts).getFullYear() === now.getFullYear();
    return new Date(ts).getMonth() === now.getMonth() && new Date(ts).getFullYear() === now.getFullYear();
  }

  function matchesFilters(card){
    var type = card.getAttribute('data-tool-type') || '';
    var cavities = card.getAttribute('data-tool-cavities') || '';
    var status = card.getAttribute('data-tool-status') || '';
    var materials = (card.getAttribute('data-tool-materials') || '').split(',').filter(Boolean);
    var grades = (card.getAttribute('data-tool-grades') || '').split(',').filter(Boolean);
    var more = (card.getAttribute('data-tool-more') || '').split(',').filter(Boolean);

    if(activeFilters.type.length && activeFilters.type.indexOf(type) === -1) return false;
    if(activeFilters.cavities.length && activeFilters.cavities.indexOf(cavities) === -1) return false;
    if(activeFilters.status.length){
      var statusMatch = activeFilters.status.some(function(s){
        return status === s || (s === 'available' && status.indexOf('available') !== -1);
      });
      if(!statusMatch) return false;
    } else if(virtualAvailableFilter){
      if(status.indexOf('available') === -1 && status.indexOf('approved') === -1 && status !== 'active') return false;
    }
    if(virtualMultiCavFilter){
      var cavityCount = parseInt(cavities || '0', 10) || 0;
      if(cavityCount <= 1) return false;
    }
    if(activeFilters.material.length){
      var hasMaterial = activeFilters.material.some(function(mat){ return materials.indexOf(mat) !== -1; });
      if(!hasMaterial) return false;
    }
    if(activeFilters.grade.length){
      var hasGrade = activeFilters.grade.some(function(g){ return grades.indexOf(g) !== -1; });
      if(!hasGrade) return false;
    }
    if(activeFilters.more.length){
      var hasMore = activeFilters.more.some(function(m){ return more.indexOf(m) !== -1; });
      if(!hasMore) return false;
    }
    if(!matchesDate(card)) return false;
    return true;
  }

  function sortCards(list){
    return list.slice().sort(function(a, b){
      if(sortMode === 'name'){
        return (a.getAttribute('data-tool-title') || '').localeCompare(b.getAttribute('data-tool-title') || '');
      }
      var aTs = parseInt(a.getAttribute('data-listing-ts') || '0', 10) || 0;
      var bTs = parseInt(b.getAttribute('data-listing-ts') || '0', 10) || 0;
      return sortMode === 'oldest' ? aTs - bTs : bTs - aTs;
    });
  }

  if(!grid) return;

  var cards = Array.prototype.slice.call(grid.children || []);
  var currentPage = 1;
  var pageSize = 12;
  var favStorageKey = 'insidehub_tool_favourites';

  function readFavourites(){ try { return JSON.parse(localStorage.getItem(favStorageKey) || '{}') || {}; } catch(e) { return {}; } }
  function writeFavourites(favs){ try { localStorage.setItem(favStorageKey, JSON.stringify(favs || {})); } catch(e) {} }
  function isFavourite(toolId){ return Boolean(readFavourites()[String(toolId || '')]); }
  function setFavourite(toolId, value){
    var favs = readFavourites();
    toolId = String(toolId || '');
    if(!toolId) return;
    if(value) favs[toolId] = true;
    else delete favs[toolId];
    writeFavourites(favs);
  }

  function icon(name){
    if(name === 'heart') return '<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z"/></svg>';
    if(name === 'more') return '<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/></svg>';
    if(name === 'eye') return '<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>';
    if(name === 'edit') return '<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>';
    if(name === 'trash') return '<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>';
    return '';
  }

  function findActionUrl(shell, kind){
    var links = Array.prototype.slice.call(shell.querySelectorAll('a[href]'));
    var match = null;
    links.some(function(a){
      var text = String(a.textContent || '').toLowerCase();
      var href = String(a.getAttribute('href') || '').toLowerCase();
      if(kind === 'view' && (text.indexOf('detail') !== -1 || text.indexOf('view') !== -1 || href.indexOf('detail') !== -1 || href.indexOf('view') !== -1)){ match = a.getAttribute('href'); return true; }
      if(kind === 'edit' && (text.indexOf('edit') !== -1 || href.indexOf('edit') !== -1 || href.indexOf('add-tool') !== -1)){ match = a.getAttribute('href'); return true; }
      return false;
    });
    return match || '#';
  }

  function closeMenus(){
    document.querySelectorAll('.ih-tool-enhanced-menu').forEach(function(menu){
      menu.hidden = true;
      var shell = menu.closest('.ih-tool-listing-shell');
      if(shell) shell.classList.remove('ih-menu-open');
    });
  }

  function updateFavouriteButtons(){
    var favs = readFavourites(), totalFavs = 0;
    Object.keys(favs).forEach(function(k){ if(favs[k]) totalFavs++; });
    if(favCount) favCount.textContent = totalFavs;
    cards.forEach(function(shell){
      var toolId = shell.getAttribute('data-tool-id') || '';
      var fav = isFavourite(toolId);
      var favBtn = shell.querySelector('.ih-tool-fav-btn');
      var favAction = shell.querySelector('.ih-tool-fav-action span');
      if(favBtn){ favBtn.classList.toggle('is-favourite', fav); favBtn.setAttribute('aria-label', fav ? 'Remove from favourites' : 'Add to favourites'); }
      if(favAction){ favAction.textContent = fav ? 'Remove favourite' : 'Add favourite'; }
    });
  }

  function buildControls(shell){
    if(shell.getAttribute('data-enhanced-controls') === '1') return;
    shell.setAttribute('data-enhanced-controls', '1');

    var toolId = shell.getAttribute('data-tool-id') || '';
    var canManage = shell.getAttribute('data-can-manage') === '1';
    var isAdmin = shell.getAttribute('data-is-admin') === '1';

    var favBtn = document.createElement('button');
    favBtn.type = 'button';
    favBtn.className = 'ih-tool-fav-btn';
    favBtn.innerHTML = icon('heart');
    favBtn.setAttribute('aria-label', 'Add to favourites');
    favBtn.addEventListener('click', function(e){
      e.preventDefault(); e.stopPropagation();
      setFavourite(toolId, !isFavourite(toolId));
      updateFavouriteButtons();
    });
    shell.appendChild(favBtn);

    if(canManage){
      var actionsBtn = document.createElement('button');
      actionsBtn.type = 'button';
      actionsBtn.className = 'ih-tool-actions-btn';
      actionsBtn.innerHTML = icon('more');
      actionsBtn.setAttribute('aria-label', 'Open listing actions');

      var menu = document.createElement('div');
      menu.className = 'ih-tool-enhanced-menu';
      menu.hidden = true;

      if(isAdmin){
        var viewUrl = findActionUrl(shell, 'view');
        menu.insertAdjacentHTML('beforeend', '<a href="'+viewUrl+'">'+icon('eye')+' <span>View</span></a>');
      }

      var editUrl = shell.getAttribute('data-edit-url') || findActionUrl(shell, 'edit');
      menu.insertAdjacentHTML('beforeend', '<a href="'+editUrl+'">'+icon('edit')+' <span>Edit</span></a>');

      if(isAdmin){
        var favAction = document.createElement('button');
        favAction.type = 'button';
        favAction.className = 'ih-tool-fav-action';
        favAction.innerHTML = icon('heart') + ' <span>Add favourite</span>';
        favAction.addEventListener('click', function(e){
          e.preventDefault(); e.stopPropagation();
          setFavourite(toolId, !isFavourite(toolId));
          updateFavouriteButtons();
          closeMenus();
        });
        menu.appendChild(favAction);
      }

      var deleteBtn = document.createElement('button');
      deleteBtn.type = 'button';
      deleteBtn.className = 'ih-tool-delete-action ih-delete-tool';
      deleteBtn.setAttribute('data-id', toolId);
      deleteBtn.title = 'Delete';
      deleteBtn.setAttribute('aria-label', 'Delete');
      deleteBtn.innerHTML = icon('trash') + ' <span>Delete</span>';
      menu.appendChild(deleteBtn);

      actionsBtn.addEventListener('click', function(e){
        e.preventDefault(); e.stopPropagation();
        var wasHidden = menu.hidden;
        closeMenus();
        menu.hidden = !wasHidden;
        shell.classList.toggle('ih-menu-open', wasHidden);
      });

      shell.appendChild(actionsBtn);
      shell.appendChild(menu);
    }
  }

  function getPageSize(){
    var w = window.innerWidth || document.documentElement.clientWidth || 1400;
    if(w <= 520) return 5;
    if(w <= 760) return 6;
    if(w <= 1280) return 8;
    return 12;
  }

  cards.forEach(function(card){
    card.setAttribute('data-tool-search', (card.textContent || '').toLowerCase());
    buildControls(card);
  });

  function filteredCards(){
    var q = search ? String(search.value || '').toLowerCase().trim() : '';
    var matched = cards.filter(function(card){
      var haystack = card.getAttribute('data-tool-search') || '';
      if(q && haystack.indexOf(q) === -1) return false;
      return matchesFilters(card);
    });
    return sortCards(matched);
  }

  function renderPageNumbers(totalPages){
    if(!pageNumbers) return;
    pageNumbers.innerHTML = '';
    var maxButtons = 5;
    var start = Math.max(1, currentPage - 2);
    var end = Math.min(totalPages, start + maxButtons - 1);
    start = Math.max(1, end - maxButtons + 1);
    for(var i = start; i <= end; i++){
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'ih-tools-page-number' + (i === currentPage ? ' is-active' : '');
      btn.textContent = i;
      btn.setAttribute('aria-label', 'Go to page ' + i);
      btn.addEventListener('click', (function(page){ return function(){ currentPage = page; applyToolSearch(false); }; })(i));
      pageNumbers.appendChild(btn);
    }
  }

  function formatPageInfo(start, end, total){
    if(isMobileLayout()){
      if(total === 0) return '0 of 0';
      return (start + 1) + '–' + Math.min(end, total) + ' of ' + total;
    }
    return total === 0 ? 'No tools to show' : 'Showing ' + (start + 1) + '-' + Math.min(end, total) + ' of ' + total + ' tools';
  }

  function updateResultsCount(total){
    var label = total + ' result' + (total === 1 ? '' : 's');
    if(resultsCount) resultsCount.textContent = label;
    if(mobileResultsCount) mobileResultsCount.textContent = label;
  }

  function applyToolSearch(resetPage){
    pageSize = getPageSize();
    if(resetPage) currentPage = 1;
    var visibleCards = filteredCards();
    var total = visibleCards.length;
    var totalPages = Math.max(1, Math.ceil(total / pageSize));
    if(currentPage > totalPages) currentPage = totalPages;
    var start = (currentPage - 1) * pageSize;
    var end = start + pageSize;
    cards.forEach(function(card){ card.style.display = 'none'; });
    visibleCards.slice(start, end).forEach(function(card){ card.style.display = ''; });
    if(count) count.textContent = total;
    updateResultsCount(total);
    if(empty) empty.hidden = total !== 0;
    if(pagination) pagination.hidden = total === 0;
    if(pageInfo) pageInfo.textContent = formatPageInfo(start, end, total);
    if(prevBtn) prevBtn.disabled = currentPage <= 1;
    if(nextBtn) nextBtn.disabled = currentPage >= totalPages;
    renderPageNumbers(totalPages);
    updateFavouriteButtons();
    syncFilterState();
  }

  if(search) search.addEventListener('input', function(){ applyToolSearch(true); });
  if(prevBtn){ prevBtn.addEventListener('click', function(){ if(currentPage > 1){ currentPage--; applyToolSearch(false); } }); }
  if(nextBtn){ nextBtn.addEventListener('click', function(){ var totalPages = Math.max(1, Math.ceil(filteredCards().length / pageSize)); if(currentPage < totalPages){ currentPage++; applyToolSearch(false); } }); }

  var resizeTimer = null;
  window.addEventListener('resize', function(){ clearTimeout(resizeTimer); resizeTimer = setTimeout(function(){ applyToolSearch(false); }, 120); });

  document.addEventListener('click', function(e){
    if(!e.target.closest('.ih-tool-enhanced-menu') && !e.target.closest('.ih-tool-actions-btn')){ closeMenus(); }
  });

  window.ihToggleCustomSelect = function(wrapId){
    var wrap = document.getElementById(wrapId);
    if(!wrap) return;
    var isOpen = wrap.classList.contains('open');
    document.querySelectorAll('.ih-custom-select.open').forEach(function(el){
      el.classList.remove('open');
      var m = el.querySelector('.ih-custom-select-menu');
      if(m) m.classList.add('hidden');
    });
    if(!isOpen){
      wrap.classList.add('open');
      var menu = wrap.querySelector('.ih-custom-select-menu');
      if(menu) menu.classList.remove('hidden');
    }
  };

  window.ihPickCustomSelect = function(wrapId, value, label, callback){
    var wrap = document.getElementById(wrapId);
    if(!wrap) return;
    var labelEl = wrap.querySelector('.ih-custom-select-btn span');
    if(labelEl && wrapId !== 'ihToolDateWrap' && wrapId !== 'ihToolSortWrap') labelEl.textContent = label;
    wrap.querySelectorAll('.ih-custom-select-option').forEach(function(opt){
      opt.classList.toggle('selected', opt.getAttribute('data-value') === value);
    });
    wrap.classList.remove('open');
    var menu = wrap.querySelector('.ih-custom-select-menu');
    if(menu) menu.classList.add('hidden');
    if(wrapId === 'ihToolDateWrap'){
      var dateLabel = document.getElementById('ihToolDateLabel');
      if(dateLabel) dateLabel.textContent = label;
      dateMode = value || 'month';
      applyToolSearch(true);
    }
    if(typeof callback === 'function') callback(value);
  };

  document.addEventListener('click', function(e){
    if(!e.target.closest('.ih-custom-select')){
      document.querySelectorAll('.ih-custom-select.open').forEach(function(el){
        el.classList.remove('open');
        var m = el.querySelector('.ih-custom-select-menu');
        if(m) m.classList.add('hidden');
      });
    }
  });

  applyToolSearch(true);
})();
</script>

</div><!-- /.ih-rd.ih-admin.ih-tools-listings -->

<?php
$content = ob_get_clean();
$title   = 'Tools';
include IH_DIR . 'pages/layout.php';
