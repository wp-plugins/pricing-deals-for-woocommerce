<?php
                     
	/*******************************************************  
 	     The session variable for this product will already have been
 	     stored during the catalog display of the product price 
          (similar pricing done in vtprd-auto-add.php...)       
  ******************************************************** */
	function vtprd_load_vtprd_cart_for_processing(){
 
      global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_setup_options, $vtprd_info; 

     // from Woocommerce/templates/cart/mini-cart.php  and  Woocommerce/templates/checkout/review-order.php

      $woocommerce_cart_contents = $woocommerce->cart->get_cart();
 //error_log( print_r(  'Woo Cart', true ) );
 //error_log( var_export($woocommerce_cart_contents, true ) );
      if (sizeof($woocommerce_cart_contents) > 0) { 

 //error_log( print_r(  'Begin LOAD CART CONTENTS ', true ) );
					
          //v1.0.8.0  save previous cart before creating new cart image, for lifetime confirmation processing only
          $vtprd_previous_cart = $vtprd_cart;     //v1.0.8.0 
          
          $vtprd_cart = new vtprd_Cart; 
           
          foreach ( $woocommerce_cart_contents as $cart_item_key => $cart_item ) {
						$_product = $cart_item['data'];
						if ($_product->exists() && $cart_item['quantity']>0) {
							$vtprd_cart_item                = new vtprd_Cart_Item;
             
              //the product id does not change in woo if variation purchased.  
              //  Load expected variation id, if there, along with constructed product title.
              $varLabels = ' ';

              if ($cart_item['variation_id'] > ' ') {      
                 
                  // get parent title
                  $parent_post = get_post($cart_item['product_id']);
                  
                  // get variation names to string onto parent title
                  foreach($cart_item['variation'] as $key => $value) {          
                    $varLabels .= $value . '&nbsp;';           
                  }
                  
                  $vtprd_cart_item->product_id           = $cart_item['variation_id'];
                  $vtprd_cart_item->variation_array      = $cart_item['variation'];                  
                  $vtprd_cart_item->product_name         = $parent_post->post_title . '&nbsp;' . $varLabels ;
                  $vtprd_cart_item->parent_product_name  = $parent_post->post_title;
                  $vtprd_cart_item->variation_name_html  = $woocommerce->cart->get_item_data( $cart_item );   //v1.0.7.9
                  
                // added for v1.0.9.0 but unused, so commented ... 
                //  $variation_product = get_product( $vtprd_cart_item->product_id );  //v1.0.9.0                  

              } else { 
                  $vtprd_cart_item->product_id           = $cart_item['product_id'];
                  $vtprd_cart_item->product_name         = $_product->get_title().$woocommerce->cart->get_item_data( $cart_item );
              }

              //v1.0.8.6  begin
              //for Variation Products with Attributes only, **there is NO product_ID difference** - the only difference is in the variation array.
              //   this info is used later to uniquely identify the product to which a discount should be added.
              $vtprd_cart_item->product_variation_key  = array (
                 'product_id'    => $cart_item['product_id'], 
                 'variation_id'  => $cart_item['variation_id'],
                 'variation'     => $cart_item['variation']
              );   
              //v1.0.8.6  end
              
              $product = get_product( $vtprd_cart_item->product_id ); //v1.0.7.4

              //v1.0.8.5 begin
              $varID  = $cart_item['variation_id'];
              $prodID = $cart_item['product_id'];
              //v1.0.8.5 end
               
              
              $vtprd_cart_item->quantity      = $cart_item['quantity'];                                                  
                  
              $product_id = $vtprd_cart_item->product_id;
               
              //***always*** will be a session found
              //$session_found = vtprd_maybe_get_product_session_info($product_id);
              vtprd_maybe_get_product_session_info($product_id);

              //************************************************************************
              /*
              v1.0.9.0 begin     this part of the routine reworked.
               WITH V1.0.9.0 WE IGNORE THE UNIT PRICE IN THE CART,
                as that could reflect both a CATALOG discount AND an **in-line** CART discount 
                not a problem with coupone-based discount)
                rather, we either go back to the ORIGINAL unit price for processing
                OR we use the CATALOG discounted unit price.
                (1) Original price, as taken from the DB
                (2) Catalog pricing, taken from session variable
                (3) Cart pricing, from running cart discount

              */
              //************************************************************************
              
              //By this time, there may  be a 'display' session variable for this product, if a discount was displayed in the catalog           
              //  so 2nd - nth iteration picks up the discounted current price AND the original price for comparison
              if ( ( isset($vtprd_info['product_session_info']['product_yousave_total_amt']) ) &&
                   ($vtprd_info['product_session_info']['product_yousave_total_amt'] > 0) ) {   //v1.0.9.0 changed to pick up FREE items...
                 //  $vtprd_cart_item->unit_price             =  vtprd_compute_current_unit_price($product_id, $cart_item, $vtprd_cart_item, $product, $vtprd_previous_cart);     //v1.0.8.0 
                  $vtprd_cart_item->unit_price             =  $vtprd_info['product_session_info']['product_discount_price'];
                  $vtprd_cart_item->save_orig_unit_price   =  $vtprd_info['product_session_info']['product_unit_price'];   //v1.0.7.4  save for later comparison
                  $vtprd_cart_item->db_unit_price          =  $vtprd_info['product_session_info']['product_unit_price'];
    
                  $vtprd_cart_item->db_unit_price_special  =  $vtprd_info['product_session_info']['product_special_price']; 
                  $vtprd_cart_item->db_unit_price_list     =  $vtprd_info['product_session_info']['product_list_price'];                
 //error_log( print_r(  'Pricing in Cart, from session_info, product = ' .$product_id. ' unit price= ' .$vtprd_cart_item->db_unit_price_list .' qty= ' .$vtprd_cart_item->quantity, true ) );
              } else { 
 //error_log( print_r(  'no session_info', true ) );                           
//echo '<pre> CALL to vtprd_get_current_active_price 001 - product= '.print_r($product_id), true).'</pre>' ;
//echo '<pre> before call 001 $product_id = '.print_r($product_id, true).'</pre>' ;                   
                  $price = vtprd_get_current_active_price($product_id);
 //error_log( print_r(  'price 001= ' .$price, true ) );                   
                  $vtprd_previous_cart = '';
                  $price  =  vtprd_compute_current_unit_price($product_id, $cart_item, $vtprd_cart_item, $product, $vtprd_previous_cart, $price); 
 //error_log( print_r(  'price 002= ' .$price, true ) );                  
                  $vtprd_cart_item->unit_price             =  $price;

                  $vtprd_cart_item->save_orig_unit_price   =  $vtprd_cart_item->db_unit_price_list;
                  /*  now loaded in vtprd_get_current_active_price
                  $vtprd_cart_item->db_unit_price         = $price;
                  $vtprd_cart_item->db_unit_price_list    = $regular_price;
                  $vtprd_cart_item->db_unit_price_special = $sale_price;                  
                  */
              }
              

              //v1.0.9.0 end

              // db_unit_price_special CAN be zero if item is FREE!!
              //if ($vtprd_cart_item->unit_price < $vtprd_cart_item->db_unit_price_list )  {
              if ($vtprd_cart_item->db_unit_price_special < $vtprd_cart_item->db_unit_price_list )  {
                  $vtprd_cart_item->product_is_on_special = 'yes';             
              }               

              $vtprd_cart_item->total_price   = $vtprd_cart_item->quantity * $vtprd_cart_item->unit_price;
              
              /*  *********************************
              ***  JUST the cat *ids* please...
              ************************************ */
                       
              $vtprd_cart_item->prod_cat_list = wp_get_object_terms( $cart_item['product_id'], $vtprd_info['parent_plugin_taxonomy'], $args = array('fields' => 'ids') );
              $vtprd_cart_item->rule_cat_list = wp_get_object_terms( $cart_item['product_id'], $vtprd_info['rulecat_taxonomy'], $args = array('fields' => 'ids') );              

              //initialize the arrays
              $vtprd_cart_item->prod_rule_include_only_list = array();  
              $vtprd_cart_item->prod_rule_exclusion_list = array();
              
              /*  *********************************
              ***  fill in include/exclude arrays if selected on the PRODUCT Screen (parent plugin)
              ************************************ */
              $vtprd_includeOrExclude_meta  = get_post_meta($cart_item['product_id'], $vtprd_info['product_meta_key_includeOrExclude'], true); //v1.0.7.8  use the parent ID at all times!
 
              if ( $vtprd_includeOrExclude_meta ) {
                switch( $vtprd_includeOrExclude_meta['includeOrExclude_option'] ) {
                  case 'includeAll':  
                    break;
                  case 'includeList':                  
                      $vtprd_cart_item->prod_rule_include_only_list = $vtprd_includeOrExclude_meta['includeOrExclude_checked_list'];                                            
                    break;
                  case 'excludeList':  
                      $vtprd_cart_item->prod_rule_exclusion_list = $vtprd_includeOrExclude_meta['includeOrExclude_checked_list'];                                               
                    break;
                  case 'excludeAll':  
                      $vtprd_cart_item->prod_rule_exclusion_list[0] = 'all';  //set the exclusion list to exclude all
                    break;
                }
              }

              //v1.0.9.3 added if isset
              if (isset($cart_item['line_subtotal'])) {
                $vtprd_cart_item->lifetime_line_subtotal = $cart_item['line_subtotal'];     //v1.0.8.0  for future lifetime processing only...  
              }
               
              //add cart_item to cart array
              $vtprd_cart->cart_items[]       = $vtprd_cart_item;
				    }
        } //	endforeach;
        
        
		} //end  if (sizeof($woocommerce->cart->get_cart())>0) 
     
     
    if ( (defined('VTPRD_PRO_DIRNAME')) && ($vtprd_setup_options['use_lifetime_max_limits'] == 'yes') )  {
      vtprd_get_purchaser_info_from_screen();   
    }
    $vtprd_cart->purchaser_ip_address = $vtprd_info['purchaser_ip_address']; 
    
    $vtprd_cart->cart_contents_count = $woocommerce->cart->cart_contents_count; //v1.0.9.3  used to check if cart contents have changed...
                               

 //error_log( print_r(  'product_session_info', true ) );
 //$product_session_info = $vtprd_info['product_session_info'];
 //error_log( var_export($product_session_info, true ) );
// error_log( print_r(  'END LOAD CART CONTENTS ', true ) );
// error_log( var_export($vtprd_cart, true ) );
  }


  
//************************************* 
   //v1.0.7.4  New Function
   //
   //  the vtprd_cart unit price and discounts all reflect the TAX STATE of 'woocommerce_prices_include_tax'
   //
   //************************************* 
	function vtprd_compute_current_unit_price($product_id, $cart_item, $vtprd_cart_item, $product, $vtprd_previous_cart, $price=null){ //v1.0.9.0 added $price=null ==> $price only comes from one place... 
      global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_setup_options, $vtprd_info;  
     // $product = get_product( $product_id ); v1.0.8.9 - value is passed already...

		  //v1.0.9.0 begin  moved here
      if ($price) {
        $unit_price  =  $price;
      } else {
        if (isset($cart_item['line_subtotal'])) {
          $unit_price  =  $cart_item['line_subtotal'] / $cart_item['quantity'];
        } else {
          $cart_item_line_subtotal = vtprd_get_line_subtotal_for_lifetime_only($product_id, $vtprd_previous_cart);
          $unit_price  =  $cart_item_line_subtotal / $cart_item['quantity'];
        }
        $price       =  $unit_price;
      }
      //v1.0.9.0 end
//error_log( print_r(  'vtprd_compute_current_unit_price, begin price= ' .$price, true ) );      

        if ( ( get_option( 'woocommerce_calc_taxes' ) == 'no' ) ||
             ( get_option( 'woocommerce_prices_include_tax' ) == 'no' )  || 
             ( vtprd_maybe_customer_tax_exempt() ) ) {      //v1.0.7.9  
           //NO VAT included in price
           // $unit_price  =  $cart_item['line_subtotal'] / $cart_item['quantity'];  //v1.0.9.0
           $do_nothing;                                                              //v1.0.9.0
        } else {
           
           //v1.0.7.4 begin
           //TAX included in price in DB, and Woo $cart_item pricing **has already subtracted out the TAX **, so restore the TAX
           //  this price reflects the tax situation of the ORIGINAL price - so if the price was originally entered with tax, this will reflect tax
       //$price now loaded above //v1.0.9.0
       //$price           =  $cart_item['line_subtotal'] / $cart_item['quantity'];  //v1.0.9.0
          // $unit_price  =  vtprd_get_price_including_tax($product_id, $price);
           $qty = 1;           
           $_tax  = new WC_Tax();                
          // $product = get_product( $product_id ); 
           $tax_rates  = $_tax->get_rates( $product->get_tax_class() );
  			 	 $taxes      = $_tax->calc_tax( $price  * $qty, $tax_rates, false );
  				 $tax_amount = $_tax->get_tax_total( $taxes );
  				 $unit_price = round( $price  * $qty + $tax_amount, absint( get_option( 'woocommerce_price_num_decimals' ) ) ); 
                     
        } 

//error_log( print_r(  'vtprd_compute_current_unit_price, end price= ' .$unit_price, true ) );                   
     return $unit_price;     
     
  }  
  
   
   //************************************* 
   //new function v1.0.8.0 
   // Lifetime only, at checkout confirmation time the line_subtotal in the cart has gone away...
   //   get the line_subtotal from previous cart image at last add-to-cart or cart screen
   //************************************* 
	function vtprd_get_line_subtotal_for_lifetime_only($product_id, $vtprd_previous_cart) {
      $lifetime_line_subtotal = 0;
      $sizeof_cart_items = sizeof($vtprd_previous_cart->cart_items);
      for($k=0; $k < $sizeof_cart_items; $k++) {
        if ($vtprd_previous_cart->cart_items[$k]->product_id == $product_id) {
           $lifetime_line_subtotal = $vtprd_previous_cart->cart_items[$k]->lifetime_line_subtotal;
           $k = $sizeof_cart_items;
        }
      } 
   
      return $lifetime_line_subtotal;     
  }


   //************************************* 

   //************************************* 
	function vtprd_count_other_coupons(){
      global $woocommerce, $vtprd_info, $vtprd_rules_set; 
            
      //v1.0.7.4 begin   routine rewritten!                     
      $coupon_cnt = 0;
      $vtprd_info['skip_cart_processing_due_to_coupon_individual_use'] = false;

      //v1.0.7.7 begin - backwards compatability
      $current_version =  WOOCOMMERCE_VERSION;
      if( (version_compare(strval('2.1.0'), strval($current_version), '>') == 1) ) {   //'==1' = 2nd value is lower
        $applied_coupons = $woocommerce->cart->get_applied_coupons();
      } else {
        $applied_coupons = WC()->cart->get_coupons();
      }          
      //v1.0.7.7 end
            
      foreach ( $applied_coupons as $code => $coupon ) {	
        if ( $code == $vtprd_info['coupon_code_discount_deal_title'] ) {
          continue;  //if the coupon is a Pricing Deal discount, skip
        } else {
          $coupon_cnt++;         
         	 // from woocommerce/includes/class-wc-cart.php
           // Set a switch to skip Cart processing if a coupon with individual_use = "yes" detected
    			$the_coupon = new WC_Coupon( $code );           
    			if ( $the_coupon->id ) {            
    				// If it's individual use then flag to skip all plugin discount processing!!!!!!!!!!!       				
            if ( $the_coupon->individual_use == 'yes' ) {
    					$vtprd_info['skip_cart_processing_due_to_coupon_individual_use'] = true;
    				}           
          }            
        }
			}
      $vtprd_rules_set[0]->coupons_amount_without_rule_discounts = $coupon_cnt;
      //v1.0.7.4 end
   
     return;     
  } 
