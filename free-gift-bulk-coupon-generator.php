<?php
/**
 * Plugin Name: WC Free Gift Coupons Bulk Coupon Generator
 * Plugin URI: https://github.com/EngineScript/wc-free-gift-coupons-bulk-coupons-generator
 * Description: Generate bulk free gift coupon codes that work with the Free Gift Coupons for WooCommerce plugin. Creates coupons with the proper data structure for free gift functionality.
 * Version: 1.5.0
 * Author: EngineScript
 * Requires at least: 6.5
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wc-free-gift-coupons-bulk-coupons-generator
 * Domain Path: /languages
 *
 * @package wc-free-gift-coupons-bulk-coupons-generator
 *
 * CODACY FALSE POSITIVE: WordPress plugin filename convention
 * Main plugin files use descriptive names, not class-prefixed names.
 * The filename free-gift-bulk-coupon-generator.php follows WordPress standards.
 * Class naming and file naming serve different purposes in WordPress plugins.
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'SCG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SCG_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'SCG_PLUGIN_VERSION', '1.5.0' );

/**
 * Main plugin class
 */
class WooCommerceFreeGiftBulkCoupons {

	/**
	 * Plugin instance
	 *
	 * @var WooCommerceFreeGiftBulkCoupons
	 */
	private static $instance = null;

