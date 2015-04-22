<?php

class VTPRD_Parent_Cart_Validation {
	
	public function __construct(){

  /*
  
*Woo Bug - If Discount applied via Woo Coupon, and taxes are Off, WOO nonetheless
		may report the coupon amount **with tax added** 
		- if Tax Rates for the "Standard" Class 
			is set to apply a tax regardless of country code
		However, when transaction is processed, 
		the **correct discount amount has been applied**.  Go figure.
  */

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
 
   
    //DISPLAY RULE INITIAL Price check - Catalog pricing filters/actions => returns HTML PRICING for display
    //********************************************************************************************************************
    
    //**********======================================================================================
    //NEED both these filters and the woocommerce_get_price filter to support both 
    //  standard products (priced in woocommerce_get_price in the catalog display)
    //      and 
    //  variation products (priced in one a variaty of the _html filters in AJAX)
    //**********======================================================================================
        
//v1.0.9.1  no globals here
//v1.0.9.1    global $vtprd_info, $vtprd_setup_options;  //v1.0.9.0
    
    //Only do these if there's an active display rule

//v1.0.9.1  moved if statement to function
//v1.0.9.1    if ($vtprd_info['ruleset_has_a_display_rule'] == 'yes') {   //v1.0.9.0
 
      //???v1.0.9.0 covered by 'woocommerce_get_price_html'
      //  add_filter('woocommerce_grouped_price_html',          array(&$this, 'vtprd_maybe_grouped_price_html'), 10, 2);
     
      //v1.0.9.0 covered by 'woocommerce_get_price_html'
      //  add_filter('woocommerce_variable_sale_price_html',    array(&$this, 'vtprd_maybe_variable_sale_price_html'), 10, 2);
    
      //v1.0.9.0 covered by 'woocommerce_get_price_html'
      //    add_filter('woocommerce_variable_price_html',         array(&$this, 'vtprd_maybe_variable_price_html'), 10, 2);  //v1.0.9.0
        
//v1.0.9.0 NOW UNNECESSARY??        add_filter('woocommerce_variation_price_html',        array(&$this, 'vtprd_maybe_catalog_price_html'), 10, 2);
      //v1.0.9.0 covered by 'woocommerce_get_variation_price_html'
      //  add_filter('woocommerce_variation_price_html',        array(&$this, 'vtprd_maybe_catalog_price_html'), 10, 2);
        //normal get price
     //v1.0.9.0 covered by 'woocommerce_get_variation_price_html'
     //   add_filter('woocommerce_variation_sale_price_html',   array(&$this, 'vtprd_maybe_catalog_price_html'), 10, 2);
            
      //v1.0.9.0 covered by 'woocommerce_get_price_html'
      //  add_filter('woocommerce_sale_price_html',             array(&$this, 'vtprd_maybe_catalog_price_html'), 10, 2);
        
      //v1.0.9.0 covered by 'woocommerce_get_price_html'
      //  add_filter('woocommerce_price_html',                  array(&$this, 'vtprd_maybe_catalog_price_html'), 10, 2);
     
      //v1.0.9.0 covered by 'woocommerce_get_price_html'
       // add_filter('woocommerce_empty_price_html',            array(&$this, 'vtprd_maybe_catalog_price_html'), 10, 2);

        //v1.0.9.0   MOVED HERE  ==>>  THIS IS EXECUTED as often as "woocommerce_get_price"
        //NOT needed for CART rules, but needed for catalog
        

        add_filter('woocommerce_get_price_html',              array(&$this, 'vtprd_maybe_catalog_price_html'), 10, 2);
        add_filter('woocommerce_get_variation_price_html',    array(&$this, 'vtprd_maybe_catalog_variation_price_html'), 10, 2);  //v1.0.9.3 changes to sep function
         
//v1.0.9.1    }

    // =====================++++++++++
    //get_price is used in the line subtotal, cart subtotal and total....
    //****************
    //v1.0.9.0 begin
    //****************
    //If discount is taken for UnitPrice, no further processing, handled in "before_calculate_totals"
    
/*  REMOVE THIS, BEING RUN TOO OFTEN
    if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  {
//NOT needed for CART rules!!!!!!!!!!!!!!
//      add_filter('woocommerce_get_price',                   array(&$this, 'vtprd_maybe_get_price'), 10, 2);    
      add_filter('woocommerce_get_price_html',                   array(&$this, 'vtprd_maybe_catalog_price_html'), 10, 2);
    
    }
    */



    // =====================++++++++++
    // inline-pricing unit pricing discount updates...
    // =====================++++++++++
      //v1.0.9.3  mini cart => manually load the new unit prices/catalog pricing, as needed
       add_action('woocommerce_before_mini_cart',            array(&$this, 'vtprd_maybe_before_mini_cart'), 10, 1   );
  
      //run it all the time!
       add_action('woocommerce_before_calculate_totals',     array(&$this, 'vtprd_maybe_before_calculate_totals'), 10, 1  );
      
      //Pick up the plugin user tax exempt flag/and/or the Role cap "buy_tax_free"   and apply it UNIVERSALLY!! 
       add_action('woocommerce_init',                        array(&$this, 'vtprd_set_woo_customer_tax_exempt'), 10, 1  );
       
       //v1.0.9.3  Supply discountUnitPrice crossout
       add_action('woocommerce_cart_item_price',             array(&$this, 'vtprd_maybe_cart_item_price_html'), 10, 3  );
       
       //v1.0.9.3  Unit Price 'you save' message for whole cart
 //      add_action('woocommerce_checkout_after_order_review', array(&$this, 'vtprd_maybe_unit_price_checkout_msg'), 10 );
        
    // =====================++++++++++
    //v1.0.9.0 end
    // =====================++++++++++
   

    //-END- CATALOG DISPLAY Filters / Actions

    
    
    //---------------------------- 
    //CART AND CHECKOUT Actions
    //----------------------------  
    

    //'woocommerce_cart_updated' RUNS EVERY TIME THE CART OR CHECKOUT PAGE DISPLAYS!!!!!!!!!!!!!
    add_action( 'woocommerce_cart_updated',                   array(&$this, 'vtprd_cart_updated') );   //AFTER cart update completed, all totals computed
    add_action( 'wp_login',                                   array(&$this, 'vtprd_update_on_login_change'), 10, 2 );   //v1.0.8.4   re-applies rules on login immediately!
    
    add_action( 'wp_logout',                                  array(&$this, 'vtprd_update_on_login_change'), 10, 2 );   //v1.0.9.4   re-applies rules on logout immediately!
             
    //this runs BEFORE the qty is zeroed, not much use...
    //add_action( 'woocommerce_before_cart_item_quantity_zero', array(&$this, 'vtprd_test_quantity_zero'), 10,1 );     //cart_item_removed

    //*************************
    //COUPON PROCESSING
    //*************************
    //add or remove Pricing Deals 'dummy' fixed_cart coupon
    //   NEED BOTH to pick up going to view cart and going directly to checkout.  Exits quickly if already done.
//v1.0.9.1  moved if statement to function
//v1.0.9.1     if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  {   //v1.0.9.0    not needed for inline-pricing
      add_filter( 'woocommerce_before_cart_table',     array(&$this, 'vtprd_woo_maybe_add_remove_discount_cart_coupon'), 10);
      add_filter( 'woocommerce_checkout_init',         array(&$this, 'vtprd_woo_maybe_add_remove_discount_cart_coupon'), 10);
        
      //change the value of the Pricing Deals 'dummy' coupon instance to the Pricing Deals discount amount
      add_filter( 'woocommerce_get_shop_coupon_data',  array(&$this, 'vtprd_woo_maybe_load_discount_amount_to_coupon'), 10,2);
     
      //created in v1.0.9.0 , now no longer necessary
      //add_action( 'woocommerce_check_cart_items',               array(&$this, 'vtprd_maybe_update_coupon_on_check_cart_items'), 10 );   //v1.0.8.9 
          
//v1.0.9.1     }
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

    //allow routine to print some detail reporting as desired  v1.0.9.0 
//    if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  {   //v1.0.9.0    not needed for inline-pricing
      add_action( 'woocommerce_widget_shopping_cart_before_buttons', array(&$this, 'vtprd_maybe_print_widget_discount'), 10, 1 ); 
//    }  
    //*************************************************
    // Print Discounts at Checkout time
    //*************************************************        
    //In woocommerce/templates/cart/cart'        
   // add_action( 'woocommerce_cart_contents', array(&$this, 'vtprd_maybe_print_checkout_discount'), 10, 1 );
//*************************************************     

  //allow routine to print some detail reporting as desired  v1.0.9.0 
  //  if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  {   //v1.0.9.0    not needed for inline-pricing
      add_action( 'woocommerce_after_cart_table', array(&$this, 'vtprd_maybe_print_checkout_discount'), 10, 1 );
  //  }
//************************************************* 

    //Reapply rules only if an error occurred during processing regarding lifetime rule limits...         
    //the form validation filter executes ONLY at click-to-pay time                                                                      
    if (defined('VTPRD_PRO_DIRNAME')) {  //v1.0.8.0
      add_filter( 'woocommerce_before_checkout_process', array(&$this, 'vtprd_woo_validate_order'), 1);   
    }   
  
    //v1.0.7.2  needed if all prices are zero from Catalog rules, otherwise subtotal reflects list price!
    add_action( 'woocommerce_before_mini_cart', array(&$this, 'vtprd_maybe_recalc_woo_totals'), 10, 1 );  

    
    //*************************************************
    // Post-Purchase
    //*************************************************       
    //v1.0.9.0 Now applies to all uses of the cart
    //In classes/class-wc-checkout.php  function process_checkout() =>  just before the 'thanks' Order Acknowledgement screen    
    add_action('woocommerce_checkout_order_processed', array( &$this, 'vtprd_post_purchase_maybe_save_log_info' ), 10, 2);  //v1.0.9.0

    //Order Acknowledgment Email     
    //add discount reporting to customer email USING LOG INFO...
    //  $return = apply_filters( 'woocommerce_email_order_items_table', ob_get_clean(), $this );
    //      ob_get_clean() = the whole output buffer 
    //USING THIS filter in this way, puts discounts within the existing products table, after products are shown, but before the close of the table...     
//v1.0.9.1  moved if statement to function
//v1.0.9.1    if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  {   //v1.0.9.0    not needed for inline-pricing
      add_filter('woocommerce_email_order_items_table', array( &$this, 'vtprd_post_purchase_maybe_email' ), 10,2);
//v1.0.9.1     }
    
    // PRIOR to WOO version ++2.13++ - won't work - as this filter only does not have $order_info (2nd variable) in prior versions
    
    //Order Acknowledgement screen
    //add discount reporting to thankyou USING LOG INFO...
    //DON'T USE ANYMORE  add_filter('woocommerce_order_details_after_order_table', array( &$this, 'vtprd_post_purchase_maybe_thankyou' ), 10,1);
    
    //do_action( 'woocommerce_thankyou', $order->id );  IS EXECUTED in WOO to place order info on thankyou page.   Put our stuff in front of thankyou.
//v1.0.9.1  moved if statement to function
//v1.0.9.1    if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  {   //v1.0.9.0    not needed for inline-pricing
      add_filter('woocommerce_thankyou', array( &$this, 'vtprd_post_purchase_maybe_before_thankyou' ), -1,1); //put our stuff in front of thankyou
//v1.0.9.1    }
    //last filter/hook which uses the session variables, also nukes the session vars...
//    add_filter('woocommerce_checkout_order_processed', array( &$this, 'vtprd_post_purchase_maybe_purchase_log' ), 10,2);   

    //lifetime tables cleanup on log delete
    add_action('wpsc_purchase_log_before_delete',    array( &$this, 'vtprd_pro_lifetime_log_roll_out' ), 10, 1); 
    add_action('wpsc_sales_log_process_bulk_action', array( &$this, 'vtprd_pro_lifetime_bulk_log_roll_out' ), 10, 1); 
     
     
    
	} //end constructor
  
  

