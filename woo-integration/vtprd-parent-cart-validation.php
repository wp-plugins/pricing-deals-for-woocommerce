<?php

class VTPRD_Parent_Cart_Validation {
	
	public function __construct(){

    //*********************************************************************************************************
    /*
        There are a number of separate functions processed here.
        
        (1) Catalog discount on a single product
            - run at catalog display time against all display rules
            - data is stored in a product_id session variable for later use 
        (2) shortcode on-demand theme marketing messages
        (3) add-to-cart realtime discount computations
            - uses any display discounts if found
            - saves the current discount computation to session variable
            - adds the discount amount to the discount bucket, with the realtime-added couone type of pricing_deal_discount 
        (4) Mini-cart discount printing routine
        (5) checkout discount printing routine
        (6) discount amount prints/computes automatically since added to discount bucket...
    */
    //*********************************************************************************************************
    
    //---------------------------- 
    //CATALOG DISPLAY Filters / Actions
    //---------------------------- 
    
    //***************************************************
    //price request processing at catalog product display time
    //***************************************************                                                                           
    //*********************************************************************************************************
    /*
        PRICE FILTER must precede all other info requests, otherwise the info will not be there
        All other info requests (yousave, msg etc) MUST follow sequentially after the price filter request.
        AND the price filter request only is exposed at price display time, so the only way the info is available
        is DIRECTLY AFTER price display time.  Otherwise, as variations don't have identifiers beyond the PARENT \
        product_id in the filter request, multiple variation requests would become hopelessly muddled.
        
        These two filters are called *many* times in wp-e-commerce when a single product is priced.  unfortunately.
        2nd-nth path length is as short as possible.  
    */
    //*********************************************************************************************************
    /*  +++++++++++++++++++++++++++++++++++++++++++++++
     as/of  3.8.11:  readme.txt(194): * Change: wpsc_the_variation_price() output is now filtered through wpsc_do_convert_price filter. 
      which changes some things... 
    */
  
  /*
    if( (version_compare(strval('3.8.11'), strval(WPSC_VERSION), '>') == 1) ) {   //'==1' = 2nd value is lower 
      //if this version predates '3.8.11' ... 
      add_filter( 'wpsc_price',             array(&$this, 'vtprd_get_product_catalog_price_new'), 10, 2 );
      add_filter( 'wpsc_do_convert_price',  array(&$this, 'vtprd_get_product_catalog_price_old'), 10, 1 );     
    } else {        
      add_filter( 'wpsc_price',                array(&$this, 'vtprd_get_product_catalog_price_new'), 10, 2 );    
      //!is_admin needed after WPEC 3.8.10 ...
      if (!is_admin()){  //convert_price is now called in wp-admin by wpec! Our "add_filter" can't be run in wp-admin....
        add_filter( 'wpsc_do_convert_price',   array(&$this, 'vtprd_get_product_catalog_price_do_convert'), 10, 3 ); //now uses up to 3 arguments
      } 
    }
    */

   
    //DISPLAY RULE INITIAL Price check - Catalog pricing filters/actions => returns HTML PRICING for display
    //********************************************************************************************************************
    
    //**********======================================================================================
    //NEED both these filters and the woocommerce_get_price filter to support both 
    //  standard products (priced in woocommerce_get_price in the catalog display)
    //      and 
    //  variation products (priced in one a variaty of the _html filters in AJAX)
    //**********======================================================================================
        
    add_filter('woocommerce_grouped_price_html',          array(&$this, 'vtprd_maybe_grouped_price_html'), 10, 2);
 
    add_filter('woocommerce_variable_sale_price_html',    array(&$this, 'vtprd_maybe_variable_sale_price_html'), 10, 2);

    add_filter('woocommerce_variable_price_html',         array(&$this, 'vtprd_maybe_catalog_price_html'), 10, 2);
    
    //normal get price
    add_filter('woocommerce_variation_price_html',        array(&$this, 'vtprd_maybe_catalog_price_html'), 10, 2);
    //normal get price
    add_filter('woocommerce_variation_sale_price_html',   array(&$this, 'vtprd_maybe_catalog_price_html'), 10, 2);
        
    add_filter('woocommerce_sale_price_html',             array(&$this, 'vtprd_maybe_catalog_price_html'), 10, 2);
    
    add_filter('woocommerce_price_html',                  array(&$this, 'vtprd_maybe_catalog_price_html'), 10, 2);
 
    add_filter('woocommerce_empty_price_html',            array(&$this, 'vtprd_maybe_catalog_price_html'), 10, 2);

    $current_version =  WOOCOMMERCE_VERSION;
    if( (version_compare(strval('2.1.0'), strval($current_version), '>') == 1) ) {   //'==1' = 2nd value is lower     
      add_filter('woocommerce_cart_item_price_html',        array(&$this, 'vtprd_maybe_cart_item_price_html'), 10, 3);
    } else {
      add_filter('woocommerce_cart_item_price',             array(&$this, 'vtprd_maybe_cart_item_price_html'), 10, 3);
    }

    // =====================++++++++++
    add_filter('woocommerce_get_price',                   array(&$this, 'vtprd_maybe_get_price'), 10, 2);
    // =====================++++++++++
    
    //********************************************************************************************************************

/*+++  TEST THIS LATER!!!!!!!!!!!
		//belt and suspenders
    add_filter( 'woocommerce_empty_price_html',           array(&$this, 'vtprd_maybe_catalog_price_html' ), 999, 2 );
    add_filter( 'woocommerce_variable_empty_price_html',  array(&$this, 'vtprd_maybe_catalog_price_html' ), 999, 2 );
+++ */      
        
    //********************************************************************************************************************
    /*
    	FROM WOO in  classes/abstracts/abstract-wc-product.php
      function get_price() {
    		return apply_filters( 'woocommerce_get_price', $this->price, $this );
    	}
      FOR DISPLAY RULE DISCOUNTS
      once the display rule discount has been determined in the display rule initial price check ABOVE, here's where we get
      that display rule discount and supply it to the get_price for all other functions...
    */

    //********************************************************************************************************************     


    /****************************************************
      Template Tag Actions 
        all of these must be executed in the loop, or after the product post has been obtained...
           Usage:  (only in the loop or after product post has been procured):
       
       WITHIN THE LOOP: (in theme files...)      
              < ?php echo do_action('vtprd_show_list_price_amt_action'); ? >
        For example
          in wp-e-commerce/wpsc-theme/wpsc-single_product.php   
      
       OUTSIDE THE LOOP:       (Where $product_id = the post_ID of the product)
              < ?php echo do_action('vtprd_show_list_price_amt_action', $product_id); ? >
     
       
       ======================================================================================
       IN WPSC, the price action must always occur before any of the Pricing Deal actions 
       ======================================================================================
       If a pricing deal action needs to happen before the theme executes the product price action,
          the following line must be placed BEFORE the FIRST Pricing deal action:
              IN the loop:  wpsc_calculate_price(wpsc_the_product_id());
       
       This ensures that the Pricing deal info is prepared for the Pricing Deal actions
             
    //***************************************************  */                         
 
    // These 3 actions work with WPEC prev 3.8.9 ...
//    add_action( 'vtprd_show_product_list_price_action',                    'vtprd_show_product_list_price', 10, 1 ); 
//    add_action( 'vtprd_show_product_realtime_discount_full_msgs_action',   'vtprd_show_product_realtime_discount_full_msgs', 10, 1 );
//    add_action( 'vtprd_show_product_you_save_action',                      'vtprd_show_product_you_save', 10, 1 ); //number formatted 
   
    /*** + + + + + + + + + + + + + + 
        Full Store Messages SHORTCODES  in  parent-functions.php  (the shortcodes have lots of options...): 
           STORE CATEGORY MESSAGES SHORTCODE    [vtprd_pricing_deal_category_msgs]
           WHOLESTORE MESSAGES SHORTCODE        [vtprd_pricing_deal_store_msgs] 
        Template USE: 
          < ?php echo do_shortcode('vtprd_pricing_deal_store_msgs'); ? >
    *** + + + + + + + + + + + + + + */

    //-END- CATALOG DISPLAY Filters / Actions

    
    
    //---------------------------- 
    //CART AND CHECKOUT Actions
    //----------------------------  
    
    /*  =============+                                    
    *  This action is done here, to facilitate the auto addition/removal of free items inserted into the cart in a BOGO free situation 
    *  the 'init' action does the actual computation                    
       =============+                                    */
        /*
          Priority of 99 to delay add_action execution.  Works normally on the 1st
          time through, and on any page refreshes.  The action kicks in 1st time on the page, and
          we're already on the shopping cart page and change to quantity happens.  The
          priority delays us in the exec sequence until after the quantity change has
          occurred, so we pick up the correct altered state.
          
          wpsc's own quantity change using:
               if ( isset( $_REQUEST['wpsc_update_quantity'] ) && ($_REQUEST['wpsc_update_quantity'] == 'true') ) {
        	add_action( 'init', 'wpsc_update_item_quantity' );
       */

    
//----------------------------------------------
//CAUSING JS FAILURE DURING CHECKOUT!!!!!!!!!!    
//
//    add_action( 'woocommerce_cart_emptied'       , array(&$this, 'vtprd_ajax_empty_cart') );
//----------------------------------------------

    //do_action( 'woocommerce_add_to_cart', $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data );
    //  the action is before calculate_totals()  execution in class-wc-cart.php              
    
    //ADD TO CART/UPD CART

//DON'T NEED THIS ==> PICKED UP BY 'woocommerce_cart_updated' <==     add_action( 'woocommerce_add_to_cart' ,                   array(&$this, 'vtprd_ajax_add_to_cart_hook'), 10, 6 );
   //MAYBE++ add_action( 'woocommerce_ajax_added_to_cart' ,              array(&$this, 'vtprd_ajax_add_to_cart_hook2'), 10, 1 );
        
    //'woocommerce_cart_updated' RUNS EVERY TIME THE CART OR CHECKOUT PAGE DISPLAYS!!!!!!!!!!!!!
    add_action( 'woocommerce_cart_updated',                   array(&$this, 'vtprd_cart_updated') );   //AFTER cart update completed, all totals computed
    
    //this runs BEFORE the qty is zeroed, not much use...
    //add_action( 'woocommerce_before_cart_item_quantity_zero', array(&$this, 'vtprd_test_quantity_zero'), 10,1 );     //cart_item_removed

    //*************************
    //COUPON PROCESSING
    //*************************
    //add or remove Pricing Deals 'dummy' fixed_cart coupon
    //   NEED BOTH to pick up going to view cart and going directly to checkout.  Exits quickly if already done.
    add_filter( 'woocommerce_before_cart_table',     array(&$this, 'vtprd_woo_maybe_add_remove_discount_cart_coupon'), 10);
    add_filter( 'woocommerce_checkout_init',         array(&$this, 'vtprd_woo_maybe_add_remove_discount_cart_coupon'), 10);
      
    //change the value of the Pricing Deals 'dummy' coupon instance to the Pricing Deals discount amount
    add_filter( 'woocommerce_get_shop_coupon_data',  array(&$this, 'vtprd_woo_maybe_load_discount_amount_to_coupon'), 10,2);
    //*************************                                                                               
 
   /*  =============+++++++++++++++++++++++++++++++++++++++++++++++++++++++++    */                       
    /*
    CHECKOUT PROCESS:
      - prep the counts at checkout page entry time
      - after each checkout row print, check to see if we're on the last one
          if so, compute and print discounts: both cart and display rules are reapplied to current unit pricing
      - at before_shipping_of_shopping_cart time, add discounts into coupon totals
      - post processing, store records in db    
    */

    //*************************************************
    // Apply discount to Discount total
    //*************************************************    
   //return apply_filters( 'woocommerce_get_discounted_price', $price, $values, $this );
   //add_filter( 'woocommerce_get_discounted_price',  array(&$this, 'vtprd_maybe_add_dscount_to_coupon_totals'), 10,3);
   
    //*************************************************
    // Print Discounts in Widget (after cart subtotal!!!)
    //*************************************************
    //  in templates/cart/mini-cart.php (exists in 2.0 ...)
    add_action( 'woocommerce_widget_shopping_cart_before_buttons', array(&$this, 'vtprd_maybe_print_widget_discount'), 10, 1 ); 
      
    //*************************************************
    // Print Discounts at Checkout time
    //*************************************************        
    //In woocommerce/templates/cart/cart'        
   // add_action( 'woocommerce_cart_contents', array(&$this, 'vtprd_maybe_print_checkout_discount'), 10, 1 );
//*************************************************     
    //IF before cart
      //add_action( 'woocommerce_before_cart',      array(&$this, 'vtprd_maybe_print_checkout_discount'), 10, 1 );
    //else
      add_action( 'woocommerce_after_cart_table', array(&$this, 'vtprd_maybe_print_checkout_discount'), 10, 1 );
//************************************************* 

    //Reapply rules only if an error occurred during processing regarding lifetime rule limits...         
    //the form validation filter executes ONLY at click-to-pay time                                                                      
    add_filter( 'woocommerce_before_checkout_process', array(&$this, 'vtprd_woo_validate_order'), 1);   
  

    //*************************************************
    // Post-Purchase
    //*************************************************       
    //In classes/class-wc-checkout.php  function process_checkout() =>  just before the 'thanks' Order Acknowledgement screen
    add_action('woocommerce_checkout_order_processed', array( &$this, 'vtprd_post_purchase_maybe_save_log_info' ), 10, 2); 

    //Order Acknowledgment Email     
    //add discount reporting to customer email USING LOG INFO...
    //  $return = apply_filters( 'woocommerce_email_order_items_table', ob_get_clean(), $this );
    //      ob_get_clean() = the whole output buffer 
    //USING THIS filter in this way, puts discounts within the existing products table, after products are shown, but before the close of the table...     
    add_filter('woocommerce_email_order_items_table', array( &$this, 'vtprd_post_purchase_maybe_email' ), 10,2);

    
    // PRIOR to WOO version ++2.13++ - won't work - as this filter only does not have $order_info (2nd variable) in prior versions
    
    //Order Acknowledgement screen
    //add discount reporting to thankyou USING LOG INFO...
    //DON'T USE ANYMORE  add_filter('woocommerce_order_details_after_order_table', array( &$this, 'vtprd_post_purchase_maybe_thankyou' ), 10,1);
    
    //do_action( 'woocommerce_thankyou', $order->id );  IS EXECUTED in WOO to place order info on thankyou page.   Put our stuff in front of thankyou.
    add_filter('woocommerce_thankyou', array( &$this, 'vtprd_post_purchase_maybe_before_thankyou' ), -1,1); //put our stuff in front of thankyou
    
    //last filter/hook which uses the session variables, also nukes the session vars...
//    add_filter('woocommerce_checkout_order_processed', array( &$this, 'vtprd_post_purchase_maybe_purchase_log' ), 10,2);   

    //lifetime tables cleanup on log delete
    add_action('wpsc_purchase_log_before_delete',    array( &$this, 'vtprd_pro_lifetime_log_roll_out' ), 10, 1); 
    add_action('wpsc_sales_log_process_bulk_action', array( &$this, 'vtprd_pro_lifetime_bulk_log_roll_out' ), 10, 1); 
 
     
    /* OLD OLD OLD
    add_action('woocommerce_checkout_order_processed', array( &$this, 'vtprd_post_purchase_maybe_save_log_info' ), 10, 1);  //1st priority


    //add discount reporting to transaction results and customer email...        
    add_filter('woocommerce_email_order_items_table', array( &$this, 'vtprd_post_purchase_maybe_email' ), 10,1);
    
    //last filter/hook which uses the session variables, also nukes the session vars...
    add_filter('woocommerce_checkout_order_processed', array( &$this, 'vtprd_post_purchase_maybe_purchase_log' ), 10,2);   

    //lifetime tables cleanup on log delete
    add_action('wpsc_purchase_log_before_delete',    array( &$this, 'vtprd_pro_lifetime_log_roll_out' ), 10, 1); 
    add_action('wpsc_sales_log_process_bulk_action', array( &$this, 'vtprd_pro_lifetime_bulk_log_roll_out' ), 10, 1); 
    
    */
  /*
             add_action('woocommerce_order_status_completed', array(&$this, 'new_order'));
            add_action('woocommerce_order_status_processing', array(&$this, 'new_order'));

            // @since 2.1.4
            add_action('woocommerce_order_status_pending', array(&$this, 'new_order'));
            add_action('woocommerce_order_status_failed', array(&$this, 'new_order'));
            add_action('woocommerce_order_status_on-hold', array(&$this, 'new_order'));
            add_action('woocommerce_order_status_refunded', array(&$this, 'new_order'));
            add_action('woocommerce_order_status_cancelled', array(&$this, 'new_order'));
 
  
  */  
     
    
	} //end constructor
  
  

