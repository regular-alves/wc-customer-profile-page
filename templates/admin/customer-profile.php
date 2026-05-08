<?php
/**
 * Customer profile page template.
 *
 * @package WCCustomerProfilePage
 *
 * @var \WC_Customer                                              $wccp_customer
 * @var array{total_spent: float, order_count: int, avg_order: float} $wccp_kpis
 * @var \WC_Order[]                                              $wccp_recent_orders
 * @var string                                                   $wccp_orders_url
 * @var int                                                      $wccp_user_id
 * @var \WC_DateTime|null                                        $wccp_first_order_date
 * @var int|null                                                 $wccp_avg_order_interval
 */

defined( 'ABSPATH' ) || exit;

$wccp_customer           = $args['customer'] ?? null;
$wccp_kpis               = $args['kpis'] ?? [];
$wccp_recent_orders      = $args['recent_orders'] ?? [];
$wccp_orders_url         = $args['orders_url'] ?? '';
$wccp_user_id            = (int) ( $args['user_id'] ?? 0 );
$wccp_first_order_date   = $args['first_order_date'] ?? null;
$wccp_avg_order_interval = $args['avg_order_interval'] ?? null;
if ( ! $wccp_customer instanceof \WC_Customer ) {
	return;
}

$wccp_first_name  = $wccp_customer->get_first_name();
$wccp_last_name   = $wccp_customer->get_last_name();
$wccp_full_name   = trim( $wccp_first_name . ' ' . $wccp_last_name );
$wccp_email       = $wccp_customer->get_email();
$wccp_phone       = $wccp_customer->get_billing_phone();
$wccp_user_data   = get_userdata( $wccp_user_id );
$wccp_registered  = $wccp_user_data ? $wccp_user_data->user_registered : '';

$wccp_initials         = strtoupper( substr( $wccp_first_name, 0, 1 ) . substr( $wccp_last_name, 0, 1 ) );
$wccp_hue              = $wccp_user_id % 360;
$wccp_whatsapp_prefix  = \WCCustomerProfilePage\Customer\CustomerData::get_phone_country_prefix( $wccp_customer->get_billing_country() );

