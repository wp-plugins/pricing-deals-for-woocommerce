<?php
 /*
   Rules are stored on the WP database as custom posts with custom field attributes.  At rule store/update
   time, a master rule option array is (re)created, to allow speedier access to rule information at
   product/cart processing time.
 */
 
 /*
 PAGE-BASED CONTEXTUAL HELP - each literal on the page will be a clickable link, popping up in prettyphoto with text and possibly a very short video, rather than all the text...
 
 GET THE PRICE off of the DB each time the apply is called, as a change to the cart contents can rejigger the whole setup.
 in wpsc    cart.class.php:
    $price = get_post_meta( $product_id, '_wpsc_price', true );
   	$special_price = get_post_meta( $product_id, '_wpsc_special_price', true );
    if ( isset( $special_price ) && $special_price > 0 && $special_price < $price )
   		$price = $special_price;

   PRICING DATABASE INFO STORAGE
 
        STORE THE INFO PRIOR TO CHECKOUT, WITH 'PENDING' STATUS.
        CHANGE STATUS TO 'SOLD' AT PAYMENT TIME (don't have to redo computations....)
 */

       //*******************************************
      //  FOR ALL optional / not selected options, unless otherwise noted, default to 'none'
      //*******************************************

class VTPRD_Rule {
	   public  $post_id;
     
     /*    RULE STATUS
     *   rule status = pending or publish  => 
     *        if status is 'pending', the rule will not be executed during cart processing
     *   rule status will be set to 'pending' 
     *      => when errors have been detected during update process
     *      => when the custom post type status has been changed to 'trash'              
     */
     public  $rule_status; 
     public  $rule_processing_status;
     public  $rule_processing_msgs;
     
     
     /*
          RULE TYPE IS DISPLAY
          RULE TYPE IS CART
          
          2 LISTS THAT SLIDE ONTO THE PAGE, DEPENDING ON  $rule_execution_type_selected
     */


     //******************************************
     // PURCHASE discount / DYNAMIc (page DISPLAY) realtime discount  ==> *******   APPLIES TO ALL PRODUCTS DISPLAYED ******
     // $rule_execution_type_selected CALL FROM:
     //   display   = reduce price at product display time, 
     //   cart  = reduce price only at add-to-cart, cart processing and checkout time [default]
     //******************************************
     public  $rule_execution_type;    

     //these two are selected off of the rule type dropdown.
     public  $rule_template;   //internal only, key value reference to $vtprd_rule_type_[x]_framework, for debugging
     public  $rule_template_name;  //internal only, reference to $vtprd_rule_type_[x]_framework, for debugging
     // from template switches, placed here for ease of review
     public  $discountAppliesWhere;   // 'allActionPop' / 'inCurrentInPopOnly'  / 'nextInInPop' / 'nextInActionPop' / 'inActionPop' /   
     //---------------------

     // deal structure array
     public  $rule_deal_structure_type;  //repeating values (single layer) / ascending values (next) (no repeating values) / 
                                        //   descending values (buy 5 get x, buy 10 get y etc) -  for descending, the deal_info array would have to be processed in reverse (no repeating values). 
    // deal structure array
     public  $rule_deal_info;
 
   //these are all now in the deal line structure for editing reasons
  //   public  $discount_rule_max_amt_type = 'all';   
  //   public  $discount_rule_max_amt_count;
     // $discount_rule_max_amt_type and $discount_rule_max_amt_count moded to deal structure...
   //  public  $discount_lifetime_max_amt;
        