  //the form validation filter executes ONLY at click-to-pay time, just to access the global variables!!!!!!!!! 
	public function vtprd_woo_validate_order(){
    global $vtprd_rules_set, $vtprd_cart, $vtprd_setup_options, $vtprd_info, $woocommerce;
        
    //Open Session Variable, get rules_set and cart if not there...
    $data_chain = $this->vtprd_get_data_chain();

    // switch from run-through at checkout time 
    if ( (defined('VTPRD_PRO_DIRNAME')) && ($vtprd_setup_options['use_lifetime_max_limits'] == 'yes') ) {    
   /* 
    if ( $vtmam_cart->error_messages_processed == 'yes' ) {  
      $woocommerce->add_error(  __('Purchase error found.', 'vtmam') );  //supplies an error msg and prevents payment from completing 
      return;
    }
    */
      
      if ( ($vtprd_cart->lifetime_limit_applies_to_cart == 'yes') && ( sizeof($vtprd_cart->error_messages) == 0 ) ) {   //error msg > 0 = 2nd time through HERE, customer has blessed the reduction
        //reapply rules to catch lifetime rule logic using email and address info...
        
        $total_discount_1st_runthrough = $vtprd_cart->yousave_cart_total_amt;
        $vtprd_info['checkout_validation_in_process'] = 'yes';
        
        $vtprd_apply_rules = new VTPRD_Apply_Rules;   
   
        //ERROR Message Path
        if ( ( sizeof($vtprd_cart->error_messages) > 0 ) && 
             ($vtprd_cart->yousave_cart_total_amt < $total_discount_1st_runthrough) ) {   //2ND runthrough found additional lifetime limitations, need to alert customer   
            //insert error messages into checkout page
            add_action('wp_head', array(&$this, 'vtprd_display_rule_error_msg_at_checkout') );  //JS to insert error msgs      
            
            /*  turn on the messages processed switch
                otherwise errors are processed and displayed multiple times when the
                wpsc_checkout_form_validation filter finds an error (causes a loop around, 3x error result...) 
            */
            $vtprd_cart->error_messages_processed = 'yes'; 
            $woocommerce->add_error(  __('Purchase error found.', 'vtprd') );  //supplies an error msg and prevents payment from completing 
   
            
            /*  *********************************************************************
              Mark checkout as having ++failed edits++, and can't progress to Payment Gateway. 
              This works only with the filter 'wpsc_checkout_form_validation', which is activated on submit of
              "payment" button. 
            *************************************************************************  */
            $is_valid = false;
      
        } 

        /*  *************************************************
         Load this info into session variables, to begin the 
         DATA CHAIN - global to session back to global
         global to session - in vtprd_process_discount
         session to global - in vtprd_woo_validate_order
         access global     - in vtprd_post_purchase_maybe_save_log_info   
        *************************************************   */
        $contents_total   =   $woocommerce->cart->cart_contents_total;
        $applied_coupons  =   $woocommerce->cart->applied_coupons;
        $data_chain = array();
        $data_chain[] = $vtprd_rules_set;
        $data_chain[] = $vtprd_cart;
        $data_chain[] =  $contents_total;
        $data_chain[] =  $applied_coupons;
        $_SESSION['data_chain'] = serialize($data_chain);              
      } else {
      
        //Get the screen data...
        vtprd_get_purchaser_info_from_screen();       
      }
    }
    return;   
  } 	
 
  
  /* ************************************************
  **  Price Filter -  Get display info for single product  & return discounted price
  *      (NEVER FORMATTED)
  *      
  *These two filters are called *many* times in wp-e-commerce when a single product is priced.  unfortunately.
        2nd-nth path length is as short as possible.        
  *************************************************** */
	public function vtprd_get_product_catalog_price_old($price, $product_id = null){     //passed data from wpsc_price

 
    global $post, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule;

   // **********************************
   /*   This is a Catalog-Only call
   // **********************************
   *    Every product call is handled, in order to record the all-important
   *      unit-current-price  information.  This info is used for all rule types, to help
   *      determine the 'yousave' information. 
   *      Possible call types are as follows:
   *        (1) Call for Theme message info, before Price call
   *        (2) Price call
   *        (3) Call for Theme message info, after Price call
   *        (4) Call for yousave and other info
   *        
   *    Message call can Precede the Price call
   *    Yousave call CANNOT Precede the Price Call => send back error msg to theme                     
   *                
   */
   
   /*
   //  only applies if one rule set to $rule_execution_type_selected == 'display'.  
   //     Carried in a separate option, set into info in parent-definitions, as this could be called many times ...     
    if ($vtprd_info['ruleset_has_a_display_rule'] == 'no') {
      return $price;
    }
    */
    if ($post->ID > ' ' ) {
      $product_id = $post->ID;
    }
    if( get_post_field( 'post_parent', $product_id ) ) {
       $product_id = get_post_field( 'post_parent', $product_id );
    }   

    $vtprd_info['current_processing_request'] = 'display'; 
           
    //This is the only time $price is sent to this routine
    vtprd_get_product_session_info($product_id, $price);

    //price is ALWAYS returned with NO formatting, as it is called during processing, not at display time
    if ($vtprd_info['product_session_info']['product_discount_price'] > 0) {
      $price = $vtprd_info['product_session_info']['product_discount_price'];
    } 
    
    return $price;     
  } 
   
