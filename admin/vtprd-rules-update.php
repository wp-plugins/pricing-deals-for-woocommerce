<?php
   
class VTPRD_Rule_update {
	

	public function __construct(){  
    
    $this->vtprd_edit_rule();
     
    //apply rule scehduling
    $this->vtprd_validate_rule_scheduling();
     
    //clear out irrelevant/conflicting data (if no errors)
    $this->vtprd_maybe_clear_extraneous_data();
       
    //translate rule into text...
    $this->vtprd_build_ruleInWords();
/*
    global $vtprd_rule;
      echo '$vtprd_rule <pre>'.print_r($vtprd_rule, true).'</pre>' ;  
 wp_die( __('<strong>Looks like you\'re running an older version of WordPress, you need to be running at least WordPress 3.3 to use the Varktech Minimum Purchase plugin.</strong>', 'vtmin'), __('VT Minimum Purchase not compatible - WP', 'vtmin'), array('back_link' => true));
 */     
    //update rule...
    $this->vtprd_update_rules_info();
      
  }
  
  /**************************************************************************************** 
  ERROR MESSAGES SHOULD GO ABOVE THE FIELDS IN ERROR, WHERE POSSIBLE, WITH A GENERAL ERROR MSG AT TOP.
  ****************************************************************************************/ 
            
