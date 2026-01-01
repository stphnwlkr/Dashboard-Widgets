# Dashboard Area Grid (WPCodeBox Snippet)

Arranges **WordPress Dashboard widget areas (drop zones)** using **CSS Grid**, and adds **Screen Options** controls to:

- choose **1–4 columns** for the Dashboard widget areas
- set a **column span** per widget area (drop zone)

> This snippet targets **Dashboard widget areas**, not individual widgets. Widgets remain 100% width within their assigned area and keep native WordPress drag-and-drop behavior.

---

## What this changes

WordPress Dashboard is composed of:

- **Dashboard canvas**: the page
- **Widget areas / drop zones**: the columns (`.postbox-container`)
- **Widgets**: the draggable panels (`.postbox`)

This snippet turns the container of widget areas (`#dashboard-widgets`) into a CSS Grid, and treats each `.postbox-container` (drop zone) as a grid item.

---

## Features

- ✅ **CSS Grid layout** for Dashboard widget areas
- ✅ **User setting** for **1–4 columns** (saved per user)
- ✅ **Per-area span setting** (saved per user)
- ✅ Keeps **native widget behavior** inside each drop zone (sorting/dragging, full-width widgets)
- ✅ Hides “Drag boxes here” when a drop zone contains widgets (uses `:has()` when supported, with JS fallback)

---

## Limitations

- WordPress core provides **up to 4 Dashboard widget areas** (drop zones), so this snippet caps columns and spans to **1–4**.
- This snippet **does not create additional widget areas** beyond the core-provided ones.
- Spans are applied to the widget area containers by ID (e.g., `postbox-container-1`).

---

## Installation (WPCodeBox)

1. Create a new **PHP** snippet in WPCodeBox.
2. Paste the full PHP code for `FWE_Dashboard_Area_Grid_V2`.
3. Set it to run in the **Admin** context.
4. Save and enable the snippet.

---

## How to use

1. Go to **Dashboard → Screen Options** (top-right).
2. Set **Widget area columns** (1–4).  
   The page will refresh and the grid will update.
3. Under **Widget area spans**, set the span for each area.  
   The page will refresh and the area will expand to span multiple grid columns.

---

## Developer customization

### Filters

You can override or tweak behavior using these filters:

```php
// Default column count (used when a user has no saved preference)
add_filter('fwe_dash_area_grid_default_cols', function () {
    return 3;
});

// Force column count for everyone (overrides user preference)
add_filter('fwe_dash_area_grid_cols', function ($cols) {
    return 3;
});

// Gap between widget areas (grid gap)
add_filter('fwe_dash_area_grid_gap', function () {
    return '12px';
});

// Vertical gap between widgets within an area
add_filter('fwe_dash_area_stack_gap', function () {
    return '12px';
});

// Enable/disable the per-area span UI in Screen Options
add_filter('fwe_dash_area_grid_span_ui_enabled', function () {
    return true;
});

// Programmatic spans (keyed by container id)
add_filter('fwe_dash_area_grid_spans', function ($spans) {
    // Example: make the first area span 2 columns
    $spans['postbox-container-1'] = 2;
    return $spans;
});
```

### CSS variable targeting (alternative to filters)

Spans are applied via a CSS variable:

- `--fwe-area-col-span`

You can set it directly in custom admin CSS:

```css
/* Make the first widget area span 2 columns */
#postbox-container-1 { --fwe-area-col-span: 2; }
```

---

## Data storage (per-user)

Settings are stored in **user meta**:

- `fwe_dash_area_grid_cols` — integer 1–4
- `fwe_dash_area_grid_spans` — associative array: `{ "postbox-container-1": 2, ... }`

To reset a user’s layout, delete those user meta keys.

---

## Compatibility notes

- Uses `:has()` for placeholder hiding where supported; includes a MutationObserver + sortable event fallback.
- Designed for WordPress Dashboard (`index.php`) only.
- Tested conceptually with the standard `#dashboard-widgets` markup. Admin themes/plugins that heavily rewrite Dashboard markup may require selector tweaks.

---

## License

Use freely within your projects. If you redistribute, keep attribution in the header comments of the snippet.

---

## Credits

Built as a WPCodeBox snippet for a CSS Grid-based Dashboard widget-area layout with per-user preferences.


GPL-2.0-or-later  
© 2026 Stephen Walker

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.