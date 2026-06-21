<?php
/**
 * ih-redesign-helpers.php — shared helpers for the v2.5 dashboard redesign.
 *
 * Source of truth: Figma file VfzCieeZ8ebjwm6vPiGajl (light = corporation, dark = admin).
 * Required once from insight-hub-dashboard.php. Every function is guarded so it is
 * safe to load alongside the existing plugin code. NO enqueue happens here — assets
 * are registered from the plugin's main admin_enqueue_scripts hook, scoped to the
 * dashboard screens, so the redesign loads last and wins.
 *
 * Data layer is wired to the REAL schema:
 *   - wp_ih_machines / wp_ih_tools own listings via `owner_id`
 *   - wp_ih_requests uses `request_date` + `status`
 */
defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------ *
 * 1.  Defensive value getter (array OR object rows)
 * ------------------------------------------------------------------ */
if ( ! function_exists( 'ih_val' ) ) {
	function ih_val( $row, $keys, $fallback = '' ) {
		foreach ( (array) $keys as $k ) {
			if ( is_array( $row ) && isset( $row[ $k ] ) && $row[ $k ] !== '' && $row[ $k ] !== null ) {
				return $row[ $k ];
			}
			if ( is_object( $row ) && isset( $row->{$k} ) && $row->{$k} !== '' && $row->{$k} !== null ) {
				return $row->{$k};
			}
		}
		return $fallback;
	}
}

/* ------------------------------------------------------------------ *
 * 2.  Reference IDs — anonymised, human-readable identifiers
 * ------------------------------------------------------------------ */
if ( ! function_exists( 'ih_listing_ref' ) ) {
	/** Tool/Machine ID e.g. TL-00231 / MCH-00018 */
	function ih_listing_ref( $row, $type = 'tool' ) {
		$id     = (int) ih_val( $row, array( 'id', 'ID' ), 0 );
		$prefix = ( $type === 'machine' ) ? 'MCH' : 'TL';
		return $prefix . '-' . str_pad( (string) $id, 5, '0', STR_PAD_LEFT );
	}
}
if ( ! function_exists( 'ih_user_ref' ) ) {
	/** Anonymised user id e.g. USR-00412 — never exposes a name. */
	function ih_user_ref( $user_id ) {
		return 'USR-' . str_pad( (string) (int) $user_id, 5, '0', STR_PAD_LEFT );
	}
}
if ( ! function_exists( 'ih_request_ref' ) ) {
	/** Unique request number e.g. REQ-2026-0014 (real column = request_date). */
	function ih_request_ref( $row ) {
		$id      = (int) ih_val( $row, array( 'id', 'ID' ), 0 );
		$created = ih_val( $row, array( 'request_date', 'created_at', 'created', 'date' ), '' );
		$year    = $created ? date( 'Y', strtotime( $created ) ) : date( 'Y' );
		return sprintf( 'REQ-%s-%04d', $year, $id );
	}
}

/* ------------------------------------------------------------------ *
 * 3.  Icon set — custom mould-injection line icons (inline SVG)
 * ------------------------------------------------------------------ */
if ( ! function_exists( 'ih_icon' ) ) {
	function ih_icon( $name, $size = 18, $stroke = 'currentColor' ) {
		$paths = array(
			'dashboard' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
			'mould'     => '<rect x="3" y="3.5" width="18" height="7" rx="1.2"/><rect x="3" y="13.5" width="18" height="7" rx="1.2"/><circle cx="8" cy="7" r="1.1"/><circle cx="12" cy="7" r="1.1"/><circle cx="16" cy="7" r="1.1"/>',
			'machine'   => '<rect x="2" y="8" width="7" height="9" rx="1"/><path d="M9 12.5h3.5"/><rect x="12.5" y="9.5" width="5.5" height="6" rx="1"/><path d="M18 12.5h4"/><path d="M15 9.5V6h2.5"/>',
			'listings'  => '<path d="M12 3l8.5 4.5L12 12 3.5 7.5 12 3z"/><path d="M3.5 12L12 16.5 20.5 12"/><path d="M3.5 16.5L12 21l8.5-4.5"/>',
			'pending'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5V12l3 2"/>',
			'messages'  => '<path d="M21 15a2 2 0 0 1-2 2H8l-4 3.5V5a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2z"/><path d="M8 9h9M8 12.5h6"/>',
			'corp'      => '<path d="M3 21h18"/><path d="M5 21V6l7-3 7 3v15"/><path d="M9 9h0M12 9h0M15 9h0M9 13h0M12 13h0M15 13h0"/><path d="M10.5 21v-4h3v4"/>',
			'approve'   => '<path d="M9 11.5l2.2 2.2L21 4"/><path d="M21 12.5V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
			'reject'    => '<circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/>',
			'browse'    => '<circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/>',
			'add'       => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>',
			'eye'       => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
			'pin'       => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
			'clock'     => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
			'user'      => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
			'users'     => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/>',
			'tool'      => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
		);
		$p = isset( $paths[ $name ] ) ? $paths[ $name ] : $paths['listings'];
		return '<svg width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="' . esc_attr( $stroke ) . '" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $p . '</svg>';
	}
}

