<?php


class VTPRD_Cart {	
    public $cart_items;
    public $cart_item;
 
    public $cart_original_total_amt;
    public $yousave_cart_total_amt;
    public $yousave_cart_total_amt_excl_tax;  //v1.0.7.4
    public $yousave_cart_total_amt_incl_tax;  //v1.0.7.4
    public $yousave_cart_total_qty;
    public $yousave_cart_total_pct;
    public $cart_discount_subtotal;
    public $error_messages; 
    public $error_messages_processed;  
    
    public $current_processing_request_type; //at catalog display time 'display'/ cart processing 'cart'
        
    public $at_least_one_rule_actionPop_product_found;
    
    public $cart_level_status;
    //flag to prevent multiple processing iterations
    public $cart_level_auditTrail_msg; 
    //used in function vtprd_post_purchase_save_info, controls 2nd-nth iteration
    public $post_purchase_processing_already_done;

    public $lifetime_limit_applies_to_cart;   
    //address info for lifetime max purchase      
    public $purchaser_ip_address;
    public $purchaser_email;
    public $billto_name;
    public $billto_address;
    public $billto_city;
    public $billto_state;
    public $billto_postcode;
    public $billto_country;
    public $shipto_name;
    public $shipto_address;
    public $shipto_city;
    public $shipto_state;
    public $shipto_postcode;
    public $shipto_country; 
    public $purchaser_table_id;
    //used to verify that a purchase is in progress, and not a re-send of an email out of wp-admin
    public $wpsc_purchase_in_progress;
  //  public $wpsc_orig_coupons_amount;
    public $cumulativeCouponPricing_maybe_rollout_needed; //in support of auto add free products timing - 'NO' now needs a rollout...
    public $customer_is_tax_exempt;        //v1.0.7.9
    public $cart_contents_count;        //v1.0.9.3
    public $cart_contents_orig_subtotal;        //v1.0.9.3
    public $cart_has_zero_price_auto_add_free_item = ''; //v1.1.0.6               
 //   CART-LEVEL UNITS AND AMOUNTS AS RELATES TO RULES ITERATIONS, INPUT AND OUTPUT..  mAYBE PUT THE NEW CART-ITEM STUFF HERE....
    
    
	public function __construct(){
    $this->cart_items = array();
    $this->cart_item; 
    $this->cart_original_total_amt = 0;
    $this->yousave_cart_total_amt = 0;
    $this->yousave_cart_total_amt_excl_tax = 0;  //v1.0.7.4
    $this->yousave_cart_total_amt_incl_tax = 0;  //v1.0.7.4
    $this->yousave_cart_total_qty = 0;
    $this->yousave_cart_total_pct = 0;
    $this->cart_discount_subtotal = 0;        
    $this->error_messages  = array(
       /* **The following array structure is created on-the-fly during the apply process**
        array(
          'msg_from_this_rule_id'    => '',
          'msg_from_this_rule_occurrence' => '',
          'msg_text'  => ''  
        )
        */
    ); 
    $this->error_messages_processed;
    $this->customer_is_tax_exempt = false;        //v1.0.7.9 
    $this->cart_contents_count = 0;        //v1.0.9.3
    $this->cart_contents_orig_subtotal = '';        //v1.0.9.3   
    $this->cart_has_zero_price_auto_add_free_item = ''; //v1.1.0.6                    
  }
  

} //end class

class VTPRD_Cart_Item {

    public $product_id; 
    public $variation_id; //woo and jigo only 
    public $variation_array; //woo and jigo only 
    public $product_variation_key; //woo, combo of other fields... //v1.0.8.6
    public $product_name;
    public $parent_product_name;  //woo and jigo only 
    public $quantity;
    public $unit_price;
    public $total_price;
    public $db_unit_price;
    public $db_unit_price_list;
    public $db_unit_price_special;
    public $product_is_on_special; 
    
    //Running totals and processing switches
    public $original_quantity;
    public $running_quantity;
    public $running_nth_quantity;
    public $running_total_price;
    public $running_nth_total_price;
    public $running_index_begin;
    public $buy_amt_process_status;        


