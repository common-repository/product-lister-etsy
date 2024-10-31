<?php
$shop_name   = get_etsy_shop_name();
$setup_steps = get_option( 'ced_etsy_setup_steps', array() );
?>
<body>
	<?php
	if ( isset( $setup_steps[ $shop_name ]['current_step'] ) && ! empty( $setup_steps[ $shop_name ]['current_step'] ) ) {
		?>
		<div class="woocommerce-progress-form-wrapper">
			<div class="wc-progress-form-content woocommerce-importer">
				<header>
					<h2><?php esc_html_e( 'Onboarding', 'product-lister-etsy' ); ?></h2>
				</header>
				<div data-wp-c16t="true" data-wp-component="Card" class="components-surface components-card woocommerce-task-card woocommerce-homescreen-card css-1pd4mph e19lxcc00">
					<div class="css-10klw3m e19lxcc00">
						<ul class="woocommerce-experimental-list">
							<li role="button" tabindex="0" class="woocommerce-experimental-list__item has-action transitions-disabled woocommerce-task-list__item index-4 is-active">
								<div class="woocommerce-task-list__item-before"><div class="woocommerce-task__icon"></div></div>
								<div class="woocommerce-task-list__item-text">
									<div data-wp-c16t="true" data-wp-component="Text" class="components-truncate components-text css-10hubey e19lxcc00">
										<span class="woocommerce-task-list__item-title">
											<a href="<?php echo esc_url( $setup_steps[ $shop_name ]['current_step'] ); ?>"><?php esc_html_e( 'Onboarding Pending', 'product-lister-etsy' ); ?></a>
										</span>
									</div>
								</div>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
	?>
	<?php
	$total_products = get_option( 'ced_etsy_total_shop_products_' . $shop_name, 0 );
	$imported_pros  = count(
		get_posts(
			array(
				'post_type'   => 'product',
				'numberposts' => -1,
				'meta_query'  => array(
					array(
						'key'     => '_ced_etsy_auto_imported_' . $shop_name,
						'compare' => 'EXISTS',
					),
				),
				'post_status' => array_keys( get_post_statuses() ),
				'fields'      => 'ids',
			)
		)
	);
	$pecentage      = 0;
	if ( ! empty( $total_products ) ) {
		$pecentage = ( (int) $imported_pros / (int) $total_products ) * 100;
	}


	?>

	<div class="woocommerce-progress-form-wrapper">
		<div class="wc-progress-form-content">
			<header>
				<h2><?php esc_html_e( 'Existing Product Sync', 'woocommerce-etsy-integration' ); ?></h2>
				<p>
					<?php
					esc_html__( 'You can see here automatically product progress out ' . $total_products . ' of ' . $imported_pros . ' products have been imported.', 'woocommerce-etsy-integration' );
					?>
				</p>
				<div class="">
					<?php
					$etsy_events = array(
						'Inventory Cron'        => 'ced_etsy_inventory_scheduler_job_' . $shop_name,
						'Existing Product Sync' => 'ced_etsy_sync_existing_products_job_' . $shop_name,
					);

					foreach ( $etsy_events as $label => $event ) {
						echo '<tr>';
						// echo '<td>' . esc_attr( $label ) . '</td> ';
						$event_info = wp_get_scheduled_event( $event );
						if ( $event_info ) {
							echo '<p>' . esc_html__( 'Last executed at', 'woocommerce-etsy-integration' ) . ' : ' . esc_attr( gmdate( 'F j, Y g:i a', $event_info->timestamp ) ) . '</p>';
							echo '<p>' . esc_html__( 'Next execution at', 'woocommerce-etsy-integration' ) . ' : ' . esc_attr( gmdate( 'F j, Y g:i a', $event_info->timestamp + $event_info->interval ) ) . '</p>';
						}
						// else {
						// echo '<p>' . esc_html__( 'Disabled', 'woocommerce-etsy-integration' ) . '</p>';
						// }
					}

					?>
				</div>
				
			</header>
		</div>
	</div>
	

	<div class="woocommerce-progress-form-wrapper">
		<div class="wc-progress-form-content">
			<header>
				<h2><?php esc_html_e( 'Product Stats', 'product-lister-etsy' ); ?></h2>
				<p><?php esc_html_e( "Track your product listing status on the go. Click on 'View all products' button to see the product details.", 'product-lister-etsy' ); ?></p>
				<div class="woocommerce-dashboard__store-performance">
					<div role="menu" aria-orientation="horizontal" aria-label="<?php esc_attr_e( 'Performance Indicators', 'product-lister-etsy' ); ?>" aria-describedby="woocommerce-summary-helptext-87">
						<ul class="woocommerce-summary has-2-items ced-woocommerce-summary ced_etsy_overview_dash">
							<li class="woocommerce-summary__item-container">
								<a href="
								<?php
								echo esc_url(
									ced_get_navigation_url(
										'etsy',
										array(
											'section'   => 'products',
											'shop_name' => get_etsy_shop_name(),
										)
									)
								);
								?>
								" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
								<div class="woocommerce-summary__item-label">
									<span data-wp-c16t="true" data-wp-component="Text" class="components-truncate components-text css-jfofvs e19lxcc00">
										<?php esc_html_e( 'Total products', 'product-lister-etsy' ); ?>
									</span>
								</div>
								<div class="woocommerce-summary__item-data">
									<div class="woocommerce-summary__item-value">
										<span data-wp-c16t="true" data-wp-component="Text" class="components-truncate components-text css-2x4s0q e19lxcc00">
											<?php echo esc_html( get_etsy_products_count( get_etsy_shop_name(), 'total' ) ); ?>
										</span>
									</div>
								</div>
							</a>
						</li>

						<li class="woocommerce-summary__item-container">
							<a href="
							<?php
							echo esc_url(
								ced_get_navigation_url(
									'etsy',
									array(
										'section'        => 'products',
										'shop_name'      => get_etsy_shop_name(),
										'status_sorting' => 'Uploaded',
									)
								)
							);
							?>
							" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
							<div class="woocommerce-summary__item-label"><span data-wp-c16t="true" data-wp-component="Text" class="components-truncate components-text css-jfofvs e19lxcc00">Listed on Etsy</span></div>
							<div class="woocommerce-summary__item-data">
								<div class="woocommerce-summary__item-value"><span data-wp-c16t="true" data-wp-component="Text" class="components-truncate components-text css-2x4s0q e19lxcc00"><?php echo esc_attr( get_etsy_products_count( get_etsy_shop_name() ) ); ?></span></div>
							</div>
						</a>
					</li>
				</ul>
			</div>
		</div>
	</header>
	<div class="wc-actions">
		<a href="
		<?php
		echo esc_url(
			ced_get_navigation_url(
				'etsy',
				array(
					'section'   => 'products',
					'shop_name' => get_etsy_shop_name(),
				)
			)
		);
		?>
		">
		<button style="float: right;" type="button" class="components-button is-primary">
			<?php esc_html_e( 'View all products', 'product-lister-etsy' ); ?>
		</button>
	</a>
</div>
</div>
</div>

</body>
</html>
