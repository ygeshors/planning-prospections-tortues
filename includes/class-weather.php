<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Intégration météo via Open-Meteo (API gratuite, sans clé).
 * Données mises en cache 3h via WP transients.
 */
class SHL_Tortues_Weather {

	const TRANSIENT_PREFIX = 'shl_wx_';
	const CACHE_SECONDS    = 10800; // 3 h

	private static $wmo = array(
		0  => array( '☀️', 'Dégagé' ),
		1  => array( '🌤', 'Peu nuageux' ),
		2  => array( '⛅', 'Nuageux' ),
		3  => array( '☁️', 'Couvert' ),
		45 => array( '🌫', 'Brouillard' ),
		48 => array( '🌫', 'Brouillard givrant' ),
		51 => array( '🌦', 'Bruine légère' ),
		53 => array( '🌦', 'Bruine' ),
		55 => array( '🌧', 'Bruine forte' ),
		61 => array( '🌧', 'Pluie faible' ),
		63 => array( '🌧', 'Pluie modérée' ),
		65 => array( '🌧', 'Pluie forte' ),
		71 => array( '❄️', 'Neige légère' ),
		73 => array( '❄️', 'Neige' ),
		75 => array( '❄️', 'Neige forte' ),
		77 => array( '🌨', 'Grêle' ),
		80 => array( '🌦', 'Averses légères' ),
		81 => array( '🌦', 'Averses' ),
		82 => array( '🌧', 'Averses fortes' ),
		85 => array( '🌨', 'Averses de neige' ),
		95 => array( '⛈', 'Orage' ),
		96 => array( '⛈', 'Orage + grêle' ),
		99 => array( '⛈', 'Orage violent' ),
	);

	/**
	 * Retourne les prévisions (14 jours) indexées par date 'Y-m-d'.
	 * Si $date est fourni, retourne uniquement ce jour ou null.
	 *
	 * @param  float       $lat
	 * @param  float       $lng
	 * @param  string|null $date  Format 'Y-m-d'
	 * @return array|null
	 */
	public static function get_forecast( $lat, $lng, $date = null ) {
		$lat = round( floatval( $lat ), 3 );
		$lng = round( floatval( $lng ), 3 );
		$key = self::TRANSIENT_PREFIX . str_replace( '.', '_', $lat ) . 'x' . str_replace( '.', '_', $lng );

		$cache = get_transient( $key );

		if ( false === $cache ) {
			$url = add_query_arg( array(
				'latitude'      => $lat,
				'longitude'     => $lng,
				'daily'         => 'weathercode,temperature_2m_max,temperature_2m_min,precipitation_sum,windspeed_10m_max',
				'timezone'      => 'Europe/Paris',
				'forecast_days' => 14,
			), 'https://api.open-meteo.com/v1/forecast' );

			$response = wp_remote_get( $url, array( 'timeout' => 5, 'sslverify' => true ) );

			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				return null;
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( empty( $body['daily']['time'] ) ) {
				return null;
			}

			$cache = $body;
			set_transient( $key, $cache, self::CACHE_SECONDS );
		}

		if ( empty( $cache['daily']['time'] ) ) {
			return null;
		}

		$indexed = array();
		foreach ( $cache['daily']['time'] as $i => $d ) {
			$code                = (int) ( $cache['daily']['weathercode'][ $i ] ?? 0 );
			list( $icon, $label ) = self::resolve_wmo( $code );
			$indexed[ $d ] = array(
				'icon'  => $icon,
				'label' => $label,
				'code'  => $code,
				'tmax'  => (int) round( $cache['daily']['temperature_2m_max'][ $i ] ?? 0 ),
				'tmin'  => (int) round( $cache['daily']['temperature_2m_min'][ $i ] ?? 0 ),
				'rain'  => (float) round( $cache['daily']['precipitation_sum'][ $i ] ?? 0, 1 ),
				'wind'  => (int) round( $cache['daily']['windspeed_10m_max'][ $i ] ?? 0 ),
			);
		}

		if ( null !== $date ) {
			return $indexed[ $date ] ?? null;
		}
		return $indexed;
	}

	/**
	 * Détermine si une prévision est dangereuse pour une prospection.
	 * Retourne un tableau ['type', 'icon', 'reason'] ou false.
	 */
	public static function is_dangerous( $forecast, $max_wind = 50, $max_rain = 20 ) {
		if ( ! $forecast ) {
			return false;
		}
		$code = $forecast['code'] ?? 0;
		if ( $code >= 95 ) {
			return array( 'type' => 'storm', 'icon' => '⛈', 'reason' => 'Orage prévu (' . $forecast['label'] . ')' );
		}
		if ( $forecast['wind'] >= $max_wind ) {
			return array( 'type' => 'wind', 'icon' => '💨', 'reason' => 'Vent fort : ' . $forecast['wind'] . ' km/h (seuil : ' . $max_wind . ' km/h)' );
		}
		if ( $forecast['rain'] >= $max_rain ) {
			return array( 'type' => 'rain', 'icon' => '🌧', 'reason' => 'Fortes précipitations : ' . $forecast['rain'] . ' mm (seuil : ' . $max_rain . ' mm)' );
		}
		return false;
	}

	private static function resolve_wmo( $code ) {
		if ( isset( self::$wmo[ $code ] ) ) {
			return self::$wmo[ $code ];
		}
		$keys = array_keys( self::$wmo );
		rsort( $keys );
		foreach ( $keys as $k ) {
			if ( $code >= $k ) {
				return self::$wmo[ $k ];
			}
		}
		return array( '🌡', 'Météo' );
	}
}
