<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Couche d'accès aux données des observations terrain (photos + résultats).
 */
class SHL_Tortues_Observations {

	public static function obs_table() {
		global $wpdb;
		return $wpdb->prefix . 'shl_tortues_observations';
	}

	// ── Récupération par inscription ────────────────────────────────────────
	public static function get_by_registration( $reg_id ) {
		global $wpdb;
		$t = self::obs_table();
		return $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare( "SELECT * FROM `{$t}` WHERE reg_id = %d ORDER BY created_at DESC", $reg_id ) // phpcs:ignore
		);
	}

	// ── Récupération par créneau (toutes observations) ──────────────────────
	public static function get_by_slot( $slot_id ) {
		global $wpdb;
		$to = self::obs_table();
		$tr = SHL_Tortues_DB::reg_table();
		return $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				"SELECT o.*, r.firstname, r.lastname
				 FROM `{$to}` o
				 LEFT JOIN `{$tr}` r ON o.reg_id = r.id
				 WHERE o.slot_id = %d ORDER BY o.created_at DESC", // phpcs:ignore
				$slot_id
			)
		);
	}

	// ── Valider un token d'inscription ──────────────────────────────────────
	public static function get_registration_by_token( $token ) {
		global $wpdb;
		$tr = SHL_Tortues_DB::reg_table();
		return $wpdb->get_row( // phpcs:ignore
			$wpdb->prepare( "SELECT * FROM `{$tr}` WHERE token = %s LIMIT 1", $token ) // phpcs:ignore
		);
	}

	// ── Toutes les observations (vue admin) ─────────────────────────────────
	public static function get_all( $args = array() ) {
		global $wpdb;
		$to = self::obs_table();
		$ts = SHL_Tortues_DB::slots_table();
		$tr = SHL_Tortues_DB::reg_table();

		$defaults = array( 'limit' => 100, 'offset' => 0, 'slot_id' => null );
		$a = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$values = array();
		if ( $a['slot_id'] ) { $where[] = 'o.slot_id = %d'; $values[] = $a['slot_id']; }

		$where_sql = implode( ' AND ', $where );
		$values[]  = intval( $a['limit'] );
		$values[]  = intval( $a['offset'] );

		$sql = $wpdb->prepare( // phpcs:ignore
			"SELECT o.*, s.date, s.zone_name, s.commune, r.firstname, r.lastname
			 FROM `{$to}` o
			 LEFT JOIN `{$ts}` s ON o.slot_id = s.id
			 LEFT JOIN `{$tr}` r ON o.reg_id = r.id
			 WHERE {$where_sql}
			 ORDER BY o.created_at DESC
			 LIMIT %d OFFSET %d", // phpcs:ignore
			$values
		);

		return $wpdb->get_results( $sql ); // phpcs:ignore
	}

	public static function get_observation( $id ) {
		global $wpdb;
		$t = self::obs_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$t}` WHERE id = %d", $id ) ); // phpcs:ignore
	}

	// ── Insérer une observation ─────────────────────────────────────────────
	public static function insert( $data ) {
		global $wpdb;
		$data['created_at'] = current_time( 'mysql' );
		$result = $wpdb->insert( self::obs_table(), $data );
		return $result ? $wpdb->insert_id : false;
	}

	// ── Supprimer (+ fichier photo) ─────────────────────────────────────────
	public static function delete( $id ) {
		global $wpdb;
		$obs = self::get_observation( $id );
		if ( $obs && ! empty( $obs->photo_path ) && file_exists( $obs->photo_path ) ) {
			@unlink( $obs->photo_path ); // phpcs:ignore
		}
		return $wpdb->delete( self::obs_table(), array( 'id' => $id ) );
	}

	// ── Gérer l'upload d'une photo ──────────────────────────────────────────
	public static function handle_photo_upload() {
		if ( empty( $_FILES['photo'] ) || empty( $_FILES['photo']['tmp_name'] ) ) {
			return new WP_Error( 'no_file', 'Aucun fichier reçu.' );
		}

		$allowed = array(
			'jpg'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png'  => 'image/png',
			'webp' => 'image/webp',
			'heic' => 'image/heic',
		);

		$file_info = wp_check_filetype( $_FILES['photo']['name'], $allowed );
		if ( empty( $file_info['ext'] ) ) {
			// Fallback : essaie de détecter depuis le type MIME réel
			$mime = mime_content_type( $_FILES['photo']['tmp_name'] );
			if ( ! in_array( $mime, array_values( $allowed ), true ) ) {
				return new WP_Error( 'invalid_type', 'Type de fichier non autorisé. Utilisez JPG, PNG ou WEBP.' );
			}
			$file_info['ext'] = array_search( $mime, $allowed, true ) ?: 'jpg';
		}

		// Vérification taille (max 15 Mo)
		if ( $_FILES['photo']['size'] > 15 * 1024 * 1024 ) {
			return new WP_Error( 'too_large', 'Le fichier est trop volumineux (max 15 Mo).' );
		}

		$upload_dir = wp_upload_dir();
		$sub_dir    = 'tortues-prospections/' . gmdate( 'Y/m' );
		$dest_dir   = $upload_dir['basedir'] . '/' . $sub_dir;

		if ( ! file_exists( $dest_dir ) ) {
			wp_mkdir_p( $dest_dir );
			// Fichier .htaccess pour sécuriser le dossier (évite l'exécution de scripts)
			file_put_contents( $upload_dir['basedir'] . '/tortues-prospections/.htaccess', "Options -Indexes\n<FilesMatch \"\\.php$\">\nDeny from all\n</FilesMatch>\n" ); // phpcs:ignore
		}

		$filename  = 'obs_' . wp_generate_password( 12, false, false ) . '_' . time() . '.' . $file_info['ext'];
		$dest_path = $dest_dir . '/' . $filename;
		$dest_url  = $upload_dir['baseurl'] . '/' . $sub_dir . '/' . $filename;

		if ( ! move_uploaded_file( $_FILES['photo']['tmp_name'], $dest_path ) ) {
			return new WP_Error( 'upload_failed', 'Impossible de déplacer le fichier. Vérifiez les permissions du serveur.' );
		}

		return array( 'path' => $dest_path, 'url' => $dest_url );
	}

	// ── Label lisible ───────────────────────────────────────────────────────
	public static function type_label( $type ) {
		$labels = array(
			'none'      => '✅ Aucune trace',
			'suspect'   => '⚠️ Trace suspecte',
			'confirmed' => '🐢 Trace confirmée',
			'other'     => '👁️ Autre observation',
		);
		return $labels[ $type ] ?? $type;
	}
}