/* ------------------------------------------------------------------ *
 * 4.  Stat tile — icon chip + value + % + sparkline (infograph tone)
 * ------------------------------------------------------------------ */
if ( ! function_exists( 'ih_stat_tile' ) ) {
	/**
	 * @param array $a icon,value,label,pct(nullable),tone(blue|green|amber|violet|rose|olive),spark(array of ints),href
	 */
	function ih_stat_tile( $a ) {
		$tone  = isset( $a['tone'] ) ? $a['tone'] : '';
		$cls   = $tone ? ' is-' . sanitize_html_class( $tone ) : '';
		$spark = isset( $a['spark'] ) && is_array( $a['spark'] ) && $a['spark'] ? $a['spark'] : array( 9, 13, 10, 16, 12, 18, 14, 21, 16, 23, 19, 28 );
		$max   = max( $spark ) ?: 1;
		$icon  = isset( $a['icon'] ) ? $a['icon'] : 'listings';
		$href  = isset( $a['href'] ) ? $a['href'] : '';
		$tag   = $href ? 'a' : 'div';
		$attr  = $href ? ' href="' . esc_url( $href ) . '"' : '';
		$label = isset( $a['label'] ) ? (string) $a['label'] : '';
		$unit  = isset( $a['spark_unit'] ) ? (string) $a['spark_unit'] : __( 'trend index', 'insight-hub-dashboard' );
		$bars  = array();
		foreach ( $spark as $i => $h ) {
			$period = isset( $a['spark_labels'][ $i ] ) ? (string) $a['spark_labels'][ $i ] : sprintf( __( 'Week %d', 'insight-hub-dashboard' ), $i + 1 );
			$bars[] = sprintf(
				'%1$s: %2$s %3$s',
				$period,
				number_format_i18n( (float) $h ),
				$unit
			);
		}
		$spark_summary = $label ? $label . ' trend. ' . implode( '; ', $bars ) : implode( '; ', $bars );
		ob_start(); ?>
		<<?php echo $tag; ?> class="ih-stat<?php echo $cls; ?>"<?php echo $attr; ?>>
			<div class="ih-stat-top">
				<span class="ih-stat-icon"><?php echo ih_icon( $icon, 20 ); ?></span>
				<?php if ( ! empty( $a['pct'] ) ) : ?>
					<span class="ih-stat-pill"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="18 15 12 9 6 15"/></svg><?php echo esc_html( $a['pct'] ); ?></span>
				<?php endif; ?>
			</div>
			<div class="ih-stat-value"><?php echo esc_html( $a['value'] ); ?></div>
			<div class="ih-stat-label"><?php echo esc_html( $label ); ?></div>
			<div class="ih-spark" role="list" aria-label="<?php echo esc_attr( $spark_summary ); ?>">
				<?php foreach ( $spark as $i => $h ) {
					$lit = $i >= count( $spark ) - 3;
					$period = isset( $a['spark_labels'][ $i ] ) ? (string) $a['spark_labels'][ $i ] : sprintf( __( 'Week %d', 'insight-hub-dashboard' ), $i + 1 );
					$title  = sprintf(
						/* translators: 1: metric label, 2: period label, 3: value, 4: unit label. */
						__( '%1$s, %2$s: %3$s %4$s', 'insight-hub-dashboard' ),
						$label,
						$period,
						number_format_i18n( (float) $h ),
						$unit
					);
					echo '<i role="listitem" tabindex="0" style="height:' . (int) round( $h / $max * 100 ) . '%;opacity:' . ( $lit ? 1 : .28 ) . '" data-ih-spark-bar="1" data-metric="' . esc_attr( $label ) . '" data-period="' . esc_attr( $period ) . '" data-value="' . esc_attr( (string) $h ) . '" data-unit="' . esc_attr( $unit ) . '" aria-label="' . esc_attr( $title ) . '"></i>';
				} ?>
			</div>
		</<?php echo $tag; ?>>
		<?php return ob_get_clean();
	}
}

