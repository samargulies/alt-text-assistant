<?php
/*
Plugin Name: Media Ally
Plugin URI: http://stephanieleary.com
Version: 0.3
Author: Stephanie Leary
Author URI: http://stephanieleary.com
Description: Provides a report on the accessibility of your media files.
Tags: accessibility, a11y, media, images, video, audio, transcripts, alt
License: GPL2
*/

// Register alt text column
function media_ally_columns($columns) {
	$columns['ally_column'] = __('Alt text');
	return $columns;
}
add_filter('manage_media_columns', 'media_ally_columns');


// Filter for Media Library that only displays only images without alt text
function media_ally_column_filter( $vars ) {
	
    if ( isset( $vars['orderby'] ) && 'ally_column' === $vars['orderby'] ) {
        $vars = array_merge( $vars, array(
		    'post_mime_type' => 'image', 
		    'meta_query' => array( array(
	            'key' => '_wp_attachment_image_alt',
	            'compare' => 'NOT EXISTS'
	        ) )
        ) );
    }
 
    return $vars;
}
add_filter( 'request', 'media_ally_column_filter' );

// Add tab to Media Library that displays only images without alt text
function media_ally_view($type_links){

	$empty_alt_args = array(
		'post_type' => 'attachment',
	    'post_mime_type' => 'image', 
		'post_status' => 'inherit',
		'posts_per_page' => -1,
	    'meta_query' => array( array(
            'key' => '_wp_attachment_image_alt',
            'compare' => 'NOT EXISTS'
        ) ),
		'fields' => 'ids'
	);
	$empty_alts = get_posts( $empty_alt_args );	
	$empty_alt_num = count($empty_alts);

	$class = (isset($_GET['orderby']) && 'ally_column' === $_GET['orderby'] ) ? ' class="current"' : '';
	$type_links['media_ally'] = "<a href='upload.php?orderby=ally_column&status=media_ally'$class>" . sprintf( 'Images without alt text <span class="count">(%s)</span>', number_format_i18n( $empty_alt_num ) ) . '</a>';

    return $type_links;
}
add_filter( 'views_upload', 'media_ally_view');

// Display alt text or link to edit image
function media_ally_ally_column($column, $id) {
	
	if ( $column == 'ally_column' && wp_attachment_is_image( $id ) ) {
		
      	$alt = get_post_meta( $id, '_wp_attachment_image_alt', true );
		
		if ( empty( $alt ) ) {
			echo '<a href="'.get_edit_post_link( $id ).'#attachment_alt" class="media_ally-no-alt">'.__('Add alt text', 'media_ally').'</a>';
			
		} else  {
			// trim alt text to 100 characters
			if( strlen($alt) > 100 ) {
				$alt = substr($alt, 0, 100) . '&hellip;';
			}
			echo "<span class='media_ally-alt'>$alt</span>";
		}
	}
}
add_action('manage_media_custom_column', 'media_ally_ally_column', 10, 2);

// Insert js to require alt text when inserting a single image
// TODO: only load when the media mananger is loaded
function media_ally_enqueue_scripts() {
	
	$options = get_option('media_ally');
	
	if( $options['require_alt_text'] ) {
		
		wp_enqueue_script( 'media-ally-require-alt-text', plugins_url('/media-ally.js', __FILE__), array('media-views') );
		
	}
	
}
add_action('admin_enqueue_scripts', 'media_ally_enqueue_scripts');

function media_ally_settings_init() {
	
	add_settings_section(
		'media_ally_alt_text_settings',
		'Image alt text',
		'media_ally_alt_text_settings_intro',
		'media'
	);
	
	add_settings_field(
		'media_ally_require_alt_text', 
		'Require alt text', 
		'media_ally_require_alt_text_settings_field', 
		'media', 
		'media_ally_alt_text_settings'
	);
	
	register_setting( 'media', 'media_ally', 'media_ally_options_validate' );
	
	
}
add_action('admin_init', 'media_ally_settings_init');

function media_ally_options_validate( $options_raw ) {
	
	$options = array();
	
	$options['require_alt_text'] = ( $options_raw['require_alt_text'] ) ? 1 : 0;
	
	return $options;
}

function media_ally_alt_text_settings_intro() {
	?>
	<p>Alternative text, or alt text, provides replacement content for users when images cannot be displayed normally. Specifying alt text assists many users, such as users who are visually impaired, or users who use speech synthesizers.</p>
	
	<p>It is required that all images contain accurate, descriptive alt text. When enabled, the setting below will force all users to enter alt text before they can insert images into a post or page.</p>
	
	<?php
}

function media_ally_require_alt_text_settings_field() {
	
	$options = get_option('media_ally');

	?>
	<label for="media_ally_require_alt_text">
	<input id='media_ally_require_alt_text' name='media_ally[require_alt_text]' type='checkbox' value="1" <?php checked( $options['require_alt_text'], 1 ); ?> />
	Require alt text
	</label>
	<?php
	
}