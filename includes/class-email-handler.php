<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SHL_Tortues_Email {

	public static function type_label( $type ) {
		$labels = array( 'foot' => 'À pied', 'drone' => 'Drone', 'mixed' => 'Mixte (pied + drone)' );
		return $labels[ $type ] ?? $type;
	}

	// ── Email de confirmation au bénévole ──────────────────────────────────
	public static function send_confirmation( $reg_id, $slot_id ) {
		$reg  = SHL_Tortues_DB::get_registration( $reg_id );
		$slot = SHL_Tortues_DB::get_slot( $slot_id );
		if ( ! $reg || ! $slot ) {
			return false;
		}

		$template  = get_option( 'shl_tortues_confirm_message', '' );
		$general   = get_option( 'shl_tortues_general_instructions', '' );
		$color     = esc_attr( get_option( 'shl_tortues_primary_color', '#2E86AB' ) );

		$terrain_url = home_url( '?shl_terrain=' . rawurlencode( $reg->token ) );
		$cancel_url  = home_url( '?shl_cancel='  . rawurlencode( $reg->token ) );

		$tags = array(
			'{prenom}'      => esc_html( $reg->firstname ),
			'{nom}'         => esc_html( $reg->lastname ),
			'{date}'        => esc_html( date_i18n( 'l d F Y', strtotime( $slot->date ) ) ),
			'{plage}'       => esc_html( $slot->zone_name ),
			'{commune}'     => esc_html( $slot->commune ),
			'{heure}'       => esc_html( substr( $slot->time_start, 0, 5 ) ),
			'{rdv}'         => esc_html( $slot->meeting_point ?: 'Non précisé' ),
			'{type}'        => esc_html( self::type_label( $slot->type_prospect ) ),
			'{consignes}'   => esc_html( $slot->instructions ?: $general ),
			'{terrain_url}' => esc_url( $terrain_url ),
			'{cancel_url}'  => esc_url( $cancel_url ),
		);

		$body_text = str_replace( array_keys( $tags ), array_values( $tags ), $template );
		$body_html = nl2br( $body_text );
		$body_html = preg_replace(
			'~(https?://[^\s<]+)~',
			'<a href="$1" style="color:' . $color . ';font-weight:bold">$1</a>',
			$body_html
		);

		// QR code pour accès rapide sur la plage
		$qr_url      = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . rawurlencode( $terrain_url );
		$qr_block    = '<div style="text-align:center;margin:10px 0;padding:12px;background:#f5f5f5;border-radius:8px">'
			. '<p style="font-size:11px;color:#888;margin:0 0 8px">📱 Scanner sur la plage pour saisir vos observations</p>'
			. '<img src="' . esc_url( $qr_url ) . '" alt="QR Code terrain" width="130" height="130" style="display:block;margin:0 auto;border-radius:4px">'
			. '</div>';

		// Bloc terrain
		$terrain_block = '<div style="background:linear-gradient(135deg,#1a6890,#2E86AB);border-radius:10px;padding:18px 20px;margin:20px 0;text-align:center">'
			. '<p style="color:#fff;margin:0 0 6px;font-size:13px;opacity:.85">📱 FORMULAIRE TERRAIN PERSONNEL</p>'
			. '<p style="color:#fff;margin:0 0 12px;font-size:13px">Saisissez vos photos et observations pendant ou après la prospection&nbsp;:</p>'
			. '<a href="' . esc_url( $terrain_url ) . '" style="display:inline-block;background:#fff;color:#1a6890;font-weight:800;font-size:14px;padding:10px 22px;border-radius:8px;text-decoration:none">🐢 Ouvrir le formulaire terrain</a>'
			. $qr_block
			. '<p style="color:rgba(255,255,255,.65);font-size:11px;margin:10px 0 0">Ce lien est personnel – ne le partagez pas</p>'
			. '</div>';

		// Lien d'annulation discret
		$cancel_block = '<p style="text-align:center;margin-top:16px;font-size:12px;color:#8a9ab0">'
			. 'Vous ne pouvez plus participer ? '
			. '<a href="' . esc_url( $cancel_url ) . '" style="color:#e05555;text-decoration:underline">Annuler mon inscription</a>'
			. '</p>';

		$body_html = $body_html . $terrain_block . self::benevole_block() . $cancel_block;

		$subject = sprintf(
			'[Tortues SHL] Confirmation – Prospection du %s – %s',
			date_i18n( 'd/m/Y', strtotime( $slot->date ) ),
			$slot->zone_name
		);

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: Sauvegarde Hérault Littoral <' . sanitize_email( get_option( 'shl_tortues_admin_email', get_option( 'admin_email' ) ) ) . '>',
		);

		return wp_mail( $reg->email, $subject, self::wrap( $subject, $body_html ), $headers );
	}

	// ── Rappel automatique J-1 ─────────────────────────────────────────────
	public static function send_reminder( $reg, $slot ) {
		if ( ! $reg || ! $slot ) {
			return false;
		}
		$color       = esc_attr( get_option( 'shl_tortues_primary_color', '#2E86AB' ) );
		$terrain_url = home_url( '?shl_terrain=' . rawurlencode( $reg->token ) );
		$cancel_url  = home_url( '?shl_cancel='  . rawurlencode( $reg->token ) );
		$date_fmt    = date_i18n( 'l d F Y', strtotime( $slot->date ) );

		$subject = sprintf( '[Tortues SHL] Rappel – Prospection demain – %s', $slot->zone_name );

		$html  = '<p style="font-size:16px;font-weight:bold;color:' . $color . '">📅 Rappel : prospection demain !</p>';
		$html .= '<p>Bonjour ' . esc_html( $reg->firstname ) . ',</p>';
		$html .= '<p>Vous êtes inscrit(e) à la prospection de <strong>demain ' . esc_html( $date_fmt ) . '</strong>.</p>';
		$html .= '<table style="border-collapse:collapse;width:100%;max-width:480px;font-size:14px;margin:16px 0">';
		$rows  = array(
			'🏖️ Plage'     => esc_html( $slot->zone_name ) . ' – ' . esc_html( $slot->commune ),
			'⏰ Heure'     => esc_html( substr( $slot->time_start, 0, 5 ) ) . ( $slot->time_end ? ' → ' . esc_html( substr( $slot->time_end, 0, 5 ) ) : '' ),
			'🚶 Type'      => esc_html( self::type_label( $slot->type_prospect ) ),
			'📌 RDV'       => esc_html( $slot->meeting_point ?: 'Non précisé' ),
			'🧭 Référent'  => esc_html( $slot->referent ?: 'Non précisé' ),
		);
		foreach ( $rows as $label => $val ) {
			$html .= '<tr><td style="padding:7px 12px;border:1px solid #e0e0e0;background:#f9f9f9;font-weight:bold;width:130px">' . esc_html( $label ) . '</td>'
				   . '<td style="padding:7px 12px;border:1px solid #e0e0e0">' . $val . '</td></tr>';
		}
		$html .= '</table>';

		$html .= '<div style="background:linear-gradient(135deg,#1a6890,#2E86AB);border-radius:10px;padding:16px 20px;margin:20px 0;text-align:center">'
			. '<p style="color:#fff;margin:0 0 10px;font-size:13px">Pendant ou après la prospection, saisissez vos observations :</p>'
			. '<a href="' . esc_url( $terrain_url ) . '" style="display:inline-block;background:#fff;color:#1a6890;font-weight:800;font-size:14px;padding:10px 22px;border-radius:8px;text-decoration:none">🐢 Formulaire terrain</a>'
			. '</div>';

		$html .= self::benevole_block();

		$html .= '<p style="text-align:center;font-size:12px;color:#8a9ab0;margin-top:16px">'
			. 'Finalement indisponible ? '
			. '<a href="' . esc_url( $cancel_url ) . '" style="color:#e05555">Annuler mon inscription</a>'
			. '</p>';

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: Sauvegarde Hérault Littoral <' . sanitize_email( get_option( 'shl_tortues_admin_email', get_option( 'admin_email' ) ) ) . '>',
		);
		return wp_mail( $reg->email, $subject, self::wrap( $subject, $html ), $headers );
	}

	// ── Confirmation d'annulation au bénévole ──────────────────────────────
	public static function send_cancellation_email( $reg, $slot ) {
		if ( ! $reg || ! $slot ) {
			return false;
		}
		$color   = esc_attr( get_option( 'shl_tortues_primary_color', '#2E86AB' ) );
		$subject = sprintf( '[Tortues SHL] Annulation confirmée – Prospection du %s', date_i18n( 'd/m/Y', strtotime( $slot->date ) ) );

		$html  = '<p style="font-size:16px;font-weight:bold;color:#e05555">✗ Inscription annulée</p>';
		$html .= '<p>Bonjour ' . esc_html( $reg->firstname ) . ',</p>';
		$html .= '<p>Votre inscription à la prospection suivante a bien été annulée :</p>';
		$html .= '<table style="border-collapse:collapse;width:100%;max-width:480px;font-size:14px;margin:16px 0">';
		$html .= '<tr><td style="padding:7px 12px;border:1px solid #e0e0e0;background:#f9f9f9;font-weight:bold;width:130px">📅 Date</td><td style="padding:7px 12px;border:1px solid #e0e0e0">' . esc_html( date_i18n( 'l d F Y', strtotime( $slot->date ) ) ) . '</td></tr>';
		$html .= '<tr><td style="padding:7px 12px;border:1px solid #e0e0e0;background:#f9f9f9;font-weight:bold">🏖️ Plage</td><td style="padding:7px 12px;border:1px solid #e0e0e0">' . esc_html( $slot->zone_name ) . ' – ' . esc_html( $slot->commune ) . '</td></tr>';
		$html .= '</table>';
		$html .= '<p>La place a été libérée. Vous pouvez vous réinscrire à une autre prospection depuis le planning.</p>';
		$html .= '<p style="margin-top:20px"><a href="' . esc_url( home_url() ) . '" style="background:' . $color . ';color:#fff;padding:10px 22px;border-radius:8px;text-decoration:none;font-size:14px">📅 Voir le planning</a></p>';

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: Sauvegarde Hérault Littoral <' . sanitize_email( get_option( 'shl_tortues_admin_email', get_option( 'admin_email' ) ) ) . '>',
		);
		return wp_mail( $reg->email, $subject, self::wrap( $subject, $html ), $headers );
	}

	// ── Confirmation prospection hors planning (bénévole) ──────────────────
	public static function send_libre_confirmation( $reg_id, $slot_id ) {
		$reg  = SHL_Tortues_DB::get_registration( $reg_id );
		$slot = SHL_Tortues_DB::get_slot( $slot_id );
		if ( ! $reg || ! $slot ) {
			return false;
		}
		$color   = esc_attr( get_option( 'shl_tortues_primary_color', '#2E86AB' ) );
		$subject = sprintf( '[Tortues SHL] Prospection enregistrée – %s – %s', $slot->zone_name, date_i18n( 'd/m/Y', strtotime( $slot->date ) ) );

		$duree = '';
		if ( $reg->actual_time_start && $reg->actual_time_end ) {
			$duree = esc_html( $reg->actual_time_start ) . ' → ' . esc_html( $reg->actual_time_end );
		} elseif ( $reg->actual_time_start ) {
			$duree = 'Début : ' . esc_html( $reg->actual_time_start );
		}

		$obs_labels = array( 'none' => '✅ Aucune trace', 'suspect' => '⚠️ Trace suspecte', 'confirmed' => '🐢 Trace confirmée !', 'other' => '👁️ Autre observation' );

		$html  = '<p style="font-size:16px;font-weight:bold;color:' . $color . '">🐢 Prospection hors planning enregistrée !</p>';
		$html .= '<p>Bonjour ' . esc_html( $reg->firstname ) . ', merci d\'avoir déclaré votre prospection bénévole.</p>';
		$html .= '<table style="border-collapse:collapse;width:100%;max-width:480px;font-size:14px;margin:16px 0">';
		$rows  = array(
			'📅 Date'     => esc_html( date_i18n( 'l d F Y', strtotime( $slot->date ) ) ),
			'⏰ Horaires' => $duree ?: '—',
			'🏖️ Plage'   => esc_html( $slot->zone_name ) . ' – ' . esc_html( $slot->commune ),
			'🚶 Type'     => esc_html( self::type_label( $slot->type_prospect ) ),
			'📋 Résultat' => esc_html( $obs_labels[ $slot->result ] ?? $slot->result ?: '—' ),
		);
		foreach ( $rows as $label => $val ) {
			$html .= '<tr><td style="padding:7px 12px;border:1px solid #e0e0e0;background:#f9f9f9;font-weight:bold;width:130px">' . esc_html( $label ) . '</td>'
				   . '<td style="padding:7px 12px;border:1px solid #e0e0e0">' . $val . '</td></tr>';
		}
		$html .= '</table>';
		$html .= '<p style="font-size:13px;color:#8a9ab0">Ces données contribuent au suivi scientifique des tortues marines sur les plages de l\'Hérault.</p>';

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: Sauvegarde Hérault Littoral <' . sanitize_email( get_option( 'shl_tortues_admin_email', get_option( 'admin_email' ) ) ) . '>',
		);
		return wp_mail( $reg->email, $subject, self::wrap( $subject, $html ), $headers );
	}

	// ── Notification admin : prospection hors planning ─────────────────────
	public static function send_libre_admin_notification( $reg_id, $slot_id ) {
		$reg  = SHL_Tortues_DB::get_registration( $reg_id );
		$slot = SHL_Tortues_DB::get_slot( $slot_id );
		if ( ! $reg || ! $slot ) {
			return false;
		}
		$admin_email = sanitize_email( get_option( 'shl_tortues_admin_email', get_option( 'admin_email' ) ) );
		$subject     = sprintf( '[Tortues SHL] Nouvelle prospection hors planning – %s – %s', $slot->zone_name, date_i18n( 'd/m/Y', strtotime( $slot->date ) ) );

		$duree = '';
		if ( $reg->actual_time_start && $reg->actual_time_end ) {
			$duree = $reg->actual_time_start . ' → ' . $reg->actual_time_end;
		} elseif ( $reg->actual_time_start ) {
			$duree = 'Début : ' . $reg->actual_time_start;
		}

		$obs_labels = array( 'none' => '✅ Aucune trace', 'suspect' => '⚠️ Trace suspecte', 'confirmed' => '🐢 Trace confirmée !', 'other' => '👁️ Autre observation' );

		$html  = '<p style="font-size:16px;font-weight:bold;color:#2E86AB">🐢 Nouvelle prospection hors planning déclarée</p>';
		$html .= '<table style="border-collapse:collapse;width:100%;max-width:520px;font-size:14px">';
		$rows  = array(
			'Bénévole'    => esc_html( $reg->firstname . ' ' . $reg->lastname ),
			'Email'       => esc_html( $reg->email ),
			'Téléphone'   => esc_html( $reg->phone ?: 'Non renseigné' ),
			'Adhérent'    => $reg->is_member ? 'Oui' : 'Non',
			'Date'        => esc_html( date_i18n( 'l d F Y', strtotime( $slot->date ) ) ),
			'Horaires'    => esc_html( $duree ?: '—' ),
			'Plage'       => esc_html( $slot->zone_name ) . ' – ' . esc_html( $slot->commune ),
			'Type'        => esc_html( self::type_label( $slot->type_prospect ) ),
			'Résultat'    => esc_html( $obs_labels[ $slot->result ] ?? $slot->result ?: '—' ),
			'Commentaire' => $reg->comment ? esc_html( $reg->comment ) : '—',
		);
		foreach ( $rows as $label => $val ) {
			$html .= '<tr><td style="padding:7px 12px;border:1px solid #e0e0e0;background:#f9f9f9;font-weight:bold;width:140px">' . esc_html( $label ) . '</td>'
				   . '<td style="padding:7px 12px;border:1px solid #e0e0e0">' . $val . '</td></tr>';
		}
		$html .= '</table>';
		$url   = admin_url( 'admin.php?page=shl-tortues-registrations' );
		$html .= '<p style="margin-top:20px"><a href="' . esc_url( $url ) . '" style="background:#2E86AB;color:#fff;padding:10px 22px;border-radius:5px;text-decoration:none;font-size:14px">Voir les inscriptions →</a></p>';

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		return wp_mail( $admin_email, $subject, self::wrap( $subject, $html ), $headers );
	}

	// ── Notification à l'administrateur ────────────────────────────────────
	public static function send_admin_notification( $reg_id, $slot_id ) {
		$reg  = SHL_Tortues_DB::get_registration( $reg_id );
		$slot = SHL_Tortues_DB::get_slot( $slot_id );
		if ( ! $reg || ! $slot ) {
			return false;
		}

		$admin_email = sanitize_email( get_option( 'shl_tortues_admin_email', get_option( 'admin_email' ) ) );

		$subject = sprintf(
			'[Tortues SHL] Nouvelle inscription – %s – %s',
			date_i18n( 'd/m/Y', strtotime( $slot->date ) ),
			esc_html( $slot->zone_name )
		);

		$rows = array(
			'Plage / secteur' => esc_html( $slot->zone_name ) . ' – ' . esc_html( $slot->commune ),
			'Date'            => esc_html( date_i18n( 'd/m/Y', strtotime( $slot->date ) ) ) . ' à ' . esc_html( substr( $slot->time_start, 0, 5 ) ),
			'Type'            => esc_html( self::type_label( $slot->type_prospect ) ),
			'Bénévole'        => esc_html( $reg->firstname . ' ' . $reg->lastname ),
			'Email'           => esc_html( $reg->email ),
			'Téléphone'       => esc_html( $reg->phone ?: 'Non renseigné' ),
			'Adhérent'        => $reg->is_member ? 'Oui' : 'Non',
			'Places prises'   => esc_html( $slot->places_taken ) . ' / ' . esc_html( $slot->places_total ),
			'Commentaire'     => $reg->comment ? esc_html( $reg->comment ) : '—',
		);

		$html  = '<p style="font-size:16px;font-weight:bold;color:#2E86AB">Nouvelle inscription reçue</p>';
		$html .= '<table style="border-collapse:collapse;width:100%;max-width:520px;font-size:14px">';
		foreach ( $rows as $label => $value ) {
			$html .= '<tr>';
			$html .= '<td style="padding:8px 12px;border:1px solid #e0e0e0;background:#f9f9f9;font-weight:bold;width:170px">' . esc_html( $label ) . '</td>';
			$html .= '<td style="padding:8px 12px;border:1px solid #e0e0e0">' . $value . '</td>';
			$html .= '</tr>';
		}
		$html .= '</table>';

		$url   = admin_url( 'admin.php?page=shl-tortues-registrations&slot_id=' . $slot_id );
		$html .= '<p style="margin-top:24px"><a href="' . esc_url( $url ) . '" style="background:#2E86AB;color:#fff;padding:10px 22px;border-radius:5px;text-decoration:none;font-size:14px">Voir les inscriptions →</a></p>';

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		return wp_mail( $admin_email, $subject, self::wrap( $subject, $html ), $headers );
	}

	// ── Email de notification admin : nouvelle observation terrain ──────────
	public static function send_observation_notification( $obs_id, $slot_id ) {
		global $wpdb;
		$to  = $wpdb->prefix . 'shl_tortues_observations';
		$obs = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$to}` WHERE id = %d", $obs_id ) ); // phpcs:ignore
		if ( ! $obs ) return false;

		$slot        = SHL_Tortues_DB::get_slot( $slot_id );
		$reg         = SHL_Tortues_DB::get_registration( $obs->reg_id );
		$admin_email = sanitize_email( get_option( 'shl_tortues_admin_email', get_option( 'admin_email' ) ) );

		if ( ! $slot || ! $reg ) return false;

		$subject = sprintf( '[Tortues SHL] Observation terrain – %s – %s', esc_html( $slot->zone_name ), date_i18n( 'd/m/Y', strtotime( $slot->date ) ) );

		$html  = '<p><strong>Nouvelle observation terrain reçue</strong></p>';
		$html .= '<p>Bénévole : ' . esc_html( $reg->firstname . ' ' . $reg->lastname ) . '</p>';
		$html .= '<p>Créneau : ' . esc_html( $slot->zone_name . ' – ' . date_i18n( 'd/m/Y', strtotime( $slot->date ) ) ) . '</p>';
		$html .= '<p>Type : <strong>' . esc_html( SHL_Tortues_Observations::type_label( $obs->obs_type ) ) . '</strong></p>';
		if ( $obs->comment ) {
			$html .= '<p>Commentaire : ' . esc_html( $obs->comment ) . '</p>';
		}
		if ( $obs->photo_url ) {
			$html .= '<p><img src="' . esc_url( $obs->photo_url ) . '" alt="Photo terrain" style="max-width:400px;border-radius:8px"></p>';
		}
		if ( $obs->latitude && $obs->longitude ) {
			$maps = 'https://www.openstreetmap.org/?mlat=' . $obs->latitude . '&mlon=' . $obs->longitude . '&zoom=17';
			$html .= '<p><a href="' . esc_url( $maps ) . '">📍 Voir la position GPS sur la carte</a></p>';
		}

		$url   = admin_url( 'admin.php?page=shl-tortues-observations&slot_id=' . $slot_id );
		$html .= '<p style="margin-top:20px"><a href="' . esc_url( $url ) . '" style="background:#2E86AB;color:#fff;padding:10px 22px;border-radius:5px;text-decoration:none">Voir toutes les observations →</a></p>';

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		return wp_mail( $admin_email, $subject, self::wrap( $subject, $html ), $headers );
	}

	// ── Lien magique espace bénévole ──────────────────────────────────────
	public static function send_magic_link( $email, $magic_url, $first_reg ) {
		$color   = esc_attr( get_option( 'shl_tortues_primary_color', '#2E86AB' ) );
		$prenom  = $first_reg ? esc_html( $first_reg->firstname ) : 'Bénévole';
		$subject = '[Tortues SHL] Connexion à votre espace bénévole';

		$html  = '<p>Bonjour <strong>' . $prenom . '</strong>,</p>';
		$html .= '<p>Vous avez demandé un lien de connexion à votre espace bénévole. Cliquez sur le bouton ci-dessous pour accéder à votre historique de prospections et télécharger votre attestation.</p>';
		$html .= '<div style="text-align:center;margin:28px 0">';
		$html .= '<a href="' . esc_url( $magic_url ) . '" style="display:inline-block;background:' . $color . ';color:#fff;font-weight:800;font-size:15px;padding:14px 32px;border-radius:10px;text-decoration:none">🔑 Accéder à mon espace bénévole</a>';
		$html .= '</div>';
		$html .= '<div style="background:#fff8e1;border:1px solid #ffe082;border-radius:8px;padding:12px 16px;font-size:12px;color:#6d5000;margin-bottom:16px">';
		$html .= '⏱️ Ce lien est <strong>valable 24 heures</strong>. Passé ce délai, vous devrez en demander un nouveau.<br>';
		$html .= '🔒 Ne partagez pas ce lien — il donne accès à vos données personnelles.';
		$html .= '</div>';
		$html .= '<p style="font-size:12px;color:#8a9ab0">Si vous n\'êtes pas à l\'origine de cette demande, ignorez simplement cet email. Votre compte n\'est pas compromis.</p>';

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: Sauvegarde Hérault Littoral <' . sanitize_email( get_option( 'shl_tortues_admin_email', get_option( 'admin_email' ) ) ) . '>',
		);
		return wp_mail( $email, $subject, self::wrap( $subject, $html ), $headers );
	}

	// ── Confirmation liste d'attente ──────────────────────────────────────
	public static function send_waitlist_confirmation( $reg_id, $slot_id ) {
		$reg  = SHL_Tortues_DB::get_registration( $reg_id );
		$slot = SHL_Tortues_DB::get_slot( $slot_id );
		if ( ! $reg || ! $slot ) {
			return false;
		}

		$color   = esc_attr( get_option( 'shl_tortues_primary_color', '#2E86AB' ) );
		$subject = sprintf( '[Tortues SHL] Liste d\'attente – Prospection du %s – %s',
			date_i18n( 'd/m/Y', strtotime( $slot->date ) ), $slot->zone_name );

		$html  = '<p style="font-size:16px;font-weight:bold;color:#e8a23a">⏳ Vous êtes sur la liste d\'attente</p>';
		$html .= '<p>Bonjour ' . esc_html( $reg->firstname ) . ',</p>';
		$html .= '<p>Le créneau que vous avez demandé est actuellement <strong>complet</strong>. Vous avez été ajouté(e) à la liste d\'attente :</p>';
		$html .= '<table style="border-collapse:collapse;width:100%;max-width:480px;font-size:14px;margin:16px 0">';
		$rows  = array(
			'📅 Date'   => esc_html( date_i18n( 'l d F Y', strtotime( $slot->date ) ) ),
			'🏖️ Plage' => esc_html( $slot->zone_name ) . ' – ' . esc_html( $slot->commune ),
			'⏰ Heure'  => esc_html( substr( $slot->time_start, 0, 5 ) ),
			'🚶 Type'   => esc_html( self::type_label( $slot->type_prospect ) ),
		);
		foreach ( $rows as $label => $val ) {
			$html .= '<tr><td style="padding:7px 12px;border:1px solid #e0e0e0;background:#f9f9f9;font-weight:bold;width:130px">' . esc_html( $label ) . '</td>'
				. '<td style="padding:7px 12px;border:1px solid #e0e0e0">' . $val . '</td></tr>';
		}
		$html .= '</table>';
		$html .= '<div style="background:#fff8e1;border:1px solid #ffe082;border-radius:8px;padding:14px 16px;margin:16px 0;font-size:13px">';
		$html .= '<strong>📬 Et ensuite ?</strong><br>';
		$html .= 'Si un bénévole se désiste, vous recevrez automatiquement un email vous proposant la place libérée. Vous aurez <strong>24 heures</strong> pour confirmer votre participation.';
		$html .= '</div>';
		$html .= '<p style="color:#888;font-size:12px">Si vous souhaitez vous désinscrire de la liste d\'attente, contactez-nous par email.</p>';

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: Sauvegarde Hérault Littoral <' . sanitize_email( get_option( 'shl_tortues_admin_email', get_option( 'admin_email' ) ) ) . '>',
		);
		return wp_mail( $reg->email, $subject, self::wrap( $subject, $html ), $headers );
	}

	// ── Invitation liste d'attente (place libérée) ─────────────────────────
	public static function send_waitlist_invitation( $reg_id, $slot_id ) {
		$reg  = SHL_Tortues_DB::get_registration( $reg_id );
		$slot = SHL_Tortues_DB::get_slot( $slot_id );
		if ( ! $reg || ! $slot ) {
			return false;
		}

		$color       = esc_attr( get_option( 'shl_tortues_primary_color', '#2E86AB' ) );
		$terrain_url = home_url( '?shl_terrain=' . rawurlencode( $reg->token ) );
		$cancel_url  = home_url( '?shl_cancel='  . rawurlencode( $reg->token ) );
		$subject     = sprintf( '[Tortues SHL] 🎉 Une place s\'est libérée ! – Prospection du %s – %s',
			date_i18n( 'd/m/Y', strtotime( $slot->date ) ), $slot->zone_name );

		$html  = '<p style="font-size:18px;font-weight:bold;color:#4caf7d">🎉 Une place vient de se libérer !</p>';
		$html .= '<p>Bonjour ' . esc_html( $reg->firstname ) . ',</p>';
		$html .= '<p>Bonne nouvelle ! Vous étiez sur liste d\'attente pour la prospection suivante et une place vient de se libérer :</p>';
		$html .= '<table style="border-collapse:collapse;width:100%;max-width:480px;font-size:14px;margin:16px 0">';
		$rows  = array(
			'📅 Date'    => esc_html( date_i18n( 'l d F Y', strtotime( $slot->date ) ) ),
			'🏖️ Plage'  => esc_html( $slot->zone_name ) . ' – ' . esc_html( $slot->commune ),
			'⏰ Heure'   => esc_html( substr( $slot->time_start, 0, 5 ) ),
			'🚶 Type'    => esc_html( self::type_label( $slot->type_prospect ) ),
			'📌 RDV'     => esc_html( $slot->meeting_point ?: 'Non précisé' ),
		);
		foreach ( $rows as $label => $val ) {
			$html .= '<tr><td style="padding:7px 12px;border:1px solid #e0e0e0;background:#f9f9f9;font-weight:bold;width:130px">' . esc_html( $label ) . '</td>'
				. '<td style="padding:7px 12px;border:1px solid #e0e0e0">' . $val . '</td></tr>';
		}
		$html .= '</table>';
		$html .= '<div style="background:#e8f5e9;border:2px solid #4caf7d;border-radius:8px;padding:16px 18px;margin:16px 0;text-align:center">';
		$html .= '<p style="font-size:14px;font-weight:bold;color:#2a7a4a;margin:0 0 12px">⏰ Vous avez <u>24 heures</u> pour confirmer</p>';
		$html .= '<p style="font-size:13px;color:#555;margin:0 0 14px">Votre inscription est réservée. Sans réponse de votre part, la place sera proposée au suivant.</p>';
		$html .= '<a href="' . esc_url( $terrain_url ) . '" style="display:inline-block;background:#4caf7d;color:#fff;font-weight:800;font-size:14px;padding:12px 28px;border-radius:8px;text-decoration:none">✅ Je confirme ma participation</a>';
		$html .= '</div>';
		$html .= '<p style="text-align:center;font-size:12px;color:#8a9ab0;margin-top:12px">';
		$html .= 'Vous ne pouvez finalement pas venir ? <a href="' . esc_url( $cancel_url ) . '" style="color:#e05555">Décliner la place</a>';
		$html .= '</p>';

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: Sauvegarde Hérault Littoral <' . sanitize_email( get_option( 'shl_tortues_admin_email', get_option( 'admin_email' ) ) ) . '>',
		);
		return wp_mail( $reg->email, $subject, self::wrap( $subject, $html ), $headers );
	}

	// ── Message groupé broadcast ───────────────────────────────────────────
	public static function send_broadcast( $slot_id, $subject, $message ) {
		$slot = SHL_Tortues_DB::get_slot( $slot_id );
		if ( ! $slot ) {
			return 0;
		}

		$regs       = SHL_Tortues_DB::get_slot_registrations( $slot_id );
		$from_email = sanitize_email( get_option( 'shl_tortues_admin_email', get_option( 'admin_email' ) ) );
		$headers    = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: Sauvegarde Hérault Littoral <' . $from_email . '>',
		);
		$date_label  = date_i18n( 'd/m/Y', strtotime( $slot->date ) );
		$full_subject = '[Tortues SHL] ' . $subject;
		$sent        = 0;

		foreach ( $regs as $reg ) {
			if ( ! in_array( $reg->status, array( 'pending', 'validated' ), true ) ) {
				continue;
			}
			$html  = '<p>Bonjour <strong>' . esc_html( $reg->firstname ) . '</strong>,</p>';
			$html .= '<hr style="border:none;border-top:1px solid #e8ecf0;margin:16px 0">';
			$html .= '<div style="font-size:15px;line-height:1.7">' . nl2br( esc_html( $message ) ) . '</div>';
			$html .= '<hr style="border:none;border-top:1px solid #e8ecf0;margin:16px 0">';
			$html .= '<p style="font-size:12px;color:#8a9ab0">Ce message concerne la prospection du '
				. esc_html( $date_label ) . ' – ' . esc_html( $slot->zone_name ) . '.</p>';

			wp_mail( $reg->email, $full_subject, self::wrap( $full_subject, $html ), $headers );
			$sent++;
		}
		return $sent;
	}

	// ── Alerte météo dangereuse (admin) ───────────────────────────────────
	public static function send_weather_alert( $slot, $forecast, $danger ) {
		$admin_email   = get_option( 'shl_tortues_admin_email', get_option( 'admin_email' ) );
		$date_fmt      = date_i18n( 'd/m/Y', strtotime( $slot->date ) );
		$date_long     = date_i18n( 'l d F Y', strtotime( $slot->date ) );
		$subject       = '[Tortues SHL] ⚠️ Alerte météo – ' . $slot->zone_name . ' – ' . $date_fmt;
		$dashboard_url = admin_url( 'admin.php?page=shl-tortues' );

		$html  = '<p style="font-size:16px;font-weight:bold;color:#e05555">⚠️ Alerte météo – Créneau à risque</p>';
		$html .= '<p>Une condition météorologique potentiellement dangereuse est prévue pour le créneau suivant&nbsp;:</p>';
		$html .= '<table style="border-collapse:collapse;width:100%;max-width:480px;font-size:14px;margin:16px 0">';
		$html .= '<tr><td style="padding:7px 12px;border:1px solid #e0e0e0;background:#f9f9f9;font-weight:bold;width:130px">📅 Date</td><td style="padding:7px 12px;border:1px solid #e0e0e0">' . esc_html( $date_long ) . '</td></tr>';
		$html .= '<tr><td style="padding:7px 12px;border:1px solid #e0e0e0;background:#f9f9f9;font-weight:bold">🏖️ Zone</td><td style="padding:7px 12px;border:1px solid #e0e0e0">' . esc_html( $slot->zone_name ) . ' – ' . esc_html( $slot->commune ) . '</td></tr>';
		$html .= '<tr><td style="padding:7px 12px;border:1px solid #e0e0e0;background:#f9f9f9;font-weight:bold">⏰ Heure</td><td style="padding:7px 12px;border:1px solid #e0e0e0">' . esc_html( substr( $slot->time_start, 0, 5 ) ) . '</td></tr>';
		$html .= '</table>';
		$html .= '<div style="background:#fff3cd;border:2px solid #ffc107;border-radius:10px;padding:14px 18px;margin:16px 0">';
		$html .= '<p style="margin:0;font-size:15px;font-weight:bold">' . esc_html( $danger['icon'] ) . ' ' . esc_html( $danger['reason'] ) . '</p>';
		$html .= '<p style="margin:8px 0 0;font-size:13px;color:#856404">' . esc_html( $forecast['icon'] ) . ' ' . esc_html( $forecast['label'] ) . ' · Vent&nbsp;: ' . esc_html( $forecast['wind'] ) . ' km/h · Précip.&nbsp;: ' . esc_html( $forecast['rain'] ) . ' mm · T° ' . esc_html( $forecast['tmin'] ) . '-' . esc_html( $forecast['tmax'] ) . '°C</p>';
		$html .= '</div>';
		$html .= '<p>Pour annuler ce créneau et notifier automatiquement tous les inscrits, rendez-vous sur le tableau de bord&nbsp;:</p>';
		$html .= '<p style="text-align:center;margin-top:16px"><a href="' . esc_url( $dashboard_url ) . '" style="background:#e05555;color:#fff;font-weight:700;font-size:15px;padding:12px 28px;border-radius:8px;text-decoration:none;display:inline-block">🗂️ Tableau de bord admin</a></p>';

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: Sauvegarde Hérault Littoral <' . sanitize_email( $admin_email ) . '>',
		);
		wp_mail( $admin_email, $subject, self::wrap( $subject, $html ), $headers );
	}

	// ── Annulation météo (bénévole) ────────────────────────────────────────
	public static function send_weather_cancellation( $reg, $slot, $reason = '' ) {
		$color    = esc_attr( get_option( 'shl_tortues_primary_color', '#2E86AB' ) );
		$date_fmt = date_i18n( 'l d F Y', strtotime( $slot->date ) );
		$subject  = '[Tortues SHL] ❌ Annulation météo – ' . date_i18n( 'd/m/Y', strtotime( $slot->date ) );

		$html  = '<p style="font-size:16px;font-weight:bold;color:#e05555">❌ Créneau annulé – Conditions météo</p>';
		$html .= '<p>Bonjour ' . esc_html( $reg->firstname ) . ',</p>';
		$html .= '<p>En raison des conditions météorologiques défavorables, le créneau auquel vous étiez inscrit·e est <strong>annulé</strong>&nbsp;:</p>';
		$html .= '<table style="border-collapse:collapse;width:100%;max-width:480px;font-size:14px;margin:16px 0">';
		$html .= '<tr><td style="padding:7px 12px;border:1px solid #e0e0e0;background:#f9f9f9;font-weight:bold;width:130px">📅 Date</td><td style="padding:7px 12px;border:1px solid #e0e0e0">' . esc_html( $date_fmt ) . '</td></tr>';
		$html .= '<tr><td style="padding:7px 12px;border:1px solid #e0e0e0;background:#f9f9f9;font-weight:bold">🏖️ Zone</td><td style="padding:7px 12px;border:1px solid #e0e0e0">' . esc_html( $slot->zone_name ) . ' – ' . esc_html( $slot->commune ) . '</td></tr>';
		if ( $reason ) {
			$html .= '<tr><td style="padding:7px 12px;border:1px solid #e0e0e0;background:#f9f9f9;font-weight:bold">⚠️ Motif</td><td style="padding:7px 12px;border:1px solid #e0e0e0">' . esc_html( $reason ) . '</td></tr>';
		}
		$html .= '</table>';
		$html .= '<p>Votre inscription est automatiquement annulée. De nouveaux créneaux seront proposés prochainement.</p>';
		$html .= '<p style="margin-top:20px"><a href="' . esc_url( home_url( '/' ) ) . '" style="background:' . $color . ';color:#fff;padding:10px 22px;border-radius:8px;text-decoration:none;font-size:14px">📅 Voir le planning</a></p>';
		$html .= '<p>Merci pour votre engagement et à très bientôt&nbsp;!</p>';
		$html .= '<p style="font-size:12px;color:#888">L\'équipe Sauvegarde Hérault Littoral</p>';

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: Sauvegarde Hérault Littoral <' . sanitize_email( get_option( 'shl_tortues_admin_email', get_option( 'admin_email' ) ) ) . '>',
		);
		return wp_mail( $reg->email, $subject, self::wrap( $subject, $html ), $headers );
	}

	// ── Bloc espace bénévole (réutilisable dans les emails) ───────────────
	private static function benevole_block() {
		$url   = get_option( 'shl_tortues_benevole_url', '' );
		$color = esc_attr( get_option( 'shl_tortues_primary_color', '#2E86AB' ) );
		if ( ! $url ) {
			return '';
		}
		return '<div style="background:#e8f4ff;border:1px solid #c0d8ec;border-radius:10px;padding:14px 18px;margin:20px 0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">'
			. '<div>'
			. '<div style="font-weight:700;color:#1a5f7a;font-size:14px">👤 Votre espace bénévole</div>'
			. '<div style="font-size:12px;color:#4a7a9a;margin-top:2px">Historique, attestation, badges, prochaines prospections</div>'
			. '</div>'
			. '<a href="' . esc_url( $url ) . '" style="background:' . $color . ';color:#fff;font-weight:700;font-size:13px;padding:9px 18px;border-radius:8px;text-decoration:none;white-space:nowrap">Accéder →</a>'
			. '</div>';
	}

	// ── Gabarit HTML de l'email – v2.1 ───────────────────────────────────
	private static function wrap( $title, $content ) {
		$color        = esc_attr( get_option( 'shl_tortues_primary_color', '#2E86AB' ) );
		$benevole_url = get_option( 'shl_tortues_benevole_url', '' );

		$benevole_btn = $benevole_url
			? '<a href="' . esc_url( $benevole_url ) . '" style="display:inline-block;margin:4px;background:#fff;color:' . $color . ';font-weight:700;font-size:12px;padding:8px 18px;border-radius:20px;text-decoration:none;border:2px solid ' . $color . '">👤 Mon espace</a>'
			: '';

		/* SVG vague entre header et contenu */
		$wave = '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
			. '<td style="background:' . $color . ';padding:0;line-height:0">'
			. '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 32" width="100%" height="32" style="display:block">'
			. '<path d="M0,16 C100,32 200,0 300,16 C400,32 500,0 600,16 L600,32 L0,32 Z" fill="#ffffff"/>'
			. '</svg>'
			. '</td></tr></table>';

		return '<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>' . esc_html( $title ) . '</title>
</head>
<body style="margin:0;padding:0;background:#eef3f8;font-family:Arial,Helvetica,sans-serif;-webkit-font-smoothing:antialiased">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eef3f8">
  <tr><td style="padding:24px 16px">

    <table width="100%" cellpadding="0" cellspacing="0" border="0"
           style="max-width:600px;margin:0 auto;border-radius:18px;overflow:hidden;box-shadow:0 8px 32px rgba(46,134,171,.15)">

      <!-- ═══ HEADER ═══ -->
      <tr>
        <td style="background:linear-gradient(135deg,' . $color . ' 0%,#1a6890 100%);padding:32px 32px 0;text-align:center">
          <!-- Emoji tortue -->
          <div style="font-size:58px;line-height:1;margin-bottom:14px;filter:drop-shadow(0 4px 10px rgba(0,0,0,.25))">🐢</div>
          <!-- Titre -->
          <div style="color:#ffffff;font-size:20px;font-weight:700;letter-spacing:.3px;margin-bottom:6px">
            Planning Prospections Tortues Marines
          </div>
          <div style="color:rgba(255,255,255,.78);font-size:13px;margin-bottom:0">
            Sauvegarde Hérault Littoral &nbsp;·&nbsp; Hérault, France
          </div>
        </td>
      </tr>

      <!-- Vague de transition -->
      <tr>
        <td style="background:' . $color . ';padding:0;line-height:0">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 32" width="100%" height="32" style="display:block">
            <path d="M0,16 C100,32 200,0 300,16 C400,32 500,0 600,16 L600,32 L0,32 Z" fill="#ffffff"/>
          </svg>
        </td>
      </tr>

      <!-- ═══ CONTENU ═══ -->
      <tr>
        <td style="background:#ffffff;padding:30px 32px 24px;font-size:14px;line-height:1.75;color:#2c3e50">
          ' . $content . '
        </td>
      </tr>

      <!-- ═══ PIED DE PAGE ═══ -->
      <tr>
        <td style="background:#f7f9fb;border-top:1px solid #e8ecf0;padding:20px 24px;text-align:center">

          <!-- Boutons -->
          <div style="margin-bottom:14px">
            ' . $benevole_btn . '
            <a href="https://chat.whatsapp.com/E6XzXU3n86MDmIHpzJ7XvR"
               style="display:inline-block;margin:4px;background:#25d366;color:#fff;font-weight:700;font-size:12px;padding:8px 18px;border-radius:20px;text-decoration:none">
              💬 WhatsApp
            </a>
            <a href="https://ashl.fr//wp-content/plugins/planning-prospections-tortues/GUIDE_BENEVOLES.html"
               style="display:inline-block;margin:4px;background:' . $color . ';color:#fff;font-weight:700;font-size:12px;padding:8px 18px;border-radius:20px;text-decoration:none">
              📖 Guide bénévoles
            </a>
          </div>

          <!-- Contacts -->
          <div style="border-top:1px solid #eaedf0;padding-top:12px;font-size:11px;color:#8a9ab0;line-height:1.8">
            Association Sauvegarde Hérault Littoral<br>
            <strong style="color:#6a7e8e">📞 <a href="tel:0423500338" style="color:#6a7e8e;text-decoration:none">04 23 50 03 38</a></strong>
            &nbsp;&middot;&nbsp;
            RTMMF&nbsp;: <a href="tel:0616862686" style="color:#6a7e8e;text-decoration:none">06 16 86 26 86</a><br>
            <span style="color:#bcc8d4;display:block;margin-top:6px">
              Ce message est généré automatiquement — merci de ne pas y répondre directement.
            </span>
          </div>

        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>';
	}
}
