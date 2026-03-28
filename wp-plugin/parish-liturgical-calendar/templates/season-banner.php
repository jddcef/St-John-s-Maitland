<?php
/**
 * Template: Season Banner
 *
 * Variables available (from PLC_Shortcodes::shortcode_season):
 *   $season  — array from PLC_Liturgical_Calendar::get_season()
 *   $atts    — shortcode attributes
 *
 * @package Parish_Liturgical_Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$color_hex   = esc_attr( $season['color_hex'] );
$color_class = 'plc-color-' . esc_attr( $season['color'] );
$season_slug = esc_attr( $season['season'] );
?>
<div class="plc-season-banner plc-season-<?php echo $season_slug; ?> <?php echo $color_class; ?>"
     style="--plc-color:<?php echo $color_hex; ?>;"
     role="region"
     aria-label="<?php esc_attr_e( 'Current liturgical season', 'parish-liturgical-calendar' ); ?>">

	<div class="plc-season-banner__stripe"></div>

	<div class="plc-season-banner__body">

		<div class="plc-season-banner__header">
			<span class="plc-season-banner__label"><?php echo esc_html( $season['season_label'] ); ?></span>

			<?php if ( ! empty( $atts['show_week'] ) && $season['week'] ) : ?>
				<span class="plc-season-banner__week">
					<?php
					printf(
						/* translators: %d: week number */
						esc_html__( 'Week %d', 'parish-liturgical-calendar' ),
						absint( $season['week'] )
					);
					?>
				</span>
			<?php endif; ?>

			<span class="plc-season-banner__colour">
				<?php
				printf(
					/* translators: %s: liturgical colour */
					esc_html__( 'Colour: %s', 'parish-liturgical-calendar' ),
					esc_html( $season['color_label'] )
				);
				?>
			</span>
		</div>

		<?php if ( $season['special_day'] ) : ?>
			<p class="plc-season-banner__special">
				&#10022; <?php echo esc_html( $season['special_day'] ); ?>
			</p>
		<?php endif; ?>

		<?php if ( ! empty( $atts['show_description'] ) && $season['description'] ) : ?>
			<p class="plc-season-banner__description"><?php echo esc_html( $season['description'] ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $atts['show_cycle'] ) ) : ?>
			<p class="plc-season-banner__cycle">
				<?php
				printf(
					/* translators: 1: Sunday cycle A/B/C, 2: Weekday cycle I/II */
					esc_html__( 'Lectionary — Sunday Cycle %1$s · Weekday Cycle %2$s', 'parish-liturgical-calendar' ),
					esc_html( $season['year_cycle'] ),
					esc_html( $season['weekday_cycle'] )
				);
				?>
			</p>
		<?php endif; ?>

		<?php if ( ! empty( $atts['show_next'] ) && ! empty( $season['next_season'] ) ) : ?>
			<p class="plc-season-banner__next">
				<?php
				$next = $season['next_season'];
				printf(
					/* translators: 1: next season name, 2: start date */
					esc_html__( 'Next season: %1$s — %2$s', 'parish-liturgical-calendar' ),
					'<strong>' . esc_html( $next['season_label'] ) . '</strong>',
					esc_html( $next['date_label'] )
				);
				?>
			</p>
		<?php endif; ?>

	</div><!-- .plc-season-banner__body -->
</div><!-- .plc-season-banner -->
