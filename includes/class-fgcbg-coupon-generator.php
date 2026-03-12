<?php
/**
 * Coupon generation logic.
 *
 * @package FreeGiftCouponsBulkGenerator
 * @since   1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles WooCommerce coupon creation, code generation, and batch processing.
 *
 * @since 1.6.0
 */
class FGCBG_Coupon_Generator {

	/**
	 * Number of coupons between server-relief micro-delays.
	 *
	 * @since 1.6.0
	 * @var int
	 */
	const DELAY_INTERVAL = 50;

	/**
	 * Micro-delay duration in microseconds (0.1 s).
	 *
	 * @since 1.6.0
	 * @var int
	 */
	const DELAY_MICROSECONDS = 100000;

	/**
	 * Default coupon code length (filterable via fgcbg_coupon_code_length).
	 *
	 * @since 1.6.0
	 * @var int
	 */
	const DEFAULT_CODE_LENGTH = 12;

	/**
	 * Default coupon expiry in days (filterable via fgcbg_coupon_expiry_days).
	 *
	 * @since 1.6.0
	 * @var int
	 */
	const DEFAULT_EXPIRY_DAYS = 365;

	/**
	 * Generate coupons.
	 *
	 * @since 1.0.0
	 * @param array<int> $product_ids      Array of product IDs to generate coupons for.
	 * @param int    $number_of_coupons Number of coupons to generate.
	 * @param string $prefix           Coupon prefix.
	 * @param string $discount_type    Type of discount.
	 * @return int Number of coupons generated.
	 */
	public function generate_coupons( $product_ids, $number_of_coupons, $prefix = '', $discount_type = 'free_gift' ) {
		$valid_products = $this->validate_products( $product_ids );
		if ( empty( $valid_products ) ) {
			return 0;
		}

		$generation_params = $this->prepare_generation_params( $number_of_coupons, $prefix, $discount_type );
		$gift_info         = $this->prepare_gift_info( $valid_products );

		do_action( 'fgcbg_before_coupon_generation', $product_ids, $generation_params['count'] );

		$generated_count = $this->execute_coupon_generation( $valid_products, $gift_info, $generation_params );

		do_action( 'fgcbg_after_coupon_generation', $product_ids, $generated_count );

		return $generated_count;
	}

