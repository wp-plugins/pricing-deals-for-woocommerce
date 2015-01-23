<?php
/*
Plugin Name: VarkTech Pricing Deals for WooCommerce
Plugin URI: http://varktech.com
Description: An e-commerce add-on for WooCommerce, supplying Pricing Deals functionality.
Version: 1.0.9.1
Author: Vark
Author URI: http://varktech.com
*/

/*  ******************* *******************
=====================
ASK YOUR HOST TO TURN OFF magic_quotes_gpc !!!!!
=====================
******************* ******************* */


/*
** define Globals 
*/
   $vtprd_info;  //initialized in VTPRD_Parent_Definitions
   $vtprd_rules_set;
   $vtprd_rule;
   $vtprd_cart;
   $vtprd_cart_item;
   $vtprd_setup_options;
   
   $vtprd_rule_display_framework;
   $vtprd_rule_type_framework; 
   $vtprd_deal_structure_framework;
   $vtprd_deal_screen_framework;
   $vtprd_deal_edits_framework;
   $vtprd_template_structures_framework;
   
   //initial setup only, overriden later in function vtprd_debug_options
   error_reporting(E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR); //v1.0.7.7



     
class VTPRD_Controller{
	
	public function __construct(){    
 
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    } 
    
		define('VTPRD_VERSION',                               '1.0.9.1');
    define('VTPRD_MINIMUM_PRO_VERSION',                   '1.0.6.0');
    define('VTPRD_LAST_UPDATE_DATE',                      '2015-01-23');
    define('VTPRD_DIRNAME',                               ( dirname( __FILE__ ) ));
    define('VTPRD_URL',                                   plugins_url( '', __FILE__ ) );
    define('VTPRD_EARLIEST_ALLOWED_WP_VERSION',           '3.3');   //To pick up wp_get_object_terms fix, which is required for vtprd-parent-functions.php
    define('VTPRD_EARLIEST_ALLOWED_PHP_VERSION',          '5');
    define('VTPRD_PLUGIN_SLUG',                           plugin_basename(__FILE__));
    define('VTPRD_PRO_PLUGIN_NAME',                      'Varktech Pricing Deals Pro for WooCommerce');    //v1.0.7.1
   
    require_once ( VTPRD_DIRNAME . '/woo-integration/vtprd-parent-definitions.php');
            
    // overhead stuff
    add_action('init', array( &$this, 'vtprd_controller_init' ));
        
