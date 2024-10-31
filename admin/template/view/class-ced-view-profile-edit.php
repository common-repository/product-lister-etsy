<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
$profileID = isset( $_GET['profileID'] ) ? sanitize_text_field( wp_unslash( $_GET['profileID'] ) ) : '';
global $wpdb;
$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
$tableName = $wpdb->prefix . 'ced_etsy_profiles';

if ( isset( $_POST['ced_etsy_custom_meta_keys_and_attributes'] ) || isset( $_POST['ced_etsy_profile_save_button'] ) ) {
	if ( ! isset( $_POST['profile_settings_submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['profile_settings_submit'] ) ), 'ced_etsy_profile_save_button' ) ) {
		return;
	}
	$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
	$marketplaceName = isset( $sanitized_array['marketplaceName'] ) ? $sanitized_array['marketplaceName'] : 'all';
	foreach ( $sanitized_array['ced_etsy_required_common'] as $key ) {
		$arrayToSave = array();
		isset( $sanitized_array[ $key ][0] ) ? $arrayToSave['default'] = $sanitized_array[ $key ][0] : $arrayToSave['default'] = '';
		if ( '_umb_' . $marketplaceName . '_subcategory' == $key ) {
			isset( $sanitized_array[ $key ] ) ? $arrayToSave['default'] = $sanitized_array[ $key ] : $arrayToSave['default'] = '';
		}
		if ( '_umb_etsy_category' == $key && empty( $profileID ) ) {
			$category_id = $sanitized_array['_umb_etsy_category'][0];
			isset( $sanitized_array[ $key ][0] ) ? $arrayToSave['default'] = $category_id : $arrayToSave['default'] = '';

		}
		isset( $sanitized_array[ $key . '_attibuteMeta' ] ) ? $arrayToSave['metakey'] = $sanitized_array[ $key . '_attibuteMeta' ] : $arrayToSave['metakey'] = 'null';
		$updateinfo[ $key ] = $arrayToSave;
	}

	$updateinfo['selected_product_id']   = isset( $sanitized_array['selected_product_id'] ) ? sanitize_text_field( wp_unslash( $sanitized_array['selected_product_id'] ) ) : '';
	$updateinfo['selected_product_name'] = isset( $sanitized_array['ced_sears_pro_search_box'] ) ? sanitize_text_field( wp_unslash( $sanitized_array['ced_sears_pro_search_box'] ) ) : '';
	$updateinfo                          = json_encode( $updateinfo );
	$profileName                         = isset( $sanitized_array['ced_etsy_profile_name'] ) ? $sanitized_array['ced_etsy_profile_name'] : $sanitized_array['_umb_etsy_category_name'][0];
	if ( empty( $profileID ) && ! empty( $profileName ) ) {
		$wooCategories  = isset( $sanitized_array['woo_categories'] ) ? $sanitized_array['woo_categories'] : array();
		$profileDetails = array(
			'profile_name'   => $profileName,
			'profile_status' => 'active',
			'profile_data'   => $updateinfo,
			'shop_name'      => $shop_name,
			'woo_categories' => json_encode( $wooCategories ),
		);

		global $wpdb;
		$profileTableName = $wpdb->prefix . 'ced_etsy_profiles';
		$wpdb->insert( $profileTableName, $profileDetails );
		$profileId = $wpdb->insert_id;
		foreach ( $wooCategories as $key12 => $value12 ) {
			update_term_meta( $value12, 'ced_etsy_profile_created_' . $shop_name, 'yes' );
			update_term_meta( $value12, 'ced_etsy_profile_id_' . $shop_name, $profileId );
			update_term_meta( $value12, 'ced_etsy_mapped_category_' . $shop_name, $profileName );
		}

		$profile_edit_url = admin_url( 'admin.php?page=sales_channel&channel=etsy&profileID=' . $profileId . '&section=templates&details=edit&shop_name=' . $shop_name );
		header( 'location:' . $profile_edit_url . '' );
	} elseif ( $profileID ) {
		$wpdb->update(
			$tableName,
			array(
				'profile_name'   => $profileName,
				'profile_status' => 'Active',
				'profile_data'   => $updateinfo,
			),
			array( 'id' => $profileID )
		);
	}
	$sanitized_array['ced_etsy_settings_category']['required'] = 'on';
	update_option( 'ced_etsy_settings_category', $sanitized_array['ced_etsy_settings_category'] );
}

$etsyFirstLevelCategories = @file_get_contents( CED_ETSY_DIRPATH . 'admin/lib/json/categoryLevel-1.json' );
$etsyFirstLevelCategories = json_decode( $etsyFirstLevelCategories, true );

$profile_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_etsy_profiles WHERE `id`=%s ", $profileID ), 'ARRAY_A' );

if ( ! empty( $profile_data ) ) {
	$profile_category_data = json_decode( $profile_data[0]['profile_data'], true );
	$profile_category_data = isset( $profile_category_data ) ? $profile_category_data : '';
	$profile_category_id   = isset( $profile_category_data['_umb_etsy_category']['default'] ) ? (int) $profile_category_data['_umb_etsy_category']['default'] : '';
	$profile_data          = isset( $profile_data[0] ) ? $profile_data[0] : $profile_data;
}

$attr_meta_keys = ced_ety_get_custom_meta_and_attributes_keys();
/* select dropdown setup */
ob_start();
$fieldID  = '{{*fieldID}}';
$selectId = $fieldID . '_attibuteMeta';
echo '<select id="' . esc_attr( $selectId ) . '" name="' . esc_attr( $selectId ) . '" class="custom_category_attributes_select2">';
echo '<option value="null"> -- select -- </option>';
if ( is_array( $attr_meta_keys ) ) {
	foreach ( $attr_meta_keys as $attrKey => $attrName ) :
		echo '<option value="' . esc_attr( $attrKey ) . '">' . esc_attr( $attrName ) . '</option>';
	endforeach;
}
echo '</select>';
$selectDropdownHTML     = ob_get_clean();
$product_instance_field = \Cedcommerce\Template\Ced_Template_Product_Fields::get_instance();
$settings               = $product_instance_field->get_custom_products_fields();
?>
<form action="" method="post">
	<?php wp_nonce_field( 'ced_etsy_profile_save_button', 'profile_settings_submit' ); ?>
	<div class="ced_etsy_heading">
		<?php
		if ( isset( $_GET['profileID'] ) ) {
			echo esc_html_e( get_etsy_instuctions_html( 'BASIC INFORMATION', 'product-lister-etsy' ) );
			?>
			<div class="ced_etsy_child_element default_modal">
				<table class="form-table ced-settings widefat">
					<tr>
						<td>
							<label><?php esc_html_e( 'Profile Name', 'product-lister-etsy' ); ?></label>
							<?php

							if ( isset( $profile_data['profile_name'] ) ) {
								?>
								<p><input type="text" name="ced_etsy_profile_name" value="<?php echo esc_attr( $profile_data['profile_name'] ); ?>"></p>
							</td>
								<?php
							}
							?>
						<td>
							<label><?php esc_html_e( 'Profile ID', 'product-lister-etsy' ); ?></label>
							<?php
							if ( isset( $profile_data['profile_name'] ) ) {
								?>
								<p><span><?php echo esc_attr( $profile_data['id'] ); ?></span> </p>
								<?php
							}
							?>
						</td>
						<td>
							<label><?php esc_html_e( 'Mapped WooCommerce Categories', 'product-lister-etsy' ); ?></label>
							<input type="hidden" name="prev_woo_categories"
								   value="<?php echo esc_attr( $profile_data['woo_categories'] ); ?>">
							<?php
							if ( isset( $profile_data['woo_categories'] ) ) {
								$woo_categories = json_decode( $profile_data['woo_categories'], true );
								foreach ( $woo_categories as $term_id ) {
									echo '<p>' . esc_attr( get_term( $term_id )->name ) . '</p>';
								}
								?>

							</td>
						</tr>
								<?php
							}
							?>
				</td>
			</tr>

		</table>
	</div>
			<?php
		} else {
			?>
		<p><a href="
			<?php
			echo esc_url(
				ced_get_navigation_url(
					'etsy',
					array(
						'shop_name' => get_etsy_shop_name(),
						'section'   => 'templates',
					)
				)
			);
			?>
					"><span class="dashicons dashicons-arrow-left-alt2"></span></a> <?php esc_html_e( 'Create New Template', 'product-lister-etsy' ); ?></b>
		</p>
		<div>
			<?php
			include_once CED_ETSY_DIRPATH . 'admin/template/view/class-ced-get-template-categories.php';
			$template_categories = new Ced_Etsy_Get_Categories();
			$template_categories->ced_etsy_get_categories();
			?>
		</div>
			<?php
		}
		?>
	</div>
	<div class="ced_etsy_heading <?php echo esc_attr( ! isset( $_GET['profileID'] ) ? 'etsy_template_edit_wrapper' : '' ); ?>">
		<div class="components-card is-size-medium woocommerce-table ">
			<div class="components-panel">
				<div class="wc-progress-form-content woocommerce-importer ced-padding">
					<?php echo esc_html_e( get_etsy_instuctions_html( 'Attributes', 'product-lister-etsy' ) ); ?>
					<p><?php esc_html_e( 'Providing attribute details can enhance and optimize your Etsy listings. Categories group attributes, and filling in the necessary attributes is essential. You can also add optional or suggested attributes as you like.', 'product-lister-etsy' ); ?></p>
					<div class="ced_etsy_child_element default_modal">

						<?php
						echo '<input type="hidden" value="' . esc_url( admin_url( 'admin.php?page=sales_channel&channel=etsy&section=add-shipping-profile&shop_name=' . $shop_name ) ) . '" id="ced_create_new_shipping_profile" >';
						$requiredInAnyCase                      = array( '_umb_id_type', '_umb_id_val', '_umb_brand' );
						$global_settings_field_data             = get_option( 'ced_etsy_global_settings', '' );
						$marketPlace                            = 'ced_etsy_required_common';
						$productID                              = 0;
						$categoryID                             = '';
						$indexToUse                             = 0;
						$ced_etsy_settings_category             = get_option( 'ced_etsy_settings_category', array() );
						$ced_etsy_settings_category['required'] = 'on';
						$count                                  = 1;
						if ( ! empty( $profile_data ) ) {
							$data = json_decode( $profile_data['profile_data'], true );
						}
						foreach ( $settings as $section => $fields ) {
							?>
							<div class="ced-etsy-integ-wrapper">
								<input class="ced-faq-trigger" id="ced-etsy-pro-exprt-wrapper_<?php echo esc_attr( $section ); ?>"
									   type="checkbox" <?php echo ( 1 == $count ? 'checked' : '' ); ?>/>
								<label class="ced-etsy-settng-title"
									   for="ced-etsy-pro-exprt-wrapper_<?php echo esc_attr( $section ); ?>"><?php echo esc_attr( ucwords( $section ) ); ?> <?php esc_html_e( 'Attributes', 'product-lister-etsy' ); ?></label>
								<div class="ced-etsy-settng-content-wrap">
									<div class="ced-etsy-settng-content-holder">
										<div class="ced-form-accordian-wrap">
											<div class="wc-progress-form-content woocommerce-importer">
												<header>

													<?php
													$style = '';

													echo "<table class='widefat form-table ced-settings' style='" . esc_attr( $style ) . "' id='" . esc_attr( $section ) . "'>";
													echo '<tbody>';
													?>
													<tr>
														<th class="titledesc <?php echo esc_attr( $section ); ?>"><b><?php echo esc_attr( ucwords( $section ) ); ?> <?php esc_html_e( 'Attributes', 'product-lister-etsy' ); ?></b></th>
														<th><label><?php esc_html_e( 'Default Value', 'product-lister-etsy' ); ?></label></th>
														<?php
														if ( 'required' == $section ) {
															echo '<th></th>';
														} else {
															echo '<th><lable>' . esc_html__( 'Pick Value From Custom field or Attribute', 'product-lister-etsy' ) . '</lable></th>';
														}
														?>

													</tr>
													<?php
													foreach ( $fields as $value ) {
														if ( '_ced_etsy_product_list_type' == $value['id'] ) {
															continue;
														}

														$isText   = false;
														$field_id = trim( $value['fields']['id'], '_' );
														if ( in_array( $value['fields']['id'], $requiredInAnyCase ) ) {
															$attributeNameToRender  = ucfirst( $value['fields']['label'] );
															$attributeNameToRender .= '<span class="ced_etsy_wal_required"> [ ' . esc_html__( 'Required', 'product-lister-etsy' ) . ' ]</span>';
														} else {
															$attributeNameToRender = ucfirst( $value['fields']['label'] );
														}
														$is_required = isset( $value['fields']['is_required'] ) ? $value['fields']['is_required'] : false;
														$default     = isset( $data[ $value['fields']['id'] ]['default'] ) ? $data[ $value['fields']['id'] ]['default'] : '';
														if ( '_hidden' !== $value['type'] ) {
															echo '<tr class="form-field _umb_id_type_field ">';
														}
														if ( '_select' == $value['type'] ) {
															$valueForDropdown = $value['fields']['options'];
															if ( '_umb_id_type' == $value['fields']['id'] ) {
																unset( $valueForDropdown['null'] );
															}
															$product_instance_field->renderDropdownHTML(
																$field_id,
																$attributeNameToRender,
																$valueForDropdown,
																$categoryID,
																$productID,
																$marketPlace,
																$value['fields']['description'],
																$indexToUse,
																array(
																	'case' => 'profile',
																	'value' => $default,
																),
																$is_required,
																$value['fields']['id']
															);


														} elseif ( '_text_input' == $value['type'] ) {
															$isText = true;
															$product_instance_field->renderInputTextHTML(
																$field_id,
																$attributeNameToRender,
																$categoryID,
																$productID,
																$marketPlace,
																$value['fields']['description'],
																$indexToUse,
																array(
																	'case' => 'profile',
																	'value' => $default,
																),
																$is_required
															);
														} elseif ( '_checkbox' == $value['type'] ) {
															$product_instance_field->rendercheckboxHTML(
																$field_id,
																$attributeNameToRender,
																$categoryID,
																$productID,
																$marketPlace,
																$value['fields']['description'],
																$indexToUse,
																array(
																	'case' => 'profile',
																	'value' => $default,
																),
																$is_required
															);
															$isText = true;
														} elseif ( '_hidden' == $value['type'] ) {
															$hidden_value = isset( $data[ $value['id'] ]['default'] ) ? $data[ $value['id'] ]['default'] : '';

															$profile_category_id = isset( $profile_category_id ) ? $profile_category_id : '';
															$product_instance_field->renderInputTextHTMLhidden(
																$field_id,
																$attributeNameToRender,
																$categoryID,
																$productID,
																$marketPlace,
																$value['fields']['description'],
																$indexToUse,
																array(
																	'case' => 'profile',
																	'value' => $hidden_value,
																),
																$is_required
															);
															$isText = false;
														}

														if ( $isText ) {
															echo '<td>';
															$previousSelectedValue = 'null';
															if ( isset( $data[ $value['fields']['id'] ]['metakey'] ) && ! empty( $data[ $value['fields']['id'] ]['metakey'] ) ) {
																$previousSelectedValue = $data[ $value['fields']['id'] ]['metakey'];
															}
															$updatedDropdownHTML = str_replace( '{{*fieldID}}', $value['fields']['id'], $selectDropdownHTML );
															$updatedDropdownHTML = str_replace( 'value="' . esc_attr( $previousSelectedValue ) . '"', 'value="' . esc_attr( $previousSelectedValue ) . '" selected="selected"', $updatedDropdownHTML );
															print_r( $updatedDropdownHTML );
															echo '</td>';
														}
														if ( '_hidden' !== $value['type'] ) {
															echo '</tr>';
														}
													}
													echo '</tbody>';
													echo '</table>';
													?>
												</header>
											</div>
										</div>
									</div>
								</div>
							</div>
							<?php
							$count++;
						}
						?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="ced-button-wrapper <?php echo esc_attr( ! isset( $_GET['profileID'] ) ? 'etsy_template_edit_save_button' : '' ); ?>">
		<?php $temp_button = $profileID ? 'Update Template' : 'Create Template'; ?>
		<button name="ced_etsy_profile_save_button" class="button-primary"><?php esc_html_e( $temp_button, 'product-lister-etsy' ); ?></button>
	</div>
</form>