    public $ignore_all_rules; //at product level, set to no rules apply to product
    public $apply_only_rule_id; //at product level, set rule_id of only rule to apply to this product   => PRODUCT MUST BE IN inPop
    
    //logic generated
    public $prod_cat_list;
    public $rule_cat_list;
    public $prod_rule_include_only_list;  //from product screen
    public $prod_rule_exclusion_list;  //from product screen
    public $rule_applied_tracking; 
       
    //used during rule process logic
    public $rules_changed_product_price_count; 
    public $cartAuditTrail;
        //see wp-e-commerce/wpsc-includes/product-template.php  function wpsc_the_product_price_display
    public $yousave_by_rule_info;     
    
       //special variations processing for later AJAX variations pricing
    public $this_is_a_parent_product_with_variations;
    public $pricing_by_rule_array;
    
    public $discount_price;
    public $yousave_total_amt;
    public $yousave_total_pct;
    public $yousave_total_qty;
    public $yousave_total_unit_price;
    public $save_orig_unit_price;  //v1.0.7.4
    public $product_is_taxable;  //v1.0.7.4
    
    public $product_discount_price_html_woo;
    public $product_in_rule_allowing_display;
    public $product_auto_insert_state;    //used only during auto insert processing ...
    public $variation_name_html;    //v1.0.7.9  pre-load variation name... 
    public $lifetime_line_subtotal;     //v1.0.8.0  for lifetime processing only...      
    public $product_already_in_an_all_rule;     //v1.0.8.4  for cumulativeRulePricing processing only...                            
    
        
    //v1.0.9.0 begin                 
    public $product_list_price_html_woo;                  //unit price list
    public $product_discount_price_woo;           //unit price with Catalog discount applied
    public $product_discount_price_incl_tax_woo;          
    public $product_discount_price_excl_tax_woo;            
    public $product_discount_price_incl_tax_html_woo;            
    public $product_discount_price_excl_tax_html_woo;                       
    public $product_discount_price_suffix_html_woo;
    
    public $product_inline_discount_price_woo;           //unit price with Catalog + Cart Discount applied, spread across the Unit Price rather than in Coupon     
    public $product_inline_discount_price_incl_tax_woo;          
    public $product_inline_discount_price_excl_tax_woo;            
    public $product_inline_discount_price_incl_tax_html_woo;            
    public $product_inline_discount_price_excl_tax_html_woo;                       
    public $product_inline_discount_price_suffix_html_woo;    
    //v1.0.9.0 end 
    //v1.0.9.3 begin 
    public $product_catalog_price_displayed; 
    public $product_catalog_price_displayed_incl_tax_woo; 
    public $product_catalog_price_displayed_excl_tax_woo; 
    public $product_catalog_yousave_total_amt_incl_tax_woo; 
    public $product_catalog_yousave_total_amt_excl_tax_woo;       
    public $computation_summary; //text summary of unitPrice computation 
    //v1.0.9.3 end                        
    public $zero_price_auto_add_free_item; //v1.1.0.6
    