/* ------------------------------------------------------------------ *
 * 5.  Status badge (maps any status string to a Figma badge)
 * ------------------------------------------------------------------ */
if ( ! function_exists( 'ih_status_badge' ) ) {
	function ih_status_badge( $status ) {
		$k = strtolower( trim( (string) $status ) );
		if ( in_array( $k, array( 'approved', 'available' ), true ) ) {
			$c = 'is-approved';
			$l = ucfirst( $k );
		} elseif ( in_array( $k, array( 'completed', 'complete' ), true ) ) {
			$c = 'is-completed';
			$l = 'Completed';
		} elseif ( in_array( $k, array( 'rejected', 'reject' ), true ) ) {
			$c = 'is-rejected';
			$l = 'Rejected';
		} else {
			$c = 'is-pending';
			$l = 'Pending';
		}
		return '<span class="ih-dash-status ' . $c . '">' . esc_html( $l ) . '</span>';
	}
}

/* ------------------------------------------------------------------ *
 * 6.  Inline SVG donut (stroke-dasharray segments)
 * ------------------------------------------------------------------ */
if ( ! function_exists( 'ih_rd_donut_svg' ) ) {
	/**
	 * @param array $segs each = array( 'v' => fraction 0..1, 'c' => colour )
	 */
	function ih_rd_donut_svg( $segs, $r = 58 ) {
		$c   = 2 * M_PI * $r;
		$off = 0;
		$out = '<circle class="ih-donut-track" cx="75" cy="75" r="' . $r . '" stroke="#e8eef0" stroke-width="16" fill="none"/>';
		foreach ( $segs as $i => $s ) {
			$len  = max( 0, (float) $s['v'] ) * $c;
			$out .= '<circle class="ih-donut-seg" data-seg="' . (int) $i . '" cx="75" cy="75" r="' . $r . '" fill="none" stroke="' . esc_attr( $s['c'] ) . '" stroke-width="16" stroke-linecap="round" stroke-dasharray="' . round( $len, 1 ) . ' ' . round( $c, 1 ) . '" stroke-dashoffset="' . round( -$off, 1 ) . '"/>';
			$off += $len;
		}
		return $out;
	}
}

/* ------------------------------------------------------------------ *
 * 7.  Browse-all query — every listing, anonymised by ID (owner_id)
 * ------------------------------------------------------------------ */
if ( ! function_exists( 'ih_browse_all_listings' ) ) {
	/**
	 * Returns a merged, anonymised list of machines + tools from every owner.
	 * Only IDs + safe spec columns are exposed — never names/emails.
	 * Falls back gracefully if a table is missing.
	 */
	function ih_browse_all_listings( $limit = 8 ) {
		global $wpdb;
		$out  = array();
		$limit = max( 1, (int) $limit );
		$sets = array(
			'machine' => $wpdb->prefix . 'ih_machines',
			'tool'    => $wpdb->prefix . 'ih_tools',
		);
		foreach ( $sets as $type => $table ) {
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
				continue;
			}
			$not_expired = function_exists( 'ih_listing_not_expired_sql' )
				? ih_listing_not_expired_sql( 'expiry_date' )
				: $wpdb->prepare( "(expiry_date IS NULL OR expiry_date = '0000-00-00' OR expiry_date >= %s)", current_time( 'Y-m-d' ) );
			$rows = $wpdb->get_results(
				"SELECT * FROM {$table} WHERE available=1 AND {$not_expired} ORDER BY id DESC LIMIT " . $limit,
				ARRAY_A
			);
			foreach ( (array) $rows as $r ) {
				$r['_type']     = $type;
				$r['_ref']      = ih_listing_ref( $r, $type );
				$r['_user_ref'] = ih_user_ref( ih_val( $r, array( 'owner_id', 'user_id', 'author' ), 0 ) );
				$out[]          = $r;
			}
		}
		return array_slice( $out, 0, $limit );
	}
}

/* ------------------------------------------------------------------ *
 * 8.  CSS bar-chart row from a 12-month series (value-ramp colours)
 * ------------------------------------------------------------------ */
