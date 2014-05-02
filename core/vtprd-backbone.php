<?php

class VTPRD_Backbone{   
	
	public function __construct(){
		  $this->vtprd_register_post_types();
      $this->vtprd_add_dummy_rule_category();
   //   add_filter( 'post_row_actions', array(&$this, 'vtprd_remove_row_actions'), 10, 2 );
	}
  
  public function vtprd_register_post_types() {
   global $vtprd_info;
  
  $tax_labels = array(
		'name' => _x( 'Pricing Deals Categories', 'taxonomy general name', 'vtprd' ),
		'singular_name' => _x( 'Pricing Deals Category', 'taxonomy singular name', 'vtprd' ),
		'search_items' => __( 'Search Pricing Deals Category', 'vtprd' ),
		'all_items' => __( 'All Pricing Deals Categories', 'vtprd' ),
		'parent_item' => __( 'Pricing Deals Category', 'vtprd' ),
		'parent_item_colon' => __( 'Pricing Deals Category:', 'vtprd' ),
		'edit_item' => __( 'Edit Pricing Deals Category', 'vtprd' ),
		'update_item' => __( 'Update Pricing Deals Category', 'vtprd' ),
		'add_new_item' => __( 'Add New Pricing Deals Category', 'vtprd' ),
		'new_item_name' => __( 'New Pricing Deals Category', 'vtprd' )
  ); 	

  
  $tax_args = array(
    'hierarchical' => true,
		'labels' => $tax_labels,
		'show_ui' => true,
		'query_var' => false,
		'rewrite' => array( 'slug' => 'vtprd_rule_category' )
  ) ;            

  $taxonomy_name =  'vtprd_rule_category';
 
  
   //REGISTER TAXONOMY 
  	register_taxonomy($taxonomy_name, $vtprd_info['applies_to_post_types'], $tax_args); 

  //this only works after the setup has been updated, and after a refresh...
  global $vtprd_setup_options;
  $vtprd_setup_options = get_option( 'vtprd_setup_options' );
  if ( (isset( $vtprd_setup_options['register_under_tools_menu'] ))  && 
       ($vtprd_setup_options['register_under_tools_menu'] == 'yes') ) {       
      $this->vtprd_register_under_tools_menu();
  } else {
      $this->vtprd_register_in_main_menu();
  }  
 
	$role = get_role( 'administrator' );
	$role->add_cap( 'read_vtprd-rule' );
}

  public function vtprd_add_dummy_rule_category() {
      $category_list = get_terms( 'vtprd_rule_category', 'hide_empty=0&parent=0' );
    	if ( count( $category_list ) == 0 ) {
    		wp_insert_term( __( 'Pricing Deals Category', 'vtprd' ), 'vtprd_rule_category', "parent=0" );
      }
  }


  public function vtprd_register_in_main_menu() {
      $post_labels = array(
				'name' => _x( 'Pricing Deals Rules', 'post type name', 'vtprd' ),
        'singular_name' => _x( 'Pricing Deals Rule', 'post type singular name', 'vtprd' ),
        'add_new' => _x( 'Add New', 'admin menu: add new Pricing Deals Rule', 'vtprd' ),
        'add_new_item' => __('Add New Pricing Deals Rule', 'vtprd' ),
        'edit_item' => __('Edit Pricing Deals Rule', 'vtprd' ),
        'new_item' => __('New Pricing Deals Rule', 'vtprd' ),
        'view_item' => __('View Pricing Deals Rule', 'vtprd' ),
        'search_items' => __('Search Pricing Deals Rules', 'vtprd' ),
        'not_found' =>  __('No Pricing Deals Rules found', 'vtprd' ),
        'not_found_in_trash' => __( 'No Pricing Deals Rules found in Trash', 'vtprd' ),
        'parent_item_colon' => '',
        'menu_name' => __( 'Pricing Deals Rules', 'vtprd' )
			);
    	register_post_type( 'vtprd-rule', array(
    		  'capability_type' => 'post',
          'hierarchical' => true,
    		  'exclude_from_search' => true,
          'labels' => $post_labels,
    			'public' => true,
    			'show_ui' => true,
         // 'show_in_menu' => true,
          'query_var' => true,
          'rewrite' => false,     
          'supports' => array('title' )	 //remove 'revisions','editor' = no content/revisions boxes 
    		)
    	);
  }

  public function vtprd_register_under_tools_menu() {
      $post_labels = array(
				'name' => _x( 'Pricing Deals Rules', 'post type name', 'vtprd' ),
        'singular_name' => _x( 'Pricing Deals Rule', 'post type singular name', 'vtprd' ),
        'add_new' => _x( 'Add New', 'vtprd' ),
        'add_new_item' => __('Add New Pricing Deals Rule', 'vtprd' ),
        'edit' => __('Edit', 'vtprd' ),
        'edit_item' => __('Edit Pricing Deals Rule', 'vtprd' ),
        'new_item' => __('New Pricing Deals Rule', 'vtprd' ),
        'view_item' => __('View Pricing Deals Rule', 'vtprd' ),
        'search_items' => __('Search Pricing Deals Rules', 'vtprd' ),
        'not_found' =>  __('No Pricing Deals Rules found', 'vtprd' ),
        'not_found_in_trash' => __( 'No Pricing Deals Rules found in Trash', 'vtprd' ),
        'parent_item_colon' => '',
        'menu_name' => __( 'Pricing Deals Rules', 'vtprd' )
			);
    	register_post_type( 'vtprd-rule', array(
    		  'capability_type' => 'post',
          'hierarchical' => true,
    		  'exclude_from_search' => true,
          'labels' => $post_labels,
    			'public' => true,
    			'show_ui' => true,
	        "show_in_menu" => 'tools.php',
          'query_var' => true,
          'rewrite' => false,     
          'supports' => array('title' )	 //remove 'revisions','editor' = no content/revisions boxes 
    		)
    	);
  }  


function vtprd_register_settings() {
    register_setting( 'vtprd_options', 'vtprd_rules' );
} 



} //end class
$vtprd_backbone = new VTPRD_Backbone;
  
  
  
  class VTPRD_Functions {   
	
	public function __construct(){

	}
    
  function vtprd_getSystemMemInfo() 
  {       
      /*  Throws errors...
      $data = explode("\n", file_get_contents("/proc/meminfo"));
      $meminfo = array();
      foreach ($data as $line) {
          list($key, $val) = explode(":", $line);
          $meminfo[$key] = trim($val);
      }
      */
      $meminfo = array();
      return $meminfo;
  }
  
  } //end class