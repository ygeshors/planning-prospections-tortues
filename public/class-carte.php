<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode [carte_prospections] — carte interactive des créneaux à venir.
 */
class SHL_Tortues_Carte {

	public static function init() {
		add_shortcode( 'carte_prospections', array( __CLASS__, 'shortcode' ) );
	}

	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array( 'days' => 30 ), $atts );
		$days = max( 7, min( 90, intval( $atts['days'] ) ) );

		wp_enqueue_style(  'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
		wp_enqueue_script( 'leaflet-js',  'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',  array(), '1.9.4', true );
		wp_enqueue_style(  'shl-public-css', SHL_TORTUES_PLUGIN_URL . 'assets/css/public.css', array(), SHL_TORTUES_VERSION );

		// Créneaux à venir
		global $wpdb;
		$st = SHL_Tortues_DB::slots_table();
		$zt = SHL_Tortues_DB::zones_table();
		$date_from = gmdate( 'Y-m-d' );
		$date_to   = gmdate( 'Y-m-d', strtotime( "+{$days} days" ) );

		$slots = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
			"SELECT s.*, z.geojson_zone, z.gps_lat, z.gps_lng
			 FROM `{$st}` s
			 LEFT JOIN `{$zt}` z ON z.id = s.zone_id
			 WHERE s.date >= %s AND s.date <= %s
			   AND s.status NOT IN ('cancelled','done')
			 ORDER BY s.date, s.time_start
			 LIMIT 100",
			$date_from, $date_to
		) );

		$color = get_option( 'shl_tortues_primary_color', '#2E86AB' );

		ob_start();
		include SHL_TORTUES_PLUGIN_DIR . 'public/views/carte.php';
		return ob_get_clean();
	}
}
