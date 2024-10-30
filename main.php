<?php
/*
Plugin Name: Ipsum Maker
Plugin URI: http://vegasgeek.com/
Description: Use Ipsum Maker to create your own Ipsum Generator
Author: John Hawkins
Author URI: http://vegasgeek.com/
Version: 0.2

*/

// Create our Custom Post Type

function make_ipsum_cpt() {
	$labels = array(
	'name' => _x('Ipsum', 'post type general name'),
	'singular_name' => _x('Ipsum', 'post type singular name'),
	'add_new' => _x('Add New Ipsum', 'services'),
	'add_new_item' => __('Add New Ipsum'),
	'edit_item' => __('Edit Ipsum'),
	'edit' => _x('Edit Ipsum', 'ipsums'),
	'new_item' => __('New Ipsum'),
	'view_item' => __('View Ipsums'),
	'search_items' => __('Search Ipsums'),
	'not_found' =>  __('No ipsums found'),
	'not_found_in_trash' => __('No ipsums found in Trash'), 
	'view' =>  __('View Ipsum'),
	'parent_item_colon' => ''
	);
	$args = array(
	'labels' => $labels,
	'public' => false,
	'publicly_queryable' => false,
	'has_archive' => false,
	'show_ui' => true,
	'query_var' => true,
	'capability_type' => 'post',
	'hierarchical' => false,
	'menu_position' => null,
	'supports' => array( 'title' )
	); 

	register_post_type( 'ipsum', $args);
}

add_action( 'init', 'make_ipsum_cpt', 1 );


// Create shortcode

function make_ipsum_sc( $atts ){
	extract( shortcode_atts( array(
      'character' => '',
      ), $atts ) );
	
	// Make sure there's no garbage
	$icount = esc_attr($_GET['par']);

	// Make sure we got a number
	if (is_numeric($icount)) {
		
		// Make sure somebody didn't tweak the URL to display a million records
		if ($icount > 5) {
			$icount = 5;
		}

		// Loop through to create our paragraphs
		while ($x < $icount) {
			$x++;
			
			// Each paragraph should randomly be made up of 4 - 8 quotes
			$qcount = rand( 4, 8);

			if ($_GET['character']) {
				$character = esc_attr($_GET['character']);
			
				$tax_array = array(
					array(
						'taxonomy' => 'ipsumcharacter',
						'field' => 'slug',
						'terms' => $character
					)
				);
			}
		
			// Set some args for to grab some posts
			$args = array(
				'numberposts'     => $qcount,
				'offset'          => 0,
				'category'        => '',
				'orderby'         => 'rand',
				'include'         => '',
				'exclude'         => '',
				'meta_key'        => '',
				'meta_value'      => '',
				'post_type'       => 'ipsum',
				'post_mime_type'  => '',
				'post_parent'     => '',
				'post_status'     => 'publish'
			);

			$args['tax_query'] = $tax_array;


			// Grab the posts
			$posts_array = get_posts( $args );

			// Generate the output
			echo '<p>';
			foreach ($posts_array as $singlepost) {
				echo $singlepost->post_title.' ';
			}
			echo '</p>';
		}
		echo '<hr>';
	}
	// Form HTML
	?>
	<div class="ipsumform">
	<form method="GET" action="">
		Paragraphs: 
		<select name="par">
			<option value="5" <?php echo ($_GET['par'] == 5 ? 'SELECTED' : ''); ?>>5</option>
			<option value="4" <?php echo ($_GET['par'] == 4 ? 'SELECTED' : ''); ?>>4</option>
			<option value="3" <?php echo ($_GET['par'] == 3 ? 'SELECTED' : ''); ?>>3</option>
			<option value="2" <?php echo ($_GET['par'] == 2 ? 'SELECTED' : ''); ?>>2</option>
			<option value="1" <?php echo ($_GET['par'] == 1 ? 'SELECTED' : ''); ?>>1</option>
		</select>
		<br /><br />
		Select Character:
		<select name="character">
			<option value="">Select One</option>
			<?php
			$terms = get_terms("ipsumcharacter");
			$count = count($terms);
			if ( $count > 0 ){
				foreach ( $terms as $term ) {
					
					if ($term->slug == $tax_array[0][terms]) {
						$termselect = "SELECTED";
					} else {
						$termselect = '';
					}
					
					
					echo '<option value="'.$term->slug.'" '.$termselect.'>' . $term->name . '</option>';
				}
			}
			?>
		</select><br /><br />
		<input type="submit" value="Generate Ipsum">
	</form>
	</div>
	<?php
}

add_shortcode( 'ipsum', 'make_ipsum_sc' );


// Setup Character Taxonomy

function setup_char_tax() {
	
	$labels = array(
		'name' => _x( 'Characters', 'taxonomy general name' ),
		'singular_name' => _x( 'Character', 'taxonomy singular name' ),
		'search_items' =>  __( 'Search characters' ),
		'popular_items' => __( 'Popular characters' ),
		'all_items' => __( 'All charachters' ),
		'parent_item' => null,
		'parent_item_colon' => null,
		'edit_item' => __( 'Edit character' ), 
		'update_item' => __( 'Update character' ),
		'add_new_item' => __( 'Add New Character' ),
		'new_item_name' => __( 'New Character' ),
		'separate_items_with_commas' => __( 'Separate characters with commas' ),
		'add_or_remove_items' => __( 'Add or remove characters' ),
		'choose_from_most_used' => __( 'Choose from the most used characters' ),
		'menu_name' => __( 'Characters' ),
	); 

	register_taxonomy('ipsumcharacter', 'ipsum', array(
		'hierarchical' => false,
		'labels' => $labels,
		'show_ui' => true,
		'query_var' => true,
	));
}

add_action( 'init', 'setup_char_tax' );

// Add character column to ipsum page
class CustomizeWPAdminTables {

	private $quickEditActions = array();
	
	function onInit() {
		// Add custom column
		add_filter( 'manage_ipsum_posts_columns', array( $this, 'addColumns' ) );

		// Add custom data to the new column
		add_action( 'manage_ipsum_posts_custom_column', array( $this, 'renderColumns' ), 10, 2 );
	}
	
	function addColumns( $columns ) {
		$columns['ipsumchar'] = 'Character';
		
		return $columns;
	}
	
	function renderColumns( $column_index, $post_id ) {
		global $post;
		if ( $column_index == 'ipsumchar' ) {
			$ipsum_terms = wp_get_object_terms($post->ID, 'ipsumcharacter');
			if(!empty($ipsum_terms)){
				if(!is_wp_error( $ipsum_terms )){
					foreach($ipsum_terms as $term){
						$term_list .= $term->name .', ';
					}
				}
			}
			echo rtrim( $term_list, ', ' );
		}
	}
}

$custom_tables_plugin = new CustomizeWPAdminTables();
add_action( 'init', array( $custom_tables_plugin, 'onInit' ) );


?>