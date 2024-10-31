<?php
class Ced_Etsy_Get_Categories {

	public function __construct() {
	}
	public function ced_etsy_get_categories() {
		$categories = @file_get_contents( CED_ETSY_DIRPATH . 'admin/lib/json/categoryLevel-1.json' );
		if ( ! empty( $categories ) ) {
			$categories = json_decode( $categories, 1 );
		} else {
			$categories = array();
		}

		if ( isset( $categories ) ) {
			print_r( $this->ced_etsy_render_select( $categories ) );
		} else {
			echo esc_attr__( 'Categories not found', 'product-lister-etsy' );
		}
	}

	public function ced_etsy_render_select( $categories ) {

			$html  = '';
			$html .= '<div class="components-card is-size-medium woocommerce-table pinterest-for-woocommerce-landing-page__faq-section css-1xs3c37-CardUI e1q7k77g0 ced_profile_table">
				<div class="components-panel ced-padding">
					<header>
						<h2>' . esc_html__( 'Category Mapping', 'product-lister-etsy' ) . '</h2>
						<p><i>' . esc_html__( 'Note: Category Mapping involves assigning a suitable marketplace category to your products, ensuring they appear in the right section on that platform. For instance, if you\'re selling women\'s jeans, you\'d link them to the \'Women\'s Jeans\' category within Women\'s Clothing, Clothing Shoe Accessories on Etsy.', 'product-lister-etsy' ) . '</i></p>
						<p>' . esc_html__( 'Choose an Etsy category for the template, then connect it to the WooCommerce category you\'ve made.', 'product-lister-etsy' ) . '</p>
					</header>
					<table class="form-table css-off1bd">
						<tbody>
							<tr>
								<th scope="row" class="titledesc">
									<label for="woocommerce_currency">' . esc_html__( 'WooCommerce Category', 'product-lister-etsy' ) . '</label>
								</th>
								<td class="forminp forminp-select ced-input-setting">

									<select class="select2 custom_category_attributes_select2" name="woo_categories[]" multiple="" required="" tabindex="-1" aria-hidden="true">';
										$woo_store_categories = get_terms( 'product_cat' );
		foreach ( $woo_store_categories as $key => $value ) {
			$exists = get_term_meta( $value->term_id, 'ced_etsy_profile_created_' . get_etsy_shop_name(), 'yes' );
			if ( $exists ) {
				continue;
			}
			$cat_name = $value->name;
			$cat_name = ced_etsy_categories_tree( $value, $cat_name );
			$html    .= '<option value="' . esc_attr( $value->term_id ) . '">' . esc_html( $cat_name ) . '</option>';
		}
									$html         .= '</select>
								</td>
							</tr>
							<tr>
								<th scope="row" class="titledesc">
									<label for="woocommerce_currency">' . esc_html__( 'Etsy Category', 'product-lister-etsy' ) . '</label>
								</th>
								<td class="forminp forminp-select">
									<div class="ced-category-mapping-wrapper">
										<div class="ced-category-mapping">
											<strong><span id="ced_etsy_cat_header" data-level="1">' . esc_html__( 'Browse and Select a Category', 'product-lister-etsy' ) . '</span></strong>
											';
											$html .= '<ol id="ced_etsy_categories_1" class="ced_etsy_categories" data-level="1" data-node-value="' . esc_html__( 'Browse and Select a Category', 'product-lister-etsy' ) . '">';
		foreach ( $categories as $key => $value ) {
			$parent_id = isset( $value['parent_id'] ) ? $value['parent_id'] : '';
			$cat_id    = isset( $value['id'] ) ? $value['id'] : '';
			$html     .= '<li id="' . esc_attr( $cat_id ) . '" data-level="1" class="ced_etsy_category_arrow" data-name="' . esc_attr( $value['name'] ) . '" data-parentId="' . esc_attr( $parent_id ) . '" data-id="' . esc_attr( $cat_id ) . '">' . esc_html( $value['name'] ) . '<span  class="dashicons dashicons-arrow-right-alt2"></span></li>';
		}
											$html .= '</ol>';
											$html .= '
										</div>
									</div>
									<div class="ced-category-mapping-wrapper-breadcrumb"><p id="ced_etsy_breadcrumb" style="display: none;">
									</p></div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>';
			return $html;
	}
}