  /* ************************************************
  **  Price Filter -  Get display info for single product  & return discounted price
  *      (NEVER FORMATTED)
  *      
  *These two filters are called *many* times in wp-e-commerce when a single product is priced.  unfortunately.
        2nd-nth path length is as short as possible.        
  *************************************************** */
	public function vtprd_get_product_catalog_price_new($price, $product_id = null){     //passed data from wpsc_price
    global $post, $vtprd_info, $vtprd_setup_options;

 //echo '001a in catalog_price_new' .'<br>';
//			 wp_die( __('<strong>DIED in vtprd_get_product_catalog_price_new.</strong>', 'vtprd'), __('VT Pricing Deals not compatible - WP', 'vtprd'), array('back_link' => true));
  /* ************************************************
  *
  * Although wpsc_price is activated all over the place,
  * wpsc_do_convert_price takes precedence when the Price is
  * displayed on Screen Display.  
  * 
  * wpsc_price runs ALONE when an ajax call is made:
  *     add_action( 'wp_ajax_update_product_price'       , 'wpsc_update_product_price' );
  *     add_action( 'wp_ajax_nopriv_update_product_price', 'wpsc_update_product_price' );
  *     
  * So in this case, the session variable will 
  *   already have been stored during the Screen Display.
  *   
  * In the grand scheme of Screen Display,  
  *   wpsc_do_convert_price is done 1st, so if there is 
  *   discount info for a single product/variation,
  *   it will already be there by the time wpsc_price is
  *   executed
  *     
  * So when Ajax runs, all the data will be there  
  *                                
  *************************************************** */
    $product_id_passed_into_function = $product_id;

    
    //if we are processing a variation, always get and pass the PARENT ID
    if ($post->ID > ' ' ) {
      $product_id = $post->ID;
    }
    if( get_post_field( 'post_parent', $product_id ) ) {
       $product_id = get_post_field( 'post_parent', $product_id );
    }  
    
    vtprd_get_product_session_info($product_id, $price);

    //were we passed a Variation ID to start with??
    if (($product_id_passed_into_function != $product_id ) && ($product_id_passed_into_function > ' ') ) {
      vtprd_recompute_discount_price($product_id_passed_into_function, $price);  
    }

/*
if ($product_id == '10') { 
echo 'product_session_info= <pre>'.print_r($vtprd_info['product_session_info'], true).'</pre>' ;  
global $vtprd_cart, $vtprd_rules_set;
echo '$vtprd_cart = <pre>'.print_r($vtprd_cart, true).'</pre>' ;
echo '$vtprd_rules_set = <pre>'.print_r($vtprd_rules_set, true).'</pre>' ;
wp_die( __('<strong>Looks like you\'re running an older version of WordPress, you need to be running at least WordPress 3.3 to use the Varktech Pricing Deals plugin.</strong>', 'vtprd'), __('VT Pricing Deals not compatible - WP', 'vtprd'), array('back_link' => true));
}
*/ 
    if ($vtprd_info['product_session_info']['product_discount_price'] > 0) {
      $price = $vtprd_info['product_session_info']['product_discount_price'];
    }     
    
    return $price;

       
  } 


	public function vtprd_maybe_grouped_price_html($price_html, $product_info){   
    global $post, $vtprd_info, $vtprd_setup_options; 
    
    //in place of is_admin, which doesn't work in AJAX...
     if ( function_exists( 'get_current_screen' ) ) {  // get_current_screen ONLY exists in ADMIN!!!   
       if ($post->post_type == 'product'  ) {    //in admin, don't run this on the PRODUCT screen!!
         return $price_html;
       }
     }
    
    //***************************************************
    //FROM  woocommerce_grouped_price_html
    /*  is this a variation price display 'From $xxxx'
     handles  woocommerce_grouped_price_html
     in woocommerce/classes/class-wc-product-grouped.php
          function get_price_html( $price = '' ) 
    */
    //***************************************************
    $from = strstr($price_html, 'From') !== false ? ' From ' : ' ';
    if ($from) {
    		$child_prices = array();
    		$all_children = $product_info->get_children();
        foreach ( $all_children as $child_id ) {
    			//changed to use the $child_id as key
          $child_prices[$child_id] = get_post_meta( $child_id, '_price', true );
                                                             //*********          
        }        
    		
        if (count($child_prices) == 0) {
          return $price_html;
        }
        
        $min_price = '';
        $min_price_id;
        //find min
        foreach ($child_prices as $key => $child_price) {
          if ( ($child_price < $min_price) || ($min_price = '') ) {
            $min_price =  $child_price;
            $min_price_id = $key;
          }        
        }
        
        //if no min price found, nothing to do
        if (!$min_price_id) {
           return $price_html;
        }
        
      /*  
        $child_prices = array_unique( $child_prices );
    		if ( ! empty( $child_prices ) ) {
    			$min_price = min( $child_prices );
    		} else {
    			//$min_price = '';
          //if min_price does not exist, we have NOTHING to proces.  return original value.
          return $price_html;
    		}
    	//	$price .= woocommerce_price( $min_price ); 
      $price =  $min_price;  */  
    }
    //END  woocommerce_grouped_price_html handling
    //***************************************************  
  
    //$product_id = isset($product_info->variation_id) ? $product_info->variation_id : $product_info->id;
    
    // $price = $min_price;
    $product_id = $min_price_id;
    $vtprd_info['current_processing_request'] = 'display'; 
    $price = $min_price;
    
    vtprd_get_product_session_info($product_id, $price);

/*
    //were we passed a Variation ID to start with??
    if (($product_id_passed_into_function != $product_id ) && ($product_id_passed_into_function > ' ') ) {

 //echo '001a above recompute_discount price' .'<br>';      
      vtprd_recompute_discount_price($product_id_passed_into_function, $price);  
    }
 */

    if ($vtprd_info['product_session_info']['product_discount_price'] > 0)  {
      if ($vtprd_setup_options['show_catalog_price_crossout'] == 'yes')  {
        $price_html = '<del>' . $vtprd_info['product_session_info']['product_list_price_html_woo']  . '</del><ins>' .$from. ' ' . $vtprd_info['product_session_info']['product_discount_price_html_woo'] . '</ins>'; 
      } else {
        $price_html = $from. ' ' . $vtprd_info['product_session_info']['product_discount_price_html_woo'];
      }
    } 
         
    return $price_html;

      
  }


	public function vtprd_maybe_variable_sale_price_html($price_html, $product_info){    
    global $post, $vtprd_info, $vtprd_setup_options;

    //in place of is_admin, which doesn't work in AJAX...
     if ( function_exists( 'get_current_screen' ) ) {  // get_current_screen ONLY exists in ADMIN!!!   
       if ($post->post_type == 'product'  ) {    //in admin, don't run this on the PRODUCT screen!!
         return $price_html;
       }
     }
    
    //***************************************************
    //FROM  woocommerce_grouped_price_html
    /*  is this a variation price display 'From $xxxx'
     handles  woocommerce_grouped_price_html
     in woocommerce/classes/class-wc-product-grouped.php
          function get_price_html( $price = '' ) 
    */
    //***************************************************
    $from = strstr($price_html, 'From') !== false ? ' From ' : ' ';
    if ($from) {
    		$child_prices = array();
    		$all_children = $product_info->get_children();
        foreach ( $all_children as $child_id ) {
    			//changed to use the $child_id as key
          $child_prices[$child_id] = get_post_meta( $child_id, '_sale_price', true );
                                                             //**************       
        }        
    		
        if (count($child_prices) == 0) {
          return $price_html;
        }
        
        $min_price = '';
        $min_price_id;
        //find min
        foreach ($child_prices as $key => $child_price) {
          if ( ($child_price < $min_price) || ($min_price = '') ) {
            $min_price =  $child_price;
            $min_price_id = $key;
          }        
        }
        
        //if no min price found, nothing to do
        if (!$min_price_id) {
           return $price_html;
        }
        
      /*  
        $child_prices = array_unique( $child_prices );
    		if ( ! empty( $child_prices ) ) {
    			$min_price = min( $child_prices );
    		} else {
    			//$min_price = '';
          //if min_price does not exist, we have NOTHING to proces.  return original value.
          return $price_html;
    		}
    	//	$price .= woocommerce_price( $min_price ); 
      $price =  $min_price;  */  
    }
    //END  woocommerce_grouped_price_html handling
    //***************************************************  
  
    //$product_id = isset($product_info->variation_id) ? $product_info->variation_id : $product_info->id;
    
    //$price = $min_price;
    
    $product_id = $min_price_id;
    $vtprd_info['current_processing_request'] = 'display'; 
    $price = $min_price;
    
    vtprd_get_product_session_info($product_id, $price);

/*
    //were we passed a Variation ID to start with??
    if (($product_id_passed_into_function != $product_id ) && ($product_id_passed_into_function > ' ') ) {

 //echo '001a above recompute_discount price' .'<br>';      
      vtprd_recompute_discount_price($product_id_passed_into_function, $price);  
    }
 */

    if ($vtprd_info['product_session_info']['product_discount_price'] > 0)  {
      if ($vtprd_setup_options['show_catalog_price_crossout'] == 'yes')  {
        $price_html = '<del>' . $vtprd_info['product_session_info']['product_list_price_html_woo']  . '</del><ins>' .$from.  ' ' . $vtprd_info['product_session_info']['product_discount_price_html_woo'] . '</ins>'; 
      } else {
        $price_html = $from. ' ' . $vtprd_info['product_session_info']['product_discount_price_html_woo'];
      }
    } 
        
    return $price_html;

 }  

