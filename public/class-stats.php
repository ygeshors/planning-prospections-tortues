<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode [stats_prospections] – statistiques publiques de la saison.
 * Usage : [stats_prospections year="2026"]
 */
class SHL_Tortues_Stats {

	public static function init() {
		add_shortcode( 'stats_prospections', array( __CLASS__, 'render' ) );
	}

	public static function render( $atts ) {
		$atts  = shortcode_atts( array( 'year' => gmdate( 'Y' ) ), $atts, 'stats_prospections' );
		$year  = intval( $atts['year'] );
		$stats = SHL_Tortues_DB::get_season_stats( $year );

		wp_enqueue_script(
			'shl-chartjs-public',
			'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
			array(),
			'4.4.0',
			true
		);

		ob_start();
		include SHL_TORTUES_PLUGIN_DIR . 'public/views/stats.php';
		return ob_get_clean();
	}
}