$wccp_billing_address = implode(
	', ',
	array_filter(
		[
			$wccp_customer->get_billing_address_1(),
			$wccp_customer->get_billing_address_2(),
			$wccp_customer->get_billing_city(),
			$wccp_customer->get_billing_state(),
			$wccp_customer->get_billing_postcode(),
			$wccp_customer->get_billing_country(),
		]
	)
);
?>
<div class="wrap wccp-profile-wrap">

	<h1 class="wp-heading-inline">
		<?php echo esc_html( $wccp_full_name ?: __( '(no name)', 'customer-profile-page-for-woocommerce' ) ); ?>
	</h1>

	<a href="<?php echo esc_url( add_query_arg( 'user_id', $wccp_user_id, admin_url( 'user-edit.php' ) ) ); ?>"
	   class="page-title-action button button-primary">
		<?php esc_html_e( 'Edit customer', 'customer-profile-page-for-woocommerce' ); ?>
	</a>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wccp-header-action">
		<input type="hidden" name="action" value="wccp_send_password_reset">
		<input type="hidden" name="user_id" value="<?php echo esc_attr( $wccp_user_id ); ?>">
		<?php wp_nonce_field( 'wccp_send_password_reset' ); ?>
		<button type="submit" class="page-title-action">
			<?php esc_html_e( 'Send password reset', 'customer-profile-page-for-woocommerce' ); ?>
		</button>
	</form>

	<hr class="wp-header-end">

	<?php
	$wccp_reset_status = isset( $_GET['wccp_reset'] ) ? sanitize_key( wp_unslash( $_GET['wccp_reset'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( 'sent' === $wccp_reset_status ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Password reset email sent successfully.', 'customer-profile-page-for-woocommerce' ); ?></p>
		</div>
	<?php elseif ( 'error' === $wccp_reset_status ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'Failed to send password reset email. Please try again.', 'customer-profile-page-for-woocommerce' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="wccp-profile-layout">
	<div class="wccp-header">
		<div class="wccp-avatar" aria-hidden="true">
			<?php
			$wccp_avatar = get_avatar( $wccp_email, 80, '', esc_attr( $wccp_full_name ), [ 'force_default' => false ] );
			if ( $wccp_avatar ) {
				echo $wccp_avatar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_avatar returns safe HTML
			} else {
				echo esc_html( $wccp_initials ?: '?' );
			}
			?>
		</div>

		<div class="wccp-contact">
			<p class="wccp-email">
				<a href="<?php echo esc_url( 'mailto:' . $wccp_email ); ?>" class="wccp-contact-data"><?php echo esc_html( $wccp_email ); ?></a>
				<button class="wccp-copy" data-copy="<?php echo esc_attr( $wccp_email ); ?>" type="button">
					<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M16 1H4C2.9 1 2 1.9 2 3v14h2V3h12V1zm3 4H8C6.9 5 6 5.9 6 7v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>
					<span class="screen-reader-text"><?php esc_html_e( 'Copy email', 'customer-profile-page-for-woocommerce' ); ?></span>
				</button>
			</p>

			<?php if ( $wccp_phone ) : ?>
				<p class="wccp-phone">
					<a href="<?php echo esc_url( 'tel:' . $wccp_phone ); ?>" class="wccp-contact-data">
						<?php echo esc_html( $wccp_phone ); ?>
					</a>
					<?php if ( $wccp_whatsapp_prefix ) : ?>
					<a
						href="<?php echo esc_url( 'https://wa.me/' . $wccp_whatsapp_prefix . preg_replace( '/\D/', '', $wccp_phone ) ); ?>"
						target="_blank"
						rel="noopener noreferrer"
						class="wccp-whatsapp"
					>
						<svg fill="currentColor" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M26.576 5.363c-2.69-2.69-6.406-4.354-10.511-4.354-8.209 0-14.865 6.655-14.865 14.865 0 2.732 0.737 5.291 2.022 7.491l-0.038-0.070-2.109 7.702 7.879-2.067c2.051 1.139 4.498 1.809 7.102 1.809h0.006c8.209-0.003 14.862-6.659 14.862-14.868 0-4.103-1.662-7.817-4.349-10.507l0 0zM16.062 28.228h-0.005c-0 0-0.001 0-0.001 0-2.319 0-4.489-0.64-6.342-1.753l0.056 0.031-0.451-0.267-4.675 1.227 1.247-4.559-0.294-0.467c-1.185-1.862-1.889-4.131-1.889-6.565 0-6.822 5.531-12.353 12.353-12.353s12.353 5.531 12.353 12.353c0 6.822-5.53 12.353-12.353 12.353h-0zM22.838 18.977c-0.371-0.186-2.197-1.083-2.537-1.208-0.341-0.124-0.589-0.185-0.837 0.187-0.246 0.371-0.958 1.207-1.175 1.455-0.216 0.249-0.434 0.279-0.805 0.094-1.15-0.466-2.138-1.087-2.997-1.852l0.010 0.009c-0.799-0.74-1.484-1.587-2.037-2.521l-0.028-0.052c-0.216-0.371-0.023-0.572 0.162-0.757 0.167-0.166 0.372-0.434 0.557-0.65 0.146-0.179 0.271-0.384 0.366-0.604l0.006-0.017c0.043-0.087 0.068-0.188 0.068-0.296 0-0.131-0.037-0.253-0.101-0.357l0.002 0.003c-0.094-0.186-0.836-2.014-1.145-2.758-0.302-0.724-0.609-0.625-0.836-0.637-0.216-0.010-0.464-0.012-0.712-0.012-0.395 0.010-0.746 0.188-0.988 0.463l-0.001 0.002c-0.802 0.761-1.3 1.834-1.3 3.023 0 0.026 0 0.053 0.001 0.079l-0-0.004c0.131 1.467 0.681 2.784 1.527 3.857l-0.012-0.015c1.604 2.379 3.742 4.282 6.251 5.564l0.094 0.043c0.548 0.248 1.25 0.513 1.968 0.74l0.149 0.041c0.442 0.14 0.951 0.221 1.479 0.221 0.303 0 0.601-0.027 0.889-0.078l-0.031 0.004c1.069-0.223 1.956-0.868 2.497-1.749l0.009-0.017c0.165-0.366 0.261-0.793 0.261-1.242 0-0.185-0.016-0.366-0.047-0.542l0.003 0.019c-0.092-0.155-0.34-0.247-0.712-0.434z"/></svg>
						<span class="screen-reader-text"><?php esc_html_e( 'WhatsApp', 'customer-profile-page-for-woocommerce' ); ?></span>
					</a>
					<?php endif; ?>
					<button class="wccp-copy" data-copy="<?php echo esc_attr( $wccp_phone ); ?>" type="button">
						<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M16 1H4C2.9 1 2 1.9 2 3v14h2V3h12V1zm3 4H8C6.9 5 6 5.9 6 7v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>
						<span class="screen-reader-text"><?php esc_html_e( 'Copy phone', 'customer-profile-page-for-woocommerce' ); ?></span>
					</button>
				</p>
			<?php else : ?>
				<p class="wccp-phone wccp-phone--empty">
					<?php esc_html_e( '—', 'customer-profile-page-for-woocommerce' ); ?>
				</p>
			<?php endif; ?>

			<?php if ( $wccp_billing_address ) : ?>
				<p class="wccp-address"><?php echo esc_html( $wccp_billing_address ); ?></p>
			<?php endif; ?>

			<?php if ( $wccp_registered ) : ?>
				<p class="wccp-registered">
					<?php
					printf(
						/* translators: %s: registration date */
						esc_html__( 'Customer since %s', 'customer-profile-page-for-woocommerce' ),
						esc_html( date_i18n( get_option( 'date_format' ), strtotime( $wccp_registered ) ) )
					);
					?>
				</p>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( $wccp_billing_address ) : ?>
	<div class="wccp-map">
		<div
			class="wccp-map-placeholder"
			data-address="<?php echo esc_attr( $wccp_billing_address ); ?>"
			data-title="<?php esc_attr_e( 'Customer location', 'customer-profile-page-for-woocommerce' ); ?>"
		>
			<span class="dashicons dashicons-location-alt" aria-hidden="true"></span>
			<button type="button" class="wccp-map-load button">
				<?php esc_html_e( 'View on map', 'customer-profile-page-for-woocommerce' ); ?>
			</button>
			<small class="wccp-map-notice">
				<?php esc_html_e( 'Loads Google Maps — address will be sent to Google.', 'customer-profile-page-for-woocommerce' ); ?>
			</small>
		</div>
	</div>
	<?php endif; ?>

	</div>

	<div class="wccp-kpis">
		<div class="wccp-kpi-card">
			<span class="wccp-kpi-label"><?php esc_html_e( 'Total spent', 'customer-profile-page-for-woocommerce' ); ?></span>
			<span class="wccp-kpi-value"><?php echo wp_kses_post( wc_price( $wccp_kpis['total_spent'] ?? 0 ) ); ?></span>
		</div>
		<div class="wccp-kpi-card">
			<span class="wccp-kpi-label"><?php esc_html_e( 'Average order', 'customer-profile-page-for-woocommerce' ); ?></span>
			<span class="wccp-kpi-value"><?php echo wp_kses_post( wc_price( $wccp_kpis['avg_order'] ?? 0 ) ); ?></span>
		</div>
		<div class="wccp-kpi-card">
			<span class="wccp-kpi-label"><?php esc_html_e( 'Orders', 'customer-profile-page-for-woocommerce' ); ?></span>
			<span class="wccp-kpi-value"><?php echo esc_html( (string) ( $wccp_kpis['order_count'] ?? 0 ) ); ?></span>
		</div>
		<div class="wccp-kpi-card">
			<span class="wccp-kpi-label"><?php esc_html_e( 'First order', 'customer-profile-page-for-woocommerce' ); ?></span>
			<span class="wccp-kpi-value">
				<?php
				echo esc_html(
					$wccp_first_order_date instanceof \WC_DateTime
						? $wccp_first_order_date->date_i18n( get_option( 'date_format' ) )
						: '—'
				);
				?>
			</span>
		</div>
		<div class="wccp-kpi-card">
			<span class="wccp-kpi-label"><?php esc_html_e( 'Avg. interval', 'customer-profile-page-for-woocommerce' ); ?></span>
			<span class="wccp-kpi-value">
				<?php
				if ( null !== $wccp_avg_order_interval ) {
					printf(
						/* translators: %d: number of days */
						esc_html__( 'every %d days', 'customer-profile-page-for-woocommerce' ),
						(int) $wccp_avg_order_interval
					);
				} else {
					echo '—';
				}
				?>
			</span>
		</div>
	</div>

	<div class="postbox wccp-orders-box">
		<div class="postbox-header">
			<h2 class="hndle"><?php esc_html_e( 'Recent orders', 'customer-profile-page-for-woocommerce' ); ?></h2>
		</div>
		<div class="inside">
			<?php if ( $wccp_recent_orders ) : ?>
				<table class="wp-list-table widefat fixed striped wccp-orders-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Order', 'customer-profile-page-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Date', 'customer-profile-page-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Status', 'customer-profile-page-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Total', 'customer-profile-page-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $wccp_recent_orders as $wccp_order ) : ?>
							<tr>
								<td>
									<a href="<?php echo esc_url( $wccp_order->get_edit_order_url() ); ?>">
										#<?php echo esc_html( (string) $wccp_order->get_order_number() ); ?>
									</a>
								</td>
								<td>
									<?php echo esc_html( $wccp_order->get_date_created() ? $wccp_order->get_date_created()->date_i18n( get_option( 'date_format' ) ) : '—' ); ?>
								</td>
								<td>
									<mark class="order-status status-<?php echo esc_attr( $wccp_order->get_status() ); ?>">
										<span><?php echo esc_html( wc_get_order_status_name( $wccp_order->get_status() ) ); ?></span>
									</mark>
								</td>
								<td><?php echo wp_kses_post( $wccp_order->get_formatted_order_total() ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p><?php esc_html_e( 'No orders found.', 'customer-profile-page-for-woocommerce' ); ?></p>
			<?php endif; ?>

			<p class="wccp-orders-footer">
				<a href="<?php echo esc_url( $wccp_orders_url ); ?>" class="button">
					<?php esc_html_e( 'View all orders', 'customer-profile-page-for-woocommerce' ); ?>
				</a>
			</p>
		</div>
	</div>

	<div class="postbox wccp-notes-box">
		<div class="postbox-header">
			<h2 class="hndle"><?php esc_html_e( 'Notes', 'customer-profile-page-for-woocommerce' ); ?></h2>
		</div>
		<div class="inside wccp-notes-layout">

			<div class="wccp-notes-form-col">
				<div class="wccp-rte-toolbar">
					<button type="button" class="wccp-rte-btn" data-cmd="bold" title="<?php esc_attr_e( 'Bold', 'customer-profile-page-for-woocommerce' ); ?>"><span class="dashicons dashicons-editor-bold"></span></button>
					<button type="button" class="wccp-rte-btn" data-cmd="italic" title="<?php esc_attr_e( 'Italic', 'customer-profile-page-for-woocommerce' ); ?>"><span class="dashicons dashicons-editor-italic"></span></button>
					<button type="button" class="wccp-rte-btn wccp-rte-btn--text" data-cmd="underline" title="<?php esc_attr_e( 'Underline', 'customer-profile-page-for-woocommerce' ); ?>"><u>U</u></button>
					<span class="wccp-rte-sep"></span>
					<button type="button" class="wccp-rte-btn" data-cmd="insertUnorderedList" title="<?php esc_attr_e( 'Bullet list', 'customer-profile-page-for-woocommerce' ); ?>"><span class="dashicons dashicons-editor-ul"></span></button>
					<button type="button" class="wccp-rte-btn" data-cmd="insertOrderedList" title="<?php esc_attr_e( 'Numbered list', 'customer-profile-page-for-woocommerce' ); ?>"><span class="dashicons dashicons-editor-ol"></span></button>
					<span class="wccp-rte-sep"></span>
					<button type="button" class="wccp-rte-btn" data-cmd="createLink" title="<?php esc_attr_e( 'Link', 'customer-profile-page-for-woocommerce' ); ?>"><span class="dashicons dashicons-admin-links"></span></button>
				</div>
				<form id="wccp-add-note-form" data-customer-id="<?php echo esc_attr( $wccp_user_id ); ?>">
					<div
						id="wccp-rte-editor"
						class="wccp-rte-editor"
						contenteditable="true"
						data-placeholder="<?php esc_attr_e( 'Add a note…', 'customer-profile-page-for-woocommerce' ); ?>"
					></div>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Add note', 'customer-profile-page-for-woocommerce' ); ?></button>
				</form>
			</div>

			<div class="wccp-notes-list-col">
				<div class="wccp-notes-controls">
					<input
						type="search"
						id="wccp-notes-search"
						class="regular-text"
						placeholder="<?php esc_attr_e( 'Search notes…', 'customer-profile-page-for-woocommerce' ); ?>"
					/>
					<select id="wccp-notes-author">
						<option value=""><?php esc_html_e( 'All authors', 'customer-profile-page-for-woocommerce' ); ?></option>
					</select>
				</div>
				<div id="wccp-notes-list"></div>
				<nav
					class="wccp-notes-pagination"
					id="wccp-notes-pagination"
					hidden
					aria-label="<?php esc_attr_e( 'Notes pagination', 'customer-profile-page-for-woocommerce' ); ?>"
				>
					<button type="button" class="button wccp-page-prev"><?php esc_html_e( '← Previous', 'customer-profile-page-for-woocommerce' ); ?></button>
					<span class="wccp-page-info"></span>
					<button type="button" class="button wccp-page-next"><?php esc_html_e( 'Next →', 'customer-profile-page-for-woocommerce' ); ?></button>
				</nav>
			</div>

		</div>
	</div>

</div>
