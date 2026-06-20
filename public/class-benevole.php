<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Espace bénévole – accès par magic link (sans compte WordPress).
 *
 * Shortcode : [espace_benevole]
 *
 * Flux :
 *  1. Bénévole saisit son email → AJAX → email avec magic link
 *  2. Clic sur le lien → ?shl_bp=TOKEN → template_redirect → session → redirect
 *  3. Page portal → ?shl_bps=SESSION → shortcode affiche le portail
 */
class SHL_Tortues_Benevole {

	const MAGIC_TTL     = DAY_IN_SECONDS;      // 24h
	const SESSION_TTL   = 8 * HOUR_IN_SECONDS; // 8h
	const RATE_LIMIT    = 300;                 // 5 min entre deux envois

	// ── Hooks ──────────────────────────────────────────────────────────────────

	public static function init() {
		add_shortcode( 'espace_benevole', array( __CLASS__, 'shortcode' ) );
		add_action( 'template_redirect', array( __CLASS__, 'intercept' ) );
		add_action( 'wp_ajax_nopriv_shl_send_magic_link', array( __CLASS__, 'ajax_send_magic_link' ) );
		add_action( 'wp_ajax_shl_send_magic_link',        array( __CLASS__, 'ajax_send_magic_link' ) );
	}

	// ── Interception magic link / logout ──────────────────────────────────────

	public static function intercept() {
		// Déconnexion
		if ( isset( $_GET['shl_bp_logout'] ) && isset( $_GET['shl_bps'] ) ) {
			$st = sanitize_text_field( wp_unslash( $_GET['shl_bps'] ) );
			delete_transient( 'shl_bps_' . $st );
			$url = remove_query_arg( array( 'shl_bps', 'shl_bp_logout' ) );
			wp_safe_redirect( $url );
			exit;
		}

		// Validation du magic link
		$token = isset( $_GET['shl_bp'] ) ? sanitize_text_field( wp_unslash( $_GET['shl_bp'] ) ) : '';
		if ( empty( $token ) ) {
			return;
		}

		$email = get_transient( 'shl_bp_' . $token );
		if ( ! $email ) {
			// Lien expiré : le shortcode affichera un message d'erreur
			return;
		}

		// Valide → créer une session et supprimer le magic token (usage unique)
		delete_transient( 'shl_bp_' . $token );
		$session = wp_generate_password( 48, false, false );
		set_transient( 'shl_bps_' . $session, $email, self::SESSION_TTL );

		// Rediriger vers la même page avec le token de session
		$current = ( is_ssl() ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$redirect = remove_query_arg( 'shl_bp', add_query_arg( 'shl_bps', $session, $current ) );
		wp_safe_redirect( $redirect );
		exit;
	}

	// ── Shortcode ──────────────────────────────────────────────────────────────

	public static function shortcode() {
		wp_enqueue_style( 'shl-public-css' );

		$session = isset( $_GET['shl_bps'] ) ? sanitize_text_field( wp_unslash( $_GET['shl_bps'] ) ) : '';
		if ( $session ) {
			$email = get_transient( 'shl_bps_' . $session );
			if ( $email ) {
				return self::render_portal( $email, $session );
			}
			return self::render_login( 'Votre session a expiré. Demandez un nouveau lien de connexion.' );
		}

		// Lien expiré encore dans l'URL
		if ( isset( $_GET['shl_bp'] ) && ! get_transient( 'shl_bp_' . sanitize_text_field( wp_unslash( $_GET['shl_bp'] ) ) ) ) {
			return self::render_login( 'Ce lien de connexion est invalide ou expiré. Demandez-en un nouveau ci-dessous.' );
		}

		return self::render_login();
	}

	// ── AJAX : envoi du magic link ─────────────────────────────────────────────

	public static function ajax_send_magic_link() {
		check_ajax_referer( 'shl_magic_nonce', 'nonce' );

		$email      = sanitize_email( wp_unslash( $_POST['email']      ?? '' ) );
		$portal_url = esc_url_raw( wp_unslash( $_POST['portal_url'] ?? home_url( '/' ) ) );

		// Valider l'URL (doit être sur le même domaine)
		if ( strpos( $portal_url, home_url() ) !== 0 ) {
			$portal_url = home_url( '/' );
		}

		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => 'Adresse email invalide.' ) );
		}

		// Rate limiting
		$rate_key = 'shl_bp_rate_' . md5( $email );
		if ( get_transient( $rate_key ) ) {
			wp_send_json_error( array( 'message' => 'Un lien vous a déjà été envoyé il y a moins de 5 minutes. Vérifiez vos emails et votre dossier spam.' ) );
		}

		// Vérifier que l'email est connu (mais ne pas le révéler pour des raisons de sécurité)
		$history = SHL_Tortues_DB::get_volunteer_history( $email );
		if ( empty( $history ) ) {
			// Même message si email inconnu : on ne révèle pas son existence
			wp_send_json_success( array( 'message' => 'Si votre adresse email est enregistrée dans notre système, vous allez recevoir un lien de connexion dans quelques instants. Pensez à vérifier vos spams.' ) );
		}

		// Générer et stocker le token
		$token = wp_generate_password( 48, false, false );
		set_transient( 'shl_bp_' . $token, $email, self::MAGIC_TTL );
		set_transient( $rate_key, 1, self::RATE_LIMIT );

		// Envoyer l'email
		$magic_url = add_query_arg( 'shl_bp', rawurlencode( $token ), $portal_url );
		SHL_Tortues_Email::send_magic_link( $email, $magic_url, $history[0] );

		wp_send_json_success( array( 'message' => 'Lien envoyé ! Vérifiez votre boîte mail (et le dossier spam). Ce lien est valable 24 heures.' ) );
	}

	// ── Rendu : formulaire de connexion ──────────────────────────────────────

	private static function render_login( $error = '' ) {
		ob_start();
		include SHL_TORTUES_PLUGIN_DIR . 'public/views/benevole-login.php';
		return ob_get_clean();
	}

	// ── Rendu : portail bénévole ───────────────────────────────────────────────

	private static function render_portal( $email, $session ) {
		$history = SHL_Tortues_DB::get_volunteer_history( $email );
		$today   = gmdate( 'Y-m-d' );
		$year    = intval( gmdate( 'Y' ) );

		$upcoming = array_values( array_filter( $history, function ( $h ) use ( $today ) {
			return $h->date && $h->date >= $today
				&& ! in_array( $h->status, array( 'cancelled', 'refused' ), true );
		} ) );

		$past = array_values( array_filter( $history, function ( $h ) use ( $today ) {
			return $h->date && $h->date < $today
				&& ! in_array( $h->status, array( 'cancelled', 'refused' ), true );
		} ) );

		// Fetch GPS tracks for all past registrations
		$past_ids = array_map( function ( $h ) { return (int) $h->id; }, $past );
		$tracks   = SHL_Tortues_DB::get_tracks_by_reg_ids( $past_ids );

		// Badges bénévoles
		$badge_stats = SHL_Tortues_DB::get_volunteer_badge_stats( $email );
		$badges      = SHL_Tortues_Badges::compute( $badge_stats );

		$first_reg    = ! empty( $history ) ? $history[0] : null;
		$att_url      = SHL_Tortues_Attestation::get_url( $email, $year );
		$att_url_prev = $year > 2024 ? SHL_Tortues_Attestation::get_url( $email, $year - 1 ) : '';
		$logout_url   = add_query_arg( 'shl_bp_logout', '1' );

		ob_start();
		include SHL_TORTUES_PLUGIN_DIR . 'public/views/benevole-portal.php';
		return ob_get_clean();
	}
}