  /*
  Used by AJAX to get variation prices during catalog display!!!
  */
	public function vtprd_maybe_catalog_price_html($price_html, $product_info){    
    global $post, $vtprd_info, $vtprd_setup_options;

    //in place of is_admin, which doesn't work in AJAX...
     if ( function_exists( 'get_current_screen' ) ) {  // get_current_screen ONLY exists in ADMIN!!!   
       if ($post->post_type == 'product'  ) {    //in admin, don't run this on the PRODUCT screen!!
         return $price_html;
       }
     }
     
    //$product_id = isset($product_info->variation_id) ? $product_info->variation_id : $product_info->id;
   
    if ($product_info->variation_id > ' ') {      
      $product_id  = $product_info->variation_id;
    } else { 
      if ($product_info->id > ' ') {
        $product_id  = $product_info->id;
      } else {
        $product_id  = $product_info->product_id;
      }     
    }
//return $product_id; //mwnprice    
 //echo '<br>IN vtprd_maybe_catalog_price_html  $price_html= ' .$price_html. '<br>'; //mwntest
    
    $vtprd_info['current_processing_request'] = 'display';
//    $price =  $product_info->get_price(); //woo pricing...
    $price = $product_info->price;
    
         
    vtprd_get_product_session_info($product_id,$price);
    
    //mwntest
//    global $vtprd_rules_set;
//    echo '$vtprd_rules_set= <pre>'.print_r($vtprd_rules_set, true).'</pre>' ;
//    			 wp_die( __('<strong>Looks like you\'re running an older version of WordPress, you need to be running at least WordPress 3.3 to use the Varktech Minimum Purchase plugin.</strong>', 'vtmin'), __('VT Minimum Purchase not compatible - WP', 'vtmin'), array('back_link' => true));
/*      
      echo 'vtprd_info <pre>'.print_r($vtprd_info, true).'</pre>' ;
      session_start();    //mwntest
      echo 'SESSION data <pre>'.print_r($_SESSION, true).'</pre>' ;      
      echo '<pre>'.print_r($vtprd_rules_set, true).'</pre>' ; 
      echo '<pre>'.print_r($vtprd_cart, true).'</pre>' ;
      echo '<pre>'.print_r($vtprd_setup_options, true).'</pre>' ;
      echo '<pre>'.print_r($vtprd_info, true).'</pre>' ;  
 wp_die( __('<strong>Looks like you\'re running an older version of WordPress, you need to be running at least WordPress 3.3 to use the Varktech Minimum Purchase plugin.</strong>', 'vtmin'), __('VT Minimum Purchase not compatible - WP', 'vtmin'), array('back_link' => true));
*/
// 
//return $price ; //mwnprice 
 
    $from = strstr($price_html, 'From') !== false ? ' From ' : ' '; 
    if ($vtprd_info['product_session_info']['product_discount_price'] > 0)  {
      if ($vtprd_setup_options['show_catalog_price_crossout'] == 'yes')  {     
        $price_html = '<del>' . $vtprd_info['product_session_info']['product_list_price_html_woo']  . '</del><ins>' .$from.  ' ' . $vtprd_info['product_session_info']['product_discount_price_html_woo'] . '</ins>'; 
      } else {
        $price_html = $from.  ' ' . $vtprd_info['product_session_info']['product_discount_price_html_woo'];
      }
    } 
        
    return $price_html;
 

 } 


	public function vtprd_maybe_cart_item_price_html($price_html, $cart_item, $cart_item_key){    
//return 444; //mwnprice
    global $post, $vtprd_info, $vtprd_setup_options;
  /* 
     session_start();    //mwntest
    echo 'SESSION data <pre>'.print_r($_SESSION, true).'</pre>' ; 
     echo '$price_html= ' .$price_html. '<br>';
     echo '$cart_item_key= ' .$cart_item_key. '<br>';
     echo '$cart_item->product_id= ' .$cart_item['product_id']. '<br>';
     echo '$cart_item <pre>'.print_r($cart_item, true).'</pre>' ;
     wp_die( __('<strong>Looks like you\'re running an older version of WordPress, you need to be running at least WordPress 3.3 to use the Varktech Maximum Purchase plugin.</strong>', 'vtmax'), __('VT Maximum Purchase not compatible - WP', 'vtmax'), array('back_link' => true));
	*/		

   
    if ($cart_item['variation_id'] > ' ') {      
      $product_id  = $cart_item['variation_id'];
    } else { 
      $product_id  = $cart_item['product_id'];
    }
    
    if ($cart_item['quantity'] <= 0) {
      return $price_html;
    }
    

//    $price =  $cart_item['line_subtotal'] / $cart_item['quantity'];
//  v1.0.3
    if ( isset($cart_item['line_subtotal']) ) {
      $price =  $cart_item['line_subtotal'] / $cart_item['quantity'];
    } else {
      $price =  0;
    }    
    //mwntest mwncurrtest 
    //vtprd_maybe_get_discount_catalog_session_price($product_id, $price);
    vtprd_maybe_get_price_single_product($product_id, $price);

    if ($vtprd_info['product_session_info']['product_discount_price'] > 0)  {
      if ($vtprd_setup_options['show_catalog_price_crossout'] == 'yes')  {
        $price_html = '<del>' . $vtprd_info['product_session_info']['product_list_price_html_woo']  . '</del><ins>' . $vtprd_info['product_session_info']['product_discount_price_html_woo'] . '</ins>'; 
    } else {
        $price_html = $vtprd_info['product_session_info']['product_discount_price_html_woo'];
      }
    } 

   return $price_html;

 }
 
  //*************************************************************************
  //FROM 'woocommerce_get_price' => Central behind the scenes pricing
  //*************************************************************************
	public function vtprd_maybe_get_price($price, $product_info){    
    global $post, $vtprd_info;		
 //echo '<br>GET PRICE BEGIN<br>';
//          session_start();    //mwntest
 //echo 'SESSION data <pre>'.print_r($_SESSION, true).'</pre>' ;

   // IF THIS IS USED, THE SESSION ROW MUST ALWAYS BE CREATED!!!
/*   
    //this can be activated in admin.  DISALLOW! BUT BUSTS MAIN FUNCTION...
    if (is_admin()){
      return $price; 
    }
*/  
    //in place of is_admin, which doesn't work in AJAX...
     if ( function_exists( 'get_current_screen' ) ) {  // get_current_screen ONLY exists in ADMIN!!!   
       if ($post->post_type == 'product'  ) {    //in admin, don't run this on the PRODUCT screen!!
         return $price;
       }
     }
         
    if ($product_info->variation_id > ' ') {      
      $product_id  = $product_info->variation_id;
    } else { 
      if ($product_info->id > ' ') {
        $product_id  = $product_info->id;
      } else {
        $product_id  = $product_info->product_id;
      }     
    }

    if ($product_id <= ' ') {  
      return $price;
    }
    
    //vtprd_maybe_get_discount_catalog_session_price($product_id);
    vtprd_maybe_get_price_single_product($product_id, $price);

    if ($vtprd_info['product_session_info']['product_discount_price'] > 0) {
      $price = $vtprd_info['product_session_info']['product_discount_price'];
    } 

 //echo '<br>GET PRICE END<br>';
//          session_start();    //mwntest
 //echo 'SESSION data <pre>'.print_r($_SESSION, true).'</pre>' ;
/*        
     echo '$product_id= ' .$product_id. ' $price= ' .$price. '<br>';
          session_start();    //mwntest
    echo 'SESSION data <pre>'.print_r($_SESSION, true).'</pre>' ; 
     echo '$price_html= ' .$price_html. '<br>';
     echo '$cart_item_key= ' .$cart_item_key. '<br>';
     echo '$cart_item->product_id= ' .$cart_item['product_id']. '<br>';
     echo '$cart_item <pre>'.print_r($cart_item, true).'</pre>' ;
     wp_die( __('<strong>Looks like you\'re running an older version of WordPress, you need to be running at least WordPress 3.3 to use the Varktech Maximum Purchase plugin.</strong>', 'vtmax'), __('VT Maximum Purchase not compatible - WP', 'vtmax'), array('back_link' => true));
  */
   
   return $price;

 }
 
 
	public function vtprd_get_product_catalog_price_do_convert($price, $product_id = null, $variation = null){   
    global $post, $vtprd_info;
//mwntest
 //echo '001a in price_do_convert' .'<br>';
    $product_id_passed_into_function = $product_id;
    
    //if we are processing a variation, always get and pass the PARENT ID
    if ($post->ID > ' ' ) {
      $product_id = $post->ID;
    }
    if( get_post_field( 'post_parent', $product_id ) ) {
       $product_id = get_post_field( 'post_parent', $product_id );
    }  
    

    vtprd_get_product_session_info($product_id, $price);


    //were we passed a Variation ID to start with??
    if (($product_id_passed_into_function != $product_id ) && ($product_id_passed_into_function > ' ') ) {
//mwntest
 //echo '001a above recompute_discount price' .'<br>';      
      vtprd_recompute_discount_price($product_id_passed_into_function, $price);  
    }

if ($product_id == '10') { 
//mwntest
 //echo 'product_session_info= <pre>'.print_r($vtprd_info['product_session_info'], true).'</pre>' ; 
//wp_die( __('<strong>Looks like you\'re running an older version of WordPress, you need to be running at least WordPress 3.3 to use the Varktech Pricing Deals plugin.</strong>', 'vtprd'), __('VT Pricing Deals not compatible - WP', 'vtprd'), array('back_link' => true));
}
   
    if ($vtprd_info['product_session_info']['product_discount_price'] > 0) {
      return $vtprd_info['product_session_info']['product_discount_price'];
    } else {     
      return $price;
    }
      
  }

   
  /* ************************************************
  **  Price Filter -  Get display info for single product at add-to_cart time and put it directly into the cart.
  *     executed out of:  do_action in => wpsc-includes/ajax.functions.php  function wpsc_add_to_cart      
  *************************************************** */

/**
 * from cart.class.php => Validate Cart Product Quantity
 * Triggered by 'wpsc_add_item' and 'wpsc_edit_item' actions when products are added to the cart.
 *
 * @since  3.8.10
 * @access private
 *
 * @param int     $product_id                    Cart product ID.
 * @param array   $parameters                    Cart item parameters.
 * @param object  $cart                          Cart object.
 *
 * @uses  wpsc_validate_product_cart_quantity    Filters and restricts the product cart quantity.
 */
  //       add_action( 'wpsc_add_item', array(&$product_info, 'vtprd_get_product_catalog_price_add_to_cart'), 99, 3 );
 //       add_action( 'wpsc_edit_item', array(&$product_info, 'vtprd_get_product_catalog_price_add_to_cart'), 99, 3); 
public function vtprd_get_product_catalog_price_add_to_cart( $product_id, $parameters, $cart ) {
     global $vtprd_info;

    $session_found = vtprd_maybe_get_product_session_info($product_id);	
   
    // $session_found MEANS ($vtprd_info['product_session_info']['product_discount_price'] > 0)
    if ($session_found) {  
      foreach ( $cart->cart_items as $key => $cart_item ) {
    		if ( $cart_item->product_id == $product_id ) {   
          if ($vtprd_info['product_session_info']['product_discount_price'] != $cart_item->unit_price) { 
            $cart_item->unit_price   =  $vtprd_info['product_session_info']['product_discount_price'];         
            $cart_item->total_price  =  $cart_item->quantity * $cart_item->unit_price;
          } 
    		}
    	}
    }
}

   
  /* ************************************************
 
  *************************************************** */
	public function vtprd_test_for_html_crossout_use(){
    global $vtprd_setup_options;
    
    //replaced by using this instead:  ($vtprd_setup_options['show_catalog_price_crossout'] == 'yes') 
    
    if ( $vtprd_setup_options['show_catalog_price_crossout'] != 'yes') {
      return false;
    }
       
    $ruleset_has_only_display_rules = get_option('vtprd_ruleset_has_only_display_rules');
    if ($ruleset_has_only_display_rules) {
      return true;
    } else {
      return false;
    }

  } 
 
   
  /* ************************************************
  ** Template Tag / Filter -  full_msg_line   => can be accessed by both display and cart rule types    
  *************************************************** */
	public function vtprd_show_product_discount_full_msg_line($product_id=null){
    global $post, $vtprd_info;
       
    if ($post->ID > ' ' ) {
      $product_id = $post->ID;
    } 
        
    //routine has been called, but no product_id supplied or available
    if (!$product_id) {
      return;
    } 
    
    vtprd_get_product_session_info($product_id);
       
    $output  = '<p class="discount-full-msg" id="fullmsg_' .$product_id. '">' ;
    for($y=0; $y < sizeof($vtprd_info['product_session_info']['product_rule_full_msg_array']); $y++) {
      $output .= $vtprd_info['product_session_info']['product_rule_full_msg_array'][$y] . '<br>' ;
    }      
    $output .= '</p>'; 
        
    echo $output;
    
    return;
  }  

   
  // from woocommerce/classes/class-wc-cart.php 
  public function vtprd_woo_get_url ($pageName) {            
     global $woocommerce;
      $checkout_page_id = $this->vtprd_woo_get_page_id($pageName);
  		if ( $checkout_page_id ) {
  			if ( is_ssl() )
  				return str_replace( 'http:', 'https:', get_permalink($checkout_page_id) );
  			else
  				return apply_filters( 'woocommerce_get_checkout_url', get_permalink($checkout_page_id) );
  		}
  }
      