     public  $discount_rule_max_amt_msg;
     public  $discount_lifetime_max_amt_msg;
     public  $discount_rule_cum_max_amt_msg;
     // always required
     public  $discount_product_short_msg;  //short msg shown in cart and checkout 
     public  $discount_product_full_msg;  //message notification of specials auto on product detail y/n  ==>> specials always avail via shortcode/template code ....  Specials messaging is customizable by rule

   
     //******************************************
     //RULE INTERACTIONS - CUMULATIVE PRICING  => Dynamic Pricing now supports cumulative rule processing via a configurable filter. When dynamic pricing processes the discount rules to apply, it will first check if the item is discounted by some other rule. If the item has already been discounted, a filter will be called allowing you to configure the cumulative nature of the processing. If you choose, you can have dynamic pricing either stop processing and only apply the discounts applied prior, or allowing dynamic pricing to continue processing the cumulative discount. A useful example is to give Members 10% off all items in your catalog, and an additional 50% a special item for members. This is just one use case, however with this new functionality, more powerful and easier to configure discount situations are possibile. 
     //******************************************
     // always required
     public  $cumulativeRulePricing;     // yes / no (def)    apply multiple rule reductions, if applicable
     public  $cumulativeSalePricing;     // no (def) / addToSalePrice / replaceSalePrice
     public  $cumulativeCouponPricing;   // yes / no (def)     apply in addition to coupon price
  //   public  $cumulativeFloorDiscount;   // yes / no (def)     apply in addition to coupon price      
    
     // always optional, defaults to 5!
     public  $ruleApplicationPriority_num;  //if there are multiple rules applicable to a product, which one gets applied first?  Higher number goes first. if a tie is found, 1st rule tested is applied                                    

     public  $discount_applies_within_pool; 

     //******************************************
     // begin INPUT POPULATION  
     //******************************************   
     //candidate population, checked arrays
    
     /*   inPop General usage
      - All products (Lable for Display rule: "in catalog" / Lable for Cart rule: "in cart"  ')
      - Selection groups (by cat / custom tax cat / membership / wholesale )
      - single product [within selection groups]  <=always
      - single product with variation [within selection groups]   <=always    
     */     

     public  $inPop;
     
    //*******************************
     /*  inPop_plus_group     ==> not necessary, can do a single product in a custom tax category along with user role
      Only exposed for:
        single product
        single product with variation 
      Valid values:
         All (Display rule: "in catalog" / Cart rule: "in cart"  ')
         And (Within selection groups)
     
     public  $inPop_plus_group;
     public  $inPop_plus_group;
     //*******************************  
     */
          
     //single with variations
     public  $inPop_varProdID;
     public  $inPop_varProdID_name;
     public  $var_in_checked;
     
     //*******************************
     /*  this is for Display rule messaging only, does not affect processing otherwise
     *UI - A count is made of all checkboxes at inpop variation list display time, 
     * and placed in a hidden UI screen field, both in regular and ajax process
     *UPDATE - takes the hidden screen field checkboxes count, and compares it with
     * the checked box count, generating the literal below  */
     //*******************************  
     public  $inPop_varProdID_parentLit; //'one', 'some' or 'all' => how many are on sale...          

         
     //single product
     public  $inPop_singleProdID;
     public  $inPop_singleProdID_name;
     
     //group choices       
     public  $prodcat_in_checked;
     public  $rulecat_in_checked;      
     public  $role_in_checked;
     public  $role_and_or_in; 
     
     //candidate population handling - these values occur once    
     public  $specChoice_in;  //              any - max number  :: CHANGE TO THRESSHOLD NUMBER   that gateway group is applied to     
     public  $anyChoiceIn_max;  //max can be units/$$$$/times

     public  $amtSelectedIn;
     
     public  $inPop_threshHold_amt;
        
     // end  INPUT POPULATION
     //******************************************
    
      
     
     //******************************************
     // begin OUTPUT POPULATION  
     //******************************************    
     //candidate population, checked arrays
   // Same as input group [default]  /  All in Cart /  Use Selection Groups /  Single Product with Variations /   Single Product Only
     public  $actionPop;     
     //single with variations
     public  $actionPop_varProdID;
     public  $actionPop_varProdID_name;
     
     public  $var_out_checked; 