/* 
   //************************************* 
   // tabulate $vtprd_info['cart_rows_at_checkout']
   //************************************* 
	function vtprd_count_wpsc_cart_contents(){
      global $woocommerce, $vtprd_info; 

      $vtprd_info['cart_rows_at_checkout_count'] = 0;
      foreach($woocommerce->cart_items as $key => $cart_item) {
        $vtprd_info['cart_rows_at_checkout_count']++;   //increment count by 1
      } 
      return;     
  } 
 */
 
	function vtprd_load_vtprd_cart_for_single_product_price($product_id, $price){
      global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info; 

 //error_log( print_r(  'begin load single product=  ' .$product_id, true ) ); 
      
      $vtprd_cart = new VTPRD_Cart; 
       
      $vtprd_cart_item                = new VTPRD_Cart_Item;    
        
      // v1.0.7.3  begin
      //  straight get_post caused WOO to loose the plot with variable products on 1st time through...
      //$post = get_post($product_id);
      if ( ( !isset($post->post_name) ) ||
           ( $post->post_name <= ' ' )  )  {  
         $post = get_post($product_id);
      }
      // v1.0.7.3  end
      
      
       //v1.0.7.8  begin
       //If this is a variation, get the Parent, needed below
      $post_parent_ID = '';
      if ( $post->ID != $product_id )   { 
         //save ID from the Post, which is the variation Parent
         $post_parent_ID = $post->ID;
         //get post for current Variation
         $post = get_post($product_id);
      } else {
        if ( $post->post_parent > 0 ) {
           $post_parent_ID = $post->post_parent;
        }
      
      }
       //v1.0.7.8  end
       
          
      //change??
      $vtprd_cart_item->product_id            = $product_id;
      $vtprd_cart_item->product_name          = $post->post_name;
      $vtprd_cart_item->quantity              = 1;
      
//echo '<pre> CALL to vtprd_get_current_active_price 002 - product= '.print_r($product_id), true).'</pre>' ;   
//echo '<pre> before call 002 $product_id = '.print_r($product_id, true).'</pre>' ;   
      $price = vtprd_get_current_active_price($product_id);

      //product and taxable
      $product = get_product( $vtprd_cart_item->product_id );  //$product needed later
      

      //init $cart_item for call
      $cart_item = array();
 //error_log( print_r(  'single_product_price 001= ' .$price, true ) ); 
      $vtprd_previous_cart = '';
      $price  =  vtprd_compute_current_unit_price($product_id, $cart_item, $vtprd_cart_item, $product, $vtprd_previous_cart, $price); 
                  
      $vtprd_cart_item->save_orig_unit_price  = $price;
        
      //v1.0.9.0 end
      //****************************
      
      $vtprd_cart_item->unit_price            = $price;
      
     //v1.0.9.0  now loaded vtprd_get_current_active_price
     // $vtprd_cart_item->db_unit_price         = $price;
     // $vtprd_cart_item->db_unit_price_list    = $price;
     // $vtprd_cart_item->db_unit_price_special = $price;    
      $vtprd_cart_item->total_price           = $price;
            
      /*  *********************************
      ***  JUST the cat *ids* please...
      ************************************ */
      //v1.0.7.8  begin
      
      
      //v1.0.8.6 begin
      //if we're on a variation, gotta use the Parent to get the taxonomies!!     
      if ($post->post_parent > 0) {
        $use_this_id    = $post->post_parent;
        $post_parent_ID = $post->post_parent;  //v1.0.9.0
      } else {
        $use_this_id = $product_id;
      }
      //v1.0.8.6 end
      
      $vtprd_cart_item->prod_cat_list = wp_get_object_terms( $use_this_id, $vtprd_info['parent_plugin_taxonomy'], $args = array('fields' => 'ids') );
      $vtprd_cart_item->rule_cat_list = wp_get_object_terms( $use_this_id, $vtprd_info['rulecat_taxonomy'], $args = array('fields' => 'ids') );
        //*************************************                    
      //v1.0.7.8  end                    

       //v1.0.7.4 begin 
      //initialize the arrays
      $vtprd_cart_item->prod_rule_include_only_list = array();  
      $vtprd_cart_item->prod_rule_exclusion_list = array();
      
      /*  *********************************
      ***  fill in include/exclude arrays if selected on the PRODUCT Screen (parent plugin)
      ************************************ */
      //v1.0.7.6 TEMPORARY removal
      
      $vtprd_includeOrExclude_meta  = get_post_meta($use_this_id, $vtprd_info['product_meta_key_includeOrExclude'], true);   //v1.0.7.8  exclusions are on the Parent!
         
      if ( $vtprd_includeOrExclude_meta ) {
        switch( $vtprd_includeOrExclude_meta['includeOrExclude_option'] ) {
          case 'includeAll':  
            break;
          case 'includeList':                  
              $vtprd_cart_item->prod_rule_include_only_list = $vtprd_includeOrExclude_meta['includeOrExclude_checked_list'];                                            
            break;
          case 'excludeList':  
              $vtprd_cart_item->prod_rule_exclusion_list = $vtprd_includeOrExclude_meta['includeOrExclude_checked_list'];                                               
            break;
          case 'excludeAll':  
              $vtprd_cart_item->prod_rule_exclusion_list[0] = 'all';  //set the exclusion list to exclude all
            break;
        }
      }
     
       //v1.0.7.4 end
      
      //v1.0.8.5 begin
      if ($post_parent_ID) {  //if a parent id is present, this is a variation...
        $prodID = $post_parent_ID;
        $varID  = $product_id;
      } else {
        $prodID = $product_id;
        $varID  = ' ';      
      }
      //v1.0.8.5 end
              
      //add cart_item to cart array
      $vtprd_cart->cart_items[]       = $vtprd_cart_item;  
      
      
       //v1.0.7.8  begin
       //restore parent $post as needed, for WOO's sanity
      if ($post_parent_ID)   { 
         $post = get_post($post_parent_ID);
      }
       //v1.0.7.8  end           
              
                
  }

	function vtprd_get_current_active_price($product_id){	
      global $post, $vtprd_cart_item;
      //****************************
      // v1.0.9.0  begin
      //  New price logic - ignore passed-in price, get the price from the DB and process anew           
      //$price
      $regular_price = get_post_meta( $product_id, '_regular_price', true );
      if ($regular_price <= 0) {
         $regular_price  =  get_post_meta( $product_id, '_price', true );
      }
 
      $sale_price  =  get_post_meta( $product_id, '_sale_price', true );                  

      if ( ($sale_price > 0) &&
           ($sale_price < $regular_price) ) {
        $price  =  $sale_price;    
      } else {
        $price  =  $regular_price;
      }
      
      //load into global
      $vtprd_cart_item->db_unit_price         = $price;
      $vtprd_cart_item->db_unit_price_list    = $regular_price;
      $vtprd_cart_item->db_unit_price_special = $sale_price;         
       
      //v1.0.9.3 begin
      // Prices from the DB include taxation in the following situation
      //   Woo carries unit pricing without taxation, so back it out here.
      if ( ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) &&
           ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' ) ) {           
         $_tax  = new WC_Tax();                
         $product = get_product( $product_id );
 /*        
 error_log( print_r(  'vtprd_get_current_active_price - cart_item', true ) );
 error_log( var_export($vtprd_cart_item, true ) );  
error_log( print_r(  '$product_id = ' .$product_id, true ) ); 
 error_log( print_r(  '$product', true ) );
 error_log( var_export($product, true ) ); 
 */ 
//echo 'SESSION data <pre>'.print_r($_SESSION, true).'</pre>' ;      
//echo '<pre> vtprd_get_current_active_price - cart_item= '.print_r($vtprd_cart_item, true).'</pre>' ; 
//echo '<pre> $product_id = '.print_r($product_id, true).'</pre>' ;
//echo '<pre> $product = '.print_r($product, true).'</pre>' ;    

   
         $tax_rates  = $_tax->get_rates( $product->get_tax_class() );
			 	 $taxes      = $_tax->calc_tax( $price , $tax_rates, false );
				 //back out taxes!!!
         $tax_amount = $_tax->get_tax_total( $taxes );
//error_log( print_r(  '$tax_amount= ' .$tax_amount, true ) );
         $tax_percent    = $tax_amount / $price  ;
//error_log( print_r(  '$tax_percent= ' .$tax_percent, true ) );         
         $divisor    = 1 + $tax_percent;
//error_log( print_r(  '$divisor= ' .$divisor, true ) ); 
//error_log( print_r(  '$price before= ' .$price, true ) );         
         $price = $price / $divisor;
//error_log( print_r(  '$price after = ' .$price, true ) );         
         //$price = round( $price / $divisor, absint( get_option( 'woocommerce_price_num_decimals' ) ) );  
      }
      //v1.0.9.3 end           
           
      return $price;
  }

	
	function vtprd_move_vtprd_single_product_to_session($product_id){
      global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_setup_options, $vtprd_rules_set;  
      
      vtprd_debug_options();  //v1.0.9.4
      
      $short_msg_array = array();
      $full_msg_array = array();
      $msg_already_done = 'no';
      $show_yousave_one_some_msg = ''; //v1.0.8.0
    
      //auditTrail keyed to rule_id, so foreach is necessary
      foreach ($vtprd_cart->cart_items[0]->cartAuditTrail as $key => $row) {       
        
        //parent product vargroup on sale, individual product variation may not be on sale.
        // send an additional sale msg for the varProd parent group...
        $show_yousave_one_some_msg = '';
        if ($vtprd_setup_options['show_yousave_one_some_msg'] == 'yes' ) {
          if (!$show_yousave_one_some_msg) {
            $rulesetKey = $row['ruleset_occurrence'];
            switch( $vtprd_rules_set[$rulesetKey]->inPop_varProdID_parentLit) {  
              case 'one':
                 $show_yousave_one_some_msg = __('One of these are on Sale', 'vtprd');
                break;
              case 'some':
                 $show_yousave_one_some_msg = __('Some of these are on Sale', 'vtprd');
                break;         
              case 'all':  //all are on sale, handled as normal.
                break; 
              default:  //handled as normal.
                break;       
            }
          }
        }
         
        if ($row['rule_short_msg'] > ' ' ) {       
          $short_msg_array [] = $row['rule_short_msg'];
          $full_msg_array  [] = $row['rule_full_msg'];
        }

      }

      /*
       if  $vtprd_cart->cart_level_status == 'rejected' no discounts found
       how to handle yousave display, etc.... If no yousave, return 'false'
      */
      if ( $vtprd_cart->cart_level_status == 'rejected' ) {
        $vtprd_cart->cart_items[0]->discount_price = 0;
        $vtprd_cart->cart_items[0]->yousave_total_amt = 0;
        $vtprd_cart->cart_items[0]->yousave_total_pct = 0;
      } 
      
      //needed for wp-e-commerce!!!!!!!!!!!
      //  if = 'yes', display of 'yousave' becomes 'save FROM' and doesn't change!!!!!!!
//      $product_variations_sw = vtprd_test_for_variations($product_id);
      $product_variations_sw = '';

      //v1.0.9.0 begin
      $number_of_times = 1;
      vtprd_get_cart_html_prices($number_of_times,'catalog');



      //*************************
      //v1.0.9.0 begin  refactored
      //*************************      
     if ($vtprd_cart->cart_items[0]->yousave_total_amt > 0) {
         $list_price                    =   $vtprd_cart->cart_items[0]->db_unit_price_list;
         
         //v1.0.8.8 begin
         //if taxation should be applied to list price, do so here
         if ( ( get_option('woocommerce_calc_taxes')  == 'yes' ) &&
              ( get_option('woocommerce_prices_include_tax') == 'no') &&
              ( get_option('woocommerce_tax_display_cart')   == 'incl') ) {
             
            $list_price                 =   vtprd_get_price_including_tax($product_id, $list_price); 
//error_log( print_r(  'vtprd_get_price_including_tax 001, price= ' .$list_price , true ) );                 
         }
         //v1.0.8.8 end
         
         $vtprd_cart->cart_items[0]->product_list_price_html_woo   =   woocommerce_price($list_price);
      }
      //v1.0.9.0 end
      
      //v1.0.9.3 begin
      //load info for old_price used later
      if ( ($vtprd_cart->cart_items[0]->yousave_total_amt > 0) &&
           ($vtprd_setup_options['show_catalog_price_crossout'] == 'yes') )  {
        switch( true ) {
          case ( ($vtprd_cart->cart_items[0]->db_unit_price_special > 0 ) &&
                 ($vtprd_cart->cart_items[0]->db_unit_price_special < $vtprd_cart->cart_items[0]->db_unit_price_list ) ) :                  //there is a discount...
              $product_orig_price = $vtprd_cart->cart_items[0]->db_unit_price_special; //special_price needs formatting ...
              
            break;
          default :               
              $product_orig_price = $vtprd_cart->cart_items[0]->db_unit_price_list;
            break; 
        } 
        
        $product_orig_price_html_woo = wc_price($product_orig_price);

        if ( get_option( 'woocommerce_calc_taxes' )  == 'yes' ) {
           if ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' )  {
              If (get_option( 'woocommerce_tax_display_shop' ) == 'excl' ) {
                $product = get_product( $product_id );                
                $product_orig_price_excl_tax =  vtprd_get_price_excluding_tax_forced($product_id, $product_orig_price, $product); 
                $product_orig_price_html_woo =  wc_price($product_orig_price_excl_tax);    
              }           
           } else {
              If (get_option( 'woocommerce_tax_display_shop' ) == 'incl' ) {
                $product = get_product( $product_id );                
                $product_orig_price_incl_tax =  vtprd_get_price_including_tax_forced($product_id, $product_orig_price, $product); 
                $product_orig_price_html_woo =  wc_price($product_orig_price_incl_tax); 
              }           
           }
        }           
            /*
           switch (true) {
              case ( (get_option( 'woocommerce_tax_display_shop' ) == 'excl' ) :
              
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

                $product = get_product( $product_id );                
                $product_orig_price_incl_tax =  vtprd_get_price_including_tax_forced($product_id, $product_orig_price, $product); 
                $product_orig_price_html_woo =  wc_price($product_orig_price_incl_tax);   

        $product = get_product( $product_id );                
        $product_orig_price_incl_tax =  vtprd_get_price_including_tax_forced($product_id, $product_orig_price, $product); 
        $product_orig_price_incl_tax_html_woo =  wc_price($product_orig_price_incl_tax); 
         
        $product_orig_price_excl_tax =  vtprd_get_price_excluding_tax_forced($product_id, $product_orig_price, $product);        
        $product_orig_price_excl_tax_html_woo =  wc_price($product_orig_price_excl_tax);
                     
        */
        
        
      }
      //v1.0.9.3 end
      
      $vtprd_info['product_session_info']  =     array (
            'product_list_price'           => $vtprd_cart->cart_items[0]->db_unit_price_list,
            'product_list_price_html_woo'  => $vtprd_cart->cart_items[0]->product_list_price_html_woo,
            'product_unit_price'           => $vtprd_cart->cart_items[0]->db_unit_price,
            'product_special_price'        => $vtprd_cart->cart_items[0]->db_unit_price_special,
            'product_discount_price'       => $vtprd_cart->cart_items[0]->product_discount_price_woo,   //mwntest
                                                  //$vtprd_cart->cart_items[0]->discount_price,
            //v1.0.7.4 - field now contains **just** the discount - suffix is added later.  
            //  this price reflects the tax situation of the ORIGINAL price - so if the price was originally entered with tax, this will reflect tax
         //   'product_discount_price_woo'   =>                                                           //v1.0.9.0
        //                                      $vtprd_cart->cart_items[0]->product_discount_unit_price_woo,   //mwntest
            'product_discount_price_html_woo'  => 
                                              $vtprd_cart->cart_items[0]->product_discount_price_html_woo,            
            //v1.0.7.4 begin
            'product_discount_price_incl_tax_woo'      =>
                                              $vtprd_cart->cart_items[0]->product_discount_price_incl_tax_woo,
            'product_discount_price_excl_tax_woo'      =>
                                              $vtprd_cart->cart_items[0]->product_discount_price_excl_tax_woo,
            'product_discount_price_incl_tax_html_woo'      =>
                                              $vtprd_cart->cart_items[0]->product_discount_price_incl_tax_html_woo,
            'product_discount_price_excl_tax_html_woo'      =>
                                              $vtprd_cart->cart_items[0]->product_discount_price_excl_tax_html_woo,                                              
            'product_discount_price_suffix_html_woo'   =>
                                              $vtprd_cart->cart_items[0]->product_discount_price_suffix_html_woo, 
            //v1.0.7.4 end
            //v1.0.9.3 begin
            'product_catalog_yousave_total_amt_incl_tax_woo'   =>
                                              $vtprd_cart->cart_items[0]->product_catalog_yousave_total_amt_incl_tax_woo,
            'product_catalog_yousave_total_amt_excl_tax_woo'   =>
                                              $vtprd_cart->cart_items[0]->product_catalog_yousave_total_amt_excl_tax_woo, 
            'product_orig_price_html_woo'   =>
                                              $product_orig_price_html_woo,
         /*   'product_orig_price_incl_tax_html_woo'   =>
                                              $product_orig_price_incl_tax_html_woo,                                              
            'product_orig_price_excl_tax_html_woo'   =>
                                              $product_orig_price_excl_tax_html_woo, */                                             
            //v1.0.9.3 end            
                                                        
            'product_is_on_special'        => $vtprd_cart->cart_items[0]->product_is_on_special,
            'product_yousave_total_amt'    => $vtprd_cart->cart_items[0]->yousave_total_amt,     
            'product_yousave_total_pct'    => $vtprd_cart->cart_items[0]->yousave_total_pct,    
            'product_rule_short_msg_array' => $short_msg_array,        
            'product_rule_full_msg_array'  => $full_msg_array,
            'product_has_variations'       => $product_variations_sw,
            'session_timestamp_in_seconds' => time(),
            'user_role'                    => vtprd_get_current_user_role(),
            'product_in_rule_allowing_display'  => $vtprd_cart->cart_items[0]->product_in_rule_allowing_display, //if not= 'yes', only msgs are returned 
            'show_yousave_one_some_msg'    => $show_yousave_one_some_msg, 
            //for later ajaxVariations pricing
            'this_is_a_parent_product_with_variations' => $vtprd_cart->cart_items[0]->this_is_a_parent_product_with_variations,            
            'pricing_by_rule_array'        => $vtprd_cart->cart_items[0]->pricing_by_rule_array,
            'product_id'                   => $product_id    //v1.0.9.0                  
      ) ;          
    //v1.0.9.0 end           