  // from woocommerce/woocommerce-core-functions.php 
  public function vtprd_woo_get_page_id ($pageName) { 
    $page = apply_filters('woocommerce_get_' . $pageName . '_page_id', get_option('woocommerce_' . $pageName . '_page_id'));
		return ( $page ) ? $page : -1;
  }    
 /*  =============+++++++++++++++++++++++++++++++++++++++++++++++++++++++++    */
    


   // do_action( 'woocommerce_add_to_cart', $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data );
   public function vtprd_ajax_add_to_cart_hook($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
    
      if(!isset($_SESSION)){
        session_start();
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
       }
      
      //**********
      //prevents recursive processing during auto add execution of add_to_cart! 
      //**********
      if ( (defined('VTPRD_PRO_DIRNAME'))  &&
           (isset($_SESSION['auto_add_in_progress'])) && 
                 ($_SESSION['auto_add_in_progress'] == 'yes') ) {
        $current_time_in_seconds = time();
        if ( ($current_time_in_seconds - $_SESSION['auto_add_in_progress_timestamp']) > '10' ) { //session data older than 10 seconds, reset and continue! 
          $contents = $_SESSION['auto_add_in_progress'];
          unset( $_SESSION['auto_add_in_progress'], $contents );
          $contents = $_SESSION['auto_add_in_progress_timestamp'];
          unset( $_SESSION['auto_add_in_progress_timestamp'], $contents ); 
        } else {
          return;
        }          
      }

      //prevents recursive updates
     // $_SESSION['update_in_progress'] == 'discount already processed';



  /*
      //UPDATE the DATA Chain immediately with the current woocommerce totals and coupon info.  That way,
      //  when the UPDATED hook is poassibly called DURING an auto-add within the add-to-cart, the info will be current.
      global $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $wpsc_coupons;   
         
      $data_chain      = unserialize($_SESSION['data_chain']); 
      if ($vtprd_rules_set == '') {
        $vtprd_rules_set = $data_chain[0];
        $vtprd_cart      = $data_chain[1];
      }
      $data_chain = array();
      $data_chain[] = $vtprd_rules_set;
      $data_chain[] = $vtprd_cart;
      $data_chain[] =  $woocommerce->cart->cart_contents_total;
      $data_chain[] =  $woocommerce->cart->applied_coupons;
      $_SESSION['data_chain'] = serialize($data_chain);             
 */   


      
      $this->vtprd_process_discount() ;

   //   $contents = $_SESSION['update_in_progress'];
   //   unset( $_SESSION['update_in_progress'], $contents );
      
      return;
      //return $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data;
   }
     


   public function vtprd_test_quantity_zero($cart_item_key) { 
 //echo '<br>vtprd_test_quantity_zero<br>' ;  
//      session_start();       
 //echo '$_SESSION= <pre>'.print_r($_SESSION, true).'</pre>' ;
//wp_die( __('<strong>vtprd_test_quantity_zero.</strong>', 'vtprd'), __('VT Pricing Deals not compatible - WP', 'vtprd'), array('back_link' => true));

      return;

   }       

   // do_action( 'woocommerce_add_to_cart', $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data );
   public function vtprd_cart_updated() {
      global $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $wpsc_coupons;  
 //echo '<br>AT BEginning: FROM parent-cart-validation  function cart-updated<br>' ;             
    //Open Session Variable, get rules_set and cart if not there....
    $data_chain = $this->vtprd_get_data_chain();
    
    $woo_cart_contents_total_previous  = $data_chain[2]; //USED HERE
    $woo_applied_coupons_previous      = $data_chain[3]; //USED HERE 
    
    //**********
    //prevents recursive processing during auto add execution of add_to_cart! 
    //**********
    if ( (defined('VTPRD_PRO_DIRNAME'))  &&
         (isset($_SESSION['auto_add_in_progress'])) && 
               ($_SESSION['auto_add_in_progress'] == 'yes') ) {
      $current_time_in_seconds = time();
      if ( ($current_time_in_seconds - $_SESSION['auto_add_in_progress_timestamp']) > '10' ) { //session data older than 10 seconds, reset and continue! 
        $contents = $_SESSION['auto_add_in_progress'];
        unset( $_SESSION['auto_add_in_progress'], $contents );
        $contents = $_SESSION['auto_add_in_progress_timestamp'];
        unset( $_SESSION['auto_add_in_progress_timestamp'], $contents );
 //echo '<br>Unset 001<br>' ;          
      } else {
 //echo '<br>Return 001<br>' ;       
        return;
      }          
    }
     
/*
echo '$woocommerce->cart->cart_contents_total= ' .$woocommerce->cart->cart_contents_total . '<br>';
echo '$woo_cart_contents_total_previous= ' .$woo_cart_contents_total_previous . '<br>';
echo '$woo_applied_coupons_previous= <pre>'.print_r($woo_applied_coupons_previous, true).'</pre>' ;
echo '$woocommerce->cart->applied_coupons= <pre>'.print_r($woocommerce->cart->applied_coupons, true).'</pre>' ;
*/    
    //-*******************************************************
    //IF nothing changed from last time, no need to process the discount => 
    //'woocommerce_cart_updated' RUNS EVERY TIME THE CART OR CHECKOUT PAGE DISPLAYS!!!!!!!!!!!!!
    //-*******************************************************
    if ( ($woocommerce->cart->cart_contents_total  ==  $woo_cart_contents_total_previous) &&
         ($woocommerce->cart->applied_coupons      ==  $woo_applied_coupons_previous) ) {
 //echo '<br>Return 002<br>' ;          
       return;  
    }

 //echo '$woo_cart_contents_total_previous= '.$woo_cart_contents_total_previous.'<br>' ;
 //echo '$woo_cart_contents_total_Current= '.$woocommerce->cart->cart_contents_total.'<br>' ;
 //echo '$woo_applied_coupons_previous= '.$woo_applied_coupons_previous.'<br>' ;
 //echo '$woo_applied_coupons_Current= '.$woocommerce->cart->applied_coupons.'<br>' ;
    
    //prevents recursive updates
//    if (isset($_SESSION['update_in_progress'])) { 
 //echo '<br>Return 002a<br>' ;        
//      return;
//    }

     
    $woocommerce_cart_contents = $woocommerce->cart->get_cart();  
    if (sizeof($woocommerce_cart_contents) > 0) {
 //echo '<br>before process discount<br>' ;
 //echo '$_SESSION= <pre>'.print_r($_SESSION, true).'</pre>' ; 
      $this->vtprd_process_discount();
    } else {
      //also clears out coupons
 //echo '<br>before clear session vars<br>' ;       
      $this->vtprd_maybe_clear_auto_add_session_vars();	
    }
 

      return;
      //return $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data;
   }
       
    
	public function vtprd_process_discount(){  //and print discount info...    
    global $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $wpsc_coupons;    
    /*
    //+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    //In order to prevent recursive executions, test for a TIMESTAMP    
    if (isset($_SESSION['process_discount_timestamp'])) {
      $previous_process_discount_timestamp = $_SESSION['process_discount_timestamp'];
      $current_process_discount_timestamp  = time();
      if ( ($current_time_in_seconds - $previous_process_discount_timestamp) > '1' ) { //session data older than 1 second
        $_SESSION['process_discount_timestamp'] = time();
      } else {
        return;
      }
    } else {
      $_SESSION['process_discount_timestamp'] = time();
    }
    //+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++    
    */
 //echo '<br>BEGIN PROCESS_DISCOUNT!!<br>' ; //mwnecho
    //calc discounts                
    $vtprd_info['current_processing_request'] = 'cart'; 
    $vtprd_apply_rules = new VTPRD_Apply_Rules;    



/*
echo '$vtprd_cart = <pre>'.print_r($vtprd_cart, true).'</pre>' ;
echo '$vtprd_rules_set = <pre>'.print_r($vtprd_rules_set, true).'</pre>' ;
wp_die( __('<strong>Looks like you\'re running an older version of WordPress, you need to be running at least WordPress 3.3 to use the Varktech Pricing Deals plugin.</strong>', 'vtprd'), __('VT Pricing Deals not compatible - WP', 'vtprd'), array('back_link' => true));
*/


    /*  *************************************************
     Load this info into session variables, to begin the 
     DATA CHAIN - global to session back to global
     global to session - in vtprd_process_discount
     session to global - in vtprd_woo_validate_order
     access global     - in vtprd_post_purchase_maybe_save_log_info   
    *************************************************   */
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }
    $contents_total   =   $woocommerce->cart->cart_contents_total;
    $applied_coupons  =   $woocommerce->cart->applied_coupons;
    $data_chain = array();
    $data_chain[] = $vtprd_rules_set;
    $data_chain[] = $vtprd_cart;
    $data_chain[] =  $contents_total;
    $data_chain[] =  $applied_coupons;
    $_SESSION['data_chain'] = serialize($data_chain);             
    
