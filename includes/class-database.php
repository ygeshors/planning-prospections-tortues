<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Couche d'accès aux données – toutes les requêtes SQL passent ici.
 */
class SHL_Tortues_DB {

	// ── Noms de tables ──────────────────────────────────────────────────────

	public static function slots_table() {
		global $wpdb;
		return $wpdb->prefix . 'shl_tortues_slots';
	}

	public static function reg_table() {
		global $wpdb;
		return $wpdb->prefix . 'shl_tortues_registrations';
	}

	public static function zones_table() {
		global $wpdb;
		return $wpdb->prefix . 'shl_tortues_zones';
	}

	// ══════════════════════════════════════════════════════════════════════
	//  CRÉNEAUX (SLOTS)
	// ══════════════════════════════════════════════════════════════════════

	public static function get_slots( $args = array() ) {
		global $wpdb;
		$t = self::slots_table();

		$defaults = array(
			'date_from' => null,
			'date_to'   => null,
			'status'    => null,
			'type'      => null,
			'orderby'   => 'date',
			'order'     => 'ASC',
			'limit'     => 200,
			'offset'    => 0,
		);
		$a = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$values = array();

		if ( $a['date_from'] ) { $where[] = 'date >= %s'; $values[] = $a['date_from']; }
		if ( $a['date_to']   ) { $where[] = 'date <= %s'; $values[] = $a['date_to'];   }
		if ( $a['status']    ) { $where[] = 'status = %s'; $values[] = $a['status'];   }
		if ( $a['type']      ) { $where[] = 'type_prospect = %s'; $values[] = $a['type']; }

		$allowed_cols = array( 'date', 'time_start', 'zone_name', 'commune', 'status', 'places_taken', 'created_at' );
		$orderby = in_array( $a['orderby'], $allowed_cols, true ) ? $a['orderby'] : 'date';
		$order   = strtoupper( $a['order'] ) === 'DESC' ? 'DESC' : 'ASC';

		$where_sql = implode( ' AND ', $where );
		$values[]  = intval( $a['limit'] );
		$values[]  = intval( $a['offset'] );

		$sql = $wpdb->prepare(
			"SELECT * FROM `{$t}` WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", // phpcs:ignore
			$values
		);

		return $wpdb->get_results( $sql ); // phpcs:ignore
	}

