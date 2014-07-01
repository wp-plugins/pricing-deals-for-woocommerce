<?php

class VTPRD_Apply_Rules{
	
	public function __construct(){
		global $woocommerce, $vtprd_cart, $vtprd_rules_set, $vtprd_info, $vtprd_setup_options, $vtprd_rule;
//echo '<br>AT TOP of APPLY RULES<br>' ; //mwnecho  
    //GET RULES SET     
    $vtprd_rules_set = get_option( 'vtprd_rules_set' );
    if ($vtprd_rules_set == FALSE) {
      return;
    }

    if ($vtprd_info['current_processing_request'] == 'cart') {  
 /* FIX FIX FIX
      //If Cart processing and nothing in the cart, exit...
      if (sizeof($woocommerce->cart_items) == 0) {
        return;
      } 
  */   
      //sort for "cart" rules and delete "display" rules
      $this->vtprd_sort_rules_set_for_cart();
            
      //after sort for cart/remove display rows, are there rows left?
      if ( sizeof($vtprd_rules_set) == 0) {
        return;
      } 
      
      //**********************
      /*  At top of routine to set a coupon discount baseline as relevant
        (b) if we're on the checkout page, and a coupon has been added/removed
        (c) if an auto-add is in the cart (which should really be skipped), it doesn't matter, it'll get picked up and corrected in the  maybe_update_parent_cart_for_autoAdds function
        (d) new coupon behavior:  With an auto add, "apply with coupons" is required
            and the Coupon will ALWAYS be skipped instead of the rule.  this is accomplished by re-running the vtprd_maybe_compute_coupon_discount function again
            (i) after the previous auto adds have been rolled out and
            (ii) before any new auto adds are rolled in 
      */
      vtprd_count_other_coupons();
      //**********************
               
     //Move parent cart contents to vtprd_cart 
      vtprd_load_vtprd_cart_for_processing(); 


      //autoAdds into internal arrays, as needed 
      //EDITED * + * +  * + * +  * + * +  * + * +  
    
      $this->vtprd_process_cart(); 
   
      
      //autoAdds  Update the parent cart for any auto add free products...
      //EDITED * + * +  * + * +  * + * +  * + * + 

    } else {
      
      //sort for "display" rules and delete "cart" rules
      $this->vtprd_sort_rules_set_for_display();
      
      //after sort for display/remove cart rows, are there rows left?
      if ( sizeof($vtprd_rules_set) == 0) {
        return;
      } 
            
      // **********************************************************  
      //  This path is for display rules only, where a SINGLE product
      //     has been loaded into the cart to test for a Display discount
      // **********************************************************       
      $this->vtprd_process_cart();
                 
    }  

    if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){   
      error_log( print_r(  '$vtprd_info', true ) );
      error_log( var_export($vtprd_info, true ) );
      session_start();    //mwntest
      error_log( print_r(  '$_SESSION', true ) );
      error_log( var_export($_SESSION, true ) );
      error_log( print_r(  '$vtprd_rules_set', true ) );
      error_log( var_export($vtprd_rules_set, true ) );
      error_log( print_r(  '$vtprd_cart', true ) );
      error_log( var_export($vtprd_cart, true ) );
      error_log( print_r(  '$vtprd_setup_options', true ) );
      error_log( var_export($vtprd_setup_options, true ) ); 
      $woocommerce_cart_contents = $woocommerce->cart->get_cart();
      error_log( print_r(  '$woocommerce', true ) );
      error_log( var_export($woocommerce, true ) );    
    }
    
