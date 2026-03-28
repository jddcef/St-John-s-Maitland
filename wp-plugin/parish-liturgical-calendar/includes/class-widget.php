<?php
/**
 * Sidebar Widget — Liturgical Season
 *
 * Displays the current liturgical season in a sidebar widget area.
 *
 * @package Parish_Liturgical_Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PLC_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'plc_liturgical_season_widget',
			__( 'Liturgical Season', 'parish-liturgical-calendar' ),
			array(
				'classname'   => 'plc-widget',
				'description' => __( 'Shows the current Catholic liturgical season with seasonal colour.', 'parish-liturgical-calendar' ),
			)
		);
	}

	public function widget( $args, $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'In the Church\'s Year', 'parish-liturgical-calendar' );
		$show_desc = ! empty( $instance['show_description'] );

		echo wp_kses_post( $args['before_widget'] );
		echo wp_kses_post( $args['before_title'] ) . esc_html( $title ) . wp_kses_post( $args['after_title'] );

		$season = PLC_Liturgical_Calendar::get_season();

		$label       = esc_html( $season['season_label'] );
		$color_hex   = esc_attr( $season['color_hex'] );
		$color_label = esc_html( $season['color_label'] );
		$week        = $season['week'] ? absint( $season['week'] ) : null;
		$special     = esc_html( $season['special_day'] );
		$cycle       = esc_html( $season['year_cycle'] );
		$description = esc_html( $season['description'] );

		echo '<div class="plc-widget-inner" style="border-left:4px solid ' . $color_hex . '; padding-left:0.75em;">';
		echo '<p class="plc-season-name" style="font-weight:bold; color:' . $color_hex . '; margin:0 0 0.25em;">' . $label . '</p>';

		if ( $week ) {
			/* translators: %d: week number */
			echo '<p class="plc-week" style="margin:0 0 0.25em; font-size:0.85em;">'
			     . sprintf( esc_html__( 'Week %d', 'parish-liturgical-calendar' ), $week )
			     . ' &bull; ' . sprintf(
				     /* translators: %s: liturgical colour */
				     esc_html__( 'Colour: %s', 'parish-liturgical-calendar' ),
				     $color_label
			     ) . '</p>';
		}

		if ( $special ) {
			echo '<p class="plc-special" style="margin:0 0 0.25em; font-style:italic; font-size:0.9em;">✦ ' . $special . '</p>';
		}

		if ( $show_desc && $description ) {
			echo '<p class="plc-desc" style="margin:0.5em 0 0; font-size:0.85em; line-height:1.5;">' . $description . '</p>';
		}

		// Next season
		if ( ! empty( $season['next_season'] ) ) {
			$next = $season['next_season'];
			echo '<p class="plc-next" style="margin:0.75em 0 0; font-size:0.8em; color:#666;">'
			     . sprintf(
				     /* translators: 1: next season name, 2: date */
				     esc_html__( 'Next: %1$s — %2$s', 'parish-liturgical-calendar' ),
				     esc_html( $next['season_label'] ),
				     esc_html( $next['date_label'] )
			     ) . '</p>';
		}

		echo '</div>';
		echo wp_kses_post( $args['after_widget'] );
	}

	public function form( $instance ) {
		$title        = ! empty( $instance['title'] ) ? $instance['title'] : __( 'In the Church\'s Year', 'parish-liturgical-calendar' );
		$show_desc    = ! empty( $instance['show_description'] );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title:', 'parish-liturgical-calendar' ); ?>
			</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
			       type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<input type="checkbox"
			       id="<?php echo esc_attr( $this->get_field_id( 'show_description' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'show_description' ) ); ?>"
			       value="1" <?php checked( $show_desc ); ?>>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_description' ) ); ?>">
				<?php esc_html_e( 'Show season description', 'parish-liturgical-calendar' ); ?>
			</label>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance                     = array();
		$instance['title']            = sanitize_text_field( $new_instance['title'] );
		$instance['show_description'] = ! empty( $new_instance['show_description'] ) ? 1 : 0;
		return $instance;
	}
}