if ( ! function_exists( 'ih_rd_bars' ) ) {
	/**
	 * @param array      $values      Numeric series.
	 * @param int        $current_idx Highlight index (-1 = none).
	 * @param array|null $labels      Optional axis labels (defaults to JAN–DEC).
	 */
	function ih_rd_bars( array $values, $current_idx = -1, $labels = null ) {
		if ( null === $labels ) {
			$labels = array( 'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC' );
		}
		$max = max( $values ?: array( 0 ) ) ?: 1;
		$out = '<div class="ih-bars">';
		foreach ( $values as $i => $v ) {
			$h   = (int) round( 16 + ( $v / $max ) * 150 );
			$cur = ( (int) $i === (int) $current_idx );
			if ( $cur ) {
				$col = 'linear-gradient(180deg,#887cfd,#5347ce)';
			} elseif ( $v >= $max * 0.8 ) {
				$col = 'linear-gradient(180deg,#43c46a,#16553a)';
			} else {
				$col = 'linear-gradient(180deg,#5fa0c4,#1f4a62)';
			}
			$out .= '<div class="col"><div class="bar" style="height:' . $h . 'px;background:' . $col . '" data-count="' . (int) $v . '"></div><span class="m">' . esc_html( $labels[ $i ] ?? '' ) . '</span></div>';
		}
		$out .= '</div>';
		return $out;
	}
}

/* ------------------------------------------------------------------ *
 * 9.  Dashboard analytics — week / month / year datasets + status
 * ------------------------------------------------------------------ */
if ( ! function_exists( 'ih_dash_analytics_periods' ) ) {
	/**
	 * Builds bar-chart + status payloads for the admin dashboard period filter.
	 *
	 * @return array{week:array,month:array,year:array}
	 */
	function ih_dash_analytics_periods() {
		global $wpdb;
		$table  = $wpdb->prefix . 'ih_requests';
		$now_ts = (int) current_time( 'timestamp' );
		$year   = (int) current_time( 'Y' );
		$month  = (int) current_time( 'n' );

		$month_labels  = array( 'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC' );
		$monthly_counts = array_fill( 0, 12, 0 );
		$monthly_rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT MONTH(request_date) AS m, COUNT(*) AS c
				 FROM {$table}
				 WHERE request_date IS NOT NULL AND YEAR(request_date)=%d
				 GROUP BY MONTH(request_date)",
				$year
			),
			ARRAY_A
		) ?: array();
		foreach ( $monthly_rows as $row ) {
			$idx = (int) $row['m'] - 1;
			if ( $idx >= 0 && $idx < 12 ) {
				$monthly_counts[ $idx ] = (int) $row['c'];
			}
		}

		$week_labels = array();
		$week_counts = array();
		$week_dates  = array();
		for ( $i = 6; $i >= 0; $i-- ) {
			$ts            = strtotime( "-{$i} days", $now_ts );
			$week_dates[]  = gmdate( 'Y-m-d', $ts );
			$week_labels[] = date_i18n( 'D', $ts );
		}
		$week_start = $week_dates[0];
		$week_end   = $week_dates[ count( $week_dates ) - 1 ];
		$week_map   = array_fill_keys( $week_dates, 0 );
		$week_rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(request_date) AS d, COUNT(*) AS c
				 FROM {$table}
				 WHERE request_date IS NOT NULL
				   AND DATE(request_date) >= %s AND DATE(request_date) <= %s
				 GROUP BY DATE(request_date)",
				$week_start,
				$week_end
			),
			ARRAY_A
		) ?: array();
		foreach ( $week_rows as $row ) {
			$d = $row['d'] ?? '';
			if ( isset( $week_map[ $d ] ) ) {
				$week_map[ $d ] = (int) $row['c'];
			}
		}
		foreach ( $week_dates as $d ) {
			$week_counts[] = $week_map[ $d ];
		}

		$year_labels = array();
		$year_counts = array();
		$year_rows   = $wpdb->get_results(
			"SELECT YEAR(request_date) AS y, COUNT(*) AS c
			 FROM {$table}
			 WHERE request_date IS NOT NULL
			 GROUP BY YEAR(request_date)
			 ORDER BY y ASC",
			ARRAY_A
		) ?: array();
		foreach ( $year_rows as $row ) {
			$year_labels[] = (string) (int) $row['y'];
			$year_counts[] = (int) $row['c'];
		}
		if ( count( $year_labels ) > 8 ) {
			$year_labels = array_slice( $year_labels, -8 );
			$year_counts = array_slice( $year_counts, -8 );
		}
		if ( empty( $year_labels ) ) {
			$year_labels = array( (string) $year );
			$year_counts = array( 0 );
		}

		$status_for = function ( $where_sql = '', $args = array() ) use ( $wpdb, $table ) {
			$sql = "SELECT LOWER(TRIM(status)) AS s, COUNT(*) AS c FROM {$table} WHERE request_date IS NOT NULL";
			if ( $where_sql ) {
				$sql .= ' AND ' . $where_sql;
			}
			$sql .= ' GROUP BY LOWER(TRIM(status))';
			$rows = $args ? $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A );
			$out  = array(
				'approved'  => 0,
				'pending'   => 0,
				'rejected'  => 0,
				'completed' => 0,
			);
			foreach ( (array) $rows as $r ) {
				$k = strtolower( trim( (string) ( $r['s'] ?? 'pending' ) ) );
				$c = (int) ( $r['c'] ?? 0 );
				if ( 'complete' === $k ) {
					$out['completed'] += $c;
				} elseif ( isset( $out[ $k ] ) ) {
					$out[ $k ] += $c;
				} else {
					$out['pending'] += $c;
				}
			}
			return $out;
		};

		return array(
			'week'  => array(
				'labels'      => $week_labels,
				'counts'      => $week_counts,
				'current_idx' => 6,
				'status'      => $status_for( 'DATE(request_date) >= %s AND DATE(request_date) <= %s', array( $week_start, $week_end ) ),
				'title'       => 'Request Analytics — Last 7 Days',
				'subtitle'    => 'Daily message-request volume.',
				'status_sub'  => 'Status split for the last 7 days.',
			),
			'month' => array(
				'labels'      => $month_labels,
				'counts'      => $monthly_counts,
				'current_idx' => $month - 1,
				'status'      => $status_for( 'YEAR(request_date) = %d', array( $year ) ),
				'title'       => 'Request Analytics — ' . $year,
				'subtitle'    => 'Monthly message-request volume across all listings.',
				'status_sub'  => 'Status split for ' . $year . '.',
			),
			'year'  => array(
				'labels'      => $year_labels,
				'counts'      => $year_counts,
				'current_idx' => max( 0, count( $year_counts ) - 1 ),
				'status'      => $status_for(),
				'title'       => 'Request Analytics — By Year',
				'subtitle'    => 'Yearly message-request totals.',
				'status_sub'  => 'All-time status split.',
			),
		);
	}
}

