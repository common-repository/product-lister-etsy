(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
	var ajaxUrl      = ced_etsy_admin_obj.ajax_url;
	var ajaxNonce    = ced_etsy_admin_obj.ajax_nonce;
	var shop_name    = ced_etsy_admin_obj.shop_name;
	var selected_btn = 'unchecked';
	var parsed_response;
	var parent = false;

	$( document ).ready(
		function(){
			$( '.custom_category_attributes_select2' ).selectWoo();
		}
		);
	$( document ).on(
		'change',
		'#ced_etsy_auto_upload_categories' ,
		function() {
			var categories = $( this ).val();
			var shop_name  = $( this ).data( 'shop-name' );
			var operation  = 'remove';
			if ( $( this ).is( ':checked' ) ) {
				operation = 'save';
			}
			$( '#wpbody-content' ).block(
			{
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			}
			);
			$.ajax(
			{
				url : ajaxUrl,
				data : {
					ajax_nonce : ajaxNonce,
					action : 'ced_etsy_auto_upload_categories',
					categories : categories,
					operation:operation,
					shop_name:shop_name,
				},
				type : 'POST',
				success: function( response ) {
					$( '#wpbody-content' ).unblock();
				}
			}
			);

		}
		);

	$( document ).on(
		'keyup',
		'#ced_etsy_input_shop_name' ,
		function() {
			if ($(this).val().length > 0 ) {
				$('#ced_etsy_connect_button').removeAttr('disabled');
			} else {
				$('#ced_etsy_connect_button').prop('disabled', true);
			}
			
		});

	$( document ).on(
		'input',
		'#ced_etsy_input_shop_name' ,
		function() {
			if ($(this).val().length > 0 ) {
				$('#ced_etsy_connect_button').removeAttr('disabled');
			} else {
				$('#ced_etsy_connect_button').prop('disabled', true);
			}
		});

	$( document ).on(
		'click',
		'.log_details',
		function(e){
			e.preventDefault();
			$( '.log_message' ).hide();
			$( document ).find( '.ced_etsy_add_account_popup_main_wrapper' ).addClass( 'show' );
			$( this ).next().addClass( 'show' );
			$( this ).next().toggle();
		}
		);

	$( document ).on(
		'click',
		'.ced_etsy_modal',
		function(e){
			e.preventDefault();
			$('.ced_etsy_template_modal').show();
		}
		);
	$( document ).on(
		'click',
		'.ced_etsy_modal_close',
		function(e){
			e.preventDefault();
			$('.ced_etsy_template_modal').hide();
		}
		);

	$( document ).on(
		'click',
		'#ced_close_log_message',
		function (e) {
			e.preventDefault();
			$( '.log_message' ).hide();
		}
		);

	$( document ).on(
		'click',
		'.ced_etsy_load_more',
		function () {
			$( '#wpbody-content' ).block(
			{
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			}
			);
			var parent  = $( document ).find( this ).attr( 'data-parent' );
			var offset  = $( document ).find( this ).attr( 'data-offset' );
			var total   = $( this ).data( 'total' );
			var element = this;
			$.ajax(
			{
				url: ajaxUrl,
				data: {
					ajax_nonce: ajaxNonce,
					parent: parent,
					offset: offset,
					total: total,
					action: 'ced_etsy_load_more_logs',
				},
				type: 'POST',
				success: function (response) {

					parsed_response = jQuery.parseJSON( response );
					if ( parsed_response.html !== "" ) {
						$( element ).attr( 'data-offset', parseInt( parsed_response.offset ) );
						setTimeout(
							function () {
								$( '#wpbody-content' ).unblock();
								$( '.' + parent ).find( '.ced_etsy_log_rows' ).last().after( parsed_response.html );

							},
							1000
							);

						if (parsed_response.is_disable == "yes" ) {
							$( element ).hide();
						}

					}
				}
			}
			);
		}
		);

	$( document ).on(
		'click' ,
		'.ced_etsy_navigation' ,
		function() {
			$( '#wpbody-content' ).block(
			{
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			}
			);
			var page_no = $( this ).data( 'page' );
			$( '.ced_etsy_metakey_body' ).hide();
			window.setTimeout( function() {$( '#wpbody-content' ).unblock()},500 );
			$( document ).find( '.ced_etsy_metakey_list_' + page_no ).show();
		}
		);

	$( document ).on(
		"change",
		".ced_etsy_shipping_country_id",
		function(){
			var country = $( this ).val();
			if ( 'US' == country || 'CA' == country ) {
				$( '.ced_etsy_shipping_profile_conditional' ).show( 'slow' );
			} else {
				$( '.ced_etsy_shipping_profile_conditional' ).hide( 'hide' );
			}
		}
		)

	$( document ).on(
		'click',
		'#ced_etsy_submit_shipment',
		function(){

			var can_ajax = true;
			$( '.ced_etsy_required_data' ).each(
				function() {
					if ( $( this ).val() == '' ) {
						$( this ).css( 'border' , '1px solid red' );
						can_ajax = false;
						return false;
					} else {
						$( this ).removeAttr( 'style' );
					}
				}
				);

			if (can_ajax) {
				$( this ).addClass( 'disabled' );
				$( '.ced_spinner' ).css( 'visibility' , 'visible' );
				var ced_etsy_tracking_code = $( '#ced_etsy_tracking_code' ).val();
				var ced_etsy_carrier_name  = $( '#ced_etsy_carrier_name' ).val();
				var order_id               = $( this ).data( 'order-id' );

				$.ajax(
				{
					url : ajaxUrl,
					data : {
						ajax_nonce : ajaxNonce,
						action : 'ced_etsy_submit_shipment',
						ced_etsy_tracking_code: ced_etsy_tracking_code,
						ced_etsy_carrier_name:ced_etsy_carrier_name,
						order_id:order_id,
					},
					type : 'POST',
					success: function(response)
					{
						$( "#ced_etsy_submit_shipment" ).removeClass( 'disabled' );
						$( '.ced_spinner' ).css( 'visibility' , 'hidden' );
						parsed_response = jQuery.parseJSON( response );
						var classes     = classes = 'notice notice-success';
						if (parsed_response.status == 400) {
							classes = 'notice notice-error';
						}
						var html = '<div class="' + classes + '"><p>' + parsed_response.message + '</p></div>';
						$( '.ced_etsy_error' ).html( html );
						window.setTimeout( function() {window.location.reload();},5000 );
					}
				}
				);
			}
		}
		);

	$( document ).on(
		'click',
		'#ced_etsy_bulk_operation',
		function(e){
			$( '.success-admin-notices' ).show();
			$( "#ced_progress" ).attr( "value", 0 );
			e.preventDefault();
			var operation = $( "#ced-etsy-bulk-operation" ).val();
			if (operation <= 0 ) {
				$( ".success-admin-notices" ).children().remove();
				var notice = "";
				notice    += "<div class='notice notice-error'><p>Please Select Operation To Be Performed</p></div>";
				$( ".success-admin-notices" ).append( notice );
				return;
			} else {
				var etsy_products_id = new Array();
				$( '.etsy_products_id:checked' ).each(
					function(){
						etsy_products_id.push( $( this ).val() );
					}
					);
				var total_products = etsy_products_id.length;
				cedEtsyperformBulkAction( etsy_products_id, operation, total_products );
			}

		}
		);

	function cedEtsyperformBulkAction( etsy_products_id,operation ,total_products)
	{

		if (etsy_products_id == "") {
			var notice = "";
			notice    += "<div class='notice notice-error'><p>No Products Selected</p></div>";
			$( ".success-admin-notices" ).append( notice );
			return;
		}

		$( '#wpbody-content' ).block(
		{
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		}
		);

		// $( '.ced_etsy_loader' ).show();
		// $( '.ced_progress' ).show();
		// $( "#ced_progress" ).attr( "max",total_products );

		var etsy_products_id_to_perform = etsy_products_id[0];
		var total_processed             = 0;

		$.ajax(
		{
			url : ajaxUrl,
			data : {
				ajax_nonce : ajaxNonce,
				action : 'ced_etsy_process_bulk_action',
				operation_to_be_performed : operation,
				id : etsy_products_id_to_perform,
				shopname:shop_name
			},
			type : 'POST',
			success: function(response)
			{
				var remainig_products_id = etsy_products_id.splice( 1 );
				total_processed          = total_products - remainig_products_id.length;
				var response             = jQuery.parseJSON( response );
				if (response.status == 200) {
					var notice = "";
					notice    += "<div class='notice notice-success'><p>" + response.message + "</p></div>";
					$( ".success-admin-notices" ).append( notice );
						// $( "#ced_progress" ).attr( "value", total_processed );
					if (remainig_products_id == "") {
						$( '#wpbody-content' ).unblock();
						return;
					} else {
						cedEtsyperformBulkAction( remainig_products_id, operation, total_products );
					}
				} else if (response.status == 400) {
					var notice = "";
					notice    += "<div class='notice notice-error'><p>" + response.message + "</p></div>";
					$( ".success-admin-notices" ).append( notice );
					
						// $( "#ced_progress" ).attr( "value", total_processed );
					var notice = "";
					if (remainig_products_id == "") {
						$( '#wpbody-content' ).unblock();
						return;
					} else {
						cedEtsyperformBulkAction( remainig_products_id, operation, total_products );
					}
				}
			}
		}
		);
	}

	

	$( document ).on(
		'click',
		'.woocommerce-importer-done-view-errors-etsy',
		function(){
			$( '#wpbody-content' ).block(
			{
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			}
			);
			$( '#wpbody-content' ).unblock();
			$( '.wc-importer-error-log-etsy' ).slideToggle();
			return false;
		}
		);

	$( document ).on(
		'change',
		'#ced_etsy_switch_account',
		function() {
			let url = $( this ).val();
			if ( url != "" ) {
				$( '#wpbody-content' ).block(
				{
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				}
				);
				$( '#wpbody-content' ).unblock();
				window.location.href = url;
			}
		}
		);

	$( document ).on(
		'click',
		'#ced_etsy_disconnect_account',
		function() {
			let shop_name = $( this ).data( 'shop-name' );
			if ( shop_name == "" ) {
				return;
			}

			$( '#wpbody-content' ).block(
			{
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			}
			);
			$( '#wpbody-content' ).unblock();

			$( "#ced-etsy-delete-account" ).attr( 'data-shop-name' , shop_name );
			$( '#ced-etsy-disconnect-account-modal' ).show();
		}
		);
	$( document ).on(
		'click',
		'.ced-close-button',
		function() {

			$( '#ced-etsy-disconnect-account-modal' ).hide();
		}
		);

	$( document ).on(
		'click',
		'#ced-etsy-delete-account',
		function() {
			$( this ).prev().show();
			$( this ).prev().css( 'visibility','visible' );
			let shop_name = $( this ).data( 'shop-name' );
			$( '#wpbody-content' ).block(
			{
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			}
			);
			$.ajax(
			{
				url : ajaxUrl,
				data : {
					ajax_nonce   : ajaxNonce,
					shop_name    : shop_name,
					action : 'ced_etsy_delete_account',
				},
				type : 'POST',
				success : function( response ){
					$( '#wpbody-content' ).unblock();
					$( '#ced-etsy-delete-account' ).prev().css( 'visibility','hidden' );
					var html = '<div class="notice notice-success"><p>Account deleted successfully.</p></div>';
					$( '.ced_etsy_error' ).html( html );
					window.setTimeout( function() {window.location.reload();},2000 );
				}
			}
			);
		}
		);

	// Timeline popup -- OPEN
	$( document ).on(
		'click',
		'.ced_etsy_timeline_popup',
		function(e) {
			e.preventDefault();
			$( '#wpbody-content' ).block(
			{
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			}
			);
			let wc_post_id = $( this ).data( 'post_id' );
			$( this ).next().show();
			$( '#wpbody-content' ).unblock();
		}
		);

	$( document ).on(
		'click',
		'.ced_s_f_log_details',
		function(e) {
			e.preventDefault();
			$( this ).next().show();
		}
		);

	$( document ).on(
		'click',
		'#ced_close_log_message',
		function() {
			$( '.ced-etsy-timeline-logs-modal' ).hide();
			$( '.ced-etsy-timeline-logs-sc-fld-modal' ).hide();
		}
		);

	document.addEventListener(
		"readystatechange",
		(event) => {
			if (event.target.readyState === "interactive") {
				$( '#wpbody-content' ).block(
				{
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				}
				);
			} else if (event.target.readyState === "complete") {
				setTimeout(
					() => {
						$( '#wpbody-content' ).unblock()
					},
					500
					)
			}
		}
		);

	$( document ).on(
		'change',
		'#_ced_etsy_shipping_profile',
		function(e){
			e.preventDefault();
			if ('create_new_shipping_profile' === $( this ).val()) {
				let create_new_url   = $( '#ced_create_new_shipping_profile' ).val();
				window.location.href = create_new_url;
			}
		}
		);

	$(document).ready(function() {
		var params = window.location.search.substring(1).split("&");
		let onlyIn = ["sync_existing", "setup", "connected"];
		for (var i = 0; i < params.length; i++) {
			var pair = params[i].split("=");
			if (onlyIn.includes(pair[1])) {
				history.pushState(null, null, document.URL);
				$(window).on('popstate', function () {
					history.pushState(null, null, document.URL);
				});
			}
		}
	});

})( jQuery );