/*
error_log( print_r(  'product_session_info= ' , true ) ); 
 error_log( var_export($vtprd_info['product_session_info'], true ) );
 error_log( print_r(  '$vtprd_cart->cart_items[0]= ' , true ) ); 
 error_log( var_export($vtprd_cart->cart_items[0], true ) );
 */
      if(!isset($_SESSION)){
        session_start();
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
      } 
      //store session id 'vtprd_product_session_info_[$product_id]'
      $_SESSION['vtprd_product_session_info_'.$product_id] = $vtprd_info['product_session_info'];
      
      //initialize vtprd_cart to clear all discount values...  //v1.0.7.8
      $vtprd_cart = new vtprd_Cart;                            //v1.0.7.8      
  }



  
    // *** ------------------------------------------------------------------------------------------------------- ***
    // v1.0.9.0  new function
    // *** ------------------------------------------------------------------------------------------------------- ***
    function vtprd_get_cart_html_prices($number_of_times, $catalog_or_inline=null) {   
      global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_setup_options, $vtprd_rules_set;
 //error_log( print_r(  'vtprd_get_cart_html_prices', true ) );   
//error_log( print_r(  '$vtprd_cart= ' , true ) ); 
 //error_log( var_export($vtprd_cart, true ) );  
      $price_display_suffix = ''; //v1.0.9.3
      for($z=0; $z < $number_of_times; $z++) {  
        
        $product_id                    =   $vtprd_cart->cart_items[$z]->product_id;   //need this on both sides of the if
 //error_log( print_r(  'product_id= ' .$product_id, true ) ); 
        if ($vtprd_cart->cart_items[$z]->yousave_total_amt > 0) { 
         //  $product_id                    =   $vtprd_cart->cart_items[$z]->product_id;
           $list_price                    =   $vtprd_cart->cart_items[$z]->db_unit_price_list;
           $db_unit_price_list_html_woo   =   woocommerce_price($list_price);
                      
           //NEW UNIT PRICE  ( $vtprd_cart->cart_items[$z]->discount_price = units subtotal including discount )
           $discount_price                =   round($vtprd_cart->cart_items[$z]->discount_price / $vtprd_cart->cart_items[$z]->quantity , 2);  //mwntest

           //v1.0.9.3  begin
            if(isset($_SESSION['vtprd_product_old_price_'.$product_id])) { 
              $oldprice_formatted = stripslashes($_SESSION['vtprd_product_old_price_'.$product_id]); //this is FORMATTED  
              
              //check del/ins in oldprice
              if (strpos($oldprice_formatted,'</del>') !== false) {  //BOOLEAN == true...
                //strip out everything up to and including '<ins>', strip out </ins>
                // place result into  $oldPrice
                
                //splits string on <ins>, removes <ins>
                $oldPrice_array1 = explode('<ins>', $oldprice_formatted); //this removes the delimiter string... 
                
                //splits string on </ins>, removes </ins> (we will work on the 2nd half of original string)
                $oldPrice_array2 = explode('</ins>', $oldPrice_array1[1]); //this removes the delimiter string... 
                $oldprice_formatted = $oldPrice_array2[0];                
              }
   
              $currency_symbol = get_woocommerce_currency_symbol();
              
              //strip out currency symbol
              $oldprice =  str_replace($currency_symbol,'',$oldprice_formatted);
             
              //********************************* 
              //v1.0.9.5 begin - crossout price fix for using different decimal/thousands separators
              
              //strip out thousands separator ==>>(getting it this way covers pre-2.3 versions...)  
              $thousands_sep = wp_specialchars_decode( stripslashes( get_option( 'woocommerce_price_thousand_sep' ) ), ENT_QUOTES );
              $oldprice =  str_replace($thousands_sep,'',$oldprice);
              
              //reformat into decimal as needed if decimal separator not "." AND turn decimal into floatval
              $oldprice = wc_format_decimal($oldprice, 2);
              
              //v1.0.9.5  end
              //*********************************

            } else {              
              if ( ($vtprd_cart->cart_items[$z]->db_unit_price_special > 0 ) &&
                   ($vtprd_cart->cart_items[$z]->db_unit_price_special < $vtprd_cart_item->db_unit_price_list) ) {
                $oldprice_formatted = wc_price( $vtprd_cart->cart_items[$z]->db_unit_price_special ) ;
                $oldprice = $vtprd_cart->cart_items[$z]->db_unit_price_special;
              } else {
                $oldprice_formatted = wc_price( $vtprd_cart->cart_items[$z]->db_unit_price_list );
                $oldprice = $vtprd_cart->cart_items[$z]->db_unit_price_list;
              }
            }  
                     
           $vtprd_cart->cart_items[$z]->product_catalog_price_displayed =  $oldprice;
           $product = get_product( $product_id );               
    //       $vtprd_cart->cart_items[$z]->product_catalog_price_displayed_incl_tax_woo =  vtprd_get_price_including_tax($product_id, $oldprice); 
           $vtprd_cart->cart_items[$z]->product_catalog_price_displayed_incl_tax_woo =  vtprd_get_price_including_tax_forced($product_id, $oldprice, $product);  
           $vtprd_cart->cart_items[$z]->product_catalog_price_displayed_excl_tax_woo =  vtprd_get_price_excluding_tax_forced($product_id, $oldprice, $product); 
           
           $yousave_total_amt = $vtprd_cart->cart_items[$z]->yousave_total_amt;
           $vtprd_cart->cart_items[$z]->product_catalog_yousave_total_amt_incl_tax_woo =  vtprd_get_price_including_tax_forced($product_id, $yousave_total_amt, $product);  
           $vtprd_cart->cart_items[$z]->product_catalog_yousave_total_amt_excl_tax_woo =  vtprd_get_price_excluding_tax_forced($product_id, $yousave_total_amt, $product);              
                   
           $vtprd_cart->cart_items[$z]->computation_summary  = $vtprd_cart->cart_items[$z]->product_name .'<br>';  //v1.0.9.3
           $vtprd_cart->cart_items[$z]->computation_summary .= '++ Computation Summary ++' .'&nbsp;&nbsp;&nbsp;'.  '(may exclude tax until end...)' .'<br>'; //v1.0.9.3
           $vtprd_cart->cart_items[$z]->computation_summary .= '- Total Product Discount = ' .$vtprd_cart->cart_items[$z]->yousave_total_amt .'<br>'; //v1.0.9.3
           $vtprd_cart->cart_items[$z]->computation_summary .= '- Pre-Discount Unit Price = ' .$oldprice_formatted .'<br>'; //v1.0.9.3
           $vtprd_cart->cart_items[$z]->computation_summary .= '- Unit Quantity = ' .$vtprd_cart->cart_items[$z]->quantity .'<br>'; //v1.0.9.3
           //next one's kinda faked...
           $vtprd_cart->cart_items[$z]->computation_summary .= '- Subtotal = Quantity * Pre-Discount Unit Price = ' . ($vtprd_cart->cart_items[$z]->quantity * $vtprd_cart->cart_items[$z]->product_catalog_price_displayed).'<br>'; //v1.0.9.3
           $vtprd_cart->cart_items[$z]->computation_summary .= '- Discounted subtotal = subtotal - discount = ' .$vtprd_cart->cart_items[$z]->discount_price .'<br>'; //v1.0.9.3
           
           $vtprd_cart->cart_items[$z]->computation_summary .= '- Initial discounted Unit Price = Discounted subtotal / quantity = ' .$discount_price .'<br>'; //v1.0.9.3
           //v1.0.9.3  end
           
           
           //Test New Unit price for rounding error and fix
           if ($vtprd_setup_options['discount_taken_where'] == 'discountUnitPrice') { 
              
              $test_total_discount_price  = $discount_price * $vtprd_cart->cart_items[$z]->quantity;
              
              $vtprd_cart->cart_items[$z]->computation_summary .= '- Test new subtotal = Initial discounted Unit Price * quantity = ' .$test_total_discount_price .'<br>'; //v1.0.9.3
                         
              switch( true ) {
 
                case ($test_total_discount_price == $vtprd_cart->cart_items[$z]->discount_price):  
                    $all_good;
                  break;

                case ($test_total_discount_price > $vtprd_cart->cart_items[$z]->discount_price):  //not enough discount
                    if ($vtprd_setup_options['give_more_or_less_discount'] == 'more')  {
                      $discount_price = $discount_price - .01; //smaller unit price = MORE discount
                      
                      $vtprd_cart->cart_items[$z]->computation_summary .= 
                        '- Test new subtotal > Discounted subtotal, and'  . '<br>'.
                        '&nbsp;&nbsp;&nbsp;' . '"give more or less discount" = more,'  . '<br>'. 
                        '&nbsp;&nbsp;&nbsp;' . 'so .01 subtracted from Initial discounted Unit Price = ' .$discount_price. '<br>'; //v1.0.9.3
                      $test_total_discount_price  = $discount_price * $vtprd_cart->cart_items[$z]->quantity; //v1.0.9.3
                      $vtprd_cart->cart_items[$z]->computation_summary .= '- Test new subtotal = New discounted Unit Price * quantity = ' .$test_total_discount_price .'<br>'; //v1.0.9.3
               
                    } /*else {
                      //subtract a penny from $discount_price until $test_total_discount_price <= $vtprd_cart->cart_items[$z]->discount_price
                      $discount_price = $discount_price + .01; //larger discount_price = larger unit price = LESS discount                      
                    }*/
                  break;

                case ($test_total_discount_price < $vtprd_cart->cart_items[$z]->discount_price):  //too much discount
                    if ($vtprd_setup_options['give_more_or_less_discount'] == 'more')  {
                       $all_good; 
                    } else {
                       $discount_price = $discount_price + .01; //slarger unit price = LESS discount 
 
                      $vtprd_cart->cart_items[$z]->computation_summary .= 
                        '- Test new subtotal < Discounted subtotal, and'  . '<br>'.
                        '&nbsp;&nbsp;&nbsp;' . '"give more or less discount" = less,'  . '<br>'. 
                        '&nbsp;&nbsp;&nbsp;' . 'so .01 added to Initial discounted Unit Price = ' .$discount_price. '<br>'; //v1.0.9.3 
                      $test_total_discount_price  = $discount_price * $vtprd_cart->cart_items[$z]->quantity; //v1.0.9.3
                      $vtprd_cart->cart_items[$z]->computation_summary .= '- Test new subtotal = New discounted Unit Price * quantity = ' .$test_total_discount_price .'<br>'; //v1.0.9.3                                                                   
                    }                    
                  break; 
                                   
              }
           }
           
           $discount_price_html_woo       =   woocommerce_price($discount_price);

           //v1.0.7.4 begin
           $price_including_tax           =   vtprd_get_price_including_tax($product_id, $discount_price); 
//error_log( print_r(  'vtprd_get_price_including_tax 001, price= ' .$price_including_tax , true ) );
           $price_excluding_tax           =   vtprd_get_price_excluding_tax($product_id, $discount_price);
           $price_including_tax_html      =   wc_price( $price_including_tax );
           $price_excluding_tax_html      =   wc_price( $price_excluding_tax );
                      
           $vtprd_cart->cart_items[$z]->computation_summary .= '- Final discounted Unit Price = ' .$discount_price_html_woo .'<br>'; //v1.0.9.3
           $vtprd_cart->cart_items[$z]->computation_summary .= '- Final Unit Price including Tax = ' .$price_including_tax_html .'<br>'; //v1.0.9.3
           $vtprd_cart->cart_items[$z]->computation_summary .= '- Final Unit Price excluding Tax = ' .$price_excluding_tax_html; //v1.0.9.3
                      
           //v1.0.7.4 end
  
           //v1.0.7 begin
           //from woocommerce/includes/abstracts/abstract-wc-product.php
           // Check for Price Suffix
           
           //v1.0.7.4 begin
           //  no suffix processing if taxes not turned on!!
           global $woocommerce; 
           if ( ( get_option( 'woocommerce_calc_taxes' ) == 'no' ) ||
                ( vtprd_maybe_customer_tax_exempt() ) ) {      //v1.0.7.9   
              $price_display_suffix  = false; 
              
 //error_log( print_r(  'suffix false', true ) );     
                
           } else {
              $price_display_suffix  = get_option( 'woocommerce_price_display_suffix' );
           }
           //v1.0.7.4 end
//$yousave_total_amt = $vtprd_cart->cart_items[$z]->yousave_total_amt;
//error_log( print_r(  'yousave_total_amt' .$yousave_total_amt, true ) );           
        	 if ( ( $price_display_suffix ) &&                              //v1.0.7.2
                ( $vtprd_cart->cart_items[$z]->yousave_total_amt > 0 ) ) {   //v1.0.7.2  don't do suffix for zero amount...
        			
              //***************
              //v1.0.7.4 begin
              //***************
  
              if ( (strpos($price_display_suffix,'{price_including_tax}') !== false)  ||
                   (strpos($price_display_suffix,'{price_excluding_tax}') !== false) ) {   //does the suffix include these wildcards?
                //  $price_including_tax = vtprd_get_price_including_tax($product_id, $discount_price); 
                //  $price_excluding_tax = vtprd_get_price_excluding_tax($product_id, $discount_price); 
     
                $find = array(    //wildcards allowed in suffix
        				  '{price_including_tax}',
        		      '{price_excluding_tax}'
        			  );              
                //replace the wildcards in the suffix!            
                $replace = array(
          			//	wc_price( $this->get_price_including_tax() ),
          			//	wc_price( $this->get_price_excluding_tax() )
                  $price_including_tax_html,  
                  $price_excluding_tax_html 
          			);
  
          			$price_display_suffix = str_replace( $find, $replace, $price_display_suffix ); 
              }
              //v1.0.7.4 end
                                          
              //then see if additonal suffix is needed
              if (strpos($discount_price_html_woo, $price_display_suffix) !== false) { //if suffix already in price, do nothing
                $do_nothing;
              } else {
                //$discount_price_html_woo = $discount_price_html_woo . ' <small class="woocommerce-price-suffix ">' . $price_display_suffix . '</small>';
                $price_display_suffix  = '<small class="woocommerce-price-suffix ">' . $price_display_suffix . '</small>';
              }
           }
           
           //v1.0.7 end
     //      $vtprd_cart->cart_items[$z]->product_catalog_discount_price_html_woo = 
     //         '<del>' . $db_unit_price_list_html_woo . '</del><ins>' . $discount_price_html_woo . '</ins>'; 
         } else {
           $db_unit_price_list_html_woo = '';
           $discount_price = 0;          
           $discount_price_html_woo = '';            
           $price_including_tax = 0;            
           $price_excluding_tax = 0;            
           $price_including_tax_html = '';            
           $price_excluding_tax_html = '';
           
           //get the suffix for non-discounted pricing also!!
    //       global $product;
    //       $product = get_product( $product_id );                     
    //       $price_display_suffix = $product->get_price_suffix();
 //error_log( print_r(  'suffix is zero', true ) );             
 //error_log( print_r(  'yousave_total_amt <= 0, all discounts zeroed', true ) );                 
         }

         $vtprd_cart->cart_items[$z]->product_list_price_html_woo                          =  $db_unit_price_list_html_woo;   

 //error_log( print_r(  '$price_display_suffix= ' .$price_display_suffix, true ) );
         
         if ($catalog_or_inline == 'inline') {
           //load the price fields used for inline 
           $vtprd_cart->cart_items[$z]->product_inline_discount_price_woo                  =  $discount_price;          
           $vtprd_cart->cart_items[$z]->product_inline_discount_price_html_woo             =  $discount_price_html_woo;            
           $vtprd_cart->cart_items[$z]->product_inline_discount_price_incl_tax_woo         =  $price_including_tax;            
           $vtprd_cart->cart_items[$z]->product_inline_discount_price_excl_tax_woo         =  $price_excluding_tax;            
           $vtprd_cart->cart_items[$z]->product_inline_discount_price_incl_tax_html_woo    =  $price_including_tax_html;            
           $vtprd_cart->cart_items[$z]->product_inline_discount_price_excl_tax_html_woo    =  $price_excluding_tax_html;                   
           $vtprd_cart->cart_items[$z]->product_inline_discount_price_suffix_html_woo      =  $price_display_suffix; 
         } else {
           $vtprd_cart->cart_items[$z]->product_discount_price_woo                         =  $discount_price;          
           $vtprd_cart->cart_items[$z]->product_discount_price_html_woo                    =  $discount_price_html_woo;            
           $vtprd_cart->cart_items[$z]->product_discount_price_incl_tax_woo                =  $price_including_tax;            
           $vtprd_cart->cart_items[$z]->product_discount_price_excl_tax_woo                =  $price_excluding_tax;            
           $vtprd_cart->cart_items[$z]->product_discount_price_incl_tax_html_woo           =  $price_including_tax_html;            
           $vtprd_cart->cart_items[$z]->product_discount_price_excl_tax_html_woo           =  $price_excluding_tax_html;                     
           $vtprd_cart->cart_items[$z]->product_discount_price_suffix_html_woo             =  $price_display_suffix;                   
         }


 //error_log( print_r(  '$vtprd_cart->cart_items[$z] out of vtprd_get_cart_html_prices', true ) );
 //$cart_item_for_log = $vtprd_cart->cart_items[$z];
 //error_log( var_export($cart_item_for_log, true ) );
   
       }  //end foreach     
       
       return; 
  }

    //v1.0.9.3 new function
    function vtprd_maybe_price_incl_tax ($product_id, $price) { 
       global $woocommerce;    
       $product = get_product( $product_id ); //v1.0.7.4 
       
 
        if ( ( get_option( 'woocommerce_calc_taxes' ) == 'no' ) ||
             ( get_option( 'woocommerce_prices_include_tax' ) == 'no' )  || 
             ( vtprd_maybe_customer_tax_exempt() ) ) {      //v1.0.7.9  
           $do_nothing;                                                              //v1.0.9.0
        } else {

           $qty = 1;           
           $_tax  = new WC_Tax();                
          // $product = get_product( $product_id ); 
           $tax_rates  = $_tax->get_rates( $product->get_tax_class() );
  			 	 $taxes      = $_tax->calc_tax( $price  * $qty, $tax_rates, false );
  				 $tax_amount = $_tax->get_tax_total( $taxes );
  				 $price = round( $price  * $qty + $tax_amount, absint( get_option( 'woocommerce_price_num_decimals' ) ) ); 
                     
        }        
       
       
       return $price; 
  }
  
      
    function vtprd_fill_variations_checklist($tax_class, $checked_list = NULL, $pop_in_out_sw, $product_ID, $product_variation_IDs) { 
        global $post, $vtprd_info;
        // *** ------------------------------------------------------------------------------------------------------- ***
        // additional code from:  woocommerce/admin/post-types/writepanels/writepanel-product-type-variable.php
        // *** ------------------------------------------------------------------------------------------------------- ***
        //    woo doesn't keep the variation title in post title of the variation ID post, additional logic constructs the title ...
        
        $parent_post = get_post($product_ID);
        
        $attributes = (array) maybe_unserialize( get_post_meta($product_ID, '_product_attributes', true) );

       
        //**********************************
        //v1.0.7.9 begin
        //**********************************
        /*   DATA STRUCTURES
        
        ==>Custom variation
        $product = get_product( '1847' );
        $attributes = vtprd_get_attributes( $product ); 
        
        [18-Jul-2014 21:31:27 UTC] $attributes
        [18-Jul-2014 21:31:27 UTC] array (
          0 => 
          array (
            'name' => 'Volume',
            'option' => '10-ml',
          ),
        )
        
        ==>Variation PARENT
        $attributes2 = maybe_unserialize( get_post_meta( '1846', '_product_attributes', true ) );
        
        [18-Jul-2014 21:31:27 UTC] $attributes2 parent 1846 custom
        [18-Jul-2014 21:31:27 UTC] array (
          'volume' => 
          array (
            'name' => 'Volume',
            'value' => '10 ml. | 20ml. | 30ml',
            'position' => '2',
            'is_visible' => 1,
            'is_variation' => 1,
            'is_taxonomy' => 0,
          ),
        )
        
        $attributes2 = maybe_unserialize( get_post_meta( '738', '_product_attributes', true ) );
        [18-Jul-2014 21:31:27 UTC] $attributes2 parent 738 Normal
        [18-Jul-2014 21:31:27 UTC] array (
          'pa_colors2' => 
          array (
            'name' => 'pa_colors2',
            'value' => '',
            'position' => '0',
            'is_visible' => 1,
            'is_variation' => 1,
            'is_taxonomy' => 1,
          ),
          'pa_size2' => 
          array (
            'name' => 'pa_size2',
            'value' => '',
            'position' => '1',
            'is_visible' => 1,
            'is_variation' => 1,
            'is_taxonomy' => 1,
          ),
        )  
        
        [19-Jul-2014 12:01:13 UTC] $custom_options= 1846
        [19-Jul-2014 12:01:13 UTC] array (
          0 => 
          array (
            'taxonomy_name' => 'Volume',
            'options_list' => 
            array (
              0 => 
              array (
                'option' => '10-ml',
                'option_name' => '10 ml.',
              ),
              1 => 
              array (
                'option' => '20ml',
                'option_name' => '20ml.',
              ),
              2 => 
              array (
                'option' => '30ml',
                'option_name' => '30ml',
              ),
            ),
          ),
        )              
        */ 
               
        //Build Custom Options Name array for all attributes found for $product_ID.  Used later as name lookup...
        
        $custom_options = array();
        $current_version =  WOOCOMMERCE_VERSION;
        if( (version_compare(strval('2.1.0'), strval($current_version), '<=') == true) ) {   //only v2.1+
          foreach ($attributes as $attribute) {        
              //is this a CUSTOM variation??  if so, split up the custom variation names into the option array
              if ( ($attribute['is_variation']) &&
                   (!$attribute['is_taxonomy'])) {
                //custom variation attribute!!!!!!!!
                $option_names = array_map( 'trim', explode( WC_DELIMITER, $attribute['value'] ) );
                $options_list = array();
                foreach ($option_names as $option_name) {
                   $options_list[] = array (
                     'option' =>      sanitize_title( $option_name ),   //ex: 10-ml
                     'option_name' => $option_name                      //ex: 10 ML.
                   );
                }
                $custom_options[] = array (                             
                  'taxonomy_name' => $attribute['name'],                 //'Volume'
                  'options_list'  => $options_list                       //list from above
                );                                                       
              }
  				}
        } 
        $sizeof_custom_options = sizeof($custom_options);                
       //v1.0.7.9  end 
       //**********************************

        //$parent_post_terms = wp_get_post_terms( $post->ID, $attribute['name'] );  //v1.0.7.9  not used

        // woo parent product title only carried on parent post
        echo '<h3>' .$parent_post->post_title.    ' - Variations</h3>'; 
        
        foreach ($product_variation_IDs as $product_variation_ID) {     //($product_variation_IDs as $product_variation_ID => $info)
            // $variation_post = get_post($product_variation_ID);
         
            $output  = '<li id='.$product_variation_ID.'>' ;
            $output  .= '<label class="selectit">' ;
            $output  .= '<input id="'.$product_variation_ID.'_'.$tax_class.' " ';
            $output  .= 'type="checkbox" name="tax-input-' .  $tax_class . '[]" ';
            $output  .= 'value="'.$product_variation_ID.'" ';
            if ($checked_list) {
                if (in_array($product_variation_ID, $checked_list)) {   //if variation is in previously checked_list   
                   $output  .= 'checked="checked"';
                }                
            }
            $output  .= '>'; //end input statement
 
            $variation_label = ''; //initialize label
            $variation_product_name_attributes = '';  //v1.0.7.9
            $variation_product_name_attributes_cnt = 0;
            
            //get the variation names
            foreach ($attributes as $attribute) :

									// Get current value for variation (if set)                                                                                 
									$variation_selected_value = get_post_meta( $product_variation_ID, 'attribute_' . sanitize_title($attribute['name']), true );       

									// Only deal with attributes that are variations
									//**********************************
                  //v1.0.7.9 begin
                  if ( ( (isset($attribute['is_variation'])) &&    
                         (!$attribute['is_variation']) ) || 
                       (!isset($attribute['is_variation'])) ) {                              
                    continue; //skip to next in $attributes foreach
                  }
                  //v1.0.7.9 end
                  //**********************************

									// Get terms for attribute taxonomy or value if its a custom attribute
									if ($attribute['is_taxonomy']) :
										$post_terms = wp_get_post_terms( $product_ID, $attribute['name'] );
										foreach ($post_terms as $term) :
											if ($variation_selected_value == $term->slug) {
                          $variation_label .= $term->name . '&nbsp;&nbsp;' ;
                          
                          //for auto-insert support
                          if ($variation_product_name_attributes_cnt > 0) {
                            $variation_product_name_attributes .= '{,}';  //custom list separator
                          }
                          $variation_product_name_attributes .= $term->name;
                          $variation_product_name_attributes_cnt++;
                          
                      }
										endforeach;
									else :
										
                     //v1.0.7.9 begin
                     //check if this is a custom attrib...  
                      $taxonomy_name = $attribute['name'];
                      for($s=0; $s < $sizeof_custom_options; $s++) {
                        
                        if ($custom_options[$s]['taxonomy_name'] == $taxonomy_name) {
                            
                            $sizeof_options_list = sizeof($custom_options[$s]['options_list']);
                            for($z=0; $z < $sizeof_options_list; $z++) {
                           
                               if ($custom_options[$s]['options_list'][$z]['option'] == $variation_selected_value ) {
                                 
                                  $variation_label .= ucfirst($custom_options[$s]['options_list'][$z]['option_name']) . '&nbsp;&nbsp;' ;
                                  if ($variation_product_name_attributes_cnt > 0) {
                                    $variation_product_name_attributes .= '{,}';  //custom list separator
                                  }
                                  $variation_product_name_attributes .= $custom_options[$s]['options_list'][$z]['option_name'];
                                  $variation_product_name_attributes_cnt++;
                                  $z = $sizeof_options_list;
                                 
                               }
                            
                            }
                          
                            $s = $sizeof_custom_options;
                        }
                      }                    
                      //v1.0.7.9 end
									endif;

						endforeach;
    
            $output  .= '&nbsp;&nbsp; #' .$product_variation_ID. '&nbsp;&nbsp; - &nbsp;&nbsp;' .$variation_label;
            $output  .= '</label>';
            
            
            //hide name attribute list, used in auto add function ONLY AUTOADD
            // custom list of attributes with   '{,}' as a separator...
            $woo_attributes_id =  'woo_attributes_' .$product_variation_ID. '_' .$tax_class ;
            $output  .= '<input type="hidden" id="'.$woo_attributes_id.'" name="'.$woo_attributes_id.'" value="'.$variation_product_name_attributes.'">';          


            $output  .= '</li>'; 
            echo $output ;           
         }   

         
               
        return;     
    }
    

  /* ************************************************
  **   Get all variations for product
  *************************************************** */
  function vtprd_get_variations_list($product_ID) {
    global $wpdb;    
    //sql from woocommerce/classes/class-wc-product.php
   $variations = get_posts( array(
			'post_parent' 	=> $product_ID,
			'posts_per_page'=> -1,
			'post_type' 	  => 'product_variation',
			'fields' 		    => 'ids',
			'post_status'	  => 'publish',
      'order'         => 'ASC'
	  ));
   $product_variations_list = array(); //v1.0.5
   if ($variations)  {    
      $product_variations_list = array();
      foreach ( $variations as $variation) {
        $product_variations_list [] = $variation;             
    	}
    }/* else  {           v1.0.5
      $product_variations_list = array();
    } */

    return ($product_variations_list);
  } 

  
  
  function vtprd_test_for_variations($prod_ID) { 
      
     $vartest_response = 'no';
     
     /* Commented => DB access method uses more IO/CPU cycles than array processing below...
     //sql from woocommerce/classes/class-wc-product.php
     $variations = get_posts( array(
    			'post_parent' 	=> $prod_ID,
    			'posts_per_page'=> -1,
    			'post_type' 	=> 'product_variation',
    			'fields' 		=> 'ids',
    			'post_status'	=> 'publish'
    		));
     if ($variations)  {
        $vartest_response = 'yes';
     }  */
     
     // code from:  woocommerce/admin/post-types/writepanels/writepanel-product-type-variable.php
     $attributes = (array) maybe_unserialize( get_post_meta($prod_ID, '_product_attributes', true) );
     foreach ($attributes as $attribute) {
       if ( (isset( $attribute['is_variation'] ) )  &&   //v1.0.8.6
            ($attribute['is_variation']) )  {
          $vartest_response = 'yes';
          break;
       }
     }
     
     return ($vartest_response);     
  }  
  
    
   function vtprd_format_money_element($price) { 
      //from woocommerce/woocommerce-core-function.php   function woocommerce_price
    	$return          = '';
    	$num_decimals    = (int) get_option( 'woocommerce_price_num_decimals' );
    	$currency_pos    = get_option( 'woocommerce_currency_pos' );
    	$currency_symbol = get_woocommerce_currency_symbol();
    	$decimal_sep     = wp_specialchars_decode( stripslashes( get_option( 'woocommerce_price_decimal_sep' ) ), ENT_QUOTES );
    	$thousands_sep   = wp_specialchars_decode( stripslashes( get_option( 'woocommerce_price_thousand_sep' ) ), ENT_QUOTES );
    
    	$price           = apply_filters( 'raw_woocommerce_price', (double) $price );
    	$price           = number_format( $price, $num_decimals, $decimal_sep, $thousands_sep );
    
    	if ( get_option( 'woocommerce_price_trim_zeros' ) == 'yes' && $num_decimals > 0 )
    		$price = woocommerce_trim_zeros( $price );
    
    	//$return = '<span class="amount">' . sprintf( get_woocommerce_price_format(), $currency_symbol, $price ) . '</span>'; 

     $formatted = sprintf( get_woocommerce_price_format(), $currency_symbol, $price );
     
     return $formatted;
   }
   
   //****************************
   // Gets Currency Symbol from PARENT plugin   - only used in backend UI during rules update
   //****************************   
  function vtprd_get_currency_symbol() {    
    return get_woocommerce_currency_symbol();  
  } 


  //ALSO DEFINE IF tax_exempt
  function vtprd_get_current_user_role() {
    global $current_user; 
    
    if ( !$current_user )  {
      $current_user = wp_get_current_user();
    }
    
    $user_roles = $current_user->roles;
    $user_role = array_shift($user_roles);
    if  ($user_role <= ' ') {
      $user_role = 'notLoggedIn';
    }

    /*   NO LONGER NECESSARY - handled in v1.0.8.8
    //v1.0.9.0  begin --  load user tax exempt status
    
    global $vtprd_info;
    
    //if already loaded, we're done
    if ($vtprd_info['user_is_tax_exempt']) {
       return $user_role;
    }
    
    $vtprd_info['user_is_tax_exempt']  =  false;
          
    // check user-level tax exemption (plugin-specific checkbox on user screen)
    if (get_user_meta( $current_user->ID, 'vtprd_user_is_tax_exempt', true ) == 'yes') {
       $vtprd_info['user_is_tax_exempt']  =  true;
       
       return $user_role;
    }
    
    //check role-level tax exemption (plugin-specific role capability)
    if ( current_user_can( 'buy_tax_free', $user_id ) ) {
       $vtprd_info['user_is_tax_exempt']  =  true;
    }

    //v1.0.9.0  end
    */      
    return $user_role;
  } 


  //****************************************
  //v1.0.8.8 new function
  //**************************************** 
   //Make all logged-in roles with "buy_tax_free" capability tax-free
   /*
    * Enhancement - Added "Wholesale Tax Free" Role.  Added "buy_tax_free" Role Capability.
		Now **Any** User logged in with a role with the "buy_tax_free" Role Capability 
		will have 0 tax applied
		And the tax-free status will apply to the **Role**, regardless of whether a deal is currently active!!

    		**************************************** 
    		**Setup needed - Requires the addition of a  "Zero Rate Rates" tax class in the wp-admin back end 
    		*****************************************     
    		*(1) go to Woocommerce/Settings
    		*(2) Select (click on) the 'Tax' tab at the top of the page
    		*(3) You will then see, just below the tabs, the line     
    		    "Tax Options | Standard Rates | Reduced Rate Rates | Zero Rate Rates " 
    		*(4) Select (click on) "Zero Rate Rates " 
    		*(5) Then at the bottom left, click on 'insert row' .  
    		* Done.
    		* 
    * 
    **Now  any role with the capability 'buy_tax_free' will have 0 taxes applied!                               
   */

    add_filter( 'woocommerce_product_tax_class', 'vtprd_maybe_tax_free_tax_class', 1, 2 );
 
    function vtprd_maybe_tax_free_tax_class( $tax_class, $product ) {

    if  ( current_user_can('buy_tax_free') ) {
        $tax_class = 'Zero Rate';
    }
    return $tax_class;
  }


  //*******************************************************************************

  //*******************************************************************************
  function vtprd_print_widget_discount() {
    global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_setup_options;
      
    vtprd_load_cart_total_incl_excl(); //v1.0.7.4 
            
    //  vtprd_enqueue_front_end_css();      
      
    //*****************************
    //PRINT DISCOUNT ROWS + total line
    //*****************************

    $vtprd_cart->cart_discount_subtotal = $vtprd_cart->yousave_cart_total_amt; 
     
    if ($vtprd_setup_options['show_cartWidget_discount_detail_lines'] == 'yes') {
      $output  = '<h3 class="widget-title">';
      $output .=  __('Discounts', 'vtprd');
      $output .= '</h3>';
      echo $output;

      //do we repeat the purchases subtotal after the discount details?
      if ($vtprd_setup_options['show_cartWidget_purchases_subtotal'] == 'beforeDiscounts') {
         vtprd_print_widget_purchases_subtotal();
      } 

      if ($vtprd_setup_options['show_cartWidget_discount_detail_lines'] == 'yes') {
         vtprd_print_widget_discount_rows();
      } 
            
    } 

    //do we repeat the purchases subtotal after the discount details?
    if ($vtprd_setup_options['show_cartWidget_purchases_subtotal'] == 'withDiscounts') {
       vtprd_print_widget_purchases_subtotal();
       echo '<br>'; //additional break needed here
    } 

    if ($vtprd_setup_options['show_cartWidget_discount_total_line'] == 'yes') {
       vtprd_print_widget_discount_total();
    }     

    if ($vtprd_setup_options['cartWidget_new_subtotal_line'] == 'yes') {
       vtprd_print_widget_new_combined_total();
    }         
            

    return;
  }
 
  /* ************************************************
  **   print discount amount by product, and print total              
  *************************************************** */
	function vtprd_print_widget_discount_rows() {
    global $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;

      echo '<ul class="cart_list product_list_widget vtprd_product_list_widget">' ;

  
      $sizeof_cart_items = sizeof($vtprd_cart->cart_items);
      for($k=0; $k < $sizeof_cart_items; $k++) {  
       	if ( $vtprd_cart->cart_items[$k]->yousave_total_amt > 0) {            
            echo '<li>';
            $msg_cnt = 0;  
//echo 'yousave_by_rule_info= <pre>'.print_r($vtprd_cart->cart_items[$k]->yousave_by_rule_info, true).'</pre>' ; 
            if ($vtprd_setup_options['show_cartWidget_discount_details_grouped_by_what'] == 'rule') {
              //these rows are indexed by ruleID, so a foreach is needed...
              foreach($vtprd_cart->cart_items[$k]->yousave_by_rule_info as $key => $yousave_by_rule) {
                $msg_cnt++;
                if ($msg_cnt > 1) {
                  echo '</li><li>';
                }
//echo '<br>$key= '.$key.'<br>' ;
                $i = $yousave_by_rule['ruleset_occurrence'];
                //display info is tabulated for cumulative rule processing, but the Price Reduction has already taken place!!
                $output  = '<span class="vtprd-discount-msg-widget" >';                  
                $output .= stripslashes($yousave_by_rule['rule_short_msg']);
                $output .= '</span><br>';
                echo  $output;
//echo '$k= '.$k. ' $key= '.$key.'<br>' ;
//echo '$yousave_by_rule= <pre>'.print_r($yousave_by_rule, true).'</pre>' ;                 
                //if a max was reached and msg supplied, print here 
                if ($yousave_by_rule['rule_max_amt_msg'] > ' ') {    
                  $output  = '<span class="vtprd-discount-max-msg-widget" >';                  
                  $output .= stripslashes($yousave_by_rule['rule_max_amt_msg']);
                  $output .= '</span><br>';
                  echo  $output;                  
                }
                
                $amt = $yousave_by_rule['yousave_amt']; 
                $units = $yousave_by_rule['discount_applies_to_qty'];                  
                vtprd_print_discount_detail_line_widget($amt, $units, $k);
              }
            } else {   //show discounts by product
              $amt = $vtprd_cart->cart_items[$k]->yousave_total_amt; 
              $units = $vtprd_cart->cart_items[$k]->yousave_total_qty;                  
              vtprd_print_discount_detail_line_widget($amt, $units, $k);
           }
           
           echo '</li>';
        }
      }

      echo '</ul>' ;

    return;
    
  }
     
	function vtprd_print_discount_detail_line_widget($amt, $units, $k) {  
    global $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
    $output = ''; //v1.0.7.9

    if (sizeof($vtprd_cart->cart_items[$k]->variation_array) > 0   ) {
      $output .= '<span class="vtprd-product-name-widget">' . $vtprd_cart->cart_items[$k]->parent_product_name .'</span>';	
      //v1.0.7.9 begin
      if ($vtprd_cart->cart_items[$k]->variation_name_html > '')  {
        $output .= $vtprd_cart->cart_items[$k]->variation_name_html;
      } else {
        $output .= '<dl class="variation">';
        foreach($vtprd_cart->cart_items[$k]->variation_array as $key => $value) {          
          //v1.0.7.8  begin               
          $name  = str_replace( 'attribute_pa_', '', $key  );  //post v 2.1
          $name  = str_replace( 'attribute_', '', $key  );     //post v 2.1   for on-the-fly variations
          $value = str_replace( 'attribute_', '', $value  );   //post v 2.1   for on-the-fly variations
          $name  = str_replace( 'pa_', '', $name  );   //pre v 2.1
          $name  = ucwords($name);
          $current_version =  WOOCOMMERCE_VERSION;
          if( (version_compare(strval('2.1.0'), strval($current_version), '>') == 1) ) {   //'==1' = 2nd value is lower     
            //pre 2.1
            $name  = sanitize_title($name);
            $value = sanitize_title($value);            
            $output .= '<dt class="variation-'.$name.'">'. $name . ': </dt>';  //added class
            $output .= '<dd class="variation-'.$name.'">'. $value .'</dd>';    //added class
          } else {
            //post 2.1
            //$name2 = sanitize_text_field( $name );
            $name2 = sanitize_title( $name );
            $name2_san = sanitize_html_class( $name2 );
            $output .= '<dt class="variation-'. $name2_san.'">'. wp_kses_post( $name ) . ': </dt>';  //added class
            $output .= '<dd class="variation-'. $name2_san.'">'. wp_kses_post( wpautop( $value )) .'</dd>';    //added class
          }
          //v1.0.7.8  end              
        }
        $output .= '</dl>';
      }
      //v1.0.7.9 end    
    } else {
      $output .= '<span class="vtprd-product-name-widget">' . $vtprd_cart->cart_items[$k]->product_name  .'</span>';
      $output .= '<br>';
    }    
            
    //*************************************
    //division creates a per-unit discount
    //*************************************
    $amt = $amt / $units;
    
    //v1.0.7.4 begin      
    $amt = vtprd_format_amt_and_adjust_for_taxes($amt, $k);  //has both formatted amount and suffix, prn
    // $amt = vtprd_format_money_element($amt);
    //v1.0.7.4 end
    		
    $output .= '<span class="quantity vtprd-quantity-widget">' . $units  .' &times; ';	
    $output .= '<span class="amount vtprd-amount-widget">' . $vtprd_setup_options['cartWidget_credit_detail_label'] .$amt  .'</span>';
    $output .= '</span>';	    

    
        
    /*
    $output .= '<span class="vtprd-prod-name-widget" >';
    $output .= $vtprd_cart->cart_items[$k]->product_name;
    $output .= '</span><br>';        
    
    $output .= '<span class="quantity vtprd-discount-quantity-widget" >';
    
    //*************************************
    //division creates a per-unit discount
    //*************************************
    $amt = $amt / $units;
    
    $amt = vtprd_format_money_element($amt); 
    
    $output .= $units .' x &nbsp;-'. $amt;
    $output .= '</span>';
    */
    //$output .= 'HOLA!';//mwnecho
    
    echo  $output;
    return;  
 }

    
	function vtprd_print_widget_discount_total() {
    global $vtprd_cart, $woocommerce, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;

    if ($vtprd_setup_options['show_cartWidget_discount_total_line'] == 'no') { 
      return;
    }
                                                    
    $output = ''; //v1.0.7.9
    $output .= '<span class="total vtprd-discount-total-label-widget" >';
    $output .= '<strong>'.$vtprd_setup_options['cartWidget_credit_total_title']. '&nbsp;</strong>';     

    //v1.0.9.3 begin
    if ( get_option( 'woocommerce_calc_taxes' )  == 'yes' ) {
      if (get_option('woocommerce_tax_display_cart')   == 'incl') {
        $amt = vtprd_format_money_element($vtprd_cart->cart_discount_subtotal);
      } else {
        $amt = vtprd_format_money_element($vtprd_cart->yousave_cart_total_amt_excl_tax);
        $amt .= ' ' . __( '(ex. VAT)', 'vtprd' );
      }
    } else {
      $amt = vtprd_format_money_element($vtprd_cart->cart_discount_subtotal);
    }
    //v1.0.9.3 END

    $output .= '<span class="amount  vtprd-discount-total-amount-widget">' . $vtprd_setup_options['cartWidget_credit_total_label'] .$amt . '</span>';

    $output .= '</span>';
    echo  $output;
       
    return;
    
  }

    
	function vtprd_print_widget_new_combined_total() {
    global $vtprd_cart, $woocommerce, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;

    if ($vtprd_setup_options['cartWidget_new_subtotal_line'] == 'no') {
       return;
    }

    $output = ''; //v1.0.7.9
    $output .= '<p class="total vtprd-combined-total-label-widget" >';
    $output .= '<strong>'.$vtprd_setup_options['cartWidget_new_subtotal_label'] .'&nbsp;</strong>';     

    
   //v1.0.7.5 - changed to "get_option('woocommerce_tax_display_cart')" - $woocommerce didn't have the info yet...
   if ( get_option('woocommerce_tax_display_cart') == 'excl' ) {  //v1.0.7.5
			$subtotal = $woocommerce->cart->subtotal_ex_tax ;
		} else {
			$subtotal = $woocommerce->cart->subtotal;
    }

    //v1.0.8.9a begin               
    // pick up included, excluded or yousave_cart_total_amt Total       
    //$subTotal -= $vtprd_cart->cart_discount_subtotal;

    //v1.0.9.3 begin
    //$subtotal -= vtprd_load_cart_total_incl_excl();
    if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  {   		
    	$subtotal -= vtprd_load_cart_total_incl_excl();
    }
    //v1.0.9.3 end      
    
    //v1.0.8.9a end  
    
    $amt = vtprd_format_money_element($subtotal);

//    $amt = $woocommerce->cart->subtotal .' - ' . $vtprd_cart->cart_discount_subtotal .' = '. $subtotal; //test test
    
    $output .= '<span class="amount  vtprd-discount-total-amount-widget">' .$amt . '</span>';

    $output .= '</p>';
    echo  $output;
       
    return;
    
  }
  
  
  /* ************************************************
  **   print cart widget purchase subtotal             
  *************************************************** */
   
	function vtprd_print_widget_purchases_subtotal() {
    global $vtprd_cart, $woocommerce, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
                                               
    $output = ''; //v1.0.7.9
    $output .= '<span class="total vtprd-product-total-label-widget" >';
    $output .= '<strong>'.$vtprd_setup_options['cartWidget_credit_subtotal_title']. '&nbsp;</strong>';     

    $amt = vtprd_get_Woo_cartSubtotal(); 
    
    $output .= '<span class="amount  vtprd-discount-total-amount-widget">' .$amt . '</span>';

    $output .= '</span>';
    echo  $output;
       
    return;
    
  }
 
  /* ************************************************
  **   print discount amount by product, and print total AND MOVE DISCOUNT INTO TOTAL...             
  *************************************************** */

	function vtprd_print_checkout_discount() {
    global $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
   //when executing from here, the table rows created by the print routines need a <table>
    //  when executed from the cart_widget, the TR lines appear in the midst of an existing <table>

    vtprd_load_cart_total_incl_excl(); //v1.0.7.4 
          
    $execType = 'checkout';
    
    if ($vtprd_setup_options['show_checkout_purchases_subtotal'] == 'beforeDiscounts') {
      vtprd_print_cart_purchases_subtotal($execType);
    }
    
    $output = ''; //v1.0.7.9

    $output .=  '<table class="vtprd-discount-table"> ';
    
    
    if ($vtprd_setup_options['show_checkout_discount_titles_above_details'] == 'yes') {    
      $output .= '<tr id="vtprd-discount-title-checkout" >';
            /* COLSPAN no longer used here, has no affect
      $output .= '<td colspan="' .$vtprd_setup_options['checkout_html_colspan_value']. '" id="vtprd-discount-title-above-checkout">';
      */
      $output .= '<td id="vtprd-discount-title-above-checkout">';
      $output .= '<div class="vtprd-discount-prodLine-checkout" >';
      
      $output .= '<span class="vtprd-discount-prodCol-checkout">' .  __('Product', 'vtprd') . '</span>';
      
      $output .= '<span class="vtprd-discount-unitCol-checkout">' .  __('Discount Qty', 'vtprd') . '</span>';
  
      //v1.0.9.0 added new title
      if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  {   
        $output .= '<span class="vtprd-discount-amtCol-checkout">' .  __('Discount Amount', 'vtprd') . '</span>';
      } else {
        $output .= '<span class="vtprd-discount-amtCol-checkout">' .  __('Discount', 'vtprd') .'<br>'.  __('( in Unit Price )', 'vtprd') . '</span>';
      }
      
      $output .= '</div'; //end prodline
      $output .= '</td>';
      $output .= '</tr>';
         
     }
     echo  $output;
    
    $vtprd_cart->cart_discount_subtotal = $vtprd_cart->yousave_cart_total_amt;
    
    /*
    if ($vtprd_rules_set[0]->coupons_amount_without_rule_discounts > 0) {
       $vtprd_cart->cart_discount_subtotal += $vtprd_rules_set[0]->coupons_amount_without_rule_discounts;
       //print a separate discount line if price discounts taken, PRN
       vtprd_print_coupon_discount_row($execType);
    }
    */ 
                                                 
    //print discount detail rows 
    vtprd_print_cart_discount_rows($execType);
 
    if ($vtprd_setup_options['show_checkout_purchases_subtotal'] == 'withDiscounts') {
      vtprd_print_cart_purchases_subtotal($execType);
    } 
 
 /*
    if ($vtprd_rules_set[0]->coupons_amount_without_rule_discounts > 0) {
       //print totals using the coupon amount  
       if ($vtprd_setup_options['show_checkout_credit_total_when_coupon_active'] == 'yes')  {          
          vtprd_print_cart_discount_total($execType); 
       }    
    } else {
      //if there's no coupon being presented, no coupon totals will be printed, so discount total line is needed     
      vtprd_print_cart_discount_total($execType);   
    }
    */
    vtprd_print_cart_discount_total($execType); 

    if ($vtprd_setup_options['checkout_new_subtotal_line'] == 'yes') {
      vtprd_print_new_cart_checkout_subtotal_line($execType);
    }    

    echo   '</table>  <!-- vtprd discounts table close -->  '; 
        
 } 
 
  /* ************************************************
  **   print discount amount by product, and print total              
  *************************************************** */
	function vtprd_print_cart_discount_rows($execType) {
    global $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
       
      $printRowsCheck = 'show_' .$execType. '_discount_detail_lines';
      if ($vtprd_setup_options[$printRowsCheck] == 'no') {
        return;
      }
  
      $sizeof_cart_items = sizeof($vtprd_cart->cart_items);
      for($k=0; $k < $sizeof_cart_items; $k++) {  
       	if ( $vtprd_cart->cart_items[$k]->yousave_total_amt > 0) {            
            if ((($execType == 'checkout')   && ($vtprd_setup_options['show_checkout_discount_details_grouped_by_what']   == 'rule')) ||
                (($execType == 'cartWidget') && ($vtprd_setup_options['show_cartWidget_discount_details_grouped_by_what'] == 'rule'))) {
              //these rows are indexed by ruleID, so a foreach is needed...
              foreach($vtprd_cart->cart_items[$k]->yousave_by_rule_info as $key => $yousave_by_rule) {
                $i = $yousave_by_rule['ruleset_occurrence'];
                //display info is tabulated for cumulative rule processing, but the Price Reduction has already taken place!!
                if ($vtprd_rules_set[$i]->rule_execution_type == 'cart') {
                  $output  = '<tr class="vtprd-discount-title-row" >';                  
                  $output .= '<td  class="vtprd-ruleNameCol-' .$execType. ' vtprd-border-cntl vtprd-deal-msg" >' . stripslashes($yousave_by_rule['rule_short_msg']) . '</td>';
                  $output .= '</tr>';
                  echo  $output;
                  
                  //if a max was reached and msg supplied, print here 
                  if ($yousave_by_rule['rule_max_amt_msg'] > ' ') {    
                    $output  = '<tr class="vtprd-discount-title-row" >';                  
                    $output .= '<td  class="vtprd-ruleNameCol-' .$execType. ' vtprd-border-cntl vtprd-deal-msg" >' . stripslashes($yousave_by_rule['rule_max_amt_msg']) . '</td>';
                    $output .= '</tr>';
                  echo  $output;                  
                  }
                  
                  $amt = $yousave_by_rule['yousave_amt']; 
                  $units = $yousave_by_rule['discount_applies_to_qty'];                  
                  vtprd_print_discount_detail_line($amt, $units, $execType, $k);
                }
              }
            } else {   //show discounts by product
                  $amt = $vtprd_cart->cart_items[$k]->yousave_total_amt; 
                  $units = $vtprd_cart->cart_items[$k]->yousave_total_qty;                  
                  vtprd_print_discount_detail_line($amt, $units, $execType, $k);
           }
        }
      }

    return;
    
  }

  function vtprd_print_cart_widget_title() {     
    global $vtprd_setup_options;
    if ($vtprd_setup_options['show_cartWidget_discount_titles_above_details'] == 'yes') {    
      $output = ''; //v1.0.7.9  
      $output .= '<tr id="vtprd-discount-title-cartWidget" >';
      $output .= '<td colspan="' .$vtprd_setup_options['cartWidget_html_colspan_value']. '" id="vtprd-discount-title-cartWidget-line">';
      $output .= '<div class="vtprd-discount-prodLine-cartWidget" >';
      
      $output .= '<span class="vtprd-discount-prodCol-cartWidget">&nbsp;</span>';
      
      $output .= '<span class="vtprd-discount-unitCol-cartWidget">&nbsp;</span>';
  
      $output .= '<span class="vtprd-discount-amtCol-cartWidget">' .  __('Discount', 'vtprd') . '</span>';
      
      $output .= '</div>'; //end prodline
      $output .= '</td>';
      $output .= '</tr>';

      echo  $output;
    }
    return;   
  }
 
     
	function vtprd_print_discount_detail_line($amt, $units, $execType, $k) {  
    global $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
    $output = ''; //v1.0.7.9
    $output .= '<tr class="vtprd-discount-total-for-product-rule-row-' .$execType. '  bottomLine-' .$execType. '" >';
    $output .= '<td colspan="' .$vtprd_setup_options['' .$execType. '_html_colspan_value']. '">';
    $output .= '<div class="vtprd-discount-prodLine-' .$execType. '" >';
    
    $output .= '<span class="vtprd-discount-prodCol-' .$execType. '" id="vtprd-discount-product-id-' . $vtprd_cart->cart_items[$k]->product_id . '">';
    $output .= $vtprd_cart->cart_items[$k]->product_name;
    $output .= '</span>';
    
    $output .= '<span class="vtprd-discount-unitCol-' .$execType. '">' . $units . '</span>';

     //v1.0.7.4 begin      
    $amt = vtprd_format_amt_and_adjust_for_taxes($amt, $k);  //has both formatted amount and suffix, prn
    // $amt = vtprd_format_money_element($amt);
    //v1.0.7.4 end   
    
    $output .= '<span class="vtprd-discount-amtCol-' .$execType. '">' . $vtprd_setup_options['' .$execType. '_credit_detail_label'] . ' ' .$amt . '</span>';
    
    $output .= '</div>'; //end prodline
    $output .= '</td>';
    $output .= '</tr>';
    echo  $output;  
 }