	public static function get_slot( $id ) {
		global $wpdb;
		$t = self::slots_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$t}` WHERE id = %d", $id ) ); // phpcs:ignore
	}

	public static function get_slots_for_month( $year, $month ) {
		global $wpdb;
		$t    = self::slots_table();
		$from = sprintf( '%04d-%02d-01', $year, $month );
		$to   = date( 'Y-m-t', mktime( 0, 0, 0, $month, 1, $year ) );
		return $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				"SELECT * FROM `{$t}` WHERE date >= %s AND date <= %s AND status != 'cancelled' ORDER BY date, time_start", // phpcs:ignore
				$from,
				$to
			)
		);
	}

	public static function get_upcoming_slots( $limit = 10 ) {
		global $wpdb;
		$t = self::slots_table();
		return $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				"SELECT * FROM `{$t}` WHERE date >= CURDATE() AND status NOT IN ('cancelled') ORDER BY date, time_start LIMIT %d", // phpcs:ignore
				$limit
			)
		);
	}

	public static function insert_slot( $data ) {
		global $wpdb;
		$data['created_at'] = current_time( 'mysql' );
		$data['updated_at'] = current_time( 'mysql' );
		$result = $wpdb->insert( self::slots_table(), $data );
		return $result ? $wpdb->insert_id : false;
	}

	public static function update_slot( $id, $data ) {
		global $wpdb;
		$data['updated_at'] = current_time( 'mysql' );
		return $wpdb->update( self::slots_table(), $data, array( 'id' => $id ) );
	}

	public static function delete_slot( $id ) {
		global $wpdb;
		$wpdb->delete( self::reg_table(), array( 'slot_id' => $id ) );
		return $wpdb->delete( self::slots_table(), array( 'id' => $id ) );
	}

	public static function refresh_slot_count( $slot_id ) {
		global $wpdb;
		$t_reg   = self::reg_table();
		$t_slots = self::slots_table();

		$count = (int) $wpdb->get_var( // phpcs:ignore
			$wpdb->prepare( "SELECT COUNT(*) FROM `{$t_reg}` WHERE slot_id = %d AND status NOT IN ('refused', 'cancelled', 'waitlist')", $slot_id ) // phpcs:ignore
		);

		$slot = self::get_slot( $slot_id );
		if ( ! $slot ) {
			return;
		}

		if ( $slot->status === 'cancelled' || $slot->status === 'done' ) {
			$new_status = $slot->status;
		} elseif ( $count >= (int) $slot->places_total ) {
			$new_status = 'full';
		} else {
			$new_status = 'open';
		}

		$wpdb->update( // phpcs:ignore
			$t_slots,
			array( 'places_taken' => $count, 'status' => $new_status, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $slot_id )
		);
	}

	// ══════════════════════════════════════════════════════════════════════
	//  INSCRIPTIONS (REGISTRATIONS)
	// ══════════════════════════════════════════════════════════════════════

	public static function get_registrations( $args = array() ) {
		global $wpdb;
		$tr = self::reg_table();
		$ts = self::slots_table();

		$defaults = array(
			'slot_id'   => null,
			'date_from' => null,
			'date_to'   => null,
			'status'    => null,
			'type'      => null,
			'search'    => null,
			'limit'     => 100,
			'offset'    => 0,
		);
		$a = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$values = array();

		if ( $a['slot_id']   ) { $where[] = 'r.slot_id = %d'; $values[] = $a['slot_id'];   }
		if ( $a['date_from'] ) { $where[] = 's.date >= %s';   $values[] = $a['date_from']; }
		if ( $a['date_to']   ) { $where[] = 's.date <= %s';   $values[] = $a['date_to'];   }
		if ( $a['status']    ) { $where[] = 'r.status = %s';  $values[] = $a['status'];    }
		if ( $a['type']      ) { $where[] = 's.type_prospect = %s'; $values[] = $a['type']; }
		if ( $a['search'] ) {
			$like = '%' . $wpdb->esc_like( $a['search'] ) . '%';
			$where[]  = '(r.firstname LIKE %s OR r.lastname LIKE %s OR r.email LIKE %s)';
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
		}

		$where_sql = implode( ' AND ', $where );
		$values[]  = intval( $a['limit'] );
		$values[]  = intval( $a['offset'] );

		$sql = $wpdb->prepare( // phpcs:ignore
			"SELECT r.*, s.date, s.time_start, s.zone_name, s.commune, s.type_prospect, s.is_hors_planning
			 FROM `{$tr}` r
			 LEFT JOIN `{$ts}` s ON r.slot_id = s.id
			 WHERE {$where_sql}
			 ORDER BY s.date DESC, r.created_at DESC
			 LIMIT %d OFFSET %d", // phpcs:ignore
			$values
		);

		return $wpdb->get_results( $sql ); // phpcs:ignore
	}

	public static function get_registration( $id ) {
		global $wpdb;
		$t = self::reg_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$t}` WHERE id = %d", $id ) ); // phpcs:ignore
	}

	public static function get_slot_registrations( $slot_id ) {
		global $wpdb;
		$t = self::reg_table();
		return $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare( "SELECT * FROM `{$t}` WHERE slot_id = %d AND status != 'refused' ORDER BY created_at ASC", $slot_id ) // phpcs:ignore
		);
	}

	public static function insert_registration( $data ) {
		global $wpdb;
		$t = self::reg_table();

		$existing = $wpdb->get_var( // phpcs:ignore
			$wpdb->prepare( "SELECT id FROM `{$t}` WHERE slot_id = %d AND email = %s", $data['slot_id'], $data['email'] ) // phpcs:ignore
		);
		if ( $existing ) {
			return new WP_Error( 'duplicate', 'Vous êtes déjà inscrit à ce créneau avec cette adresse email.' );
		}

		// Générer un token unique pour le formulaire terrain
		do {
			$token = wp_generate_password( 48, false, false );
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `{$t}` WHERE token = %s", $token ) ); // phpcs:ignore
		} while ( $exists );

		$data['token']      = $token;
		$data['created_at'] = current_time( 'mysql' );
		$result = $wpdb->insert( $t, $data );
		return $result ? $wpdb->insert_id : false;
	}

	public static function update_registration( $id, $data ) {
		global $wpdb;
		return $wpdb->update( self::reg_table(), $data, array( 'id' => $id ) );
	}

	public static function delete_registration( $id ) {
		global $wpdb;
		$reg = self::get_registration( $id );
		$ok  = $wpdb->delete( self::reg_table(), array( 'id' => $id ) );
		if ( $ok && $reg ) {
			self::refresh_slot_count( $reg->slot_id );
		}
		return $ok;
	}

	// ══════════════════════════════════════════════════════════════════════
	//  ZONES DE PROSPECTION
	// ══════════════════════════════════════════════════════════════════════

	public static function get_zones() {
		global $wpdb;
		$t = self::zones_table();
		return $wpdb->get_results( "SELECT * FROM `{$t}` ORDER BY priority ASC, name ASC" ); // phpcs:ignore
	}

	public static function get_zone( $id ) {
		global $wpdb;
		$t = self::zones_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$t}` WHERE id = %d", $id ) ); // phpcs:ignore
	}

	public static function insert_zone( $data ) {
		global $wpdb;
		$data['created_at'] = current_time( 'mysql' );
		$result = $wpdb->insert( self::zones_table(), $data );
		return $result ? $wpdb->insert_id : false;
	}

	public static function update_zone( $id, $data ) {
		global $wpdb;
		return $wpdb->update( self::zones_table(), $data, array( 'id' => $id ) );
	}

	public static function delete_zone( $id ) {
		global $wpdb;
		return $wpdb->delete( self::zones_table(), array( 'id' => $id ) );
	}

	// ══════════════════════════════════════════════════════════════════════
	//  STATISTIQUES
	// ══════════════════════════════════════════════════════════════════════

	public static function get_stats() {
		global $wpdb;
		$ts = self::slots_table();
		$tr = self::reg_table();

		return array(
			'upcoming'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$ts}` WHERE date >= CURDATE() AND status NOT IN ('cancelled')" ), // phpcs:ignore
			'registrations' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$tr}` WHERE status != 'refused'" ), // phpcs:ignore
			'full'          => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$ts}` WHERE status = 'full' AND date >= CURDATE()" ), // phpcs:ignore
			'drone'         => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$ts}` WHERE type_prospect = 'drone' AND date >= CURDATE()" ), // phpcs:ignore
			'done'          => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$ts}` WHERE status = 'done'" ), // phpcs:ignore
		);
	}

	// ── Bilan de saison (dashboard) ────────────────────────────────────────
	public static function get_season_stats( $year ) {
		global $wpdb;
		$ts   = self::slots_table();
		$tr   = self::reg_table();
		$to   = $wpdb->prefix . 'shl_tortues_observations';
		$year = intval( $year );
		$from = "{$year}-01-01";
		$end  = "{$year}-12-31";

		$done = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM `{$ts}` WHERE date BETWEEN %s AND %s AND status = 'done'",
			$from, $end
		) ); // phpcs:ignore

		$total_slots = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM `{$ts}` WHERE date BETWEEN %s AND %s AND status != 'cancelled' AND is_hors_planning = 0",
			$from, $end
		) ); // phpcs:ignore

		$unique_volunteers = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT r.email) FROM `{$tr}` r
			 LEFT JOIN `{$ts}` s ON r.slot_id = s.id
			 WHERE s.date BETWEEN %s AND %s AND r.status != 'refused'",
			$from, $end
		) ); // phpcs:ignore

		$total_participations = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM `{$tr}` r
			 LEFT JOIN `{$ts}` s ON r.slot_id = s.id
			 WHERE s.date BETWEEN %s AND %s AND r.status != 'refused'",
			$from, $end
		) ); // phpcs:ignore

		$total_obs = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM `{$to}` o
			 LEFT JOIN `{$ts}` s ON o.slot_id = s.id
			 WHERE s.date BETWEEN %s AND %s",
			$from, $end
		) ); // phpcs:ignore

		$obs_by_type = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
			"SELECT o.obs_type, COUNT(*) as cnt FROM `{$to}` o
			 LEFT JOIN `{$ts}` s ON o.slot_id = s.id
			 WHERE s.date BETWEEN %s AND %s
			 GROUP BY o.obs_type ORDER BY cnt DESC",
			$from, $end
		) );

		$by_zone = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
			"SELECT s.zone_name,
			        COUNT(DISTINCT s.id) as slots,
			        COUNT(r.id) as participations
			 FROM `{$ts}` s
			 LEFT JOIN `{$tr}` r ON r.slot_id = s.id AND r.status != 'refused'
			 WHERE s.date BETWEEN %s AND %s AND s.status != 'cancelled' AND s.is_hors_planning = 0
			 GROUP BY s.zone_name ORDER BY slots DESC",
			$from, $end
		) );

		$weekly = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
			"SELECT WEEK(s.date, 1) as wk, MIN(s.date) as week_start, COUNT(r.id) as cnt
			 FROM `{$tr}` r
			 LEFT JOIN `{$ts}` s ON r.slot_id = s.id
			 WHERE s.date BETWEEN %s AND %s AND r.status != 'refused'
			 GROUP BY WEEK(s.date, 1) ORDER BY wk ASC",
			$from, $end
		) );

		return compact( 'done', 'total_slots', 'unique_volunteers', 'total_participations', 'total_obs', 'obs_by_type', 'by_zone', 'weekly' );
	}

	// ── Liste des bénévoles (profil admin) ────────────────────────────────
	public static function get_volunteers_summary( $search = '' ) {
		global $wpdb;
		$tr = self::reg_table();
		$ts = self::slots_table();

		$where  = "r.status != 'refused'";
		$values = array();

		if ( $search ) {
			$like    = '%' . $wpdb->esc_like( $search ) . '%';
			$where  .= ' AND (r.firstname LIKE %s OR r.lastname LIKE %s OR r.email LIKE %s)';
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
		}

		$sql = "SELECT r.email,
		               MAX(r.firstname) as firstname,
		               MAX(r.lastname)  as lastname,
		               MAX(r.phone)     as phone,
		               MAX(r.is_member) as is_member,
		               MIN(s.date)      as first_date,
		               MAX(s.date)      as last_date,
		               SUM(CASE WHEN s.date <  CURDATE() THEN 1 ELSE 0 END) as nb_done,
		               SUM(CASE WHEN s.date >= CURDATE() THEN 1 ELSE 0 END) as nb_upcoming
		        FROM `{$tr}` r
		        LEFT JOIN `{$ts}` s ON r.slot_id = s.id
		        WHERE {$where}
		        GROUP BY r.email
		        ORDER BY nb_done DESC, lastname ASC
		        LIMIT 500";

		if ( $values ) {
			return $wpdb->get_results( $wpdb->prepare( $sql, $values ) ); // phpcs:ignore
		}
		return $wpdb->get_results( $sql ); // phpcs:ignore
	}

	// ── Liste d'attente ───────────────────────────────────────────────────

	public static function get_waitlist_registrations( $slot_id ) {
		global $wpdb;
		$t = self::reg_table();
		return $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				"SELECT * FROM `{$t}` WHERE slot_id = %d AND status = 'waitlist' ORDER BY created_at ASC",
				$slot_id
			)
		);
	}

	/**
	 * Promeut la première personne en liste d'attente si une place s'est libérée.
	 * Envoie l'email d'invitation (place disponible – confirmer sous 24h).
	 *
	 * @param  int $slot_id
	 * @return bool  true si quelqu'un a été promu
	 */
	public static function promote_first_waitlist( $slot_id ) {
		$slot = self::get_slot( $slot_id );
		if ( ! $slot || in_array( $slot->status, array( 'cancelled', 'done' ), true ) ) {
			return false;
		}

		// Vérifie qu'une place est réellement disponible
		if ( (int) $slot->places_taken >= (int) $slot->places_total ) {
			return false;
		}

		global $wpdb;
		$t   = self::reg_table();
		$reg = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore
			"SELECT * FROM `{$t}` WHERE slot_id = %d AND status = 'waitlist' ORDER BY created_at ASC LIMIT 1",
			$slot_id
		) );

		if ( ! $reg ) {
			return false;
		}

		self::update_registration( $reg->id, array( 'status' => 'pending' ) );
		self::refresh_slot_count( $slot_id );
		SHL_Tortues_Email::send_waitlist_invitation( $reg->id, $slot_id );
		return true;
	}

	// ── Tracés GPS ────────────────────────────────────────────────────────

	public static function save_track( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'shl_tortues_tracks';
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM `{$table}` WHERE reg_id = %d", $data['reg_id']
		) );
		if ( $existing ) {
			unset( $data['created_at'] );
			$wpdb->update( $table, $data, array( 'id' => $existing ) );
			return $existing;
		}
		$data['created_at'] = current_time( 'mysql' );
		$wpdb->insert( $table, $data );
		return $wpdb->insert_id;
	}

	public static function get_track_by_reg( $reg_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM `{$wpdb->prefix}shl_tortues_tracks` WHERE reg_id = %d",
			$reg_id
		) );
	}

	public static function get_all_tracks_with_meta( $year = null ) {
		global $wpdb;
		$tt    = $wpdb->prefix . 'shl_tortues_tracks';
		$tr    = $wpdb->prefix . 'shl_tortues_registrations';
		$ts    = $wpdb->prefix . 'shl_tortues_slots';
		$where = $year ? $wpdb->prepare( 'WHERE YEAR(s.date) = %d', intval( $year ) ) : '';
		return $wpdb->get_results( // phpcs:ignore
			"SELECT t.id, t.reg_id, t.slot_id, t.geojson, t.distance_m, t.duration_s,
			        t.started_at, t.ended_at, t.created_at,
			        r.firstname, r.lastname, r.email,
			        s.date, s.zone_name, s.commune, s.type_prospect
			 FROM `{$tt}` t
			 JOIN `{$tr}` r ON r.id = t.reg_id
			 JOIN `{$ts}` s ON s.id = t.slot_id
			 {$where}
			 ORDER BY s.date DESC"
		);
	}

	public static function get_tracks_by_reg_ids( $reg_ids ) {
		if ( empty( $reg_ids ) ) {
			return array();
		}
		global $wpdb;
		$in   = implode( ',', array_map( 'intval', $reg_ids ) );
		$rows = $wpdb->get_results(
			"SELECT * FROM `{$wpdb->prefix}shl_tortues_tracks` WHERE reg_id IN ({$in})" // phpcs:ignore
		);
		$result = array();
		foreach ( $rows as $row ) {
			$result[ (int) $row->reg_id ] = $row;
		}
		return $result;
	}

	// ── Créneaux à venir pour la vérification météo ──────────────────────
	public static function get_upcoming_slots_for_weather( $from, $until ) {
		global $wpdb;
		$ts = self::slots_table();
		return $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
			"SELECT * FROM `{$ts}` WHERE date BETWEEN %s AND %s AND status IN ('open','full') AND latitude IS NOT NULL AND latitude != ''",
			$from, $until
		) );
	}

	// ── Stats bénévole pour les badges ────────────────────────────────────
	public static function get_volunteer_badge_stats( $email ) {
		global $wpdb;
		$tr    = self::reg_table();
		$ts    = self::slots_table();
		$to    = $wpdb->prefix . 'shl_tortues_observations';
		$tt    = $wpdb->prefix . 'shl_tortues_tracks';
		$today = gmdate( 'Y-m-d' );

		$done = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM `{$tr}` r JOIN `{$ts}` s ON s.id = r.slot_id
			 WHERE r.email = %s AND s.date < %s AND r.status NOT IN ('cancelled','refused')",
			$email, $today
		) ); // phpcs:ignore

		$obs = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM `{$to}` o JOIN `{$tr}` r ON r.id = o.reg_id WHERE r.email = %s",
			$email
		) ); // phpcs:ignore

		$confirmed = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM `{$to}` o JOIN `{$tr}` r ON r.id = o.reg_id
			 WHERE r.email = %s AND o.obs_type IN ('confirmed','trace')",
			$email
		) ); // phpcs:ignore

		$km = (float) ( $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(t.distance_m) / 1000 FROM `{$tt}` t JOIN `{$tr}` r ON r.id = t.reg_id WHERE r.email = %s",
			$email
		) ) ?? 0 ); // phpcs:ignore

		$seasons = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT YEAR(s.date)) FROM `{$tr}` r JOIN `{$ts}` s ON s.id = r.slot_id
			 WHERE r.email = %s AND r.status NOT IN ('cancelled','refused') AND s.date < %s",
			$email, $today
		) ); // phpcs:ignore

		return compact( 'done', 'obs', 'confirmed', 'km', 'seasons' );
	}

	// ── Données étendues pour le rapport de saison ────────────────────────
	public static function get_rapport_extended( $year ) {
		global $wpdb;
		$tr   = self::reg_table();
		$ts   = self::slots_table();
		$tt   = $wpdb->prefix . 'shl_tortues_tracks';
		$year = intval( $year );
		$from = "{$year}-01-01";
		$end  = "{$year}-12-31";

		$top_volunteers = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
			"SELECT r.firstname, r.lastname, r.email, r.is_member, COUNT(*) as nb_done
			 FROM `{$tr}` r JOIN `{$ts}` s ON s.id = r.slot_id
			 WHERE s.date BETWEEN %s AND %s AND r.status NOT IN ('cancelled','refused')
			 GROUP BY r.email ORDER BY nb_done DESC LIMIT 10",
			$from, $end
		) );

		$total_km = (float) ( $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
			"SELECT SUM(t.distance_m) / 1000 FROM `{$tt}` t
			 JOIN `{$tr}` r ON r.id = t.reg_id JOIN `{$ts}` s ON s.id = r.slot_id
			 WHERE s.date BETWEEN %s AND %s",
			$from, $end
		) ) ?? 0 );

		$nb_tracks = (int) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
			"SELECT COUNT(*) FROM `{$tt}` t
			 JOIN `{$tr}` r ON r.id = t.reg_id JOIN `{$ts}` s ON s.id = r.slot_id
			 WHERE s.date BETWEEN %s AND %s",
			$from, $end
		) );

		$done_regs = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
			"SELECT r.actual_time_start, r.actual_time_end FROM `{$tr}` r
			 JOIN `{$ts}` s ON s.id = r.slot_id
			 WHERE s.date BETWEEN %s AND %s AND r.status NOT IN ('cancelled','refused')",
			$from, $end
		) );

		$total_hours = 0.0;
		foreach ( $done_regs as $dr ) {
			if ( $dr->actual_time_start && $dr->actual_time_end ) {
				$t1 = strtotime( '2000-01-01 ' . $dr->actual_time_start );
				$t2 = strtotime( '2000-01-01 ' . $dr->actual_time_end );
				$total_hours += ( $t2 > $t1 ) ? ( $t2 - $t1 ) / 3600.0 : 2.0;
			} else {
				$total_hours += 2.0;
			}
		}

		return compact( 'top_volunteers', 'total_km', 'nb_tracks', 'total_hours' );
	}

	// ── Historique complet d'un bénévole ──────────────────────────────────
	public static function get_volunteer_history( $email ) {
		global $wpdb;
		$tr = self::reg_table();
		$ts = self::slots_table();
		$to = $wpdb->prefix . 'shl_tortues_observations';

		return $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
			"SELECT r.id, r.slot_id, r.token, r.firstname, r.lastname, r.email, r.phone,
			        r.actual_time_start, r.actual_time_end, r.status,
			        s.date, s.time_start, s.time_end, s.zone_name, s.commune,
			        s.type_prospect, s.status as slot_status,
			        (SELECT COUNT(*) FROM `{$to}` o WHERE o.reg_id = r.id) as obs_count
			 FROM `{$tr}` r
			 LEFT JOIN `{$ts}` s ON r.slot_id = s.id
			 WHERE r.email = %s AND r.status != 'refused'
			 ORDER BY s.date DESC",
			$email
		) );
	}
}
