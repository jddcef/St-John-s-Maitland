/**
 * Parish Liturgical Calendar — Frontend Script
 * Version: 1.0.0
 *
 * Lightweight enhancements — no jQuery dependency.
 *
 * Features:
 *  - Adds a live countdown timer to the next upcoming event (if the event has
 *    a data-event-datetime ISO attribute on its list item).
 */

( function () {
	'use strict';

	/**
	 * Format a duration in seconds as a human-readable string.
	 * @param {number} seconds
	 * @returns {string}
	 */
	function formatCountdown( seconds ) {
		if ( seconds <= 0 ) {
			return '';
		}
		var days    = Math.floor( seconds / 86400 );
		var hours   = Math.floor( ( seconds % 86400 ) / 3600 );
		var minutes = Math.floor( ( seconds % 3600 ) / 60 );

		if ( days > 1 ) {
			return days + ' days';
		}
		if ( days === 1 ) {
			return '1 day ' + hours + 'h';
		}
		if ( hours > 0 ) {
			return hours + 'h ' + minutes + 'm';
		}
		return minutes + ' min';
	}

	/**
	 * Attach countdown timers to the first upcoming event item that carries
	 * a [data-event-datetime] attribute (ISO 8601 string).
	 */
	function initCountdowns() {
		var items = document.querySelectorAll( '.plc-events__item[data-event-datetime]' );
		if ( ! items.length ) {
			return;
		}

		items.forEach( function ( item ) {
			var iso = item.getAttribute( 'data-event-datetime' );
			if ( ! iso ) {
				return;
			}
			var targetTime = new Date( iso ).getTime();
			if ( isNaN( targetTime ) ) {
				return;
			}

			var span = document.createElement( 'span' );
			span.className = 'plc-countdown';
			span.setAttribute( 'aria-live', 'off' );

			var metaDiv = item.querySelector( '.plc-events__meta' );
			if ( metaDiv ) {
				metaDiv.appendChild( span );
			}

			function tick() {
				var remaining = Math.floor( ( targetTime - Date.now() ) / 1000 );
				if ( remaining > 0 ) {
					span.textContent = '⏱ ' + formatCountdown( remaining );
					setTimeout( tick, 30000 ); // update every 30 s
				} else {
					span.textContent = '';
				}
			}

			tick();
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initCountdowns );
	} else {
		initCountdowns();
	}
}() );
