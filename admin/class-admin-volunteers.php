<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SHL_Tortues_Admin_Volunteers {

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$search       = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
		$email_detail = isset( $_GET['email'] )  ? sanitize_email( wp_unslash( $_GET['email'] ) )       : '';

		$volunteers     = SHL_Tortues_DB::get_volunteers_summary( $search );
		$history        = array();
		$volunteer_info = null;

		if ( $email_detail ) {
			$history = SHL_Tortues_DB::get_volunteer_history( $email_detail );
			if ( ! empty( $history ) ) {
				$first          = $history[0];
				$volunteer_info = array(
					'name'  => $first->firstname . ' ' . $first->lastname,
					'email' => $first->email,
					'phone' => $first->phone ?: '',
				);
			}
		}

		include SHL_TORTUES_PLUGIN_DIR . 'admin/views/volunteers-list.php';
	}
}