	/**
	 * Get plugin instance
	 *
	 * @return WooCommerceFreeGiftBulkCoupons
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		// Initialize the plugin immediately since plugins_loaded has already fired.
		$this->init();
	}

	/**
	 * Initialize plugin
	 */
	public function init() {
		// Check if WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		// Initialize admin functionality.
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		}
	}

	/**
	 * WooCommerce missing notice
	 */
	public function woocommerce_missing_notice() {
		$message = sprintf(
			/* translators: %s: WooCommerce download link */
			esc_html__( 'WC Free Gift Coupons Bulk Coupon Generator requires WooCommerce to be installed and active. You can download %s here.', 'wc-free-gift-coupons-bulk-coupons-generator' ),
			'<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>'
		);
		echo '<div class="error"><p>' . wp_kses_post( $message ) . '</p></div>';
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Free Gift Bulk Coupons', 'wc-free-gift-coupons-bulk-coupons-generator' ),
			__( 'Coupon Generator', 'wc-free-gift-coupons-bulk-coupons-generator' ),
			'manage_woocommerce',
			'free-gift-bulk-coupon-generator',
			array( $this, 'admin_page' )
		);
	}

	/**
	 * Initialize admin functionality
	 */
	public function admin_init() {
		// Handle form submission.
		if ( isset( $_POST['scg_generate_coupons'] ) && isset( $_POST['scg_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['scg_nonce'] ) ), 'scg_generate_coupons_action' ) ) {
			$this->handle_coupon_generation();
		}
	}

	/**
	 * Enqueue admin scripts and styles
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'woocommerce_page_free-gift-bulk-coupon-generator' !== $hook ) {
			return;
		}

		// Add security headers.
		if ( ! headers_sent() ) {
			header( 'X-Content-Type-Options: nosniff' );
			header( 'X-Frame-Options: SAMEORIGIN' );
		}

		wp_enqueue_script( 'scg-admin', SCG_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), SCG_PLUGIN_VERSION, true );
		wp_enqueue_style( 'scg-admin', SCG_PLUGIN_URL . 'assets/css/admin.css', array(), SCG_PLUGIN_VERSION );

		wp_localize_script(
			'scg-admin',
			'scg_ajax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'scg_ajax_nonce' ),
			)
		);
	}

	/**
	 * Handle coupon generation
	 */
	private function handle_coupon_generation() {
		// Verify nonce for security.
		if ( ! isset( $_POST['scg_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['scg_nonce'] ) ), 'scg_generate_coupons_action' ) ) {
			wp_die( esc_html__( 'Security check failed. Please try again.', 'wc-free-gift-coupons-bulk-coupons-generator' ) );
		}

		// Verify user capabilities.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to generate coupons.', 'wc-free-gift-coupons-bulk-coupons-generator' ) );
		}

		// Basic rate limiting - prevent multiple simultaneous requests.
		$transient_key = 'scg_generating_' . get_current_user_id();
		if ( get_transient( $transient_key ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error is-dismissible"><p>' .
						 esc_html__( 'Coupon generation already in progress. Please wait before starting another batch.', 'wc-free-gift-coupons-bulk-coupons-generator' ) .
						 '</p></div>';
				}
			);
			return;
		}

		// Set transient to prevent concurrent requests.
		set_transient( $transient_key, true, 300 ); // 5 minutes

		// Sanitize and validate input with proper unslashing.
		// Get form data and sanitize inputs.
		$product_ids       = isset( $_POST['product_id'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['product_id'] ) ) : array();
		$number_of_coupons = isset( $_POST['number_of_coupons'] ) ? absint( wp_unslash( $_POST['number_of_coupons'] ) ) : 0;
		$coupon_prefix     = isset( $_POST['coupon_prefix'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_prefix'] ) ) : '';
		$discount_type     = isset( $_POST['discount_type'] ) ? sanitize_text_field( wp_unslash( $_POST['discount_type'] ) ) : 'free_gift';

		// Validate discount type against allowed values.
		$allowed_discount_types = array( 'free_gift', 'percent', 'fixed_cart', 'fixed_product' );
		if ( ! in_array( $discount_type, $allowed_discount_types, true ) ) {
			$discount_type = 'free_gift'; // Default to safe value.
		}

		// Validate and sanitize coupon prefix.
		if ( ! empty( $coupon_prefix ) ) {
			$coupon_prefix = preg_replace( '/[^A-Za-z0-9]/', '', $coupon_prefix );
			$coupon_prefix = strtoupper( substr( $coupon_prefix, 0, 10 ) );
		}

		// Remove any empty values from product IDs.
		$product_ids = array_filter( $product_ids );

		// Validate inputs.
		if ( empty( $product_ids ) || empty( $number_of_coupons ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error is-dismissible"><p>' .
						esc_html__( 'Please select at least one product and specify the number of coupons to generate.', 'wc-free-gift-coupons-bulk-coupons-generator' ) .
						'</p></div>';
				}
			);
			return;
		}

		// Additional validation for product IDs.
		foreach ( $product_ids as $product_id ) {
			if ( $product_id <= 0 || $product_id > PHP_INT_MAX ) {
				add_action(
					'admin_notices',
					function () {
						echo '<div class="notice notice-error is-dismissible"><p>' .
							esc_html__( 'Invalid product selection. Please try again.', 'wc-free-gift-coupons-bulk-coupons-generator' ) .
							'</p></div>';
					}
				);
				return;
			}
		}

		if ( $number_of_coupons <= 0 || $number_of_coupons > 100 ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error is-dismissible"><p>' .
						esc_html__( 'Maximum number of coupons that can be generated at once is 100.', 'wc-free-gift-coupons-bulk-coupons-generator' ) .
						'</p></div>';
				}
			);
			return;
		}

		// Generate coupons.
		$generated_coupons = $this->generate_coupons( $product_ids, $number_of_coupons, $coupon_prefix, $discount_type );

		// Clear the rate limiting transient.
		delete_transient( $transient_key );

		if ( $generated_coupons > 0 ) {
			add_action(
				'admin_notices',
				function () use ( $generated_coupons ) {
					echo '<div class="notice notice-success is-dismissible"><p>' .
						sprintf(
							/* translators: %d: Number of coupons generated */
							esc_html__( 'Successfully generated %d coupons.', 'wc-free-gift-coupons-bulk-coupons-generator' ),
							esc_html( $generated_coupons )
						) .
						 '</p></div>';
				}
			);
		} else {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error is-dismissible"><p>' .
						 esc_html__( 'Failed to generate coupons. Please try again.', 'wc-free-gift-coupons-bulk-coupons-generator' ) .
						 '</p></div>';
				}
			);
		}
	}

	/**
	 * Generate coupons
	 *
	 * @param array  $product_ids      Array of product IDs to generate coupons for.
	 * @param int    $number_of_coupons Number of coupons to generate.
	 * @param string $prefix           Coupon prefix.
	 * @param string $discount_type    Type of discount.
	 * @return int Number of coupons generated.
	 */
	private function generate_coupons( $product_ids, $number_of_coupons, $prefix = '', $discount_type = 'free_gift' ) {
		$valid_products = $this->validate_products( $product_ids );
		if ( empty( $valid_products ) ) {
			return 0;
		}

		$generation_params = $this->prepare_generation_params( $number_of_coupons, $prefix, $discount_type );
		$gift_info         = $this->prepare_gift_info( $valid_products );

		// Fire before generation action.
		do_action( 'scg_before_coupon_generation', $product_ids, $generation_params['count'] );

		$generated_count = $this->execute_coupon_generation( $valid_products, $gift_info, $generation_params );

		// Fire after generation action.
		do_action( 'scg_after_coupon_generation', $product_ids, $generated_count );

		return $generated_count;
	}

	/**
	 * Validate products for coupon generation
	 *
	 * @param array|int $product_ids Product IDs to validate.
	 * @return array Array of valid product objects.
	 */
	private function validate_products( $product_ids ) {
		// Ensure product_ids is an array.
		if ( ! is_array( $product_ids ) ) {
			$product_ids = array( $product_ids );
		}

		// Validate all products exist.
		$valid_products = array();
		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$valid_products[ $product_id ] = $product;
			}
		}

		return $valid_products;
	}

	/**
	 * Prepare generation parameters
	 *
	 * @param int    $number_of_coupons Number of coupons to generate.
	 * @param string $prefix           Coupon prefix.
	 * @param string $discount_type    Type of discount.
	 * @return array Generation parameters array.
	 */
	private function prepare_generation_params( $number_of_coupons, $prefix, $discount_type ) {
		return array(
			'count'         => apply_filters( 'scg_max_coupons_per_batch', $number_of_coupons ),
			'prefix'        => $prefix,
			'discount_type' => $discount_type,
			'expiry_days'   => apply_filters( 'scg_coupon_expiry_days', 365 ),
			'max_attempts'  => $number_of_coupons * 2,
		);
	}

	/**
	 * Prepare gift information for coupons
	 *
	 * @param array $valid_products Array of valid product objects.
	 * @return array Gift information array.
	 */
	private function prepare_gift_info( $valid_products ) {
		$gift_info = array();
		// Use array_keys to avoid unused variable warning.
		foreach ( array_keys( $valid_products ) as $product_id ) {
			$gift_info[ $product_id ] = array(
				'product_id'   => $product_id,
				'variation_id' => 0,
				'quantity'     => 1,
			);
		}
		return $gift_info;
	}

	/**
	 * Execute the coupon generation process
	 *
	 * @param array $valid_products Array of valid product objects.
	 * @param array $gift_info      Gift information array.
	 * @param array $params         Generation parameters.
	 * @return int Number of coupons generated.
	 */
	private function execute_coupon_generation( $valid_products, $gift_info, $params ) {
		$generated_count = 0;
		$attempt_count   = 0;

		for ( $i = 1; $i <= $params['count']; $i++ ) {
			// Prevent infinite loops.
			if ( $attempt_count >= $params['max_attempts'] ) {
				break;
			}
			$attempt_count++;

			$coupon_created = $this->create_single_coupon( $valid_products, $gift_info, $params, $i );

			if ( $coupon_created ) {
				$generated_count++;
				$this->handle_generation_delay( $i );
			} else {
				$i--; // Try again with same counter.
			}
		}

		return $generated_count;
	}

	/**
	 * Create a single coupon
	 *
	 * @param array $valid_products Array of valid product objects.
	 * @param array $gift_info      Gift information array.
	 * @param array $params         Generation parameters.
	 * @param int   $current_number Current coupon number in batch.
	 * @return bool True if coupon was created successfully, false otherwise.
	 */
	private function create_single_coupon( $valid_products, $gift_info, $params, $current_number ) {
		try {
			$coupon = new WC_Coupon();

			// Generate unique coupon code.
			$random_code = $this->generate_coupon_code( $params['prefix'] );

			// Skip if code already exists.
			if ( wc_get_coupon_id_by_code( $random_code ) ) {
				return false;
			}

			$this->set_coupon_properties( $coupon, $random_code, $valid_products, $params, $current_number );
			$this->set_coupon_metadata( $coupon, $gift_info, $params );

			// Save coupon.
			$coupon->save();

			// Fire action after each coupon is generated.
			do_action( 'scg_coupon_generated', $coupon->get_id(), array_keys( $valid_products ) );

			return true;

		} catch ( Exception $e ) {
			$this->log_coupon_error( $e );
			return false;
		}
	}

	/**
	 * Set coupon properties
	 *
	 * @param WC_Coupon $coupon         The coupon object.
	 * @param string    $code           The coupon code.
	 * @param array     $valid_products Array of valid product objects.
	 * @param array     $params         Generation parameters.
	 * @param int       $current_number Current coupon number in batch.
	 */
	private function set_coupon_properties( $coupon, $code, $valid_products, $params, $current_number ) {
		// Create product names list for description.
		$product_names = array();
		foreach ( $valid_products as $product ) {
			$product_names[] = $product->get_name();
		}
		$products_text = count( $product_names ) > 1 ?
			implode( ', ', array_slice( $product_names, 0, -1 ) ) . ' and ' . end( $product_names ) :
			$product_names[0];

		$coupon->set_code( $code );
		$coupon->set_description(
			sprintf(
				/* translators: 1: Product names, 2: Current batch number, 3: Total number of coupons */
				__( 'Auto-generated coupon for %1$s (Batch %2$d/%3$d)', 'wc-free-gift-coupons-bulk-coupons-generator' ),
				$products_text,
				$current_number,
				$params['count']
			)
		);
		$coupon->set_discount_type( $params['discount_type'] );
		$coupon->set_individual_use( true );
		$coupon->set_usage_limit( 1 );
		$coupon->set_date_expires( time() + ( $params['expiry_days'] * 24 * 60 * 60 ) );
	}

	/**
	 * Set coupon metadata
	 *
	 * @param WC_Coupon $coupon    The coupon object.
	 * @param array     $gift_info Gift information array.
	 * @param array     $params    Generation parameters.
	 */
	private function set_coupon_metadata( $coupon, $gift_info, $params ) {
		// For free gift coupons, add the gift data.
		if ( 'free_gift' === $params['discount_type'] ) {
			$coupon->update_meta_data( '_wc_free_gift_coupon_data', $gift_info );
		}

		// Add plugin identifier meta.
		$coupon->update_meta_data( '_scg_generated', true );
		$coupon->update_meta_data( '_scg_product_ids', array_keys( $gift_info ) );
		$coupon->update_meta_data( '_scg_generation_date', current_time( 'mysql' ) );
	}

	/**
	 * Handle generation delay for performance
	 *
	 * Implements micro-delays to prevent server overload during bulk generation.
	 * Uses usleep() for precise timing control without blocking the entire process.
	 *
	 * @param int $current_number Current coupon number in the batch.
	 */
	private function handle_generation_delay( $current_number ) {
		// Add small delay to prevent overwhelming the server.
		if ( 0 === $current_number % 50 ) {
			usleep( 100000 ); // 0.1 second delay every 50 coupons.
		}
	}

	/**
	 * Log coupon generation errors
	 *
	 * @param Exception $exception The exception that occurred.
	 */
	private function log_coupon_error( $exception ) {
		// Only log in debug mode.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			wc_get_logger()->error(
				sprintf(
					/* translators: %s: Error code */
					__( 'SCG Error generating coupon: %s', 'wc-free-gift-coupons-bulk-coupons-generator' ),
					$exception->getCode()
				),
				array( 'source' => 'wc-free-gift-coupons-bulk-coupons-generator' )
			);
		}
	}

	/**
	 * Generate unique coupon code
	 *
	 * @param string $prefix Optional prefix for the coupon code.
	 * @return string Generated coupon code.
	 */
	private function generate_coupon_code( $prefix = '' ) {
		$code_length   = 12;
		$characters    = 'abcdefghijklmnopqrstuvwxyz0123456789';
		$random_string = '';
		$char_count    = strlen( $characters );

		for ( $i = 0; $i < $code_length; $i++ ) {
			$random_string .= $characters[ random_int( 0, $char_count - 1 ) ];
		}

		// Add prefix if provided.
		if ( ! empty( $prefix ) ) {
			return strtolower( $prefix ) . '-' . $random_string;
		}

		return $random_string;
	}

	/**
	 * Get products for dropdown
	 *
	 * @return array Array of product options for dropdown.
	 */
	private function get_products_for_dropdown() {
		// Use transient caching for performance.
		$cache_key       = 'scg_products_dropdown_' . wp_cache_get_last_changed( 'posts' );
		$product_options = get_transient( $cache_key );

		if ( false === $product_options ) {
			$args = array(
				'post_type'              => 'product',
				'posts_per_page'         => 100, // Reasonable limit.
				'post_status'            => 'publish',
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true, // Performance optimization.
				'update_post_meta_cache' => false, // Performance optimization.
				'update_post_term_cache' => false, // Performance optimization.
			);

			$products        = get_posts( $args );
			$product_options = array();

			foreach ( $products as $product ) {
				$product_obj = wc_get_product( $product->ID );
				if ( $product_obj && $product_obj->is_purchasable() && $product_obj->is_visible() ) {
					$product_options[ $product->ID ] = esc_html( $product_obj->get_name() ) . ' (ID: ' . esc_html( (string) $product->ID ) . ')';
				}
			}

			// Cache for 1 hour.
			set_transient( $cache_key, $product_options, HOUR_IN_SECONDS );
		}

		return $product_options;
	}

	/**
	 * Admin page content
	 */
	public function admin_page() {
		$products = $this->get_products_for_dropdown();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WC Free Gift Coupons Bulk Coupon Generator', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?></h1>
			<p><?php esc_html_e( 'Generate bulk free gift coupons that work with the Free Gift Coupons for WooCommerce plugin. These coupons are created with the proper data structure required for free gift functionality.', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?></p>

			<div class="scg-admin-container">
				<div class="scg-main-content">
					<?php $this->render_admin_form( $products ); ?>
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
	 * Render admin form
	 *
	 * @param array $products Array of products for the dropdown.
	 */
	private function render_admin_form( $products ) {
		?>
		<form method="post" action="" class="scg-form">
			<?php wp_nonce_field( 'scg_generate_coupons_action', 'scg_nonce' ); ?>

			<table class="form-table">
				<?php $this->render_product_selection_field( $products ); ?>
				<?php $this->render_coupon_count_field(); ?>
				<?php $this->render_coupon_prefix_field(); ?>
				<?php $this->render_discount_type_field(); ?>
			</table>

			<p class="submit">
				<input type="submit" name="scg_generate_coupons" class="button-primary"
					   value="<?php esc_attr_e( 'Generate Free Gift Coupons', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?>">
			</p>
		</form>
		<?php
	}

	/**
	 * Render product selection field
	 *
	 * @param array $products Array of products for the dropdown.
	 */
	private function render_product_selection_field( $products ) {
		?>
		<tr>
			<th scope="row">
				<label for="product_id"><?php esc_html_e( 'Select Products', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?></label>
			</th>
			<td>
				<select name="product_id[]" id="product_id" class="regular-text" multiple="multiple" size="8" required>
					<?php foreach ( $products as $product_id => $product_name ) : ?>
						<option value="<?php echo esc_attr( $product_id ); ?>">
							<?php echo esc_html( $product_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description">
					<?php esc_html_e( 'Select one or more products that will be given as free gifts with the coupon. Hold Ctrl (Windows) or Cmd (Mac) to select multiple products.', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render coupon count field
	 */
	private function render_coupon_count_field() {
		?>
		<tr>
			<th scope="row">
				<label for="number_of_coupons"><?php esc_html_e( 'Number of Coupons', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?></label>
			</th>
			<td>
				<input type="number" name="number_of_coupons" id="number_of_coupons"
					   class="regular-text" min="1" max="100" value="10" required>
				<p class="description">
					<?php esc_html_e( 'Enter the number of coupons to generate (maximum 100).', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?>
				</p>
				<div class="scg-warning-box">
					<p class="scg-warning-text">
						<span class="dashicons dashicons-warning" style="color: #d63638;"></span>
						<?php esc_html_e( 'Note: Coupon generation can be time-consuming. Generating large numbers of coupons may cause the page to timeout based on your server\'s PHP timeout settings. If you need to generate many coupons, consider doing it in smaller batches.', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?>
					</p>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render coupon prefix field
	 */
	private function render_coupon_prefix_field() {
		?>
		<tr>
			<th scope="row">
				<label for="coupon_prefix"><?php esc_html_e( 'Coupon Prefix', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?></label>
			</th>
			<td>
				<input type="text" name="coupon_prefix" id="coupon_prefix"
					   class="regular-text" maxlength="10" placeholder="e.g. GIFT">
				<p class="description">
					<?php esc_html_e( 'Optional prefix for coupon codes (e.g. GIFT-ABC123DEF456).', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render discount type field
	 */
	private function render_discount_type_field() {
		?>
		<tr>
			<th scope="row">
				<label for="discount_type"><?php esc_html_e( 'Discount Type', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?></label>
			</th>
			<td>
				<select name="discount_type" id="discount_type" class="regular-text">
					<option value="free_gift"><?php esc_html_e( 'Free Gift', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?></option>
					<option value="percent"><?php esc_html_e( 'Percentage Discount', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?></option>
					<option value="fixed_cart"><?php esc_html_e( 'Fixed Cart Discount', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?></option>
					<option value="fixed_product"><?php esc_html_e( 'Fixed Product Discount', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?></option>
				</select>
				<p class="description">
					<?php esc_html_e( 'Select the type of discount for the coupons.', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render admin sidebar
	 */
	private function render_admin_sidebar() {
		?>
		<div class="scg-info-box">
			<h3><?php esc_html_e( 'Information', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?></h3>
			<ul>
				<li><?php esc_html_e( 'Maximum 100 coupons can be generated at once', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?></li>
				<li><?php esc_html_e( 'Coupons are set to expire after 1 year', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?></li>
				<li><?php esc_html_e( 'Each coupon can only be used once', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?></li>
				<li><?php esc_html_e( 'Coupons are set for individual use only', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?></li>
				<li><?php esc_html_e( 'Generated coupons appear in WooCommerce > Coupons', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?></li>
			</ul>
		</div>
		<?php
	}

	/**
	 * Render admin footer
	 */
	private function render_admin_footer() {
		?>
		<div class="scg-footer">
			<p class="scg-repo-link">
				<a href="https://github.com/EngineScript/wc-free-gift-coupons-bulk-coupons-generator" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'View on GitHub', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?>
				</a>
				|
				<a href="https://github.com/EngineScript/wc-free-gift-coupons-bulk-coupons-generator/issues" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Report Issues', 'wc-free-gift-coupons-bulk-coupons-generator' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}

/**
 * Helper function to check if plugin is loaded
 *
 * @return bool
 */
function wc_free_gift_bulk_coupons_is_loaded() {
	return class_exists( 'WooCommerceFreeGiftBulkCoupons' );
}

/**
 * Initialize the plugin after all plugins are loaded.
 * This ensures proper load order and prevents conflicts with dependencies like WooCommerce.
 */
function wc_free_gift_bulk_coupons_init() {
	WooCommerceFreeGiftBulkCoupons::get_instance();
}

// Hook plugin initialization to plugins_loaded to ensure proper load order.
add_action( 'plugins_loaded', 'wc_free_gift_bulk_coupons_init' );
