<?php
/**
 * Product Data Payload For Upload Update and all.
 *
 * @since 1.0.0
 * @package Cedcommmerce\ProductDelete\Class
 */

namespace Cedcommerce\Product;

/**
 * Class ProductDelete
 *
 * @package Cedcommerce\Product.
 */
class Ced_Product_Payload {

	/**
	 * Listing ID variable
	 *
	 * @var int
	 */
	public $listing_id;
	/**
	 * Ced Etsy global settings
	 *
	 * @var array
	 */
	public $ced_global_settings;
	/**
	 * Profile assign flag
	 *
	 * @var bool
	 */
	public $is_profile_assing;
	/**
	 * Mapped profile data.
	 *
	 * @var int
	 */
	public $profile_data;
	/**
	 * Is product type dowloadable or not.
	 *
	 * @var array
	 */
	public $is_downloadable;
	/**
	 * Downloadable file data.
	 *
	 * @var string
	 */
	public $downloadable_data;
	/**
	 * Product Type variable
	 *
	 * @var int
	 */
	public $product_type;

	/**
	 * Etsy shop name.
	 *
	 * @var string
	 */
	public $shop_name;

	/**
	 * Product ID.
	 *
	 * @var int
	 */
	public $product;

	/**
	 * Etsy Payload response.
	 *
	 * @var string
	 */
	public $response;
	public $product_id;
	public $pro_data = array();
	public $profile_id;
	public $profile_name;
	public $prod_obj;
	public $parent_id;
	public $product_arguements = array();
	public $error              = array();

	public $required;
	public $recommended;
	public $optional;
	public $shipping;
	public $personalization;
	public $is_upload = false;


	/**
	 * ********************************************************
	 * SET SETTINGS VALUE AND ETSY CREDS TO MANAGE API REQUEST
	 * ********************************************************
	 *
	 * @since 1.0.0
	 */

	public function __construct( $product_id = '', $shop_name = '', $listing_id = '' ) {

		$this->ced_global_settings = get_option( 'ced_etsy_global_settings', array() );
		$this->shop_name           = $shop_name;
		$this->product_id          = $product_id;
		$this->listing_id          = $listing_id;
		if ( $this->shop_name ) {
			$this->ced_global_settings = isset( $this->ced_global_settings[ $this->shop_name ] ) ? $this->ced_global_settings[ $this->shop_name ] : $this->ced_global_settings;
		}
	}

	/**
	 * Get value of an property which isn't exist in this class.
	 *
	 * @param array $property_name Get result by Defferent names.
	 * @since    1.0.0
	 */
	public function __get( $property_name ) {
		if ( 'result' === $property_name ) {
			return $this->response;
		}
	}

	/**
	 * Set the value of a property which is not exist in Class.
	 *
	 * @param array $proId Product lsting  ids.
	 * @since    1.0.0
	 */
	public function __set( $name, $value ) {
		if ( 'e_shop' === $name || 's_n' === $name || 'shop' === $name ) {
			$this->shop_name = $value;
		}
		if ( 'type' === $name || 'p_type' === $name || 'wc_type' === $name ) {
			$this->product_type = $value;
		}
	}

	/**
	 * **********************************************
	 * Get WooCommerce Product Data, Type, Parent ID.
	 * **********************************************
	 *
	 * @since 1.0.0
	 *
	 * @param string $pr_id Product lsting  ids.
	 * @param string $shop_name Active shopName.
	 *
	 * @return string Woo product type.
	 */

	public function ced_pro_type( $pr_id = '' ) {
		if ( empty( $pr_id ) ) {
			$pr_id = $this->product_id;
		}
		$wc_product = wc_get_product( $pr_id );
		if ( is_bool( $wc_product ) ) {
			return false;
		}
		$this->prod_obj     = $wc_product;
		$this->product      = $wc_product->get_data();
		$this->product_type = $wc_product->get_type();
		$this->parent_id    = 0;
		if ( 'variation' == $this->product_type ) {
			$this->parent_id = $wc_product->get_parent_id();
		}
		return $this->product_type;
	}

	/**
	 * *****************************************
	 * GET ASSIGNED PRODUCT DATA FROM PROFILES
	 * *****************************************
	 *
	 * @since 1.0.0
	 *
	 * @param array  $product_id Product lsting  ids.
	 * @param string $shop_name Active Etsy shopName.
	 *
	 * @return $profile_data assigined profile data .
	 */

