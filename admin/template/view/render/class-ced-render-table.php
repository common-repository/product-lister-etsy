<?php
namespace Cedcommerce\Template\View\Render;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * The tables for Admin functionality.
 *
 * @since      2.1.1
 *
 * @package    Ced_Etsy_Integration
 * @subpackage Ced_Etsy_Integration/admin
 */

class Ced_Render_Table {
	/**
	 * *******************
	 * Function construct.
	 * *******************
	 */
	public function __construct() {
	}
	/**
	 * ************
	 * Open table.
	 * ************
	 *
	 * @since 2.1.1
	 * @param string $class Table #class.
	 *
	 * @return string.
	 */
	public function table_open( $class = '' ) {
		return '<table class="' . esc_attr( $class ) . '">';
	}
	/**
	 * ************
	 * Open table.
	 * ************
	 *
	 * @since 2.1.1
	 * @param string $name Table #name.
	 *
	 * @return string.
	 */
	public function table_label( $name ) {
		return '<label for="' . esc_attr( $name ) . '">' . esc_html( $name ) . '</label> : ';
	}
	/**
	 * *****************
	 * Open input fiels.
	 * *****************
	 *
	 * @since 2.1.1
	 * @param string $type Table input type #name.
	 * @param string $class Table input #class.
	 * @param string $name Table input #name.
	 * @param string $placeholder Table input #palceholder.
	 *
	 * @return string.
	 */
	public function table_input( $type, $class, $name, $placeholder ) {
		return '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '" class="' . esc_attr( $class ) . ' forminp forminp-text" id="' . esc_attr( $name ) . '" placeholder="' . esc_attr( $placeholder ) . '">';
	}
	/**
	 * *****************
	 * Open input fiels.
	 * *****************
	 *
	 * @since 2.1.1
	 * @param string $name Table textarea #name.
	 * @param string $placeholder Table textarea #placeholder.
	 *
	 * @return string.
	 */
	public function table_textarea( $name, $placeholder ) {
			return '<textarea name="' . esc_attr( $name ) . '" placeholder="' . esc_attr( $placeholder ) . '"></textarea>';
	}
	/**
	 * *****************************************
	 * Table data fields values putting inside.
	 * *****************************************
	 *
	 * @since 2.1.1
	 * @param string $in_params Table data in parameter.
	 *
	 * @return string.
	 */
	public function td( $in_params = '' ) {
		return '<td>' . $in_params . '</td>';
	}
	/**
	 * *****************
	 * Table heading th
	 * *****************
	 *
	 * @since 2.1.1
	 * @param string $in_params inside table heading contents.
	 *
	 * @return string.
	 */
	public function th( $in_params = '' ) {
		return '<th class="titledesc">' . $in_params . '</th>';
	}

	/**
	 * *****************
	 * Table heading th
	 * *****************
	 *
	 * @since 2.1.1
	 * @param string $in_params inside table heading contents.
	 *
	 * @return string.
	 */
	public function tr( $in_params = '' ) {
		return '<tr valign="top">' . $in_params . '</tr>';
	}

	/**
	 * *****************
	 * Table Body <body>
	 * *****************
	 *
	 * @since 2.1.1
	 * @param string $in_params inside table body contents.
	 *
	 * @return string.
	 */
	public function table_body( $in_params = '' ) {
		return '<tbody>' . $in_params . '</tbody>';
	}

	/**
	 * *************
	 * Table Button
	 * *************
	 *
	 * @since 2.1.1
	 *
	 * @param string $type Table Button type.
	 * @param string $text Button Text inside.
	 *
	 * @return string Input fields.
	 */
	public function table_button( $type, $text ) {
		return '<input type="' . esc_attr( $type ) . '" value="' . esc_attr( $text ) . '">';
	}


	/**
	 * *************
	 * Table Close
	 * *************
	 *
	 * @since 2.1.1
	 *
	 * @return string Table close fields.
	 */
	public function table_close() {
		return '</table>';
	}

	/**
	 * ************
	 * Table label
	 * ************
	 *
	 * @since 2.1.1
	 *
	 * @param string $class Label class.
	 * @param string $in Lable inside content.
	 * @param string $desc Label description.
	 *
	 * @return string table label.
	 */
	public function label( $class = '', $in = '', $desc = '', $for = '' ) {
		return '<label class="' . esc_attr( $class ) . '" for="' . esc_attr( $for ) . '">' . $in . wc_help_tip( $desc ) . '</label>';
	}

	/**
	 * **************************
	 * Table Select and Options
	 * **************************
	 *
	 * @since 2.1.1
	 *
	 * @param string $name select name.
	 * @param array  $option_array Select option arrays.
	 * @param string $selected Selected flag to make it selected.
	 * @param string $class Select class.
	 * @param string $id Select tag id.
	 *
	 * @return string of select.
	 */
	public function select( $name = '', $option_array = '', $prev_val = '', $class = '', $id = '', $style = '' ) {
		$to_return = '';

		$to_return .= '<select name="' . esc_attr( $name ) . '" class="' . esc_attr( $class ) . '" id="' . esc_attr( $id ) . '" style="' . esc_attr( $style ) . '">';
		$to_return .= '<option value="">---Select---</option>';
		foreach ( $option_array as $opt_key => $opt_value ) {
			$selected = '';
			if ( $opt_key == $prev_val ) {
				$selected = 'selected';
			}
			$to_return .= '<option value="' . esc_attr( $opt_key ) . '"' . esc_attr( $selected ) . '>' . esc_html( $opt_value ) . '</option>';
		}
		$to_return .= '</select>';
		return $to_return;
	}

	/**
	 * **************************
	 * Table Select and Options
	 * **************************
	 *
	 * @since 2.1.1
	 *
	 * @param string $name checkbox name.
	 * @param array  $is_checked Flag whether it checked or not.
	 * @param string $class checkbox class.
	 *
	 * @return string of checkbox.
	 */
	public function check_box( $name = '', $is_checked = '', $l_class = '', $id = '', $for = '', $i_class = '' ) {
		return '<input class="' . esc_attr( $i_class ) . '" id="' . esc_attr( $id ) . '" type="checkbox" name="' . esc_attr( $name ) . '"  ' . esc_attr( $is_checked ) . '><label class="' . esc_attr( $l_class ) . '" for="' . esc_attr( $for ) . '"></label>';
	}
}