  public  function vtprd_edit_rule() {
      global $post, $wpdb, $vtprd_rule, $vtprd_info, $vtprd_rules_set, $vtprd_rule_template_framework, $vtprd_deal_edits_framework, $vtprd_deal_structure_framework; 
                                                                                                                                                         
      $vtprd_rule_new = new VTPRD_Rule();   //  always  start with fresh copy
      $selected = 's';

      $vtprd_rule = $vtprd_rule_new;  //otherwise vtprd_rule is not addressable!
      
      // NOT NEEDED now that the edits are going through successfully
      //for new rule, put in 1st iteration of deal info
      //$vtprd_rule->rule_deal_info[] = $vtprd_deal_structure_framework;   mwnt
       
     //*****************************************
     //  FILL / upd VTPRD_RULE...
     //*****************************************
     //   Candidate Population
     
     $vtprd_rule->post_id = $post->ID;

     if ( ($_REQUEST['post_title'] > ' ' ) ) {
       //do nothing
     } else {     
       $vtprd_rule->rule_error_message[] = array( 
              'insert_error_before_selector' => '#vtprd-deal-selection',
              'error_msg'  => __('The Rule needs to have a Title, but Title is empty.', 'vtprd')  );   
     }

/*

//specialty edits list:

**FOR THE PRICE OF**
=>for the price of within the group:
buy condition must be an amt
buy amt count must be > 1
buy amt must be = to discount amount count

action group condition must be 'applies to entire'
action group must be same as buy pool group only
discount applies to must be = 'all'

=> for the price of next
buy condition can be anything
action amt condition must be an amt
action amt count must be > 1
action amt must be = to discount amount count 

**CHEAPEST/MOST EXPENSIVE**
*=> in buy group
buy condition must be an amt
buy amt count must be > 1

*=> in action group
buy condition can be anything
action amt condition can be an amt or $$

*/
      //Upper Selects

      $vtprd_rule->cart_or_catalog_select   = $_REQUEST['cart-or-catalog-select'];  
      $vtprd_rule->pricing_type_select      = $_REQUEST['pricing-type-select'];  
      $vtprd_rule->minimum_purchase_select  = $_REQUEST['minimum-purchase-select'];  
      $vtprd_rule->buy_group_filter_select  = $_REQUEST['buy-group-filter-select'];  
      $vtprd_rule->get_group_filter_select  = $_REQUEST['get-group-filter-select'];  
      $vtprd_rule->rule_on_off_sw_select    = $_REQUEST['rule-on-off-sw-select'];
      $vtprd_rule->rule_type_select         = $_REQUEST['rule-type-select'];
      $vtprd_rule->wizard_on_off_sw_select  = $_REQUEST['wizard-on-off-sw-select']; 
        
      $upperSelectsDoneSw                   = $_REQUEST['upperSelectsDoneSw']; 
      
      if ($upperSelectsDoneSw != 'yes') {       
          $vtprd_rule->rule_error_message[] = array( 
                'insert_error_before_selector' => '.top-box',  
                'error_msg'  => __('Upper Level Filter choices not yet completed', 'vtprd') );            
      } 
      
      
      //#RULEtEMPLATE IS NOW A HIDDEN FIELD which carries the rule template SET WITHIN THE JS
      //   in response to the inital dropdowns being selected. 
     $vtprd_rule->rule_template = $_REQUEST['rule_template_framework']; 
     if ($vtprd_rule->rule_template == '0') { 
          $vtprd_rule->rule_error_message[] = array( 
                'insert_error_before_selector' => '.template-area',  
                'error_msg'  => __('Pricing Deal Template choice is required.', 'vtprd') );
          $vtprd_rule->rule_error_red_fields[] = '#deal-type-title' ; 
          $this->vtprd_dump_deal_lines_to_rule();
          $this->vtprd_update_rules_info();              
          return; //fatal exit....           
      } else {    
        for($i=0; $i < sizeof($vtprd_rule_template_framework['option']); $i++) {
          //get template title to make that name available on the Rule
          if ( $vtprd_rule_template_framework['option'][$i]['value'] == $vtprd_rule->rule_template )  {
            $vtprd_rule->rule_template_name = $vtprd_rule_template_framework['option'][$i]['title'];
            $i = sizeof($vtprd_rule_template_framework['option']);
          } 
        }
      }

     //DISCOUNT TEMPLATE
     $display_or_cart = substr($vtprd_rule->rule_template ,0 , 1);
     if ($display_or_cart == 'D') {
       $vtprd_rule->rule_execution_type = 'display';
     } else {
       $vtprd_rule->rule_execution_type = 'cart';
     }

     //using the selected Template, build the $vtprd_deal_edits_framework, used for all DEAL edits following
     $this->vtprd_build_deal_edits_framework();
  
     //********************************************************************************
     //EDIT DEAL LINES
     //***LOOP*** through all of the deal line iterations, edit lines 
     //********************************************************************************        
     $deal_iterations_done = 'no'; //initialize variable
     $active_line_count = 0; //initialize variable
     $active_field_count = 0;     

     for($k=0; $deal_iterations_done == 'no'; $k++) {      
       if ( (isset( $_REQUEST['buy_repeat_condition_' . $k] )) && (!empty( $_REQUEST['buy_repeat_condition_' . $k] )) ) {    //is a deal line there? always 1 at least...
         foreach( $vtprd_deal_structure_framework as $key => $value ) {   //spin through all of the screen fields=>  $key = field name, so has multiple uses...  
            //load up the deal structure with incoming fields
            $vtprd_deal_structure_framework[$key] = $_REQUEST[$key . '_' .$k];
         }   
          
            //Edit deal line
         $this->vtprd_edit_deal_info_line($active_field_count, $active_line_count, $k);
            //add deal line to rule
         $vtprd_rule->rule_deal_info[] = $vtprd_deal_structure_framework;   //add each line to rule, regardless if empty              
       } else {     
         $deal_iterations_done = 'yes';
       }
     }

    //if max_amt_type is active, may have a max_amt_msg
    $vtprd_rule->discount_rule_max_amt_msg = $_REQUEST['discount_rule_max_amt_msg'];
  
    //EDITED * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * +
    // + ADDED => pro-only option chosen                                                                             
    if ($vtprd_rule->rule_deal_info[0]['discount_rule_max_amt_type'] != 'none') { 
         $vtprd_rule->rule_error_message[] = array(                                                                              
                'insert_error_before_selector' => '.top-box',  
                'error_msg'  => __('The "Maximum Rule Discount for the Cart" option chosen is only available in the Pro Version. ', 'vtprd') .'<em><strong>'. __(' * Option restored to default value, * Please Update to Confirm!', 'vtprd') .'</strong></em>');
          $vtprd_rule->rule_error_red_fields[] = '#discount_rule_max_amt_type_label_0' ; 
          $vtprd_rule->rule_deal_info[0]['discount_rule_max_amt_type'] = 'none'; //overwrite ERROR choice with DEFAULT  
    } 
    //EDITED end   * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * +
    
    //if max_lifetime_amt_type is active, may have a max_amt_msg
    $vtprd_rule->discount_lifetime_max_amt_msg = $_REQUEST['discount_lifetime_max_amt_msg'];

    //if max_cum_amt_type is active, may have a max_amt_msg
    $vtprd_rule->discount_rule_cum_max_amt_msg = $_REQUEST['discount_rule_cum_max_amt_msg'];
    
               
    $vtprd_rule->discount_product_short_msg = $_REQUEST['discount_product_short_msg'];
    if ( ($vtprd_rule->discount_product_short_msg <= ' ') || 
         ($vtprd_rule->discount_product_short_msg == $_REQUEST['shortMsg']) ) {
        $vtprd_rule->rule_error_message[] = array( 
              'insert_error_before_selector' => '#messages-box',            
              'error_msg'  => __('Checkout Short Message is required.', 'vtprd') );
        $vtprd_rule->rule_error_red_fields[] = '#discount_product_short_msg_label' ;
        $vtprd_rule->rule_error_box_fields[] = '#discount_product_short_msg';       
    }    

    $vtprd_rule->discount_product_full_msg = $_REQUEST['discount_product_full_msg']; 
    //if default msg, get rid of it!!!!!!!!!!!!!!
    if ( $vtprd_rule->discount_product_full_msg == $vtprd_info['default_full_msg'] ) {
       $vtprd_rule->discount_product_full_msg == ' ';
    }          
    /* full msg now OPTIONAL
    if ( ($vtprd_rule->discount_product_full_msg <= ' ') || 
         ($vtprd_rule->discount_product_full_msg == $_REQUEST['fullMsg'] )){
        $vtprd_rule->rule_error_message[] = array( 
              'insert_error_before_selector' => '#messages-box',  
              'error_msg'  => __('Theme Full Message is required.', 'vtprd') );
        $vtprd_rule->rule_error_red_fields[] = '#discount_product_full_msg_label' ;       
    }    
*/
              
    $vtprd_rule->cumulativeRulePricing = $_REQUEST['cumulativeRulePricing']; 
    if ($vtprd_rule->cumulativeRulePricing == 'yes') {
       if ($vtprd_rule->cumulativeRulePricingAllowed == 'yes') {
         $vtprd_rule->ruleApplicationPriority_num = $_REQUEST['ruleApplicationPriority_num'];
         $vtprd_rule->ruleApplicationPriority_num = preg_replace('/[^0-9.]+/', '', $vtprd_rule->ruleApplicationPriority_num); //remove leading/trailing spaces, percent sign, dollar sign
         if ( is_numeric($vtprd_rule->ruleApplicationPriority_num) === false ) { 
            $vtprd_rule->ruleApplicationPriority_num = '10'; //init variable 
            /*
            $vtprd_rule->rule_error_message[] = array( 
                  'insert_error_before_selector' => '#cumulativePricing_box',  
                  'error_msg'  => __('"Apply this Rule Discount in Addition to Other Rule Discounts" = Yes.  Rule Priority Sort Number is required, and must be numeric. "10" inserted if blank.', 'vtprd') );
            $vtprd_rule->rule_error_red_fields[] = '#ruleApplicationPriority_num_label' ;        
            */
         }
       } else {
            $vtprd_rule->rule_error_message[] = array( 
                  'insert_error_before_selector' => '#cumulativePricing_box',  
                  'error_msg'  => __('With this Rule Template chosen, "Apply this Rule Discount in Addition to Other Rule Discounts" must = "No".', 'vtprd') );
            $vtprd_rule->rule_error_red_fields[] = '#ruleApplicationPriority_num_label' ;
            $vtprd_rule->rule_error_box_fields[] = '#ruleApplicationPriority_num'; 
            $vtprd_rule->ruleApplicationPriority_num = '10'; //init variable     
       }
    } else {
      $vtprd_rule->ruleApplicationPriority_num = '10'; //init variable  
    }
                 
    $vtprd_rule->cumulativeSalePricing   = $_REQUEST['cumulativeSalePricing'];
    if ( ($vtprd_rule->cumulativeSalePricing != 'no') && ($vtprd_rule->cumulativeSalePricingAllowed == 'no') ) {
      $vtprd_rule->rule_error_message[] = array( 
            'insert_error_before_selector' => '#cumulativePricing_box',  
            'error_msg'  => __('With this Rule Template chosen, "Rule Discount in addition to Product Sale Pricing" must = "Does not apply when Product Sale Priced".', 'vtprd') );
      $vtprd_rule->rule_error_red_fields[] = '#cumulativePricing_box';
      $vtprd_rule->rule_error_box_fields[] = '#cumulativePricing';  
    }
               
    $vtprd_rule->cumulativeCouponPricing = $_REQUEST['cumulativeCouponPricing'];            
    if ( ($vtprd_rule->cumulativeCouponPricing == 'yes') && ($vtprd_rule->cumulativeCouponPricingAllowed == 'no') ) {
      $vtprd_rule->rule_error_message[] = array( 
            'insert_error_before_selector' => '#cumulativePricing_box',  
            'error_msg'  => __('With this Rule Template chosen, " Apply Rule Discount in addition to Coupon Discount?" must = "No".', 'vtprd') );
      $vtprd_rule->rule_error_red_fields[] = '#cumulativePricing_box' ;
      $vtprd_rule->rule_error_box_fields[] = '#cumulativePricing'; 
    } 
 
 
         
     //inPop        
     $vtprd_rule->role_and_or_in = 'or'; //initialize so it's always there.  overwritten by logic as needed.
     $vtprd_rule->inPop = $_REQUEST['popChoiceIn'];
     switch( $vtprd_rule->inPop ) {
        case 'wholeStore':
          break;
        
        //EDITED * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * +
        default: // +ADDED => pro-only option chosen                                                                             
            $vtprd_rule->rule_error_message[] = array( 
                  'insert_error_before_selector' => '.top-box',  
                  'error_msg'  => __('The "Buy Group Selection" option chosen is only available in the Pro Version. ', 'vtprd') .'<em><strong>'. __(' * Option restored to default value, * Please Update to Confirm!', 'vtprd') .'</strong></em>');
            $vtprd_rule->rule_error_red_fields[] = '#buy_group_label' ; 
             $vtprd_rule->inPop = 'wholeStore'; //overwrite ERROR choice with DEFAULT 
             $vtprd_rule->buy_group_filter_select = 'wholeStore'; //overwrite ERROR choice with DEFAULT     
          break; 
        //Edited end  * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * +
        
      }

      //********************************************************************************************************************
      //The WPSC Realtime Catalog repricing action does not pass variation-level info, so these options are disallowed
      //********************************************************************************************************************
      if (($vtprd_rule->rule_execution_type == 'display') && 
          (VTPRD_PARENT_PLUGIN_NAME == 'WP E-Commerce') ) {
          
            if ($vtprd_rule->inPop == 'vargroup')  { 
               $vtprd_rule->rule_error_message[] = array( 
                              'insert_error_before_selector' => '#inPop-varProdID-cntl', 
                              'error_msg'  => __('"Buy" Group Selection - Single with Variations may not be chosen when "Apply Price Reduction to Products in the Catalog" Pricing Deal Type is chosen.', 'vtprd') );
               $vtprd_rule->rule_error_red_fields[] = '#inPopChoiceIn_label';
            }
    
            if ($vtprd_rule->cumulativeSalePricingAllowed == 'no' )  { 
               $vtprd_rule->rule_error_message[] = array( 
                              'insert_error_before_selector' => '#cumulativePricing_box',  
                              'error_msg'  => __('"Apply this Rule Discount in addition to Product Sale Pricing" must not be "No" when "Apply Price Reduction to Products in the Catalog" Pricing Deal Type is chosen.', 'vtprd') );
               $vtprd_rule->rule_error_red_fields[] = '#cumulativeSalePricing_label';
               $vtprd_rule->rule_error_box_fields[] = '#cumulativeSalePricing';
            }        
      }                                                                                     

      //********************************************************************************************************************  
      //  ruleApplicationPriority_num must ALWAYS be numeric, to allow for sorting
      //********************************************************************************************************************
 /*     $vtprd_rule->ruleApplicationPriority_num = preg_replace('/[^0-9.]+/', '', $vtprd_rule->ruleApplicationPriority_num); //remove leading/trailing spaces, percent sign, dollar sign
      if ( is_numeric($vtprd_rule->ruleApplicationPriority_num) === false ) { 
        $vtprd_rule->ruleApplicationPriority_num = '10';
      }  */
      /* not necessary any more!
      if ($vtprd_rule->rule_execution_type == 'display') {
        //display rules must ALWAYS sort first, so we reset it here
        $vtprd_rule->ruleApplicationPriority_num = '0';  
      }
      */      
     //actionPop        
     $vtprd_rule->role_and_or_out = 'or'; //initialize so it's always there.  overwritten by logic as needed.
     $vtprd_rule->actionPop = $_REQUEST['popChoiceOut'];
     switch( $vtprd_rule->actionPop ) {
        case 'sameAsInPop':
		    case 'wholeStore':
            //  $vtprd_rule->actionPop[0]['user_input'] = $selected;
            //  $this->vtprd_set_default_or_values_out();
          break;
        
        //EDITED * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * +
        default: //  +ADDED => pro-only option chosen                                                                             
            $vtprd_rule->rule_error_message[] = array( 
                  'insert_error_before_selector' => '.top-box',  
                  'error_msg'  => __('The "Get Group Selection" option chosen is only available in the Pro Version. ', 'vtprd') .'<em><strong>'. __(' * Option restored to default value, * Please Update to Confirm!', 'vtprd') .'</strong></em>');
            $vtprd_rule->rule_error_red_fields[] = '#action_group_label' ;
            $vtprd_rule->actionPop = 'sameAsInPop'; //overwrite ERROR choice with DEFAULT 
            $vtprd_rule->get_group_filter_select = 'sameAsInPop'; //overwrite ERROR choice with DEFAULT    
          break; 
        //EDIT end   * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * +
      
      }


      //********************************************************************************************************************
      //Specialty Complex edits... 
      //********************************************************************************************************************
                                                  
       //FOR THE PRICE OF requirements...
       if ($vtprd_rule->rule_deal_info[0]['discount_amt_type'] =='forThePriceOf_Units') {
          switch ($vtprd_rule->rule_template) {
           case 'C-forThePriceOf-inCart':  //buy-x-action-forThePriceOf-same-group-discount
                 if ($vtprd_rule->rule_deal_info[0]['buy_amt_type'] != 'quantity') {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#buy_amt_box_0',  
                          'error_msg'  => __('"Buy Unit Quantity" required for Discount Type "For the Price of (Units) Discount"', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#buy_amt_type_label_0';
                    $vtprd_rule->rule_error_red_fields[] = '#discount_amt_type_label_0';                
                 } 
                 elseif ( (is_numeric( $vtprd_rule->rule_deal_info[0]['buy_amt_count'])) && ($vtprd_rule->rule_deal_info[0]['buy_amt_count'] < '2' )) {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#buy_amt_box_0',  
                          'error_msg'  => __('"Buy Unit Quantity" must be > 1 for Discount Type "For the Price of (Units) Discount".', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#discount_amt_type_label_0';
                    $vtprd_rule->rule_error_box_fields[] = '#buy_amt_count_0';                    
                 }
                 elseif ( (is_numeric( $vtprd_rule->rule_deal_info[0]['buy_amt_count'])) && 
                          ($vtprd_rule->rule_deal_info[0]['buy_amt_count'] <= $vtprd_rule->rule_deal_info[0]['discount_amt_count'])  ) {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#buy_amt_box_0',  
                          'error_msg'  => __('"Buy Unit Quantity" must be greater than Discount Type "Discount For the Price of Units", when "For the Price of (Units) Discount" chosen.', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#discount_amt_count_literal_forThePriceOf_0';
                    $vtprd_rule->rule_error_box_fields[] = '#buy_amt_count_0';                    
                 }      
              break;
           case 'C-forThePriceOf-Next':  //buy-x-action-forThePriceOf-other-group-discount
                 if ($vtprd_rule->rule_deal_info[0]['action_amt_type'] != 'quantity') {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#action_amt_box_0',  
                          'error_msg'  => __('"Get Unit Quantity" required for Discount Type "For the Price of (Units) Discount"', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#discount_amt_type_label_0';
                    $vtprd_rule->rule_error_box_fields[] = '#action_amt_count_0';                
                 } 
                 elseif ( (is_numeric($vtprd_rule->rule_deal_info[0]['action_amt_count'])) && ($vtprd_rule->rule_deal_info[0]['action_amt_count'] < '2') ) {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#action_amt_box_0',  
                          'error_msg'  => __('"Get Unit Quantity" must be > 1 for Discount Type "For the Price of (Units) Discount".', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#discount_amt_type_label_0';
                    $vtprd_rule->rule_error_box_fields[] = '#action_amt_count_0';                    
                 }
                 elseif ( (is_numeric($vtprd_rule->rule_deal_info[0]['action_amt_count'])) &&
                        ($vtprd_rule->rule_deal_info[0]['action_amt_count'] <= $vtprd_rule->rule_deal_info[0]['discount_amt_count']) ) {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#action_amt_box_0',  
                          'error_msg'  => __('"Get Unit Quantity" must be greater than Discount Type "Discount For the Price of Units", when "For the Price of (Units) Discount" chosen.', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#discount_amt_count_literal_forThePriceOf_0';
                    $vtprd_rule->rule_error_box_fields[] = '#action_amt_count_0';                    
                 }     
              break;
           default:
                $vtprd_rule->rule_error_message[] = array( 
                      'insert_error_before_selector' => '#discount_amt_box_0',  
                      'error_msg'  => __('To use Discount Type "For the Price of (Units) Discount", choose a "For the Price Of" template type.', 'vtprd') );
                $vtprd_rule->rule_error_red_fields[] = '#discount_amt_type_label_0'; 
                $vtprd_rule->rule_error_red_fields[] = '#deal-type-title';                   
              break;
         } //end switch   
       } //end if forThePriceOf_Units

       if ($vtprd_rule->rule_deal_info[0]['discount_amt_type'] =='forThePriceOf_Currency') {
          switch ($vtprd_rule->rule_template) {
           case 'C-forThePriceOf-inCart':  //buy-x-action-forThePriceOf-same-group-discount
                 if ($vtprd_rule->rule_deal_info[0]['buy_amt_type'] != 'quantity') {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#buy_amt_box_0',  
                          'error_msg'  => __('"Buy Unit Quantity" required for Discount Type "For the Price of (Currency) Discount"', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#buy_amt_type_label_0';
                    $vtprd_rule->rule_error_red_fields[] = '#discount_amt_type_label_0';                
                 } 
                 elseif ($vtprd_rule->rule_deal_info[0]['buy_amt_count'] < '2' ) {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#buy_amt_box_0',  
                          'error_msg'  => __('"Buy Unit Quantity" must be > 1 for Discount Type "For the Price of (Currency) Discount".', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#discount_amt_type_label_0';
                    $vtprd_rule->rule_error_box_fields[] = '#buy_amt_count_0';                    
                 }     
              break;
           case 'C-forThePriceOf-Next':  //buy-x-action-forThePriceOf-other-group-discount
                 if ($vtprd_rule->rule_deal_info[0]['action_amt_type'] != 'quantity') {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#action_amt_box_0',  
                          'error_msg'  => __('"Get Unit Quantity" required for Discount Type "For the Price of (Currency) Discount"', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#action_amt_type_label_0';
                    $vtprd_rule->rule_error_red_fields[] = '#discount_amt_type_label_0';                
                 } 
                 elseif ($vtprd_rule->rule_deal_info[0]['action_amt_count'] < '2' ) {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#action_amt_box_0',  
                          'error_msg'  => __('"Get Unit Quantity" must be > 1 for Discount Type "For the Price of (Currency) Discount".', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#discount_amt_type_label_0';
                    $vtprd_rule->rule_error_box_fields[] = '#action_amt_count_0';                    
                 }     
              break;
           default:
                $vtprd_rule->rule_error_message[] = array( 
                      'insert_error_before_selector' => '#discount_amt_box_0',  
                      'error_msg'  => __('To use Discount Type "For the Price of (Currency) Discount", choose a "For the Price Of" template type.', 'vtprd') );
                $vtprd_rule->rule_error_red_fields[] = '#discount_amt_type_label_0'; 
                $vtprd_rule->rule_error_red_fields[] = '#deal-type-title';                   
              break;
         } //end switch   
       } //end if forThePriceOf_Currency
                                                    
       //DISCOUNT APPLIES TO requirements...
       if ( ($vtprd_rule->rule_deal_info[0]['discount_applies_to'] == 'cheapest') || 
            ($vtprd_rule->rule_deal_info[0]['discount_applies_to'] == 'most_expensive') ){
          switch ($vtprd_rule->rule_template) {
           case 'C-cheapest-inCart':  //buy-x-action-most-expensive-same-group-discount
                 if ( ($vtprd_rule->rule_deal_info[0]['buy_amt_type'] != 'quantity') && 
                      ($vtprd_rule->rule_deal_info[0]['buy_amt_type'] != 'currency') ) {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#buy_amt_box_0',  
                          'error_msg'  => __('Buy Amount type must be Quantity or Currency, when Discount "Applies To Cheapest/Most Expensive" chosen.', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#buy_amt_type_label_0';
                    $vtprd_rule->rule_error_red_fields[] = '#discount_applies_to_label_0';                 
                 }                     
                 elseif ( (is_numeric($vtprd_rule->rule_deal_info[0]['buy_amt_count'])) && ($vtprd_rule->rule_deal_info[0]['buy_amt_count'] < '2') ) {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#buy_amt_box_0',  
                          'error_msg'  => __('Buy Amount Count must be greater than 1, when Discount "Applies To Cheapest/Most Expensive" chosen.', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#buy_amt_type_label_0';
                    $vtprd_rule->rule_error_red_fields[] = '#discount_applies_to_label_0';                 
                 }                                                
              break;           
           case 'C-cheapest-Next':  //buy-x-action-most-expensive-other-group-discount
                 if ( ($vtprd_rule->rule_deal_info[0]['action_amt_type'] != 'quantity') && 
                      ($vtprd_rule->rule_deal_info[0]['action_amt_type'] != 'currency') ) {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#action_amt_box_0',  
                          'error_msg'  => __('Get Amount type must be Quantity or Currency, when Discount "Applies To Cheapest/Most Expensive" chosen.', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#action_amt_type_label_0';
                    $vtprd_rule->rule_error_red_fields[] = '#discount_applies_to_label_0';                 
                 }                     
                 elseif ( (is_numeric($vtprd_rule->rule_deal_info[0]['action_amt_count'])) && ($vtprd_rule->rule_deal_info[0]['action_amt_count'] < '2') ) {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#action_amt_box_0',  
                          'error_msg'  => __('Get Amount Count must be greater than 1, when Discount "Applies To Cheapest/Most Expensive" chosen.', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#action_amt_type_label_0';
                    $vtprd_rule->rule_error_red_fields[] = '#discount_applies_to_label_0';                 
                 }
              break;
           default:
                $vtprd_rule->rule_error_message[] = array( 
                      'insert_error_before_selector' => '#discount_applies_to_box_0',  
                      'error_msg'  => __('Please choose a "Cheapest/Most Expensive" template type, when Discount "Applies To Cheapest/Most Expensive" chosen.', 'vtprd') );
                $vtprd_rule->rule_error_red_fields[] = '#discount_applies_to_label_0';
                $vtprd_rule->rule_error_red_fields[] = '#deal-type-title';
              break;           
         } //end switch        
       } //end if discountAppliesTo


    
      //********************************************************************************************************************
      //The WPSC Realtime Catalog repricing action does not pass variation-level info, so these options are disallowed
      //********************************************************************************************************************
      if (($vtprd_rule->rule_execution_type == 'display') && 
          (VTPRD_PARENT_PLUGIN_NAME == 'WP E-Commerce') ) {
          
            if ($vtprd_rule->actionPop == 'vargroup')  { 
               $vtprd_rule->rule_error_message[] = array( 
                              'insert_error_before_selector' => '#actionPop-varProdID-cntl',  
                              'error_msg'  => __('"Action" Group Selection - Single with Variations may not be chosen when "Apply Price Reduction to Products in the Catalog" Pricing Deal Type is chosen, due to a WPEC limitation.', 'vtprd') );
               $vtprd_rule->rule_error_red_fields[] = '#actionPopChoiceOut_label';
            }
    
            if ($vtprd_rule->cumulativeSalePricingAllowed == 'no' )  { 
               $vtprd_rule->rule_error_message[] = array( 
                              'insert_error_before_selector' => '#cumulativeSalePricing_areaID',  
                              'error_msg'  => __('"Apply this Rule Discount in addition to Product Sale Pricing" must not be "No" when "Apply Price Reduction to Products in the Catalog" Pricing Deal Type is chosen, due to a WPEC limitation.', 'vtprd') );
               $vtprd_rule->rule_error_red_fields[] = '#cumulativeSalePricing_label';
            }        
      }                                                                                     

      //********************************************************************************************************************      
      //********************************************************************************************************************
       
      //EDITED BEGIN * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * +
        // +ADDED =>    BEGIN
        // pro-only option chosen                                                                             
      if ($vtprd_rule->rule_deal_info[0]['discount_lifetime_max_amt_type'] != 'none') { 
           $vtprd_rule->rule_error_message[] = array( 
                  'insert_error_before_selector' => '.top-box',  
                  'error_msg'  => __('The "Maximum Discounts per Customer (for Lifetime of the rule)" option chosen is only available in the Pro Version ', 'vtprd') .'<em><strong>'. __(' * Option restored to default value, * Please Update to Confirm!', 'vtprd') .'</strong></em>');
            $vtprd_rule->rule_error_red_fields[] = '#discount_lifetime_max_amt_type_label_0' ; 
            $vtprd_rule->rule_deal_info[0]['discount_lifetime_max_amt_type'] = 'none'; //overwrite ERROR choice with DEFAULT   
      }    
      if ($vtprd_rule->rule_deal_info[0]['discount_rule_cum_max_amt_type'] != 'none') { 
           $vtprd_rule->rule_error_message[] = array( 
                  'insert_error_before_selector' => '.top-box',  
                  'error_msg'  => __('The "Cart Maximum for all Discounts Per Product" option chosen is only available in the Pro Version. ', 'vtprd') .'<em><strong>'. __(' * Option restored to default value, * Please Update to Confirm!', 'vtprd') .'</strong></em>');
            $vtprd_rule->rule_error_red_fields[] = '#discount_rule_cum_max_amt_type_label_0' ; 
            $vtprd_rule->rule_deal_info[0]['discount_rule_cum_max_amt_type'] = 'none'; //overwrite ERROR choice with DEFAULT  
      }      
      // +ADDED   End
      //EDITED END  * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * +   
 

      //*************************
      //AUTO ADD switching (+ sort field switching as well)
      //*************************
      $vtprd_rule->rule_contains_auto_add_free_product = 'no';
      $vtprd_rule->rule_contains_free_product = 'no'; //used for sort in apply-rules.php
      $vtprd_rule->var_out_product_variations_parameter = array(); 
      $sizeof_rule_deal_info = sizeof($vtprd_rule->rule_deal_info);
      for($d=0; $d < $sizeof_rule_deal_info; $d++) {                  
         if ($vtprd_rule->rule_deal_info[$d]['discount_auto_add_free_product'] == 'yes') {
                                 
             //EDITED * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * +
            // + ADDED => pro-only option chosen                                                                             

                 $vtprd_rule->rule_error_message[] = array(                                                                              
                        'insert_error_before_selector' => '#discount_amt_box_' .$d ,  
                        'error_msg'  => __('The " Automatically Add Free Product to Cart" option chosen is only available in the Pro Version. ', 'vtprd') .'<em><strong>'. __(' * Option restored to default value, * Please Update to Confirm!', 'vtprd') .'</strong></em>'
                        );
                  $vtprd_rule->rule_error_red_fields[] = '#discount_auto_add_free_product_label_' .$d ;
                  
                  $vtprd_rule->rule_deal_info[$d]['discount_auto_add_free_product'] = ''; 

            //EDITED end  * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * +
           
         }
         if ($vtprd_rule->rule_deal_info[$d]['discount_amt_type'] == 'free') {
            $vtprd_rule->rule_contains_free_product = 'yes'; 
         }                   
      }

      //*************************
      //Pop Filter Agreement Check (switch used in apply...)
      //*************************
      $this->vtprd_maybe_pop_filter_agreement();

            
      //*************************
      //check against all other rules acting on the free product
      //*************************
      if ($vtprd_rule->rule_contains_auto_add_free_product == 'yes') {

        $vtprd_rules_set = get_option( 'vtprd_rules_set' );

        $sizeof_rules_set = sizeof($vtprd_rules_set);
       
        for($i=0; $i < $sizeof_rules_set; $i++) { 
                     
          if ( ($vtprd_rules_set[$i]->rule_status != 'publish') ||
               ($vtprd_rules_set[$i]->rule_on_off_sw_select == 'off') ) {             
             continue;
          }

            
          if ($vtprd_rules_set[$i]->post_id == $vtprd_rule->post_id) {                   
             continue;
          } 
                        
          //if another rule has the exact same FREE product, that's an ERROR
          if ($vtprd_rules_set[$i]->rule_contains_auto_add_free_product == 'yes') {  
              
               //EDITED * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * +
               //   Leave the following here...
               //EDITED * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * +
              switch( true ) {    
                
                // compare to vtprd_rule  actionpop vargroup
                case  ($vtprd_rule->actionPop == 'vargroup' ) :
                     
                      //current rule vs other rule actionPop vs actionPop
                      if (($vtprd_rules_set[$i]->actionPop           == 'vargroup') &&
                         ($vtprd_rules_set[$i]->actionPop_varProdID  == $vtprd_rule->actionPop_varProdID) &&
                         ($vtprd_rules_set[$i]->var_out_checked[0]   == $vtprd_rule->var_out_checked[0] )) {
                        $conflictPost = get_post($vtprd_rules_set[$i]->post_id);
                        $vtprd_rule->rule_error_message[] = array( 
                            'insert_error_before_selector' => '#discount_amt_box_0',  
                            'error_msg'  => __('When "Automatically Add Free Product to Cart" is Selected, no other Auto Add Rule may have the same product as the Discount Group.  CONFLICTING RULE NAME is: ', 'vtprd') .$conflictPost->post_title 
                            );
                        $vtprd_rule->rule_error_red_fields[] = '#discount_auto_add_free_product_label_0'; 
                        break 2; 
                      }   
                      
                      //current rule actionPop vs other rule inPop
                      if ($vtprd_rules_set[$i]->actionPop  == 'sameAsInPop' ) { 
                          if (($vtprd_rules_set[$i]->inPop              == 'vargroup') &&
                              ($vtprd_rules_set[$i]->inPop_varProdID    == $vtprd_rule->actionPop_varProdID) &&
                              ($vtprd_rules_set[$i]->var_in_checked[0]  == $vtprd_rule->var_out_checked[0] )) {
                            $conflictPost = get_post($vtprd_rules_set[$i]->post_id);
                            $vtprd_rule->rule_error_message[] = array( 
                                'insert_error_before_selector' => '#discount_amt_box_0',  
                                'error_msg'  => __('When "Automatically Add Free Product to Cart" is Selected, no other Auto Add Rule may have the same product as the Discount Group.  CONFLICTING RULE NAME is: ', 'vtprd') .$conflictPost->post_title 
                                );
                            $vtprd_rule->rule_error_red_fields[] = '#discount_auto_add_free_product_label_0';
                            break 2; 
                          }                      
                      }             

                   break;

                // compare to vtprd_rule  actionpop single
                case  ($vtprd_rule->actionPop == 'single' ) : 
                            
                      if (($vtprd_rules_set[$i]->actionPop              == 'single') &&
                          ($vtprd_rules_set[$i]->actionPop_singleProdID == $vtprd_rule->actionPop_singleProdID) ) { 
                        $conflictPost = get_post($vtprd_rules_set[$i]->post_id);
                        $vtprd_rule->rule_error_message[] = array( 
                            'insert_error_before_selector' => '#discount_amt_box_0',  
                            'error_msg'  => __('When "Automatically Add Free Product to Cart" is Selected, no other Auto Add Rule may have the same product as the Discount Group.  CONFLICTING RULE NAME is: ', 'vtprd') . $conflictPost->post_title 
                            );
                        $vtprd_rule->rule_error_red_fields[] = '#discount_auto_add_free_product_label_0';
                        break 2;              
                      }                   

                      
                      //current rule actionPop vs other rule inPop
                      if ($vtprd_rules_set[$i]->actionPop  == 'sameAsInPop' ) { 
                          if (($vtprd_rules_set[$i]->inPop                == 'single') &&
                              ($vtprd_rules_set[$i]->inPop_singleProdID   == $vtprd_rule->actionPop_singleProdID) ) { 
                            $conflictPost = get_post($vtprd_rules_set[$i]->post_id);
                            $vtprd_rule->rule_error_message[] = array( 
                                'insert_error_before_selector' => '#discount_amt_box_0',  
                                'error_msg'  => __('When "Automatically Add Free Product to Cart" is Selected, no other Auto Add Rule may have the same product as the Discount Group.  CONFLICTING RULE NAME is: ', 'vtprd') . $conflictPost->post_title 
                                );
                            $vtprd_rule->rule_error_red_fields[] = '#discount_auto_add_free_product_label_0';
                            break 2;              
                          }                 
                      }

                   break;

                // compare to vtprd_rule  inpop vargroup
                case  ( ($vtprd_rule->actionPop == 'sameAsInPop' ) &&
                        ($vtprd_rule->inPop     == 'vargroup' ) ) : 
                      
                      //current rule vs other rule actionPop vs actionPop
                      if (($vtprd_rules_set[$i]->actionPop            == 'vargroup') &&
                          ($vtprd_rules_set[$i]->actionPop_varProdID  == $vtprd_rule->inPop_varProdID) &&
                          ($vtprd_rules_set[$i]->var_out_checked[0]   == $vtprd_rule->var_in_checked[0] ) ) {
                        $conflictPost = get_post($vtprd_rules_set[$i]->post_id);
                        $vtprd_rule->rule_error_message[] = array( 
                            'insert_error_before_selector' => '#discount_amt_box_0',  
                            'error_msg'  => __('When "Automatically Add Free Product to Cart" is Selected, no other Auto Add Rule may have the same product as the Discount Group.  CONFLICTING RULE NAME is: ', 'vtprd') . $conflictPost->post_title 
                            );
                        $vtprd_rule->rule_error_red_fields[] = '#discount_auto_add_free_product_label_0';
                        break 2; 
                      }   
                      
                      //current rule actionPop vs other rule inPop
                      if ($vtprd_rules_set[$i]->actionPop  == 'sameAsInPop' ) { 
                          if (($vtprd_rules_set[$i]->inPop             == 'vargroup') &&
                              ($vtprd_rules_set[$i]->inPop_varProdID   == $vtprd_rule->inPop_varProdID) &&
                              ($vtprd_rules_set[$i]->var_in_checked[0] == $vtprd_rule->var_in_checked[0] )) {
                            $conflictPost = get_post($vtprd_rules_set[$i]->post_id);
                            $vtprd_rule->rule_error_message[] = array( 
                                'insert_error_before_selector' => '#discount_amt_box_0',  
                                'error_msg'  => __('When "Automatically Add Free Product to Cart" is Selected, no other Auto Add Rule may have the same product as the Discount Group.  CONFLICTING RULE NAME is: ', 'vtprd') . $conflictPost->post_title 
                                );
                            $vtprd_rule->rule_error_red_fields[] = '#discount_auto_add_free_product_label_0';
                            break 2; 
                          }                      
                      }             

                   break;

                // compare to vtprd_rule  inpop single
                case  ( ($vtprd_rule->actionPop == 'sameAsInPop' ) &&
                        ($vtprd_rule->inPop     == 'single' ) ) : 
                                                              
                      if ( ($vtprd_rules_set[$i]->actionPop               == 'single') && 
                           ($vtprd_rules_set[$i]->actionPop_singleProdID  == $vtprd_rule->inPop_singleProdID) ) { 
                        $conflictPost = get_post($vtprd_rules_set[$i]->post_id);
                        $vtprd_rule->rule_error_message[] = array( 
                            'insert_error_before_selector' => '#discount_amt_box_0',  
                            'error_msg'  => __('When "Automatically Add Free Product to Cart" is Selected, no other Auto Add Rule may have the same product as the Discount Group.  CONFLICTING RULE NAME is: ', 'vtprd') . $conflictPost->post_title 
                            );
                        $vtprd_rule->rule_error_red_fields[] = '#discount_auto_add_free_product_label_0';
                        break 2;              
                      }                   

                      
                      //current rule actionPop vs other rule inPop
                      if ($vtprd_rules_set[$i]->actionPop  == 'sameAsInPop' ) { 
                          if ( ($vtprd_rules_set[$i]->inPop               == 'single') && 
                               ($vtprd_rules_set[$i]->inPop_singleProdID  == $vtprd_rule->inPop_singleProdID) ) { 
                            $conflictPost = get_post($vtprd_rules_set[$i]->post_id);
                            $vtprd_rule->rule_error_message[] = array( 
                                'insert_error_before_selector' => '#discount_amt_box_0',  
                                'error_msg'  => __('When "Automatically Add Free Product to Cart" is Selected, no other Auto Add Rule may have the same product as the Discount Group.  CONFLICTING RULE NAME is: ', 'vtprd') . $conflictPost->post_title 
                                );
                            $vtprd_rule->rule_error_red_fields[] = '#discount_auto_add_free_product_label_0';
                            break 2;              
                          }                 
                      }

                   break;                   
                   
            }  //end switch
          } //end if
          
        } //end 'for' loop
      } //end if auto product 
      //*************************



  } //end vtprd_edit_rule
  
  
  public function vtprd_update_rules_info() {
    global $post, $vtprd_rule, $vtprd_rules_set; 

/*      
    //set the switch used on the screen for JS data check
    switch( true ) {
      case (!$vtprd_rule->rule_deal_info[0]['discount_rule_max_amt_type'] == 'none'):
      case ( $vtprd_rule->rule_deal_info[0]['discount_rule_max_amt_count'] > 0) :
      case (!$vtprd_rule->rule_deal_info[0]['discount_lifetime_max_amt_type'] == 'none'):
      case ( $vtprd_rule->rule_deal_info[0]['discount_lifetime_max_amt_count'] > 0) :
      case (!$vtprd_rule->rule_deal_info[0]['discount_rule_cum_max_amt_type'] == 'none'):
      case ( $vtprd_rule->rule_deal_info[0]['discount_rule_cum_max_amt_count'] > 0) :
          $vtprd_rule->advancedSettingsDiscountLimits = 'yes';
        break;
    }
  */
    //*****************************************
    //  If errors were found, the error message array will be displayed by the UI on next screen send.
    //*****************************************
    if  ( sizeof($vtprd_rule->rule_error_message) > 0 ) {
      $vtprd_rule->rule_status = 'pending';
    } else {
      $vtprd_rule->rule_status = 'publish';
    }

    $rules_set_found = false;
    $vtprd_rules_set = get_option( 'vtprd_rules_set' ); 
    if ($vtprd_rules_set) {
      $rules_set_found = true;
    }
          
    if ($rules_set_found) {
      $rule_found = false;
      $sizeof_rules_set = sizeof($vtprd_rules_set);
      for($i=0; $i < $sizeof_rules_set; $i++) {       
         if ($vtprd_rules_set[$i]->post_id == $post->ID) {
            $vtprd_rules_set[$i] = $vtprd_rule;
            $i =  $sizeof_rules_set;
            $rule_found = true;
         }
      }
      if (!$rule_found) {
         $vtprd_rules_set[] = $vtprd_rule;        
      } 
    } else {
      $vtprd_rules_set = array();
      $vtprd_rules_set[] = $vtprd_rule;
    }

    if ($rules_set_found) {
      update_option( 'vtprd_rules_set',$vtprd_rules_set );
    } else {
      add_option( 'vtprd_rules_set',$vtprd_rules_set );
    }
                                                 
    //**************
    //keep a running track of $vtprd_display_type_in_rules_set   ==> used in apply-rules processing
    //*************
    if ($vtprd_rule->rule_execution_type  == 'display') {
      $ruleset_has_a_display_rule = 'yes';
    } else { 
      $ruleset_has_a_display_rule = 'no';
      $sizeof_rules_set = sizeof($vtprd_rules_set);
      for($i=0; $i < $sizeof_rules_set; $i++) { 
         if ($vtprd_rules_set[$i]->rule_execution_type == 'display') {
            $i =  $sizeof_rules_set;
            $ruleset_has_a_display_rule = 'yes'; 
         }
      }
    } 

   
    if (get_option('vtprd_ruleset_has_a_display_rule') == true) {
      update_option( 'vtprd_ruleset_has_a_display_rule',$ruleset_has_a_display_rule );
    } else {
      add_option( 'vtprd_ruleset_has_a_display_rule',$ruleset_has_a_display_rule );
    }
    //**************        
    
    //nuke the browser session variables in this case - allows clean retest ...
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }      
    session_destroy();
    
    
    return;
  } 
  
  public function vtprd_validate_rule_scheduling() {
    global $vtprd_rule, $vtprd_setup_options;  
    
    $date_valid = true;     
    $loop_ended = 'no';
    $today = date("Y-m-d");

    if ( $vtprd_setup_options['use_this_timeZone'] == 'none') {
        $vtprd_rule->rule_error_message[] = array( 
          'insert_error_before_selector' => '#date-line-0',  
          'error_msg'  => __('Scheduling requires setup', 'vtprd') );
        $date_valid = false; 
    }

    for($t=0; $loop_ended  == 'no'; $t++) {

      if ( (isset($_REQUEST['date-begin-' .$t])) ||
           (isset($_REQUEST['date-end-' .$t])) ) {  
       
        $date = $_REQUEST['date-begin-' .$t];
        
        $vtprd_rule->periodicByDateRange[$t]['rangeBeginDate'] = $date;

        if (!vtprd_checkDateTime($date)) {
           $vtprd_rule->rule_error_red_fields[] = '#date-begin-' .$t. '-error';
           $date_valid = false;
        }

        $date = $_REQUEST['date-end-' .$t];
        $vtprd_rule->periodicByDateRange[$t]['rangeEndDate'] = $date;
        if (!vtprd_checkDateTime($date)) {
           $vtprd_rule->rule_error_red_fields[] = '#date-end-' .$t. '-error';
           $date_valid = false;
        }
      
      } else {
        $loop_ended = true;
        break;        
      }

      if ($vtprd_rule->periodicByDateRange[$t]['rangeBeginDate'] >  $vtprd_rule->periodicByDateRange[$t]['rangeEndDate']) {
          $vtprd_rule->rule_error_message[] = array( 
            'insert_error_before_selector' => '#date-line-0',  
            'error_msg'  => __('End Date must be Greater than or equal to Begin Date.', 'vtprd') );
          $vtprd_rule->rule_error_red_fields[] = '#end-date-label-' .$t;
          $date_valid = false;
      }    
      //emergency exit
      if ($t > 9) {
        break; //exit the for loop
      }
    } 
    
    if (!$date_valid) {
      $vtprd_rule->rule_error_message[] = array( 
            'insert_error_before_selector' => '#vtprd-rule-scheduling',  
            'error_msg'  => __('Please repair date error.', 'vtprd') );                   
    }
    
  } 

  public function vtprd_build_ruleInWords() {
    global $vtprd_rule;
    
    //Don't process if errors present
  /*  if  ( sizeof($vtprd_rule->rule_error_message) > 0 ) {
      $vtprd_rule->ruleInWords = '';
      return;
    }    */
    
    $vtprd_rule->ruleInWords = ''; 
    
    switch( $vtprd_rule->rule_template   ) {
      //display templates
      case 'D-storeWideSale':  //Store-Wide Sale with a Percentage or $$ Value Off, at Catalog Display Time - Realtime
      case 'C-storeWideSale':  //Store-Wide Sale with a Percentage or $$ Value Off all Products in the Cart          vtprd_buy_info(
          $vtprd_rule->ruleInWords .= $this->vtprd_show_buy_info();
          $vtprd_rule->ruleInWords .= $this->vtprd_show_action_info();
          $vtprd_rule->ruleInWords .= $this->vtprd_show_discount_amt();
        break;
      case 'D-simpleDiscount':  //Membership Discount in the Buy Pool Group, at Catalog Display Time - Realtime
      case 'C-simpleDiscount':  //Sale Price by any Buy Pool Group Criteria [Product / Category / Custom Taxonomy Category / Membership / Wholesale] - Cart
          $vtprd_rule->ruleInWords .= $this->vtprd_show_buy_info();
          $vtprd_rule->ruleInWords .= $this->vtprd_show_action_info();
          $vtprd_rule->ruleInWords .= $this->vtprd_show_discount_amt();          
        //  $vtprd_rule->ruleInWords .= $this->vtprd_show_pop();
        break;
      default:    
          $vtprd_rule->ruleInWords .= $this->vtprd_show_buy_info();
          $vtprd_rule->ruleInWords .= $this->vtprd_show_action_info();
          $vtprd_rule->ruleInWords .= $this->vtprd_show_discount_amt();          
          $vtprd_rule->ruleInWords .= $this->vtprd_show_repeats();
        //  $vtprd_rule->ruleInWords .= $this->vtprd_show_pop();
          $vtprd_rule->ruleInWords .= $this->vtprd_show_limits();   
        break;
    }
    
    //replace $ with the currency symbol set up on the Parent Plugin!!
    $currency_symbol = vtprd_get_currency_symbol();
    $vtprd_rule->ruleInWords = str_replace('$', $currency_symbol, $vtprd_rule->ruleInWords);
    
  } 
  
  public function vtprd_show_buy_info() {
    global $vtprd_rule;  
    $output;    
    switch( $vtprd_rule->rule_template   ) {
      case 'D-storeWideSale':
          $output .= '<span class="words-line"><span class="words-line-buy">' . __('* For</span><!-- 001 --> any item,', 'vtprd') . '</span><!-- 001a --><!-- /words-line-->';
          return $output;
        break;      
      case 'D-simpleDiscount': 
          $output .= '<span class="words-line"><span class="words-line-buy">' . __('* For</span><!-- 002 --> any item within the defined Buy group,', 'vtprd') . '</span><!-- 002a --><!-- /words-line-->';
          return $output;
        break;
      case 'C-storeWideSale':
          $output .= '<span class="words-line"><span class="words-line-buy">' . __('* Buy</span><!-- 003 --> any item,', 'vtprd') . '</span><!-- 003a --><!-- /words-line-->';
          return $output;
        break;      
      case 'C-simpleDiscount': 
          $output .= '<span class="words-line"><span class="words-line-buy">' . __('* Buy</span><!-- 005 --> any item within the Buy defined group,', 'vtprd') . '</span><!-- 005a --><!-- /words-line-->';
          return $output;
        break;
      default:
          $output .= '<span class="words-line"><span class="words-line-buy">' . __('* Buy</span><!-- 007 --> ', 'vtprd') ;
        break;
    }
     
    switch( $vtprd_rule->rule_deal_info[0]['buy_amt_type']  ) {    
      case 'none':
          $output .= __('any item within the defined Buy group,', 'vtprd') . '</span><!-- 008 -->';
          return $output;
        break;
      case 'one':
          $output .= __('one item within the defined Buy group,', 'vtprd') . '</span><!-- 009 -->';
          return $output;
        break; 
      case 'quantity':
          $output .= $vtprd_rule->rule_deal_info[0]['buy_amt_count'];
          $output .= __(' units', 'vtprd'); 
        break; 
      case 'currency':
          $output .= '$' . $vtprd_rule->rule_deal_info[0]['buy_amt_count'];                    
        break;
      case 'nthQuantity':
          $output .= __('every', 'vtprd'); 
          $output .= '$' . $vtprd_rule->rule_deal_info[0]['buy_amt_count'];
          $output .= __('th unit ', 'vtprd');                    
        break;
    }    
 
 
    switch( $vtprd_rule->rule_deal_info[0]['buy_amt_mod']  ) {
      case 'none':
        break;
      case 'minCurrency':
          $output .= '<br> &nbsp;&nbsp;&nbsp; -'; 
          $output .= __(' for a mininimum of ', 'vtprd');
          $output .= '$' . $vtprd_rule->rule_deal_info[0]['buy_amt_mod_count'];
        break; 
      case 'maxCurrency':
          $output .= '<br> &nbsp;&nbsp;&nbsp; -'; 
          $output .= __(' for a maxinimum of ', 'vtprd');
          $output .= '$' . $vtprd_rule->rule_deal_info[0]['buy_amt_mod_count'];
        break;               
    }   
    
    switch( $vtprd_rule->rule_deal_info[0]['buy_amt_applies_to']  ) {
      case 'all':
          $output .= '<br> &nbsp;&nbsp;&nbsp; -'; 
          $output .= __(' within the Buy group', 'vtprd');
        break;
      case 'each':
          $output .= '<br> &nbsp;&nbsp;&nbsp; -'; 
          $output .= __(' of each product quantity of the defined Buy group', 'vtprd');
        break;        
    }
    $output .=  '</span><!-- 010 -->';
   
    return $output;   
  } 


    
  public function vtprd_show_action_info() {
    global $vtprd_rule;  
    $output;    
    switch( $vtprd_rule->rule_template   ) {                      
      case 'D-storeWideSale':    
      case 'D-simpleDiscount': 
          $output .= '<span class="words-line"><span class="words-line-get">' .  __('* Get ', 'vtprd') . '</span><!-- 012 -->';
        break;
      case 'C-storeWideSale':    
      case 'C-simpleDiscount':
      case 'C-discount-inCart':
      case 'C-cheapest-inCart': 
          $output .= '<span class="words-line"><span class="words-line-get">' .  __('* Get ', 'vtprd') . '</span><!-- 014 -->';
        break;
      case 'C-forThePriceOf-inCart':    //Buy 5, get them for the price of 4/$400
          $output .= '<span class="words-line"><span class="words-line-get">' .  __('* Get ', 'vtprd') . '</span><!-- 014 -->' .  __('the Buy Group ', 'vtprd') . '</span>';
          return $output;
        break;  
      case 'C-discount-Next':
      case 'C-forThePriceOf-Next':     // Buy 5/$500, get next 3 for the price of 2/$200 - Cart
      case 'C-cheapest-Next':
      case 'C-nth-Next':
          $output .= '<span class="words-line"><span class="words-line-get">' .  __('* Get ', 'vtprd') . '</span><!-- 014 -->' .  __('the Next - ', 'vtprd');
        break;               
      default:
          $output .= '<span class="words-line"><span class="words-line-get">' .  __('* Get ', 'vtprd') . '</span><!-- 015 -->';
        break;
    }
     
    switch( $vtprd_rule->rule_deal_info[0]['action_amt_type']  ) {    
      case 'none':
          $output .= __('any item', 'vtprd');
          $output .= '<br> &nbsp;&nbsp;&nbsp; -';  
          $output .= __('within the defined Get group,', 'vtprd'); 
          return $output;
        break;
      case 'one':
          $output .= __('one item', 'vtprd');
          $output .= '<br> &nbsp;&nbsp;&nbsp; -';  
          $output .= __('within the defined Get group,', 'vtprd');
          return $output;
        break; 
      case 'quantity':
          $output .= $vtprd_rule->rule_deal_info[0]['action_amt_count'];
          $output .= __(' units', 'vtprd'); 
        break; 
      case 'currency':
          $output .= '$' . $vtprd_rule->rule_deal_info[0]['action_amt_count'];                    
        break;
      case 'nthQuantity':
          $output .= __('every', 'vtprd'); 
          $output .= '$' . $vtprd_rule->rule_deal_info[0]['action_amt_count'];
          $output .= __('th unit ', 'vtprd');                    
        break;
    }    
 
    switch( $vtprd_rule->rule_deal_info[0]['action_amt_mod']  ) {
      case 'none':
        break;
      case 'minCurrency':
          $output .= '<br> &nbsp;&nbsp;&nbsp; -'; 
          $output .= __(' for a mininimum of ', 'vtprd');
          $output .= '$' . $vtprd_rule->rule_deal_info[0]['action_amt_mod_count'];
        break; 
      case 'maxCurrency':
          $output .= '<br> &nbsp;&nbsp;&nbsp; -'; 
          $output .= __(' for a maxinimum of ', 'vtprd');
          $output .= '$' . $vtprd_rule->rule_deal_info[0]['action_amt_mod_count'];
        break;               
    }   
    
    switch( $vtprd_rule->rule_deal_info[0]['action_amt_applies_to']  ) {
      case 'all':
          $output .= '<br> &nbsp;&nbsp;&nbsp; -'; 
          $output .= __(' within the Get group', 'vtprd');
        break;
      case 'each':
          $output .= '<br> &nbsp;&nbsp;&nbsp; -'; 
          $output .= __(' of each product quantity of the defined Get group', 'vtprd');
        break;        
    }
    $output .=  '</span><!-- 018 --><!-- /words-line-->';
    
    return $output;   
  }   
 
     
  public function vtprd_show_discount_amt() {
    global $vtprd_rule;  
    $output;    
    
    switch( $vtprd_rule->rule_deal_info[0]['discount_applies_to'] ) {
      case 'each':
          $output .= '<span class="words-line"> &nbsp;&nbsp;&nbsp; -';
          $output .=  __('each product ', 'vtprd');
        break;
      case 'all': 
          if ( $vtprd_rule->rule_template != 'C-forThePriceOf-inCart' ) { //Don't show for  "Buy 5, get them for the price of 4/$400"
            $output .= '<span class="words-line"> &nbsp;&nbsp;&nbsp; -';
            $output .=  __('all products', 'vtprd');
          }
        break;      
      case 'cheapest':
          $output .= '<span class="words-line"> &nbsp;&nbsp;&nbsp; -';
          $output .=  __('cheapest product in the group ', 'vtprd');
        break;       
      case 'most_expensive':
          $output .= '<span class="words-line"> &nbsp;&nbsp;&nbsp; -';
          $output .=  __('most expensive product in the group ', 'vtprd');
        break;
      default:
        break; 
    /*  default:
          $output .=  __(' discount_applies_to= ', 'vtprd');
          $output .=  $vtprd_rule->rule_deal_info[0]['discount_applies_to'];
          $output .=  __('end ', 'vtprd');
        break;   */
    }
    
    $output .= '</span><!-- 018b --><span class="words-line"><span class="words-line-get">';
    $output .= __('* For ', 'vtprd') . '</span><!-- 018c -->';  
    
    switch( $vtprd_rule->rule_deal_info[0]['discount_amt_type'] ) {
      case 'percent':
          $output .=  $vtprd_rule->rule_deal_info[0]['discount_amt_count'] . __('% off', 'vtprd');
        break;
      case 'currency': 
          $amt = vtprd_format_money_element( $vtprd_rule->rule_deal_info[0]['discount_amt_count'] );
          $output .= $amt . __(' off', 'vtprd');
        break;      
      case 'fixedPrice':
          $amt = vtprd_format_money_element( $vtprd_rule->rule_deal_info[0]['discount_amt_count'] );
          $output .= $amt;
        break;       
      case 'free':
          $output .=  __('Free', 'vtprd');
        break;
      case 'forThePriceOf_Units': 
      case 'forThePriceOf_Currency':
         $output .=  __('the Group Price of $', 'vtprd');
         $output .=  $vtprd_rule->rule_deal_info[0]['discount_amt_count']; 
        break;      
    }  
        
    switch( $vtprd_rule->rule_template   ) {
      case 'D-storeWideSale':
      case 'D-simpleDiscount': 
          $output .= '<br> &nbsp;&nbsp;&nbsp; -'; 
          $output .=  __(' when catalog displays.', 'vtprd')  . '</span><!-- 019 -->'; //'</span><!-- 019 --> </span><!-- 019a -->';
        break;
      default:
          $output .= '<br> &nbsp;&nbsp;&nbsp; -'; 
          $output .=  __(' when added to cart.', 'vtprd') . '</span><!-- 020 -->'; //'</span><!-- 020 --> </span><!-- 020a -->';
        break;
    }       
    
    return $output;
  }
 
  
  public function vtprd_show_pop() {  
    global $vtprd_rule;  
   
    $output = '<span class="words-line extra-top-margin">';  
    
    $output .= '&nbsp;&nbsp;&nbsp; -';
    switch( $vtprd_rule->inPop ) {
      case 'wholeStore':                                                                                      
          if ( ($vtprd_rule->actionPop == 'sameAsInPop') ||              //in these cases, inpop/actionpop treated as 'sameAsInPop'
               ($vtprd_rule->actionPop == 'wholeStore') ||
               ($vtprd_rule->actionPop == 'cart') ) {
            $output .=  __(' Acts on the Whole Store ', 'vtprd'); 
          }  else {
            $output .=  __(' The Buy Group is the Whole Store ', 'vtprd');
          }         
        break;
      case 'cart':                                                                                      
          if ( ($vtprd_rule->actionPop == 'sameAsInPop') ||              //in these cases, inpop/actionpop treated as 'sameAsInPop'
               ($vtprd_rule->actionPop == 'wholeStore') ||
               ($vtprd_rule->actionPop == 'cart') ) {
            $output .=  __(' Acts on the any Product in the Cart ', 'vtprd'); 
          }  else {
            $output .=  __(' Buy Group is any Product in the Cart ', 'vtprd');
          }                              
        break;
  
      //EDITED * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * + 
             
    }

   switch( $vtprd_rule->actionPop ) { 
      case 'sameAsInPop':
      case 'wholeStore':;           
      case 'cart':
        //all done, all processing completed while handling inpop above                                                                                     
        break;
  
    //EDITED * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * +
    
    }      
/*   
     //**********************************************************
     If inpop = ('wholeStore' or 'cart') and actionpop = ('sameAsInPop' or 'wholeStore' or 'cart')
        inpop and actionpop are treated as a single group ('sameAsInPop'), and the 'ball' bounces between them.
     //**********************************************************
        //logic from apply-rules.php:
        switch( $vtprd_rules_set[$i]->inpop ) {
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

*/   

     $output .= '</span><!-- 021 -->';
     
    return $output; 
  } 
  
 
  public function vtprd_show_repeats() {
    global $vtprd_rule;  
    $output;
     
    
    switch( $vtprd_rule->rule_deal_info[0]['action_repeat_condition'] ) {
      case 'none':
        break;
      case 'unlimited': 
          $output .= '<span class="words-line extra-top-margin"><em>'; //here due to 'none'
          $output .=  __('Once the Buy group threshhold has been reached, the action group repeats an unlimited number of times. ', 'vtprd');
          $output .=  '</em></span><!-- 023 -->';
        break;      
      case 'count':
          $output .= '<span class="words-line extra-top-margin"><em>';
          $output .=  __('Once the Buy group threshhold has been reached, the action group repeats ', 'vtprd'); 
          $output .=  $vtprd_rule->rule_deal_info[0]['action_repeat_count'];
          $output .=  __(' times. ', 'vtprd');
          $output .=  '</em></span><!-- 024 -->';
        break;       
    }
    
        
    switch( $vtprd_rule->rule_deal_info[0]['buy_repeat_condition'] ) {
      case 'none':
        break;
      case 'unlimited': 
          $output .= '<span class="words-line extra-top-margin"><em>';
          $output .=  __('The entire rule repeats an unlimited number of times. ', 'vtprd');
          $output .=  '</em></span><!-- 024 -->';
        break;      
      case 'count':
          $output .= '<span class="words-line extra-top-margin"><em>';
          $output .=  __('The entire rule repeats ', 'vtprd');  
          $output .=  $vtprd_rule->rule_deal_info[0]['buy_repeat_count'];
          $output .=  __(' times. ', 'vtprd');
          $output .=  '</em></span><!-- 024 -->';
        break;       
    }
    
    return $output;
  }  
  
 
  public function vtprd_show_limits() {
    global $vtprd_rule;  
    $output;
        
    switch( $vtprd_rule->rule_deal_info[0]['discount_rule_max_amt_type']) {
      case 'none':
        break;
      case 'percent':
          $output .=  __(' Discount Cart Maximum set at ', 'vtprd');
          $output .= $vtprd_rule->rule_deal_info[0]['discount_rule_max_amt_count'];
          $output .=  __('% ', 'vtprd');
        break;
      case 'quantity':
          $output .=  __(' Discount Cart Maximum set at ', 'vtprd');
          $output .= $vtprd_rule->rule_deal_info[0]['discount_rule_max_amt_count'];
          $output .=  __(' times it can be applied. ', 'vtprd');
        break;
      case 'currency':
          $output .=  __(' Discount Cart Maximum set at $$', 'vtprd');
          $output .= $vtprd_rule->rule_deal_info[0]['discount_rule_max_amt_count'];      
        break; 
    }
        
    switch( $vtprd_rule->rule_deal_info[0]['discount_lifetime_max_amt_type']) {
      case 'none':
        break;
      case 'percent':
          $output .=  __(' Discount Lifetime Maximum set at ', 'vtprd');
          $output .= $vtprd_rule->rule_deal_info[0]['discount_lifetime_max_amt_count'];
          $output .=  __('% ', 'vtprd');
        break;
      case 'quantity':
          $output .=  __(' Discount Lifetime Maximum set at ', 'vtprd');
          $output .= $vtprd_rule->rule_deal_info[0]['discount_lifetime_max_amt_count'];
          $output .=  __(' times it can be applied. ', 'vtprd');
        break;
      case 'currency':
          $output .=  __(' Discount Lifetime Maximum set at $$', 'vtprd');
          $output .= $vtprd_rule->rule_deal_info[0]['discount_lifetime_max_amt_count'];      
        break; 
    }    
        
    switch( $vtprd_rule->rule_deal_info[0]['discount_rule_cum_max_amt_type']) {
      case 'none':
        break;
      case 'percent':
          $output .=  __(' Discount Cumulative Maximum set at ', 'vtprd');
          $output .= $vtprd_rule->rule_deal_info[0]['discount_rule_cum_max_amt_count'];
          $output .=  __('% ', 'vtprd');
        break;
      case 'quantity':
          $output .=  __(' Discount Cumulative_cum Maximum set at ', 'vtprd');
          $output .= $vtprd_rule->rule_deal_info[0]['discount_rule_cum_max_amt_count'];
          $output .=  __(' times it can be applied. ', 'vtprd');
        break;
      case 'currency':
          $output .=  __(' Discount Cumulative Maximum set at $$', 'vtprd');
          $output .= $vtprd_rule->rule_deal_info[0]['discount_rule_cum_max_amt_count'];      
        break; 
    }   
      
             
    return $output;
  }    
  
/*  
 //default to 'OR', as the default value goes away and may be needed if the user switches back to 'groups'...
  public function vtprd_set_default_or_values_in() {
    global $vtprd_rule;  
   // $vtprd_rule->role_and_or_in[1]['user_input'] = 's'; //'s' = 'selected'
    $vtprd_rule->role_and_or_in = 'or';
  } */
 /*
 //default to 'OR', as the default value goes away and may be needed if the user switches back to 'groups'...
  public function vtprd_set_default_or_values_out() {
    global $vtprd_rule;  
   // $vtprd_rule->role_and_or_out[1]['user_input'] = 's'; //'s' = 'selected'
    $vtprd_rule->role_and_or_out = 'or';
  }   */

  public function vtprd_initialize_deal_structure_framework() {
    global $vtprd_deal_structure_framework;
    foreach( $vtprd_deal_structure_framework as $key => $value ) { 
    //for($i=0; $i < sizeof($vtprd_deal_field_name_array); $i++) {
       $vtprd_deal_structure_framework[$value] = '';
       //FIX THIS -> BUG where the foreach goes beyond the end of the $vtprd_deal_structure_framework - emergency eXIT
       if ($key == 'discount_rule_cum_max_amt_count') {
         break; //emergency end of the foreach...
       }            
    }     
  }
  
  //**********************
  // DEAL Line Edits
  //**********************
  public function vtprd_edit_deal_info_line($active_field_count, $active_line_count, $k ) {
    global $vtprd_rule, $vtprd_deal_structure_framework, $vtprd_deal_edits_framework;
   
    $skip_amt_edit_dropdown_values  =  array('once', 'none' , 'zero', 'one' , 'unlimited', 'each', 'all', 'cheapest', 'most_expensive');
   
   //FIX THIS LATER!!!!!!!!!!!!!!!
   /* if ($active_field_count == 0) { 
      if ( !isset( $_REQUEST['dealInfoLine_' . ($k + 1) ] ) ) {  //if we're on the last line onscreen
         if ($k == 0) { //if the 1st line is the only line 
            $vtprd_rule->rule_error_message[] = array( 'insert_error_before_selector' => '#rule_deal_info_line.0',  //errmsg goes before the 1st line onscreen
                                                        'error_msg'  => __('Deal Info Line must be filled in, for the rule to be valid.', 'vtprd')  );
          }  else {
            $vtprd_rule->rule_error_message[] = array( 'insert_error_before_selector' => '#rule_deal_info_line.' .$k,  //errmsg goes before current onscreen line
                                                        'error_msg'  => __('At least one Deal Info Line must be filled in, for the rule to be valid.', 'vtprd')  );        
          }
        
      } else {    //this empty line is not the last...
            $vtprd_rule->rule_error_message[] = array( 'insert_error_before_selector' => '#rule_deal_info_line.' .$k,  //errmsg goes before current onscreen line
                                                       'error_msg'  => __('Deal Info Line is not filled in.  Please delete the line.', 'vtprd')  );      
      }
      return;
    }    */

  
    //Go through all of the possible deal structure fields            
    foreach( $vtprd_deal_edits_framework as $fieldName => $fieldAttributes ) {      
       /* ***********************
       special handling for  discount_rule_max_amt_type, discount_lifetime_max_amt_type.  Even though they appear iteratively in deal info,
       they are only active on the '0' occurrence line.  further, they are displayed only AFTER all of the deal lines are displayed
       onscreen... This is actually a kluge, done to utilize the complete editing already available here for a  dropdown and an associated amt field.
       The ui-php points to the '0' iteration of the deal data, when displaying these fields.
       *********************** */
       if ( ($fieldName == 'discount_rule_max_amt_type' )     || ($fieldName == 'discount_rule_max_amt_count' ) ||
            ($fieldName == 'discount_rule_cum_max_amt_type' ) || ($fieldName == 'discount_rule_cum_max_amt_count' ) ||
            ($fieldName == 'discount_lifetime_max_amt_type' ) || ($fieldName == 'discount_lifetime_max_amt_count' ) ) {
          //only process these combos on the 1st iteration only!!
          if ($k > 0) {
             break;
          }
       }

      $field_has_an_error = 'no'; 
      //if the DEAL STRUCTURE KEY field name is in the RULE EDITS array
      if ( $fieldAttributes['edit_is_active'] ) {   //if field active for this template selection
        $dropdown_status; //init variable
        $dropdown_value;  //init variable  
        switch( $fieldAttributes['field_type'] ) {
          case 'dropdown':                   
                if ( ( $vtprd_deal_structure_framework[$fieldName] == '0' ) || ($vtprd_deal_structure_framework[$fieldName] == ' ' ) || ($vtprd_deal_structure_framework[$fieldName] == ''  ) ) {   //dropdown value not selected
                    if ( $fieldAttributes['required_or_optional'] == 'required' ) {                          
                      $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector']. '_' . $k,  //errmsg goes before current onscreen line
                        'error_msg'  => $fieldAttributes['field_label'] . __(' is required. Please select an option.', 'vtprd') );
                      $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ; 
                      $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ;        
                      $dropdown_status = 'error';
                      $field_has_an_error = 'yes';
                    }  else {
                       $dropdown_status = 'notSelected'; //optional, still at title, Nothing selected
                    }
                } else {  //something selected
                  //standard 'selected' path              
                  $dropdown_status = 'selected';
                  $dropdown_value  =  $vtprd_deal_structure_framework[$fieldName];
                } 
             break;

          case 'amt':   //amt is ALWAYS preceeded by a dropdown of some sort...
              //clear the amt field if the matching dropdown is not selected
              if ($dropdown_status == 'notSelected') {
                 $vtprd_deal_structure_framework[$fieldName] = ''; //initialize the amt field
                 break;
              }
              //clear the amt field if the matching dropdown is selected, but has a value of  'none', etc.. [values not requiring matching amt]
              $dropdown_values_with_no_amt = array('none', 'unlimited', 'zero', 'one', 'no', 'free', 'each', 'all', 'cheapest', 'most_expensive');
              if ( ($dropdown_status == 'selected') && (in_array($dropdown_value, $dropdown_values_with_no_amt)) ) {
                 $vtprd_deal_structure_framework[$fieldName] = ''; //initialize the amt field
                 break;              
              }                           
             
              // if 'once', 'none' , 'unlimited' on dropdown , then amt field not relevant.
              if ( ($dropdown_status == 'selected') &&  ( in_array($dropdown_value, $skip_amt_edit_dropdown_values) )  ) {                                      
                break;
              }                         
              
              $vtprd_deal_structure_framework[$fieldName] =  preg_replace('/[^0-9.]+/', '', $vtprd_deal_structure_framework[$fieldName]); //remove leading/trailing spaces, percent sign, dollar sign
              if ( !is_numeric($vtprd_deal_structure_framework[$fieldName]) ) {  // not numeric covers it all....
                 if ($dropdown_status == 'selected') { //only produce err msg if previous dropdown status=selected [otherwise amt field cannot be entered]              
                    if  ($vtprd_deal_structure_framework[$fieldName] <= ' ') {  //if blank, use 'required' msg
                        if ( $fieldAttributes['required_or_optional'] == 'required' ) {
                           $error_msg = $fieldAttributes['field_label'] . 
                                        __(' is required. Please enter a value.', 'vtprd'); 
                        } else {
                           $error_msg = $fieldAttributes['field_label'] . 
                                        __(' must have a value when a count option chosen in ', 'vtprd') .
                                        $fieldAttributes['matching_dropdown_label'];
                                                       
                        }
                     } else { //something entered but not numeric...
                        if ( $fieldAttributes['required_or_optional'] == 'required' ) {
                           $error_msg = $fieldAttributes['field_label'] . 
                                        __(' is required and not numeric. Please enter a numeric value <em>only</em>.', 'vtprd');
                        } else {
                           $error_msg = $fieldAttributes['field_label'] . 
                                        __(' is not numeric, and must have a value value when a count option chosen in ', 'vtprd') .
                                        $fieldAttributes['matching_dropdown_label'];                             
                        }                         
                     }
                     
                     $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector'] . '_' . $k,  //errmsg goes before current onscreen line
                        'error_msg'  => $error_msg ); 
                     //$vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                     $vtprd_rule->rule_error_red_fields[] = $fieldAttributes['matching_dropdown_label_id'] . '_' .$k ;
                     $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ;   
                     $field_has_an_error = 'yes';                            
                 } //end  if 'selected' 
                 //THIS path exits here  
              } else {  
                //SPECIAL NUMERIC EDITS, PRN                  
                 switch( $dropdown_value ) {
                    case 'quantity':
                    case 'forThePriceOf_Units':                                           //only allow whole numbers
                        if ($vtprd_deal_structure_framework[$fieldName] <= 0) {
                           $vtprd_rule->rule_error_message[] = array( 
                              'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector']. '_' . $k,  //errmsg goes before current onscreen line
                              'error_msg'  => $fieldAttributes['field_label'] .  __(' - when Units are selected, the number must be greater than zero. ', 'vtprd') );
                           $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                           $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ;                         
                           $field_has_an_error = 'yes';
                        } else {
                          $number_of_decimal_places = vtprd_numberOfDecimals( $vtprd_deal_structure_framework[$fieldName] ) ;
                          if ( $number_of_decimal_places > 0 ) {           
                             $vtprd_rule->rule_error_message[] = array( 
                                'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector']. '_' . $k,  //errmsg goes before current onscreen line
                                'error_msg'  => $fieldAttributes['field_label'] .  __(' - when Units are selected, no decimals are allowed. ', 'vtprd') );
                             $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                             $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ; 
                             $field_has_an_error = 'yes';
                          }                        
                        }                            
                      break;
                    case 'forThePriceOf_Currency':  // (only on discount_amt_type)
                        if ( $vtprd_deal_structure_framework[$fieldName] <= 0 ) {           
                           $vtprd_rule->rule_error_message[] = array( 
                              'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector'] . '_' . $k,  //errmsg goes before current onscreen line
                              'error_msg'  => $fieldAttributes['field_label'] .  __(' - when For the Price of (Currency) is selected, the amount must be greater than zero. ', 'vtprd') );
                           $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                           $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ; 
                        } else {
                          $number_of_decimal_places = vtprd_numberOfDecimals( $vtprd_deal_structure_framework[$fieldName] ) ;
                          if ( $number_of_decimal_places > 2 ) {           
                             $vtprd_rule->rule_error_message[] = array( 
                                'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector']. '_' . $k,  //errmsg goes before current onscreen line
                                'error_msg'  => $fieldAttributes['field_label'] .  __(' - when For the Price of (Currency) is selected, up to 2 decimal places <em>only</em>  are allowed. ', 'vtprd') );
                             $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                             $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ; 
                          }
                        }                           
                      break;  
                    case 'currency':
                        if ( $vtprd_deal_structure_framework[$fieldName] <= 0 ) {           
                           $vtprd_rule->rule_error_message[] = array( 
                              'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector'] . '_' . $k,  //errmsg goes before current onscreen line
                              'error_msg'  => $fieldAttributes['field_label'] .  __(' - when Currency is selected, the amount must be greater than zero. ', 'vtprd') );
                           $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                           $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ; 
                           $field_has_an_error = 'yes';
                        } else {
                          $number_of_decimal_places = vtprd_numberOfDecimals( $vtprd_deal_structure_framework[$fieldName] ) ;
                          if ( $number_of_decimal_places > 2 ) {           
                             $vtprd_rule->rule_error_message[] = array( 
                                'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector']. '_' . $k,  //errmsg goes before current onscreen line
                                'error_msg'  => $fieldAttributes['field_label'] .  __(' - when Currency is selected, up to 2 decimal places <em>only</em>  are allowed. ', 'vtprd') );
                             $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                             $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ; 
                             $field_has_an_error = 'yes';
                          }
                        }                             
                      break;
                    case 'fixedPrice':   // (only on discount_amt_type)
                        if ( $vtprd_deal_structure_framework[$fieldName] <= 0 ) {           
                           $vtprd_rule->rule_error_message[] = array( 
                              'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector'] . '_' . $k,  //errmsg goes before current onscreen line
                              'error_msg'  => $fieldAttributes['field_label'] .  __(' - when Fixed Price is selected, the amount must be greater than zero. ', 'vtprd') );
                           $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                           $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ; 
                           $field_has_an_error = 'yes';
                        } else {
                          $number_of_decimal_places = vtprd_numberOfDecimals( $vtprd_deal_structure_framework[$fieldName] ) ;
                          if ( $number_of_decimal_places > 2 ) {           
                             $vtprd_rule->rule_error_message[] = array( 
                                'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector']. '_' . $k,  //errmsg goes before current onscreen line
                                'error_msg'  => $fieldAttributes['field_label'] .  __(' - when Fixed Price is selected, up to 2 decimal places <em>only</em>  are allowed. ', 'vtprd') );
                             $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                             $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ; 
                             $field_has_an_error = 'yes';
                          }                        
                        }                             
                      break;
                    case 'percent':
                        if ( $vtprd_deal_structure_framework[$fieldName] <= 0 ) {           
                           $vtprd_rule->rule_error_message[] = array( 
                              'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector'] . '_' . $k,  //errmsg goes before current onscreen line
                              'error_msg'  => $fieldAttributes['field_label'] .  __(' - when Percent is selected, the amount must be greater than zero. ', 'vtprd') );
                           $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                           $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ; 
                           $field_has_an_error = 'yes';
                        } else {
                          if ( $vtprd_deal_structure_framework[$fieldName] < 1 ) {           
                             $vtprd_rule->rule_error_message[] = array( 
                                'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector'] . '_' . $k,  //errmsg goes before current onscreen line
                                'error_msg'  => $fieldAttributes['field_label'] .  __(' - the Percent value must be greater than 1.  For example 10% would be "10", not ".10" . ', 'vtprd') );
                             $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                             $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ; 
                             $field_has_an_error = 'yes';
                          } 
                         }                                  
                      break;
                    case '':
                      break;
                } //end switch
              } //end amount numeric testing       
            break;
         
         case 'text':
              if ( ($vtprd_deal_structure_framework[$fieldName] <= ' ') && ( $fieldAttributes['required_or_optional'] == 'required' ) ) {  //error possible only if blank                        
                        $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector'] . '_' . $k,  //errmsg goes before current onscreen line
                          'error_msg'  => $fieldAttributes['field_label'] . __(' is required. Please enter a description.', 'vtprd') );
                        $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                        $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ;  
                        $field_has_an_error = 'yes';
              }
            break;
        } //end switch
      }  else {
        //if this field doesn't have an active edit and hence is not allowed, clear it out in the DEAL STRUCTURE.
        $vtprd_deal_structure_framework[$fieldName] = '';
      }

      //*******************************
      //Template-Level and Cross-field edits
      //*******************************      
      //This picks up the template_profile_error_msg if appropriate, 
      //  and if no other error messages already created
      if ($field_has_an_error == 'no') {
        switch( $fieldAttributes['allowed_values'] ) {
            case 'all':    //all values are allowed
              break;
            case '':       //no values are allowed
                if ( ($vtprd_deal_structure_framework[$fieldName] > ' ') && ($fieldAttributes['template_profile_error_msg'] > ' ' ) ) {
                  $field_has_an_error = 'yes';
                  $display_this_msg = $fieldAttributes['template_profile_error_msg'];
                  $insertBefore = $fieldAttributes['insert_error_before_selector'];
                  $this->vtprd_add_cross_field_error_message($insertBefore, $k, $display_this_msg, $fieldName);
                }                      
              break;              
            default:  //$fieldAttributes['allowed_values'] is an array!
                //check for valid values
                if ( !in_array($vtprd_deal_structure_framework[$fieldName], $fieldAttributes['allowed_values']) ) {  
                  $field_has_an_error = 'yes';
                  $display_this_msg = $fieldAttributes['template_profile_error_msg'];
                  $insertBefore = $fieldAttributes['insert_error_before_selector'];
                  $this->vtprd_add_cross_field_error_message($insertBefore, $k, $display_this_msg, $fieldName);
                }
              break;
        }

        //Cross-field edits
        $sizeof_cross_field_edits = sizeof($fieldAttributes['cross_field_edits']);
        if ( ($field_has_an_error == 'no') && ($sizeof_cross_field_edits > 0) ) {
          for ( $c=0; $c < $sizeof_cross_field_edits; $c++) {
              //if current field values fall within value array that the cross-edit applies to
              if ( in_array($vtprd_deal_structure_framework[$fieldName], $fieldAttributes['cross_field_edits'][$c]['applies_to_this_field_values']) ) {               
                 $cross_field_name = $fieldAttributes['cross_field_edits'][$c]['cross_field_name'];
                 if ( !in_array($vtprd_deal_structure_framework[$cross_field_name], $fieldAttributes['cross_field_edits'][$c]['cross_allowed_values']) ) {  
                    //special handling for these 2, as they're not in the standard edit framwork, and we don't have the values yet
                    if ( ($fieldName = 'discount_auto_add_free_product') &&
                        (($cross_field_name == 'popChoiceOut') ||
                         ($cross_field_name == 'cumulativeCouponPricing')) ) {
                        
                        if ($cross_field_name == 'popChoiceOut') {
                          $field_value_temp = $_REQUEST['popChoiceOut'];
                        } else {
                          $field_value_temp = $_REQUEST['cumulativeCouponPricing'];
                        }
                        
                        if ( !in_array($field_value_temp, $fieldAttributes['cross_field_edits'][$c]['cross_allowed_values']) ) { 
                          $field_has_an_error = 'yes';
                          $display_this_msg = $fieldAttributes['cross_field_edits'][$c]['cross_error_msg'];
                          $insertBefore = $fieldAttributes['cross_field_edits'][$c]['cross_field_insertBefore'];
                          $vtprd_rule->rule_error_red_fields[] = '#' . $cross_field_name . '_label_' .$k ;
                          //custom error name
                          //this cross-edit name wasn't being picked up correctly...                    
                          $this->vtprd_add_cross_field_error_message($insertBefore, $k, $display_this_msg, $fieldName);	 
                        } else {
                          
                          if ($cross_field_name == 'popChoiceOut') {
                          
                                
                                //EDITED * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * +             
                                                                                
                          
                          }
                          
                        }
                        
                    } else {
                      //Normal error processing
                      $field_has_an_error = 'yes';
                      $display_this_msg = $fieldAttributes['cross_field_edits'][$c]['cross_error_msg'];
                      $insertBefore = $fieldAttributes['cross_field_edits'][$c]['cross_field_insertBefore'];
                      $vtprd_rule->rule_error_red_fields[] = '#' . $cross_field_name . '_label_' .$k ;
                      //custom error name
                      //this cross-edit name wasn't being picked up correctly...                    
                      $this->vtprd_add_cross_field_error_message($insertBefore, $k, $display_this_msg, $fieldName);	 
                      
                    }

              }
            }
          } //end for cross-edit loop       
        } //END Template-Level and Cross-field edits

      } //end if no-error 
      
       
    }  //end foreach

    return;
  }

