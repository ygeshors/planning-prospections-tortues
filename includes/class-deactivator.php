<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SHL_Tortues_Deactivator {
	public static function deactivate() {
		SHL_Tortues_Cron::unregister();
		flush_rewrite_rules();
	}
}