/*  
  //coupon discount only shows at Checkout 
	function vtprd_print_coupon_discount_row($execType) {
    global $woocommerce, $vtprd_setup_options, $vtprd_rules_set;

    $output;
    $output .= '<tr class="vtprd-discount-total-for-product-rule-row-' .$execType. '  bottomLine-' .$execType. '  vtprd-coupon_discount-' .$execType. '" >';
    $output .= '<td colspan="' .$vtprd_setup_options['' .$execType. '_html_colspan_value']. '">';
    $output .= '<div class="vtprd-discount-prodLine-' .$execType. '" >';
    
    $output .= '<span class="vtprd-discount-prodCol-' .$execType. ' vtprd-coupon_discount-literal-' .$execType. '">';
    $output .= __('Coupon Discount: ', 'vtprd'); 
    $output .= '</span>';
    
    $output .= '<span class="vtprd-discount-unitCol-' .$execType. '">&nbsp;</span>';
    
    $labelType = $execType . '_credit_detail_label';
    
    $amt = vtprd_format_money_element($vtprd_rules_set[0]->coupons_amount_without_rule_discounts);  //show original coupon amt as credit
    $output .= '<span class="vtprd-discount-amtCol-' .$execType. '  vtprd-coupon_discount-amt-' .$execType. '">' . $vtprd_setup_options['' .$execType. '_credit_detail_label'] . ' ' .$amt . '</span>';
    
    $output .= '</div>'; //end prodline
    $output .= '</td>';
    $output .= '</tr>';
    echo  $output; 
       
    return;
    
  }
 */  
   
	//***************************************
  // Subtotal - Cart Purchases:
  //***************************************
  function vtprd_print_cart_purchases_subtotal($execType) {
    global $vtprd_cart, $woocommerce, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;   
      $subTotalCheck = 'show_' .$execType. '_purchases_subtotal';
      if ($vtprd_setup_options[$subTotalCheck] == 'none') {     
        return;
      }

      $output = ''; //v1.0.7.9
      if ($vtprd_setup_options[$subTotalCheck] == 'beforeDiscounts') {
          $output .= '<tr class="vtprd-discount-total-' .$execType. '" >';
          $output .= '<td colspan="' .$vtprd_setup_options['' .$execType. '_html_colspan_value'].'" class="vtprd-discount-total-' .$execType. '-line">';
          $output .= '<div class="vtprd-discount-prodLine-' .$execType. '" >';
          
          $output .= '<span class="vtprd-discount-totCol-' .$execType. '">';
          $output .= $vtprd_setup_options['' .$execType. '_credit_subtotal_title'];
          $output .= '</span>';
      
 //       due to a WPEC problem,  $vtprd_cart->cart_original_total_amt  may be inaccurate - use wpec's own subtotaling....
 //         $subTotal = $vtprd_cart->cart_original_total_amt;    //show as a credit 
          $amt = vtprd_get_Woo_cartSubtotal();
  
          $labelType = $execType . '_credit_detail_label';  
          $output .= '<span class="vtprd-discount-totAmtCol-' .$execType. '"> &nbsp;&nbsp;' .$amt . '</span>';
          
          $output .= '</div>'; //end prodline
          $output .= '</td>';
          $output .= '</tr>'; 
      } else {
          $output .= '<tr class="vtprd-discount-total-' .$execType. '" >';
          $output .= '<td colspan="' .$vtprd_setup_options['' .$execType. '_html_colspan_value'].'" class="vtprd-discount-total-' .$execType. '-line">';
          $output .= '<div class="vtprd-discount-prodLine-' .$execType. '" >';
          
          $output .= '<span class="vtprd-discount-totCol-' .$execType. '">';
          $output .= $vtprd_setup_options['' .$execType. '_credit_subtotal_title'];
          $output .= '</span>';
      
      
 //         $subTotal = $vtprd_cart->cart_original_total_amt;    //show as a credit
          $amt = vtprd_get_Woo_cartSubtotal();
          
          $labelType = $execType . '_credit_detail_label';  
          $output .= '<span class="vtprd-discount-totAmtCol-' .$execType. '"> &nbsp;&nbsp;' .$amt . '</span>';
          
          $output .= '</div>'; //end prodline
          $output .= '</td>';
          $output .= '</tr>'; 
      }
      echo  $output;
   
    return;
    
  }

  //***************************************
  // Subtotal with Discount:  (print)
  //***************************************
	function vtprd_print_new_cart_checkout_subtotal_line($execType) {
    global $vtprd_cart, $woocommerce, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;   

      $output = ''; //v1.0.7.9
 
      $output .= '<tr class="vtprd-discount-total-' .$execType. ' vtprd-new-subtotal-line" >';
      $output .= '<td colspan="' .$vtprd_setup_options['' .$execType. '_html_colspan_value'].'" class="vtprd-discount-total-' .$execType. '-line">';
      $output .= '<div class="vtprd-discount-prodLine-' .$execType. '" >';
      
      $output .= '<span class="vtprd-discount-totCol-' .$execType. '">';
      $output .= $vtprd_setup_options['' .$execType. '_new_subtotal_label'];
      $output .= '</span>';
  
  
      //$subTotal = $vtprd_cart->cart_original_total_amt - $vtprd_cart->yousave_cart_total_amt;    //show as a credit
      //v1.0.8.9a begin  
      //$subTotal  = $woocommerce->cart->subtotal;
       
      if ( $woocommerce->cart->tax_display_cart == 'excl' ) {
    		$subtotal = $woocommerce->cart->subtotal_ex_tax ;
    	} else {
    		$subtotal = $woocommerce->cart->subtotal;
      }  
             
      // pick up included, excluded or yousave_cart_total_amt Total       
      //$subTotal -= $vtprd_cart->cart_discount_subtotal;
      $subtotal -= vtprd_load_cart_total_incl_excl();
      $amt = vtprd_format_money_element($subtotal); 
      //v1.0.8.9a end  
            
      
      $labelType = $execType . '_credit_detail_label';  
      $output .= '<span class="vtprd-discount-totAmtCol-' .$execType. ' vtprd-new-subtotal-amt"> &nbsp;&nbsp;' .$amt . '</span>';
      
      $output .= '</div>'; //end prodline
      $output .= '</td>';
      $output .= '</tr>'; 

      echo  $output;
   
    return;  
  }
    
     
	function vtprd_print_cart_discount_total($execType) {
    global $vtprd_cart, $woocommerce, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
    
    $printRowsCheck = 'show_' .$execType. '_discount_total_line';
    
    if ($vtprd_setup_options[$printRowsCheck] == 'no') {
      return;
    }
    $output = ''; //v1.0.7.9
    $output .= '<tr class="vtprd-discount-total-' .$execType. ' vtprd-discount-line" >';    
    $output .= '<td colspan="' .$vtprd_setup_options['' .$execType. '_html_colspan_value']. '" class="vtprd-discount-total-' .$execType. '-line ">';
    $output .= '<div class="vtprd-discount-prodLine-' .$execType. '" >';
    
    $output .= '<span class="vtprd-discount-totCol-' .$execType. '">';
    $output .= $vtprd_setup_options['' .$execType. '_credit_total_title'];
    $output .= '</span>';

    $amt = vtprd_format_money_element($vtprd_cart->cart_discount_subtotal);
    
    $output .= '<span class="vtprd-discount-totAmtCol-' .$execType. ' vtprd-discount-amt">' . $vtprd_setup_options['' .$execType. '_credit_detail_label'] . ' ' .$amt . '</span>';
     
    $output .= '</div>'; //end prodline
    $output .= '</td>';
    $output .= '</tr>';
    echo  $output;
       
    return;
    
  }
   
    
     /*
    \n = CR (Carriage Return) // Used as a new line character in Unix
    \r = LF (Line Feed) // Used as a new line character in Mac OS
    \n\r = CR + LF // Used as a new line character in Windows
    (char)13 = \n = CR // Same as \n
    http://en.wikipedia.org/wiki/Newline
    */
  /* ************************************************
  **   Assemble all of the cart discount row info FOR email/transaction results messaging  
  *        $msgType = 'html' or 'plainText'            
  *************************************************** */
	function vtprd_email_cart_reporting($msgType) {
    global $vtprd_cart, $vtprd_cart_item, $vtprd_rules_set, $vtprd_info, $vtprd_setup_options;
    $output = ''; //v1.0.7.9
    
    if ($vtprd_setup_options['show_checkout_discount_titles_above_details'] == 'yes') {
      if ($msgType == 'html') {
        //Skip a line between products and discounts      		
        $output .= '<tr>';
        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word"  colspan="3"> &nbsp;</td>';				
        $output .= '</tr>';  
        
        //New headers, but printed as TD instead, to keep the original structure going...                    
        $output .= '<tr>';
        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word;font-weight:bold;">' . __('Discount Product', 'vtprd') .'</td>';			
        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word;font-weight:bold;">' . __('Quantity', 'vtprd') .'</td>';			
        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word;font-weight:bold;">' . __('Amount', 'vtprd') .'</td>';		
        $output .= '</tr>';    
      
      } else {
        //first a couple of page ejects
        $output .= "\r\n \r\n";
        $output .= __( 'Discounts ', 'vtprd' );
        $output .= "\r\n";
      }
    }
 
    if ($vtprd_setup_options['show_checkout_discount_detail_lines'] == 'yes') { //v1.0.9.0
      //get the discount details    
      $output .= vtprd_email_cart_discount_rows($msgType);
    }
     
    vtprd_load_cart_total_incl_excl(); //v1.0.7.4 
    
    if ($vtprd_setup_options['show_checkout_discount_total_line'] == 'yes') {
        
        //v1.0.8.9a begin               
        //$amt = vtprd_format_money_element($vtprd_cart->yousave_cart_total_amt);        
        $amt = vtprd_load_cart_total_incl_excl();
        $amt = vtprd_format_money_element($amt); 
        $amt .= vtprd_maybe_load_incl_excl_vat_lit();  //v1.0.7.4         
        //v1.0.8.9a end  
                  
      if ($msgType == 'html') {        
        //v1.0.8.9a begin               
        //$amt = vtprd_format_money_element($vtprd_cart->yousave_cart_total_amt); 
        //$amt .= vtprd_maybe_load_incl_excl_vat_lit();  //v1.0.7.4             
        //v1.0.8.9a end       
        $output .= '<tr>';
        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word;font-weight:bold"  colspan="2">'. $vtprd_setup_options['checkout_credit_total_title'] .'</td>';						
        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee">'  . $vtprd_setup_options['checkout_credit_total_label'] .$amt .'</td>';		
        $output .= '</tr>';   
        $output .= '<tr>';
        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word"  colspan="3"> &nbsp;</td>';				
        $output .= '</tr>';
      } else {
        $output .= "\r\n";
        $output .= "\n" .$vtprd_setup_options['checkout_credit_total_title'];
        $output .= "\n" .$vtprd_setup_options['checkout_credit_total_label'] .$amt ;
      }
    }      
           
    return $output;
    
  }
  
  //coupon discount only shows at Checkout 
	function vtprd_email_cart_coupon_discount_row($msgType) {
    global $vtprd_cart, $vtprd_rules_set, $vtprd_setup_options;

    $output = ''; //v1.0.7.9
    $amt = vtprd_format_money_element($vtprd_cart->wpsc_orig_coupon_amount);  //show original coupon amt as credit
    
    vtprd_format_money_element($vtprd_cart->wpsc_orig_coupon_amount);  //show original coupon amt as credit
       
    if ($msgType == 'html')  {
      $output .= '<tr>';
        $output .= '<td colspan="2">' . __('Coupon Discount', 'vtprd') .'</td>';
        $output .= '<td>' . $vtprd_setup_options['checkout_credit_detail_label'] . ' ' .$amt .'</td>';
      $output .= '</tr>';    
    } else {
      $output .= __('Coupon Discount: ', 'vtprd'); 
      
      $output .= $amt;
      $output .= "\r\n \r\n";
    }

    return $output; 
    
  }      
    
  /* ************************************************
  **   Assemble all of the cart discount row info              
  *************************************************** */
	function vtprd_email_cart_discount_rows($msgType) {
    global $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
       
      $output = ''; //v1.0.7.9

      $sizeof_cart_items = sizeof($vtprd_cart->cart_items);
      for($k=0; $k < $sizeof_cart_items; $k++) {  
       	if ( $vtprd_cart->cart_items[$k]->yousave_total_amt > 0) {            
            if ($vtprd_setup_options['show_checkout_discount_details_grouped_by_what']   == 'rule') {
              //these rows are indexed by ruleID, so a foreach is needed...
              foreach($vtprd_cart->cart_items[$k]->yousave_by_rule_info as $key => $yousave_by_rule) {
              
                //display info is tabulated for cumulative rule processing, but the Price Reduction has already taken place!!
                if ($yousave_by_rule['rule_execution_type'] == 'cart') {
                  //CREATE NEW SWITCH
                  //TEST TEST TEST
                 // if ($vtprd_setup_options['show_checkout_discount_each_msg'] == 'yes') {
                    if ($msgType == 'html')  {
                        $output .= '<tr  class="vtprd-rule-msg-checkout"  >';
                        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word;" colspan="3">' . stripslashes($yousave_by_rule['rule_short_msg'])  .'</td>';				
                        $output .= '</tr>';                       
                    } else {
                      $output .= "\n" .  stripslashes($yousave_by_rule['rule_short_msg']) . "\r\n"; 
                    }                                 
                    $amt   = $yousave_by_rule['yousave_amt']; 
                    $units = $yousave_by_rule['discount_applies_to_qty'];                  
                    $output .= vtprd_email_discount_detail_line($amt, $units, $msgType, $k); 
              
                 // } 
                }                
              }
            } else {   //show discounts by product
                  $amt = $vtprd_cart->cart_items[$k]->yousave_total_amt; 
                  $units = $vtprd_cart->cart_items[$k]->yousave_total_qty;                  
                  $output .= vtprd_email_discount_detail_line($amt, $units, $msgType, $k);
           }
        }
      }

    return $output;
    
  }

    
	function vtprd_email_discount_detail_line($amt, $units, $msgType, $k) {  
    global $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
      $output = ''; //v1.0.7.9
          
      //v1.0.7.4 begin      
      $amt = vtprd_format_amt_and_adjust_for_taxes($amt, $k);  //has both formatted amount and suffix, prn
      // $amt = vtprd_format_money_element($amt); //mwn
      //v1.0.7.4 end 
         
    if ($msgType == 'html')  {
      $output .= '<tr>';

      if (sizeof($vtprd_cart->cart_items[$k]->variation_array) > 0   ) {
        $output .= '<td  class="vtprd-product-name-email" style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word"><span class="vtprd-product-name-span">' . $vtprd_cart->cart_items[$k]->parent_product_name .'</span>';
        $output .= '<small>';
        //v1.0.7.9 begin
        if ($vtprd_cart->cart_items[$k]->variation_name_html > '')  {
          $variation_name_html = $vtprd_cart->cart_items[$k]->variation_name_html;
          //remove wrapping paragraph on variation name...
          $variation_name_html = str_replace( '<p>',  '', $variation_name_html  );
          $variation_name_html = str_replace( '</p>', '', $variation_name_html  );                                                                               
           $output .= $variation_name_html;
        } else {
        //v1.0.7.9 end        
           // $output .= '<dl class="variation">';
          foreach($vtprd_cart->cart_items[$k]->variation_array as $key => $value) {          
            //v1.0.7.8  begin                
            $name  = str_replace( 'attribute_pa_', '', $key  );  //post v 2.1
            $name  = str_replace( 'attribute_', '', $key  );     //post v 2.1   for on-the-fly variations
            $value = str_replace( 'attribute_', '', $value  );   //post v 2.1   for on-the-fly variations
            $name  = str_replace( 'pa_', '', $name  );   //pre v 2.1
            $current_version =  WOOCOMMERCE_VERSION;
            if( (version_compare(strval('2.1.0'), strval($current_version), '>') == 1) ) {   //'==1' = 2nd value is lower     
              //pre 2.1
              $name  = sanitize_title($name);
              $value = sanitize_title($value);
              $name  = ucwords($name);  
              $output .= '<br class="variation-'.$name.'">'. $name . ': ' .$value ;  //added class
            } else {
              //post 2.1
              $name2 = sanitize_text_field( $name );
              $output .= '<br class="variation-'.sanitize_html_class( $name2 ).'">'. wp_kses_post( $name ) . ': ' .wp_kses_post( wpautop( $value ));  //added class
            }
            //v1.0.7.8  end                       
          }
          //$output .= '</dl></small>'; 
        }  //v1.0.7.9 
        $output .= '</small>';      			
        $output .= '</td>';     
      } else {
        $output .= '<td  class="vtprd-product-name-email" style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word">' . $vtprd_cart->cart_items[$k]->product_name .'</td>';
      }
			
      $output .= '<td class="vtprd-quantity-email" style="text-align:left;vertical-align:middle;border:1px solid #eee">' . $units .'</td>';			
      $output .= '<td class="vtprd-amount-email"  style="text-align:left;vertical-align:middle;border:1px solid #eee">' . $vtprd_setup_options['checkout_credit_detail_label'] . ' ' .$amt .'</td>';		
      $output .= '</tr>';        
    } else {
      if ($vtprd_setup_options['show_checkout_discount_titles_above_details'] == 'yes') {  //v1.0.9.0 
        $output .= "\n" . __( 'Product: ', 'vtprd' ); 
        $output .= "\n" . $vtprd_cart->cart_items[$k]->product_name;
        $output .= "\n" . __( ' Discount Units: ', 'vtprd' );
        $output .= "\n" . $units ;
        
        
        //v1.0.9.0 added new title
        if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  {   
          $output .= "\n" . __( ' Discount Amount: ', 'vtprd' );
        } else {
          $output .= "\n" . __('  Discount', 'vtprd') .'<br>'.  __('( in Unit Price )', 'vtprd');
        } 
        
        $output .= "\n" . $amt;
        $output .= "\r\n";
      }
    }
    
    return  $output;  
 }
   
	function vtprd_email_cart_purchases_subtotal($msgType) {
    global $vtprd_cart, $woocommerce, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;   

    $output = ''; //v1.0.7.9
    //$subTotal = $vtprd_cart->cart_original_total_amt;    //show as a credit
    $amt = vtprd_get_Woo_cartSubtotal(); 
    
    if ($msgType == 'html')  {
      $output .= '<tr>';
        $output .= '<td  class="vtprd-subtotal-email" colspan="2">' . $vtprd_setup_options['checkout_credit_subtotal_title'] .'</td>';
        $output .= '<td>' . $amt .'</td>';
      $output .= '</tr>';   
    } else {
      $output .= $vtprd_setup_options['checkout_credit_subtotal_title'];
      $output .= '  ';
      $output .= $amt;
      $output .= "\r\n";        
    }
    return $output;  
  }
 
     
	function vtprd_email_cart_discount_total($msgType) {
    global $vtprd_cart, $vtprd_rules_set, $vtprd_setup_options;

    $output = ''; //v1.0.7.9
      
    //v1.0.8.9a begin               
    // pick up included, excluded or yousave_cart_total_amt Total       
    $amt = vtprd_load_cart_total_incl_excl();
    //$amt = vtprd_format_money_element($vtprd_cart->yousave_cart_total_amt);
    $amt = vtprd_format_money_element($amt);
    $amt .= vtprd_maybe_load_incl_excl_vat_lit(); 
    //v1.0.8.9a end  

    if ($msgType == 'html')  {
      $output .= '<tr>';
        $output .= '<td colspan="2">' . $vtprd_setup_options['checkout_credit_total_title'] .'</td>';
        $output .= '<td>' . $vtprd_setup_options['checkout_credit_total_label'] . ' ' .$amt .'</td>';
      $output .= '</tr>';   
    } else {      
      $output .= $vtprd_setup_options['checkout_credit_total_title'];          //Discount Total
      $output .= $amt ;
      $output .= "\r\n";        
    }
    
    return $output;  
    
  }
   
	
  //***************************************
  // Subtotal with Discount:  (email)
  //***************************************
  function vtprd_email_new_cart_checkout_subtotal_line($msgType) {
    global $vtprd_cart, $woocommerce, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;   

      $output = ''; //v1.0.7.9
   
      // for wpec $vtprd_cart->cart_original_total_amt is not accurate - use wpec's own routine
      //$subTotal = $vtprd_cart->cart_original_total_amt - $vtprd_cart->yousave_cart_total_amt;    //show as a credit
      
     
      //v1.0.8.9a begin  
      //$subTotal  = $woocommerce->cart->subtotal;
       
      if ( $woocommerce->cart->tax_display_cart == 'excl' ) {
    		$subtotal = $woocommerce->cart->subtotal_ex_tax ;
    	} else {
    		$subtotal = $woocommerce->cart->subtotal;
      }  
      //v1.0.8.9a end   
            
      //v1.0.8.9a no longer needed  vtprd_load_cart_total_incl_excl(); //v1.0.7.4 
    

      //*****************************
      //No longer used - $subTotal -= $vtprd_cart->yousave_cart_total_amt;
      //*****************************
      //v1.0.8.9a begin               
      // pick up included, excluded or yousave_cart_total_amt Total       
      //$subTotal -= $vtprd_cart->cart_discount_subtotal;  /may or may not contain the coupon amount, depending on passed value calling function
      $subtotal -= vtprd_load_cart_total_incl_excl();
      $amt = vtprd_format_money_element($subtotal);
      //v1.0.8.9a end              
 
      $amt .= vtprd_maybe_load_incl_excl_vat_lit();  //v1.0.7.4
      
      if ($msgType == 'html')  {
        $output .= '<tr>';
          $output .= '<td colspan="2">' . $vtprd_setup_options['checkout_new_subtotal_label'] .'</td>';
          $output .= '<td>' . $amt .'</td>';
        $output .= '</tr>';
      } else {
        $output .= $vtprd_setup_options['checkout_new_subtotal_label'];
        $output .= '  '; 
        $output .= $amt;
        $output .= "\r\n";        
      }
    
    return $output; 
  }  

   

  /* ************************************************
  **   Assemble all of the cart discount row info FOR email/transaction results messaging  
  *        $msgType = 'html' or 'plainText'            
  *************************************************** */
	function vtprd_thankyou_cart_reporting() {
    global $vtprd_cart, $vtprd_cart_item, $vtprd_rules_set, $vtprd_info, $vtprd_setup_options, $woocommerce;
    $output = ''; //v1.0.7.9
   	
    $output .=  '<h2 id="vtprd-thankyou-title">' . __('Cart Discount Details', 'vtprd') .'</h2>'; //v1.0.9.5  closing '</h2>' fixed
     	
    $output .= '<table class="shop_table order_details vtprd-thankyou-table">';
    $output .= '<thead>';
    $output .= '<tr>';
    $output .= '<th class="product-name">' . __('Discount Product', 'vtprd') .'</th>';
    
    //v1.0.9.0 added new title
    if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  {   
      $output .= '<th class="product-name">' . __('Discount Amount', 'vtprd') .'</th>';	
    } else {
      $output .= '<th class="product-name">' . __('Discount', 'vtprd') .'<br>'.  __('( in Unit Price )', 'vtprd') .'</th>';	
    }
    
    $output .= '</tr>';    
    $output .= '</thead>';
    
    vtprd_load_cart_total_incl_excl(); //v1.0.7.4 
    
    if (($vtprd_setup_options['show_checkout_discount_total_line'] == 'yes') || 
        ($vtprd_setup_options['checkout_new_subtotal_line']        == 'yes')) {
        $output .= '<tfoot>';
        if ($vtprd_setup_options['show_checkout_discount_total_line'] == 'yes') {

            //v1.0.8.9a begin               
            // pick up included, excluded or yousave_cart_total_amt Total       
            $amt = vtprd_load_cart_total_incl_excl();
            //$amt = vtprd_format_money_element($vtprd_cart->yousave_cart_total_amt);
            $amt = vtprd_format_money_element($amt);
            //v1.0.8.9a end            
            
            $amt .= vtprd_maybe_load_incl_excl_vat_lit();  //v1.0.7.4  
            $output .= '<tr class="checkout_credit_total">';
            $output .= '<th scope="row">'. $vtprd_setup_options['checkout_credit_total_title'] .'</th>';						
            $output .= '<td><span class="amount">'  . $vtprd_setup_options['checkout_credit_total_label'] .$amt .'</span></td>';		
            $output .= '</tr>';
        }
        /*
        if ($vtprd_setup_options['checkout_new_subtotal_line'] == 'yes') {
            //can't use the regular routine ($subtotal = vtprd_get_Woo_cartSubtotal(); ), as it returns a formatted result
           if ( $woocommerce->tax_display_cart == 'excl' ) {
        			$subtotal = $woocommerce->cart->subtotal_ex_tax ;
        		} else {
        			$subtotal = $woocommerce->cart->subtotal;
            }   
            $subtotal -= $vtprd_cart->yousave_cart_total_amt;
            $amt = vtprd_format_money_element($subtotal);              
            $output .= '<tr class="checkout_new_subtotal">';
            $output .= '<th scope="row">'. $vtprd_setup_options['checkout_new_subtotal_label'] .'</th>';						
            $output .= '<td><span class="amount">'  . $vtprd_setup_options['checkout_credit_detail_label'] .$amt .'</span></td>';		
            $output .= '</tr>';
        }  
        */      
        $output .= '</tfoot>';   
    }   
 
    $output .= '<tbody>';
 
    //get the discount details    
    $output .= vtprd_thankyou_cart_discount_rows($msgType);
  
    $output .= '</tbody>';
    $output .= '</table>';
           
    return $output;
    
  }
    
  /* ************************************************
  **   Assemble all of the cart discount row info              
  *************************************************** */
	function vtprd_thankyou_cart_discount_rows($msgType) {
    global $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
       
      $output = ''; //v1.0.7.9

      $sizeof_cart_items = sizeof($vtprd_cart->cart_items);
      for($k=0; $k < $sizeof_cart_items; $k++) {  
       	if ( $vtprd_cart->cart_items[$k]->yousave_total_amt > 0) {            
            if ($vtprd_setup_options['show_checkout_discount_details_grouped_by_what']   == 'rule') {
              //these rows are indexed by ruleID, so a foreach is needed...
              foreach($vtprd_cart->cart_items[$k]->yousave_by_rule_info as $key => $yousave_by_rule) {
              
                //display info is tabulated for cumulative rule processing, but the Price Reduction has already taken place!!
                if ($yousave_by_rule['rule_execution_type'] == 'cart') {
                  //CREATE NEW SWITCH
                  //TEST TEST TEST
                 // if ($vtprd_setup_options['show_checkout_discount_each_msg'] == 'yes') {
                      $output .= '<tr class = "order_table_item">';
                      $output .= '<td  class="product-name">' . stripslashes($yousave_by_rule['rule_short_msg'])  .'</td>';
                      //td with blank needed to complete the border line in the finished product
                      $output .= '<td  class="product-name">&nbsp;</td>';				
                      $output .= '</tr>';                       
                 // }                                 
                    $amt   = $yousave_by_rule['yousave_amt']; 
                    $units = $yousave_by_rule['discount_applies_to_qty'];                  
                    $output .= vtprd_thankyou_discount_detail_line($amt, $units, $msgType, $k); 
              
                }                
              }
            } else {   //show discounts by product
                  $amt = $vtprd_cart->cart_items[$k]->yousave_total_amt; 
                  $units = $vtprd_cart->cart_items[$k]->yousave_total_qty;                  
                  $output .= vtprd_thankyou_discount_detail_line($amt, $units, $msgType, $k);
           }
        }
      }

    return $output;
    
  }
     
	function vtprd_thankyou_discount_detail_line($amt, $units, $msgType, $k) {  
    global $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
      $output = ''; //v1.0.7.9
    
    //v1.0.7.4 begin      
    $amt = vtprd_format_amt_and_adjust_for_taxes($amt, $k);  //has both formatted amount and suffix, prn
    // $amt = vtprd_format_money_element($amt); //mwn
    //v1.0.7.4 end
    
    $output .= '<tr class = "order_table_item">';
    /*
    $output .= '<td  class="product-name">' . $vtprd_cart->cart_items[$k]->product_name ;
    $output .= '<strong class="product-quantity"> &times; ' . $units  .'</strong>';				
    $output .= '</td>';
    */

    if (sizeof($vtprd_cart->cart_items[$k]->variation_array) > 0   ) {
      $output .= '<td  class="product-name vtprd-product-name" ><span class="vtprd-product-name-span">' . $vtprd_cart->cart_items[$k]->parent_product_name .'</span>';
      $output .= '<strong class="product-quantity"> &times; ' . $units  .'</strong>';	
           
      //v1.0.7.9 begin
      if ($vtprd_cart->cart_items[$k]->variation_name_html > '')  {
        $output .= $vtprd_cart->cart_items[$k]->variation_name_html;
      } else {
        $output .= '<dl class="variation">';
        foreach($vtprd_cart->cart_items[$k]->variation_array as $key => $value) {          
          //v1.0.7.8  begin              
          $name  = str_replace( 'attribute_pa_', '', $key  );  //post v 2.1
          $name  = str_replace( 'attribute_', '', $key  );     //post v 2.1   for on-the-fly variations
          $value = str_replace( 'attribute_', '', $value  );   //post v 2.1   for on-the-fly variations
          $name  = str_replace( 'pa_', '', $name  );   //pre v 2.1
          $name  = ucwords($name);
          $current_version =  WOOCOMMERCE_VERSION;
          if( (version_compare(strval('2.1.0'), strval($current_version), '>') == 1) ) {   //'==1' = 2nd value is lower     
            //pre 2.1
            $name  = sanitize_title($name);
            $value = sanitize_title($value);            
            $output .= '<dt class="variation-'.$name.'">'. $name . ': </dt>';  //added class
            $output .= '<dd class="variation-'.$name.'">'. $value .'</dd>';    //added class
          } else {
            //post 2.1
            //$name2 = sanitize_text_field( $name );
            $name2 = sanitize_title( $name );
            $name2_san = sanitize_html_class( $name2 );
            $output .= '<dt class="variation-'. $name2_san.'">'. wp_kses_post( $name ) . ': </dt>';  //added class
            $output .= '<dd class="variation-'. $name2_san.'">'. wp_kses_post( wpautop( $value )) .'</dd>';    //added class
          }
          //v1.0.7.8  end              
        }
        $output .= '</dl>';
      }
      //v1.0.7.9 end
      $output .= '</td>';     
    } else {
      $output .= '<td  class="product-name" >' . $vtprd_cart->cart_items[$k]->product_name ;
			$output .= '<strong class="product-quantity"> &times; ' . $units  .'</strong>';	
      $output .= '</td>';
    }

    
    $output .= '<td  class="product-total">';
    $output .= '<span class="amount">' . $vtprd_setup_options['checkout_credit_detail_label'] .$amt .'</span>';				
    $output .= '</td>';

    
                          
    $output .= '</tr>'; 
    
    return  $output;  
 }
   
	function vtprd_thankyou_cart_purchases_subtotal($msgType) {
    global $vtprd_cart, $woocommerce, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;   

    $output = ''; //v1.0.7.9
    //$subTotal = $vtprd_cart->cart_original_total_amt;    //show as a credit
    $amt = vtprd_get_Woo_cartSubtotal(); 
    
    if ($msgType == 'html')  {
      $output .= '<tr>';
        $output .= '<td colspan="2">' . $vtprd_setup_options['checkout_credit_subtotal_title'] .'</td>';
        $output .= '<td>' . $amt .'</td>';
      $output .= '</tr>';   
    } else {
      $output .= $vtprd_setup_options['checkout_credit_subtotal_title'];
      $output .= '  ';
      $output .= $amt;
      $output .= "\r\n";        
    }
    return $output;  
  }


  

  /* ************************************************
  **   Assemble all of the cart discount row info FOR email/transaction results messaging  
  *        $msgType = 'html' or 'plainText'            
  *************************************************** */
	function vtprd_checkout_cart_reporting($msgType) {      //v1.0.8.0
    global $vtprd_cart, $vtprd_cart_item, $vtprd_rules_set, $vtprd_info, $vtprd_setup_options, $woocommerce;
    $output = ''; //v1.0.7.9        
   	if (($vtprd_setup_options['show_checkout_discount_detail_lines'] == 'yes') ||  
        ($vtprd_setup_options['show_checkout_discount_total_line']   == 'yes') ||  
        ($vtprd_setup_options['checkout_new_subtotal_line']          == 'yes') ) {	
            $output .= '<table class="shop_table cart vtprd_shop_table" cellspacing="0">';
            $output .= '<thead>';
            $output .= '<tr class="checkout_discount_headings">';
            $output .= '<th  class="product-name" >' . __('Discount Product', 'vtprd') .'</th>';
            $output .= '<th  class="product-quantity">' . __('Quantity', 'vtprd') .'</th>';
            	            
            //v1.0.9.0 added new title
            if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  {   
              $output .= '<th  class="product-subtotal" >' . __('Discount Amount', 'vtprd') .'</th>';
            } else {
              $output .= '<th  class="product-subtotal" >' . __('Discount', 'vtprd') .'<br>'.  __('( in Unit Price )', 'vtprd') .'</th>';
            }
            
            $output .= '</tr>';    
            $output .= '</thead>';
    } 
   
    vtprd_load_cart_total_incl_excl(); //v1.0.7.4 
    
    if (($vtprd_setup_options['show_checkout_discount_total_line'] == 'yes') || 
        ($vtprd_setup_options['checkout_new_subtotal_line']        == 'yes')) { 
        $output .= '<tfoot>';
         if ($vtprd_setup_options['show_checkout_discount_total_line'] == 'yes') {
      
            //v1.0.8.9a begin               
            // pick up included, excluded or yousave_cart_total_amt Total       
            $amt = vtprd_load_cart_total_incl_excl();
            //$amt = vtprd_format_money_element($vtprd_cart->yousave_cart_total_amt);
            $amt = vtprd_format_money_element($amt);
            //v1.0.8.9a end
      
            $amt .= vtprd_maybe_load_incl_excl_vat_lit();  //v1.0.7.4  
            $output .= '<tr class="checkout_discount_total_line">';
            $output .= '<th scope="row" colspan="2">'. $vtprd_setup_options['checkout_credit_total_title'] .'</th>';						
            $output .= '<td ><span class="amount">'  . $vtprd_setup_options['checkout_credit_total_label'] .$amt .'</span></td>';		
            $output .= '</tr>';
        }
         
        if ($vtprd_setup_options['checkout_new_subtotal_line'] == 'yes') {
            //can't use the regular routine ($subtotal = vtprd_get_Woo_cartSubtotal(); ), as it returns a formatted result
           if ( $woocommerce->cart->tax_display_cart == 'excl' ) {
        			$subtotal = $woocommerce->cart->subtotal_ex_tax ;
        		} else {
        			$subtotal = $woocommerce->cart->subtotal;
            }   

            //v1.0.8.9a begin               
            // pick up included, excluded or yousave_cart_total_amt Total       
            //$subtotal -= $vtprd_cart->yousave_cart_total_amt;
            $subtotal -= vtprd_load_cart_total_incl_excl();
            $amt = vtprd_format_money_element($subtotal);
            //v1.0.8.9a end  

            $amt .= vtprd_maybe_load_incl_excl_vat_lit();  //v1.0.7.4
                               
            $output .= '<tr class="checkout_new_subtotal">';
            $output .= '<th scope="row" colspan="2">'. $vtprd_setup_options['checkout_new_subtotal_label'] .'</th>';						
            $output .= '<td ><span class="amount">'  . $amt .'</span></td>';		
            $output .= '</tr>'; 
        }        
        $output .= '</tfoot>';   
    }   
 
    $output .= '<tbody>';

    //new    
    if ($vtprd_setup_options['show_checkout_purchases_subtotal'] == 'beforeDiscounts') {
      $amt = vtprd_get_Woo_cartSubtotal();
      $output .= '<tr class="checkout_purchases_subtotal">';
      $output .= '<th scope="row" colspan="2">'. $vtprd_setup_options['checkout_credit_subtotal_title'] .'</th>';						
      $output .= '<td ><span class="amount">'   .$amt .'</span></td>';		
      $output .= '</tr>';
    }
 
    if ($vtprd_setup_options['show_checkout_discount_detail_lines'] == 'yes') {
      //get the discount details    
      $output .= vtprd_checkout_cart_discount_rows($msgType);
     }
     
    if ($vtprd_setup_options['show_checkout_purchases_subtotal'] == 'withDiscounts') {
      $amt = vtprd_get_Woo_cartSubtotal();
      $output .= '<tr class="checkout_purchases_subtotal">';
      $output .= '<th scope="row" colspan="2">'. $vtprd_setup_options['checkout_credit_subtotal_title'] .'</th>';						
      $output .= '<td ><span class="amount">'   .$amt .'</span></td>';		
      $output .= '</tr>';
    }


    $output .= '</tbody>';
    $output .= '</table>';
    
    echo $output;
           
    return;
    
  }
    
  /* ************************************************
  **   Assemble all of the cart discount row info              
  *************************************************** */
	function vtprd_checkout_cart_discount_rows($msgType) {
    global $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
       
      $output = ''; //v1.0.7.9

      $sizeof_cart_items = sizeof($vtprd_cart->cart_items);
      for($k=0; $k < $sizeof_cart_items; $k++) {  
       	if ( $vtprd_cart->cart_items[$k]->yousave_total_amt > 0) {            
            if ($vtprd_setup_options['show_checkout_discount_details_grouped_by_what']   == 'rule') {
              //these rows are indexed by ruleID, so a foreach is needed...
              foreach($vtprd_cart->cart_items[$k]->yousave_by_rule_info as $key => $yousave_by_rule) {
              
                //display info is tabulated for cumulative rule processing, but the Price Reduction has already taken place!!
                if ($yousave_by_rule['rule_execution_type'] == 'cart') {
                  //CREATE NEW SWITCH
                  //TEST TEST TEST
                 // if ($vtprd_setup_options['show_checkout_discount_each_msg'] == 'yes') {
                      $output .= '<tr class = "order_table_item">';
                      $output .= '<td  class="product-name vtprd-rule_msg" colspan="3">' . stripslashes($yousave_by_rule['rule_short_msg'])  .'</td>';			
                      $output .= '</tr>';                       
                 // }                                 
                    $amt   = $yousave_by_rule['yousave_amt']; 
                    $units = $yousave_by_rule['discount_applies_to_qty'];                  
                    $output .= vtprd_checkout_discount_detail_line($amt, $units, $msgType, $k); 
              
                }                
              }
            } else {   //show discounts by product
                  $amt = $vtprd_cart->cart_items[$k]->yousave_total_amt; 
                  $units = $vtprd_cart->cart_items[$k]->yousave_total_qty;                  
                  $output .= vtprd_checkout_discount_detail_line($amt, $units, $msgType, $k);
           }
           
           //v1.0.9.3 begin
           if ( ($vtprd_setup_options['discount_taken_where'] == 'discountUnitPrice') &&
                ($vtprd_setup_options['show_unit_price_cart_discount_computation'] == 'yes') )  {
              $computation_summary = $vtprd_cart->cart_items[$k]->computation_summary; 
              $output .= '<tr class = "order_table_item">';
              $output .= '<td  class="unit-price-computation" colspan="3">' . $computation_summary  .'</td>';			
              $output .= '</tr>';             
           }
           //v1.0.9.3 end
        }
      }

    return $output;
    
  }
     
	function vtprd_checkout_discount_detail_line($amt, $units, $msgType, $k) {  
    global $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
      $output = ''; //v1.0.7.9

      //v1.0.7.4 begin      
      $amt = vtprd_format_amt_and_adjust_for_taxes($amt, $k);  //has both formatted amount and suffix, prn
      // $amt = vtprd_format_money_element($amt);
      //v1.0.7.4 end
    
    $output .= '<tr class = "order_table_item">';

    if (sizeof($vtprd_cart->cart_items[$k]->variation_array) > 0   ) {
      $output .= '<td  class="product-name vtprd-product-name" ><span class="vtprd-product-name-span">' . $vtprd_cart->cart_items[$k]->parent_product_name .'</span>';
      
      //v1.0.7.9 begin
      if ($vtprd_cart->cart_items[$k]->variation_name_html > '')  {
        $output .= $vtprd_cart->cart_items[$k]->variation_name_html;
      } else {
        $output .= '<dl class="variation">';
        foreach($vtprd_cart->cart_items[$k]->variation_array as $key => $value) {          
          //v1.0.7.8  begin               
          $name  = str_replace( 'attribute_pa_', '', $key  );  //post v 2.1
          $name  = str_replace( 'attribute_', '', $key  );     //post v 2.1   for on-the-fly variations
          $value = str_replace( 'attribute_', '', $value  );   //post v 2.1   for on-the-fly variations
          $name  = str_replace( 'pa_', '', $name  );   //pre v 2.1
          $name  = ucwords($name);
          $current_version =  WOOCOMMERCE_VERSION;
          if( (version_compare(strval('2.1.0'), strval($current_version), '>') == 1) ) {   //'==1' = 2nd value is lower     
            //pre 2.1
            $name  = sanitize_title($name);
            $value = sanitize_title($value);            
            $output .= '<dt class="variation-'.$name.'">'. $name . ': </dt>';  //added class
            $output .= '<dd class="variation-'.$name.'">'. $value .'</dd>';    //added class
          } else {
            //post 2.1
            //$name2 = sanitize_text_field( $name );
            $name2 = sanitize_title( $name );
            $name2_san = sanitize_html_class( $name2 );
            $output .= '<dt class="variation-'. $name2_san.'">'. wp_kses_post( $name ) . ': </dt>';  //added class
            $output .= '<dd class="variation-'. $name2_san.'">'. wp_kses_post( wpautop( $value )) .'</dd>';    //added class
          }
          //v1.0.7.8  end              
        }
        $output .= '</dl>';
      }
      //v1.0.7.9 end
 
            			
      $output .= '</td>';
      //$output .= '<strong class="product-quantity"> &times; ' . $units  .'</strong>';	     
    } else {
      $output .= '<td  class="product-name" >' . $vtprd_cart->cart_items[$k]->product_name ;
     // $output .= '<strong class="product-quantity"> &times; ' . $units  .'</strong>';				
      $output .= '</td>';
    }

    $output .= '<td  class="product-quantity" style="text-align:middle;">' . $units .'</td>';
    
    $output .= '<td  class="product-total">';
    $output .= '<span class="amount">' . $vtprd_setup_options['checkout_credit_detail_label'] .$amt .'</span>';				
    $output .= '</td>';
                          
    $output .= '</tr>'; 
    
    return  $output;  
 }
   
	function vtprd_checkout_cart_purchases_subtotal($msgType) {
    global $vtprd_cart, $woocommerce, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;   

    $output = ''; //v1.0.7.9
    //$subTotal = $vtprd_cart->cart_original_total_amt;    //show as a credit
    $amt = vtprd_get_Woo_cartSubtotal(); 
    
    if ($msgType == 'html')  {
      $output .= '<tr>';
        $output .= '<td colspan="2">' . $vtprd_setup_options['checkout_credit_subtotal_title'] .'</td>';
        $output .= '<td>' . $amt .'</td>';
      $output .= '</tr>';   
    } else {
      $output .= $vtprd_setup_options['checkout_credit_subtotal_title'];
      $output .= '  ';
      $output .= $amt;
      $output .= "\r\n";        
    }
    return $output;  
  }


  
  
  function vtprd_numberOfDecimals($value) {
      if ((int)$value == $value) {
          return 0;
      }
      else if (! is_numeric($value)) {
          // throw new Exception('numberOfDecimals: ' . $value . ' is not a number!');
          return false;
      }
  
      return strlen($value) - strrpos($value, '.') - 1;  
  }

   function vtprd_print_rule_full_msg($i) { 
    global $vtprd_rules_set;
    $output  = '<span  class="vtprd-full-messages" id="vtprd-category-deal-msg' . $vtprd_rules_set[$i]->post_id . '">';
    $output .= stripslashes($vtprd_rules_set[$i]->discount_product_full_msg);
    $output .= '</span>'; 
    return $output;    
   }  


  // ****************  
  // Date Validity Rule Test
  // ****************             
   function vtprd_rule_date_validity_test($i) {  
       global $vtprd_rules_set;

       switch( $vtprd_rules_set[$i]->rule_on_off_sw_select ) {
          case 'on':  //continue, use scheduling dates
            break;
          case 'off': //rule is always off!!!
              return false;
            break;
          case 'onForever': //rule is always on!!
              return true;
            break;
        }

       $today = date("Y-m-d");
       
       for($t=0; $t < sizeof($vtprd_rules_set[$i]->periodicByDateRange); $t++) {
          if ( ($today >= $vtprd_rules_set[$i]->periodicByDateRange[$t]['rangeBeginDate']) &&
               ($today <= $vtprd_rules_set[$i]->periodicByDateRange[$t]['rangeEndDate']) ) {
             return true;  
          }
       } 
        
       return false; //marks test as valid
   }   


  /* ************************************************
  *    PRODUCT META INCLUDE/EXCLUDE RULE ID LISTS
  *       Meta box added to PRODUCT in rules-ui.php 
  *             updated in pricing-deals.php    
  * ************************************************               
  **   Products can be individually added to two lists:
  *       Include only list - **includes** the product in a rule population 
  *         *only" if:
  *           (1) The product already participates in the rule
  *           (2) The product is in the include only rule list 
*       Exclude list - excludes the product in a rule population 
  *         *only" if:
  *           (1) The product already participates in the rule
  *           (2) The product is in the exclude rule list        
  *************************************************** */  

  //depending on the switch setting, this will be either include or exclude - but from the function's
  //  point of view, it doesn't matter...
  function vtprd_fill_include_exclude_lists($checked_list = NULL) { 
      global $wpdb, $post, $vtprd_setup_options;

      $varsql = "SELECT posts.`id`
            			FROM `".$wpdb->posts."` AS posts			
            			WHERE posts.`post_status` = 'publish' AND posts.`post_type`= 'vtprd-rule'";                    
    	$rule_id_list = $wpdb->get_col($varsql);

      //Include or Exclude list
      foreach ($rule_id_list as $rule_id) {     //($rule_ids as $rule_id => $info)
          $rule_for_title = get_post($rule_id);  //v1.0.8.9 changed field name here and below...
          $output  = '<li id="inOrEx-li-' .$rule_id. '">' ;
          $output  .= '<label class="selectit inOrEx-list-checkbox-label">' ;
          $output  .= '<input id="inOrEx-input-' .$rule_id. '" class="inOrEx-list-checkbox-class" ';
          $output  .= 'type="checkbox" name="includeOrExclude-checked_list[]" ';
          $output  .= 'value="'.$rule_id.'" ';
          $check_found = 'no';
          if ($checked_list) {
              if (in_array($rule_id, $checked_list)) {   //if variation is in previously checked_list   
                 $output  .= 'checked="checked"';
                 $check_found = 'yes';
              }                
          }
          $output  .= '>'; //end input statement
          $output  .= '&nbsp;' . $rule_for_title->post_title; //v1.0.8.9 
          $output  .= '</label>';            
          $output  .= '</li>';
          echo  $output ;
       }
       
      return;   
  }
   
  function vtprd_set_selected_timezone() {
   global $vtprd_setup_options;
    //set server timezone to Store local for date processing
    switch( $vtprd_setup_options['use_this_timeZone'] ) {
      case 'none':
      case 'keep':
        break;
      default:
          $useThisTimeZone = $vtprd_setup_options['use_this_timeZone'];
          date_default_timezone_set($useThisTimeZone);
        break;
    }
  }

	//this routine only gets previouosly-stored session info
  function vtprd_maybe_get_product_session_info($product_id) {
    global $vtprd_info;    
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }  
    // ********************************************************
    //this routine is also called during cart processing.             
    //  if so, get the session info if there, MOVE it to VTPRD_INFO and exit
    // ********************************************************
    if(isset($_SESSION['vtprd_product_session_info_'.$product_id])) {      
      $vtprd_info['product_session_info'] = $_SESSION['vtprd_product_session_info_'.$product_id];
    } else {
      //v1.0.9.0 begin
      // we'll want to pick up the Catalog discount, if any...
      global $vtprd_info;  
      if ($vtprd_info['ruleset_has_a_display_rule'] == 'yes') {
          vtprd_get_product_session_info($product_id);
      } else {
          $vtprd_info['product_session_info'] = array();
      }
      //v1.0.9.0 end
    }

           
  }  
          
   /* ************************************************
  **  get display session info and MOVE to $vtprd_info['product_session_info']
  *  First time go to the DB.
  *  2nd thru nth go to session variable...
  *    If the ID is a Variation (only comes realtime from AJAX), the recompute is run to refigure price.
  *    
  * //$cart_processing_sw: 'yes' => only get the session info
  *                        'no'  => only get the session info
  *             
  *************************************************** */
	//PRICE only comes from  parent-cart-validation function vtprd_show_product_catalog_price
  // $product_info comes from catalog calls...
  function vtprd_get_product_session_info($product_id, $price=null){   
    global $post, $vtprd_info;
//echo ' cv001a product_id= ' . $product_id. ' price= ' .$price. '<br>' ; //mwnt    
    //store product-specific session info
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }  
    

    //if already in the session variable... => this routine can be called multiple times in displaying a single catalog price.  check first if already done.
    if(isset($_SESSION['vtprd_product_session_info_'.$product_id])) {
       $vtprd_info['product_session_info'] = $_SESSION['vtprd_product_session_info_'.$product_id];   
      //will be a problem in Ajax...
      $current_time_in_seconds = time();          
      $user_role = vtprd_get_current_user_role();      
      
      //*****************************
      //v1.0.8.4 timestamp  begin
      $vtprd_ruleset_timestamp = get_option( 'vtprd_ruleset_timestamp' );  
      if (!$vtprd_ruleset_timestamp) {
        $vtprd_ruleset_timestamp = 0; 
      }     
      //v1.0.8.4 timestamp  end 
      //*****************************      

      if ( ( ($current_time_in_seconds - $vtprd_info['product_session_info']['session_timestamp_in_seconds']) > '3600' ) ||     //session data older than 60 minutes
           (  $user_role != $vtprd_info['product_session_info']['user_role']) ||                                      //user role CHANGED via user login
           (  $vtprd_ruleset_timestamp > $vtprd_info['product_session_info']['session_timestamp_in_seconds'] ) ) {   //v1.0.8.4 timestamp - GET *more recent* ADMIN updates to ruleset NOW    
//error_log( print_r(  'FROM 001', true ) );        
        vtprd_apply_rules_to_single_product($product_id, $price);
        //reset user role info, in case it changed
        $vtprd_info['product_session_info']['user_role'] = $user_role;
      }         
    } else { 
       //First time obtaining the info, also moves the data to $vtprd_info       
//error_log( print_r(  'FROM 002', true ) );
      vtprd_apply_rules_to_single_product($product_id, $price);
      // vtprd_apply_rules_to_vargroup_or_single($product_id, $price);        
    } 

 /*   
    //If the correct discount already computed, then nothing further needed...
    if ($vtprd_info['product_session_info']['product_unit_price'] == $price) {
      return;
    }

    // *****************
    //if this is the 2nd thru nth call, $price value passed in may be different (if product has a product sale price), reapply percent in all cases...
    // *****************
    if ($price > 0) {
      vtprd_recompute_discount_price($product_id, $price);
    }        
//echo ' price after refigure= ' . $vtprd_info['product_session_info']['product_discount_price']. '<br>';
 */
    return;
  }
  
   
  //if discount price already in session variable, get in during the get_price() woo function
  function vtprd_maybe_get_discount_catalog_session_price($product_id){   
    global $post, $vtprd_info;
//echo ' cv001a product_id= ' . $product_id. ' price= ' .$price. '<br>' ; //mwnt    
    //store product-specific session info
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }  
    