  public function vtprd_add_cross_field_error_message($insertBefore, $k, $display_this_msg, $fieldName) { 
    global $vtprd_rule, $vtprd_deal_structure_framework;
    $vtprd_rule->rule_error_message[] = array( 
      'insert_error_before_selector' =>  $insertBefore . '_' . $k,  //errmsg goes before current onscreen line
      'error_msg'  => $display_this_msg 
      );
    //  'error_msg'  => $fieldAttributes['field_label'] . ' ' .$display_this_msg );
    $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;  
  }
  
    
  public function vtprd_dump_deal_lines_to_rule() {  
     global $vtprd_rule, $vtprd_deal_structure_framework;
     $deal_iterations_done = 'no'; //initialize variable

     for($k=0; $deal_iterations_done == 'no'; $k++) {      
       if ( (isset( $_REQUEST['buy_repeat_condition_' . $k] )) && (!empty( $_REQUEST['buy_repeat_condition_' . $k] )) ) {    //is a deal line there? always 1 at least...
         //INITIALIZE was introducing an iteration error!!!!!!!!          
         //$this->vtprd_initialize_deal_structure_framework();       
         foreach( $vtprd_deal_structure_framework as $key => $value ) {   //spin through all of the screen fields  
            $vtprd_deal_structure_framework[$key] = $_REQUEST[$key . '_' .$k];        
         }                 
         $vtprd_rule->rule_deal_info[] = $vtprd_deal_structure_framework;   //add each line to rule, regardless if empty              
       } else {     
         $deal_iterations_done = 'yes';
       }
     }		  
  }
  
