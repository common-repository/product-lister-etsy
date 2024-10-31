<?php
/**
 * Class Ced View Settings.
 *
 * @package Settings view
 * Class Ced View Settings is under the Cedcommerce\View\Settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * The Settings specific class..
 *
 * Ced_View_Settings class is rending fields which are required to show on the settings tab.
 *
 * @package    Ced_Etsy_Integration
 * @subpackage Ced_Etsy_Integration/View/Settings
 */
class Ced_View_Settings {
	/**
	 * The Current shop name which currently active now.
	 *
	 * @since    2.1.3
	 * @var      string    $plugin_name   The shop Name.
	 */
	public $shop_name;

	/**
	 * WC Identifier to sync with existing products.
	 *
	 * @since    2.1.3
	 * @var      string    $wc_identifiers   The identifier Name.
	 */
	public $wc_identifiers;

	/**
	 * Etsy Identifier to sync with existing products.
	 *
	 * @since    2.1.3
	 * @var      string    $e_identifiers   The identifier Name.
	 */
	public $e_identifiers;

	/**
	 * Previously saved values in DB.
	 *
	 * @since    1.0.0
	 * @var      string    $pre_saved_values    The PresavedValues is pre-saved values in DB.
	 */
	private $pre_saved_values;
	/**
	 * Cron jobs option want to increase.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $schedulers = array( 'ced_etsy_inventory_scheduler_job_' );
	/**
	 * Setting tabs.
	 *
	 * @since    1.0.0
	 * @var      string    $tabs    The ID of this plugin.
	 */
	private $tabs = array();

