<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SHL_Tortues_Admin_Tracks {

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Accès refusé.' );
		}
		$year   = intval( $_GET['year'] ?? gmdate( 'Y' ) );
		$tracks = SHL_Tortues_DB::get_all_tracks_with_meta( $year );
		include SHL_TORTUES_PLUGIN_DIR . 'admin/views/tracks-map.php';
	}
}
