<?php
use Cedcommerce\EtsyManager\Ced_Etsy_Manager as EtsyManager;
use Cedcommerce\Product\Ced_Product_Category as EtsyCategory;
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    Ced_Etsy_Integration
 * @subpackage Ced_Etsy_Integration/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Ced_Etsy_Integration
 * @subpackage Ced_Etsy_Integration/admin
 */
class Ced_Etsy_Integration_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->etsy_manager = EtsyManager::get_instance();
		// require_once CED_ETSY_DIRPATH . 'admin/ced-builder/order/class-ced-order-get.php';
		// $this->ced_etsy_order   = new Ced_Order_Get();
		$this->ced_etsy_product = $this->etsy_manager->{'etsy_product_upload'};
		$this->etsy_cat_obj     = EtsyCategory::get_instance();
		$this->plugin_name      = $plugin_name;
		require_once CED_ETSY_DIRPATH . 'admin/lib/class-ced-etsy-activities.php';
		$activity            = new Etsy_Activities();
		$GLOBALS['activity'] = $activity;

		add_action( 'ced_show_connected_accounts', array( $this, 'ced_show_connected_accounts' ) );
		add_action( 'ced_show_connected_accounts_details', array( $this, 'ced_show_connected_accounts_details' ) );

		add_action( 'wp_ajax_ced_etsy_update_inventory', array( $this, 'ced_etsy_inventory_schedule_manager' ) );
		add_action( 'wp_ajax_nopriv_ced_etsy_update_inventory', array( $this, 'ced_etsy_inventory_schedule_manager' ) );

		add_action( 'wp_ajax_ced_etsy_sync_existing_products', array( $this, 'ced_etsy_sync_existing_products' ) );
		add_action( 'wp_ajax_nopriv_ced_etsy_sync_existing_products', array( $this, 'ced_etsy_sync_existing_products' ) );

		add_action( 'wp_ajax_ced_etsy_load_more_logs', array( $this, 'ced_etsy_load_more_logs' ) );

		if ( isset( $_GET['channel'] ) && 'etsy' == $_GET['channel'] && isset( $_GET['shop_name'] ) ) {
			update_option( 'ced_etsy_shop_name', sanitize_text_field( $_GET['shop_name'] ) );
		}

		add_action( 'admin_footer', array( $this, 'ced_etsy_trademark_notice' ) );
	}

	public function ced_etsy_trademark_notice() {
		if ( isset( $_GET['page'] ) && 'sales_channel' == $_GET['page'] && isset( $_GET['channel'] ) && 'etsy' == $_GET['channel'] ) {
			echo "<div class='ced_etsy_trademark_notice'><p><i><strong>NOTE:</strong> #The term 'Etsy' is a trademark of Etsy, Inc. This application uses the Etsy API but is not endorsed or certified by Etsy, Inc.#</i></p></div>";
		}
	}

	public function ced_show_connected_accounts( $channel = 'etsy' ) {
		if ( 'etsy' == $channel ) {
			$connected_accounts = get_etsy_connected_accounts();
			if ( ! empty( $connected_accounts ) ) {
				?>
				<a class="woocommerce-importer-done-view-errors-etsy" href="javascript:void(0)"><?php echo esc_attr( count( $connected_accounts ) ); ?> account
					connected <span class="dashicons dashicons-arrow-down-alt2"></span></a>
					<?php
			}
		}
	}

	public function ced_show_connected_accounts_details( $channel = 'etsy' ) {
		if ( 'etsy' == $channel ) {
			$connected_accounts = get_etsy_connected_accounts();
			if ( ! empty( $connected_accounts ) ) {

				?>
				<div id="ced-etsy-disconnect-account-modal" class="ced-modal">

					<div class="ced-modal-text-content">
						<div class="ced_etsy_error"></div>
						<h4>Are you sure want to delete the account ?</h4>
						<div class="ced-button-wrap-popup">
							<span class="spinner"></span>
							<span id="ced-etsy-delete-account" data-shop-name="" class="button-primary">Confirm</span>
							<span class="ced-close-button">Cancel</span>
						</div>
					</div>

				</div>
				<tr class="wc-importer-error-log-etsy" style="display:none;">
					<td colspan="4">
						<div class="ced-account-connected-form">
							<div class="ced-account-connected-form">
								<div class="ced-account-head">
									<div class="ced-account-label">
										<strong>Account Details</strong>
									</div>
									<div class="ced-account-label">
										<strong>Status</strong>
									</div>
									<div class="ced-account-label">
										<!-- <p>Status</p> -->
									</div>
								</div>
								<?php
								foreach ( $connected_accounts as $account ) {
									?>
									<div class="ced-account-body">
										<div class="ced-acount-body-label">
											<strong><?php echo esc_attr( $account['details']['ced_etsy_shop_name'] ); ?></strong>
										</div>
										<div class="ced-connected-button-wrapper">

											<?php
											$overview    = ced_get_navigation_url(
												'etsy',
												array(
													'panel'     => 'overview',
													'shop_name' => $account['details']['ced_etsy_shop_name'],
												)
											);
											$setup_steps = get_option( 'ced_etsy_setup_steps', array() );
											if ( empty( $setup_steps[ $account['details']['ced_etsy_shop_name'] ]['current_step'] ) ) {
												?>
												<a style="width: 33%;" class="ced-connected-link-account" href="
												<?php
												echo esc_url( $overview );
												?>
												"><span class="ced-circle"></span>Onboarding Completed</a>
												<?php
											} else {
												$overview = $setup_steps[ $account['details']['ced_etsy_shop_name'] ]['current_step'];
												?>
												<a style="width: 33%;" class="ced-pending-link-account"><span class="ced-circle"></span>Onboarding Pending</a>
												<?php
											}
											?>

										</div>
										<div class="ced-account-button">
											<button type="button" class="components-button is-tertiary" id="ced_etsy_disconnect_account" data-shop-name="<?php echo esc_attr( $account['details']['ced_etsy_shop_name'] ); ?>"> Disconnect</button>
											<button type="button" class="components-button is-primary ced-manage" style="margin:unset;"><a href="<?php echo esc_url( $overview ); ?>">Manage</a></button>
											
										</div>
										
									</div>

									<?php
								}
								?>

							</div>
						</div>
					</td>
				</tr>
				<?php
			}
		}
	}

	public function ced_etsy_load_more_logs() {
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$parent          = isset( $sanitized_array['parent'] ) ? $sanitized_array['parent'] : '';
			$offset          = isset( $sanitized_array['offset'] ) ? (int) $sanitized_array['offset'] : '';
			$total           = isset( $sanitized_array['total'] ) ? (int) $sanitized_array['total'] : '';

			$log_info = get_option( $parent, '' );
			if ( empty( $log_info ) ) {
				$log_info = array();
			} else {
				$log_info = json_decode( $log_info, true );
			}
			$log_info   = array_slice( $log_info, (int) $offset, 50 );
			$is_disable = 'no';
			$html       = '';
			if ( ! empty( $log_info ) ) {
				$offset += count( $log_info );
				foreach ( $log_info as $key => $info ) {

					$html .= "<tr class='ced_etsy_log_rows'>";
					$html .= "<td><span class='log_item_label log_details'><a>" . ( $info['post_title'] ) . "</a></span><span class='log_message' style='display:none;'><h3>Input payload for " . ( $info['post_title'] ) . '</h3><button id="ced_close_log_message">Close</button><pre>' . ( ! empty( $info['input_payload'] ) ? json_encode( $info['input_payload'], JSON_PRETTY_PRINT ) : '' ) . '</pre></span></td>';
					$html .= "<td><span class=''>" . $info['action'] . '</span></td>';
					$html .= "<td><span class=''>" . $info['time'] . '</span></td>';
					$html .= "<td><span class=''>" . ( $info['is_auto'] ? 'Automatic' : 'Manual' ) . '</span></td>';
					$html .= '<td>';
					if ( isset( $info['response']['response']['results'] ) || isset( $info['response']['results'] ) || isset( $info['response']['listing_id'] ) || isset( $info['response']['response']['products'] ) || isset( $info['response']['products'] ) || isset( $info['response']['listing_id'] ) ) {
						$html .= "<span class='etsy_log_success log_details'>Success</span>";
					} else {
						$html .= "<span class='etsy_log_fail log_details'>Failed</span>";
					}
					$html .= "<span class='log_message' style='display:none;'><h3>Response payload for " . ( $info['post_title'] ) . '</h3><button id="ced_close_log_message">Close</button><pre>' . ( ! empty( $info['response'] ) ? json_encode( $info['response'], JSON_PRETTY_PRINT ) : '' ) . '</pre></span>';
					$html .= '</td>';
					$html .= '</tr>';
				}
			}
			if ( $offset >= $total ) {
				$is_disable = 'yes';
			}
			echo json_encode(
				array(
					'html'       => $html,
					'offset'     => $offset,
					'is_disable' => $is_disable,
				)
			);
			wp_die();
		}
	}



	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		global $pagenow;
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Ced_Etsy_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Ced_Etsy_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if ( isset( $_GET['page'] ) && ( 'sales_channel' == $_GET['page'] ) ) {

			/*
			woocommerce style */
			// wp_register_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
			wp_enqueue_style( 'woocommerce_admin_styles' );
			wp_enqueue_style( WC_ADMIN_APP );

			/* woocommerce style */

			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . '/assets/css/etsy-integration-admin.css', array(), $this->version, 'all' );

		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		global $pagenow;
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Ced_Etsy_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Ced_Etsy_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if ( isset( $_GET['page'] ) && ( 'sales_channel' == $_GET['page'] ) ) {
			/* woocommerce script */
			$suffix = '';
			wp_register_script( 'woocommerce_admin', WC()->plugin_url() . '/assets/js/admin/woocommerce_admin' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-tiptip' ), WC_VERSION );
			wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), WC_VERSION, true );

			$params = array(
				'strings' => array(
					'import_products' => __( 'Import', 'woocommerce' ),
					'export_products' => __( 'Export', 'woocommerce' ),
				),
				'urls'    => array(
					'import_products' => esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_importer' ) ),
					'export_products' => esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_exporter' ) ),
				),
			);

			wp_localize_script( 'woocommerce_admin', 'woocommerce_admin', $params );
			wp_enqueue_script( 'woocommerce_admin' );
			wp_enqueue_script( 'selectWoo' );

			/* woocommerce script */

			$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';

			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'assets/js/etsy-integration-admin.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name . '_template_category', plugin_dir_url( __FILE__ ) . 'assets/js/ced-etsy-cat.js', array( 'jquery' ), $this->version, false );
			$ajax_nonce     = wp_create_nonce( 'ced-etsy-ajax-seurity-string' );
			$localize_array = array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => $ajax_nonce,
				'shop_name'  => $shop_name,
				'etsy_path'  => CED_ETSY_URL,
			);
			wp_localize_script( $this->plugin_name, 'ced_etsy_admin_obj', $localize_array );
			wp_localize_script( $this->plugin_name . '_template_category', 'ced_etsy_admin_obj', $localize_array );
		}

		// wp_enqueue_script( $this->plugin_name . '_etsy_shippment_manage', plugin_dir_url( __FILE__ ) . 'assets/js/ced-etsy-shippment-manage.js', array( 'jquery' ), $this->version, false );
		$localize_array = array(
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'ajax_nonce' => wp_create_nonce( 'ced-etsy-ajax-seurity-string' ),
			'etsy_path'  => CED_ETSY_URL,
		);
		wp_localize_script( $this->plugin_name . '_etsy_shippment_manage', 'ced_etsy_shipment_obj', $localize_array );
	}

	public function ced_zoho_chat() {

		if ( isset( $_GET['page'] ) && 'sales_channel' == $_GET['page'] && isset( $_GET['channel'] ) && 'etsy' == $_GET['channel'] ) {
			echo ' <script type="text/javascript" id="zsiqchat">var $zoho=$zoho || {};$zoho.salesiq = $zoho.salesiq || {widgetcode: "siqa8c5761519650fe047076203095c17a98d7328e7afd77313fcda6c78dfb88931", values:{},ready:function(){
			 $zoho.salesiq.visitor.info({"App Name" : "Etsy Product Lister", "FrameWork": "WooCommerce"});
		}};var d=document;s=d.createElement("script");s.type="text/javascript";s.id="zsiqscript";s.defer=true;s.src="https://salesiq.zohopublic.in/widget";t=d.getElementsByTagName("script")[0];t.parentNode.insertBefore(s,t);</script>';
		}
	}

	/**
	 * Add admin menus and submenus
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_add_menus() {
		global $submenu;

		$menu_slug = 'woocommerce';

		if ( ! empty( $submenu[ $menu_slug ] ) ) {
			$sub_menus = array_column( $submenu[ $menu_slug ], 2 );
			if ( ! in_array( 'sales_channel', $sub_menus ) ) {
				add_submenu_page( 'woocommerce', 'CedCommerce', 'CedCommerce', 'manage_woocommerce', 'sales_channel', array( $this, 'ced_marketplace_home_page' ) );
			}
		}
	}

	/**
	 * Active Marketplace List
	 *
	 * @since    1.0.0
	 */

	public function ced_marketplace_home_page() {

		require CED_ETSY_DIRPATH . 'admin/template/home/home.php';
		if ( isset( $_GET['page'] ) && 'sales_channel' == $_GET['page'] && ! isset( $_GET['channel'] ) ) {
			require CED_ETSY_DIRPATH . 'admin/template/home/marketplaces.php';
		} elseif ( isset( $_GET['page'] ) && 'sales_channel' == $_GET['page'] && isset( $_GET['channel'] ) ) {
			$channel = isset( $_GET['channel'] ) ? sanitize_text_field( wp_unslash( $_GET['channel'] ) ) : '';
			/**
			 * Filter the output to menus and section of Etsy channel.
			 *
			 * @since    1.0.0
			 * @param string $channel Current channel .
			 */
			do_action( 'ced_sales_channel_include_template', $channel );
		}
	}

	public function ced_etsy_add_marketplace_menus_to_array( $menus = array() ) {
		$installed_plugins = get_plugins();
		/**
		 * Get all active plugins.
		 *
		 * @since    1.0.0
		 * @param string $active Active plugin name .
		 */
		$active_marketplaces_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
		$menus                       = array(
			'product-lister-etsy'                 => array(
				'name'            => 'Etsy Integration',
				'tab'             => 'Etsy',
				'page_url'        => 'https://woocommerce.com/products/etsy-integration-for-woocommerce/',
				'doc_url'         => 'https://woocommerce.com/document/etsy-integration-for-woocommerce/',
				'slug'            => 'product-lister-etsy',
				'menu_link'       => 'etsy',
				'card_image_link' => CED_ETSY_URL . 'admin/assets/images/etsy-logo.png',
				'is_active'       => in_array( 'product-lister-etsy/product-lister-etsy.php', $active_marketplaces_plugins ),
				'is_installed'    => isset( $installed_plugins['product-lister-etsy/product-lister-etsy.php'] ) ? true : false,
			),
			'walmart-integration-for-woocommerce' => array(
				'name'            => 'Walmart Integration',
				'tab'             => 'Walmart',
				'page_url'        => 'https://woocommerce.com/products/walmart-integration-for-woocommerce/',
				'doc_url'         => 'https://woocommerce.com/document/walmart-integration-for-woocommerce/',
				'slug'            => 'walmart-integration-for-woocommerce',
				'menu_link'       => 'walmart',
				'card_image_link' => CED_ETSY_URL . 'admin/assets/images/walmart-logo.png',
				'is_active'       => in_array( 'walmart-integration-for-woocommerce/walmart-woocommerce-integration.php', $active_marketplaces_plugins ),
				'is_installed'    => isset( $installed_plugins['walmart-integration-for-woocommerce/walmart-woocommerce-integration.php'] ) ? true : false,
			),
			'ebay-integration-for-woocommerce'    => array(
				'name'            => 'eBay Integration',
				'tab'             => 'eBay',
				'page_url'        => 'https://woocommerce.com/products/ebay-integration-for-woocommerce/',
				'doc_url'         => 'https://woocommerce.com/document/ebay-integration-for-woocommerce/',
				'slug'            => 'ebay-integration-for-woocommerce',
				'menu_link'       => 'ebay',
				'card_image_link' => CED_ETSY_URL . 'admin/assets/images/ebay-logo.png',
				'is_active'       => in_array( 'ebay-integration-for-woocommerce/ebay-integration-for-woocommerce.php', $active_marketplaces_plugins ),
				'is_installed'    => isset( $installed_plugins['ebay-integration-for-woocommerce/ebay-woocommerce-integration.php'] ) ? true : false,
			),
			'amazon-for-woocommerce'              => array(
				'name'            => 'Amazon Integration',
				'tab'             => 'Amazon',
				'page_url'        => 'https://woocommerce.com/products/amazon-for-woocommerce/',
				'doc_url'         => 'https://woocommerce.com/document/amazon-for-woocommerce/',
				'slug'            => 'amazon-for-woocommerce',
				'menu_link'       => 'amazon',
				'card_image_link' => CED_ETSY_URL . 'admin/assets/images/amazon-logo.png',
				'is_active'       => in_array( 'amazon-for-woocommerce/amazon-for-woocommerce.php', $active_marketplaces_plugins ),
				'is_installed'    => isset( $installed_plugins['amazon-for-woocommerce/amazon-for-woocommerce.php'] ) ? true : false,
			),
		);
		return $menus;
	}

	/**
	 * Ced Etsy Accounts Page
	 *
	 * @since    1.0.0
	 */
	public function ced_sales_channel_include_template( $channel = 'etsy' ) {
		if ( 'etsy' === $channel ) {
			$shop_name       = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
			$step            = isset( $_GET['step'] ) ? sanitize_text_field( wp_unslash( $_GET['step'] ) ) : '';
			$section         = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';
			$this->shop_name = ! empty( $shop_name ) ? $shop_name : get_option( 'ced_etsy_shop_name', '' );
			$account         = new Cedcommerce\Template\View\Ced_View_Etsy_Accounts();
			$current_setup   = get_option( 'ced_etsy_setup_steps', array() );
			if ( 0 == count( get_option( 'ced_etsy_details', array() ) ) || isset( $_GET['add-new-account'] ) ) {
				$show_message = '';
				$message      = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';
				if ( 'blank' === $message ) {
					$show_message = $account->ced_etsy_onboarding_message( 'Before moving forward, please input the shop name', 'ced-onboarding-error-notification' );
				} elseif ( 'same_shop' === $message ) {
					$show_message = $account->ced_etsy_onboarding_message( 'This shop is already connected, please try another shop.', 'ced-onboarding-error-notification' );
				}
				if ( 'reconnect' === $section && ! empty( $this->shop_name ) ) {
					$connected_accounts = get_etsy_connected_accounts();
					unset( $connected_accounts[ $this->shop_name ] );
					update_option( 'ced_etsy_details', $connected_accounts );
				}
				print_r( $account->ced_etsy_connect_e_shop_onboarding_html( '', $show_message ) );
			} elseif ( 'connected' === $section && count( get_option( 'ced_etsy_details', array() ) ) ) {
				$current_setup[ $this->shop_name ]['current_step'] = isset( $_SERVER['REQUEST_URI'] ) && ! empty( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : admin_url( 'admin.php?page=sales_channel&channel=etsy&section=connected&shop_name=' . $this->shop_name );
				$user_details                                      = get_option( 'ced_etsy_details', array() );
				$account->shop_name                                = $this->shop_name;
				$form = new \Cedcommerce\Template\View\Render\Ced_Render_Form();
				print_r( $form->form_open( 'POST', '' ) );
				wp_nonce_field( 'ced_etsy_verify_and_continue', 'ced_etsy_verify_and_continue_submit' );
				print_r( $account->ced_etsy_completed_authorisation_view( $user_details, '', $shop_name ) );
				print_r( $form->form_close() );
			} elseif ( 'sync_existing' === $section ) {

				$current_setup[ $this->shop_name ]['current_step'] = isset( $_SERVER['REQUEST_URI'] ) && ! empty( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : admin_url( 'admin.php?page=sales_channel&channel=etsy&section=connected&shop_name=' . $this->shop_name );
				$all_e_pro = isset( $_GET['count'] ) ? sanitize_text_field( wp_unslash( $_GET['count'] ) ) : '';
				$message   = $account->ced_etsy_onboarding_message( 'We found ' . $all_e_pro . ' items in your Etsy store. Enable syncing and enjoy real-time updates between Etsy and WooCommerce stores. ' );
				$form      = new \Cedcommerce\Template\View\Render\Ced_Render_Form();
				print_r( $form->form_open( 'POST', '' ) );
				wp_nonce_field( 'ced_etsy_verify_and_continue', 'ced_etsy_verify_and_continue_submit' );
				print_r( $account->ced_etsy_sync_existing_products_html_view( $message, $shop_name ) );
				print_r( $form->form_close() );

			} elseif ( 'setup' === $section ) {

				$account->ced_etsy_setup_wizard();
				return true;

			} elseif ( 'ced_etsy_completed' === $step ) {

				$current_setup[ $this->shop_name ]['current_step'] = false;
				$user_details                                      = get_option( 'ced_etsy_details', array() );
				$account->shop_name                                = get_option( 'ced_etsy_shop_name', '' );
				$form = new \Cedcommerce\Template\View\Render\Ced_Render_Form();
				print_r( $form->form_open( 'POST', '' ) );
				wp_nonce_field( 'ced_etsy_verify_and_continue', 'ced_etsy_verify_and_continue_submit' );
				print_r( $account->ced_etsy_completed_authorisation_view( $user_details ) );
				print_r( $form->form_close() );

			} else {

				$ced_e_header = new \Cedcommerce\Template\View\Ced_View_Header();
				$section      = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : 'overview';
				$file         = '';
				switch ( $section ) {
					case 'settings':
						$file = CED_ETSY_DIRPATH . 'admin/template/view/class-ced-view-settings.php';
						break;
					case 'category':
						$file = CED_ETSY_DIRPATH . 'admin/template/view/class-ced-view-category.php';
						break;
					case 'templates':
						$file = CED_ETSY_DIRPATH . 'admin/template/view/class-ced-view-profiles.php';
						break;
					case 'products':
						$file = CED_ETSY_DIRPATH . 'admin/template/view/class-ced-view-products.php';
						break;
					case 'timeline':
						$file = CED_ETSY_DIRPATH . 'admin/template/view/class-ced-view-timeline.php';
						break;
					case 'add-shipping-profile':
						$file = CED_ETSY_DIRPATH . 'admin/template/view/class-ced-view-add-shipping-profile.php';
						break;
					case 'overview':
						$file = CED_ETSY_DIRPATH . 'admin/template/view/ced-etsy-overview.php';
						break;
				}

				if ( $file && file_exists( $file ) ) {
					echo "<div class='ced_etsy_body'>";
						require_once $file;
					echo '</div>';
				}
			}
			update_option( 'ced_etsy_setup_steps', $current_setup );
		}
	}


	/**
	 * Marketplace
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_marketplace_to_be_logged( $marketplaces = array() ) {

		$marketplaces[] = array(
			'name'             => 'Etsy',
			'marketplace_slug' => 'etsy',
		);
		return $marketplaces;
	}

	/**
	 * Etsy Cron Schedules
	 *
	 * @since    1.0.0
	 */
	public function my_etsy_cron_schedules( $schedules ) {
		if ( ! isset( $schedules['ced_etsy_6min'] ) ) {
			$schedules['ced_etsy_6min'] = array(
				'interval' => 6 * 60,
				'display'  => __( 'Once every 6 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_etsy_10min'] ) ) {
			$schedules['ced_etsy_10min'] = array(
				'interval' => 10 * 60,
				'display'  => __( 'Once every 10 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_etsy_15min'] ) ) {
			$schedules['ced_etsy_15min'] = array(
				'interval' => 15 * 60,
				'display'  => __( 'Once every 15 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_etsy_30min'] ) ) {
			$schedules['ced_etsy_30min'] = array(
				'interval' => 30 * 60,
				'display'  => __( 'Once every 30 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_etsy_20min'] ) ) {
			$schedules['ced_etsy_20min'] = array(
				'interval' => 20 * 60,
				'display'  => __( 'Once every 20 minutes' ),
			);
		}
		return $schedules;
	}

	/**
	 * Etsy Inventory Scheduler
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_inventory_schedule_manager() {

		$hook    = current_action();
		$shop_id = str_replace( 'ced_etsy_inventory_scheduler_job_', '', $hook );
		$shop_id = trim( $shop_id );

		$shop_id = get_option( 'ced_etsy_shop_name', '' );

		$products_to_sync = get_option( 'ced_etsy_chunk_products_' . $shop_id, array() );
		if ( empty( $products_to_sync ) ) {
			$store_products   = get_posts(
				array(
					'numberposts' => -1,
					'post_type'   => 'product',
					'meta_query'  => array(
						array(
							'key'     => '_ced_etsy_listing_id_' . $shop_id,
							'compare' => 'EXISTS',
						),
					),
				)
			);
			$store_products   = wp_list_pluck( $store_products, 'ID' );
			$products_to_sync = array_chunk( $store_products, 10 );

		}
		if ( is_array( $products_to_sync[0] ) && ! empty( $products_to_sync[0] ) ) {
			foreach ( $products_to_sync[0] as $product_id ) {
				$response = ( new \Cedcommerce\Product\Ced_Product_Update( $shop_id, $product_id ) )->ced_etsy_update_inventory( $product_id, $shop_id, true );
			}
			unset( $products_to_sync[0] );
			$products_to_sync = array_values( $products_to_sync );
			update_option( 'ced_etsy_chunk_products_' . $shop_id, $products_to_sync );
		}
	}



	/**
	 * Etsy Sync existing products scheduler
	 *
	 * @since    1.0.5
	 */
	public function ced_etsy_sync_existing_products() {
		$hook      = current_action();
		$shop_name = str_replace( 'ced_etsy_sync_existing_products_job_', '', $hook );
		$shop_name = trim( $shop_name );
		$shop_name = ! empty( $shop_name ) ? $shop_name : get_option( 'ced_etsy_shop_name', '' );
		$shop_id   = get_etsy_shop_id( $shop_name );
		$offset    = get_option( 'ced_etsy_get_offset_' . $shop_name, '' );
		if ( empty( $offset ) ) {
			$offset = 0;
		}
		$query_args = array(
			'offset' => $offset,
			'limit'  => 25,
			'state'  => 'active',
		);

		/** Refresh token
		 *
		 * @since 2.0.0
		 */
		do_action( 'ced_etsy_refresh_token', $shop_name );
		$action   = "application/shops/{$shop_id}/listings";
		$response = etsy_request()->get( $action, $shop_name, $query_args );
		if ( isset( $response['results'][0] ) ) {

			// Manage syncing with Etsy and WC identifier

			$saved_identifiers = get_option( 'ced_etsy_sync_existing_by_identifiers_' . $shop_name, array() );
			$e_identifier      = isset( $saved_identifiers['etsy_identifier'] ) ? $saved_identifiers['etsy_identifier'] : 'sku';
			$wc_identifier     = isset( $saved_identifiers['wc_identifier'] ) ? $saved_identifiers['wc_identifier'] : 'sku';
			$product_id        = false;

			foreach ( $response['results'] as $key => $value ) {
				if ( 'sku' == $e_identifier && 'sku' == $wc_identifier ) {
					$sku = isset( $value['skus'][0] ) ? $value['skus'][0] : false;
					if ( $sku ) {
						$product_id = wc_get_product_id_by_sku( $sku );
						if ( $product_id ) {
							$_product = wc_get_product( $product_id );
							if ( 'variation' == $_product->get_type() ) {
								$product_id = $_product->get_parent_id();
							}
						}
					}
				} elseif ( 'listing_id' === $e_identifier && 'sku' === $wc_identifier ) {
					$listing_id = isset( $value['listing_id'] ) ? $value['listing_id'] : false;
					if ( $listing_id ) {
						$product_id = wc_get_product_id_by_sku( $listing_id );
						if ( $product_id ) {
							$_product = wc_get_product( $product_id );
							if ( 'variation' == $_product->get_type() ) {
								$product_id = $_product->get_parent_id();
							}
						}
					}
				} elseif ( 'listing_id' === $e_identifier && 'product_id' === $wc_identifier ) {
					$product_id = isset( $value['listing_id'] ) ? $value['listing_id'] : false;
				}

				if ( '' !== $product_id && null !== $product_id && ! empty( $product_id ) ) {
					update_post_meta( $product_id, '_ced_etsy_state_' . $shop_name, $value['state'] );
					update_post_meta( $product_id, '_ced_etsy_url_' . $shop_name, $value['url'] );
					update_post_meta( $product_id, '_ced_etsy_listing_id_' . $shop_name, $value['listing_id'] );
					update_post_meta( $product_id, '_ced_etsy_listing_data_' . $shop_name, json_encode( $value ) );
				}
			}
			$next_offset = $offset + 25;
			update_option( 'ced_etsy_get_offset_' . $shop_name, $next_offset );
		} else {
			update_option( 'ced_etsy_get_offset_' . $shop_name, 0 );
		}
	}


	/**
	 * Etsy Bulk Operations
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_process_bulk_action() {
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$shop_name           = isset( $_POST['shopname'] ) ? sanitize_text_field( wp_unslash( $_POST['shopname'] ) ) : '';
			$operation           = isset( $_POST['operation_to_be_performed'] ) ? sanitize_text_field( wp_unslash( $_POST['operation_to_be_performed'] ) ) : '';
			$product_id          = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
			$title               = '<b><i>' . get_the_title( $product_id ) . '</i></b>';
			$already_uploaded    = get_post_meta( $product_id, '_ced_etsy_listing_id_' . $shop_name, true );
			$response['status']  = 400;
			$response['message'] = 'you need to upload this product first';
			switch ( $operation ) {
				case 'upload_product':
					$restricted = ced_etsy_check_if_limit_reached( $shop_name );
					if ( $restricted ) {
						echo json_encode(
							array(
								'status'  => 400,
								'message' => __(
									'You have reached the maximum limit of syncing 100 products from WooCommerce to Etsy . If you wish to sync more products and manage orders as well upgrade to premium version now ! <p><a href="https://woocommerce.com/products/etsy-integration-for-woocommerce/" target="_blank"><button id="" type="submit" class="button-primary get_preminum">Upgrade Now</button></a></p>',
									'product-lister-etsy'
								),
							)
						);
						die;
					}
					if ( ! $already_uploaded ) {
						$response = ( new \Cedcommerce\Product\Ced_Product_Upload( $product_id, $shop_name ) )->ced_etsy_upload_product( $product_id, $shop_name );
					} else {
						$response['status']  = 400;
						$response['message'] = 'Product already uploaded';
					}
					break;
				case 'update_product':
					if ( $already_uploaded ) {
						$response = ( new \Cedcommerce\Product\Ced_Product_Update( $product_id, $shop_name ) )->ced_etsy_update_product( $product_id, $shop_name );
					}
					break;
				case 'remove_product':
					if ( $already_uploaded ) {
						$response = ( new \Cedcommerce\Product\Ced_Product_Delete( $shop_name, $product_id ) )->ced_etsy_delete_product( $product_id, $shop_name );
					}
					break;
				case 'update_inventory':
					if ( $already_uploaded ) {
						$response = ( new \Cedcommerce\Product\Ced_Product_Update( $product_id, $shop_name ) )->ced_etsy_update_inventory( $product_id, $shop_name );
					}
					break;
				case 'update_image':
					if ( $already_uploaded ) {
						$response = ( new \Cedcommerce\Product\Ced_Product_Update( $product_id, $shop_name ) )->ced_update_images_on_etsy( $product_id, $shop_name );
					}
					break;
				case 'unlink_product':
					if ( $already_uploaded ) {
						delete_post_meta( $product_id, '_ced_etsy_listing_id_' . $shop_name );
						delete_post_meta( $product_id, '_ced_etsy_url_' . $shop_name );
						delete_post_meta( $product_id, '_ced_etsy_listing_data_' . $shop_name );
						delete_post_meta( $product_id, '_ced_etsy_state_' . $shop_name );
						$response['status']  = 200;
						$response['message'] = 'Unlinked successfully';
					}
					break;
				default:
					$response['status']  = 400;
					$response['message'] = 'Invalid operation';
					break;
			}

			echo json_encode(
				array(
					'status'  => $response['status'],
					'message' => $title . ' : ' . ced_etsy_format_response( $response['message'], $shop_name ),
				)
			);
			wp_die();
		}
	}


	private function ced_notice_response( $status = '', $message = '', $product_id = '' ) {
		return json_encode(
			array(
				'status'  => $status,
				'message' => __( $message, 'product-lister-etsy' ),
				'prodid'  => $product_id,
			)
		);
	}

	/**
	 * ***********************************************************
	 * CED etsy prdouct field table on the simple product level .
	 * ***********************************************************
	 *
	 * @since 2.0.0
	 */
	public function ced_etsy_product_data_tabs( $tabs ) {
		$tabs['etsy_inventory'] = array(
			'label'  => __( 'Etsy', 'product-lister-etsy' ),
			'target' => 'etsy_inventory_options',
			'class'  => array( 'show_if_simple', 'show_if_variable' ),
		);
		return $tabs;
	}


	/**
	 * ******************************************************************
	 * Woocommerce_Etsy_Integration_Admin ced_Etsy_product_data_panels.
	 * ******************************************************************
	 *
	 * @since 2.0.0
	 */
	public function ced_etsy_product_data_panels() {

		global $post;

		?>
		<div id='etsy_inventory_options' class='panel woocommerce_options_panel'><div class='options_group'>
			<form>
				<?php wp_nonce_field( 'ced_product_settings', 'ced_product_settings_submit' ); ?>
			</form>
			<?php
			echo "<div class='ced_etsy_simple_product_level_wrap'>";
			echo "<div class=''>";
			echo "<h2 class='etsy-cool'>Etsy Product Data";
			echo '</h2>';
			echo '</div>';
			echo "<div class='ced_etsy_simple_product_content' style='max-height: 350px;min-height: 350px;
			overflow: scroll;'>";
			$this->ced_esty_render_fields( $post->ID, true );
			echo '</div>';
			echo '</div>';
			?>
		</div></div>
		<?php
	}
	/**
	 * ******************************************************************
	 * Woocommerce_Etsy_Integration_Admin ced_Etsy_product_data_panels.
	 * ******************************************************************
	 *
	 * @since 2.0.0
	 */

	public function ced_etsy_render_product_fields( $loop, $variation_data, $variation ) {
		if ( ! empty( $variation_data ) ) {
			?>
			<div id='etsy_inventory_options_variable' class='panel woocommerce_options_panel'><div class='options_group'>
				<form>
					<?php wp_nonce_field( 'ced_product_settings', 'ced_product_settings_submit' ); ?>
				</form>
				<?php
				echo "<div class='ced_etsy_variation_product_level_wrap'>";
				echo "<div class='ced_etsy_parent_element'>";
				echo "<h2 class='etsy-cool'> Etsy Product Data";
				echo "<span class='dashicons dashicons-arrow-down-alt2 ced_etsy_instruction_icon'></span>";
				echo '</h2>';
				echo '</div>';
				echo "<div class='ced_etsy_variation_product_content ced_etsy_child_element'>";
				$this->ced_esty_render_fields( $variation->ID, false );
				echo '</div>';
				echo '</div>';
				?>
			</div></div>
			<?php
		}
	}

	/**
	 * ********************************************************
	 * CREATE FIELDS AT EACH VARIATIONS LEVEL FOR ENTER PRICE
	 * ********************************************************
	 *
	 * @since 2.0.0
	 */

	public function ced_esty_render_fields( $product_id = '', $simple_product = '' ) {

		$productFieldInstance = \Cedcommerce\Template\Ced_Template_Product_Fields::get_instance();
		$settings             = $productFieldInstance->get_custom_products_fields( get_etsy_shop_name() );

		$variation_fields = array(
			'_ced_etsy_price',
			'_ced_etsy_markup_type',
			'_ced_etsy_markup_value',
			'_ced_etsy_stock',
		);

		if ( ! empty( $settings ) ) {
			foreach ( $settings as $section => $product_fields ) {
				foreach ( $product_fields as $key => $value ) {

					$label    = isset( $value['fields']['label'] ) ? $value['fields']['label'] : '';
					$field_id = isset( $value['fields']['id'] ) ? $value['fields']['id'] : '';

					if ( ! in_array( $field_id, $variation_fields ) && ! $simple_product ) {
						continue;
					}

					$id             = 'ced_etsy_data[' . $product_id . '][' . $field_id . ']';
					$selected_value = get_post_meta( $product_id, $field_id, true );

					if ( '_select' == $value['type'] ) {
						$option_array     = array();
						$option_array[''] = '--select--';
						foreach ( $value['fields']['options'] as $option_key => $option ) {
							$option_array[ $option_key ] = $option;
						}
						woocommerce_wp_select(
							array(
								'id'          => $id,
								'label'       => $value['fields']['label'],
								'options'     => $option_array,
								'value'       => $selected_value,
								'desc_tip'    => 'true',
								'description' => $value['fields']['description'],
								'class'       => 'ced_etsy_product_select',
							)
						);
					} elseif ( '_text_input' == $value['type'] ) {
						woocommerce_wp_text_input(
							array(
								'id'          => $id,
								'label'       => $value['fields']['label'],
								'desc_tip'    => 'true',
								'description' => $value['fields']['description'],
								'type'        => 'text',
								'value'       => $selected_value,
							)
						);
					}
				}
			}
		}
	}


	/**
	 * *****************************************************************
	 * Woocommerce_etsy_Integration_Admin ced_etsy_save_product_fields.
	 * *****************************************************************
	 *
	 * @since 2.0.0
	 */
	public function ced_etsy_save_product_fields_variation( $post_id = '', $i = '' ) {

		if ( empty( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['ced_product_settings_submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ced_product_settings_submit'] ) ), 'ced_product_settings' ) ) {
			return;
		}

		if ( isset( $_POST['ced_etsy_data'] ) ) {
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( ! empty( $sanitized_array ) ) {
				foreach ( $sanitized_array['ced_etsy_data'] as $id => $value ) {
					foreach ( $value as $meta_key => $meta_val ) {
						update_post_meta( $id, $meta_key, $meta_val );
					}
				}
			}
		}
	}


	/**
	 * **************************************************************
	 * Woocommerce_Etsy_Integration_Admin ced_Etsy_save_meta_data
	 * **************************************************************
	 *
	 * @since 1.0.0
	 */
	public function ced_etsy_save_meta_data( $post_id = '' ) {

		if ( empty( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['ced_product_settings_submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ced_product_settings_submit'] ) ), 'ced_product_settings' ) ) {
			return;
		}

		if ( isset( $_POST['ced_etsy_data'] ) ) {
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( ! empty( $sanitized_array ) ) {
				foreach ( $sanitized_array['ced_etsy_data'] as $id => $value ) {
					foreach ( $value as $meta_key => $meta_val ) {
						update_post_meta( $id, $meta_key, $meta_val );
					}
				}
			}
		}
	}



	public function ced_etsy_delete_shipping_profile() {
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$e_shiping_id    = isset( $sanitized_array['e_profile_id'] ) ? $sanitized_array['e_profile_id'] : array();
			$shop_name       = isset( $sanitized_array['shop_name'] ) ? $sanitized_array['shop_name'] : '';
			if ( '' != $shop_name && ! empty( $e_shiping_id ) ) {
				$shop_id = get_etsy_shop_id( $shop_name );
				$action  = 'application/shops/' . $shop_id . '/shipping-profiles/' . $e_shiping_id;
				/** Refresh token
				 *
				 * @since 2.0.0
				 */
				do_action( 'ced_etsy_refresh_token', $shop_name );
				$is_deleted = etsy_request()->delete( $action, $shop_name, array(), 'DELETE' );
				echo json_encode(
					array(
						'status'  => 200,
						'message' => __(
							'Profile is Deleted!',
							'product-lister-etsy'
						),
					)
				);
				wp_die();
			}
		}
	}

	public function ced_etsy_delete_account() {
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$sanitized_array    = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$shop_name          = isset( $sanitized_array['shop_name'] ) ? $sanitized_array['shop_name'] : '';
			$connected_accounts = get_etsy_connected_accounts();
			unset( $connected_accounts[ $shop_name ] );
			update_option( 'ced_etsy_details', $connected_accounts );
			die;
		}
	}
}
