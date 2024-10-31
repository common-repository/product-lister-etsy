<?php
/**
 * Class Ced_Template_Etsy_Setup_Wizard file.
 *
 * @package CedEtsy\Core
 */
namespace Cedcommerce\Template;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ced Etsy Setup Wizard - This will handle to showcase setup wizard on admin area to connect with Etsy shop in one go.
 *
 * @package     WooCommerce\Admin\Importers
 * @version     3.1.0
 */
class Ced_Template_Etsy_Setup_Wizard {

	/**
	 * The current Etsy step.
	 *
	 * @var string
	 */
	protected $step = '';

	/**
	 * Progress steps.
	 *
	 * @var array
	 */
	protected $steps = array();

	/**
	 * Errors.
	 *
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->current_setup = get_option( 'ced_etsy_setup_steps', array() );

		$default_steps = array(
			'ced_etsy_req_attr'     => array(
				'name'      => __( 'Global Options', 'product-lister-etsy' ),
				'view'      => array( $this, 'ced_etsy_required_attr' ),
				'handler'   => array( $this, 'ced_etsy_required_attr_manage' ),
				'shop_name' => isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : get_option( 'ced_etsy_shop_name', '' ),
			),
			'ced_etsy_glbl_setting' => array(
				'name'      => __( 'General Settings', 'product-lister-etsy' ),
				'view'      => array( $this, 'ced_etsy_glbl_setttings' ),
				'handler'   => array( $this, 'ced_etsy_glbl_setttings_manage' ),
				'shop_name' => isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : get_option( 'ced_etsy_shop_name', '' ),
			),
			'ced_etsy_completed'    => array(
				'name'      => __( 'Done', 'product-lister-etsy' ),
				'view'      => array( $this, 'ced_etsy_process_completed' ),
				'handler'   => array( $this, 'ced_etsy_process_completed_handle' ),
				'shop_name' => isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : get_option( 'ced_etsy_shop_name', '' ),
			),
		);
		/**
		 * Filter to add more section in setup wizard.
		 *
		 * @param array $default_steps The default section in setupwizard.
		 * @since version 1.0.0
		 */
		$this->steps = apply_filters( 'woocommerce_etsy_integration_setup_wizard', $default_steps );
		$this->step  = isset( $_REQUEST['step'] ) ? sanitize_key( $_REQUEST['step'] ) : current( array_keys( $this->steps ) );
	}

	public function ced_etsy_process_completed() {

		$form = new \Cedcommerce\Template\View\Render\Ced_Render_Form();
		$this->current_setup[ get_etsy_shop_name() ]['current_step'] = false;
		print_r( $form->form_open( 'POST', '' ) );
		echo '<div class="woocommerce-progress-form-wrapper">
		<div class="wc-progress-form-content woocommerce-importer">
		<header style="text-align: center;">
		<img style="width: 15%;" src="' . esc_url( CED_ETSY_URL . 'admin/assets/images/success.jpg' ) . '" alt="">
		<p><strong>' . esc_html__( 'Great job! Your onboarding process is complete.', 'product-lister-etsy' ) . '</strong></p>
		</header>
		<div class="wc-actions">
		<a href="' . esc_url( admin_url( 'admin.php?page=sales_channel&channel=etsy' ) ) . '">
		<input style="float: right;" type="button" name="ced_e_onboard_done" class="components-button is-primary" value="' . esc_attr__( 'Go to Overview', 'product-lister-etsy' ) . '">
		</a>
		</div>
		</div>
		</div>';
		print_r( $form->form_close() );
	}


	/**
	 * Completed Step.
	 */
	public function ced_etsy_process_completed_handle() {
		return true;
	}

	/**
	 * Ced Etsy current step and show correct view.
	 */
	public function ced_etsy_show_setup_wizard() {

		if ( isset( $_POST['connect_etsy_btn'] ) && ! empty( $_POST['connect_etsy_btn'] ) && ! empty( $this->steps[ $this->step ]['handler'] ) ) {

			if ( ! isset( $_POST['woocommerce-etsy-setup-wizard-submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-etsy-setup-wizard-submit'] ) ), 'woocommerce-etsy-setup-wizard' ) ) {
				// print_r($_POST);
				return;
			}

			call_user_func( $this->steps[ $this->step ]['handler'], $this );
		}

		$this->ced_etsy_output_header();
		echo '<h2 style="text-align: left;" >Etsy Integration: Onboarding </h2>';
		$this->ced_etsy_output_steps();
		$this->ced_etsy_output_errors();
		call_user_func( $this->steps[ $this->step ]['view'], $this );
		$this->ced_etsy_output_footer();
		update_option( 'ced_etsy_setup_steps', $this->current_setup );
	}

	/**
	 * Manage required attributes while setting up Etsy account
	 *
	 * @since 1.0.0
	 */
	public function ced_etsy_required_attr() {
		$this->current_setup[ get_etsy_shop_name() ]['current_step'] = isset( $_SERVER['REQUEST_URI'] ) && ! empty( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : admin_url( 'admin.php?page=sales_channel&channel=etsy&section=connected&shop_name=' . $shop_name );
		$shop_name       = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : admin_url( 'admin.php?page=sales_channel&channel=etsy&section=connected&shop_name=' . $shop_name );
		$this->shop_name = ! empty( $shop_name ) ? $shop_name : get_option( 'ced_etsy_shop_name', '' );
		$attr_options    = ced_ety_get_custom_meta_and_attributes_keys();

		echo '<div class="woocommerce-progress-form-wrapper woocommerce-importer">';
		echo '<header>
		<h2>' . esc_html__( 'Global Options', 'product-lister-etsy' ) . '</h2>
		<p>' . esc_html__( 'Enhance your Etsy listing with the following attributes. Efficient and time-saving, these can be reused later on in product templates.', 'product-lister-etsy' ) . '</p>
		</header><header>';

		$form = new \Cedcommerce\Template\View\Render\Ced_Render_Form();
		print_r( $form->form_open( 'POST', '' ) );
		wp_nonce_field( 'woocommerce-etsy-setup-wizard', 'woocommerce-etsy-setup-wizard-submit' );

		$saved_req_values       = get_option( 'ced_etsy_setup_wiz_req_attrs_' . $this->shop_name, array() );
		$product_field_instance = \Cedcommerce\Template\Ced_Template_Product_Fields::get_instance();
		$settings_fields        = $product_field_instance->get_custom_products_fields();
		$settings_fields        = isset( $settings_fields['required'] ) ? $settings_fields['required'] : array();
		$table                  = new \Cedcommerce\Template\View\Render\Ced_Render_Table();
		$setup_wiz_req_attr     = isset( $saved_req_values['ced_etsy_setup_wiz_req_attr'] ) ? $saved_req_values['ced_etsy_setup_wiz_req_attr'] : array();
		$setup_wiz_req_cstm     = isset( $saved_req_values['ced_etsy_setup_wiz_req_cstm'] ) ? $saved_req_values['ced_etsy_setup_wiz_req_cstm'] : array();
		print_r( $table->table_open( 'form-table' ) );
		$req_html  = '';
		$req_html .= '<input type="hidden" value="' . esc_attr( $shop_name ) . '" name="e_shop_name">
		<tbody>
		<tr valign="top">
		<th scope="row" class="titledesc">
		<label for="woocommerce_currency">
		' . esc_html__( 'Setting Name', 'product-lister-etsy' ) . '
		</label>
		</th>
		<th scope="row" class="titledesc">
		<label for="woocommerce_currency">
		' . esc_html__( 'Map to fields', 'product-lister-etsy' ) . '
		</label>
		</th>
		<th scope="row" class="titledesc">
		<label for="woocommerce_currency">
		' . esc_html__( 'Default Value', 'product-lister-etsy' ) . '
		</label>
		</th>
		</tr>';

		foreach ( $settings_fields as $req_fields ) {
			if ( '_hidden' === $req_fields['type'] ) {
				continue;
			}
			$req_html .= '<tr>
			<th scope="row" class="titledesc">
			<label for="woocommerce_currency">
			' . esc_html( $req_fields['fields']['label'] ) . wc_help_tip( $req_fields['fields']['description'] ) . '
			</label>
			</th>';

			$req_html .= '<td class="forminp forminp-select">
			<select style="width: 100%;" name="ced_etsy_setup_wiz_req_cstm[' . esc_attr( $req_fields['id'] ) . ']" id="bulk-action-selector-top" class="custom_category_attributes_select2">
			<option value=""> -- ' . esc_html__( 'Map with existing', 'product-lister-etsy' ) . ' -- </option>';

			$previous_selected_value = isset( $setup_wiz_req_cstm[ $req_fields['id'] ] ) ? $setup_wiz_req_cstm[ $req_fields['id'] ] : '';

			if ( is_array( $attr_options ) ) {
				foreach ( $attr_options as $attr_key => $attr_name ) {
					$selected_attr_mt = '';
					if ( trim( $previous_selected_value ) === $attr_name ) {
						$selected_attr_mt = 'selected';
					}
					$req_html .= '<option value="' . esc_attr( $attr_name ) . '" class="hide-if-no-js" ' . $selected_attr_mt . '>' . esc_html( $attr_name ) . '</option>';
				}
			}

			$req_html .= '</select>
			</td>';

			if ( '_select' === $req_fields['type'] && isset( $req_fields['fields']['options'] ) ) {
				$req_html .= '<td class="forminp forminp-select">';
				$req_html .= '<select style="width: 100%;" name="ced_etsy_setup_wiz_req_attr[' . esc_attr( $req_fields['id'] ) . ']" id="">
				<option value="">-- ' . esc_html__( 'Select', 'product-lister-etsy' ) . ' -- </option>';

				foreach ( $req_fields['fields']['options'] as $key => $req_opt_val ) {
					$selected = '';
					if ( ! empty( $setup_wiz_req_attr[ $req_fields['id'] ] ) && $key === $setup_wiz_req_attr[ $req_fields['id'] ] ) {
						$selected = 'selected';
					}
					$req_html .= '<option value="' . esc_attr( $key ) . '" class="hide-if-no-js" ' . $selected . '>' . esc_html( $req_opt_val ) . '</option>';
				}

				$req_html .= '</select>
				</td>';
			}

			$req_html .= '</tr>';
		}

		$req_html .= '</tbody>';
		$req_html .= $table->table_close();

		$req_html .= '<div class="wc-actions">
		<a href="' . esc_url( admin_url( 'admin.php?page=sales_channel&channel=etsy&section=setup&shop_name=' . $this->shop_name ) ) . '" class="components-button is-secondary">' . esc_html__( 'Reset all values', 'product-lister-etsy' ) . '</a>
		<button type="submit" style="float: right;" class="button-primary is-primary components-button button-next" value="Connect" name="connect_etsy_btn">' . esc_html__( 'Save & Continue', 'product-lister-etsy' ) . '</button>
		<button style="float: right;" type="button" class="components-button woocommerce-admin-dismiss-notification">
		<a class="components-button is-tertiary" href="' . esc_url_raw( $this->ced_etsy_get_next_step_link() ) . '">' . esc_html__( 'Skip', 'product-lister-etsy' ) . '</a>
		</button>
		</div>';
		print_r( $req_html );
		print_r( $form->form_close() );
		echo '</header></div>';
	}

	/**
	 * *****************************************
	 *  MANAGE REQUIRED ATTRIBUTES FORMS HERE
	 * *****************************************
	 *
	 * @since 1.0.0
	 */
	public function ced_etsy_required_attr_manage() {
		if ( ! isset( $_POST['woocommerce-etsy-setup-wizard-submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-etsy-setup-wizard-submit'] ) ), 'woocommerce-etsy-setup-wizard' ) ) {
			return;
		}
		$sanitized_array = ced_filter_input();
		echo '<pre>';
		print_r( $sanitized_array );
		echo '</pre>';
		$e_shop_name     = isset( $_POST['e_shop_name'] ) ? sanitize_text_field( wp_unslash( $_POST['e_shop_name'] ) ) : '';
		$this->shop_name = ! empty( $e_shop_name ) ? $e_shop_name : get_option( 'ced_etsy_shop_name', '' );
		if ( isset( $sanitized_array['ced_etsy_setup_wiz_req_cstm'] ) && isset( $sanitized_array['ced_etsy_setup_wiz_req_attr'] ) ) {
			update_option( 'ced_etsy_setup_wiz_req_attrs_' . $this->shop_name, $sanitized_array );
		}
		wp_redirect( esc_url_raw( $this->ced_etsy_get_next_step_link() ) );
	}

	/**
	 * ********************************************
	 *  MANAGE REQUIRED GLOBAL SETTING FORMS HERE
	 * ********************************************
	 *
	 * @since 1.0.0
	 */
	public function ced_etsy_glbl_setttings_manage() {
		if ( ! isset( $_POST['woocommerce-etsy-setup-wizard-submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-etsy-setup-wizard-submit'] ) ), 'woocommerce-etsy-setup-wizard' ) ) {
			return;
		}
		$sanitized_array = ced_filter_input();
		$e_shop_name     = isset( $sanitized_array['e_shop_name'] ) ? sanitize_text_field( wp_unslash( $sanitized_array['e_shop_name'] ) ) : '';
		$this->shop_name = ! empty( $e_shop_name ) ? $e_shop_name : get_option( 'ced_etsy_shop_name', '' );
		if ( isset( $sanitized_array['ced_etsy_scheduler_wiz'] ) && isset( $sanitized_array['ced_etsy_scheduler_wiz'] ) ) {

			$sync_inventory = isset( $sanitized_array['ced_etsy_scheduler_wiz']['ced_etsy_auto_update_inventory'] ) ? $sanitized_array['ced_etsy_scheduler_wiz']['ced_etsy_auto_update_inventory'] : 'no';

			if ( ! wp_get_schedule( 'ced_etsy_inventory_scheduler_job_' . $this->shop_name ) && 'yes' == $sync_inventory ) {
				wp_schedule_event( time(), 'ced_etsy_10min', 'ced_etsy_inventory_scheduler_job_' . $this->shop_name );
			} else {
				wp_clear_scheduled_hook( 'ced_etsy_inventory_scheduler_job_' . $this->shop_name );
			}

			$glbl_settings = get_option( 'ced_etsy_global_settings', array() );
			$glbl_settings[ $this->shop_name ]['ced_etsy_auto_update_inventory'] = $sync_inventory;
			update_option( 'ced_etsy_global_settings', $glbl_settings );
			wp_redirect( esc_url_raw( $this->ced_etsy_get_next_step_link() ) );
		}
	}

	/**
	 * ********************************************************************
	 *  MANAGE GLOBAL SETTINGS VIEW TO SHOW GLOBAL SETTINGS'S ATTRIBUTES
	 * ********************************************************************
	 *
	 * @since 1.0.0
	 */
	public function ced_etsy_glbl_setttings() {
		$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : get_option( 'ced_etsy_shop_name', '' );

		$saved_schedler = get_option( 'ced_etsy_global_settings', array() );
		$inventory_scl  = isset( $saved_schedler[ $shop_name ]['ced_etsy_auto_update_inventory'] ) ? $saved_schedler[ $shop_name ]['ced_etsy_auto_update_inventory'] : '';

		$this->current_setup[ get_etsy_shop_name() ]['current_step'] = isset( $_SERVER['REQUEST_URI'] ) && ! empty( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : admin_url( 'admin.php?page=sales_channel&channel=etsy&section=connected&shop_name=' . $shop_name );
		?>
		<form class="wc-progress-form-content woocommerce-importer" enctype="multipart/form-data" method="post">
			<?php wp_nonce_field( 'woocommerce-etsy-setup-wizard', 'woocommerce-etsy-setup-wizard-submit' ); ?>
			<input type="hidden" value="<?php echo esc_attr( $shop_name ); ?>" name="e_shop_name">
			<header>
				<h2><?php echo esc_html_e( 'General Settings', 'product-lister-etsy' ); ?></h2>
				<p><?php esc_html_e( 'Enable automatic initiation of inventory and order cron jobs for real-time updates between WooCommerce and Etsy.', 'product-lister-etsy' ); ?></p>
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row" class="titledesc">
								<label for="woocommerce_currency">
									<?php esc_html_e( 'Scheduler Name', 'product-lister-etsy' ); ?>
								</label>
							</th>
							<th scope="row" class="titledesc">
								<label for="woocommerce_currency">
									<?php esc_html_e( 'Option', 'product-lister-etsy' ); ?>
								</label>
							</th>
						</tr>
						<tr>
							<th scope="row" class="titledesc">
								<label for="woocommerce_currency">
									<?php
									esc_html_e( 'Sync Inventory cron job', 'product-lister-etsy' );
									echo wc_help_tip( esc_html__( 'Update inventory from WooCommerce to Etsy', 'product-lister-etsy' ) );
									?>
								</label>
							</th>
							<td class="forminp forminp-select">
								<select style="width: 100%;" name="ced_etsy_scheduler_wiz[ced_etsy_auto_update_inventory]" id="bulk-action-selector-top">
									<option value=""><?php echo esc_html__( '---Option---', 'product-lister-etsy' ); ?></option>
									<option value="on" class="hide-if-no-js" <?php selected( 'on', $inventory_scl ); ?>><?php esc_html_e( 'Enable', 'product-lister-etsy' ); ?></option>
									<option value="" <?php selected( '', $inventory_scl ); ?>><?php esc_html_e( 'Disable', 'product-lister-etsy' ); ?></option>
								</select>
							</td>
						</tr>
						
					</tbody>
				</table>
			</header>
			<div class="wc-actions">
				<button type="submit" style="float: right;" class="button-primary is-primary components-button button-next" value="<?php esc_attr_e( 'Save & Continue', 'product-lister-etsy' ); ?>" name="connect_etsy_btn"><?php esc_html_e( 'Save & Continue', 'product-lister-etsy' ); ?></button>
				<button style="float: right;" type="button" class="components-button woocommerce-admin-dismiss-notification"><a class="components-button is-tertiary" href="<?php echo esc_url_raw( $this->ced_etsy_get_next_step_link() ); ?>"><?php esc_html_e( 'Skip', 'product-lister-etsy' ); ?></a></button>
			</div>
		</form>
		<?php
	}

	/**
	 * Get the URL for the next step's screen.
	 *
	 * @param string $step  slug (default: current step).
	 * @return string       URL for next step if a next step exists.
	 *                      Admin URL if it's the last step.
	 *                      Empty string on failure.
	 */
	public function ced_etsy_get_next_step_link( $step = '' ) {
		if ( ! $step ) {
			$step = $this->step;
		}

		$keys = array_keys( $this->steps );

		if ( end( $keys ) === $step ) {
			return admin_url();
		}

		$step_index = array_search( $step, $keys, true );

		if ( false === $step_index ) {
			return '';
		}

		$params = array(
			'step'     => $keys[ $step_index + 1 ],
			'_wpnonce' => wp_create_nonce( 'woocommerce-etsy-setup-wizard' ), // wp_nonce_url() escapes & to &amp; breaking redirects.
		);

		return add_query_arg( $params );
	}

	/**
	 * Output header view.
	 */
	protected function ced_etsy_output_header() {
		?>
		<div class="">
			<!-- <h3><?php // esc_html_e( 'Now start connecting your Etsy Shop', 'product-lister-etsy' ); ?></h3> -->
			<div class="woocommerce-progress-form-wrapper">
				<?php
	}

	/**
	 * Output steps view.
	 */
	protected function ced_etsy_output_steps() {

		?>
		<ol class="wc-progress-steps ced-progress">
			<?php foreach ( $this->steps as $step_key => $step ) : ?>
				<?php
				$step_class = '';
				if ( $step_key === $this->step ) {
					$step_class = 'active';
				} elseif ( array_search( $this->step, array_keys( $this->steps ), true ) > array_search( $step_key, array_keys( $this->steps ), true ) ) {
					$step_class = 'done';
				}
				?>
				<li class="<?php echo esc_attr( $step_class ); ?>">
					<?php echo esc_html( $step['name'] ); ?>
				</li>
			<?php endforeach; ?>
		</ol>
		<?php
	}

	/**
	 * Output footer view.
	 */
	protected function ced_etsy_output_footer() {
		echo '	</div>
		</div>';
	}

	/**
	 * Add error message.
	 *
	 * @param string $message Error message.
	 * @param array  $actions List of actions with 'url' and 'label'.
	 */
	protected function ced_etsy_add_error( $message, $actions = array() ) {
		$this->errors[] = array(
			'message' => $message,
			'actions' => $actions,
		);
	}

	/**
	 * Add error message.
	 */
	protected function ced_etsy_output_errors() {
		if ( ! $this->errors ) {
			return;
		}

		foreach ( $this->errors as $error ) {
			echo '<div class="error inline">';
			echo '<p>' . esc_html( $error['message'] ) . '</p>';

			if ( ! empty( $error['actions'] ) ) {
				echo '<p>';
				foreach ( $error['actions'] as $action ) {
					echo '<a class="button button-primary" href="' . esc_url( $action['url'] ) . '">' . esc_html( $action['label'] ) . '</a> ';
				}
				echo '</p>';
			}
			echo '</div>';
		}
	}
}