	public function __construct(){
    $this->product_id;
    $this->variation_id;
    $this->variation_array = array(); 
      /*
         Array
            (
                [pa_colors2] => purple,
                [pa_size2] => lg
            )
     
      */ 
    $this->product_variation_key = array(); //woo, combo of other fields... //v1.0.8.6   
      /*
         Array
            (
               [product_id]    => $cart_item['product_id'], 
               [variation_id]  => $cart_item['variation_id'],
               [variation]     => $cart_item['variation']
            )
     
      */      
    $this->product_name;
    $this->parent_product_name;
    $this->quantity = 0.00;
    $this->unit_price = 0.00;
    $this->total_price = 0.00;
    $this->db_unit_price = 0.00;
    $this->db_unit_price_list = 0.00;
    $this->db_unit_price_special = 0.00;
    $this->product_is_on_special; 
    
    //Running totals and processing switches
    $this->original_quantity = 0.00;
    $this->running_quantity = 0.00;
    $this->running_nth_quantity = 0.00;
    $this->running_total_price = 0.00;
    $this->running_nth_total_price = 0.00;
    
    $this->product_in_rule_allowing_display = 'no'; //used during pricing/shortcode calls, initialized to 'no'
    
    //pricing deal switching fields from the product custom fields
    $this->ignore_all_rules = 'no';  //in a metabox on product screen, set to no rules apply to product
    $this->apply_only_rule_id;  //in a metabox on product screen, set rule_id of only rule to apply to this product   => PRODUCT MUST BE IN inPop
    
    $this->prod_cat_list = array();
    $this->rule_cat_list = array();
    $this->prod_rule_include_only_list = array();  
    $this->prod_rule_exclusion_list = array();    
    
    $this->rule_applied_tracking;
       /* **The following array structure is created on-the-fly during the apply process**
        array(
           'rule_id_applied'    => '' ,
           'rule_id_applied'    => '' ,
        )
       */ 
    $this->rules_changed_product_price_count = 0;  //tracks processing, used for sale price interaction
    
    //set up cart audit trail info, keyed to rule prod_id
    $this->cartAuditTrail = array(
        /* **The following array structure is created on-the-fly during the apply process**
        // in $vtprd_rule_test_info_framework *************
        array(
          'ruleset_occurrence',         => $i, 
          'inPop_participation_msgs'    => array (),
          'product_in_inPop'            => '' , 
          'actionPop_participation_msgs'  => array (),
          'product_in_actionPop'        => '' ,
          'discount_msgs'  => array (),
          
          'inPop_prod_cat_found'        => '' ,   
          'inPop_rule_cat_found'        => '' ,
          'inPop_and_required'          => '' ,
          'userRole'            				=> '' ,  
          'inPop_role_found'            => '' ,  
          'inPop_single_found'          => '' , 
          'inPop_variation_found'       => '' ,
          'product_in_inPop'            => '' ,  
          
          
 
          'actionPop_prod_cat_found'    => '' ,  
          'actionPop_rule_cat_found'    => '' ,
          'actionPop_and_required'      => '' ,  
          'actionPop_role_found'        => '' , 
          'actionPop_single_found'      => '' ,  
          'actionPop_variation_found'   => '' ,
          'product_in_actionPop'        => '' ,
                      
          'rule_priority'      => ''    // y/n
          'discount_info'    => array(
                  'inPop'         => '' ,  // cart/single etc

                )                
        )
        */    
     );
    
    //only store data here when discount applied    keyed to rule prod_id
    $this->yousave_by_rule_info = array(  
       /* **The following array structure is created on-the-fly during the apply process**
        array(
           'ruleset_occurrence'    => $i, 
           'discount_amt_type'   => '',
           'discount_amt_count'   => 0,
           'discount_for_the_price_of_count'  => '', 
           'discount_applies_to_qty'  => 1,         
           'yousave_amt'       => $curr_prod_array['prod_discount_amt'] ,
           'yousave_pct'       => $yousave_pct ,
           'rule_max_amt_msg'  => $max_msg,
           'rule_execution_type' =>  $vtprd_rules_set[$i]->rule_execution_type, //used in email msg production!            
           'rule_short_msg'    => $vtprd_rules_set[$i]->discount_product_short_msg,
           'rule_full_msg'     => $vtprd_rules_set[$i]->discount_product_full_msg
           //used at cart discount display time => if coupon used, does this discount apply?
           //  ---> pick this up directly from the ruleset occurrence at application time
           //'cumulativeCouponPricingAllowed' => $vtprd_rules_set[$i]->cumulativeCouponPricingAllowed  
        )
       */
     );
      
         //******************
        //Display Rule variation stuff, at Display time, used to compute AJAX price changes on variations
        //  MUST be filled for ALL PRODUCTS, as we don't know if if a product
        //******************
    //for later ajaxVariations pricing  
    $this->this_is_a_parent_product_with_variations = ' '; //yes/no   => ******triggers a check against inpop_varprodid, NOT against product...*********                                       
    $this->pricing_by_rule_array = array(
        /*
          'pricing_rule_id' => '', 
          'pricing_rule_applies_to_variations_array' => '', //' ' or var list array
          'pricing_rule_percent_discount' => '',
          'pricing_rule_currency_discount' => ''
        */
     ); 
                        
    $this->discount_price = '';
    $this->yousave_total_amt = 0.00;
    $this->yousave_total_pct = 0;
    $this->yousave_total_qty = 0;
    $this->yousave_total_unit_price = 0;
    $this->save_orig_unit_price = 0;     //v1.0.7.4
    $this->product_is_taxable = '';    //v1.0.7.4
    $this->variation_name_html = '';    //v1.0.7.9
    $this->lifetime_line_subtotal = 0;     //v1.0.8.0  for lifetime processing only... 
    $this->product_already_in_an_all_rule = '';     //v1.0.8.4  for cumulativeRulePricing processing only...  
    
    
    //v1.0.9.0 begin
    $this->product_list_price_html_woo = '';
    $this->product_discount_price_woo = 0;             
    $this->product_discount_price_html_woo = '';          
    $this->product_discount_price_incl_tax_woo = '';          
    $this->product_discount_price_excl_tax_woo = '';            
    $this->product_discount_price_incl_tax_html_woo = '';            
    $this->product_discount_price_excl_tax_html_woo = '';                       
    $this->product_discount_price_suffix_html_woo = ''; 
    
    $this->product_inline_discount_price_woo = 0;       
    $this->product_inline_discount_price_incl_tax_woo = '';          
    $this->product_inline_discount_price_excl_tax_woo = '';            
    $this->product_inline_discount_price_incl_tax_html_woo = '';            
    $this->product_inline_discount_price_excl_tax_html_woo = '';                       
    $this->product_inline_discount_price_suffix_html_woo = '';    
    //v1.0.9.0 end 
    //v1.0.9.3 begin
    $this->product_catalog_price_displayed = 0;  
    $this->product_catalog_price_displayed_incl_tax_woo; 
    $this->product_catalog_price_displayed_excl_tax_woo; 
    $this->product_catalog_yousave_total_amt_incl_tax_woo; 
    $this->product_catalog_yousave_total_amt_excl_tax_woo;       
    $this->computation_summary = '';  
    //v1.0.9.3 end 
    $this->zero_price_auto_add_free_item = '';  //v1.1.0.6            
	}

} //end class


