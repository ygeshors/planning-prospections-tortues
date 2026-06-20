<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SHL_Tortues_Admin_Zones {

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
		$id     = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( 'save' === $action && isset( $_POST['_wpnonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'shl_zone_save' ) ) {
				wp_die( 'Nonce invalide.' );
			}
			self::save();
			return;
		}

		if ( 'delete' === $action && $id > 0 ) {
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'shl_zone_delete_' . $id ) ) {
				wp_die( 'Nonce invalide.' );
			}
			SHL_Tortues_DB::delete_zone( $id );
			wp_safe_redirect( admin_url( 'admin.php?page=shl-tortues-zones&msg=deleted' ) );
			exit;
		}

		if ( in_array( $action, array( 'new', 'edit' ), true ) ) {
			$zone = ( 'edit' === $action && $id > 0 ) ? SHL_Tortues_DB::get_zone( $id ) : null;
			include SHL_TORTUES_PLUGIN_DIR . 'admin/views/zone-form.php';
			return;
		}

		$zones = SHL_Tortues_DB::get_zones();
		include SHL_TORTUES_PLUGIN_DIR . 'admin/views/zones-list.php';
	}

	private static function save() {
		$id = isset( $_POST['zone_id'] ) ? intval( $_POST['zone_id'] ) : 0;

		$raw_geojson  = wp_unslash( $_POST['geojson_zone'] ?? '' );
		$geojson_zone = null;
		if ( $raw_geojson ) {
			$decoded = json_decode( $raw_geojson, true );
			$geojson_zone = ( json_last_error() === JSON_ERROR_NONE ) ? wp_json_encode( $decoded ) : null;
		}

		$data = array(
			'name'         => sanitize_text_field( wp_unslash( $_POST['name']        ?? '' ) ),
			'commune'      => sanitize_text_field( wp_unslash( $_POST['commune']     ?? '' ) ),
			'description'  => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ) ?: null,
			'gps_lat'      => sanitize_text_field( wp_unslash( $_POST['gps_lat']     ?? '' ) ) ?: null,
			'gps_lng'      => sanitize_text_field( wp_unslash( $_POST['gps_lng']     ?? '' ) ) ?: null,
			'geojson_zone' => $geojson_zone,
			'priority'     => max( 1, min( 5, intval( $_POST['priority'] ?? 3 ) ) ),
		);

		if ( empty( $data['name'] ) || empty( $data['commune'] ) ) {
			$back = $id ? 'edit&id=' . $id : 'new';
			wp_safe_redirect( admin_url( 'admin.php?page=shl-tortues-zones&action=' . $back . '&msg=missing' ) );
			exit;
		}

		if ( $id > 0 ) {
			SHL_Tortues_DB::update_zone( $id, $data );
		} else {
			SHL_Tortues_DB::insert_zone( $data );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=shl-tortues-zones&msg=saved' ) );
		exit;
	}
}
