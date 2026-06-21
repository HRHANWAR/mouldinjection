<?php
/**
 * class-ih-material-price-admin.php — "Material Price Checks" wp-admin panel.
 *
 * Insight Hub ▸ Material Price Checks. Shows the last 24h check status, which
 * sources are enabled/disabled, last success + last error per source, how many
 * reference prices were imported, recent reference prices and news/RSS items, and
 * the setting "Allow public reference prices for calculator estimates". Includes
 * a rate-limited "Check now" button.
 *
 * Repeats the clear note: PUBLIC REFERENCES ARE NOT SUPPLIER QUOTE PRICES.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IH_Material_Price_Admin {

	const CAP         = 'ih_manage_pricing';
	const SLUG        = 'ih-material-price-checks';
	const PARENT_SLUG = 'ih-dashboard';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_ih_price_check_now', array( __CLASS__, 'handle_check_now' ) );
		add_action( 'admin_post_ih_price_save_settings', array( __CLASS__, 'handle_save_settings' ) );
	}

	public static function menu() {
		add_submenu_page(
			self::PARENT_SLUG,
			'Material Price Checks',
			'Material Price Checks',
			self::CAP,
			self::SLUG,
			array( __CLASS__, 'render' )
		);
	}

	private static function url( $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::SLUG ), $args ), admin_url( 'admin.php' ) );
	}

	public static function render() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'You do not have permission to manage pricing.' );
		}
		$last    = IH_Material_Price_Check::last_run();
		$sources = IH_Material_Price_Check::sources();
		$refs    = IH_Material_Price_Check::recent_references( 25, false );
		$news    = IH_Material_Price_Check::recent_news( 15 );
		$next    = wp_next_scheduled( 'ih_material_price_check' );

		echo '<div class="wrap"><h1 class="wp-heading-inline">Material Price Checks</h1>';
		self::notices();

		// The single most important disclaimer on the screen.
		echo '<div class="notice notice-info" style="border-left-color:#5347ce"><p><strong>Public references are not supplier quote prices.</strong> They are indicative, often delayed market figures used to show whether material costs may be moving. Only a verified licensed feed is ever labelled “Live”.</p></div>';

		/* ---- Check-now + scheduling ---- */
		echo '<h2>Scheduled check</h2><p>';
		printf( 'Runs every <strong>%d hours</strong>. ', (int) IH_Material_Price_Config::interval_hours() );
		echo $next ? 'Next run: <strong>' . esc_html( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $next ), 'j M Y H:i' ) ) . '</strong>.' : 'Not currently scheduled.';
		echo '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-bottom:18px">';
		wp_nonce_field( 'ih_price_check_now' );
		echo '<input type="hidden" name="action" value="ih_price_check_now">';
		echo '<button class="button button-primary">Check now</button> <span class="description">Rate-limited to once a minute. No API keys are exposed.</span>';
		echo '</form>';

		/* ---- last run summary ---- */
		echo '<h2>Last 24h check</h2>';
		if ( empty( $last ) || empty( $last['started_at'] ) ) {
			echo '<p>No check has run yet. Click “Check now” to run one.</p>';
		} else {
			printf(
				'<p>Started: <strong>%s</strong> · Reference prices imported: <strong>%d</strong> · Live prices applied: <strong>%d</strong></p>',
				esc_html( $last['started_at'] ),
				(int) ( $last['reference_count'] ?? 0 ),
				(int) ( $last['live_applied'] ?? 0 )
			);
			if ( ! empty( $last['errors'] ) ) {
				echo '<details><summary style="cursor:pointer;color:#b45309">' . count( $last['errors'] ) . ' warning(s)/error(s)</summary><ul style="margin:8px 0 0 18px;list-style:disc">';
				foreach ( array_slice( $last['errors'], 0, 25 ) as $err ) {
					echo '<li>' . esc_html( $err ) . '</li>';
				}
				echo '</ul></details>';
			}
		}

		/* ---- sources table ---- */
		echo '<h2>Sources</h2>';
		echo '<table class="wp-list-table widefat fixed striped"><thead><tr>'
			. '<th>Source</th><th>Type</th><th>Enabled</th><th>Last status</th><th>Last checked</th><th>Last success</th><th># refs</th><th>Last error</th>'
			. '</tr></thead><tbody>';
		if ( ! $sources ) {
			echo '<tr><td colspan="8">No sources recorded yet — run a check.</td></tr>';
		}
		foreach ( (array) $sources as $s ) {
			printf(
				'<tr><td><strong>%s</strong></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%d</td><td style="color:#a00">%s</td></tr>',
				esc_html( $s->source_name ),
				esc_html( $s->source_type ),
				$s->enabled ? 'Yes' : '<span style="color:#a00">No</span>',
				esc_html( self::status_label( $s->last_status ) ),
				esc_html( self::date( $s->last_checked_at ) ),
				esc_html( self::date( $s->last_success_at ) ),
				(int) $s->reference_count,
				esc_html( wp_trim_words( (string) $s->last_error, 18 ) )
			);
		}
		echo '</tbody></table>';

		/* ---- recent reference prices ---- */
		echo '<h2>Recent reference prices</h2>';
		echo '<p class="description">Stored references — <strong>not</strong> applied to quotes unless you enable the option below. Index rows have no £/kg by design.</p>';
		echo '<table class="wp-list-table widefat fixed striped"><thead><tr>'
			. '<th>When</th><th>Polymer / family</th><th>Source</th><th>Type</th><th>Original</th><th>£/kg (GBP)</th><th>Index</th>'
			. '</tr></thead><tbody>';
		if ( ! $refs ) {
			echo '<tr><td colspan="7">No reference prices stored yet.</td></tr>';
		}
		foreach ( (array) $refs as $r ) {
			$orig = ( null !== $r->original_price && '' !== $r->original_price )
				? esc_html( $r->original_price . ' ' . $r->original_currency . '/' . $r->original_unit ) : '—';
			$gbp  = ( null !== $r->price_per_kg_gbp && '' !== $r->price_per_kg_gbp ) ? '£' . number_format( (float) $r->price_per_kg_gbp, 4 ) : '<span class="description">n/a</span>';
			$idx  = $r->is_index ? esc_html( $r->index_series_id . ' = ' . $r->index_value ) : '—';
			printf(
				'<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
				esc_html( self::date( $r->checked_at ) ),
				esc_html( trim( $r->polymer . ' ' . $r->material_family ) ?: $r->grade_code ),
				esc_html( $r->source_name ),
				esc_html( $r->source_type ),
				$orig, $gbp, $idx
			);
		}
		echo '</tbody></table>';

		/* ---- news / RSS ---- */
		echo '<h2>Market news (alerts only)</h2>';
		if ( ! $news ) {
			echo '<p>No market news items. News is informational only and never sets a price.</p>';
		} else {
			echo '<ul style="list-style:disc;margin-left:18px">';
			foreach ( (array) $news as $n ) {
				$meta  = json_decode( (string) $n->metadata_json, true );
				$title = is_array( $meta ) && ! empty( $meta['title'] ) ? $meta['title'] : ( $n->source_reference ?: 'Untitled' );
				$link  = $n->source_reference;
				if ( $link ) {
					printf( '<li><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></li>', esc_url( $link ), esc_html( $title ) );
				} else {
					printf( '<li>%s</li>', esc_html( $title ) );
				}
			}
			echo '</ul>';
		}

		/* ---- settings ---- */
		self::render_settings();

		echo '</div>';
	}

	private static function render_settings() {
		$allow      = IH_Material_Price_Config::allow_public_reference_for_quotes();
		$opt        = get_option( IH_Material_Price_Config::OPTION_ALLOW_PUBLIC_REF, null );
		$by_const   = ( null === $opt || '' === $opt ) && defined( 'IH_ALLOW_PUBLIC_REFERENCE_FOR_QUOTES' );
		echo '<hr><h2>Settings</h2>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'ih_price_save_settings' );
		echo '<input type="hidden" name="action" value="ih_price_save_settings">';
		echo '<table class="form-table" role="presentation"><tbody>';
		echo '<tr><th scope="row">Allow public reference prices for calculator estimates</th><td>';
		echo '<label><input type="checkbox" name="allow_public_reference" value="1" ' . checked( $allow, true, false ) . '> Use public market references as ESTIMATE prices when no manual / CSV / live price exists</label>';
		echo '<p class="description"><strong>Default: off.</strong> When OFF, public references are recorded and displayed but never used as a quote price. When ON, they may be used as a clearly-badged <em>Estimate</em> (never a “Quote”, never “Live”).';
		if ( $by_const ) {
			echo ' <em>Note: a wp-config constant is set; saving here creates an option that overrides it.</em>';
		}
		echo '</p>';
		echo '</td></tr>';
		echo '</tbody></table>';
		echo '<p class="submit"><button class="button button-primary">Save settings</button></p>';
		echo '</form>';
	}

	/* ============================================================
	   HANDLERS
	   ============================================================ */
	public static function handle_check_now() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'Permission denied.' );
		}
		check_admin_referer( 'ih_price_check_now' );

		if ( get_transient( IH_Material_Price_Config::CHECK_NOW_LOCK ) ) {
			self::bounce( 'ratelimited' );
		}
		set_transient( IH_Material_Price_Config::CHECK_NOW_LOCK, 1, MINUTE_IN_SECONDS );
		IH_Material_Price_Check::run( 'manual' );
		self::bounce( 'checked' );
	}

	public static function handle_save_settings() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'Permission denied.' );
		}
		check_admin_referer( 'ih_price_save_settings' );
		$allow = ! empty( $_POST['allow_public_reference'] ) ? 1 : 0;
		update_option( IH_Material_Price_Config::OPTION_ALLOW_PUBLIC_REF, $allow );
		self::bounce( 'saved' );
	}

	/* ============================================================
	   helpers
	   ============================================================ */
	private static function notices() {
		if ( empty( $_GET['ih_msg'] ) ) {
			return;
		}
		$map = array(
			'checked'     => array( 'success', 'Price check complete.' ),
			'saved'       => array( 'success', 'Settings saved.' ),
			'ratelimited' => array( 'error', 'A check ran very recently — please wait a minute.' ),
		);
		$k = sanitize_key( $_GET['ih_msg'] );
		if ( isset( $map[ $k ] ) ) {
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $map[ $k ][0] ), esc_html( $map[ $k ][1] ) );
		}
	}

	private static function bounce( $msg ) {
		wp_safe_redirect( self::url( array( 'ih_msg' => $msg ) ) );
		exit;
	}

	private static function status_label( $status ) {
		$map = array( 'success' => 'Success', 'partial' => 'Partial', 'failed' => 'Failed', 'skipped' => 'Skipped' );
		return isset( $map[ $status ] ) ? $map[ $status ] : ( $status ?: '—' );
	}

	private static function date( $v ) {
		return $v ? mysql2date( 'j M Y H:i', $v ) : '—';
	}
}
