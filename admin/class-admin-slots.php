<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SHL_Tortues_Admin_Slots {

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
		$id     = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		// Sauvegarde
		if ( 'save' === $action && isset( $_POST['_wpnonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'shl_slot_save' ) ) {
				wp_die( 'Nonce invalide.' );
			}
			self::save();
			return;
		}

		// Suppression
		if ( 'delete' === $action && $id > 0 ) {
			$nonce_key = 'shl_slot_delete_' . $id;
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), $nonce_key ) ) {
				wp_die( 'Nonce invalide.' );
			}
			SHL_Tortues_DB::delete_slot( $id );
			wp_safe_redirect( admin_url( 'admin.php?page=shl-tortues-slots&msg=deleted' ) );
			exit;
		}

		// Formulaire (nouveau / édition)
		if ( in_array( $action, array( 'new', 'edit' ), true ) ) {
			$slot           = ( 'edit' === $action && $id > 0 ) ? SHL_Tortues_DB::get_slot( $id ) : null;
			$zones          = SHL_Tortues_DB::get_zones();
			$default_places = (int) get_option( 'shl_tortues_default_places', 2 );
			include SHL_TORTUES_PLUGIN_DIR . 'admin/views/slot-form.php';
			return;
		}

		// Liste
		$filter_status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
		$filter_type   = isset( $_GET['type'] )   ? sanitize_text_field( wp_unslash( $_GET['type'] ) )   : '';
		$args = array( 'orderby' => 'date', 'order' => 'DESC', 'limit' => 200 );
		if ( $filter_status ) { $args['status'] = $filter_status; }
		if ( $filter_type )   { $args['type']   = $filter_type;   }
		$slots = SHL_Tortues_DB::get_slots( $args );
		include SHL_TORTUES_PLUGIN_DIR . 'admin/views/slots-list.php';
	}

	private static function save() {
		$id = isset( $_POST['slot_id'] ) ? intval( $_POST['slot_id'] ) : 0;

		// Champs communs à tous les créneaux
		$common = array(
			'time_start'     => sanitize_text_field( wp_unslash( $_POST['time_start'] ?? '' ) ),
			'time_end'       => sanitize_text_field( wp_unslash( $_POST['time_end'] ?? '' ) ) ?: null,
			'meeting_point'  => sanitize_textarea_field( wp_unslash( $_POST['meeting_point'] ?? '' ) ) ?: null,
			'type_prospect'  => sanitize_text_field( wp_unslash( $_POST['type_prospect'] ?? 'foot' ) ),
			'places_total'   => max( 1, intval( $_POST['places_total'] ?? 2 ) ),
			'status'         => sanitize_text_field( wp_unslash( $_POST['status'] ?? 'open' ) ),
			'instructions'   => sanitize_textarea_field( wp_unslash( $_POST['instructions'] ?? '' ) ) ?: null,
			'referent'       => sanitize_text_field( wp_unslash( $_POST['referent'] ?? '' ) ) ?: null,
			'result'         => sanitize_text_field( wp_unslash( $_POST['result'] ?? '' ) ),
			'result_comment' => sanitize_textarea_field( wp_unslash( $_POST['result_comment'] ?? '' ) ) ?: null,
		);

		if ( ! in_array( $common['type_prospect'], array( 'foot', 'drone', 'mixed' ), true ) ) {
			$common['type_prospect'] = 'foot';
		}
		if ( ! in_array( $common['status'], array( 'open', 'full', 'cancelled', 'done' ), true ) ) {
			$common['status'] = 'open';
		}
		if ( ! in_array( $common['result'], array( '', 'none', 'suspect', 'confirmed', 'other' ), true ) ) {
			$common['result'] = '';
		}

		// ── Édition d'un créneau existant ──────────────────────────────────────
		if ( $id > 0 ) {
			$data = array_merge( $common, array(
				'date'      => sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) ),
				'zone_id'   => intval( $_POST['zone_id'] ?? 0 ) ?: null,
				'zone_name' => sanitize_text_field( wp_unslash( $_POST['zone_name'] ?? '' ) ),
				'commune'   => sanitize_text_field( wp_unslash( $_POST['commune'] ?? '' ) ),
				'latitude'  => sanitize_text_field( wp_unslash( $_POST['latitude'] ?? '' ) ) ?: null,
				'longitude' => sanitize_text_field( wp_unslash( $_POST['longitude'] ?? '' ) ) ?: null,
			) );

			if ( empty( $data['date'] ) || empty( $common['time_start'] ) || empty( $data['zone_name'] ) || empty( $data['commune'] ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=shl-tortues-slots&action=edit&id=' . $id . '&msg=missing' ) );
				exit;
			}

			SHL_Tortues_DB::update_slot( $id, $data );
			wp_safe_redirect( admin_url( 'admin.php?page=shl-tortues-slots&msg=updated' ) );
			exit;
		}

		// ── Création de nouveaux créneaux (potentiellement en lot) ─────────────

		// 1. Déterminer les zones à créer
		$zones_to_create = array();
		$zone_ids = isset( $_POST['zone_ids'] ) && is_array( $_POST['zone_ids'] )
			? array_map( 'intval', $_POST['zone_ids'] )
			: array();

		if ( ! empty( $zone_ids ) ) {
			$all_zones = SHL_Tortues_DB::get_zones();
			$zones_map = array();
			foreach ( $all_zones as $z ) {
				$zones_map[ (int) $z->id ] = $z;
			}
			foreach ( $zone_ids as $zid ) {
				if ( isset( $zones_map[ $zid ] ) ) {
					$z = $zones_map[ $zid ];
					$zones_to_create[] = array(
						'zone_id'   => $z->id,
						'zone_name' => $z->name,
						'commune'   => $z->commune,
						'latitude'  => isset( $z->gps_lat ) ? ( $z->gps_lat ?: null ) : null,
						'longitude' => isset( $z->gps_lng ) ? ( $z->gps_lng ?: null ) : null,
					);
				}
			}
		}

		// Fallback : zone libre saisie en texte
		if ( empty( $zones_to_create ) ) {
			$zone_name = sanitize_text_field( wp_unslash( $_POST['zone_name'] ?? '' ) );
			$commune   = sanitize_text_field( wp_unslash( $_POST['commune']   ?? '' ) );
			if ( empty( $zone_name ) || empty( $commune ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=shl-tortues-slots&action=new&msg=missing' ) );
				exit;
			}
			$zones_to_create[] = array(
				'zone_id'   => null,
				'zone_name' => $zone_name,
				'commune'   => $commune,
				'latitude'  => sanitize_text_field( wp_unslash( $_POST['latitude']  ?? '' ) ) ?: null,
				'longitude' => sanitize_text_field( wp_unslash( $_POST['longitude'] ?? '' ) ) ?: null,
			);
		}

		// 2. Déterminer les dates
		$dates       = array();
		$repeat_mode = sanitize_text_field( wp_unslash( $_POST['repeat_mode'] ?? 'single' ) );

		if ( 'weekly' === $repeat_mode ) {
			$date_from = sanitize_text_field( wp_unslash( $_POST['date_from'] ?? '' ) );
			$date_to   = sanitize_text_field( wp_unslash( $_POST['date_to']   ?? '' ) );
			$weekdays  = isset( $_POST['weekdays'] ) && is_array( $_POST['weekdays'] )
				? array_map( 'intval', $_POST['weekdays'] )
				: array();

			if ( empty( $date_from ) || empty( $date_to ) || empty( $weekdays ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=shl-tortues-slots&action=new&msg=missing' ) );
				exit;
			}

			$ts_from = strtotime( $date_from );
			$ts_to   = strtotime( $date_to );
			if ( false === $ts_from || false === $ts_to || $ts_from > $ts_to ) {
				wp_safe_redirect( admin_url( 'admin.php?page=shl-tortues-slots&action=new&msg=missing' ) );
				exit;
			}

			$cur = $ts_from;
			while ( $cur <= $ts_to ) {
				$dow = (int) gmdate( 'w', $cur ); // 0=Dim, 1=Lun, ..., 6=Sam
				if ( in_array( $dow, $weekdays, true ) ) {
					$dates[] = gmdate( 'Y-m-d', $cur );
				}
				$cur = strtotime( '+1 day', $cur );
			}

			if ( empty( $dates ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=shl-tortues-slots&action=new&msg=missing' ) );
				exit;
			}
		} else {
			$date = sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) );
			if ( empty( $date ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=shl-tortues-slots&action=new&msg=missing' ) );
				exit;
			}
			$dates[] = $date;
		}

		// Heure départ obligatoire
		if ( empty( $common['time_start'] ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=shl-tortues-slots&action=new&msg=missing' ) );
			exit;
		}

		// 3. Créer tous les créneaux (zones × dates)
		$created = 0;
		foreach ( $zones_to_create as $zone ) {
			foreach ( $dates as $date ) {
				$data   = array_merge( $common, $zone, array( 'date' => $date ) );
				$result = SHL_Tortues_DB::insert_slot( $data );
				if ( $result ) {
					$created++;
				}
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=shl-tortues-slots&msg=created&count=' . $created ) );
		exit;
	}
}
