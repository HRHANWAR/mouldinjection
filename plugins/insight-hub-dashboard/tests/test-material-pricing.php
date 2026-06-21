<?php
/**
 * tests/test-material-pricing.php — self-contained assertion suite for the pure
 * material-price logic: badge mapping, quote-selection order, unit + currency
 * conversion, normalisation, and provider source-failure handling.
 *
 * Run:  php tests/test-material-pricing.php
 * External HTTP is mocked (see bootstrap.php) — NO real network calls.
 */

require __DIR__ . '/bootstrap.php';

$tests  = 0;
$passed = 0;
$fails  = array();

function check( $cond, $label ) {
	global $tests, $passed, $fails;
	$tests++;
	if ( $cond ) {
		$passed++;
		echo "  PASS  {$label}\n";
	} else {
		$fails[] = $label;
		echo "  FAIL  {$label}\n";
	}
}
function approx( $a, $b, $eps = 0.0001 ) { return abs( (float) $a - (float) $b ) < $eps; }

echo "\n== Badge logic ==\n";
$C = 'IH_Material_Price_Config';
$U = 'IH_Material_Price_Utils';

$b = $U::get_price_badge( constant( "$C::SRC_LIVE_FEED" ), true );
check( 'live' === $b['state'] && 'Live' === $b['label'], 'live_feed + verified → Live' );

$b = $U::get_price_badge( constant( "$C::SRC_LIVE_FEED" ), false );
check( 'live' !== $b['state'] && 'Live' !== $b['label'], 'live_feed NOT verified → NOT Live (critical)' );

$b = $U::get_price_badge( constant( "$C::SRC_PUBLIC_MARKET_REFERENCE" ), true );
check( 'public' === $b['state'] && 'Live' !== $b['label'], 'public reference never Live even if flag set' );

$cases = array(
	'SRC_CSV_IMPORTED'             => array( 'csv', 'CSV imported' ),
	'SRC_MANUAL_OVERRIDE'          => array( 'override', 'Manual override' ),
	'SRC_MONTHLY_INDEX'            => array( 'index', 'Monthly index' ),
	'SRC_DELAYED_PUBLIC_REFERENCE' => array( 'delayed', 'Delayed reference' ),
	'SRC_DEFAULT_ESTIMATE'         => array( 'estimate', 'Default estimate' ),
	'SRC_MANUAL_REQUIRED'          => array( 'manual', 'Manual required' ),
	'SRC_NEWS_REFERENCE'           => array( 'news', 'News reference' ),
);
foreach ( $cases as $const => $exp ) {
	$b = $U::get_price_badge( constant( "$C::$const" ), false );
	check( $exp[0] === $b['state'] && $exp[1] === $b['label'], "badge {$const} → {$exp[1]}" );
}
$b = $U::get_price_badge( 'something_unknown', false );
check( 'manual' === $b['state'], 'unknown source_type → manual fallback' );

echo "\n== Unit + currency conversion ==\n";
check( approx( $U::to_gbp_per_kg( 1000, 'GBP', 'tonne' ), 1.0 ), 'tonne→kg: 1000/tonne = 1.0/kg' );
check( approx( $U::to_gbp_per_kg( 1, 'GBP', 'kg' ), 1.0 ), 'kg stays kg' );
check( approx( $U::to_gbp_per_kg( 1, 'GBP', 'g' ), 1000.0 ), 'gram→kg: 1/g = 1000/kg' );
check( approx( $U::to_gbp_per_kg( 1, 'GBP', 'lb' ), 1 / 0.45359237 ), 'lb→kg: 1/lb = 2.2046/kg' );
check( approx( $U::to_gbp_per_kg( 7000, 'CNY', 'tonne' ), 7000 / 1000 * 0.11 ), 'CNY/tonne → GBP/kg (0.11 fx)' );
check( null === $U::to_gbp_per_kg( 100, 'XYZ', 'kg' ), 'unknown currency → null (never guess)' );
check( null === $U::to_gbp_per_kg( 100, 'GBP', 'furlong' ), 'unknown unit → null (never guess)' );
check( null === $U::fx_to_gbp( 'ZZZ' ), 'unknown fx → null' );
check( approx( $U::fx_to_gbp( 'GBP' ), 1.0 ), 'GBP fx = 1.0' );

echo "\n== Normalisation ==\n";
$N = 'IH_Material_Price_Normalisation';
$R = 'IH_Material_Price_Result';

