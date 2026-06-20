<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SHL_Tortues_Admin_Broadcast {

	// Appelé par admin-post.php (action=shl_broadcast)
	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Permission refusée.' );
		}

		$slot_id = intval( $_POST['slot_id'] ?? 0 );

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'shl_broadcast_' . $slot_id ) ) {
			wp_die( 'Nonce invalide.' );
		}

		$subject = sanitize_text_field( wp_unslash( $_POST['bc_subject'] ?? '' ) );
		$message = sanitize_textarea_field( wp_unslash( $_POST['bc_message'] ?? '' ) );

		if ( ! $slot_id || ! $subject || ! $message ) {
			wp_safe_redirect( admin_url( 'admin.php?page=shl-tortues-registrations&slot_id=' . $slot_id . '&msg=broadcast_empty' ) );
			exit;
		}

		$sent = SHL_Tortues_Email::send_broadcast( $slot_id, $subject, $message );

		wp_safe_redirect( admin_url( 'admin.php?page=shl-tortues-registrations&slot_id=' . $slot_id . '&msg=broadcast_sent&sent=' . intval( $sent ) ) );
		exit;
	}

	// Affiche le formulaire d'envoi groupé
	public static function render_form( $slot_id ) {
		$slot = SHL_Tortues_DB::get_slot( $slot_id );
		if ( ! $slot ) {
			wp_die( 'Créneau introuvable.' );
		}

		$regs       = SHL_Tortues_DB::get_slot_registrations( $slot_id );
		$recipients = array_filter( $regs, static function ( $r ) {
			return in_array( $r->status, array( 'pending', 'validated' ), true );
		} );

		include SHL_TORTUES_PLUGIN_DIR . 'admin/views/broadcast-form.php';
	}
}
