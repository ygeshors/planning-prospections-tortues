<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SHL_Tortues_Admin_Dashboard {
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$year            = isset( $_GET['season_year'] ) ? intval( $_GET['season_year'] ) : intval( gmdate( 'Y' ) );
		$stats           = SHL_Tortues_DB::get_stats();
		$upcoming        = SHL_Tortues_DB::get_upcoming_slots( 10 );
		$season          = SHL_Tortues_DB::get_season_stats( $year );
		$weather_alerts  = self::get_weather_alerts();
		include SHL_TORTUES_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	private static function get_weather_alerts() {
		$days     = intval( get_option( 'shl_tortues_weather_alert_days', 2 ) );
		$max_wind = intval( get_option( 'shl_tortues_weather_alert_wind', 50 ) );
		$max_rain = intval( get_option( 'shl_tortues_weather_alert_rain', 20 ) );

		$from  = gmdate( 'Y-m-d' );
		$until = gmdate( 'Y-m-d', strtotime( "+{$days} days" ) );

		$slots  = SHL_Tortues_DB::get_upcoming_slots_for_weather( $from, $until );
		$alerts = array();

		foreach ( $slots as $slot ) {
			if ( empty( $slot->latitude ) || empty( $slot->longitude ) ) {
				continue;
			}
			$forecast = SHL_Tortues_Weather::get_forecast(
				floatval( $slot->latitude ),
				floatval( $slot->longitude ),
				$slot->date
			);
			if ( ! $forecast ) {
				continue;
			}
			$danger = SHL_Tortues_Weather::is_dangerous( $forecast, $max_wind, $max_rain );
			if ( $danger ) {
				$alerts[] = array( 'slot' => $slot, 'forecast' => $forecast, 'danger' => $danger );
			}
		}
		return $alerts;
	}
}
