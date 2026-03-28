<?php
/**
 * Template: Parish Dashboard
 *
 * Full combined block: season banner + upcoming events + recent events + bulletin.
 *
 * Variables available:
 *   $season        — array from PLC_Liturgical_Calendar::get_season()
 *   $events        — WP_Post[]  upcoming events
 *   $recent        — WP_Post[]  recent events
 *   $bulletin_url  — string     URL of the bulletin PDF (may be empty)
 *   $bulletin_label— string     Link label
 *
 * @package Parish_Liturgical_Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$color_hex   = esc_attr( $season['color_hex'] );
$season_slug = esc_attr( $season['season'] );
$parish_name = esc_html( get_option( 'plc_parish_name', 'St John\'s Catholic Church' ) );
$show_mass   = (bool) get_option( 'plc_show_mass_times', 1 );
$mass_text   = get_option( 'plc_mass_times_text', '' );
?>
<div class="plc-dashboard plc-season-<?php echo $season_slug; ?>"
     style="--plc-color:<?php echo $color_hex; ?>;">

	<!-- ── Season Banner ─────────────────────────────────── -->
	<div class="plc-dashboard__season-banner" style="border-top:5px solid <?php echo $color_hex; ?>;">
		<div class="plc-dashboard__season-inner">

			<div class="plc-dashboard__season-swatch" style="background:<?php echo $color_hex; ?>;"></div>

			<div class="plc-dashboard__season-text">
				<h2 class="plc-dashboard__season-name">
					<?php echo esc_html( $season['season_label'] ); ?>
					<?php if ( $season['week'] ) : ?>
						<span class="plc-dashboard__week"><?php printf( esc_html__( '· Week %d', 'parish-liturgical-calendar' ), absint( $season['week'] ) ); ?></span>
					<?php endif; ?>
				</h2>

				<?php if ( $season['special_day'] ) : ?>
					<p class="plc-dashboard__special">&#10022; <?php echo esc_html( $season['special_day'] ); ?></p>
				<?php endif; ?>

				<p class="plc-dashboard__season-desc"><?php echo esc_html( $season['description'] ); ?></p>

				<?php if ( ! empty( $season['next_season'] ) ) : ?>
					<p class="plc-dashboard__next-season">
						<?php
						printf(
							/* translators: 1: season name, 2: date */
							esc_html__( 'Next: %1$s on %2$s', 'parish-liturgical-calendar' ),
							'<strong>' . esc_html( $season['next_season']['season_label'] ) . '</strong>',
							esc_html( $season['next_season']['date_label'] )
						);
						?>
					</p>
				<?php endif; ?>

				<p class="plc-dashboard__cycle">
					<?php
					printf(
						/* translators: 1: Sunday cycle, 2: weekday cycle */
						esc_html__( 'Lectionary — Cycle %1$s (Sundays) · Cycle %2$s (weekdays)', 'parish-liturgical-calendar' ),
						esc_html( $season['year_cycle'] ),
						esc_html( $season['weekday_cycle'] )
					);
					?>
				</p>
			</div><!-- .plc-dashboard__season-text -->
		</div><!-- .plc-dashboard__season-inner -->
	</div><!-- .plc-dashboard__season-banner -->

	<!-- ── Main content grid ─────────────────────────────── -->
	<div class="plc-dashboard__grid">

		<!-- Upcoming events -->
		<section class="plc-dashboard__col plc-dashboard__col--events" aria-label="<?php esc_attr_e( 'Upcoming events', 'parish-liturgical-calendar' ); ?>">
			<h3 class="plc-dashboard__col-heading" style="color:<?php echo $color_hex; ?>;">
				<?php esc_html_e( 'Coming Up', 'parish-liturgical-calendar' ); ?>
			</h3>
			<?php
			$atts   = array( 'heading' => false );
			include __DIR__ . '/events-list.php';
			// Reset $events to recent for the next block
			?>
		</section>

		<!-- Right column: recent + mass times + bulletin -->
		<aside class="plc-dashboard__col plc-dashboard__col--aside">

			<?php if ( ! empty( $recent ) ) : ?>
				<section aria-label="<?php esc_attr_e( 'Recent parish life', 'parish-liturgical-calendar' ); ?>">
					<h3 class="plc-dashboard__col-heading" style="color:<?php echo $color_hex; ?>;">
						<?php esc_html_e( 'Recent Parish Life', 'parish-liturgical-calendar' ); ?>
					</h3>
					<?php
					$events = $recent;
					$atts   = array( 'heading' => false );
					include __DIR__ . '/events-list.php';
					?>
				</section>
			<?php endif; ?>

			<?php if ( $show_mass && $mass_text ) : ?>
				<section class="plc-dashboard__mass-times" aria-label="<?php esc_attr_e( 'Mass times', 'parish-liturgical-calendar' ); ?>">
					<h3 class="plc-dashboard__col-heading" style="color:<?php echo $color_hex; ?>;">
						<?php esc_html_e( 'Mass Times', 'parish-liturgical-calendar' ); ?>
					</h3>
					<div class="plc-dashboard__mass-text">
						<?php echo wp_kses_post( $mass_text ); ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( $bulletin_url ) : ?>
				<section class="plc-dashboard__bulletin">
					<a class="plc-bulletin-btn" href="<?php echo esc_url( $bulletin_url ); ?>"
					   target="_blank" rel="noopener noreferrer"
					   style="background:<?php echo $color_hex; ?>;">
						<svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
						<?php echo esc_html( $bulletin_label ); ?>
					</a>
				</section>
			<?php endif; ?>

		</aside><!-- .plc-dashboard__col--aside -->

	</div><!-- .plc-dashboard__grid -->
</div><!-- .plc-dashboard -->