  public function vtprd_build_deal_edits_framework() {
    global $vtprd_rule, $vtprd_template_structures_framework, $vtprd_deal_edits_framework;
    
    // previously determined template key
    $templateKey = $vtprd_rule->rule_template; 
    $additional_template_rule_switches = array ( 'discountAppliesWhere' ,  'inPopAllowed' , 'actionPopAllowed'  , 'cumulativeRulePricingAllowed', 'cumulativeSalePricingAllowed', 'replaceSalePricingAllowed', 'cumulativeCouponPricingAllowed') ;
    $nextInActionPop_templates = array ( 'C-discount-Next', 'C-forThePriceOf-Next', 'C-cheapest-Next', 'C-nth-Next' );
 
    foreach( $vtprd_template_structures_framework[$templateKey] as $key => $value ) {            
      //check for addtional template switches first ==> they are stored in this framework for convenience only.
      if ( in_array($key, $additional_template_rule_switches) ) {
        switch( $key ) {
            case 'discountAppliesWhere':               // 'allActionPop' / 'inCurrentInPopOnly'  / 'nextInInPop' / 'nextInActionPop' / 'inActionPop' /
              // if template set to nextInActionPop, check if it should be overwritten...
              //this is a duplicate field load, done here in advance PRN 
              //OVERWRITE discountAppliesWhere TO GUIDE THE APPLY LOGIC AS TO WHICH GROUP WILL BE ACTED UPON 
              $vtprd_rule->actionPop = $_REQUEST['popChoiceOut'];
              if ( (in_array($templateKey, $nextInActionPop_templates))  &&
                   ($vtprd_rule->actionPop == 'sameAsInPop') ) {
                $vtprd_rule->discountAppliesWhere =  'nextInInPop';
              } else {
                $vtprd_rule->discountAppliesWhere = $value;
              }             
            break;
          case 'inPopAllowed':
              $vtprd_rule->inPopAllowed = $value;
            break; 
          case 'actionPopAllowed':
              $vtprd_rule->actionPopAllowed = $value;
            break;            
          case 'cumulativeRulePricingAllowed':
              $vtprd_rule->cumulativeRulePricingAllowed = $value; 
            break;
          case 'cumulativeSalePricingAllowed':
              $vtprd_rule->cumulativeSalePricingAllowed = $value; 
            break;
          case 'replaceSalePricingAllowed':
              $vtprd_rule->replaceSalePricingAllowed = $value; 
            break;            
          case 'cumulativeCouponPricingAllowed':
              $vtprd_rule->cumulativeCouponPricingAllowed = $value; 
            break;
        }
      } else {      
        if ( ($value['required_or_optional'] == 'required') || ($value['required_or_optional'] == 'optional') ) {
          //update required/optional, $key = field name, same relative value across both frameworks...
          $vtprd_deal_edits_framework[$key]['edit_is_active']       = 'yes';
          $vtprd_deal_edits_framework[$key]['required_or_optional'] = $value['required_or_optional'];         
        } else {
          $vtprd_deal_edits_framework[$key]['edit_is_active']       = '';
        }
        
        $vtprd_deal_edits_framework[$key]['allowed_values']  =  $value['allowed_values'];
        $vtprd_deal_edits_framework[$key]['template_profile_error_msg']  =  $value['template_profile_error_msg'];
        
        //cross_field_edits is an array which ***will only exist where required ****
        if ($value['cross_field_edits']) {
           $vtprd_deal_edits_framework[$key]['cross_field_edits']  =  $value['cross_field_edits'];
        }
      }            
    } 
   
    return;
  }  