if ( ! function_exists( 'ih_status_pill' ) ) {
	/** Status badge with table pill styling. */
	function ih_status_pill( $status ) {
		$html = ih_status_badge( $status );
		return str_replace( 'class="ih-dash-status ', 'class="ih-dash-status ih-status-pill ', $html );
	}
}

if ( ! function_exists( 'ih_user_initials' ) ) {
	/** Two-letter initials for avatar fallbacks. */
	function ih_user_initials( $name ) {
		$parts = preg_split( '/\s+/', trim( (string) $name ), -1, PREG_SPLIT_NO_EMPTY );
		if ( count( $parts ) >= 2 ) {
			return strtoupper( substr( $parts[0], 0, 1 ) . substr( $parts[1], 0, 1 ) );
		}
		$clean = preg_replace( '/[^a-zA-Z0-9]/', '', (string) $name );
		return strtoupper( substr( $clean, 0, 2 ) ) ?: '?';
	}
}

if ( ! function_exists( 'ih_user_avatar_color' ) ) {
	/** Deterministic accent colour for initials avatars. */
	function ih_user_avatar_color( $seed ) {
		$palette = array( '#3380bd', '#8c5cf5', '#6f63e8', '#16a34a', '#d97706', '#4896fe' );
		$idx     = abs( crc32( (string) $seed ) ) % count( $palette );
		return $palette[ $idx ];
	}
}

if ( ! function_exists( 'ih_user_avatar_html' ) ) {
	/**
	 * Avatar markup with photo + initials fallback (table sm / drawer lg).
	 *
	 * @param int    $user_id
	 * @param string $name
	 * @param string $size    sm|lg
	 * @param string $class   extra classes
	 */
	function ih_user_avatar_html( $user_id, $name = '', $size = 'sm', $class = '' ) {
		$user_id = (int) $user_id;
		$name    = trim( (string) $name );
		if ( $name === '' && $user_id ) {
			$u = get_userdata( $user_id );
			$name = $u ? $u->display_name : '';
		}
		$initials = ih_user_initials( $name );
		$color    = ih_user_avatar_color( $user_id ?: $name );
		$img      = '';
		if ( $user_id && function_exists( 'ih_get_user_avatar_url' ) ) {
			$img = ih_get_user_avatar_url( $user_id, $size === 'lg' ? 120 : 72 );
		}
		if ( ! $img && $user_id ) {
			$img = get_avatar_url( $user_id, array( 'size' => $size === 'lg' ? 120 : 72 ) );
		}
		$size_cls = $size === 'lg' ? 'ih-u-av-lg' : 'ih-u-av-sm';
		$classes  = trim( 'ih-u-avatar ' . $size_cls . ' ' . $class );
		$html     = '<span class="' . esc_attr( $classes ) . '" style="--ih-av-color:' . esc_attr( $color ) . '">';
		if ( $img ) {
			$html .= '<img src="' . esc_url( $img ) . '" alt="" class="ih-u-avatar-img" loading="lazy" onerror="this.hidden=true;this.nextElementSibling.hidden=false">';
		}
		$html .= '<span class="ih-u-avatar-fallback"' . ( $img ? ' hidden' : '' ) . '>' . esc_html( $initials ) . '</span>';
		$html .= '</span>';
		return $html;
	}
}

