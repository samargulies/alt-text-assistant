<?php
/*
Plugin Name: Alt Text Assistant
Plugin URI: https://github.com/samargulies/alt-text-assistant
Version: 1.0
Author: Sam Margulies
Author URI: http://belabor.org
Description: Find missing alt text and require alt text when inserting images
Tags: accessibility, media, images, alt
License: GPL2
*/

class Alt_Text_Assistant {
	
	private static $option_name = 'alt_text_assistant';
	private static $no_alt_text_meta_query = array( 
		'relation' => 'OR',
		array(
       		'key' => '_wp_attachment_image_alt',
        	'compare' => 'NOT EXISTS'
    	),
		array(
        	'key' => '_wp_attachment_image_alt',
			'value' => '',
        	'compare' => '='
    	),
	);
	
	// constructor
	function Alt_Text_Assistant() {
		
		add_filter( 'manage_media_columns', array(&$this, 'manage_media_columns') );
		add_filter( 'request', array(&$this, 'alt_text_column_filter') );
		add_filter( 'views_upload', array(&$this, 'views_upload') );
		add_action( 'manage_media_custom_column', array(&$this, 'display_alt_text_column'), 10, 2 );
		
		add_action( 'admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts') );
		
		add_action( 'admin_init', array(&$this, 'settings_init') );
		
	}


	// Register alt text column
	function manage_media_columns( $columns ) {
		$columns['alt_text_assistant_column'] = __('Alt text');
		return $columns;
	}


	// Filter for Media Library that only displays only images without alt text
	function alt_text_column_filter( $vars ) {
	
	    if ( isset( $vars['orderby'] ) && 'alt_text_assistant_column' === $vars['orderby'] ) {
	        $vars = array_merge( $vars, array(
			    'post_mime_type' => 'image', 
			    'meta_query' => self::$no_alt_text_meta_query
	        ) );
	    }
 
	    return $vars;
	}

	// Add tab to Media Library that displays only images without alt text
	function views_upload( $type_links ){

		$empty_alt_args = array(
			'post_type' => 'attachment',
		    'post_mime_type' => 'image', 
			'post_status' => 'inherit',
			'posts_per_page' => -1,
		    'meta_query' => self::$no_alt_text_meta_query,
			'fields' => 'ids'
		);
		$empty_alts = get_posts( $empty_alt_args );	
		$empty_alt_num = count( $empty_alts );

		$class = (isset($_GET['orderby']) && 'alt_text_assistant_column' === $_GET['orderby'] ) ? ' class="current"' : '';
		
		$type_links['alt_text_assistant'] = "<a href='upload.php?orderby=alt_text_assistant_column&status=alt_text_assistant'$class>" . __('Images without alt text', 'alt-text-assistant') . sprintf( ' <span class="count">(%s)</span>', number_format_i18n( $empty_alt_num ) ) . '</a>';

	    return $type_links;
	}

	// Display alt text or link to edit image
	function display_alt_text_column($column, $id) {
		if ( $column == 'alt_text_assistant_column' && wp_attachment_is_image( $id ) ) {

	      	$alt = get_post_meta( $id, '_wp_attachment_image_alt', true );
			
			if ( empty( $alt ) ) {
				echo '<a href="' . get_edit_post_link( $id ) . '#attachment_alt" class="alt_text_assistant-no-alt">' . __('Add alt text', 'alt_text_assistant') . '</a>';
			} else  {
				
				// trim alt text to 100 characters
				if( strlen($alt) > 100 ) {
					$alt = substr($alt, 0, 100) . '&hellip;';
				}
				echo "<span class='alt_text_assistant-alt'>$alt</span>";
			}
		}
	}

	// Insert js to require alt text when inserting a single image
	// TODO: only load when the media mananger is loaded
	function admin_enqueue_scripts() {
		$options = get_option( self::$option_name );
	
		if( $options['require_alt_text'] ) {
			wp_enqueue_script( 'alt-text-assistant', plugins_url('/alt-text-assistant.js', __FILE__), array('media-views') );
			wp_localize_script( 'alt-text-assistant', 'altTextAssistant', array(
				'alertMessage'  => __('Please add alt text to insert this image', 'alt-text-assistant'),
			) );
		}
	}

	function settings_init() {
	
		register_setting( 'media', 'alt_text_assistant', array(&$this, 'validate_options') );
	
		add_settings_section(
			'alt_text_assistant_alt_text_settings',
			'Image alt text',
			array(&$this, 'alt_text_settings_intro'),
			'media'
		);
	
		add_settings_field(
			'alt_text_assistant_require_alt_text', 
			'Require alt text', 
			array(&$this, 'require_alt_text_settings_field'), 
			'media', 
			'alt_text_assistant_alt_text_settings'
		);
	
	}

	function validate_options( $options_raw ) {
		$options = array();
		$options['require_alt_text'] = ( $options_raw['require_alt_text'] ) ? 1 : 0;
		return $options;
	}

	function alt_text_settings_intro() {
		echo '<p>' . __('Alternative text, or alt text, provides replacement content for users when images cannot be displayed normally. Specifying alt text assists many users, such as users who are visually impaired, or users who use speech synthesizers.', 'alt_text_assistant') . '</p>';
		echo '<p>' . __('It is required that all images contain accurate, descriptive alt text. When enabled, the setting below will force all users to enter alt text before they can insert images into a post or page.', 'alt_text_assistant') . '</p>';
	}

	function require_alt_text_settings_field() {
	
		$options = get_option( self::$option_name );

		?>
		<label for="alt_text_assistant_require_alt_text_input">
		<input id='alt_text_assistant_require_alt_text_input' name='alt_text_assistant[require_alt_text]' type='checkbox' value="1" <?php checked( $options['require_alt_text'], 1 ); ?> />
		<?php _e('Require alt text when inserting images', 'alt_text_assistant'); ?>
		</label>
		<?php
	
	}

}

/* Initialise outselves */
$GLOBALS['alt_text_assistant'] = new Alt_Text_Assistant();