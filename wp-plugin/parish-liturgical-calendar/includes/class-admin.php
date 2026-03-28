<?php
/**
 * Admin Settings Page
 *
 * Settings > Parish Calendar
 *
 * Options stored:
 *   plc_parish_name      — Parish name (used in headings)
 *   plc_bulletin_url     — URL of the current weekly bulletin PDF
 *   plc_bulletin_label   — Button/link label for the bulletin
 *   plc_show_mass_times  — Whether to show the quick mass-times note
 *   plc_mass_times_text  — Free-text mass times blurb (HTML allowed for admins)
 *
 * @package Parish_Liturgical_Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PLC_Admin {

	const PAGE_SLUG = 'plc-settings';
	const OPTION_GROUP = 'plc_options';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	public static function add_menu() {
		add_options_page(
			__( 'Parish Calendar Settings', 'parish-liturgical-calendar' ),
			__( 'Parish Calendar', 'parish-liturgical-calendar' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	public static function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			'plc_parish_name',
			array( 'sanitize_callback' => 'sanitize_text_field', 'default' => 'St John\'s Catholic Church' )
		);
		register_setting(
			self::OPTION_GROUP,
			'plc_bulletin_url',
			array( 'sanitize_callback' => 'esc_url_raw', 'default' => '' )
		);
		register_setting(
			self::OPTION_GROUP,
			'plc_bulletin_label',
			array( 'sanitize_callback' => 'sanitize_text_field', 'default' => 'Download this week\'s bulletin (PDF)' )
		);
		register_setting(
			self::OPTION_GROUP,
			'plc_show_mass_times',
			array( 'sanitize_callback' => 'absint', 'default' => 1 )
		);
		register_setting(
			self::OPTION_GROUP,
			'plc_mass_times_text',
			array(
				'sanitize_callback' => function ( $v ) {
					return wp_kses_post( $v );
				},
				'default' => '',
			)
		);

		// ── Section: General ────────────────────────────────────────────

		add_settings_section(
			'plc_general',
			__( 'General', 'parish-liturgical-calendar' ),
			'__return_false',
			self::PAGE_SLUG
		);

		add_settings_field(
			'plc_parish_name',
			__( 'Parish Name', 'parish-liturgical-calendar' ),
			array( __CLASS__, 'field_text' ),
			self::PAGE_SLUG,
			'plc_general',
			array( 'option' => 'plc_parish_name', 'description' => __( 'Used in headings on the frontend.', 'parish-liturgical-calendar' ) )
		);

		// ── Section: Bulletin ────────────────────────────────────────────

		add_settings_section(
			'plc_bulletin',
			__( 'Weekly Bulletin', 'parish-liturgical-calendar' ),
			function () {
				echo '<p>' . esc_html__( 'Upload the current bulletin PDF via Media > Add New, then paste the URL here.', 'parish-liturgical-calendar' ) . '</p>';
			},
			self::PAGE_SLUG
		);

		add_settings_field(
			'plc_bulletin_url',
			__( 'Bulletin PDF URL', 'parish-liturgical-calendar' ),
			array( __CLASS__, 'field_url' ),
			self::PAGE_SLUG,
			'plc_bulletin',
			array( 'option' => 'plc_bulletin_url' )
		);

		add_settings_field(
			'plc_bulletin_label',
			__( 'Link Label', 'parish-liturgical-calendar' ),
			array( __CLASS__, 'field_text' ),
			self::PAGE_SLUG,
			'plc_bulletin',
			array( 'option' => 'plc_bulletin_label' )
		);

		// ── Section: Mass Times ──────────────────────────────────────────

		add_settings_section(
			'plc_mass',
			__( 'Mass Times', 'parish-liturgical-calendar' ),
			function () {
				echo '<p>' . esc_html__( 'Optionally show a quick mass-times summary in the parish dashboard.', 'parish-liturgical-calendar' ) . '</p>';
			},
			self::PAGE_SLUG
		);

		add_settings_field(
			'plc_show_mass_times',
			__( 'Show Mass Times', 'parish-liturgical-calendar' ),
			array( __CLASS__, 'field_checkbox' ),
			self::PAGE_SLUG,
			'plc_mass',
			array( 'option' => 'plc_show_mass_times', 'label' => __( 'Display mass times in the parish dashboard', 'parish-liturgical-calendar' ) )
		);

		add_settings_field(
			'plc_mass_times_text',
			__( 'Mass Times Text', 'parish-liturgical-calendar' ),
			array( __CLASS__, 'field_textarea' ),
			self::PAGE_SLUG,
			'plc_mass',
			array(
				'option'      => 'plc_mass_times_text',
				'description' => __( 'HTML is allowed. Example: <b>Saturday</b> 17:30 | <b>Sunday</b> 08:00 & 10:30', 'parish-liturgical-calendar' ),
			)
		);
	}

	// ── Field renderers ────────────────────────────────────────────────────

	public static function field_text( $args ) {
		$option = get_option( $args['option'], '' );
		echo '<input type="text" name="' . esc_attr( $args['option'] ) . '" value="' . esc_attr( $option ) . '" class="regular-text">';
		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	public static function field_url( $args ) {
		$option = get_option( $args['option'], '' );
		echo '<input type="url" name="' . esc_attr( $args['option'] ) . '" value="' . esc_url( $option ) . '" class="regular-text">';
	}

	public static function field_checkbox( $args ) {
		$option = get_option( $args['option'], 0 );
		echo '<label><input type="checkbox" name="' . esc_attr( $args['option'] ) . '" value="1" ' . checked( 1, $option, false ) . '> ' . esc_html( $args['label'] ) . '</label>';
	}

	public static function field_textarea( $args ) {
		$option = get_option( $args['option'], '' );
		echo '<textarea name="' . esc_attr( $args['option'] ) . '" rows="4" class="large-text">' . esc_textarea( $option ) . '</textarea>';
		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	// ── Settings page ──────────────────────────────────────────────────────

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		// Show current season preview
		$season = PLC_Liturgical_Calendar::get_season();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Parish Calendar Settings', 'parish-liturgical-calendar' ); ?></h1>

			<div style="background:#fff;border-left:4px solid <?php echo esc_attr( $season['color_hex'] ); ?>;padding:1em 1.5em;margin:1em 0 2em;max-width:600px;box-shadow:0 1px 3px rgba(0,0,0,.1);">
				<strong><?php echo esc_html__( 'Today\'s liturgical moment:', 'parish-liturgical-calendar' ); ?></strong>
				<?php echo esc_html( $season['season_label'] ); ?>
				<?php if ( $season['week'] ) : ?>
					— <?php printf( esc_html__( 'Week %d', 'parish-liturgical-calendar' ), absint( $season['week'] ) ); ?>
				<?php endif; ?>
				<span style="color:<?php echo esc_attr( $season['color_hex'] ); ?>;">
					(<?php echo esc_html( $season['color_label'] ); ?>)
				</span>
				<?php if ( $season['special_day'] ) : ?>
					<br><em><?php echo esc_html( $season['special_day'] ); ?></em>
				<?php endif; ?>
				<br><small><?php printf(
					/* translators: 1: Sunday cycle, 2: weekday cycle */
					esc_html__( 'Lectionary: Sunday Cycle %1$s · Weekday Cycle %2$s', 'parish-liturgical-calendar' ),
					esc_html( $season['year_cycle'] ),
					esc_html( $season['weekday_cycle'] )
				); ?></small>
			</div>

			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>

			<hr>
			<h2><?php esc_html_e( 'Shortcodes', 'parish-liturgical-calendar' ); ?></h2>
			<table class="widefat striped" style="max-width:700px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Shortcode', 'parish-liturgical-calendar' ); ?></th>
						<th><?php esc_html_e( 'Description', 'parish-liturgical-calendar' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr><td><code>[parish_dashboard]</code></td><td><?php esc_html_e( 'Full season + events + bulletin block. Best for the home page.', 'parish-liturgical-calendar' ); ?></td></tr>
					<tr><td><code>[liturgical_season]</code></td><td><?php esc_html_e( 'Season banner only.', 'parish-liturgical-calendar' ); ?></td></tr>
					<tr><td><code>[parish_events limit="5"]</code></td><td><?php esc_html_e( 'Upcoming events list.', 'parish-liturgical-calendar' ); ?></td></tr>
					<tr><td><code>[parish_recent limit="3" days="30"]</code></td><td><?php esc_html_e( 'Recent past events.', 'parish-liturgical-calendar' ); ?></td></tr>
					<tr><td><code>[parish_bulletin]</code></td><td><?php esc_html_e( 'Bulletin PDF download link.', 'parish-liturgical-calendar' ); ?></td></tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function enqueue_admin_assets( $hook ) {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}
		// No extra assets needed currently
	}
}

PLC_Admin::init();
