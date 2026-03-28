# Parish Liturgical Calendar — WordPress Plugin

**Version:** 1.0.0  
**Requires WordPress:** 5.6+  
**Requires PHP:** 7.4+  
**Tested with:** WordPress 6.x  
**Licence:** GPL-2.0-or-later

---

## What this plugin does

Gives visitors to **stjohnscatholicchurch.co.za** (or any Catholic parish WordPress site) an immediate, living sense of where the Church is in her year. Every time someone opens the home page they can see:

| Feature | How it works |
|---|---|
| **Current liturgical season** | Automatically calculated from the Roman Catholic General Calendar. Updates every day — no admin work needed. |
| **Season colour** | A coloured stripe/banner uses the correct liturgical colour (violet, rose, white, green, red). The whole dashboard tints accordingly. |
| **Special days** | Solemnities such as Ash Wednesday, Gaudete Sunday, Easter, Pentecost, and fixed feasts (Assumption, All Saints…) are highlighted automatically. |
| **Sunday & weekday lectionary cycle** | Shows "Cycle A/B/C · Weekday I/II" so ministers can prepare. |
| **Upcoming parish events** | A custom post type lets you add any one-off event (date, time, location, description). |
| **Recent parish life** | Shows events from the past 30 days so the page never looks stale. |
| **Next-season countdown** | Tells visitors when the next season begins. |
| **Weekly bulletin link** | Paste the URL of the current bulletin PDF; the plugin shows a download button in the right liturgical colour. |
| **Mass times block** | Set a short HTML snippet in settings; it appears in the dashboard. |
| **Sidebar widget** | Drag "Liturgical Season" into any widget area. |
| **The Events Calendar compatible** | If you already use The Events Calendar plugin, the shortcodes query that instead of the built-in CPT. |

---

## Installation

1. **Download / copy** the `parish-liturgical-calendar` folder.  
2. **Upload** it to `/wp-content/plugins/` on your server  
   *(WP Admin → Plugins → Add New → Upload Plugin is the easiest route).*  
3. **Activate** the plugin via **Plugins → Installed Plugins**.  
4. Open **Settings → Parish Calendar** to:
   - Confirm the parish name.
   - Paste the URL of this week's bulletin PDF.
   - Enter the mass-times text (HTML is allowed).
5. Add the shortcode `[parish_dashboard]` to your home page (or any page).

---

## Shortcodes

| Shortcode | Description | Key attributes |
|---|---|---|
| `[parish_dashboard]` | Full combined block — season banner + events + recent + bulletin. Best for the home page. | `events_limit="5"` `recent_limit="3"` |
| `[liturgical_season]` | Season banner only. | `show_week="true"` `show_next="true"` `show_description="true"` `show_cycle="false"` |
| `[parish_events]` | Upcoming events list. | `limit="5"` `past="0"` `heading="Upcoming Events"` |
| `[parish_recent]` | Recent past events. | `limit="3"` `days="30"` `heading="Recent Parish Life"` |
| `[parish_bulletin]` | PDF bulletin download link only. | `label="Download…"` |

### Example home-page placement

Open your home page in the WordPress editor and add:

```
[parish_dashboard events_limit="6" recent_limit="3"]
```

Or, for a leaner layout that works inside a sidebar column:

```
[liturgical_season]
[parish_events limit="4"]
[parish_bulletin]
```

---

## Adding parish events

1. Go to **Parish Events → Add New** in the WP admin sidebar.  
2. Give the event a title and, optionally, a description or featured image.  
3. Fill in the **Event Details** meta box (right sidebar):
   - **Date** (required)
   - **Time** (optional)
   - **End Date** (optional — for multi-day events)
   - **Location** (optional — e.g. "Parish Hall")
4. Publish.

Events appear automatically in `[parish_events]` and `[parish_dashboard]`.

---

## Updating the weekly bulletin

1. Upload the PDF via **Media → Add New**.  
2. Click the file to open its details; copy the **File URL**.  
3. Paste it into **Settings → Parish Calendar → Bulletin PDF URL**.  
4. Save. The download button on the front end updates immediately.

---

## How the liturgical calendar is calculated

The plugin uses the **General Roman Calendar** as promulgated for ordinary-form (Novus Ordo) celebrations:

| Season | Dates |
|---|---|
| Advent | 4 Sundays before Christmas → Christmas Eve |
| Christmas Time | Christmas Day → Baptism of the Lord |
| Ordinary Time (early) | Monday after Baptism of the Lord → Shrove Tuesday |
| Lent | Ash Wednesday → Wednesday of Holy Week |
| Sacred Triduum | Holy Thursday → Holy Saturday |
| Easter Time | Easter Sunday → Pentecost Sunday |
| Ordinary Time (later) | Monday after Pentecost → Saturday before Advent |

Easter is computed using the **Butcher/Meeus–Jones–Butcher algorithm** (accurate for all years 1583–9999 CE).

Special days automatically recognised include: Ash Wednesday, Palm Sunday, Gaudete Sunday (Advent 3), Laetare Sunday (Lent 4), Holy Thursday, Good Friday, Holy Saturday, Easter Sunday, Divine Mercy Sunday, Ascension, Pentecost, Trinity Sunday, Corpus Christi, Sacred Heart, Assumption (Aug 15), All Saints (Nov 1), All Souls (Nov 2), Immaculate Conception (Dec 8), Christmas (Dec 25), and Christ the King (last Sunday before Advent).

---

## Running the tests

A self-contained PHP test file with no dependencies is included:

```bash
php tests/test-liturgical-calendar.php
```

Expected output: 57 tests, 0 failures.

---

## Folder structure

```
parish-liturgical-calendar/
├── parish-liturgical-calendar.php   ← Main plugin file (load this)
├── includes/
│   ├── class-liturgical-calendar.php  ← Season engine (pure PHP)
│   ├── class-events.php               ← Custom post type + queries
│   ├── class-shortcodes.php           ← All shortcodes
│   ├── class-widget.php               ← Sidebar widget
│   └── class-admin.php                ← Settings page
├── templates/
│   ├── season-banner.php              ← Banner HTML template
│   ├── events-list.php                ← Events list HTML template
│   └── dashboard.php                  ← Full dashboard HTML template
├── assets/
│   ├── style.css                      ← Frontend styles (seasonal colours)
│   └── script.js                      ← Countdown timers (no jQuery)
└── tests/
    └── test-liturgical-calendar.php   ← 57-assertion test suite
```

---

## Frequently asked questions

**Does this work with Gutenberg / the block editor?**  
Yes — add a Shortcode block and paste any of the shortcodes above.

**I already use The Events Calendar plugin. Will this conflict?**  
No. When The Events Calendar is active, the built-in `parish_event` custom post type is not registered, and the shortcodes query `tribe_get_events()` instead.

**Can I style the colours to match our theme?**  
Yes. The CSS uses the custom property `--plc-color` set inline on every component. You can override classes like `.plc-season-advent` or `.plc-color-purple` in your theme's `style.css` or via the WordPress Customizer's "Additional CSS" panel.

**Will the season update automatically?**  
Yes — it is recalculated on every page load. There is no cron job or cache to manage.

---

## Changelog

### 1.0.0
- Initial release.
- Liturgical calendar engine with full Roman Rite season detection.
- `parish_event` custom post type.
- Shortcodes: `[parish_dashboard]`, `[liturgical_season]`, `[parish_events]`, `[parish_recent]`, `[parish_bulletin]`.
- Sidebar widget.
- Admin settings page with season preview.
- 57-test suite covering Easter algorithm and all season/colour/special-day logic.