    /*  =============+++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
    //  these control the rules ui, add/save/trash/modify/delete
    /*  =============+++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
    
    /*  =============+++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
    //  One of these will pick up the NEW post, both the Rule custom post, and the PRODUCT
    //    picks up ONLY the 1st publish, save_post works thereafter...   
    //      (could possibly conflate all the publish/save actions (4) into the publish_post action...)
    /*  =============+++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */    
    if (is_admin()) {   //v1.0.7.2   only add during is_admin
        add_action( 'draft_to_publish',       array( &$this, 'vtprd_admin_update_rule_cntl' )); 
        add_action( 'auto-draft_to_publish',  array( &$this, 'vtprd_admin_update_rule_cntl' ));
        add_action( 'new_to_publish',         array( &$this, 'vtprd_admin_update_rule_cntl' )); 			
        add_action( 'pending_to_publish',     array( &$this, 'vtprd_admin_update_rule_cntl' ));
        
        //standard mod/del/trash/untrash
        add_action('save_post',     array( &$this, 'vtprd_admin_update_rule_cntl' ));
        add_action('delete_post',   array( &$this, 'vtprd_admin_delete_rule' ));    
        add_action('trash_post',    array( &$this, 'vtprd_admin_trash_rule' ));
        add_action('untrash_post',  array( &$this, 'vtprd_admin_untrash_rule' ));
        /*  =============+++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
        
        //get rid of bulk actions on the edit list screen, which aren't compatible with this plugin's actions...
        add_action('bulk_actions-edit-vtprd-rule', array($this, 'vtprd_custom_bulk_actions') );
    } //v1.0.7.2  end
    
	}   //end constructor

  	                                                             
 /* ************************************************
 **   Overhead and Init
 *************************************************** */
	public function vtprd_controller_init(){
    global $vtprd_setup_options;

    //$product->get_rating_count() odd error at checkout... woocommerce/templates/single-product-reviews.php on line 20  
    //  (Fatal error: Call to a member function get_rating_count() on a non-object)
    global $product;
       
    load_plugin_textdomain( 'vtprd', null, dirname( plugin_basename( __FILE__ ) ) . '/languages' );  //v1.0.8.4  moved here above defs


     if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon') { //v1.0.9.0  doesn't apply if 'discountUnitPrice'
      //v1.0.8.5 begin
      // instead of translation, using filter to allow title change!!!!!!!!
      //  this propagates throughout all plugin code execution through global...
      $coupon_title  = apply_filters('vtprd_coupon_code_discount_title','' );
      if ($coupon_title) {
         global $vtprd_info; 
         $vtprd_info['coupon_code_discount_deal_title'] = $coupon_title;
      }
    }  //v1.0.9.0
    /*
    // Sample filter execution ==>>  put into your theme's functions.php file, so it's not affected by plugin updates
          function coupon_code_discount_title() {
            return 'different coupon title';  //<<==  Change this text to be the title you want!!!
          }
          add_filter('vtprd_coupon_code_discount_title', 'coupon_code_discount_title', 10);         
    */
    //v1.0.8.5 end
    
    
    //Split off for AJAX add-to-cart, etc for Class resources.  Loads for is_Admin and true INIT loads are kept here.
    //require_once ( VTPRD_DIRNAME . '/core/vtprd-load-execution-resources.php' );

    require_once  ( VTPRD_DIRNAME . '/core/vtprd-backbone.php' );    
    require_once  ( VTPRD_DIRNAME . '/core/vtprd-rules-classes.php');
    require_once  ( VTPRD_DIRNAME . '/admin/vtprd-rules-ui-framework.php' );
    require_once  ( VTPRD_DIRNAME . '/woo-integration/vtprd-parent-functions.php');
    require_once  ( VTPRD_DIRNAME . '/woo-integration/vtprd-parent-theme-functions.php');
    require_once  ( VTPRD_DIRNAME . '/woo-integration/vtprd-parent-cart-validation.php');
//  require_once  ( VTPRD_DIRNAME . '/woo-integration/vtprd-parent-definitions.php');    //v1.0.8.4  moved above
    require_once  ( VTPRD_DIRNAME . '/core/vtprd-cart-classes.php');
    
    //************
    //changed for AJAX add-to-cart, removed the is_admin around these resources => didn't work, for whatever reason...
    if(defined('VTPRD_PRO_DIRNAME')) {
      require_once  ( VTPRD_PRO_DIRNAME . '/core/vtprd-apply-rules.php' );
      require_once  ( VTPRD_PRO_DIRNAME . '/woo-integration/vtprd-lifetime-functions.php' );          
    } else {
      require_once  ( VTPRD_DIRNAME .     '/core/vtprd-apply-rules.php' );
    }

    $vtprd_setup_options = get_option( 'vtprd_setup_options' );  //put the setup_options into the global namespace 
    
    //**************************
    //v1.0.9.0 begin  
    //**************************
    switch( true ) { 
      case  is_admin() :
        $do_nothing;
        break;
         
      case ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon') :
        $do_nothing;
        break;
             
      case ($vtprd_setup_options['discount_taken_where'] == 'discountUnitPrice') :
        //turn off switches not allowed for "discountUnitPrice" 
        $vtprd_setup_options['show_checkout_purchases_subtotal']     =   'none';                           
        $vtprd_setup_options['show_checkout_discount_total_line']    =   'no'; 
        $vtprd_setup_options['checkout_new_subtotal_line']           =   'no'; 
        $vtprd_setup_options['show_cartWidget_purchases_subtotal']   =   'none';                           
        $vtprd_setup_options['show_cartWidget_discount_total_line']  =   'no'; 
        $vtprd_setup_options['cartWidget_new_subtotal_line']         =   'no';         
        break;
                
      default:
        // supply default for new variables as needed for upgrade v1.0.8.9 => v1.0.9.0 as needed
        $vtprd_setup_options['discount_taken_where']        =   'discountCoupon';  
        $vtprd_setup_options['give_more_or_less_discount']  =   'more'; 
        update_option( 'vtprd_setup_options',$vtprd_setup_options);  //v1.0.9.1
        break;
    
    }
    //v1.0.9.0 end 
    
    if (function_exists('vtprd_debug_options')) { 
      vtprd_debug_options();  //v1.0.5
    }
            
    /*  **********************************
        Set GMT time zone for Store 
    Since Web Host can be on a different
    continent, with a different *Day* and Time,
    than the actual store.  Needed for Begin/end date processing
    **********************************  */
    vtprd_set_selected_timezone();
        
    if (is_admin()){ 
        add_filter( 'plugin_action_links_' . VTPRD_PLUGIN_SLUG , array( $this, 'vtprd_custom_action_links' ) );
        require_once ( VTPRD_DIRNAME . '/admin/vtprd-setup-options.php');
        require_once ( VTPRD_DIRNAME . '/admin/vtprd-rules-ui.php' );
           
        if(defined('VTPRD_PRO_DIRNAME')) {         
          require_once ( VTPRD_PRO_DIRNAME . '/admin/vtprd-rules-update.php'); 
          require_once ( VTPRD_PRO_DIRNAME . '/woo-integration/vtprd-lifetime-functions.php' );           
        } else {
          require_once ( VTPRD_DIRNAME .     '/admin/vtprd-rules-update.php');
        }
        
        require_once ( VTPRD_DIRNAME . '/admin/vtprd-show-help-functions.php');
        require_once ( VTPRD_DIRNAME . '/admin/vtprd-checkbox-classes.php');
        require_once ( VTPRD_DIRNAME . '/admin/vtprd-rules-delete.php');
        
        $this->vtprd_admin_init();
            
        if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon') { //v1.0.9.0  doesn't apply if 'discountUnitPrice'
          //always check if the manually created coupon codes are there - if not create them.
          vtprd_woo_maybe_create_coupon_types();
        }   
        
        //v1.0.7.1 begin
        if ( (defined('VTPRD_PRO_DIRNAME')) &&
             (version_compare(VTPRD_PRO_VERSION, VTPRD_MINIMUM_PRO_VERSION) < 0) ) {    //'<0' = 1st value is lower  
          add_action( 'admin_notices',array(&$this, 'vtprd_admin_notice_version_mismatch') );            
        }
        //v1.0.7.1 end 
      
      //v1.0.7.4 begin  
      //****************************************
      //INSIST that coupons be enabled in woo, in order for this plugin to work!!
      //****************************************
      $coupons_enabled = get_option( 'woocommerce_enable_coupons' ) == 'no' ? false : true;
      if (!$coupons_enabled) {  
        add_action( 'admin_notices',array(&$this, 'vtprd_admin_notice_coupon_enable_required') );            
      } 
  // don't have to do this EXCEPT at install time....
  //    $this->vtprd_maybe_add_wholesale_role(); //v1.0.9.0
 
      //v1.0.7.4 end 
      
    } else {

        add_action( "wp_enqueue_scripts", array(&$this, 'vtprd_enqueue_frontend_scripts'), 1 );    //priority 1 to run 1st, so front-end-css can be overridden by another file with a dependancy

    }

