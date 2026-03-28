<?php
/**
 * Unit tests for PLC_Liturgical_Calendar
 *
 * Run with:  php tests/test-liturgical-calendar.php
 *
 * No PHPUnit required — uses a minimal hand-rolled harness so these tests
 * can be executed in any PHP 7.4+ environment, including vanilla web-server
 * installs where PHPUnit may not be available.
 *
 * @package Parish_Liturgical_Calendar
 */

// ── Stub the WordPress functions used by the calendar class ────────────────
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $key, $default = '' ) {
		if ( $key === 'timezone_string' ) {
			return 'Africa/Johannesburg';
		}
		if ( $key === 'date_format' ) {
			return 'j F Y';
		}
		return $default;
	}
}
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = '' ) {
		return $text;
	}
}

require_once __DIR__ . '/../includes/class-liturgical-calendar.php';

// ── Minimal test harness ────────────────────────────────────────────────────

$pass = 0;
$fail = 0;

function assert_equals( $expected, $actual, $label ) {
	global $pass, $fail;
	if ( $expected === $actual ) {
		echo "  ✓  $label\n";
		$pass++;
	} else {
		echo "  ✗  $label\n";
		echo "     Expected: " . var_export( $expected, true ) . "\n";
		echo "     Actual:   " . var_export( $actual, true ) . "\n";
		$fail++;
	}
}

function assert_contains( $needle, $haystack, $label ) {
	global $pass, $fail;
	if ( strpos( $haystack, $needle ) !== false ) {
		echo "  ✓  $label\n";
		$pass++;
	} else {
		echo "  ✗  $label\n";
		echo "     Expected '$needle' inside '$haystack'\n";
		$fail++;
	}
}

function section( $name ) {
	echo "\n── $name\n";
}

// ── Easter algorithm ───────────────────────────────────────────────────────

section( 'Easter dates (known values)' );
$known_easters = array(
	2019 => '2019-04-21',
	2020 => '2020-04-12',
	2021 => '2021-04-04',
	2022 => '2022-04-17',
	2023 => '2023-04-09',
	2024 => '2024-03-31',
	2025 => '2025-04-20',
	2026 => '2026-04-05',
	2027 => '2027-03-28',
);
foreach ( $known_easters as $year => $expected ) {
	$e = PLC_Liturgical_Calendar::easter( $year );
	assert_equals( $expected, $e->format( 'Y-m-d' ), "Easter $year = $expected" );
}

// ── Advent start ──────────────────────────────────────────────────────────

section( 'Advent start dates' );
$known_advents = array(
	2023 => '2023-12-03',
	2024 => '2024-12-01',
	2025 => '2025-11-30',
	2026 => '2026-11-29',
);
foreach ( $known_advents as $year => $expected ) {
	$a = PLC_Liturgical_Calendar::advent_start( $year );
	assert_equals( $expected, $a->format( 'Y-m-d' ), "Advent $year = $expected" );
}

// ── Baptism of the Lord ───────────────────────────────────────────────────

section( 'Baptism of the Lord' );
$known_baptisms = array(
	2024 => '2024-01-07', // Epiphany Jan 6 (Sat) → next Sunday Jan 7
	2025 => '2025-01-12',
	2026 => '2026-01-11',
);
foreach ( $known_baptisms as $year => $expected ) {
	$b = PLC_Liturgical_Calendar::baptism_of_lord( $year );
	assert_equals( $expected, $b->format( 'Y-m-d' ), "Baptism of Lord $year = $expected" );
}

// ── Season detection ──────────────────────────────────────────────────────

section( 'Season detection — Advent' );
$result = PLC_Liturgical_Calendar::get_season( new DateTime( '2024-12-01' ) ); // First Sunday of Advent 2024
assert_equals( PLC_Liturgical_Calendar::SEASON_ADVENT, $result['season'], 'Dec 1 2024 is Advent' );
assert_equals( PLC_Liturgical_Calendar::COLOR_PURPLE, $result['color'], 'Advent colour is purple' );
assert_equals( 1, $result['week'], 'Dec 1 2024 is Advent week 1' );

