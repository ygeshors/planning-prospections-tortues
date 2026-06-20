<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SHL_Tortues_Export {

	private static $type_labels = array(
		'foot'  => 'À pied',
		'drone' => 'Drone',
		'mixed' => 'Mixte',
	);

	private static $status_labels = array(
		'pending'   => 'En attente',
		'validated' => 'Validé',
		'refused'   => 'Refusé',
	);

	public static function export_csv( $args = array() ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Permission refusée.' );
		}

		$registrations = SHL_Tortues_DB::get_registrations( array_merge( $args, array( 'limit' => 9999 ) ) );

		$filename = 'prospections-tortues-' . gmdate( 'Y-m-d' ) . '.csv';

		// En-têtes HTTP
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$fp = fopen( 'php://output', 'w' ); // phpcs:ignore
		// BOM UTF-8 pour Excel
		fputs( $fp, "\xEF\xBB\xBF" ); // phpcs:ignore

		// En-tête colonnes
		fputcsv( $fp, array( // phpcs:ignore
			'Date',
			'Heure départ',
			'Plage / Secteur',
			'Commune',
			'Type de prospection',
			'Prénom',
			'Nom',
			'Email',
			'Téléphone',
			'Adhérent',
			'Statut inscription',
			'Commentaire',
		), ';' );

		foreach ( $registrations as $r ) {
			fputcsv( $fp, array( // phpcs:ignore
				$r->date     ? date_i18n( 'd/m/Y', strtotime( $r->date ) ) : '',
				$r->time_start ? substr( $r->time_start, 0, 5 ) : '',
				$r->zone_name   ?? '',
				$r->commune     ?? '',
				self::$type_labels[ $r->type_prospect ]   ?? $r->type_prospect,
				$r->firstname,
				$r->lastname,
				$r->email,
				$r->phone ?? '',
				$r->is_member ? 'Oui' : 'Non',
				self::$status_labels[ $r->status ] ?? $r->status,
				$r->comment ?? '',
			), ';' );
		}

		fclose( $fp ); // phpcs:ignore
		exit;
	}
}
