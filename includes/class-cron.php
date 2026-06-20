<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SHL_Tortues_Cron {

	const HOOK         = 'shl_tortues_daily_reminders';
	const WEATHER_HOOK = 'shl_tortues_weather_check';

	// Appelé à l'activation du plugin
	public static function register() {
		add_action( self::HOOK,         array( __CLASS__, 'send_daily_reminders' ) );
		add_action( self::WEATHER_HOOK, array( __CLASS__, 'check_weather_alerts' ) );

		if ( ! wp_next_scheduled( self::HOOK ) ) {
			$next = mktime( 7, 0, 0, (int) gmdate( 'n' ), (int) gmdate( 'j' ) + 1, (int) gmdate( 'Y' ) );
			wp_schedule_event( $next, 'daily', self::HOOK );
		}
		if ( ! wp_next_scheduled( self::WEATHER_HOOK ) ) {
			$next_wx = mktime( 8, 0, 0, (int) gmdate( 'n' ), (int) gmdate( 'j' ) + 1, (int) gmdate( 'Y' ) );
			wp_schedule_event( $next_wx, 'daily', self::WEATHER_HOOK );
		}
	}

	// Appelé à la désactivation du plugin
	public static function unregister() {
		wp_clear_scheduled_hook( self::HOOK );
		wp_clear_scheduled_hook( self::WEATHER_HOOK );
	}

	// Callback cron : envoie un rappel J-1 à tous les inscrits
	public static function send_daily_reminders() {
		global $wpdb;
		$ts = SHL_Tortues_DB::slots_table();
		$tr = SHL_Tortues_DB::reg_table();

		$tomorrow = gmdate( 'Y-m-d', strtotime( '+1 day' ) );

		$slots = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				"SELECT * FROM `{$ts}` WHERE date = %s AND status NOT IN ('cancelled')", // phpcs:ignore
				$tomorrow
			)
		);

		foreach ( $slots as $slot ) {
			$regs = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					"SELECT * FROM `{$tr}` WHERE slot_id = %d AND status IN ('validated', 'pending')", // phpcs:ignore
					$slot->id
				)
			);
			foreach ( $regs as $reg ) {
				SHL_Tortues_Email::send_reminder( $reg, $slot );
			}
		}
	}

	// Callback cron : vérifie la météo des créneaux à venir
	public static function check_weather_alerts() {
		$days     = intval( get_option( 'shl_tortues_weather_alert_days', 2 ) );
		$max_wind = intval( get_option( 'shl_tortues_weather_alert_wind', 50 ) );
		$max_rain = intval( get_option( 'shl_tortues_weather_alert_rain', 20 ) );

		$from  = gmdate( 'Y-m-d' );
		$until = gmdate( 'Y-m-d', strtotime( "+{$days} days" ) );

		$slots = SHL_Tortues_DB::get_upcoming_slots_for_weather( $from, $until );

		foreach ( $slots as $slot ) {
			if ( empty( $slot->latitude ) || empty( $slot->longitude ) ) {
				continue;
			}

			$alert_key = 'shl_wx_alert_' . $slot->id . '_' . gmdate( 'Y-m-d' );
			if ( get_transient( $alert_key ) ) {
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
			if ( ! $danger ) {
				continue;
			}

			SHL_Tortues_Email::send_weather_alert( $slot, $forecast, $danger );
			set_transient( $alert_key, 1, DAY_IN_SECONDS );
		}
	}
}
