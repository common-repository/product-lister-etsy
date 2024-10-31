<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! function_exists( 'ced_etsy_tool_tip' ) ) {
	function ced_etsy_tool_tip( $tip = '' ) {
		echo wc_help_tip( $tip );
	}
}

/**
 * Callback function for display html.
 *
 * @since 1.0.0
 */
if ( ! function_exists( 'get_etsy_instuctions_html' ) ) {
	function get_etsy_instuctions_html( $label = 'Instructions' ) {
		if ( 'Instructions' == $label ) {
			return;
		}
		?>
		<div class="ced_etsy_parent_element">
			<h2>
				<label><?php echo esc_html_e( $label, 'etsy-woocommerce-integration' ); ?></label>
			</h2>
		</div>
		<?php
	}
}

/**
 * *********************************************
 * Get Product id by listing id and Shop Name
 * *********************************************
 *
 * @since 1.0.0
 */
if ( ! function_exists( 'etsy_get_product_id_by_shopname_and_listing_id' ) ) {
	function etsy_get_product_id_by_shopname_and_listing_id( $shop_name = '', $listing = '' ) {

		if ( empty( $shop_name ) || empty( $listing ) ) {
			return;
		}
		$if_exists  = get_posts(
			array(
				'numberposts' => -1,
				'post_type'   => 'product',
				'post_status' => array_keys( get_post_statuses() ),
				'meta_query'  => array(
					array(
						'key'     => '_ced_etsy_listing_id_' . $shop_name,
						'value'   => $listing,
						'compare' => '=',
					),
				),
				'fields'      => 'ids',
			)
		);
		$product_id = isset( $if_exists[0] ) ? $if_exists[0] : '';
		return $product_id;
	}
}

if ( ! function_exists( 'ced_etsy_cedcommerce_logo' ) ) {
	function ced_etsy_cedcommerce_logo() {
		return '<img src="' . esc_url( CED_ETSY_URL . 'admin/assets/images/ced-logo.png' ) . '">';
	}
}

if ( ! function_exists( 'etsy_request' ) ) {
	function etsy_request() {
		$request = new \Cedcommerce\EtsyManager\Ced_Etsy_Request();
		return $request;
	}
}

function ced_etsy_check_if_limit_reached( $shop_name ) {
	$store_products = get_posts(
		array(
			'numberposts' => -1,
			'post_type'   => 'product',
			'meta_query'  => array(
				array(
					'key'     => '_ced_etsy_listing_id_' . $shop_name,
					'compare' => 'EXISTS',
				),
			),
			'fields'      => 'ids',
		)
	);
	$count          = count( $store_products );
	if ( $count < 100 ) {
		return false;
	}
	return true;

}

if ( ! function_exists( 'etsy_shop_id' ) ) {
	function etsy_shop_id( $shop_name = '' ) {
		$saved_etsy_details = get_option( 'ced_etsy_details', array() );
		$shopDetails        = $saved_etsy_details[ $shop_name ];
		$shop_id            = isset( $shopDetails['details']['shop_id'] ) ? $shopDetails['details']['shop_id'] : '';
		return $shop_id;
	}
}

if ( ! function_exists( 'deactivate_ced_etsy_woo_missing' ) ) {
	function deactivate_ced_etsy_woo_missing() {
		deactivate_plugins( CED_ETSY_LISTER_PLUGIN_BASENAME );
		add_action( 'admin_notices', 'ced_etsy_woo_missing_notice' );
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}
}



/**
 * Callback function for sending notice if woocommerce is not activated.
 *
 * @since 1.0.0
 */

if ( ! function_exists( 'ced_etsy_woo_missing_notice' ) ) {
	function ced_etsy_woo_missing_notice() {
		// translators: %s: search term !!
		echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( esc_html( __( 'Etsy Integration For WooCommerce requires WooCommerce to be installed and active. You can download %s from here.', 'product-lister-etsy' ) ), '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>' ) . '</p></div>';
	}
}



if ( ! function_exists( 'ced_etsy_check_woocommerce_active' ) ) {
	function ced_etsy_check_woocommerce_active() {
		/** Alter active plugin list
					 *
					 * @since 2.0.0
					 */
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			return true;
		}
		return false;
	}
}

