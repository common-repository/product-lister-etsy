<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
class Ced_Etsy_Profile_Table extends WP_List_Table {

	/** Class constructor */
	public function __construct() {

		parent::__construct(
			array(
				'singular' => __( 'Etsy Template', 'product-lister-etsy' ), // singular name of the listed records
				'plural'   => __( 'Etsy Templates', 'product-lister-etsy' ), // plural name of the listed records
				'ajax'     => false, // does this table support ajax?
			)
		);
	}

	public function prepare_items() {

		global $wpdb;

		$per_page = 10;
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		// Column headers
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}

		$this->items = self::get_profiles( $per_page, $current_page );

		$count = self::get_count();

		// Set the pagination
		$this->set_pagination_args(
			array(
				'total_items' => $count,
				'per_page'    => $per_page,
				'total_pages' => ceil( $count / $per_page ),
			)
		);

		if ( ! $this->current_action() ) {
			$this->items = self::get_profiles( $per_page, $current_page );
			$this->renderHTML();
		} else {
			$this->process_bulk_action();
		}
	}

	public function get_profiles( $per_page = 10, $page_number = 1 ) {

		global $wpdb;
		$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		$offset    = ( $page_number - 1 ) * $per_page;
		$tableName = $wpdb->prefix . 'ced_etsy_profiles';
		$result    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM  {$wpdb->prefix}ced_etsy_profiles WHERE `shop_name`= %s ORDER BY `id` DESC LIMIT %d OFFSET %d", $shop_name, $per_page, $offset ), 'ARRAY_A' );
		return $result;
	}

	/**
	 * Function to count number of responses in result
	 */
	public function get_count() {
		$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		global $wpdb;
		$tableName = $wpdb->prefix . 'ced_etsy_profiles';
		$result    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM  {$wpdb->prefix}ced_etsy_profiles WHERE `shop_name`= %s ", $shop_name ), 'ARRAY_A' );
		return count( $result );
	}

	/** Text displayed when no customer data is available */
	public function no_items() {
		esc_html_e( 'No Templates Created.', 'product-lister-etsy' );
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="etsy_profile_ids[]" value="%s" />',
			$item['id']
		);
	}


	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	public function column_profile_name( $item ) {
		$shop_name       = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		$url             = admin_url( 'admin.php?page=sales_channel&channel=etsy&profileID=' . $item['id'] . '&section=templates&details=edit&shop_name=' . $shop_name );
		$actions['edit'] = '<a href=' . $url . '>Edit</a>';
		echo '<strong>' . esc_html( $item['profile_name'] ) . '</strong>';
		return $this->row_actions( $actions, true );
	}


	public function column_profile_status( $item ) {

		if ( 'inactive' == $item['profile_status'] ) {
			return 'InActive';
		} else {
			return 'Active';
		}
	}


	public function column_woo_categories( $item ) {

		$woo_categories = json_decode( $item['woo_categories'], true );

		if ( ! empty( $woo_categories ) ) {
			foreach ( $woo_categories as $key => $value ) {
				$term = get_term_by( 'id', $value, 'product_cat' );
				if ( $term ) {
					echo '<p>' . esc_attr( $term->name ) . '</p>';
				}
			}
		}
	}

	public function column_edit_profiles( $item ) {
		$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		$edit_url  = admin_url( 'admin.php?page=sales_channel&channel=etsy&profileID=' . $item['id'] . '&section=profile-edit&details=edit&shop_name=' . $shop_name );
		echo "<a class='button-primary' href='" . esc_url( $edit_url ) . "'>Edit</a>";
	}

	public function column_auto_upload( $ced_etsy_profile_details ) {
		$woo_categories = json_decode( $ced_etsy_profile_details['woo_categories'], true );
		$shop_name      = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		if ( ! empty( $woo_categories ) ) {

			$ced_etsy_auto_upload_categories = get_option( 'ced_etsy_auto_upload_categories_' . $shop_name, array() );
			$woo_category_ids                = array();
			foreach ( $woo_categories as $key => $value ) {
				$woo_category_ids[] = $value;
			}
			$checked = '';
				echo '<label class="switch"><input type="checkbox" value="' . json_encode( $woo_category_ids ) . '" id="ced_etsy_auto_upload_categories" ' . esc_attr( $checked ) . ' data-shop-name="' . esc_attr( $shop_name ) . '">
			</label>';
		}
	}
	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'             => '<input type="checkbox" />',
			'profile_name'   => __( 'Template Name', 'product-lister-etsy' ),
			'woo_categories' => __( 'Mapped WooCommerce Categories', 'product-lister-etsy' ),
		);
		return $columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete' => __( 'Delete', 'product-lister-etsy' ),
		);
		return $actions;
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

	/**
	 * Function to get changes in html
	 */
	public function renderHTML() {
		$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		$url       = admin_url( 'admin.php?page=sales_channel&channel=etsy&section=templates&details=edit&shop_name=' . $shop_name );
		?>
		<div class="ced_etsy_wrap ced_etsy_wrap_extn">
					<div class="wrap">
						<h1><?php esc_html_e( 'Templates', 'product-lister-etsy' ); ?></h1>
<a href="
		<?php
		echo esc_attr(
			ced_get_navigation_url(
				'etsy',
				array(
					'section'   => 'templates',
					'details'   => 'edit',
					'shop_name' => get_etsy_shop_name(),
				)
			)
		);
		?>
			" class="button-primary alignright">Create new template</a>
					</div>
			<div>
				
				<div id="post-body" class="metabox-holder columns-2">
					<div id="">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<?php
								wp_nonce_field( 'etsy_profiles', 'etsy_profiles_actions' );
								$this->display();
								?>
							</form>
						</div>
					</div>
					<div class="clear"></div>
				</div>
				<br class="clear">
			</div>
		</div>
		<?php
	}

	public function current_action() {

		if ( isset( $_GET['details'] ) ) {
			$action = isset( $_GET['details'] ) ? sanitize_text_field( wp_unslash( $_GET['details'] ) ) : '';
			return $action;
		} elseif ( isset( $_POST['action'] ) ) {
			if ( ! isset( $_POST['etsy_profiles_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['etsy_profiles_actions'] ) ), 'etsy_profiles' ) ) {
				return;
			}
			$action = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : '';
			return $action;
		}
	}

	public function process_bulk_action() {

		$shop_id = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		if ( 'bulk-delete' === $this->current_action() || ( isset( $_GET['action'] ) && 'bulk-delete' === $_GET['action'] ) ) {

			if ( ! isset( $_POST['etsy_profiles_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['etsy_profiles_actions'] ) ), 'etsy_profiles' ) ) {
				return;
			}
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$profileIds      = isset( $sanitized_array['etsy_profile_ids'] ) ? $sanitized_array['etsy_profile_ids'] : array();
			if ( is_array( $profileIds ) && ! empty( $profileIds ) ) {

				global $wpdb;

				$tableName = $wpdb->prefix . 'ced_etsy_profiles';

				foreach ( $profileIds as $index => $pid ) {

					$product_ids_assigned = get_option( 'ced_etsy_product_ids_in_profile_' . $pid, array() );
					foreach ( $product_ids_assigned as $index => $ppid ) {
						delete_post_meta( $ppid, 'ced_etsy_profile_assigned' . $shop_id );
					}

					$term_id = $wpdb->get_results( $wpdb->prepare( "SELECT `woo_categories` FROM {$wpdb->prefix}ced_etsy_profiles WHERE `id` = %d", $pid ), 'ARRAY_A' );
					$term_id = json_decode( $term_id[0]['woo_categories'], true );
					foreach ( $term_id as $key => $value ) {
						delete_term_meta( $value, 'ced_etsy_profile_created_' . $shop_id );
						delete_term_meta( $value, 'ced_etsy_profile_id_' . $shop_id );
						delete_term_meta( $value, 'ced_etsy_mapped_category_' . $shop_id );
					}
				}

				foreach ( $profileIds as $id ) {
					$wpdb->delete( $tableName, array( 'id' => $id ) );
				}
				$redirectURL = get_admin_url() . 'admin.php?page=sales_channel&channel=etsy&section=templates&shop_name=' . $shop_id;
				wp_redirect( $redirectURL );
			} else {
				$redirectURL = get_admin_url() . 'admin.php?page=sales_channel&channel=etsy&section=templates&shop_name=' . $shop_id;
				wp_redirect( $redirectURL );
			}
		} elseif ( isset( $_GET['details'] ) && 'edit' == $_GET['details'] ) {
			require_once CED_ETSY_DIRPATH . 'admin/template/view/class-ced-view-profile-edit.php';
		} else {
			$redirectURL = get_admin_url() . 'admin.php?page=sales_channel&channel=etsy&section=templates&shop_name=' . $shop_id;
			wp_redirect( $redirectURL );
		}
	}
}

$ced_etsy_profile_obj = new Ced_Etsy_Profile_Table();
$ced_etsy_profile_obj->prepare_items();
