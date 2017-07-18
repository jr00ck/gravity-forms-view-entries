<?php
/*
Plugin Name: Gravity Forms View Entries
Plugin URI: https://github.com/jr00ck/gravity-forms-view-entries
Description: Allows viewing Gravity Forms entries on your site using shortcodes. Uses [gf-view-entries] shortcode. Also provides a link to view an entry using [gf-view-entries-link] shortcode.
Version: 1.3.3
Author: FreeUp
Author URI: http://freeupwebstudio.com
Author Email: jeremy@freeupwebstudio.com
GitHub Plugin URI: https://github.com/jr00ck/gravity-forms-view-entries
*/

$plugin_ver = '1.3.3';

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
                    'entry_id'		=> '',
                    'exclude_fields'=> null,
                    'error'			=> 'There was an error retrieving this entry. Please try again later.',
                    'key'			=> '',
                    'value'			=> ''
                ), $params ) );

	if( is_numeric($_GET['entry_id']) || $entry_id ){
		
		$entry_id = $entry_id ? $entry_id : $_GET['entry_id'];

		// get entry by ID
		$entry = GFAPI::get_entry($entry_id);
	}
	
	if( !is_array($entry) ){
    	$entry_html = '<div id="gfve-error">' . $error . '</div>';
	} else {
		$form = GFAPI::get_form($entry['form_id']);
		// check for fields to exclude
		$exclude_fields = explode(',', $exclude_fields);
		$entry_html = gfve_display_profile($entry, $form, $exclude_fields);
	}

	return $entry_html;
}

function gfve_display_profile($entry, $form, $exclude_fields = null, $include_notes = false ){

	// get all field labels & IDs into a single-dimensional array
	$fields = gfve_get_fields($form);

	// placeholder for fields w/ multiple values and only one label
	$last_label = '';

	foreach ($fields as $key => $value) {
		
		if($entry[$key]){
			// only show if this field should not be excluded
			if(!in_array($key, $exclude_fields)){
				if($value != $last_label){
					// close previous div before starting new label
					if($last_label !== '') { $entry_html .= '</div>'; }
					$entry_html .= '<div class="gfve-entry-field"><span class="gfve-field-label">' . $value . '</span>';
				}
				$entry_html .= '<span class="gfve-field-val">' . $entry[$key] . '</span>';
				$last_label = $value;
			}
		}
	}

	if($include_notes){
		// close fields div
		$entry_html .= '</div>';

		$values = array();	
    	$notes = RGFormsModel::get_lead_notes( $entry['id'] );
    	
    	$format = '%1$s %2$s %4$s';
				
		if( !isset( $separator ) || '' == $separator ){
	        	$separator = '|';
        	}
				
		if ( sizeof( $notes ) > 0 ) {
		
			$entry_html .= '<div class="gfve-entry-field"><span class="gfve-field-label">Notes</span>';

			foreach ( $notes as $note ) {
			
				$username = $note->user_name;
				$emailaddress = $note->user_email;					
				$date = GFCommon::format_date( $note->date_created, false );
				$content = $note->value;
				$userid = $note->user_id;
				$noteid = $note->id;
				$author = get_user_by('id', $note->user_id );
				$name = $author->first_name . ' ' . $author->last_name;
				
				// $values[] = sprintf($format, $date, $username, $emailaddress, $content, $userid, $noteid, $name);    
				$entry_html .= '<div class="gfve-notes-entry">';
				$entry_html .= '<span class="gfve-field-val">' . $date . '</span>';
				$entry_html .= '<span class="gfve-field-val">' . $username . '</span>';
	        	$entry_html .= '<span class="gfve-field-val">' . $content . '</span>';
    	    	$entry_html .= '</div>';
			}

			$entry_html .= '</div>';
		}


	}else{
		// close fields div
		$entry_html .= '</div>';
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


// shortcode [gf-view-entries-link]
add_shortcode('gf-view-entries-link', 'gf_view_entries_link_shortcode');

function gf_view_entries_link_shortcode( $params, $content = NULL ) {

	extract( shortcode_atts( array(
					'entry_id'		=> '',
                    'url'			=> '',
                    'form_id'		=> '',
                    'key'			=> '',
                    'value'			=> '',
                    'button'		=> ''
                ), $params ) );

	if($entry_id && $url){
		$view_link = $url . '?entry_id=' . $entry_id;
	} elseif($url && $form_id && $key && $value){
		//if trying to get current user by username
		if($value=="current_username") {
			global $current_user;
			get_currentuserinfo();
			$value = $current_user->user_login;
		}
		// setup search criteria
		$search_criteria['field_filters'][] = array( 'key' => $key, 'value' => $value );
		// run query
    	$entry = GFAPI::get_entries($form_id, $search_criteria);
    	
		$view_link = $url . '?entry_id=' . $entry[0]['id'];

		if($button){
			$view_link = '<a class="icon-button ' . $button . '" href="' . $view_link . '">' . $content . '<span class="et-icon"></span></a>';
		}
	}

	return $view_link;

}

// shortcode [gf-view-entries-list]
add_shortcode('gf-view-entries-list', 'gf_view_entries_list_shortcode');

function gf_view_entries_list_shortcode( $params, $content = NULL ) {

	extract( shortcode_atts( array(
					'form_id'		=> '',
                    'key'			=> '',
                    'value'			=> '',
                    'exclude_fields'=> array(),
                    'include_notes'	=> false,
                ), $params ) );

	$view_list = '';

	if( $form_id && is_numeric( $form_id ) ){
		
		$search_criteria = array();

		if( $key && $value ){

			//if trying to get current user by username
			if($value=="current_username") {
				global $current_user;
				get_currentuserinfo();
				$value = $current_user->user_login;
			}
			// setup search criteria
			$search_criteria['field_filters'][] = array( 'key' => $key, 'value' => $value );

		}
		
		// run query
    	$entries = GFAPI::get_entries($form_id, $search_criteria);
    	
    	$form = GFAPI::get_form($form_id);

    	if( $entries ){
    		$view_list .= '<div class="gfve-entries-list">';
    		foreach($entries as $entry){
				$view_list .= '<div class="entry-id-'.$entry['id'].'">';
				$view_list .= gfve_display_profile($entry, $form, $exclude_fields, $include_notes);
				$view_list .= '</div>';;
			}
			$view_list .= '</div>';
    	}
		
	}

	return $view_list;

}