      /*
    if (is_admin()){ 

      //LIFETIME logid cleanup...
      //  LogID logic from wpsc-admin/init.php
      if(defined('VTPRD_PRO_DIRNAME')) {
        switch( true ) {
          case ( isset( $_REQUEST['wpsc_admin_action2'] ) && ($_REQUEST['wpsc_admin_action2'] == 'purchlog_bulk_modify') )  :
                 vtprd_maybe_lifetime_log_bulk_modify();
             break; 
          case ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'delete_purchlog') ) :
                 vtprd_maybe_lifetime_log_roll_out_cntl();
             break;                                             
        } 
          
        if (version_compare(VTPRD_PRO_VERSION, VTPRD_MINIMUM_PRO_VERSION) < 0) {    //'<0' = 1st value is lower  
          add_action( 'admin_notices',array(&$this, 'vtprd_admin_notice_version_mismatch') );            
        }          
      }
      
      //****************************************
      //INSIST that coupons be enabled in woo, in order for this plugin to work!!
      //****************************************
      $coupons_enabled = get_option( 'woocommerce_enable_coupons' ) == 'no' ? false : true;
      if (!$coupons_enabled) {  
        add_action( 'admin_notices',array(&$this, 'vtprd_admin_notice_coupon_enable_required') );            
      } 
    } 
      */   
         