$result = PLC_Liturgical_Calendar::get_season( new DateTime( '2024-12-15' ) ); // 3rd Sunday of Advent 2024
assert_equals( PLC_Liturgical_Calendar::SEASON_ADVENT, $result['season'], 'Dec 15 2024 is Advent' );
assert_equals( 3, $result['week'], 'Dec 15 2024 is Advent week 3' );
assert_equals( PLC_Liturgical_Calendar::COLOR_ROSE, $result['color'], 'Advent week 3 Sunday = Rose' );
assert_contains( 'Gaudete', $result['special_day'], 'Dec 15 2024 is Gaudete Sunday' );

section( 'Season detection — Christmas' );
$result = PLC_Liturgical_Calendar::get_season( new DateTime( '2024-12-25' ) );
assert_equals( PLC_Liturgical_Calendar::SEASON_CHRISTMAS, $result['season'], 'Dec 25 is Christmas' );
assert_equals( PLC_Liturgical_Calendar::COLOR_WHITE, $result['color'], 'Christmas colour is white' );
assert_contains( 'Nativity', $result['special_day'], 'Dec 25 special day = Nativity' );

section( 'Season detection — Lent' );
// Ash Wednesday 2025 = March 5
$result = PLC_Liturgical_Calendar::get_season( new DateTime( '2025-03-05' ) );
assert_equals( PLC_Liturgical_Calendar::SEASON_LENT, $result['season'], 'Mar 5 2025 is Lent (Ash Wednesday)' );
assert_contains( 'Ash Wednesday', $result['special_day'], 'Mar 5 2025 = Ash Wednesday' );

// Laetare Sunday 2025 = 4th Sunday of Lent = Mar 30
$result = PLC_Liturgical_Calendar::get_season( new DateTime( '2025-03-30' ) );
assert_equals( PLC_Liturgical_Calendar::SEASON_LENT, $result['season'], 'Mar 30 2025 is Lent' );
assert_equals( 4, $result['week'], 'Mar 30 2025 is Lent week 4' );
assert_equals( PLC_Liturgical_Calendar::COLOR_ROSE, $result['color'], 'Laetare Sunday = Rose' );

// Palm Sunday 2025 = April 13
$result = PLC_Liturgical_Calendar::get_season( new DateTime( '2025-04-13' ) );
assert_equals( PLC_Liturgical_Calendar::SEASON_LENT, $result['season'], 'Apr 13 2025 is Lent (Palm Sunday)' );
assert_contains( 'Palm Sunday', $result['special_day'], 'Apr 13 2025 = Palm Sunday' );
assert_equals( PLC_Liturgical_Calendar::COLOR_RED, $result['color'], 'Palm Sunday colour = Red' );

section( 'Season detection — Triduum' );
// Holy Thursday 2025 = April 17
$result = PLC_Liturgical_Calendar::get_season( new DateTime( '2025-04-17' ) );
assert_equals( PLC_Liturgical_Calendar::SEASON_TRIDUUM, $result['season'], 'Apr 17 2025 = Triduum (Holy Thursday)' );
assert_contains( 'Holy Thursday', $result['special_day'], 'Holy Thursday special day label' );

// Good Friday 2025 = April 18
$result = PLC_Liturgical_Calendar::get_season( new DateTime( '2025-04-18' ) );
assert_equals( PLC_Liturgical_Calendar::SEASON_TRIDUUM, $result['season'], 'Apr 18 2025 = Triduum (Good Friday)' );
assert_equals( PLC_Liturgical_Calendar::COLOR_RED, $result['color'], 'Good Friday = Red' );
assert_contains( 'Good Friday', $result['special_day'], 'Good Friday special day label' );

