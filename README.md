# St John's Catholic Church — Maitland

Website development repository for **stjohnscatholicchurch.co.za** — a Roman Catholic parish in Maitland, Cape Town, belonging to the Archdiocese of Cape Town.

---

## Contents

| Path | Description |
|---|---|
| [`wp-plugin/parish-liturgical-calendar/`](wp-plugin/parish-liturgical-calendar/) | WordPress plugin: liturgical season banner, parish events, and weekly bulletin — linked to the Roman Catholic liturgical year |

---

## Parish Liturgical Calendar plugin

The core deliverable is a WordPress plugin that answers the question:  
*"What is happening in the parish right now, and where are we in the Church's year?"*

### What it shows on the home page

- **Current liturgical season** (Advent, Christmas, Lent, Triduum, Easter, Ordinary Time) with the correct liturgical colour as a visual accent
- **Special days** (Ash Wednesday, Palm Sunday, Gaudete/Laetare Sundays, Holy Thursday, Good Friday, Easter, Pentecost, fixed solemnities…) — automatically, every day
- **Upcoming parish events** entered by any admin
- **Recent events** (past 30 days) so the page never looks stale
- **Next-season countdown** so visitors can anticipate what's coming
- **Lectionary cycle** (Sunday A/B/C, Weekday I/II) for ministers
- **Weekly bulletin PDF** download link in the current liturgical colour
- **Mass times** configurable in Settings

### Quick install

1. Copy `wp-plugin/parish-liturgical-calendar/` to `/wp-content/plugins/` on the WordPress server
2. Activate in **WP Admin → Plugins**
3. Configure in **Settings → Parish Calendar**
4. Add `[parish_dashboard]` to the home page

See the full [plugin README](wp-plugin/parish-liturgical-calendar/README.md) for detailed instructions.
