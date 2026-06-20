<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gestion du formulaire terrain mobile (prise de photo + saisie résultat).
 * Accessible via : https://votre-site.fr/?shl_terrain=TOKEN
 */
class SHL_Tortues_Terrain {

	public function init() {
		add_action( 'template_redirect', array( $this, 'intercept_terrain_page' ) );

		// AJAX handlers (publics – authentification par token)
		$actions = array( 'shl_terrain_upload', 'shl_terrain_submit_obs', 'shl_terrain_delete_photo', 'shl_terrain_save_track' );
		foreach ( $actions as $action ) {
			add_action( 'wp_ajax_'        . $action, array( $this, 'ajax_' . str_replace( 'shl_terrain_', '', $action ) ) );
			add_action( 'wp_ajax_nopriv_' . $action, array( $this, 'ajax_' . str_replace( 'shl_terrain_', '', $action ) ) );
		}
	}

	// ── Interception de la requête ──────────────────────────────────────────
	public function intercept_terrain_page() {
		$token = isset( $_GET['shl_terrain'] ) ? sanitize_text_field( wp_unslash( $_GET['shl_terrain'] ) ) : '';
		if ( empty( $token ) ) {
			return;
		}

		$reg = SHL_Tortues_Observations::get_registration_by_token( $token );
		if ( ! $reg ) {
			$this->render_error( '🔒 Lien invalide ou expiré.', 'Vérifiez votre email de confirmation ou contactez l\'association.' );
			exit;
		}

		$slot = SHL_Tortues_DB::get_slot( $reg->slot_id );
		if ( ! $slot ) {
			$this->render_error( 'Créneau introuvable.', 'Ce créneau a peut-être été supprimé.' );
			exit;
		}

		$observations   = SHL_Tortues_Observations::get_by_registration( $reg->id );
		$existing_track = SHL_Tortues_DB::get_track_by_reg( $reg->id );
		$nonce          = wp_create_nonce( 'shl_terrain_nonce' );
		$ajax_url     = admin_url( 'admin-ajax.php' );
		$type_labels  = array( 'foot' => 'À pied 🚶', 'drone' => 'Drone 🚁', 'mixed' => 'Mixte 🔀' );

		// Météo du jour pour cette plage (si coordonnées GPS disponibles)
		$weather = null;
		if ( ! empty( $slot->latitude ) && ! empty( $slot->longitude ) ) {
			$weather = SHL_Tortues_Weather::get_forecast( $slot->latitude, $slot->longitude, $slot->date );
		}

		// Zone délimitée (GeoJSON)
		$zone_geojson = null;
		if ( ! empty( $slot->zone_id ) ) {
			$zone_obj = SHL_Tortues_DB::get_zone( intval( $slot->zone_id ) );
			if ( $zone_obj && ! empty( $zone_obj->geojson_zone ) ) {
				$zone_geojson = $zone_obj->geojson_zone;
			}
		}

		include SHL_TORTUES_PLUGIN_DIR . 'public/views/terrain.php';
		exit;
	}

