<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Attestation de bénévolat – accessible via :
 * https://votre-site.fr/?shl_attestation=email@example.com&year=2026&key=XXXX
 *
 * Le lien sécurisé est généré depuis l'admin (profil bénévole) via get_url().
 */
class SHL_Tortues_Attestation {

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'intercept' ) );
	}

	public static function intercept() {
		if ( ! isset( $_GET['shl_attestation'] ) ) {
			return;
		}

		$email = sanitize_email( wp_unslash( $_GET['shl_attestation'] ) );
		$year  = isset( $_GET['year'] ) ? intval( $_GET['year'] ) : intval( gmdate( 'Y' ) );
		$key   = isset( $_GET['key'] )  ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';

		if ( ! is_email( $email ) || ! hash_equals( self::make_key( $email, $year ), $key ) ) {
			wp_die( 'Lien invalide ou expiré. Contactez l\'administrateur pour un nouveau lien.', 'Erreur', array( 'response' => 403 ) );
		}

		$history = SHL_Tortues_DB::get_volunteer_history( $email );
		if ( empty( $history ) ) {
			wp_die( 'Aucune participation trouvée pour cette adresse email.', 'Attestation' );
		}

		$season_history = array_values( array_filter( $history, function ( $h ) use ( $year ) {
			return $h->date
				&& intval( substr( $h->date, 0, 4 ) ) === $year
				&& in_array( $h->status, array( 'validated', 'pending' ), true )
				&& $h->date <= gmdate( 'Y-m-d' );
		} ) );

		$first          = $history[0];
		$volunteer_name = trim( $first->firstname . ' ' . $first->lastname );
		$nb_obs         = 0;
		$heures         = 0.0;
		foreach ( $season_history as $h ) {
			$nb_obs += intval( $h->obs_count );
			// Calcul réel si horaires saisies, sinon estimation 2h
			if ( ! empty( $h->actual_time_start ) && ! empty( $h->actual_time_end ) ) {
				$ts = strtotime( '2000-01-01 ' . $h->actual_time_start );
				$te = strtotime( '2000-01-01 ' . $h->actual_time_end );
				if ( $te > $ts ) {
					$heures += ( $te - $ts ) / 3600.0;
				} else {
					$heures += 2.0;
				}
			} else {
				$heures += 2.0;
			}
		}
		$heures = round( $heures, 1 );

		include SHL_TORTUES_PLUGIN_DIR . 'public/views/attestation.php';
		exit;
	}

	/**
	 * Génère l'URL sécurisée de l'attestation (usage admin).
	 *
	 * @param  string $email
	 * @param  int    $year
	 * @return string
	 */
	public static function get_url( $email, $year = null ) {
		if ( null === $year ) {
			$year = intval( gmdate( 'Y' ) );
		}
		return add_query_arg( array(
			'shl_attestation' => rawurlencode( $email ),
			'year'            => $year,
			'key'             => self::make_key( $email, $year ),
		), home_url( '/' ) );
	}

	private static function make_key( $email, $year ) {
		return substr( wp_hash( $email . '|' . intval( $year ) . '|' . AUTH_KEY ), 0, 16 );
	}
}
