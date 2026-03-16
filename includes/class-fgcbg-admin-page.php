<?php
/**
 * Admin page rendering.
 *
 * @package FreeGiftCouponsBulkGenerator
 * @since   1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the plugin admin page and manages the product dropdown cache.
 *
 * @since 1.6.0
 */
class FGCBG_Admin_Page {

	/**
	 * Render the admin page.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	public function render() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Free Gift Coupons Bulk Coupon Generator', 'free-gift-coupons-bulk-coupons-generator' ); ?></h1>
			<p><?php esc_html_e( 'Generate bulk free gift coupons that work with the Free Gift Coupons for WooCommerce plugin. These coupons are created with the proper data structure required for free gift functionality.', 'free-gift-coupons-bulk-coupons-generator' ); ?></p>

			<div class="scg-admin-container">
				<div class="scg-main-content">
					<?php $this->render_admin_form(); ?>
				</div>

				<div class="scg-sidebar">
					<?php $this->render_admin_sidebar(); ?>
				</div>
			</div>

			<?php $this->render_admin_footer(); ?>
		</div>
		<?php
	}

	/**
	 * Render admin form.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_admin_form() {
		?>
		<form class="scg-form">

			<table class="form-table">
				<?php $this->render_product_selection_field(); ?>
				<?php $this->render_coupon_count_field(); ?>
				<?php $this->render_coupon_prefix_field(); ?>
				<?php $this->render_discount_type_field(); ?>
			</table>

			<p class="submit">
				<button type="submit" class="button-primary">
					<?php esc_html_e( 'Generate Free Gift Coupons', 'free-gift-coupons-bulk-coupons-generator' ); ?>
				</button>
			</p>

			<div id="fgcbg-progress" class="fgcbg-progress" style="display: none;">
				<div class="fgcbg-progress-track">
					<div id="fgcbg-progress-bar" class="fgcbg-progress-bar" style="width: 0%;"></div>
				</div>
				<p id="fgcbg-progress-text" class="fgcbg-progress-text"></p>
			</div>
		</form>
		<?php
	}

	/**
	 * Render product selection field.
	 *
	 * Uses WooCommerce's built-in AJAX product search (Select2) for scalability.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_product_selection_field() {
		?>
		<tr>
			<th scope="row">
				<label for="product_id"><?php esc_html_e( 'Select Products', 'free-gift-coupons-bulk-coupons-generator' ); ?></label>
			</th>
			<td>
				<select class="wc-product-search" multiple="multiple" style="width: 300px;" id="product_id" name="product_id[]"
						data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'free-gift-coupons-bulk-coupons-generator' ); ?>"
						data-action="woocommerce_json_search_products"
						data-allow_clear="true"
						aria-describedby="product-id-description">
				</select>
				<p class="description" id="product-id-description">
					<?php esc_html_e( 'Search and select one or more products that will be given as free gifts with the coupon.', 'free-gift-coupons-bulk-coupons-generator' ); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render coupon count field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_coupon_count_field() {
		?>
		<tr>
			<th scope="row">
				<label for="number_of_coupons"><?php esc_html_e( 'Number of Coupons', 'free-gift-coupons-bulk-coupons-generator' ); ?></label>
			</th>
			<td>
				<input type="number" name="number_of_coupons" id="number_of_coupons"
					   class="regular-text" min="1" max="100" value="10" required aria-describedby="coupon-count-description">
				<p class="description" id="coupon-count-description">
					<?php esc_html_e( 'Enter the number of coupons to generate (maximum 100).', 'free-gift-coupons-bulk-coupons-generator' ); ?>
				</p>
				<div class="scg-warning-box">
					<p class="scg-warning-text">
						<span class="dashicons dashicons-warning scg-warning-icon"></span>
						<?php esc_html_e( 'Note: Coupon generation can be time-consuming. Generating large numbers of coupons may cause the page to timeout based on your server\'s PHP timeout settings. If you need to generate many coupons, consider doing it in smaller batches.', 'free-gift-coupons-bulk-coupons-generator' ); ?>
					</p>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render coupon prefix field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_coupon_prefix_field() {
		?>
		<tr>
			<th scope="row">
				<label for="coupon_prefix"><?php esc_html_e( 'Coupon Prefix', 'free-gift-coupons-bulk-coupons-generator' ); ?></label>
			</th>
			<td>
				<input type="text" name="coupon_prefix" id="coupon_prefix"
					   class="regular-text" maxlength="10" placeholder="e.g. GIFT" aria-describedby="coupon-prefix-description">
				<p class="description" id="coupon-prefix-description">
					<?php esc_html_e( 'Optional prefix for coupon codes (e.g. GIFT-ABC123DEF456).', 'free-gift-coupons-bulk-coupons-generator' ); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render discount type field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_discount_type_field() {
		?>
		<tr>
			<th scope="row">
				<label for="discount_type"><?php esc_html_e( 'Discount Type', 'free-gift-coupons-bulk-coupons-generator' ); ?></label>
			</th>
			<td>
				<select name="discount_type" id="discount_type" class="regular-text" aria-describedby="discount-type-description">
					<option value="free_gift"><?php esc_html_e( 'Free Gift', 'free-gift-coupons-bulk-coupons-generator' ); ?></option>
					<option value="percent"><?php esc_html_e( 'Percentage Discount', 'free-gift-coupons-bulk-coupons-generator' ); ?></option>
					<option value="fixed_cart"><?php esc_html_e( 'Fixed Cart Discount', 'free-gift-coupons-bulk-coupons-generator' ); ?></option>
					<option value="fixed_product"><?php esc_html_e( 'Fixed Product Discount', 'free-gift-coupons-bulk-coupons-generator' ); ?></option>
				</select>
				<p class="description" id="discount-type-description">
					<?php esc_html_e( 'Select the type of discount for the coupons.', 'free-gift-coupons-bulk-coupons-generator' ); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render admin sidebar.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_admin_sidebar() {
		?>
		<div class="scg-info-box">
			<h3><?php esc_html_e( 'Information', 'free-gift-coupons-bulk-coupons-generator' ); ?></h3>
			<ul>
				<li><?php esc_html_e( 'Maximum 100 coupons can be generated at once', 'free-gift-coupons-bulk-coupons-generator' ); ?></li>
				<li><?php esc_html_e( 'Coupons are set to expire after 1 year', 'free-gift-coupons-bulk-coupons-generator' ); ?></li>
				<li><?php esc_html_e( 'Each coupon can only be used once', 'free-gift-coupons-bulk-coupons-generator' ); ?></li>
				<li><?php esc_html_e( 'Coupons are set for individual use only', 'free-gift-coupons-bulk-coupons-generator' ); ?></li>
				<li><?php esc_html_e( 'Generated coupons appear in WooCommerce > Coupons', 'free-gift-coupons-bulk-coupons-generator' ); ?></li>
			</ul>
		</div>
		<?php
	}

	/**
	 * Render admin footer.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_admin_footer() {
		?>
		<div class="scg-footer">
			<p class="scg-repo-link">
				<a href="https://github.com/EngineScript/free-gift-coupons-bulk-coupons-generator" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'View on GitHub', 'free-gift-coupons-bulk-coupons-generator' ); ?>
				</a>
				|
				<a href="https://github.com/EngineScript/free-gift-coupons-bulk-coupons-generator/issues" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Report Issues', 'free-gift-coupons-bulk-coupons-generator' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}