$item = $R::item( array( 'source_type' => constant( "$C::SRC_MONTHLY_INDEX" ), 'is_index' => true, 'index_value' => 250.3 ) );
$item = $N::normalise( $item );
check( null === $item['price_per_kg_gbp'] && $item['is_index'], 'monthly_index normalised to index, no £/kg' );

$item = $R::item( array( 'source_type' => constant( "$C::SRC_PUBLIC_MARKET_REFERENCE" ), 'original_price' => 7000, 'original_currency' => 'CNY', 'original_unit' => 'tonne' ) );
$item = $N::normalise( $item );
check( $item['normalized'] && approx( $item['price_per_kg_gbp'], 0.77 ), 'public CNY/tonne normalised to ~£0.77/kg' );

$item = $R::item( array( 'source_type' => constant( "$C::SRC_PUBLIC_MARKET_REFERENCE" ), 'original_price' => 100, 'original_currency' => 'XYZ', 'original_unit' => 'kg' ) );
$item = $N::normalise( $item );
check( ! $item['normalized'] && null === $item['price_per_kg_gbp'], 'unconvertible item → normalized=false, no price' );

$item = $R::item( array( 'source_type' => constant( "$C::SRC_PUBLIC_MARKET_REFERENCE" ), 'original_price' => 99999, 'original_currency' => 'GBP', 'original_unit' => 'kg' ) );
$item = $N::normalise( $item );
check( ! $item['normalized'] && null === $item['price_per_kg_gbp'], 'implausible >£500/kg rejected' );

echo "\n== Selection order ==\n";
$S = 'IH_Material_Price_Selection';

$full = array(
	'manual_override_price'  => 2.10,
	'csv_price'              => 1.80,
	'live_feed_price'        => 1.50,
	'live_feed_verified'     => true,
	'public_reference_price' => 1.20,
	'default_estimate_price' => 1.00,
	'allow_public_reference' => true,
	'price_age_days'         => 0,
);
$r = $S::select( $full );
check( constant( "$C::SRC_MANUAL_OVERRIDE" ) === $r['source_type'] && 'Quote' === $r['calculation_type'] && approx( $r['price_per_kg'], 2.10 ), '1) manual override wins' );

$ctx = $full; unset( $ctx['manual_override_price'] );
$r = $S::select( $ctx );
check( constant( "$C::SRC_CSV_IMPORTED" ) === $r['source_type'] && 'Quote' === $r['calculation_type'] && approx( $r['price_per_kg'], 1.80 ), '2) supplier CSV second' );

$ctx = $full; unset( $ctx['manual_override_price'], $ctx['csv_price'] );
$r = $S::select( $ctx );
check( constant( "$C::SRC_LIVE_FEED" ) === $r['source_type'] && true === $r['is_verified_live'] && 'Live' === $r['badge']['label'], '3) verified live feed third → Live' );

// Live feed present but NOT verified must fall through to public reference.
$ctx = $full; unset( $ctx['manual_override_price'], $ctx['csv_price'] );
$ctx['live_feed_verified'] = false;
$r = $S::select( $ctx );
check( constant( "$C::SRC_PUBLIC_MARKET_REFERENCE" ) === $r['source_type'] && 'Estimate' === $r['calculation_type'] && '' !== $r['warning'], '3b) unverified feed → falls through to public Estimate' );

// Public reference only allowed when the flag is on.
$ctx = array( 'public_reference_price' => 1.20, 'default_estimate_price' => 1.00, 'allow_public_reference' => false, 'price_age_days' => 0 );
$r = $S::select( $ctx );
check( constant( "$C::SRC_DEFAULT_ESTIMATE" ) === $r['source_type'], '4) public reference SKIPPED when option off → default estimate' );

$ctx['allow_public_reference'] = true;
$r = $S::select( $ctx );
check( constant( "$C::SRC_PUBLIC_MARKET_REFERENCE" ) === $r['source_type'] && 'Estimate' === $r['calculation_type'], '4b) public reference used when option on (Estimate)' );

$ctx = array( 'default_estimate_price' => 1.00, 'price_age_days' => 0 );
$r = $S::select( $ctx );
check( constant( "$C::SRC_DEFAULT_ESTIMATE" ) === $r['source_type'] && 'Estimate' === $r['calculation_type'], '5) default estimate fifth' );

$r = $S::select( array() );
check( constant( "$C::SRC_MANUAL_REQUIRED" ) === $r['source_type'] && 'Incomplete' === $r['calculation_type'], '6) nothing → manual required (Incomplete)' );

