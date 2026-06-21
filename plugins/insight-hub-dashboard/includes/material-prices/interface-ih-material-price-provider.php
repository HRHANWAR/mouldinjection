<?php
/**
 * interface-ih-material-price-provider.php
 *
 * The shared provider contract. Every market-reference source (Trading
 * Economics, FRED, PlasticPortal, Plasticker, RSS news, the licensed live feed)
 * implements this interface, so the orchestrator can iterate them uniformly and
 * wrap each in try/catch.
 *
 * check() MUST NOT throw. It returns a normalised result array (see
 * IH_Material_Price_Result), the PHP equivalent of the spec's
 * `MaterialPriceProviderResult` / `MaterialPriceReferenceItem` shapes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface IH_Material_Price_Provider {

	/** Stable machine key, e.g. "trading_economics". */
	public function key();

	/** Human-readable source name, e.g. "Trading Economics". */
	public function name();

	/** The source_type string this provider emits (IH_Material_Price_Config::SRC_*). */
	public function source_type();

	/** Whether this provider is currently enabled (config-gated). */
	public function enabled();

	/**
	 * Run the check. MUST return an IH_Material_Price_Result array and MUST NOT
	 * throw — on any failure return a 'failed' / 'skipped' result instead.
	 *
	 * @return array
	 */
	public function check();
}
