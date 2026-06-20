<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Système de badges bénévoles – calculé dynamiquement depuis les données existantes.
 */
class SHL_Tortues_Badges {

	private static function definitions() {
		return array(
			'first_step' => array( 'icon' => '🐣', 'name' => 'Premiers pas',      'desc' => '1re prospection réalisée' ),
			'regular'    => array( 'icon' => '🌊', 'name' => 'Habitué·e',          'desc' => '5 prospections réalisées' ),
			'expert'     => array( 'icon' => '🐢', 'name' => 'Expert·e',           'desc' => '10 prospections réalisées' ),
			'hero'       => array( 'icon' => '🦸', 'name' => 'Super-bénévole',     'desc' => '20 prospections réalisées' ),
			'explorer'   => array( 'icon' => '🗺️', 'name' => 'Explorateur·ice',   'desc' => 'Premier tracé GPS enregistré' ),
			'hiker'      => array( 'icon' => '🚶', 'name' => 'Marcheur·euse',      'desc' => '10 km cumulés (tracé GPS)' ),
			'tireless'   => array( 'icon' => '🏃', 'name' => 'Infatigable',        'desc' => '50 km cumulés (tracé GPS)' ),
			'observer'   => array( 'icon' => '👁️', 'name' => 'Observateur·ice',   'desc' => '1re observation soumise' ),
			'discoverer' => array( 'icon' => '🔍', 'name' => 'Découvreur·euse',    'desc' => 'Trace de tortue confirmée' ),
			'loyal'      => array( 'icon' => '⭐', 'name' => 'Fidèle',             'desc' => '2 saisons ou plus de bénévolat' ),
		);
	}

	private static function check( $key, $stats ) {
		switch ( $key ) {
			case 'first_step':  return $stats['done'] >= 1;
			case 'regular':     return $stats['done'] >= 5;
			case 'expert':      return $stats['done'] >= 10;
			case 'hero':        return $stats['done'] >= 20;
			case 'explorer':    return $stats['km'] > 0;
			case 'hiker':       return $stats['km'] >= 10;
			case 'tireless':    return $stats['km'] >= 50;
			case 'observer':    return $stats['obs'] >= 1;
			case 'discoverer':  return $stats['confirmed'] >= 1;
			case 'loyal':       return $stats['seasons'] >= 2;
			default:            return false;
		}
	}

	/**
	 * Calcule les badges pour un bénévole donné.
	 *
	 * @param  array $stats  Résultat de SHL_Tortues_DB::get_volunteer_badge_stats()
	 * @return array  ['earned' => [...], 'locked' => [...]]
	 */
	public static function compute( $stats ) {
		$all    = self::definitions();
		$earned = array();
		$locked = array();

		foreach ( $all as $key => $def ) {
			if ( self::check( $key, $stats ) ) {
				$earned[ $key ] = $def;
			} else {
				$locked[ $key ] = $def;
			}
		}

		return array( 'earned' => $earned, 'locked' => $locked );
	}
}