	public function ced_etsy_check_profile( $product_id = '', $shop_name = '' ) {
		if ( 'variation' == $this->ced_pro_type( $product_id ) ) {
			$product_id = $this->parent_id;
		}

		$wc_product  = wc_get_product( $product_id );
		$data        = $wc_product->get_data();
		$category_id = isset( $data['category_ids'] ) ? $data['category_ids'] : array();

		foreach ( $category_id as $key => $value ) {
			$profile_id = get_term_meta( $value, 'ced_etsy_profile_id_' . $shop_name, true );

			if ( ! empty( $profile_id ) ) {
				break;

			}
		}

		global $wpdb;
		if ( isset( $profile_id ) && ! empty( $profile_id ) ) {
			$this->profile_id        = $profile_id;
			$this->is_profile_assing = true;
			$profile_data            = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_etsy_profiles WHERE `id`=%d ", $profile_id ), 'ARRAY_A' );
			if ( is_array( $profile_data ) ) {
				$profile_data       = isset( $profile_data[0] ) ? $profile_data[0] : $profile_data;
				$this->profile_name = isset( $profile_data['profile_name'] ) ? $profile_data['profile_name'] : '';
				$profile_data       = isset( $profile_data['profile_data'] ) ? json_decode( $profile_data['profile_data'], true ) : array();
			}
		} else {
			$this->is_profile_assing = false;
			return 'false';
		}
		$this->profile_data = isset( $profile_data ) ? $profile_data : '';
		return $this->profile_data;
	}


	/**
	 * **********************************************
	 * GET FORMATTED DATA FOR UPLOAD/UPDATE PRODUCTS
	 * **********************************************
	 *
	 * @since 1.0.0
	 *
	 * @param int    $product_id Woo Product ids.
	 * @param string $shop_name Active etsy shop name.
	 *
	 * @return $arguments all possible arguments .
	 */
	public function get_formatted_data( $product_id = '', $shop_name = '' ) {
		$this->ced_etsy_check_profile( $product_id, $shop_name );

		$this->product_id       = $product_id;
		$product_field_instance = \Cedcommerce\Template\Ced_Template_Product_Fields::get_instance();
		$etsy_data_field        = $product_field_instance->get_custom_products_fields();
		$this->pro_data         = array();
		$sections               = array( 'required', 'recommended', 'optional', 'shipping', 'personalization' );

		$this->is_downloadable = isset( $this->product['downloadable'] ) ? $this->product['downloadable'] : 0;
		if ( $this->is_downloadable ) {
			$this->s = isset( $this->product['downloads'] ) ? $this->product['downloads'] : array();
		}
		foreach ( $sections as $section ) {
			foreach ( $etsy_data_field[ $section ] as $section_attributes ) {

				$ced_etsy_settings_category = get_option( 'ced_etsy_settings_category', array() );
				if ( isset( $ced_etsy_settings_category[ $section ] ) ) {
					$this->{$section} = true;
				} else {
					$this->{$section} = false;
				}

				$meta_key = $section_attributes['id'];
				$pro_val  = get_post_meta( $product_id, $meta_key, true );// getting info from product level
				if ( '' == $pro_val ) {
					$pro_val = $this->fetch_meta_value( $product_id, $meta_key );// getting info from profile level
				}
				if ( '' == $pro_val ) {
					$pro_val = isset( $this->ced_global_settings['product_data'][ $meta_key ]['default'] ) ? $this->ced_global_settings['product_data'][ $meta_key ]['default'] : '';// getting info from global level
				}
				if ( '' == $pro_val ) {
					$metakey = isset( $this->ced_global_settings['product_data'][ $meta_key ]['metakey'] ) ? $this->ced_global_settings['product_data'][ $meta_key ]['metakey'] : '';// getting info from global level
					if ( ! empty( $metakey ) ) {
						$pro_val = $this->fetch_meta_value( $product_id, $metakey );// getting info from global level
					}
				}
				$this->pro_data[ trim( str_replace( '_ced_etsy_', ' ', $meta_key ) ) ] = ! empty( $pro_val ) ? $pro_val : '';

			}
		};

		if ( ! $this->is_profile_assing ) {
			$this->error['has_error'] = true;
			$this->error['error']     = 'Template not assigned <span class="ced_etsy_modal">Learn how to create</span>';
			return $this->error;
		}

		if ( ! $this->prepare_required_fields() ) {
			return $this->error;
		}

		$this->prepare_rec_opt_ship_per_fields();

		if ( $this->is_downloadable ) {
			$this->product_arguements['type'] = 'download';
		}
		return $this->product_arguements;
	}


	public function prepare_required_fields() {
		$required_fields = array(
			'quantity',
			'title',
			'description',
			'price',
			'who_made',
			'when_made',
			'taxonomy_id',
			'shipping_profile_id',
			'is_supply',
		);

		if ( $this->is_downloadable ) {
			$index = array_search( 'shipping_profile_id', $required_fields );
			if ( $index ) {
				unset( $required_fields[ $index ] );
			}
		}

		$valid     = true;
		$error_msg = '';

		foreach ( $required_fields as $index ) {
			if ( method_exists( $this, 'get_' . $index ) ) {
				$info = call_user_func( array( $this, 'get_' . $index ) );
				if ( false !== $info ) {
					$this->product_arguements[ $index ] = $info;
				} else {
					$valid      = false;
					$error_msg .= '[ ' . ucwords( $index ) . ' is required but missing from product information ]';
				}
			}
		}

		if ( ! $valid ) {
			$this->error['has_error'] = true;
			$this->error['error']     = $error_msg;
		}

		return $valid;
	}