  /* **********************************
   If no edit errors are present,
      clear out irrelevant/conflicting data 
      left over from setting up the rule
        where conditions were changed
      *************************************** */
  public function vtprd_maybe_clear_extraneous_data() { 
    global $post, $vtprd_rule, $vtprd_rule_template_framework, $vtprd_deal_edits_framework, $vtprd_deal_structure_framework;     
    
    //IF there are edit errors, leave everything as is, exit stage left...
    if ( sizeof($vtprd_rule->rule_error_message ) > 0 ) {  
      return;
    }

    //*************
    //Clear BUY area
    //*************
    if (($vtprd_rule->rule_deal_info[0]['buy_amt_type'] == 'none') ||
        ($vtprd_rule->rule_deal_info[0]['buy_amt_type'] == 'one')) {
       $vtprd_rule->rule_deal_info[0]['buy_amt_count'] = null; 
    }
    
    if ($vtprd_rule->rule_deal_info[0]['buy_amt_mod'] == 'none') {
       $vtprd_rule->rule_deal_info[0]['buy_amt_mod_count'] = null; 
    }  
  
    switch( $vtprd_rule->inPop ) {
      case 'wholeStore':
          //clear vargroup
          $vtprd_rule->inPop_varProdID = null;
          $vtprd_rule->inPop_varProdID_name = null; 
          $vtprd_rule->var_in_checked = array(); 
          $vtprd_rule->inPop_varProdID_parentLit = null; 
          //clear single
          $vtprd_rule->inPop_singleProdID = null; 
          $vtprd_rule->inPop_singleProdID_name = null;
          //clear groups
          $vtprd_rule->prodcat_in_checked = array();
          $vtprd_rule->rulecat_in_checked = array();
          $vtprd_rule->role_in_checked = array();
          $vtprd_rule->role_and_or_in = null;          
        break;
      
       //EDITED * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * +
      
    }  
    
    if ($vtprd_rule->rule_deal_info[0]['buy_repeat_condition'] == 'none') {
       $vtprd_rule->rule_deal_info[0]['buy_repeat_count'] = null; 
    }      
    //End BUY area clear

    //*************
    //Clear GET area
    //*************
    if (($vtprd_rule->rule_deal_info[0]['action_amt_type'] == 'none') ||
        ($vtprd_rule->rule_deal_info[0]['action_amt_type'] == 'zero') ||
        ($vtprd_rule->rule_deal_info[0]['action_amt_type'] == 'one')) {
       $vtprd_rule->rule_deal_info[0]['action_amt_count'] = null; 
    }
    
    if ($vtprd_rule->rule_deal_info[0]['action_amt_mod'] == 'none') {
       $vtprd_rule->rule_deal_info[0]['action_amt_mod_count'] = null; 
    }  
  
    switch( $vtprd_rule->actionPop ) {
      case 'sameAsInPop':
      case 'wholeStore':
          //clear vargroup
          $vtprd_rule->actionPop_varProdID = null;
          $vtprd_rule->actionPop_varProdID_name = null; 
          $vtprd_rule->var_out_checked = array(); 
          $vtprd_rule->actionPop_varProdID_parentLit = null;
         // $vtprd_rule->var_out_product_variations_parameter = array(); 
          //clear single
          $vtprd_rule->actionPop_singleProdID = null; 
          $vtprd_rule->actionPop_singleProdID_name = null;
          //clear groups
          $vtprd_rule->prodcat_out_checked = array();
          $vtprd_rule->rulecat_out_checked = array();
          $vtprd_rule->role_out_checked = array();
          $vtprd_rule->role_and_or_out = null;          
        break;
      
       //EDITED * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * +
       
    }  
    
    if ($vtprd_rule->rule_deal_info[0]['action_repeat_condition'] == 'none') {
       $vtprd_rule->rule_deal_info[0]['action_repeat_count'] = null; 
    }      
    //End GET area clear


    //*************
    //Clear DISCOUNT area        
    //*************
    switch( $vtprd_rule->rule_deal_info[0]['discount_amt_type'] ) {
      case 'percent':
          $vtprd_rule->rule_deal_info[0]['discount_auto_add_free_product'] = null;  
        break;
      case 'currency':
          $vtprd_rule->rule_deal_info[0]['discount_auto_add_free_product'] = null; 
        break;
      case 'fixedPrice':
          $vtprd_rule->rule_deal_info[0]['discount_auto_add_free_product'] = null; 
        break;
      case 'free':
          $vtprd_rule->rule_deal_info[0]['discount_amt_count'] = null;
        break;
      case 'forThePriceOf_Units':
          $vtprd_rule->rule_deal_info[0]['discount_auto_add_free_product'] = null; 
        break;
      case 'forThePriceOf_Currency':
          $vtprd_rule->rule_deal_info[0]['discount_auto_add_free_product'] = null; 
        break;
    }
    //End Discount clear


    //*************
    //Clear MAXIMUM LIMITS area        
    //*************    
    
    if ($vtprd_rule->rule_deal_info[0]['discount_rule_max_amt_type'] == 'none') {
       $vtprd_rule->rule_deal_info[0]['discount_rule_max_amt_count'] = null; 
    } 
    if ($vtprd_rule->rule_deal_info[0]['discount_lifetime_max_amt_type'] == 'none') {
       $vtprd_rule->rule_deal_info[0]['discount_lifetime_max_amt_count'] = null; 
    }     
    if ($vtprd_rule->rule_deal_info[0]['discount_rule_cum_max_amt_type'] == 'none') {
       $vtprd_rule->rule_deal_info[0]['discount_rule_cum_max_amt_count'] = null; 
    } 
    //End Maximum Limits clear        

    return;  
  }


