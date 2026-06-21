<?php
/**
 * class-ih-rss-news-provider.php — news/RSS ALERTS ONLY.
 *
 * Pulls headlines from a polymer-market RSS/news feed so the admin panel can
 * surface "prices may be moving" signals. It emits source_type=news_reference
 * items that carry NO numeric quote price (price_per_kg_gbp is always null) and
 * are NEVER used by the quote-selection logic — they are informational alerts.
 *
 * Uses WordPress' built-in SimplePie via fetch_feed() when available; otherwise
 * a tolerant XML parse. Degrades gracefully (skipped/failed) and never throws.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IH_RSS_News_Provider implements IH_Material_Price_Provider {

	public function key()         { return 'rss_news'; }
	public function name()        { return 'Polymer market news (RSS)'; }
	public function source_type() { return IH_Material_Price_Config::SRC_NEWS_REFERENCE; }
	public function enabled()     { return IH_Material_Price_Config::rss_news_enabled(); }

	public function check() {
		if ( ! $this->enabled() ) {
			return IH_Material_Price_Result::skipped( $this->key(), $this->source_type(), 'RSS news provider disabled.' );
		}

		$url = IH_Material_Price_Config::rss_news_url();
		$url = apply_filters( 'ih_rss_market_news_url', $url );
		if ( '' === $url ) {
			return IH_Material_Price_Result::skipped(
				$this->key(),
				$this->source_type(),
				'No RSS feed configured (set IH_RSS_MARKET_NEWS_URL or the ih_rss_market_news_url filter).'
			);
		}

		$items  = array();
		$errors = array();

		if ( function_exists( 'fetch_feed' ) ) {
			$feed = fetch_feed( $url );
			if ( is_wp_error( $feed ) ) {
				return IH_Material_Price_Result::failed( $this->key(), $this->source_type(), 'RSS fetch failed: ' . $feed->get_error_message() );
			}
			$max     = (int) $feed->get_item_quantity( 10 );
			$entries = $feed->get_items( 0, $max );
			foreach ( $entries as $entry ) {
				$items[] = IH_Material_Price_Result::item( array(
					'source_type'       => $this->source_type(),
					'source_name'       => $this->name(),
					'source_reference'  => esc_url_raw( $entry->get_permalink() ),
					'price_per_kg_gbp'  => null, // alerts only, never a price
					'normalized'        => false,
					'is_public_reference' => true,
					'is_verified_live'  => false,
					'metadata'          => array(
						'title' => sanitize_text_field( $entry->get_title() ),
						'date'  => $entry->get_date( 'c' ),
					),
				) );
			}
		} else {
			// Fallback raw fetch (e.g. in the test harness without SimplePie).
			$res = IH_Material_Price_Utils::http_get( $url, array( 'timeout' => 15, 'headers' => array( 'Accept' => 'application/rss+xml' ) ) );
			if ( ! $res['ok'] ) {
				return IH_Material_Price_Result::failed( $this->key(), $this->source_type(), 'RSS fetch failed (HTTP ' . $res['code'] . ').' );
			}
			$xml = @simplexml_load_string( $res['body'] );
			if ( false === $xml ) {
				return IH_Material_Price_Result::failed( $this->key(), $this->source_type(), 'RSS feed was not valid XML.' );
			}
			$nodes = isset( $xml->channel->item ) ? $xml->channel->item : array();
			foreach ( $nodes as $node ) {
				$items[] = IH_Material_Price_Result::item( array(
					'source_type'       => $this->source_type(),
					'source_name'       => $this->name(),
					'source_reference'  => (string) $node->link,
					'price_per_kg_gbp'  => null,
					'normalized'        => false,
					'is_public_reference' => true,
					'metadata'          => array( 'title' => (string) $node->title, 'date' => (string) $node->pubDate ),
				) );
				if ( count( $items ) >= 10 ) {
					break;
				}
			}
		}

		if ( empty( $items ) ) {
			return IH_Material_Price_Result::make( $this->key(), $this->source_type(), IH_Material_Price_Result::STATUS_SUCCESS, array(), array(), 'No recent market news items.' );
		}
		return IH_Material_Price_Result::make( $this->key(), $this->source_type(), IH_Material_Price_Result::STATUS_SUCCESS, $items, $errors, 'Market news: ' . count( $items ) . ' headlines (alerts only).' );
	}
}