	/**
	 * Validate products for coupon generation.
	 *
	 * @since 1.0.0
	 * @param array<int>|int $product_ids Product IDs to validate.
	 * @return array<int, WC_Product> Array of valid product objects keyed by ID.
	 */
	private function validate_products( $product_ids ) {
		if ( ! is_array( $product_ids ) ) {
			$product_ids = array( $product_ids );
		}

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
	 * Prepare generation parameters.
	 *
	 * @since 1.0.0
	 * @param int    $number_of_coupons Number of coupons to generate.
	 * @param string $prefix           Coupon prefix.
	 * @param string $discount_type    Type of discount.
	 * @return array{count:int, prefix:string, discount_type:string, expiry_days:int, max_attempts:int} Generation parameters array.
	 */
	private function prepare_generation_params( $number_of_coupons, $prefix, $discount_type ) {
		$count       = (int) apply_filters( 'fgcbg_max_coupons_per_batch', $number_of_coupons );
		$expiry_days = (int) apply_filters( 'fgcbg_coupon_expiry_days', self::DEFAULT_EXPIRY_DAYS );

		return array(
			'count'         => $count,
			'prefix'        => $prefix,
			'discount_type' => $discount_type,
			'expiry_days'   => $expiry_days,
			'max_attempts'  => $number_of_coupons * 2,
		);
	}

	/**
	 * Prepare gift information for coupons.
	 *
	 * @since 1.0.0
	 * @param array<int, WC_Product> $valid_products Array of valid product objects.
	 * @return array<int, array{product_id:int, variation_id:int, quantity:int}> Gift information array.
	 */
	private function prepare_gift_info( $valid_products ) {
		$gift_info = array();
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
	 * Execute the coupon generation process.
	 *
	 * @since 1.0.0
	 * @param array<int, WC_Product> $valid_products Array of valid product objects.
	 * @param array<int, array{product_id:int, variation_id:int, quantity:int}> $gift_info Gift information array.
	 * @param array{count:int, prefix:string, discount_type:string, expiry_days:int, max_attempts:int} $params Generation parameters.
	 * @return int Number of coupons generated.
	 */
	private function execute_coupon_generation( $valid_products, $gift_info, $params ) {
		$generated_count = 0;
		$attempt_count   = 0;

		for ( $i = 1; $i <= $params['count']; $i++ ) {
			if ( $attempt_count >= $params['max_attempts'] ) {
				break;
			}
			$attempt_count++;

			$coupon_created = $this->create_single_coupon( $valid_products, $gift_info, $params, $i );

			if ( $coupon_created ) {
				$generated_count++;
				$this->handle_generation_delay( $i );
			} else {
				$i--;
			}
		}

		return $generated_count;
	}

	/**
	 * Create a single coupon.
	 *
	 * @since 1.0.0
	 * @param array<int, WC_Product> $valid_products Array of valid product objects.
	 * @param array<int, array{product_id:int, variation_id:int, quantity:int}> $gift_info Gift information array.
	 * @param array{count:int, prefix:string, discount_type:string, expiry_days:int, max_attempts:int} $params Generation parameters.
	 * @param int   $current_number Current coupon number in batch.
	 * @return bool True if coupon was created successfully, false otherwise.
	 */
	private function create_single_coupon( $valid_products, $gift_info, $params, $current_number ) {
		try {
			$coupon      = new WC_Coupon();
			$random_code = $this->generate_coupon_code( $params['prefix'] );

			$this->set_coupon_properties( $coupon, $random_code, $valid_products, $params, $current_number );
			$this->set_coupon_metadata( $coupon, $gift_info, $params );

			$coupon->save();

			do_action( 'fgcbg_coupon_generated', $coupon->get_id(), array_keys( $valid_products ) );

			return true;
		} catch ( \Exception $e ) {
			$this->log_coupon_error( $e );
			return false;
		}
	}

	/**
	 * Set coupon properties.
	 *
	 * @since 1.0.0
	 * @param WC_Coupon $coupon         The coupon object.
	 * @param string    $code           The coupon code.
	 * @param array<int, WC_Product> $valid_products Array of valid product objects.
	 * @param array{count:int, prefix:string, discount_type:string, expiry_days:int, max_attempts:int} $params Generation parameters.
	 * @param int       $current_number Current coupon number in batch.
	 * @return void
	 */
	private function set_coupon_properties( $coupon, $code, $valid_products, $params, $current_number ) {
		$product_names = array();
		foreach ( $valid_products as $product ) {
			$product_names[] = $product->get_name();
		}
		$products_text = wp_sprintf( '%l', $product_names );

		$coupon->set_code( $code );
		$coupon->set_description(
			sprintf(
				/* translators: 1: Product names, 2: Current batch number, 3: Total number of coupons */
				__( 'Auto-generated coupon for %1$s (Batch %2$d/%3$d)', 'free-gift-coupons-bulk-coupons-generator' ),
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
	 * Set coupon metadata.
	 *
	 * @since 1.0.0
	 * @param WC_Coupon $coupon    The coupon object.
	 * @param array<int, array{product_id:int, variation_id:int, quantity:int}> $gift_info Gift information array.
	 * @param array{count:int, prefix:string, discount_type:string, expiry_days:int, max_attempts:int} $params Generation parameters.
	 * @return void
	 */
	private function set_coupon_metadata( $coupon, $gift_info, $params ) {
		if ( 'free_gift' === $params['discount_type'] ) {
			$coupon->update_meta_data( '_wc_free_gift_coupon_data', $gift_info );
		}

		$coupon->update_meta_data( '_fgcbg_generated', true );
		$coupon->update_meta_data( '_fgcbg_product_ids', array_keys( $gift_info ) );
		$coupon->update_meta_data( '_fgcbg_generation_date', current_time( 'mysql' ) );
	}

	/**
	 * Handle generation delay for performance.
	 *
	 * @since 1.0.0
	 * @param int $current_number Current coupon number in the batch.
	 * @return void
	 */
	private function handle_generation_delay( $current_number ) {
		if ( 0 === $current_number % self::DELAY_INTERVAL ) {
			usleep( self::DELAY_MICROSECONDS );
		}
	}

	/**
	 * Log coupon generation errors.
	 *
	 * @since 1.0.0
	 * @param \Exception $exception The exception that occurred.
	 * @return void
	 */
	private function log_coupon_error( $exception ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			wc_get_logger()->error(
				sprintf(
					/* translators: 1: Exception class name, 2: Error message */
					__( 'FGCBG Error generating coupon [%1$s]: %2$s', 'free-gift-coupons-bulk-coupons-generator' ),
					get_class( $exception ),
					$exception->getMessage()
				),
				array( 'source' => 'free-gift-coupons-bulk-coupons-generator' )
			);
		}
	}

	/**
	 * Generate unique coupon code.
	 *
	 * @since 1.0.0
	 * @param string $prefix Optional prefix for the coupon code.
	 * @return string Generated coupon code.
	 */
	private function generate_coupon_code( $prefix = '' ) {
		$code_length   = apply_filters( 'fgcbg_coupon_code_length', self::DEFAULT_CODE_LENGTH );
		$code_length   = max( 8, min( 32, (int) $code_length ) );
		$characters    = 'abcdefghijklmnopqrstuvwxyz0123456789';
		$random_string = '';
		$char_count    = strlen( $characters );

		for ( $i = 0; $i < $code_length; $i++ ) {
			$random_string .= $characters[ random_int( 0, $char_count - 1 ) ];
		}

		if ( ! empty( $prefix ) ) {
			return strtolower( $prefix ) . '-' . $random_string;
		}

		return $random_string;
	}
}