// Staleness warning appended.
$ctx = array( 'csv_price' => 1.5, 'price_age_days' => 9 );
$r = $S::select( $ctx );
check( false !== strpos( $r['warning'], 'older than 7 days' ), 'stale (>7d) warning appended' );

$ctx = array( 'public_reference_price' => 1.5, 'allow_public_reference' => true, 'price_age_days' => 50, 'source_name' => 'x' );
// delayed window only applies to delayed_public_reference; public ref uses 7d.
$r = $S::select( $ctx );
check( false !== strpos( $r['warning'], 'older than 7 days' ), 'public ref >7d → 7-day warning' );

echo "\n== Provider source-failure handling (mocked HTTP) ==\n";

// Trading Economics: HTTP 500 → failed, no throw, no items.
$GLOBALS['ih_http_handler'] = function () { return array( 'code' => 500, 'body' => 'err' ); };
$te = new IH_Trading_Economics_Provider();
$res = $te->check();
check( $R::STATUS_FAILED === $res['status'] && empty( $res['items'] ), 'TE HTTP 500 → failed gracefully' );

// Trading Economics: valid commodities JSON → success, normalised, NOT live.
$GLOBALS['ih_http_handler'] = function () {
	return array( 'code' => 200, 'body' => json_encode( array(
		array( 'Category' => 'Polypropylene', 'Symbol' => 'PP', 'Last' => 7000, 'Currency' => 'CNY', 'unit' => 'tonne' ),
		array( 'Category' => 'Polyethylene',  'Symbol' => 'PE', 'Last' => 8000, 'Currency' => 'CNY', 'unit' => 'tonne' ),
	) ) );
};
$res = $te->check();
$first = $res['items'][0] ?? array();
check( $R::STATUS_SUCCESS === $res['status'] && count( $res['items'] ) >= 2, 'TE valid JSON → success with items' );
check( isset( $first['is_verified_live'] ) && false === $first['is_verified_live'], 'TE items NEVER verified-live' );
check( constant( "$C::SRC_PUBLIC_MARKET_REFERENCE" ) === $first['source_type'], 'TE source_type = public_market_reference' );
check( approx( $first['price_per_kg_gbp'], 0.77 ), 'TE CNY/tonne normalised to ~£0.77/kg' );

// Trading Economics: WP_Error transport failure → failed, no throw.
$GLOBALS['ih_http_handler'] = function () { return new WP_Error( 'down', 'connection refused' ); };
$res = $te->check();
check( $R::STATUS_FAILED === $res['status'], 'TE transport error → failed gracefully' );

// FRED: no API key → skipped (key not configured in tests).
$fred = new IH_FRED_Provider();
$res = $fred->check();
check( $R::STATUS_SKIPPED === $res['status'], 'FRED without API key → skipped' );

// Licensed feed: configured (bootstrap defines URL) but HTTP 500 → failed.
$GLOBALS['ih_http_handler'] = function () { return array( 'code' => 500, 'body' => '' ); };
$lf = new IH_Licensed_Live_Feed_Provider();
$res = $lf->check();
check( $R::STATUS_FAILED === $res['status'], 'Licensed feed HTTP 500 → failed gracefully' );

// Licensed feed: valid rows → success, source_type live_feed, verified live.
$GLOBALS['ih_http_handler'] = function () {
	return array( 'code' => 200, 'body' => json_encode( array(
		array( 'grade_code' => 'PP-HOMO-INJ', 'price' => 1.45, 'currency' => 'GBP', 'unit' => 'kg', 'source' => 'MyLicensedFeed' ),
	) ) );
};
$res = $lf->check();
$it = $res['items'][0] ?? array();
check( $R::STATUS_SUCCESS === $res['status'] && constant( "$C::SRC_LIVE_FEED" ) === $res['source_type'], 'Licensed feed valid → success live_feed' );
check( ! empty( $it['is_verified_live'] ) && approx( $it['price_per_kg_gbp'], 1.45 ), 'Licensed feed item verified-live + normalised' );

/* ---- summary ---- */
echo "\n" . str_repeat( '=', 48 ) . "\n";
echo "Results: {$passed}/{$tests} passed\n";
if ( $fails ) {
	echo "FAILURES:\n";
	foreach ( $fails as $f ) {
		echo "  - {$f}\n";
	}
	exit( 1 );
}
echo "ALL TESTS PASSED\n";
exit( 0 );