    return;      
	}
 

  public function vtprd_process_cart() { 
    global $post, $vtprd_setup_options, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info;	
//echo 'in vtprd_process_cart' .'<br>';
    //cart may be empty...
    if (sizeof($vtprd_cart) == 0) {
      $vtprd_cart->cart_level_status = 'rejected';
      $vtprd_cart->cart_level_auditTrail_msg = 'No Products in the Cart.';
      return;
    }
    
    //v1.0.7.4 begin
    if ( ($vtprd_info['current_processing_request'] == 'cart') &&
         ($vtprd_info['skip_cart_processing_due_to_coupon_individual_use']) )  {
      $vtprd_cart->cart_level_status = 'rejected';
      $vtprd_cart->cart_level_auditTrail_msg = 'Another Coupon with Individual_use = "yes" has been activated.  Cart processing may not continue.';          
      return;
    }
    //v1.0.7.4 end
        
    //test all rules for inPop and actionPop participation 
    $vtprd_cart->at_least_one_rule_actionPop_product_found = 'no';
    //
    $this->vtprd_test_cart_for_rules_populations();
    //        

   if ($vtprd_cart->at_least_one_rule_actionPop_product_found != 'yes') {
      $vtprd_cart->cart_level_status = 'rejected';
      $vtprd_cart->cart_level_auditTrail_msg = 'No actionPop Products found.  Processing ended.';     
      return;
   } 
    
    /* if price or template code request (display), there's only one product in the cart for the call
       if either of these conditions exist:
          no display rules found
          or product does not participate in a display rule
            product_in_rule_allowing_display will be 'no'      
    */
    if ( ($vtprd_info['current_processing_request'] == 'display') &&
         ($vtprd_cart->cart_items[0]->product_in_rule_allowing_display == 'no') )  {
      $vtprd_cart->cart_level_status = 'rejected';
      $vtprd_cart->cart_level_auditTrail_msg = 'A single product "Display" request sent, product not in any Display rule.  Processing ended.';          
      return;
    }

    //test all rules whether in and out counts satisfied    
    $this->vtprd_process_cart_for_rules_discounts();

    return;
 }   

  //************************************************
  //Load inpop found list and actionopop found list
  //************************************************
  public function vtprd_test_cart_for_rules_populations() { 
    global $post, $vtprd_setup_options, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info;
     
     
    //************************************************
    //BEGIN processing to mark product as participating in the rule or not...
    //************************************************
    
    /*  Analyze each rule, and load up any cart products found into the relevant rule
        fill rule array with product cart data :: load inPop info 
    */  

    //************************************************
    //FIRST PASS:
    //    - does the product participate in either inPop or actionPop 
    //************************************************
    $sizeof_rules_set = sizeof($vtprd_rules_set);
    for($i=0; $i < $sizeof_rules_set; $i++) {                                                               

      //pick up existing invalid rules
      if ( $vtprd_rules_set[$i]->rule_status != 'publish' ) { 
        continue;  //skip out of this for loop iteration
      } 
      
      $this->vtprd_manage_shared_rule_tests($i);      

            // test whether the product participates in either inPop or actionPop
      if ( $vtprd_rules_set[$i]->rule_status != 'publish' ) { 
          continue;  //skip out of this for loop iteration
      } 

      
      
      //****************************************************
      // ONLY FOR AUTO ADD - overwrite actionPop and discountAppliesWhere
      //******************
      //  - timing of this overwrite is different for auto adds...
      //  - NON auto adds are done below
      //**************************************************** 
  
      //EDITED * + * +  * + * +  * + * +  * + * 
      
      
       
      //Cart Main Processing
      $sizeof_cart_items = sizeof($vtprd_cart->cart_items);
      for($k=0; $k < $sizeof_cart_items; $k++) {                 

        //only do this check if the product is on special!!
        if ($vtprd_cart->cart_items[$k]->product_is_on_special == 'yes')  { 
          $do_continue = '';  //v1.0.4 set = to ''
          switch( $vtprd_rules_set[$i]->cumulativeSalePricing) {
            case 'no':              
                //product already on sale, can't apply further discount
                $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_status'] = 'rejected';
                $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] =  'No Discount - product already on sale, can"t apply further discount - discount in addition to sale pricing not allowed';
                $do_continue = 'yes';                  
              break;
            case 'addToSalePrice':               
               //just act naturally, apply the discount to the price we find, which is already the Sale Price...
              break;
            case 'replaceSalePrice':     //ONLY applies if discount is greater than sale price!!!!!!!!
                /*  **********************************************************
                  At this point in time, unit and db_unit both contain the Sale Price,
                  Overwrite the sale price with the list price, process as normal, then check at the bottom...
                  if the discount is <= the existing sale price, DO NOT APPLY AS DISCOUNT!
                  ********************************************************** */ 
                $vtprd_cart->cart_items[$k]->unit_price     = $vtprd_cart->cart_items[$k]->db_unit_price_list;
                $vtprd_cart->cart_items[$k]->db_unit_price  = $vtprd_cart->cart_items[$k]->db_unit_price_list;               
              break;
          } //end cumulativeSalePricing check
                   
          if ($do_continue) {
            continue; //skip further processing for this iteration of the "for" loop
          }
        }  //end product is on special check

        //set up cart audit trail info, keyed to rule prod_id
        $this->vtprd_init_cartAuditTrail($i,$k);
        
        
        //does product participate in inPop
        $this->vtprd_test_if_inPop_product($i, $k);       
    
    
        $this->vtprd_test_if_actionPop_product($i, $k);                                                            


      } //end cart-items 'for' loop

/* 
      //mwn0402  => moved from outside the loop, inside!! 
      //****************************************************
      // ONLY FOR non- AUTO ADD - overwrite actionPop and discountAppliesWhere
      //******************
      //  - timing of this overwrite is different for auto adds...
      //  - auto adds are done ABOVE
      //****************************************************      
      if ($vtprd_rules_set[$i]->rule_contains_auto_add_free_product != 'yes') {     
        //****************************************************
        //overwrite actionPop and discountAppliesWhere as appropriate 
        //**************************************************** 
        switch( $vtprd_rules_set[$i]->inPop ) {
          case 'wholeStore':
          case 'cart':        //in these cases, inpop/actionpop treated as 'sameAsInPop'                                                                               
              if ( ($vtprd_rules_set[$i]->actionPop == 'sameAsInPop') ||              
                   ($vtprd_rules_set[$i]->actionPop == 'wholeStore') ||
                   ($vtprd_rules_set[$i]->actionPop == 'cart') ) {
                $vtprd_rules_set[$i]->actionPop = 'sameAsInPop';
                $vtprd_rules_set[$i]->discountAppliesWhere =  'nextInInPop' ;
              }   
            break; 
        }  
      }
*/
    
    }  //end rules 'for' loop
   
    
      return;   
   }                              
 
        
   public function vtprd_manage_shared_rule_tests($i) { 

      global $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_setup_options;
    
      $rule_is_date_valid = vtprd_rule_date_validity_test($i);
      if (!$rule_is_date_valid) {
         $vtprd_rules_set[$i]->rule_status = 'dateInvalid';  //temp chg of rule_status for this execution only
         $vtprd_rules_set[$i]->rule_processing_status = 'Cart Transaction does not fall within date boundaries set for the rule.';
      }

      //IP is immediately available, check against Lifetime limits
      //  check against all rules
  
      //EDITED * + * +  * + * +  * + * +  * + * +       
    
      //don't run if 'no'
      if ( ($vtprd_rules_set[$i]->cumulativeCouponPricing == 'no') && ($vtprd_rules_set[0]->coupons_amount_without_rule_discounts > 0) ) {
           $vtprd_rules_set[$i]->rule_status = 'cumulativeCouponPricingNo';  //temp chg of rule_status for this execution only
           $vtprd_rules_set[$i]->rule_processing_status = 'Coupon presented, rule switch says do not run.';                
      }      
   
   } 
   
  // ****************  
  // inPop TESTS
  // ****************     
        
   public function vtprd_test_if_inPop_product($i, $k) { 
      global $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_setup_options;

      switch( $vtprd_rules_set[$i]->inPop ) {  
           case 'wholeStore':                                                                                      
                //load whole cart into inPop
                $this->vtprd_load_inPop_found_list($i, $k);                              
            break;
          case 'cart':                                                                                      
                //load whole cart into inPop               
                $this->vtprd_load_inPop_found_list($i, $k);                              
            break;
          
          //EDITED * + * +  * + * +  * + * +  * + * +
      
      }
      
    } 


   // **************** 
  // actionPop TESTS        
  // **************** 
           
   public function vtprd_test_if_actionPop_product($i, $k) { 
      global $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_setup_options;

      switch( $vtprd_rules_set[$i]->actionPop ) {  
          case 'sameAsInPop':
                //if current product in inpop products array...
                if ( in_array($vtprd_cart->cart_items[$k]->product_id, $vtprd_rules_set[$i]->inPop_prodIds_array) ) {
                  $this->vtprd_load_actionPop_found_list($i, $k);
                } else {
                  $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['actionPop_participation_msgs'][] = 'Product not found in inpop list, so not included on actionPop';
                }
            break;
          case 'wholeStore':
                $this->vtprd_load_actionPop_found_list($i, $k);
            break;            
          case 'cart':                                                                                      
                //load whole cart into actionPop
                $this->vtprd_load_actionPop_found_list($i, $k);
            break;
          
          //EDITED * + * +  * + * +  * + * +  * + * +
          
        } 
    } 



  public function vtprd_process_cart_for_rules_discounts() {
    global $post, $vtprd_setup_options, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info;      
    //************************************************
    //SECOND PASS - have the inPop, output and rule conditions been met
    //************************************************
    $sizeof_rules_set = sizeof($vtprd_rules_set);
    for($i=0; $i < $sizeof_rules_set; $i++) {         
      if ( $vtprd_rules_set[$i]->rule_status != 'publish' ) {          
        continue;  //skip the rest of this iteration, but keep the "for" loop going
      }

      //THIS WOULD ONLY BE A MESSAGE REQUEST AT DISPLAY TIME for a single product on a Cart rule      
      if ($vtprd_info['current_processing_request'] == 'display') {  
          if ($vtprd_rules_set[$i]->rule_execution_type == 'cart') {
            $vtprd_info['product_session_info']['product_rule_short_msg_array'][] = $vtprd_cart->cart_items[0]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['rule_short_msg'];
            $vtprd_info['product_session_info']['product_rule_full_msg_array'][]  = $vtprd_cart->cart_items[0]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['rule_full_msg'];
            $vtprd_cart->cart_items[0]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_status'] = 'MessageRequestCompleted';
            $vtprd_cart->cart_items[0]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] =  'Display Message for Cart rule successfully sent back.';           
            continue;  //skip the rest of this iteration, but keep the "for" loop going
          }
      } 

      //no point in continuing of no actionpop to discount for this rule...
      if ( sizeof($vtprd_rules_set[$i]->actionPop_found_list) == 0 ) {
       // $vtprd_rules_set[$i]->rule_requires_cart_action = 'no';
        $vtprd_rules_set[$i]->rule_processing_status = 'No action population products found for this rule.';
        continue;   
      }      
      //reset inPop running totals for each rule iteration
      $vtprd_rules_set[$i]->inPop_group_begin_pointer     = 1; //begin with 1st iteration
      $vtprd_rules_set[$i]->inPop_exploded_group_begin   = 0;
      $vtprd_rules_set[$i]->inPop_exploded_group_end     = 0;

      //reset actionPop running totals => they will aways reflect the inPop, unless using different actionPop
      $vtprd_rules_set[$i]->actionPop_group_begin_pointer     = 1;  //begin with 1st iteration
      $vtprd_rules_set[$i]->actionPop_exploded_group_begin   = 0;  
      $vtprd_rules_set[$i]->actionPop_exploded_group_end     = 0; 

    /* ******************
     PROCESS CART FOR DISCOUNT: group within rule until: info lines done / processing completed / inpop ended
     ********************* */       
      
      //Overriding Control Status Switch Setup
      $vtprd_rules_set[$i]->discount_processing_status = 'inProcess'; // inProcess / completed /  InPopEnd
     // $vtprd_rules_set[$i]->end_of_actionPop_reached = 'no';   

      // ends with sizeof being reached, OR  $vtprd_rules_set[$i]->discount_processing_status == 'yes'
      $sizeof_rule_deal_info = sizeof($vtprd_rules_set[$i]->rule_deal_info);
      for($d=0; $d < $sizeof_rule_deal_info; $d++) {
       switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_repeat_condition'] ) {
            case 'none':     //only applies to 1st rule deal line
                 /* 
                 There can be multiple conditions which are covered by inserting a repeat count = 1.
                 Most often, the rule applies to the entire actionPop.  If that is the case, the 
                 actionPop Loop will run through the whole actionPop in one go, to process all of the 
                 discounts.  This is a hack, as it really should be governed here.                 
                 */
                $buy_repeat_count = 1;
              break;
            case 'unlimited':   //only applies to 1st rule deal line
                $buy_repeat_count = 999999;
              break;
            case 'count':     //can only occur when there's only one rule deal line
                $buy_repeat_count = $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_repeat_count'];
              break;  
        }  

        //REPEAT count only augments IF a discount successfully processes...
        for($br=0; $br < $buy_repeat_count; $br++) {
           $this->vtprd_repeating_group_discount_cntl($i, $d, $br );             
           if ($vtprd_rules_set[$i]->discount_processing_status != 'inProcess') { 
             break; // exit repeat for loop
           }                     
        } // $buy_repeat_count for loop        
    
        //v1.0.4 begin => lifetime counted by group (= 'all') up count here, once per rule/cart
            //EDITED * + * +  * + * +  * + * +  * + * + 
        //v1.0.4 end 
               
      }  //rule_deal_info for loop
       
      
     /*  THIS IS ONLY NECESSARY IN WPEC, NOT WOO,
         as in woo the adds haven't happened yet - nothing to roll out in this situation...
      
      //***********************************************************
      // If a product was auto inserted for a free discount, but does *not*
      //     receive that discount,
      //   Roll the auto-added product 'UNfree' qty out of the all of the rules actionPop array
      //      AND out of vtprd_cart, removing the product entirely if necessary.
      //***********************************************************
      if ( ($vtprd_rules_set[$i]->rule_contains_auto_add_free_product == 'yes') &&
           (sizeof($vtprd_rules_set[$i]->auto_add_inserted_array) > 0) )  {        
        $this->vtprd_maybe_roll_out_auto_inserted_products($i); 
      }     
      
      */
      
      //***********************************************************
      // If a product has been given a 'Free' discount, it can't get
      //     any further discounts.
      //   Roll the product 'free' qty out of the rest of the rules actionPop array
      //***********************************************************
      if (sizeof($vtprd_rules_set[$i]->free_product_array) > 0) {
        $this->vtprd_roll_free_products_out_of_other_rules($i); 
      }
      
      
    }  //ruleset for loop
    return;    
  }

  //$i = rule index, $d = deal index, $br = repeat index
  //***********************************************************
  // Take a Single BUY group all the way through the discount process,
  //     Performed by  REPEAT NUM  within DEAL LINE within RULE
  //***********************************************************
  public function vtprd_repeating_group_discount_cntl($i, $d, $br) {
    global $post, $vtprd_setup_options, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_template_structures_framework, $vtprd_deal_structure_framework;        
            
    //initialize rule_processing_trail(
    $vtprd_rules_set[$i]->rule_processing_trail[] = $vtprd_deal_structure_framework;
    $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupBeingTested';

    // previously determined template key
    $templateKey = $vtprd_rules_set[$i]->rule_template; 
   
    //if buy_amt_type is active and there is a buy_amt count...
    //***********************************************************
    //THIS SETS THE SIZE OF THE BUY exploded GROUP "WINDOW"
    //***********************************************************
    // Initialize the amt qty as needed
    if ($vtprd_template_structures_framework[$templateKey]['buy_amt_type'] > ' ' ) { 
      if ( ($vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_type'] == 'none' ) ||  
           ($vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_type'] == 'one' ) ) {
         $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_type'] = 'quantity';
         $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count'] = 1;
         if ($vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_applies_to']  <= ' ') {
           $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_applies_to']  = 'all';
         }
      }
    } else {
       $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_type'] = 'quantity';
       $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count'] = 1;
       $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_applies_to']  = 'all';
    }    
    
    // INPOP_EXPLODED_GROUP_BEGIN setup
    if ($br == 0) { //is this the 1st time through the buy repeat?  
      $vtprd_rules_set[$i]->inPop_exploded_group_begin = 0;
    } else {    //if 2nd-nth time      
       switch ($vtprd_rules_set[$i]->discountAppliesWhere) {
        case 'allActionPop':        //process all actionPop in one go , 'allActionPop'
        case 'inCurrentInPopOnly':  //treats inpop group as a unit, so we get the next inpop group unit                    
        case 'nextInActionPop':     //FOR all 3 values, add 1 to end            
            $vtprd_rules_set[$i]->inPop_exploded_group_begin = $vtprd_rules_set[$i]->inPop_exploded_group_end;// + 1;
          break;
        case 'nextInInPop':   //we're bouncing between inpop and actionpop, so use actionPop end + 1 here:
            $vtprd_rules_set[$i]->inPop_exploded_group_begin = $vtprd_rules_set[$i]->actionPop_exploded_group_end;// + 1;
          break;     
      }
    }

    //*************************************************************
    //1st pass through data, set the begin/end pointers, 
    // verify 'buy' conditions met
    //*************************************************************
    $this->vtprd_set_buy_group_end($i, $d, $br );     //vtprd_buy_amt_process   
    
    //if buy amt process failed, exit
    if ($vtprd_rules_set[$i]->rule_processing_status == 'cartGroupFailedTest') {
      //if buy criteria not met, discount processing for rule is done
      $vtprd_rules_set[$i]->discount_processing_status = 'InPopEnd';
      return;
    } 

    //***************
    //ACTION area
    //***************
    switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['action_repeat_condition'] ) {
      case 'none':     //only one rule deal line
          $action_repeat_count = 1;
        break;
      case 'unlimited':   //only one rule deal line
          $action_repeat_count = 999999;
        break;
      case 'count':     //only one rule deal line
          $action_repeat_count = $vtprd_rules_set[$i]->rule_deal_info[$d]['action_repeat_count'];
        break;  
    } 
    
    for($ar=0; $ar < $action_repeat_count; $ar++) {
       $this->vtprd_process_actionPop_and_discount($i, $d, $br, $ar );                 
       if ($vtprd_rules_set[$i]->discount_processing_status != 'inProcess')  {         
         break; //break out of  for loop
       }                             
    } // end $action_repeat_count for loop  
                                                                
  }
 
  public function vtprd_process_actionPop_and_discount($i, $d, $br, $ar ) {      
    global $post, $vtprd_setup_options, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_template_structures_framework;        
          
    $templateKey = $vtprd_rules_set[$i]->rule_template;

    if ($vtprd_template_structures_framework[$templateKey]['action_amt_type'] > ' ' ) { 
      if ( ($vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] == 'none' ) ||  
          ($vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] == 'one' ) ) {
        $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] = 'quantity';
        $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count'] = 1;
        if ($vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_applies_to'] <= ' ') {
           $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_applies_to']  = 'all';
        }
      }
    } else {
        $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] = 'quantity';
        $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count'] = 1;
        $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_applies_to']  = 'all';
    }

   //ACTIONPOP_EXPLODED_GROUP BEGN AND END  SETUP
   switch( $vtprd_rules_set[$i]->discountAppliesWhere  ) {     // 'allActionPop' / 'inCurrentInPopOnly'  / 'nextInInPop' / 'nextInActionPop' / 'inActionPop' /
      case 'allActionPop':
          //process all actionPop in one go
          $vtprd_rules_set[$i]->actionPop_exploded_group_begin = 0;
          $vtprd_rules_set[$i]->actionPop_exploded_group_end   = sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list);
        break;
      case 'inCurrentInPopOnly':
          if ($vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] == 'zero' ) {  //means we are acting on the already-found 'buy' unit
            $vtprd_rules_set[$i]->actionPop_exploded_group_begin = $vtprd_rules_set[$i]->inPop_exploded_group_end - 1;   //end - 1 gets the nth, as well as the direct hit...       
          } else {          
            //always the same as inPop pointers
            $vtprd_rules_set[$i]->actionPop_exploded_group_begin = $vtprd_rules_set[$i]->inPop_exploded_group_begin;
          }
        //$vtprd_rules_set[$i]->actionPop_exploded_group_end   = $vtprd_rules_set[$i]->inPop_exploded_group_end;   //v1.0.3 
          $vtprd_rules_set[$i]->actionPop_exploded_group_end   = sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list);    //v1.0.3               
        break;  
      case 'nextInInPop':   
          if ($vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] == 'zero' ) {  //means we are acting on the already-found 'buy' unit
            $vtprd_rules_set[$i]->actionPop_exploded_group_begin = $vtprd_rules_set[$i]->inPop_exploded_group_end - 1;   //end - 1 gets the nth, as well as the direct hit...
            $vtprd_rules_set[$i]->actionPop_exploded_group_end   = $vtprd_rules_set[$i]->inPop_exploded_group_end;         
          } else {
            if ($ar > 0) { //if 2nd - nth actionPop repeat, use the previous actionPop group end to begin the next group
              $vtprd_rules_set[$i]->actionPop_exploded_group_begin = $vtprd_rules_set[$i]->actionPop_exploded_group_end;
            } else {
              $vtprd_rules_set[$i]->actionPop_exploded_group_begin = $vtprd_rules_set[$i]->inPop_exploded_group_end;// + 1;                   
            } 
   
            //SETS action amt "window" for the actionPop_exploded_group
            $this->vtprd_set_action_group_end($i, $d, $ar );  //vtprd_action_amt_process 
          }
        break;  
      case 'nextInActionPop':         
          //first time actionPop_exploded_group_end arrives here = 0...
          if (($br > 0) ||    //if 2nd to nth buy repeat or actionpop repeat, , use the previous actionPop group end to begin the next group
              ($ar > 0)) { 
            $vtprd_rules_set[$i]->actionPop_exploded_group_begin = $vtprd_rules_set[$i]->actionPop_exploded_group_end;// + 1;
          } 
          // first time through,  $vtprd_rules_set[$i]->actionPop_exploded_group_begin = 0;
            
          //SETS action amt "window" for the actionPop_exploded_group
          $this->vtprd_set_action_group_end($i, $d, $ar );  //vtprd_action_amt_process      

        break;   
    } 
    
    //only possible if  vtprd_set_action_group_end  executed
    if ($vtprd_rules_set[$i]->rule_processing_status == 'cartGroupFailedTest') {
      //THIS PATH can either end processing for the rule, or just this iteration of actionPop processing, based on settings in set_action_group...    
      $vtprd_rules_set[$i]->discount_processing_status = 'InPopEnd';
      return;
    }         

    //************************************************
    //************************************************
    //     PROCESS DISCOUNTS                             
    //************************************************
    //************************************************
    /*
     Do we treat the actionPop as a group or as individuals ?
        Requires group analysis:
          *least expensive
          *most expensive
          *forThePriceOf units
          *forThePriceOf currency        
        Can be applied to the group or individually (each/all)
          *currency discount
          *percentage discount
        Can only be applied to individual products
          *free
          *fixed price                        
    */
    switch( true ) {
      case ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'forThePriceOf_Units') :
      case ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'forThePriceOf_Currency') :
      case ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'cheapest') :    //can only be 'each'
      case ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'most_expensive') :   //can only be 'each'
        
        //v1.0.7.2 begin
          //reset the action group pointers to be = to buy group pointers, so actionpop doesn't count whole group at once...  so whatever group count the buy group as set, we do here.
          if ($vtprd_rules_set[$i]->discountAppliesWhere == 'inCurrentInPopOnly') {  //'inCurrentInPopOnly' = 'discount this one'
              $vtprd_rules_set[$i]->actionPop_exploded_group_begin = $vtprd_rules_set[$i]->inPop_exploded_group_begin; 
              $vtprd_rules_set[$i]->actionPop_exploded_group_end   = $vtprd_rules_set[$i]->inPop_exploded_group_end;
          }
          $this->vtprd_apply_discount_as_a_group($i, $d, $ar );       
        break;
        //v1.0.7.2 end  
              
      case ( ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'all') && ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'currency') ):
      case ( ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'all') && ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'percent') ):  //v1.0.7.4   floating point fix...
          $this->vtprd_apply_discount_as_a_group($i, $d, $ar );       
        break;
      
      case ( ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'free')       && ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'each') ):   //can only be 'each'
      case ( ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'fixedPrice') && ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'each') ):   //can only be 'each'  
      case ( ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'currency')   && ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'each') ):
      case ( ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'percent')    && ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'each') ):    //v1.0.7.4
          $this->vtprd_apply_discount_to_each_product($i, $d, $ar );       
        break;
    } 
 

    $sizeof_actionpop_list = sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list); //v 1.0.3
    if ( ($vtprd_rules_set[$i]->actionPop_exploded_group_end >= $sizeof_actionpop_list ) || 
         ($ar >= ($sizeof_actionpop_list) ) ||  //v1.0.3 exit if infinite repeat  
         ($vtprd_rules_set[$i]->end_of_actionPop_reached == 'yes') ) {
       $vtprd_rules_set[$i]->discount_processing_status = 'InPopEnd';

    } else {
      switch ($vtprd_rules_set[$i]->discountAppliesWhere)  {
        case 'allActionPop':
           $vtprd_rules_set[$i]->discount_processing_status = 'InPopEnd'; //all done - process all actionPop in one go  
          break;
        case 'inCurrentInPopOnly':              
        case 'nextInInPop':       
        case 'nextInActionPop':
            $vtprd_rules_set[$i]->actionPop_repeat_activity_completed = 'yes';  //action completed, then allow the repeat to control the discount action
          break;          
      }    
    }
            
  } // end  vtprd_process_actionPop_and_discount

 
  public function vtprd_apply_discount_to_each_product($i, $d, $ar ) {  
     global $post, $vtprd_setup_options, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_template_structures_framework;        

     //if we're doing action nth processing, only the LAST product in the list gets the discount.
     if ($vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] == 'nthQuantity') {
       $each_product_group_begin = $vtprd_rules_set[$i]->actionPop_exploded_group_end - 1;
     } else {
       $each_product_group_begin = $vtprd_rules_set[$i]->actionPop_exploded_group_begin;
     }
          
     for( $s=$each_product_group_begin; $s < $vtprd_rules_set[$i]->actionPop_exploded_group_end; $s++) {
        $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_discount_amt'] = $this->vtprd_compute_each_discount($i, $d, $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_unit_price']);
        $curr_prod_array = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s];
        $curr_prod_array['exploded_group_occurrence'] = $s; 
        $this->vtprd_upd_cart_discount($i, $d, $curr_prod_array);
        //just in case...
        if ($s >= sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list)) {
          $vtprd_rules_set[$i]->discount_processing_status = 'InPopEnd';
          return;
        }  
     } 
    //at this point we may have processed all of actionPop in one go, so we set the end switch
     
     return; 
  }
 
  public function vtprd_apply_discount_as_a_group($i, $d, $ar ) {   
     global $post, $vtprd_setup_options, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_template_structures_framework;        
    $prod_discount = 0;    
   
    switch( true ) {
      case ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'forThePriceOf_Units') :
         // buy 5 ( $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count'] ) 
         // get 5   ( $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count']; )
         // FOR THE PRICE OF           
         // 4 ( $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_for_the_price_of_count'] )
         
         //add unit prices together
         $cart_group_total_price = 0;
         for ( $s=$vtprd_rules_set[$i]->actionPop_exploded_group_begin; $s < $vtprd_rules_set[$i]->actionPop_exploded_group_end; $s++) {
            $cart_group_total_price += $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_unit_price'];
         }      
       if ($vtprd_rules_set[$i]->rule_template == 'C-forThePriceOf-inCart') {  //buy-x-action-forThePriceOf-same-group-discount
           $forThePriceOf_Divisor = $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count'];
        } else {
           $forThePriceOf_Divisor = $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count'];
        }

        //divide by total by number of units = average price
        $cart_group_avg_price = $cart_group_total_price / $forThePriceOf_Divisor;

        //multiply average price * # of forthepriceof units = new group price
        $new_total_price = $cart_group_avg_price * $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count'];

        $total_savings = $cart_group_total_price - $new_total_price;

        //per unit savings = new total / group unit count => by Buy group or Action Group
        //$per_unit_savings = $total_savings / $forThePriceOf_Divisor;

        //compute remainder
        //$per_unit_savings_2decimals = bcdiv($total_savings , $forThePriceOf_Divisor , 2); 
        $per_unit_savings_2decimals = round( ($total_savings / $forThePriceOf_Divisor) , 2);    
        $running_total =  $per_unit_savings_2decimals * $forThePriceOf_Divisor;
       
        
        //$remainder = $total_savings - $running_total;
        $remainder = round($total_savings - $running_total, 2); //v1.0.7.4   floating point...

        if ($remainder <> 0) {      //v1.0.7.2 changed > 0 to <>0 ==>> pick up positive or negative rounding error
          $add_a_penny_to_first = $remainder;
        } else {
          $add_a_penny_to_first = 0;
        }

       
        //apply the per unit savings to each unit       
        for ( $s=$vtprd_rules_set[$i]->actionPop_exploded_group_begin; $s < $vtprd_rules_set[$i]->actionPop_exploded_group_end; $s++) {
            $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_discount_amt'] = $per_unit_savings_2decimals;
            
            //if first occurrence, add in penny if remainder calc produced one
            if ($s == $vtprd_rules_set[$i]->actionPop_exploded_group_begin) {
               $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_discount_amt'] += $add_a_penny_to_first;
            }
            
            $curr_prod_array = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s];
            $curr_prod_array['exploded_group_occurrence'] = $s;
            $this->vtprd_upd_cart_discount($i, $d, $curr_prod_array);
         } 

        break;
      
      case ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'forThePriceOf_Currency') :

         // buy 5 ( $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count'] ) 
         // get 5   ( $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count']; )
         // FOR THE PRICE OF           
         // 4 ( $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_for_the_price_of_count'] )
         
         //add unit prices together
         $cart_group_total_price = 0;
         for ( $s=$vtprd_rules_set[$i]->actionPop_exploded_group_begin; $s < $vtprd_rules_set[$i]->actionPop_exploded_group_end; $s++) {
            $cart_group_total_price += $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_unit_price'];
         }      

       if ($vtprd_rules_set[$i]->rule_template == 'C-forThePriceOf-inCart') {  //buy-x-action-forThePriceOf-same-group-discount
           $forThePriceOf_Divisor = $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count'];
        } else {
           $forThePriceOf_Divisor = $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count'];
        }

        $cart_group_new_fixed_price = $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count'];
        
        $total_savings = $cart_group_total_price - $cart_group_new_fixed_price;

        //compute remainder
        //$per_unit_savings_2decimals = bcdiv($total_savings , $forThePriceOf_Divisor , 2); 
        $per_unit_savings_2decimals = round( ($total_savings / $forThePriceOf_Divisor) , 2);    
        $running_total =  $per_unit_savings_2decimals * $forThePriceOf_Divisor;
      
        //$remainder = $total_savings - $running_total;
        $remainder = round($total_savings - $running_total, 2); //v1.0.7.4   floating point...
        
        if ($remainder <> 0) {      //v1.0.7.2 changed > 0 to <>0 ==>> pick up positive or negative rounding error
          $add_a_penny_to_first = $remainder;
        } else {
          $add_a_penny_to_first = 0;
        }
        
        //apply the per unit savings to each unit       
        for ( $s=$vtprd_rules_set[$i]->actionPop_exploded_group_begin; $s < $vtprd_rules_set[$i]->actionPop_exploded_group_end; $s++) {
            $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_discount_amt'] = $per_unit_savings_2decimals;
            
            //if first occurrence, add in penny if remainder calc produced one
            if ($s == $vtprd_rules_set[$i]->actionPop_exploded_group_begin) {
               $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_discount_amt'] += $add_a_penny_to_first;
            }
            
            $curr_prod_array = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s];
            $curr_prod_array['exploded_group_occurrence'] = $s;
            $this->vtprd_upd_cart_discount($i, $d, $curr_prod_array);
         } 

        break;
        
        
      case ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'cheapest') :
         $cheapest_array = array();
         //create candidate array
         for( $s=$vtprd_rules_set[$i]->actionPop_exploded_group_begin; $s < $vtprd_rules_set[$i]->actionPop_exploded_group_end; $s++) {
            $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['exploded_group_occurrence'] = $s;
            $cheapest_array [] = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s];           
         }
         //http://stackoverflow.com/questions/7839198/array-multisort-with-natural-sort
         //http://isambard.com.au/blog/2009/07/03/sorting-a-php-multi-column-array/
         //sort group by prod_unit_price (relative column3), cheapest 1stt
         $prod_unit_price = array();
         foreach ($cheapest_array as $key => $row) {
            $prod_unit_price[$key] = $row['prod_unit_price'];
         } 
         array_multisort($prod_unit_price, SORT_ASC, SORT_NUMERIC, $cheapest_array);
         
         //apply discount        
         $curr_prod_array = $cheapest_array[0];
         $curr_prod_array['prod_discount_amt'] = $this->vtprd_compute_each_discount($i, $d, $cheapest_array[0]['prod_unit_price'] );
         $this->vtprd_upd_cart_discount($i, $d, $curr_prod_array);
 
        break;
      
      case ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'most_expensive') :
         $mostExpensive_array = array();
         
         //create candidate array
         for( $s=$vtprd_rules_set[$i]->actionPop_exploded_group_begin; $s < $vtprd_rules_set[$i]->actionPop_exploded_group_end; $s++) {
            $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['exploded_group_occurrence'] = $s;
            $mostExpensive_array [] = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s];
         }
         
         //sort group by prod_unit_price , most expensive 1st
         $prod_unit_price = array();
         foreach ($mostExpensive_array as $key => $row) {
            $prod_unit_price[$key] = $row['prod_unit_price'];
         } 
         array_multisort($prod_unit_price, SORT_DESC, SORT_NUMERIC, $mostExpensive_array);
         
         //apply discount
         $curr_prod_array = $mostExpensive_array[0];
         $curr_prod_array['prod_discount_amt'] = $this->vtprd_compute_each_discount($i, $d, $mostExpensive_array[0]['prod_unit_price'] );
         $this->vtprd_upd_cart_discount($i, $d, $curr_prod_array);
         
        break;
        
      //$$ value off of a group
      case ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'currency') :  //only 'ALL'
         
         //add unit prices together
         $cart_group_total_price = 0;
         for( $s=$vtprd_rules_set[$i]->actionPop_exploded_group_begin; $s < $vtprd_rules_set[$i]->actionPop_exploded_group_end; $s++) {
            $cart_group_total_price += $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_unit_price'];
         }      
        $unit_count = $vtprd_rules_set[$i]->actionPop_exploded_group_end - $vtprd_rules_set[$i]->actionPop_exploded_group_begin;
       
        //per unit savings = new total / group unit count
        

        //compute remainder
        //$per_unit_savings_2decimals = bcdiv($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count'] , $unit_count , 2); 
        $per_unit_savings_2decimals = round( ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count'] / $unit_count ) , 2);    
   
        $running_total =  $per_unit_savings_2decimals * $unit_count;
        
        //$remainder = $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count'] - $running_total;
        $remainder = round($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count'] - $running_total, 2);   //v1.0.7.4  PHP floating point error fix - limit to 4 places right of the decimal!!
        
        if ($remainder > 0) {
          $add_a_penny_to_first = $remainder;
        } else {
          $add_a_penny_to_first = 0;
        }
    
        //apply the per unit savings to each unit
        for( $s=$vtprd_rules_set[$i]->actionPop_exploded_group_begin; $s < $vtprd_rules_set[$i]->actionPop_exploded_group_end; $s++) {
            $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_discount_amt'] = $per_unit_savings_2decimals;
            
            //if first occurrence, add in penny if remainder calc produced one
            if ($s == $vtprd_rules_set[$i]->actionPop_exploded_group_begin) {
               $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_discount_amt'] += $add_a_penny_to_first;
            }
                      
            $curr_prod_array = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s];
            $curr_prod_array['exploded_group_occurrence'] = $s;
            $this->vtprd_upd_cart_discount($i, $d, $curr_prod_array);
         } 

        break;
      
         
      //v1.0.7.4 begin
      //*******************************************
      //% value off of a group
      //  added to handle a price decimal ending in 5, which otherwise will produce a rounding-based error.
      //  rounding errors are now handled within each product sub-group, so that the fix will be reflected in the appropriate bucket.
      //*******************************************
      //--------------------------------------------
      //v1.0.7.6 entire case structure reworked
      //--------------------------------------------
      case ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'percent') :     
         //Applying a % discount to a group is often different from applying it singly, due to rounding issues.  This routine repairs that
         //  by comparing the total of the individually discounted items against the discount total of the same group, and adding any remainder to the last item discounted

         //*******************************************
         //add unit prices together, per product (so any group-level remainder goes with the correct product!)
         //******************************************* 
         
         $cart_group_total = array();
  
         $s = $vtprd_rules_set[$i]->actionPop_exploded_group_begin;
         $current_product_id = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_id'];
         
         $current_unit_count = 0;
         $current_total_price = 0;
         $current_unit_price = 0; 
         $grand_total_exploded_group = 0;
         $running_grand_total = 0;
     
         //pre-process action group for remainder info
         for( $s=$vtprd_rules_set[$i]->actionPop_exploded_group_begin; $s < $vtprd_rules_set[$i]->actionPop_exploded_group_end; $s++) {
            
            if ($current_product_id == $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_id'])  {
               
               //add to current totals
               $current_unit_count++;
               $current_total_price += $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_unit_price'];
               $current_unit_price = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_unit_price'];
           
            } else {
               //insert the totals of the previous product id     
               $cart_group_total[] = array(
                   'product_id'  => $current_product_id,
                   'unit_count'  => $current_unit_count,
                   'unit_price'  => $current_unit_price,
                   'total_price' => $current_total_price,
                   'total_discount' => 0,
                   'total_remainder' => 0,
                   'product_discount' => 0,
                   'product_discount_remainder' => 0  
                    ); 
               
               //initialize the current group     
               $current_product_id = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_id'];
               $current_unit_count = 1;
               $current_total_price = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_unit_price'];
               $current_unit_price = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_unit_price']; 
                                                      
            }

            //handle last in list
            if (  ($s + 1) == $vtprd_rules_set[$i]->actionPop_exploded_group_end ) {
               $cart_group_total[] = array(
                   'product_id'  => $current_product_id,
                   'unit_count'  => $current_unit_count,
                   'unit_price'  => $current_unit_price,
                   'total_price' => $current_total_price,
                   'total_discount' => 0,
                   'total_remainder' => 0,
                   'product_discount' => 0,
                   'product_discount_remainder' => 0   
                    );            
            }

            $grand_total_exploded_group += $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_unit_price']; 
            
         }

         $percent_off = $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count'] / 100;

         //*******************
         //compute group discounts, by product
         //*******************
         $sizeof_cart_group_total = sizeof ($cart_group_total);
         
         for( $c=0; $c < $sizeof_cart_group_total; $c++) {
            //****************************************************
            //Get the total discount amount for the whole group, used to calculate final remainder later
            //****************************************************
        //  $discount_applied_to_total = bcmul($cart_group_total[$c]['total_price'], $percent_off , 2);
            $discount_applied_to_total = round($cart_group_total[$c]['total_price'] * $percent_off , 2); //v1.0.7.6

            $cart_group_total[$c]['total_discount']  =  $discount_applied_to_total; 
            $cart_group_total[$c]['total_remainder'] =  0;
            
            
            //****************************************************
            //Get the Unit Price discount
            //****************************************************            
        //  $discounted_per_unit = (bcmul($cart_group_total[$c]['unit_price'], $percent_off , 2));
            $discounted_per_unit = (round($cart_group_total[$c]['unit_price'] * $percent_off , 2));  //v1.0.7.6
            
            $discount_applied_to_unit_times_count = $discounted_per_unit * $cart_group_total[$c]['unit_count'];
            
            $unit_price_discount_difference = $discount_applied_to_total - $discount_applied_to_unit_times_count;

            $discounted_per_unit_round = (round ($cart_group_total[$c]['unit_price'] * $percent_off , 2));


            $cart_group_total[$c]['product_discount'] = $discounted_per_unit;
            $cart_group_total[$c]['product_discount_remainder'] = $unit_price_discount_difference;
            
            //keep track of grand total
          //  $running_grand_total += ($temp_product_total_discount + $unit_price_discount_difference);           
             
         }
         
         //See if there is remainder AFTER all of the product groups are computed
       //$grand_total_exploded_group_discount = bcmul($grand_total_exploded_group, $percent_off , 2); 
         $grand_total_exploded_group_discount = round($grand_total_exploded_group * $percent_off , 2);
         $grand_total_remainder = round($grand_total_exploded_group_discount - $running_grand_total, 2);

        $current_product_id = '';
        $current_unit_count = 0;
        $current_total_discount = 0;
        
        //*******************  
        //apply discount to each item - add in **group** remainder to last
        //*******************
        for( $s=$vtprd_rules_set[$i]->actionPop_exploded_group_begin; $s < $vtprd_rules_set[$i]->actionPop_exploded_group_end; $s++) { 

            //*******************
            // track unit count for this product
            //*******************  
            if ($current_product_id == $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_id'])  {
              $current_unit_count++; 
            } else {
              $current_product_id = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_id'];
              $current_unit_count     = 1;
            }   
            
            //*******************
            // add in group remainder, as needed
            //*******************                      
            for( $c=0; $c < $sizeof_cart_group_total; $c++) {               
               
               if ($cart_group_total[$c]['product_id']  ==  $current_product_id ) {
                  
                  $this_prod_discount_amt = $cart_group_total[$c]['product_discount'];
                  
                  if ($cart_group_total[$c]['unit_count']  ==  $current_unit_count ){  //are we on last unit in product group?                  
                      $this_prod_discount_amt += $cart_group_total[$c]['product_discount_remainder']; 
                      /*  only do this by product!!
                      //if we're on the last product, last unit - add in grand_total_remainder
                      if ( ($c + 1) == $sizeof_cart_group_total) {
                         $this_prod_discount_amt += $grand_total_remainder;
                      }
                     */                   
                  }

                  $c = $sizeof_cart_group_total; //exit stage left
               }
            }
            
            //*******************
            // update discount
            //*******************                
            $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_discount_amt'] = $this_prod_discount_amt;
            $curr_prod_array = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s];
            $curr_prod_array['exploded_group_occurrence'] = $s;
            $this->vtprd_upd_cart_discount($i, $d, $curr_prod_array);                 
        }

      break;
      //v1.0.7.4 end
       
    }
    
    return;           
  }
 
 /*  --------------------------
 This routine creates a single exploded product's discount.  It also checks that discount against
 individual limits.  It also checks if this exploded product discount 
 exceeds the product's cumulative quantity discount.
    -------------------------- */
  public function vtprd_upd_cart_discount($i, $d, $curr_prod_array) {   
    global $post, $vtprd_setup_options, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_template_structures_framework;  

    $k = $curr_prod_array['prod_id_cart_occurrence'];
    $rule_id = $vtprd_rules_set[$i]->post_id; 

    if ($curr_prod_array['prod_discount_amt'] == 0) {
      $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No discount for this rule';
      return;
    }
      
    //just in case discount for this rule already applied to this product iteration....
    //mark exploded list product as already processed for this rule
    $occurrence = $curr_prod_array['exploded_group_occurrence'];       
    if (($curr_prod_array['prod_discount_applied'] == 'yes') ||
        ($vtprd_rules_set[$i]->actionPop_exploded_found_list[$occurrence]['prod_discount_applied'] == 'yes')) {
      $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'Discount already applied, can"t reapply';
      //exit stage left, can't apply discount for same rule to same product....
      return;
    }
 

    //*********************************************************************
    //CHECK THE MANY DIFFERENT MAX LIMITS BEFORE UPDATING THE DISCOUNT TO THE ARRAY
    //********************************************************************* 
   
    if ( isset( $vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id] ) ) {
      if ( (sizeof ($vtprd_cart->cart_items[$k]->yousave_by_rule_info) > 1 ) &&   //only 1 allowed in this case...
           ($vtprd_rules_set[$i]->cumulativeRulePricing == 'no') ) {
         //1 discount rule already applied discount, no more allowed;
        $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - 1 discount rule already applied discount, no more allowed';
         return;  
      }
      if ( $vtprd_setup_options['discount_floor_pct_per_single_item'] > 0 ) {
        if ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   != 'free') {
           if ( ($vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['yousave_pct'] >= $vtprd_setup_options['discount_floor_pct_per_single_item']) ||
                ($vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['rule_max_amt_msg'] > ' ') ) {
              //yousave percent max alread reached in a previous discount!!!!!!  Do Nothing
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - Discount floor percentage max reached, ' .$vtprd_setup_options['discount_floor_pct_msg']; //floor percentage maxed out;            
              return;
           }
        }
      } 

      //v1.0.7.4 begin
      //CHECK if catalog discount already applied
      if ($vtprd_info['current_processing_request'] == 'cart') { 
         if ( ($vtprd_cart->cart_items[$k]->unit_price < $vtprd_cart->cart_items[$k]->save_orig_unit_price) &&
              ($vtprd_rules_set[$i]->cumulativeRulePricing == 'no') ) {
            //A Catalog or other external discount previously applied, no 
            $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - CATALOG discount rule already applied discount, no more allowed';
            return;           
         }
      }      
      //v1.0.7.4 end
      
      switch( $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_max_amt_type'] ) {
        case 'none':
            $do_nothing;
          break;
        case 'percent':
           if ( ($vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['yousave_pct'] >= $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_max_amt_count']) ||
                ($vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['rule_max_amt_msg'] > ' ') ) {          
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - Rule Max Percent Previously Reached.'; //floor percentage maxed out;                      
              return;
            }
          break;
        case 'quantity':       
           if ( ($vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['discount_applies_to_qty'] >= $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_max_amt_count']) ||
                ($vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['rule_max_amt_msg'] > ' ') ) {          
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - Rule Max Qty Previously Reached.'; //floor percentage maxed out;                      
              return;
            }
          break;        
        case 'currency': 
           if ( ($vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['yousave_amt'] >= $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_max_amt_count']) ||
                ($vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['rule_max_amt_msg'] > ' ') ) {          
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - Rule Max $$ Value Previously Reached.'; //floor percentage maxed out;                      
              return;
            }      
          break;
      }
      
       
      switch( $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_cum_max_amt_type'] ) {
        case 'none':
            $do_nothing;
          break;
        case 'percent':
           if ( $vtprd_rules_set[$i]->discount_total_pct >= $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_cum_max_amt_count']) {          
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - Rule Cumulative Max Percent Previously Reached.'; //floor percentage maxed out;                      
              return;
            }
          break;
        case 'quantity':
           if ( $vtprd_rules_set[$i]->discount_total_qty >= $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_cum_max_amt_count']) {          
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_status'] = 'rejected';
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - Rule Cumulative Max Qty Previously Reached.'; //floor percentage maxed out;                      
              return;
            }
          break;        
        case 'currency':    
           if ( $vtprd_rules_set[$i]->discount_total_amt >= $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_cum_max_amt_count'])  {          
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - Rule Cumulative Max $$ Value Previously Reached.'; //floor percentage maxed out;                      
              return;
            }      
          break;
      }      
 
      $yousave_for_this_rule_id_already_exists = 'yes';

    } else {      
      if ( (sizeof($vtprd_cart->cart_items[$k]->yousave_by_rule_info) > 0 ) &&
           ($vtprd_rules_set[$i]->cumulativeRulePricing == 'no') ) {
         //1 discount rule already applied discount, no more allowed
        $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - Discount for this another rule already applied to this Product, multiple rule discounts not allowed.';         
         return;  
      }
      
      $yousave_for_this_rule_id_already_exists = 'no';
      
    }
    
    
    //*****************************************
    //find current product's yousave percent, altered as needed below
    //*****************************************
    
    if ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']  == 'percent') {
      $yousave_pct = $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count']; 
    } else {
      //compute yousave_pct_at_upd_begin
      $computed_pct =  $curr_prod_array['prod_discount_amt'] /  $curr_prod_array['prod_unit_price'] ;
      //$computed_pct_2decimals = bcdiv($curr_prod_array['prod_discount_amt'] , $curr_prod_array['prod_unit_price'] , 2);

      $computed_pct_2decimals = round( ($curr_prod_array['prod_discount_amt'] / $curr_prod_array['prod_unit_price'] ) , 2); 
                
      //$remainder = $computed_pct - $computed_pct_2decimals;
      $remainder = round($computed_pct - $computed_pct_2decimals, 4);   //v1.0.7.4  PHP floating point error fix - limit to 4 places right of the decimal!!
                  
      if ($remainder > 0.005) {
        //v1.0.7.4 begin
        $increment = round($remainder, 2); //round the rounding error to 2 decimal points!  floating point
        if ($increment < .01) {
          $increment = .01;
        }
        ////v1.0.7.4 end
        $yousave_pct = ($computed_pct_2decimals + $increment) * 100;  //v1.0.7.4   floating point
      } else {
        $yousave_pct = $computed_pct_2decimals * 100;
      }
    }

 
    $max_msg = '';
    $discount_status = '';
    
    //compute current discount_totals for limits testing
    $discount_total_qty_for_rule = $vtprd_rules_set[$i]->discount_total_qty_for_rule + 1;
    $discount_total_amt_for_rule = $vtprd_rules_set[$i]->discount_total_amt_for_rule + $curr_prod_array['prod_discount_amt'] ;
    //$discount_total_unit_price_for_rule will be the unit qty * db_unit_price already, as this routine is done 1 by 1...
    $discount_total_unit_price_for_rule =  $vtprd_rules_set[$i]->discount_total_unit_price_for_rule + $curr_prod_array['prod_unit_price'] ;
    //yousave pct whole number  = total discount amount / (orig unit price * number of units discounted)
    $discount_total_pct_for_rule = ($discount_total_amt_for_rule / $discount_total_unit_price_for_rule) * 100 ;  //in round #s
     
    //adjust yousave_amt and yousave_pct as needed based on limits
    switch( $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_max_amt_type']  ) {  //var on the 1st iteration only
      case 'none':
          $do_nothing;
        break;
      case 'percent':           
          if ($discount_total_pct_for_rule > $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_max_amt_count']) {
            
             // % = floor minus rule % totaled in previous iteration
            $yousave_pct = $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_max_amt_count'] - $vtprd_rules_set[$i]->discount_total_pct_for_rule; 
            
            //*********************************************************************
            //reduce discount amount to max allowed by rule percentage
            //*********************************************************************
         // $discount_2decimals = bcmul(($yousave_pct / 100) , $curr_prod_array['prod_unit_price'] , 2);
            $discount_2decimals = round(($yousave_pct / 100) * $curr_prod_array['prod_unit_price'] , 2);  //v1.0.7.6 
          
            //compute rounding
            $temp_discount = ($yousave_pct / 100) * $curr_prod_array['prod_unit_price'];
         
            //$rounding = $temp_discount - $discount_2decimals;
            $rounding = round($temp_discount - $discount_2decimals, 4);   //v1.0.7.4  PHP floating point error fix - limit to 4 places right of the decimal!!
                     
            if ($rounding > 0.005) {
              //v1.0.7.4 begin
              $increment = round($rounding, 2); //round the rounding error to 2 decimal points!  floating point
              if ($increment < .01) {
                $increment = .01;
              }
              ////v1.0.7.4 end              
              $discount = $discount_2decimals + $increment;   //v1.0.7.4  floating point
            }  else {
              $discount = $discount_2decimals;
            } 
            
            $curr_prod_array['prod_discount_amt']  = $discount;
            $max_msg = $vtprd_rules_set[$i]->discount_rule_max_amt_msg;
 
            $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 
            'Discount reduced due to max rule percent overrun.';         
                        
          }
 
        break;      
      case 'quantity':
          if ($discount_total_qty_for_rule > $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_max_amt_count']) {
             //we've reached the max allowed by this rule, as we only process 1 at a time, exit
            $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - Discount Rule Max Qty already reached, discount skipped';               
             return;
          }
        break;
      case 'currency':
          if ($discount_total_amt_for_rule > $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_max_amt_count']) {
            //reduce discount to max...
            $reduction_amt = $discount_total_amt_for_rule - $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_max_amt_count'];

            $curr_prod_array['prod_discount_amt']  = $curr_prod_array['prod_discount_amt'] - $reduction_amt;
            
            $max_msg = $vtprd_rules_set[$i]->discount_rule_max_amt_msg;
             
            $yousave_pct_temp = $curr_prod_array['prod_discount_amt'] / $curr_prod_array['prod_unit_price'];
            
            // $yousave_pct = $yousave_amt / $curr_prod_array['prod_unit_price'] * 100;        
            //compute remainder
            //$yousave_pct_2decimals = bcdiv($curr_prod_array['prod_discount_amt'] , $curr_prod_array['prod_unit_price'] , 2);

            $yousave_pct_2decimals = round( ($curr_prod_array['prod_discount_amt'] / $curr_prod_array['prod_unit_price'] ) , 2);      
                              
          //$remainder = $yousave_pct_temp - $yousave_pct_2decimals;
            $remainder = round($yousave_pct_temp - $yousave_pct_2decimals, 4);   //v1.0.7.4  PHP floating point error fix - limit to 4 places right of the decimal!!
                        
            if ($remainder > 0.005) {
              //v1.0.7.4 begin
              $increment = round($remainder, 2); //round the rounding error to 2 decimal points!  floating point
              if ($increment < .01) {
                $increment = .01;
              }
              ////v1.0.7.4 end              
              $yousave_pct = ($yousave_pct_2decimals + $increment) * 100;  //v1.0.7.4  PHP floating point 
            } else {
              $yousave_pct = $yousave_pct_2decimals * 100;
            }
          }
        break;
    }

    //Test yousave for product across All Rules applied to the Product
    $yousave_product_total_amt = $vtprd_cart->cart_items[$k]->yousave_total_amt +  $curr_prod_array['prod_discount_amt'] ;
    $yousave_product_total_qty = $vtprd_cart->cart_items[$k]->yousave_total_qty + 1;
    //  yousave_total_unit_price is a rolling full total of unit price already
    $yousave_total_unit_price = $vtprd_cart->cart_items[$k]->yousave_total_unit_price + $curr_prod_array['prod_unit_price'];  
    //yousave pct whole number = (total discount amount / (orig unit price * number of units discounted))
    $yousave_pct_prod_temp = $yousave_product_total_amt / $yousave_total_unit_price;
    //$yousave_pct_prod_2decimals = bcdiv($yousave_product_total_amt , $yousave_total_unit_price , 2);
    $yousave_pct_prod_2decimals = round( ($yousave_product_total_amt / $yousave_total_unit_price ) , 2); 
       
  //$remainder = $yousave_pct_prod_temp - $yousave_pct_prod_2decimals;
    $remainder = round($yousave_pct_prod_temp - $yousave_pct_prod_2decimals, 4);   //v1.0.7.4  PHP floating point error fix - limit to 4 places right of the decimal!!
                    
    if ($remainder > 0.005) {
      //v1.0.7.4 begin
      $increment = round($remainder, 2); //round the rounding error to 2 decimal points!  floating point
      if ($increment < .01) {
        $increment = .01;
      }
      ////v1.0.7.4 end
      $yousave_product_total_pct = ($yousave_pct_prod_2decimals + $increment) * 100;   //v1.0.7.4  PHP floating point 
    } else {
      $yousave_product_total_pct = $yousave_pct_prod_2decimals * 100;
    }
    $refigure_yousave_product_totals = 'no';

    //if amts have been massaged, recheck vs discount_floor_percentage
    if ($max_msg > ' ') {
      if ( $vtprd_setup_options['discount_floor_pct_per_single_item'] > 0 ) {

        if ( $yousave_product_total_pct > $vtprd_setup_options['discount_floor_pct_per_single_item']) {
          //reduce discount amount to max allowed by max floor discount percentage
          //    compute the allowed remainder percentage
          // % = floor minus product % totaled *before now*
          $yousave_pct = $vtprd_setup_options['discount_floor_pct_per_single_item'] - $vtprd_cart->cart_items[$k]->yousave_total_pct;
         
          $percent_off = $yousave_pct / 100;         
          //compute rounding
        //$discount_2decimals = bcmul($curr_prod_array['prod_unit_price'] , $percent_off , 2);
          $discount_2decimals = round($curr_prod_array['prod_unit_price'] * $percent_off , 2);   //v1.0.7.6    
          
          $temp_discount = $curr_prod_array['prod_unit_price'] * $percent_off;
          
          //$rounding = $temp_discount - $discount_2decimals;
          $rounding = round($temp_discount - $discount_2decimals, 4);   //v1.0.7.4  PHP floating point error fix - limit to 4 places right of the decimal!!
                             
          if ($rounding > 0.005) {
            //v1.0.7.4 begin
            $increment = round($rounding, 2); //round the rounding error to 2 decimal points!  floating point
            if ($increment < .01) {
              $increment = .01;
            }
            ////v1.0.7.4 end            
            $curr_prod_array['prod_discount_amt'] = $discount_2decimals + $increment;   //v1.0.7.4  PHP floating point
          }  else {
            $curr_prod_array['prod_discount_amt'] = $discount_2decimals;
          }          
          $refigure_yousave_product_totals = 'yes';
          //$curr_prod_array['prod_discount_amt']  = ($yousave_pct / 100) * $curr_prod_array['prod_unit_price'];
        } 
      }         
    }
    
        
    //adjust yousave_amt and yousave_pct as needed based on limits
    switch( $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_cum_max_amt_type']  ) {  //var on the 1st iteration only
      case 'none':
          $do_nothing;
        break;
      case 'percent':           
          if ($yousave_product_total_pct > $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_cum_max_amt_count']) {
            
             // % = floor minus rule % totaled *before now*
            $yousave_pct = $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_max_amt_count'] - $vtprd_cart->cart_items[$k]->yousave_total_pct;
            
            //*********************************************************************
            //reduce discount amount to max allowed by rule percentage
            //*********************************************************************
        //  $discount_2decimals = bcmul(($yousave_pct / 100) , $curr_prod_array['prod_unit_price'] , 2);
            $discount_2decimals = round(($yousave_pct / 100) * $curr_prod_array['prod_unit_price'] , 2);  //v1.0.7.6 
         
            //compute rounding
            $temp_discount = ($yousave_pct / 100) * $curr_prod_array['prod_unit_price'];

          //$rounding = $temp_discount - $discount_2decimals;
            $rounding = round($temp_discount - $discount_2decimals, 4);   //v1.0.7.4  PHP floating point error fix - limit to 4 places right of the decimal!!
         
            if ($rounding > 0.005) {
               //v1.0.7.4 begin
              $increment = round($rounding, 2); //round the rounding error to 2 decimal points!  floating point
              if ($increment < .01) {
                $increment = .01;
              }
              ////v1.0.7.4 end             
              $discount = $discount_2decimals + $increment;    //v1.0.7.4  PHP floating point 
            }  else {
              $discount = $discount_2decimals;
            } 
            
            $curr_prod_array['prod_discount_amt']  = $discount;
            $max_msg = $vtprd_rules_set[$i]->discount_rule_max_amt_msg; 
            $refigure_yousave_product_totals = 'yes';           
          }
 
        break;       
      case 'quantity':
          if ($yousave_product_total_qty > $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_cum_max_amt_count']) {
             //we've reached the max allowed by this rule, as we only process 1 at a time, exit
            $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - Discount Rule Max Qty already reached, discount skipped';               
             return;
          }
        break;
      case 'currency':
          if ($yousave_product_total_amt > $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_cum_max_amt_count']) {
            //reduce discount to max...
            $reduction_amt = $yousave_product_total_amt - $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_cum_max_amt_count'];

            $curr_prod_array['prod_discount_amt']  = $curr_prod_array['prod_discount_amt'] - $reduction_amt;
            
            $max_msg = $vtprd_rules_set[$i]->discount_rule_cum_max_amt_msg;
             
            $yousave_pct_temp = $curr_prod_array['prod_discount_amt'] / $curr_prod_array['prod_unit_price'];
            
            // $yousave_pct = $yousave_amt / $curr_prod_array['prod_unit_price'] * 100;        
            //compute remainder
            //$yousave_pct_2decimals = bcdiv($curr_prod_array['prod_discount_amt'] , $curr_prod_array['prod_unit_price'] , 2);
            $yousave_pct_2decimals = round( ($curr_prod_array['prod_discount_amt'] / $curr_prod_array['prod_unit_price'] ) , 2);     
                            
          //$remainder = $yousave_pct_temp - $yousave_pct_2decimals;
            $remainder = round($yousave_pct_temp - $yousave_pct_2decimals, 4);   //v1.0.7.4  PHP floating point error fix - limit to 4 places right of the decimal!!
             
            if ($remainder > 0.005) {
              //v1.0.7.4 begin
              $increment = round($remainder, 2); //round the rounding error to 2 decimal points!  floating point
              if ($increment < .01) {
                $increment = .01;
              }
              ////v1.0.7.4 end
              $yousave_pct = ($yousave_pct_2decimals + $increment) * 100;     //v1.0.7.4  PHP floating point 
            } else {
              $yousave_pct = $yousave_pct_2decimals * 100;
            }
            $refigure_yousave_product_totals = 'yes';
          }
        break;
    }

    //*************************************
    // PURCHASE HISTORY LIFETIME LIMIT
    //*************************************   

    //EDITED * + * +  * + * +  * + * +  * + * + 
    
    //EXIT if Sale Price already lower than Discount
    if ( ($vtprd_cart->cart_items[$k]->product_is_on_special == 'yes') &&
         ($vtprd_rules_set[$i]->cumulativeSalePricing == 'replaceSalePrice' ) )  {
      //Replacement of Sale Price is requested, but only happens if Discount is GREATER THAN sale price
      $discounted_price = ($curr_prod_array['prod_unit_price'] - $curr_prod_array['prod_discount_amt'] ) ;
      If ($vtprd_cart->cart_items[$k]->db_unit_price_special < $discounted_price ) {
        $vtprd_cart->cart_items[$k]->unit_price     = $vtprd_cart->cart_items[$k]->db_unit_price_special;
        $vtprd_cart->cart_items[$k]->db_unit_price  = $vtprd_cart->cart_items[$k]->db_unit_price_special;
        $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_status'] = 'rejected';
        $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] =  'No Discount - Sale Price Less than Discounted price';
        return;      
      }
   }
  
    //*********************************************************************
    //eND MAX LIMITS CHECKING
    //*********************************************************************


    //*************************************
    // Add Discount Totals into the Array
    //*************************************       
    if ($yousave_for_this_rule_id_already_exists == 'yes') { 
       $vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['yousave_amt']     +=  $curr_prod_array['prod_discount_amt'] ;
    //cumulative percentage
       $vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['discount_applies_to_qty']++; 
       $vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['rule_max_amt_msg'] =  $max_msg;
    }  else {
       $vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id] =  array(
           'ruleset_occurrence'    => $i, 
           'discount_amt_type'   => '',
           'discount_amt_count'   => 0,
           'discount_for_the_price_of_count'  => '', 
           'discount_applies_to_qty'  => 1,         
           'yousave_amt'       => $curr_prod_array['prod_discount_amt'] ,
           'yousave_pct'       => $yousave_pct ,
           'rule_max_amt_msg'  => $max_msg,
           'rule_execution_type' =>  $vtprd_rules_set[$i]->rule_execution_type, //used when sending purchase EMAIL!!       
           'rule_short_msg'    => $vtprd_rules_set[$i]->discount_product_short_msg,
           'rule_full_msg'     => $vtprd_rules_set[$i]->discount_product_full_msg
           //used at cart discount display time => if coupon used, does this discount apply?
           //  ---> pick this up directly from the ruleset occurrence at application time
           //'cumulativeCouponPricingAllowed' => $vtprd_rules_set[$i]->cumulativeCouponPricingAllowed  
          );
        
        //******************************************
        //for later ajaxVariations pricing    - BEGIN
        //******************************************        
        if ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type'] == 'percent') {
          $pricing_rule_percent_discount  = $yousave_pct;
          $pricing_rule_currency_discount = 0;
        } else {
          $pricing_rule_percent_discount  = 0;
          $pricing_rule_currency_discount = $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count'];        
        }
        $vtprd_cart->cart_items[$k]->pricing_by_rule_array[] =  array(  
            'pricing_rule_id' => $rule_id, 
            'pricing_rule_applies_to_variations_array' => $vtprd_rules_set[$i]->var_in_checked , //' ' or var list array
            'pricing_rule_percent_discount'  => $pricing_rule_percent_discount,
            'pricing_rule_currency_discount' => $pricing_rule_currency_discount 
          );
        //  ajaxVariations pricing - END
           
    }
    //recompute the discount totals for use in next iteration
    $vtprd_rules_set[$i]->discount_total_qty_for_rule = $vtprd_rules_set[$i]->discount_total_qty_for_rule + 1;
    $vtprd_rules_set[$i]->discount_total_amt_for_rule = $vtprd_rules_set[$i]->discount_total_amt_for_rule + $curr_prod_array['prod_discount_amt'] ;
    //$discount_total_unit_price_for_rule will be the unit qty * db_unit_price already, as this routine is done 1 by 1...
    $vtprd_rules_set[$i]->discount_total_unit_price_for_rule =  $vtprd_rules_set[$i]->discount_total_unit_price_for_rule + $curr_prod_array['prod_db_unit_price'] ;
    //yousave pct whole number  = total discount amount / (orig unit price * number of units discounted)
    $vtprd_rules_set[$i]->discount_total_pct_for_rule = ($discount_total_amt_for_rule / $discount_total_unit_price_for_rule) * 100 ;

    //REFIGURE the product totals, if there was a reduction above...
    if ($refigure_yousave_product_totals == 'yes') {
      $yousave_product_total_amt = $vtprd_cart->cart_items[$k]->yousave_total_amt +  $curr_prod_array['prod_discount_amt'] ;
      $yousave_product_total_qty = $vtprd_cart->cart_items[$k]->yousave_total_qty + 1;
      //  yousave_total_unit_price is a rolling full total of unit price already
      $yousave_total_unit_price = $vtprd_cart->cart_items[$k]->yousave_total_unit_price + $curr_prod_array['prod_unit_price'];  
      //yousave pct whole number = (total discount amount / (orig unit price * number of units discounted))
      $yousave_pct_prod_temp = $yousave_product_total_amt / $yousave_total_unit_price;
      //$yousave_pct_prod_2decimals = bcdiv($yousave_product_total_amt , $yousave_total_unit_price , 2);
 
      $yousave_pct_prod_2decimals = round( ($yousave_product_total_amt / $yousave_total_unit_price ) , 2);     
        
      //$remainder = $yousave_pct_prod_temp - $yousave_pct_prod_2decimals;
      $remainder = round($yousave_pct_prod_temp - $yousave_pct_prod_2decimals, 4);   //v1.0.7.4  PHP floating point error fix - limit to 4 places right of the decimal!!
       
      if ($remainder > 0.005) {
      //v1.0.7.4 begin
      $increment = round($remainder, 2); //round the rounding error to 2 decimal points!  floating point
      if ($increment < .01) {
        $increment = .01;
      }
      ////v1.0.7.4 end
        $yousave_product_total_pct = ($yousave_pct_prod_2decimals + $increment) * 100;    //v1.0.7.4  PHP floating point 
      } else {
        $yousave_product_total_pct = $yousave_pct_prod_2decimals * 100;
      } 
    }      
    $vtprd_cart->cart_items[$k]->yousave_total_amt = $yousave_product_total_amt; 
    $vtprd_cart->cart_items[$k]->yousave_total_qty = $yousave_product_total_qty; 
    $vtprd_cart->cart_items[$k]->yousave_total_pct = $yousave_product_total_pct ;
    $vtprd_cart->cart_items[$k]->yousave_total_unit_price = $yousave_total_unit_price;
    
    //keep track of historical discount totals 
     //instead of $yousave_product_total_qty;, we're actually counting home many times the RULE was used, not the total qty it was applied to... 
    $vtprd_rules_set[$i]->purch_hist_rule_row_qty_total_plus_discounts    +=  1; // +1 for each RULE OCCURRENCE usage...
    $vtprd_rules_set[$i]->purch_hist_rule_row_price_total_plus_discounts  +=  $curr_prod_array['prod_discount_amt'];
    
    //used in lifetime limits
    $vtprd_rules_set[$i]->actionPop_rule_yousave_amt  +=  $curr_prod_array['prod_discount_amt'];
    $vtprd_rules_set[$i]->actionPop_rule_yousave_qty  +=  1;  //$yousave_product_total_qty;  not qty, but iterations of USAGE!
    
    //$vtprd_cart->cart_items[$k]->discount_price    = ($vtprd_cart->cart_items[$k]->db_unit_price * $vtprd_cart->cart_items[$k]->quantity) - $yousave_product_total_amt ;  
    $vtprd_cart->cart_items[$k]->discount_price    = ( $curr_prod_array['prod_unit_price'] * $vtprd_cart->cart_items[$k]->quantity) - $yousave_product_total_amt ; 
    
    $vtprd_rules_set[$i]->discount_applied == 'yes';
    $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_status'] = 'applied';
    $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = __('Discount Applied', 'vtprd');
    $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_amt'] = $curr_prod_array['prod_discount_amt'];
    $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_pct'] = $yousave_pct;
    
    //                         *******************
    //if discount has applied, update rule totals after recalc to pick up most current total price info 
    //                         *******************  
    
    
    //add in total saved to yousave_total_amt for PRODUCT
    if ($curr_prod_array['prod_discount_amt'] > 0) {
 
    //v1.0.7.4 begin 
    //  the vtprd_cart unit price and discounts all reflect the TAX STATE of 'woocommerce_prices_include_tax'      
		   global $woocommerce;
       $product_id = $vtprd_cart->cart_items[$k]->product_id;
       switch( true ) {
          case ( get_option( 'woocommerce_calc_taxes' ) == 'no' ):
          case ( !$vtprd_cart->cart_items[$k]->product_is_taxable ):
          case ( $woocommerce->customer->is_vat_exempt() ):  
             $prod_discount_amt_excl_tax  =  $curr_prod_array['prod_discount_amt'];
             $prod_discount_amt_incl_tax  =  $curr_prod_array['prod_discount_amt'];
            break; 
          case ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' ): 
             $prod_discount_amt_excl_tax  =  vtprd_get_price_excluding_tax($product_id, $curr_prod_array['prod_discount_amt']);
             $prod_discount_amt_incl_tax  =  $curr_prod_array['prod_discount_amt'];
            break; 
          case ( get_option( 'woocommerce_prices_include_tax' ) == 'no' ): 
             $prod_discount_amt_excl_tax  =  $curr_prod_array['prod_discount_amt'];
             $prod_discount_amt_incl_tax  =  vtprd_get_price_including_tax($product_id, $curr_prod_array['prod_discount_amt']);
            break;                      
		   }
       //THIS is where the cart SAVE TOTALS are stored!!             
       $vtprd_cart->yousave_cart_total_amt_excl_tax      += $prod_discount_amt_excl_tax;
       $vtprd_cart->yousave_cart_total_amt_incl_tax      += $prod_discount_amt_incl_tax;
    //v1.0.7.4 end    
         
                 
      $vtprd_rules_set[$i]->discount_total_qty += 1;     
      $vtprd_rules_set[$i]->discount_total_amt += $curr_prod_array['prod_discount_amt'];
      $vtprd_cart->yousave_cart_total_qty      += 1;
      $vtprd_cart->yousave_cart_total_amt      += $curr_prod_array['prod_discount_amt'];        
    }    

    //mark exploded list product as already processed for this rule
    $vtprd_rules_set[$i]->actionPop_exploded_found_list[$occurrence]['prod_discount_applied'] = 'yes';


    //**********************************************
    //  if this product is free, add the product qty to the tracking bucket
    //**********************************************    
    if ($curr_prod_array['prod_discount_amt'] == $vtprd_cart->cart_items[$k]->unit_price) {  
      $key =  $vtprd_cart->cart_items[$k]->product_id;
      if (isset($vtprd_rules_set[$i]->free_product_array[$key])) {
         $vtprd_rules_set[$i]->free_product_array[$key]++;
      } else {
         $vtprd_rules_set[$i]->free_product_array[$key] = 1;
      }
    }
    
 }
 
 
  public function vtprd_compute_each_discount($i, $d, $prod_unit_price ) {   
    global $post, $vtprd_setup_options, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_template_structures_framework;        
     //$vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_unit_price']
    switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']  ) {            
      case 'free':
          $discount = $prod_unit_price;
        break;
      case 'fixedPrice':
          $discount = $prod_unit_price - $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count'];                               
        break;
      case 'percent': 
          $percent_off = $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count'] / 100;   
       // $discount_2decimals = bcmul($prod_unit_price , $percent_off , 2); 
          $discount_2decimals = round($prod_unit_price * $percent_off , 2);   //v1.0.7.6
               
          //compute rounding
          $temp_discount = $prod_unit_price * $percent_off;
          
          //$rounding = $temp_discount - $discount_2decimals;
          $rounding = round($temp_discount - $discount_2decimals, 4);   //v1.0.7.4  PHP floating point error fix - limit to 4 places right of the decimal!!
                  
          if ($rounding > 0.005) {
            //v1.0.7.4 begin
            $increment = round($rounding, 2); //round the rounding error to 2 decimal points!  floating point
            if ($increment < .01) {
              $increment = .01;
            }
            ////v1.0.7.4 end          
            $discount = $discount_2decimals + $increment;   //v1.0.7.4  PHP floating
          }  else {
            $discount = $discount_2decimals;
          }             
        break;              
      case 'currency': 
          $discount = $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count'];     
        break;
    }
    return $discount;
  }
 
  public function vtprd_set_buy_group_end($i, $d, $r ) { 
    global $post, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_template_structures_framework;     
    //mwn change
    /* 
    can only set begin here: 
    * if qty, end is buy_amt_count
    * if currency, end is only known if currency value of buy_amt_count is reached
    * if nth, end is a multiple of ($r + 1) * buy_amt_count        
    */
    $templateKey = $vtprd_rules_set[$i]->rule_template;    
    
    $for_loop_current_prod_id = ''; //v1.0.7.2        

    $for_loop_unit_count = 0;
    $for_loop_price_total = 0;
    $for_loop_elapsed_count = 0;

    if ( ($vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_type'] == 'quantity') || 
         ($vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_type'] == 'currency') ) {

      $sizeof_inPop_exploded_found_list = sizeof($vtprd_rules_set[$i]->inPop_exploded_found_list);
      for($e=$vtprd_rules_set[$i]->inPop_exploded_group_begin; $e < $sizeof_inPop_exploded_found_list; $e++) {
        $for_loop_elapsed_count++;        
        switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_type'] ) {
          
          case 'quantity':
                $temp_end = $vtprd_rules_set[$i]->inPop_exploded_group_begin + $for_loop_elapsed_count;
               // $temp_end = $vtprd_rules_set[$i]->inPop_exploded_group_end + $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count'] ;  
                if ( $temp_end > sizeof($vtprd_rules_set[$i]->inPop_exploded_found_list) ) {
                   $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                   $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining qty in cart to fulfill buy amt qty';
                   return;
                }              
               switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_applies_to'] ) {             
                  case 'each':
                       //check if new product in list...
                       if ($for_loop_current_prod_id != $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_id'] ) {
                          //if new product, reset all tracking fields
                          $for_loop_current_prod_id = $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_id'];
                          $for_loop_unit_count = 1;
                          $for_loop_price_total = $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_unit_price'];
                       } else {
                          $for_loop_unit_count++;
                          $for_loop_price_total += $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_unit_price'];
                       }
                    break;               
                  case 'all':
                      $for_loop_unit_count++;
                      $for_loop_price_total += $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_unit_price']; 
                    break;           
               } //end switch  $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_applies_to']                
               if ($for_loop_unit_count == $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count']) {
                  //Set group_end here.  use $e + 1 since we may have reset the for_loop_unit_count during processing
                  
                  $vtprd_rules_set[$i]->inPop_exploded_group_end = $vtprd_rules_set[$i]->inPop_exploded_group_begin + $for_loop_elapsed_count; 
                                                      
                  if ($vtprd_template_structures_framework[$templateKey]['buy_amt_mod'] > ' ' ) {
                     switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_mod'] ) {
                         case 'none':
                           break;  
                         case 'minCurrency':                           
                              if ($for_loop_price_total < $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_mod_count']) { // < is an error, value should be >= 
                                $failed_test_total++;
                                $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                                $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining $$ in cart to fulfill minimum buy amt qty';                                                                
                              }
                           break;
                         case 'maxCurrency':
                              if ($for_loop_price_total > $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_mod_count']) { // > is an error, value should be <= 
                                $failed_test_total++;
                                $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                                $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining $$ in cart to fulfill maximum buy amt qty';
                              }                              
                           break;                                            
                     } //end switch 
                   }                                   
                  $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Buy amt Qty test completed';
                  return; // done, passed the test, both begin and end set...
               }  //end if         
            break;
         
          case 'currency':
                $temp_end = $vtprd_rules_set[$i]->inPop_exploded_group_begin + $for_loop_elapsed_count;  
                if ( $temp_end > sizeof($vtprd_rules_set[$i]->inPop_exploded_found_list) ) {
                   $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                    $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining $$ in cart to fulfill buy amt qty';
                   return;
                }             
               switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_applies_to'] ) {             
                  case 'each':
                       //check if new product in list...
                       if ($for_loop_current_prod_id != $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_id'] ) {
                          //if new product, reset all tracking fields
                          $for_loop_current_prod_id = $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_id'];
                          $for_loop_unit_count = 1;
                          $for_loop_price_total = $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_unit_price'];
                       } else {
                          $for_loop_unit_count++;
                          $for_loop_price_total += $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_unit_price'];
                       }
                    break;               
                  case 'all':
                      $for_loop_unit_count++;
                      $for_loop_price_total += $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_unit_price']; 
                    break;           
               } //end switch  $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_applies_to']
               
               if ($for_loop_price_total >= $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count']) {
                  //Set group_end here.  use $e + 1 since we may have reset the for_loop_unit_count during processing
                  
                  $vtprd_rules_set[$i]->inPop_exploded_group_end = $vtprd_rules_set[$i]->inPop_exploded_group_begin + $for_loop_elapsed_count;
                                
                  if ($vtprd_template_structures_framework[$templateKey]['buy_amt_mod'] > ' ' ) {
                    switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_mod'] ) {
                       case 'none':
                         break;
                       case 'minCurrency':                           
                            if ($for_loop_price_total < $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_mod_count']) { // < is an error, value should be >= 
                              $failed_test_total++;
                              $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                              $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining $$ in cart to fulfill minimum buy amt mod count';
                            }
                         break;
                       case 'maxCurrency':
                            if ($for_loop_price_total > $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_mod_count']) { // > is an error, value should be <= 
                              $failed_test_total++;
                              $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                              $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining $$ in cart to fulfill maximum buy amt mod count';
                            }                              
                         break;                                              
                    } //end switch                                    
                  }
                  $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Buy amt $$ test completed';
                  return; // done, passed the test, both begin and end set...
               }  //end if  
           break;
                       
        }  //end switch  vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_type'] 
      } //end for loop
      
      //if loop reached end of list...
      if ($e >= sizeof($vtprd_rules_set[$i]->inPop_exploded_found_list) ) {
        $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
        $vtprd_rules_set[$i]->rule_processing_msgs[] = 'reached end of inPop_exploded_found_list';
        return;
      }
    } else {//end if 'quantity' or 'currency'
      
      //'nthQuantity' path
      $end_of_nth_test = 'no';         
      //Must do 'for' loop, as exploded list may cross product boundaries and if 'each' the count must be reset...
      for($e=$vtprd_rules_set[$i]->inPop_exploded_group_begin; $end_of_nth_test == 'no'; $e++) {
          $for_loop_elapsed_count++;       
          $temp_end = $vtprd_rules_set[$i]->inPop_exploded_group_begin + $for_loop_elapsed_count;  
          if ( $temp_end > sizeof($vtprd_rules_set[$i]->inPop_exploded_found_list) ) {
             $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
             $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining buy qty for nth';
             return;
          }
          
          switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_applies_to'] ) {             
            case 'each':
                 //check if new product in list...
                 if ($for_loop_current_prod_id != $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_id'] ) {
                    //if new product, reset all tracking fields
                    $for_loop_current_prod_id = $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_id'];
                    $for_loop_unit_count = 1;                
                 } else {
                    $for_loop_unit_count++;                  
                 }
              break;               
            case 'all':
                $for_loop_unit_count++;               
              break;           
          } //end switch  $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_applies_to']        
               
          if ($for_loop_unit_count == $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count']) {
            //Set group_end here.  use $e + 1 since we may have reset the for_loop_unit_count during processing
            $vtprd_rules_set[$i]->inPop_exploded_group_end = $vtprd_rules_set[$i]->inPop_exploded_group_begin + $for_loop_elapsed_count;                               
            if ($vtprd_template_structures_framework[$templateKey]['buy_amt_mod'] > ' ' ) {
              switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_mod'] ) {
                 case 'none':
                    break;
                 case 'minCurrency':                           
                      if ($vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_unit_price'] < $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_mod_count']) { // < is an error, value should be >= 
                        $failed_test_total++;
                        $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                        $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining minimum buy $$ for nth';
                      }
                   break;
                 case 'maxCurrency':
                      if ($vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_unit_price'] > $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_mod_count']) { // > is an error, value should be <= 
                        $failed_test_total++;
                        $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                        $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining maximum buy $$ for nth';
                      }                              
                   break;                                              
              } //end switch                                    
            }
            $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Buy amt Qty Nth test completed';
            return; // done, passed the test, both begin and end set...            
          }  //end if
          
          if ( $e >= sizeof($vtprd_rules_set[$i]->inPop_exploded_found_list) ) { 
            $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
            $vtprd_rules_set[$i]->rule_processing_msgs[] = 'End of inPop reached during Nth processing';
            $end_of_nth_test = 'yes';
            return;
          }         
      } // end for loop $end_of_nth_test
        
    } //end if
 
    return;
 }
 
     //if action_amt_type is active and there is a action_amt count...
    //***********************************************************
    //THIS SETS THE SIZE OF THE BUY exploded GROUP "WINDOW"
    //***********************************************************
 
  public function vtprd_set_action_group_end($i, $d, $ar ) { 
    global $post, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_template_structures_framework;     

    /*
    DETERMINE THE BEGIN AND END OF ACTIONPOP PROCESSING "WINDOW"
    
    1st time, group_end set to 0, end may be set but will be overwritten here
              group_begin remains at 0, since its an OCCURRENCE begin
    2nd-Nth,  group_begin set to previous end + 1
              group_end set to a computed value.  If the required action group size is not reached or end of actionPop reached, 
                  the setup/edit fails.     
    */
    
    $templateKey = $vtprd_rules_set[$i]->rule_template;
    
    $for_loop_current_prod_id;
    $for_loop_unit_count = 0;
    $for_loop_price_total = 0;
    $for_loop_elapsed_count = 0;

    if ( ($vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] == 'quantity') || ($vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] == 'currency') ) {
      $sizeof_actionPop_exploded_found_list = sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list);
      for($e=$vtprd_rules_set[$i]->actionPop_exploded_group_begin; $e < $sizeof_actionPop_exploded_found_list; $e++) {
        $for_loop_elapsed_count++;
        switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] ) {
          
          case 'quantity':         
                $temp_end = $vtprd_rules_set[$i]->actionPop_exploded_group_end + $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count'] ;  
                if ( $temp_end > sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list) ) {
                   $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                   $vtprd_rules_set[$i]->end_of_actionPop_reached = 'yes';
                   $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining qty in cart to fulfill action amt qty';
                   return;
                }               
               switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_applies_to'] ) {             
                  case 'each':
                       //check if new product in list...
                       if ($for_loop_current_prod_id != $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_id'] ) {
                          //if new product, reset all tracking fields
                          $for_loop_current_prod_id = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_id'];
                          $for_loop_unit_count = 1;
                          $for_loop_price_total = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_unit_price'];                     
                       } else {
                          $for_loop_unit_count++;
                          $for_loop_price_total += $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_unit_price'];
                       }
                    break;               
                  case 'all':
                      $for_loop_unit_count++;
                      $for_loop_price_total += $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_unit_price'];                   
                    break;           
               } //end switch  $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_applies_to']            
               if ($for_loop_unit_count == $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count']) {
                  $vtprd_rules_set[$i]->actionPop_exploded_group_end = $vtprd_rules_set[$i]->actionPop_exploded_group_begin + $for_loop_elapsed_count;                     
                  if ($vtprd_template_structures_framework[$templateKey]['action_amt_mod'] > ' ' ) {
                     switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_mod'] ) {
                         case 'none':
                           break;  
                         case 'minCurrency':                           
                              if ($for_loop_price_total < $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_mod_count']) { // < is an error, value should be >= 
                                $failed_test_total++;
                                $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                                $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining $$ in cart to fulfill minimum action amt qty';                                                                                                
                              }
                           break;
                         case 'maxCurrency':
                              if ($for_loop_price_total > $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_mod_count']) { // > is an error, value should be <= 
                                $failed_test_total++;
                                $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                                $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining $$ in cart to fulfill maximum action amt qty';                                
                              }                              
                           break;                                            
                     } //end switch 
                   }                                                   
                  $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Action amt Qty test completed';
                  return; // done, passed the test, both begin and end set...
               }  //end if         
          break;
         
          case 'currency':          
               switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_applies_to'] ) {             
                  case 'each':
                       //check if new product in list...
                       if ($for_loop_current_prod_id != $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_id'] ) {
                          //if new product, reset all tracking fields
                          $for_loop_current_prod_id = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_id'];
                          $for_loop_unit_count = 1;
                          $for_loop_price_total = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_unit_price'];
                       } else {
                          $for_loop_unit_count++;
                          $for_loop_price_total += $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_unit_price'];
                       }
                    break;               
                  case 'all':
                      $for_loop_unit_count++;
                      $for_loop_price_total += $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_unit_price']; 
                    break;           
               } //end switch  $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_applies_to']
               
               if ($for_loop_price_total >= $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count']) {
                  
                  $vtprd_rules_set[$i]->actionPop_exploded_group_end = $vtprd_rules_set[$i]->actionPop_exploded_group_begin + $for_loop_elapsed_count;                     
                                    
                  if ($vtprd_template_structures_framework[$templateKey]['action_amt_mod'] > ' ' ) {
                    switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_mod'] ) {
                       case 'none':
                         break;
                       case 'minCurrency':                           
                            if ($for_loop_price_total < $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_mod_count']) { // < is an error, value should be >= 
                              $failed_test_total++;
                              $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                              $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining $$ in cart to fulfill minimum action amt mod count';
                            }
                         break;
                       case 'maxCurrency':
                            if ($for_loop_price_total > $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_mod_count']) { // > is an error, value should be <= 
                              $failed_test_total++;
                              $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                              $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining $$ in cart to fulfill maximum action amt mod count';                              
                            }                              
                         break;                                              
                    } //end switch                                    
                  }
                  $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Action amt $$ test completed';
                  return; // done, passed the test, both begin and end set...
               }  //end if  
          break;
                       
        }  //end switch  vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] 
      } //end for loop
            
      //if loop dropout + reached end of list...
      if ($e >= sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list) ) {
        $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
        $vtprd_rules_set[$i]->rule_processing_msgs[] = 'reached end of actionPop_exploded_found_list';
        return;
      }
    } else {//end if 'quanity' or 'currency'
      
      //'nthQuantity' path
      $end_of_nth_test = 'no';
      for($e=$vtprd_rules_set[$i]->actionPop_exploded_group_begin; $end_of_nth_test == 'no'; $e++) {
         $for_loop_elapsed_count++;
         $temp_end = $vtprd_rules_set[$i]->actionPop_exploded_group_begin + $for_loop_elapsed_count;  
          if ( $temp_end > sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list) ) {
             $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
             $vtprd_rules_set[$i]->end_of_actionPop_reached = 'yes';
             $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining action qty for nth';
             return;
          }
          
          switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_applies_to'] ) {             
            case 'each':
                 //check if new product in list...
                 if ($for_loop_current_prod_id != $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_id'] ) {
                    //if new product, reset all tracking fields
                    $for_loop_current_prod_id = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_id'];
                    $for_loop_unit_count = 1;
                 } else {
                    $for_loop_unit_count++;
                 }
              break;               
            case 'all':
                $for_loop_unit_count++;
              break;           
          } //end switch  $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_applies_to']        
               
          if ($for_loop_unit_count == $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count']) {
            $vtprd_rules_set[$i]->actionPop_exploded_group_end = $vtprd_rules_set[$i]->actionPop_exploded_group_begin + $for_loop_elapsed_count;                     
            if ($vtprd_template_structures_framework[$templateKey]['action_amt_mod'] > ' ' ) {
              switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_mod'] ) {
                 case 'none':
                    break;
                 case 'minCurrency':                           
                      if ($vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_unit_price'] < $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_mod_count']) { // < is an error, value should be >= 
                        $failed_test_total++;
                        $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                        $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining minimum action $$ for nth';
                      }
                   break;
                 case 'maxCurrency':
                      if ($vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_unit_price'] > $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_mod_count']) { // > is an error, value should be <= 
                        $failed_test_total++;
                        $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                        $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining maximum action $$ for nth';
                      }                              
                   break;                                              
              } //end switch                                    
            }
            $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Action amt Qty Nth test completed';
            return; // done, passed the test, both begin and end set...
          }  //end if        
      } // end for loop $end_of_nth_test
        
    } //end if
    
   return;
 }
 
 /*
 This process treats all of the products/quantities in the cart as a running total.  For each sub-group in the cart, derived from applying the buy_amt_count,
 the group valuation is computed.  if it doesn't fulfill the buy_amt_mod requirements, that part of the cart fails this test (for this rule). 
 */ 
  public function vtprd_buy_amt_mod_all_process($i,$d, $failed_test_total) { 
    global $post, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_template_structures_framework;     
    //walk through the cart imits 1 by 1 until inPop_running_unit_group_begin_pointer reached

    //preset to 'fail', on success it is switched to 'pass' in the routine
    //$vtprd_rules_set[$i]->buy_amt_process_status = 'fail';
    $current_group_pointer = 0;
    $buy_amt_mod_count_elapsed = 0;
    $buy_amt_mod_count_currency_total = 0;

    $sizeof_inPop_found_list = sizeof($vtprd_rules_set[$i]->inPop_found_list);
    for($k=0; $k < $sizeof_inPop_found_list; $k++) {      
    //add this product's unit count to the current_group_pointer
    //   until unit_counter_begin reached or end of unit count 
      for($z=0; $z < $vtprd_rules_set[$i]->inPop_found_list[$k]['prod_qty']; $z++) {
         //this augments the $current_group_pointer until it equals the begin pointer, then stops
         //  from this point on, it's the gateway to the rest of the routine.
         if ($current_group_pointer < $vtprd_rules_set[$i]->inPop_group_begin_pointer) { 
            $current_group_pointer++;
         }         
         if ($current_group_pointer == $vtprd_rules_set[$i]->inPop_group_begin_pointer) {
            //used to track the correct starting point
            $buy_amt_mod_count_elapsed++;
            //total up the unit costs until ...
            $buy_amt_mod_count_currency_total +=  $vtprd_rules_set[$i]->inPop_found_list[$k]['prod_unit_price'];  
            
            //if currency threshhold reached...., test and exit
            if ($buy_amt_mod_count_currency_total >= $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_mod_count']  ) {            
              switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_mod'] ) {
                case 'minCurrency':
                    
                  break;
                case 'maxCurrency':
                  break;
              }
              
              //increment the begin pointer to the end of current group +1 
              $vtprd_rules_set[$i]->inPop_group_begin_pointer = $vtprd_rules_set[$i]->inPop_group_begin_pointer + $buy_amt_mod_count_elapsed + 1 ;
              break 2;  //break out of both for loops and return...
            }
         }
      }
    }                        
     
    return;
 }

  /*
  NO LONGER USED!!!!!!!!!!!!!
  
  //EDITED  -+-+-+-+-+-+-+-+-------
  
  public function vtprd_test_actionPop_conditions($i) {
    global $post, $vtprd_setup_options, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info;         
    
           
    //Test PRODCAT list for min requirements   
    $prodcat_out_checked_min_requirement_total_count = 0; 
    $prodcat_out_checked_min_requirement_value_reached_count = 0;
    
    $sizeof_prodcat_out_checked_info = sizeof($vtprd_rules_set[$i]->prodcat_out_checked_info);
    for($k=0; $k < $sizeof_prodcat_out_checked_info; $k++) {
        if ($vtprd_rules_set[$i]->prodcat_out_checked_info[$term_id]->value > 0) {
          $prodcat_out_checked_min_requirement_total_count++; //track min requirements, increment
          if ($vtprd_rules_set[$i]->prodcat_out_checked_info[$term_id]->value_reached == 'yes')  {           
                $prodcat_out_checked_min_requirement_value_reached_count++; //track all min requirements, increment
          }                
        }
    }
    
    if ( $vtprd_rules_set[$i]->prodcat_out_checked_min_requirement == "one"  ) {
      if ( $prodcat_out_checked_min_requirement_value_reached_count == 0 ) {
        //min requirement not met, exit
        $vtprd_info['actionPop_conditions_met'] = 'no';
        return;
      }    
    } else {  //all required
      if ($prodcat_out_checked_min_requirement_value_reached_count <> $prodcat_out_checked_min_requirement_total_count) {
        //min requirement not met, exit
        $vtprd_info['actionPop_conditions_met'] = 'no';
        return;
      } 
    }

      
    //Test RULECAT list for min requirements   
    $rulecat_out_checked_min_requirement_total_count = 0; 
    $rulecat_out_checked_min_requirement_value_reached_count = 0;
    
    $sizeof_rulecat_out_checked_info = sizeof($vtprd_rules_set[$i]->rulecat_out_checked_info); 
    for($k=0; $k < $sizeof_rulecat_out_checked_info; $k++) {
        if ($vtprd_rules_set[$i]->rulecat_out_checked_info[$term_id]->value > 0) {
          $rulecat_out_checked_min_requirement_total_count++; //track min requirements, increment
          if ($vtprd_rules_set[$i]->rulecat_out_checked_info[$term_id]->value_reached == 'yes')  {           
                $rulecat_out_checked_min_requirement_value_reached_count++; //track all min requirements, increment
          }                
        }
    }
    
    if ( $vtprd_rules_set[$i]->rulecat_out_checked_min_requirement == "one"  ) {
      if ( $rulecat_out_checked_min_requirement_value_reached_count == 0 ) {
        //min requirement not met, exit
        $vtprd_info['actionPop_conditions_met'] = 'no';
        return;
      }    
    } else {  //all required
      if ($rulecat_out_checked_min_requirement_value_reached_count <> $rulecat_out_checked_min_requirement_total_count) {
        //min requirement not met, exit
        $vtprd_info['actionPop_conditions_met'] = 'no';
        return;
      } 
    }                                 
   
      
    $vtprd_info['actionPop_conditions_met'] = 'yes';
    return;  
         
  }
  */  
        
   public function vtprd_is_product_in_inPop_group($i, $k) { 
 
     //EDITED * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * + 
 
      return false;
   } 
  
    public function vtprd_is_role_in_inPop_list_check($i,$k) {
 
     //EDITED * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * + 
 
      return false;
    }
    
    public function vtprd_are_cats_in_inPop_list_check($i,$k) {
 
     //EDITED * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * + 
 
      return false;
    }    


         
   public function vtprd_is_product_in_actionPop_group($i,$k) { 
 
     //EDITED * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * + 
 
      return false;
   } 
  
    public function vtprd_is_role_in_actionPop_list_check($i,$k) {
 
     //EDITED * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * + 
 
      return false;
    }
    
    public function vtprd_are_cats_in_actionPop_list_check($i,$k) {
 
     //EDITED * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * + 
 
      return false;
    }    

      
    public function vtprd_list_out_product_names($i) {
      $prodnames;
    	global $vtprd_rules_set;     
    	for($p=0; $p < sizeof($vtprd_rules_set[$i]->errProds_names); $p++) {
          $prodnames .= __(' "', 'vtprd');
          $prodnames .= $vtprd_rules_set[$i]->errProds_names[$p];
          $prodnames .= __('"  ', 'vtprd');
      } 
    	return $prodnames;
    }
      
   public function vtprd_load_inPop_found_list($i,$k) {
    	global $vtprd_cart, $vtprd_rules_set, $vtprd_info;
    
      //******************************************
      //****  CHECK for PRODUCT EXCLUSIONS 
      //******************************************   
 
     //EDITED * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * + 
   
      //END product exclusions check
       
       
     // $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['at_least_one_inPop_product_found_in_rule']  = 'yes';
      
      $vtprd_rules_set[$i]->inPop_found_list[] = array('prod_id' => $vtprd_cart->cart_items[$k]->product_id,
                                                       'prod_name' => $vtprd_cart->cart_items[$k]->product_name,
                                                       'prod_qty' => $vtprd_cart->cart_items[$k]->quantity,
                                                       'prod_running_qty' => $vtprd_cart->cart_items[$k]->quantity,
                                                       'prod_unit_price' => $vtprd_cart->cart_items[$k]->unit_price,
                                                       'prod_db_unit_price' => $vtprd_cart->cart_items[$k]->db_unit_price, 
                                                       'prod_total_price' => $vtprd_cart->cart_items[$k]->total_price,
                                                       'prod_running_total_price' => $vtprd_cart->cart_items[$k]->total_price,
                                                       'prod_cat_list' => $vtprd_cart->cart_items[$k]->prod_cat_list,
                                                       'rule_cat_list' => $vtprd_cart->cart_items[$k]->rule_cat_list,
                                                       'prod_id_cart_occurrence' => $k //used to mark product in cart if failed a rule                                                    
                                                      );
     $vtprd_rules_set[$i]->inPop_qty_total   += $vtprd_cart->cart_items[$k]->quantity;
     $vtprd_rules_set[$i]->inPop_total_price += $vtprd_cart->cart_items[$k]->total_price;
     $vtprd_rules_set[$i]->inPop_running_qty_total   += $vtprd_cart->cart_items[$k]->quantity;
     $vtprd_rules_set[$i]->inPop_running_total_price += $vtprd_cart->cart_items[$k]->total_price;

     if ($vtprd_rules_set[$i]->rule_execution_type == 'display') {
        $vtprd_cart->cart_items[$k]->product_in_rule_allowing_display = 'yes';     
     }
     
    //*****************************************************************************
    //EXPLODE out the cart into individual unit quantity lines for DISCOUNT processing
    //*****************************************************************************
    for($e=0; $e < $vtprd_cart->cart_items[$k]->quantity; $e++) {            
      $vtprd_rules_set[$i]->inPop_exploded_found_list[] = array(
                                                       'prod_id' => $vtprd_cart->cart_items[$k]->product_id,
                                                       'prod_name' => $vtprd_cart->cart_items[$k]->product_name,
                                                       'prod_qty' => 1,
                                                       'prod_unit_price' => $vtprd_cart->cart_items[$k]->unit_price,
                                                       'prod_db_unit_price' => $vtprd_cart->cart_items[$k]->db_unit_price, 
                                                       'prod_db_unit_price_list' => $vtprd_cart->cart_items[$k]->db_unit_price_list,
                                                       'prod_db_unit_price_special' => $vtprd_cart->cart_items[$k]->db_unit_price_special,
                                                       'prod_id_cart_occurrence' => $k, //used to mark product in cart if failed a rule
                                                       'exploded_group_occurrence' => $e,
                                                       'prod_discount_amt'  => 0,
                                                       'prod_discount_applied'  => ''
                                                      );          
  //    $vtprd_rules_set[$i]->inPop_exploded_group_occurrence++;
      $vtprd_rules_set[$i]->inPop_exploded_group_occurrence = $e;
    } //end explode
    
    $vtprd_rules_set[$i]->inPop_prodIds_array [] = $vtprd_cart->cart_items[$k]->product_id; //used only when searching for sameAsInpop
      
    $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Product participates in buy population';              
    $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['rule_short_msg'] = $vtprd_rules_set[$i]->discount_product_short_msg;
    $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['rule_full_msg']  = $vtprd_rules_set[$i]->discount_product_full_msg;    
  }
    
        
   public function vtprd_load_actionPop_found_list($i,$k) {
    	global $vtprd_cart, $vtprd_rules_set, $vtprd_info;

      //******************************************
      //****  CHECK for PRODUCT EXCLUSIONS 
      //******************************************     
 
     //EDITED * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * + 
 
                         
      //END product exclusions check
      
      $prod_unit_price = $vtprd_cart->cart_items[$k]->unit_price;
      //Skip if item already on sale and switch = no
      if ($vtprd_cart->cart_items[$k]->product_is_on_special == 'yes')  {
          if ( $vtprd_rules_set[$i]->cumulativeSalePricing == 'no') { 
            //product already on sale, can't apply further discount
            $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_status'] = 'rejected';
            $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] =  'No Discount - product already on sale, can"t apply further discount - discount in addition to sale pricing not allowed';
            return;
          } else {
            //overwrite the sale price with the original unit price when applying IN PLACE OF the sale price
            $prod_unit_price = $vtprd_cart->cart_items[$k]->db_unit_price;
          }         
     }

      $vtprd_cart->at_least_one_rule_actionPop_product_found = 'yes'; //mark rule for further processing
      
      $vtprd_rules_set[$i]->actionPop_found_list[] = array('prod_id' => $vtprd_cart->cart_items[$k]->product_id,
                                                       'prod_name' => $vtprd_cart->cart_items[$k]->product_name,
                                                       'prod_qty' => $vtprd_cart->cart_items[$k]->quantity,
                                                       'prod_running_qty' => $vtprd_cart->cart_items[$k]->quantity, 
                                                       'prod_unit_price' => $prod_unit_price,
                                                       'prod_db_unit_price' => $vtprd_cart->cart_items[$k]->db_unit_price,
                                                       'prod_total_price' => $vtprd_cart->cart_items[$k]->total_price,
                                                       'prod_running_total_price' => $vtprd_cart->cart_items[$k]->total_price,
                                                       'prod_cat_list' => $vtprd_cart->cart_items[$k]->prod_cat_list,
                                                       'rule_cat_list' => $vtprd_cart->cart_items[$k]->rule_cat_list,
                                                       'prod_id_cart_occurrence' => $k //used to access product in later processing
                                                      );
     $vtprd_rules_set[$i]->actionPop_qty_total   += $vtprd_cart->cart_items[$k]->quantity;
     $vtprd_rules_set[$i]->actionPop_total_price += $vtprd_cart->cart_items[$k]->total_price;
     $vtprd_rules_set[$i]->actionPop_running_qty_total   += $vtprd_cart->cart_items[$k]->quantity;
     $vtprd_rules_set[$i]->actionPop_running_total_price += $vtprd_cart->cart_items[$k]->total_price;
          
     $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['rule_short_msg'] = $vtprd_rules_set[$i]->discount_product_short_msg;
     $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['rule_full_msg']  = $vtprd_rules_set[$i]->discount_product_full_msg; 
          
     if ($vtprd_rules_set[$i]->rule_execution_type == 'display') {
        $vtprd_cart->cart_items[$k]->product_in_rule_allowing_display = 'yes';     
     }
     
    //*****************************************************************************
    //EXPLODE out the cart into individual unit quantity lines for DISCOUNT processing
    //*****************************************************************************
    for($e=0; $e < $vtprd_cart->cart_items[$k]->quantity; $e++) {       
      $vtprd_rules_set[$i]->actionPop_exploded_found_list[] = array('prod_id' => $vtprd_cart->cart_items[$k]->product_id,
                                                       'prod_name' => $vtprd_cart->cart_items[$k]->product_name,
                                                       'prod_qty' => 1,
                                                       'prod_unit_price' => $vtprd_cart->cart_items[$k]->unit_price,
                                                       'prod_db_unit_price' => $vtprd_cart->cart_items[$k]->db_unit_price, 
                                                       'prod_db_unit_price_list' => $vtprd_cart->cart_items[$k]->db_unit_price_list,
                                                       'prod_db_unit_price_special' => $vtprd_cart->cart_items[$k]->db_unit_price_special,
                                                       'prod_id_cart_occurrence' => $k, //used to mark product in cart if failed a rule
                                                       'exploded_group_occurrence' => $e,
                                                       'prod_discount_amt'  => 0,
                                                       'prod_discount_applied'  => ''
                                                      );          
   //   $vtprd_rules_set[$i]->actionPop_exploded_group_occurrence++;
      $vtprd_rules_set[$i]->actionPop_exploded_group_occurrence = $e;
    } //end explode
  
    $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['actionPop_participation_msgs'][] = 'Product participates in action population';
  
   }
 
      
  public function vtprd_init_recursive_work_elements($i){ 
    global $vtprd_rules_set;
    $vtprd_rules_set[$i]->errProds_qty = 0 ;
    $vtprd_rules_set[$i]->errProds_total_price = 0 ;
    $vtprd_rules_set[$i]->errProds_ids = array() ;
    $vtprd_rules_set[$i]->errProds_names = array() ;    
  }
  public function vtprd_init_cat_work_elements($i){ 
    global $vtprd_rules_set;
    $vtprd_rules_set[$i]->errProds_cat_names = array() ;             
  }     


   public  function vtprd_sort_rules_set_for_cart() {
    global $vtprd_cart, $vtprd_rules_set;

    //***********************************************************
    //DELETE ALL "DISPLAY" RULES from the array for this iteration, leaving only the 'cart' rules
    //***********************************************************
     if ( sizeof($vtprd_rules_set) > 0) {    
        foreach ($vtprd_rules_set as $key => $rule )  {
           if ($rule->rule_execution_type == 'display') {
              unset( $vtprd_rules_set[$key]);           
           }      
        } 
                
        //reknit the array to get rid of any holes
        $vtprd_rules_set = array_values($vtprd_rules_set);  
     }    

     //****
     //SORT  if any rules are left...
     //****
     if ( sizeof($vtprd_rules_set) > 1) {
        $this->vtprd_sort_rules_set(); 
     } 
     
     return;
  }


   public  function vtprd_sort_rules_set_for_display() {
     global $vtprd_cart, $vtprd_rules_set;

      //***********************************************************
      //DELETE ALL "CART" RULES from the array for this iteration, leaving only the 'display' rules
      //***********************************************************     
     if ( sizeof($vtprd_rules_set) > 0) {         
        foreach ($vtprd_rules_set as $key => $rule )  {
           if ($rule->rule_execution_type == 'cart') {
              unset( $vtprd_rules_set[$key]);           
           }      
        } 
                
        //reknit the array to get rid of any holes
        $vtprd_rules_set = array_values($vtprd_rules_set);  
     }
     
     //****
     //SORT   if any rules are left...
     //****
     if ( sizeof($vtprd_rules_set) > 1) {
        $this->vtprd_sort_rules_set(); 
     }
    
    return;
  }

   public  function vtprd_sort_rules_set() {
     global $vtprd_cart, $vtprd_rules_set;

      //http://stackoverflow.com/questions/3232965/sort-multidimensional-array-by-multiple-keys
      // excellent example here:   http://cybernet-computing.com/news/blog/php-sort-array-multiple-fields
      $rule_execution_type = array();
      $rule_contains_free_product = array();
      $ruleApplicationPriority_num = array();
      
      $sizeof_rules_set = sizeof($vtprd_rules_set);
      for($i=0; $i < $sizeof_rules_set; $i++) { 
      //  $rule_execution_type[]          =  $vtprd_rules_set[$i]->rule_execution_type;
        $rule_contains_free_product[]   =  $vtprd_rules_set[$i]->rule_contains_free_product;
        $ruleApplicationPriority_num[]  =  $vtprd_rules_set[$i]->ruleApplicationPriority_num;
      }
      array_multisort(
      //  $rule_execution_type, SORT_DESC, //display / cart  
        $rule_contains_free_product, SORT_DESC,   // yes / no / [blank]]
        $ruleApplicationPriority_num, SORT_ASC,     // 0 => on up
        
			  $vtprd_rules_set  //applies all the sort parameters to the object in question
      ); 
    
    return;
  }
  
  
   public  function vtprd_init_cartAuditTrail($i,$k) {
    global $vtprd_cart, $vtprd_rules_set;  
    $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id] = array(  
          'ruleset_occurrence'          => $i,
          'inPop'                       => $vtprd_rules_set[$i]->inPop, 
          'inPop_prod_cat_found'        => '' ,   
          'inPop_rule_cat_found'        => '' ,
          'inPop_and_required'          => '' ,  
          'userRole'            				=> '' ,
          'inPop_role_found'            => '' ,  
          'inPop_single_found'          => '' , 
          'inPop_variation_found'       => '' ,
          'at_least_one_inPop_product_found_in_rule' => '' ,          
          'product_in_inPop'            => '' ,  
          
          'actionPop'                   => $vtprd_rules_set[$i]->actionPop,   
          'actionPop_prod_cat_found'    => '' ,  
          'actionPop_rule_cat_found'    => '' ,
          'actionPop_and_required'      => '' ,  
          'actionPop_role_found'        => '' , 
          'actionPop_single_found'      => '' ,  
          'actionPop_variation_found'   => '' ,
          'product_in_actionPop'        => '' ,
                      
          'rule_priority'               => '',    // y/n
          
          'discount_status'             => '',
          'discount_msgs'               => array(),
          'discount_amt'                => '',
          'discount_pct'                => '',
          
          // if 'product_in_actionPop' == yes, messages are filled in
          'rule_short_msg'              => '' ,
          'rule_full_msg'               => ''       
    ); 
 
    return;   
  }
                                       


      //autoadds AREA
      //EDITED * + * +  * + * +  * + * +  * + * + 
       
      //EXCEPT::::::::::::  ====>>>>>>>>>>
  
  //***********************************************************
  // If a product(s) has been given a 'Free' discount, it can't get
  //     any further discounts.
  //   Roll the product 'free' qty out of the rest of the rules actionPop arrays
  //      so that they can't be found when searching for other discounts
  //***********************************************************     
   public  function vtprd_roll_free_products_out_of_other_rules($i) {
		global $vtprd_cart, $vtprd_rules_set, $vtprd_info, $vtprd_setup_options, $vtprd_rule;     

    $sizeof_ruleset = sizeof($vtprd_rules_set);
    
    //for this rule's free_product_array, roll out these products from all other rules...
    foreach($vtprd_rules_set[$i]->free_product_array as $free_product_key => $free_qty) {  
      
      for($rule=0; $rule < $sizeof_ruleset; $rule++) {

        //skip if we're on the rule initiating the free product array logic
        if  ( ($vtprd_rules_set[$rule]->post_id == $vtprd_rules_set[$i]->post_id) ||      //1.0.7.2
              ($vtprd_rules_set[$rule]->rule_status != 'publish') ) {                     //1.0.7.2 added in != 'publish' test
          continue; 
        }
        
        //delete as many of the product from the actionpop array as there are free qty
        $delete_qty = $free_qty;
        foreach ($vtprd_rules_set[$rule]->actionPop_exploded_found_list as $actionPop_key => $actionPop_exploded_found_list )  {
           if ($actionPop_exploded_found_list['prod_id'] == $free_product_key) {
              
              //as each row has a quantity of 1, unset is the way to go....
              //from  http://stackoverflow.com/questions/2304570/how-to-delete-object-from-array-inside-foreach-loop
              unset( $vtprd_rules_set[$rule]->actionPop_exploded_found_list[$actionPop_key]);           
              
              $delete_qty -= 1;
           }
           
           if ($delete_qty == 0) {
             break;
           }
           
        } //end "for" loop unsetting the free product
        
        //if any unsets were done, need to re-knit the array so that there are no gaps...
        //    from    http://stackoverflow.com/questions/1748006/what-is-the-best-way-to-delete-array-item-in-php/1748132#1748132
        //            $a = array_values($a);
        if ($delete_qty < $free_qty) {          
          $vtprd_rules_set[$rule]->actionPop_exploded_found_list = array_values($vtprd_rules_set[$rule]->actionPop_exploded_found_list);
        }
      
      } //end "for"  rule loop
      
    } //end foreach free product
    
    return;
  }  
 


   
} //end class