     /*
      an array containing a *single* variation's 
        term_taxonomy as index
        term_taxonomy_id as data
      needed for auto-insert of free products
      (array group id and array option value...)
     */     
     public  $var_out_product_variations_parameter ; 
 
     //single product
     public  $actionPop_singleProdID;
     public  $actionPop_singleProdID_name;
     //group choices       
     public  $prodcat_out_checked;
     public  $rulecat_out_checked;     
     public  $role_out_checked;
     public  $role_and_or_out; 
   
     //     all/each/any     
     public  $specChoice_out;  //              any - max number  :: CHANGE TO THRESSHOLD NUMBER   that gateway group is applied to     
     public  $anyChoiceout_max;
     //boGO number (get ONE)  type =>  unit/$$$/all/cheapest/most expensive
     public  $amtSelectedOut;

     //****************************************** 
     // end OUTPUT POPULATION
     //****************************************** 
    
     //******************************************
     //Template-based switches, set at rule update time
     //******************************************
     public  $inPopAllowed; 
     public  $actionPopAllowed;  
     public  $discountTypeAllowed;
     public  $discountTypeAllowedArray;
     public  $cumulativeRulePricingAllowed;   
     public  $cumulativeSalePricingAllowed;   
     public  $cumulativeCouponPricingAllowed;
     public  $ruleInWords; //text summary of rule selections  
     // end  TEMPLATE SWITCHES 
     
     
     //******************************************
     // begin DATE GATEWAY FUNCTIONS
     //******************************************  
     //       do we do any date processing
     //           'yes' clicked exposes the next level of options...
     //           only one type of periodicity per rule allowed 
     public  $periodicityApplicable;                 // no / pattern / singleDates / dateRange 
     //       date processing types
     public  $periodicByPattern;                     //  periodic dropdown options: daily/weekly/monthly/annual / every mon/tues/weds etc
     public  $periodicByPattern_begins_on;           //date/day of the week/month
     
     public  $periodicBySingleDates;                  //  periodic date selected from date chooser
                /*   array (
                       $periodicBySingleDate   //   allow multiple occurrences, added by Ajax 
               ) */  
                 
     
     public  $periodicByDateRange;                   // periodic date selected from date chooser
               /*   array (
                       $periodicByDateRange_begins   //   allow multiple occurrences, added by Ajax 
                       $periodicByDateRange_ends     //   allow multiple occurrences, added by Ajax
               ) */              
     // end  DATE FUNCTIONS
     //******************************************  
   
       
     //******************************************
     // begin Pricing Rules LIMITS and TRACKING 
     //******************************************      
 
     
     public  $rule_application_discount_savings; //available via shortcode
     
     //(data fields only, shown on rule screen in right column as usage reporting tool) 
     //  these fields only updated at PURCHASE time!!! 
     public  $rule_application_TotalUnits;
     public  $rule_application_TotalDollars;
     public  $rule_application_TotalCartOcurrences;    
     public  $rule_application_daily_tracking; //array
               /*  rule_application_date
                   rule_application_discount_units
                   rule_application_discount_dollars
                   rule_application_discount_CartOcurrences
               */
   
    // end Pricing Rule TRACKING
     //******************************************     
      
     //******************************************   
     // begin PRICING DEAL ACTION    
     //****************************************** 
     //  
     //   public  $pricingAction_AppliesTo;            //whole output group (def) / part of output group [hides the type fields, exposes recurring iteration group below]
     
     //RECURRING ITERATION GROUP, all are defined as 2-level arrays, which match together via occurrence #  
     //   Just like the custom fields plugin, ajaxified to 'new group' button, 'hide group' etc...  
     //         advanced-custom-fields\js\input-actions.js    (clone new groups, drag n drop)
     //         advanced-custom-fields\core\fields\repeater.php
     //  1 2 3 4 5
     public  $pricingAction_appliesRecursively; // y/n    does the rule get applied more than once within a cart