 //echo '<br>End PROCESS_DISCOUNT!!<br>' ; //mwnecho
 //echo 'process_discount $contents_total= ' .$contents_total. '<br>';
 //echo 'process_discount $applied_coupons= ' .$applied_coupons. '<br>';   
    
    return;        
} 
     
    
	public function vtprd_woo_maybe_add_remove_discount_cart_coupon(){  //and print discount info...    
      
    global $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $wpsc_coupons;  
                 
    //Open Session Variable, get rules_set and cart if not there....
    $data_chain = $this->vtprd_get_data_chain();
      
    //engenders a tr class coupon-deals, used in CSS!
    $coupon_title = $vtprd_info['coupon_code_discount_deal_title']; 
     
    if ($vtprd_cart->yousave_cart_total_amt > 0) {
       //add coupon - recalc totals done when actual coupon amount updated
       if ($woocommerce->cart->has_discount($coupon_title)) {
          $do_nothing;
       } else {
          $woocommerce->cart->add_discount($coupon_title);
//echo '$woocommerce->messages BEFORE= <pre>'.print_r($woocommerce->messages, true).'</pre>' ;  
          //Remove add coupons success msg if there...  otherwise it may display and confuse the customer => "Coupon code applied successfully"
          $coupon_succss_msg = __( 'Coupon code applied successfully.', 'vtprd' );
          $sizeof_messages = sizeof($woocommerce->messages);
          for($y=0; $y < $sizeof_messages; $y++) { 
             if ($woocommerce->messages[$y] == $coupon_succss_msg ) {
                unset ( $woocommerce->messages[$y] );
                break;
             }
          } 
          
//echo '$woocommerce->messages AFTER= <pre>'.print_r($woocommerce->messages, true).'</pre>' ;  
                
       }
      
    } else {
       //remove coupon and recalculate totals
       if ($woocommerce->cart->has_discount($coupon_title) ) {
  				$this->vtprd_woo_maybe_remove_coupon_from_cart($coupon_title);
          $woocommerce->cart->calculate_totals();
       }
       
    }
/*
echo '$woocommerce= <pre>'.print_r($woocommerce, true).'</pre>' ;
echo '$vtprd_cart= <pre>'.print_r($vtprd_cart, true).'</pre>' ; 
echo '$vtprd_rules_set= <pre>'.print_r($vtprd_rules_set, true).'</pre>' ; 
wp_die( __('<strong>die again.</strong>', 'vtprd'), __('VT Pricing Deals not compatible - WP', 'vtprd'), array('back_link' => true));     
*/
              
    return;        
} 

  //clears coupon from cart
   public function vtprd_woo_maybe_remove_coupon_from_cart($coupon_title) {
      global $woocommerce;
			if ( $woocommerce->applied_coupons ) {
				foreach ( $woocommerce->applied_coupons as $index => $code ) {
					if ( $code == $coupon_title ) {
            unset( $woocommerce->applied_coupons[ $index ] );
            break;
          } 
				}
			}              
    return;        
} 

      
   //****************************************************************
   // Update the placeholder Coupon previously manually added 
   //  with the discount amount
   //****************************************************************
   public function vtprd_woo_maybe_load_discount_amount_to_coupon($status, $code) {
      global $vtprd_rules_set, $wpdb, $vtprd_cart, $vtprd_setup_options, $vtprd_info, $woocommerce;
      
  //echo '$code= ' .$code. '<br>';
  //echo 'coupon_code_discount_deal= ' .$vtprd_info['coupon_code_discount_deal_title']. '<br>';
      
      
      if ($code != $vtprd_info['coupon_code_discount_deal_title']) {
         return false;    
      }

                 
      //Open Session Variable, get rules_set and cart if not there....
      $data_chain = $this->vtprd_get_data_chain();
    
    
  //echo '$woocommerce= <pre>'.print_r($woocommerce, true).'</pre>' ;
  //echo '$vtprd_cart= <pre>'.print_r($vtprd_cart, true).'</pre>' ; 
  //echo '$vtprd_rules_set= <pre>'.print_r($vtprd_rules_set, true).'</pre>' ; 
      
      if ($vtprd_cart->yousave_cart_total_amt <= 0) {
         return false;
      }
      
      //GET coupon_id of the previously inserted placeholder coupon where title = $vtprd_info['coupon_code_discount_deal_title']
      $deal_discount_title = $vtprd_info['coupon_code_discount_deal_title'];
      $coupon_id 	= $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_title ='" . $deal_discount_title. "'  AND post_type = 'shop_coupon' AND post_status = 'publish'  LIMIT 1" );     	
         
      //defaults take from  class/class-wc-coupon.php    function __construct
      $coupon_data = array(
            'id'                         => $coupon_id,
            'type'                       => 'fixed_cart',   //type = discount_type
            'amount'                     => $vtprd_cart->yousave_cart_total_amt,
            'individual_use'             => 'no',
            'product_ids'                => array(),
            'exclude_product_ids'        => array(),
            'usage_limit'                => '',
            'usage_count'                => '',
            'expiry_date'                => '',
            'apply_before_tax'           => 'yes',
            'free_shipping'              => 'no',
            'product_categories'         => array(),
            'exclude_product_categories' => array(),
            'exclude_sale_items'         => 'no',
            'minimum_amount'             => '',
            'customer_email'             => ''
      );            

  //echo '$coupon_data= <pre>'.print_r($coupon_data, true).'</pre>' ;
  //echo '$vtprd_cart= <pre>'.print_r($vtprd_cart, true).'</pre>' ; 
  //echo '$vtprd_rules_set= <pre>'.print_r($vtprd_rules_set, true).'</pre>' ;

     return $coupon_data;
   }


  //**************************************************
  //  Maybe print discount, always update the coupon info for post-payment processing
  //**************************************************
	public function vtprd_maybe_print_checkout_discount(){  //and print discount info...
    
     global $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $wpsc_coupons;                 
    
    //Open Session Variable, get rules_set and cart if not there....
    $data_chain = $this->vtprd_get_data_chain();

    //set one-time switch for use in function vtprd_post_purchase_maybe_save_log_info
    $_SESSION['do_log_function'] = true;
          
    /*  *************************************************
     At this point the global variable contents are gone. 
     session variables are destroyed in parent plugin before post-update processing...
     load the globals with the session variable contents, so that the data will be 
     available in the globals during post-update processing!!!
      
     DATA CHAIN - global to session back to global
     global to session - in vtprd_process_discount
     session to global - in vtprd_woo_validate_order  +
                            vtprd_post_purchase_maybe_purchase_log
     access global     - in vtprd_post_purchase_maybe_save_log_info    
    *************************************************   */

    //**************************************************
    //Add discount totals into coupon_totals (a positive #) for payment gateway processing and checkout totals processing
    //  $wpsc_cart->coupons_amount has ALREADY been re-computed in apply-rules.php at add to cart time
    //**************************************************    
/*
echo '$woocommerce cart= <pre>'.print_r($woocommerce, true).'</pre>' ;
echo '$vtprd_cart= <pre>'.print_r($vtprd_cart, true).'</pre>' ; 
echo '$vtprd_rules_set= <pre>'.print_r($vtprd_rules_set, true).'</pre>' ; 
*/         
    
    if ($vtprd_cart->yousave_cart_total_amt > 0) {  
    //    vtprd_print_checkout_discount();
        vtprd_checkout_cart_reporting();
    } 

    /*  *************************************************
     Load this info into session variables, to begin the 
     DATA CHAIN - global to session back to global
     global to session - in vtprd_process_discount
     session to global - in vtprd_woo_validate_order
     access global     - in vtprd_post_purchase_maybe_save_log_info   
    *************************************************   */
/*  WHY DOE THIS HERE???????????????????????????????  
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }
    $data_chain = array();
    $data_chain[] = $vtprd_rules_set;
    $data_chain[] = $vtprd_cart;
    $data_chain[] =  $woocommerce->cart->cart_contents_total;
    $data_chain[] =  $woocommerce->cart->applied_coupons;
    $_SESSION['data_chain'] = serialize($data_chain);  
 */           
    return;        
} 


  //**************************************************
  //  Maybe print Widget discount
  //**************************************************
	public function vtprd_maybe_print_widget_discount(){  //and print discount info...
    global $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $wpsc_coupons;
        
    //Open Session Variable, get rules_set and cart if not there....
    $data_chain = $this->vtprd_get_data_chain();

    
    //set one-time switch for use in function vtprd_post_purchase_maybe_save_log_info
    $_SESSION['do_log_function'] = true;
          
    /*  *************************************************
     At this point the global variable contents are gone. 
     session variables are destroyed in parent plugin before post-update processing...
     load the globals with the session variable contents, so that the data will be 
     available in the globals during post-update processing!!!
      
     DATA CHAIN - global to session back to global
     global to session - in vtprd_process_discount
     session to global - in vtprd_woo_validate_order  +
                            vtprd_post_purchase_maybe_purchase_log
     access global     - in vtprd_post_purchase_maybe_save_log_info    
    *************************************************   */

    //**************************************************
    //Add discount totals into coupon_totals (a positive #) for payment gateway processing and checkout totals processing
    //  $wpsc_cart->coupons_amount has ALREADY been re-computed in apply-rules.php at add to cart time
    //**************************************************    
/*
echo '$woocommerce= <pre>'.print_r($woocommerce, true).'</pre>' ;
echo '$vtprd_cart= <pre>'.print_r($vtprd_cart, true).'</pre>' ; 
echo '$vtprd_rules_set= <pre>'.print_r($vtprd_rules_set, true).'</pre>' ; 
*/
    if ($vtprd_cart->yousave_cart_total_amt > 0) {
    //   vtprd_enqueue_front_end_css();   
        vtprd_print_widget_discount();
    } 
        
    return;        
} 


  /* ************************************************
  **   After purchase is completed, store lifetime purchase and discount log info
  *
  * This function is executed multiple times, only complete on 1st time through    
  * //				do_action( 'woocommerce_checkout_order_processed', $order_id, $this->posted );     
  *************************************************** */ 
  public function vtprd_post_purchase_maybe_save_log_info($log_id, $posted_info) {   //$log_id comes in as an argument from wpsc call...
      
    global $woocommerce, $vtprd_setup_options, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule;
           
    //while the global data is available here, it does not stay 'current' between iterations, and we loos the 'already_done' switch, so we need the data chain.
         
    //Open Session Variable, get rules_set and cart if not there....
    $data_chain = $this->vtprd_get_data_chain();


    //only do this once - set in function vtprd_maybe_print_checkout_discount    
    if (!$_SESSION['do_log_function']) {
        return;
    }
    $_SESSION['do_log_function'] = false;
    
    /*  *************************************************
     At this point the global variable contents are gone. 
     session variables are destroyed in parent plugin before post-update processing...
     load the globals with the session variable contents, so that the data will be 
     available in the globals during post-update processing!!!
      
     DATA CHAIN - global to session back to global
     global to session - in vtprd_process_discount
     session to global - in vtprd_woo_validate_order  +
                            vtprd_post_purchase_maybe_purchase_log
     access global     - in vtprd_post_purchase_maybe_save_log_info    
    *************************************************   */

    //if this was initiated during a re-send of the customer email out of WP-Admin, EXIT stage left!!
    //    (this switch set at cart load time...)
    //  do_log_function above should take care of this already...
//    if ($vtprd_cart->wpsc_purchase_in_progress != 'yes') {
//      return;
//    }
  
    //*****************
    //Save LIfetime data
    //*****************
    if ( (defined('VTPRD_PRO_DIRNAME')) && ($vtprd_setup_options['use_lifetime_max_limits'] == 'yes') )  { 
      vtprd_save_lifetime_purchase_info($log_id);
    }

    //Save Discount Purchase Log info
    //************************************************
    //*   Purchase log is essential to customer email reporting
    //*      so it MUST be saved at all times.
    //************************************************
    vtprd_save_discount_purchase_log($log_id);     
//wp_die( __('<strong>die again.</strong>', 'vtprd'), __('VT Pricing Deals not compatible - WP', 'vtprd'), array('back_link' => true));     
    return;
  } // end  function vtprd_store_max_purchaser_info()     


   
  /* ************************************************
  USING THIS filter in this way, puts discounts within the existing products table, after products are shown, but before the close of the table...
  *************************************************** */ 
 public function vtprd_post_purchase_maybe_email($message, $order_info) {   
    global $wpdb, $vtprd_rules_set, $vtprd_cart, $vtprd_setup_options; 
/*
echo '$message= <pre>'.print_r($message, true).'</pre>' ; 
echo '$order_info[id]= ' .$order_info->id . '<br>';
echo '$order_info= <pre>'.print_r($order_info, true).'</pre>' ; 
	 wp_die( __('<strong>DIED in vtprd_get_product_catalog_price_new.</strong>', 'vtprd'), __('VT Pricing Deals not compatible - WP', 'vtprd'), array('back_link' => true)); 
*/

    $log_Id = $order_info->id;
   
    //if there's a discount history, let's find it...
    $vtprd_purchase_log = $wpdb->get_row( "SELECT * FROM `" . VTPRD_PURCHASE_LOG . "` WHERE `cart_parent_purchase_log_id`='" . $log_Id . "' LIMIT 1", ARRAY_A );      	
    	    
    //if purchase log, use that info instead of current 
    if ($vtprd_purchase_log) { 
      $vtprd_cart      = unserialize($vtprd_purchase_log['cart_object']);    
      $vtprd_rules_set = unserialize($vtprd_purchase_log['ruleset_object']);
    }                                                                                                                          

    //NO discount found, no msg changes
    if (!($vtprd_cart->yousave_cart_total_amt > 0)) {
      return $message;    
    } 

      //get the Discount detail report...
    if (strpos($message, '\n\n')) {   //if '\n\n' is in the #message, it's not html!!  =>  see last line, templates/emails/plain/email-order-items.php
      $discount_reporting = vtprd_email_cart_reporting('plain'); 
    } else {
      $discount_reporting = vtprd_email_cart_reporting('html');     
    }

    //overwrite $message old message parts, new info as well...
//    $message .=  '<br>';
    $message .=  $discount_reporting;
//    $message .=  '<br>';

    
    return $message;
  }    

    
  /* ************************************************
  //  do_action( 'woocommerce_order_details_after_order_table', $order );
  *************************************************** */ 
  public function vtprd_post_purchase_maybe_before_thankyou($order_id) {   
    global $wpdb, $vtprd_rules_set, $vtprd_cart, $vtprd_setup_options; 


    $log_Id = $order_id;
   
    //if there's a discount history, let's find it...
    $vtprd_purchase_log = $wpdb->get_row( "SELECT * FROM `" . VTPRD_PURCHASE_LOG . "` WHERE `cart_parent_purchase_log_id`='" . $log_Id . "' LIMIT 1", ARRAY_A );      	
    	    
    //if purchase log, use that info instead of current 
    if ($vtprd_purchase_log) { 
      $vtprd_cart      = unserialize($vtprd_purchase_log['cart_object']);    
      $vtprd_rules_set = unserialize($vtprd_purchase_log['ruleset_object']);
    }                                                                                                                          

    //NO discount found, no msg changes
    if (!($vtprd_cart->yousave_cart_total_amt > 0)) {
      return;    
    } 

    //get the Discount detail report...
    $discount_reporting = vtprd_thankyou_cart_reporting(); 

    //overwrite $message old message parts, new info as well...
//    $message  =  '<br>';
    
    $message .=  $discount_reporting;
//    $message .=  '<br>';

    echo  $message;

    /*
echo '$message= <pre>'.print_r($message, true).'</pre>' ; 
echo '$order_info[id]= ' .$order_info->id . '<br>';
echo '$order_info= <pre>'.print_r($order_info, true).'</pre>' ; 
	 wp_die( __('<strong>DIED in vtprd_get_product_catalog_price_new.</strong>', 'vtprd'), __('VT Pricing Deals not compatible - WP', 'vtprd'), array('back_link' => true)); 
*/
    
    return;  
  }

     
  /* ************************************************
  //  do_action( 'woocommerce_order_details_after_order_table', $order );
  *************************************************** */ 