if ( ! function_exists( 'ced_etsy_format_response' ) ) {
	function ced_etsy_format_response( $message = '', $shop_name = '' ) {
		$formatted_responses = array( 'invalid_token' => "Token expired . This may be because of recent change in login details for 'etsy.com' or some other reason . In order to update the token please <a href='" . esc_url( ced_etsy_get_auth_url( $shop_name ) ) . "' class='expired_access_token' > <b><i> Re-authorize </i></b> </a> ." );
		$message             = isset( $formatted_responses[ $message ] ) ? $formatted_responses[ $message ] : $message;
		return $message;
	}
}


if ( ! function_exists( 'get_etsy_connected_accounts' ) ) {
	function get_etsy_connected_accounts() {
		return get_option( 'ced_etsy_details', array() );
	}
}

if ( ! function_exists( 'ced_etsy_get_auth_url' ) ) {
	function ced_etsy_get_auth_url( $shop_name ) {

		$scopes = array(
			'address_r',
			'address_w',
			'billing_r',
			'cart_r',
			'cart_w',
			'email_r',
			'favorites_r',
			'favorites_w',
			'feedback_r',
			'listings_d',
			'listings_r',
			'listings_w',
			'profile_r',
			'profile_w',
			'recommend_r',
			'recommend_w',
			'shops_r',
			'shops_w',
			'transactions_r',
			'transactions_w',
		);

		$scopes         = urlencode( implode( ' ', $scopes ) );
		$redirect_uri   = 'https://woodemo.cedcommerce.com/woocommerce/authorize/etsy/authorize.php';
		$client_id      = ced_etsy_get_auth();
		$verifier       = base64_encode( admin_url( 'admin.php?page=sales_channel&channel=etsy&shop_name=' . $shop_name ) );
		$code_challenge = strtr(
			trim(
				base64_encode( pack( 'H*', hash( 'sha256', $verifier ) ) ),
				'='
			),
			'+/',
			'-_'
		);

		return "https://www.etsy.com/oauth/connect?response_type=code&redirect_uri=$redirect_uri&scope=$scopes&client_id=$client_id&state=$verifier&code_challenge=$code_challenge&code_challenge_method=S256";
	}
}

if ( ! function_exists( 'get_etsy_shop_id' ) ) {
	function get_etsy_shop_id( $shop_name = '' ) {
		$saved_etsy_details = get_option( 'ced_etsy_details', array() );
		$shopDetails        = isset( $saved_etsy_details[ $shop_name ] ) ? $saved_etsy_details[ $shop_name ] : false;
		$shop_id            = false;
		if ( $shopDetails ) {
			$shop_id = isset( $shopDetails['details']['shop_id'] ) ? $shopDetails['details']['shop_id'] : '';
		}
		return $shop_id;
	}
}
if ( ! function_exists( 'ced_filter_input' ) ) {
	function ced_filter_input() {
		return filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
	}
}

if ( ! function_exists( 'get_product_id_by_params' ) ) {
	function get_product_id_by_params( $meta_key = '', $meta_value = '' ) {
		if ( ! empty( $meta_value ) ) {
			$posts = get_posts(
				array(

					'numberposts' => -1,
					'post_type'   => array( 'product', 'product_variation' ),
					'meta_query'  => array(
						array(
							'key'     => $meta_key,
							'value'   => trim( $meta_value ),
							'compare' => '=',
						),
					),
					'fields'      => 'ids',

				)
			);
			if ( ! empty( $posts ) ) {
				return $posts[0];
			}
			return false;
		}
		return false;
	}
}
if ( ! function_exists( 'get_etsy_shop_name' ) ) {
	function get_etsy_shop_name() {
		$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( $_GET['shop_name'] ) : get_option( 'ced_etsy_shop_name' );
		return $shop_name;
	}
}
if ( ! function_exists( 'ced_get_navigation_url' ) ) {
	function ced_get_navigation_url( $channel = 'home', $query_args = array() ) {
		if ( ! empty( $query_args ) ) {
			return admin_url( 'admin.php?page=sales_channel&channel=' . $channel . '&' . http_build_query( $query_args ) );
		}
		return admin_url( 'admin.php?page=sales_channel&channel=' . $channel );
	}
}

