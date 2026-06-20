<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prospection hors planning – shortcode [prospection_libre] + endpoint AJAX.
 */
class SHL_Tortues_Libre {

	public static function init() {
		add_shortcode( 'prospection_libre', array( __CLASS__, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	public static function register_ajax() {
		add_action( 'wp_ajax_shl_submit_libre',        array( __CLASS__, 'ajax_submit' ) );
		add_action( 'wp_ajax_nopriv_shl_submit_libre', array( __CLASS__, 'ajax_submit' ) );
	}

	public static function register_assets() {
		wp_register_style(  'shl-public-css', SHL_TORTUES_PLUGIN_URL . 'assets/css/public.css', array(), SHL_TORTUES_VERSION );
		wp_register_script( 'shl-public-js',  SHL_TORTUES_PLUGIN_URL . 'assets/js/public.js',  array( 'jquery' ), SHL_TORTUES_VERSION, true );
	}

	public static function render_shortcode( $atts ) {
		wp_enqueue_style( 'shl-public-css' );
		wp_enqueue_script( 'shl-public-js' );

		// Localisation pour le JS du formulaire libre
		wp_localize_script( 'shl-public-js', 'shlLibre', array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'shl_libre_nonce' ),
		) );

		$zones = SHL_Tortues_DB::get_zones();

		ob_start();
		include SHL_TORTUES_PLUGIN_DIR . 'public/views/libre.php';
		return ob_get_clean();
	}

	// ── AJAX : soumettre une prospection hors planning ──────────────────────
	public static function ajax_submit() {
		check_ajax_referer( 'shl_libre_nonce', 'nonce' );

		// Champs requis
		$required = array( 'firstname', 'lastname', 'email', 'date_prospect', 'time_start', 'zone_name', 'commune', 'type_prospect' );
		foreach ( $required as $field ) {
			if ( empty( trim( wp_unslash( $_POST[ $field ] ?? '' ) ) ) ) {
				wp_send_json_error( 'Champ obligatoire manquant.' );
			}
		}

		if ( empty( $_POST['accepted_rules'] ) ) {
			wp_send_json_error( 'Vous devez accepter les consignes de prospection.' );
		}

		$firstname   = sanitize_text_field( wp_unslash( $_POST['firstname'] ) );
		$lastname    = sanitize_text_field( wp_unslash( $_POST['lastname'] ) );
		$email       = sanitize_email( wp_unslash( $_POST['email'] ) );
		$phone       = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
		$is_member   = ! empty( $_POST['is_member'] ) ? 1 : 0;
		$date        = sanitize_text_field( wp_unslash( $_POST['date_prospect'] ) );
		$time_start  = sanitize_text_field( wp_unslash( $_POST['time_start'] ) );
		$time_end    = sanitize_text_field( wp_unslash( $_POST['time_end'] ?? '' ) );
		$zone_name   = sanitize_text_field( wp_unslash( $_POST['zone_name'] ) );
		$commune     = sanitize_text_field( wp_unslash( $_POST['commune'] ) );
		$type        = sanitize_text_field( wp_unslash( $_POST['type_prospect'] ) );
		$obs_type    = sanitize_text_field( wp_unslash( $_POST['obs_type'] ?? '' ) );
		$comment     = sanitize_textarea_field( wp_unslash( $_POST['comment'] ?? '' ) );

		if ( ! is_email( $email ) ) {
			wp_send_json_error( 'Adresse email invalide.' );
		}
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			wp_send_json_error( 'Date invalide.' );
		}
		if ( strtotime( $date ) < strtotime( 'tomorrow' ) ) {
			wp_send_json_error( 'La déclaration doit être faite au plus tard la veille de la prospection. Veuillez sélectionner une date à partir de demain.' );
		}
		if ( ! in_array( $type, array( 'foot', 'drone', 'mixed' ), true ) ) {
			wp_send_json_error( 'Type de prospection invalide.' );
		}
		if ( $obs_type && ! in_array( $obs_type, array( 'none', 'suspect', 'confirmed', 'other' ), true ) ) {
			$obs_type = '';
		}

		// Créer le créneau hors planning
		$slot_id = SHL_Tortues_DB::insert_slot( array(
			'date'             => $date,
			'time_start'       => $time_start . ':00',
			'time_end'         => $time_end ? $time_end . ':00' : null,
			'zone_name'        => $zone_name,
			'commune'          => $commune,
			'type_prospect'    => $type,
			'places_total'     => 1,
			'places_taken'     => 1,
			'status'           => 'done',
			'is_hors_planning' => 1,
			'result'           => $obs_type ?: '',
			'result_comment'   => $comment ?: null,
		) );

		if ( ! $slot_id ) {
			wp_send_json_error( 'Erreur lors de la création de la prospection. Réessayez.' );
		}

		// Créer l'inscription (bypass du check doublon unique slot/email)
		global $wpdb;
		$token = wp_generate_password( 48, false, false );
		$result = $wpdb->insert( SHL_Tortues_DB::reg_table(), array(
			'slot_id'           => $slot_id,
			'firstname'         => $firstname,
			'lastname'          => $lastname,
			'email'             => $email,
			'phone'             => $phone ?: null,
			'is_member'         => $is_member,
			'comment'           => $comment ?: null,
			'accepted_rules'    => 1,
			'status'            => 'validated',
			'token'             => $token,
			'actual_time_start' => $time_start ?: null,
			'actual_time_end'   => $time_end ?: null,
			'created_at'        => current_time( 'mysql' ),
		) );

		if ( ! $result ) {
			wp_send_json_error( 'Erreur lors de l\'enregistrement.' );
		}

		$reg_id = $wpdb->insert_id;

		// Envoyer les emails
		SHL_Tortues_Email::send_libre_confirmation( $reg_id, $slot_id );
		SHL_Tortues_Email::send_libre_admin_notification( $reg_id, $slot_id );

		wp_send_json_success( array(
			'message' => 'Merci ' . esc_html( $firstname ) . ' ! Votre prospection du ' . esc_html( date_i18n( 'd/m/Y', strtotime( $date ) ) ) . ' a bien été enregistrée. Un email de confirmation vous a été envoyé.',
		) );
	}
}
