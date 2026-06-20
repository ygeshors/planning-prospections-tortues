<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SHL_Tortues_Public {

	public function init() {
		add_shortcode( 'planning_tortues', array( $this, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	public function register_assets() {
		wp_register_style(
			'shl-public-css',
			SHL_TORTUES_PLUGIN_URL . 'assets/css/public.css',
			array(),
			SHL_TORTUES_VERSION
		);
		wp_register_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
		wp_register_script( 'leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
		wp_register_script(
			'shl-public-js',
			SHL_TORTUES_PLUGIN_URL . 'assets/js/public.js',
			array( 'jquery', 'leaflet-js' ),
			SHL_TORTUES_VERSION,
			true
		);
	}

	public function shortcode( $atts ) {
		wp_enqueue_style( 'leaflet-css' );
		wp_enqueue_script( 'leaflet-js' );
		wp_enqueue_style( 'shl-public-css' );
		wp_enqueue_script( 'shl-public-js' );
		wp_localize_script( 'shl-public-js', 'shlTortues', array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'shl_public_nonce' ),
			'color' => esc_attr( get_option( 'shl_tortues_primary_color', '#2E86AB' ) ),
		) );

		ob_start();
		include SHL_TORTUES_PLUGIN_DIR . 'public/views/calendar.php';
		return ob_get_clean();
	}

	// ══════════════════════════════════════════════════════════════════════
	//  AJAX : données du calendrier pour un mois donné
	// ══════════════════════════════════════════════════════════════════════
	public static function ajax_shl_get_calendar_data() {
		check_ajax_referer( 'shl_public_nonce', 'nonce' );

		$year  = max( 2020, min( 2040, intval( $_POST['year']  ?? date( 'Y' ) ) ) );
		$month = max( 1,    min( 12,   intval( $_POST['month'] ?? date( 'm' ) ) ) );

		$slots      = SHL_Tortues_DB::get_slots_for_month( $year, $month );
		$show_names = get_option( 'shl_tortues_show_names', '1' );

		$data = array();
		foreach ( $slots as $s ) {
			$names = array();
			if ( '1' === $show_names ) {
				$regs = SHL_Tortues_DB::get_slot_registrations( $s->id );
				foreach ( $regs as $r ) {
					$names[] = esc_html( $r->firstname . ' ' . mb_substr( $r->lastname, 0, 1 ) . '.' );
				}
			}
			$data[] = array(
				'id'          => (int) $s->id,
				'date'        => $s->date,
				'time_start'  => substr( $s->time_start, 0, 5 ),
				'time_end'    => $s->time_end ? substr( $s->time_end, 0, 5 ) : '',
				'zone_name'   => esc_html( $s->zone_name ),
				'commune'     => esc_html( $s->commune ),
				'type'        => $s->type_prospect,
				'places_total'=> (int) $s->places_total,
				'places_taken'=> (int) $s->places_taken,
				'places_left' => max( 0, (int) $s->places_total - (int) $s->places_taken ),
				'status'      => $s->status,
				'names'       => $names,
			);
		}

		wp_send_json_success( $data );
	}

	// ══════════════════════════════════════════════════════════════════════
	//  AJAX : détail d'un créneau (modal)
	// ══════════════════════════════════════════════════════════════════════
	public static function ajax_shl_get_slot_details() {
		check_ajax_referer( 'shl_public_nonce', 'nonce' );

		$id   = intval( $_POST['slot_id'] ?? 0 );
		$slot = SHL_Tortues_DB::get_slot( $id );

		if ( ! $slot || 'cancelled' === $slot->status ) {
			wp_send_json_error( 'Créneau introuvable ou annulé.' );
		}

		$show_names = get_option( 'shl_tortues_show_names', '1' );
		$names = array();
		if ( '1' === $show_names ) {
			$regs = SHL_Tortues_DB::get_slot_registrations( $id );
			foreach ( $regs as $r ) {
				$names[] = esc_html( $r->firstname . ' ' . mb_substr( $r->lastname, 0, 1 ) . '.' );
			}
		}

		// Zone GeoJSON
		$zone_geojson = null;
		if ( ! empty( $slot->zone_id ) ) {
			$zone_obj = SHL_Tortues_DB::get_zone( intval( $slot->zone_id ) );
			if ( $zone_obj && ! empty( $zone_obj->geojson_zone ) ) {
				$zone_geojson = $zone_obj->geojson_zone;
			}
		}

		wp_send_json_success( array(
			'id'                  => (int) $slot->id,
			'date_formatted'      => date_i18n( 'l d F Y', strtotime( $slot->date ) ),
			'date_raw'            => $slot->date,
			'time_start'          => substr( $slot->time_start, 0, 5 ),
			'time_end'            => $slot->time_end ? substr( $slot->time_end, 0, 5 ) : '',
			'zone_name'           => esc_html( $slot->zone_name ),
			'commune'             => esc_html( $slot->commune ),
			'meeting_point'       => $slot->meeting_point ? esc_html( $slot->meeting_point ) : '',
			'type'                => $slot->type_prospect,
			'places_total'        => (int) $slot->places_total,
			'places_taken'        => (int) $slot->places_taken,
			'places_left'         => max( 0, (int) $slot->places_total - (int) $slot->places_taken ),
			'status'              => $slot->status,
			'instructions'        => $slot->instructions ? esc_html( $slot->instructions ) : '',
			'general_instructions'=> esc_html( get_option( 'shl_tortues_general_instructions', '' ) ),
			'referent'            => $slot->referent ? esc_html( $slot->referent ) : '',
			'names'               => $names,
			'latitude'            => $slot->latitude  ? esc_html( $slot->latitude )  : '',
			'longitude'           => $slot->longitude ? esc_html( $slot->longitude ) : '',
			'zone_geojson'        => $zone_geojson,
		) );
	}

	// ══════════════════════════════════════════════════════════════════════
	//  AJAX : inscription à un créneau
	// ══════════════════════════════════════════════════════════════════════
	public static function ajax_shl_register_slot() {
		check_ajax_referer( 'shl_public_nonce', 'nonce' );

		$slot_id = intval( $_POST['slot_id'] ?? 0 );
		if ( ! $slot_id ) {
			wp_send_json_error( array( 'message' => 'Créneau invalide.' ) );
		}

		$slot = SHL_Tortues_DB::get_slot( $slot_id );
		if ( ! $slot ) {
			wp_send_json_error( array( 'message' => 'Créneau introuvable.' ) );
		}
		if ( in_array( $slot->status, array( 'cancelled', 'done' ), true ) ) {
			wp_send_json_error( array( 'message' => 'Ce créneau n\'accepte plus d\'inscriptions.' ) );
		}
		// Si le créneau est complet → inscription en liste d'attente
		$is_waitlist = ( 'full' === $slot->status || (int) $slot->places_taken >= (int) $slot->places_total );
		$reg_status  = $is_waitlist ? 'waitlist' : 'pending';

		// Nettoyage des entrées
		$firstname = sanitize_text_field( wp_unslash( $_POST['firstname'] ?? '' ) );
		$lastname  = sanitize_text_field( wp_unslash( $_POST['lastname']  ?? '' ) );
		$email     = sanitize_email( wp_unslash( $_POST['email']          ?? '' ) );
		$phone     = sanitize_text_field( wp_unslash( $_POST['phone']     ?? '' ) );
		$is_member = ( isset( $_POST['is_member'] ) && '1' === $_POST['is_member'] ) ? 1 : 0;
		$comment   = sanitize_textarea_field( wp_unslash( $_POST['comment']       ?? '' ) );
		$accepted  = ( isset( $_POST['accepted_rules'] ) && '1' === $_POST['accepted_rules'] ) ? 1 : 0;

		if ( empty( $firstname ) || empty( $lastname ) || empty( $email ) ) {
			wp_send_json_error( array( 'message' => 'Veuillez remplir tous les champs obligatoires (prénom, nom, email).' ) );
		}
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => 'L\'adresse email saisie n\'est pas valide.' ) );
		}
		if ( ! $accepted ) {
			wp_send_json_error( array( 'message' => 'Vous devez accepter les consignes de prospection.' ) );
		}

		$reg_id = SHL_Tortues_DB::insert_registration( array(
			'slot_id'       => $slot_id,
			'firstname'     => $firstname,
			'lastname'      => $lastname,
			'email'         => $email,
			'phone'         => $phone ?: null,
			'is_member'     => $is_member,
			'comment'       => $comment ?: null,
			'accepted_rules'=> $accepted,
			'status'        => $reg_status,
		) );

		if ( is_wp_error( $reg_id ) ) {
			wp_send_json_error( array( 'message' => $reg_id->get_error_message() ) );
		}
		if ( ! $reg_id ) {
			wp_send_json_error( array( 'message' => 'Une erreur est survenue. Veuillez réessayer.' ) );
		}

		SHL_Tortues_DB::refresh_slot_count( $slot_id );

		if ( $is_waitlist ) {
			SHL_Tortues_Email::send_waitlist_confirmation( $reg_id, $slot_id );
			wp_send_json_success( array(
				'message'     => 'Créneau complet — vous avez été ajouté(e) à la liste d\'attente ! Vous recevrez un email si une place se libère.',
				'places_left' => 0,
				'status'      => 'full',
				'waitlist'    => true,
			) );
		} else {
			SHL_Tortues_Email::send_confirmation( $reg_id, $slot_id );
			SHL_Tortues_Email::send_admin_notification( $reg_id, $slot_id );
			$slot        = SHL_Tortues_DB::get_slot( $slot_id );
			$places_left = max( 0, (int) $slot->places_total - (int) $slot->places_taken );
			wp_send_json_success( array(
				'message'     => 'Votre inscription a bien été enregistrée ! Un email de confirmation vous a été envoyé.',
				'places_left' => $places_left,
				'status'      => $slot->status,
			) );
		}
	}
}