  //the form validation filter executes ONLY at click-to-pay time, just to access the global variables!!!!!!!!! 
	public function vtprd_woo_validate_order(){
    global $vtprd_rules_set, $vtprd_cart, $vtprd_setup_options, $vtprd_info, $woocommerce;
    vtprd_debug_options();  //v1.0.5    
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
//error_log( print_r(  'new Apply_rules in **vtprd_woo_validate_order', true ) );
        
        $vtprd_apply_rules = new VTPRD_Apply_Rules;   
        
        $vtprd_info['checkout_validation_in_process'] = 'no'; //v1.0.8.0  
      
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
        $data_chain[] = vtprd_get_current_user_role();  //v1.0.7.2
        $data_chain[] = $contents_total;
        $data_chain[] = $applied_coupons;
        $_SESSION['data_chain'] = serialize($data_chain);              
      } else {
      
        //Get the screen data...
        vtprd_get_purchaser_info_from_screen();       
      }
    }
    return;   
  } 	


	public function vtprd_maybe_grouped_price_html($price_html, $product_info){   
    global $post, $vtprd_info, $vtprd_setup_options; 
    vtprd_debug_options();  //v1.0.5 
    //in place of is_admin, which doesn't work in AJAX...
     if (is_admin() || ( defined('DOING_AJAX') && DOING_AJAX ) ) {  //v1.0.9.0  syntax cleaned up
 //error_log( print_r(  '001 $price_html= ' .$price_html, true ) );         
         return $price_html;
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
    $min_price_id = ''; //v1.0.7.4 moved here
    if ($from) {
    		$child_prices = array();
    		$all_children = $product_info->get_children();
        foreach ( $all_children as $child_id ) {
    			//changed to use the $child_id as key
          $child_prices[$child_id] = get_post_meta( $child_id, '_price', true );
                                                             //*********          
        }        
    		
        if (count($child_prices) == 0) {
 //error_log( print_r(  '002 $price_html= ' .$price_html, true ) ); 
          return $price_html;
        }

        $min_price = ''; 
        //find min
        foreach ($child_prices as $key => $child_price) {
          if ( ($child_price < $min_price) || ($min_price = '') ) {
            $min_price =  $child_price;
            $min_price_id = $key;
          }        
        }
        
        //if no min price found, nothing to do
        if (!$min_price_id) {
 //error_log( print_r(  '003 $price_html= ' .$price_html, true ) );         
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
    
     //v1.0.9.0 begin
    //if we already have the html price, no need to reprocess
    //test min price ID
     if(isset($_SESSION['vtprd_product_session_price_'.$product_id])) { 
       $price_html = stripslashes($_SESSION['vtprd_product_session_price_'.$product_id]);
 //error_log( print_r(  '004 $price_html= ' .$price_html, true ) );        
       return $price_html; 
     }     
     //v1.0.9.0 end      
    
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

    if ($vtprd_info['product_session_info']['product_yousave_total_amt'] > 0)  {     //v1.0.7.2  replaced 'product_discount_price' with 'product_yousave_total_amt' to pick up a FREE discount
      /*  //v1.0.7.4 replaced with below
      if ($vtprd_setup_options['show_catalog_price_crossout'] == 'yes')  {
        $price_html = '<del>' . $vtprd_info['product_session_info']['product_list_price_html_woo']  . '</del><ins>' .$from. ' ' . $vtprd_info['product_session_info']['product_discount_price_html_woo'] . '</ins>'; 
      } else {
        $price_html = $from. ' ' . $vtprd_info['product_session_info']['product_discount_price_html_woo'];  
      }
      */
      $price_html = $this->vtprd_show_shop_price_html(); //v1.0.7.4 
    } 
    
    $_SESSION['vtprd_product_session_price_'.$product_id] = $price_html; //v1.0.9.0  
 //error_log( print_r(  '005 $price_html= ' .$price_html, true ) ); 
    return $price_html;

      
  }


	public function vtprd_maybe_variable_sale_price_html($price_html, $product_info){    
    global $post, $vtprd_info, $vtprd_setup_options;
    vtprd_debug_options();  //v1.0.5 
    //in place of is_admin, which doesn't work in AJAX...
     if ( function_exists( 'get_current_screen' ) ) {  // get_current_screen ONLY exists in ADMIN!!!   
       if ($post->post_type == 'product'  ) {    //in admin, don't run this on the PRODUCT screen!!
 //error_log( print_r(  '006 $price_html= ' .$price_html, true ) );          
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
 //error_log( print_r(  '007 $price_html= ' .$price_html, true ) );           
          return $price_html;
        }
        
        $min_price = '';
        $min_price_id = ''; //v1.0.7.7
        //find min
        foreach ($child_prices as $key => $child_price) {
          if ( ($child_price < $min_price) || ($min_price = '') ) {
            $min_price =  $child_price;
            $min_price_id = $key;
          }        
        }
        
        //if no min price found, nothing to do
        if (!$min_price_id) {
 //error_log( print_r(  '008 $price_html= ' .$price_html, true ) ); 
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
    
    
    //v1.0.9.0 begin
    //if we already have the html price, no need to reprocess
    //test min price id
     $product_id = isset($product_info->variation_id) ? $product_info->variation_id : $product_info->id;
     if(isset($_SESSION['vtprd_product_session_price_'.$product_id])) { 
       $price_html = stripslashes($_SESSION['vtprd_product_session_price_'.$product_id]);
 //error_log( print_r(  '009 $price_html= ' .$price_html, true ) ); 
       return $price_html; 
     }     
   //v1.0.9.0 end    
    
    
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

    if ($vtprd_info['product_session_info']['product_yousave_total_amt'] > 0)  {     //v1.0.7.2  replaced 'product_discount_price' with 'product_yousave_total_amt' to pick up a FREE discount
      /*  //v1.0.7.4 replaced with below
      if ($vtprd_setup_options['show_catalog_price_crossout'] == 'yes')  {
        $price_html = '<del>' . $vtprd_info['product_session_info']['product_list_price_html_woo']  . '</del><ins>' .$from. ' ' . $vtprd_info['product_session_info']['product_discount_price_html_woo'] . '</ins>'; 
      } else {
        $price_html = $from. ' ' . $vtprd_info['product_session_info']['product_discount_price_html_woo'];  
      }
      */
      $price_html = $this->vtprd_show_shop_price_html(); //v1.0.7.4 
    } 

    $_SESSION['vtprd_product_session_price_'.$product_id] = $price_html; //v1.0.9.0  
 //error_log( print_r(  '010 $price_html= ' .$price_html, true ) ); 
    return $price_html;

 }  

  /*
  Used by AJAX to get variation prices during catalog display!!!
  */
	public function vtprd_maybe_catalog_price_html($price_html, $product_info){    
//error_log( print_r(  'immed exit $price_html= ' .$price_html, true ) );   

    global $post, $vtprd_info, $vtprd_setup_options;
 //error_log( print_r(  'vtprd_maybe_catalog_price_html REGULAR IN price = ' .$price_html, true ) ); 
//error_log( print_r(  '$product_info', true ) );
//error_log( var_export($product_info, true ) );    

      
    vtprd_debug_options();  //v1.0.5  
//return $price_html;
//error_log( print_r(  'immed exit $price_html= ' .$price_html, true ) );   
//return $price_html;

 //error_log( print_r(  'Begin vtprd_maybe_catalog_price_html $price_html=  ' .$price_html, true ) );
 //error_log( var_export($product_info, true ) );
 //error_log( print_r(  ' ', true ) );
    //in place of is_admin, which doesn't work in AJAX...
     if (is_admin() || ( defined('DOING_AJAX') && DOING_AJAX ) ) {  //v1.0.9.0  syntax cleaned up
 //error_log( print_r(  '011 $price_html= ' .$price_html, true ) );         
         return $price_html;
     }
//return $price_html;  
    if ($product_info->variation_id > ' ') {         
      $product_id  = $product_info->variation_id;
    } else { 
      if ($product_info->id > ' ') {
        $product_id  = $product_info->id;
      } else {
        $product_id  = $product_info->product_id;
      }     
    }
//return $price_html;
//echo '<pre> $product_info->variation_id = '.print_r($product_info->variation_id, true).'</pre>' ; 
//echo '<pre> $product_info->id = '.print_r($product_info->id, true).'</pre>' ; 
//echo '<pre> $product_info->product_id = '.print_r($product_info->product_id, true).'</pre>' ; 

//echo '<pre> $product_info = '.print_r($product_info, true).'</pre>' ; 

    //v1.0.9.3 begin
    //moved here to store vtprd_product_old_price, used in showing cart crossouts
    if ($vtprd_info['ruleset_has_a_display_rule'] != 'yes') {   //v1.0.9.1 
      if(!isset($_SESSION['vtprd_product_old_price_'.$product_id])) { 
        $_SESSION['vtprd_product_old_price_'.$product_id] = $price_html;
      } 
      return $price_html;
    } 
    //v1.0.9.3 end
    
    //v1.0.9.0 begin
    //if we already have the html price, no need to reprocess
     if(isset($_SESSION['vtprd_product_session_price_'.$product_id])) { 
       $price_html = stripslashes($_SESSION['vtprd_product_session_price_'.$product_id]);
 //error_log( print_r(  '012 $price_html= ' .$price_html, true ) ); 
 //error_log( print_r(  'vtprd_maybe_catalog_price_html OUT 1 price = ' .$price_html, true ) );      
      return $price_html; 
     }     
     //v1.0.9.0 end

    //***************
    //v1.0.9.3 begin
    //***************
    
    /*
    example of variable pricing line coming here: 
    
    "<del><span class="amount">&pound;110.00</span>&ndash;<span class="amount">&pound;330.00</span></del> <ins><span class="amount">&pound;55.00</span>&ndash;<span class="amount">&pound;330.00</span></ins>"
        OR
    "<span class="amount">&pound;100.00</span>&ndash;<span class="amount">&pound;300.00</span>"  
    
    If '<del>' found, strip out everything up to and including '<ins>', strip out </ins>
      $oldprice = stripped info
    else
      $oldprice = passed info as it stands 
      
    Wrap $oldprice in '<del>' '</del>' 
    
    Then do new pricing.
    
     */ 
     
    //v1.0.9.5 begin
    //catches product widget sending variable type info with no children listed
    if ( ($product_info->product_type == 'variable') &&
         (sizeof($product_info->children) == 0) ) {  
      $product_info->get_children();   
    }    
    //v1.0.9.5 end      
     
    if ($product_info->product_type == 'variable')  {  
      //This is the 1st time thru for the original WOO message.  Parse the message for use later.
      $del_end = '</del>';
      $span_end = '</span>'; 
          
      //is this <del>/<ins> structure
      if (strpos($price_html,'</del>') !== false) {  //BOOLEAN == true...
        //strip out everything up to and including '<ins>', strip out </ins>
        // place result into  $oldPrice
        
        //splits string on <ins>, removes <ins>
        $oldPrice_array1 = explode('<ins>', $price_html); //this removes the delimiter string... 
        
        //splits string on </ins>, removes </ins> (we will work on the 2nd half of original string)
        $oldPrice_array2 = explode('</ins>', $oldPrice_array1[1]); //this removes the delimiter string... 
        $oldPrice = $oldPrice_array2[0];
        
      } else {
        $oldPrice = $price_html;
      }
      
      if(!isset($_SESSION['vtprd_product_old_price_'.$product_id])) { 
        $_SESSION['vtprd_product_old_price_'.$product_id] = $oldPrice; //used in showing cart crossouts 
      }
      
      //splits "<span class="amount">&pound;100.00</span>&ndash;<span class="amount">&pound;300.00</span>"  into:
      //  "<span class="amount">&pound;100.00"
      //  "&ndash;<span class="amount">&pound;300.00"
      $oldprice_split_array = explode('</span>', $oldPrice); 
      //add '</span>' back into both occurrences
      $oldprice_split_array[0] .= '</span>';
      $oldprice_split_array[1] .= '</span>';
       
      //v1.0.9.5 begin
      
      $vtprd_info['current_processing_request'] = 'display';
      $justThePricing = 'yes';
      $discount_found = false;
      
      $first_child_price_hold = 99999;
      $last_child_price_hold = 0;
      
      $sizeof_children = sizeof($product_info->children);
    
      //sort for least/most expensive
      for($k=0; $k < $sizeof_children; $k++) {
        vtprd_get_product_session_info($product_info->children[$k]);
        
        //v1.0.9.7 begin
        if ($vtprd_info['product_session_info']['product_yousave_total_amt'] > 0) {
          $current_price = $vtprd_info['product_session_info']['product_discount_price'];
        } else {
          $current_price = $vtprd_info['product_session_info']['product_unit_price'];
        }
        //v1.0.9.7 end
      
        if ($current_price < $first_child_price_hold) { //v1.0.9.7 
          $first_child_price_hold = $current_price; //v1.0.9.7 
          $first_child_price_ID_hold = $product_info->children[$k];
          $first_child_session_hold = $vtprd_info['product_session_info'];
        } 
        //most expensive could be first one...
        if ($current_price > $last_child_price_hold) { //v1.0.9.7 
          $last_child_price_hold = $current_price; //v1.0.9.7 
          $last_child_price_ID_hold = $product_info->children[$k];
          $last_child_session_hold = $vtprd_info['product_session_info'];
        }       
      } 
      
      $vtprd_info['product_session_info'] = $first_child_session_hold;
      if ($vtprd_info['product_session_info']['product_yousave_total_amt'] > 0)  { 
        $first_child_price = $this->vtprd_show_shop_price_html($justThePricing);
        $first_child_price_html = '<span class="amount">' . $first_child_price .'</span>';
        $discount_found = true;
      } else {
        $first_child_price_html = $oldprice_split_array[0];  
      }
      
      $vtprd_info['product_session_info'] = $last_child_session_hold;
      if ($vtprd_info['product_session_info']['product_yousave_total_amt'] > 0)  { 
        $last_child_price = $this->vtprd_show_shop_price_html($justThePricing);
        $last_child_price_html = '&ndash;<span class="amount">' . $last_child_price .'</span>';
        $discount_found = true;
      } else {
        $last_child_price_html = $oldprice_split_array[1];
      }

/* 
error_log( print_r(  '$first_child_session_hold= ', true ) );
error_log( var_export($first_child_session_hold, true ) );  
error_log( print_r(  '$first_child_price_html= ' .$first_child_price_html, true ) );
 
error_log( print_r(  '$last_child_session_hold= ', true ) );
error_log( var_export($last_child_session_hold, true ) );  
error_log( print_r(  '$last_child_price_html= ' .$last_child_price_html, true ) );
error_log( print_r(  '$oldPrice= ' .$oldPrice, true ) );
*/            
      //v1.0.9.5 end     
      
     /* //v1.0.9.5 replaced by above       
      //find the new values for the 1st and last entries in the variable product list...
      $first_child = $product_info->children[0];
      $sizeof_children = sizeof($product_info->children);
      //starts at 1, since 0 already processed - at end, last_chold contains last iteration.
      for($k=1; $k < $sizeof_children; $k++) {
        $last_child = $product_info->children[$k];
      }
      
      $vtprd_info['current_processing_request'] = 'display';
      $justThePricing = 'yes';
      $discount_found = false;
      
      vtprd_get_product_session_info($first_child);
      if ($vtprd_info['product_session_info']['product_yousave_total_amt'] > 0)  { 
        $first_child_price = $this->vtprd_show_shop_price_html($justThePricing);
        $first_child_price_html = '<span class="amount">' . $first_child_price .'</span>';
        $discount_found = true;
      } else {
        $first_child_price_html = $oldprice_split_array[0];  
      }
      
      vtprd_get_product_session_info($last_child);
      if ($vtprd_info['product_session_info']['product_yousave_total_amt'] > 0)  { 
        $last_child_price = $this->vtprd_show_shop_price_html($justThePricing);
        $last_child_price_html = '&ndash;<span class="amount">' . $last_child_price .'</span>';
        $discount_found = true;
      } else {
        $last_child_price_html = $oldprice_split_array[1];
      }
      */
      
 //error_log( print_r(  '$discount_found = ' .$discount_found, true ) );
      //build $price_html
      if ($discount_found) {  //if no discount, original value of $price_html is used
        
        //v1.0.9.6  begin 
        if  ($first_child_price_hold == $last_child_price_hold) {
          $newprice = $first_child_price_html;  //don't show 2nd price if both are the same!!
        } else {
          $newprice = $first_child_price_html . $last_child_price_html ;
        }   
        //v1.0.9.6  end
        
        if ($vtprd_setup_options['show_catalog_price_crossout'] == 'yes')  {  
          //create 'del' string
          $price_html  = '<del>' .$oldPrice .'</del>'; //created above from input 
          
          //$price_html .= '<ins>' .$first_child_price_html . $last_child_price_html .'</ins>';   //v1.0.9.6  replaced
          $price_html .= '<ins>' .$newprice .'</ins>'; //v1.0.9.6 
                
          //attach 'ins' string        
        } else {       
          //add new price to string
          
          //$price_html =  $first_child_price_html . $last_child_price_html;  //v1.0.9.6  replaced
          $price_html =  $newprice;  //v1.0.9.6 
        }  

        
        //add in WOO suffix 
        $price_html .= $vtprd_info['product_session_info']['product_discount_price_suffix_html_woo'];
        
        //add in Pricing suffix
        $price_html = $this->vtprd_maybe_show_pricing_suffix($price_html);
      }       
                      
    } else {

      
      $vtprd_info['current_processing_request'] = 'display';
      $price = $product_info->price;
           
      vtprd_get_product_session_info($product_id,$price);
  
   
      $from = strstr($price_html, 'From') !== false ? ' From ' : ' ';
       
      if ($vtprd_info['product_session_info']['product_yousave_total_amt'] > 0)  {     //v1.0.7.2  replaced 'product_discount_price' with 'product_yousave_total_amt' to pick up a FREE discount
        /*  //v1.0.7.4 replaced with below
        if ($vtprd_setup_options['show_catalog_price_crossout'] == 'yes')  {
          $price_html = '<del>' . $vtprd_info['product_session_info']['product_list_price_html_woo']  . '</del><ins>' .$from. ' ' . $vtprd_info['product_session_info']['product_discount_price_html_woo'] . '</ins>'; 
        } else {
          $price_html = $from. ' ' . $vtprd_info['product_session_info']['product_discount_price_html_woo'];  
        }
        */
        $price_html = $this->vtprd_show_shop_price_html(); //v1.0.7.4 
      }
      
      if(!isset($_SESSION['vtprd_product_old_price_'.$product_id])) { 
        $_SESSION['vtprd_product_old_price_'.$product_id] = $price_html; //used in showing cart crossouts 
      }       
    }
    //v1.0.9.3 end
    
    $_SESSION['vtprd_product_session_price_'.$product_id] = $price_html; //v1.0.9.0  
    
 //error_log( print_r(  'vtprd_maybe_catalog_price_html OUT 2 price = ' .$price_html, true ) ); 
     
    return $price_html;
 

 } 

	//v1.0.9.3 new function
  // only called when individual variation pricing needed....
  public function vtprd_maybe_catalog_variation_price_html($price_html, $product_info){    
//error_log( print_r(  'immed exit $price_html= ' .$price_html, true ) );   

    global $post, $vtprd_info, $vtprd_setup_options;
//error_log( print_r(  'vtprd_maybe_catalog_variation_price_html VARIATION IN price = ' .$price_html, true ) );   

      
    vtprd_debug_options();  //v1.0.5  

    //in place of is_admin, which doesn't work in AJAX...
     if (is_admin() || ( defined('DOING_AJAX') && DOING_AJAX ) ) {  //v1.0.9.0  syntax cleaned up
         return $price_html;
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
//error_log( print_r(  'Product ID = ' .$product_id, true ) );
    //v1.0.9.3 begin
    //moved here to store vtprd_product_old_price, used in showing cart crossouts
    if ($vtprd_info['ruleset_has_a_display_rule'] != 'yes') {   //v1.0.9.1 
      if(!isset($_SESSION['vtprd_product_old_price_'.$product_id])) { 
 //error_log( print_r(  'OLD PRICE EXIT' .$product_id . $price_html, true ) );     
        $_SESSION['vtprd_product_old_price_'.$product_id] = $price_html;
      } 
      return $price_html;
    } 
    //v1.0.9.3 end

    //v1.0.9.0 begin
    //if we already have the html price, no need to reprocess
     if(isset($_SESSION['vtprd_product_session_price_'.$product_id])) { 
       $price_html = stripslashes($_SESSION['vtprd_product_session_price_'.$product_id]);

//error_log( print_r(  'vtprd_maybe_catalog_price_html VARIATION OUT 1 price = ' .$price_html, true ) );
       
      return $price_html; 
     }     
     //v1.0.9.0 end

    $vtprd_info['current_processing_request'] = 'display';
    $price = $product_info->price;
         
    vtprd_get_product_session_info($product_id,$price);

 
    $from = strstr($price_html, 'From') !== false ? ' From ' : ' ';

            
    if ($vtprd_info['product_session_info']['product_yousave_total_amt'] > 0)  {     //v1.0.7.2  replaced 'product_discount_price' with 'product_yousave_total_amt' to pick up a FREE discount
      /*  //v1.0.7.4 replaced with below
      if ($vtprd_setup_options['show_catalog_price_crossout'] == 'yes')  {
        $price_html = '<del>' . $vtprd_info['product_session_info']['product_list_price_html_woo']  . '</del><ins>' .$from. ' ' . $vtprd_info['product_session_info']['product_discount_price_html_woo'] . '</ins>'; 
      } else {
        $price_html = $from. ' ' . $vtprd_info['product_session_info']['product_discount_price_html_woo'];  
      }
      */
      $price_html = $this->vtprd_show_shop_price_html(); //v1.0.7.4 
    } 
 //error_log( print_r(  'End vtprd_maybe_catalog_price_html $price_html= ' .$price_html, true ) );
    
    //MUST be HERE!!
    //v1.0.9.3 begin
    if(!isset($_SESSION['vtprd_product_old_price_'.$product_id])) { 
      $_SESSION['vtprd_product_old_price_'.$product_id] = $price_html;
    }
    //v1.0.9.3 end
    //*/
        
    $_SESSION['vtprd_product_session_price_'.$product_id] = $price_html; //v1.0.9.0  
 //error_log( print_r(  'vtprd_maybe_catalog_price_html VARIATION OUT 2 price = ' .$price_html, true ) );     
    return $price_html;

 }  
 
  
  //v1.0.9.0 new function
  /* ***********************************************************  
  **  Spin through the woo cart, and for inline price discounts, put discounts into unit price.
  **    so EVERY TIME the cart displays , the pricing is altered HERE, if needed  
  ************************************************************** */
	public function vtprd_maybe_variable_price_html($price_html, $product){    
    global $woocommerce, $vtprd_info, $vtprd_setup_options, $vtprd_cart, $vtprd_cart_item, $vtprd_rules_set;
    vtprd_debug_options(); 
 //error_log( print_r(  'Begin vtprd_maybe_variable_price_html ', true ) );

     //don't run in admin or ajax...
     if (is_admin() || ( defined('DOING_AJAX') && DOING_AJAX ) ) { 
        return $price_html; 
     }
     
     
    //if we already have the html price, no need to reprocess
     //PARENT ID holds whole array, if there
     $parent_product_id = $product->id;
     if(isset($_SESSION['vtprd_product_session_price_'.$parent_product_id])) { 
       $price_html = stripslashes($_SESSION['vtprd_product_session_price_'.$product_id]);
 //error_log( print_r(  '014 $price_html= ' .$price_html, true ) );      
       return $price_html; 
     }     
     //v1.0.9.0 end

		$variations = $product->get_available_variations();
		$varPrice_array = array();
    $varPrice_array_with_suffix = array();
		
		foreach ($variations as $variation){
      $product_id = $variation['variation_id'];      
//error_log( print_r(  'FROM vtprd_maybe_variable_price_html', true ) );     
      vtprd_maybe_get_price_single_product($product_id);
			$varPrice = $this->vtprd_show_shop_price(); 
      $varPrice_array[] = $varPrice;
      
      //store array for later suffix retrieval
      $suffix = $vtprd_info['product_session_info']['product_discount_price_suffix_html_woo'];
      $varPrice_array_with_suffix[] = array (
        'varPrice' => $varPrice,
        'suffix'   => $suffix
      );

		}
		   
    array_multisort($varPrice_array, SORT_ASC);
		$varPrice_min = min($varPrice_array);
		$varPrice_max = max($varPrice_array);

    //get min price suffix
    $suffix = '';
    for($s=0; $s < sizeof($varPrice_array_with_suffix); $s++) {
      if ($varPrice_min == $varPrice_array_with_suffix[$s]['varPrice']) {
        $suffix = $varPrice_array_with_suffix[$s]['suffix'];
        break;
      }
    }


		if ($varPrice_min == $varPrice_max){ 
			$price_html = woocommerce_price($varPrice_min) . ' ' . $suffix;
		} else { 
			$price_html = woocommerce_price($varPrice_min).' - '.woocommerce_price($varPrice_max) . ' ' . $suffix; 
		}

    //SUFFIX
 //error_log( print_r(  '$parent_product_id= ' .$parent_product_id, true ) );
 //error_log( print_r(  '$product array', true ) );
 //error_log( var_export($product, true ) );
 //error_log( print_r(  '$vtprd_info out of vtprd_maybe_variable_price_html', true ) );
 //error_log( var_export($vtprd_info, true ) );    
 //error_log( print_r(  'end vtprd_maybe_variable_price_html, price= ' .$price_html, true ) );
 //error_log( print_r(  '$_SESSION', true ) );
 //error_log( var_export($_SESSION, true ) ); 

    //store price under PARENT ID
    $_SESSION['vtprd_product_session_price_'.$parent_product_id] = $price_html; //v1.0.9.0 
 //error_log( print_r(  '015 $price_html= ' .$price_html, true ) );
    return $price_html;
 } 


 
  //v1.0.9.3 new function
  /* ***********************************************************  
  **  Refresh the mini-cart numbers as needed ==> UnitPrice AND coupon both  
  ************************************************************** */
	public function vtprd_maybe_before_mini_cart(){ 
    global $woocommerce, $vtprd_info, $vtprd_setup_options, $vtprd_cart, $vtprd_cart_item, $vtprd_rules_set;
//error_log( print_r(  'IN vtprd_maybe_before_mini_cart ' , true ) );
    
    vtprd_debug_options();  //v1.1
    
    if ($vtprd_cart == null) {
       $data_chain = $this->vtprd_get_data_chain();
      if ($vtprd_cart == null) {  //haven't had the cart call yet...         
        return;
      } 
    } 
    
    $mini_cart_updated = false;
    $cart_object =  $woocommerce->cart->get_cart();
    
//error_log( print_r(  '$cart_object= ' , true ) );
//error_log( var_export($cart_object , true ) ); 
  
    foreach ( $cart_object as $key => $value ) {
 
      //price already at zero, no update needed
      if ($value['data']->price == 0) {
        continue;
      }
      
      if ($value['variation_id'] > ' ') {      
          $woo_product_id  =  $value['variation_id'];
      } else { 
          $woo_product_id  =  $value['product_id'];
      }
    
      if ($vtprd_setup_options['discount_taken_where'] == 'discountUnitPrice')  {

        foreach($vtprd_cart->cart_items as $vtprd_key => $vtprd_cart_item) {      
  
  //error_log( print_r(  'IN vtprd_maybe_before_mini_cart FOReach', true ) );        
  
   //error_log( print_r(  'discount_price= ' .$vtprd_cart_item->discount_price . ' for PRODUCT= ' .$vtprd_cart_item->product_id , true ) ); 
   //error_log( print_r(  'product_inline_discount_price_woo= ' .$vtprd_cart_item->product_inline_discount_price_woo, true ) ); 
    //error_log( print_r(  '$vtprd_cart_item ' , true ) );
    //error_log( var_export($vtprd_cart_item , true ) );       
          
          if ($vtprd_cart_item->product_id == $woo_product_id ) {
  
          //this will now pick up BOTH inline discounts, and solo CATLOG discounts...
  
              switch( true ) {
                case ( ($vtprd_cart_item->product_inline_discount_price_woo > 0) ||  
                      (($vtprd_cart_item->product_inline_discount_price_woo == 0) &&  //price can be zero if item is free
                       ($vtprd_cart_item->product_discount_price_woo == 0) &&  //regular discount price must also be zero
                       ($vtprd_cart_item->yousave_total_amt > 0)) ):                  //there is a discount...
                    //v1.0.9.3 spec begin
                    $value['data']->price = $this->vtprd_choose_mini_cart_price($vtprd_cart_item);
                    //$vtprd_cart_item->product_inline_discount_price_woo;   //$vtprd_cart_item->discount_price;    //
                    //v1.0.9.3 spec end
                    $mini_cart_updated = true;
//error_log( print_r(  'case 1' , true ) );                    
                  break;
                case ($vtprd_cart_item->product_discount_price_woo > 0)  :               
                    $value['data']->price = $this->vtprd_choose_mini_cart_price($vtprd_cart_item);   //$vtprd_cart_item->discount_price;    //
                    $mini_cart_updated = true;
//error_log( print_r(  'case 2' , true ) );                    
                  break; 
                case ($vtprd_cart_item->unit_price < $value['data']->price )  :    //Pick up a **solo CATALOG price reduction**          
                    $value['data']->price = $vtprd_cart_item->unit_price;   
                    $mini_cart_updated = true;
//error_log( print_r(  'case 3' , true ) );                    
                  break;  
//default:
//error_log( print_r(  'NO CASE Action' , true ) ); 
//break;                   
              }
            

          }
          
        } //end foreach
        
      } else { //discountCoupon path
        //check to be sure any Catalog deal prices are reflected here - *** 2nd-nth time, these numbers are not reflected in the mini-cart ***
        vtprd_maybe_get_product_session_info($woo_product_id);
        
        if ( ( ($vtprd_info['product_session_info']['product_discount_price']  > 0) ||  
              (($vtprd_info['product_session_info']['product_discount_price'] == 0) &&  //price can be zero if item is free
               ($vtprd_info['product_session_info']['product_yousave_total_amt']  > 0)) )
                  &&
               ($vtprd_info['product_session_info']['product_discount_price'] < $value['data']->price ) )  {
          $value['data']->price = $vtprd_info['product_session_info']['product_discount_price'];  
          $mini_cart_updated = true;
        }
        
      }
    }
  
    if ($mini_cart_updated) {
      $_SESSION['internal_call_for_calculate_totals'] = true;           
      $woocommerce->cart->calculate_totals(); 
    }
         
    return;
 } 
 
 
  //v1.0.9.3 new function
  /* ***********************************************************  
  **  Unit price taxation choice for cart   
  ************************************************************** */
	public function vtprd_choose_mini_cart_price($vtprd_cart_item){ 
     global $woocommerce, $vtprd_info, $vtprd_setup_options, $vtprd_cart, $vtprd_cart_item, $vtprd_rules_set;
           
    $price = $vtprd_cart_item->product_inline_discount_price_woo;
    
    if ( get_option( 'woocommerce_calc_taxes' )  == 'yes' ) {
       switch (get_option('woocommerce_prices_include_tax')) {
          case 'yes':
              if (get_option('woocommerce_tax_display_cart')   == 'excl') {
 
                if (get_option( 'woocommerce_tax_display_shop' ) == 'incl' ) {
                  $price = $vtprd_cart_item->product_inline_discount_price_incl_tax_woo; 
   //error_log( print_r(  'vtprd_choose_mini_cart_price - 001a incl_tax= ' .$price , true ) );                 
                }          
              }   
             break;         
          case 'no':
              if (get_option('woocommerce_tax_display_cart')   == 'incl') { //v1.0.9.3
                if (get_option( 'woocommerce_tax_display_shop' ) == 'excl' ) {
                  $price = $vtprd_cart_item->product_inline_discount_price_excl_tax_woo; //TAX WILL BE added by WOo, don't do it here!
  //error_log( print_r(  'vtprd_choose_mini_cart_price - 003 excl_tax= ' .$price , true ) );                   
                }
              }           
             break;
       }          
    } 

  //error_log( print_r(  'vtprd_choose_mini_cart_price - 004 at exit= ' .$price , true ) );
    return $price;
 }    
 
  //v1.0.9.0 new function
  /* ***********************************************************  
  **  Spin through the woo cart, and for inline price discounts, put discounts into unit price.
  **    so EVERY TIME the cart displays , the pricing is altered HERE, if needed 
  **  refactored in v1.0.9.3   
  ************************************************************** */
	public function vtprd_maybe_before_calculate_totals($cart_object){    
    global $woocommerce, $vtprd_info, $vtprd_setup_options, $vtprd_cart, $vtprd_cart_item, $vtprd_rules_set;
    
         
    //v1.0.9.3 begin - 
    //	This switch is set to true wherever the plugin itself calls calculate_totals
    if (isset($_SESSION['internal_call_for_calculate_totals'])) {
      if ($_SESSION['internal_call_for_calculate_totals'] == true) {
 //error_log( print_r(  'BEGIN vtprd_maybe_before_calculate_totals, INTERNAL CALL ', true ) );      
        $_SESSION['internal_call_for_calculate_totals'] = false;
        return $cart_object;
      } 
    }
           
             
    vtprd_debug_options(); //v1.1
 //error_log( print_r(  'BEGIN vtprd_maybe_before_calculate_totals , $cart_object= ', true ) );
   //error_log( var_export($cart_object  , true ) ); 
    //get saved vtprd_cart with discount info
    $discount_already_processed_here = false; //v1.0.9.3
    if ($vtprd_cart == null) {
       $data_chain = $this->vtprd_get_data_chain();
      //just in case...
      if ($vtprd_cart == null) {  //haven't had the cart call yet...
         
        if (sizeof($cart_object->cart_contents) > 0) { 
 //error_log( print_r(  'GOTO vtprd_process_discount FROM vtprd_maybe_before_calculate_totals ', true ) );        
          $woocommerce_cart_contents = $woocommerce->cart->get_cart(); 
          $this->vtprd_process_discount();
          $discount_already_processed_here = true; //v1.0.9.3
          
        }
      } 
    }

    foreach ( $cart_object->cart_contents as $key => $value ) {
 
      if ($value['variation_id'] > ' ') {      
          $woo_product_id  =  $value['variation_id'];
      } else { 
          $woo_product_id  =  $value['product_id'];
      }
 //error_log( print_r(  ' ' , true ) );    
 //error_log( print_r(  '$woo_product_id= ' .$woo_product_id, true ) );


      foreach($vtprd_cart->cart_items as $vtprd_key => $vtprd_cart_item) {      
//error_log( print_r(  'IN vtprd_maybe_before_calculate_totals FOReach', true ) );        

 //error_log( print_r(  'discount_price= ' .$vtprd_cart_item->discount_price . ' for PRODUCT= ' .$vtprd_cart_item->product_id , true ) ); 
 //error_log( print_r(  'product_inline_discount_price_woo= ' .$vtprd_cart_item->product_inline_discount_price_woo, true ) ); 
  //error_log( print_r(  '$vtprd_cart_item ' , true ) );
  //error_log( var_export($vtprd_cart_item , true ) );       
        
        if ($vtprd_cart_item->product_id == $woo_product_id ) {

        //this will now pick up BOTH inline discounts, and solo CATLOG discounts...
           
           if ($vtprd_setup_options['discount_taken_where'] == 'discountUnitPrice')  {



              switch( true ) {
                case ( ($vtprd_cart_item->product_inline_discount_price_woo > 0) ||  
                      (($vtprd_cart_item->product_inline_discount_price_woo == 0) &&  //price can be zero if item is free
                       ($vtprd_cart_item->product_discount_price_woo == 0) &&  //regular discount price must also be zero
                       ($vtprd_cart_item->yousave_total_amt > 0)) ):                  //there is a discount...
                    $value['data']->price = $vtprd_cart_item->product_inline_discount_price_woo;   //$vtprd_cart_item->discount_price;    //
                  break;
                case ($vtprd_cart_item->product_discount_price_woo > 0)  :               
                    $value['data']->price = $vtprd_cart_item->product_inline_discount_price_woo;   //$vtprd_cart_item->discount_price;    //
                  break;
                case ($vtprd_cart_item->unit_price < $value['data']->price )  :    //Pick up a **solo CATALOG price reduction**          
                    $value['data']->price = $vtprd_cart_item->unit_price;   
                /*
                default :               
                    if ($vtprd_cart_item->unit_price > 0) {
                      $value['data']->price = $vtprd_cart_item->unit_price;   //$vtprd_cart_item->discount_price;    //
                    }
                */                        
                  break; 
              }
           }
            else {  //discount in coupon, just show unit_price, which already includes any Catalog discount
//NEW CODE!   NEW CODE!!!
             if ( ($vtprd_cart_item->product_discount_price_woo > 0) ||
                  ($vtprd_cart_item->unit_price < $value['data']->price) ) { //v1.0.9.3 pick up CATALOG-only discount when discountCoupon!!
                $value['data']->price = $vtprd_cart_item->unit_price;   //$vtprd_cart_item->discount_price;    //
             }
             /*
             if ($vtprd_cart_item->product_discount_price_woo > 0) {               
                $value['data']->price = $vtprd_cart_item->product_discount_price_woo;   //$vtprd_cart_item->discount_price; 
               // $value['data']->price = $vtprd_cart_item->product_inline_discount_price_woo;   //$vtprd_cart_item->discount_price; 
               $value['data']->price = $vtprd_cart_item->unit_price;  
             } else {
                if ($vtprd_cart_item->unit_price > 0) {
                  $value['data']->price = $vtprd_cart_item->unit_price;   //$vtprd_cart_item->discount_price;    //
                }
             }
             */
           }
          
           break;
        }
      }

    }

 //error_log( print_r(  'after massage', true ) );
 //error_log( print_r(  'data price= ' .$value['data']->price . ' for PRODUCT= ' .$vtprd_cart_item->product_id , true ) ); 
 //error_log( var_export($cart_object, true ) );

      
 //error_log( print_r(  '$vtprd_cart', true ) );
 //error_log( var_export($vtprd_cart, true ) ); 
 //error_log( print_r(  '$vtprd_rules_set', true ) );
 //error_log( var_export($vtprd_rules_set, true ) );
   
    
    return $cart_object;

 } 
  //**************************************
	//refactored v1.0.9.3
  //  Only used for Cart/Mini-cart unit price display ***with crossout***
  //**************************************
  public function vtprd_maybe_cart_item_price_html($price_html, $cart_item, $cart_item_key){    
//return 444; //mwnprice
    global $post, $vtprd_info, $vtprd_setup_options, $woocommerce, $vtprd_cart;
    vtprd_debug_options();  //v1.0.5
 
 //error_log( print_r(  'TOP of CART ITEM PRICE, $cart_item=', true ) );
 //error_log( var_export($cart_item, true ) ); 
 //error_log( print_r(  '$vtprd_cart', true ) );
 //error_log( var_export($vtprd_cart, true ) );
 
    //If discount in coupon, or show no crossouts, exit stage left
    if ( ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon') ||
         ($vtprd_setup_options['show_unit_price_cart_discount_crossout'] == 'no') )  {
 //error_log( print_r(  'CART ITEM PRICE, $exit 001', true ) );      
      return $price_html;    
    }

    if ($cart_item['quantity'] <= 0) {
 //error_log( print_r(  '016 $price_html= ' .$price_html, true ) );
  //error_log( print_r(  'CART ITEM PRICE, $exit 002', true ) ); 
      return $price_html;
    }
   
    if ($cart_item['variation_id'] > ' ') {      
      $product_id  = $cart_item['variation_id'];
    } else { 
      $product_id  = $cart_item['product_id'];
    }
    
    //current $price_html, if updated, has been overwritten with a new price, without the previous crossout, if any 
    /*
    if ($cart_item['data']->price == 0) {
      $newprice = __('Free!', 'vtprd');
    } else {
      $newprice = $price_html;
    }
    */
    $newprice = $price_html;
          
    foreach($vtprd_cart->cart_items as $vtprd_key => $vtprd_cart_item) {      
      
      //already free, no crossout !!!!!!
      if ( ($vtprd_cart_item->product_catalog_price_displayed == 0) ||
           ($vtprd_cart_item->product_catalog_price_displayed == __('Free!', 'vtprd')) ) {
         continue;  //skip to next in foreach
      }
      
      if ($vtprd_cart_item->product_id == $product_id ) {
  //error_log( print_r(  'CART ITEM PRICE, This Far 001', true ) );         

        //pick up both Catalog and Cart discount for comparison test sake only, as the 1st test only tests if CART discount = price
        $combined_discount = $vtprd_cart_item->unit_price + $vtprd_cart_item->product_inline_discount_price_woo;

         if ( ($vtprd_cart_item->product_inline_discount_price_woo == $cart_item['data']->price) ||
              ($combined_discount                                  == $cart_item['data']->price)) {
    //error_log( print_r(  'CART ITEM PRICE, This Far 002', true ) );                  
             // vtprd_maybe_get_price_single_product($product_id, $price);
    //error_log( print_r(  'product_session_info = ', true ) );
    //error_log( var_export($vtprd_info['product_session_info'], true ) );           
               

            if ( get_option( 'woocommerce_calc_taxes' )  == 'yes' ) {
               switch (true) {
                  case ( (get_option( 'woocommerce_tax_display_shop' ) == 'excl' ) &&
                         (get_option( 'woocommerce_tax_display_cart' ) == 'incl') ) :
                  
                         $oldprice = $vtprd_cart_item->product_catalog_price_displayed_incl_tax_woo;
                        
                     break;         
                  case ( (get_option( 'woocommerce_tax_display_shop' ) == 'incl' ) &&
                         (get_option( 'woocommerce_tax_display_cart' ) == 'excl') ) :
                  
                         $oldprice = $vtprd_cart_item->product_catalog_price_displayed_excl_tax_woo;
                                 
                     break;
                  default:
                         $oldprice = $vtprd_cart_item->product_catalog_price_displayed;                     
                     break;                  
               }          
            } else {
              $oldprice = $vtprd_cart_item->product_catalog_price_displayed; 
            }
                
            $oldprice = wc_price( $oldprice );  
              
            $price_html = '<del>' . $oldprice  . '</del> &nbsp; <ins>' . $newprice . '</ins>'; 
            
         } else {
  //error_log( print_r(  'CART ITEM PRICE, $exit 003', true ) );         
            return $price_html;
         }    
  
      }               
      
    } 
  //error_log( print_r(  'CART ITEM PRICE, $exit 004', true ) );
   return $price_html;

 }
 
  //v1.0.7.4 new function
  public function vtprd_show_shop_price() {
    global $vtprd_info, $vtprd_setup_options, $woocommerce, $vtprd_cart; 
    
    if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) {         
      $woocommerce_tax_display_shop = get_option( 'woocommerce_tax_display_shop' );
        
      //suffix gets added automatically, blank if no suffix provided ...
      if ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' ) {      
          switch( true ) {
            // case ( $woocommerce->customer->is_vat_exempt()):    //v1.0.7.9
            case ( vtprd_maybe_customer_tax_exempt() ):            //v1.0.7.9  
                $price = $vtprd_info['product_session_info']['product_discount_price_excl_tax_woo'];
              break; 
            case ( $woocommerce_tax_display_shop == 'incl'):
                $price = $vtprd_info['product_session_info']['product_discount_price']; 
              break;           
            case ( $woocommerce_tax_display_shop == 'excl'):
                $price = $vtprd_info['product_session_info']['product_discount_price_excl_tax_woo'];    
              break;
          } 
      } else {      
          switch( true ) {
            // case ( $woocommerce->customer->is_vat_exempt()):   //v1.0.7.9
            case ( vtprd_maybe_customer_tax_exempt() ):           //v1.0.7.9
                $price = $vtprd_info['product_session_info']['product_discount_price'];
              break; 
            case ( $woocommerce_tax_display_shop == 'incl'):
                $price = $vtprd_info['product_session_info']['product_discount_price_incl_tax_woo'];; 
              break;           
            case ( $woocommerce_tax_display_shop == 'excl'):
                $price = $vtprd_info['product_session_info']['product_discount_price'];    
              break;
          }                 
      }    
    } else {
      $price = $vtprd_info['product_session_info']['product_discount_price']; 
    }
 //error_log( print_r(  '018 $price_html= ' .$price, true ) );    
    return $price;
  }
  
  //****************************
  //v1.0.7.4 new function
  //v1.0.9.3  refactored
  //  $justThePricing = yes only when doing variation group presentation - crossouts and suffixes are introduced later
  //****************************
  public function vtprd_show_shop_price_html($justThePricing = null) {
    global $vtprd_info, $vtprd_setup_options, $woocommerce, $vtprd_cart; 
    
    vtprd_debug_options();  //v1.1
    
    $price_html = '';  //v1.0.8.0 
    
    if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) {
      //suffix gets added automatically, blank if no suffix provided ...
      $woocommerce_tax_display_shop = get_option( 'woocommerce_tax_display_shop' );
      
      if ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' ) {      
          switch( true ) {
         // case ( $woocommerce->customer->is_vat_exempt()):
            case ( vtprd_maybe_customer_tax_exempt() ):      //v1.0.7.9  
                $price_contents = $vtprd_info['product_session_info']['product_discount_price_excl_tax_html_woo'];
                //$price_html = $this->vtprd_maybe_show_crossouts($price_contents);
              break; 
            case ( $woocommerce_tax_display_shop == 'incl'):
                $price_contents = $vtprd_info['product_session_info']['product_discount_price_html_woo'];
                //$price_html = $this->vtprd_maybe_show_crossouts($price_contents);  
              break;           
            case ( $woocommerce_tax_display_shop == 'excl'):
                $price_contents = $vtprd_info['product_session_info']['product_discount_price_excl_tax_html_woo'];
                //$price_html = $this->vtprd_maybe_show_crossouts($price_contents);    
              break;
          }       
      } else {      
          switch( true ) {
        //  case ( $woocommerce->customer->is_vat_exempt()):
            case ( vtprd_maybe_customer_tax_exempt() ):      //v1.0.7.9 
                $price_contents = $vtprd_info['product_session_info']['product_discount_price_html_woo'];
                //$price_html = $this->vtprd_maybe_show_crossouts($price_contents);
              break; 
            case ( $woocommerce_tax_display_shop == 'incl'):
                $price_contents = $vtprd_info['product_session_info']['product_discount_price_incl_tax_html_woo'];
                //$price_html = $this->vtprd_maybe_show_crossouts($price_contents);  
              break;           
            case ( $woocommerce_tax_display_shop == 'excl'):
                $price_contents = $vtprd_info['product_session_info']['product_discount_price_html_woo'];
                //$price_html = $this->vtprd_maybe_show_crossouts($price_contents);    
              break;
          }                    
      }    
    } else { 
      $price_contents = $vtprd_info['product_session_info']['product_discount_price_html_woo'];      
    }
 //error_log( print_r(  '019 $price_html= ' .$price_html, true ) );    
    
    if ($justThePricing == 'yes') {
      $price_html = $price_contents;
    } else {
      $price_contents .= $vtprd_info['product_session_info']['product_discount_price_suffix_html_woo'];
      $price_html = $this->vtprd_maybe_show_crossouts($price_contents);     
    }

    return $price_html;
  }


  //v1.0.7.4 new function
  //v1.0.9.3 refactored
//  public function vtprd_maybe_show_crossouts($price_contents, $justGetOldPrice = null) {
  public function vtprd_maybe_show_crossouts($price_contents) {  
    global $vtprd_setup_options, $vtprd_info;  
    
    
     
    if ($vtprd_setup_options['show_catalog_price_crossout'] == 'yes')  {
      //mwntestnew
      $old_price = $vtprd_info['product_session_info']['product_orig_price_html_woo'];
      
      /*
      $product_id = $vtprd_info['product_session_info']['product_id'];
      switch( true ) {
        case ( ($vtprd_info['product_session_info']['product_special_price'] > 0 ) &&
               ($vtprd_info['product_session_info']['product_special_price'] < $vtprd_info['product_session_info']['product_list_price'] ) ) :                  //there is a discount...
            $old_price = woocommerce_price($vtprd_info['product_session_info']['product_special_price']); //special_price needs formatting ...
          break;
        default :               
            $old_price = $vtprd_info['product_session_info']['product_list_price_html_woo'];
          break; 
      }      
      */
      
      
      /*
      if ( ($vtprd_info['product_session_info']['product_special_price'] > 0 ) &&
           ($vtprd_info['product_session_info']['product_special_price'] < $vtprd_info['product_session_info']['product_list_price'] ) ) {
        $old_price = woocommerce_price($vtprd_info['product_session_info']['product_special_price']); //special_price needs formatting ...
      } else {
        $old_price = $vtprd_info['product_session_info']['product_list_price_html_woo'];
      }
      */
      //Used only in displaying cross-ed out old pricing for variation display
      /*
      if ($justGetOldPrice) {
        return $old_price;
      }
      */
      $price_html = '<del>' . $old_price . '</del><ins>' . $price_contents . '</ins>'; 
     } else {
      $price_html = $price_contents;  
    }
    
    $price_html = $this->vtprd_maybe_show_pricing_suffix($price_html);
    
    
 //error_log( print_r(  '020 $price_html= ' .$price_html, true ) );    
    return $price_html;
  }
 
  //*************************************************************************
  //v1.0.9.3 new function
  //*************************************************************************
	public function vtprd_maybe_show_pricing_suffix($price_html){ 
    global $vtprd_setup_options, $vtprd_info;    
    //v1.0.9.0  begin
    if ($vtprd_setup_options['show_price_suffix'] > ' ')  {
        $price_display_suffix = $vtprd_setup_options['show_price_suffix'];
        
        if ( (strpos($price_display_suffix,'{price_save_percent}') !== false)  ||
             (strpos($price_display_suffix,'{price_save_amount}')  !== false)   ||
             (strpos($price_display_suffix,'{sale_badge_product}') !== false) ) {   //does the suffix include these wildcards?
          //  $price_including_tax = vtprd_get_price_including_tax($product_id, $discount_price); 
          //  $price_excluding_tax = vtprd_get_price_excluding_tax($product_id, $discount_price); 
           
          $find = array(    //wildcards allowed in suffix
  				  '{price_save_percent}',
  		      '{price_save_amount}',
            '{sale_badge_product}'
  			  ); 
          $price_save_percent = $vtprd_info['product_session_info']['product_yousave_total_pct'] . '%';
          
          //show "$$ saved" with appropriate taxation
          if (strpos($price_display_suffix,'{price_save_amount}')  !== false) {
            $price_save_amount = $this->vtprd_show_price_save_amount();
          }
          //$price_save_amount = wc_price( $vtprd_info['product_session_info']['product_yousave_total_amt'] );
          
          //this span allows the user to attach a sale badge to each price, via CSS, using the background-image property. 
          $sale_badge_product = '<span class="sale_badge_product" id="sale_badge_product_' .$vtprd_info['product_session_info']['product_id']. '"> &nbsp; </span>';
          
          //replace the wildcards in the suffix!            
          $replace = array(
    			//	wc_price( $this->get_price_including_tax() ),
    			//	wc_price( $this->get_price_excluding_tax() )
            $price_save_percent,  
            $price_save_amount,
            $sale_badge_product 
    			);
          
          $price_display_suffix = str_replace( $find, $replace, $price_display_suffix );
        }
        			
                                        
        //then see if additonal suffix is needed
        if (strpos($discount_price_html_woo, $price_display_suffix) !== false) { //if suffix already in price, do nothing
          $do_nothing;
        } else {
          $price_html =  $price_html . '<span class="pricing-suffix">' . $price_display_suffix . '</span>';
        }
        
    }
    //v1.0.9.0  end
    return $price_html;  
  }
 
  
  //*************************************************************************
  //v1.0.9.3 new function
  //*************************************************************************
	public function vtprd_show_price_save_amount(){ 
    global $vtprd_setup_options, $vtprd_info; 

    if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) {
      //suffix gets added automatically, blank if no suffix provided ...
      $woocommerce_tax_display_shop = get_option( 'woocommerce_tax_display_shop' );
      
      if ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' ) {      
          switch( true ) {
            case ( vtprd_maybe_customer_tax_exempt() ):      
                $price_contents = $vtprd_info['product_session_info']['product_catalog_yousave_total_amt_excl_tax_woo'];
              break; 
            case ( $woocommerce_tax_display_shop == 'incl'):
                $price_contents = $vtprd_info['product_session_info']['product_yousave_total_amt'];
              break;           
            case ( $woocommerce_tax_display_shop == 'excl'):
                $price_contents = $vtprd_info['product_session_info']['product_catalog_yousave_total_amt_excl_tax_woo'];
              break;
          }       
      } else {      
          switch( true ) {
            case ( vtprd_maybe_customer_tax_exempt() ):      
                $price_contents = $vtprd_info['product_session_info']['product_yousave_total_amt'];
              break; 
            case ( $woocommerce_tax_display_shop == 'incl'):
                $price_contents = $vtprd_info['product_session_info']['product_catalog_yousave_total_amt_incl_tax_woo'];
              break;           
            case ( $woocommerce_tax_display_shop == 'excl'):
                $price_contents = $vtprd_info['product_session_info']['product_yousave_total_amt'];
              break;
          }                    
      }    
    } else { 
      $price_contents = $vtprd_info['product_session_info']['product_yousave_total_amt'];      
    }       
    
    $price_contents = wc_price( $price_contents );
      
    return $price_contents;  
  }
  
  
  //*************************************************************************
  //FROM 'woocommerce_get_price' => Central behind the scenes pricing
  //*************************************************************************
	public function vtprd_maybe_get_price($price, $product_info){    
    global $post, $vtprd_info;		
    vtprd_debug_options();  //v1.0.5
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
     
     //********************
     //v1.0.8.9 begin
     //  rarely at checkout screen the "return $price" was happening!!
     //  added in the 'doing_ajax' logic
     //    needed because 'is_admin' doesn't work in ajax...
     //********************
     if ( defined('DOING_AJAX') && DOING_AJAX ) {
        $do_nothing;
     } else {
         if ( (function_exists( 'get_current_screen' ) ) ||    // get_current_screen ONLY exists in ADMIN!!!  
              ( is_admin() ) ) {                //v1.0.7.4
           if ( (isset($post->post_type)) &&    //v1.0.7.4
                ($post->post_type == 'product'  ) ) {    //in admin, don't run this on the PRODUCT screen!!
             return $price;
           }
         }
     }
     //v1.0.8.9 end
     //********************
  
         
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
 //error_log( print_r(  '022 $price_html= ' .$price, true ) );     
      return $price;
    }
