<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SHL_Tortues_Admin_Settings {

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$saved = false;

		if ( isset( $_POST['shl_save_settings'] ) ) {
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'shl_settings_save' ) ) {
				wp_die( 'Nonce invalide.' );
			}

			update_option( 'shl_tortues_admin_email',            sanitize_email( wp_unslash( $_POST['admin_email'] ?? '' ) ) );
			update_option( 'shl_tortues_show_names',             isset( $_POST['show_names'] ) ? '1' : '0' );
			update_option( 'shl_tortues_default_places',         max( 1, intval( $_POST['default_places'] ?? 2 ) ) );
			update_option( 'shl_tortues_primary_color',          sanitize_hex_color( wp_unslash( $_POST['primary_color'] ?? '#2E86AB' ) ) );
			update_option( 'shl_tortues_confirm_message',        sanitize_textarea_field( wp_unslash( $_POST['confirm_message'] ?? '' ) ) );
			update_option( 'shl_tortues_general_instructions',   sanitize_textarea_field( wp_unslash( $_POST['general_instructions'] ?? '' ) ) );
			update_option( 'shl_tortues_benevole_url',           esc_url_raw( wp_unslash( $_POST['benevole_url'] ?? '' ) ) );
			update_option( 'shl_tortues_allow_uninstall_cleanup',isset( $_POST['allow_uninstall_cleanup'] ) ? '1' : '0' );
			update_option( 'shl_tortues_weather_alert_days', max( 1, intval( $_POST['weather_alert_days'] ?? 2 ) ) );
			update_option( 'shl_tortues_weather_alert_wind', max( 20, intval( $_POST['weather_alert_wind'] ?? 50 ) ) );
			update_option( 'shl_tortues_weather_alert_rain', max( 5,  intval( $_POST['weather_alert_rain'] ?? 20 ) ) );

			$saved = true;
		}

		include SHL_TORTUES_PLUGIN_DIR . 'admin/views/settings.php';
	}
}
