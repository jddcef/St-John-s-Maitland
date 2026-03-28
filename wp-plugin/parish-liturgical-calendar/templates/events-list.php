<?php
/**
 * Template: Events List
 *
 * Variables available:
 *   $events  — WP_Post[] (may be empty)
 *   $atts    — shortcode attributes (includes 'heading')
 *
 * @package Parish_Liturgical_Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $events ) ) : ?>
	<div class="plc-events plc-events--empty">
		<p><?php esc_html_e( 'No events found.', 'parish-liturgical-calendar' ); ?></p>
	</div>
<?php return; endif; ?>

<div class="plc-events">
	<?php if ( ! empty( $atts['heading'] ) ) : ?>
		<h3 class="plc-events__heading"><?php echo esc_html( $atts['heading'] ); ?></h3>
	<?php endif; ?>

	<ul class="plc-events__list">
		<?php foreach ( $events as $event ) : ?>
			<?php
			$date_str = PLC_Events::format_date( $event );
			$location = get_post_meta( $event->ID, PLC_Events::META_LOC, true );
			$link     = get_permalink( $event->ID );
			$excerpt  = has_excerpt( $event->ID ) ? get_the_excerpt( $event->ID ) : '';
			?>
			<li class="plc-events__item">
				<a class="plc-events__title" href="<?php echo esc_url( $link ); ?>">
					<?php echo esc_html( get_the_title( $event->ID ) ); ?>
				</a>
				<div class="plc-events__meta">
					<?php if ( $date_str ) : ?>
						<span class="plc-events__date">
							<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
							<?php echo esc_html( $date_str ); ?>
						</span>
					<?php endif; ?>
					<?php if ( $location ) : ?>
						<span class="plc-events__location">
							<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
							<?php echo esc_html( $location ); ?>
						</span>
					<?php endif; ?>
				</div>
				<?php if ( $excerpt ) : ?>
					<p class="plc-events__excerpt"><?php echo esc_html( $excerpt ); ?></p>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</div><!-- .plc-events -->
