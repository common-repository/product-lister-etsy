<?php
namespace Cedcommerce\Template;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 *Used to render the Product Fields
 *
 * @since      1.0.0
 *
 * @package    Woocommerce etsy Integration
 * @subpackage Woocommerce etsy Integration/admin/helper
 */

if ( ! class_exists( 'Ced_Template_Product_Fields' ) ) {

	/**
	 * Single product related functionality.
	 *
	 * Manage all single product related functionality required for listing product on marketplaces.
	 *
	 * @since      1.0.0
	 * @package    Woocommerce etsy Integration
	 * @subpackage Woocommerce etsy Integration/admin/helper
	 */
	class Ced_Template_Product_Fields {

		/**
		 * The Instace of Ced_Template_Product_Fields.
		 *
		 * @since    1.0.0
		 * @var      $_instance   The Instance of Ced_Template_Product_Fields class.
		 */
		private static $_instance;

		/**
		 * Ced_Template_Product_Fields Instance.
		 *
		 * Ensures only one instance of Ced_Template_Product_Fields is loaded or can be loaded.
		 *
		 * @since 1.0.0
		 * @static
		 * @return Ced_Template_Product_Fields instance.
		 */
		public static function get_instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Get product custom fields for preparing
		 * product data information to send on different
		 * marketplaces accoding to there requirement.
		 *
		 * @since 1.0.0
		 * @param string $type  required|framework_specific|common
		 * @param bool   $ids  true|false
		 * @return array  fields array
		 */
		public static function get_custom_products_fields( $shop_name = '' ) {

			$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : $shop_name;
			if ( empty( $shop_name ) ) {
				$shop_name = get_option( 'ced_etsy_shop_name', '' );
			}
			$shop_id  = get_etsy_shop_id( $shop_name );
			$sections = array();
			if ( ! empty( $shop_id ) ) {
				$action = "application/shops/{$shop_id}/sections";
				/** Refresh token
				 *
				 * @since 2.0.0
				 */
				do_action( 'ced_etsy_refresh_token', $shop_name );
				$shop_sections = etsy_request()->get( $action, $shop_name );
				if ( isset( $shop_sections['count'] ) && $shop_sections['count'] >= 1 ) {
					$shop_sections = $shop_sections['results'];
					foreach ( $shop_sections as $key => $value ) {
						$sections[ $value['shop_section_id'] ] = $value['title'];
					}
				}
			}

			/*GET COUNTRIES LIST FOR SHIPPING TEMPLATE */
			$shop_id             = get_etsy_shop_id( $shop_name );
			$production_partners = array();
			/** Refresh token
				 *
				 * @since 2.0.0
				 */
			do_action( 'ced_etsy_refresh_token', $shop_name );
			$action   = "application/shops/{$shop_id}/production-partners";
			$partners = etsy_request()->get( $action, $shop_name );
			if ( isset( $partners['count'] ) && $partners['count'] >= 1 ) {
				foreach ( $partners['results'] as $key => $value ) {
					$production_partners[ $value['production_partner_id'] ] = $value['partner_name'] . ' - ' . $value['location'];
				}
			}

			$shipping_templates                                = array();
			$shipping_templates['create_new_shipping_profile'] = '+ Create New Shipping Profile +';
			$action         = "application/shops/{$shop_id}/shipping-profiles";
			$e_shpng_tmplts = etsy_request()->get( $action, $shop_name );
			if ( isset( $e_shpng_tmplts['count'] ) && $e_shpng_tmplts['count'] >= 1 ) {
				foreach ( $e_shpng_tmplts['results'] as $key => $value ) {
					$shipping_templates[ $value['shipping_profile_id'] ] = $value['title'];
				}
			}
			$required_fields = array(
				'required'        => array(
					array(
						'type'   => '_hidden',
						'id'     => '_umb_etsy_category',
						'fields' => array(
							'id'          => '_umb_etsy_category',
							'label'       => __( 'Etsy category', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Specify the Etsy category.', 'product-lister-etsy' ),
							'type'        => 'hidden',
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),
					array(
						'type'   => '_hidden',
						'id'     => '_umb_etsy_category_name',
						'fields' => array(
							'id'          => '_umb_etsy_category_name',
							'label'       => __( 'Etsy category', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Specify the Etsy category.', 'product-lister-etsy' ),
							'type'        => 'hidden',
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),
					array(
						'type'   => '_select',
						'id'     => '_ced_etsy_product_list_type',
						'fields' => array(
							'id'          => '_ced_etsy_product_list_type',
							'label'       => __( 'Product listing type', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Product listing type , whether you want to upload the product on etsy as active or draft.', 'product-lister-etsy' ),
							'type'        => 'select',
							'options'     => array(
								'draft'  => 'Draft',
								'active' => 'Active',
							),
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => 'draft',
						),
					),
					array(
						'type'   => '_select',
						'id'     => '_ced_etsy_shipping_profile',
						'fields' => array(
							'id'          => '_ced_etsy_shipping_profile',
							'label'       => __( 'Shipping profile', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Shipping profile to be used for products while uploading on etsy.If you do not have any etsy shipping profile you can <i>Create a new one here</i></a>.', 'product-lister-etsy' ),
							'type'        => 'select',
							'options'     => $shipping_templates,
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),
					array(
						'type'   => '_select',
						'id'     => '_ced_etsy_who_made',
						'fields' => array(
							'id'          => '_ced_etsy_who_made',
							'label'       => __( 'Who made', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Who made the item being listed.', 'product-lister-etsy' ),
							'type'        => 'select',
							'options'     => array(
								'i_did'        => 'I did',
								'collective'   => 'A member of my shop',
								'someone_else' => 'Another company or person',
							),
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => 'i_did',
						),
					),
					array(
						'type'   => '_select',
						'id'     => '_ced_etsy_product_supply',
						'fields' => array(
							'id'          => '_ced_etsy_product_supply',
							'label'       => __( 'Product supply', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Use of the products.', 'product-lister-etsy' ),
							'type'        => 'select',
							'options'     => array(
								'true'  => 'A supply or tool to make things',
								'false' => 'A finished product',
							),
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => 'false',
						),
					),
					array(
						'type'   => '_select',
						'id'     => '_ced_etsy_when_made',
						'fields' => array(
							'id'          => '_ced_etsy_when_made',
							'label'       => __( 'Manufacturing year', 'woocommerce-etsy-integration' ),
							'desc_tip'    => true,
							'description' => __( 'When was the item made.', 'woocommerce-etsy-integration' ),
							'type'        => 'select',
							'options'     => array(
								'made_to_order' => 'Made to Order',
								'2020_2024'     => '2020-2024',
								'2010_2019'     => '2010-2019',
								'2005_2009'     => '2005-2009',
								'before_2005'   => 'Before 2005',
								'2000_2004'     => '2000-2004',
								'1990s'         => '1990s',
								'1980s'         => '1980s',
								'1970s'         => '1970s',
								'1960s'         => '1960s',
								'1950s'         => '1950s',
								'1940s'         => '1940s',
								'1930s'         => '1930s',
								'1920s'         => '1920s',
								'1910s'         => '1910s',
								'1900s'         => '1900s',
								'1800s'         => '1800s',
								'1700s'         => '1700s',
								'before_1700'   => 'Before 1700',
							),
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => '2020_2024',
						),
					),
				),
				'recommended'     => array(
					array(
						'type'   => '_text_input',
						'id'     => '_ced_etsy_tags',
						'fields' => array(
							'id'          => '_ced_etsy_tags',
							'label'       => __( 'Product tags', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Product tags. Enter upto 13 tags comma ( , ) separated. Do not include special characters.', 'product-lister-etsy' ),
							'type'        => 'text',
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),
					array(
						'type'   => '_text_input',
						'id'     => '_ced_etsy_materials',
						'fields' => array(
							'id'          => '_ced_etsy_materials',
							'label'       => __( 'Product materials', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Product materials. Enter upto 13 materials comma ( , ) separated. Do not include special characters.', 'product-lister-etsy' ),
							'type'        => 'text',
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),
					array(
						'type'   => '_text_input',
						'id'     => '_ced_etsy_styles',
						'fields' => array(
							'id'          => '_ced_etsy_styles',
							'label'       => __( 'Product styles', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Product styles. Enter materials comma ( , ) separated. Do not include special characters.', 'product-lister-etsy' ),
							'type'        => 'text',
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),
					array(
						'type'   => '_select',
						'id'     => '_ced_etsy_shop_section',
						'fields' => array(
							'id'          => '_ced_etsy_shop_section',
							'label'       => __( 'Shop section', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Shop section for the products . The products will be listed in the section on etsy if selected.', 'product-lister-etsy' ),
							'type'        => 'select',
							'options'     => $sections,
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),

					array(
						'type'   => '_select',
						'id'     => '_ced_etsy_production_partners',
						'fields' => array(
							'id'          => '_ced_etsy_production_partners',
							'label'       => __( 'Production partner', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'A production partner is anyone who’s not a part of your Etsy shop who helps you physically produce your items.', 'product-lister-etsy' ),
							'type'        => 'select',
							'options'     => $production_partners,
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),
				),
				'optional'        => array(
					array(
						'type'   => '_text_input',
						'id'     => '_ced_etsy_title',
						'fields' => array(
							'id'          => '_ced_etsy_title',
							'label'       => __( 'Title', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Title of the product to be uploaded on etsy.If left blank woocommerce title will be used.', 'product-lister-etsy' ),
							'type'        => 'text',
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),
					array(
						'type'   => '_text_input',
						'id'     => '_ced_etsy_title_pre',
						'fields' => array(
							'id'          => '_ced_etsy_title_pre',
							'label'       => __( 'Title prefix', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Text to be added before the title.', 'product-lister-etsy' ),
							'type'        => 'text',
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),
					array(
						'type'   => '_text_input',
						'id'     => '_ced_etsy_title_post',
						'fields' => array(
							'id'          => '_ced_etsy_title_post',
							'label'       => __( 'Title suffix', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Text to be added after the title.', 'product-lister-etsy' ),
							'type'        => 'text',
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),
					array(
						'type'   => '_text_input',
						'id'     => '_ced_etsy_description',
						'fields' => array(
							'id'          => '_ced_etsy_description',
							'label'       => __( 'Description', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Description of the product to be uploaded on etsy.If left blank woocommerce description will be used.', 'product-lister-etsy' ),
							'type'        => 'text',
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),
					array(
						'type'   => '_text_input',
						'id'     => '_ced_etsy_processing_min',
						'fields' => array(
							'id'          => '_ced_etsy_processing_min',
							'label'       => __( 'Processing min', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'The minimum number of days for processing for this listing.', 'product-lister-etsy' ),
							'type'        => 'text',
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => 1,
						),
					),
					array(
						'type'   => '_text_input',
						'id'     => '_ced_etsy_processing_max',
						'fields' => array(
							'id'          => '_ced_etsy_processing_max',
							'label'       => __( 'Processing max', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'The maximum number of days for processing for this listing.', 'product-lister-etsy' ),
							'type'        => 'text',
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => 3,
						),
					),

					array(
						'type'   => '_select',
						'id'     => '_ced_etsy_is_customizable',
						'fields' => array(
							'id'          => '_ced_etsy_is_customizable',
							'label'       => __( 'Is customizable', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'When yes, a buyer may contact the seller for a customized order. The default value is yes when a shop accepts custom orders. Does not apply to shops that do not accept custom orders.', 'product-lister-etsy' ),
							'type'        => 'select',
							'options'     => array(
								'true'  => 'Yes',
								'false' => 'No',
							),
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => 'false',
						),
					),
					array(
						'type'   => '_select',
						'id'     => '_ced_etsy_is_taxable',
						'fields' => array(
							'id'          => '_ced_etsy_is_taxable',
							'label'       => __( 'Is taxable', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'When yes, applicable shop tax rates apply to this listing at checkout.', 'product-lister-etsy' ),
							'type'        => 'select',
							'options'     => array(
								'true'  => 'Yes',
								'false' => 'No',
							),
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => 'false',
						),
					),
					array(
						'type'   => '_text_input',
						'id'     => '_ced_etsy_price',
						'fields' => array(
							'id'          => '_ced_etsy_price',
							'label'       => __( 'Price', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Price of the product to be uploaded on etsy.If left blank WooCommerce price will be used.', 'product-lister-etsy' ),
							'type'        => 'text',
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),
					array(
						'type'     => '_select',
						'id'       => '_ced_etsy_markup_type',
						'fields'   => array(
							'id'          => '_ced_etsy_markup_type',
							'label'       => __( 'Increase price by', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Increase price by a certain amount in the actual price of the product when uploading on etsy.', 'product-lister-etsy' ),
							'type'        => 'select',
							'options'     => array(
								'Fixed_Increased'      => __( 'Fixed Increase' ),
								'Percentage_Increased' => __( 'Percentage Increase' ),
							),
							'class'       => 'wc_input_price',
							'default'     => '',
						),
						'required' => false,
					),
					array(
						'type'   => '_text_input',
						'id'     => '_ced_etsy_markup_value',
						'fields' => array(
							'id'          => '_ced_etsy_markup_value',
							'label'       => __( 'Markup value', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Enter the markup value to be added in the price. Eg : 10', 'product-lister-etsy' ),
							'type'        => 'text',
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),

					array(
						'type'   => '_text_input',
						'id'     => '_ced_etsy_stock',
						'fields' => array(
							'id'          => '_ced_etsy_stock',
							'label'       => __( 'Quantity', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Quantity [ Stock ] of the product to be uploaded on etsy.If left blank WooCommerce quantity will be used.', 'product-lister-etsy' ),
							'type'        => 'text',
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),
					array(
						'type'   => '_text_input',
						'id'     => '_ced_etsy_default_stock',
						'fields' => array(
							'id'          => '_ced_etsy_default_stock',
							'label'       => __( 'Default quantity', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Default [ Stock ] for the products that are instock but you do not manage stock in WooCommerce or you have unlimited stock for those products [ MAX value can be 99 ].', 'product-lister-etsy' ),
							'type'        => 'text',
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => 1,
						),
					),
					array(
						'type'   => '_select',
						'id'     => '_ced_etsy_should_auto_renew',
						'fields' => array(
							'id'          => '_ced_etsy_should_auto_renew',
							'label'       => __( 'Should auto renew listing', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'When yes, renews a listing for four months upon expiration.', 'product-lister-etsy' ),
							'type'        => 'select',
							'options'     => array(
								'true'  => 'Yes',
								'false' => 'No',
							),
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),
				),
				'shipping'        => array(
					array(
						'type'   => '_text_input',
						'id'     => '_ced_etsy_item_weight',
						'fields' => array(
							'id'          => '_ced_etsy_item_weight',
							'label'       => __( 'Weight', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Weight of the product to be uploaded on etsy.If left blank WooCommerce weight will be used.', 'product-lister-etsy' ),
							'type'        => 'text',
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),
					array(
						'type'   => '_text_input',
						'id'     => '_ced_etsy_item_length',
						'fields' => array(
							'id'          => '_ced_etsy_item_length',
							'label'       => __( 'Length', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Length of the product to be uploaded on etsy.If left blank WooCommerce length will be used.', 'product-lister-etsy' ),
							'type'        => 'text',
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),
					array(
						'type'   => '_text_input',
						'id'     => '_ced_etsy_item_height',
						'fields' => array(
							'id'          => '_ced_etsy_item_height',
							'label'       => __( 'Height', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Height of the product to be uploaded on etsy.If left blank WooCommerce height will be used.', 'product-lister-etsy' ),
							'type'        => 'text',
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),
					array(
						'type'   => '_text_input',
						'id'     => '_ced_etsy_item_width',
						'fields' => array(
							'id'          => '_ced_etsy_item_width',
							'label'       => __( 'Width', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Width of the product to be uploaded on etsy.If left blank WooCommerce width will be used.', 'product-lister-etsy' ),
							'type'        => 'text',
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),
					array(
						'type'   => '_select',
						'id'     => '_ced_etsy_item_weight_unit',
						'fields' => array(
							'id'          => '_ced_etsy_item_weight_unit',
							'label'       => __( 'Weight unit', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Weight unit of the product to be uploaded on etsy.If left blank WooCommerce weigth unit will be used.', 'product-lister-etsy' ),
							'type'        => 'select',
							'options'     => array(
								'oz' => 'Ounce',
								'lb' => 'Pound',
								'g'  => 'Gram',
								'kg' => 'Kilogram',
							),
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => 'draft',
						),
					),
					array(
						'type'   => '_select',
						'id'     => '_ced_etsy_item_dimensions_unit',
						'fields' => array(
							'id'          => '_ced_etsy_item_dimensions_unit',
							'label'       => __( 'Dimension unit', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Dimension unit of the product to be uploaded on etsy.If left blank WooCommerce dimension unit will be used.', 'product-lister-etsy' ),
							'type'        => 'select',
							'options'     => array(
								'in' => 'Inch',
								'ft' => 'Feet',
								'mm' => 'Millimetre',
								'cm' => 'Centimeter',
								'm'  => 'Meter',
							),
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => 'draft',
						),
					),
				),
				'personalization' => array(
					array(
						'type'   => '_select',
						'id'     => '_ced_etsy_is_personalizable',
						'fields' => array(
							'id'          => '_ced_etsy_is_personalizable',
							'label'       => __( 'Is personalizable', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'When yes, this listing is personalizable. The default value is no.', 'product-lister-etsy' ),
							'type'        => 'select',
							'options'     => array(
								'true'  => 'Yes',
								'false' => 'No',
							),
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),
					array(
						'type'   => '_select',
						'id'     => '_ced_etsy_personalization_is_required',
						'fields' => array(
							'id'          => '_ced_etsy_personalization_is_required',
							'label'       => __( 'Is personalization required', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'When yes, this listing requires personalization. The default value is null. Will only change if Is Personalizable is yes. The default value is no.', 'product-lister-etsy' ),
							'type'        => 'select',
							'options'     => array(
								'true'  => 'Yes',
								'false' => 'No',
							),
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),
					array(
						'type'   => '_text_input',
						'id'     => '_ced_etsy_personalization_char_count_max',
						'fields' => array(
							'id'          => '_ced_etsy_personalization_char_count_max',
							'label'       => __( 'Personalization character limit', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'This an number value representing the maximum length for the personalization message entered by the buyer. Will only change if Is Personalizable is yes.', 'product-lister-etsy' ),
							'type'        => 'text',
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),
					array(
						'type'   => '_text_input',
						'id'     => '_ced_etsy_personalization_instructions',
						'fields' => array(
							'id'          => '_ced_etsy_personalization_instructions',
							'label'       => __( 'Instructions for buyers', 'product-lister-etsy' ),
							'desc_tip'    => true,
							'description' => __( 'Enter the personalization instructions you want buyers to see.', 'product-lister-etsy' ),
							'type'        => 'text',
							'is_required' => false,
							'class'       => 'wc_input_price',
							'default'     => '',
						),
					),
				),
			);

			return $required_fields;
		}

		/*
		* Function to render input text html
		*/
		public function renderInputTextHTML( $attribute_id, $attribute_name, $categoryID, $productID, $marketPlace, $attribute_description = null, $indexToUse = '', $additionalInfo = array( 'case' => 'product' ), $conditionally_required = false, $conditionally_required_text = '' ) {
			global $post, $product, $loop;
			$fieldName = $categoryID . '_' . $attribute_id;
			if ( 'product' == $additionalInfo['case'] ) {
				$previousValue = get_post_meta( $productID, $fieldName, true );
			} else {
				$previousValue = $additionalInfo['value'];
			}

			?>
			<!-- <p class="form-field _umb_brand_field "> -->
				<th class="titledesc">
					<label for=""><?php echo esc_attr( $attribute_name ); ?>
					<?php
					if ( $conditionally_required ) {
						?>
						<span style="color: red; margin-left:5px; ">*</span>
						<?php
					}
					if ( ! is_null( $attribute_description ) && ! empty( $attribute_description ) ) {
						ced_etsy_tool_tip( $attribute_description );
					}

					?>
					</label>
				</th>

				<input type="hidden" name="<?php echo esc_attr( $marketPlace . '[]' ); ?>" value="<?php echo esc_attr( $fieldName ); ?>" />
				<td>
					<input class="short" style="" name="<?php echo esc_attr( $fieldName . '[' . $indexToUse . ']' ); ?>" id="" value="<?php echo esc_attr( $previousValue ); ?>" placeholder="" type="text" /> 
				</td>

				<!-- </p> -->
				<?php
		}

		/*
		* Function to render input text html
		*/
		public function rendercheckboxHTML( $attribute_id, $attribute_name, $categoryID, $productID, $marketPlace, $attribute_description = null, $indexToUse = '', $additionalInfo = array( 'case' => 'product' ), $conditionally_required = false, $conditionally_required_text = '' ) {

			global $post, $product, $loop;
			$fieldName = $categoryID . '_' . $attribute_id;
			if ( 'product' == $additionalInfo['case'] ) {
				$previousValue = get_post_meta( $productID, $fieldName, true );
			} else {
				$checked = ( 'yes' == $additionalInfo['value'] ) ? 'checked="checked"' : '';
			}

			?>
			<th class="titledesc">
				<label for=""><?php echo esc_attr( $attribute_name ); ?>
			
			<?php
			if ( ! is_null( $attribute_description ) && ! empty( $attribute_description ) ) {
				ced_etsy_tool_tip( $attribute_description );
			}

			?>
			</label>
			<input type="hidden" name="<?php echo esc_attr( $marketPlace . '[]' ); ?>" value="<?php echo esc_attr( $fieldName ); ?>" />
		</th>
		<td>
			<input class="short" style="" name="<?php echo esc_attr( $fieldName . '[' . $indexToUse . ']' ); ?>" id="" value="<?php echo esc_attr( 'yes' ); ?>" placeholder="" <?php echo esc_attr( $checked ); ?> type="checkbox" /> 
		</td>

		<!-- </p> -->
			<?php
		}

		/*
		* Function to render dropdown html
		*/
		public function renderDropdownHTML( $attribute_id, $attribute_name, $values, $categoryID, $productID, $marketPlace, $attribute_description = null, $indexToUse = '', $additionalInfo = array( 'case' => 'product' ), $is_required = false, $option_id = '' ) {
			$fieldName = $categoryID . '_' . $attribute_id;
			if ( 'product' == $additionalInfo['case'] ) {
				$previousValue = get_post_meta( $productID, $fieldName, true );
			} else {
				$previousValue = $additionalInfo['value'];
			}
			?>
				<th class="titledesc">
					<label for=""><?php echo esc_attr( $attribute_name ); ?>
					<?php
					if ( $is_required ) {
						?>
						<span style="color: red; margin-left:5px; ">*</span>
						<?php
					}
					if ( ! is_null( $attribute_description ) && ! empty( $attribute_description ) ) {
						ced_etsy_tool_tip( $attribute_description );
					}
					?>
					</label>
				</th>
				<input type="hidden" name="<?php echo esc_attr( $marketPlace . '[]' ); ?>" value="<?php echo esc_attr( $fieldName ); ?>" />
				<td colspan="">
					<select id="<?php echo esc_attr( $option_id ); ?>" name="<?php echo esc_attr( $fieldName . '[' . $indexToUse . ']' ); ?>" class="select short" style="">
						<?php
						echo '<option value="">-- Select --</option>';
						foreach ( $values as $key => $value ) {
							if ( $previousValue == $key ) {
								echo '<option value="' . esc_attr( $key ) . '" selected>' . esc_attr( $value ) . '</option>';
							} else {
								echo '<option value="' . esc_attr( $key ) . '">' . esc_attr( $value ) . '</option>';
							}
						}
						?>
					</select>
				</td>
				<?php
		}

		public function renderInputTextHTMLhidden( $attribute_id, $attribute_name, $categoryID, $productID, $marketPlace, $attribute_description = null, $indexToUse = '', $additionalInfo = array( 'case' => 'product' ), $conditionally_required = false, $conditionally_required_text = '' ) {
			global $post, $product, $loop;
			$fieldName = $categoryID . '_' . $attribute_id;
			if ( 'product' == $additionalInfo['case'] ) {
				$previousValue = get_post_meta( $productID, $fieldName, true );
			} else {
				$previousValue = $additionalInfo['value'];
			}

			?>
				<input type="hidden"  name="<?php echo esc_attr( $marketPlace . '[]' ); ?>" value="<?php echo esc_attr( $fieldName ); ?>" />
			<!-- 	<td>
				</label>
			</td>
			<td>
				<label></label> -->
				<input class="short" id="<?php echo esc_attr( $fieldName ); ?>" style="" name="<?php echo esc_attr( $fieldName . '[' . $indexToUse . ']' ); ?>" id="" value="<?php echo esc_attr( $previousValue ); ?>" placeholder="" type="hidden" /> 
			<!-- </td> -->
			<?php
		}

		public function get_taxonomy_node_properties( $getTaxonomyNodeProperties = '' ) {

			$taxonomyList = array();
			if ( isset( $getTaxonomyNodeProperties ) && is_array( $getTaxonomyNodeProperties ) && ! empty( $getTaxonomyNodeProperties ) ) {
				foreach ( $getTaxonomyNodeProperties as $getTaxonomyNodeProperties_key => $getTaxonomyNodeProperties_value ) {
					$type             = '';
					$taxonomy_options = array();
					if ( isset( $getTaxonomyNodeProperties_value['possible_values'] ) && is_array( $getTaxonomyNodeProperties_value['possible_values'] ) && ! empty( $getTaxonomyNodeProperties_value['possible_values'] ) ) {
						$type = '_select';
						foreach ( $getTaxonomyNodeProperties_value['possible_values'] as $possible_values_key => $possible_value ) {
							$taxonomy_options[ $possible_value['value_id'] ] = $possible_value['name'];
						}
					} else {
						$type = '_text_input';
					}
					if ( isset( $type ) && '_select' != $type ) {
						$taxonomyList[] = array(
							'type'   => $type,
							'id'     => '_ced_etsy_taxonomy_id_' . $getTaxonomyNodeProperties_value['property_id'],
							'fields' => array(
								'id'          => '_ced_etsy_property_id_' . $getTaxonomyNodeProperties_value['property_id'],
								'label'       => $getTaxonomyNodeProperties_value['name'],
								'desc_tip'    => true,
								'description' => /*$variation_category_attribute_property_value['description']*/ $getTaxonomyNodeProperties_value['name'],
								'type'        => 'text',
								'class'       => 'wc_input_price',
							),
						);
					} else {
						$taxonomyList[] = array(
							'type'   => $type,
							'id'     => '_ced_etsy_taxonomy_id_' . $getTaxonomyNodeProperties_value['property_id'],
							'fields' => array(
								'id'          => '_ced_etsy_property_id_' . $getTaxonomyNodeProperties_value['property_id'],
								'label'       => $getTaxonomyNodeProperties_value['name'],
								'desc_tip'    => true,
								'description' => /* $variation_category_attribute_property_value['description']*/ $getTaxonomyNodeProperties_value['name'],
								'type'        => 'text',
								'options'     => $taxonomy_options,
								'class'       => 'wc_input_price',
							),
						);
					}
				}
			}
			return $taxonomyList;
		}
	}
}
