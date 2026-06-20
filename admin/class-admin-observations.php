<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SHL_Tortues_Admin_Observations {

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
		$id     = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		// Suppression
		if ( 'delete' === $action && $id > 0 ) {
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'shl_obs_delete_' . $id ) ) {
				wp_die( 'Nonce invalide.' );
			}
			SHL_Tortues_Observations::delete( $id );
			wp_safe_redirect( admin_url( 'admin.php?page=shl-tortues-observations&msg=deleted' ) );
			exit;
		}

		// Filtres
		$args = array( 'limit' => 100 );
		if ( ! empty( $_GET['slot_id'] ) ) { $args['slot_id'] = intval( $_GET['slot_id'] ); }

		$observations = SHL_Tortues_Observations::get_all( $args );

		// Stats rapides
		global $wpdb;
		$to = $wpdb->prefix . 'shl_tortues_observations';
		$stats_obs = array(
			'total'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$to}`" ), // phpcs:ignore
			'with_photo'=> (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$to}` WHERE photo_url IS NOT NULL AND photo_url != ''" ), // phpcs:ignore
			'with_gps'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$to}` WHERE latitude IS NOT NULL" ), // phpcs:ignore
			'confirmed' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$to}` WHERE obs_type = 'confirmed'" ), // phpcs:ignore
			'suspect'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$to}` WHERE obs_type = 'suspect'" ), // phpcs:ignore
		);

		$current_slot = null;
		if ( ! empty( $args['slot_id'] ) ) {
			$current_slot = SHL_Tortues_DB::get_slot( $args['slot_id'] );
		}

		include SHL_TORTUES_PLUGIN_DIR . 'admin/views/observations-list.php';
	}
}