if ( ! function_exists( 'ih_user_uid_label' ) ) {
	/** Display label e.g. SARLEE · USR-102 */
	function ih_user_uid_label( $unique_id, $user_id ) {
		$uid = strtoupper( preg_replace( '/[^a-zA-Z0-9]/', '', (string) $unique_id ) );
		if ( $uid === '' ) {
			$uid = 'USR' . (int) $user_id;
		}
		return $uid . ' · USR-' . (int) $user_id;
	}
}

/* ------------------------------------------------------------------ *
 * 10.  Owner "listing health" — completeness, status, expiry, requests
 *
 *  Read-only. Reuses the EXISTING calculations rather than inventing a
 *  new formula:
 *    - completeness field lists come straight from the detail pages
 *        · machines → $spec_fields in pages/machine-detail.php
 *        · tools    → $imd_completeness_fields in theme page-tool-detail.php
 *      (+ a single material-present bonus, exactly like the tool page).
 *    - status      → ih_listing_status_meta()  (plugin core)
 *    - expiry      → ih_listing_is_expired()    (plugin core)
 *    - not-expired → ih_listing_not_expired_sql() (used elsewhere here)
 * ------------------------------------------------------------------ */

if ( ! function_exists( 'ih_listing_completeness_fields' ) ) {
	/**
	 * The exact public field list each detail page measures completeness against.
	 *
	 * @param string $type 'machine' | 'tool'
	 * @return array
	 */
	function ih_listing_completeness_fields( $type ) {
		if ( $type === 'machine' ) {
			// Mirrors $spec_fields in pages/machine-detail.php.
			return array(
				'clamping_force', 'shot_size', 'screw_diameter', 'max_injection_pressure',
				'tie_bar_spacing', 'max_mould_height', 'min_mould_height', 'max_part_weight',
				'max_part_dimensions', 'tolerance', 'batch_size', 'min_order_qty',
				'max_monthly_output', 'avg_cycle_time', 'operating_hours', 'utilization',
				'location', 'automation_level', 'certifications', 'qc_tools',
				'tolerance_consistency',
			);
		}
		// Mirrors $imd_completeness_fields in theme/page-tool-detail.php.
		return array(
			'part_name', 'part_dimensions', 'part_weight', 'num_cavities_spec', 'mould_type',
			'mould_material', 'mould_condition', 'mould_dimensions', 'mould_weight', 'tool_life',
			'runner_type', 'gate_type', 'ejector_type', 'nozzle_type', 'surface_finish',
			'tolerance', 'draft_angle', 'cycle_time', 'annual_volume', 'min_order_qty',
			'colour', 'location',
		);
	}
}

if ( ! function_exists( 'ih_listing_field_has_value' ) ) {
	/** Same "is this field filled?" test the detail pages use. */
	function ih_listing_field_has_value( $value ) {
		$value = trim( (string) $value );
		return ( $value !== '' && $value !== '0' && strtolower( $value ) !== 'n/a' && $value !== '—' );
	}
}

