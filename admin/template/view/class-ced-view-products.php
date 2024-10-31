<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
class EtsyListProducts extends WP_List_Table {

	public $show_reset;
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Product', 'product-lister-etsy' ), // singular name of the listed records
				'plural'   => __( 'Products', 'product-lister-etsy' ), // plural name of the listed records
				'ajax'     => true, // does this table support ajax?
			)
		);
	}

	public function prepare_items() {

		global $wpdb;
		$per_page  = 25;
		$post_type = 'product';
		$columns   = $this->get_columns();
		$hidden    = array();
		$sortable  = $this->get_sortable_columns();

		// Column headers
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}
		$this->items = self::get_product_details( $per_page, $current_page, $post_type );
		$count       = self::get_count( $per_page, $current_page );

		// Set the pagination
		$this->set_pagination_args(
			array(
				'total_items' => $count,
				'per_page'    => $per_page,
				'total_pages' => ceil( $count / $per_page ),
			)
		);

		if ( ! $this->current_action() ) {
			$this->items = self::get_product_details( $per_page, $current_page, $post_type );
			$this->renderHTML();
		} else {
			$this->process_bulk_action();
		}
	}


	public function get_product_details( $per_page = '', $page_number = 1, $post_type = 'product' ) {
		$pro_fltr_inst = new \Cedcommerce\Template\Ced_Template_Product_Filter();
		$shop_name     = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		$args          = $this->GetFilteredData( $per_page, $page_number );
		if ( ! empty( $args ) && isset( $args['tax_query'] ) || isset( $args['meta_query'] ) || isset( $args['s'] ) ) {
			$args = $args;
		} else {
			$args = array(
				'post_type'      => $post_type,
				'posts_per_page' => $per_page,
				'paged'          => $page_number,
			);
		}
		$args['product_type'] = array( 'simple', 'variable' );
		$args['post_status']  = 'publish';
		$args['order']        = 'DESC';
		$args['orderby']      = 'ID';
		$loop                 = new WP_Query( $args );
		$product_data         = $loop->posts;
		$wooProducts          = array();
		foreach ( $product_data as $key => $value ) {
			$prodID        = $value->ID;
			$productDATA   = wc_get_product( $prodID );
			$productDATA   = $productDATA->get_data();
			$wooProducts[] = $productDATA;
		}

		if ( isset( $_POST['filter_button'] ) ) {
			if ( ! isset( $_POST['manage_product_filters'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['manage_product_filters'] ) ), 'manage_products' ) ) {
				return;
			}
			$wooProducts = $pro_fltr_inst->ced_etsy_filters_on_products( $wooProducts, $shop_name );
		} elseif ( isset( $_POST['s'] ) ) {
			if ( ! isset( $_POST['manage_product_filters'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['manage_product_filters'] ) ), 'manage_products' ) ) {
				return;
			}
			$pro_fltr_inst->productSearch_box( $wooProducts, $shop_name );
		}
		return $wooProducts;
	}


	public function GetFilteredData( $per_page, $page_number ) {
		$this->show_reset = false;
		if ( isset( $_GET['status_sorting'] ) || isset( $_GET['pro_cat_sorting'] ) || isset( $_GET['pro_type_sorting'] ) || isset( $_GET['s'] ) || isset( $_GET['stock_status'] ) ) {
			$this->show_reset = true;
			if ( ! empty( $_REQUEST['pro_cat_sorting'] ) ) {
				$pro_cat_sorting = isset( $_GET['pro_cat_sorting'] ) ? sanitize_text_field( wp_unslash( $_GET['pro_cat_sorting'] ) ) : '';
				if ( ! empty( $pro_cat_sorting ) ) {
					$selected_cat          = array( $pro_cat_sorting );
					$tax_query             = array();
					$tax_queries           = array();
					$tax_query['taxonomy'] = 'product_cat';
					$tax_query['field']    = 'id';
					$tax_query['terms']    = $selected_cat;
					$args['tax_query'][]   = $tax_query;
				}
			}

			if ( ! empty( $_REQUEST['pro_type_sorting'] ) ) {
				$pro_type_sorting = isset( $_GET['pro_type_sorting'] ) ? sanitize_text_field( wp_unslash( $_GET['pro_type_sorting'] ) ) : '';
				if ( ! empty( $pro_type_sorting ) ) {
					$selected_type         = array( $pro_type_sorting );
					$tax_query             = array();
					$tax_queries           = array();
					$tax_query['taxonomy'] = 'product_type';
					$tax_query['field']    = 'id';
					$tax_query['terms']    = $selected_type;
					$args['tax_query'][]   = $tax_query;
				}
			}

			if ( ! empty( $_REQUEST['status_sorting'] ) ) {
				$status_sorting = isset( $_GET['status_sorting'] ) ? sanitize_text_field( wp_unslash( $_GET['status_sorting'] ) ) : '';
				$shop_name      = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
				if ( ! empty( $status_sorting ) ) {
					$meta_query = array();
					if ( 'Uploaded' == $status_sorting ) {
						$args['orderby'] = 'meta_value_num';
						$args['order']   = 'ASC';

						$meta_query[] = array(
							'key'     => '_ced_etsy_listing_id_' . $shop_name,
							'compare' => 'EXISTS',
						);
					} elseif ( 'NotUploaded' == $status_sorting ) {
						$meta_query[] = array(
							'key'     => '_ced_etsy_listing_id_' . $shop_name,
							'compare' => 'NOT EXISTS',
						);
					}
				}
			}

			if ( ! empty( $_REQUEST['s'] ) ) {
				$s = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
				if ( ! empty( $s ) ) {
					$args['s'] = $s;
				}
			}

			if ( ! empty( $_REQUEST['stock_status'] ) ) {
				$stock_status = isset( $_GET['stock_status'] ) ? sanitize_text_field( wp_unslash( $_GET['stock_status'] ) ) : '';

				$meta_query[] = array(
					'key'     => '_stock_status',
					'compare' => '=',
					'value'   => $stock_status,
				);

			}

			if ( ! empty( $_GET['stock_status'] ) && ! empty( $_GET['status_sorting'] ) ) {
				$meta_query['relation'] = 'AND';
			}
			if ( ! empty( $meta_query ) ) {
				$args['meta_query'] = $meta_query;
			}

			$args['post_type']      = 'product';
			$args['posts_per_page'] = $per_page;
			$args['paged']          = $page_number;
			return $args;
		}
	}

	public function no_items() {
		esc_html_e( 'No Products To Show.', 'product-lister-etsy' );
	}

	/**
	 *
	 * Function to count number of responses in result
	 */
	public function get_count( $per_page, $page_number ) {
		$args = $this->GetFilteredData( $per_page, $page_number );
		if ( ! empty( $args ) && isset( $args['tax_query'] ) || isset( $args['meta_query'] ) ) {
			$args = $args;
		} else {
			$args = array( 'post_type' => 'product' );
		}
		$args['product_type'] = array( 'simple', 'variable' );
		$args['post_status']  = 'publish';
		$args['order']        = 'DESC';
		$args['orderby']      = 'ID';
		$loop                 = new WP_Query( $args );
		$product_data         = $loop->posts;
		$product_data         = $loop->found_posts;
		return $product_data;
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */

	public function get_sortable_columns() {
		$sortable_columns = array();
		return $sortable_columns;
	}

	public function column_cb( $item ) {

		return sprintf(
			'<input type="checkbox" name="etsy_product_ids[]" class="etsy_products_id" value="%s" />',
			$item['id']
		);
	}


	public function column_price( $item ) {
		$price = get_post_meta( $item['id'], '_price', true );
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';
		$currencySymbol = get_woocommerce_currency_symbol();
		return $currencySymbol . '&nbsp' . $price . '</div></div>';
	}

	public function column_sku( $item ) {
		$sku = get_post_meta( $item['id'], '_sku', true );
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';
			echo '<b>' . esc_html__( $sku, 'product-lister-etsy' ) . '</b>';
		echo '</div></div>';
	}

	public function column_name( $item ) {
		$product           = wc_get_product( $item['id'] );
		$product_type      = $product->get_type();
		$shop_name         = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		$editUrl           = get_edit_post_link( $item['id'], '' );
		$actions['id']     = '<strong>ID :' . $item['id'] . '</strong>';
		$actions['status'] = '<strong>' . ucwords( $item['status'] ) . '</strong>';
		$actions['type']   = '<strong>' . ucwords( $product_type ) . '</strong>';
		echo '<b><a class="ced_etsy_prod_name" href="' . esc_attr( $editUrl ) . '" >' . esc_attr( $item['name'] ) . '</a></b>';
		return $this->row_actions( $actions, true );
	}

	public function column_profile( $item ) {
		$shop_name      = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		$productBuilder = ( new \Cedcommerce\Product\Ced_Product_Payload( $item['id'], $shop_name ) );
		$productBuilder->ced_etsy_check_profile( $item['id'], $shop_name );
		if ( $productBuilder->profile_name ) {
			$profile_name     = esc_attr( $productBuilder->profile_name );
			$edit_profile_url = admin_url( 'admin.php?page=sales_channel&channel=etsy&profileID=' . $productBuilder->profile_id . '&section=templates&details=edit&shop_name=' . $shop_name );
			echo '<a href="' . esc_url( $edit_profile_url ) . '">' . esc_html( $profile_name ) . '</a>';
		} else {
			$cat_mapping_section = admin_url( 'admin.php?page=sales_channel&channel=etsy&section=category&shop_name=' . $shop_name );
			echo '<span class="">----</span>';
		}
	}


	public function column_category( $item ) {
		foreach ( $item['category_ids'] as $key => $value ) {
			$wooCategory = get_term_by( 'id', $value, 'product_cat', 'ARRAY_A' );
			echo esc_attr( $wooCategory['name'] ) . '</br>';
		}
	}
	public function column_stock( $item ) {
		if ( 'instock' == $item['stock_status'] ) {
			if ( 0 == $item['stock_quantity'] || '0' == $item['stock_quantity'] ) {
				echo '<div class="ced-connected-button-wrap"><span class="ced-circle-instock"></span><span class="stock_alert_instock">' . esc_html( 'In Stock', 'product-lister-etsy' ) . '</span></div>';
			} else {
				echo '<div class="ced-connected-button-wrap"><span class="ced-circle-instock"></span><span class="stock_alert_instock">In Stock(' . esc_html( $item['stock_quantity'] ) . ')</span></div>';
			}
		} else {
			echo '<div class="ced-connected-button-wrap"><span class="ced-circle-outofstock" style="background:#e2401c;"></span><span class="stock_alert_outofstock">' . esc_html( 'Out of Stock', 'product-lister-etsy' ) . '</span></div>';
		}
	}

	public function column_image( $item ) {
		$image = wp_get_attachment_url( $item['image_id'] );
		if ( empty( $image ) ) {
			$image = wc_placeholder_img_src();
		}
		return '<img height="50" width="50" src="' . $image . '">';
	}
	public function column_status( $item ) {
		$shop_name    = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		$listing_info = get_post_meta( $item['id'], '_ced_etsy_listing_data_' . $shop_name, true );
		$meta_info    = array();
		$actions      = array();

		$listingId = get_post_meta( $item['id'], '_ced_etsy_listing_id_' . $shop_name, true );
		$view_url  = get_post_meta( $item['id'], '_ced_etsy_url_' . $shop_name, true );
		if ( ! empty( $listingId ) ) {
			echo '<div class="ced_etsy_product_status_wrap"><p><span class="success_upload_on_etsy" id="' . esc_attr( $item['id'] ) . '"><span class="ced-circle-instock"></span><span class="ced_etsy_product_status">Present on Etsy</span></p></div>';
		} else {
			echo '<div class="ced_etsy_product_status_wrap"><p><span class="ced-circle-notuploaded"></span><span class="not_completed ced_etsy_product_status" id="' . esc_attr( $item['id'] ) . '">Not on Etsy</span></p></div>';
		}
	}


	public function get_columns() {
		$columns = array(
			'cb'       => '<input type="checkbox" />',
			'image'    => __( '<b>Image</b>', 'product-lister-etsy' ),
			'name'     => __( '<b>Name</b>', 'product-lister-etsy' ),
			'price'    => __( '<b>Price</b>', 'product-lister-etsy' ),
			'profile'  => __( '<b>Template</b>', 'product-lister-etsy' ),
			'sku'      => __( '<b>SKU</b>', 'product-lister-etsy' ),
			'stock'    => __( '<b>Stock</b>', 'product-lister-etsy' ),
			'category' => __( '<b>Category</b>', 'product-lister-etsy' ),
			'status'   => __( '<b>Etsy Status</b>', 'product-lister-etsy' ),
		);
		return $columns;
	}

	protected function bulk_actions( $which = '' ) {
		if ( 'top' == $which ) :
			if ( is_null( $this->_actions ) ) {
				$this->_actions = $this->get_bulk_actions();
				/**
				 * Filters the list table Bulk Actions drop-down.
				 *
				 * The dynamic portion of the hook name, `$this->screen->id`, refers
				 * to the ID of the current screen, usually a string.
				 *
				 * This filter can currently only be used to remove bulk actions.
				 *
				 * @since 3.5.0
				 *
				 * @param array $actions An array of the available bulk actions.
				 */
				$this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions );
				$two            = '';
			} else {
				$two = '2';
			}

			if ( empty( $this->_actions ) ) {
				return;
			}

			echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . esc_html( __( 'Select bulk action' ) ) . '</label>';
			echo '<select name="action' . esc_attr( $two ) . '" class="bulk-action-selector " id="ced-etsy-bulk-operation">';
			echo '<option value="-1">' . esc_html( __( 'Bulk Actions' ) ) . "</option>\n";

			foreach ( $this->_actions as $name => $title ) {
				$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

				echo "\t" . '<option value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . "</option>\n";
			}

			echo "</select>\n";

			submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => 'ced_etsy_bulk_operation' ) );
			echo "\n";
		endif;
	}
		/**
		 * Returns an associative array containing the bulk action
		 *
		 * @return array
		 */
	public function get_bulk_actions() {
		return array(
			'upload_product'   => esc_html__( 'Upload', 'product-lister-etsy' ),
			'update_product'   => esc_html__( 'Update', 'product-lister-etsy' ),
			'update_inventory' => esc_html__( 'Update inventory & price', 'product-lister-etsy' ),
			'update_image'     => esc_html__( 'Update images', 'product-lister-etsy' ),
			'remove_product'   => esc_html__( 'Remove', 'product-lister-etsy' ),
			'unlink_product'   => esc_html__( 'Unlink', 'product-lister-etsy' ),
		);
	}


	public function renderHTML() {
		?>
		<!-- <div class="ced_etsy_template_modal"> -->
			<div id="myModal" class="modal ced_etsy_template_modal">
			  <div class="modal-content">
				<span class="close ced_etsy_modal_close">&times;</span>
				<p><img src= "<?php echo esc_url( CED_ETSY_URL . 'admin/assets/images/output1.gif' ); ?> "/></p>
			  </div>

			</div>
			
		<!-- </div> -->
		<div class="ced_etsy_heading">
		<?php echo esc_html_e( get_etsy_instuctions_html() ); ?>
			<div class="ced_etsy_child_element default_modal">
			<?php
				$activeShop = isset( $_GET['shop_name'] ) ? sanitize_text_field( $_GET['shop_name'] ) : '';
			?>
			</div>			
		</div>
		
		<div id="" class="">
			<div class="ced_progress">
				<h4 class="ced-red"><?php esc_html_e( '*Do not press any key or refresh the page until the operation is complete', 'product-lister-etsy' ); ?>
					<a target="_blank" href="<?php echo esc_url( admin_url( 'admin.php?page=sales_channel&channel=etsy&section=timeline&shop_name=' . esc_attr( $activeShop ) ) ); ?>">
						<?php esc_html_e( 'Click here to see activity', 'product-lister-etsy' ); ?>
					</a>
				</h4>
				<progress id="ced_progress" value="0" max="100"></progress>
			</div>
			<div class="wrap">
				<h1><?php esc_html_e( 'Products', 'product-lister-etsy' ); ?></h1>
			</div>
			
			<div id="">
				<div class="">
				<?php
				$shop_name      = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
				$status_actions = array(
					'Uploaded'    => __( 'On Etsy', 'product-lister-etsy' ),
					'NotUploaded' => __( 'Not on Etsy', 'product-lister-etsy' ),
				);
				$stock_status   = array(
					'instock'    => __( 'In stock', 'product-lister-etsy' ),
					'outofstock' => __( 'Out of stock', 'product-lister-etsy' ),
				);
				$product_types  = get_terms( 'product_type' );
				$temp_array     = array();
				foreach ( $product_types as $key => $value ) {
					if ( 'simple' == $value->name || 'variable' == $value->name ) {
						$temp_array_type[ $value->term_id ] = ucfirst( $value->name );
					}
				}
				$product_types      = $temp_array_type;
				$product_categories = get_terms( 'product_cat' );
				$temp_array         = array();
				foreach ( $product_categories as $key => $value ) {
					$temp_array[ $value->term_id ] = $value->name;
				}
				$product_categories             = $temp_array;
				$previous_selected_status       = isset( $_GET['status_sorting'] ) ? sanitize_text_field( wp_unslash( $_GET['status_sorting'] ) ) : '';
				$previous_selected_cat          = isset( $_GET['pro_cat_sorting'] ) ? sanitize_text_field( wp_unslash( $_GET['pro_cat_sorting'] ) ) : '';
				$previous_selected_type         = isset( $_GET['pro_type_sorting'] ) ? sanitize_text_field( wp_unslash( $_GET['pro_type_sorting'] ) ) : '';
				$previous_selected_stock_status = isset( $_GET['stock_status'] ) ? sanitize_text_field( wp_unslash( $_GET['stock_status'] ) ) : '';
				echo '<div class="ced_etsy_wrap">';

				echo '<form method="post" action="">';
				wp_nonce_field( 'manage_products', 'manage_product_filters' );

				echo '<div class="ced_etsy_top_wrapper">';
				// echo "<span class='ced_etsy_filter_label'>Filter product by</span>";
				echo '<select name="status_sorting" class="select_boxes_product_page">';
				echo '<option value="">' . esc_html( __( ' — Filter by Etsy Product Status — ', 'product-lister-etsy' ) ) . '</option>';
				foreach ( $status_actions as $name => $title ) {
					$selectedStatus = ( $previous_selected_status == $name ) ? 'selected="selected"' : '';
					$class          = 'edit' === $name ? ' class="hide-if-no-js"' : '';
					echo '<option ' . esc_attr( $selectedStatus ) . ' value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . '</option>';
				}
				echo '</select>';
				wp_dropdown_categories(
					array(
						'name'            => 'pro_cat_sorting',
						'taxonomy'        => 'product_cat',
						'class'           => 'select_boxes_product_page',
						'orderby'         => 'NAME',
						'order'           => 'ASC',
						'hierarchical'    => 1,
						'hide_empty'      => 1,
						'show_count'      => true,
						'selected'        => $previous_selected_cat,
						'show_option_all' => __(
							' — Filter by Product Category — ',
							'product-lister-etsy'
						),
					)
				);
					echo '<select name="pro_type_sorting" class="select_boxes_product_page">';
					echo '<option value="">' . esc_html( __( ' — Filter by Product Type — ', 'product-lister-etsy' ) ) . '</option>';
				foreach ( $product_types as $name => $title ) {
					$selectedType = ( $previous_selected_type == $name ) ? 'selected="selected"' : '';
					$class        = 'edit' === $name ? ' class="hide-if-no-js"' : '';
					echo '<option ' . esc_attr( $selectedType ) . ' value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . '</option>';
				}
					echo '</select>';
					echo '<select name="stock_status" class="select_boxes_product_page">';
					echo '<option value="">' . esc_html( __( ' — Filter by Stock Status — ', 'product-lister-etsy' ) ) . '</option>';
				foreach ( $stock_status as $name => $title ) {
					$selectedType = ( $previous_selected_stock_status == $name ) ? 'selected="selected"' : '';
					$class        = 'edit' === $name ? ' class="hide-if-no-js"' : '';
					echo '<option ' . esc_attr( $selectedType ) . ' value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . '</option>';
				}
					echo '</select>';
					$this->search_box( 'Search products', 'search_id', 'search_product' );
					submit_button( __( 'Filter', 'ced-etsy' ), 'action', 'filter_button', false, array() );
				if ( $this->show_reset ) {
					echo '<span class="ced_reset"><a href="' . esc_url( admin_url( 'admin.php?page=sales_channel&channel=etsy&section=products&shop_name=' . $shop_name ) ) . '" class="button">X</a></span>';
				}
					echo '</div>';
					echo '</form>';
					echo '</div>';
				?>
					<form method="post">
					<?php
					$this->display();
					?>
					</form>

				</div>
			</div>
			<div class="clear"></div>
		</div>
		<?php
	}
}

	$ced_etsy_products_obj = new etsyListProducts();
	$ced_etsy_products_obj->prepare_items();
