=== Letterboxd Movies Block ===
Contributors:      serhiikorolchuk
Tags:              letterboxd, movies, films, block, gutenberg
Requires at least: 6.3
Tested up to:      6.9
Stable tag:        1.0.0
Requires PHP:      7.4
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Displays your recently watched Letterboxd movies as a configurable Gutenberg block.

== Description ==

Add the **Letterboxd Movies** block to any post or page to showcase your recently watched films pulled live from your public Letterboxd RSS feed.

**Features:**

* Live server-side preview in the block editor
* Choose the number of grid columns (1–6)
* Choose how many recent films to display (1–20)
* Toggle poster image, title, and star rating visibility independently
* Movie data cached for 30 minutes to avoid repeated RSS requests
* Cache can be cleared manually from the settings page

**Setup:**

1. Install and activate the plugin.
2. Go to **Settings → Letterboxd Movies** and enter your Letterboxd username.
3. Insert the **Letterboxd Movies** block from the block inserter (Widgets category).

== Installation ==

1. Upload the `letterboxd-movies-block` folder to the `/wp-content/plugins/` directory, or install the plugin directly from the WordPress plugin directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings → Letterboxd Movies** and enter your Letterboxd username.
4. Add the **Letterboxd Movies** block to any post or page.

== Frequently Asked Questions ==

= Does this require a Letterboxd account? =

Yes. You need a public Letterboxd account. The plugin reads your public RSS feed — no API key is required.

= How often is the data updated? =

Movie data is fetched from Letterboxd's RSS feed and cached for 30 minutes. You can clear the cache manually from **Settings → Letterboxd Movies**.

= Can I style the block to match my theme? =

Yes. The block uses BEM-like CSS classes (`lbm-grid`, `lbm-card`, `lbm-title`, `lbm-rating`) that you can override in your theme or via the Additional CSS panel. The rating colour uses your theme's `--wp--preset--color--accent` CSS variable by default, with an orange fallback.

= Why are no movies showing? =

Make sure your Letterboxd profile is public and that your username is entered correctly in **Settings → Letterboxd Movies**. You can verify by visiting `https://letterboxd.com/yourusername/rss/` in a browser.

== Screenshots ==

1. The Letterboxd Movies block on the front end — 4-column grid with poster, title, and star rating.
2. Block editor with the Display Settings panel open in the sidebar.
3. Settings page for entering your Letterboxd username.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