	public function prepare_rec_opt_ship_per_fields() {
		$required_fields = array(
			'materials',
			'shop_section_id',
			'tags',
			'styles',
			'production_partner_ids',
			'processing_min',
			'processing_max',
			'is_personalizable',
			'personalization_is_required',
			'personalization_char_count_max',
			'personalization_instructions',
			'is_customizable',
			'is_taxable',
		);

		if ( /*$this->shipping*/ 1 ) {
			$required_fields = array_merge(
				$required_fields,
				array(
					'item_weight',
					'item_length',
					'item_width',
					'item_height',
					'item_weight_unit',
					'item_dimensions_unit',
				)
			);
		}

		if ( $this->is_upload ) {
			$required_fields = array_merge(
				$required_fields,
				array(
					'should_auto_renew',
				)
			);
		}
		foreach ( $required_fields as $index ) {
			if ( method_exists( $this, 'get_' . $index ) ) {
				$info = call_user_func( array( $this, 'get_' . $index ) );
				if ( false !== $info ) {
					$this->product_arguements[ $index ] = $info;
				}
			}
		}
	}


	public function get_quantity() {
		$quantity = isset( $this->pro_data['stock'] ) ? $this->pro_data['stock'] : '';
		if ( '' === $quantity ) {
			$quantity = get_post_meta( $this->product_id, '_stock', true );
			if ( 'variable' == $this->product_type ) {
				$quantity = 1;
			}
			$manage_stock = get_post_meta( $this->product_id, '_manage_stock', true );
			$stock_status = get_post_meta( $this->product_id, '_stock_status', true );
			if ( 'instock' == $stock_status && 'no' == $manage_stock ) {
				$quantity = ( '' !== $this->pro_data['default_stock'] ) ? $this->pro_data['default_stock'] : 1;
			}

			if ( $quantity > 99 ) {
				$quantity = 99;
			}

			if ( $quantity <= 0 ) {
				$quantity = 0;
			}
		}
		/** Alter etsy product qty
				 *
				 * @since 2.0.0
				 */
		return ( '' === $quantity ) ? false : apply_filters( 'ced_etsy_quantity', (int) $quantity, $this->product_id, $this->shop_name );
	}

	public function get_title() {
		$title = isset( $this->pro_data['title'] ) ? $this->pro_data['title'] : '';
		$title = ! empty( $title ) ? $title : $this->product['name'];
		$title = $this->pro_data['title_pre'] . ' ' . $title . ' ' . $this->pro_data['title_post'];
		if ( '' != trim( $title ) ) {
			/** Alter etsy product title
				 *
				 * @since 2.0.0
				 */
			return apply_filters( 'ced_etsy_title', (string) trim( $title ), $this->product_id, $this->shop_name );
		}
		return false;
	}

	public function get_description() {
		$description = isset( $this->pro_data['description'] ) ? $this->pro_data['description'] : '';
		$description = ! empty( $description ) ? $description : $this->product['description'];
		if ( '' != trim( strip_tags( $description ) ) ) {
			/** Alter etsy product description
				 *
				 * @since 2.0.0
				 */
			return apply_filters( 'ced_etsy_description', (string) trim( strip_tags( html_entity_decode( $description ) ) ), $this->product_id, $this->shop_name );
		}
		return false;
	}

	public function get_price() {
		$price = isset( $this->pro_data['price'] ) ? $this->pro_data['price'] : '';

		if ( 'variable' == $this->product_type ) {
			$variations = $this->prod_obj->get_available_variations();
			if ( isset( $variations['0']['display_regular_price'] ) ) {
				$price = $variations['0']['display_regular_price'];
			}
		}

		$price        = ! empty( $price ) ? $price : $this->product['price'];
		$markup_type  = $this->pro_data['markup_type'];
		$markup_value = (float) $this->pro_data['markup_value'];
		if ( ! empty( $markup_type ) && '' !== $markup_value ) {
			$price = ( 'Fixed_Increased' == $markup_type ) ? ( (float) $price + $markup_value ) : ( (float) $price + ( ( $markup_value / 100 ) * (float) $price ) );
		}

		$price = (float) $price;

		if ( '' != (float) round( $price, 2 ) ) {
			/** Alter etsy product price
				 *
				 * @since 2.0.0
				 */
			return apply_filters( 'ced_etsy_price', (float) round( $price, 2 ), $this->product_id, $this->shop_name );
		}
		return false;
	}

	public function get_who_made() {
		$who_made = ! empty( $this->pro_data['who_made'] ) ? $this->pro_data['who_made'] : 'i_did';
		return (string) $who_made;
	}

	public function get_when_made() {
		$when_made = ! empty( $this->pro_data['when_made'] ) ? $this->pro_data['when_made'] : '2020_2022';
		return (string) $when_made;
	}

	public function get_taxonomy_id() {
		$taxonomy_id = $this->fetch_meta_value( $this->product_id, '_umb_etsy_category' );
		if ( (int) $taxonomy_id ) {
			return (int) $taxonomy_id;
		}
		return false;
	}

