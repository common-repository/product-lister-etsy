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

	$( document ).ready(
		function(){

			const path          = window.ced_etsy_admin_obj || {}
			const { etsy_path } = path;

			var breadCrumbArr = [];
			var lastLevalCat  = [];

			$( document ).on(
				"click",
				".ced_etsy_category_arrow",
				async function(){
					var selectedValue = $( this ).data( "id" );
					var parent_id     = $( this ).data( "parentid" );
					var name          = $( this ).data( "name" );
					var level         = $( this ).data( "level" );
					var fileLevel     = level + 1;
					$( '#_umb_etsy_category' ).attr( 'value', selectedValue );
					const jsonData = await ced_etsy_getJsonData( fileLevel );
					if (jsonData) {
						$( '#ced_etsy_cat_header' ).html( "" );
						$( '#ced_etsy_cat_header' ).html(
							"<span data-level='" + level + "' class='dashicons dashicons-arrow-left-alt2 ced_etsy_prev_category_arrow' \
					data-id='" + selectedValue + "'' data-name='" + name + "'' class='dashicons dashicons-arrow-right-alt2'></span><strong id='ced_cat_label'>" + name + " </strong>"
						);
						if ( ! breadCrumbArr.includes( name )) {
							breadCrumbArr.push( name );
						}
						ced_etsy_update_breadCrumb();
						let olList = ced_etsy_add_next_level_cat( jsonData,level,name,selectedValue );
					} else {
						console.log( "An error has occurred." );
					}

				}
			)

			$( document ).on(
				'click',
				'.ced_etsy_prev_category_arrow',
				function() {
					let level = $( this ).attr( 'data-level' );
					if ( level <= 0) {
						return;
					}
					$( this ).attr( 'data-level',(parseInt( level ) - 1) );
					$( '.ced_etsy_categories' ).each(
						function(){
							$( this ).hide();
						}
					);
					$( '#ced_etsy_categories_' + level ).show();
					// $('#ced_etsy_categories li:gt(0)').show();
					let label = $( "#ced_etsy_categories_" + level ).attr( 'data-node-value' );
					$( "#ced_cat_label" ).text( label );
					if (lastLevalCat.length >= 0 ) {
						lastLevalCat.pop();
					}
					breadCrumbArr.pop( label );
					ced_etsy_update_breadCrumb();
					return;

				}
			);

			$( document ).on(
				'click',
				'#ced_etsy_last_level_cat',
				function() {
					let val      = $( this ).val();
					let fullName = $( "#ced_etsy_breadcrumb" ).text();
					let id       = $( this ).data( 'id' );
					console.log( 'ced etsy category ID ' + id );
					$( '#_umb_etsy_category' ).attr( 'value',id );
					$( '#_umb_etsy_category_name' ).attr( 'value',fullName );
					if (val.length <= 0) {
						lastLevalCat.push( val )
						ced_etsy_update_breadCrumb()
					} else {
						lastLevalCat.pop();
						ced_etsy_update_breadCrumb();
						lastLevalCat.push( val );
						ced_etsy_update_breadCrumb();
					}
				}
			)

			async function ced_etsy_getJsonData(level) {

				try {
					const response = await $.getJSON( etsy_path + "admin/lib/json/categoryLevel-" + level + ".json" );
					return response;
				} catch (error) {
					console.log( "An error has occurred." );
				}
			}

			async function ced_etsy_has_MatchingParent(data, currentID) {
				return data.some( item => item.parent_id === currentID );
			}

			async function ced_etsy_add_next_level_cat(response, level = 1, name, selectedValue) {
				let data                   = response.length > 0 ? response : [];
				let next_level             = level + 1;
				let level_after_next_level = next_level + 1;
				var html                   = "";

				html += '<ol id="ced_etsy_categories_' + next_level + '" class="ced_etsy_categories" data-level="' + next_level + '" data-node-value="' + name + '">';

				for (const val of data) {
					let parentId = val.parent_id;
					let id       = val.id;

					if (selectedValue === parentId) {
						const jsonData = await ced_etsy_getJsonData( level_after_next_level );
						const check    = await ced_etsy_has_MatchingParent( jsonData, id );
						if (check) {
							html += '<li class="ced_etsy_category_arrow" data-name="' + val.name + '"  id="' + id + '" data-id="' + id + '" data-level="' + next_level + '">' + val.name + '<span class="dashicons dashicons-arrow-right-alt2"></span></li>';
						} else {
							html += '<li class="ced_etsy_last_cat_tree" data-name="' + val.name + '"  id="' + id + '" data-id="' + id + '" data-level="' + next_level + '">' + val.name + '<input type="radio"  name="ced_etsy_last_level_cat" id="ced_etsy_last_level_cat" data-id="' + id + '" value="' + val.name + '" /></li>';
						}
					}
				}

				html += '</ol>';
				$( '#ced_etsy_categories_' + (level + 1) ).remove();
				$( '#ced_etsy_categories_' + level ).after( html );
				$( '#ced_etsy_categories_' + level ).hide();
			}

			function ced_etsy_update_breadCrumb(){
				var breadCrumbHtml = "";

				for (var i = 0; i < breadCrumbArr.length; i++) {

					if (i === 0) {
						breadCrumbHtml += breadCrumbArr[i];
					} else {
						breadCrumbHtml += " > " + breadCrumbArr[i];

					}
				}
				if (lastLevalCat.length > 0) {
					breadCrumbHtml += " > " + lastLevalCat.join( " " );
				}
				$( "#ced_etsy_breadcrumb" ).text( breadCrumbHtml );
				$( "#ced_etsy_hidden_profile_name" ).val( breadCrumbHtml );
				$( '#_umb_etsy_category_name' ).attr( 'value',breadCrumbHtml );
				$( "#ced_etsy_breadcrumb" ).show();
				$( '.etsy_template_edit_wrapper' ).show();
				$( '.etsy_template_edit_save_button' ).show();
			}

			$( document ).on(
				"click",
				".ced_etsy_last_cat_tree",
				function(){
					$( this ).children().prop( 'checked', true );
					let val      = $( this ).data( 'name' );
					let fullName = $( "#ced_etsy_breadcrumb" ).text();
					if (val.length <= 0) {
						lastLevalCat.push( val )
						ced_etsy_update_breadCrumb()
					} else {
						lastLevalCat.pop();
						ced_etsy_update_breadCrumb();
						lastLevalCat.push( val );
						ced_etsy_update_breadCrumb();
					}
					$( '#_umb_etsy_category' ).attr( 'value',$( this ).data( 'id' ) );
					$( '#_umb_etsy_category_name' ).attr( 'value',fullName );
				}
			);
		}
	);

})( jQuery );