if ( ! function_exists( 'ih_tool_has_materials' ) ) {
	/** Replicates imt_materials() presence check from the tool detail page. */
	function ih_tool_has_materials( $row ) {
		$raw = trim( (string) ih_val( $row, 'materials', '' ) );
		if ( $raw !== '' && $raw !== '[]' ) {
			return true;
		}
		if ( trim( (string) ih_val( $row, 'material', '' ) ) !== '' ) {
			return true;
		}
		foreach ( array( 'tolerance_pp', 'tolerance_abs', 'tolerance_pe' ) as $col ) {
			$v = strtolower( trim( (string) ih_val( $row, $col, '' ) ) );
			if ( $v === 'yes' || $v === '1' || $v === 'true' ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'ih_listing_completeness' ) ) {
	/**
	 * Listing completeness 0–100, faithful to the detail-page meters.
	 *
	 * @param array|object $row  Listing row.
	 * @param string       $type 'machine' | 'tool'
	 * @return int
	 */
	function ih_listing_completeness( $row, $type ) {
		$type   = ( $type === 'machine' ) ? 'machine' : 'tool';
		$fields = ih_listing_completeness_fields( $type );
		if ( ! $fields ) {
			return 0;
		}
		$filled = 0;
		foreach ( $fields as $f ) {
			if ( ih_listing_field_has_value( ih_val( $row, $f, '' ) ) ) {
				$filled++;
			}
		}
		if ( $type === 'tool' ) {
			// Tool detail page adds a single material-present point (denominator +1).
			if ( ih_tool_has_materials( $row ) ) {
				$filled++;
			}
			return (int) round( $filled / ( count( $fields ) + 1 ) * 100 );
		}
		return (int) round( 100 * $filled / count( $fields ) );
	}
}

if ( ! function_exists( 'ih_listing_completeness_tone' ) ) {
	/** Meter tone bucket consistent with the drawer/profile meters. */
	function ih_listing_completeness_tone( $pct ) {
		$pct = (int) $pct;
		if ( $pct >= 80 ) {
			return 'is-green';
		}
		if ( $pct >= 50 ) {
			return 'is-amber';
		}
		return 'is-rose';
	}
}

if ( ! function_exists( 'ih_listing_expiry_state' ) ) {
	/**
	 * Expiry status for a listing using the core expiry helpers.
	 *
	 * @param array|object $row       Listing row.
	 * @param int          $soon_days Amber window (default 14).
	 * @return array{state:string,days:?int,date:string,label:string}
	 *               state = expired | soon | ok | none
	 */
	function ih_listing_expiry_state( $row, $soon_days = 14 ) {
		$raw = trim( (string) ih_val( $row, 'expiry_date', '' ) );
		if ( $raw === '' || $raw === '0000-00-00' || ! preg_match( '/^\d{4}-\d{2}-\d{2}/', $raw ) ) {
			return array( 'state' => 'none', 'days' => null, 'date' => '', 'label' => 'No expiry' );
		}
		$date = date_i18n( 'd M Y', strtotime( $raw ) );
		if ( function_exists( 'ih_listing_is_expired' ) && ih_listing_is_expired( $row ) ) {
			return array( 'state' => 'expired', 'days' => 0, 'date' => $date, 'label' => 'Expired' );
		}
		$today   = strtotime( current_time( 'Y-m-d' ) );
		$expiry  = strtotime( substr( $raw, 0, 10 ) );
		$days    = (int) max( 0, floor( ( $expiry - $today ) / DAY_IN_SECONDS ) );
		if ( $days <= (int) $soon_days ) {
			return array(
				'state' => 'soon',
				'days'  => $days,
				'date'  => $date,
				'label' => $days === 0 ? 'Expires today' : sprintf( _n( '%d day left', '%d days left', $days, 'insight-hub-dashboard' ), $days ),
			);
		}
		return array( 'state' => 'ok', 'days' => $days, 'date' => $date, 'label' => 'Active' );
	}
}

if ( ! function_exists( 'ih_owner_listing_request_counts' ) ) {
	/**
	 * Incoming detail/contact request counts for an owner's listings, keyed by
	 * "type:id". Excludes the owner's own rows (e.g. listing-approval requests).
	 *
	 * @param int $user_id Owner.
	 * @return array<string,array{total:int,pending:int}>
	 */
	function ih_owner_listing_request_counts( $user_id ) {
		global $wpdb;
		$user_id = (int) $user_id;
		$rtbl    = $wpdb->prefix . 'ih_requests';
		$map     = array();
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $rtbl ) ) !== $rtbl ) {
			return $map;
		}
		$sources = array(
			'machine' => array( $wpdb->prefix . 'ih_machines', array( 'machine', 'machine_contact' ) ),
			'tool'    => array( $wpdb->prefix . 'ih_tools', array( 'tool', 'tool_contact' ) ),
		);
		foreach ( $sources as $type => $cfg ) {
			list( $ltbl, $types ) = $cfg;
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $ltbl ) ) !== $ltbl ) {
				continue;
			}
			$placeholders = implode( ',', array_fill( 0, count( $types ), '%s' ) );
			$args         = array_merge( array( $user_id ), $types, array( $user_id ) );
			$rows         = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT r.listing_id AS lid,
					        COUNT(*) AS total,
					        SUM(CASE WHEN LOWER(TRIM(r.status)) = 'pending' THEN 1 ELSE 0 END) AS pending
					 FROM {$rtbl} r
					 INNER JOIN {$ltbl} l ON l.id = r.listing_id AND l.owner_id = %d
					 WHERE r.listing_type IN ({$placeholders})
					   AND r.user_id != %d
					 GROUP BY r.listing_id",
					$args
				),
				ARRAY_A
			) ?: array();
			foreach ( $rows as $r ) {
				$map[ $type . ':' . (int) $r['lid'] ] = array(
					'total'   => (int) $r['total'],
					'pending' => (int) $r['pending'],
				);
			}
		}
		return $map;
	}
}

