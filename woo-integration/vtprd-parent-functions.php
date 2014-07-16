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
      if (sizeof($woocommerce_cart_contents) > 0) {
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

              } else { 
                  $vtprd_cart_item->product_id           = $cart_item['product_id'];
                  $vtprd_cart_item->product_name         = $_product->get_title().$woocommerce->cart->get_item_data( $cart_item );
              }
              
              //v1.0.7.4 begin
              $product = get_product( $vtprd_cart_item->product_id );
              $tax_status = get_post_meta( $vtprd_cart_item->product_id, '_tax_status', true ); 
              if ( $tax_status == 'taxable' ) {              
                $vtprd_cart_item->product_is_taxable = true; 
              } else {
                $vtprd_cart_item->product_is_taxable = false;                                              
              } 
 
              //v1.0.7.4 end  
              
              $vtprd_cart_item->quantity      = $cart_item['quantity'];                                                  
                  
              $product_id = $vtprd_cart_item->product_id;
               
              //***always*** will be a session found
              //$session_found = vtprd_maybe_get_product_session_info($product_id);
              vtprd_maybe_get_product_session_info($product_id);

              //By this time, there may  be a 'display' session variable for this product, if a discount was displayed in the catalog
              //  so 2nd - nth iteration picks up the discounted current price AND the original price for comparison
              if ($vtprd_info['product_session_info']['product_discount_price'] > 0) {
                  $vtprd_cart_item->unit_price             =  vtprd_compute_current_unit_price($product_id, $cart_item, $vtprd_cart_item, $product);     //v1.0.7.4  get current unit price off of the cart, to pick up any CATALOG discounts
                  $vtprd_cart_item->save_orig_unit_price   =  $vtprd_info['product_session_info']['product_unit_price'];   //v1.0.7.4  save for later comparison
                  $vtprd_cart_item->db_unit_price          =  $vtprd_info['product_session_info']['product_unit_price'];

                  $vtprd_cart_item->db_unit_price_special  =  $vtprd_info['product_session_info']['product_special_price']; 
                  $vtprd_cart_item->db_unit_price_list     =  $vtprd_info['product_session_info']['product_list_price'];     
              } else {              
                  //v1.0.5 begin - redo for VAT processing...
                  
                  //v1.0.6 begin
                  $regular_price = get_post_meta( $product_id, '_regular_price', true );
                  if ($regular_price > 0) {
                     $vtprd_cart_item->db_unit_price_list  =  $regular_price;
                  } else {
                     $vtprd_cart_item->db_unit_price_list  =  get_post_meta( $product_id, '_price', true );
                  }
                  //v1.0.6 end
                  
                  $vtprd_cart_item->db_unit_price_special  =  get_post_meta( $product_id, '_sale_price', true );                  

                   //v1.0.7.4  picks up unit price from product subtotal and quantity
                  $vtprd_cart_item->unit_price             =  vtprd_compute_current_unit_price($product_id, $cart_item, $vtprd_cart_item, $product); //v1.0.7.4
                  
                  $vtprd_cart_item->save_orig_unit_price   =  $vtprd_cart_item->unit_price;   //v1.0.7.4  save for later comparison
                  
                  /*  moved to its own function, above  v1.0.7.4
                  if (( get_option( 'woocommerce_prices_include_tax' ) == 'no' )  || 
                      ( $woocommerce->customer->is_vat_exempt()) ) {
                     //NO VAT included in price
                     $vtprd_cart_item->unit_price  =  $cart_item['line_subtotal'] / $cart_item['quantity'];                                                           
                  } else {
                     
                     //v1.0.7.4 begin
                     //TAX included in price in DB, and Woo $cart_item pricing **has already subtracted out the TAX **, so restore the TAX
                     //  this price reflects the tax situation of the ORIGINAL price - so if the price was originally entered with tax, this will reflect tax
                     $unit_price_no_tax            =  $cart_item['line_subtotal'] / $cart_item['quantity'];
                     $vtprd_cart_item->unit_price  =  vtprd_get_price_including_tax($product_id, $unit_price_no_tax);
                  }
                  */                     
                     /*  replaced in v1.0.7.4 with above
                     //VAT included in price in DB, and Woo $cart_item pricing **has already subtracted out the VAT **, so use DB prices (otherwise if other functions called, recursive processing will ensue...)                     
                      switch(true) {
                        case ($vtprd_cart_item->db_unit_price_special > 0) :  
                            if ( ($vtprd_cart_item->db_unit_price_list < 0) ||    //sometimes list price is blank, and user only sets a sale price!!!
                                 ($vtprd_cart_item->db_unit_price_special < $vtprd_cart_item->db_unit_price_list) )  {
                                //item is on sale, use sale price
                               $vtprd_cart_item->unit_price = $vtprd_cart_item->db_unit_price_special;
                            } else {
                               //use regular price
                               $vtprd_cart_item->unit_price = $vtprd_cart_item->db_unit_price_list;
                            }
                          break;
                        default:
                            $vtprd_cart_item->unit_price = $vtprd_cart_item->db_unit_price_list;                            
                          break;
                      }
                      
                  }                     
                   */
                      //v1.0.7.4 end
                     

                  
                  $vtprd_cart_item->db_unit_price  =  $vtprd_cart_item->unit_price;

                  //v1.0.5 end               
              }               


              // db_unit_price_special CAN be zero if item is FREE!!
              if ($vtprd_cart_item->unit_price < $vtprd_cart_item->db_unit_price_list )  {
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
              //v1.0.7.6 TEMPORARY removal
              
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
              
               
              //add cart_item to cart array
              $vtprd_cart->cart_items[]       = $vtprd_cart_item;
				    }
        } //	endforeach;
        
        
		} //end  if (sizeof($woocommerce->cart->get_cart())>0) 
     
     
    if ( (defined('VTPRD_PRO_DIRNAME')) && ($vtprd_setup_options['use_lifetime_max_limits'] == 'yes') )  {
      vtprd_get_purchaser_info_from_screen();   
    }
    $vtprd_cart->purchaser_ip_address = $vtprd_info['purchaser_ip_address']; 

  }


  
   //************************************* 
   //v1.0.7.4  New Function
   //
   //  the vtprd_cart unit price and discounts all reflect the TAX STATE of 'woocommerce_prices_include_tax'
   //
   //************************************* 
	function vtprd_compute_current_unit_price($product_id, $cart_item, $vtprd_cart_item, $product){
      global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_setup_options, $vtprd_info;  
      $product = get_product( $product_id ); 

		  if ( $vtprd_cart_item->product_is_taxable ) {
        if ( ( get_option( 'woocommerce_calc_taxes' ) == 'no' ) ||
             ( get_option( 'woocommerce_prices_include_tax' ) == 'no' )  || 
             ( $woocommerce->customer->is_vat_exempt()) ) {
           //NO VAT included in price
           $unit_price  =  $cart_item['line_subtotal'] / $cart_item['quantity'];                                                  
        } else {
           
           //v1.0.7.4 begin
           //TAX included in price in DB, and Woo $cart_item pricing **has already subtracted out the TAX **, so restore the TAX
           //  this price reflects the tax situation of the ORIGINAL price - so if the price was originally entered with tax, this will reflect tax
           $price           =  $cart_item['line_subtotal'] / $cart_item['quantity'];
          // $unit_price  =  vtprd_get_price_including_tax($product_id, $price);
           $qty = 1;           
           $_tax  = new WC_Tax();                
          // $product = get_product( $product_id ); 
           $tax_rates  = $_tax->get_rates( $product->get_tax_class() );
  			 	 $taxes      = $_tax->calc_tax( $price  * $qty, $tax_rates, false );
  				 $tax_amount = $_tax->get_tax_total( $taxes );
  				 $unit_price = round( $price  * $qty + $tax_amount, absint( get_option( 'woocommerce_price_num_decimals' ) ) ); 
                     
        } 
      } else {
         $unit_price  =  $cart_item['line_subtotal'] / $cart_item['quantity'];
      } 
                   
     return $unit_price;     
     
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

      $vtprd_cart = new VTPRD_Cart;  
      $vtprd_cart_item                = new VTPRD_Cart_Item;    
        
      // v1.0.7.3  begin
      //  straight get_post caused WOO to loose the plot with variable products on 1st time through...
      //$post = get_post($product_id);
      if ( ( !isset($post->post_name) ) ||
           ( $post->post_name <= ' ' ) ) {
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
      $vtprd_cart_item->unit_price            = $price;
      $vtprd_cart_item->db_unit_price         = $price;
      $vtprd_cart_item->db_unit_price_list    = $price;
      $vtprd_cart_item->db_unit_price_special = $price;    
      $vtprd_cart_item->total_price           = $price;
            
      /*  *********************************
      ***  JUST the cat *ids* please...
      ************************************ */
      //v1.0.7.8  begin
      
      //if we're on a variation, gotta use the Parent to get the taxonomies!!
      if ($post_parent_ID) {
        $use_this_id = $post_parent_ID;
      } else {
        $use_this_id = $product_id;
      }
      
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
      
      
      $tax_status = get_post_meta( $product_id, '_tax_status', true ); 
      if ( $tax_status == 'taxable' ) {              
        $vtprd_cart_item->product_is_taxable = true; 
      } else {
        $vtprd_cart_item->product_is_taxable = false;
      }      
      
       //v1.0.7.4 end
        
      //add cart_item to cart array
      $vtprd_cart->cart_items[]       = $vtprd_cart_item;  
      
       //v1.0.7.8  begin
       //restore parent $post as needed, for WOO's sanity
      if ($post_parent_ID)   { 
         $post = get_post($post_parent_ID);
      }
       //v1.0.7.8  end           
            
                
  }

	
	function vtprd_move_vtprd_single_product_to_session($product_id){
      global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_setup_options, $vtprd_rules_set;  

      $short_msg_array = array();
      $full_msg_array = array();
      $msg_already_done = 'no';
    
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

        //v1.0.7.4 begin  -  initialize variables
       $price_including_tax           =   0; 
       $price_excluding_tax           =   0;
       $price_including_tax_html      =   '';
       $price_excluding_tax_html      =   '';
       $price_display_suffix          =   '';
       //v1.0.7.4 end
      
      if ($vtprd_cart->cart_items[0]->yousave_total_amt > 0) {
         $list_price                    =   $vtprd_cart->cart_items[0]->db_unit_price_list;
         $db_unit_price_list_html_woo   =   woocommerce_price($list_price);
         $discount_price                =   $vtprd_cart->cart_items[0]->discount_price;
         $discount_price_html_woo       =   woocommerce_price($discount_price);

         //v1.0.7.4 begin
         $price_including_tax           =   vtprd_get_price_including_tax($product_id, $discount_price); 
         $price_excluding_tax           =   vtprd_get_price_excluding_tax($product_id, $discount_price);
         $price_including_tax_html      =   woocommerce_price( $price_including_tax );  //v1.0.7.7  using older woocommerce_price, was wc_price
         $price_excluding_tax_html      =   woocommerce_price( $price_excluding_tax );  //v1.0.7.7  using older woocommerce_price, was wc_price
         //v1.0.7.4 end

         //v1.0.7 begin
         //from woocommerce/includes/abstracts/abstract-wc-product.php
         // Check for Price Suffix
         
         //v1.0.7.4 begin
         //  no suffix processing if taxes not turned on!!
         global $woocommerce; 
         if ( ( get_option( 'woocommerce_calc_taxes' ) == 'no' ) ||
              ( !$vtprd_cart->cart_items[0]->product_is_taxable) ||
              ( $woocommerce->customer->is_vat_exempt()) )  {
            $price_display_suffix  = false;            
         } else {
            $price_display_suffix  = get_option( 'woocommerce_price_display_suffix' );
         }
         //v1.0.7.4 end
         
      	 if ( ( $price_display_suffix ) &&                              //v1.0.7.2
              ( $vtprd_cart->cart_items[0]->discount_price > 0 ) ) {   //v1.0.7.2  don't do suffix for zero amount...
      			
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
   //      $vtprd_cart->cart_items[0]->product_discount_price_html_woo = 
   //         '<del>' . $db_unit_price_list_html_woo . '</del><ins>' . $discount_price_html_woo . '</ins>'; 
      } else {
         $db_unit_price_list_html_woo = '';
         $discount_price_html_woo = '';
         $vtprd_cart->cart_items[0]->product_discount_price_html_woo = '';      
      }

      $vtprd_info['product_session_info']  =     array (
            'product_list_price'           => $vtprd_cart->cart_items[0]->db_unit_price_list,
            'product_list_price_html_woo'  => $db_unit_price_list_html_woo,
            'product_unit_price'           => $vtprd_cart->cart_items[0]->db_unit_price,
            'product_special_price'        => $vtprd_cart->cart_items[0]->db_unit_price_special,
            'product_discount_price'       => $vtprd_cart->cart_items[0]->discount_price,
            //v1.0.7.4 - field now contains **just** the discount - suffix is added later.  
            //  this price reflects the tax situation of the ORIGINAL price - so if the price was originally entered with tax, this will reflect tax
            'product_discount_price_html_woo'  => 
                                              $discount_price_html_woo,            
            //v1.0.7.4 begin
            'product_discount_price_incl_tax_woo'      =>
                                              $price_including_tax,
            'product_discount_price_excl_tax_woo'      =>
                                              $price_excluding_tax,
            'product_discount_price_incl_tax_html_woo'      =>
                                              $price_including_tax_html,
            'product_discount_price_excl_tax_html_woo'      =>
                                              $price_excluding_tax_html,                                              
            'product_discount_price_suffix_html_woo'   =>
                                              $price_display_suffix, 
            //v1.0.7.4 end
                                                        
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
            'pricing_by_rule_array'        => $vtprd_cart->cart_items[0]->pricing_by_rule_array                   
          ) ;      
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

    function vtprd_fill_variations_checklist($tax_class, $checked_list = NULL, $pop_in_out_sw, $product_ID, $product_variation_IDs) { 
        global $post, $vtprd_info;
        // *** ------------------------------------------------------------------------------------------------------- ***
        // additional code from:  woocommerce/admin/post-types/writepanels/writepanel-product-type-variable.php
        // *** ------------------------------------------------------------------------------------------------------- ***
        //    woo doesn't keep the variation title in post title of the variation ID post, additional logic constructs the title ...
        
        $parent_post = get_post($product_ID);
        
        $attributes = (array) maybe_unserialize( get_post_meta($product_ID, '_product_attributes', true) );
    
        $parent_post_terms = wp_get_post_terms( $post->ID, $attribute['name'] );
       
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
            $variation_product_name_attributes;
            $variation_product_name_attributes_cnt = 0;
            
            //get the variation names
            foreach ($attributes as $attribute) :

									// Only deal with attributes that are variations
									if ( !$attribute['is_variation'] ) continue;

									// Get current value for variation (if set)
									$variation_selected_value = get_post_meta( $product_variation_ID, 'attribute_' . sanitize_title($attribute['name']), true );

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
										$options = explode('|', $attribute['value']);
										foreach ($options as $option) :
											if ($variation_selected_value == $option) {
                        $variation_label .= ucfirst($option) . '&nbsp;&nbsp;' ;
                          
                          //for auto-insert support
                          if ($variation_product_name_attributes_cnt > 0) {
                            $variation_product_name_attributes .= '{,}';  //custom list separator
                          }
                          $variation_product_name_attributes .= ucfirst($option);
                          $variation_product_name_attributes_cnt++;
                                                 
                      }
										endforeach;
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
       if ($attribute['is_variation'])  {
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
    return $user_role;
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
    $output;

    if (sizeof($vtprd_cart->cart_items[$k]->variation_array) > 0   ) {
      $output .= '<span class="vtprd-product-name-widget">' . $vtprd_cart->cart_items[$k]->parent_product_name .'</span>';	
      $output .= '<dl class="variation">';
      foreach($vtprd_cart->cart_items[$k]->variation_array as $key => $value) {          
        //v1.0.7.8  begin
        // add in on-the-fly attribute name handling; remove sanitize_title
        $name  = str_replace( 'attribute_pa_', '', $key  );  //post v 2.1
        $name  = str_replace( 'attribute_', '', $key  );     //post v 2.1   for on-the-fly variations
        $value = str_replace( 'attribute_', '', $value  );   //post v 2.1   for on-the-fly variations
        //v1.0.7.8  end
        $name = sanitize_title( str_replace( 'pa_', '', $name  ) );   //pre v 2.1
        $name = ucwords($name);  
        $output .= '<dt>'. $name . ': </dt>';
        $output .= '<dd>'. $value .'</dd>';           
      }
      $output .= '</dl>';
    
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
                                                    
    $output;
    $output .= '<span class="total vtprd-discount-total-label-widget" >';
    $output .= '<strong>'.$vtprd_setup_options['cartWidget_credit_total_title']. '&nbsp;</strong>';     

    $amt = vtprd_format_money_element($vtprd_cart->cart_discount_subtotal);

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

    $output;
    $output .= '<p class="total vtprd-combined-total-label-widget" >';
    $output .= '<strong>'.$vtprd_setup_options['cartWidget_new_subtotal_label'] .'&nbsp;</strong>';     

    
   //v1.0.7.5 - changed to "get_option('woocommerce_tax_display_cart')" - $woocommerce didn't have the info yet...
   if ( get_option('woocommerce_tax_display_cart') == 'excl' ) {  //v1.0.7.5
			$subtotal = $woocommerce->cart->subtotal_ex_tax ;
		} else {
			$subtotal = $woocommerce->cart->subtotal;
    }

    
    $subtotal -= $vtprd_cart->cart_discount_subtotal;

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
                                               
    $output;
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
    
    $output;

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
  
      $output .= '<span class="vtprd-discount-amtCol-checkout">' .  __('Discount Amount', 'vtprd') . '</span>';
      
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
      $output;  
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
    $output;
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


      $output;
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

      $output;
 
      $output .= '<tr class="vtprd-discount-total-' .$execType. ' vtprd-new-subtotal-line" >';
      $output .= '<td colspan="' .$vtprd_setup_options['' .$execType. '_html_colspan_value'].'" class="vtprd-discount-total-' .$execType. '-line">';
      $output .= '<div class="vtprd-discount-prodLine-' .$execType. '" >';
      
      $output .= '<span class="vtprd-discount-totCol-' .$execType. '">';
      $output .= $vtprd_setup_options['' .$execType. '_new_subtotal_label'];
      $output .= '</span>';
  
      //$subTotal = $vtprd_cart->cart_original_total_amt - $vtprd_cart->yousave_cart_total_amt;    //show as a credit
      $subTotal  = $woocommerce->cart->subtotal;
  
   //no longer used...   $subTotal -= $vtprd_cart->yousave_cart_total_amt;
      $subTotal  -= $vtprd_cart->cart_discount_subtotal;
      
      $amt = vtprd_format_money_element($subTotal);      
      
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
    $output;
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
    $output;
    
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
 
    //get the discount details    
    $output .= vtprd_email_cart_discount_rows($msgType);
     
    vtprd_load_cart_total_incl_excl(); //v1.0.7.4 
    
    if ($vtprd_setup_options['show_checkout_discount_total_line'] == 'yes') {
      if ($msgType == 'html') {
        $amt = vtprd_format_money_element($vtprd_cart->yousave_cart_total_amt);
        $amt .= vtprd_maybe_load_incl_excl_vat_lit();  //v1.0.7.4        
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

    $output;
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
       
      $output;

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
      $output;
          
      //v1.0.7.4 begin      
      $amt = vtprd_format_amt_and_adjust_for_taxes($amt, $k);  //has both formatted amount and suffix, prn
      // $amt = vtprd_format_money_element($amt); //mwn
      //v1.0.7.4 end 
         
    if ($msgType == 'html')  {
      $output .= '<tr>';

      if (sizeof($vtprd_cart->cart_items[$k]->variation_array) > 0   ) {
        $output .= '<td  class="vtprd-product-name-email" style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word"><span class="vtprd-product-name-span">' . $vtprd_cart->cart_items[$k]->parent_product_name .'</span>';
        $output .= '<small>';
       // $output .= '<dl class="variation">';
        foreach($vtprd_cart->cart_items[$k]->variation_array as $key => $value) {          
          //v1.0.7.8  begin
          // add in on-the-fly attribute name handling; remove sanitize_title
          $name  = str_replace( 'attribute_pa_', '', $key  );  //post v 2.1
          $name  = str_replace( 'attribute_', '', $key  );     //post v 2.1   for on-the-fly avariations
          $value = str_replace( 'attribute_', '', $value  );   //post v 2.1   for on-the-fly variations
          //v1.0.7.8  end
          $name = sanitize_title( str_replace( 'pa_', '', $name  ) );   //pre v 2.1
          $name = ucwords($name);  
          $output .= '<br>'. $name . ': ' .$value ;           
        }
        //$output .= '</dl></small>';
        $output .= '</small>';      			
        $output .= '</td>';     
      } else {
        $output .= '<td  class="vtprd-product-name-email" style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word">' . $vtprd_cart->cart_items[$k]->product_name .'</td>';
      }
			
      $output .= '<td class="vtprd-quantity-email" style="text-align:left;vertical-align:middle;border:1px solid #eee">' . $units .'</td>';			
      $output .= '<td class="vtprd-amount-email"  style="text-align:left;vertical-align:middle;border:1px solid #eee">' . $vtprd_setup_options['checkout_credit_detail_label'] . ' ' .$amt .'</td>';		
      $output .= '</tr>';        
    } else {
      $output .= "\n" . __( 'Product: ', 'vtprd' ); 
      $output .= "\n" . $vtprd_cart->cart_items[$k]->product_name;
      $output .= "\n" . __( ' Discount Units: ', 'vtprd' );
      $output .= "\n" . $units ;
      $output .= "\n" . __( ' Discount Amount: ', 'vtprd' ); 
      $output .= "\n" . $amt;
      $output .= "\r\n";
    }
    
    return  $output;  
 }
   
	function vtprd_email_cart_purchases_subtotal($msgType) {
    global $vtprd_cart, $woocommerce, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;   

    $output;
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

      $output;
  
      $amt = vtprd_format_money_element($vtprd_cart->yousave_cart_total_amt);
 
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

      $output;
   
      // for wpec $vtprd_cart->cart_original_total_amt is not accurate - use wpec's own routine
      //$subTotal = $vtprd_cart->cart_original_total_amt - $vtprd_cart->yousave_cart_total_amt;    //show as a credit
      
      $subTotal  = $woocommerce->cart->subtotal;
      
      vtprd_load_cart_total_incl_excl(); //v1.0.7.4 
      
      //*****************************
      //No longer used - $subTotal -= $vtprd_cart->yousave_cart_total_amt;
      //*****************************
      $subTotal -= $vtprd_cart->cart_discount_subtotal;   //may or may not contain the coupon amount, depending on passed value calling function
            
      $amt = vtprd_format_money_element($subTotal);
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
    $output;
   	
    $output .=  '<h2 id="vtprd-thankyou-title">' . __('Cart Discount Details', 'vtprd') .'<h2>';
     	
    $output .= '<table class="shop_table order_details vtprd-thankyou-table">';
    $output .= '<thead>';
    $output .= '<tr>';
    $output .= '<th class="product-name">' . __('Discount Product', 'vtprd') .'</th>';
    $output .= '<th class="product-name">' . __('Discount Amount', 'vtprd') .'</th>';					
    $output .= '</tr>';    
    $output .= '</thead>';
    
    vtprd_load_cart_total_incl_excl(); //v1.0.7.4 
    
    if (($vtprd_setup_options['show_checkout_discount_total_line'] == 'yes') || 
        ($vtprd_setup_options['checkout_new_subtotal_line']        == 'yes')) {
        $output .= '<tfoot>';
        if ($vtprd_setup_options['show_checkout_discount_total_line'] == 'yes') {
            $amt = vtprd_format_money_element($vtprd_cart->yousave_cart_total_amt);
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
       
      $output;

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
      $output;
    
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
      $output .= '<dl class="variation">';
      foreach($vtprd_cart->cart_items[$k]->variation_array as $key => $value) {          
        //v1.0.7.8  begin
        // add in on-the-fly attribute name handling; remove sanitize_title
        $name  = str_replace( 'attribute_pa_', '', $key  );  //post v 2.1
        $name  = str_replace( 'attribute_', '', $key  );     //post v 2.1   for on-the-fly variations
        $value = str_replace( 'attribute_', '', $value  );   //post v 2.1   for on-the-fly variations
        //v1.0.7.8  end
        $name = sanitize_title( str_replace( 'pa_', '', $name  ) );   //pre v 2.1
        $name = ucwords($name);  
        $output .= '<dt>'. $name . ': </dt>';
        $output .= '<dd>'. $value .'</dd>';           
      }
      $output .= '</dl>';
            			
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

    $output;
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
	function vtprd_checkout_cart_reporting() {
    global $vtprd_cart, $vtprd_cart_item, $vtprd_rules_set, $vtprd_info, $vtprd_setup_options, $woocommerce;
    $output;        
   	if (($vtprd_setup_options['show_checkout_discount_detail_lines'] == 'yes') ||  
        ($vtprd_setup_options['show_checkout_discount_total_line']   == 'yes') ||  
        ($vtprd_setup_options['checkout_new_subtotal_line']          == 'yes') ) {	
            $output .= '<table class="shop_table cart vtprd_shop_table" cellspacing="0">';
            $output .= '<thead>';
            $output .= '<tr class="checkout_discount_headings">';
            $output .= '<th  class="product-name" >' . __('Discount Product', 'vtprd') .'</th>';
            $output .= '<th  class="product-quantity">' . __('Quantity', 'vtprd') .'</th>';
            $output .= '<th  class="product-subtotal" >' . __('Discount Amount', 'vtprd') .'</th>';					
            $output .= '</tr>';    
            $output .= '</thead>';
    } 
   
    vtprd_load_cart_total_incl_excl(); //v1.0.7.4 
    
    if (($vtprd_setup_options['show_checkout_discount_total_line'] == 'yes') || 
        ($vtprd_setup_options['checkout_new_subtotal_line']        == 'yes')) { 
        $output .= '<tfoot>';
         if ($vtprd_setup_options['show_checkout_discount_total_line'] == 'yes') {
            $amt = vtprd_format_money_element($vtprd_cart->yousave_cart_total_amt);
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
            $subtotal -= $vtprd_cart->yousave_cart_total_amt;

            $amt = vtprd_format_money_element($subtotal);
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
       
      $output;

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
        }
      }

    return $output;
    
  }
     
	function vtprd_checkout_discount_detail_line($amt, $units, $msgType, $k) {  
    global $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
      $output;

      //v1.0.7.4 begin      
      $amt = vtprd_format_amt_and_adjust_for_taxes($amt, $k);  //has both formatted amount and suffix, prn
      // $amt = vtprd_format_money_element($amt);
      //v1.0.7.4 end
    
    $output .= '<tr class = "order_table_item">';

    if (sizeof($vtprd_cart->cart_items[$k]->variation_array) > 0   ) {
      $output .= '<td  class="product-name vtprd-product-name" ><span class="vtprd-product-name-span">' . $vtprd_cart->cart_items[$k]->parent_product_name .'</span>';
      
      $output .= '<dl class="variation">';

      foreach($vtprd_cart->cart_items[$k]->variation_array as $key => $value) {          
        //v1.0.7.8  begin
        // add in on-the-fly attribute name handling; remove sanitize_title
        $name  = str_replace( 'attribute_pa_', '', $key  );  //post v 2.1
        $name  = str_replace( 'attribute_', '', $key  );     //post v 2.1   for on-the-fly variations
        $value = str_replace( 'attribute_', '', $value  );   //post v 2.1   for on-the-fly variations
        //v1.0.7.8  end
        $name = sanitize_title( str_replace( 'pa_', '', $name  ) );   //pre v 2.1
        $name = ucwords($name);  
        $output .= '<dt>'. $name . ': </dt>';
        $output .= '<dd>'. $value .'</dd>';           
      }
      $output .= '</dl>';
            			
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

    $output;
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
          $post = get_post($rule_id);
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
          $output  .= '&nbsp;' . $post->post_title;
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
//echo 'at get session time, product id= ' .$product_id . '<br>';     
    } else {
      $vtprd_info['product_session_info'] = array();
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
      if ( ( ($current_time_in_seconds - $vtprd_info['product_session_info']['session_timestamp_in_seconds']) > '3600' ) ||     //session data older than 60 minutes
           (  $user_role != $vtprd_info['product_session_info']['user_role']) ) {    //current user role not the same as prev
        vtprd_apply_rules_to_single_product($product_id, $price);
        //reset user role info, in case it changed
        $vtprd_info['product_session_info']['user_role'] = $user_role;
      }         
    } else { 
       //First time obtaining the info, also moves the data to $vtprd_info       
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
      if ( ( ($current_time_in_seconds - $vtprd_info['product_session_info']['session_timestamp_in_seconds']) > '3600' ) ||     //session data older than 60 minutes
           (  $user_role != $vtprd_info['product_session_info']['user_role']) ) {    //current user role not the same as prev
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
      if ( ( ($current_time_in_seconds - $vtprd_info['product_session_info']['session_timestamp_in_seconds']) > '3600' ) ||     //session data older than 60 minutes
           (  $user_role != $vtprd_info['product_session_info']['user_role']) ) {    //current user role not the same as prev
        vtprd_apply_rules_to_single_product($product_id, $price);
        //reset stored role to current
        $vtprd_info['product_session_info']['user_role'] = $user_role;        
      }        
    } else { 
       //First time obtaining the info, also moves the data to $vtprd_info       
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
      $next_id; //supply null value for use with autoincrement table key
 
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

    if ($k) {   //routine can be called without '$k' value
       if  ( !$vtprd_cart->cart_items[$k]->product_is_taxable )  {  
         return vtprd_format_money_element($amt);
       }
    }
    
    //at a minimum, format $amt
    if ( ( get_option( 'woocommerce_calc_taxes' ) == 'no' ) ||
         ( $woocommerce->customer->is_vat_exempt() ) ) {  
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
     
    return $amt;
  }
  
  
  //****************************************
  //v1.0.7.4 new function
  //from woocommerce/includes/abstracts/abstract-wc-product.php
  //****************************************
  function vtprd_get_price_including_tax($product_id, $discount_price){ 
    global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_setup_options, $vtprd_info; 

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

				if ( ! empty( $woocommerce->customer ) && $woocommerce->customer->is_vat_exempt() ) {   //v1.0.7.5

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
    If ( get_role( $wholesale_role_name ) ) {
       return;
    }
  
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
        
    return;
  }  
  
  
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
    if ( get_option( 'woocommerce_calc_taxes' )  == 'yes' ) {
       switch (get_option('woocommerce_prices_include_tax')) {
          case 'yes':
              if (get_option('woocommerce_tax_display_cart')   == 'excl') {
                 $excl_vat_lit .= ' <small>' . $woocommerce->countries->ex_tax_or_vat() . '</small>';  //v1.0.7.5
              }   
             break;         
          case 'no':
              if (get_option('woocommerce_tax_display_cart')   == 'incl') {
                 $vtprd_cart->yousave_cart_total_amt =  $vtprd_cart->yousave_cart_total_amt_incl_tax;   
              }           
             break;
       }          
    }    

 
    return;
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