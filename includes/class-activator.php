<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SHL_Tortues_Activator {

	public static function activate() {
		self::create_tables();
		self::set_default_options();
		SHL_Tortues_Cron::register();
		flush_rewrite_rules();
		update_option( 'shl_tortues_db_version', SHL_TORTUES_VERSION );
	}

	private static function create_tables() {
		global $wpdb;
		$c = $wpdb->get_charset_collate();

		$ts  = $wpdb->prefix . 'shl_tortues_slots';
		$tr  = $wpdb->prefix . 'shl_tortues_registrations';
		$tz  = $wpdb->prefix . 'shl_tortues_zones';
		$to  = $wpdb->prefix . 'shl_tortues_observations';

		// Note : dbDelta nécessite exactement 2 espaces après PRIMARY KEY
		$sql_slots = "CREATE TABLE {$ts} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  date date NOT NULL,
  time_start time NOT NULL,
  time_end time DEFAULT NULL,
  zone_id bigint(20) unsigned DEFAULT NULL,
  zone_name varchar(200) NOT NULL DEFAULT '',
  commune varchar(200) NOT NULL DEFAULT '',
  meeting_point text DEFAULT NULL,
  latitude varchar(25) DEFAULT NULL,
  longitude varchar(25) DEFAULT NULL,
  type_prospect varchar(20) NOT NULL DEFAULT 'foot',
  places_total smallint(5) unsigned NOT NULL DEFAULT 2,
  places_taken smallint(5) unsigned NOT NULL DEFAULT 0,
  status varchar(20) NOT NULL DEFAULT 'open',
  instructions text DEFAULT NULL,
  referent varchar(200) DEFAULT NULL,
  result varchar(20) NOT NULL DEFAULT '',
  result_comment text DEFAULT NULL,
  is_hors_planning tinyint(1) NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  updated_at datetime DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY date_idx (date),
  KEY status_idx (status)
) {$c};";

		// token : lien unique envoyé par email pour accéder au formulaire terrain
		$sql_reg = "CREATE TABLE {$tr} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  slot_id bigint(20) unsigned NOT NULL,
  firstname varchar(100) NOT NULL DEFAULT '',
  lastname varchar(100) NOT NULL DEFAULT '',
  email varchar(200) NOT NULL DEFAULT '',
  phone varchar(30) DEFAULT NULL,
  is_member tinyint(1) NOT NULL DEFAULT 0,
  comment text DEFAULT NULL,
  accepted_rules tinyint(1) NOT NULL DEFAULT 0,
  status varchar(20) NOT NULL DEFAULT 'pending',
  token varchar(64) DEFAULT NULL,
  actual_time_start varchar(5) DEFAULT NULL,
  actual_time_end varchar(5) DEFAULT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY slot_id_idx (slot_id),
  KEY email_idx (email),
  UNIQUE KEY slot_email (slot_id,email),
  UNIQUE KEY token_idx (token)
) {$c};";

		$sql_zones = "CREATE TABLE {$tz} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(200) NOT NULL DEFAULT '',
  commune varchar(200) NOT NULL DEFAULT '',
  description text DEFAULT NULL,
  gps_lat varchar(25) DEFAULT NULL,
  gps_lng varchar(25) DEFAULT NULL,
  geojson_zone longtext DEFAULT NULL,
  priority tinyint(3) unsigned NOT NULL DEFAULT 3,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id)
) {$c};";

		// Observations terrain : photos géolocalisées + résultats saisis par les bénévoles
		$sql_obs = "CREATE TABLE {$to} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  slot_id bigint(20) unsigned NOT NULL,
  reg_id bigint(20) unsigned DEFAULT NULL,
  token varchar(64) DEFAULT NULL,
  obs_type varchar(20) NOT NULL DEFAULT '',
  comment text DEFAULT NULL,
  photo_path varchar(600) DEFAULT NULL,
  photo_url varchar(600) DEFAULT NULL,
  latitude varchar(25) DEFAULT NULL,
  longitude varchar(25) DEFAULT NULL,
  accuracy varchar(20) DEFAULT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY slot_id_idx (slot_id),
  KEY reg_id_idx (reg_id)
) {$c};";

		$tt = $wpdb->prefix . 'shl_tortues_tracks';
		$sql_tracks = "CREATE TABLE {$tt} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  reg_id bigint(20) unsigned NOT NULL,
  slot_id bigint(20) unsigned NOT NULL,
  geojson longtext NOT NULL,
  distance_m float NOT NULL DEFAULT 0,
  duration_s int NOT NULL DEFAULT 0,
  started_at varchar(5) DEFAULT NULL,
  ended_at varchar(5) DEFAULT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY reg_id_unique (reg_id),
  KEY slot_id_idx (slot_id)
) {$c};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_slots );
		dbDelta( $sql_reg );
		dbDelta( $sql_zones );
		dbDelta( $sql_obs );
		dbDelta( $sql_tracks );

		update_option( 'shl_tortues_db_version', SHL_TORTUES_VERSION );
	}

	private static function set_default_options() {
		$terrain_url = home_url( '?shl_terrain={token}' );

		$defaults = array(
			'shl_tortues_admin_email'            => get_option( 'admin_email' ),
			'shl_tortues_show_names'             => '1',
			'shl_tortues_default_places'         => '2',
			'shl_tortues_primary_color'          => '#2E86AB',
			'shl_tortues_confirm_message'        => "Bonjour {prenom},\n\nVotre inscription à la prospection du {date} sur la plage de {plage} ({commune}) a bien été enregistrée.\n\nHeure de départ : {heure}\nPoint de rendez-vous : {rdv}\nType de prospection : {type}\n\n---\n📱 FORMULAIRE TERRAIN\nDurant ou après votre prospection, vous pouvez saisir vos observations et photos géolocalisées via ce lien personnel :\n{terrain_url}\n\n(Ce lien est personnel, ne le partagez pas)\n---\n\nConsignes :\n{consignes}\n\nMerci pour votre engagement bénévole !\n\nL'équipe Sauvegarde Hérault Littoral",
			'shl_tortues_general_instructions'   => "Munissez-vous de chaussures fermées, d'eau et d'une protection solaire.\nSignalez toute trace suspecte au référent du groupe.\nNe dérangez pas la faune et la flore littorales.\nRespectez les consignes de sécurité en vigueur.\nConservez les traces photographiques de toute observation.",
			'shl_tortues_allow_uninstall_cleanup'=> '0',
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}
	}
}
