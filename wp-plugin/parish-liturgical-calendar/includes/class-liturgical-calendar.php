<?php
/**
 * Liturgical Calendar Engine
 *
 * Calculates the current Roman Rite liturgical season, week, and colour
 * for any given date, using the General Roman Calendar.
 *
 * @package Parish_Liturgical_Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PLC_Liturgical_Calendar {

	// ── Liturgical season slugs ────────────────────────────────────────────

	const SEASON_ADVENT          = 'advent';
	const SEASON_CHRISTMAS       = 'christmas';
	const SEASON_ORDINARY_EARLY  = 'ordinary_early';  // Epiphany → Ash Wed
	const SEASON_LENT            = 'lent';
	const SEASON_TRIDUUM         = 'triduum';
	const SEASON_EASTER          = 'easter';
	const SEASON_ORDINARY_LATER  = 'ordinary_later';  // Pentecost → Advent

	// ── Liturgical colours ─────────────────────────────────────────────────

	const COLOR_PURPLE = 'purple';
	const COLOR_ROSE   = 'rose';
	const COLOR_WHITE  = 'white';
	const COLOR_GREEN  = 'green';
	const COLOR_RED    = 'red';

	/**
	 * Returns the Easter Sunday date for the given year (Gregorian calendar).
	 * Uses the Butcher / Meeus–Jones–Butcher algorithm.
	 *
	 * @param int $year Four-digit year.
	 * @return DateTime Easter Sunday (midnight, site timezone).
	 */
	public static function easter( $year ) {
		$a = $year % 19;
		$b = intdiv( $year, 100 );
		$c = $year % 100;
		$d = intdiv( $b, 4 );
		$e = $b % 4;
		$f = intdiv( $b + 8, 25 );
		$g = intdiv( $b - $f + 1, 3 );
		$h = ( 19 * $a + $b - $d - $g + 15 ) % 30;
		$i = intdiv( $c, 4 );
		$k = $c % 4;
		$l = ( 32 + 2 * $e + 2 * $i - $h - $k ) % 7;
		$m = intdiv( $a + 11 * $h + 22 * $l, 451 );
		$month = intdiv( $h + $l - 7 * $m + 114, 31 );
		$day   = ( ( $h + $l - 7 * $m + 114 ) % 31 ) + 1;
		return new DateTime( sprintf( '%04d-%02d-%02d', $year, $month, $day ) );
	}

	/**
	 * Returns the date of the first Sunday of Advent for the given year.
	 * Advent begins on the Sunday that falls on or closest to 30 November.
	 *
	 * @param int $year
	 * @return DateTime
	 */
	public static function advent_start( $year ) {
		// Find the Sunday on or before 3 December (= 4 Sundays before 25 Dec)
		$christmas = new DateTime( "$year-12-25" );
		$dow       = (int) $christmas->format( 'N' ); // 1=Mon … 7=Sun
		$days_back  = ( $dow === 7 ) ? 28 : $dow + 21;
		$advent     = clone $christmas;
		$advent->modify( "-{$days_back} days" );
		return $advent;
	}

	/**
	 * Returns a structured description of the liturgical moment for $date.
	 *
	 * @param DateTime|null $date Defaults to today (site timezone).
	 * @return array {
	 *   'season'       => string   Season slug.
	 *   'season_label' => string   Human-readable season name.
	 *   'week'         => int|null Week number within the season (null if N/A).
	 *   'color'        => string   Liturgical colour slug.
	 *   'color_hex'    => string   Hex colour for UI.
	 *   'color_label'  => string   Human-readable colour.
	 *   'description'  => string   A short paragraph explaining the season.
	 *   'special_day'  => string   Name of a special day/solemnity, or ''.
	 *   'year_cycle'   => string   Sunday cycle A/B/C.
	 *   'weekday_cycle'=> string   Weekday cycle I/II.
	 *   'next_season'  => array    Same structure for the next season start.
	 * }
	 */
	public static function get_season( DateTime $date = null ) {
		if ( ! $date ) {
			$tz   = new DateTimeZone( get_option( 'timezone_string', 'Africa/Johannesburg' ) ?: 'Africa/Johannesburg' );
			$date = new DateTime( 'now', $tz );
		}
		$date->setTime( 0, 0, 0 ); // normalise to midnight

		$year = (int) $date->format( 'Y' );

		// We may need keys from adjacent years
		$keys_this  = self::key_dates( $year );
		$keys_next  = self::key_dates( $year + 1 );
		$keys_prev  = self::key_dates( $year - 1 );

		$ts = $date->getTimestamp();

		// ── 1. Advent of this year or next? ───────────────────────────────
		// Advent for year Y begins in the calendar year Y, running into Y+1
		$advent_this = $keys_this['advent_start'];
		$advent_next = $keys_next['advent_start'];
		$advent_prev = $keys_prev['advent_start'];

		// ── 2. Christmas Time  (Christmas → Baptism of the Lord) ─────────
		$christmas      = new DateTime( "{$year}-12-25" );
		$christmas_prev = new DateTime( ( $year - 1 ) . '-12-25' );

		// ── Determine season ──────────────────────────────────────────────

		$season      = '';
		$week        = null;
		$special_day = '';

		// Test in reverse-chronological order for the *current* liturgical year,
		// which starts in Advent. We test the Advent belonging to THIS calendar
		// year first, then fall back to the previous year's Advent.

		if ( $ts >= $advent_this->getTimestamp() ) {
			// ─ Advent (this year) ─
			$season = self::SEASON_ADVENT;
			$week   = self::advent_week( $date, $advent_this );
			if ( $week === 3 ) {
				$gaudete = self::nth_sunday_of_advent( $advent_this, 3 );
				if ( $date->format( 'Y-m-d' ) === $gaudete->format( 'Y-m-d' ) ) {
					$special_day = __( 'Gaudete Sunday', 'parish-liturgical-calendar' );
				}
			}

		} elseif ( $ts >= $keys_this['easter']->getTimestamp() &&
		           $ts <= $keys_this['pentecost']->getTimestamp() ) {
			// ─ Easter Time ─
			$season = self::SEASON_EASTER;
			$week   = self::week_diff( $keys_this['easter'], $date ) + 1;
			$special_day = self::special_day_easter( $date, $keys_this );

		} elseif ( $ts >= $keys_this['holy_thursday']->getTimestamp() &&
		           $ts < $keys_this['easter']->getTimestamp() ) {
			// ─ Sacred Triduum ─
			$season = self::SEASON_TRIDUUM;
			$d      = $date->format( 'Y-m-d' );
			if ( $d === $keys_this['holy_thursday']->format( 'Y-m-d' ) ) {
				$special_day = __( 'Holy Thursday \u2014 Mass of the Lord\'s Supper', 'parish-liturgical-calendar' );
			} elseif ( $d === $keys_this['good_friday']->format( 'Y-m-d' ) ) {
				$special_day = __( 'Good Friday — Passion of the Lord', 'parish-liturgical-calendar' );
			} else {
				$special_day = __( 'Holy Saturday — Easter Vigil', 'parish-liturgical-calendar' );
			}

		} elseif ( $ts >= $keys_this['ash_wednesday']->getTimestamp() &&
		           $ts < $keys_this['holy_thursday']->getTimestamp() ) {
			// ─ Lent ─
			$season = self::SEASON_LENT;
			$week   = self::lent_week( $date, $keys_this['ash_wednesday'] );
			$d = $date->format( 'Y-m-d' );
			if ( $d === $keys_this['ash_wednesday']->format( 'Y-m-d' ) ) {
				$special_day = __( 'Ash Wednesday', 'parish-liturgical-calendar' );
			} elseif ( $d === $keys_this['palm_sunday']->format( 'Y-m-d' ) ) {
				$special_day = __( 'Palm Sunday — Passion Sunday', 'parish-liturgical-calendar' );
			} elseif ( $week === 4 && (int) $date->format( 'N' ) === 7 ) {
				$special_day = __( 'Laetare Sunday', 'parish-liturgical-calendar' );
			}

		} elseif ( $ts > $keys_this['pentecost']->getTimestamp() ) {
			// ─ After Pentecost, before Advent = Ordinary Time (later) ─
			$season = self::SEASON_ORDINARY_LATER;
			$week   = self::ordinary_week_after_pentecost( $date, $keys_this );
			$special_day = self::special_day_fixed( $date );
			// Christ the King: last Sunday before Advent
			$christ_king = clone $advent_this;
			$christ_king->modify( '-7 days' );
			if ( $date->format( 'Y-m-d' ) === $christ_king->format( 'Y-m-d' ) ) {
				$special_day = __( 'Our Lord Jesus Christ, King of the Universe', 'parish-liturgical-calendar' );
			}

		} elseif ( $ts >= $keys_prev['advent_start']->getTimestamp() ) {
			// ─ We are between Advent-prev and Pentecost-this ─
			// Sub-cases: Advent carry-over (Dec), Christmas Time (Jan), early Ordinary Time (Feb–Ash Wed)
			$baptism_this_year = self::baptism_of_lord( $year );

			// Christmas Time spans Dec 25 (prev year) → Baptism of the Lord
			// For dates in Jan/Feb of $year, Christmas from $year-1 applies.
			$xmas_prev_ts   = $christmas_prev->getTimestamp();
			$baptism_ts     = $baptism_this_year->getTimestamp();
			$ash_ts         = $keys_this['ash_wednesday']->getTimestamp();
			$advent_prev_ts = $keys_prev['advent_start']->getTimestamp();

			if ( $ts >= $xmas_prev_ts && $ts <= $baptism_ts ) {
				// ─ Christmas Time (carrying over from prior year) ─
				$season = self::SEASON_CHRISTMAS;
				$d = $date->format( 'Y-m-d' );
				if ( $d === $christmas_prev->format( 'Y-m-d' ) ) {
					$special_day = __( 'The Nativity of the Lord', 'parish-liturgical-calendar' );
				} elseif ( $d === $baptism_this_year->format( 'Y-m-d' ) ) {
					$special_day = __( 'Baptism of the Lord', 'parish-liturgical-calendar' );
				}

			} elseif ( $ts > $baptism_ts && $ts < $ash_ts ) {
				// ─ Ordinary Time (early) ─
				$season = self::SEASON_ORDINARY_EARLY;
				$week   = self::ordinary_week_after_epiphany( $date, $baptism_this_year );
				$special_day = self::special_day_fixed( $date );

			} elseif ( $ts >= $advent_prev_ts && $ts < $xmas_prev_ts ) {
				// ─ Advent (carried from prior year, i.e. Dec 1–24) ─
				$season = self::SEASON_ADVENT;
				$week   = self::advent_week( $date, $keys_prev['advent_start'] );
				if ( $week === 3 ) {
					$gaudete = self::nth_sunday_of_advent( $keys_prev['advent_start'], 3 );
					if ( $date->format( 'Y-m-d' ) === $gaudete->format( 'Y-m-d' ) ) {
						$special_day = __( 'Gaudete Sunday', 'parish-liturgical-calendar' );
					}
				}
				if ( $date->format( 'md' ) === '1208' ) {
					$special_day = __( 'Immaculate Conception of the Blessed Virgin Mary', 'parish-liturgical-calendar' );
				}
			}

		} else {
			// Fallback for any date not caught above (shouldn't normally be reached)
			$season = self::SEASON_ORDINARY_LATER;
			$special_day = self::special_day_fixed( $date );
		}

		// ── Christmas Time within THIS year (Dec 25 → end of Dec) ────────
		if ( $ts >= $christmas->getTimestamp() ) {
			$season = self::SEASON_CHRISTMAS;
			$d = $date->format( 'Y-m-d' );
			if ( $d === $christmas->format( 'Y-m-d' ) ) {
				$special_day = __( 'The Nativity of the Lord', 'parish-liturgical-calendar' );
			}
			if ( $ts >= $advent_this->getTimestamp() ) {
				// Can't be both Advent and Christmas — Christmas wins after Dec 25
				// (Advent ends on Dec 24)
				$season = self::SEASON_CHRISTMAS;
			}
		}

		// ── Colour ────────────────────────────────────────────────────────
		$color = self::season_color( $season, $date, $week );

		// Pentecost Sunday (last day of Easter Time) is always Red, not White.
		if ( $season === self::SEASON_EASTER &&
		     $date->format( 'Y-m-d' ) === $keys_this['pentecost']->format( 'Y-m-d' ) ) {
			$color = self::COLOR_RED;
		}

		// ── Sunday cycle (A/B/C) ──────────────────────────────────────────
		// 2019 is the reference year for Cycle A: the liturgical year that began
		// on the First Sunday of Advent 2019 uses Cycle A. Each subsequent year
		// rotates A → B → C → A. Weekday cycles alternate odd/even year.
		$liturgical_year_start = ( $ts >= $advent_this->getTimestamp() ) ? $year : $year - 1;
		$cycle_index           = ( $liturgical_year_start - 2019 ) % 3;
		if ( $cycle_index < 0 ) {
			$cycle_index += 3;
		}
		$sunday_cycle  = array( 'A', 'B', 'C' )[ $cycle_index ];
		$weekday_cycle = ( ( $liturgical_year_start % 2 ) === 1 ) ? 'I' : 'II';

		// ── Next season info ──────────────────────────────────────────────
		$next = self::next_season_info( $season, $year, $keys_this, $keys_next );

		return array(
			'season'        => $season,
			'season_label'  => self::season_label( $season ),
			'week'          => $week,
			'color'         => $color,
			'color_hex'     => self::color_hex( $color ),
			'color_label'   => self::color_label( $color ),
			'description'   => self::season_description( $season ),
			'special_day'   => $special_day,
			'year_cycle'    => $sunday_cycle,
			'weekday_cycle' => $weekday_cycle,
			'next_season'   => $next,
		);
	}

	// ── Key dates ─────────────────────────────────────────────────────────

	public static function key_dates( $year ) {
		$easter        = self::easter( $year );
		$ash           = self::days_offset( $easter, -46 );
		$palm          = self::days_offset( $easter, -7 );
		$holy_thursday = self::days_offset( $easter, -3 );
		$good_friday   = self::days_offset( $easter, -2 );
		$pentecost     = self::days_offset( $easter, 49 );
		$ascension     = self::days_offset( $easter, 39 ); // Thursday (some dioceses keep this)
		$corpus        = self::days_offset( $easter, 63 ); // Sunday option for Corpus Christi
		$sacred_heart  = self::days_offset( $easter, 68 ); // Friday after Corpus Christi
		$trinity       = self::days_offset( $easter, 56 );
		return array(
			'advent_start'  => self::advent_start( $year ),
			'easter'        => $easter,
			'ash_wednesday' => $ash,
			'palm_sunday'   => $palm,
			'holy_thursday' => $holy_thursday,
			'good_friday'   => $good_friday,
			'pentecost'     => $pentecost,
			'ascension'     => $ascension,
			'corpus_christi'=> $corpus,
			'sacred_heart'  => $sacred_heart,
			'trinity'       => $trinity,
		);
	}

	// ── Baptism of the Lord (end of Christmas Time) ───────────────────────
	// The Sunday after 6 January; if 6 Jan is itself a Sunday, the following Sunday.
	public static function baptism_of_lord( $year ) {
		$epiphany = new DateTime( "$year-01-06" );
		$dow      = (int) $epiphany->format( 'N' ); // 1=Mon … 7=Sun
		$days_to_sunday = ( 7 - $dow ) % 7;
		if ( $days_to_sunday === 0 ) {
			$days_to_sunday = 7; // Epiphany itself is Sunday → next Sunday
		}
		$baptism = clone $epiphany;
		$baptism->modify( "+{$days_to_sunday} days" );
		return $baptism;
	}

	// ── Week helpers ──────────────────────────────────────────────────────

	private static function week_diff( DateTime $start, DateTime $end ) {
		$diff = $start->diff( $end );
		return intdiv( $diff->days, 7 );
	}

	private static function advent_week( DateTime $date, DateTime $advent_start ) {
		return self::week_diff( $advent_start, $date ) + 1;
	}

	private static function nth_sunday_of_advent( DateTime $advent_start, $n ) {
		$s = clone $advent_start;
		$s->modify( '+' . ( ( $n - 1 ) * 7 ) . ' days' );
		return $s;
	}

	private static function lent_week( DateTime $date, DateTime $ash_wednesday ) {
		// Week 1 starts on Ash Wednesday
		return self::week_diff( $ash_wednesday, $date ) + 1;
	}

	private static function ordinary_week_after_epiphany( DateTime $date, DateTime $baptism ) {
		// Ordinary Time week 2 starts the Monday after Baptism of the Lord
		$start = clone $baptism;
		$start->modify( '+1 day' ); // Monday after Baptism
		$weeks = self::week_diff( $start, $date );
		return $weeks + 2; // Week 2 is first week of OT after Epiphany
	}

	private static function ordinary_week_after_pentecost( DateTime $date, array $keys ) {
		// Ordinary Time (later) picks up the week count where it left off before Lent.
		// The week of Ordinary Time running from Pentecost Monday = same count as if
		// we counted continuously from Baptism of the Lord.
		// Simplification: count weeks from the Monday after Pentecost.
		$pentecost_monday = clone $keys['pentecost'];
		$pentecost_monday->modify( '+1 day' );
		$weeks_from_pm = self::week_diff( $pentecost_monday, $date );
		// Find what ordinary week Pentecost-Monday falls on:
		// Pentecost is always 49 days after Easter, so the week count from
		// Baptism of the Lord to Ash Wed, plus the weeks skipped (Lent + Easter).
		// Easier: the week of OT = total weeks from start of year OT + adjustment.
		// We'll use the "elapsed OT weeks" approach:
		$baptism    = self::baptism_of_lord( (int) $keys['easter']->format( 'Y' ) );
		$baptism_monday = clone $baptism;
		$baptism_monday->modify( '+1 day' );
		$weeks_ot_before_lent = self::week_diff( $baptism_monday, $keys['ash_wednesday'] );
		$ot_week_at_pentecost = $weeks_ot_before_lent + 2 + $weeks_from_pm;
		return max( 2, $ot_week_at_pentecost );
	}

	// ── Colour logic ──────────────────────────────────────────────────────

	private static function season_color( $season, DateTime $date, $week ) {
		switch ( $season ) {
			case self::SEASON_ADVENT:
				return ( $week === 3 && (int) $date->format( 'N' ) === 7 )
					? self::COLOR_ROSE
					: self::COLOR_PURPLE;

			case self::SEASON_CHRISTMAS:
				return self::COLOR_WHITE;

			case self::SEASON_ORDINARY_EARLY:
			case self::SEASON_ORDINARY_LATER:
				// Fixed feasts can override
				$md = $date->format( 'md' );
				if ( in_array( $md, array( '0101', '0815', '1101', '1208', '1225' ), true ) ) {
					return self::COLOR_WHITE;
				}
				return self::COLOR_GREEN;

			case self::SEASON_LENT:
				// Rose on Laetare (4th Sunday)
				if ( $week === 4 && (int) $date->format( 'N' ) === 7 ) {
					return self::COLOR_ROSE;
				}
				// Red on Palm Sunday
				if ( $week >= 6 && (int) $date->format( 'N' ) === 7 ) {
					return self::COLOR_RED;
				}
				return self::COLOR_PURPLE;

			case self::SEASON_TRIDUUM:
				// Holy Thursday (N=4) and Holy Saturday (N=6) = White.
				// Good Friday (N=5) = Red.
				// The Triduum spans exactly these three consecutive days (Thu-Sat).
				$dow = (int) $date->format( 'N' );
				return ( $dow === 5 ) ? self::COLOR_RED : self::COLOR_WHITE;

			case self::SEASON_EASTER:
				// Default Easter Time colour is White.
				// Pentecost Sunday (the 50th day) is Red; that override is applied
				// in get_season() after season_color() returns.
				return self::COLOR_WHITE;

			default:
				return self::COLOR_GREEN;
		}
	}

	// ── Special fixed-date days ────────────────────────────────────────────

	private static function special_day_fixed( DateTime $date ) {
		$map = array(
			'0101' => __( 'Solemnity of Mary, Mother of God', 'parish-liturgical-calendar' ),
			'0202' => __( 'Presentation of the Lord (Candlemas)', 'parish-liturgical-calendar' ),
			'0325' => __( 'Annunciation of the Lord', 'parish-liturgical-calendar' ),
			'0620' => __( 'Immaculate Heart of Mary', 'parish-liturgical-calendar' ),
			'0629' => __( 'Sts Peter and Paul, Apostles', 'parish-liturgical-calendar' ),
			'0815' => __( 'Assumption of the Blessed Virgin Mary', 'parish-liturgical-calendar' ),
			'1101' => __( 'All Saints', 'parish-liturgical-calendar' ),
			'1102' => __( 'All Souls', 'parish-liturgical-calendar' ),
			'1208' => __( 'Immaculate Conception of the Blessed Virgin Mary', 'parish-liturgical-calendar' ),
			'1225' => __( 'The Nativity of the Lord', 'parish-liturgical-calendar' ),
		);
		$md = $date->format( 'md' );
		return isset( $map[ $md ] ) ? $map[ $md ] : '';
	}

	private static function special_day_easter( DateTime $date, array $keys ) {
		$d = $date->format( 'Y-m-d' );
		$checks = array(
			$keys['easter']->format( 'Y-m-d' )        => __( 'Easter Sunday — The Resurrection of the Lord', 'parish-liturgical-calendar' ),
			self::days_offset( $keys['easter'], 1 )->format( 'Y-m-d' )  => __( 'Easter Monday', 'parish-liturgical-calendar' ),
			self::days_offset( $keys['easter'], 7 )->format( 'Y-m-d' )  => __( 'Divine Mercy Sunday', 'parish-liturgical-calendar' ),
			$keys['ascension']->format( 'Y-m-d' )     => __( 'Ascension of the Lord', 'parish-liturgical-calendar' ),
			$keys['pentecost']->format( 'Y-m-d' )     => __( 'Pentecost Sunday', 'parish-liturgical-calendar' ),
		);
		return isset( $checks[ $d ] ) ? $checks[ $d ] : '';
	}

	// ── Labels and descriptions ────────────────────────────────────────────

	public static function season_label( $season ) {
		$labels = array(
			self::SEASON_ADVENT         => __( 'Advent', 'parish-liturgical-calendar' ),
			self::SEASON_CHRISTMAS      => __( 'Christmas Time', 'parish-liturgical-calendar' ),
			self::SEASON_ORDINARY_EARLY => __( 'Ordinary Time', 'parish-liturgical-calendar' ),
			self::SEASON_LENT           => __( 'Lent', 'parish-liturgical-calendar' ),
			self::SEASON_TRIDUUM        => __( 'Sacred Paschal Triduum', 'parish-liturgical-calendar' ),
			self::SEASON_EASTER         => __( 'Easter Time', 'parish-liturgical-calendar' ),
			self::SEASON_ORDINARY_LATER => __( 'Ordinary Time', 'parish-liturgical-calendar' ),
		);
		return isset( $labels[ $season ] ) ? $labels[ $season ] : ucfirst( $season );
	}

	public static function season_description( $season ) {
		$desc = array(
			self::SEASON_ADVENT         => __( 'We prepare our hearts to welcome Christ — both in the celebration of his Nativity and at his coming at the end of time. Advent is a season of hopeful waiting, prayer, and conversion.', 'parish-liturgical-calendar' ),
			self::SEASON_CHRISTMAS      => __( 'We celebrate the mystery of the Word made flesh. Christmas Time stretches from the Nativity of the Lord through the Baptism of the Lord, inviting us to contemplate God dwelling among us.', 'parish-liturgical-calendar' ),
			self::SEASON_ORDINARY_EARLY => __( 'In Ordinary Time we reflect on the full mystery of Christ — his teaching, his miracles, and his call to discipleship. The word "ordinary" comes from "ordinal" (counted), not "plain".', 'parish-liturgical-calendar' ),
			self::SEASON_LENT           => __( 'A season of forty days of prayer, fasting, and almsgiving in preparation for the great Easter celebration. We are invited to deeper conversion and to accompany those preparing for Baptism.', 'parish-liturgical-calendar' ),
			self::SEASON_TRIDUUM        => __( 'The three holiest days of the Church\'s year: Holy Thursday, Good Friday, and Holy Saturday / Easter Vigil. We move from the Last Supper through the Passion to the dawn of the Resurrection.', 'parish-liturgical-calendar' ),
			self::SEASON_EASTER         => __( 'The fifty days from Easter Sunday to Pentecost are celebrated as one continuous feast day, even as one great Sunday. We rejoice in the Risen Lord and await the gift of the Holy Spirit.', 'parish-liturgical-calendar' ),
			self::SEASON_ORDINARY_LATER => __( 'In Ordinary Time we reflect on the full mystery of Christ — his teaching, his miracles, and his call to discipleship. The word "ordinary" comes from "ordinal" (counted), not "plain".', 'parish-liturgical-calendar' ),
		);
		return isset( $desc[ $season ] ) ? $desc[ $season ] : '';
	}

	public static function color_hex( $color ) {
		$map = array(
			self::COLOR_PURPLE => '#6b3fa0',
			self::COLOR_ROSE   => '#c47fa5',
			self::COLOR_WHITE  => '#f5f0e0',
			self::COLOR_GREEN  => '#2d6a4f',
			self::COLOR_RED    => '#b5253a',
		);
		return isset( $map[ $color ] ) ? $map[ $color ] : '#555555';
	}

	public static function color_label( $color ) {
		$map = array(
			self::COLOR_PURPLE => __( 'Violet', 'parish-liturgical-calendar' ),
			self::COLOR_ROSE   => __( 'Rose', 'parish-liturgical-calendar' ),
			self::COLOR_WHITE  => __( 'White', 'parish-liturgical-calendar' ),
			self::COLOR_GREEN  => __( 'Green', 'parish-liturgical-calendar' ),
			self::COLOR_RED    => __( 'Red', 'parish-liturgical-calendar' ),
		);
		return isset( $map[ $color ] ) ? $map[ $color ] : $color;
	}

	// ── Next season ────────────────────────────────────────────────────────

	private static function next_season_info( $current_season, $year, array $keys, array $keys_next ) {
		$map = array(
			self::SEASON_ADVENT         => array( 'season' => self::SEASON_CHRISTMAS,      'date_key' => 'christmas' ),
			self::SEASON_CHRISTMAS      => array( 'season' => self::SEASON_ORDINARY_EARLY, 'date_key' => 'baptism' ),
			self::SEASON_ORDINARY_EARLY => array( 'season' => self::SEASON_LENT,           'date_key' => 'ash_wednesday' ),
			self::SEASON_LENT           => array( 'season' => self::SEASON_TRIDUUM,        'date_key' => 'holy_thursday' ),
			self::SEASON_TRIDUUM        => array( 'season' => self::SEASON_EASTER,         'date_key' => 'easter' ),
			self::SEASON_EASTER         => array( 'season' => self::SEASON_ORDINARY_LATER, 'date_key' => 'pentecost_next_day' ),
			self::SEASON_ORDINARY_LATER => array( 'season' => self::SEASON_ADVENT,         'date_key' => 'advent_next' ),
		);

		if ( ! isset( $map[ $current_season ] ) ) {
			return null;
		}

		$info     = $map[ $current_season ];
		$next_key = $info['date_key'];

		$date = null;
		if ( $next_key === 'christmas' ) {
			$date = new DateTime( "$year-12-25" );
		} elseif ( $next_key === 'baptism' ) {
			$date = self::baptism_of_lord( $year + 1 );
		} elseif ( $next_key === 'pentecost_next_day' ) {
			$date = self::days_offset( $keys['pentecost'], 1 );
		} elseif ( $next_key === 'advent_next' ) {
			$date = $keys_next['advent_start'];
		} elseif ( isset( $keys[ $next_key ] ) ) {
			$date = $keys[ $next_key ];
		}

		return array(
			'season'       => $info['season'],
			'season_label' => self::season_label( $info['season'] ),
			'date'         => $date,
			'date_label'   => $date ? $date->format( get_option( 'date_format', 'j F Y' ) ) : '',
		);
	}

	// ── Utility ───────────────────────────────────────────────────────────

	public static function days_offset( DateTime $base, $days ) {
		$d = clone $base;
		if ( $days >= 0 ) {
			$d->modify( "+{$days} days" );
		} else {
			$abs = abs( $days );
			$d->modify( "-{$abs} days" );
		}
		return $d;
	}

	/**
	 * Returns an array of key liturgical dates for a full year for display
	 * in a "coming up" list — all moveable feasts.
	 *
	 * @param int $year
	 * @return array[] Array of ['label', 'date' (DateTime), 'color']
	 */
	public static function moveable_feasts( $year ) {
		$k = self::key_dates( $year );
		$feasts = array(
			array( 'label' => __( 'Ash Wednesday', 'parish-liturgical-calendar' ),           'date' => $k['ash_wednesday'],  'color' => self::COLOR_PURPLE ),
			array( 'label' => __( 'Palm Sunday', 'parish-liturgical-calendar' ),             'date' => $k['palm_sunday'],    'color' => self::COLOR_RED    ),
			array( 'label' => __( 'Holy Thursday', 'parish-liturgical-calendar' ),           'date' => $k['holy_thursday'],  'color' => self::COLOR_WHITE  ),
			array( 'label' => __( 'Good Friday', 'parish-liturgical-calendar' ),             'date' => $k['good_friday'],    'color' => self::COLOR_RED    ),
			array( 'label' => __( 'Easter Sunday', 'parish-liturgical-calendar' ),           'date' => $k['easter'],         'color' => self::COLOR_WHITE  ),
			array( 'label' => __( 'Ascension Thursday', 'parish-liturgical-calendar' ),      'date' => $k['ascension'],      'color' => self::COLOR_WHITE  ),
			array( 'label' => __( 'Pentecost Sunday', 'parish-liturgical-calendar' ),        'date' => $k['pentecost'],      'color' => self::COLOR_RED    ),
			array( 'label' => __( 'Trinity Sunday', 'parish-liturgical-calendar' ),          'date' => $k['trinity'],        'color' => self::COLOR_WHITE  ),
			array( 'label' => __( 'Corpus Christi', 'parish-liturgical-calendar' ),          'date' => $k['corpus_christi'], 'color' => self::COLOR_WHITE  ),
			array( 'label' => __( 'Sacred Heart of Jesus', 'parish-liturgical-calendar' ),   'date' => $k['sacred_heart'],   'color' => self::COLOR_WHITE  ),
			array( 'label' => __( 'First Sunday of Advent', 'parish-liturgical-calendar' ),  'date' => $k['advent_start'],   'color' => self::COLOR_PURPLE ),
		);
		return $feasts;
	}
}