//    echo '$product_id= ' .$product_id. '<br>';
    //if already in the session variable... => this routine can be called multiple times in displaying a single catalog price.  check first if already done.
    if(isset($_SESSION['vtprd_product_session_info_'.$product_id])) {
//      echo 'isset  yes<br>';
       $vtprd_info['product_session_info'] = $_SESSION['vtprd_product_session_info_'.$product_id];   
      //will be a problem in Ajax...
      $current_time_in_seconds = time();          
      $user_role = vtprd_get_current_user_role(); 
       
      //*****************************
      //v1.0.8.4 timestamp  begin
      $vtprd_ruleset_timestamp = get_option( 'vtprd_ruleset_timestamp' );  
      if (!$vtprd_ruleset_timestamp) {
        $vtprd_ruleset_timestamp = 0;
      }     
      //v1.0.8.4 timestamp  end
      //*****************************            
           
      if ( ( ($current_time_in_seconds - $vtprd_info['product_session_info']['session_timestamp_in_seconds']) > '3600' ) ||     //session data older than 60 minutes
           (  $user_role != $vtprd_info['product_session_info']['user_role']) ||                                      //user role CHANGED via user login
           (  $vtprd_ruleset_timestamp > $vtprd_info['product_session_info']['session_timestamp_in_seconds'] ) ) {   //v1.0.8.4 timestamp - GET *more recent* ADMIN updates to ruleset NOW           
//error_log( print_r(  'FROM 003', true ) );        
        vtprd_apply_rules_to_single_product($product_id, $price);
        //reset stored role to current
        $vtprd_info['product_session_info']['user_role'] = $user_role;        
      }        
    }

    return;
  }  


  /* ************************************************
  **   Apply Rules to single product + store as session info
  *************************************************** */
	function vtprd_apply_rules_to_single_product($product_id, $price=null){    
 
    global $post, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule;

    vtprd_set_selected_timezone();
    vtprd_load_vtprd_cart_for_single_product_price($product_id, $price);
//error_log( print_r(  'new Apply_rules in **vtprd_apply_rules_to_single_product', true ) );
 
    $vtprd_info['current_processing_request'] = 'display';
    $vtprd_apply_rules = new VTPRD_Apply_Rules; 

    //also moves the data to $vtprd_info
    vtprd_move_vtprd_single_product_to_session($product_id);
    //return formatted price; if discounted, store price, orig price and you_save in session id
    //  if no discount, formatted DB price returned, no session variable stored
      
    //price result stored in $vtprd_info['product_session_info'] 
    return; 
      
  }
  
  //*********************************
  //NEW GET PRICE FUNCTION
  //FROM 'woocommerce_get_price' => Central behind the scenes pricing  
  //*********************************
  function vtprd_maybe_get_price_single_product($product_id, $price=null){   
    global $post, $vtprd_info;
//echo ' cv001a product_id= ' . $product_id. ' price= ' .$price. '<br>' ; //mwnt    
    //store product-specific session info
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }  

    //if already in the session variable... => this routine can be called multiple times in displaying a single catalog price.  check first if already done.
     