/*
  public function vtprd_post_purchase_maybe_thankyou($order_info) {   
    global $wpdb, $vtprd_rules_set, $vtprd_cart, $vtprd_setup_options; 


    $log_Id = $order_info->id;
   
    //if there's a discount history, let's find it...
    $vtprd_purchase_log = $wpdb->get_row( "SELECT * FROM `" . VTPRD_PURCHASE_LOG . "` WHERE `cart_parent_purchase_log_id`='" . $log_Id . "' LIMIT 1", ARRAY_A );      	
    	    
    //if purchase log, use that info instead of current 
    if ($vtprd_purchase_log) { 
      $vtprd_cart      = unserialize($vtprd_purchase_log['cart_object']);    
      $vtprd_rules_set = unserialize($vtprd_purchase_log['ruleset_object']);
    }                                                                                                                          

    //NO discount found, no msg changes
    if (!($vtprd_cart->yousave_cart_total_amt > 0)) {
      return;    
    } 

    //get the Discount detail report...
    $discount_reporting = vtprd_email_cart_reporting('html'); 

    //overwrite $message old message parts, new info as well...
//    $message =  '<br>';
    $message .=  $discount_reporting;
//    $message .=  '<br>';

    echo  $message;

    
echo '$message= <pre>'.print_r($message, true).'</pre>' ; 
echo '$order_info[id]= ' .$order_info->id . '<br>';
echo '$order_info= <pre>'.print_r($order_info, true).'</pre>' ; 
	 wp_die( __('<strong>DIED in vtprd_get_product_catalog_price_new.</strong>', 'vtprd'), __('VT Pricing Deals not compatible - WP', 'vtprd'), array('back_link' => true)); 

    
    return;  
  }
*/