if ( ! function_exists( 'get_etsy_orders_count' ) ) {
	function get_etsy_orders_count( $shop_name ) {

		global $wpdb;
		$orders_post_ids = $wpdb->get_results( $wpdb->prepare( "SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key`=%s AND `meta_value`=%s", 'ced_etsy_order_shop_id', $shop_name ), 'ARRAY_A' );
		return count( $orders_post_ids );
	}
}

if ( ! function_exists( 'get_etsy_products_count' ) ) {
	function get_etsy_products_count( $shop_name, $is_all = false ) {
		$args =
		array(
			'post_type'   => 'product',
			'post_status' => 'publish',
			'numberposts' => -1,
			'fields'      => 'ids',
			'tax_query'   => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => array( 'simple', 'variable' ),
					'operator' => 'IN',
				),
			),
		);
		if ( ! $is_all ) {
			$args['meta_query'] = array(
				array(
					'key'     => '_ced_etsy_listing_id_' . $shop_name,
					'compare' => '!=',
					'value'   => '',
				),
			);
		}

		$posts = get_posts( $args );

		return count( $posts );
	}
}



if ( ! function_exists( 'get_etsy_orders_revenue' ) ) {
	function get_etsy_orders_revenue( $shop_name ) {
		global $wpdb;
		$args = array(
			'post_type'   => 'shop_order',
			'numberposts' => -1,
			'fields'      => 'ids',
			'post_status' => array( 'wc-completed' ),
		);

		$args['meta_query'] = array(
			array(
				'key'     => 'ced_etsy_order_shop_id',
				'compare' => '=',
				'value'   => $shop_name,
			),
		);
		$ids                = get_posts(
			$args
		);
		if ( is_array( $ids ) && ! empty( $ids ) ) {
			$total_value = 0;
			$total_value = array_map(
				function ( $id ) {
					$order = wc_get_order( $id );
					return $order->get_total();
				},
				$ids
			);
			$total_value = array_sum( $total_value );
		}
		return ! empty( $total_value ) ? get_woocommerce_currency_symbol() . $total_value : get_woocommerce_currency_symbol() . 0.00;
	}
}

if ( ! function_exists( 'ced_etsy_categories_tree' ) ) {
	function ced_etsy_categories_tree( $value, $cat_name ) {
		if ( 0 != $value->parent ) {
			$parent_id = $value->parent;
			$sbcatch2  = get_term( $parent_id );
			$cat_name  = $sbcatch2->name . ' --> ' . $cat_name;
			if ( 0 != $sbcatch2->parent ) {
				$cat_name = ced_etsy_categories_tree( $sbcatch2, $cat_name );
			}
		}
		return $cat_name;
	}
}
if ( ! function_exists( 'ced_ety_get_custom_meta_and_attributes_keys' ) ) {
	function ced_ety_get_custom_meta_and_attributes_keys() {
		global $wpdb;
		$meta_keys            = $wpdb->get_col(
			"
		    SELECT DISTINCT meta_key
		    FROM {$wpdb->postmeta}
		    ORDER BY meta_key
		"
		);
		$meta_keys            = empty( $meta_keys ) ? array() : $meta_keys;
		$attribute_taxonomies = wc_get_attribute_taxonomies();
		$attribute_keys       = array();
		foreach ( $attribute_taxonomies as $attribute ) {
			$attribute_keys[] = wc_attribute_taxonomy_name( $attribute->attribute_name );
		}
		return array_merge( array_combine( array_values( $meta_keys ), $meta_keys ), array_combine( array_values( $attribute_keys ), $attribute_keys ) );
	}
}

if ( ! function_exists( 'ced_etsy_get_auth' ) ) {
	function ced_etsy_get_auth() {
		$infrm = get_option( 'ced_etsy_auth_info', array() );
		return openssl_decrypt( $infrm['scrt'], 'AES-128-CTR', $infrm['ky'] );
	}
}
