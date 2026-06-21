<?php defined( 'ABSPATH' ) || exit;

$listings = ih_db_machines();

if ( ! function_exists('ih_machine_page_get_value') ) {
    function ih_machine_page_get_value( $item, $keys, $fallback = '' ) {
        foreach ( (array) $keys as $key ) {
            if ( is_array($item) && isset($item[$key]) && $item[$key] !== '' && $item[$key] !== null ) {
                return $item[$key];
            }
            if ( is_object($item) && isset($item->{$key}) && $item->{$key} !== '' && $item->{$key} !== null ) {
                return $item->{$key};
            }
        }
        return $fallback;
    }
}

$total_machines     = is_array($listings) ? count($listings) : 0;
$available_machines = 0;
$hydraulic_machines = 0;
$electric_machines  = 0;

if ( ! empty($listings) && is_array($listings) ) {
    foreach ( $listings as $machine_row ) {
        $status = function_exists( 'ih_listing_status_meta' )
            ? ( ih_listing_status_meta( $machine_row )['key'] ?? 'pending' )
            : strtolower((string) ih_machine_page_get_value($machine_row, ['status','availability','listing_status'], ''));
        $type   = strtolower((string) ih_machine_page_get_value($machine_row, ['machine_type','type','power_type','drive_type'], ''));

        if ( $status === 'available' || strpos($status, 'approved') !== false || $status === 'active' ) {
            $available_machines++;
        }
        if ( strpos($type, 'hydraulic') !== false ) {
            $hydraulic_machines++;
        }
        if ( strpos($type, 'electric') !== false ) {
            $electric_machines++;
        }
    }
}

$ih_machines_current_user_id = get_current_user_id();
$ih_machines_is_admin = current_user_can('manage_options') || current_user_can('edit_users');

$ih_machines_filter_types     = array();
$ih_machines_filter_materials = array();
$ih_machines_filter_locations = array();
$ih_machines_filter_statuses  = array();

if ( ! empty( $listings ) && is_array( $listings ) ) {
    foreach ( $listings as $machine_row ) {
        $type_val = trim( (string) ih_machine_page_get_value( $machine_row, array( 'machine_type', 'type', 'power_type', 'drive_type' ), '' ) );
        if ( $type_val !== '' ) {
            $ih_machines_filter_types[ strtolower( $type_val ) ] = $type_val;
        }

        $status_val = function_exists( 'ih_listing_status_meta' )
            ? ( ih_listing_status_meta( $machine_row )['label'] ?? '' )
            : (string) ih_machine_page_get_value( $machine_row, array( 'status', 'availability', 'listing_status' ), '' );
        if ( $status_val !== '' ) {
            $ih_machines_filter_statuses[ strtolower( $status_val ) ] = $status_val;
        }

        $location_val = trim( (string) ih_machine_page_get_value( $machine_row, array( 'location', 'city', 'country' ), '' ) );
        if ( $location_val !== '' ) {
            $ih_machines_filter_locations[ strtolower( $location_val ) ] = $location_val;
        }

        $materials_row = function_exists( 'ih_machine_materials' ) ? ih_machine_materials( $machine_row ) : array();
        foreach ( (array) $materials_row as $mat ) {
            $mat = trim( (string) $mat );
            if ( $mat !== '' ) {
                $ih_machines_filter_materials[ strtolower( $mat ) ] = $mat;
            }
        }
    }
}

asort( $ih_machines_filter_types );
asort( $ih_machines_filter_materials );
asort( $ih_machines_filter_locations );
asort( $ih_machines_filter_statuses );

ob_start();
?>
<div class="ih-rd ih-admin ih-machines-listings">
<div class="ih-page-header ih-machines-page-header">
  <div>
    <p class="ih-machines-eyebrow"><span class="dot" aria-hidden="true"></span>ALL LISTINGS · MACHINES</p>
    <h2 class="ih-page-title">Machines</h2>
    <p class="ih-page-sub">See and manage all listed machines.</p>
  </div>
</div>