class VTPRD_Cart_Functions{
	
	public function __construct(){
		
	}


    public function vtprd_destroy_cart() { 
        global $vtprd_cart;
        unset($vtprd_cart);
    }
    
    /*
     Template Function
     In your theme, execute the function
     where you want the amount to show
    */
    public function vtprd_cart_oldprice() { 
        global $vtprd_cart;
        echo '$vtprd_cart->$cart_oldprice';
    }

    /*
     Template Function
     In your theme, execute the function
     where you want the amount to show
    */    
    public function vtprd_cart_yousave() { 
        global $vtprd_cart;
        echo '$vtprd_cart->$cart_yousave';
    }
    
    /*
     Template Function
     In your theme, execute the function
     where you want the amount to show
    */
    public function vtprd_cart_unit_oldprice($product_id) { 
        global $vtprd_cart;
        foreach($vtprd_cart->vtprd_cart_items as $key => $vtprd_cart_item) {
           if ($vtprd_cart_item->product_id == $product_id) {
              echo $vtprd_cart->cart_unit_oldprice;
              break;
           }
        }
    }
    
    /*
     Template Function
     In your theme, execute the function
     where you want the amount to show
    */    
    public function vtprd_cart_total_oldprice($product_id) { 
        global $vtprd_cart;
        foreach($vtprd_cart->vtprd_cart_items as $key => $vtprd_cart_item) {
           if ($vtprd_cart_item->product_id == $product_id) {
              echo $vtprd_cart_item->cart_total_oldprice;
              break;
           }
        }
    }
    
    /*
     Template Function
     In your theme, execute the function
     where you want the amount to show
    */    
    public function vtprd_cart_total_yousave($product_id) { 
        global $vtprd_cart;
        foreach($vtprd_cart->vtprd_cart_items as $key => $vtprd_cart_item) {
           if ($vtprd_cart_item->product_id == $product_id) {
              echo $vtprd_cart->cart_total_yousave;
              break;
           }
        }
    }    

} //end class
$vtprd_cart_functions = new VTPRD_Cart_Functions;

