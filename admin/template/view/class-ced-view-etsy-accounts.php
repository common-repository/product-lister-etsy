<?php
namespace Cedcommerce\Template\View;

class Ced_View_Etsy_Accounts {
	/**
	 * Is Etsy shop authorised flag
	 *
	 * @var int
	 */
	public $is_authorised;

	/**
	 * Etsy shop name.
	 *
	 * @var string
	 */
	public $shop_name;

	/**
	 * Redirect flag.
	 *
	 * @var string
	 */
	public $to_setup_wizard;

	/**
	 * Account contructure to get authorised.
	 *
	 * @param string $is_authorised flag to manage the save.
	 * @param string $shop_name Active Etsy shop name.
	 *
	 * @since    2.3.2
	 * @return string Woo product type.
	 */
	public function __construct( $is_authorised = '', $shop_name = '' ) {
		$this->shop_name     = ! empty( $shop_name ) ? $shop_name : $this->shop_name;
		$this->is_authorised = ! empty( $is_authorised ) ? $is_authorised : $this->is_authorised;

		 /**
		  ******************************************************************************************
		  *  WHEN CLICK ON CONNECT SHOP REDIRECT TO ETSY FOR AUTHORISTAION TO GET SERLLER CONSENT
		  ******************************************************************************************
		*
		  * @since 1.0.0
		  */
		if ( isset( $_POST['ced_etsy_connect_shop'] ) ) {
			if ( ! isset( $_POST['ced_etsy_connect_shop_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ced_etsy_connect_shop_nonce'] ) ), 'ced_etsy_connect_e_shop' ) ) {
				return;
			}
			if ( isset( $_POST['ced_etsy_shop_name'] ) && null !== $_POST['ced_etsy_shop_name'] && ! empty( $_POST['ced_etsy_shop_name'] ) && '' !== $_POST['ced_etsy_shop_name'] ) {
				$shop_name = isset( $_POST['ced_etsy_shop_name'] ) ? sanitize_text_field( $_POST['ced_etsy_shop_name'] ) : '';
				if ( empty( $shop_name ) || null === $shop_name || '' === $shop_name ) {
					$message = $this->ced_etsy_onboarding_message( 'You are not doing good' );
					wp_redirect( admin_url( 'admin.php?page=sales_channel&channel=etsy&add-new-account=yes&message=blank' ) );
					exit;
				}
				$ced_etsy_accounts = get_option( 'ced_etsy_details', array() );
				if ( isset( $ced_etsy_accounts[ $shop_name ] ) ) {
					wp_redirect( admin_url( 'admin.php?page=sales_channel&channel=etsy&add-new-account=yes&message=same_shop' ) );
					exit;
				}
				update_option( 'ced_etsy_shop_name', $shop_name );
				$auth_url = ced_etsy_get_auth_url( $shop_name );
				wp_redirect( $auth_url );
			}
		}

		 /**
		  *********************************************************************
		  *  GET VERIFIER CODE , STATE TO GET ACCESS TOKEN AND MANAGE IN DB
		  *********************************************************************
		*
		  * @since 1.0.0
		  */
		if ( isset( $_GET['state'] ) && ! empty( $_GET['code'] ) ) {
			delete_option( 'ced_etsy_reauthorize_account' );
			$code       = isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '';
			$verifier   = isset( $_GET['state'] ) ? sanitize_text_field( $_GET['state'] ) : '';
			$action     = 'public/oauth/token';
			$query_args = array(
				'grant_type'    => 'authorization_code',
				'client_id'     => ced_etsy_get_auth(),
				'redirect_uri'  => 'https://woodemo.cedcommerce.com/woocommerce/authorize/etsy/authorize.php',
				'code'          => $code,
				'code_verifier' => $verifier,
			);
			$parameters = $query_args;
			$shop_name  = get_option( 'ced_etsy_shop_name', '' );
			$response   = etsy_request()->post( $action, $parameters, $shop_name, $query_args );
			if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {
				$action     = 'application/shops';
				$query_args = array(
					'shop_name' => $shop_name,
				);
				$shop       = etsy_request()->get( $action, '', $query_args );
				if ( isset( $shop['results'][0] ) ) {
					set_transient( 'ced_etsy_token_' . $shop_name, $response, (int) $response['expires_in'] );
					$user_details               = get_option( 'ced_etsy_details', array() );
					$user_id                    = isset( $shop['results'][0]['user_id'] ) ? $shop['results'][0]['user_id'] : '';
					$user_name                  = isset( $shop['results'][0]['login_name'] ) ? $shop['results'][0]['login_name'] : '';
					$shop_id                    = isset( $shop['results'][0]['shop_id'] ) ? $shop['results'][0]['shop_id'] : '';
					$info                       = array(
						'details' => array(
							'ced_etsy_shop_name'      => $shop_name,
							'user_id'                 => $user_id,
							'user_name'               => $user_name,
							'shop_id'                 => $shop_id,
							'ced_etsy_keystring'      => ced_etsy_get_auth(),
							'ced_etsy_shared_string'  => ced_etsy_get_auth(),
							'ced_shop_account_status' => 'Active',
							'token'                   => $response,
							'shop_info'               => $shop['results'][0],
						),
					);
					$user_details[ $shop_name ] = $info;
					if ( count( $user_details ) < 5 ) {
						update_option( 'ced_etsy_details', $user_details );
					}
				}
			}
			wp_redirect( admin_url( 'admin.php?page=sales_channel&channel=etsy&section=connected&shop_name=' . $shop_name ) );
		}

		 /**
		  ********************************************************
		  *  USER VEFIRY AND CONTINUE TO SYNC EXISTING PRODUCTS
		  ********************************************************
		*
		  * @since 1.0.0
		  */
		if ( isset( $_POST['ced_etsy_connect_and_verify'] ) ) {
			// print_r($_POST);die;
			if ( ! isset( $_POST['ced_etsy_verify_and_continue_submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ced_etsy_verify_and_continue_submit'] ) ), 'ced_etsy_verify_and_continue' ) ) {
				return;
			}
			$e_shop_name     = isset( $_POST['e_shop_name'] ) ? sanitize_text_field( wp_unslash( $_POST['e_shop_name'] ) ) : '';
			$this->shop_name = ! empty( $e_shop_name ) ? $e_shop_name : get_option( 'ced_etsy_shop_name', '' );
			$all_e_pro       = ! empty( get_option( 'ced_etsy_total_e_shop_pros_' . $this->shop_name, '' ) ) ? get_option( 'ced_etsy_total_e_shop_pros_' . $this->shop_name, '' ) : 0;

			if ( ! $all_e_pro ) {
				$shop_id = get_etsy_shop_id( $this->shop_name );
				/**
				 * Get refresh token.
				 *
				 * @since    1.0.0
				 * @param string $shopname Active Etsy shope name .
				 */
				do_action( 'ced_etsy_refresh_token', $this->shop_name );
				$response  = etsy_request()->get(
					"application/shops/{$shop_id}/listings",
					$this->shop_name,
					array(
						'state' => 'active',
					)
				);
				$all_e_pro = isset( $response['count'] ) ? $response['count'] : 0;
				update_option( 'ced_etsy_total_e_shop_pros_' . $this->shop_name, $all_e_pro );
			}

			if ( $all_e_pro ) {
				wp_redirect( admin_url( 'admin.php?page=sales_channel&channel=etsy&section=sync_existing&count=' . $all_e_pro . '&shop_name=' . $this->shop_name ) );
			} else {
				wp_redirect( admin_url( 'admin.php?page=sales_channel&channel=etsy&section=setup&shop_name=' . $this->shop_name ) );
			}
		}

		 /**
		  ********************************************************
		  *  START SETUP WIZARD WHEN ALL PROCESS DONE WITH ACCOUNT
		  ********************************************************
		*
		  * @since 1.0.0
		  */
		if ( isset( $_POST['start_setup_wiz'] ) ) {
			if ( ! isset( $_POST['ced_etsy_verify_and_continue_submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ced_etsy_verify_and_continue_submit'] ) ), 'ced_etsy_verify_and_continue' ) ) {
				return;
			}
			$e_shop_name              = isset( $_POST['e_shop_name'] ) ? sanitize_text_field( wp_unslash( $_POST['e_shop_name'] ) ) : '';
			$this->shop_name          = ! empty( $e_shop_name ) ? $e_shop_name : get_option( 'ced_etsy_shop_name', '' );
			$sanitized_array          = ced_filter_input();
			$ced_etsy_pro_identifiers = isset( $sanitized_array['ced_etsy_sync_identifier'] ) ? $sanitized_array['ced_etsy_sync_identifier'] : array();
			if ( isset( $ced_etsy_pro_identifiers['ced_sync_exc_etsy_identifier'] ) && isset( $ced_etsy_pro_identifiers['ced_etsy_wc_identifier'] ) ) {
				$glbl_settings = get_option( 'ced_etsy_global_settings', array() );
				$glbl_settings[ $this->shop_name ]['ced_sync_exc_etsy_identifier'] = $ced_etsy_pro_identifiers['ced_sync_exc_etsy_identifier'];
				$glbl_settings[ $this->shop_name ]['ced_etsy_wc_identifier']       = $ced_etsy_pro_identifiers['ced_etsy_wc_identifier'];
				update_option( 'ced_etsy_global_settings', $glbl_settings );
			}
			wp_redirect( admin_url( 'admin.php?page=sales_channel&channel=etsy&section=setup&shop_name=' . $this->shop_name ) );
		}
	}

	/**
	 * *********************************
	 *  SHOW ONBOARDING HTML MESSAGE
	 * *********************************
	 *
	 * @since 1.0.0
	 *
	 * @param string $shop_name Current Etsy shop name of user.
	 */
	public function ced_etsy_connect_e_shop_onboarding_html( $shop_name = '', $message = '' ) {
		return '<div class="woocommerce-progress-form-wrapper">
			<h2 style="text-align: left;">' . esc_html__( 'Etsy Integration: Onboarding', 'product-lister-etsy' ) . '</h2>
			' . $message . '
			<form method="post" action="">
				' . wp_nonce_field( 'ced_etsy_connect_e_shop', 'ced_etsy_connect_shop_nonce', true, false ) . '
				<div class="wc-progress-form-content woocommerce-importer">
					<header>
						<h2>' . esc_html__( 'Connect Etsy', 'product-lister-etsy' ) . '</h2>
						<p>' . esc_html__( 'To connect with Etsy, simply fill in the required details and hit ‘Connect Etsy.’', 'product-lister-etsy' ) . '</p>
					</header>
					<header class="ced-label-wrap">
						<div class="form-field form-required term-name-wrap">
							<label for="tag-name">' . esc_html__( 'Etsy Shop Name', 'product-lister-etsy' ) . '</label>
							<input name="ced_etsy_shop_name" id="ced_etsy_input_shop_name" type="text" value="" size="40" aria-required="true" aria-describedby="name-description" required>
						</div>
					</header>
					<input type="hidden" value="' . esc_attr( $shop_name ) . '" name="e_shop_name">
					<div class="wc-actions">
						<button type="submit" id="ced_etsy_connect_button" name="ced_etsy_connect_shop" class="components-button is-primary button-next" style="float:right;" disabled>' . esc_html__( 'Connect Etsy', 'product-lister-etsy' ) . '</button>
					</div>
				</form>
			</div>
		</div>';
	}

	/**
	 * **************************************
	 *  COMPLETED AUTHORISATION VIEW
	 * **************************************
	 *
	 * @since 1.0.0
	 *
	 * @param string $e_account_details Etsy seller account details.
	 * @param string $message Message of the exiting product with number of count.
	 * @param string $shop_name Current Etsy shop name of user
	 */
	public function ced_etsy_completed_authorisation_view( $e_account_details = '', $message = '', $shop_name = '' ) {
		$e_details         = isset( $e_account_details[ $shop_name ] ) ? $e_account_details[ $shop_name ] : array();
		$e_shop_name       = isset( $e_details['details']['ced_etsy_shop_name'] ) ? $e_details['details']['ced_etsy_shop_name'] : $shop_name;
		$html              = '<div class="woocommerce-progress-form-wrapper">
					<h2 style="text-align: left;">' . esc_html__( 'Etsy Integration: Onboarding', 'product-lister-etsy' ) . '</h2>
					' . $message . '
					<div class="wc-progress-form-content">
						<header>
							<h2>' . esc_html__( 'Connect Etsy', 'product-lister-etsy' ) . '</h2>
						<div id="message" class="updated inline ced-notification-notice">
							<p><strong>' . esc_html__( 'Awesome. Your Etsy account is now connected!', 'product-lister-etsy' ) . '</strong></p>
							<div class="ced-account-detail-wrapper">
								<div class="ced-account-details-holder">';
					$html .= '<p>' . esc_html__( 'Store Name', 'product-lister-etsy' ) . ' : ' . esc_html( $e_shop_name ) . '</p>';
					$html .= '</div>
					    </div>
					    <p class="ced-link">Connected the wrong account? Click to <a href="' . esc_url( admin_url( 'admin.php?page=sales_channel&channel=etsy&section=reconnect&add-new-account=yes&shop_name=' . $this->shop_name ) ) . '"><strong>reconnect</strong></a></p>
					</div>
					<p></p>
					</header>
					<input type="hidden" value="' . esc_attr( $shop_name ) . '" name="e_shop_name">
					<div class="wc-actions">' . wp_nonce_field( 'ced_etsy_verify_and_continue', 'ced_etsy_verify_and_continue_submit' ) . '
					    <button style="float: right;" type="submit" name="ced_etsy_connect_and_verify" class="components-button is-primary button-next">' . esc_html__( 'Verify and continue', 'product-lister-etsy' ) . '</button>
					</div>
					</div>
					</div>';
		return $html;
	}

	/**
	 * **************************************
	 *  PAREPARE MESSAGE ON-ONBOARDING
	 * **************************************
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Message which user gonna see while operations.
	 */
	public function ced_etsy_onboarding_message( $text = '', $class = 'ced-onboarding-notification' ) {
		return '<div class="' . $class . '">
				<p>' . esc_html( $text ) . '</p>
			</div>';
	}
	/**
	 * **************************************
	 *  MANAGE ETSY SETUP WIZARD CLASSS VIEW
	 * **************************************
	 *
	 * @since 1.0.0
	 */
	public function ced_etsy_setup_wizard() {
		$ced_etsy_setup_wizard = new \Cedcommerce\Template\Ced_Template_Etsy_Setup_Wizard();
		return $ced_etsy_setup_wizard->ced_etsy_show_setup_wizard();
	}

	/**
	 * **************************************
	 *  SYNC EXISTING PRODUCT VIEW PAGE
	 * **************************************
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Message of the exiting product with number of count.
	 * @param string $shop_name Current Etsy shop name of user
	 */
	public function ced_etsy_sync_existing_products_html_view( $message = '', $shop_name = '' ) {
		$glbl_settings  = get_option( 'ced_etsy_global_settings', array() );
		$e_identifiers  = isset( $glbl_settings[ $shop_name ]['ced_sync_exc_etsy_identifier'] ) ? $glbl_settings[ $shop_name ]['ced_sync_exc_etsy_identifier'] : '';
		$wc_identifiers = isset( $glbl_settings[ $shop_name ]['ced_etsy_wc_identifier'] ) ? $glbl_settings[ $shop_name ]['ced_etsy_wc_identifier'] : '';
		$html           = '<div class="woocommerce-progress-form-wrapper">
					<h2 style="text-align: left;">' . esc_html__( 'Etsy Integration: Onboarding', 'product-lister-etsy' ) . '</h2>
					' . $message . '
					<div class="wc-progress-form-content woocommerce-importer">
						<header>
						<h2>' . esc_html__( 'Product Mapping', 'product-lister-etsy' ) . '</h2>
								<p>' . esc_html__( 'Select WooCommerce and Etsy product attributes that you want to sync and seamlessly map WooCommerce and Etsy products.', 'product-lister-etsy' ) . '</p>
								</header>
								<header class="ced-label-wrap">						
							<div class="form-field form-required term-name-wrap">
									<label for="tag-name">' . esc_html__( 'Etsy Identification', 'product-lister-etsy' ) . '</label>

									<select style="width: 100%;" name="ced_etsy_sync_identifier[ced_sync_exc_etsy_identifier]">
										<option value="sku" ' . selected( 'sku', $e_identifiers, false ) . ' >' . esc_html__( 'SKU', 'product-lister-etsy' ) . '</option>
										<option value="listing_id" ' . selected( 'listing_id', $e_identifiers, false ) . ' >' . esc_html__( 'Etsy Listing ID', 'product-lister-etsy' ) . '</option>
									</select>

								</div>
								<div class="form-field form-required term-name-wrap">
									<label for="tag-name">' . esc_html__( 'WooCommerce Identification', 'product-lister-etsy' ) . '</label>
									<select style="width: 100%;" name="ced_etsy_sync_identifier[ced_etsy_wc_identifier]">
										<option value="sku" ' . selected( 'sku', $wc_identifiers, false ) . ' >' . esc_html__( 'SKU', 'product-lister-etsy' ) . '</option>
										<option value="product_id" ' . selected( 'product_id', $wc_identifiers, false ) . ' >' . esc_html__( 'WooCommerce Product ID', 'product-lister-etsy' ) . '</option>
									</select>
								</div>
						</header>
				
						<div class="wc-actions">
						' . wp_nonce_field( 'ced_etsy_verify_and_continue', 'ced_etsy_verify_and_continue_submit' ) . '
							<input type="hidden" value="' . esc_attr( $shop_name ) . '" name="e_shop_name">
							<button style="float: right;" type="submit" name="start_setup_wiz" class="components-button is-primary button-next">' . esc_html__( 'Verify and continue', 'product-lister-etsy' ) . '</button>
						</div>
					</div>
				</div>';
		return $html;
	}
}
