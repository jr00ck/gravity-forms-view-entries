<?php
/*
Plugin Name: Gravity Form View Entries
Plugin URI: https://github.com/jr00ck/gravity-forms-view-entries
Description: Allows viewing Gravity Forms entries on your site using shortcodes. Uses [gf-view-entries] shortcode.
Version: 1.0
Author: FreeUp
Author URI: http://freeupwebstudio.com
Author Email: jeremy@freeupwebstudio.com
*/

$plugin_ver = '1.0';

/* Load styles */
add_action( 'wp_enqueue_scripts', 'gfve_styles' );

function gfve_styles() {
    wp_enqueue_style( 'gfve-styles', plugins_url( '/gravity-forms-view-entries.css' , __FILE__ ), array(), $plugin_ver );
}


// shortcode [gf-view-entries]
add_shortcode('gf-view-entries', 'gf_view_entries_shortcode');

function gf_view_entries_shortcode( $params ) {

	extract( shortcode_atts( array(
                    'form_id'		=> '',
                    'exclude'		=> null
                ), $params ) );

	if( is_numeric($_GET['eid']) ){
		
		$entry_id = $_GET['eid'];

		// get entry by ID
		$entry = GFAPI::get_entry($entry_id);
	}
	
	if( !is_array($entry) ){
    	$entry_html = '<div id="gfve-error">There was an error retrieving this entry. Please try again later.</div>';
	} else {
		$form = GFAPI::get_form($form_id);
		$entry_html .= gfve_display_profile($entry, $form);
	}

	return $entry_html;
}

function gfve_display_profile($entry, $form){

	// get all field labels & IDs into a single-dimensional array
	$fields = gfve_get_fields($form);

	// placeholder for fields w/ multiple values and only one label
	$last_label = '';

	foreach ($fields as $key => $value) {
		
		if($entry[$key]){
			if($value != $last_label){
				// close previous div before starting new label
				if($last_label) { $entry_html .= '</div>'; }
				$entry_html .= '<div class="gfve-entry-field"><span class="gfve-field-label">' . $value . '</span>';
			}
			$entry_html .= '<span class="gfve-field-val">' . $entry[$key] . '</span>';
		}

		$last_label = $value;
	}

	if($entry_html){
		$entry_html = '<div id="gfve-entry-details">' . $entry_html . '</div>';
	}

	return $entry_html;
}

function gfve_get_fields($form){

	foreach ($form['fields'] as $field) {
		if(is_array($field['inputs'])){
			foreach ($field['inputs'] as $input) {
				$label = $field['type'] === 'checkbox' ? $field['label'] : $input['label'];
				$fields[(string)$input['id']] 	= $label;
			}
		} else {
			$fields[$field['id']] 	= $field['label'];
		}
	}
	return $fields;
}
