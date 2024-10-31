<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( $_GET['shop_name'] ) : '';
?>
<div class="components-card is-size-medium woocommerce-table ">
	<div class="components-panel">
		<div class="wc-progress-form-content woocommerce-importer ced-padding">
			<?php
			$log_types = array(
				'Product'   => 'ced_etsy_product_logs_' . $shop_name,
				'Inventory' => 'ced_etsy_product_inventory_logs_' . $shop_name,
				// 'Order'     => 'ced_etsy_order_logs_' . $shop_name,
			);
			$count = 1;
			foreach ( $log_types as $label => $log_type ) {
				?>
				<div class="ced-faq-wrapper">
					<input class="ced-faq-trigger" id="<?php echo esc_attr( $label ); ?>"
						   type="checkbox" <?php echo esc_attr( 1 == $count ? 'checked' : '' ); ?>/>
					<label class="ced-faq-title" for="<?php echo esc_attr( $label ); ?>">
						<?php echo esc_html( $label ); ?>
					</label>
					<div class="ced-faq-content-wrap">
						<div class="ced-faq-content-holder">
							<div class="ced-form-accordian-wrap">
								<div class="wc-progress-form-content woocommerce-importer">
									<?php
									$log_info = get_option( $log_type, '' );
									if ( empty( $log_info ) ) {
										$log_info = array();
									} else {
										$log_info = json_decode( $log_info, true );
									}

									$total_records = count( $log_info );
									$log_info      = array_slice( $log_info, 0, 50 );
									echo '<table class=" wp-list-table widefat table-view-list posts ' . esc_attr( $log_type ) . ' ced_etsy_logs widefat">';
									$offset = count( $log_info );
									if ( ! empty( $log_info ) ) {
										$was_auto = 'Manual';
										foreach ( $log_info as $key => $info ) {
											$was_auto = isset( $info['is_auto'] ) && ! empty( $info['is_auto'] ) ? 'Automatic' : 'Manual';
											echo '<tr class="ced_etsy_log_rows">';
											echo "<td>
													<span data-post_id='" . esc_attr( $info['post_id'] ) . "' class='log_item_label ced_etsy_timeline_popup'><a class='row-title'>" . esc_attr( $info['post_title'] ) . '</a></span>';
											echo '<!-- // Start of popup rap -->
													<div id="" class="ced-modal ced-etsy-timeline-logs-modal" style="display:none;">
														<div class="ced-modal-text-content ced_etsy_timeline_box_content">
															<h3>Input payload for ' . esc_html( $info['post_title'] ) . '</h3>
															<button id="ced_close_log_message">Close</button>
															<div class="ced-etsy-res-popup-wrapper">
															<pre style="overflow: auto; height: 60vh;">
																' . ( ! empty( $info['input_payload'] ) ? json_encode( $info['input_payload'], JSON_PRETTY_PRINT ) : '' ) . '
															</pre>
															</div>
														</div>
													</div>
												<!-- // End of popup rap -->
											</td>';
											echo "<td><span class=''>" . esc_html( $info['action'] ) . '</span></td>';
											echo "<td><span class=''>" . esc_html( $info['time'] ) . '</span></td>';
											echo "<td><span class=''>" . esc_html( $was_auto ) . '</span></td>';
											// echo "<td><span class=''>" .  . '</span></td>';
											echo '<td>';
											if ( isset( $info['response']['response']['results'] ) || isset( $info['response']['results'] ) || isset( $info['response']['listing_id'] ) || isset( $info['response']['response']['products'] ) || isset( $info['response']['products'] ) || isset( $info['response']['listing_id'] ) ) {
												echo "<span class='etsy_log_success ced_s_f_log_details row-title ced-sucess'>" . esc_html__( 'Success', 'product-lister-etsy' ) . '</span>';
											} else {
												echo "<span class='etsy_log_fail ced_s_f_log_details row-title  ced-failed'>" . esc_html__( 'Failed', 'product-lister-etsy' ) . '</span>';
											}
											echo '<!-- // Start of popup rap -->
													<div id="" class="ced-modal ced-etsy-timeline-logs-sc-fld-modal" style="display:none;">
														<div class="ced-modal-text-content ced_etsy_timeline_box_content">
															<h3> Reponse from Etsy : ' . esc_html( $info['post_title'] ) . '</h3>
															<button id="ced_close_log_message">Close</button>
															<pre style="overflow: auto; height: 60vh;">
															<div class="ced-etsy-res-popup-wrapper">
																' . ( ! empty( $info['response'] ) ? json_encode( $info['response'], JSON_PRETTY_PRINT ) : '' ) . '
															</div>
															</pre>
														</div>
													</div>
												<!-- // End of popup rap -->';
											echo '</td>';
											echo '</tr>';
										}
									} else {
										echo '<tr class="ced_etsy_log_rows"><td>' . esc_html__( 'No info to show.', 'product-lister-etsy' ) . '</td></tr>';
									}
									echo '<tr>';
									if ( $offset < $total_records ) {
										echo '<td colspan="2"></td>';
										echo "<td><span class=''><i><a class='ced_etsy_load_more' data-total='" . esc_attr( $total_records ) . "' data-parent='" . esc_attr( $log_type ) . "' data-offset='" . esc_attr( $offset ) . "'>" . esc_html__( 'load more', 'product-lister-etsy' ) . '</a></i></span></td>';
										echo '</tr>';
									}

									echo '</table>';
									?>
								</div>
							</div>
						</div>
					</div>
				</div>

				<?php
				$count++;
			}
			?>
		<div class="ced-etsy-integ-wrapper">
			<input class="ced-faq-trigger" id="was-cron-exc" type="checkbox"/>
			<label class="ced-faq-title" for="was-cron-exc">
				<?php esc_html_e( 'Was Cron Executed', 'product-lister-etsy' ); ?>
			</label>
			<div class="ced-faq-content-wrap">
				<div class="ced-faq-content-holder">
					<div class="ced-form-accordian-wrap">
						<div class="wc-progress-form-content woocommerce-importer">
							<?php
							$etsy_events = array(
								'Inventory Cron'        => 'ced_etsy_inventory_scheduler_job_' . $shop_name,
								'Existing Product Sync' => 'ced_etsy_sync_existing_products_job_' . $shop_name,
							);
							echo '<table class="wp-list-table widefat">';
							foreach ( $etsy_events as $label => $event ) {
								echo '<tr>';
								echo '<td>' . esc_attr( $label ) . '</td> ';
								$event_info = wp_get_scheduled_event( $event );
								if ( $event_info ) {
									echo '<td><a>' . esc_html__( 'Last executed at', 'product-lister-etsy' ) . ' :</a>' . esc_attr( gmdate( 'F j, Y g:i a', $event_info->timestamp ) ) . '</td>';
									echo '<td><a>' . esc_html__( 'Next execution at', 'product-lister-etsy' ) . ' :</a>' . esc_attr( gmdate( 'F j, Y g:i a', $event_info->timestamp + $event_info->interval ) ) . '</td>';
								} else {
									echo '<td>' . esc_html__( 'Disabled', 'product-lister-etsy' ) . '</td>';
								}
								echo '</tr>';
							}
							echo '</table>';
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
		</div>
	</div>
</div>
</div>