	// ── AJAX : upload d'une photo géolocalisée ──────────────────────────────
	public function ajax_upload() {
		check_ajax_referer( 'shl_terrain_nonce', 'nonce' );

		$token = sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) );
		$reg   = SHL_Tortues_Observations::get_registration_by_token( $token );
		if ( ! $reg ) {
			wp_send_json_error( 'Token invalide.' );
		}

		$slot = SHL_Tortues_DB::get_slot( $reg->slot_id );
		if ( ! $slot ) {
			wp_send_json_error( 'Créneau introuvable.' );
		}

		// Upload et validation du fichier
		$upload = SHL_Tortues_Observations::handle_photo_upload();
		if ( is_wp_error( $upload ) ) {
			wp_send_json_error( $upload->get_error_message() );
		}

		$lat      = sanitize_text_field( wp_unslash( $_POST['lat']      ?? '' ) );
		$lng      = sanitize_text_field( wp_unslash( $_POST['lng']      ?? '' ) );
		$accuracy = sanitize_text_field( wp_unslash( $_POST['accuracy'] ?? '' ) );
		$obs_type = sanitize_text_field( wp_unslash( $_POST['obs_type'] ?? '' ) );
		$comment  = sanitize_textarea_field( wp_unslash( $_POST['comment'] ?? '' ) );

		// Valider obs_type
		if ( ! in_array( $obs_type, array( 'none', 'suspect', 'confirmed', 'other', '' ), true ) ) {
			$obs_type = '';
		}

		$obs_id = SHL_Tortues_Observations::insert( array(
			'slot_id'    => $reg->slot_id,
			'reg_id'     => $reg->id,
			'token'      => $token,
			'obs_type'   => $obs_type,
			'comment'    => $comment ?: null,
			'photo_path' => $upload['path'],
			'photo_url'  => $upload['url'],
			'latitude'   => $lat ?: null,
			'longitude'  => $lng ?: null,
			'accuracy'   => $accuracy ?: null,
		) );

		if ( ! $obs_id ) {
			// Nettoyage si l'insertion échoue
			@unlink( $upload['path'] ); // phpcs:ignore
			wp_send_json_error( 'Erreur lors de l\'enregistrement de l\'observation.' );
		}

		// Notification email admin (optionnel – non bloquant)
		// SHL_Tortues_Email::send_observation_notification( $obs_id, $reg->slot_id );

		wp_send_json_success( array(
			'id'      => $obs_id,
			'url'     => $upload['url'],
			'lat'     => $lat,
			'lng'     => $lng,
			'message' => 'Photo enregistrée avec succès !',
		) );
	}

	// ── AJAX : soumettre une observation sans photo ─────────────────────────
	public function ajax_submit_obs() {
		check_ajax_referer( 'shl_terrain_nonce', 'nonce' );

		$token = sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) );
		$reg   = SHL_Tortues_Observations::get_registration_by_token( $token );
		if ( ! $reg ) {
			wp_send_json_error( 'Token invalide.' );
		}

		$obs_type = sanitize_text_field( wp_unslash( $_POST['obs_type'] ?? '' ) );
		if ( ! in_array( $obs_type, array( 'none', 'suspect', 'confirmed', 'other' ), true ) ) {
			wp_send_json_error( 'Veuillez sélectionner un type d\'observation.' );
		}

		$comment     = sanitize_textarea_field( wp_unslash( $_POST['comment'] ?? '' ) );
		$lat         = sanitize_text_field( wp_unslash( $_POST['lat'] ?? '' ) );
		$lng         = sanitize_text_field( wp_unslash( $_POST['lng'] ?? '' ) );
		$time_start  = sanitize_text_field( wp_unslash( $_POST['actual_time_start'] ?? '' ) );
		$time_end    = sanitize_text_field( wp_unslash( $_POST['actual_time_end'] ?? '' ) );

		// Sauvegarder les heures réelles dans l'inscription (valorisation bénévole)
		if ( $time_start || $time_end ) {
			$time_data = array();
			if ( $time_start ) { $time_data['actual_time_start'] = substr( $time_start, 0, 5 ); }
			if ( $time_end )   { $time_data['actual_time_end']   = substr( $time_end, 0, 5 );   }
			SHL_Tortues_DB::update_registration( $reg->id, $time_data );
		}

		$obs_id = SHL_Tortues_Observations::insert( array(
			'slot_id'   => $reg->slot_id,
			'reg_id'    => $reg->id,
			'token'     => $token,
			'obs_type'  => $obs_type,
			'comment'   => $comment ?: null,
			'latitude'  => $lat ?: null,
			'longitude' => $lng ?: null,
		) );

		if ( ! $obs_id ) {
			wp_send_json_error( 'Erreur lors de l\'enregistrement.' );
		}

		// Notification admin si observation importante
		if ( in_array( $obs_type, array( 'suspect', 'confirmed' ), true ) ) {
			SHL_Tortues_Email::send_observation_notification( $obs_id, $reg->slot_id );
		}

		wp_send_json_success( array(
			'message' => 'Observation enregistrée. Merci pour votre contribution !',
		) );
	}

	// ── AJAX : supprimer une photo terrain (par le bénévole) ───────────────
	public function ajax_delete_photo() {
		check_ajax_referer( 'shl_terrain_nonce', 'nonce' );

		$token  = sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) );
		$obs_id = intval( $_POST['obs_id'] ?? 0 );

		$reg = SHL_Tortues_Observations::get_registration_by_token( $token );
		if ( ! $reg ) {
			wp_send_json_error( 'Token invalide.' );
		}

		$obs = SHL_Tortues_Observations::get_observation( $obs_id );
		if ( ! $obs || (int) $obs->reg_id !== (int) $reg->id ) {
			wp_send_json_error( 'Observation introuvable.' );
		}

		SHL_Tortues_Observations::delete( $obs_id );
		wp_send_json_success( array( 'message' => 'Photo supprimée.' ) );
	}

	// ── AJAX : enregistrer un tracé GPS ────────────────────────────────────
	public function ajax_save_track() {
		check_ajax_referer( 'shl_terrain_nonce', 'nonce' );

		$token    = sanitize_text_field( wp_unslash( $_POST['token']       ?? '' ) );
		$geojson  = wp_unslash( $_POST['geojson']   ?? '' );
		$distance = floatval( $_POST['distance_m']  ?? 0 );
		$duration = intval( $_POST['duration_s']    ?? 0 );
		$started  = sanitize_text_field( wp_unslash( $_POST['started_at'] ?? '' ) );
		$ended    = sanitize_text_field( wp_unslash( $_POST['ended_at']   ?? '' ) );

		$reg = SHL_Tortues_Observations::get_registration_by_token( $token );
		if ( ! $reg ) {
			wp_send_json_error( 'Token invalide.' );
		}

		$decoded = json_decode( $geojson );
		if ( ! $decoded || ! isset( $decoded->type ) || 'LineString' !== $decoded->type ) {
			wp_send_json_error( 'GeoJSON invalide.' );
		}
		if ( empty( $decoded->coordinates ) || count( $decoded->coordinates ) < 2 ) {
			wp_send_json_error( 'Tracé trop court (minimum 2 points GPS).' );
		}

		SHL_Tortues_DB::save_track( array(
			'reg_id'     => $reg->id,
			'slot_id'    => $reg->slot_id,
			'geojson'    => $geojson,
			'distance_m' => $distance,
			'duration_s' => $duration,
			'started_at' => $started ?: null,
			'ended_at'   => $ended   ?: null,
		) );

		$time_data = array();
		if ( $started ) {
			$time_data['actual_time_start'] = substr( $started, 0, 5 );
		}
		if ( $ended ) {
			$time_data['actual_time_end'] = substr( $ended, 0, 5 );
		}
		if ( $time_data ) {
			SHL_Tortues_DB::update_registration( $reg->id, $time_data );
		}

		wp_send_json_success( array(
			'distance_m' => $distance,
			'duration_s' => $duration,
		) );
	}

	// ── Page d'erreur minimale ──────────────────────────────────────────────
	private function render_error( $title, $detail = '' ) {
		$color = esc_attr( get_option( 'shl_tortues_primary_color', '#2E86AB' ) );
		echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">'
			. '<meta name="viewport" content="width=device-width,initial-scale=1">'
			. '<title>Erreur – Prospections Tortues</title>'
			. '<style>body{margin:0;font-family:sans-serif;background:#0d1b2a;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;box-sizing:border-box}'
			. '.box{background:#1a2f45;border-radius:12px;padding:32px;max-width:400px;text-align:center}'
			. 'h1{font-size:20px;margin:0 0 10px}'
			. 'p{color:#8a9ab0;font-size:14px;margin:0}'
			. 'a{display:inline-block;margin-top:20px;background:' . $color . ';color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-size:14px}'
			. '</style></head><body>'
			. '<div class="box"><div style="font-size:48px;margin-bottom:16px">🐢</div>'
			. '<h1>' . esc_html( $title ) . '</h1>'
			. ( $detail ? '<p>' . esc_html( $detail ) . '</p>' : '' )
			. '<a href="' . esc_url( home_url() ) . '">← Retour au site</a>'
			. '</div></body></html>';
	}
}
