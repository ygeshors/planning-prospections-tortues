<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SHL_Tortues_Admin_Registrations {

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
		$id     = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( 'delete' === $action && $id > 0 ) {
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'shl_reg_delete_' . $id ) ) {
				wp_die( 'Nonce invalide.' );
			}
			SHL_Tortues_DB::delete_registration( $id );
			wp_safe_redirect( admin_url( 'admin.php?page=shl-tortues-registrations&msg=deleted' ) );
			exit;
		}

		if ( 'broadcast' === $action ) {
			$slot_id = isset( $_GET['slot_id'] ) ? intval( $_GET['slot_id'] ) : 0;
			SHL_Tortues_Admin_Broadcast::render_form( $slot_id );
			return;
		}

		// Filtres
		$filters = array();
		if ( ! empty( $_GET['date_from'] ) ) { $filters['date_from'] = sanitize_text_field( wp_unslash( $_GET['date_from'] ) ); }
		if ( ! empty( $_GET['date_to']   ) ) { $filters['date_to']   = sanitize_text_field( wp_unslash( $_GET['date_to'] ) );   }
		if ( ! empty( $_GET['status']    ) ) { $filters['status']    = sanitize_text_field( wp_unslash( $_GET['status'] ) );    }
		if ( ! empty( $_GET['type']      ) ) { $filters['type']      = sanitize_text_field( wp_unslash( $_GET['type'] ) );      }
		if ( ! empty( $_GET['search']    ) ) { $filters['search']    = sanitize_text_field( wp_unslash( $_GET['search'] ) );    }
		if ( ! empty( $_GET['slot_id']   ) ) { $filters['slot_id']   = intval( $_GET['slot_id'] );                               }

		$registrations = SHL_Tortues_DB::get_registrations( array_merge( $filters, array( 'limit' => 200 ) ) );

		// Créneau courant si filtré par slot
		$current_slot = null;
		if ( ! empty( $filters['slot_id'] ) ) {
			$current_slot = SHL_Tortues_DB::get_slot( $filters['slot_id'] );
		}

		include SHL_TORTUES_PLUGIN_DIR . 'admin/views/registrations-list.php';
	}
}