section( 'Season detection — Easter' );
// Easter Sunday 2025 = April 20
$result = PLC_Liturgical_Calendar::get_season( new DateTime( '2025-04-20' ) );
assert_equals( PLC_Liturgical_Calendar::SEASON_EASTER, $result['season'], 'Apr 20 2025 = Easter' );
assert_equals( PLC_Liturgical_Calendar::COLOR_WHITE, $result['color'], 'Easter colour = White' );
assert_contains( 'Easter Sunday', $result['special_day'], 'Easter Sunday special day label' );

// Pentecost 2025 = June 8
$result = PLC_Liturgical_Calendar::get_season( new DateTime( '2025-06-08' ) );
assert_equals( PLC_Liturgical_Calendar::SEASON_EASTER, $result['season'], 'Jun 8 2025 = Easter time (Pentecost)' );
assert_contains( 'Pentecost', $result['special_day'], 'Pentecost special day label' );
assert_equals( PLC_Liturgical_Calendar::COLOR_RED, $result['color'], 'Pentecost colour = Red' );

section( 'Season detection — Ordinary Time (later)' );
// Mid-July 2025 = Ordinary Time
$result = PLC_Liturgical_Calendar::get_season( new DateTime( '2025-07-15' ) );
assert_equals( PLC_Liturgical_Calendar::SEASON_ORDINARY_LATER, $result['season'], 'Jul 15 2025 = Ordinary Time' );
assert_equals( PLC_Liturgical_Calendar::COLOR_GREEN, $result['color'], 'Ordinary Time colour = Green' );

// Assumption Aug 15
$result = PLC_Liturgical_Calendar::get_season( new DateTime( '2025-08-15' ) );
assert_contains( 'Assumption', $result['special_day'], 'Aug 15 = Assumption' );
assert_equals( PLC_Liturgical_Calendar::COLOR_WHITE, $result['color'], 'Assumption colour = White' );

// All Saints Nov 1
$result = PLC_Liturgical_Calendar::get_season( new DateTime( '2025-11-01' ) );
assert_contains( 'All Saints', $result['special_day'], 'Nov 1 = All Saints' );

// Christ the King 2025 = Sunday before Advent 2025 (Nov 30 - 7 = Nov 23)
$result = PLC_Liturgical_Calendar::get_season( new DateTime( '2025-11-23' ) );
assert_contains( 'King', $result['special_day'], 'Nov 23 2025 = Christ the King' );

section( 'Sunday Cycle' );
// Liturgical year 2023-2024 = Cycle B (2023 mod 3 = ?); 2024-2025 = C; 2025-2026 = A
$result = PLC_Liturgical_Calendar::get_season( new DateTime( '2024-06-15' ) ); // within year starting Advent 2023
assert_equals( 'B', $result['year_cycle'], 'Liturgical year 2023-2024 = Cycle B' );

$result = PLC_Liturgical_Calendar::get_season( new DateTime( '2025-02-01' ) ); // within year starting Advent 2024
assert_equals( 'C', $result['year_cycle'], 'Liturgical year 2024-2025 = Cycle C' );

$result = PLC_Liturgical_Calendar::get_season( new DateTime( '2025-12-01' ) ); // Advent 2025 starts year
assert_equals( 'A', $result['year_cycle'], 'Liturgical year 2025-2026 = Cycle A' );

section( 'Moveable feasts list' );
$feasts = PLC_Liturgical_Calendar::moveable_feasts( 2025 );
assert_equals( true, count( $feasts ) >= 10, 'At least 10 moveable feasts returned for 2025' );
$feast_labels = array_column( $feasts, 'label' );
assert_equals( true, in_array( 'Easter Sunday', $feast_labels, true ), 'Easter Sunday in feasts list' );
assert_equals( true, in_array( 'Ash Wednesday', $feast_labels, true ), 'Ash Wednesday in feasts list' );
assert_equals( true, in_array( 'Pentecost Sunday', $feast_labels, true ), 'Pentecost Sunday in feasts list' );

// ── Summary ────────────────────────────────────────────────────────────────

echo "\n── Results\n";
echo "   Passed: $pass\n";
echo "   Failed: $fail\n";

if ( $fail > 0 ) {
	exit( 1 );
}