     public  $pricingAction_recursive_application_max; // any / max number 
     
     public  $pricingAction_Qualified;        //action required for input group to qualify: 'y' = $/qty must be purchased, 'n'= no input group qualification required
     public  $pricingAction_QualifiedAmt;        // amt of '$'' or 'units' for qualification amount
     public  $pricingAction_QualifiedAmtIdent; // '$ '(def, by currency symbol) / 'units'
          
     public  $pricingAction_Amount;
     
     //     $ off / %$ off /  *** FREE ***   / Qty amt at reduced price / Qty % at reduced price
     public  $pricingAction_Type;                 //default = '%' => how the 'off' value is created - as in percent off, $value off, etc    //values: percent of dollar value / dollar /  percent of quantity value  / quantity
        
     
     public  $pricingAction_Direction;            // '-' (def) / '+' 

         
     public  $pricingAction_AppliesTo;            // 'all as a group' [all] (def) / 'each within the group' [each]

     
     public  $pricingAction_Count;                // 'how many' - applies to the 1st X of the group , works with both all/each - 'all' (def)
       //values in dropdown: 'all' 1-101, 200, 300, 500, 1000,  
        /*$pricingAction_Count =>  all/#(number)/rest (remainder) 
        ==>>     The logic is recursive,  allowing each pricing action to cherry pick off of the action population
        ==>>     "for the 1st 2, 10% off, for the 2nd 2 20% off, for the rest, 25% off"
        */
     
     public  $discountApplicationOrder;    //coupons first / (d) rules first   => floor percentage logic only applies to rules, best to allow coupons to process first
      /*
          coupons are usually entered only at checkout.  if coupons are allowed, the true pricing deal $$ can only be accurately computed after coupons are applied
      */
    
   //******************************************
     // TAXATION
     //******************************************
     public  $taxOnWhichPrice;    //reduced price/original price  
          
     
     //******************************************
     // SHIPPING  => if ship on original price, have to interact with shipping call - put in original $$ just before shipping call, put back reduced amount after shipping call
     //    ** FUTURE ENHANCEMENT  **
     //******************************************
     public  $shipOnWhichPrice;    //reduced price/original price 

     public  $only_for_this_coupon_name;    //v1.1.0.8  only apply if matching coupon PRESENTED at checkout

/*
     WHAT ABOUT TAXATION?  IF TAXES ARE INCLUDED IN THE PRICING, HOW DOES THAT WORK???
     
     IF THERE ARE OTHER DISCOUNTS, DO THESE RULES GET APPLIED FIRST????
     
     2ND-TIER PROMOTIONS, ONLY AVAILABLE IF 1ST TIER PROMOTION ATTAINED...
*/     
     //******************************************
     //begin Temporary Processing  AREA
     //******************************************
        
     /*********************
     * error messages during admin rule creation - if error message, 
     *      overall rule status is pending, 
     *           ie inactive relative to ecommerce purchases
     *********************    */
     public  $rule_error_message;
         /*array (
              array (
                'insert_error_before_selector' => '',
                'error_msg' => ''
              )
            )  */        
     public  $rule_error_red_fields;  //array of selectors to turn red using inline css
     public  $rule_error_box_fields;  //array of selectors to turn red using inline css
     /*********************
       * New Dropdowns for UI screen
     *********************    */     
     public  $cart_or_catalog_select;
     public  $pricing_type_select;
     public  $minimum_purchase_select;
     public  $buy_group_filter_select;
     public  $get_group_filter_select;
     public  $rule_on_off_sw_select;     
     
     //log if any iteration has auto-add selected 
     public  $rule_contains_auto_add_free_product;
     public  $rule_contains_free_product; //for sorting purposes, allows free stuff to happen 1st, and then disallow further discounts for the free product     
     //does the rule trigger auto adds from an external group, or the same product?
     public  $auto_add_free_trigger_rule_type; //external or same_product