//      echo 'IN THE ROUTINE, $product_id= ' .$product_id.'<br>' ;
//            echo 'SESSION data <pre>'.print_r($_SESSION, true).'</pre>' ; 
      
    if(isset($_SESSION['vtprd_product_session_info_'.$product_id])) {
       $vtprd_info['product_session_info'] = $_SESSION['vtprd_product_session_info_'.$product_id];   
      //will be a problem in Ajax...
      $current_time_in_seconds = time();          
      $user_role = vtprd_get_current_user_role();
      
      //*****************************
      //v1.0.8.4 timestamp  begin
      $vtprd_ruleset_timestamp = get_option( 'vtprd_ruleset_timestamp' );  
      if (!$vtprd_ruleset_timestamp) {
        $vtprd_ruleset_timestamp = 0;
      }     
      //v1.0.8.4 timestamp  end
      //*****************************      
            
      if ( ( ($current_time_in_seconds - $vtprd_info['product_session_info']['session_timestamp_in_seconds']) > '3600' ) ||     //session data older than 60 minutes
           (  $user_role != $vtprd_info['product_session_info']['user_role']) ||                                      //user role CHANGED via user login
           (  $vtprd_ruleset_timestamp > $vtprd_info['product_session_info']['session_timestamp_in_seconds'] ) ) {   //v1.0.8.4 timestamp - GET *more recent* ADMIN updates to ruleset NOW            
//error_log( print_r(  'FROM 004', true ) );        
        vtprd_apply_rules_to_single_product($product_id, $price);
        //reset stored role to current
        $vtprd_info['product_session_info']['user_role'] = $user_role;        
      }        
    } else { 
       //First time obtaining the info, also moves the data to $vtprd_info       
//error_log( print_r(  'FROM 005', true ) );
      vtprd_apply_rules_to_single_product($product_id, $price);
      // vtprd_apply_rules_to_vargroup_or_single($product_id, $price);        
    } 

    //$vtprd_info['product_session_info'] is loaded by this time...
    return;
  }
    
  
  
  
   
  
  /* ************************************************
  **   Post-purchase discount logging
  *************************************************** */	
	function vtprd_save_discount_purchase_log($cart_parent_purchase_log_id) {   
      global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set;  

      if ($vtprd_cart->yousave_cart_total_amt == 0) {
        return;
      }

      //Create PURCHASE LOG row - 1 per cart
      $purchaser_ip_address = $vtprd_info['purchaser_ip_address']; 
      $next_id = '';             //v1.0.8.0 //supply null value for use with autoincrement table key
      $date = date("Y-m-d");     //v1.0.8.0 
 
      $ruleset_object = serialize($vtprd_rules_set); 
      $cart_object    = serialize($vtprd_cart);
      
      $wpdb->query("INSERT INTO `".VTPRD_PURCHASE_LOG."` (`id`,`cart_parent_purchase_log_id`,`purchaser_name`,`purchaser_ip_address`,`purchase_date`,`cart_total_discount_currency`,`ruleset_object`,`cart_object`) 
        VALUES ('{$next_id}','{$cart_parent_purchase_log_id}','{$vtprd_cart->billto_name}','{$purchaser_ip_address}','{$date}','{$vtprd_cart->yousave_cart_total_amt}','{$ruleset_object}','{$cart_object}' );");

      $purchase_log_row_id = $wpdb->get_var("SELECT LAST_INSERT_ID() AS `id` FROM `".VTPRD_PURCHASE_LOG."` LIMIT 1");

      foreach($vtprd_cart->cart_items as $key => $cart_item) {  
        if ($cart_item->yousave_total_amt > 0 ) { 
          //Create PURCHASE LOG PRODUCT row - 1 per product
          $wpdb->query("INSERT INTO `".VTPRD_PURCHASE_LOG_PRODUCT."` (`id`,`purchase_log_row_id`,`product_id`,`product_title`,`cart_parent_purchase_log_id`,
                `product_orig_unit_price`,`product_total_discount_units`,`product_total_discount_currency`,`product_total_discount_percent`) 
            VALUES ('{$next_id}','{$purchase_log_row_id}','{$cart_item->product_id}','{$cart_item->product_name}','{$cart_parent_purchase_log_id}',
                '{$cart_item->db_unit_price}','{$cart_item->yousave_total_qty}','{$cart_item->yousave_total_amt}','{$cart_item->yousave_total_pct}' );");
      
          $purchase_log_product_row_id = $wpdb->get_var("SELECT LAST_INSERT_ID() AS `id` FROM `".VTPRD_PURCHASE_LOG_PRODUCT."` LIMIT 1"); 
          foreach($cart_item->yousave_by_rule_info as $key => $yousave_by_rule) {
            $ruleset_occurrence = $yousave_by_rule['ruleset_occurrence'] ;
            $rule_id = $vtprd_rules_set[$ruleset_occurrence]->post_id;
            $discount_applies_to_qty = $yousave_by_rule['discount_applies_to_qty'];
            $yousave_amt = $yousave_by_rule['yousave_amt'];
            $yousave_pct = $yousave_by_rule['yousave_pct'];        
            //Create PURCHASE LOG PRODUCT RULE row  -  1 per product/rule combo
            $wpdb->query("INSERT INTO `".VTPRD_PURCHASE_LOG_PRODUCT_RULE."` (`id`,`purchase_log_product_row_id`,`product_id`,`rule_id`,`cart_parent_purchase_log_id`,
                  `product_rule_discount_units`,`product_rule_discount_dollars`,`product_rule_discount_percent`) 
              VALUES ('{$next_id}','{$purchase_log_product_row_id}','{$cart_item->product_id}','{$rule_id}','{$cart_parent_purchase_log_id}',
                  '{$discount_applies_to_qty}','{$yousave_amt}','{$yousave_pct}' );");              
          }    
        }
      }
      
           
  }
    
     
  /* ************************************************
  **   Recompute Discount for VARIATION Display rule AJAX  
  *************************************************** */
  function vtprd_recompute_discount_price($variation_id, $price){
      global $vtprd_info;  
      
      $yousave_amt = 0;
      $sizeof_pricing_array = sizeof($vtprd_info['product_session_info']['pricing_by_rule_array']);
      for($y=0; $y < $sizeof_pricing_array; $y++) {
        
        $apply_this = 'yes';
        
        $pricing_rule_applies_to_variations_array = $vtprd_info['product_session_info']['pricing_by_rule_array'][$y]['pricing_rule_applies_to_variations_array'];
        
        if (sizeof($pricing_rule_applies_to_variations_array) > 0) {
           if (in_array($variation_id, $pricing_rule_applies_to_variations_array )) {
             $apply_this = 'yes';
           } else {
             $apply_this = 'no';  //this rule is variation-specific, and the passed id is not!! in the group - skip
           }
        }
        
        if ($apply_this == 'yes') {
          if ($vtprd_info['product_session_info']['pricing_by_rule_array'][$y]['pricing_rule_currency_discount'] > 0) {
            $yousave_amt +=  $vtprd_info['product_session_info']['pricing_by_rule_array'][$y]['pricing_rule_currency_discount'];
          } else {
            $PercentValue =  $vtprd_info['product_session_info']['pricing_by_rule_array'][$y]['pricing_rule_percent_discount'];
            $yousave_amt +=  vtprd_compute_percent_discount($PercentValue, $price);
          }
        }
        
      }  //end for loop
      
      $vtprd_info['product_session_info']['product_discount_price'] = $price - $yousave_amt;
      //                                  ************************
       
     return;
  }
  
   
  /* ************************************************
  **   Compute percent discount for VARIATION realtime
  *************************************************** */
  function vtprd_compute_percent_discount($PercentValue, $price){
    //from apply-rules.php   function vtprd_compute_each_discount
      $percent_off = $PercentValue / 100;          
      
   // $discount_2decimals = bcmul($price , $percent_off , 2);
      $discount_2decimals = round($price * $percent_off , 2); //v1.0.7.6
      
      //compute rounding
      $temp_discount = $price * $percent_off;
          
    //$rounding = $temp_discount - $discount_2decimals;
      $rounding = round($temp_discount - $discount_2decimals, 4);   //v1.0.7.4  PHP floating point error fix - limit to 4 places right of the decimal!!
           
      if ($rounding > 0.005) {
        $discount = $discount_2decimals + .01;
      }  else {
        $discount = $discount_2decimals;
      }
           
     return $discount;
  }

   
   //APPLY TEST Globally in wp-admin ...  supply woo with ersatz pricing deals discount type
   function vtprd_woo_maybe_create_coupon_types() {
      global $wpdb, $vtprd_info;    
      
      $deal_discount_title = $vtprd_info['coupon_code_discount_deal_title'];

      $coupon_id 	= $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_title ='" . $deal_discount_title. "'  AND post_type = 'shop_coupon' AND post_status = 'publish'  LIMIT 1" );     	
      if (!$coupon_id) {
        //$coupon_code = 'UNIQUECODE'; // Code
        
        $amount = '0'; // Amount
        $discount_type = 'fixed_cart'; // Type: fixed_cart, percent, fixed_product, percent_product
        $coupon = array(
        'post_title' => $deal_discount_title, //$coupon_code,
        'post_content' => 'Pricing Deal Plugin Inserted Coupon, please do not delete',
        'post_excerpt' => 'Pricing Deal Plugin Inserted Coupon, please do not delete',        
        'post_status' => 'publish',
        'post_author' => 1,
        'post_type' => 'shop_coupon'
        );
        $new_coupon_id = wp_insert_post( $coupon );
        // Add meta
        update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
        update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
        update_post_meta( $new_coupon_id, 'individual_use', 'no' );
        update_post_meta( $new_coupon_id, 'product_ids', '' );
        update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
        update_post_meta( $new_coupon_id, 'usage_limit', '' );
        update_post_meta( $new_coupon_id, 'expiry_date', '' );
        update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
        update_post_meta( $new_coupon_id, 'free_shipping', 'no' );
      }
   
    /*  FUTURE code for free shipping with discount...
      $deal_free_shipping_title = __('Free Shipping Deal', 'vtprd'); 

      $coupon_id 	= $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_title ='" .$deal_free_shipping_title. "'  AND post_type = 'shop_coupon' AND post_status = 'publish'  LIMIT 1" );
      if (!$coupon_id) {
        //$coupon_code = 'UNIQUECODE'; // Code
        
        $amount = '0'; // Amount
        $discount_type = 'fixed_cart'; // Type: fixed_cart, percent, fixed_product, percent_product
        $coupon = array(
        'post_title' => $deal_free_shipping_title, //$coupon_code,
        'post_content' => 'Pricing Deal Plugin Inserted Coupon, please do not delete',
        'post_excerpt' => 'Pricing Deal Plugin Inserted Coupon, please do not delete',
        'post_status' => 'publish',
        'post_author' => 1,
        'post_type' => 'shop_coupon'
        );
        $new_coupon_id = wp_insert_post( $coupon );
        // Add meta
        update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
        update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
        update_post_meta( $new_coupon_id, 'individual_use', 'no' );
        update_post_meta( $new_coupon_id, 'product_ids', '' );
        update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
        update_post_meta( $new_coupon_id, 'usage_limit', '' );
        update_post_meta( $new_coupon_id, 'expiry_date', '' );
        update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
        update_post_meta( $new_coupon_id, 'free_shipping', 'yes' ); //YES!!!
      }
      */      
      
     return;
   } 

  
  function vtprd_woo_ensure_coupons_are_allowed() {     

    if ( ($_REQUEST['page'] == 'woocommerce_settings' ) ) {
      $coupons_enabled = get_option( 'woocommerce_enable_coupons' );
      if ($coupons_enabled == 'no') {
          $message  =  '<strong>' . __('Message from Pricing Deals Plugin => WooCommerce setting "Enable the use of coupons" checkbox must be checked, as Pricing Deal cart discounts are applied using the coupon system.' , 'vtprd') . '</strong>' ;
          $message .=  '<br><br>';
          $message .=  '<strong>' . __('"Enable the use of coupons" reset to Checked.' , 'vtprd') . '</strong>' ;
          $admin_notices = '<div id="message" class="error fade" style="background-color: #FFEBE8 !important;"><p>' . $message . ' </p></div>';
          add_action( 'admin_notices', create_function( '', "echo '$admin_notices';" ) );
          update_option( 'woocommerce_enable_coupons','yes');    
      }
    }
  }

  
  function vtprd_checkDateTime($date) {
    if (date('Y-m-d', strtotime($date)) == $date) {
        return true;
    } else {
        return false;
    }
  }
      
  /* ************************************************
  **   Amount comes back Formatted!
  *************************************************** */  
  function vtprd_get_Woo_cartSubtotal() {

      global $woocommerce;
      $amt = $woocommerce->cart->get_cart_subtotal();
      
      return $amt;
  }


  /* ************************************************
  **   Change the default title in the rule custom post type
  *************************************************** */
  function vtprd_change_default_title( $title ){
     $screen = get_current_screen();
     if  ( 'vtprd-rule' == $screen->post_type ) {
          $title = 'Enter Rule Title';
     }
     return $title;
  }
  add_filter( 'enter_title_here', 'vtprd_change_default_title' ); 


  //***** v1.0.4 begin
  /* ************************************************
  **  if BCMATH not installed with PHP by host, this will replace it.
  *************************************************** */
 /*  v1.0.7.6 removed!
  if (!function_exists('bcmul')) {
    function bcmul($_ro, $_lo, $_scale=0) {
      return round($_ro*$_lo, $_scale);
    }
  }
  if (!function_exists('bcdiv')) {
    function bcdiv($_ro, $_lo, $_scale=0) {
      return round($_ro/$_lo, $_scale);
    }
  }
  */
  //***** v1.0.4 end 
  
  //v1.0.7 change
  function vtprd_debug_options(){ 
    
    global $vtprd_setup_options;
    if ( ( isset( $vtprd_setup_options['debugging_mode_on'] )) &&
         ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ) ) {  
      error_reporting(E_ALL);  
    }  else {
      error_reporting(E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR);    //only allow FATAL error types  //v1.0.7.7       
    } 
    
    //v1.0.7.8 begin
    //refresh $woocommerce addressablility
    $current_version =  WOOCOMMERCE_VERSION;
    if( (version_compare(strval('2.1.0'), strval($current_version), '>') == 1) ) {   //'==1' = 2nd value is lower     
      $do_nothing_for_older_version = '';
    } else {
      //from woocommerce.php
      global $woocommerce;
      $woocommerce = WC();
    }
    //v1.0.7.8 end 
       
  }
  
  //****************************************
  //v1.0.7.4 new function
  // Format $amt with VAT suffix, as needed 
  //****************************************
  function vtprd_format_amt_and_adjust_for_taxes($amt, $k=null){ 
    global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_setup_options, $vtprd_info; 
//error_log( print_r(  'vtprd_format_amt_and_adjust_for_taxes, Begin price= ' .$amt, true ) ); 
    //at a minimum, format $amt
    if ( ( get_option( 'woocommerce_calc_taxes' ) == 'no' ) ||
         ( vtprd_maybe_customer_tax_exempt() ) ) {      //v1.0.7.9      
       return vtprd_format_money_element($amt);
    }
    
    $woocommerce_prices_include_tax   =   get_option('woocommerce_prices_include_tax');
    $woocommerce_tax_display_cart     =   get_option('woocommerce_tax_display_cart');

    if ($woocommerce_prices_include_tax == 'yes') {
        switch(true) {
          case ($woocommerce_tax_display_cart   == 'incl') :
              $amt = vtprd_format_money_element($amt); 
            break; 
          case ($woocommerce_tax_display_cart   == 'excl') :
              $product_id = $vtprd_cart->cart_items[$k]->product_id;
              $amt = vtprd_get_price_excluding_tax($product_id, $amt);
              $amt = vtprd_format_money_element($amt);
            break;
        }           
    } else {  // price does NOT include tax
        switch(true) {
          case ($woocommerce_tax_display_cart   == 'excl') :         
              $amt = vtprd_format_money_element($amt); 
            break; 
          case ($woocommerce_tax_display_cart   == 'incl') :
             $qty = 1;           
             $_tax  = new WC_Tax();
             $product = get_product( $vtprd_cart->cart_items[$k]->product_id );
             $tax_rates  = $_tax->get_rates( $product->get_tax_class() );
    			 	 $taxes      = $_tax->calc_tax( $amt  * $qty, $tax_rates, false );
    				 $tax_amount = $_tax->get_tax_total( $taxes );
    				 $amt        = round( $amt  * $qty + $tax_amount, absint( get_option( 'woocommerce_price_num_decimals' ) ) ); 
             $amt        = vtprd_format_money_element($amt); 
            break;
        }    
    }

    $amt .= vtprd_maybe_load_incl_excl_vat_lit(); 
//error_log( print_r(  'vtprd_format_amt_and_adjust_for_taxes, End price= ' .$amt, true ) );
    return $amt;
  }
  
  
  //****************************************
  //v1.0.7.4 new function
  //from woocommerce/includes/abstracts/abstract-wc-product.php
  //****************************************
  function vtprd_get_price_including_tax($product_id, $discount_price){ 
    global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_setup_options, $vtprd_info; 
//error_log( print_r(  'vtprd_get_price_including_tax, Begin price= ' .$discount_price .' Product= ' .$product_id , true ) );
    //changed $this->  to  $product->
    //use $discount_price as basi

    $qty = 1; 
    $product = get_product( $product_id ); 
    $price = $discount_price;
    
		$_tax  = new WC_Tax();

		if ( $product->is_taxable() ) {

			if ( get_option('woocommerce_prices_include_tax') === 'no' ) {

				$tax_rates  = $_tax->get_rates( $product->get_tax_class() );
				$taxes      = $_tax->calc_tax( $price * $qty, $tax_rates, false );
				$tax_amount = $_tax->get_tax_total( $taxes );
				$price      = round( $price * $qty + $tax_amount, absint( get_option( 'woocommerce_price_num_decimals' ) ) );
			} else {

				$tax_rates      = $_tax->get_rates( $product->get_tax_class() );
				$base_tax_rates = $_tax->get_shop_base_rate( $product->tax_class );

			//	if ( ! empty( $woocommerce->customer ) && $woocommerce->customer->is_vat_exempt() ) {   //v1.0.7.5
        if ( vtprd_maybe_customer_tax_exempt() )  {      //v1.0.7.9
					$base_taxes 		= $_tax->calc_tax( $price * $qty, $base_tax_rates, true );
					$base_tax_amount	= array_sum( $base_taxes );
					$price      		= round( $price * $qty - $base_tax_amount, absint( get_option( 'woocommerce_price_num_decimals' ) ) );

				} elseif ( $tax_rates !== $base_tax_rates ) {

					$base_taxes			= $_tax->calc_tax( $price * $qty, $base_tax_rates, true );
					$modded_taxes		= $_tax->calc_tax( ( $price * $qty ) - array_sum( $base_taxes ), $tax_rates, false );
					$price      		= round( ( $price * $qty ) - array_sum( $base_taxes ) + array_sum( $modded_taxes ), absint( get_option( 'woocommerce_price_num_decimals' ) ) );

				} else {

					$price = $price * $qty;

				}

			}

		} else {
			$price = $price * $qty;     
		}
//error_log( print_r(  'vtprd_get_price_including_tax, End price= ' .$price, true ) );    
    return $price;
  }
 
  //****************************************
  //v1.0.7.4 new function
  //from woocommerce/includes/abstracts/abstract-wc-product.php
  //****************************************

  function vtprd_get_price_excluding_tax($product_id, $discount_price){ 
    global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_setup_options, $vtprd_info; 

    //changed $this->  to  $product->
    //use $discount_price as basis
    
    $qty = 1;
    $product = get_product( $product_id );
    $price = $discount_price;
    
		if ( $product->is_taxable() && get_option('woocommerce_prices_include_tax') === 'yes' ) {

			$_tax       = new WC_Tax();
			$tax_rates  = $_tax->get_shop_base_rate( $product->tax_class );
			$taxes      = $_tax->calc_tax( $price * $qty, $tax_rates, true );
			$price      = $_tax->round( $price * $qty - array_sum( $taxes ) );
		} else {
			$price = $price * $qty;     
		}
        
    return $price;
  }

 
  //****************************************
  //v1.0.9.3 new function
  //from woocommerce/includes/abstracts/abstract-wc-product.php
  //****************************************
  function vtprd_get_price_including_tax_forced ($product_id, $price, $product){ 
    global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_setup_options, $vtprd_info; 
//error_log( print_r(  'vtprd_get_price_including_tax_forced, Begin price= ' .$price .' Product= ' .$product_id , true ) );
    //changed $this->  to  $product->
    //use $discount_price as basi

    $qty = 1; 
   
    
		$_tax  = new WC_Tax();

		if ( $product->is_taxable() ) {

			if ( get_option('woocommerce_prices_include_tax') === 'no' ) {

				$tax_rates  = $_tax->get_rates( $product->get_tax_class() );
				$taxes      = $_tax->calc_tax( $price * $qty, $tax_rates, false );
				$tax_amount = $_tax->get_tax_total( $taxes );
				$price      = round( $price * $qty + $tax_amount, absint( get_option( 'woocommerce_price_num_decimals' ) ) );
//error_log( print_r(  'vtprd_get_price_including_tax_forced, 001 price= ' .$price  , true ) );        
			} else {

				$tax_rates      = $_tax->get_rates( $product->get_tax_class() );
				$base_tax_rates = $_tax->get_shop_base_rate( $product->tax_class );

			//	if ( ! empty( $woocommerce->customer ) && $woocommerce->customer->is_vat_exempt() ) {   //v1.0.7.5
        if ( vtprd_maybe_customer_tax_exempt() )  {      //v1.0.7.9
					$base_taxes 		= $_tax->calc_tax( $price * $qty, $base_tax_rates, true );
					$base_tax_amount	= array_sum( $base_taxes );
					$price      		= round( $price * $qty - $base_tax_amount, absint( get_option( 'woocommerce_price_num_decimals' ) ) );
//error_log( print_r(  'vtprd_get_price_including_tax_forced, 002 price= ' .$price  , true ) ); 
				} elseif ( $tax_rates !== $base_tax_rates ) {

					$base_taxes			= $_tax->calc_tax( $price * $qty, $base_tax_rates, true );
					$modded_taxes		= $_tax->calc_tax( ( $price * $qty ) - array_sum( $base_taxes ), $tax_rates, false );
					$price      		= round( ( $price * $qty ) - array_sum( $base_taxes ) + array_sum( $modded_taxes ), absint( get_option( 'woocommerce_price_num_decimals' ) ) );
//error_log( print_r(  'vtprd_get_price_including_tax_forced, 003 price= ' .$price  , true ) ); 
				} else {

   				$taxes      = $_tax->calc_tax( $price * $qty, $tax_rates, false );      
  				$tax_amount = $_tax->get_tax_total( $taxes );      
  				$price      = round( $price * $qty + $tax_amount, absint( get_option( 'woocommerce_price_num_decimals' ) ) );
				}

			}

		} else {
			$price = $price * $qty;  
//error_log( print_r(  'vtprd_get_price_including_tax_forced, 005 price= ' .$price  , true ) ); 
		}




/* 
    $qty = 1; 
     
    
		if ( $product->is_taxable() ) {

				$_tax       = new WC_Tax();
        $tax_rates  = $_tax->get_rates( $product->get_tax_class() );
				$taxes      = $_tax->calc_tax( $price * $qty, $tax_rates, false );
				$tax_amount = $_tax->get_tax_total( $taxes );
				$price      = round( $price * $qty + $tax_amount, absint( get_option( 'woocommerce_price_num_decimals' ) ) );
      
 			$_tax       = new WC_Tax();
			$tax_rates  = $_tax->get_shop_base_rate( $product->tax_class );
			$taxes      = $_tax->calc_tax( $price * $qty, $tax_rates, true );
			$price      = $_tax->round( $price * $qty + array_sum( $taxes ) ); 
      $price = round( $price  * $qty + $tax_amount, absint( get_option( 'woocommerce_price_num_decimals' ) ) );      
       
       $qty = 1;           
       $_tax  = new WC_Tax();                
      // $product = get_product( $product_id ); 
       $tax_rates  = $_tax->get_rates( $product->get_tax_class() );
		 	 $taxes      = $_tax->calc_tax( $price  * $qty, $tax_rates, false );
			 $tax_amount = $_tax->get_tax_total( $taxes );
       
			 $price = round( $price  * $qty + $tax_amount, absint( get_option( 'woocommerce_price_num_decimals' ) ) ); 

		} 
*/    
    
//error_log( print_r(  'vtprd_get_price_including_tax_forced, End price= ' .$price, true ) );    
    return $price;
  }
 
  //****************************************
  //v1.0.9.3 new function
  //from woocommerce/includes/abstracts/abstract-wc-product.php
  //****************************************

  function vtprd_get_price_excluding_tax_forced ($product_id, $price, $product){ 
    global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_setup_options, $vtprd_info; 

    //changed $this->  to  $product->
    //use $discount_price as basis
    
    $qty = 1;
    
		if ( $product->is_taxable() ) {
    //if ( $product->is_taxable() && get_option('woocommerce_prices_include_tax') === 'yes' ) {

			$_tax       = new WC_Tax();
			$tax_rates  = $_tax->get_shop_base_rate( $product->tax_class );
			$taxes      = $_tax->calc_tax( $price * $qty, $tax_rates, true );
			$price      = $_tax->round( $price * $qty - array_sum( $taxes ) );
		} 
        
    return $price;
  }



  