  //*************************
  //Pop Filter Agreement Check (switch used in apply...)
  //*************************
  public function vtprd_maybe_pop_filter_agreement() { 
    global $vtprd_rule;     
  
    if ($vtprd_rule->actionPop  ==  'sameAsInPop' ) {
      $vtprd_rule->set_actionPop_same_as_inPop = 'yes';
      return;
    }
    
    
    if (($vtprd_rule->inPop      ==  'wholeStore') &&
        ($vtprd_rule->actionPop  ==  'wholeStore') ) {
      $vtprd_rule->set_actionPop_same_as_inPop = 'yes';
      return;
    }


    //EDITED * + * +  * + * +  * + * +  * + * + * + * +  * + * +  * + * +  * + * +
     
      
    $vtprd_rule->set_actionPop_same_as_inPop = 'no';
    return;
  }
  


  /* ************************************************
  **   Get single variation data to support discount_auto_add_free_product, Pro Only
  *************************************************** */
  public function vtprd_get_variations_parameter($which_vargroup) {

    global $wpdb, $post, $vtprd_rule, $woocommerce;

    if ($which_vargroup == 'inPop') {
       $product_id    =  $vtprd_rule->inPop_varProdID;
       $variation_id  =  $vtprd_rule->var_in_checked[0];    
    } else {
       $product_id    =  $vtprd_rule->actionPop_varProdID;
       $variation_id  =  $vtprd_rule->var_out_checked[0];     
    }
 
    //************************
    //FROM woocommerce/woocommerce-functions.php  function woocommerce_add_to_cart_action
    //************************
    
	  $adding_to_cart      = get_product( $product_id );

  	$all_variations_set = true;
  	$variations         = array();

		$attributes = $adding_to_cart->get_attributes();
		$variation  = get_product( $variation_id );

		// Verify all attributes
		foreach ( $attributes as $attribute ) {
      if ( ! $attribute['is_variation'] )
      	continue;

      $taxonomy = 'attribute_' . sanitize_title( $attribute['name'] );


          // Get value from post data
          // Don't use woocommerce_clean as it destroys sanitized characters
         // $value = sanitize_title( trim( stripslashes( $_REQUEST[ $taxonomy ] ) ) );
          $value = $variation->variation_data[ $taxonomy ];

          // Get valid value from variation
          $valid_value = $variation->variation_data[ $taxonomy ];
          // Allow if valid
          if ( $valid_value == '' || $valid_value == $value ) {
            if ( $attribute['is_taxonomy'] )
            	$variations[ esc_html( $attribute['name'] ) ] = $value;
            else {
              // For custom attributes, get the name from the slug
              $options = array_map( 'trim', explode( '|', $attribute['value'] ) );
              foreach ( $options as $option ) {
              	if ( sanitize_title( $option ) == $value ) {
              		$value = $option;
              		break;
              	}
              }
               $variations[ esc_html( $attribute['name'] ) ] = $value;
            }
            continue;
        }

    }


    $product_variations_array = array(
       'parent_product_id'    => $product_id,
       'variation_product_id' => $variation_id,
       'variations_array'     => $variations
      );   
    

    return ($product_variations_array);
  } 
  
       
  
} //end class