	public function get_shipping_profile_id() {
		$shipping_profile = ! empty( $this->pro_data['shipping_profile'] ) ? $this->pro_data['shipping_profile'] : 0;
		if ( doubleval( $shipping_profile ) ) {
			return doubleval( $shipping_profile );
		}
		return false;
	}

	public function get_is_supply() {
		$product_supply = ( 'true' == $this->pro_data['product_supply'] ) ? 1 : 0;
		return (int) $product_supply;
	}


	public function get_materials() {
		$get_materials = ! empty( $this->pro_data['materials'] ) ? $this->pro_data['materials'] : array();
		$material_info = array();
		if ( ! empty( $get_materials ) ) {
			$explode_materials = array_filter( explode( ',', $get_materials ) );
			foreach ( $explode_materials as $key_tags => $material ) {
				$material = str_replace( ' ', '-', $material );
				$material = preg_replace( '/[^A-Za-z0-9\-]/', '', $material );
				$material = str_replace( '-', ' ', $material );
				if ( $key_tags <= 12 && strlen( $material ) <= 20 ) {
					$material_info[] = $material;
				}
			}
			$material_info = array_filter( array_values( array_unique( $material_info ) ) );
			if ( ! empty( $material_info ) ) {
				return $material_info;
			}
		}
		return false;
	}

	public function get_shop_section_id() {
		$shop_section = ! empty( $this->pro_data['shop_section'] ) ? $this->pro_data['shop_section'] : 0;
		if ( (int) $shop_section ) {
			return (int) $shop_section;
		}
		return false;
	}

	public function get_tags() {
		$get_tags = ! empty( $this->pro_data['tags'] ) ? $this->pro_data['tags'] : array();
		$tag_info = array();
		if ( ! empty( $get_tags ) ) {
			$explode_materials = array_filter( explode( ',', $get_tags ) );
			foreach ( $explode_materials as $key_tags => $tag_name ) {
				$tag_name = trim( $tag_name );
				$tag_name = str_replace( ' ', '-', $tag_name );
				$tag_name = preg_replace( '/[^A-Za-z0-9\-]/', '', $tag_name );
				$tag_name = str_replace( '-', ' ', $tag_name );
				if ( $key_tags <= 12 && strlen( $tag_name ) <= 20 ) {
					$tag_info[] = $tag_name;
				}
			}

			$tag_info = array_filter( array_values( array_unique( $tag_info ) ) );
			if ( ! empty( $tag_info ) ) {
				return $tag_info;
			}
		}

		if ( empty( $get_tags ) ) {
			$get_tags = get_the_terms( $this->product_id, 'product_tag' );
			if ( isset( $get_tags ) && ! empty( $get_tags ) && is_array( $get_tags ) ) {
				foreach ( $get_tags as $tag_key => $tags ) {
					$tag_name = $tags->name;
					$tag_name = str_replace( ' ', '-', $tag_name );
					$tag_name = preg_replace( '/[^A-Za-z0-9\-]/', '', $tag_name );
					$tag_name = str_replace( '-', ' ', $tag_name );
					if ( $tag_key <= 12 && strlen( $tag_name ) <= 20 ) {
						$tag_info[] = $tag_name;
					}
				}
				return $tag_info;
			}
		}
		return false;
	}

	public function get_styles() {
		$get_styles = ! empty( $this->pro_data['styles'] ) ? $this->pro_data['styles'] : array();
		$style_info = array();
		if ( ! empty( $get_styles ) ) {
			$explode_materials = array_filter( explode( ',', $get_styles ) );
			foreach ( $explode_materials as $key_tags => $style ) {
				$style = str_replace( ' ', '-', $style );
				$style = preg_replace( '/[^A-Za-z0-9\-]/', '', $style );
				$style = str_replace( '-', ' ', $style );
				if ( $key_tags <= 2 && strlen( $style ) <= 20 ) {
					$style_info[] = $style;
				}
			}
			$style_info = array_filter( array_values( array_unique( $style_info ) ) );
			if ( ! empty( $style_info ) ) {
				return $style_info;
			}
		}
		return false;
	}

	public function get_production_partner_ids() {
		$shipping_profile = ! empty( $this->pro_data['production_partners'] ) ? $this->pro_data['production_partners'] : array();
		if ( ! empty( $shipping_profile ) ) {
			return $shipping_profile;
		}
		return false;
	}

	public function get_processing_min() {
		$processing_min = ! empty( $this->pro_data['processing_min'] ) ? (int) $this->pro_data['processing_min'] : 1;
		return $processing_min;
	}

	public function get_processing_max() {
		$processing_max = ! empty( $this->pro_data['processing_max'] ) ? (int) $this->pro_data['processing_max'] : 3;
		return $processing_max;
	}

	public function get_item_weight() {
		$item_weight = ! empty( $this->pro_data['item_weight'] ) ? $this->pro_data['item_weight'] : get_post_meta( $this->product_id, '_weight', true );
		if ( ! empty( $item_weight ) ) {
			return (float) $item_weight;
		}
		return false;
	}