/*  MOVED to vt-pricing-deals.php  v1.0.9.0
 
  //****************************************
  //v1.0.7.4 new function
  //  adds in default 'Wholesale Buyer' role at install time
  //****************************************
  function vtprd_maybe_add_wholesale_role(){ 
		global $wp_roles;
	
		if ( class_exists( 'WP_Roles' ) ) {
      if ( !isset( $wp_roles ) ) { 
			   $wp_roles = new WP_Roles();
      }
    }
  
    $wholesale_role_name =  __('Wholesale Buyer' , 'vtprd');
  
    //Check if it's already there!
    If ( !get_role( $wholesale_role_name ) ) {
  		if ( is_object( $wp_roles ) ) { 
  			$capabilities = array( 
  				'read' => true,
  				'edit_posts' => false,
  				'delete_posts' => false,
  			);
  
  			add_role ('wholesale_buyer', $wholesale_role_name, $capabilities );
  
  			$role = get_role( 'wholesale_buyer' ); 
  			$role->add_cap( 'buy_wholesale' );
  
  		}
    } 
    
     //v1.0.9.0 begin
    $wholesale_role_name =  __('Wholesale Tax Free' , 'vtprd');
  
    //Check if it's already there!
    If ( !get_role( $wholesale_role_name ) ) {
  		if ( is_object( $wp_roles ) ) { 
  			$capabilities = array( 
  				'read' => true,
  				'edit_posts' => false,
  				'delete_posts' => false,
  			);
  
  			add_role ('wholesale_tax_free', $wholesale_role_name, $capabilities );
  
  			$role = get_role( 'wholesale_tax_free' ); 
  			$role->add_cap( 'buy_tax_free' );
  
  		}
    }     
    //could have done it this way, but not switchable!!
    //$user = new WP_User( $user_id );
    //$user->add_cap( 'can_edit_posts');
    //v1.0.9.0 end
           
    return;
  }  
*/
  

  //****************************************
  //v1.0.7.4 new function
  //****************************************
  function vtprd_load_cart_total_incl_excl(){ 
	  global $vtprd_cart, $woocommerce;
/*
    if ( get_option( 'woocommerce_calc_taxes' )      == 'yes' ) {
      if ( get_option( 'woocommerce_tax_display_cart' ) == 'incl' )  {
          $vtprd_cart->yousave_cart_total_amt =  $vtprd_cart->yousave_cart_total_amt_incl_tax;  
      } else {
          $vtprd_cart->yousave_cart_total_amt =  $vtprd_cart->yousave_cart_total_amt_excl_tax;
      }
    } 
 */   
    //v1.0.8.9a  initialize the return base amt
    $return_amt = $vtprd_cart->yousave_cart_total_amt; //v1.0.8.9a
    
    if ( get_option( 'woocommerce_calc_taxes' )  == 'yes' ) {
       switch (get_option('woocommerce_prices_include_tax')) {
          case 'yes':
              if (get_option('woocommerce_tax_display_cart')   == 'excl') {
                 //v1.0.8.9a begin  re-fix!!!
                 //$excl_vat_lit .= ' <small>' . $woocommerce->countries->ex_tax_or_vat() . '</small>';  //v1.0.7.5
                 $return_amt =  $vtprd_cart->yousave_cart_total_amt_excl_tax; //The return value is only accessed in a very few executions if this function!
                 //v1.0.8.9a end
              }   
             break;         
          case 'no':
              if (get_option('woocommerce_tax_display_cart')   != 'excl') { //v1.0.9.3
              //if (get_option('woocommerce_tax_display_cart')   == 'incl') {              
                 $vtprd_cart->yousave_cart_total_amt =  $vtprd_cart->yousave_cart_total_amt_incl_tax; 
                 $return_amt = $vtprd_cart->yousave_cart_total_amt;  //v1.0.8.9a  The return value is only accessed in a very few executions if this function!
              }           
             break;
       }          
    }    

 
    //v1.0.8.9a  The return_amt is ONLY accessed when reporting on CART DISCOUNT (sub)TOTAL in the detail area
    return $return_amt; //v1.0.8.9a
  }

  
  //****************************************
  //v1.0.7.9 new function
  //****************************************
  function vtprd_maybe_customer_tax_exempt(){ 
		global $vtprd_cart, $woocommerce, $vtprd_info;

    //save is_tax_exempt status
    //handles addressability for emails!
    //defaults to false.
    if ( (isset($vtprd_cart->customer_is_tax_exempt)) &&  //v1.0.8.0
         ($vtprd_cart->customer_is_tax_exempt) ) {           
      return true;
    }
    if (!is_object($woocommerce->customer)) {   
      return false; 
    }
    if ( ! empty( $woocommerce->customer ) && $woocommerce->customer->is_vat_exempt() ) {
      $vtprd_cart->customer_is_tax_exempt = true;      
      return true;
    } 
    
    //v1.0.9.0 begin
    //pick up setting from NEW user-level wp-admin screen field!!
    if ($vtprd_info['user_is_tax_exempt'])  {
       return true;
    } 
    //v1.0.9.0 end
       
    return false;
  }  
   
  
  //****************************************
  //v1.0.7.4 new function
  //****************************************
  function vtprd_maybe_load_incl_excl_vat_lit(){ 
		global $vtprd_cart, $woocommerce;
    //inc_tax_or_vat()

    $excl_vat_lit = '';
/*    
    if ( get_option( 'woocommerce_calc_taxes' )  == 'yes' ) {
       if (get_option('woocommerce_prices_include_tax') == 'yes') {
          if (get_option('woocommerce_tax_display_cart')   == 'excl') {
             $excl_vat_lit .= ' <small>' . $woocommerce->countries->ex_tax_or_vat() . '</small>';   //v1.0.7.5
          } 
       } else {
          if (get_option('woocommerce_tax_display_cart')   == 'incl') {
             $excl_vat_lit .= ' <small>' . $woocommerce->countries->inc_tax_or_vat() . '</small>';   //v1.0.7.5
          }               
       }
    }
*/
     if ( get_option( 'woocommerce_calc_taxes' )  == 'yes' ) {
       switch (get_option('woocommerce_prices_include_tax')) {
          case 'yes':
              if (get_option('woocommerce_tax_display_cart')   == 'excl') {
                 $excl_vat_lit .= ' <small>' . $woocommerce->countries->ex_tax_or_vat() . '</small>';     //v1.0.7.5
              } 
             break;         
          case 'no':
              if (get_option('woocommerce_tax_display_cart')   == 'incl') {
                 $excl_vat_lit .= ' <small>' . $woocommerce->countries->inc_tax_or_vat() . '</small>';     //v1.0.7.5
              }           
             break;
       }          
    } 


    return $excl_vat_lit;
  } 
   
  //****************************************
  //v1.0.7.4 new function
  //  Testing Note:  Compare how Deal discount is applied vs Regular coupon discount of same amount
  //    example: 10% cart discount vs 10% coupon, with a variety of tax switch settings...
  //****************************************
  function vtprd_coupon_apply_before_tax(){ 

    $apply_before_tax = 'yes';  //supply DEFAULT
    if ( get_option( 'woocommerce_calc_taxes' )  == 'yes' ) {
       if (get_option('woocommerce_prices_include_tax') == 'yes') {
         $do_nothing;
          if (get_option('woocommerce_tax_display_cart')   == 'excl') {
             $apply_before_tax = 'no'; 
          } 
       } else {
          if (get_option('woocommerce_tax_display_cart')   == 'incl') {
            $apply_before_tax = 'no'; 
          }               
       }
    } 
 
    return $apply_before_tax;
  }
        
      //v1.0.9.0  begin
      add_action( 'show_user_profile', 'vtprd_my_show_extra_profile_fields' );
      add_action( 'edit_user_profile', 'vtprd_my_show_extra_profile_fields' );
      
      function vtprd_my_show_extra_profile_fields( $user ) { 
      
      		if ( current_user_can( 'edit_user', $user->ID ) ) {
      			?>
      				<table class="form-table">
      					<tbody>
      						<tr>
      							<th><label for="vtprd_user_is_tax_exempt"><?php _e( 'Pricing Deals User Tax Free', 'vtprd' ); ?></label></th>
      							<td>
      								<?php //if ( empty( $user->woocommerce_api_consumer_key ) ) : ?>
      									<input name="vtprd_user_is_tax_exempt" type="checkbox" id="vtprd_user_is_tax_exempt" value="0" />
      									<span class="description"><?php _e( 'User Transactions are Tax-Free', 'vtprd' ); ?></span>
      							</td>
      						</tr>
      					</tbody>
      				</table>
      			<?php
      		}
      }
      
      add_action( 'personal_options_update',  'vtprd_my_save_extra_profile_fields' );
      add_action( 'edit_user_profile_update', 'vtprd_my_save_extra_profile_fields' );
      
      function vtprd_my_save_extra_profile_fields( $user_id ) {
      
      	if ( !current_user_can( 'edit_user', $user_id ) )
      		return false;
      
      	update_usermeta( $user_id, 'vtprd_user_is_tax_exempt', $_POST['vtprd_user_is_tax_exempt'] );
      }
      //v1.0.9.0  end
          
    
    //v1.0.7.9 new function
    //from::includes/api/class-wc-api-products.php
  	function vtprd_get_attributes( $product ) {

		$attributes = array();

		if ( $product->is_type( 'variation' ) ) {

			// variation attributes
			foreach ( $product->get_variation_attributes() as $attribute_name => $attribute ) {

				// taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`
				$attributes[] = array(
					'name'   => ucwords( str_replace( 'attribute_', '', str_replace( 'pa_', '', $attribute_name ) ) ),
					'option' => $attribute,
				);
			}

		} else {

			foreach ( $product->get_attributes() as $attribute ) {

				// taxonomy-based attributes are comma-separated, others are pipe (|) separated
				if ( $attribute['is_taxonomy'] )
					$options = explode( ',', $product->get_attribute( $attribute['name'] ) );
				else
					$options = explode( '|', $product->get_attribute( $attribute['name'] ) );

				$attributes[] = array(
					'name'      => ucwords( str_replace( 'pa_', '', $attribute['name'] ) ),
					'position'  => $attribute['position'],
					'visible'   => (bool) $attribute['is_visible'],
					'variation' => (bool) $attribute['is_variation'],
					'options'   => array_map( 'trim', $options ),
				);
			}
		}

		return $attributes;
	}
  //v1.0.7.9  end
  
  //v1.0.9.3 begin
  //Need to use this deferred admin structure for ACTIVATION messages
  /*
        $notices= get_option('vtprd_deferred_admin_notices', array());
        $notices[]= $admin_notices;
        update_option('vtprd_deferred_admin_notices', $notices);
  */
  add_action('admin_notices', 'vtprd_admin_notices');
  function vtprd_admin_notices() {
    if ($notices= get_option('vtprd_deferred_admin_notices')) {
      foreach ($notices as $notice) {
        echo $notice;
      }
      delete_option('vtprd_deferred_admin_notices');
    }   
  } 
    
  add_action('admin_init', 'vtprd_check_for_deactivation_action');
  function vtprd_check_for_deactivation_action() {
    //moved here from vt-pricing-deals.php, so it can run at admin init time
    if( !is_plugin_active(VTPRD_PLUGIN_SLUG) ) { 
      return;
    }  
    
    global $wp_version, $vtprd_setup_options;
		$earliest_allowed_wp_version = 3.3;
    if( (version_compare(strval($earliest_allowed_wp_version), strval($wp_version), '>') == 1) ) {   //'==1' = 2nd value is lower  
        $message  =  '<strong>' . __('Looks like you\'re running an older version of WordPress, you need to be running at least WordPress 3.3 to use the Varktech Pricing Deals plugin.' , 'vtprd') . '</strong>' ;
        $message .=  '<br>' . __('Current Wordpress Version = ' , 'vtprd')  . $wp_version ;
        $admin_notices = '<div id="message" class="error fade" style="background-color: #FFEBE8 !important;"><p>' . $message . ' </p></div>';
        add_action( 'admin_notices', create_function( '', "echo '$admin_notices';" ) );
        if (get_option('vtprd_deferred_admin_notices')) {
          delete_option('vtprd_deferred_admin_notices');
        }
        deactivate_plugins( VTPRD_PLUGIN_SLUG );
        return;
		}
   
            
   if (version_compare(PHP_VERSION, VTPRD_EARLIEST_ALLOWED_PHP_VERSION) < 0) {    //'<0' = 1st value is lower  
        $message  =  '<strong>' . __('Looks like you\'re running an older version of PHP.   - your PHP version = ' , 'vtprd') .PHP_VERSION. '</strong>' ;
        $message .=  '<br>' . __('You need to be running **at least PHP version 5** to use this plugin. Please contact your host and request an upgrade to PHP 5+ .  Once that has been installed, you can activate this plugin.' , 'vtprd');
        $admin_notices = '<div id="message" class="error fade" style="background-color: #FFEBE8 !important;"><p>' . $message . ' </p></div>';
        add_action( 'admin_notices', create_function( '', "echo '$admin_notices';" ) );
        if (get_option('vtprd_deferred_admin_notices')) {
          delete_option('vtprd_deferred_admin_notices');
        }
        deactivate_plugins( VTPRD_PLUGIN_SLUG );
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
        if (get_option('vtprd_deferred_admin_notices')) {
          delete_option('vtprd_deferred_admin_notices');
        }        
        deactivate_plugins( VTPRD_PLUGIN_SLUG );
        return;         
  		}
    } 
    /*  else 
    if (VTPRD_PARENT_PLUGIN_NAME == 'WooCommerce') {
        $message  =  '<strong>' . __('Varktech Pricing Deals for WooCommerce requires that WooCommerce be installed and activated. ' , 'vtprd') . '</strong>' ;
        $message .=  '<br>' . __('It looks like WooCommerce is either not installed, or not activated. ' , 'vtprd');
        $admin_notices = '<div id="message" class="error fade" style="background-color: #FFEBE8 !important;"><p>' . $message . ' </p></div>';
        add_action( 'admin_notices', create_function( '', "echo '$admin_notices';" ) );
        if (get_option('vtprd_deferred_admin_notices')) {
          delete_option('vtprd_deferred_admin_notices');
        }        
        deactivate_plugins( VTPRD_PLUGIN_SLUG );
        return;         
    }
    */
    
        if ( ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon') ||
             ($vtprd_setup_options['discount_taken_where'] <= ' ') ) { //v1.0.9.3  doesn't apply if 'discountUnitPrice'
        //v1.0.7.4 begin  
          //****************************************
          //INSIST that coupons be enabled in woo, in order for this plugin to work!!
          //****************************************
          //always check if the manually created coupon codes are there - if not create them.
          vtprd_woo_maybe_create_coupon_types();        
          $coupons_enabled = get_option( 'woocommerce_enable_coupons' ) == 'no' ? false : true;
          if (!$coupons_enabled) {  
            $message  =  '<strong>' . __('In order for the "Pricing Deals" plugin to function successfully when the "Coupon Discount" setting is selected, the Woo Coupons Setting must be on, and it is currently off.' , 'vtprd') . '</strong>' ;
            $message .=  '<br><br>' . __('Please go to the Woocommerce/Settings page.  Under the "Checkout" tab, check the box next to "Enable the use of coupons" and click on the "Save Changes" button.'  , 'vtprd');
            $admin_notices = '<div id="message" class="error fade" style="background-color: #FFEBE8 !important;"><p>' . $message . ' </p></div>';
            add_action( 'admin_notices', create_function( '', "echo '$admin_notices';" ) );            
          } 
        }   
    
  }
  //v1.0.9.3 end

  //**v1.0.7.5 begin
  /* ************************************************
  **  if wc_price not there (pre woo 2.1 )
  *************************************************** */
  /*v1.0.7.6 removed!
  if (!function_exists('wc_price')) {
    function wc_price($amount) {
      return woocommerce_price($amount);
    }
  }
   */
  //***** 1.0.7.5  end 

  
  /* ************************************************
  **  Disable draggable metabox in the rule custom post type
  *************************************************** */
