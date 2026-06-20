<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SHL_Tortues_Admin {

	public function init() {
		add_action( 'admin_menu',             array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts',  array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init',             array( $this, 'handle_early_actions' ) );
		add_action( 'wp_ajax_shl_admin_registration_status', array( $this, 'ajax_registration_status' ) );
		add_action( 'wp_ajax_shl_admin_copy_registrants',    array( $this, 'ajax_copy_registrants' ) );
		add_action( 'wp_ajax_shl_weather_cancel_slot',        array( $this, 'ajax_weather_cancel_slot' ) );
		add_action( 'admin_post_shl_broadcast',               array( 'SHL_Tortues_Admin_Broadcast', 'handle' ) );
	}

	public function register_menus() {
		$icon = 'data:image/svg+xml;base64,' . base64_encode(
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">'
			. '<ellipse cx="50" cy="54" rx="38" ry="28" fill="currentColor" opacity=".9"/>'
			. '<ellipse cx="50" cy="38" rx="16" ry="12" fill="currentColor"/>'
			. '<ellipse cx="18" cy="62" rx="12" ry="7" fill="currentColor" transform="rotate(-30 18 62)"/>'
			. '<ellipse cx="82" cy="62" rx="12" ry="7" fill="currentColor" transform="rotate(30 82 62)"/>'
			. '<ellipse cx="28" cy="80" rx="12" ry="7" fill="currentColor" transform="rotate(-15 28 80)"/>'
			. '<ellipse cx="72" cy="80" rx="12" ry="7" fill="currentColor" transform="rotate(15 72 80)"/>'
			. '</svg>'
		);

		add_menu_page(
			'Planning Tortues Marines',
			'Planning Tortues',
			'manage_options',
			'shl-tortues',
			array( 'SHL_Tortues_Admin_Dashboard', 'render' ),
			$icon,
			30
		);

		add_submenu_page( 'shl-tortues', 'Tableau de bord',    'Tableau de bord',    'manage_options', 'shl-tortues',                   array( 'SHL_Tortues_Admin_Dashboard',      'render' ) );
		add_submenu_page( 'shl-tortues', 'Créneaux',           'Créneaux',           'manage_options', 'shl-tortues-slots',             array( 'SHL_Tortues_Admin_Slots',          'render' ) );
		add_submenu_page( 'shl-tortues', 'Inscriptions',       'Inscriptions',       'manage_options', 'shl-tortues-registrations',     array( 'SHL_Tortues_Admin_Registrations',  'render' ) );
		add_submenu_page( 'shl-tortues', 'Observations terrain','Observations 📸',   'manage_options', 'shl-tortues-observations',      array( 'SHL_Tortues_Admin_Observations',   'render' ) );
		add_submenu_page( 'shl-tortues', 'Bénévoles',          'Bénévoles',          'manage_options', 'shl-tortues-volunteers',        array( 'SHL_Tortues_Admin_Volunteers',     'render' ) );
		add_submenu_page( 'shl-tortues', 'Carte des tracés',  '🗺️ Tracés GPS',     'manage_options', 'shl-tortues-tracks',            array( 'SHL_Tortues_Admin_Tracks',         'render' ) );
		add_submenu_page( 'shl-tortues', 'Rapport de saison', '📊 Rapport',          'manage_options', 'shl-tortues-rapport',           array( 'SHL_Tortues_Admin_Rapport',        'render' ) );
		add_submenu_page( 'shl-tortues', 'Zones',              'Zones',              'manage_options', 'shl-tortues-zones',             array( 'SHL_Tortues_Admin_Zones',          'render' ) );
		add_submenu_page( 'shl-tortues', 'Réglages',           'Réglages',           'manage_options', 'shl-tortues-settings',          array( 'SHL_Tortues_Admin_Settings',       'render' ) );
	}

	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'shl-tortues' ) === false ) {
			return;
		}
		wp_enqueue_style(  'shl-admin-css', SHL_TORTUES_PLUGIN_URL . 'assets/css/admin.css',  array(), SHL_TORTUES_VERSION );
		wp_enqueue_script( 'shl-admin-js',  SHL_TORTUES_PLUGIN_URL . 'assets/js/admin.js',   array( 'jquery' ), SHL_TORTUES_VERSION, true );
		wp_localize_script( 'shl-admin-js', 'shlAdmin', array(
			'ajax' => admin_url( 'admin-ajax.php' ),
			'nonce'=> wp_create_nonce( 'shl_admin_nonce' ),
		) );
		// Styles galerie pour la page Observations
		if ( strpos( $hook, 'shl-tortues-observations' ) !== false ) {
			wp_enqueue_style( 'shl-terrain-css', SHL_TORTUES_PLUGIN_URL . 'assets/css/terrain.css', array(), SHL_TORTUES_VERSION );
		}
		// Chart.js uniquement sur le tableau de bord principal
		if ( 'toplevel_page_shl-tortues' === $hook ) {
			wp_enqueue_script( 'chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', array(), '4.4.0', true );
		}
	}

	// Gérer l'export CSV (doit arriver avant les headers WordPress)
	public function handle_early_actions() {
		if ( ! isset( $_GET['page'] ) || strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 'shl-tortues' ) === false ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( isset( $_GET['action'] ) && 'export_csv' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'shl_export_csv' ) ) {
				wp_die( 'Nonce invalide.' );
			}
			$args = array();
			if ( ! empty( $_GET['date_from'] ) ) { $args['date_from'] = sanitize_text_field( wp_unslash( $_GET['date_from'] ) ); }
			if ( ! empty( $_GET['date_to']   ) ) { $args['date_to']   = sanitize_text_field( wp_unslash( $_GET['date_to'] ) ); }
			if ( ! empty( $_GET['type']      ) ) { $args['type']      = sanitize_text_field( wp_unslash( $_GET['type'] ) ); }
			if ( ! empty( $_GET['slot_id']   ) ) { $args['slot_id']   = intval( $_GET['slot_id'] ); }
			SHL_Tortues_Export::export_csv( $args );
		}
	}

	// ── AJAX : changer le statut d'une inscription ─────────────────────────
	public function ajax_registration_status() {
		check_ajax_referer( 'shl_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission refusée.' );
		}

		$id     = intval( $_POST['id'] ?? 0 );
		$status = sanitize_text_field( $_POST['status'] ?? '' );

		if ( ! in_array( $status, array( 'pending', 'validated', 'refused' ), true ) ) {
			wp_send_json_error( 'Statut invalide.' );
		}

		$reg = SHL_Tortues_DB::get_registration( $id );
		if ( ! $reg ) {
			wp_send_json_error( 'Inscription introuvable.' );
		}

		SHL_Tortues_DB::update_registration( $id, array( 'status' => $status ) );
		SHL_Tortues_DB::refresh_slot_count( $reg->slot_id );
		wp_send_json_success( array( 'status' => $status ) );
	}

	// ── AJAX : copier la liste des inscrits ────────────────────────────────
	public function ajax_copy_registrants() {
		check_ajax_referer( 'shl_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission refusée.' );
		}

		$slot_id = intval( $_POST['slot_id'] ?? 0 );
		$regs    = SHL_Tortues_DB::get_slot_registrations( $slot_id );

		$lines = array();
		foreach ( $regs as $r ) {
			$line  = esc_html( $r->firstname . ' ' . $r->lastname );
			$line .= ' – ' . esc_html( $r->email );
			if ( $r->phone ) { $line .= ' – ' . esc_html( $r->phone ); }
			$lines[] = $line;
		}

		wp_send_json_success( array( 'text' => implode( "\n", $lines ) ) );
	}

	// ── AJAX : annulation météo d'un créneau ───────────────────────────────
	public function ajax_weather_cancel_slot() {
		check_ajax_referer( 'shl_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission refusée.' );
		}

		$slot_id = intval( $_POST['slot_id'] ?? 0 );
		$slot    = SHL_Tortues_DB::get_slot( $slot_id );

		if ( ! $slot ) {
			wp_send_json_error( 'Créneau introuvable.' );
		}
		if ( 'cancelled' === $slot->status ) {
			wp_send_json_error( 'Créneau déjà annulé.' );
		}

		SHL_Tortues_DB::update_slot( $slot_id, array( 'status' => 'cancelled' ) );

		$regs   = SHL_Tortues_DB::get_slot_registrations( $slot_id );
		$sent   = 0;
		$reason = sanitize_text_field( $_POST['reason'] ?? 'Conditions météorologiques dangereuses prévues' );

		foreach ( $regs as $reg ) {
			if ( in_array( $reg->status, array( 'validated', 'pending', 'waitlist' ), true ) ) {
				SHL_Tortues_DB::update_registration( $reg->id, array( 'status' => 'cancelled' ) );
				SHL_Tortues_Email::send_weather_cancellation( $reg, $slot, $reason );
				$sent++;
			}
		}

		wp_send_json_success( array( 'sent' => $sent ) );
	}
}
