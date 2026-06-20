<?php
/**
 * Désinstallation propre du plugin.
 * Les tables et options ne sont supprimées que si l'option est activée dans les réglages.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$cleanup = get_option( 'shl_tortues_allow_uninstall_cleanup', '0' );

if ( '1' === $cleanup ) {
	global $wpdb;

	// Suppression des tables
	$tables = array(
		$wpdb->prefix . 'shl_tortues_observations',
		$wpdb->prefix . 'shl_tortues_registrations',
		$wpdb->prefix . 'shl_tortues_slots',
		$wpdb->prefix . 'shl_tortues_zones',
	);
	foreach ( $tables as $table ) {
		$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore
	}

	// Suppression des options
	$options = array(
		'shl_tortues_admin_email',
		'shl_tortues_show_names',
		'shl_tortues_default_places',
		'shl_tortues_primary_color',
		'shl_tortues_confirm_message',
		'shl_tortues_general_instructions',
		'shl_tortues_allow_uninstall_cleanup',
		'shl_tortues_db_version',
	);
	foreach ( $options as $opt ) {
		delete_option( $opt );
	}
}