	public function get_item_length() {
		$item_length = ! empty( $this->pro_data['item_length'] ) ? $this->pro_data['item_length'] : get_post_meta( $this->product_id, '_length', true );
		if ( ! empty( $item_length ) ) {
			return (float) $item_length;
		}
		return false;
	}

	public function get_item_width() {
		$item_width = ! empty( $this->pro_data['item_width'] ) ? $this->pro_data['item_width'] : get_post_meta( $this->product_id, '_width', true );
		if ( ! empty( $item_width ) ) {
			return (float) $item_width;
		}
		return false;
	}

	public function get_item_height() {
		$item_height = ! empty( $this->pro_data['item_height'] ) ? $this->pro_data['item_height'] : get_post_meta( $this->product_id, '_height', true );
		if ( ! empty( $item_height ) ) {
			return (float) $item_height;
		}
		return false;
	}

	public function get_item_weight_unit() {
		$item_weight_unit = ! empty( $this->pro_data['item_weight_unit'] ) ? $this->pro_data['item_weight_unit'] : get_option( 'woocommerce_weight_unit', '' );
		if ( ! empty( $item_weight_unit ) ) {
			return (string) $item_weight_unit;
		}
		return false;
	}

	public function get_item_dimensions_unit() {
		$item_dimensions_unit = ! empty( $this->pro_data['item_dimensions_unit'] ) ? $this->pro_data['item_dimensions_unit'] : get_option( 'woocommerce_dimension_unit', '' );
		if ( ! empty( $item_dimensions_unit ) ) {
			return (string) $item_dimensions_unit;
		}
		return false;
	}

	public function get_is_personalizable() {
		$is_personalizable = ( 'true' == $this->pro_data['is_personalizable'] ) ? 1 : 0;
		return (int) $is_personalizable;
	}

	public function get_personalization_is_required() {
		$personalization_is_required = ( 'true' == $this->pro_data['personalization_is_required'] ) ? 1 : 0;
		return (int) $personalization_is_required;
	}

	public function get_personalization_char_count_max() {
		$personalization_char_count_max = ! empty( $this->pro_data['personalization_char_count_max'] ) ? $this->pro_data['personalization_char_count_max'] : false;
		if ( (int) $personalization_char_count_max ) {
			return (int) $personalization_char_count_max;
		}
		return false;
	}

	public function get_personalization_instructions() {
		$personalization_instructions = ! empty( $this->pro_data['personalization_instructions'] ) ? $this->pro_data['personalization_instructions'] : '';
		if ( ! empty( $personalization_instructions ) ) {
			return (string) $personalization_instructions;
		}
		return false;
	}

	public function get_is_customizable() {
		$is_customizable = ( 'true' == $this->pro_data['is_customizable'] ) ? 1 : 0;
		return (int) $is_customizable;
	}

	public function get_is_taxable() {
		$is_taxable = ( 'true' == $this->pro_data['is_taxable'] ) ? 1 : 0;
		return (int) $is_taxable;
	}

	public function get_state() {
		$product_list_type = ! empty( $this->ced_global_settings['product_data']['_ced_etsy_product_list_type']['default'] ) ? $this->ced_global_settings['product_data']['_ced_etsy_product_list_type']['default'] : 'draft';
		return (string) $product_list_type;
	}

	public function get_should_auto_renew() {

		$should_auto_renew = ( 'true' == $this->pro_data['should_auto_renew'] ) ? 1 : 0;
		return (int) $should_auto_renew;
	}



	/**
	 * *****************************************
	 * GET VARIATION DATA TO UPDATE ON ETSY
	 * *****************************************
	 *
	 * @since 1.0.0
	 *
	 * @param string $product_id Product lsting  ids.
	 * @param string $shop_name Product  ids.
	 * @param string $is_sync Active shopName.
	 *
	 * @return $reponse
	 */