<div class="ih-machines-hero">
  <div class="ih-machine-stat-card">
    <span><span class="ih-stat-label-long">Total listings</span><span class="ih-stat-label-short">Total</span></span>
    <strong><?php echo (int) $total_machines; ?></strong>
    <small>All machine cards</small>
  </div>
  <div class="ih-machine-stat-card is-green">
    <span><span class="ih-stat-label-long">Available</span><span class="ih-stat-label-short">Available</span></span>
    <strong><?php echo (int) $available_machines; ?></strong>
    <small>Ready or approved</small>
  </div>
  <div class="ih-machine-stat-card is-blue">
    <span><span class="ih-stat-label-long">Hydraulic</span><span class="ih-stat-label-short">Hydraulic</span></span>
    <strong><?php echo (int) $hydraulic_machines; ?></strong>
    <small>Hydraulic drive</small>
  </div>
  <div class="ih-machine-stat-card is-amber">
    <span><span class="ih-stat-label-long">Electric</span><span class="ih-stat-label-short">Electric</span></span>
    <strong><?php echo (int) $electric_machines; ?></strong>
    <small>Electric drive</small>
  </div>
</div>

<div class="ih-card ih-machines-page">
  <div class="ih-card-head ih-machines-head">
    <span class="ih-card-title">Listed Machines <span class="ih-count-badge" id="machineVisibleCount"><?php echo (int) $total_machines; ?></span></span>
    <div class="ih-head-actions">
      <div class="ih-search-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" id="machineSearch" placeholder="Search machines…" class="ih-search-input">
      </div>
      <div class="ih-custom-select" id="ihMachineDateWrap">
        <button type="button" class="ih-custom-select-btn" onclick="ihToggleCustomSelect('ihMachineDateWrap')">
          <span id="ihMachineDateLabel">This Month</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="ih-custom-select-menu hidden">
          <div class="ih-custom-select-option selected" data-value="month" onclick="ihPickCustomSelect('ihMachineDateWrap','month','This Month')">
            <span>This Month</span>
            <svg class="ih-check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <div class="ih-custom-select-option" data-value="year" onclick="ihPickCustomSelect('ihMachineDateWrap','year','This Year')">
            <span>This Year</span>
            <svg class="ih-check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <div class="ih-custom-select-option" data-value="all" onclick="ihPickCustomSelect('ihMachineDateWrap','all','All Time')">
            <span>All Time</span>
            <svg class="ih-check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
        </div>
      </div>
      <a href="<?php echo esc_url(admin_url('admin.php?page=ih-add-machine')); ?>" class="ih-btn ih-btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Machine
      </a>
    </div>
  </div>

  <div class="ih-machines-filters" id="ihMachinesFilters">
    <div class="ih-machines-mobile-filter-bar" id="ihMachinesMobileFilterBar">
      <button type="button" class="ih-machines-mobile-filters-btn" id="ihMachinesMobileFiltersBtn" aria-expanded="false" aria-controls="ihMachinesDesktopFilters">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15" aria-hidden="true"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
        <span>Filters</span>
        <span class="ih-machines-mobile-filters-count" id="ihMachinesMobileFiltersCount" hidden>0</span>
      </button>
      <div class="ih-machines-mobile-filter-scroll" role="group" aria-label="Quick filters">
        <button type="button" class="ih-machines-filter-pill is-active" data-quick-filter="all">All</button>
        <button type="button" class="ih-machines-filter-pill" data-quick-filter="type:hydraulic">Hydraulic</button>
        <button type="button" class="ih-machines-filter-pill" data-quick-filter="type:electric">Electric</button>
        <button type="button" class="ih-machines-filter-pill" data-quick-filter="status:available">Available</button>
        <?php foreach ( $ih_machines_filter_materials as $mat_key => $mat_label ) : ?>
        <button type="button" class="ih-machines-filter-pill" data-quick-filter="material:<?php echo esc_attr( $mat_key ); ?>"><?php echo esc_html( $mat_label ); ?></button>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="ih-machines-filter-row ih-machines-desktop-filters" id="ihMachinesDesktopFilters">
      <div class="ih-machines-filter-label">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" aria-hidden="true"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
        <span>Filters</span>
      </div>
      <div class="ih-custom-select ih-machines-filter-select" id="ihMachineFilterType" data-filter-key="type">
        <button type="button" class="ih-custom-select-btn ih-machines-filter-btn" onclick="ihToggleCustomSelect('ihMachineFilterType')">
          <span>Type</span>
          <span class="ih-machines-filter-count" id="ihMachineFilterTypeCount" hidden>0</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="ih-custom-select-menu hidden">
          <?php foreach ( $ih_machines_filter_types as $type_key => $type_label ) : ?>
          <label class="ih-custom-select-option ih-machines-filter-option">
            <input type="checkbox" class="ih-machines-filter-input" data-filter="type" value="<?php echo esc_attr( $type_key ); ?>">
            <span><?php echo esc_html( $type_label ); ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="ih-custom-select ih-machines-filter-select" id="ihMachineFilterMaterial" data-filter-key="material">
        <button type="button" class="ih-custom-select-btn ih-machines-filter-btn" onclick="ihToggleCustomSelect('ihMachineFilterMaterial')">
          <span>Material</span>
          <span class="ih-machines-filter-count" id="ihMachineFilterMaterialCount" hidden>0</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="ih-custom-select-menu hidden">
          <?php foreach ( $ih_machines_filter_materials as $mat_key => $mat_label ) : ?>
          <label class="ih-custom-select-option ih-machines-filter-option">
            <input type="checkbox" class="ih-machines-filter-input" data-filter="material" value="<?php echo esc_attr( $mat_key ); ?>">
            <span><?php echo esc_html( $mat_label ); ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="ih-custom-select ih-machines-filter-select" id="ihMachineFilterStatus" data-filter-key="status">
        <button type="button" class="ih-custom-select-btn ih-machines-filter-btn" onclick="ihToggleCustomSelect('ihMachineFilterStatus')">
          <span>Status</span>
          <span class="ih-machines-filter-count" id="ihMachineFilterStatusCount" hidden>0</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="ih-custom-select-menu hidden">
          <?php foreach ( $ih_machines_filter_statuses as $status_key => $status_label ) : ?>
          <label class="ih-custom-select-option ih-machines-filter-option">
            <input type="checkbox" class="ih-machines-filter-input" data-filter="status" value="<?php echo esc_attr( $status_key ); ?>">
            <span><?php echo esc_html( $status_label ); ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="ih-custom-select ih-machines-filter-select" id="ihMachineFilterLocation" data-filter-key="location">
        <button type="button" class="ih-custom-select-btn ih-machines-filter-btn" onclick="ihToggleCustomSelect('ihMachineFilterLocation')">
          <span>Location</span>
          <span class="ih-machines-filter-count" id="ihMachineFilterLocationCount" hidden>0</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="ih-custom-select-menu hidden">
          <?php foreach ( $ih_machines_filter_locations as $loc_key => $loc_label ) : ?>
          <label class="ih-custom-select-option ih-machines-filter-option">
            <input type="checkbox" class="ih-machines-filter-input" data-filter="location" value="<?php echo esc_attr( $loc_key ); ?>">
            <span><?php echo esc_html( $loc_label ); ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="ih-custom-select ih-machines-filter-select ih-machines-filter-sort" id="ihMachineSortWrap">
        <button type="button" class="ih-custom-select-btn ih-machines-filter-btn" onclick="ihToggleCustomSelect('ihMachineSortWrap')">
          <span id="ihMachineSortLabel">Sort: Newest</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="ih-custom-select-menu hidden">
          <div class="ih-custom-select-option selected" data-value="newest" onclick="ihPickMachineSort('newest','Sort: Newest')"><span>Newest</span></div>
          <div class="ih-custom-select-option" data-value="oldest" onclick="ihPickMachineSort('oldest','Sort: Oldest')"><span>Oldest</span></div>
          <div class="ih-custom-select-option" data-value="name" onclick="ihPickMachineSort('name','Sort: Name')"><span>Name</span></div>
        </div>
      </div>
    </div>
    <div class="ih-machines-active-row" id="ihMachinesActiveRow" hidden>
      <span class="ih-machines-active-label ih-machines-active-label-desktop">Active:</span>
      <div class="ih-machines-active-chips" id="ihMachinesActiveChips"></div>
      <button type="button" class="ih-machines-clear-all" id="ihMachinesClearAll">Clear all</button>
      <span class="ih-machines-results-count ih-machines-results-count-inline" id="ihMachinesResultsCount"></span>
    </div>
    <div class="ih-machines-mobile-results-row">
      <div class="ih-machines-active-chips ih-machines-mobile-active-chips" id="ihMachinesMobileActiveChips"></div>
      <span class="ih-machines-results-count" id="ihMachinesMobileResultsCount"></span>
    </div>
  </div>

  <div class="ih-listing-grid ih-machine-grid-boost" id="machinesGrid">
    <?php foreach ( $listings as $m ) : ?>
      <?php
        $machine_id_for_card = ih_machine_page_get_value($m, ['id','machine_id','listing_id','ID'], '');
        $machine_owner_id    = (int) ih_machine_page_get_value($m, ['user_id','owner_id','author_id','created_by','submitted_by','wp_user_id','post_author'], 0);
        $can_manage_machine  = $ih_machines_is_admin || ($machine_owner_id && $machine_owner_id === (int) $ih_machines_current_user_id);
        $machine_type_val    = strtolower( trim( (string) ih_machine_page_get_value( $m, array( 'machine_type', 'type', 'power_type', 'drive_type' ), '' ) ) );
        $machine_status_meta = function_exists( 'ih_listing_status_meta' ) ? ih_listing_status_meta( $m ) : array( 'label' => '' );
        $machine_status_val  = strtolower( (string) ( $machine_status_meta['label'] ?? '' ) );
        $machine_location    = strtolower( trim( (string) ih_machine_page_get_value( $m, array( 'location', 'city', 'country' ), '' ) ) );
        $machine_materials   = function_exists( 'ih_machine_materials' ) ? ih_machine_materials( $m ) : array();
        $machine_materials_val = implode( ',', array_map( 'strtolower', array_map( 'trim', (array) $machine_materials ) ) );
        $machine_listing_ts  = ! empty( $m['listing_date'] ) ? strtotime( (string) $m['listing_date'] ) : 0;
        $machine_title_val   = strtolower( trim( (string) ih_machine_page_get_value( $m, array( 'title', 'machine_name', 'name' ), '' ) ) );
      ?>
      <div class="ih-machine-listing-shell"
           data-machine-id="<?php echo esc_attr($machine_id_for_card); ?>"
           data-owner-id="<?php echo esc_attr($machine_owner_id); ?>"
           data-can-manage="<?php echo $can_manage_machine ? '1' : '0'; ?>"
           data-is-admin="<?php echo $ih_machines_is_admin ? '1' : '0'; ?>"
           data-machine-type="<?php echo esc_attr( $machine_type_val ); ?>"
           data-machine-status="<?php echo esc_attr( $machine_status_val ); ?>"
           data-machine-location="<?php echo esc_attr( $machine_location ); ?>"
           data-machine-materials="<?php echo esc_attr( $machine_materials_val ); ?>"
           data-listing-ts="<?php echo esc_attr( (string) $machine_listing_ts ); ?>"
           data-machine-title="<?php echo esc_attr( $machine_title_val ); ?>">
        <?php include __DIR__ . '/partials/machine-card.php'; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="ih-machines-pagination" id="machinesPagination" aria-label="Machines pagination">
    <div class="ih-machines-page-info" id="machinesPageInfo">Showing machines</div>
    <div class="ih-machines-page-controls">
      <button type="button" class="ih-machines-page-btn" id="machinesPrevPage" aria-label="Previous page"><span class="ih-page-btn-short">‹</span><span class="ih-page-btn-long">‹ Prev</span></button>
      <div class="ih-machines-page-numbers" id="machinesPageNumbers"></div>
      <button type="button" class="ih-machines-page-btn" id="machinesNextPage" aria-label="Next page"><span class="ih-page-btn-short">›</span><span class="ih-page-btn-long">Next ›</span></button>
    </div>
  </div>

  <div class="ih-machines-empty" id="machinesEmptyState" hidden>
    <div class="ih-machines-empty-icon">🔍</div>
    <strong>No machines found</strong>
    <span>Try another machine name, location, material or type.</span>
  </div>
