<?php
/**
 * Parish Events — Custom Post Type
 *
 * Registers the `parish_event` CPT used to store upcoming and past events.
 * If The Events Calendar (tribe_events) is already active, this CPT is not
 * registered — the shortcodes will fall back to querying tribe_events instead.
 *
 * @package Parish_Liturgical_Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PLC_Events {

	const POST_TYPE = 'parish_event';
	const META_DATE = '_plc_event_date';       // YYYY-MM-DD
	const META_TIME = '_plc_event_time';       // HH:MM (24 h)
	const META_END  = '_plc_event_end_date';   // YYYY-MM-DD (optional)
	const META_LOC  = '_plc_event_location';   // free text

	/**
	 * Register the custom post type (skip if The Events Calendar is active).
	 */
	public static function register_post_type() {
		if ( self::tribe_active() ) {
			return;
		}

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'        => array(
					'name'               => __( 'Parish Events', 'parish-liturgical-calendar' ),
					'singular_name'      => __( 'Parish Event', 'parish-liturgical-calendar' ),
					'add_new'            => __( 'Add New Event', 'parish-liturgical-calendar' ),
					'add_new_item'       => __( 'Add New Parish Event', 'parish-liturgical-calendar' ),
					'edit_item'          => __( 'Edit Parish Event', 'parish-liturgical-calendar' ),
					'new_item'           => __( 'New Parish Event', 'parish-liturgical-calendar' ),
					'view_item'          => __( 'View Parish Event', 'parish-liturgical-calendar' ),
					'search_items'       => __( 'Search Parish Events', 'parish-liturgical-calendar' ),
					'not_found'          => __( 'No parish events found.', 'parish-liturgical-calendar' ),
					'not_found_in_trash' => __( 'No parish events found in Trash.', 'parish-liturgical-calendar' ),
				),
				'public'        => true,
				'show_in_rest'  => true,
				'menu_icon'     => 'dashicons-calendar-alt',
				'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'has_archive'   => true,
				'rewrite'       => array( 'slug' => 'events' ),
				'show_in_menu'  => true,
			)
		);

		// Register the custom fields
		register_meta(
			'post',
			self::META_DATE,
			array(
				'object_subtype' => self::POST_TYPE,
				'type'           => 'string',
				'single'         => true,
				'show_in_rest'   => true,
			)
		);
		register_meta(
			'post',
			self::META_TIME,
			array(
				'object_subtype' => self::POST_TYPE,
				'type'           => 'string',
				'single'         => true,
				'show_in_rest'   => true,
			)
		);
		register_meta(
			'post',
			self::META_END,
			array(
				'object_subtype' => self::POST_TYPE,
				'type'           => 'string',
				'single'         => true,
				'show_in_rest'   => true,
			)
		);
		register_meta(
			'post',
			self::META_LOC,
			array(
				'object_subtype' => self::POST_TYPE,
				'type'           => 'string',
				'single'         => true,
				'show_in_rest'   => true,
			)
		);

		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta' ) );
	}

	// ── Meta box ──────────────────────────────────────────────────────────

	public static function add_meta_box() {
		add_meta_box(
			'plc_event_details',
			__( 'Event Details', 'parish-liturgical-calendar' ),
			array( __CLASS__, 'render_meta_box' ),
			self::POST_TYPE,
			'side',
			'high'
		);
	}

	public static function render_meta_box( $post ) {
		wp_nonce_field( 'plc_event_meta', 'plc_event_nonce' );
		$date = get_post_meta( $post->ID, self::META_DATE, true );
		$time = get_post_meta( $post->ID, self::META_TIME, true );
		$end  = get_post_meta( $post->ID, self::META_END,  true );
		$loc  = get_post_meta( $post->ID, self::META_LOC,  true );
		?>
		<p>
			<label for="plc_event_date"><strong><?php esc_html_e( 'Date', 'parish-liturgical-calendar' ); ?></strong></label><br>
			<input type="date" id="plc_event_date" name="plc_event_date"
			       value="<?php echo esc_attr( $date ); ?>" style="width:100%">
		</p>
		<p>
			<label for="plc_event_time"><strong><?php esc_html_e( 'Time', 'parish-liturgical-calendar' ); ?></strong></label><br>
			<input type="time" id="plc_event_time" name="plc_event_time"
			       value="<?php echo esc_attr( $time ); ?>" style="width:100%">
		</p>
		<p>
			<label for="plc_event_end"><strong><?php esc_html_e( 'End Date (optional)', 'parish-liturgical-calendar' ); ?></strong></label><br>
			<input type="date" id="plc_event_end" name="plc_event_end"
			       value="<?php echo esc_attr( $end ); ?>" style="width:100%">
		</p>
		<p>
			<label for="plc_event_loc"><strong><?php esc_html_e( 'Location', 'parish-liturgical-calendar' ); ?></strong></label><br>
			<input type="text" id="plc_event_loc" name="plc_event_loc"
			       value="<?php echo esc_attr( $loc ); ?>" style="width:100%"
			       placeholder="<?php esc_attr_e( 'e.g. Parish Hall', 'parish-liturgical-calendar' ); ?>">
		</p>
		<?php
	}

	public static function save_meta( $post_id ) {
		if ( ! isset( $_POST['plc_event_nonce'] ) ||
		     ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['plc_event_nonce'] ) ), 'plc_event_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'plc_event_date' => self::META_DATE,
			'plc_event_time' => self::META_TIME,
			'plc_event_end'  => self::META_END,
			'plc_event_loc'  => self::META_LOC,
		);

		foreach ( $fields as $field => $meta_key ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
			}
		}
	}

	// ── Query helpers ─────────────────────────────────────────────────────

	/**
	 * Fetch upcoming events, ordered by date ascending.
	 *
	 * @param int $limit   Max number of events to return.
	 * @param int $past    Also include events up to $past days ago.
	 * @return WP_Post[]
	 */
	public static function get_upcoming( $limit = 5, $past = 0 ) {
		if ( self::tribe_active() ) {
			return self::get_tribe_events( $limit, $past );
		}

		$today = current_time( 'Y-m-d' );
		if ( $past > 0 ) {
			$from = gmdate( 'Y-m-d', strtotime( "-{$past} days", current_time( 'timestamp' ) ) );
		} else {
			$from = $today;
		}

		$args = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'meta_key'       => self::META_DATE,
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => self::META_DATE,
					'value'   => $from,
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
		);

		return get_posts( $args );
	}

	/**
	 * Fetch recent past events (for "What we've been up to").
	 *
	 * @param int $limit  Max number of events.
	 * @param int $days   How many days back to look.
	 * @return WP_Post[]
	 */
	public static function get_recent( $limit = 3, $days = 30 ) {
		if ( self::tribe_active() ) {
			return array(); // Tribe handles its own past events
		}

		$today = current_time( 'Y-m-d' );
		$from  = gmdate( 'Y-m-d', strtotime( "-{$days} days", current_time( 'timestamp' ) ) );

		$args = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'meta_key'       => self::META_DATE,
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
			'meta_query'     => array(
				array(
					'key'     => self::META_DATE,
					'value'   => array( $from, $today ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		);

		return get_posts( $args );
	}

	/**
	 * Format an event date/time for display.
	 *
	 * @param WP_Post $post
	 * @return string
	 */
	public static function format_date( $post ) {
		if ( self::tribe_active() && isset( $post->EventStartDate ) ) {
			return date_i18n( get_option( 'date_format' ), strtotime( $post->EventStartDate ) );
		}
		$date = get_post_meta( $post->ID, self::META_DATE, true );
		$time = get_post_meta( $post->ID, self::META_TIME, true );
		if ( ! $date ) {
			return '';
		}
		$ts     = strtotime( $date . ( $time ? " $time" : '' ) );
		$output = date_i18n( get_option( 'date_format', 'j F Y' ), $ts );
		if ( $time ) {
			$output .= ' &ndash; ' . date_i18n( get_option( 'time_format', 'H:i' ), $ts );
		}
		return $output;
	}

	/**
	 * Returns a DateTime for the event start (for countdown use).
	 *
	 * @param WP_Post $post
	 * @return DateTime|null
	 */
	public static function event_datetime( $post ) {
		$date = get_post_meta( $post->ID, self::META_DATE, true );
		$time = get_post_meta( $post->ID, self::META_TIME, true ) ?: '00:00';
		if ( ! $date ) {
			return null;
		}
		return new DateTime( "$date $time" );
	}

	// ── The Events Calendar compatibility ─────────────────────────────────

	public static function tribe_active() {
		return function_exists( 'tribe_get_events' );
	}

	private static function get_tribe_events( $limit, $past ) {
		if ( ! function_exists( 'tribe_get_events' ) ) {
			return array();
		}
		$args = array(
			'posts_per_page' => $limit,
			'start_date'     => ( $past > 0 ) ? date( 'Y-m-d', strtotime( "-{$past} days" ) ) : date( 'Y-m-d' ),
			'orderby'        => 'event_date',
			'order'          => 'ASC',
		);
		return tribe_get_events( $args );
	}
}