	public function ced_variation_details( $product_id = '', $shop_name = '', $is_sync = false ) {

			$property_ids          = array();
			$product               = wc_get_product( $product_id );
			$variations            = $product->get_available_variations();
			$attributes            = array();
			$parent_sku            = get_post_meta( $product_id, '_sku', true );
			$parent_attributes     = $product->get_variation_attributes();
			$possible_combinations = array_values( wc_array_cartesian( ( $parent_attributes ) ) );
			$no_property_to_use    = count( $parent_attributes );
			$com_to_be_prepared    = array();
		foreach ( $possible_combinations as $po_attr => $po_values ) {
			$att_name_po = '';
			$po_values   = array_reverse( $po_values );

			foreach ( $po_values as $kk => $po_value ) {
				if ( ! isset( $parent_attributes[ $kk ] ) ) {
					continue;
				}
				$att_name_po .= $po_value . '~';
			}

			$com_to_be_prepared[ trim( strtolower( $att_name_po ) ) ] = trim( strtolower( $att_name_po ) );
		}

		foreach ( $variations as $variation ) {
			$var_id               = $variation['variation_id'];
			$attribute_one_mapped = false;
			$attribute_two_mapped = false;
			$var_product          = wc_get_product( $variation['variation_id'] );
			$attributes           = $var_product->get_variation_attributes();
			$count                = 0;
			$property_values      = array();
			$offerings            = array();
			$var_array            = array();
			$_count               = 0;

			$var_att_array = '';
			foreach ( $attributes as $property_name => $property_value ) {

				$product_terms = get_the_terms( $product_id, $property_name );
				if ( is_array( $product_terms ) && ! empty( $product_terms ) ) {
					foreach ( $product_terms as $tempkey => $tempvalue ) {
						if ( $tempvalue->slug == $property_value ) {
							$property_value = $tempvalue->name;
							break;
						}
					}
				}

				$_count ++;
				$property_id = 513;
				if ( ! isset( $p_name_1 ) ) {
					$p_name_1 = ucwords( str_replace( array( 'attribute_pa_', 'attribute_' ), array( '', '' ), $property_name ) );
				}

				if ( $count > 0 ) {
					if ( ! isset( $p_name_2 ) ) {
						$p_name_2 = ucwords( str_replace( array( 'attribute_pa_', 'attribute_' ), array( '', '' ), $property_name ) );
					}
					$property_id = 514;
				}

				$property_values[] = array(
					'property_id'   => (int) $property_id,
					'value_ids'     => array( $property_id ),
					'property_name' => ucwords( str_replace( array( 'attribute_pa_', 'attribute_' ), array( '', '' ), $property_name ) ),
					'values'        => array( ucwords( strtolower( $property_value ) ) ),

				);

				$var_att_array .= $property_value . '~';
				$count++;
				$property_ids[] = $property_id;
			}

			if ( isset( $com_to_be_prepared[ strtolower( $var_att_array ) ] ) ) {
					unset( $com_to_be_prepared[ strtolower( $var_att_array ) ] );
			}

			$this->get_formatted_data( $var_id, $shop_name );
			$price        = $this->get_price();
			$var_quantity = $this->get_quantity();
			$var_sku      = $variation['sku'];
			if ( empty( $var_sku ) || strlen( $var_sku ) > 32 || $parent_sku == $var_sku ) {
				$var_sku = (string) $variation['variation_id'];
			}

			$offerings      = array(
				array(
					'price'      => (float) $price,
					'quantity'   => (int) $var_quantity,
					'is_enabled' => 1,
				),
			);
			$variation_info = array(
				'sku'             => $var_sku,
				'property_values' => $property_values,
				'offerings'       => $offerings,
			);
			$offer_info[]   = $variation_info;
		}

			$remaining_combination = $com_to_be_prepared;
		foreach ( $remaining_combination as $combination ) {
			$property_values_remaining = array_values( array_filter( explode( '~', $combination ) ) );
			if ( isset( $property_values_remaining[1] ) ) {
				$offer_info[] = array(

					'sku'             => '',
					'property_values' => array(
						array(
							'property_id'   => (int) 513,
							'value_ids'     => array( 513 ),
							'property_name' => $p_name_1,
							'values'        => array(
								isset( $property_values_remaining[0] ) ? ucwords( strtolower( $property_values_remaining[0] ) ) : '',
							),
						),
						array(
							'property_id'   => (int) 514,
							'value_ids'     => array( 514 ),
							'property_name' => $p_name_2,
							'values'        => array(
								isset( $property_values_remaining[1] ) ? ucwords( strtolower( $property_values_remaining[1] ) ) : '',
							),
						),
					),
					'offerings'       => array(
						array(
							'price'      => (float) $price,
							'quantity'   => 0,
							'is_enabled' => 0,
						),
					),

				);
			} elseif ( isset( $property_values_remaining[0] ) ) {
				$offer_info[] = array(

					'sku'             => '',
					'property_values' => array(
						array(
							'property_id'   => (int) 513,
							'value_ids'     => array( 513 ),
							'property_name' => $p_name_1,
							'values'        => array(
								isset( $property_values_remaining[0] ) ? ucwords( strtolower( $property_values_remaining[0] ) ) : '',
							),
						),

					),
					'offerings'       => array(
						array(
							'price'      => (float) $price,
							'quantity'   => 0,
							'is_enabled' => 0,
						),
					),

				);
			}
		}

			$property_ids = array_unique( $property_ids );
			$property_ids = implode( ',', $property_ids );
			$payload      = array(
				'products'             => $offer_info,
				'price_on_property'    => $property_ids,
				'quantity_on_property' => $property_ids,
				'sku_on_property'      => $property_ids,
			);
			return $payload;
	}


	/**
	 * *************************************************************************************************************
	 * This function fetches meta value of a product in accordance with profile assigned and meta value available.
	 * *************************************************************************************************************
	 *
	 * @since 1.0.0
	 *
	 * @param int    $product_id Product  ids.
	 * @param string $metaKey meta key name .
	 * @param bool   $is_variation variation or not.
	 *
	 * @return $meta data
	 */