     public  $set_actionPop_same_as_inPop;  //if group filter pop is set exactly the same....
     
     public  $rule_type_select;
     
     public  $wizard_on_off_sw_select; //addition of wizard to screen... defaults to  'on'
     
     public  $advertising_msg_badge_sw; //v1.0.9.0  if this is on, span is created which allows a badge to be attached using css
     //*********************
   
     //******************************************
     //temp data loaded only at rule processing time, not retained in storage
     //******************************************
     public  $inPop_found_list;
     public  $actionPop_found_list;
     public  $inPop_prodIds_array; //used only in 'sameAsInpo' processing
     
     public  $inPop_free_product_group_count;
     public  $inPop_exploded_found_list;                                  
     public  $inPop_exploded_group_begin;
     public  $inPop_exploded_group_end;
     public  $inPop_exploded_group_occurrence;
     public  $actionPop_exploded_found_list;
     public  $actionPop_exploded_group_begin;
     public  $actionPop_exploded_group_end;
     public  $actionPop_exploded_group_occurrence;
     public  $end_of_inPop_reached; 
     public  $discount_applied;
     public  $discount_processing_status;
     
     public  $inPop_qty_total;
     public  $inPop_total_price;
     public  $inPop_running_qty_total;
     public  $inPop_running_nth_qty_total;
     public  $inPop_running_total_price;
     public  $inPop_running_nth_total_price;
     public  $inPop_group_begin_pointer;
     public  $actionPop_qty_total;
     public  $actionPop_total_price;
     public  $actionPop_running_qty_total;
     public  $actionPop_running_nth_qty_total;
     public  $actionPop_running_total_price;
     public  $actionPop_group_begin_pointer;
     public  $actionPop_running_nth_total_price;
     public  $actionPop_rule_yousave_amt;
     public  $actionPop_rule_yousave_qty;
     public  $actionPop_rule_yousave_pct;
     public  $end_of_actionPop_reached;
     
     public  $buy_amt_process_status;
 
     public  $buy_repeat_condition_satisfied;
     public  $buy_repeat_activity_completed;
     public  $actionPop_repeat_condition_satisfied;
     public  $actionPop_repeat_activity_completed;  
 
     public  $rule_requires_cart_action;  // yes=apply rule, no=skip
     public  $errProds_qty;
     public  $discount_total_qty_for_rule;
     public  $discount_total_amt_for_rule;
     public  $discount_total_unit_price_for_rule;
     public  $discount_total_pct_for_rule;
     public  $errProds_ids;
     public  $errProds_names;
     public  $errProds_cat_names;
     
     public  $rule_template_occurrence;
     public  $rule_processing_trail; //cart tracking array
     public  $free_product_array; //products with free discount for this rule
     
     public  $purch_hist_rule_row_id;   
     public  $purch_hist_rule_row_qty_total_orig;
     public  $purch_hist_rule_row_qty_total_plus_discounts;  
     public  $purch_hist_rule_row_price_total_orig;
     public  $purch_hist_rule_row_price_total_plus_discounts;
     public  $purch_hist_rule_percent_total;      
     //purchaser address debug info
     public  $purch_hist_found_why;
     public  $auto_add_inserted_array; 
       /*
       array (
         'prod_id' => $free_product_id,
         'qty'     => $current_auto_add_array[$free_product_id]['action_inserted_qty']
        );
        */
        
     public  $coupons_amount_without_rule_discounts;  //TOTAL $$ value of USER-ENTERED coupons (only the 0 iteration is used)
     public  $auto_add_inserted_total_for_rule_repeat;  //v1.1.0.6
     public  $auto_add_inserted_total_for_rule;   //v1.1.0.6       
     //******************************************
   
