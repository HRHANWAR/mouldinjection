<?php
/**
 * pricing-admin.php — wp-admin screen for material pricing
 * Part of insight-hub-dashboard. require_once from the main plugin file
 * AFTER ih-material-pricing.php (it reuses IH_Material_Pricing::compute_quote()).
 *
 * Adds:  Insight Hub ▸ Material Pricing
 *   • editable ih_materials table (supplier price, cost add-ons, margin, active)
 *   • add a new material
 *   • set / clear a manual override (with reason)
 *   • per-material price history (audit trail)
 *
 * Every write: capability ih_manage_pricing + nonce, parameterised SQL,
 * recompute quote, log to ih_material_price_history, bust the price cache.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IH_Pricing_Admin {

	const CAP  = 'ih_manage_pricing';
	const SLUG = 'ih-material-pricing';

	/* The Insight Hub admin top-level menu slug (see insight-hub-dashboard.php). */
	const PARENT_SLUG = 'ih-dashboard';

	public static function init() {
		add_action( 'admin_menu',            [ __CLASS__, 'menu' ] );
		add_action( 'admin_post_ih_save_material',    [ __CLASS__, 'handle_save' ] );
		add_action( 'admin_post_ih_set_override',     [ __CLASS__, 'handle_override' ] );
		add_action( 'admin_post_ih_clear_override',   [ __CLASS__, 'handle_clear_override' ] );
	}

	public static function menu() {
		// Nest under the real Insight Hub admin menu (ih-dashboard). add_submenu_page
		// resolves the parent at render time, so registration order does not matter;
		// fall back to a self-standing top-level menu if that parent is absent.
		$cb     = [ __CLASS__, 'render' ];
		$parent = self::PARENT_SLUG;
		if ( $parent ) {
			add_submenu_page( $parent, 'Material Pricing', 'Material Pricing', self::CAP, self::SLUG, $cb );
		} else {
			add_menu_page( 'Material Pricing', 'Material Pricing', self::CAP, self::SLUG, $cb, 'dashicons-chart-line', 58 );
		}
	}

	private static function tbl()  { global $wpdb; return $wpdb->prefix . 'ih_materials'; }
	private static function htbl() { global $wpdb; return $wpdb->prefix . 'ih_material_price_history'; }
	private static function url( $args = [] ) { return add_query_arg( array_merge( [ 'page' => self::SLUG ], $args ), admin_url( 'admin.php' ) ); }

	/* ============================================================
	   ROUTER
	   ============================================================ */
	public static function render() {
		if ( ! current_user_can( self::CAP ) ) wp_die( 'You do not have permission to manage pricing.' );
		$view = isset( $_GET['view'] ) ? sanitize_key( $_GET['view'] ) : 'list';
		echo '<div class="wrap"><h1 class="wp-heading-inline">Material Pricing</h1>';
		if ( 'list' !== $view ) echo ' <a href="' . esc_url( self::url() ) . '" class="page-title-action">← All materials</a>';
		self::notices();
		if ( 'edit' === $view || 'add' === $view ) self::render_form();
		elseif ( 'history' === $view )             self::render_history();
		else                                       self::render_list();
		echo '</div>';
	}

	private static function notices() {
		if ( empty( $_GET['ih_msg'] ) ) return;
		$map = [ 'saved' => 'Material saved.', 'override' => 'Override applied.', 'cleared' => 'Override cleared.', 'error' => 'Something went wrong.' ];
		$k = sanitize_key( $_GET['ih_msg'] );
		$cls = 'error' === $k ? 'notice-error' : 'notice-success';
		if ( isset( $map[ $k ] ) ) printf( '<div class="notice %s is-dismissible"><p>%s</p></div>', esc_attr( $cls ), esc_html( $map[ $k ] ) );
	}

	/* ============================================================
	   LIST
	   ============================================================ */
	private static function render_list() {
		global $wpdb; $t = self::tbl();
		$rows = $wpdb->get_results( "SELECT * FROM $t ORDER BY material_family, material_name, region, supplier" );
		echo ' <a href="' . esc_url( self::url( [ 'view' => 'add' ] ) ) . '" class="page-title-action">Add material</a>';
		echo '<table class="wp-list-table widefat fixed striped" style="margin-top:12px">';
		echo '<thead><tr>'
			. '<th>Material / grade</th><th>Region</th><th>Supplier</th>'
			. '<th>Market £/kg</th><th>Supplier £/kg</th><th>Quote £/kg</th><th>Price used</th>'
			. '<th>Source</th><th>Updated</th><th>Status</th><th></th></tr></thead><tbody>';
		if ( ! $rows ) echo '<tr><td colspan="11">No materials yet. Click “Add material”.</td></tr>';
		foreach ( $rows as $r ) {
			$quote = IH_Material_Pricing::compute_quote( $r );
			$used  = ( $r->is_manual_override && $r->manual_override_price > 0 ) ? (float) $r->manual_override_price : $quote;
			$status = ! $r->active ? '<span style="color:#a00">inactive</span>'
				: ( $r->is_manual_override ? '<span style="color:#5347ce;font-weight:600">override</span>'
				: ( $r->is_live_price ? '<span style="color:#16a34a;font-weight:600">live</span>' : 'manual' ) );
			echo '<tr>';
			printf( '<td><strong>%s</strong><br><span style="color:#666">%s %s</span></td>', esc_html( $r->material_name ), esc_html( $r->grade ), esc_html( $r->grade_code ? "($r->grade_code)" : '' ) );
			printf( '<td>%s</td><td>%s</td>', esc_html( $r->region ), esc_html( $r->supplier ) );
			printf( '<td>%s</td><td>%s</td>', self::money( $r->market_price_per_kg ), self::money( $r->supplier_price_per_kg ) );
			printf( '<td>%s</td><td><strong>%s</strong></td>', self::money( $quote ), self::money( $used ) );
			printf( '<td>%s</td><td>%s</td><td>%s</td>', esc_html( $r->price_source ), esc_html( self::date( $r->last_updated ) ), $status );
			echo '<td>'
				. '<a href="' . esc_url( self::url( [ 'view' => 'edit', 'id' => $r->id ] ) ) . '">Edit</a> | '
				. '<a href="' . esc_url( self::url( [ 'view' => 'history', 'id' => $r->id ] ) ) . '">History</a>'
				. '</td></tr>';
		}
		echo '</tbody></table>';
		echo '<p style="color:#666;margin-top:10px">Quote = (supplier price, or market if no supplier price, + delivery + handling + masterbatch + drying) × (1 + margin%). “Price used” is the override when set, otherwise the quote.</p>';
	}

	/* ============================================================
	   ADD / EDIT FORM
	   ============================================================ */
	private static function render_form() {
		global $wpdb;
		$id  = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		$r   = $id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::tbl() . " WHERE id = %d", $id ) ) : null;
		$g   = function ( $k, $d = '' ) use ( $r ) { return $r && isset( $r->$k ) ? $r->$k : $d; };
		echo '<h2>' . ( $r ? 'Edit material' : 'Add material' ) . '</h2>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'ih_save_material_' . $id );
		echo '<input type="hidden" name="action" value="ih_save_material"><input type="hidden" name="id" value="' . esc_attr( $id ) . '">';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::txt( 'material_family', 'Material family', $g( 'material_family' ), 'e.g. Polypropylene' );
		self::txt( 'material_name',   'Material name *', $g( 'material_name' ), 'e.g. PP Homopolymer Injection Grade' );
		self::txt( 'grade',           'Grade',           $g( 'grade' ), 'e.g. Injection moulding' );
		self::txt( 'grade_code',      'Grade code *',    $g( 'grade_code' ), 'e.g. PP-HOMO-INJ (used by the API)' );
		self::txt( 'manufacturer',    'Manufacturer',    $g( 'manufacturer' ) );
		self::txt( 'supplier',        'Supplier',        $g( 'supplier' ) );
		self::txt( 'region',          'Region',          $g( 'region' ), 'e.g. UK / Europe' );
		self::num( 'market_price_per_kg',     'Market price £/kg (from feed)', $g( 'market_price_per_kg' ) );
		self::num( 'supplier_price_per_kg',   'Supplier price £/kg (what we pay)', $g( 'supplier_price_per_kg' ) );
		self::num( 'delivery_cost_per_kg',    'Delivery £/kg',     $g( 'delivery_cost_per_kg', '0' ) );
		self::num( 'handling_cost_per_kg',    'Handling £/kg',     $g( 'handling_cost_per_kg', '0' ) );
		self::num( 'masterbatch_cost_per_kg', 'Masterbatch £/kg',  $g( 'masterbatch_cost_per_kg', '0' ) );
		self::num( 'drying_cost_per_kg',      'Drying £/kg',       $g( 'drying_cost_per_kg', '0' ) );
		self::num( 'margin_percent',          'Margin %',          $g( 'margin_percent', '0' ) );
		self::txt( 'price_source',    'Price source label', $g( 'price_source', 'Supplier price list' ) );
		// active
		echo '<tr><th scope="row">Active</th><td><label><input type="checkbox" name="active" value="1" ' . checked( (int) $g( 'active', 1 ), 1, false ) . '> Available in the calculator</label></td></tr>';
		echo '</tbody></table>';
		echo '<p class="submit"><button class="button button-primary">Save material</button> '
			. '<a class="button" href="' . esc_url( self::url() ) . '">Cancel</a></p>';
		echo '</form>';

		// override box (edit only)
		if ( $r ) self::render_override_box( $r );
	}

	private static function render_override_box( $r ) {
		$quote = IH_Material_Pricing::compute_quote( $r );
		echo '<hr><h2>Manual override</h2>';
		echo '<p style="color:#666">Computed quote is <strong>' . self::money( $quote ) . '/kg</strong>. An override replaces it everywhere and is logged with your reason.</p>';
		if ( $r->is_manual_override && $r->manual_override_price > 0 ) {
			echo '<p>Current override: <strong>' . self::money( $r->manual_override_price ) . '/kg</strong> — ' . esc_html( $r->override_reason ) . '</p>';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline">';
			wp_nonce_field( 'ih_clear_override_' . $r->id );
			echo '<input type="hidden" name="action" value="ih_clear_override"><input type="hidden" name="id" value="' . esc_attr( $r->id ) . '">';
			echo '<button class="button">Clear override (revert to quote)</button></form>';
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:12px">';
		wp_nonce_field( 'ih_set_override_' . $r->id );
		echo '<input type="hidden" name="action" value="ih_set_override"><input type="hidden" name="id" value="' . esc_attr( $r->id ) . '">';
		echo '<input type="number" step="0.01" min="0" name="override_price" placeholder="£/kg" required style="width:120px"> ';
		echo '<input type="text" name="reason" placeholder="Reason (required, ≥5 chars)" required style="width:340px"> ';
		echo '<button class="button button-secondary">Apply override</button>';
		echo '</form>';
	}

	/* ============================================================
	   HISTORY
	   ============================================================ */
	private static function render_history() {
		global $wpdb;
		$id = (int) $_GET['id'];
		$mat = $wpdb->get_row( $wpdb->prepare( "SELECT material_name, grade_code FROM " . self::tbl() . " WHERE id = %d", $id ) );
		echo '<h2>Price history — ' . esc_html( $mat ? $mat->material_name : "#$id" ) . '</h2>';
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . self::htbl() . " WHERE material_id = %d ORDER BY changed_at DESC LIMIT 200", $id ) );
		echo '<table class="wp-list-table widefat fixed striped"><thead><tr>'
			. '<th>When (UTC)</th><th>Field</th><th>Old £/kg</th><th>New £/kg</th><th>Source</th><th>By</th><th>Reason</th>'
			. '</tr></thead><tbody>';
		if ( ! $rows ) echo '<tr><td colspan="7">No changes logged yet.</td></tr>';
		foreach ( $rows as $h ) {
			$user = $h->changed_by ? get_userdata( $h->changed_by ) : null;
			printf(
				'<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
				esc_html( $h->changed_at ), esc_html( $h->field ),
				self::money( $h->old_price_per_kg ), self::money( $h->new_price_per_kg ),
				esc_html( $h->source ), esc_html( $user ? $user->display_name : ( $h->changed_by ? '#' . $h->changed_by : 'system' ) ),
				esc_html( $h->reason )
			);
		}
		echo '</tbody></table>';
	}

	/* ============================================================
	   WRITE HANDLERS
	   ============================================================ */
	public static function handle_save() {
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		self::guard( 'ih_save_material_' . $id );
		global $wpdb;

		$name = sanitize_text_field( $_POST['material_name'] ?? '' );
		$code = sanitize_text_field( $_POST['grade_code'] ?? '' );
		if ( '' === $name || '' === $code ) self::bounce( 'error', $id ? [ 'view' => 'edit', 'id' => $id ] : [ 'view' => 'add' ] );

		$data = [
			'material_family'       => sanitize_text_field( $_POST['material_family'] ?? '' ),
			'material_name'         => $name,
			'grade'                 => sanitize_text_field( $_POST['grade'] ?? '' ),
			'grade_code'            => $code,
			'manufacturer'          => sanitize_text_field( $_POST['manufacturer'] ?? '' ),
			'supplier'              => sanitize_text_field( $_POST['supplier'] ?? '' ),
			'region'                => sanitize_text_field( $_POST['region'] ?? '' ),
			'currency'              => 'GBP',
			'unit'                  => 'kg',
			'market_price_per_kg'   => self::price( $_POST['market_price_per_kg'] ?? '' ),
			'supplier_price_per_kg' => self::price( $_POST['supplier_price_per_kg'] ?? '' ),
			'delivery_cost_per_kg'  => self::price( $_POST['delivery_cost_per_kg'] ?? 0, 0 ),
			'handling_cost_per_kg'  => self::price( $_POST['handling_cost_per_kg'] ?? 0, 0 ),
			'masterbatch_cost_per_kg'=> self::price( $_POST['masterbatch_cost_per_kg'] ?? 0, 0 ),
			'drying_cost_per_kg'    => self::price( $_POST['drying_cost_per_kg'] ?? 0, 0 ),
			'margin_percent'        => self::pct( $_POST['margin_percent'] ?? 0 ),
			'price_source'          => sanitize_text_field( $_POST['price_source'] ?? 'Supplier price list' ),
			'last_updated'          => current_time( 'mysql', true ),
			'active'                => empty( $_POST['active'] ) ? 0 : 1,
		];

		$t = self::tbl();
		if ( $id ) {
			$old = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE id = %d", $id ) );
			$wpdb->update( $t, $data, [ 'id' => $id ] );
		} else {
			$wpdb->insert( $t, $data );
			$id  = $wpdb->insert_id;
			$old = null;
		}
		// recompute & persist quote, log if it moved
		$fresh = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE id = %d", $id ) );
		$quote = IH_Material_Pricing::compute_quote( $fresh );
		$wpdb->update( $t, [ 'quote_price_per_kg' => $quote ], [ 'id' => $id ] );
		$old_quote = $old ? IH_Material_Pricing::compute_quote( $old ) : null;
		if ( is_null( $old_quote ) || (float) $old_quote !== (float) $quote ) {
			self::log( $id, 'quote_price_per_kg', $old_quote, $quote, 'admin edit', 'Saved in admin' );
		}
		self::bust();
		self::bounce( 'saved', [ 'view' => 'edit', 'id' => $id ] );
	}

	public static function handle_override() {
		$id = (int) $_POST['id'];
		self::guard( 'ih_set_override_' . $id );
		global $wpdb;
		$price  = self::price( $_POST['override_price'] ?? '' );
		$reason = sanitize_text_field( $_POST['reason'] ?? '' );
		if ( $price <= 0 || $price > 500 || mb_strlen( $reason ) < 5 ) self::bounce( 'error', [ 'view' => 'edit', 'id' => $id ] );

		$t = self::tbl();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE id = %d", $id ) );
		if ( ! $row ) self::bounce( 'error', [ 'view' => 'edit', 'id' => $id ] );
		$old = ( $row->is_manual_override && $row->manual_override_price > 0 ) ? $row->manual_override_price : IH_Material_Pricing::compute_quote( $row );
		$wpdb->update( $t, [
			'is_manual_override'    => 1,
			'manual_override_price' => $price,
			'override_reason'       => $reason,
			'last_updated'          => current_time( 'mysql', true ),
		], [ 'id' => $id ] );
		self::log( $id, 'manual_override_price', $old, $price, 'manual', $reason );
		self::bust();
		self::bounce( 'override', [ 'view' => 'edit', 'id' => $id ] );
	}

	public static function handle_clear_override() {
		$id = (int) $_POST['id'];
		self::guard( 'ih_clear_override_' . $id );
		global $wpdb;
		$t = self::tbl();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE id = %d", $id ) );
		if ( $row ) {
			$old   = $row->manual_override_price;
			$quote = IH_Material_Pricing::compute_quote( $row );
			$wpdb->update( $t, [ 'is_manual_override' => 0, 'manual_override_price' => null, 'override_reason' => '' ], [ 'id' => $id ] );
			self::log( $id, 'manual_override_price', $old, $quote, 'manual', 'Override cleared — reverted to quote' );
			self::bust();
		}
		self::bounce( 'cleared', [ 'view' => 'edit', 'id' => $id ] );
	}

	/* ============================================================
	   helpers
	   ============================================================ */
	private static function guard( $nonce_action ) {
		if ( ! current_user_can( self::CAP ) ) wp_die( 'Permission denied.' );
		check_admin_referer( $nonce_action );
	}
	private static function log( $id, $field, $old, $new, $source, $reason ) {
		global $wpdb;
		$wpdb->insert( self::htbl(), [
			'material_id' => $id, 'field' => $field,
			'old_price_per_kg' => is_null( $old ) ? null : $old, 'new_price_per_kg' => $new,
			'currency' => 'GBP', 'source' => $source,
			'changed_by' => get_current_user_id() ?: null, 'changed_at' => current_time( 'mysql', true ),
			'reason' => $reason,
		] );
	}
	private static function bust() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ih_price_%' OR option_name LIKE '_transient_timeout_ih_price_%'" );
	}
	private static function bounce( $msg, $args = [] ) {
		wp_safe_redirect( self::url( array_merge( $args, [ 'ih_msg' => $msg ] ) ) );
		exit;
	}
	private static function price( $v, $default = null ) { $v = floatval( str_replace( [ '£', ',', ' ' ], '', (string) $v ) ); if ( $v < 0 ) $v = 0; if ( $v > 500 ) $v = 500; return ( $v == 0 && is_null( $default ) ) ? null : $v; }
	private static function pct( $v ) { $v = floatval( $v ); return max( -100, min( 1000, $v ) ); }
	private static function money( $v ) { return is_null( $v ) || '' === $v ? '—' : '£' . number_format( (float) $v, 2 ); }
	private static function date( $v ) { return $v ? esc_html( mysql2date( 'j M Y H:i', $v ) ) : '—'; }
	private static function txt( $name, $label, $val, $hint = '' ) {
		printf(
			'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><input name="%1$s" id="%1$s" type="text" class="regular-text" value="%3$s">%4$s</td></tr>',
			esc_attr( $name ), esc_html( $label ), esc_attr( $val ),
			$hint ? '<p class="description">' . esc_html( $hint ) . '</p>' : ''
		);
	}
	private static function num( $name, $label, $val ) {
		printf(
			'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><input name="%1$s" id="%1$s" type="number" step="0.001" min="0" value="%3$s" style="width:140px"></td></tr>',
			esc_attr( $name ), esc_html( $label ), esc_attr( is_null( $val ) ? '' : $val )
		);
	}
}

IH_Pricing_Admin::init();
