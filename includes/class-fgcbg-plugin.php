<?php
/**
 * Main plugin orchestration class.
 *
 * @package FreeGiftCouponsBulkGenerator
 * @since   1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin singleton that wires hooks, handles AJAX coupon generation, and delegates
 * rendering to dedicated classes.
 *
 * @since 1.0.0
 */
class FGCBG_Plugin {

	/**
	 * Maximum coupons that can be generated in a single batch.
	 *
	 * @since 1.6.0
	 * @var int
	 */
	const MAX_COUPONS_PER_BATCH = 100;

	/**
	 * Plugin instance.
	 *
	 * @since 1.0.0
	 * @var FGCBG_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Coupon generator instance.
	 *
	 * @since 1.6.0
	 * @var FGCBG_Coupon_Generator
	 */
	private $generator;

	/**
	 * Admin page renderer instance.
	 *
	 * @since 1.6.0
	 * @var FGCBG_Admin_Page
	 */
	private $admin_page;

	/**
	 * Get plugin instance.
	 *
	 * @since 1.0.0
	 * @return FGCBG_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		$this->generator  = new FGCBG_Coupon_Generator();
		$this->admin_page = new FGCBG_Admin_Page();

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
			add_action( 'wp_ajax_fgcbg_generate_batch', array( $this, 'ajax_generate_batch' ) );
		}
	}

	/**
	 * WooCommerce missing notice.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function woocommerce_missing_notice() {
		$message = sprintf(
			/* translators: %s: WooCommerce download link */
			esc_html__( 'Free Gift Coupons Bulk Coupon Generator requires WooCommerce to be installed and active. You can download %s here.', 'free-gift-coupons-bulk-coupons-generator' ),
			'<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>'
		);
		echo '<div class="error"><p>' . wp_kses_post( $message ) . '</p></div>';
	}

	/**
	 * Add admin menu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Free Gift Bulk Coupons', 'free-gift-coupons-bulk-coupons-generator' ),
			__( 'Coupon Generator', 'free-gift-coupons-bulk-coupons-generator' ),
			'manage_woocommerce',
			'free-gift-bulk-coupon-generator',
			array( $this->admin_page, 'render' )
		);
	}

	/**
	 * Handle AJAX batch coupon generation.
	 *
	 * Accepts a batch_size parameter and generates that many coupons per request.
	 * The JS client calls this repeatedly until all coupons are created.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	public function ajax_generate_batch() {
		check_ajax_referer( 'fgcbg_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to generate coupons.', 'free-gift-coupons-bulk-coupons-generator' ) ), 403 );
		}

		$product_ids   = isset( $_POST['product_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['product_ids'] ) ) : array();
		$batch_size    = isset( $_POST['batch_size'] ) ? absint( $_POST['batch_size'] ) : 10;
		$coupon_prefix = isset( $_POST['coupon_prefix'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_prefix'] ) ) : '';
		$discount_type = isset( $_POST['discount_type'] ) ? sanitize_text_field( wp_unslash( $_POST['discount_type'] ) ) : 'free_gift';

		$allowed_discount_types = array( 'free_gift', 'percent', 'fixed_cart', 'fixed_product' );
		if ( ! in_array( $discount_type, $allowed_discount_types, true ) ) {
			$discount_type = 'free_gift';
		}

		if ( ! empty( $coupon_prefix ) ) {
			$coupon_prefix = preg_replace( '/[^A-Za-z0-9]/', '', $coupon_prefix );
			$coupon_prefix = strtoupper( substr( $coupon_prefix, 0, 10 ) );
		}

		$product_ids = array_filter( $product_ids );

		if ( empty( $product_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'Please select at least one product.', 'free-gift-coupons-bulk-coupons-generator' ) ) );
		}

		$batch_size = min( $batch_size, self::MAX_COUPONS_PER_BATCH );
		if ( $batch_size < 1 ) {
			$batch_size = 10;
		}

		$generated = $this->generator->generate_coupons( $product_ids, $batch_size, $coupon_prefix, $discount_type );

		wp_send_json_success( array( 'generated' => $generated ) );
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 1.0.0
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'woocommerce_page_free-gift-bulk-coupon-generator' !== $hook ) {
			return;
		}

		wp_enqueue_script( 'fgcbg-admin', FGCBG_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery', 'wc-enhanced-selects' ), FGCBG_PLUGIN_VERSION, true );
		wp_enqueue_style( 'fgcbg-admin', FGCBG_PLUGIN_URL . 'assets/css/admin.css', array(), FGCBG_PLUGIN_VERSION );

		wp_localize_script(
			'fgcbg-admin',
			'fgcbg_i18n',
			array(
				'ajax_url'               => admin_url( 'admin-ajax.php' ),
				'nonce'                  => wp_create_nonce( 'fgcbg_ajax_nonce' ),
				/* translators: %d is the number of coupons to be generated. */
				'confirm_large_batch'    => __( 'You are about to generate %d coupons. This may take a while and could potentially timeout depending on your server settings. Do you want to continue?', 'free-gift-coupons-bulk-coupons-generator' ),
				'max_coupons_warning'    => __( 'Maximum 100 coupons allowed', 'free-gift-coupons-bulk-coupons-generator' ),
				'many_coupons_warning'   => __( 'Generating many coupons may take some time and could timeout', 'free-gift-coupons-bulk-coupons-generator' ),
				'select_product'         => __( 'Please select at least one product.', 'free-gift-coupons-bulk-coupons-generator' ),
				'invalid_coupon_count'   => __( 'Please enter a valid number of coupons (minimum 1).', 'free-gift-coupons-bulk-coupons-generator' ),
				'max_coupon_count'       => __( 'Maximum number of coupons is 100.', 'free-gift-coupons-bulk-coupons-generator' ),
				'prefix_too_long'        => __( 'Coupon prefix must be 10 characters or less.', 'free-gift-coupons-bulk-coupons-generator' ),
				'generation_in_progress' => __( 'Coupon generation is in progress. Are you sure you want to leave this page?', 'free-gift-coupons-bulk-coupons-generator' ),
				/* translators: %1$d is the current coupon count, %2$d is the total number of coupons to generate. */
				'generating_progress'    => __( 'Generating coupons: %1$d of %2$d', 'free-gift-coupons-bulk-coupons-generator' ),
				/* translators: %d is the number of successfully generated coupons. */
				'generation_complete'    => __( 'Successfully generated %d coupons.', 'free-gift-coupons-bulk-coupons-generator' ),
				'generation_failed'      => __( 'Failed to generate coupons. Please try again.', 'free-gift-coupons-bulk-coupons-generator' ),
			)
		);
	}
}
