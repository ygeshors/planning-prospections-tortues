<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SHL_Tortues_Admin_Rapport {

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$year     = isset( $_GET['year'] ) ? intval( $_GET['year'] ) : intval( gmdate( 'Y' ) );
		$season   = SHL_Tortues_DB::get_season_stats( $year );
		$extended = SHL_Tortues_DB::get_rapport_extended( $year );

		include SHL_TORTUES_PLUGIN_DIR . 'admin/views/rapport-saison.php';
	}
}