	public function __construct(){
  
     $this->post_id = ' ';  //id of custom post rule
     $this->rule_status = ' ';  //pending or publish
     $this->rule_processing_msgs = array();
     
     $this->rule_execution_type;
          
     $this->rule_type_framework_key;   //reference to $vtprd_rule_type_[x]_framework, guides editing and logic
     $this->rule_type_name;
     
     //*************************************
        //    PRICING DEAL TABLE INFO
        //       as the "html" and data layout arrays are held in a framework, the naming convention of 'selected' is not used here 
     //*************************************
     $this->rule_deal_info = array();         //repeatable array for future table processing

 //    $this->discount_rule_max_amt_type = 'all';   
 //   $this->discount_rule_max_amt_count;
     $this->discount_rule_max_amt_msg;
//     $this->discount_lifetime_max_amt = 0;
     $this->discount_product_short_msg;
     $this->discount_product_full_msg;  
  
      //Cumulative Pricing Switches

     $this->cumulativeRulePricing = 'no';
     $this->cumulativeSalePricing = 'no';
     $this->cumulativeCouponPricing = 'no';     
     $this->ruleApplicationPriority_num = 10;
     $this->discount_applies_within_pool; 
     //*****************
     //  inPop
    //*****************

      $this->inPop; // cart or single or groups
            
      $this->inPop_varProdID;
      $this->inPop_varProdID_name;
      $this->var_in_checked; 
      
      $this->inPop_varProdID_parentLit;                  
     
      $this->inPop_singleProdID;
      $this->inPop_singleProdID_name;
          
      $this->prodcat_in_checked;

      $this->rulecat_in_checked;
       
     $this->role_in_checked;
     $this->role_and_or_in; //and/or
     $this->specChoice_in; // all or each or any 
     $this->anyChoiceIn_max;
     $this->amtSelectedIn; //quantity or currency 
     $this->inPop_threshHold_amt;
          
     //END inPop     
          
    
     //*****************
     //  actionPop
    //*****************
      $this->actionPop_same_as_inPop; 
      $this->actionPop; // cart or single or groups
            
      $this->actionPop_varProdID;
      $this->actionPop_varProdID_name;
      $this->var_out_checked;
      $this->var_out_product_variations_parameter; //set to null by default
    
      $this->actionPop_singleProdID ;
      $this->actionPop_singleProdID_name;
          
      $this->prodcat_out_checked;
      $this->rulecat_out_checked;       
      $this->role_out_checked;
      $this->role_and_or_out; //and/or
      
     $this->specChoice_out; // all or each or any 
     
     $this->anyChoiceOut_max;

     $this->amtSelectedOut; //quantity or currency 
     $this->actionPop_threshHold_amt;
     $this->end_of_actionPop_reached;
          
     //******************************************
     //END actionPop 
     //******************************************

    
    //FUTURE
     $this->periodicityApplicable;                 // no / pattern / singleDates / dateRange 
     //       date processing types
     $this->periodicByPattern;                     //  periodic dropdown options: daily/weekly/monthly/annual / every mon/tues/weds etc
     $this->periodicByPattern_begins_on;
    
     $this->periodicBySingleDates = array( 
       //  'actionDate'    => ''
     );               
     $this->periodicByDateRange = array( 
     /*
        array (
          'rangeBeginDate'    => '',
          'rangeEndDate'    => '',
        )     
     */    
     );
 
       
      
          
     $this->rule_error_message = array();
     $this->rule_error_red_fields = array();
     $this->rule_error_box_fields = array();
     $this->advertising_msg_badge_sw;  //v1.0.9.0         
     /* ************************************************* */
     /* Rule Processing at Purchase
     *  data is loaded here only at purchase processing time
     *    category info covers both product cats and rule cats
     */
     /* ************************************************* */
     $this->inPop_found_list = array(
        /* **The following array structure is created on-the-fly during the apply process**
        array(
          'prod_id'    => '',
          'prod_name'    => '',
          'prod_qty'  => '',
          'prod_unit_price'  => '',
          'prod_db_unit_price'  => '',
          'prod_total_price'  => '',
          'prod_cat_list' => array(),
          'rule_cat_list' => array(),
          'prod_id_cart_occurrence' => '', //used to mark product in cart if failed a rule
          'count_satisfied' => '', // "Buy 2 of x..." or "Buy a minimum of $x" test
          'prod_requires_action'  => '' //rule may require cart action, but some of the pop may not.... 
        )
        */
      ); 
       $this->actionPop_found_list = array(
        /* **The following array structure is created on-the-fly during the apply process**
        array(
          'prod_id'    => '',
          'prod_name'    => '',
          'prod_qty'  => '',
          'prod_unit_price'  => '',
          'prod_db_unit_price'  => '',
          'prod_total_price'  => '',
          'prod_yousave_amt'    => '' ,   //*****
          'prod_yousave_pct'    => '' ,   //*****
          'prod_cat_list' => array(),
          'rule_cat_list' => array(),
          'prod_id_cart_occurrence' => '', //used to mark product in cart if failed a rule
          'count_satisfied' => '', // "2nd one free..." or "Buy a minimum of $x" test
          'prod_requires_action'  => '' //rule may require cart action, but some of the pop may not.... 
        )
        */
      );

      $this->inPop_free_product_group_count = 0;
      $this->inPop_prodIds_array = array();

      $this->inPop_exploded_found_list = array();     
      $this->inPop_exploded_group_begin;
      $this->inPop_exploded_group_end;
      $this->inPop_exploded_group_occurrence = 0;
      $this->actionPop_exploded_found_list = array();
      $this->actionPop_exploded_group_begin;
      $this->actionPop_exploded_group_end;      
      $this->actionPop_exploded_group_occurrence = 0;
      
      $this->inPop_qty_total = 0.00;
      $this->inPop_total_price = 0.00;
      $this->inPop_running_qty_total = 0.00;
      $this->inPop_running_nth_qty_total = 0.00;
      $this->inPop_running_total_price = 0.00;
      $this->actionPop_qty_total = 0.00;
      $this->actionPop_total_price = 0.00;
      $this->actionPop_running_qty_total = 0.00;
      $this->actionPop_running_nth_qty_total = 0.00;
      $this->actionPop_running_total_price = 0.00;
      $this->actionPop_rule_yousave_amt = 0.00;
      $this->actionPop_rule_yousave_qty = 0.00;
      $this->actionPop_rule_yousave_pct = 0;
      $this->rule_requires_cart_action;
      $this->errProds_qty = 0.00 ;
      $this->discount_total_qty_for_rule = 0;
      $this->discount_total_amt_for_rule = 0.00 ;
      $this->discount_total_unit_price_for_rule = 0.00 ;
      $this->discount_total_pct_for_rule = 0;
      $this->errProds_ids = array() ;
      $this->errProds_names = array() ;
      $this->errProds_cat_names = array() ;
      $this->rule_processing_trail = array();
      $this->free_product_array = array(); //products with free discount for this rule
      $this->purch_hist_rule_row_id = 0;   
      $this->purch_hist_rule_row_qty_total_orig = 0;
      $this->purch_hist_rule_row_qty_total_plus_discounts = 0;  
      $this->purch_hist_rule_row_price_total_orig = 0;
      $this->purch_hist_rule_row_price_total_plus_discounts = 0;
      $this->purch_hist_rule_percent_total = 0; 
      $this->auto_add_inserted_array = array();
      $this->coupons_amount_without_rule_discounts = 0;  //TOTAL $$ value of USER-ENTERED coupons (only the 0 iteration is used) 
      $this->auto_add_count_for_rule_repeat = 0;  //v1.1.0.6  ==> cleared at begine of each rule repeat
      $this->auto_add_count_for_rule = 0;   //v1.1.0.6 
      
      $this->only_for_this_coupon_name = '';    //v1.1.0.8  only apply if matching coupon PRESENTED at checkout     
  } //end function 
    
} //end class    