if ( ! function_exists( 'ih_owner_listing_health' ) ) {
	/**
	 * Builds the owner "listing health" payload: one entry per owned listing
	 * plus a summary strip. Owner-scoped, read-only.
	 *
	 * @param int        $user_id  Current user.
	 * @param array|null $machines Optional pre-fetched machine rows.
	 * @param array|null $tools    Optional pre-fetched tool rows.
	 * @return array{listings:array,summary:array}
	 */
	function ih_owner_listing_health( $user_id, $machines = null, $tools = null ) {
		global $wpdb;
		$user_id = (int) $user_id;

		if ( ! is_array( $machines ) ) {
			$machines = $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ih_machines WHERE owner_id=%d ORDER BY id DESC", $user_id ),
				ARRAY_A
			) ?: array();
		}
		if ( ! is_array( $tools ) ) {
			$tools = $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ih_tools WHERE owner_id=%d ORDER BY id DESC", $user_id ),
				ARRAY_A
			) ?: array();
		}

		$req_counts = ih_owner_listing_request_counts( $user_id );

		$listings = array();
		$summary  = array(
			'total'          => 0,
			'live'           => 0,
			'pending'        => 0,
			'expiring_soon'  => 0,
			'expired'        => 0,
			'total_requests' => 0,
			'new_requests'   => 0,
		);

		$build = function ( $row, $type ) use ( &$summary, $req_counts ) {
			$id     = (int) ih_val( $row, array( 'id', 'ID' ), 0 );
			$status = function_exists( 'ih_listing_status_meta' )
				? ih_listing_status_meta( $row )
				: array( 'key' => ! empty( $row['available'] ) ? 'available' : 'pending', 'label' => '', 'class' => 'is-pending' );
			$key    = $status['key'] ?? 'pending';
			$comp   = ih_listing_completeness( $row, $type );
			$expiry = ih_listing_expiry_state( $row );
			$rc     = $req_counts[ $type . ':' . $id ] ?? array( 'total' => 0, 'pending' => 0 );

			$title = trim( (string) ih_val( $row, 'title', '' ) );
			if ( $title === '' ) {
				$title = ( $type === 'machine' ? 'Machine · ' : 'Mould · ' ) . ih_listing_ref( $row, $type );
			}

			if ( $type === 'machine' ) {
				$view_url = admin_url( 'admin.php?page=ih-user-view-machine&id=' . $id );
				$edit_url = admin_url( 'admin.php?page=ih-user-edit-machine&machine_id=' . $id );
			} else {
				$view_url = admin_url( 'admin.php?page=ih-user-view-tool&id=' . $id );
				$edit_url = admin_url( 'admin.php?page=ih-user-edit-tool&tool_id=' . $id );
			}

			$summary['total']++;
			if ( $key === 'available' ) {
				$summary['live']++;
			} elseif ( $key === 'pending' ) {
				$summary['pending']++;
			}
			if ( $expiry['state'] === 'expired' ) {
				$summary['expired']++;
			} elseif ( $expiry['state'] === 'soon' ) {
				$summary['expiring_soon']++;
			}
			$summary['total_requests'] += (int) $rc['total'];
			$summary['new_requests']   += (int) $rc['pending'];

			return array(
				'id'           => $id,
				'type'         => $type,
				'title'        => $title,
				'ref'          => ih_listing_ref( $row, $type ),
				'completeness' => $comp,
				'comp_tone'    => ih_listing_completeness_tone( $comp ),
				'status'       => $status,
				'status_key'   => $key,
				'expiry'       => $expiry,
				'req_total'    => (int) $rc['total'],
				'req_pending'  => (int) $rc['pending'],
				'view_url'     => $view_url,
				'edit_url'     => $edit_url,
			);
		};

		foreach ( $tools as $row ) {
			$listings[] = $build( $row, 'tool' );
		}
		foreach ( $machines as $row ) {
			$listings[] = $build( $row, 'machine' );
		}

		return array(
			'listings' => $listings,
			'summary'  => $summary,
		);
	}
}