	/**
	 * Instializing all the required variations and functions.
	 *
	 * @since    2.1.3
	 *    string    $shop_name    The Etsy shop name.
	 */
	public function __construct( $shop_name = '' ) {
		$this->tabs      = array(
			'scheduler_setting_view' => array(
				'name' => __( 'Crons', 'product-lister-etsy' ),
				'desc' => 'This is proudct import setting where you can set setting for importing products',
			),
		);
		$this->shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( $_GET['shop_name'] ) : '';
		if ( empty( $this->shop_name ) ) {
			$this->shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		}
		if ( $this->shop_name ) {
			$this->pre_saved_values = get_option( 'ced_etsy_global_settings', array() );
			$this->pre_saved_values = isset( $this->pre_saved_values[ $this->shop_name ] ) ? $this->pre_saved_values[ $this->shop_name ] : array();
		}
		/**
		 * Get submit form here.
		 */

		if ( isset( $_POST['global_settings'] ) ) {
			if ( ! isset( $_POST['global_settings_submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['global_settings_submit'] ) ), 'global_settings' ) ) {
				return;
			}
			/**
			 * Save Settings in DB.
			 *
			 * @since    2.1.3
			 */
			$this->ced_etsy_save_settings();
		}
	}

	/**
	 * Schedule events for automate the scheduling of import and export.
	 *
	 * @since    2.1.3
	 * @var      string    $scheduler_name    The Scheduler hook name .
	 * @var      string    $times_stamp    The given times stamp.
	 */
	public function ced_schedule_events( $scheduler_name = '', $times_stamp = '' ) {
		wp_schedule_event( time(), $times_stamp, $scheduler_name . $this->shop_name );
		update_option( $scheduler_name . $this->shop_name, $this->shop_name );
	}

	/**
	 * Clear Schedule events for automate the scheduling of import and export.
	 *
	 * @since    2.1.3
	 * @var      string    $hook_name    The Scheduler hook name.
	 */
	public function ced_clear_scheduled_hook( $hook_name = '' ) {
		wp_clear_scheduled_hook( $hook_name . $this->shop_name );
	}

	/**
	 * Save setting values in Db.
	 *
	 * @since    2.1.3
	 */
	public function ced_etsy_save_settings() {

		$sanitized_array = ced_filter_input();

		if ( ! isset( $_POST['global_settings_submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['global_settings_submit'] ) ), 'global_settings' ) ) {
			return;
		}

		$ced_etsy_global_settings = isset( $sanitized_array['ced_etsy_global_settings'] ) ? $sanitized_array['ced_etsy_global_settings'] : array();
		if ( isset( $sanitized_array['ced_etsy_global_settings'] ) ) {
			foreach ( $sanitized_array['ced_etsy_global_settings'] as $scheduler => $scheduler_value ) {
				// Un-schedule the events.
				$this->ced_clear_scheduled_hook( $scheduler );
				// scheduling evens.
				if ( in_array( $scheduler, $this->schedulers ) ) {
					if ( isset( $this->schedulers[ $scheduler ] ) && 'on' === $this->schedulers[ $scheduler ] ) {
						$this->ced_schedule_events( $scheduler, 'ced_etsy_15min' );
					}
				}
			}
		}

		wp_clear_scheduled_hook( 'ced_etsy_inventory_scheduler_job_' . $this->shop_name );

		$inventory_schedule = isset( $sanitized_array['ced_etsy_global_settings']['ced_etsy_auto_update_inventory'] ) ? $sanitized_array['ced_etsy_global_settings']['ced_etsy_auto_update_inventory'] : '';

		if ( ! empty( $inventory_schedule ) ) {
			wp_schedule_event( time(), 'ced_etsy_10min', 'ced_etsy_inventory_scheduler_job_' . $this->shop_name );
			update_option( 'ced_etsy_inventory_scheduler_job_' . $this->shop_name, $this->shop_name );
		}

		$marketplace_name           = isset( $_POST['marketplaceName'] ) ? sanitize_text_field( wp_unslash( $_POST['marketplaceName'] ) ) : 'etsy';
		$offer_settings_information = array();
		$array_to_save              = array();
		if ( isset( $sanitized_array['ced_etsy_required_common'] ) ) {
			foreach ( ( $sanitized_array['ced_etsy_required_common'] ) as $key ) {
				isset( $sanitized_array[ $key ][0] ) ? $array_to_save['default'] = $sanitized_array[ $key ][0] : $array_to_save['default'] = '';

				if ( '_umb_' . $marketplace_name . '_subcategory' == $key ) {
					isset( $sanitized_array[ $key ] ) ? $array_to_save['default'] = $sanitized_array[ $key ] : $array_to_save['default'] = '';
				}

				isset( $sanitized_array[ $key . '_attribute_meta' ] ) ? $array_to_save['metakey'] = $sanitized_array[ $key . '_attribute_meta' ] : $array_to_save['metakey'] = 'null';
				$offer_settings_information['product_data'][ $key ]                               = $array_to_save;
			}
		}
		/**
		 * Getting older settings values merging with new settings values.
		 *
		 * @since    2.0.8
		 */
		$settings                     = get_option( 'ced_etsy_global_settings', array() );
		$settings[ $this->shop_name ] = array_merge( $ced_etsy_global_settings, $offer_settings_information );
		update_option( 'ced_etsy_global_settings', $settings );
		delete_option( 'ced_etsy_setup_wiz_req_attrs_' . $this->shop_name );
		delete_option( 'ced_etsy_sync_existing_by_identifiers_' . $this->shop_name );
		wp_redirect( admin_url( 'admin.php?page=sales_channel&channel=etsy&section=settings&shop_name=' . $this->shop_name ) );
		exit;
	}

	/**
	 * Showing setting values in form.
	 *
	 * @since    2.0.8
	 */
	public function settings_view( $shop_name = '' ) {
		// Manage sync existing product identifiers
		$shop_name = empty( $shop_name ) ? $this->shop_name : $shop_name;
		echo '<div class="components-card is-size-medium woocommerce-table">
				<div class="components-panel">
					<div class="wc-progress-form-content woocommerce-importer ced-padding">';
					// Rending forms.
					$form = new \Cedcommerce\Template\View\Render\Ced_Render_Form();
					print_r( $form->form_open( 'POST', '' ) );
					wp_nonce_field( 'global_settings', 'global_settings_submit' );
					$this->product_export_setting();
		foreach ( $this->tabs as $tab_key => $tab_name ) {
			print_r( $this->ced_etsy_show_setting_tabs( $tab_name, $tab_key ) );
		}
					print_r( '<div class="wc-actions"><div class="left ced-button-wrapper" >' . $form->button( 'glb_stg_btn', 'components-button is-primary button-primary button-next', 'submit', 'global_settings', 'Save Settings' ) . '</div></div>' );
					print_r( $form->form_close() );
		echo '</div>
			</div>
		</div>';
	}
	/**
	 * Show settings tabs using array.
	 *
	 * @since    2.1.3
	 */
	private function ced_etsy_show_setting_tabs( $tab_name = '', $tab_key = '' ) {
		?>
			<div class="ced-etsy-integ-wrapper">
				<input class="ced-faq-trigger" id="<?php echo esc_attr( $tab_name['name'] ); ?>" type="checkbox" /><label class="ced-etsy-settng-title" for="<?php echo esc_attr( $tab_name['name'] ); ?>"><?php echo esc_html( $tab_name['name'] ); ?></label>
				<div class="ced-etsy-settng-content-wrap">
					<div class="ced-etsy-settng-content-holder">
						<div class="ced-form-accordian-wrap">
							<div class="wc-progress-form-content woocommerce-importer">
							   <header>
								<div class="ced_etsy_child_element">
									<?php
										wp_nonce_field( 'global_settings', 'global_settings_submit' );
										$fields = $this->ced_etsy_all_settings_fields();
										$fields = isset( $fields[ $tab_key ] ) ? $fields[ $tab_key ] : array();
										print_r( $this->ced_etsy_render_table( $fields ) );
									?>
								</header>
							</div>
						</div>
					</div>
				</div>	
			</div>
		<?php
	}
	/**
	 * Reder Table into forms.
	 *
	 * @since    2.0.8
	 */
	private function ced_etsy_render_table( $table_array = array() ) {
		$stored_value = isset( $this->pre_saved_values[ $this->shop_name ] ) ? $this->pre_saved_values[ $this->shop_name ] : $this->pre_saved_values;
		$table        = new \Cedcommerce\Template\View\Render\Ced_Render_Table();
		// Output the table opening tag
		print_r( $table->table_open( 'form-table ced_etsy_settings_table' ) );
		$table_array = isset( $table_array ) ? $table_array : array();
		$prep_tr     = '';
		$table_tds   = '';

		// Output the table row for headers
		echo '<tr class="ced-etsy-setting-top" valign="top">
		        <th colspan="" scope="row" class="titledesc rquired"></th>
		        <th colspan="" scope="row" class="titledesc"></th>
		        <th></th>
		    </tr>';

		foreach ( $table_array as $table_values ) {
			$is_value   = isset( $stored_value[ $table_values['name'] ] ) ? $stored_value[ $table_values['name'] ] : '';
			$table_ids  = '';
			$is_checked = '';
			$table_tds .= '<tr valign="top" class="form-field">';
			if ( 'on' === $is_value ) {
				$is_checked = 'checked';
			}

			// Update HTML output with proper indentation
			$table_ids .= $table->label( '', $table_values['label'], $table_values['tooltip'], 'woocommerce_currency' );
			$table_tds .= $table->th( $table_ids );

			if ( 'select' === $table_values['type'] ) {
				$table_tds .= $table->td( $table->select( 'ced_etsy_global_settings[' . $table_values['name'] . ']', $table_values['options'], $is_value, '', 'bulk-action-selector-top', '' ) );
			}
			if ( 'check' === $table_values['type'] ) {
				$table_tds .= $table->td( $table->check_box( 'ced_etsy_global_settings[' . $table_values['name'] . ']', $is_checked, '', $table_values['name'], $table_values['name'], 'ced-checked-button' ) );
			}
			$table_tds .= $table->td();
			$table_tds .= '</tr>';
		}
		// Output the table body and closing tag
		print_r( $table->table_body( $table_tds ) );
		print_r( $table->table_close() );
	}

	/**
	 * All the Required settings tabs ans sub-tabs.
	 *
	 * @since    2.0.8
	 */
	public function ced_etsy_all_settings_fields() {
		return array(
			'scheduler_setting_view' => array(
				array(
					'label'   => __( 'Update inventory to Etsy', 'product-lister-etsy' ),
					'tooltip' => __( 'Auto update price and stock from WooCommerce to Etsy.', 'product-lister-etsy' ),
					'type'    => 'check',
					'name'    => 'ced_etsy_auto_update_inventory',
					'options' => '',
				),

			),
		);
	}

	/**
	 * Product export setting view.
	 *
	 * @since    2.0.8
	 */
	public function product_export_setting() {
		?>
		<div class="ced-etsy-integ-wrapper">
			<input class="ced-faq-trigger" id="ced-etsy-pro-exprt-wrapper" type="checkbox" checked /><label class="ced-etsy-settng-title" for="ced-etsy-pro-exprt-wrapper"  checked><?php esc_html_e( 'Product Export Settings', 'product-lister-etsy' ); ?></label>
			<div class="ced-etsy-settng-content-wrap">
				<div class="ced-etsy-settng-content-holder">
					<div class="ced-form-accordian-wrap">
						<div class="wc-progress-form-content woocommerce-importer">
							<header>
								<table class="form-table ced-settings widefat">
									<tbody>
									<?php wp_nonce_field( 'global_settings', 'global_settings_submit' ); ?>
									<?php
									/**
									 * -------------------------------------
									 *  INCLUDING PRODUCT FIELDS ARRAY FILE
									 * -------------------------------------
									 */

									$this->shop_name        = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
									$product_field_instance = \Cedcommerce\Template\Ced_Template_Product_Fields::get_instance();
									$settings               = $product_field_instance->get_custom_products_fields();
									$product_fields         = isset( $settings['required'] ) ? $settings['required'] : array();
									$ced_etsy_global_data   = get_option( 'ced_etsy_global_settings', array() );
									$setup_wiz_gnrl_stngs   = get_option( 'ced_etsy_setup_wiz_req_attrs_' . $this->shop_name, array() );
									$setup_wiz_req_attr     = isset( $setup_wiz_gnrl_stngs['ced_etsy_setup_wiz_req_attr'] ) ? $setup_wiz_gnrl_stngs['ced_etsy_setup_wiz_req_attr'] : array();
									$saved_pro_datas        = isset( $ced_etsy_global_data[ $this->shop_name ]['product_data'] ) ? $ced_etsy_global_data[ $this->shop_name ]['product_data'] : array();

									if ( ! empty( $product_fields ) ) {
										echo '<input type="hidden" value="' . esc_url( admin_url( 'admin.php?page=sales_channel&channel=etsy&section=add-shipping-profile&shop_name=' . $this->shop_name ) ) . '" id="ced_create_new_shipping_profile" >';
										echo "<table class='form-table ced-settings widefat' style='' id='required' class='ced_etsy_setting_body'>";
										// echo '<tbody>';
										?>
										<tr valign="top">
											<th colspan="" scope="row" class="titledesc rquired"><label for="woocommerce_currency"><?php esc_html_e( 'Required Attributes', 'product-lister-etsy' ); ?></label></th>
											<th colspan="" scope="row" class="titledesc" ><label for="woocommerce_currency"><?php esc_html_e( 'Default Value', 'product-lister-etsy' ); ?></label></th>
											<th></th>
										</tr>
										<?php
										foreach ( $product_fields as $field_data ) {

											if ( '_umb_etsy_category' == $field_data['id'] ) {
												continue;
											}
											$field_id = isset( $field_data['id'] ) ? $field_data['id'] : '';
											echo '<tr class="form-field _umb_id_type_field" valign="top">';
											$label        = isset( $field_data['fields']['label'] ) ? $field_data['fields']['label'] : '';
											$field_id     = trim( $field_id, '_' );
											$category_id  = '';
											$product_id   = '';
											$market_place = 'ced_etsy_required_common';
											$description  = isset( $field_data['fields']['description'] ) ? $field_data['fields']['description'] : '';
											$required     = isset( $field_data['fields']['is_required'] ) ? (bool) $field_data['fields']['is_required'] : '';
											$index_to_use = 0;

											// Setup Wizard Values
											$default = isset( $setup_wiz_req_attr[ $field_data['fields']['id'] ] ) && ! empty( $setup_wiz_req_attr[ $field_data['fields']['id'] ] ) ? $setup_wiz_req_attr[ $field_data['fields']['id'] ] : '';
											if ( empty( $default ) ) {
												$default = isset( $saved_pro_datas[ $field_data['fields']['id'] ]['default'] ) ? $saved_pro_datas[ $field_data['fields']['id'] ]['default'] : $field_data['fields']['default'];
											}

											$field_value = array(
												'case'  => 'profile',
												'value' => trim( $default ),
											);

											$value_for_dropdown = isset( $field_data['fields']['options'] ) ? $field_data['fields']['options'] : array();
											$product_field_instance->renderDropdownHTML( $field_id, $label, $value_for_dropdown, $category_id, $product_id, $market_place, $description, $index_to_use, $field_value, $required, $field_data['id'] );
											echo '</tr>';
										}
										echo '</tbody>';
										echo '</table>';
									}
									?>
								</header>
							</div>
						</div>
				  </div>
			</div>
		</div>
		<?php
	}
}
$global_setting = new Ced_View_Settings();
$global_setting->settings_view();
?>
