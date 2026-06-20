<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gestion de l'annulation d'inscription par le bénévole.
 * Accessible via : https://votre-site.fr/?shl_cancel=TOKEN
 */
class SHL_Tortues_Cancel {

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'intercept' ) );
	}

	public static function intercept() {
		$token = isset( $_GET['shl_cancel'] ) ? sanitize_text_field( wp_unslash( $_GET['shl_cancel'] ) ) : '';
		if ( empty( $token ) ) {
			return;
		}

		$reg       = SHL_Tortues_Observations::get_registration_by_token( $token );
		$slot      = $reg ? SHL_Tortues_DB::get_slot( $reg->slot_id ) : null;
		$cancelled = false;
		$already   = false;
		$invalid   = ! $reg;
		$error     = '';

		if ( $reg && in_array( $reg->status, array( 'cancelled', 'refused' ), true ) ) {
			$already = true;
		}

		if ( $reg && ! $already && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$nonce_val = isset( $_POST['shl_cancel_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['shl_cancel_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce_val, 'shl_cancel_' . $token ) ) {
				$error = 'Sécurité invalide. Rechargez la page et réessayez.';
			} else {
				SHL_Tortues_DB::update_registration( $reg->id, array( 'status' => 'cancelled' ) );
				SHL_Tortues_DB::refresh_slot_count( $reg->slot_id );
				SHL_Tortues_DB::promote_first_waitlist( $reg->slot_id );
				SHL_Tortues_Email::send_cancellation_email( $reg, $slot );
				$cancelled = true;
			}
		}

		include SHL_TORTUES_PLUGIN_DIR . 'public/views/cancel.php';
		exit;
	}
}