	private function fetch_meta_value( $product_id, $metaKey, $is_variation = false ) {
		if ( isset( $this->is_profile_assing ) && $this->is_profile_assing ) {

			$_product = wc_get_product( $product_id );
			if ( ! is_object( $_product ) ) {
				return false;
			}

			if ( '_woocommerce_title' == $metaKey ) {
				$product = wc_get_product( $product_id );
				return $product->get_title();
			}if ( '_woocommerce_short_description' == $metaKey ) {
				$product = wc_get_product( $product_id );
				if ( $product->get_type() == 'variation' ) {
					$_parent_obj = wc_get_product( $product->get_parent_id() );
					return $_parent_obj->get_short_description();
				}
				return $product->get_short_description();

			}if ( '_woocommerce_description' == $metaKey ) {
				$product = wc_get_product( $product_id );
				if ( $product->get_type() == 'variation' ) {
					$_parent_obj = wc_get_product( $product->get_parent_id() );
					return $_parent_obj->get_description();
				}
				return $product->get_description();
			}

			if ( WC()->version < '3.0.0' ) {
				if ( 'variation' == $_product->product_type ) {
					$parentId = $_product->parent->id;
				} else {
					$parentId = '0';
				}
			} elseif ( 'variation' == $_product->get_type() ) {
					$parentId = $_product->get_parent_id();
			} else {
				$parentId = '0';
			}

			if ( ! empty( $this->profile_data ) && isset( $this->profile_data[ $metaKey ] ) ) {
				$profileData     = $this->profile_data[ $metaKey ];
				$tempProfileData = $this->profile_data[ $metaKey ];
				if ( isset( $tempProfileData['default'] ) && ! empty( $tempProfileData['default'] ) && ! empty( $tempProfileData['default'] ) && ! is_null( $tempProfileData['default'] ) ) {
					$value = $tempProfileData['default'];
				} elseif ( isset( $tempProfileData['metakey'] ) ) {

					if ( '_woocommerce_title' == $tempProfileData['metakey'] ) {
						$product = wc_get_product( $product_id );
						return $product->get_title();
					}if ( '_woocommerce_short_description' == $tempProfileData['metakey'] ) {
						$product = wc_get_product( $product_id );
						if ( $product->get_type() == 'variation' ) {
							$_parent_obj = wc_get_product( $product->get_parent_id() );
							return $_parent_obj->get_short_description();
						}
						return $product->get_short_description();

					}if ( '_woocommerce_description' == $tempProfileData['metakey'] ) {
						$product = wc_get_product( $product_id );
						if ( $product->get_type() == 'variation' ) {
							$_parent_obj = wc_get_product( $product->get_parent_id() );
							return $_parent_obj->get_description();
						}
						return $product->get_description();
					}

					if ( strpos( $tempProfileData['metakey'], 'umb_pattr_' ) !== false ) {

						$wooAttribute = explode( 'umb_pattr_', $tempProfileData['metakey'] );
						$wooAttribute = end( $wooAttribute );

						if ( WC()->version < '3.0.0' ) {
							if ( 'variation' == $_product->product_type ) {
								$attributes = $_product->get_variation_attributes();
								if ( isset( $attributes[ 'attribute_pa_' . $wooAttribute ] ) && ! empty( $attributes[ 'attribute_pa_' . $wooAttribute ] ) ) {
									$wooAttributeValue = $attributes[ 'attribute_pa_' . $wooAttribute ];
									if ( '0' != $parentId ) {
										$product_terms = get_the_terms( $parentId, 'pa_' . $wooAttribute );
									} else {
										$product_terms = get_the_terms( $product_id, 'pa_' . $wooAttribute );
									}
								} else {
									$wooAttributeValue = $_product->get_attribute( 'pa_' . $wooAttribute );

									$wooAttributeValue = explode( ',', $wooAttributeValue );
									$wooAttributeValue = $wooAttributeValue[0];

									if ( '0' != $parentId ) {
										$product_terms = get_the_terms( $parentId, 'pa_' . $wooAttribute );
									} else {
										$product_terms = get_the_terms( $product_id, 'pa_' . $wooAttribute );
									}
								}

								if ( is_array( $product_terms ) && ! empty( $product_terms ) ) {
									foreach ( $product_terms as $tempkey => $tempvalue ) {
										if ( $tempvalue->slug == $wooAttributeValue ) {
											$wooAttributeValue = $tempvalue->name;
											break;
										}
									}
									if ( isset( $wooAttributeValue ) && ! empty( $wooAttributeValue ) ) {
										$value = $wooAttributeValue;
									} else {
										$value = get_post_meta( $product_id, $metaKey, true );
									}
								} else {
									$value = get_post_meta( $product_id, $metaKey, true );
								}
							} else {
								$wooAttributeValue = $_product->get_attribute( 'pa_' . $wooAttribute );
								$product_terms     = get_the_terms( $product_id, 'pa_' . $wooAttribute );
								if ( is_array( $product_terms ) && ! empty( $product_terms ) ) {
									foreach ( $product_terms as $tempkey => $tempvalue ) {
										if ( $tempvalue->slug == $wooAttributeValue ) {
											$wooAttributeValue = $tempvalue->name;
											break;
										}
									}
									if ( isset( $wooAttributeValue ) && ! empty( $wooAttributeValue ) ) {
										$value = $wooAttributeValue;
									} else {
										$value = get_post_meta( $product_id, $metaKey, true );
									}
								} else {
									$value = get_post_meta( $product_id, $metaKey, true );
								}
							}
						} elseif ( 'variation' == $_product->get_type() ) {

								$attributes = $_product->get_variation_attributes();
							if ( isset( $attributes[ 'attribute_pa_' . $wooAttribute ] ) && ! empty( $attributes[ 'attribute_pa_' . $wooAttribute ] ) ) {

								$wooAttributeValue = $attributes[ 'attribute_pa_' . $wooAttribute ];
								if ( '0' != $parentId ) {
									$product_terms = get_the_terms( $parentId, 'pa_' . $wooAttribute );
								} else {
									$product_terms = get_the_terms( $product_id, 'pa_' . $wooAttribute );
								}
							} elseif ( isset( $attributes[ 'attribute_' . $wooAttribute ] ) && ! empty( $attributes[ 'attribute_' . $wooAttribute ] ) ) {

								$wooAttributeValue = $attributes[ 'attribute_' . $wooAttribute ];

								if ( '0' != $parentId ) {
									$product_terms = get_the_terms( $parentId, 'pa_' . $wooAttribute );
								} else {
									$product_terms = get_the_terms( $product_id, 'pa_' . $wooAttribute );
								}
							} else {

								$wooAttributeValue = $_product->get_attribute( 'pa_' . $wooAttribute );

								$wooAttributeValue = explode( ',', $wooAttributeValue );
								$wooAttributeValue = $wooAttributeValue[0];

								if ( '0' != $parentId ) {
									$product_terms = get_the_terms( $parentId, 'pa_' . $wooAttribute );
								} else {
									$product_terms = get_the_terms( $product_id, 'pa_' . $wooAttribute );
								}
							}

							if ( is_array( $product_terms ) && ! empty( $product_terms ) ) {
								foreach ( $product_terms as $tempkey => $tempvalue ) {
									if ( $tempvalue->slug == $wooAttributeValue ) {
										$wooAttributeValue = $tempvalue->name;
										break;
									}
								}
								if ( isset( $wooAttributeValue ) && ! empty( $wooAttributeValue ) ) {
									$value = $wooAttributeValue;
								} else {
									$value = get_post_meta( $product_id, $metaKey, true );
								}
							} elseif ( isset( $wooAttributeValue ) && ! empty( $wooAttributeValue ) ) {
								$value = $wooAttributeValue;
							} else {
								$value = get_post_meta( $product_id, $metaKey, true );
							}
						} else {
							$wooAttributeValue = $_product->get_attribute( 'pa_' . $wooAttribute );
							$product_terms     = get_the_terms( $product_id, 'pa_' . $wooAttribute );
							if ( is_array( $product_terms ) && ! empty( $product_terms ) ) {
								foreach ( $product_terms as $tempkey => $tempvalue ) {
									if ( $tempvalue->slug == $wooAttributeValue ) {
										$wooAttributeValue = $tempvalue->name;
										break;
									}
								}
								if ( isset( $wooAttributeValue ) && ! empty( $wooAttributeValue ) ) {
									$value = $wooAttributeValue;
								} else {
									$value = get_post_meta( $product_id, $metaKey, true );
								}
							} else {
								$value = get_post_meta( $product_id, $metaKey, true );
							}
						}
					} else {

						$value = get_post_meta( $product_id, $tempProfileData['metakey'], true );
						if ( '_thumbnail_id' == $tempProfileData['metakey'] ) {
							$value = wp_get_attachment_image_url( get_post_meta( $product_id, '_thumbnail_id', true ), 'thumbnail' ) ? wp_get_attachment_image_url( get_post_meta( $product_id, '_thumbnail_id', true ), 'thumbnail' ) : '';
						}
						if ( ! isset( $value ) || empty( $value ) || '' == $value || is_null( $value ) || '0' == $value || 'null' == $value ) {
							if ( '0' != $parentId ) {

								$value = get_post_meta( $parentId, $tempProfileData['metakey'], true );
								if ( '_thumbnail_id' == $tempProfileData['metakey'] ) {
									$value = wp_get_attachment_image_url( get_post_meta( $parentId, '_thumbnail_id', true ), 'thumbnail' ) ? wp_get_attachment_image_url( get_post_meta( $parentId, '_thumbnail_id', true ), 'thumbnail' ) : '';
								}

								if ( ! isset( $value ) || empty( $value ) || '' == $value || is_null( $value ) ) {
									$value = get_post_meta( $product_id, $metaKey, true );

								}
							} else {
								$value = get_post_meta( $product_id, $metaKey, true );
							}
						}
					}
				} else {
					$value = get_post_meta( $product_id, $metaKey, true );
				}
			} else {
				$value = get_post_meta( $product_id, $metaKey, true );
			}
		} else {
			$value = get_post_meta( $product_id, $metaKey, true );
		}

		return $value;
	}
}