/*
  function vtprd_disable_drag_metabox() {
     $screen = get_current_screen();
     if  ( 'vtprd-rule' == $screen->post_type ) { 
       wp_deregister_script('postbox');
     }
  }
  add_action( 'admin_init', 'vtprd_disable_drag_metabox' ); 
*/
  
  /* ************************************************
  **  Display DB queries, time spent and memory consumption  IF  debugging_mode_on
  *************************************************** */
/*
  function vtprd_performance( $visible = false ) {
    global  $vtprd_setup_options;
    if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){ 
      $stat = sprintf(  '%d queries in %.3f seconds, using %.2fMB memory',
          get_num_queries(),
          timer_stop( 0, 3 ),
          memory_get_peak_usage() / 1024 / 1024
          );
      echo  $visible ? $stat : "<!-- {$stat} -->" ;
    }
}
 add_action( 'wp_footer', 'vtprd_performance', 20 );
*/ 
 

/*
  // ****************************************************
  //**  W3 Total Cache Special Flush for Custom Post type
  // ***************************************************  
function vtprd_w3_flush_page_custom( $post_id ) {

   if ( !is_plugin_active('W3 Total Cache') ) {
      return;
   }
   
   if ( 'vtprd-rule' != get_post_type( $post_id ) ) {
      return;
   }
          
    $w3_plugin_totalcache->flush_pgcache();

}  
  add_action( 'edit_post',    'vtprd_w3_flush_page_custom', 10, 1 );
  add_action( 'save_post',    'vtprd_w3_flush_page_custom', 10, 1 );
  add_action( 'delete_post',  'vtprd_w3_flush_page_custom', 10, 1 );
  add_action( 'trash_post',   'vtprd_w3_flush_page_custom', 10, 1 );
  add_action( 'untrash_post', 'vtprd_w3_flush_page_custom', 10, 1 );
 */
/*
add_filter('woocommerce_coupon_message',  'prevent_coupon_msg', 10, 3);
function prevent_coupon_msg($msg, $msg_code, $coupon ){

$msg = ' ';
return $msg;

}


show_checkout_discount_detail_lines		             Show Product Discount Detail Lines?
show_checkout_discount_titles_above_details	Show   Short Checkout Message for "Grouped by Rule within Product"?
show_checkout_purchases_subtotal		                Show Cart Purchases Subtotal Line?
show_checkout_discount_total_line		                Show Products + Discounts Grand Total Line

*/