//error_log( print_r(  'FROM vtprd_maybe_get_price', true ) );    
    //vtprd_maybe_get_discount_catalog_session_price($product_id);
    vtprd_maybe_get_price_single_product($product_id, $price);

    if ($vtprd_info['product_session_info']['product_yousave_total_amt'] > 0)  {     //v1.0.7.2  replaced 'product_discount_price' with 'product_yousave_total_amt' to pick up a FREE discount      
      $price = $vtprd_info['product_session_info']['product_discount_price'];  //v1.0.7.4 ==>> this remains unchanged!! 
     // $price = $this->vtprd_show_shop_price(); //v1.0.7.4 
   
    } 
 //error_log( print_r(  '023 $price_html= ' .$price, true ) );
   return $price;

 }
 
 
	public function vtprd_get_product_catalog_price_do_convert($price, $product_id = null, $variation = null){   
    global $post, $vtprd_info;
	vtprd_debug_options();  //v1.0.5
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
  
 
    if ($vtprd_info['product_session_info']['product_yousave_total_amt'] > 0)  {     //v1.0.7.2  replaced 'product_discount_price' with 'product_yousave_total_amt' to pick up a FREE discount
      //$price = $vtprd_info['product_session_info']['product_discount_price'];
      $price = $this->vtprd_show_shop_price(); //v1.0.7.4
    } 
 //error_log( print_r(  '024 $price_html= ' .$price, true ) );    
    return $price;   

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
/*  v1.0.8.9 commented as unused!!
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
*/
   
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
      $data_chain[] = vtprd_get_current_user_role();  //v1.0.7.2
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
 
   //*************************************
   // v1.0.8.4  new function
   //recalc the cart if user changes, to pick up user/role-based rules
   //*************************************
   public function vtprd_update_on_login_change($user_login, $user) {
      global $woocommerce;
      
      //v1.0.9.4 begin - force the CATALOG rules to be redone
      vtprd_debug_options(); //v1.1  
      
      if(!isset($_SESSION)){
        session_start();
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
      }    
      session_destroy(); 
      //v1.0.9.4 end
      
                 
      $woocommerce_cart_contents = $woocommerce->cart->get_cart();
      if ( sizeof($woocommerce_cart_contents) > 0 ) {       
         //this re-does the CART rules
         $this->vtprd_cart_updated();                  
      }
               
      return; 
   }


       
   //*************************************
   // v1.0.8.9  new function
   /*
      When new customer account created on the fly at checkout, discount amount for coupon will be zero **when it gets to PayPal**
      however, even though this action 'woocommerce_created_customer' adds the customer, the zeroed amount error is only
      available at the last action before adding the order - 'woocommerce_check_cart_items'
      
    if a coupon discount is due, while the coupon remains in the cart, 
    the actual coupon discount AMOUNT is zero when it gets here.  Identify and fix.
   */
   //*************************************
/*  v1.0.9.0  NO longer needed, commented out
   public function vtprd_maybe_update_coupon_on_check_cart_items() {
 //   public function vtprd_maybe_update_cart_on_created_customer($customer_id, $new_customer_data, $password_generated) {  
      global $woocommerce, $vtprd_info, $vtprd_cart;

      $coupon_title = $vtprd_info['coupon_code_discount_deal_title'];    
 
      $woocommerce->cart->get_cart();
            
      if ( empty( $woocommerce->cart->coupon_discount_amounts[ $coupon_title ] ) ) {  
        //Open Session Variable, get rules_set and cart if not there....
        $data_chain = $this->vtprd_get_data_chain();
        if ($vtprd_cart->yousave_cart_total_amt > 0) {
  				$woocommerce->cart->coupon_discount_amounts[ $code ] = 0;
  
  			  $woocommerce->cart->coupon_discount_amounts[ $code ] += $vtprd_cart->yousave_cart_total_amt;
        //v1.0.9.3 - mark call as internal only - 
        //	accessed in parent-cart-validation/ function vtprd_maybe_before_calculate_totals
        $_SESSION['internal_call_for_calculate_totals'] = true;           
          $woocommerce->cart->calculate_totals();

        }
     }
          
    return; 
    
   }  
 */
 
   // do_action( 'woocommerce_add_to_cart', $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data );
   public function vtprd_cart_updated() {
      global $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $wpsc_coupons, $vtprd_setup_options;  
 //error_log( print_r(  'Begin vtprd_cart_updated', true ) );            
    //Open Session Variable, get rules_set and cart if not there....
    
    vtprd_debug_options();  //v1.1
    
    $data_chain = $this->vtprd_get_data_chain();
    
    //v1.0.8.0  begin
    if ( isset ($data_chain[2]) ) {   
      $previous_user_role                = $data_chain[2]; //v1.0.7.2  added
    } else {
      $previous_user_role                = ''; 
    }
    if ( isset ($data_chain[3]) ) {   
      $woo_cart_contents_total_previous  = $data_chain[3]; //v1.0.7.2  changed occurrence numbers
    } else {
      $woo_cart_contents_total_previous  = ''; 
    }
    if ( isset ($data_chain[4]) ) {   
      $woo_applied_coupons_previous      = $data_chain[4]; //v1.0.7.2  changed occurrence numbers 
    } else {
      $woo_applied_coupons_previous      = ''; 
    }
    //v1.0.8.0  end
    
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
        unset( $_SESSION['auto_add_in_progress_timestamp'], $contents );;          
      } else { 
 //error_log( print_r(  'vtprd_cart_updated exit 001', true ) ); 
        return;
      }          
    }
     
   
    //-*******************************************************
    //IF nothing changed from last time, no need to process the discount => 
    //'woocommerce_cart_updated' RUNS EVERY TIME THE CART OR CHECKOUT PAGE DISPLAYS!!!!!!!!!!!!!
    //-*******************************************************
    if ( ($woocommerce->cart->cart_contents_total  > 0) &&   //V1.0.7.1  if == 0, lost addressability to woo, rerun
         ($woocommerce->cart->cart_contents_total  ==  $woo_cart_contents_total_previous) &&
         ($woocommerce->cart->applied_coupons      ==  $woo_applied_coupons_previous)  && 
         ($previous_user_role                      ==  vtprd_get_current_user_role() ) )  { //v1.0.7.2  only return if user_role has not changed
       //v1.0.9.3 begin ==>>  see if a zero value item has been removed from the cart...
         if ( (isset($vtprd_cart->cart_contents_count)) &&
              ($vtprd_cart->cart_contents_count == $woocommerce->cart->cart_contents_count) ) {
 //error_log( print_r(  'cart_contents_count= ' .$woocommerce->cart->cart_contents_count, true ) );  
 //error_log( print_r(  'cart_contents_total= ' .$woocommerce->cart->cart_contents_total, true ) );   
 //error_log( print_r(  'vtprd_cart_updated exit 002', true ) );         
       return; 
       }
       //v1.0.9.3 end  
    }

    $woocommerce_cart_contents = $woocommerce->cart->get_cart();  
    if (sizeof($woocommerce_cart_contents) > 0) {
//error_log( print_r(  'go to  vtprd_process_discount', true ) ); 
      $this->vtprd_process_discount();
    } else { 
//error_log( print_r(  'no cart contents', true ) );        
      $this->vtprd_maybe_clear_auto_add_session_vars();	
    }
 
 //error_log( print_r(  'End vtprd_cart_updated ', true ) ); 
    return;
   }
       
    
	public function vtprd_process_discount(){  //and print discount info...    
    global $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $wpsc_coupons, $vtprd_setup_options; //v1.0.9.0   
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
    //calc discounts                
    $vtprd_info['current_processing_request'] = 'cart'; 
//error_log( print_r(  'new Apply_rules in **vtprd_process_discount', true ) );
    $vtprd_apply_rules = new VTPRD_Apply_Rules;    

        
    //v1.0.9.0  begin
    //load the vtprd cart html fields, for later use - IF we are showing the discount in-line in unit price  
 //error_log( print_r(  'in vtprd_process_discount, above vtprd_get_cart_html_prices', true ) );
    if ($vtprd_setup_options['discount_taken_where'] == 'discountUnitPrice')  { 
      $catalog_or_inline =  'inline';
    } else {
      $catalog_or_inline =  null;
    }
//error_log( print_r(  '$vtprd_cart in vtprd_process_discount', true ) );
//error_log( var_export($vtprd_cart, true ) );
//error_log( print_r(  '$vtprd_cart_items in vtprd_process_discount', true ) );
//error_log( var_export($vtprd_cart->cart_items, true ) );
//error_log( print_r(  'go to vtprd_get_cart_html_prices', true ) );              
    $number_of_times = sizeof($vtprd_cart->cart_items);  
    vtprd_get_cart_html_prices($number_of_times,$catalog_or_inline);
 //error_log( print_r(  'in vtprd_process_discount, AFTER vtprd_get_cart_html_prices', true ) );      

    //v1.0.9.0  end      
       

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
    $data_chain[] = vtprd_get_current_user_role();  //v1.0.7.2
    $data_chain[] =  $contents_total;
    $data_chain[] =  $applied_coupons;
    $_SESSION['data_chain'] = serialize($data_chain);             
     
    return;        
} 
     
    
	public function vtprd_woo_maybe_add_remove_discount_cart_coupon(){  //and print discount info...    

//error_log( print_r(  'in maybe_add_remove_discount_cart_coupon ', true ) ); 
      
    global $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $wpsc_coupons, $vtprd_setup_options; //v1.0.9.1
     
    //v1.0.9.1 begin
    if ($vtprd_setup_options['discount_taken_where'] != 'discountCoupon')  {   		
    	return;
    }
    //v1.0.9.1 end  
      
    vtprd_debug_options();  //v1.0.5                 
    //Open Session Variable, get rules_set and cart if not there....
    $data_chain = $this->vtprd_get_data_chain();
      
    //engenders a tr class coupon-deals, used in CSS!
    $coupon_title = $vtprd_info['coupon_code_discount_deal_title']; 
     
    if ($vtprd_cart->yousave_cart_total_amt > 0) {
//error_log( print_r(  'mar 001 ', true ) );   
       //add coupon - recalc totals done when actual coupon amount updated
       if ($woocommerce->cart->has_discount($coupon_title)) {
//error_log( print_r(  'mar 002 ', true ) );          
          $do_nothing;
       } else {
//error_log( print_r(  'mar 003 ', true ) );
          $woocommerce->cart->add_discount($coupon_title);
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
//error_log( print_r(  'mar 004 ', true ) ); 
       //remove coupon and recalculate totals
       if ($woocommerce->cart->has_discount($coupon_title) ) {
//error_log( print_r(  'mar 005 ', true ) );  		
      		$this->vtprd_woo_maybe_remove_coupon_from_cart($coupon_title);
        
          //v1.0.9.3 - mark call as internal only - 
          //	accessed in parent-cart-validation/ function vtprd_maybe_before_calculate_totals
          $_SESSION['internal_call_for_calculate_totals'] = true;   
                    
          $woocommerce->cart->calculate_totals();
       }
       
    }
/*
echo '$woocommerce= <pre>'.print_r($woocommerce, true).'</pre>' ;
echo '$vtprd_cart= <pre>'.print_r($vtprd_cart, true).'</pre>' ; 
echo '$vtprd_rules_set= <pre>'.print_r($vtprd_rules_set, true).'</pre>' ; 
wp_die( __('<strong>die again.</strong>', 'vtprd'), __('VT Pricing Deals not compatible - WP', 'vtprd'), array('back_link' => true));     
*/
//error_log( print_r(  'mar END ', true ) );              
    return;        
} 

  //clears coupon from cart
   public function vtprd_woo_maybe_remove_coupon_from_cart($coupon_title) {
      global $woocommerce;
			//v1.0.7.5 reworked for backwards compatability
       
      $current_version =  WOOCOMMERCE_VERSION;
      //if before woo version 2.1
      if( (version_compare(strval('2.1.0'), strval($current_version), '>') == 1) ) {   //'==1' = 2nd value is lower  
        if ( $woocommerce->applied_coupons ) {
  				foreach ( $woocommerce->applied_coupons as $index => $code ) {
  					if ( $code == $coupon_title ) {
              unset( $woocommerce->applied_coupons[ $index ] );
              break;
            } 
  				}
  			}    
      } else {
        WC()->cart->remove_coupon( $coupon_title );   //v1.0.7.4 
      }      
                   
    return;        
} 

      
   //****************************************************************
   // Update the placeholder Coupon previously manually added 
   //  with the discount amount
   //****************************************************************
   public function vtprd_woo_maybe_load_discount_amount_to_coupon($status, $code) {
      global $vtprd_rules_set, $wpdb, $vtprd_cart, $vtprd_setup_options, $vtprd_info, $woocommerce;
     
    //v1.0.9.1 begin
    if ($vtprd_setup_options['discount_taken_where'] != 'discountCoupon')  {   		
    	return;
    }
    //v1.0.9.1 end  
            
      
      vtprd_debug_options();  //v1.0.5      
  //echo '$code= ' .$code. '<br>';
  //echo 'coupon_code_discount_deal= ' .$vtprd_info['coupon_code_discount_deal_title']. '<br>';
      
      
      if ($code != $vtprd_info['coupon_code_discount_deal_title']) {
        //v1.0.8.9 change return
        // return false;  //this steps on other plugins using the same action
        return;  
      }

                 
      //Open Session Variable, get rules_set and cart if not there....
      $data_chain = $this->vtprd_get_data_chain();
    
    
  //echo '$woocommerce= <pre>'.print_r($woocommerce, true).'</pre>' ;
  //echo '$vtprd_cart= <pre>'.print_r($vtprd_cart, true).'</pre>' ; 
  //echo '$vtprd_rules_set= <pre>'.print_r($vtprd_rules_set, true).'</pre>' ; 
      
      if ($vtprd_cart->yousave_cart_total_amt <= 0) {
         return false;
      }

//error_log( print_r(  '$vtprd_cart= ' , true ) ); 
 //error_log( var_export($vtprd_cart, true ) );
 
 
      //v1.0.7.4 begin
//v1.0.9.3 moved      vtprd_load_cart_total_incl_excl(); 

//error_log( print_r(  'yousave_cart_total_amt 2 = ' .$vtprd_cart->yousave_cart_total_amt, true ) ); 
      
      //$apply_before_tax  used to MIMIC the way regular coupons taxation!!
      //  Testing Note:  Compare how Deal discount is applied vs Regular coupon discount of same amount
      //    example: 10% cart discount vs 10% coupon, with a variety of tax switch settings...
      $apply_before_tax = vtprd_coupon_apply_before_tax();    
//error_log( print_r(  'apply_before_tax = ' .$apply_before_tax, true ) ); 
//      $apply_before_tax = '';      
      //v1.0.7.4 end
 //error_log( print_r(  'yousave_cart_total_amt 3 = ' .$vtprd_cart->yousave_cart_total_amt, true ) );    
   
      //GET coupon_id of the previously inserted placeholder coupon where title = $vtprd_info['coupon_code_discount_deal_title']
      $deal_discount_title = $vtprd_info['coupon_code_discount_deal_title'];
      $coupon_id 	= $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_title ='" . $deal_discount_title. "'  AND post_type = 'shop_coupon' AND post_status = 'publish'  LIMIT 1" );     	

         
      //defaults take from  class/class-wc-coupon.php    function __construct
      
      //v1.0.9.3 redone begin
      
      $current_version =  WOOCOMMERCE_VERSION;
      //AFTER Woo 2.3, coupon is always applied PRE_TAX i
      if( (version_compare(strval('2.3.0'), strval($current_version), '>') == 1) ) {   //'==1' = 2nd value is lower     
        //pre woo 2.3
        vtprd_load_cart_total_incl_excl(); 
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
              'apply_before_tax'           => $apply_before_tax,
              'free_shipping'              => 'no',
              'product_categories'         => array(),
              'exclude_product_categories' => array(),
              'exclude_sale_items'         => 'no',
              'minimum_amount'             => '',
              'customer_email'             => ''
        );      
      } else {
               
        if ( (get_option( 'woocommerce_calc_taxes' )  == 'yes' ) && 
             (get_option('woocommerce_prices_include_tax')  == 'yes') ) { 
          //$amount = $vtprd_cart->yousave_cart_total_amt;
          $amount = $vtprd_cart->yousave_cart_total_amt_incl_tax;
        }  else  {
          $amount = $vtprd_cart->yousave_cart_total_amt_excl_tax;
        }        
            
        $coupon_data = array(
              	'id'                         => $coupon_id,
                'discount_type'              => 'fixed_cart',
              	'coupon_amount'              => $amount, //always use untaxed, as it's added in WOO, if there...
              	'individual_use'             => 'no',
              	'product_ids'                => array(),
              	'exclude_product_ids'        => array(),
              	'usage_limit'                => '',
              	'usage_limit_per_user'       => '',
              	'limit_usage_to_x_items'     => '',
              	'usage_count'                => '',
              	'expiry_date'                => '',
              	'free_shipping'              => 'no',
              	'product_categories'         => array(),
              	'exclude_product_categories' => array(),
              	'exclude_sale_items'         => 'no',
              	'minimum_amount'             => '',
              	'maximum_amount'             => '',
              	'customer_email'             => array()
              ); 
      }     
           

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
     vtprd_debug_options();  //v1.0.5    
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
        $msgType = 'plainText';                         //v1.0.8.0
        vtprd_checkout_cart_reporting($msgType);        //v1.0.8.0
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
    $data_chain[] = vtprd_get_current_user_role();  //v1.0.7.2
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
    global $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $wpsc_coupons, $vtprd_setup_options;
    vtprd_debug_options();  //v1.0.5        
    
    //v1.0.9.3 begin
    //  NO widget print for inline pricing
    if ($vtprd_setup_options['discount_taken_where'] != 'discountCoupon')  {   		
    	return;
    }
    
    
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
    vtprd_debug_options();  //v1.0.5           
    //while the global data is available here, it does not stay 'current' between iterations, and we loos the 'already_done' switch, so we need the data chain.
         
    //Open Session Variable, get rules_set and cart if not there....
    $data_chain = $this->vtprd_get_data_chain();


    //only do this once - set in function vtprd_maybe_print_checkout_discount    
    if (!$_SESSION['do_log_function']) {
        return;
    }
    $_SESSION['do_log_function'] = false;
    
    //*****************
    //Save LIfetime data
    //*****************
    //v1.0.7.3 begin
    /*
    //moved to thankyou function
    if ( (defined('VTPRD_PRO_DIRNAME')) && ($vtprd_setup_options['use_lifetime_max_limits'] == 'yes') )  { 
      vtprd_save_lifetime_purchase_info($log_id);
    }
    */
    //v1.0.7.3 end
    
    //Save Discount Purchase Log info
    //************************************************
    //*   Purchase log is essential to customer email reporting
    //*      so it MUST be saved at all times.
    //************************************************
    vtprd_save_discount_purchase_log($log_id);     
   
    return;
  } // end  function vtprd_store_max_purchaser_info()     


   
  /* ************************************************
  USING THIS filter in this way, puts discounts within the existing products table, after products are shown, but before the close of the table...
  *************************************************** */ 
 public function vtprd_post_purchase_maybe_email($message, $order_info) {   
    global $wpdb, $vtprd_rules_set, $vtprd_cart, $vtprd_setup_options; 
     
    //v1.0.9.1 begin
    if ($vtprd_setup_options['discount_taken_where'] != 'discountCoupon')  {   		
    	return $message;
    }
    //v1.0.9.1 end      
    
     vtprd_debug_options();  //v1.0.5   


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

    $message .=  $discount_reporting;

    return $message;
  }    

    
  /* ************************************************
  //  do_action( 'woocommerce_order_details_after_order_table', $order );
  *************************************************** */ 
  public function vtprd_post_purchase_maybe_before_thankyou($order_id) {   
    global $wpdb, $vtprd_rules_set, $vtprd_cart, $vtprd_setup_options; 
     
    //v1.0.9.1 begin
    if ($vtprd_setup_options['discount_taken_where'] != 'discountCoupon')  {   		
    	return;
    }
    //v1.0.9.1 end      
      
     vtprd_debug_options();  //v1.0.5
    
    $message = '';  //v1.0.8.0
    $log_Id = $order_id;
   
    //if there's a discount history, let's find it...
    $vtprd_purchase_log = $wpdb->get_row( "SELECT * FROM `" . VTPRD_PURCHASE_LOG . "` WHERE `cart_parent_purchase_log_id`='" . $log_Id . "' LIMIT 1", ARRAY_A );      	
    	    
    //if purchase log, use that info instead of current 
    if ($vtprd_purchase_log) { 
      $vtprd_cart      = unserialize($vtprd_purchase_log['cart_object']);    
      $vtprd_rules_set = unserialize($vtprd_purchase_log['ruleset_object']);
    }  else {
      return;
    }                                                                                                                        

    //NO discount found, no msg changes
    if (!($vtprd_cart->yousave_cart_total_amt > 0)) {
      return;    
    } 
    
    //*****************
    //Save LIfetime data
    //*****************
    //v1.0.7.3 begin
    //  moved HERE so that abandoned carts are avoided in lifetime info
    if ( (defined('VTPRD_PRO_DIRNAME')) && ($vtprd_setup_options['use_lifetime_max_limits'] == 'yes') )  { 
      vtprd_save_lifetime_purchase_info($log_id);
    }
    //v1.0.7.3 end
    
    
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
    vtprd_debug_options();  //v1.0.5             
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
    
    vtprd_debug_options();  //v1.1 
    
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
        if (isset($data_chain[0])) {    //v1.0.8.0
          $vtprd_rules_set = $data_chain[0];
        }
        if (isset($data_chain[1])) {    //v1.0.8.3
          $vtprd_cart      = $data_chain[1];
        }
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
    
    vtprd_debug_options();  //v1.1
    
    global  $vtprd_info;
    $coupon_title = $vtprd_info['coupon_code_discount_deal_title'];
    $this->vtprd_woo_maybe_remove_coupon_from_cart($coupon_title);
       
    return;    
  }
   
   //v1.0.7.2 begin    New function, to pick up a zero total produced by catalog discounts...
   //  really only needed if ALL products have a catalog discount which ends up with ALL products FREE ...
   public function vtprd_maybe_recalc_woo_totals() {
     global $woocommerce;
      
     vtprd_debug_options();  //v1.1
        
     //v1.0.9.3 - mark call as internal only - 
     //	accessed in parent-cart-validation/ function vtprd_maybe_before_calculate_totals
     $_SESSION['internal_call_for_calculate_totals'] = true;   
               
     $woocommerce->cart->calculate_totals();        
     return;
   }
   //v1.0.7.2 end
  
   
 /*
    also:  in wpsc-includes/purchase-log-class.php  (from 3.9)
		do_action( 'wpsc_sales_log_process_bulk_action', $current_action );
  */
	public function vtprd_pro_lifetime_log_roll_out($log_id ){  
    if ( (is_admin()) && (defined('VTPRD_PRO_DIRNAME')) ) {     
       vtprd_debug_options();  //v1.1
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
       vtprd_debug_options();  //v1.1
       vtprd_maybe_lifetime_bulk_roll_log_totals_out($current_action);
    }
    return;   
  }
  
  //********************************************************
  // v1.0.9.0  New function - set WOO tax exemption flag 
  //********************************************************
	public function vtprd_set_woo_customer_tax_exempt(){  
		global $woocommerce, $current_user;
    
    vtprd_debug_options();  //v1.1

    if ( (!is_object($woocommerce->customer) ) ||
         (empty( $woocommerce->customer) )     ||
         ($woocommerce->customer->is_vat_exempt() ) ) {   
      return; 
    }
    
    // check user-level tax exemption (plugin-specific checkbox on user screen)
    //USER LEVEL TAX EXEMPTION = ALL TRANSACTIONS TAX EXEMPT
    if (get_user_meta( $current_user->ID, 'vtprd_user_is_tax_exempt', true ) == 'yes') {
       $woocommerce->customer->is_vat_exempt == true;
       return;
    }

    if ( !$current_user )  {
      $current_user = wp_get_current_user();
    }
    
    //check role-level tax exemption (plugin-specific role capability)
    if ( current_user_can( 'buy_tax_free', $user_id ) ) {
       $woocommerce->customer->is_vat_exempt == true;
    }    
    
    return;   
  }
   
       
   
} //end class
$vtprd_parent_cart_validation = new VTPRD_Parent_Cart_Validation;
