<?php
/**
 * Plugin Name: Parish Liturgical Calendar
 * Plugin URI:  https://github.com/jddcef/St-John-s-Maitland
 * Description: Displays the current liturgical season, upcoming and recent parish events, and a seasonal banner — giving visitors an immediate sense of where the parish is in the Church year.
 * Version:     1.0.0
 * Author:      St John's Maitland
 * Author URI:  https://stjohnsmaitland.co.za
 * License:     GPL-2.0-or-later
 * Text Domain: parish-liturgical-calendar
 *
 * Installation
 * ────────────
 * 1. Upload the `parish-liturgical-calendar` folder to /wp-content/plugins/.
 * 2. Activate through Plugins > Installed Plugins.
 * 3. Configure via Settings > Parish Calendar.
 * 4. Add [parish_dashboard] (or individual shortcodes) to any page or widget.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PLC_VERSION', '1.0.0' );
define( 'PLC_PLUGIN_FILE', __FILE__ );
define( 'PLC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PLC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// ─── Autoload classes ────────────────────────────────────────────────────────

require_once PLC_PLUGIN_DIR . 'includes/class-liturgical-calendar.php';
require_once PLC_PLUGIN_DIR . 'includes/class-events.php';
require_once PLC_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once PLC_PLUGIN_DIR . 'includes/class-widget.php';
require_once PLC_PLUGIN_DIR . 'includes/class-admin.php';

// ─── Bootstrap ───────────────────────────────────────────────────────────────

add_action( 'init', array( 'PLC_Events', 'register_post_type' ) );
add_action( 'widgets_init', function () {
	register_widget( 'PLC_Widget' );
} );
add_action( 'wp_enqueue_scripts', 'plc_enqueue_assets' );

function plc_enqueue_assets() {
	wp_enqueue_style(
		'parish-liturgical-calendar',
		PLC_PLUGIN_URL . 'assets/style.css',
		array(),
		PLC_VERSION
	);
	wp_enqueue_script(
		'parish-liturgical-calendar',
		PLC_PLUGIN_URL . 'assets/script.js',
		array(),
		PLC_VERSION,
		true
	);
}

// ─── Activation / deactivation hooks ─────────────────────────────────────────

register_activation_hook( __FILE__, 'plc_activate' );
function plc_activate() {
	PLC_Events::register_post_type();
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'plc_deactivate' );
function plc_deactivate() {
	flush_rewrite_rules();
}