/* ************************************************
  **   After purchase is completed, => create the html transaction results report <=
  *       ONLY at transaction time...
  *********************************************** */     
 public function vtprd_post_purchase_maybe_purchase_log($message, $notification) {   
    global $woocommerce, $vtprd_rules_set, $vtprd_cart, $vtprd_setup_options, $vtprd_info;    
             
    //Open Session Variable, get rules_set and cart if not there....
    $data_chain = $this->vtprd_get_data_chain();
   
    /*  *************************************************
     At this point the global variable contents are gone. 
     session variables are destroyed in parent plugin before post-update processing...
     load the globals with the session variable contents, so that the data will be 
     available in the globals during post-update processing!!!
      
     DATA CHAIN - global to session back to global
     global to session - in vtprd_process_discount
     session to global - in vtprd_woo_validate_order  +
                            vtprd_post_purchase_maybe_purchase_log
     access global     - in vtprd_post_purchase_maybe_save_log_info    
    *************************************************   */

    if(!isset($_SESSION['data_chain'])){
      return $message;    
    }

    
    //NO discount found, no msg changes
    if (!($vtprd_cart->yousave_cart_total_amt > 0)) {
      $this->vtprd_nuke_session_variables();
      return $message;    
    } 
    
    //check if the discount reporting has already been applied, by looking for the header
    //  as this function may be called Twice
    $needle = '<th>' . __('Discount Quantity', 'vtprd') .'</th>';
    if (strpos($message, $needle)) {   //if $needle already in the #message
      $this->vtprd_nuke_session_variables();
      return $message;
    }
    
  
    $msgType = 'html';

    //get the Discount detail report...
    $discount_reporting = vtprd_email_cart_reporting($msgType); 
    
    //just concatenate in the discount DETAIL info into $message and return
    
    //split the message up into pieces.  We're going to insert all the Discount Reporting
    //  just before "Total Shipping:"
    $totShip_literal = __( 'Total Shipping:', 'wpsc' ); 
    $message_pieces  = explode($totShip_literal, $message); //this removes the delimiter string...
    
    //overwrite $message old message parts, new info as well...
    $message  =  $message_pieces[0]; //1st piece before the delimiter "Total Shipping:"
    $message .=  $discount_reporting;
    
    //skip a line    
    if ($msgType == 'html') {
      $message .= '<br>';
    } else {
      $message .= "\r\n";
    }
    
    //put the delimeter string BACK
    $message .=  $totShip_literal; 
    $message .=  $message_pieces[1]; //2nd piece after the delimiter "Total Shipping:"

    $this->vtprd_nuke_session_variables();
    return $message;
  } 
 
   
  /* ************************************************
  **   Post-transaction cleanup - Nuke the session variables 
  *************************************************** */ 
 public  function vtprd_nuke_session_variables() {
    
    if (isset($_SESSION['data_chain']))  {
      $contents = $_SESSION['data_chain'];
      unset( $_SESSION['data_chain'], $contents );
    }
    
    if (isset($_SESSION['previous_free_product_array']))  {    
      $contents = $_SESSION['previous_free_product_array'];
      unset( $_SESSION['previous_free_product_array'], $contents );
    }

    if (isset($_SESSION['current_free_product_array']))  {         
      $contents = $_SESSION['current_free_product_array'];
      unset( $_SESSION['current_free_product_array'], $contents ); 
    }
    
    return;   
 }
   
  /* ************************************************
  **   Application - get current page url
  *       
  *       The code checking for 'www.' is included since
  *       some server configurations do not respond with the
  *       actual info, as to whether 'www.' is part of the 
  *       URL.  The additional code balances out the currURL,
  *       relative to the Parent Plugin's recorded URLs           
  *************************************************** */ 
 public  function vtprd_currPageURL() {
     global $vtprd_info;
     $currPageURL = $this->vtprd_get_currPageURL();
     $www = 'www.';
     
     $curr_has_www = 'no';
     if (strpos($currPageURL, $www )) {
         $curr_has_www = 'yes';
     }
     
     //use checkout URL as an example of all setup URLs
     $checkout_has_www = 'no';
     if (strpos($vtprd_info['woo_checkout_url'], $www )) {
         $checkout_has_www = 'yes';
     }     
         
     switch( true ) {
        case ( ($curr_has_www == 'yes') && ($checkout_has_www == 'yes') ):
        case ( ($curr_has_www == 'no')  && ($checkout_has_www == 'no') ): 
            //all good, no action necessary
          break;
        case ( ($curr_has_www == 'no') && ($checkout_has_www == 'yes') ):
            //reconstruct the URL with 'www.' included.
            $currPageURL = $this->vtprd_get_currPageURL($www); 
          break;
        case ( ($curr_has_www == 'yes') && ($checkout_has_www == 'no') ): 
            //all of the woo URLs have no 'www.', and curr has it, so remove the string 
            $currPageURL = str_replace($www, "", $currPageURL);
          break;
     } 
 
     return $currPageURL;
  } 
 public  function vtprd_get_currPageURL($www = null) {
     global $vtprd_info;
     $pageURL = 'http';
     //if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
     if ( isset( $_SERVER["HTTPS"] ) && strtolower( $_SERVER["HTTPS"] ) == "on" ) { $pageURL .= "s";}
     $pageURL .= "://";
     $pageURL .= $www;   //mostly null, only active rarely, 2nd time through - see above
     
     //NEVER create the URL with the port name!!!!!!!!!!!!!!
     $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
     /* 
     if ($_SERVER["SERVER_PORT"] != "80") {
        $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
     } else {
        $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
     }
     */
     return $pageURL;
  }  
    
  
  /* ************************************************
  **   Application - On Error Display Message on E-Commerce Checkout Screen  
  *************************************************** */ 
  public function vtprd_display_rule_error_msg_at_checkout(){
    global $vtprd_info, $vtprd_cart, $vtprd_setup_options;
     
    //error messages are inserted just above the checkout products, and above the checkout form
     ?>     
        <script type="text/javascript">
        jQuery(document).ready(function($) {
    <?php 
    //loop through all of the error messages 
    //          $vtprd_info['line_cnt'] is used when table formattted msgs come through.  Otherwise produces an inactive css id. 
    for($i=0; $i < sizeof($vtprd_cart->error_messages); $i++) { 
      ?>
       <?php  if ( $vtprd_setup_options['show_error_before_checkout_products_selector'] > ' ' )  {  ?> 
          $('<div class="vtprd-error"><p> <?php echo $vtprd_cart->error_messages[$i] ?> </p></div>').insertBefore('<?php echo $vtprd_setup_options['show_error_before_checkout_products_selector'] ?>') ;
       <?php  }  ?>
       <?php  if ( $vtprd_setup_options['show_error_before_checkout_address_selector'] > ' ' )  {  ?>  
          $('<div class="vtprd-error"><p> <?php echo $vtprd_cart->error_messages[$i] ?> </p></div>').insertBefore('<?php echo $vtprd_setup_options['show_error_before_checkout_address_selector'] ?>') ;
       <?php  }  ?>
      <?php 
    }  //end 'for' loop      
    ?>   
            });   
          </script>
     <?php    


     /* ***********************************
        CUSTOM ERROR MSG CSS AT CHECKOUT
        *********************************** */
     if ($vtprd_setup_options[custom_error_msg_css_at_checkout] > ' ' )  {
        echo '<style type="text/css">';
        echo $vtprd_setup_options[custom_error_msg_css_at_checkout];
        echo '</style>';
     }
     
     /*
      Turn off the messages processed switch.  As this function is only executed out
      of wp_head, the switch is only cleared when the next screenful is sent.
     */
     $vtprd_cart->error_messages_processed = 'no';
       
 } 

   //Ajax-only
   public function vtprd_ajax_empty_cart() {
     //clears ALL the session variables, also clears out coupons
     $this->vtprd_maybe_clear_auto_add_session_vars();
     
     //Ajax needs exit
     exit;
   }


   //supply woo with ersatz pricing deals discount type
   public function vtprd_woo_add_pricing_deal_discount_type($coupon_types_array) {
      $coupon_types_array['pricing_deal_discount']	=  __( 'Pricing Deal Discount', 'woocommerce' );
     return $coupon_types_array;
   }


   public function vtprd_get_data_chain() {
         
      if(!isset($_SESSION)){
        session_start();
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
      }   
      /*  *************************************************
       At this point the global variable contents are gone. 
       session variables are destroyed in parent plugin before post-update processing...
       load the globals with the session variable contents, so that the data will be 
       available in the globals during post-update processing!!!
        
       DATA CHAIN - global to session back to global
       global to session - in vtprd_process_discount
       session to global - in vtprd_woo_validate_order  +
                              vtprd_post_purchase_maybe_purchase_log
       access global     - in vtprd_post_purchase_maybe_save_log_info    
      *************************************************   */
      global $vtprd_rules_set, $vtprd_cart;
      
      //mwn0402
      if (isset($_SESSION['data_chain'])) {
        $data_chain      = unserialize($_SESSION['data_chain']);
      } else {
        $data_chain = array();
      }
      
          
      if ($vtprd_rules_set == '') {        
        $vtprd_rules_set = $data_chain[0];
        $vtprd_cart      = $data_chain[1];
      }


      return $data_chain;
   }

/*
   //supply woo with ersatz pricing deals coupon data on demand
   public function vtprd_woo_add_pricing_deal_coupon_data($status, $code) {
      if ($code != 'pricing_deal_discount') {
         return false;
      } 
         
      //defaults take from  class/class-wc-coupon.php    function __construct
      $coupon_data = array(
            'id'                         => '',
            'type'                       => 'pricing_deal_discount',   //type = discount_type
            'amount'                     => 0,
            'individual_use'             => 'no',
            'product_ids'                => '',
            'exclude_product_ids'        => '',
            'usage_limit'                => '',
            'usage_count'                => '',
            'expiry_date'                => '',
            'apply_before_tax'           => 'yes',
            'free_shipping'              => 'no',
            'product_categories'         => array(),
            'exclude_product_categories' => array(),
            'exclude_sale_items'         => 'no',
            'minimum_amount'             => '',
            'customer_email'             => array()
      );            

   
     return $coupon_data;
   }
*/ //v1.0.4 fix (missing close comment...)
   
 //Clean Up Session Variables which would otherwise persist during Discount Processing       
  public function vtprd_maybe_clear_auto_add_session_vars() {
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    } 
    if (isset($_SESSION['previous_auto_add_array']))  {
        $contents = $_SESSION['previous_auto_add_array'];
        unset( $_SESSION['previous_auto_add_array'], $contents );    
    }
    if (isset($_SESSION['current_auto_add_array']))  {
        $contents = $_SESSION['current_auto_add_array'];
        unset( $_SESSION['current_auto_add_array'], $contents );    
    }
    if (isset($_SESSION['data_chain']))  {
        $contents = $_SESSION['data_chain'];
        unset( $_SESSION['data_chain'], $contents );    
    }    
    
    global  $vtprd_info;
    $coupon_title = $vtprd_info['coupon_code_discount_deal_title'];
    $this->vtprd_woo_maybe_remove_coupon_from_cart($coupon_title);
       
    return;    
  }

 /*
    also:  in wpsc-includes/purchase-log-class.php  (from 3.9)
		do_action( 'wpsc_sales_log_process_bulk_action', $current_action );
  */
	public function vtprd_pro_lifetime_log_roll_out($log_id ){  
    if ( (is_admin()) && (defined('VTPRD_PRO_DIRNAME')) ) {     
       vtprd_maybe_lifetime_roll_log_totals_out($log_id);
    }
    return;   
  }

 /*
    also:  in wpsc-includes/purchase-log-class.php  (from 3.9)
 		do_action( 'wpsc_purchase_log_before_delete', $log_id ); 
  */
	public function vtprd_pro_lifetime_bulk_log_roll_out($current_action){  
    if ( (is_admin()) && (defined('VTPRD_PRO_DIRNAME')) ) {     
       vtprd_maybe_lifetime_bulk_roll_log_totals_out($current_action);
    }
    return;   
  }
   
    
   
} //end class
$vtprd_parent_cart_validation = new VTPRD_Parent_Cart_Validation;
