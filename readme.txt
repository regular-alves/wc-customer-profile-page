=== Customer Profile Page for WooCommerce ===
Contributors: regularalves
Tags: woocommerce, customer, profile, crm, orders
Requires at least: 6.4
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 8.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a dedicated customer profile page to the WooCommerce admin, with KPIs, recent orders, contact details, and internal notes at a glance.

== Description ==

**Customer Profile Page for WooCommerce** replaces the generic WordPress user-edit screen with a purpose-built profile page designed for store managers.

When you click on a customer name anywhere inside WooCommerce — orders list, reports, or the admin panel — you land on a clean profile page that shows everything you need without digging through multiple screens.

**What you get on each profile:**

* **Contact card** — name, email (with one-click copy), phone (with one-click copy and direct WhatsApp link), and billing address.
* **Location map** — a click-to-load map built from the customer's billing address so you can instantly see where they are. The map is powered by Google Maps and only loads after you click the "View on map" button — nothing is sent to Google on page load.
* **Key metrics** — total spent, average order value, total order count, date of first order, and average interval between orders.
* **Recent orders table** — the last orders with status badges and direct links to each order.
* **Internal notes** — add private notes to any customer profile. Notes support rich-text formatting (bold, italic, underline, bullet and numbered lists, and links), can be searched by keyword, filtered by author, and are paginated. Each team member can edit or delete only their own notes.
* **Quick actions** — a direct link to the standard WordPress user-edit page whenever you need to change account details.

The plugin intercepts all WooCommerce entry points that normally link to `user-edit.php` and silently redirects them to the new profile page, so your team never has to remember a separate URL.

== External Services ==

This plugin optionally connects to **Google Maps** (maps.google.com) to display a location map based on the customer's billing address.

* The map is only loaded after an explicit click on the "View on map" button. No data is sent to Google automatically on page load.
* By clicking "View on map", the customer's billing address is sent to Google as part of the map request.
* [Google Privacy Policy](https://policies.google.com/privacy)
* [Google Terms of Service](https://policies.google.com/terms)

== Installation ==

1. Upload the `customer-profile-page-for-woocommerce` folder to the `/wp-content/plugins/` directory, or install it directly from the WordPress plugin directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Make sure WooCommerce is installed and active.
4. Open any order in WooCommerce and click the customer name — you will be taken to the new profile page.

== Frequently Asked Questions ==

= Does this replace the default user-edit page? =

No. The plugin adds a new admin page and redirects WooCommerce-specific links to it. The standard WordPress user management screens remain untouched.

= What capability is required to view the profile page? =

The page requires the `manage_woocommerce` capability, the same one used to access the WooCommerce admin area.

= Is it compatible with WooCommerce HPOS (High-Performance Order Storage)? =

Yes. The plugin explicitly declares compatibility with WooCommerce's Custom Order Tables (HPOS).

= Who can see the internal notes? =

Notes are visible to any user with the `manage_woocommerce` capability. Only the author of a note can edit or delete it.

= Are notes private to each store? =

Yes. Notes are stored in the site's own database and are never shared or synced externally.

== Screenshots ==

1. Customer profile page showing contact details, location map, and key metrics.
2. Recent orders table with status badges and direct order links.
3. Internal notes panel with rich-text editor, search, and author filter.

== Changelog ==

= 1.1.0 =
* Added internal notes — per-customer private notes with rich-text formatting, keyword search, author filter, and pagination.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.0 =
Adds the internal notes feature. No action required after updating — the notes table is created automatically on first use.
