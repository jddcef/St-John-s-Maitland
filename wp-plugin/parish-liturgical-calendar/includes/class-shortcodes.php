<?php
/**
 * Shortcodes
 *
 * [liturgical_season]  — Season banner only.
 * [parish_events]      — Upcoming events list.
 * [parish_recent]      — Recent events list.
 * [parish_bulletin]    — Link to the current weekly bulletin PDF.
 * [parish_dashboard]   — Full combined widget (season + events + bulletin).
 *
 * @package Parish_Liturgical_Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PLC_Shortcodes {

	public static function register() {
		$codes = array(
			'liturgical_season' => array( __CLASS__, 'shortcode_season' ),
			'parish_events'     => array( __CLASS__, 'shortcode_events' ),
			'parish_recent'     => array( __CLASS__, 'shortcode_recent' ),
			'parish_bulletin'   => array( __CLASS__, 'shortcode_bulletin' ),
			'parish_dashboard'  => array( __CLASS__, 'shortcode_dashboard' ),
		);
		foreach ( $codes as $tag => $cb ) {
			add_shortcode( $tag, $cb );
		}
	}

	// ── [liturgical_season] ───────────────────────────────────────────────

	/**
	 * @param array $atts
	 *   - show_week       bool  Show "Week N of …" label.       Default true.
	 *   - show_cycle      bool  Show Sunday/weekday cycle.       Default false.
	 *   - show_next       bool  Show next-season countdown.      Default true.
	 *   - show_description bool Show season description text.    Default true.
	 */
	public static function shortcode_season( $atts ) {
		$atts = shortcode_atts(
			array(
				'show_week'        => true,
				'show_cycle'       => false,
				'show_next'        => true,
				'show_description' => true,
			),
			$atts,
			'liturgical_season'
		);

		$season = PLC_Liturgical_Calendar::get_season();
		ob_start();
		include PLC_PLUGIN_DIR . 'templates/season-banner.php';
		return ob_get_clean();
	}

	// ── [parish_events] ───────────────────────────────────────────────────

	/**
	 * @param array $atts
	 *   - limit   int   Max events to show.   Default 5.
	 *   - past    int   Days back to include. Default 0.
	 *   - heading string  Section heading.     Default 'Upcoming Events'.
	 */
	public static function shortcode_events( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'   => 5,
				'past'    => 0,
				'heading' => __( 'Upcoming Events', 'parish-liturgical-calendar' ),
			),
			$atts,
			'parish_events'
		);

		$events = PLC_Events::get_upcoming( (int) $atts['limit'], (int) $atts['past'] );
		ob_start();
		include PLC_PLUGIN_DIR . 'templates/events-list.php';
		return ob_get_clean();
	}

	// ── [parish_recent] ───────────────────────────────────────────────────

	/**
	 * @param array $atts
	 *   - limit  int  Max events.         Default 3.
	 *   - days   int  Days back to look.  Default 30.
	 *   - heading string                  Default 'Recent Parish Life'.
	 */
	public static function shortcode_recent( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'   => 3,
				'days'    => 30,
				'heading' => __( 'Recent Parish Life', 'parish-liturgical-calendar' ),
			),
			$atts,
			'parish_recent'
		);

		$events = PLC_Events::get_recent( (int) $atts['limit'], (int) $atts['days'] );
		ob_start();
		include PLC_PLUGIN_DIR . 'templates/events-list.php';
		return ob_get_clean();
	}

	// ── [parish_bulletin] ─────────────────────────────────────────────────

	/**
	 * @param array $atts
	 *   - label  string  Link text.  Default 'Download this week's bulletin'.
	 */
	public static function shortcode_bulletin( $atts ) {
		$atts = shortcode_atts(
			array(
				'label' => __( 'Download this week\'s bulletin (PDF)', 'parish-liturgical-calendar' ),
			),
			$atts,
			'parish_bulletin'
		);

		$url = get_option( 'plc_bulletin_url', '' );
		if ( ! $url ) {
			return '';
		}

		$label = esc_html( $atts['label'] );
		$url   = esc_url( $url );
		return "<p class=\"plc-bulletin-link\"><a href=\"{$url}\" target=\"_blank\" rel=\"noopener noreferrer\">📄 {$label}</a></p>";
	}

	// ── [parish_dashboard] ────────────────────────────────────────────────

	/**
	 * Full combined block: season banner + upcoming events + bulletin link.
	 *
	 * @param array $atts
	 *   - events_limit int  Default 5.
	 *   - recent_limit int  Default 3.
	 */
	public static function shortcode_dashboard( $atts ) {
		$atts = shortcode_atts(
			array(
				'events_limit' => 5,
				'recent_limit' => 3,
			),
			$atts,
			'parish_dashboard'
		);

		$season  = PLC_Liturgical_Calendar::get_season();
		$events  = PLC_Events::get_upcoming( (int) $atts['events_limit'] );
		$recent  = PLC_Events::get_recent( (int) $atts['recent_limit'] );
		$bulletin_url = esc_url( get_option( 'plc_bulletin_url', '' ) );
		$bulletin_label = esc_html( get_option( 'plc_bulletin_label', __( 'Download this week\'s bulletin (PDF)', 'parish-liturgical-calendar' ) ) );

		ob_start();
		include PLC_PLUGIN_DIR . 'templates/dashboard.php';
		return ob_get_clean();
	}
}

// Register on init (after text domain is loaded)
add_action( 'init', array( 'PLC_Shortcodes', 'register' ) );
