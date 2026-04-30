# Customer Profile Page for WooCommerce

Adds a dedicated customer profile page to the WooCommerce admin, with KPIs, recent orders, and contact details at a glance.

## Features

- **Contact card** — name, email (one-click copy), phone (one-click copy + WhatsApp link), and billing address
- **Location map** — embedded map from the customer's billing address
- **Key metrics** — total spent, average order value, order count, first order date, average interval between orders
- **Recent orders table** — last orders with status badges and direct links
- **Seamless navigation** — all WooCommerce customer links redirect to the profile page automatically

## Requirements

- WordPress 6.4+
- WooCommerce 9.0+
- PHP 8.0+

## Installation

```bash
composer install
```

Activate the plugin through the WordPress admin or via WP-CLI:

```bash
wp plugin activate customer-profile-page-for-woocommerce
```

## Development

```bash
composer test       # run unit tests
composer lint       # run PHPCS
```

## License

GPL-2.0-or-later — see [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).