</div>

<footer class="ih-machines-page-footer" aria-label="Machines page footer">
  <span class="ih-machines-footer-meta">MACHINES · v<?php echo esc_html( IH_VERSION ); ?></span>
  <span class="ih-machines-footer-status"><span class="dot" aria-hidden="true"></span>OPERATIONAL</span>
</footer>

<script id="ih-machines-redesign-v3-js">
(function(){
  var search = document.getElementById('machineSearch');
  var grid = document.getElementById('machinesGrid');
  var count = document.getElementById('machineVisibleCount');
  var empty = document.getElementById('machinesEmptyState');
  var pagination = document.getElementById('machinesPagination');
  var pageInfo = document.getElementById('machinesPageInfo');
  var prevBtn = document.getElementById('machinesPrevPage');
  var nextBtn = document.getElementById('machinesNextPage');
  var pageNumbers = document.getElementById('machinesPageNumbers');
  var activeRow = document.getElementById('ihMachinesActiveRow');
  var activeChips = document.getElementById('ihMachinesActiveChips');
  var mobileActiveChips = document.getElementById('ihMachinesMobileActiveChips');
  var clearAllBtn = document.getElementById('ihMachinesClearAll');
  var resultsCount = document.getElementById('ihMachinesResultsCount');
  var mobileResultsCount = document.getElementById('ihMachinesMobileResultsCount');
  var mobileFiltersBtn = document.getElementById('ihMachinesMobileFiltersBtn');
  var mobileFiltersCount = document.getElementById('ihMachinesMobileFiltersCount');
  var desktopFilters = document.getElementById('ihMachinesDesktopFilters');
  var sortLabel = document.getElementById('ihMachineSortLabel');
  var sortMode = 'newest';

  function isMobileLayout(){
    return (window.innerWidth || document.documentElement.clientWidth || 1400) <= 768;
  }

  function totalActiveFilterCount(){
    return Object.keys(activeFilters).reduce(function(sum, key){ return sum + activeFilters[key].length; }, 0);
  }

  function renderActiveChips(container){
    if(!container) return;
    container.innerHTML = '';
    Object.keys(activeFilters).forEach(function(key){
      activeFilters[key].forEach(function(val){
        var chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'ih-machines-active-chip';
        chip.setAttribute('data-filter', key);
        chip.setAttribute('data-value', val);
        chip.innerHTML = '<span>' + (filterLabels[key][val] || val) + '</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="11" height="11" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>';
        chip.addEventListener('click', function(){
          var selector = '.ih-machines-filter-input[data-filter="' + key + '"][value="' + val.replace(/"/g, '\\"') + '"]';
          var input = document.querySelector(selector);
          if(input) input.checked = false;
          syncFilterState();
          applyMachineSearch(true);
        });
        container.appendChild(chip);
      });
    });
    if(virtualAvailableFilter){
      var availChip = document.createElement('button');
      availChip.type = 'button';
      availChip.className = 'ih-machines-active-chip';
      availChip.setAttribute('data-filter', 'status');
      availChip.setAttribute('data-value', 'available');
      availChip.innerHTML = '<span>Available</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="11" height="11" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>';
      availChip.addEventListener('click', function(){
        virtualAvailableFilter = false;
        syncFilterState();
        applyMachineSearch(true);
      });
      container.appendChild(availChip);
    }
  }

  function syncQuickFilterPills(){
    var totalActive = totalActiveFilterCount() + (virtualAvailableFilter ? 1 : 0);
    document.querySelectorAll('.ih-machines-filter-pill').forEach(function(pill){
      var quick = pill.getAttribute('data-quick-filter') || '';
      if(quick === 'all'){
        pill.classList.toggle('is-active', totalActive === 0);
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
    var count = totalActiveFilterCount() + (virtualAvailableFilter ? 1 : 0);
    if(mobileFiltersCount){
      mobileFiltersCount.textContent = count;
      mobileFiltersCount.hidden = count === 0;
    }
    if(mobileFiltersBtn){
      mobileFiltersBtn.classList.toggle('has-active', count > 0);
    }
  }

  var activeFilters = { type: [], material: [], status: [], location: [] };
  var filterLabels = { type: {}, material: {}, status: {}, location: {} };
  var virtualAvailableFilter = false;

  document.querySelectorAll('.ih-machines-filter-input').forEach(function(input){
    var key = input.getAttribute('data-filter');
    var val = input.value;
    var label = input.closest('label') ? (input.closest('label').querySelector('span') || {}).textContent : val;
    if(key && val) filterLabels[key] = filterLabels[key] || {};
    if(key && val) filterLabels[key][val] = label || val;
    input.addEventListener('change', function(){ syncFilterState(); applyMachineSearch(true); });
  });

  function syncFilterState(){
    activeFilters = { type: [], material: [], status: [], location: [] };
    document.querySelectorAll('.ih-machines-filter-input:checked').forEach(function(input){
      var key = input.getAttribute('data-filter');
      if(!key || !activeFilters[key]) return;
      activeFilters[key].push(input.value);
    });

    ['type','material','status','location'].forEach(function(key){
      var countEl = document.getElementById('ihMachineFilter' + key.charAt(0).toUpperCase() + key.slice(1) + 'Count');
      var wrap = document.getElementById('ihMachineFilter' + key.charAt(0).toUpperCase() + key.slice(1));
      var count = activeFilters[key].length;
      if(countEl){
        countEl.textContent = count;
        countEl.hidden = count === 0;
      }
      if(wrap){
        wrap.classList.toggle('has-active', count > 0);
      }
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
      document.querySelectorAll('.ih-machines-filter-input').forEach(function(input){ input.checked = false; });
      syncFilterState();
      applyMachineSearch(true);
    });
  }

  document.querySelectorAll('.ih-machines-filter-pill').forEach(function(pill){
    pill.addEventListener('click', function(){
      var quick = pill.getAttribute('data-quick-filter') || '';
      if(quick === 'all'){
        virtualAvailableFilter = false;
        document.querySelectorAll('.ih-machines-filter-input').forEach(function(input){ input.checked = false; });
        syncFilterState();
        applyMachineSearch(true);
        return;
      }
      var parts = quick.split(':');
      if(parts.length !== 2) return;
      var key = parts[0];
      var val = parts[1];
      var selector = '.ih-machines-filter-input[data-filter="' + key + '"][value="' + val.replace(/"/g, '\\"') + '"]';
      var input = document.querySelector(selector);
      if(key === 'status' && val === 'available' && !input){
        input = Array.prototype.slice.call(document.querySelectorAll('.ih-machines-filter-input[data-filter="status"]')).find(function(el){
          return el.value.indexOf('available') !== -1;
        }) || null;
      }
      if(key === 'status' && val === 'available' && !input){
        virtualAvailableFilter = !virtualAvailableFilter;
        syncFilterState();
        applyMachineSearch(true);
        return;
      }
      if(!input) return;
      if(key === 'status' && val === 'available') virtualAvailableFilter = false;
      input.checked = !input.checked;
      syncFilterState();
      applyMachineSearch(true);
    });
  });

  if(mobileFiltersBtn && desktopFilters){
    mobileFiltersBtn.addEventListener('click', function(){
      var expanded = desktopFilters.classList.toggle('is-expanded');
      mobileFiltersBtn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    });
  }

  window.ihPickMachineSort = function(value, label){
    sortMode = value || 'newest';
    if(sortLabel) sortLabel.textContent = label || 'Sort: Newest';
    ihPickCustomSelect('ihMachineSortWrap', value, label);
    applyMachineSearch(false);
  };

  function matchesFilters(card){
    var type = card.getAttribute('data-machine-type') || '';
    var status = card.getAttribute('data-machine-status') || '';
    var location = card.getAttribute('data-machine-location') || '';
    var materials = (card.getAttribute('data-machine-materials') || '').split(',').filter(Boolean);

    if(activeFilters.type.length && activeFilters.type.indexOf(type) === -1) return false;
    if(activeFilters.status.length){
      var statusMatch = activeFilters.status.some(function(s){
        return status === s || (s === 'available' && status.indexOf('available') !== -1);
      });
      if(!statusMatch) return false;
    } else if(virtualAvailableFilter){
      if(status.indexOf('available') === -1 && status.indexOf('approved') === -1 && status !== 'active') return false;
    }
    if(activeFilters.location.length && activeFilters.location.indexOf(location) === -1) return false;
    if(activeFilters.material.length){
      var hasMaterial = activeFilters.material.some(function(mat){ return materials.indexOf(mat) !== -1; });
      if(!hasMaterial) return false;
    }
    return true;
  }

  function sortCards(list){
    return list.slice().sort(function(a, b){
      if(sortMode === 'name'){
        return (a.getAttribute('data-machine-title') || '').localeCompare(b.getAttribute('data-machine-title') || '');
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
  var favStorageKey = 'insidehub_machine_favourites';

  function readFavourites(){
    try { return JSON.parse(localStorage.getItem(favStorageKey) || '{}') || {}; } catch(e) { return {}; }
  }
  function writeFavourites(favs){
    try { localStorage.setItem(favStorageKey, JSON.stringify(favs || {})); } catch(e) {}
  }
  function isFavourite(machineId){
    return Boolean(readFavourites()[String(machineId || '')]);
  }
  function setFavourite(machineId, value){
    var favs = readFavourites();
    machineId = String(machineId || '');
    if(!machineId) return;
    if(value) favs[machineId] = true;
    else delete favs[machineId];
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
      if(kind === 'edit' && (text.indexOf('edit') !== -1 || href.indexOf('edit') !== -1 || href.indexOf('add-machine') !== -1)){ match = a.getAttribute('href'); return true; }
      return false;
    });
    return match || '#';
  }

  function closeMenus(){
    document.querySelectorAll('.ih-machine-enhanced-menu').forEach(function(menu){
      menu.hidden = true;
      var shell = menu.closest('.ih-machine-listing-shell');
      if(shell) shell.classList.remove('ih-menu-open');
    });
  }

  function buildControls(shell){
    if(shell.getAttribute('data-enhanced-controls') === '1') return;
    shell.setAttribute('data-enhanced-controls','1');

    var machineId = shell.getAttribute('data-machine-id') || '';
    if(!machineId){
      machineId = (shell.textContent || '').trim().toLowerCase().replace(/[^a-z0-9]+/g,'-').slice(0,40);
      shell.setAttribute('data-machine-id', machineId);
    }

    var canManage = shell.getAttribute('data-can-manage') === '1';
    var isAdmin = shell.getAttribute('data-is-admin') === '1';

    var favBtn = document.createElement('button');
    favBtn.type = 'button';
    favBtn.className = 'ih-machine-fav-btn';
    favBtn.innerHTML = icon('heart');
    favBtn.setAttribute('aria-label','Add listing to favourites');
    favBtn.setAttribute('title','Add to favourites');
    favBtn.addEventListener('click', function(e){
      e.preventDefault(); e.stopPropagation();
      setFavourite(machineId, !isFavourite(machineId));
      updateFavouriteButtons();
    });
    shell.appendChild(favBtn);

    if(canManage){
      var actionsBtn = document.createElement('button');
      actionsBtn.type = 'button';
      actionsBtn.className = 'ih-machine-actions-btn';
      actionsBtn.innerHTML = icon('more');
      actionsBtn.setAttribute('aria-label','Open listing actions');
      actionsBtn.setAttribute('title','Listing actions');

      var menu = document.createElement('div');
      menu.className = 'ih-machine-enhanced-menu';
      menu.hidden = true;

      if(isAdmin){
        var viewUrl = findActionUrl(shell, 'view');
        menu.insertAdjacentHTML('beforeend', '<a href="'+viewUrl+'" title="View" aria-label="View">'+icon('eye')+' <span>View</span></a>');
      }

      var editUrl = findActionUrl(shell, 'edit');
      menu.insertAdjacentHTML('beforeend', '<a href="'+editUrl+'" title="Edit" aria-label="Edit">'+icon('edit')+' <span>Edit</span></a>');

      if(isAdmin){
        var favAction = document.createElement('button');
        favAction.type = 'button';
        favAction.className = 'ih-machine-fav-action';
        favAction.title = 'Add favourite';
        favAction.setAttribute('aria-label','Add favourite');
        favAction.innerHTML = icon('heart') + ' <span>Add favourite</span>';
        favAction.addEventListener('click', function(e){
          e.preventDefault(); e.stopPropagation();
          setFavourite(machineId, !isFavourite(machineId));
          updateFavouriteButtons();
          closeMenus();
        });
        menu.appendChild(favAction);
      }

      // ── DELETE FIX: ih-delete-machine class → main.js ka handler fire hoga ──
      var deleteBtn = document.createElement('button');
      deleteBtn.type = 'button';
      deleteBtn.className = 'ih-machine-delete-action ih-delete-machine';
      deleteBtn.setAttribute('data-id', machineId);
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

  function updateFavouriteButtons(){
    cards.forEach(function(shell){
      var machineId = shell.getAttribute('data-machine-id') || '';
      var fav = isFavourite(machineId);
      var favBtn = shell.querySelector('.ih-machine-fav-btn');
      var favAction = shell.querySelector('.ih-machine-fav-action span');
      if(favBtn){ favBtn.classList.toggle('is-favourite', fav); favBtn.setAttribute('aria-label', fav ? 'Remove listing from favourites' : 'Add listing to favourites'); }
      if(favAction){
        favAction.textContent = fav ? 'Remove favourite' : 'Add favourite';
        var favActionButton = favAction.closest('.ih-machine-fav-action');
        if(favActionButton){ favActionButton.title = fav ? 'Remove favourite' : 'Add favourite'; favActionButton.setAttribute('aria-label', fav ? 'Remove favourite' : 'Add favourite'); }
      }
    });
  }

  function getPageSize(){
    var w = window.innerWidth || document.documentElement.clientWidth || 1400;
    if(w <= 520) return 5;
    if(w <= 760) return 6;
    if(w <= 1280) return 8;
    return 12;
  }

  cards.forEach(function(card){
    card.setAttribute('data-machine-search', (card.textContent || '').toLowerCase());
    buildControls(card);
  });

  function filteredCards(){
    var q = search ? String(search.value || '').toLowerCase().trim() : '';
    var matched = cards.filter(function(card){
      var haystack = card.getAttribute('data-machine-search') || '';
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
      btn.className = 'ih-machines-page-number' + (i === currentPage ? ' is-active' : '');
      btn.textContent = i;
      btn.setAttribute('aria-label', 'Go to page ' + i);
      btn.addEventListener('click', (function(page){ return function(){ currentPage = page; applyMachineSearch(false); }; })(i));
      pageNumbers.appendChild(btn);
    }
  }

  function formatPageInfo(start, end, total){
    if(isMobileLayout()){
      if(total === 0) return '0 of 0';
      return (start + 1) + '–' + Math.min(end, total) + ' of ' + total;
    }
    return total === 0 ? 'No machines to show' : 'Showing ' + (start + 1) + '-' + Math.min(end, total) + ' of ' + total + ' machines';
  }

  function updateResultsCount(total){
    var label = total + ' result' + (total === 1 ? '' : 's');
    if(resultsCount) resultsCount.textContent = label;
    if(mobileResultsCount) mobileResultsCount.textContent = label;
  }

  function applyMachineSearch(resetPage){
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

  if(search) search.addEventListener('input', function(){ applyMachineSearch(true); });
  if(prevBtn){ prevBtn.addEventListener('click', function(){ if(currentPage > 1){ currentPage--; applyMachineSearch(false); } }); }
  if(nextBtn){ nextBtn.addEventListener('click', function(){ var totalPages = Math.max(1, Math.ceil(filteredCards().length / pageSize)); if(currentPage < totalPages){ currentPage++; applyMachineSearch(false); } }); }

  var resizeTimer = null;
  window.addEventListener('resize', function(){ clearTimeout(resizeTimer); resizeTimer = setTimeout(function(){ applyMachineSearch(false); }, 120); });

  document.addEventListener('click', function(e){
    if(!e.target.closest('.ih-machine-enhanced-menu') && !e.target.closest('.ih-machine-actions-btn')){ closeMenus(); }
  });

  applyMachineSearch(true);

  /* ── Custom Select JS ── */
  window.ihToggleCustomSelect = function(wrapId) {
    var wrap = document.getElementById(wrapId);
    if (!wrap) return;
    var isOpen = wrap.classList.contains('open');
    document.querySelectorAll('.ih-custom-select.open').forEach(function(el) {
      el.classList.remove('open');
      var m = el.querySelector('.ih-custom-select-menu');
      if (m) m.classList.add('hidden');
    });
    if (!isOpen) {
      wrap.classList.add('open');
      var menu = wrap.querySelector('.ih-custom-select-menu');
      if (menu) menu.classList.remove('hidden');
    }
  };

  window.ihPickCustomSelect = function(wrapId, value, label, callback) {
    var wrap = document.getElementById(wrapId);
    if (!wrap) return;
    var labelEl = wrap.querySelector('.ih-custom-select-btn span');
    if (labelEl) labelEl.textContent = label;
    wrap.querySelectorAll('.ih-custom-select-option').forEach(function(opt) {
      opt.classList.toggle('selected', opt.getAttribute('data-value') === value);
    });
    wrap.classList.remove('open');
    var menu = wrap.querySelector('.ih-custom-select-menu');
    if (menu) menu.classList.add('hidden');
    if (typeof callback === 'function') callback(value);
  };

  document.addEventListener('click', function(e) {
    if (!e.target.closest('.ih-custom-select')) {
      document.querySelectorAll('.ih-custom-select.open').forEach(function(el) {
        el.classList.remove('open');
        var m = el.querySelector('.ih-custom-select-menu');
        if (m) m.classList.add('hidden');
      });
    }
  });

})();
</script>

</div><!-- /.ih-rd.ih-admin.ih-machines-listings -->

<?php
$content = ob_get_clean();
$title   = 'Machines';
include IH_DIR . 'pages/layout.php';