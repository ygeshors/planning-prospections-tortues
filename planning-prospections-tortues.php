<?php
/**
 * Plugin Name: Planning Prospections Tortues Marines
 * Plugin URI:  https://www.sauvegarde-herault-littoral.fr
 * Description: Gestion des créneaux de prospection de traces de tortues marines sur les plages de l'Hérault – Association Sauvegarde Hérault Littoral.
 * Version:     2.1.0
 * Author:      Sauvegarde Hérault Littoral
 * Author URI:  https://www.sauvegarde-herault-littoral.fr
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: planning-tortues
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Constantes ─────────────────────────────────────────────────────────────
define( 'SHL_TORTUES_VERSION',      '2.1.0' );
define( 'SHL_TORTUES_PLUGIN_FILE',  __FILE__ );
define( 'SHL_TORTUES_PLUGIN_DIR',   plugin_dir_path( __FILE__ ) );
define( 'SHL_TORTUES_PLUGIN_URL',   plugin_dir_url( __FILE__ ) );
define( 'SHL_TORTUES_BASENAME',     plugin_basename( __FILE__ ) );

// ── Includes ────────────────────────────────────────────────────────────────
require_once SHL_TORTUES_PLUGIN_DIR . 'includes/class-database.php';
require_once SHL_TORTUES_PLUGIN_DIR . 'includes/class-activator.php';
require_once SHL_TORTUES_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once SHL_TORTUES_PLUGIN_DIR . 'includes/class-email-handler.php';
require_once SHL_TORTUES_PLUGIN_DIR . 'includes/class-export.php';
require_once SHL_TORTUES_PLUGIN_DIR . 'includes/class-observations.php';
require_once SHL_TORTUES_PLUGIN_DIR . 'includes/class-cron.php';
require_once SHL_TORTUES_PLUGIN_DIR . 'includes/class-weather.php';

if ( is_admin() ) {
	require_once SHL_TORTUES_PLUGIN_DIR . 'admin/class-admin.php';
	require_once SHL_TORTUES_PLUGIN_DIR . 'admin/class-admin-dashboard.php';
	require_once SHL_TORTUES_PLUGIN_DIR . 'admin/class-admin-slots.php';
	require_once SHL_TORTUES_PLUGIN_DIR . 'admin/class-admin-registrations.php';
	require_once SHL_TORTUES_PLUGIN_DIR . 'admin/class-admin-zones.php';
	require_once SHL_TORTUES_PLUGIN_DIR . 'admin/class-admin-settings.php';
	require_once SHL_TORTUES_PLUGIN_DIR . 'admin/class-admin-observations.php';
	require_once SHL_TORTUES_PLUGIN_DIR . 'admin/class-admin-broadcast.php';
	require_once SHL_TORTUES_PLUGIN_DIR . 'admin/class-admin-volunteers.php';
	require_once SHL_TORTUES_PLUGIN_DIR . 'admin/class-admin-tracks.php';
	require_once SHL_TORTUES_PLUGIN_DIR . 'admin/class-admin-rapport.php';
}

require_once SHL_TORTUES_PLUGIN_DIR . 'public/class-public.php';
require_once SHL_TORTUES_PLUGIN_DIR . 'public/class-terrain.php';
require_once SHL_TORTUES_PLUGIN_DIR . 'public/class-cancel.php';
require_once SHL_TORTUES_PLUGIN_DIR . 'public/class-libre.php';
require_once SHL_TORTUES_PLUGIN_DIR . 'public/class-stats.php';
require_once SHL_TORTUES_PLUGIN_DIR . 'public/class-attestation.php';
require_once SHL_TORTUES_PLUGIN_DIR . 'public/class-benevole.php';
require_once SHL_TORTUES_PLUGIN_DIR . 'public/class-badges.php';
require_once SHL_TORTUES_PLUGIN_DIR . 'public/class-carte.php';

// ── Hooks d'activation / désactivation ────────────────────────────────────
register_activation_hook( __FILE__,   array( 'SHL_Tortues_Activator',   'activate'   ) );
register_deactivation_hook( __FILE__, array( 'SHL_Tortues_Deactivator', 'deactivate' ) );

// ── Mise à jour automatique de la BDD si version obsolète ─────────────────
add_action( 'plugins_loaded', function () {
	if ( get_option( 'shl_tortues_db_version' ) !== SHL_TORTUES_VERSION ) {
		SHL_Tortues_Activator::activate();
	}
	// Re-enregistrer le cron au cas où il aurait été perdu
	SHL_Tortues_Cron::register();
} );

// ── Classe principale ──────────────────────────────────────────────────────
final class SHL_Tortues_Plugin {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		$this->register_ajax_hooks();

		if ( is_admin() ) {
			( new SHL_Tortues_Admin() )->init();
		}
		( new SHL_Tortues_Public() )->init();
		( new SHL_Tortues_Terrain() )->init();
		SHL_Tortues_Cancel::init();
		SHL_Tortues_Libre::init();
		SHL_Tortues_Libre::register_ajax();
		SHL_Tortues_Stats::init();
		SHL_Tortues_Attestation::init();
		SHL_Tortues_Benevole::init();
		SHL_Tortues_Carte::init();
	}

	public function load_textdomain() {
		load_plugin_textdomain(
			'planning-tortues',
			false,
			dirname( SHL_TORTUES_BASENAME ) . '/languages/'
		);
	}

	private function register_ajax_hooks() {
		$public_actions = array(
			'shl_get_calendar_data',
			'shl_get_slot_details',
			'shl_register_slot',
		);
		foreach ( $public_actions as $action ) {
			add_action( 'wp_ajax_'        . $action, array( 'SHL_Tortues_Public', 'ajax_' . $action ) );
			add_action( 'wp_ajax_nopriv_' . $action, array( 'SHL_Tortues_Public', 'ajax_' . $action ) );
		}

		// Les hooks AJAX terrain sont enregistrés par SHL_Tortues_Terrain::init() via $this.
	}
}

SHL_Tortues_Plugin::get_instance();