    return; 
  }

  public function vtprd_enqueue_frontend_scripts(){
    global $vtprd_setup_options;
        
    wp_enqueue_script('jquery'); //needed universally
    
    if ( $vtprd_setup_options['use_plugin_front_end_css'] == 'yes' ){
      wp_register_style( 'vtprd-front-end-style', VTPRD_URL.'/core/css/vtprd-front-end-min.css'  );   //every theme MUST have a style.css...  
      //wp_register_style( 'vtprd-front-end-style', VTPRD_URL.'/core/css/vtprd-front-end-min.css', array('style.css')  );   //every theme MUST have a style.css...      
      wp_enqueue_style('vtprd-front-end-style');
    }
    
    return;
  
  }  

         
  /* ************************************************
  **   Admin - Remove bulk actions on edit list screen, actions don't work the same way as onesies...
  ***************************************************/ 
  function vtprd_custom_bulk_actions($actions){
              //v1.0.7.2  add  ".inline.hide-if-no-js, .view" to display:none; list
    ?>         
    <style type="text/css"> #delete_all, .inline.hide-if-no-js, .view {display:none;} /*kill the 'empty trash' buttons, for the same reason*/ </style>
    <?php
    
    unset( $actions['edit'] );
    unset( $actions['trash'] );
    unset( $actions['untrash'] );
    unset( $actions['delete'] );
    return $actions;
  }

      
  /* ************************************************
  **   Admin - Show Rule UI Screen
  *************************************************** 
  *  This function is executed whenever the add/modify screen is presented
  *  WP also executes it ++right after the update function, prior to the screen being sent back to the user.   
  */  
	public function vtprd_admin_init(){
     if ( !current_user_can( 'edit_posts', 'vtprd-rule' ) )
          return;

     $vtprd_rules_ui = new VTPRD_Rules_UI;      
  }

  /* ************************************************
  **   Admin - Publish/Update Rule or Parent Plugin CPT 
  *************************************************** */
	public function vtprd_admin_update_rule_cntl(){
      global $post, $vtprd_info;    
      
      
      // v1.0.7.3 begin
      if( !isset( $post ) ) {    
        return;
      }  
      // v1.0.7.3  end
                        
      switch( $post->post_type ) {
        case 'vtprd-rule':
            $this->vtprd_admin_update_rule();  
          break; 
        case $vtprd_info['parent_plugin_cpt']: //this is the update from the PRODUCT screen, and updates the include/exclude lists
            $this->vtprd_admin_update_product_meta_info();
          break;
      }  
      return;
  }
  
  
  /* ************************************************
  **   Admin - Publish/Update Rule 
  *************************************************** */
	public function vtprd_admin_update_rule(){
    /* *****************************************************************
         The delete/trash/untrash actions *will sometimes fire save_post*
         and there is a case structure in the save_post function to handle this.
    
          the delete/trash actions are sometimes fired twice, 
               so this can be handled by checking 'did_action'
               
          'publish' action flows through to the bottom     
     ***************************************************************** */
      
      global $post, $vtprd_rules_set;
      if ( !( 'vtprd-rule' == $post->post_type )) {
        return;
      }  
      if (( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
            return; 
      }
     if (isset($_REQUEST['vtprd_nonce']) ) {     //nonce created in vtprd-rules-ui.php  
          $nonce = $_REQUEST['vtprd_nonce'];
          if(!wp_verify_nonce($nonce, 'vtprd-rule-nonce')) { 
            return;
          }
      } 
      if ( !current_user_can( 'edit_posts', 'vtprd-rule' ) ) {
          return;
      }

      
      /* ******************************************
       The 'SAVE_POST' action is fired at odd times during updating.
       When it's fired early, there's no post data available.
       So checking for a blank post id is an effective solution.
      *************************************************** */      
      if ( !( $post->ID > ' ' ) ) { //a blank post id means no data to proces....
        return;
      } 
      //AND if we're here via an action other than a true save, do the action and exit stage left
      $action_type = $_REQUEST['action'];
      if ( in_array($action_type, array('trash', 'untrash', 'delete') ) ) {
        switch( $action_type ) {
            case 'trash':
                $this->vtprd_admin_trash_rule();  
              break; 
            case 'untrash':
                $this->vtprd_admin_untrash_rule();
              break;
            case 'delete':
                $this->vtprd_admin_delete_rule();  
              break;
        }
        return;
      }
      // lets through  $action_type == editpost                
      $vtprd_rule_update = new VTPRD_Rule_update;
  }
   
  
 /* ************************************************
 **   Admin - Delete Rule
 *************************************************** */
	public function vtprd_admin_delete_rule(){
     global $post, $vtprd_rules_set; 
     if ( !( 'vtprd-rule' == $post->post_type ) ) {
      return;
     }        

     if ( !current_user_can( 'delete_posts', 'vtprd-rule' ) )  {
          return;
     }
    
    $vtprd_rule_delete = new VTPRD_Rule_delete;            
    $vtprd_rule_delete->vtprd_delete_rule();
        
    /* NO!! - the purchase history STAYS!
    if(defined('VTPRD_PRO_DIRNAME')) {
      vtprd_delete_lifetime_rule_info();
    }   
     */
  }
  
  
  /* ************************************************
  **   Admin - Trash Rule
  *************************************************** */   
	public function vtprd_admin_trash_rule(){
     global $post, $vtprd_rules_set; 
     if ( !( 'vtprd-rule' == $post->post_type ) ) {
      return;
     }        
  
     if ( !current_user_can( 'delete_posts', 'vtprd-rule' ) )  {
          return;
     }  
     
     if(did_action('trash_post')) {    
         return;
    }
    
    $vtprd_rule_delete = new VTPRD_Rule_delete;            
    $vtprd_rule_delete->vtprd_trash_rule();

  }
  
  
 /* ************************************************
 **   Admin - Untrash Rule
 *************************************************** */   
	public function vtprd_admin_untrash_rule(){
     global $post, $vtprd_rules_set; 
     if ( !( 'vtprd-rule' == $post->post_type ) ) {
      return;
     }        

     if ( !current_user_can( 'delete_posts', 'vtprd-rule' ) )  {
          return;
     }       
    $vtprd_rule_delete = new VTPRD_Rule_delete;            
    $vtprd_rule_delete->vtprd_untrash_rule();
  }
  
  
  /* ************************************************
  **   Admin - Update PRODUCT Meta - include/exclude info
  *      from Meta box added to PRODUCT in rules-ui.php  
  *************************************************** */
	public function vtprd_admin_update_product_meta_info(){
      global $post, $vtprd_rules_set, $vtprd_info;
      if ( !( $vtprd_info['parent_plugin_cpt'] == $post->post_type )) {
        return;
      }  
      if (( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
            return; 
      }

      if ( !current_user_can( 'edit_posts', $vtprd_info['parent_plugin_cpt'] ) ) {
          return;
      }
       //AND if we're here via an action other than a true save, exit stage left
      $action_type = $_REQUEST['action'];
      if ( in_array($action_type, array('trash', 'untrash', 'delete') ) ) {
        return;
      }
      
      /* ******************************************
       The 'SAVE_POST' action is fired at odd times during updating.
       When it's fired early, there's no post data available.
       So checking for a blank post id is an effective solution.
      *************************************************** */      
      if ( !( $post->ID > ' ' ) ) { //a blank post id means no data to proces....
        return;
      } 
      


      $includeOrExclude_option = $_REQUEST['includeOrExclude'];
      switch( $includeOrExclude_option ) {
        case 'includeAll':
        case 'excludeAll':   
            $includeOrExclude_checked_list = null; //initialize to null, as it's used later...
          break;
        case 'includeList':                  
        case 'excludeList':
            $includeOrExclude_checked_list = $_REQUEST['includeOrExclude-checked_list']; //contains list of checked rule post-id"s  v1.0.8.9                                               
          break;
      }

      $vtprd_includeOrExclude = array (
            'includeOrExclude_option'         => $includeOrExclude_option,
            'includeOrExclude_checked_list'   => $includeOrExclude_checked_list
             );
     
      //keep the add meta to retain the unique parameter...
      $vtprd_includeOrExclude_meta  = get_post_meta($post->ID, $vtprd_info['product_meta_key_includeOrExclude'], true);
      if ( $vtprd_includeOrExclude_meta  ) {
        update_post_meta($post->ID, $vtprd_info['product_meta_key_includeOrExclude'], $vtprd_includeOrExclude);
      } else {
        add_post_meta($post->ID, $vtprd_info['product_meta_key_includeOrExclude'], $vtprd_includeOrExclude, true);
      }

      
  }
 

  /* ************************************************
  **   Admin - Activation Hook
  *************************************************** */  
	public function vtprd_activation_hook() {
    global $wp_version, $vtprd_setup_options;
    //the options are added at admin_init time by the setup_options.php as soon as plugin is activated!!!
        
    $this->vtprd_create_discount_log_tables();

    $this->vtprd_maybe_add_wholesale_role(); //v1.0.9.0

		$earliest_allowed_wp_version = 3.3;
    if( (version_compare(strval($earliest_allowed_wp_version), strval($wp_version), '>') == 1) ) {   //'==1' = 2nd value is lower  
        $message  =  '<strong>' . __('Looks like you\'re running an older version of WordPress, you need to be running at least WordPress 3.3 to use the Varktech Pricing Deals plugin.' , 'vtprd') . '</strong>' ;
        $message .=  '<br>' . __('Current Wordpress Version = ' , 'vtprd')  . $wp_version ;
        $admin_notices = '<div id="message" class="error fade" style="background-color: #FFEBE8 !important;"><p>' . $message . ' </p></div>';
        add_action( 'admin_notices', create_function( '', "echo '$admin_notices';" ) );
        return;
		}
   
            
   if (version_compare(PHP_VERSION, VTPRD_EARLIEST_ALLOWED_PHP_VERSION) < 0) {    //'<0' = 1st value is lower  
        $message  =  '<strong>' . __('Looks like you\'re running an older version of PHP.   - your PHP version = ' , 'vtprd') .PHP_VERSION. '</strong>' ;
        $message .=  '<br>' . __('You need to be running **at least PHP version 5** to use this plugin. Please contact your host and request an upgrade to PHP 5+ .  Once that has been installed, you can activate this plugin.' , 'vtprd');
        $admin_notices = '<div id="message" class="error fade" style="background-color: #FFEBE8 !important;"><p>' . $message . ' </p></div>';
        add_action( 'admin_notices', create_function( '', "echo '$admin_notices';" ) );
        return;      
      
		}

    
    if(defined('WOOCOMMERCE_VERSION') && (VTPRD_PARENT_PLUGIN_NAME == 'WooCommerce')) { 
      $new_version =      VTPRD_EARLIEST_ALLOWED_PARENT_VERSION;
      $current_version =  WOOCOMMERCE_VERSION;
      if( (version_compare(strval($new_version), strval($current_version), '>') == 1) ) {   //'==1' = 2nd value is lower 
        $message  =  '<strong>' . __('Looks like you\'re running an older version of WooCommerce. You need to be running at least ** WooCommerce 2.0 **, to use the Varktech Pricing Deals plugin' , 'vtprd') . '</strong>' ;
        $message .=  '<br>' . __('Your current WooCommerce version = ' , 'vtprd') .WOOCOMMERCE_VERSION;
        $admin_notices = '<div id="message" class="error fade" style="background-color: #FFEBE8 !important;"><p>' . $message . ' </p></div>';
        add_action( 'admin_notices', create_function( '', "echo '$admin_notices';" ) );
        return;         
  		}
    }   else 
    if (VTPRD_PARENT_PLUGIN_NAME == 'WooCommerce') {
        $message  =  '<strong>' . __('Varktech Pricing Deals for WooCommerce requires that WooCommerce be installed and activated. ' , 'vtprd') . '</strong>' ;
        $message .=  '<br>' . __('It looks like WooCommerce is either not installed, or not activated. ' , 'vtprd');
        $admin_notices = '<div id="message" class="error fade" style="background-color: #FFEBE8 !important;"><p>' . $message . ' </p></div>';
        add_action( 'admin_notices', create_function( '', "echo '$admin_notices';" ) );
        return;         
    }

    return; 
  }

   //v1.0.7.1 begin                          
   public function vtprd_admin_notice_version_mismatch() {
      $message  =  '<strong>' . __('Please also update plugin: ' , 'vtprd') . ' &nbsp;&nbsp;'  .VTPRD_PRO_PLUGIN_NAME . '</strong>' ;
      $message .=  '<br>&nbsp;&nbsp;&bull;&nbsp;&nbsp;' . __('Your Pro Version = ' , 'vtprd') .VTPRD_PRO_VERSION. ' &nbsp;&nbsp;' . __(' The Minimum Required Pro Version = ' , 'vtprd') .VTPRD_MINIMUM_PRO_VERSION ;      
      $message .=  '<br>&nbsp;&nbsp;&bull;&nbsp;&nbsp;' . __('Please delete the old Pro plugin from your installation (no rules will be affected).'  , 'vtprd');
      $message .=  '<br>&nbsp;&nbsp;&bull;&nbsp;&nbsp;' . __('Go to ', 'vtprd');
      $message .=  '<a target="_blank" href="http://www.varktech.com/download-pro-plugins/">Varktech Downloads</a>';
      $message .=   __(', download and install the newest <strong>'  , 'vtprd') .VTPRD_PRO_PLUGIN_NAME. '</strong>' ;
      
      $admin_notices = '<div id="message" class="error fade" style="background-color: #FFEBE8 !important;"><p>' . $message . ' </p></div>';
      echo $admin_notices;
      return;    
  }   
   //v1.0.7.1 end  

   public function vtprd_admin_notice_coupon_enable_required() {
      $message  =  '<strong>' . __('In order for the "Pricing Deals" plugin to function successfully, the Woo Coupons Setting must be on, and it is currently off.' , 'vtprd') . '</strong>' ;
      $message .=  '<br><br>' . __('Please go to the Woocommerce/Settings page.  Under the "Checkout" tab, check the box next to "Enable the use of coupons" and click on the "Save Changes" button.'  , 'vtprd');
      $admin_notices = '<div id="message" class="error fade" style="background-color: #FFEBE8 !important;"><p>' . $message . ' </p></div>';
      echo $admin_notices;
      return;    
  } 
    
    
  /* ************************************************
  **   Admin - **Uninstall** Hook and cleanup
  *************************************************** */ 
	public function vtprd_uninstall_hook() {
      
      if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
      	return;
        //exit ();
      }
  
      delete_option('vtprd_setup_options');
      $vtprd_nuke = new VTPRD_Rule_delete;            
      $vtprd_nuke->vtprd_nuke_all_rules();
      $vtprd_nuke->vtprd_nuke_all_rule_cats();
      
  }
  
   
    //Add Custom Links to PLUGIN page action links                     ///wp-admin/edit.php?post_type=vtmam-rule&page=vtmam_setup_options_page
  public function vtprd_custom_action_links( $links ) {                 
		$plugin_links = array(
			'<a href="' . admin_url( 'edit.php?post_type=vtprd-rule&page=vtprd_setup_options_page' ) . '">' . __( 'Settings', 'vtprd' ) . '</a>',
			'<a href="http://www.varktech.com">' . __( 'Docs', 'vtprd' ) . '</a>'
		);
		return array_merge( $plugin_links, $links );
	}



	public function vtprd_create_discount_log_tables() {
    global $wpdb;
    //Cart Audit Trail Tables
  	
    $wpdb->hide_errors();    
  	$collate = '';  
    if ( $wpdb->has_cap( 'collation' ) ) {  //mwn04142014
  		if( ! empty($wpdb->charset ) ) $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
  		if( ! empty($wpdb->collate ) ) $collate .= " COLLATE $wpdb->collate";
    }
     
      
  //  $is_this_purchLog = $wpdb->get_var("SHOW TABLES LIKE `".VTPRD_PURCHASE_LOG."` ");
    $table_name =  VTPRD_PURCHASE_LOG;
    $is_this_purchLog = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" );
    if ( $is_this_purchLog  == VTPRD_PURCHASE_LOG) {
      return;
    }

     
    $sql = "
        CREATE TABLE  `".VTPRD_PURCHASE_LOG."` (
              id bigint NOT NULL AUTO_INCREMENT,
              cart_parent_purchase_log_id bigint,
              purchaser_name VARCHAR(50), 
              purchaser_ip_address VARCHAR(50),                
              purchase_date DATE NULL,
              cart_total_discount_currency DECIMAL(11,2),      
              ruleset_object TEXT,
              cart_object TEXT,
          KEY id (id, cart_parent_purchase_log_id)
        ) $collate ;      
        ";
 
     $this->vtprd_create_table( $sql );
     
    $sql = "
        CREATE TABLE  `".VTPRD_PURCHASE_LOG_PRODUCT."` (
              id bigint NOT NULL AUTO_INCREMENT,
              purchase_log_row_id bigint,
              product_id bigint,
              product_title VARCHAR(100),
              cart_parent_purchase_log_id bigint,
              product_orig_unit_price   DECIMAL(11,2),     
              product_total_discount_units   DECIMAL(11,2),
              product_total_discount_currency DECIMAL(11,2),
              product_total_discount_percent DECIMAL(11,2),
          KEY id (id, purchase_log_row_id, product_id)
        ) $collate ;      
        ";
 
     $this->vtprd_create_table( $sql );
     
    $sql = "
        CREATE TABLE  `".VTPRD_PURCHASE_LOG_PRODUCT_RULE."` (
              id bigint NOT NULL AUTO_INCREMENT,
              purchase_log_product_row_id bigint,
              product_id bigint,
			  rule_id bigint,
              cart_parent_purchase_log_id bigint,
              product_rule_discount_units   DECIMAL(11,2),
              product_rule_discount_dollars DECIMAL(11,2),
              product_rule_discount_percent DECIMAL(11,2),
          KEY id (id, purchase_log_product_row_id, rule_id)
        ) $collate ;      
        ";
 
     $this->vtprd_create_table( $sql );



  }
  
	public function vtprd_create_table( $sql ) {   
      global $wpdb;
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');	        
      dbDelta($sql);
      return; 
   } 
                            
                            
 
  //****************************************
  //v1.0.7.4 new function
  //v1.0.8.8 refactored for new 'Wholesale Tax Free' role, buy_tax_free role capability
  //  adds in default 'Wholesale Buyer' + new 'Wholesale Tax Free'  role at iadmin time  
  //v1.0.9.0 moved here from functions.php, so it only executes on insall...
  //****************************************
  Public function vtprd_maybe_add_wholesale_role(){ 
		global $wp_roles;
	
		if ( class_exists( 'WP_Roles' ) ) {
      if ( !isset( $wp_roles ) ) { 
			   $wp_roles = new WP_Roles();
      }
    }

		$capabilities = array( 
			'read' => true,
			'edit_posts' => false,
			'delete_posts' => false,
		); 
     
    $wholesale_buyer_role_name    =  __('Wholesale Buyer' , 'vtprd');
    $wholesale_tax_free_role_name =  __('Wholesale Tax Free' , 'vtprd');
  

		if ( is_object( $wp_roles ) ) { 

      If ( !get_role( $wholesale_buyer_role_name ) ) {
    			add_role ('wholesale_buyer', $wholesale_buyer_role_name, $capabilities );    
    			$role = get_role( 'wholesale_buyer' ); 
    			$role->add_cap( 'buy_wholesale' );
      }

      If ( !get_role(  $wholesale_tax_free_role_name ) ) {
    			add_role ('wholesale_tax_free',  $wholesale_tax_free_role_name, $capabilities );    
    			$role = get_role( 'wholesale_tax_free' ); 
    			$role->add_cap( 'buy_tax_free' );
      }

		}
       
    return;
  }  


  
} //end class
$vtprd_controller = new VTPRD_Controller;
     
//has to be out here, accessing the plugin instance
if (is_admin()){
  register_activation_hook(__FILE__, array($vtprd_controller, 'vtprd_activation_hook'));
//mwn0405
//  register_uninstall_hook (__FILE__, array($vtprd_controller, 'vtprd_uninstall_hook'));
}

  
