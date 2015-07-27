<?php
 /*
   Rule CPT rows are stored.  At rule store/update
   time, a master rule option array is (re)created, to allow speedier access to rule information at
   product/cart processing time.
 */
   
class VTPRD_Rules_UI{ 
	
	public function __construct(){       
    global $post, $vtprd_info;
    
    //ACTION TO ALLOW THEME TO OFFER ALL PRODUCTS AT A DISCOUNT.....
    
        
    add_action( 'add_meta_boxes_vtprd-rule', array(&$this, 'vtprd_remove_meta_boxes') );   
    add_action( 'add_meta_boxes_vtprd-rule', array(&$this, 'vtprd_add_metaboxes') );
    add_action( "admin_enqueue_scripts",     array(&$this, 'vtprd_enqueue_admin_scripts') );

    add_action( 'add_meta_boxes_vtprd-rule', array($this, 'vtprd_remove_all_in_one_seo_aiosp') ); 
    
    
    //AJAX actions
    //   uses the action name from the js....
    add_action( 'wp_ajax_vtprd_ajax_load_variations_in',         array(&$this, 'vtprd_ajax_load_variations_in') ); 
    add_action( 'wp_ajax_vtprd_ajax_load_variations_out',        array(&$this, 'vtprd_ajax_load_variations_out') );
    add_action( 'wp_ajax_noprov_vtprd_ajax_load_variations_in',  array(&$this, 'vtprd_ajax_load_variations_in') );      
    add_action( 'wp_ajax_noprov_vtprd_ajax_load_variations_out', array(&$this, 'vtprd_ajax_load_variations_out') );         
          
    add_action( 'post_submitbox_misc_actions', array( $this, 'vtprd_product_data_visibility' ) ); //v1.1.0.7 
      
    //add a metabox to the **parent product custom post type page**
    //v1.0.7.1 begin  ==>> all in one seo conflicts with this box, don't show when that plugin is active
    if ( ! defined( 'AIOSEOP_VERSION' ) ) {
      add_action( 'add_meta_boxes_' .$vtprd_info['parent_plugin_cpt'] , array(&$this, 'vtprd_parent_product_meta_box_cntl') );
    }
    // v1.0.7.1 end
	}
                               
    
  public function vtprd_enqueue_admin_scripts() {
    global $post_type, $vtprd_info;
    if( get_post_type() == 'vtprd-rule' ){         //v1.0.8.2   can't just test $post_type here, not always accurate!
        
        //QTip Resources
        wp_register_style ('vtprd-qtip-style', VTPRD_URL.'/admin/css/vtprd.qtip.min.css' );  
        wp_enqueue_style  ('vtprd-qtip-style'); 
       
       //qtip resources named jquery-qtip, to agree with same name used in wordpress-seo from yoast!
        wp_register_script('jquery-qtip', VTPRD_URL.'/admin/js/vtprd.qtip.min.js' );  
        wp_enqueue_script ('jquery-qtip', array('jquery'), false, true);

        wp_register_style ('vtprd-admin-style', VTPRD_URL.'/admin/css/vtprd-admin-style-' .VTPRD_ADMIN_CSS_FILE_VERSION. '.css' );  //v1.1.0.7
        wp_enqueue_style  ('vtprd-admin-style');
        
        wp_register_script('vtprd-admin-script', VTPRD_URL.'/admin/js/vtprd-admin-script-' .VTPRD_ADMIN_JS_FILE_VERSION. '.js' );  //v1.1
        //create ajax resource
        wp_localize_script('vtprd-admin-script', 'variationsInAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )  ));        
        //create ajax resource
        wp_localize_script('vtprd-admin-script', 'variationsOutAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )  ));                
        wp_enqueue_script ('vtprd-admin-script', array('jquery', 'vtprd-qtip-js'), false, true);

      
        //Datepicker resources, some part of WP
        wp_register_style ('vtprd-jquery-datepicker-style', VTPRD_URL.'/admin/css/smoothness/jquery-ui-1.10.2.custom.css' );  
        wp_enqueue_style  ('vtprd-jquery-datepicker-style');
        wp_enqueue_script ('jquery-ui-core', array('jquery'), false, true );
        wp_enqueue_script ('jquery-ui-datepicker', array('jquery'), false, true );

        if(defined('VTPRD_PRO_DIRNAME')) {
            wp_register_style ('vtprd-admin-style2', VTPRD_PRO_URL.'/admin/css/vtprd-admin-style2.css' );  
            wp_enqueue_style  ('vtprd-admin-style2');
        }
       
      }
    //These are for the include/exclude meta box on the parent plugin PRODUCT page
    if( $post_type == $vtprd_info['parent_plugin_cpt']){
      wp_register_style('vtprd-admin-product-metabox-style', VTPRD_URL.'/admin/css/vtprd-admin-product-metabox-style.css' );  
      wp_enqueue_style( 'vtprd-admin-product-metabox-style');
      if (defined('VTPRD_PRO_DIRNAME'))  {
        $register_metabox_script = VTPRD_PRO_URL.'/admin/js/vtprd-admin-product-metabox-script.js';
      } else {
        $register_metabox_script = VTPRD_URL.'/admin/js/vtprd-admin-product-metabox-script.js';
      }     
      wp_register_script('vtprd-admin-product-metabox-script', $register_metabox_script );
      wp_enqueue_script('vtprd-admin-product-metabox-script', array('jquery'), false, true);    
    }
  }    
  
  public function vtprd_remove_meta_boxes() {
     if(!current_user_can('administrator')) {  
      	remove_meta_box( 'revisionsdiv', 'post', 'normal' ); // Revisions meta box
        remove_meta_box( 'commentsdiv', 'vtprd-rule', 'normal' ); // Comments meta box
      	remove_meta_box( 'authordiv', 'vtprd-rule', 'normal' ); // Author meta box
      	remove_meta_box( 'slugdiv', 'vtprd-rule', 'normal' );	// Slug meta box        	
      	remove_meta_box( 'postexcerpt', 'vtprd-rule', 'normal' ); // Excerpt meta box
      	remove_meta_box( 'formatdiv', 'vtprd-rule', 'normal' ); // Post format meta box
      	remove_meta_box( 'trackbacksdiv', 'vtprd-rule', 'normal' ); // Trackbacks meta box
      	remove_meta_box( 'postcustom', 'vtprd-rule', 'normal' ); // Custom fields meta box
      	remove_meta_box( 'commentstatusdiv', 'vtprd-rule', 'normal' ); // Comment status meta box
      	remove_meta_box( 'postimagediv', 'vtprd-rule', 'side' ); // Featured image meta box
      	remove_meta_box( 'pageparentdiv', 'vtprd-rule', 'side' ); // Page attributes meta box
        remove_meta_box( 'categorydiv', 'vtprd-rule', 'side' ); // Category meta box
        remove_meta_box( 'tagsdiv-post_tag', 'vtprd-rule', 'side' ); // Post tags meta box
        remove_meta_box( 'tagsdiv-vtprd_rule_category', 'vtprd-rule', 'side' ); // vtprd_rule_category tags  
        remove_meta_box( 'relateddiv', 'vtprd-rule', 'side');                  
      } 
 
  }
         
  //v1.1.0.7  New Function - 
  //    add wholesale Product tickbox in PUBLISH metabox for Parent Product
  public  function vtprd_product_data_visibility() {
      global $post, $vtprd_info, $vtprd_rule, $vtprd_rules_set;        

      //only do this for PRODUCT
      if( get_post_type() != $vtprd_info['parent_plugin_cpt'] ){  
        return;
      } 
      
      $current_visibility = get_post_meta( $post->ID, 'vtprd_wholesale_visibility', true );
      
      ?> 
      &nbsp; &nbsp; 
      <span id="vtprd-wholesale">
      <label class="selectit vtprd-wholesale-visibility-label">
        <input id="vtprd-wholesale-visibility" class="vtprd-wholesale-visibility-class" name="vtprd-wholesale-visibility" value="yes" <?php if ($current_visibility == 'yes'){echo ' checked="checked" ';} ?>  type="checkbox">
        <strong>&nbsp; <?php _e('Wholesale Product', 'vtprd') ?></strong>
      </label>
      </span>
      <?php 
      
      return;
  }
          
  public  function vtprd_add_metaboxes() {
      global $post, $vtprd_info, $vtprd_rule, $vtprd_rules_set;        

      $found_rule = false;                            
      if ($post->ID > ' ' ) {
        $post_id =  $post->ID;
        $vtprd_rules_set   = get_option( 'vtprd_rules_set' ) ;

        $sizeof_rules_set = sizeof($vtprd_rules_set);
        for($i=0; $i < $sizeof_rules_set; $i++) {  
           if ($vtprd_rules_set[$i]->post_id == $post_id) {
              $vtprd_rule = $vtprd_rules_set[$i];  //load vtprd-rule               
              $found_rule = true;
              $found_rule_index = $i; 
              $i = $sizeof_rules_set;              
           }
        }
      } 

      if (!$found_rule) {
        $this->vtprd_build_new_rule();        
      } 
         
      add_meta_box('vtprd-deal-selection',  __('Pricing Deals', 'vtprd') , array(&$this, 'vtprd_deal'), 'vtprd-rule', 'normal', 'high');

      //side boxes
//      add_meta_box('vtprd-rule-id', __('Rule In Words', 'vtprd'), array(&$this, 'vtprd_rule_id'), 'vtprd-rule', 'side', 'low'); //low = below Publish box
//      add_meta_box('vtprd-rule-resources', __('Resources', 'vtprd'), array(&$this, 'vtprd_rule_resources'), 'vtprd-rule', 'side', 'low'); //low = below Publish box 

      //create help tab...                                                                                                                                                                                                          
      $content;
      $content .= '<br><a id="pricing-deal-title-more2" class="more-anchor" href="javascript:void(0);"><img class="pricing-deal-title-helpPng" alt="help"  width="14" height="14" src="' . VTPRD_URL .  '/admin/images/help.png" />' .    __(' Help! ', 'vtprd')  .'&nbsp;'.   __('Tell me about Pricing Deals ', 'vtprd') . '<img class="plus-button" alt="help" height="10px" width="10px" src="' . VTPRD_URL . '/admin/images/plus-toggle2.png" /></a>';            
      $content .= '    <a id="pricing-deal-title-less2" class="more-anchor less-anchor" href="javascript:void(0);"><img class="pricing-deal-title-helpPng" alt="help" width="14" height="14" src="' . VTPRD_URL . '/admin/images/help.png" />' . __('   Less Pricing Deals Help ... ', 'vtprd') . '<img class="minus-button" alt="help" height="12px" width="12px" src="' . VTPRD_URL . '/admin/images/minus-toggle2.png" /></a>';   
      
      $screen = get_current_screen();
      $screen->add_help_tab( array( 
         'id' => 'vtprd-help',            //unique id for the tab
         'title' => 'Pricing Deals Help',      //unique visible title for the tab
         'content' => $content  //actual help text
        ) );  
  }                   
   
                                                    
  public function vtprd_error_messages() {     
      global $post, $vtprd_rule;

      $error_msg_count = sizeof($vtprd_rule->rule_error_message);
       ?>        
          <script type="text/javascript">
          jQuery(document).ready(function($) {           
          $('<div class="vtprd-error" id="vtprd-error-announcement"><?php _e("Please Repair Errors below", "vtprd"); ?></div>').insertBefore('#vtprd-deal-selection');  
      <?php 
      //loop through all of the error messages 
      //          $vtmax_info['line_cnt'] is used when table formattted msgs come through.  Otherwise produces an inactive css id. 
     for($i=0; $i < $error_msg_count; $i++) { 
       ?>
             $('<div class="vtprd-error"><?php echo $vtprd_rule->rule_error_message[$i]['error_msg'];?></div>').insertBefore('<?php echo $vtprd_rule->rule_error_message[$i]['insert_error_before_selector']; ?>');
      <?php 
  
      }  //end 'for' loop      
      ?>   
            });   
          </script>
     <?php 
     
     //Change the label color to red for fields in error
     if ( sizeof($vtprd_rule->rule_error_red_fields) > 0 )  {
      
       echo '<style>' ;   // echo '<style type="text/css">' ;
       
       for($i=0; $i < sizeof($vtprd_rule->rule_error_red_fields); $i++) { 
          if ($i > 0) { // if 2nd to n field name, put comma before the name...
            echo ', ';
          }
          echo $vtprd_rule->rule_error_red_fields[$i];
       }
       echo '{color:red !important; display:block;}' ;         // display:block added for hidden date err msg fields
       
       for($i=0; $i < sizeof($vtprd_rule->rule_error_box_fields); $i++) { 
          if ($i > 0) { // if 2nd to n field name, put comma before the name...
            echo ', ';
          }
          echo $vtprd_rule->rule_error_box_fields[$i];
       }
       echo '{border-color:red !important; display:block;}' ;         // display:block added for hidden date err msg fields
              
       echo '</style>' ;
     }

      
      if( $post->post_status == 'publish') { //if post status not = pending, make it so  
          $post_id = $post->ID;
          global $wpdb;
          $wpdb->update( $wpdb->posts, array( 'post_status' => 'pending' ), array( 'ID' => $post_id ) );
      } 

  }   

/* **************************************************************
    Deal Selection Metabox
                                                                                     
    Includes: 
    - Rule type info
    - Rule deal info
    - applies-to max info
    - rule catalog/cart display msgs
    - cumulative logic rule switches
************************************************************** */                                                   
  public function vtprd_deal() {     
      global $vtprd_rule_template_framework, $vtprd_deal_structure_framework, $vtprd_deal_screen_framework, $vtprd_rule_display_framework, $vtprd_rule, $vtprd_info, $vtprd_setup_options;
      $selected = 'selected="selected"';
      $checked = 'checked="checked"';
      $disabled = 'disabled="disabled"' ; 
      $vtprdNonce = wp_create_nonce("vtprd-rule-nonce"); //nonce verified in vt-pricing-deals.php
                
      if ( sizeof($vtprd_rule->rule_error_message ) > 0 ) {    //these error messages are from the last upd action attempt, coming from vtprd-rules-update.php
           $this->vtprd_error_messages();
      } 
    
      $currency_symbol = vtprd_get_currency_symbol();
      
      //v1.1.0.8 begin ==>>  init messages with default value, if blank (cleared out in rules_update )
      if ($vtprd_rule->discount_product_short_msg <= ' ') {
        $vtprd_rule->discount_product_short_msg = $vtprd_info['default_short_msg']; 
      }
      if ($vtprd_rule->discount_product_full_msg <= ' ') {
        $vtprd_rule->discount_product_full_msg = $vtprd_info['default_full_msg']; 
      }
      if ($vtprd_rule->only_for_this_coupon_name <= ' ') {
        $vtprd_rule->only_for_this_coupon_name = $vtprd_info['default_coupon_msg']; 
      }
      //v1.1.0.8 end
           
      //**********************************************************************
      //IE CSS OVERRIDES, done here to ensure they're last in line...
      //**********************************************************************
      echo '<!--[if IE]>';
	    echo '<link rel="stylesheet" type="text/css"  media="all" href="' .VTPRD_URL.'/admin/css/vtprd-admin-style-ie.css" />';
      echo '<![endif]-->';
      // end override
       
      //This Div only shows if there is a JS error in the customer implementation of the plugin, as the JS hides this div, if the JS is active
      //vtprd_show_help_if_js_is_broken();  
      
      ?>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
        
        //Spinner gif wasn't working... 
         $('.spinner').append('<img src="<?php echo VTPRD_URL;?>/admin/images/indicator.gif" />');
       
          });   
        </script>
     
       <?php /*
       <div class="hide-by-jquery">
        <span class="">< ?php _e('If you can see this, there is a JavaScript Error on the Page. Hover over this &rarr;', 'vtprd'); ? > </span>
            < ?php vtprd_show_help_tooltip($context = 'onlyShowsIfJSerror', $location = 'title'); ? >
       </div>
       */
       ?>

    <?php //BANNER AND BUTTON AREA ?>
                         

    
    <img id="pricing-deals-img-preload" alt="" src="<?php echo VTPRD_URL;?>/admin/images/upgrade-bkgrnd-banner.jpg" />
 		<div id="upgrade-title-area">
      <a  href=" <?php echo VTPRD_PURCHASE_PRO_VERSION_BY_PARENT ; ?> "  title="Purchase Pro">
      <img id="pricing-deals-img" alt="help" height="40px" width="40px" src="<?php echo VTPRD_URL;?>/admin/images/sale-circle.png" />
      </a>      
      <h2>
        <?php _e('Pricing Deals', 'vtprd'); ?>
        <?php if(defined('VTPRD_PRO_DIRNAME')) {  
                _e(' Pro', 'vtprd');
              }
        ?>    
        
        </h2>  
      
      <?php if(!defined('VTPRD_PRO_DIRNAME')) {  ?> 
          <span class="group-power-msg">
            <strong><em><?php _e('Create rules for Any Group you can think of, and More!', 'vtprd'); ?></em></strong>
            <?php /* 
              - Product Category
              - Pricing Deal Custom Category
              - Logged-in Status
              - Product
              - Variations!
                */ ?> 
          </span> 
          <span class="buy-button-area">
            <a href="<?php echo VTPRD_PURCHASE_PRO_VERSION_BY_PARENT; ?>" class="help tooltip tooltipWide buy-button">
                <span class="buy-button-label"><?php _e('Get Pricing Deals Pro', 'vtprd'); ?></span>
                <b> <?php vtprd_show_help_tooltip_text('upgradeToPro'); ?> </b>
            </a>
          </span> 
      <?php }  ?>
          
    </div>  

            
      <?php //RULE EXECUTION TYPE ?> 
      <div class="display-virtual_box  top-box">                           
        
        <?php //************************ ?>
        <?php //HIDDEN FIELDS BEGIN ?>
        <?php //************************ ?>
        <?php //RULE EXECUTION blue-dropdownS - only one actually displays at a time, depending on ?>
        <input type="hidden" id="vtprd_nonce" name="vtprd_nonce" value="<?php echo $vtprdNonce; ?>" />
        <?php //Hidden switch to communicate with the JS that the data is 1st time screenful ?>
        <input type="hidden" id="firstTimeBackFromServer" name="firstTimeBackFromServer" value="yes" />        
        <input type="hidden" id="upperSelectsFirstTime" name="upperSelectsFirstTime" value="yes" />
        <input type="hidden" id="upperSelectsDoneSw" name="upperSelectsDoneSw" value="" />
        <input type="hidden" id="catalogCheckoutMsg" name="catalogCheckoutMsg" value="<?php echo __('Message unused for Catalog Discount', 'vtprd');?>" />
        <input type="hidden" id="vtprd-moreInfo" name="vtprd-docTitle" value="<?php _e('More Info', 'vtprd');?>" /> <?php //v1.0.5 added 2nd button ?>
        <input type="hidden" id="vtprd-docTitle" name="vtprd-docTitle" value="<?php _e('- Help! -', 'vtprd');?>" />         
        <?php 
           /*
            Assign a numeric value to the switch
              showing HOW MANY selects have data
                on 1st return from server...
           */           
           $data_sw = '0';
           
           //test the Various group filter selects and set a value...
           switch( true) {
              case ( ($vtprd_rule->get_group_filter_select > ' ') &&
                     ($vtprd_rule->get_group_filter_select != 'choose') ):
                  $data_sw = '5';
                break;
              case ( ($vtprd_rule->buy_group_filter_select > ' ') &&
                     ($vtprd_rule->buy_group_filter_select != 'choose') ):
                  $data_sw = '4';
                break;  
              case ( ($vtprd_rule->minimum_purchase_select > ' ') &&
                     ($vtprd_rule->minimum_purchase_select != 'choose') ):              
                  $data_sw = '3';
                break;   
              case ( ($vtprd_rule->pricing_type_select > ' ') &&
                     ($vtprd_rule->pricing_type_select != 'choose') ):
                  $data_sw = '2';
                break;   
              case ( ($vtprd_rule->cart_or_catalog_select > ' ') &&
                     ($vtprd_rule->cart_or_catalog_select != 'choose') ):              
                  $data_sw = '1';
                break;                    
             } 
             
             /*  upperSelectsHaveDataFirstTime has values from 0 => 5
             value = 0  no previous data saved 
             value = 1  last run got to:  cart_or_catalog_select
             value = 2  last run got to:  pricing_type_select
             value = 3  last run got to:  minimum_purchase_select
             value = 4  last run got to:  buy_group_filter_select
             value = 5  last run got to:  get_group_filter_select
             */
        ?>
        <input type="hidden" id="upperSelectsHaveDataFirstTime" name="upperSelectsHaveDataFirstTime" value="<?php echo $data_sw; ?>" />
        
        <input type="hidden" id="templateChanged" name="templateChanged" value="no" /> 
        
        <?php //Statuses used for switching of the upper dropdowns ?>
        <input type="hidden" id="select_status_sw"  name="select_status_sw"  value="no" />
        <input type="hidden" id="chg_detected_sw"  name="chg_detected_sw"    value="no" />   <?php //v1.0.7.6 ?>
        
        <?php //pass these two messages up to JS, translated here if necessary ?>
        <input type="hidden" id="fullMsg" name="fullMsg" value="<?php echo $vtprd_info['default_full_msg'];?>" />    
        <input type="hidden" id="shortMsg" name="shortMsg" value="<?php echo $vtprd_info['default_short_msg'];?>" />
        <input type="hidden" id="couponMsg" name="couponMsg" value="<?php echo $vtprd_info['default_coupon_msg'];?>" />   <?php //v1.1.0.8  ?>
  
        <input id="pluginVersion" type="hidden" value="<?php if(defined('VTPRD_PRO_DIRNAME')) { echo "proVersion"; } else { echo "freeVersion"; } ?>" name="pluginVersion" />  
        <input id="rule_template_framework" type="hidden" value="<?php echo $vtprd_rule->rule_template;  ?>" name="rule_template_framework" />
              
           
        <?php //************************ ?>
        <?php //HIDDEN FIELDS END ?>
        <?php //************************ ?>

        <div class="template-area clear-left">  
          
          <div class="clear-left" id="blue-area-title-line"> 
              <img id="blue-area-title-icon" src="<?php echo VTPRD_URL;?>/admin/images/tab-icons.png" width="1" height="1" />
              <span class="section-headings column-width2" id="blue-area-title">  <?php _e('Blueprint', 'vtprd');?></span>             
          </div>
          
          <div class="clear-left" id="first-blue-line">                          
                                                                             
              <span class="left-column  left-column-nothing-on-top">                              
                 <?php //mwn20140414 added id ?>
                 <label id="cart-or-catalog-select-label" class="hasWizardHelpRight"  for="<?php echo $vtprd_rule_display_framework['cart_or_catalog_select']['label']['for'];?>"><?php echo $vtprd_rule_display_framework['cart_or_catalog_select']['label']['title'];?></label>  
                 <?php vtprd_show_object_hover_help ('cart_or_catalog_select', 'wizard') ?> 
              </span>
              <span class="blue-dropdown  right-column" id="cart-or-catalog-select-area">
                 <select id="<?php echo $vtprd_rule_display_framework['cart_or_catalog_select']['select']['id'];?>" class="<?php echo$vtprd_rule_display_framework['cart_or_catalog_select']['select']['class']; ?>" name="<?php echo $vtprd_rule_display_framework['cart_or_catalog_select']['select']['name'];?>" tabindex="<?php echo $vtprd_rule_display_framework['cart_or_catalog_select']['select']['tabindex']; ?>" >          
                   <?php
                   for($i=0; $i < sizeof($vtprd_rule_display_framework['cart_or_catalog_select']['option']); $i++) { 
                   ?>                             
                      <option id="<?php echo $vtprd_rule_display_framework['cart_or_catalog_select']['option'][$i]['id']; ?>"  class="<?php echo $vtprd_rule_display_framework['cart_or_catalog_select']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_rule_display_framework['cart_or_catalog_select']['option'][$i]['value']; ?>"   <?php if ($vtprd_rule_display_framework['cart_or_catalog_select']['option'][$i]['value'] == $vtprd_rule->cart_or_catalog_select )  { echo $selected; } ?> >  <?php echo $vtprd_rule_display_framework['cart_or_catalog_select']['option'][$i]['title']; ?> </option>
                   <?php } ?> 
                 </select> 
                  <span class="shortIntro  shortIntro2"  id="buy_group_filter_comment">
                    <em><?php _e('Where is the discount', 'vtprd');?></em>
                    <br>
                    <em><?php _e('taken first?', 'vtprd');?></em>
                    &nbsp;
                    <img class="hasHoverHelp2" width="11px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" />
                    <?php vtprd_show_object_hover_help ('cart_or_catalog_select', 'small') ?>
                  </span>                                        
              </span>
       
          </div> <?php //end blue-line ?>  
            
          <div class="blue-line  clear-left">                                  
               <span class="left-column  left-column-less-padding-top3">                              
                 <?php //mwn20140414 added id ?>
                 <label id="pricing-type-select-label" class="hasWizardHelpRight"   for="<?php echo $vtprd_rule_display_framework['pricing_type_select']['label']['for'];?>"><?php echo $vtprd_rule_display_framework['pricing_type_select']['label']['title'];?></label>
                 <?php vtprd_show_object_hover_help ('pricing_type_select', 'wizard') ?> 
               </span>
               <span class="blue-dropdown  right-column" id="pricing-type-select-area">   
                 <select id="<?php echo $vtprd_rule_display_framework['pricing_type_select']['select']['id'];?>" class="<?php echo$vtprd_rule_display_framework['pricing_type_select']['select']['class']; ?>  " name="<?php echo $vtprd_rule_display_framework['pricing_type_select']['select']['name'];?>" tabindex="<?php echo $vtprd_rule_display_framework['pricing_type_select']['select']['tabindex']; ?>" >          
                   <?php
                   for($i=0; $i < sizeof($vtprd_rule_display_framework['pricing_type_select']['option']); $i++) { 
                   ?>                             
                      <option id="<?php echo $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['id']; ?>"  class="<?php echo $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['value']; ?>"   <?php if ($vtprd_rule_display_framework['pricing_type_select']['option'][$i]['value'] == $vtprd_rule->pricing_type_select )  { echo $selected; } ?> >  <?php echo $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['title']; ?> </option>
                   <?php } ?> 
                 </select>  
                  <span class="shortIntro  shortIntro2"  id="buy_group_filter_comment">
                      <span class="">
                          <em><?php _e("What kind of Deal is it?", 'vtprd');?></em>
                          &nbsp;
                          <img  class="hasHoverHelp2" width="11px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" />  
                          <?php vtprd_show_object_hover_help ('pricing_type_select', 'small') ?>
                      </span>                   
                      <br>
                      <a class="commentURL" target="_blank" href="http://www.varktech.com/documentation/pricing-deals/examples"><?php _e('Deal Examples', 'vtprd');?></a>                
                  </span>                                                          
               </span> 
          </div> <?php //end blue-line ?>
              
          <div class="blue-line  clear-left">  
               <span class="left-column  left-column-less-padding-top3">                                            
                 <?php //mwn20140414 added id ?>
                 <label id="minimum-purchase-select-label" class="hasWizardHelpRight" for="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['label']['for'];?>"><?php echo $vtprd_rule_display_framework['minimum_purchase_select']['label']['title'];?></label>
                 <?php vtprd_show_object_hover_help ('minimum_purchase_select', 'wizard') ?> 
               </span>
               <span class="blue-dropdown  blue-dropdown-minimum  right-column" id="minimum-purchase-select-area">  
                 <select id="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['select']['id'];?>" class="<?php echo$vtprd_rule_display_framework['minimum_purchase_select']['select']['class']; ?>  " name="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['select']['name'];?>" tabindex="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['select']['tabindex']; ?>" >          
                   <?php
                   for($i=0; $i < sizeof($vtprd_rule_display_framework['minimum_purchase_select']['option']); $i++) { 
                   ?>                             
                      <option id="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['option'][$i]['id']; ?>"  class="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['option'][$i]['value']; ?>"   <?php if ($vtprd_rule_display_framework['minimum_purchase_select']['option'][$i]['value'] == $vtprd_rule->minimum_purchase_select )  { echo $selected; } ?> >  <?php echo $vtprd_rule_display_framework['minimum_purchase_select']['option'][$i]['title']; ?> </option>
                   <?php } ?> 
                 </select>
                  <span class="shortIntro  shortIntro2"  id="buy_group_filter_comment">
                    <em>
                    <?php _e('Buy this,', 'vtprd');?>
                    <br>
                    <?php _e('Discount this?', 'vtprd');?>
                    </em>
                    &nbsp;
                    <img  class="hasHoverHelp2" width="11px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" />                
                    <?php vtprd_show_object_hover_help ('minimum_purchase_select', 'small') ?>
                  </span>                                          
              </span>         
          </div> <?php //end blue-line ?>  
              
          <div class="blue-line  blue-line-less-top  clear-left">
              <span class="left-column">                                                      
                <label class="scheduling-label hasWizardHelpRight" id="scheduling-label-item"><?php _e('Deal Schedule', 'vtprd');?></label>   
                <?php vtprd_show_object_hover_help ('scheduling', 'wizard') ?>
              </span>
              <span class="blue-dropdown  scheduling-group  right-column" id="scheduling-area">   
                <span class="date-line" id='date-line-0'>                               
                <?php //   <label class="scheduling-label">Scheduling</label> ?>                                              
                    <span class="date-line-area">  
                      <?php  $this->vtprd_rule_scheduling(); ?> 
                    </span> 
                    <span class="on-off-switch">                              
                    <?php //     <label for="rule-state-select">On/Off Switch</label>  ?> 
                       <select id="<?php echo $vtprd_rule_display_framework['rule_on_off_sw_select']['select']['id'];?>" class="<?php echo$vtprd_rule_display_framework['rule_on_off_sw_select']['select']['class']; ?>" name="<?php echo $vtprd_rule_display_framework['rule_on_off_sw_select']['select']['name'];?>" tabindex="<?php echo $vtprd_rule_display_framework['rule_on_off_sw_select']['select']['tabindex']; ?>" >          
                         <?php
                         for($i=0; $i < sizeof($vtprd_rule_display_framework['rule_on_off_sw_select']['option']); $i++) { 
                         ?>                             
                            <option id="<?php echo $vtprd_rule_display_framework['rule_on_off_sw_select']['option'][$i]['id']; ?>"  class="<?php echo $vtprd_rule_display_framework['rule_on_off_sw_select']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_rule_display_framework['rule_on_off_sw_select']['option'][$i]['value']; ?>"   <?php if ($vtprd_rule_display_framework['rule_on_off_sw_select']['option'][$i]['value'] == $vtprd_rule->rule_on_off_sw_select )  { echo $selected; } ?> >  <?php echo $vtprd_rule_display_framework['rule_on_off_sw_select']['option'][$i]['title']; ?> </option>
                         <?php } ?> 
                       </select>                        
                    </span>                                
                </span> 
                   

                  <span class="shortIntro"  id="deal_schedule_comment">
                    <em>
                    <?php _e('Active When?', 'vtprd');?>
                    <br>
                    <?php _e('On or Off?', 'vtprd');?>
                    </em>
                    &nbsp;
                    <img  class="hasHoverHelp2" width="11px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                    <?php vtprd_show_object_hover_help ('scheduling', 'small') ?>
                  </span>                                                      
              </span>      
          </div> <?php //end blue-line ?>

          <div class="blue-line  clear-left">
              <span class="left-column">                                                      
                &nbsp;
              </span>
              <span class="right-column">       

                  <span class="blue-dropdown  rule-type" id="rule-type-select-area"> 
                      <label class="rule-type-label  hasWizardHelpRight"><?php _e('Show Me', 'vtprd');?></label> 
                      <?php vtprd_show_object_hover_help ('rule-type-select', 'wizard') ?>
                      <span id="rule-type-info" class="clear-left">                    
                        <?php
                         for($i=0; $i < sizeof($vtprd_rule_display_framework['rule-type-select']); $i++) { 
                         ?>                               
                            <input id="<?php echo $vtprd_rule_display_framework['rule-type-select'][$i]['id']; ?>" class="<?php echo $vtprd_rule_display_framework['rule-type-select'][$i]['class']; ?>" type="<?php echo $vtprd_rule_display_framework['rule-type-select'][$i]['type']; ?>" name="<?php echo $vtprd_rule_display_framework['rule-type-select'][$i]['name']; ?>" value="<?php echo $vtprd_rule_display_framework['rule-type-select'][$i]['value']; ?>" <?php if ( $vtprd_rule_display_framework['rule-type-select'][$i]['value'] == $vtprd_rule->rule_type_select) { echo $checked; } ?>    /><span id="<?php echo $vtprd_rule_display_framework['rule-type-select'][$i]['id'] . '-label'; ?>"> <?php echo $vtprd_rule_display_framework['rule-type-select'][$i]['label']; ?></span> 
                        <?php } ?>                    
                      </span>
                                        
                  </span>
                   <span class="blue-dropdown wizard-type" id="wizard-select-area"> 
                      <label class="wizard-type-label"><?php _e('Hover Help', 'vtprd');?></label> 
                      
                         <select id="<?php echo $vtprd_rule_display_framework['wizard_on_off_sw_select']['select']['id'];?>" class="<?php echo$vtprd_rule_display_framework['wizard_on_off_sw_select']['select']['class']; ?>  hasHoverHelp2" name="<?php echo $vtprd_rule_display_framework['wizard_on_off_sw_select']['select']['name'];?>" tabindex="<?php echo $vtprd_rule_display_framework['wizard_on_off_sw_select']['select']['tabindex']; ?>" >          
                           <?php
                           for($i=0; $i < sizeof($vtprd_rule_display_framework['wizard_on_off_sw_select']['option']); $i++) { 
                           ?>                             
                              <option id="<?php echo $vtprd_rule_display_framework['wizard_on_off_sw_select']['option'][$i]['id']; ?>"  class="<?php echo $vtprd_rule_display_framework['wizard_on_off_sw_select']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_rule_display_framework['wizard_on_off_sw_select']['option'][$i]['value']; ?>"   <?php if ($vtprd_rule_display_framework['wizard_on_off_sw_select']['option'][$i]['value'] == $vtprd_rule->wizard_on_off_sw_select )  { echo $selected; } ?> >  <?php echo $vtprd_rule_display_framework['wizard_on_off_sw_select']['option'][$i]['title']; ?> </option>
                           <?php } ?> 
                         </select> 
                         <?php vtprd_show_object_hover_help ('hover-help', 'small') ?>
                   </span>                              
                     
              </span>
          </div> <?php //end blue-line ?>
    
          <?php //v1.0.9.0 begin  
           $memory = wc_let_to_num( WP_MEMORY_LIMIT );
      
      		 if ( $memory < 67108864 ) {     //test for 64mb     
          ?>
          <div class="blue-line  clear-left"> 
              <span class="left-column">                                                      
                &nbsp;
              </span>
              <span class="right-column"> 
    			     <?php
               echo 'WP Memory Limit: ' . sprintf( __( '%s - We recommend setting memory to at least 64MB. See: <a href="%s">Increasing memory allocated to PHP</a>', 'woocommerce' ), size_format( $memory ), 'http://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP' ) ;
    		       ?> 
              </span>                 
          </div> <?php //end blue-line ?>
          <?php } //v1.0.9.0 end ?>
          
          
      </div> <?php //end template-area ?>                       

     </div> <?php //end top-box ?>                
     
  <div class="display-virtual_box hideMe" id="lower-screen-wrapper" >

  
      <?php //****************  
            //DEAL INFO GROUP  
            //**************** ?> 
 
     <div class="display-virtual_box  clear-left" id="rule_deal_info_group">  
                       
      <?php // for($k=0; $k < sizeof($vtprd_rule->rule_deal_info[$k]); $k++) {  ?> 
      <?php  for($k=0; $k < sizeof($vtprd_rule->rule_deal_info); $k++) {  ?>         
      <div class="display-virtual_box rule_deal_info" id="rule_deal_info_line<?php echo '_' .$k; ?>">   
        <div class="display-virtual_box" id="buy_info<?php echo '_' .$k; ?>">  
         
           <input id="hiddenDealInfoLine<?php echo '_' .$k; ?>" type="hidden" value="lineActive" name="dealInfoLine<?php echo '_' .$k; ?>" />

           <?php 
              //*****************************************************
              //set the switch used on the screen for JS data check 
              //*****************************************************  ?>
           <?php //end switch ************************************** ?> 

         <div class="screen-box buy_group_title_box">
            <span class="buy_group_title-area">
              <img class="buy_amt_title_icon" src="<?php echo VTPRD_URL;?>/admin/images/tab-icons.png" width="1" height="1" />              
              
              <?php //EITHER / OR TITLE BASED ON DISCOUNT PRICING TYPE ?>
              <span class="section-headings first-level-title showBuyAsDiscount" id="buy_group_title_asDiscount">
                <?php _e('Buy Group ', 'vtprd');?><span class="label-no-cap this-is-the-discount">&nbsp;&nbsp;<?php _e('(Discounted Group)', 'vtprd');?></span>
              </span>
              <span class="section-headings first-level-title showBuyAsBuy" id="buy_group_title_asBuy">
                <?php _e('Buy Group', 'vtprd');?>
              </span>          
            </span>
            <span class="column-heading-titles">              
                <span class="column-heading-titles-type"><?php // _e('Type', 'vtprd');?></span>   <span class="column-heading-titles-count"><?php // _e('Count', 'vtprd');?></span>
            </span>   
         </div><!-- //buy_group_title_box --> 
 

         <div class="screen-box buy_group_box" id="buy_group_box<?php echo '_' .$k; ?>" >
            <span class="left-column">
                <span class="title  hasWizardHelpRight" id="buy_group_title">
                  <a id="buy_group_title_anchor" class="title-anchors second-level-title" href="javascript:void(0);"><span class="showBuyAsBuy"><?php _e('Group Product Filter', 'vtprd');?></span><span class="showBuyAsDiscount"><?php _e('Group Product Filter', 'vtprd');?></span> </a>                    
                  <span class="required-asterisk">* </span>                    
                </span>
                <?php vtprd_show_object_hover_help ('inPop', 'wizard') ?> 
                 
            </span>
            
            <span class="dropdown  buy_group  right-column" id="buy_group_dropdown">              
               <select id="<?php echo $vtprd_rule_display_framework['inPop']['select']['id'];?>" class="<?php echo$vtprd_rule_display_framework['inPop']['select']['class']; ?> " name="<?php echo $vtprd_rule_display_framework['inPop']['select']['name'];?>" tabindex="<?php //echo $vtprd_rule_display_framework['inPop']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['inPop']['option']); $i++) { 
                      
                      //pick up the free/pro version of the title => in this case, title and title3
                      $title = $vtprd_rule_display_framework['inPop']['option'][$i]['title'];
                      if ( ( defined('VTPRD_PRO_DIRNAME') ) &&
                           ( $vtprd_rule_display_framework['inPop']['option'][$i]['title3'] > ' ' ) ) {
                        $title = $vtprd_rule_display_framework['inPop']['option'][$i]['title3'];                        
                      }                 
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['inPop']['option'][$i]['id']; ?>"  class="<?php echo $vtprd_rule_display_framework['inPop']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_rule_display_framework['inPop']['option'][$i]['value']; ?>"   <?php if ($vtprd_rule_display_framework['inPop']['option'][$i]['value'] == $vtprd_rule->inPop )  { echo $selected; } ?> >  <?php echo $title; ?> </option>
                 <?php } ?> 
               </select> 
               
                           
               <span class="buy_group_line_remainder_class" id="buy_group_line_remainder">   
                  <?php $this->vtprd_buy_group_cntl(); ?> 
               </span>                
               
               <?php  /* v1.1 "Product must be in the Filter Group" messaging removed!  */ ?>
                                          
                                        
            </span>                                                                          

         </div><!-- //buy_group_box -->
        
                     
         <div class="screen-box buy_amt_box_class<?php echo '_' .$k; ?>" id="buy_amt_box<?php echo '_' .$k; ?>" >
            
            <span class="left-column">
                <span class="title hasWizardHelpRight" id="buy_amt_title<?php echo '_' .$k; ?> ">
                  <a id="buy_amt_title_anchor<?php echo '_' .$k; ?>" class="title-anchors second-level-title" href="javascript:void(0);"><span class="showBuyAsBuy"><?php _e('Group Amount', 'vtprd');?></span><span class="showBuyAsDiscount"><?php _e('Group Amount', 'vtprd');?></span>
                  </a>
                  <span class="required-asterisk">*</span>                      
                </span> 
                <?php vtprd_show_object_hover_help ('buy_amt_type', 'wizard') ?>                                             
            </span>                
 
            <span class="dropdown  buy_amt  right-column" id="buy_amt_dropdown<?php echo '_' .$k; ?>">              
               <select id="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['select']['id'] . '_' .$k ; ?>" class="<?php echo$vtprd_deal_screen_framework['buy_amt_type']['select']['class']; ?>  " name="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['select']['name'] . '_' .$k ; ?>" tabindex="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_amt_type']['option']); $i++) { 
                          $this->vtprd_change_title_currency_symbol('buy_amt_type', $i, $currency_symbol);
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['id'] . '_'  .$k; ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['value'] == $vtprd_rule->rule_deal_info[$k]['buy_amt_type'] )  { echo $selected; } ?> >  <?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['title']; ?> </option>
                 <?php } ?>                   
                </select>  
                
                            
                 <span class="buy_amt_line_remainder  buy_amt_line_remainder_class<?php echo '_' .$k; ?>" id="buy_amt_line_remainder<?php echo '_' .$k; ?>">   
                     <span class="amt-field buy_amt_count" id="buy_amt_count_area<?php echo '_' .$k; ?>">
                       <input id="<?php echo $vtprd_deal_screen_framework['buy_amt_count']['id'] . '_'  .$k; ?>" class="<?php echo $vtprd_deal_screen_framework['buy_amt_count']['class']; ?>" type="<?php echo $vtprd_deal_screen_framework['buy_amt_count']['type']; ?>" name="<?php echo $vtprd_deal_screen_framework['buy_amt_count']['name'] . '_' .$k ; ?>" value="<?php echo $vtprd_rule->rule_deal_info[$k]['buy_amt_count']; ?>" />
                     </span>

                 </span> 
           
               <span class="shortIntro  shortIntro2" >
                  <em>
                  <?php _e('Buy how many / how much', 'vtprd');?>
                  </em><br>
                  <em>
                  <?php _e('to get access to the Deal?', 'vtprd');?>
                  </em> 
                &nbsp;
                  <img  class="hasHoverHelp2" width="11px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                 <?php vtprd_show_object_hover_help ('buy_amt_type', 'small') ?>
               </span>                               
                                            
            </span>
            
         </div><!-- //buy_amt_box -->


                  
         <div class="screen-box  buy_amt_box_appliesto_class<?php echo '_' .$k; ?>  buy_amt_line_remainder  clear-left" id="buy_amt_box_appliesto<?php echo '_' .$k; ?>" > 
            <span class="show-in-adanced-mode-only">
                <span class="left-column  left-column-less-padding-top3">  
                    <span class="title  hasWizardHelpRight" id="buy_amt_type_title<?php echo '_' .$k; ?>" >            
                      <a id="buy_amt_title_anchor<?php echo '_' .$k; ?>" class="title-anchors second-level-title" href="javascript:void(0);"><?php _e('Group Amount', 'vtprd'); echo '<br>'; _e('Applies to', 'vtprd');?></a>
                    </span> 
                    <?php vtprd_show_object_hover_help ('buy_amt_applies_to', 'wizard') ?>           
                </span> 
                

                <span class="dropdown  right-column">                           
                     <select id="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['select']['id'] . '_' .$k ; ?>" class="<?php echo$vtprd_deal_screen_framework['buy_amt_applies_to']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['select']['name'] . '_' .$k ; ?>" tabindex="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['select']['tabindex']; ?>" >          
                       <?php
                       for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_amt_applies_to']['option']); $i++) { 
                       ?>                             
                          <option id="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['id'] . '_'  .$k  ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['value'] == $vtprd_rule->rule_deal_info[$k]['buy_amt_applies_to'] )  { echo $selected; } ?> >  <?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['title']; ?> </option>
                       <?php } ?> 
                     </select>
                    
                               
                   <span class="shortIntro" >
                      <em>
                      <?php _e('How is the Buy', 'vtprd');?>
                      </em><br>
                      <em>
                      <?php _e('Group Amount counted?', 'vtprd');?>
                      </em>                   
                    &nbsp;
                      <img  class="hasHoverHelp2" width="11px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                     <?php vtprd_show_object_hover_help ('buy_amt_applies_to', 'small') ?>
                   </span>                               
                                  
                </span>
                                         
           </span>
        </div><!-- //buy_amt_box_appliesto -->


                    
         <div class="screen-box buy_amt_mod_box  buy_amt_mod_box_class<?php echo '_' .$k; ?>" id="buy_amt_mod_box<?php echo '_' .$k; ?>" > 
            <span class="left-column">
                <span class="title  third-level-title  hasWizardHelpRight" id="buy_amt_mod_title<?php echo '_' .$k; ?>" >
                  <a id="buy_amt_mod_title_anchor<?php echo '_' .$k; ?>" class="title-anchors third-level-title" href="javascript:void(0);"><span class="showBuyAsBuy"><?php _e('Min / Max', 'vtprd');?></span><span class="showBuyAsDiscount"><?php _e('Min / Max', 'vtprd');?></span></a> 
                </span>
                <?php vtprd_show_object_hover_help ('buy_amt_mod', 'wizard') ?>
            </span>
            <span class="dropdown  buy_amt_mod  right-column" id="buy_amt_mod_dropdown<?php echo '_' .$k; ?>">              
               <select id="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['select']['id'] . '_' .$k ; ?>" class="<?php echo$vtprd_deal_screen_framework['buy_amt_mod']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['select']['name'] . '_' .$k ; ?>" tabindex="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_amt_mod']['option']); $i++) {
                          $this->vtprd_change_title_currency_symbol('buy_amt_mod', $i, $currency_symbol);                  
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['id'] . '_'  .$k  ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['value'] == $vtprd_rule->rule_deal_info[$k]['buy_amt_mod'] )  { echo $selected; } ?> >  <?php echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['title']; ?> </option>
                 <?php } ?> 
               </select>
               
               
               <span class="amt-field  buy_amt_mod_count_area  buy_amt_mod_count_area_class<?php echo '_' .$k; ?>" id="buy_amt_mod_count_area<?php echo '_' .$k; ?>">
                 <input id="<?php echo $vtprd_deal_screen_framework['buy_amt_mod_count']['id'] . '_'  .$k; ?>" class="<?php echo $vtprd_deal_screen_framework['buy_amt_mod_count']['class']; ?>" type="<?php echo $vtprd_deal_screen_framework['buy_amt_mod_count']['type']; ?>" name="<?php echo $vtprd_deal_screen_framework['buy_amt_mod_count']['name'] . '_' .$k ; ?>" value="<?php echo $vtprd_rule->rule_deal_info[$k]['buy_amt_mod_count']; ?>" />
               </span>   
            
               <span class="shortIntro  shortIntro2" >
                  <em>
                  <?php _e('Put an upper or lower ', 'vtprd');?>
                  </em><br>
                  <em>
                  <?php _e('limit on the Buy Group', 'vtprd');?>
                  </em>                   
                &nbsp;
                  <img  class="hasHoverHelp2" width="11px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                 <?php vtprd_show_object_hover_help ('buy_amt_mod', 'small') ?>
               </span>                               
             
            </span>
                          
         </div><!-- //buy_amt_mod_box -->


                    
          <div class="screen-box buy_repeat_box  buy_repeat_box_class<?php echo '_' .$k; ?>" id="buy_repeat_box<?php echo '_' .$k; ?>" >     <?php //Rule repeat shifted to end of action area, although processed first ?> 
            <span class="left-column">
                <span class="title  third-level-title  hasWizardHelpRight" id="buy_repeat_title<?php echo '_' .$k; ?> ">
                   <a id="buy_repeat_title_anchor<?php echo '_' .$k; ?>" class="title-anchors third-level-title" href="javascript:void(0);"><span class="showBuyAsBuy"><?php echo __('Rule Usage Count', 'vtprd');?></span><span class="showBuyAsDiscount"><?php echo __('Rule Usage Count', 'vtprd');?></span></a>
                   <span class="required-asterisk">* </span>
                </span>
                <?php vtprd_show_object_hover_help ('buy_repeat_condition', 'wizard') ?>
            </span>
            
            <span class="dropdown buy_repeat right-column" id="buy_repeat_dropdown<?php echo '_' .$k; ?>">              
               <select id="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['id'] . '_' .$k ; ?>" class="<?php echo$vtprd_deal_screen_framework['buy_repeat_condition']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['name'] . '_' .$k ; ?>" tabindex="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_repeat_condition']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['id'] . '_'  .$k  ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['value'] == $vtprd_rule->rule_deal_info[$k]['buy_repeat_condition'] )  { echo $selected; } ?> >  <?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['title']; ?> </option>
                 <?php } ?> 
               </select>
               
                             
               <span class="amt-field  buy_repeat_count_area  buy_repeat_count_area_class<?php echo '_' .$k; ?>" id="buy_repeat_count_area<?php echo '_' .$k; ?>">              
                 <input id="<?php echo $vtprd_deal_screen_framework['buy_repeat_count']['id'] . '_'  .$k; ?>" class="<?php echo $vtprd_deal_screen_framework['buy_repeat_count']['class']; ?>" type="<?php echo $vtprd_deal_screen_framework['buy_repeat_count']['type']; ?>" name="<?php echo $vtprd_deal_screen_framework['buy_repeat_count']['name'] . '_' .$k ; ?>" value="<?php echo $vtprd_rule->rule_deal_info[$k]['buy_repeat_count']; ?>" />                
               </span>
                        
               <span class="shortIntro  shortIntro2" >
                  <em>
                  <?php _e('How many times can the Rule ', 'vtprd');?>
                  </em><br>
                  <em>
                  <?php _e('be used per Cart?', 'vtprd');?>
                  </em>                   
                &nbsp;
                  <img  class="hasHoverHelp2" width="11px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                  <?php vtprd_show_object_hover_help ('buy_repeat_condition', 'small') ?>
               </span>                               
                       
            </span>
                     
         </div><!-- //buy_repeat_box --> 
          
        </div><!-- //buy_info -->
           
        <?php //ACtion INFO  ?>        
        
        <div class="display-virtual_box action_info" id="action_info<?php echo '_' .$k; ?>"> 
          <span class="box-border-line" id="line-above-action-info">&nbsp;</span>
         <div class="screen-box get_group_title_box">
            <span class="get_group_title-area">
              <img class="get_amt_title_icon" src="<?php echo VTPRD_URL;?>/admin/images/tab-icons.png" width="1" height="1" />              
              <span class="section-headings first-level-title showGetAsDiscount" id="get_group_title_active">
                <?php _e('Get Group ', 'vtprd');?><span class="label-no-cap">&nbsp;&nbsp;<?php _e('(Discounted Group)', 'vtprd');?></span>
              </span>
              <span class="section-headings first-level-title showGetAsGet" id="get_group_title_inactive">
                <?php _e('Get Group ', 'vtprd');?><span class="label-no-cap">&nbsp;&nbsp;<?php _e('(Inactive)', 'vtprd');?></span>
              </span>
              
            </span>
         </div><!-- //get_group_title_box --> 



         <div class="screen-box action_group_box" id="action_group_box<?php echo '_' .$k; ?>" >
            <span class="left-column">
                <span class="title  hasWizardHelpRight" id="action_group_title">
                  <a id="action_group_title_anchor" class="title-anchors second-level-title" href="javascript:void(0);"><span class="showGetAsGet"><?php _e('Group Product Filter', 'vtprd');?></span><span class="showGetAsDiscount"><?php _e('Group Product Filter', 'vtprd');?></span></a>
                  <span class="required-asterisk">*</span>
                </span> 
                <?php vtprd_show_object_hover_help ('actionPop', 'wizard') ?>      
            </span>
             
            <span class="dropdown action_group right-column" id="action_group_dropdown_0">              
               <select id="<?php echo $vtprd_rule_display_framework['actionPop']['select']['id'];?>" class="<?php echo$vtprd_rule_display_framework['actionPop']['select']['class']; ?>" name="<?php echo $vtprd_rule_display_framework['actionPop']['select']['name'];?>" tabindex="<?php //echo $vtprd_rule_display_framework['actionPop']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['actionPop']['option']); $i++) { 
                       
                      //pick up the free/pro version of the title => in this case, title and title3
                      $title = $vtprd_rule_display_framework['actionPop']['option'][$i]['title'];
                      if ( ( defined('VTPRD_PRO_DIRNAME') ) &&
                           ( $vtprd_rule_display_framework['actionPop']['option'][$i]['title3'] > ' ' ) ) {
                        $title = $vtprd_rule_display_framework['actionPop']['option'][$i]['title3'];                        
                      }                
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['actionPop']['option'][$i]['id']; ?>"  class="<?php echo $vtprd_rule_display_framework['actionPop']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_rule_display_framework['actionPop']['option'][$i]['value']; ?>"   <?php if ($vtprd_rule_display_framework['actionPop']['option'][$i]['value'] == $vtprd_rule->actionPop )  { echo $selected; } ?> >  <?php echo $title; ?> </option>
                 <?php } ?> 
               </select> 
               
                           
               <span class="action_group_line_remainder_class" id="action_group_line_remainder">   
                <?php $this->vtprd_action_group_cntl(); ?> 
               </span>
               
               <?php  /* v1.1 "Product must be in the Filter Group" messaging removed!  */ ?>                               
                    
            </span>

         </div><!-- //action_group_box -->

                   
         <div class="screen-box action_amt_box  action_amt_box_class<?php echo '_' .$k; ?>" id="action_amt_box<?php echo '_' .$k; ?>" > 
            <span class="left-column">  
                <span class="title  hasWizardHelpRight" id="action_amt_type_title<?php echo '_' .$k; ?>" >            
                  <a id="action_amt_title_anchor<?php echo '_' .$k; ?>" class="title-anchors second-level-title" href="javascript:void(0);"><span class="showGetAsGet"><?php _e('Group Amount', 'vtprd');?></span><span class="showGetAsDiscount"><?php _e('Group Amount', 'vtprd');?></span></a>
                  <span class="required-asterisk">*</span>
                </span>
                <?php vtprd_show_object_hover_help ('action_amt_type', 'wizard') ?>                                
            </span> 
            <span class="dropdown action_amt right-column" id="action_amt_dropdown<?php echo '_' .$k; ?>">              
               <select id="<?php echo $vtprd_deal_screen_framework['action_amt_type']['select']['id'] . '_' .$k ; ?>" class="<?php echo$vtprd_deal_screen_framework['action_amt_type']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['action_amt_type']['select']['name'] . '_' .$k ; ?>" tabindex="<?php echo $vtprd_deal_screen_framework['action_amt_type']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['action_amt_type']['option']); $i++) {
                          $this->vtprd_change_title_currency_symbol('action_amt_type', $i, $currency_symbol);                  
                 ?>                            
                    <option id="<?php echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['id'] . '_'  .$k  ?>"  class="<?php echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['action_amt_type']['option'][$i]['value'] == $vtprd_rule->rule_deal_info[$k]['action_amt_type'] )  { echo $selected; } ?> >  <?php echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['title']; ?> </option>
                 <?php } ?> 
               </select>              
               
              
               <span class="action_amt_line_remainder  action_amt_line_remainder_class<?php echo '_' .$k; ?>" id="action_amt_line_remainder<?php echo '_' .$k; ?>">
                   <span class="amt-field action_amt_count" id="action_amt_count_pair<?php echo '_' .$k; ?>">
                     <input id="<?php echo $vtprd_deal_screen_framework['action_amt_count']['id'] . '_'  .$k; ?>" class="<?php echo $vtprd_deal_screen_framework['action_amt_count']['class']; ?>" type="<?php echo $vtprd_deal_screen_framework['action_amt_count']['type']; ?>" name="<?php echo $vtprd_deal_screen_framework['action_amt_count']['name'] . '_' .$k ; ?>" value="<?php echo $vtprd_rule->rule_deal_info[$k]['action_amt_count']; ?>" />
                   </span>                                                    
               </span>  
           
               <span class="shortIntro  shortIntro2" >
                  <em>
                  <?php _e('Get how many / how much', 'vtprd');?>
                  </em><br>
                  <em>
                  <?php _e('to get access to the Deal?', 'vtprd');?>
                  </em> 
                &nbsp;
                  <img  class="hasHoverHelp2" width="11px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                 <?php vtprd_show_object_hover_help ('action_amt_type', 'small') ?>
               </span>                               
                                          
            </span>

        </div><!-- //action_amt_box -->

                  
         <div class="screen-box action_amt_box_appliesto_class<?php echo '_' .$k; ?>  action_amt_line_remainder clear-left  " id="action_amt_box_appliesto<?php echo '_' .$k; ?>" > 
            <span class="show-in-adanced-mode-only">
                <span class="left-column  left-column-less-padding-top3">  
                    <span class="title  hasWizardHelpRight" id="action_amt_type_title<?php echo '_' .$k; ?>" >            
                      <a id="action_amt_title_anchor<?php echo '_' .$k; ?>" class="title-anchors second-level-title" href="javascript:void(0);"><span class="showGetAsGet"><?php _e('Group Amount', 'vtprd'); echo '<br>'; _e('Applies to', 'vtprd');?></span><span class="showGetAsDiscount"><?php _e('Group Amount', 'vtprd'); echo '<br>'; _e('Applies to', 'vtprd');?></span></a>
                    </span>
                    <?php vtprd_show_object_hover_help ('action_amt_applies_to', 'wizard') ?>            
                </span> 

                <span class="dropdown    right-column">                           
                     <select id="<?php echo $vtprd_deal_screen_framework['action_amt_applies_to']['select']['id'] . '_' .$k ; ?>" class="<?php echo$vtprd_deal_screen_framework['action_amt_applies_to']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['action_amt_applies_to']['select']['name'] . '_' .$k ; ?>" tabindex="<?php //echo $vtprd_deal_screen_framework['action_amt_applies_to']['select']['tabindex']; ?>" >          
                       <?php
                       for($i=0; $i < sizeof($vtprd_deal_screen_framework['action_amt_applies_to']['option']); $i++) { 
                       ?>                             
                          <option id="<?php echo $vtprd_deal_screen_framework['action_amt_applies_to']['option'][$i]['id'] . '_'  .$k  ?>"  class="<?php echo $vtprd_deal_screen_framework['action_amt_applies_to']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['action_amt_applies_to']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['action_amt_applies_to']['option'][$i]['value'] == $vtprd_rule->rule_deal_info[$k]['action_amt_applies_to'] )  { echo $selected; } ?> >  <?php echo $vtprd_deal_screen_framework['action_amt_applies_to']['option'][$i]['title']; ?> </option>
                       <?php } ?> 
                     </select>
                     
                               
                   <span class="shortIntro" >
                      <em>
                      <?php _e('How is the Get', 'vtprd');?>
                      </em><br>
                      <em>
                      <?php _e('Group Amount counted?', 'vtprd');?>
                      </em>                   
                    &nbsp;
                      <img  class="hasHoverHelp2" width="11px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                     <?php vtprd_show_object_hover_help ('action_amt_applies_to', 'small') ?>
                   </span>                               
    
                </span>

            </span>
        </div><!-- //action_amt_box_appliesto -->


 
                    
        <div class="screen-box action_amt_mod_box  action_amt_mod_box_class<?php echo '_' .$k; ?>" id="action_amt_mod_box<?php echo '_' .$k; ?>" >
            <span class="left-column">
                <span class="title  third-level-title  hasWizardHelpRight" id="action_amt_mod_title<?php echo '_' .$k; ?>" >
                   <a id="action_amt_mod_title_anchor<?php echo '_' .$k; ?>" class="title-anchors third-level-title" href="javascript:void(0);"><span class="showGetAsGet"><?php _e('Min / Max', 'vtprd');?></span><span class="showGetAsDiscount"><?php _e('Min / Max', 'vtprd');?></span></a>
                </span>
                <?php vtprd_show_object_hover_help ('action_amt_mod', 'wizard') ?>
            </span>
            
            <span class="dropdown  right-column" id="action_amt_mod_dropdown<?php echo '_' .$k; ?>">
               <select id="<?php echo $vtprd_deal_screen_framework['action_amt_mod']['select']['id'] . '_' .$k ; ?>" class="<?php echo $vtprd_deal_screen_framework['action_amt_mod']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['action_amt_mod']['select']['name'] . '_' .$k ; ?>" tabindex="<?php //echo $vtprd_deal_screen_framework['action_amt_mod']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['action_amt_mod']['option']); $i++) { 
                          $this->vtprd_change_title_currency_symbol('action_amt_mod', $i, $currency_symbol);                  
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['action_amt_mod']['option'][$i]['id'] . '_'  .$k  ?>"  class="<?php echo $vtprd_deal_screen_framework['action_amt_mod']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['action_amt_mod']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['action_amt_mod']['option'][$i]['value'] == $vtprd_rule->rule_deal_info[$k]['action_amt_mod'] )  { echo $selected; } ?> >  <?php echo $vtprd_deal_screen_framework['action_amt_mod']['option'][$i]['title']; ?> </option>
                 <?php } ?> 
               </select>
               
                            
               <span class="amt-field  action_amt_mod_count_area  action_amt_mod_count_area_class<?php echo '_' .$k; ?>" id="action_amt_mod_count_area<?php echo '_' .$k; ?>">
                 <input id="<?php echo $vtprd_deal_screen_framework['action_amt_mod_count']['id'] . '_'  .$k; ?>" class="<?php echo $vtprd_deal_screen_framework['action_amt_mod_count']['class']; ?>" type="<?php echo $vtprd_deal_screen_framework['action_amt_mod_count']['type']; ?>" name="<?php echo $vtprd_deal_screen_framework['action_amt_mod_count']['name'] . '_' .$k ; ?>" value="<?php echo $vtprd_rule->rule_deal_info[$k]['action_amt_mod_count']; ?>" />
               </span>  
            
               <span class="shortIntro  shortIntro2" >
                  <em>
                  <?php _e('Put an upper or lower ', 'vtprd');?>
                  </em><br>
                  <em>
                  <?php _e('limit on the Get Group', 'vtprd');?>
                  </em>                   
                &nbsp;
                  <img  class="hasHoverHelp2" width="11px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                  <?php vtprd_show_object_hover_help ('action_amt_mod', 'small') ?>
               </span>                                  
            </span>
         </div><!-- //action_amt_mod_box -->  


         
         <div class="screen-box action_repeat_condition_box  action_repeat_condition_box_class<?php echo '_' .$k; ?>" id="action_repeat_condition_box<?php echo '_' .$k; ?>" >      <?php //Action repeat shifted to end of action area, although processed first ?> 
            <span class="left-column">
                <span class="title  third-level-title  hasWizardHelpRight" id="action_repeat_condition_title<?php echo '_' .$k; ?>" >
                   <a id="action_repeat_condition_title_anchor<?php echo '_' .$k; ?>" class="title-anchors third-level-title" href="javascript:void(0);"><span class="showGetAsGet"><?php _e('Group Repeat', 'vtprd');?></span><span class="showGetAsDiscount"><?php _e('Group Repeat', 'vtprd');?></span></a>
                </span>
                <?php vtprd_show_object_hover_help ('action_repeat_condition', 'wizard') ?>
            </span>
            <span class="dropdown action_repeat_condition right-column"  id="action_repeat_condition_dropdown<?php echo '_' .$k; ?>">              
               
               <select id="<?php echo $vtprd_deal_screen_framework['action_repeat_condition']['select']['id'] . '_' .$k ; ?>" class="<?php echo$vtprd_deal_screen_framework['action_repeat_condition']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['action_repeat_condition']['select']['name'] . '_' .$k ; ?>" tabindex="<?php //echo $vtprd_deal_screen_framework['action_repeat_condition']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['action_repeat_condition']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['action_repeat_condition']['option'][$i]['id'] . '_'  .$k  ?>"  class="<?php echo $vtprd_deal_screen_framework['action_repeat_condition']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['action_repeat_condition']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['action_repeat_condition']['option'][$i]['value'] == $vtprd_rule->rule_deal_info[$k]['action_repeat_condition'] )  { echo $selected; } ?> >  <?php echo $vtprd_deal_screen_framework['action_repeat_condition']['option'][$i]['title']; ?> </option>
                 <?php } ?> 
               </select> 
               
                            
               <span class="amt-field action_repeat_count_area  action_repeat_count_area_class<?php echo '_' .$k; ?>" id="action_repeat_count_area<?php echo '_' .$k; ?>">
                 <input id="<?php echo $vtprd_deal_screen_framework['action_repeat_count']['id'] . '_'  .$k; ?>" class="<?php echo $vtprd_deal_screen_framework['action_repeat_count']['class']; ?>" type="<?php echo $vtprd_deal_screen_framework['action_repeat_count']['type']; ?>" name="<?php echo $vtprd_deal_screen_framework['action_repeat_count']['name'] . '_' .$k ; ?>" value="<?php echo $vtprd_rule->rule_deal_info[$k]['action_repeat_count']; ?>" />                 
               </span>
                        
               <span class="shortIntro  shortIntro2" >
                  <em>
                  <?php _e('How many times may the Get Group be', 'vtprd');?>
                  </em><br>
                  <em>
                  <?php _e('used (after Buy Group satisfied)?', 'vtprd');?>
                  </em>                   
                &nbsp;
                  <img  class="hasHoverHelp2" width="11px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                  <?php vtprd_show_object_hover_help ('action_repeat_condition', 'small') ?>
               </span>                                                   
           </span>
         </div><!-- //action_repeat_condition_box -->  
         
      </div><!-- //action_info -->  
        
       
        <span class="box-border-line">&nbsp;</span>
        
        <div class="display-virtual_box" id="discount_info">
                 
          <div class="screen-box discount_amt_box  discount_amt_box_class<?php echo '_' .$k; ?>" id="discount_amt_box<?php echo '_' .$k; ?>" >  
            <span class="title" id="discount_amt_title<?php echo '_' .$k; ?>" >
              <img class="discount_amt_title_icon" src="<?php echo VTPRD_URL;?>/admin/images/tab-icons.png" width="1" height="1" />                            
              <a id="discount_amt_title_anchor<?php echo '_' .$k; ?>" class="section-headings first-level-title" href="javascript:void(0);"><?php _e('Discount ', 'vtprd'); echo $currency_symbol; ?></a>
            </span>
            
            <span class="clear-both left-column">
                <span class="title  discount_action_type  hasWizardHelpRight" id="discount_action_type_title<?php echo '_' .$k; ?>" >            
                  <a id="discount_action_title_anchor<?php echo '_' .$k; ?>" class="title-anchors second-level-title" href="javascript:void(0);"><?php _e('Discount Amount', 'vtprd');?></a>
                  <span class="required-asterisk">*</span>
                </span>
                <?php vtprd_show_object_hover_help ('discount_amt_type', 'wizard') ?>
            </span>

            <span class="dropdown discount_amt_type right-column" id="discount_amt_type_dropdown<?php echo '_' .$k; ?>">              
              
               <select id="<?php echo $vtprd_deal_screen_framework['discount_amt_type']['select']['id'] . '_' .$k ; ?>" class="<?php echo$vtprd_deal_screen_framework['discount_amt_type']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['discount_amt_type']['select']['name'] . '_' .$k ; ?>" tabindex="<?php //echo $vtprd_deal_screen_framework['discount_amt_type']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['discount_amt_type']['option']); $i++) { 
                          $this->vtprd_change_title_currency_symbol('discount_amt_type', $i, $currency_symbol);                 
                  ?>                                                
                    <option id="<?php echo $vtprd_deal_screen_framework['discount_amt_type']['option'][$i]['id'] . '_'  .$k  ?>"  class="<?php echo $vtprd_deal_screen_framework['discount_amt_type']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['discount_amt_type']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['discount_amt_type']['option'][$i]['value'] == $vtprd_rule->rule_deal_info[$k]['discount_amt_type'] )  { echo $selected; } ?> >  <?php echo $vtprd_deal_screen_framework['discount_amt_type']['option'][$i]['title']; ?> </option>
                 <?php } ?> 
               </select>
               
                
               <span class="discount_amt_count_area  discount_amt_count_area_class<?php echo '_' .$k; ?>  amt-field" id="discount_amt_count_area<?php echo '_' .$k; ?>">    
                 <span class="discount_amt_count_label" id="discount_amt_count_label<?php echo '_' .$k; ?>"> 
                    <span class="forThePriceOf-amt-literal-inserted  discount_amt_count_literal  discount_amt_count_literal<?php echo '_' .$k;?> " id="discount_amt_count_literal_forThePriceOf_buyAmt<?php echo '_' .$k; ?>"><?php $this->vtprd_load_forThePriceOf_literal($k); ?> </span>
                    <span class="discount_amt_count_literal  discount_amt_count_literal_forThePriceOf  discount_amt_count_literal<?php echo '_' .$k;?> " id="discount_amt_count_literal_forThePriceOf<?php echo '_' .$k; ?>"><?php _e('units ', 'vtprd'); echo  '&nbsp;';  _e(' For the Price of ', 'vtprd');?> </span>
                    <span class="discount_amt_count_literal  discount_amt_count_literal_forThePriceOf_Currency  discount_amt_count_literal<?php echo '_' .$k;?> " id="discount_amt_count_literal_forThePriceOf_Currency<?php echo '_' .$k; ?>"><?php echo $currency_symbol; ?></span>
                 </span>                 
                 <input id="<?php echo $vtprd_deal_screen_framework['discount_amt_count']['id'] . '_'  .$k; ?>" class="<?php echo $vtprd_deal_screen_framework['discount_amt_count']['class']; ?>" type="<?php echo $vtprd_deal_screen_framework['discount_amt_count']['type']; ?>" name="<?php echo $vtprd_deal_screen_framework['discount_amt_count']['name'] . '_' .$k ; ?>" value="<?php echo $vtprd_rule->rule_deal_info[$k]['discount_amt_count']; ?>" />                 
                 <span class="discount_amt_count_literal_units_area  discount_amt_count_literal<?php echo '_' .$k;?>  discount_amt_count_literal_units_area_class<?php echo '_' .$k; ?>" id="discount_amt_count_literal_units_area<?php echo '_' .$k; ?>">
                   <span class="discount_amt_count_literal" id="discount_amt_count_literal_units<?php echo '_' .$k; ?>"><?php _e(' units', 'vtprd');?> </span>
                   <?php vtprd_show_help_tooltip($context = 'discount_amt_count_forThePriceOf'); ?>
                 </span>                
               </span>
                <label id="<?php echo $vtprd_deal_screen_framework['discount_auto_add_free_product']['label']['id'] . '_'  .$k; ?>"   class="<?php echo $vtprd_deal_screen_framework['discount_auto_add_free_product']['label']['class'] ?>"> 
                    
                    <input id="<?php echo $vtprd_deal_screen_framework['discount_auto_add_free_product']['checkbox']['id'] . '_'  .$k; ?>" 
                          class="<?php echo $vtprd_deal_screen_framework['discount_auto_add_free_product']['checkbox']['class']; ?>  hasWizardHelpBelow"
                          type="checkbox" 
                          value="<?php echo $vtprd_deal_screen_framework['discount_auto_add_free_product']['checkbox']['value']; ?>" 
                           <?php if ($vtprd_deal_screen_framework['discount_auto_add_free_product']['checkbox']['value'] == $vtprd_rule->rule_deal_info[$k]['discount_auto_add_free_product'] )  { echo $checked; } ?>
                          name="<?php echo $vtprd_deal_screen_framework['discount_auto_add_free_product']['checkbox']['name'] . '_'  .$k; ?>" />
                    <?php vtprd_show_object_hover_help ('discount_free', 'wizard') ?> 
                          
                    <?php echo $vtprd_deal_screen_framework['discount_auto_add_free_product']['label']['title']; ?>  
                    <?php vtprd_show_help_tooltip($context = 'discount_auto_add_free_product', $location = 'title'); ?> 
                </label>
                        
               <span class="shortIntro  shortIntro2" >
                  <em>
                  <?php _e('What kind of Discount is offered,', 'vtprd');?>
                  </em><br>
                  <em>
                  <?php _e('and in what amount?', 'vtprd');?>
                  </em>                   
                &nbsp;
                  <img  class="hasHoverHelp2" width="11px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                  <?php vtprd_show_object_hover_help ('discount_amt_type', 'small') ?>
               </span>                                     
            </span>
     
          </div> <!-- //discount_amt_box -->
                  
          <div class="screen-box discount_applies_to_box  discount_applies_to_box_class<?php echo '_' .$k; ?>" id="discount_applies_to_box<?php echo '_' .$k; ?>" >
            <span class="left-column">
                <span class="title  hasWizardHelpRight" id="discount_applies_to_title<?php echo '_' .$k; ?>" >
                  <a id="discount_applies_to_title_anchor<?php echo '_' .$k; ?>" class="title-anchors second-level-title" href="javascript:void(0);"><?php _e('Discount Applies To', 'vtprd');?></a>
                </span>
                <?php vtprd_show_object_hover_help ('discount_applies_to', 'wizard') ?>
            </span>
            
            <span class="dropdown discount_applies_to right-column"  id="discount_applies_to_dropdown<?php echo '_' .$k; ?>">              
               
               <select id="<?php echo $vtprd_deal_screen_framework['discount_applies_to']['select']['id'] . '_' .$k ; ?>" class="<?php echo$vtprd_deal_screen_framework['discount_applies_to']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['discount_applies_to']['select']['name'] . '_' .$k ; ?>" tabindex="<?php //echo $vtprd_deal_screen_framework['discount_applies_to']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['discount_applies_to']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['discount_applies_to']['option'][$i]['id'] . '_'  .$k  ?>"  class="<?php echo $vtprd_deal_screen_framework['discount_applies_to']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['discount_applies_to']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['discount_applies_to']['option'][$i]['value'] == $vtprd_rule->rule_deal_info[$k]['discount_applies_to'] )  { echo $selected; } ?> >  <?php echo $vtprd_deal_screen_framework['discount_applies_to']['option'][$i]['title']; ?> </option>
                 <?php } ?> 
               </select>
               
                               
                   <span class="shortIntro" >
                      <em>
                      <?php _e('How is the Discount ', 'vtprd');?>
                      </em><br>
                      <em>
                      <?php _e('Amount counted?', 'vtprd');?>
                      </em>                   
                    &nbsp;
                      <img  class="hasHoverHelp2" width="11px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                     <?php vtprd_show_object_hover_help ('discount_applies_to', 'small') ?>
                   </span>                                                                               
              </span>
          </div><!-- //discount_applies_to_box -->


          <?php //v1.1.0.8 New  BOX - only by coupon ;?>  
          <div class="screen-box only_for_this_coupon_box only_for_this_coupon_box_class<?php echo '_' .$k; ?>" id="only_for_this_coupon_box<?php echo '_' .$k; ?>" >     <?php //Rule repeat shifted to end of action area, although processed first ?> 
            <span class="left-column">
                <span class="title  third-level-title  hasWizardHelpRight" id="only_for_this_coupon_title">
                   <a id="only_for_this_coupon_anchor" class="title-anchors third-level-title" href="javascript:void(0);"><?php _e('Discount Coupon Code', 'vtprd');  //_e('Apply Discount only with', 'vtprd'); echo '<br>'; _e('This Coupon Code', 'vtprd');  // _e('Discount Only with Coupon Code (optional)', 'vtprd'); ?> </a>
                </span>
                <?php vtprd_show_object_hover_help ('only_for_this_coupon_name', 'wizard') ?>
            </span>
            
            <span class="dropdown buy_repeat right-column only_for_this_coupon_name-column" id="only_for_this_coupon_name_dropdown">              
                     <span class="column-width50">
                         <textarea rows="1" cols="50" id="<?php echo $vtprd_rule_display_framework['only_for_this_coupon_name']['id']; ?>" class="<?php echo $vtprd_rule_display_framework['only_for_this_coupon_name']['class']; ?>  right-column" type="<?php echo $vtprd_rule_display_framework['only_for_this_coupon_name']['type']; ?>" name="<?php echo $vtprd_rule_display_framework['only_for_this_coupon_name']['name']; ?>" ><?php echo $vtprd_rule->only_for_this_coupon_name; ?></textarea>
                         
                     </span>              
                     <span class="shortIntro" >
                        <em>
                        <?php _e('Apply Rule Only if Coupon Code', 'vtprd');?>
                        </em><br>
                        <em>
                        <?php _e('is Presented (optional)', 'vtprd');?>
                        </em>                   
                      &nbsp;
                        <img  class="hasHoverHelp2" width="11px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                        <?php vtprd_show_object_hover_help ('only_for_this_coupon_name', 'small') ?>
                     </span>                               
                               
                       
            </span>
                     
         </div><!-- //only_for_this_coupon_box-->  
                            
                  
        </div> <!-- //discount_info -->
  
        
        </div> <!-- //end DEAL INFO line in "for" loop --><?php //end DEAL INFO line in "for" loop ?>   
      <?php } //end $k'for' LOOP ?>
      </div> <!-- //rule_deal_info_group --> <?php //end rule_deal_info_group ?>  
      
      <span class="box-border-line">&nbsp;</span>
      <div id="messages-outer-box">           
         <div class="screen-box  messages-box_class" id="messages-box">
           <span class="title" id="discount_msgs_title" >
              <img class="theme_msgs_title_icon" src="<?php echo VTPRD_URL;?>/admin/images/tab-icons.png" width="1" height="1" />                                          
              <a id="discount_msgs_title_anchor" class="section-headings first-level-title" href="javascript:void(0);"><?php _e('Discount Messages:', 'vtprd');?></a>            
           </span>
           <span class="dropdown messages-box-area clear-left"  id="discount_msgs_dropdown">
             <span class="discount_product_short_msg_area  clear-left">

                 <span class="left-column">
                     <span class="title  hasHoverHelp  hasWizardHelpRight">                
                         <span class="title-anchors" id="discount_product_short_msg_label"><?php _e('Checkout Message', 'vtprd'); ?></span> 
                         <span class="required-asterisk">*</span>
                     </span>
                     <?php vtprd_show_object_hover_help ('discount_product_short_msg', 'wizard') ?>
                 </span>

                 <span class="right-column">
                     <span class="column-width50">
                         <textarea rows="1" cols="50" id="<?php echo $vtprd_rule_display_framework['discount_product_short_msg']['id']; ?>" class="<?php echo $vtprd_rule_display_framework['discount_product_short_msg']['class']; ?>  right-column" type="<?php echo $vtprd_rule_display_framework['discount_product_short_msg']['type']; ?>" name="<?php echo $vtprd_rule_display_framework['discount_product_short_msg']['name']; ?>" ><?php echo $vtprd_rule->discount_product_short_msg; ?></textarea>
                         
                     </span>              
                     <span class="shortIntro" >
                        <em>
                        <?php _e('Checkout Message shows only ', 'vtprd');?>
                        </em><br>
                        <em>
                        <?php _e('for Cart Deals (not Catalog)', 'vtprd');?>
                        </em>                   
                      &nbsp;
                        <img  class="hasHoverHelp2" width="11px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                        <?php vtprd_show_object_hover_help ('discount_product_short_msg', 'small') ?>
                     </span>                               

                  </span>                      
             </span>       
             
                    
             <span class="discount_product_full_msg_area clear-both">

                 <span class="left-column">
                     <span class="title  hasWizardHelpRight">                
                         <span class="title-anchors" id="discount_product_full_msg_label"> <?php _e('Advertising Message', 'vtprd');?> </span> 
                     </span>
                     <?php vtprd_show_object_hover_help ('discount_product_full_msg', 'wizard') ?>
                 </span>
                                    
                 <span class="right-column">                
                     <span class="column-width50">
                         <textarea rows="2" cols="35" id="<?php echo $vtprd_rule_display_framework['discount_product_full_msg']['id']; ?>" class="<?php echo $vtprd_rule_display_framework['discount_product_full_msg']['class']; ?>  right-column" type="<?php echo $vtprd_rule_display_framework['discount_product_full_msg']['type']; ?>" name="<?php echo $vtprd_rule_display_framework['discount_product_full_msg']['name']; ?>" ><?php echo $vtprd_rule->discount_product_full_msg; ?></textarea>                                                                                              
                         
                     </span>                               
                     <span class="shortIntro" >
                        <em>
                        <?php _e('Can be shown in your Website using', 'vtprd');?>
                        </em><br>
                        <em>
                        <?php _e('Shortcodes', 'vtprd');?>
                        </em>
                     &nbsp;
                    <a class="commentURL" target="_blank" href="http://www.varktech.com/documentation/pricing-deals/shortcodes"><?php _e('Shortcode Examples', 'vtprd');?></a>                                                     
                      &nbsp;
                        <img  class="hasHoverHelp2" width="11px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                       <?php vtprd_show_object_hover_help ('discount_product_full_msg', 'small') ?>
                     </span>  
                                            
                  </span> 
            
             </span>

           </span>
         </div>    
      </div>
       
      
      <span class="box-border-line">&nbsp;</span>
      
    
    <div id="advanced-data-area"> 

      <div class="screen-box" id="maximums_box">   
          <span class="title" id="cumulativePricing_title" >
            <img class="maximums_icon" src="<?php echo VTPRD_URL;?>/admin/images/tab-icons.png" width="1" height="1" />                                                        
            <a id="cumulativePricing_title_anchor" class="section-headings first-level-title" href="javascript:void(0);">
                <?php _e('Discount Limits:', 'vtprd');?>
                <?php if (!defined('VTPRD_PRO_DIRNAME'))  {  ?>
                    <span id="max-limits-subtitle"><?php _e('(pro only)', 'vtprd');?></span>
                <?php }  ?>
            </a>
          </span>
 
           
        
          <div class="screen-box  screen-box2 discount_lifetime_max_amt_type_box  clear-left" id="discount_lifetime_max_amt_type_box_0">  
             <?php
                 /* ***********************
                 special handling for  discount_lifetime_max_amt_type, discount_lifetime_max_amt_type.  Even though they appear iteratively in deal info,
                 they are only active on the '0' occurrence line.  further, they are displayed only AFTER all of the deal lines are displayed
                 onscreen... This is actually a kluge, done to utilize the complete editing already available in the deal info loop for a  dropdown and an associated amt field.
                 *********************** */
             
               //Both _label fields have trailing '_0', as edits are actually handled in the discount info loop ?>          
            <span class="left-column  left-column-less-padding-top2">
                <span class="title  hasWizardHelpRight" id="discount_lifetime_max_title_0" >
                  <a id="discount_lifetime_max_title_anchor" class="title-anchors second-level-title" href="javascript:void(0);"><?php _e('Customer', 'vtprd'); echo '<br>'; _e('Rule Limit', 'vtprd');?></a>
                </span>
                <?php vtprd_show_object_hover_help ('discount_lifetime_max_amt_type', 'wizard') ?> 
            </span>
            
            <span class="dropdown  right-column" id="discount_lifetime_max_dropdown">
               
               <select id="<?php echo $vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['select']['id'] .'_0' ;?>" class="<?php echo$vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['select']['name'] .'_0' ;?>" tabindex="<?php echo $vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['select']['tabindex'] .'_0' ; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['option']); $i++) { 
                          $this->vtprd_change_title_currency_symbol('discount_lifetime_max_amt_type', $i, $currency_symbol);
                      
                      //pick up the free/pro version of the title => in this case, title and title3
                      $title = $vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['option'][$i]['title'];
                      if ( ( defined('VTPRD_PRO_DIRNAME') ) &&
                           ( $vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['option'][$i]['title3'] > ' ' ) ) {
                        $title = $vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['option'][$i]['title3'];                        
                      }          
                                                            
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['option'][$i]['id'] .'_0' ;?>"  class="<?php echo $vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['option'][$i]['value']  == $vtprd_rule->rule_deal_info[0]['discount_lifetime_max_amt_type']  )  { echo $selected; } // use '0' deal_info_line...?> >  <?php echo $title; ?> </option>
                 <?php } ?> 
               </select>
               
                           
               <span class="amt-field" id="discount_lifetime_max_amt_count_area">
 
                 <input id="<?php echo $vtprd_deal_screen_framework['discount_lifetime_max_amt_count']['id'] .'_0' ?>" class="<?php echo $vtprd_deal_screen_framework['discount_lifetime_max_amt_count']['class']; ?>  limit-count" type="<?php echo $vtprd_deal_screen_framework['discount_lifetime_max_amt_count']['type']; ?>" name="<?php echo $vtprd_deal_screen_framework['discount_lifetime_max_amt_count']['name'] .'_0' ;?>" value="<?php echo $vtprd_rule->rule_deal_info[0]['discount_lifetime_max_amt_count']; // use '0' deal_info_line...?>" />
               </span>
            
                        
               <span class="shortIntro  shortIntro2" >
                  <em>
                  <?php _e('Limit by Customer - by Count or', 'vtprd');?>
                  </em><br>
                  <em>
                  <?php _e('Discount value - Lifetime of rule', 'vtprd');?>
                  </em>                   
                &nbsp;
                  <img  class="hasHoverHelp2" width="11px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                  <?php vtprd_show_object_hover_help ('discount_lifetime_max_amt_type', 'small') ?>
               </span>                               

            </span>
             <span class="text-field  clear-left" id="discount_lifetime_max_amt_msg">
               <span class="data-line-indent">&nbsp;</span>
               <span class="text-field-label" id="discount_lifetime_max_amt_msg_label"> <?php _e('Short Message When Max Applied (opt) ', 'vtprd');?> </span>
                <?php vtprd_show_help_tooltip($context = 'discount_lifetime_max_amt_msg'); ?>
               <textarea rows="1" cols="100" id="<?php echo $vtprd_rule_display_framework['discount_lifetime_max_amt_msg']['id']; ?>" class="<?php echo $vtprd_rule_display_framework['discount_lifetime_max_amt_msg']['class']; ?>" type="<?php echo $vtprd_rule_display_framework['discount_lifetime_max_amt_msg']['type']; ?>" name="<?php echo $vtprd_rule_display_framework['discount_lifetime_max_amt_msg']['name']; ?>" ><?php echo $vtprd_rule->discount_lifetime_max_amt_msg; ?></textarea>
             </span>           
          </div> 
                   
                    
          
 
           
        <div class="screen-box  screen-box2  dropdown discount_rule_max_amt_type discount_rule_max_amt_type_box clear-left" id="discount_rule_max_amt_type_box_0">  
             <?php
                 /* ***********************
                 special handling for  discount_rule_max_amt_type, discount_rule_max_amt_type.  Even though they appear iteratively in deal info,
                 they are only active on the '0' occurrence line.  further, they are displayed only AFTER all of the deal lines are displayed
                 onscreen... This is actually a kluge, done to utilize the complete editing already available in the deal info loop for a  dropdown and an associated amt field.
                 *********************** */
             
               //Both _label fields have trailing '_0', as edits are actually handled in the discount info loop ?>          
            <span class="left-column">
                <span class="title  hasWizardHelpRight" id="discount_rule_max_title_0" >
                  <a id="discount_rule_max_title_anchor" class="title-anchors second-level-title" href="javascript:void(0);"><?php _e('Cart Limit', 'vtprd');?></a>
                </span>
                <?php vtprd_show_object_hover_help ('discount_rule_max_amt_type', 'wizard') ?>                
            </span>   
                    
            <span class="dropdown right-column" id="discount_rule_max_dropdown">
                
                <select id="<?php echo $vtprd_deal_screen_framework['discount_rule_max_amt_type']['select']['id'] .'_0' ;?>" class="<?php echo$vtprd_deal_screen_framework['discount_rule_max_amt_type']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['discount_rule_max_amt_type']['select']['name'] .'_0' ;?>" tabindex="<?php //echo $vtprd_deal_screen_framework['discount_rule_max_amt_type']['select']['tabindex'] .'_0' ; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['discount_rule_max_amt_type']['option']); $i++) {
                          $this->vtprd_change_title_currency_symbol('discount_rule_max_amt_type', $i, $currency_symbol); 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['discount_rule_max_amt_type']['option'][$i]['id'] .'_0' ;?>"  class="<?php echo $vtprd_deal_screen_framework['discount_rule_max_amt_type']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['discount_rule_max_amt_type']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['discount_rule_max_amt_type']['option'][$i]['value']  == $vtprd_rule->rule_deal_info[0]['discount_rule_max_amt_type']  )  { echo $selected; } // use '0' deal_info_line...?> >  <?php echo $vtprd_deal_screen_framework['discount_rule_max_amt_type']['option'][$i]['title']; ?> </option>
                 <?php } ?> 
                </select> 
                
                
                <span class="amt-field  " id="discount_rule_max_amt_count_area">
                 <input id="<?php echo $vtprd_deal_screen_framework['discount_rule_max_amt_count']['id'] .'_0' ?>" class="<?php echo $vtprd_deal_screen_framework['discount_rule_max_amt_count']['class']; ?>  limit-count" type="<?php echo $vtprd_deal_screen_framework['discount_rule_max_amt_count']['type']; ?>" name="<?php echo $vtprd_deal_screen_framework['discount_rule_max_amt_count']['name'] .'_0' ;?>" value="<?php echo $vtprd_rule->rule_deal_info[0]['discount_rule_max_amt_count']; // use '0' deal_info_line...?>" />
                </span>
                        
               <span class="shortIntro  shortIntro2" >
                  <em>
                  <?php _e('Limit by Cart - by Count or', 'vtprd');?>
                  </em><br>
                  <em>
                  <?php _e('Discount $$ value or % value', 'vtprd');?>
                  </em>                   
                &nbsp;
                  <img  class="hasHoverHelp2" width="11px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                  <?php vtprd_show_object_hover_help ('discount_rule_max_amt_type', 'small') ?>
               </span>                                  
            </span>

           <?php //while the 2 max_amt fields above are kluged onto the deal_screen_framework, the msg field is on the rule proper ?>
           <span class="text-field  clear-left" id="discount_rule_max_amt_msg">
             <span class="data-line-indent">&nbsp;</span>
             <span class="left-column">
                 <span class="text-field-label" id="discount_rule_max_amt_msg_label"> <?php _e('Short Message When Max Applied (opt) ', 'vtprd');?> </span>
                  <?php vtprd_show_help_tooltip($context = 'discount_rule_max_amt_msg'); ?>
             </span>
             <textarea rows="1" cols="100" id="<?php echo $vtprd_rule_display_framework['discount_rule_max_amt_msg']['id']; ?>" class="<?php echo $vtprd_rule_display_framework['discount_rule_max_amt_msg']['class']; ?> right-column" type="<?php echo $vtprd_rule_display_framework['discount_rule_max_amt_msg']['type']; ?>" name="<?php echo $vtprd_rule_display_framework['discount_rule_max_amt_msg']['name']; ?>" ><?php echo $vtprd_rule->discount_rule_max_amt_msg; ?></textarea>
           </span>           
        </div>     
  
            <div class="screen-box  screen-box2  dropdown discount_rule_cum_max_amt_type discount_rule_cum_max_amt_type_box clear-left" id="discount_rule_cum_max_amt_type_box_0">  
                 <?php
                     /* ***********************
                     special handling for  discount_rule_cum_max_amt_type, discount_rule_cum_max_amt_type.  Even though they appear iteratively in deal info,
                     they are only active on the '0' occurrence line.  further, they are displayed only AFTER all of the deal lines are displayed
                     onscreen... This is actually a kluge, done to utilize the complete editing already available in the deal info loop for a  dropdown and an associated amt field.
                     *********************** */
                 
                   //Both _label fields have trailing '_0', as edits are actually handled in the discount info loop ?>          
                <span class="left-column">
                    <span class="title  hasWizardHelpRight" >
                      <span class="title-anchors" id="discount_rule_cum_max_title_0" ><?php _e('Product Limit', 'vtprd');?></span>
                    </span> 
                    <?php vtprd_show_object_hover_help ('discount_rule_cum_max_amt_type', 'wizard') ?>      
                </span>
                
                <span class="dropdown right-column" id="discount_rule_cum_max_dropdown">                                                         
                   
                   <select id="<?php echo $vtprd_deal_screen_framework['discount_rule_cum_max_amt_type']['select']['id'] .'_0' ;?>" class="<?php echo$vtprd_deal_screen_framework['discount_rule_cum_max_amt_type']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['discount_rule_cum_max_amt_type']['select']['name'] .'_0' ;?>" tabindex="<?php //echo $vtprd_deal_screen_framework['discount_rule_cum_max_amt_type']['select']['tabindex'] .'_0' ; ?>" >          
                     <?php
                     for($i=0; $i < sizeof($vtprd_deal_screen_framework['discount_rule_cum_max_amt_type']['option']); $i++) { 
                              $this->vtprd_change_title_currency_symbol('discount_rule_cum_max_amt_type', $i, $currency_symbol);             
                     ?>                             
                        <option id="<?php echo $vtprd_deal_screen_framework['discount_rule_cum_max_amt_type']['option'][$i]['id'] .'_0' ;?>"  class="<?php echo $vtprd_deal_screen_framework['discount_rule_cum_max_amt_type']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['discount_rule_cum_max_amt_type']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['discount_rule_cum_max_amt_type']['option'][$i]['value']  == $vtprd_rule->rule_deal_info[0]['discount_rule_cum_max_amt_type']  )  { echo $selected; } // use '0' deal_info_line...?> >  <?php echo $vtprd_deal_screen_framework['discount_rule_cum_max_amt_type']['option'][$i]['title']; ?> </option>
                     <?php } ?> 
                   </select>
                   
                    
                   <span class="amt-field" id="discount_rule_cum_max_amt_count_area">
              
                     <input id="<?php echo $vtprd_deal_screen_framework['discount_rule_cum_max_amt_count']['id'] .'_0' ?>" class="<?php echo $vtprd_deal_screen_framework['discount_rule_cum_max_amt_count']['class']; ?>  limit-count" type="<?php echo $vtprd_deal_screen_framework['discount_rule_cum_max_amt_count']['type']; ?>" name="<?php echo $vtprd_deal_screen_framework['discount_rule_cum_max_amt_count']['name'] .'_0' ;?>" value="<?php echo $vtprd_rule->rule_deal_info[0]['discount_rule_cum_max_amt_count']; // use '0' deal_info_line...?>" />
                   </span>
                        
                   <span class="shortIntro  shortIntro2" >
                      <em>
                      <?php _e('Limit by Product - by Count or', 'vtprd');?>
                      </em><br>
                      <em>
                      <?php _e('Discount $$ value or % value', 'vtprd');?>
                      </em>                   
                    &nbsp;
                      <img  class="hasHoverHelp2" width="11px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                      <?php vtprd_show_object_hover_help ('discount_rule_max_amt_type', 'small') ?>
                   </span>                                
                </span>
               <span class="text-field  clear-left" id="discount_rule_cum_max_amt_msg">
                 <span class="data-line-indent">&nbsp;</span>
                 <span class="text-field-label" id="discount_rule_cum_max_amt_msg_label"> <?php _e('Short Message When Max Applied (opt) ', 'vtprd');?> </span>
                  <?php vtprd_show_help_tooltip($context = 'discount_rule_cum_max_amt_msg'); ?>
                 <textarea rows="1" cols="100" id="<?php echo $vtprd_rule_display_framework['discount_rule_cum_max_amt_msg']['id']; ?>" class="<?php echo $vtprd_rule_display_framework['discount_rule_cum_max_amt_msg']['class']; ?>" type="<?php echo $vtprd_rule_display_framework['discount_rule_cum_max_amt_msg']['type']; ?>" name="<?php echo $vtprd_rule_display_framework['discount_rule_cum_max_amt_msg']['name']; ?>" ><?php echo $vtprd_rule->discount_rule_cum_max_amt_msg; ?></textarea>
               </span>
            </div>                
          
      </div> <?php //end maximums_box box ?>                      
      
      <span class="box-border-line">&nbsp;</span>             

      <div class="screen-box" id="cumulativePricing_box">     
          <span class="title" id="cumulativePricing_title" >
            <img class="working_together_icon" src="<?php echo VTPRD_URL;?>/admin/images/tab-icons.png" width="1" height="1" />                                                        
            <a id="cumulativePricing_title_anchor" class="section-headings first-level-title" href="javascript:void(0);"><?php _e('Discount Works Together With:', 'vtprd');?></a>
          </span>
          
          <div class="clear-left" id="cumulativePricing_dropdown">       
            <div class="screen-box dropdown cumulativeRulePricing_area clear-left" id="cumulativeRulePricing_areaID"> 
               
               <span class="left-column  left-column-less-padding-top">
                  <span class="title  hasWizardHelpRight" >
                    <span class="cumulativeRulePricing_lit" id="cumulativeRulePricing_label"><?php _e('Other', 'vtprd'); echo '&nbsp;<br>';  _e('Rule Discounts', 'vtprd');?></span>
                  </span> 
                  <?php vtprd_show_object_hover_help ('cumulativeRulePricing', 'wizard') ?>    
               </span>
               
               <span class="right-column">
                   <span class="column-width50"> 
                     <select id="<?php echo $vtprd_rule_display_framework['cumulativeRulePricing']['select']['id'];?>" class="<?php echo$vtprd_rule_display_framework['cumulativeRulePricing']['select']['class']; ?>" name="<?php echo $vtprd_rule_display_framework['cumulativeRulePricing']['select']['name'];?>" tabindex="<?php //echo $vtprd_rule_display_framework['cumulativeRulePricing']['select']['tabindex']; ?>" >          
                       <?php
                       for($i=0; $i < sizeof($vtprd_rule_display_framework['cumulativeRulePricing']['option']); $i++) { 
                       ?>                             
                          <option id="<?php echo $vtprd_rule_display_framework['cumulativeRulePricing']['option'][$i]['id']; ?>"  class="<?php echo $vtprd_rule_display_framework['cumulativeRulePricing']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_rule_display_framework['cumulativeRulePricing']['option'][$i]['value']; ?>"   <?php if ($vtprd_rule_display_framework['cumulativeRulePricing']['option'][$i]['value'] == $vtprd_rule->cumulativeRulePricing )  { echo $selected; } ?> >  <?php echo $vtprd_rule_display_framework['cumulativeRulePricing']['option'][$i]['title']; ?> </option>
                       <?php } ?> 
                     </select>
                     
                     
                     <span class="" id="priority_num">   <?php //only display if multiple rule discounts  ?>
                       <span class="text-field" id="ruleApplicationPriority_num">
                         <span class="text-field-label" id="ruleApplicationPriority_num_label"> <?php _e('Priority', 'vtprd');//_e('Rule Priority Sort Number:', 'vtprd');?> </span>
                         <input id="<?php echo $vtprd_rule_display_framework['ruleApplicationPriority_num']['id']; ?>" class="<?php echo $vtprd_rule_display_framework['ruleApplicationPriority_num']['class']; ?>" type="<?php echo $vtprd_rule_display_framework['ruleApplicationPriority_num']['type']; ?>" name="<?php echo $vtprd_rule_display_framework['ruleApplicationPriority_num']['name']; ?>" value="<?php echo $vtprd_rule->ruleApplicationPriority_num; ?>" />
                       </span>
                     </span>
                   </span>           
                   <span class="shortIntro  shortIntro2" >
                      <em>
                      <?php _e('Does this Rule apply its discount', 'vtprd');?>
                      </em><br>
                      <em>
                      <?php _e('in addition to other Rules?', 'vtprd');?>
                      </em>                   
                    &nbsp;
                      <img  class="hasHoverHelp2" width="11px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                      <?php vtprd_show_object_hover_help ('cumulativeRulePricing', 'small') ?>
                   </span>                                   
               </span> 
                            
            </div>
    
            <div class="screen-box dropdown cumulativeCouponPricing_area clear-left" id="cumulativeCouponPricing_0">              
               <span class="left-column  left-column-less-padding-top">
                  <span class="title  hasWizardHelpRight" >
                    <span class="cumulativeRulePricing_lit" id="cumulativeCouponPricing_label"><?php _e('Other <br>Coupon Discounts', 'vtprd');//_e('Apply this Rule Discount ', 'vtprd'); echo '&nbsp;&nbsp;';  _e('in Addition to Coupon Discount : &nbsp;', 'vtprd');?></span>
                  </span> 
                  <?php vtprd_show_object_hover_help ('cumulativeCouponPricing', 'wizard') ?>  
               </span>
               <span class="right-column">
                   <span class="column-width50"> 
                     <select id="<?php echo $vtprd_rule_display_framework['cumulativeCouponPricing']['select']['id'];?>" class="<?php echo$vtprd_rule_display_framework['cumulativeCouponPricing']['select']['class']; ?>" name="<?php echo $vtprd_rule_display_framework['cumulativeCouponPricing']['select']['name'];?>" tabindex="<?php //echo $vtprd_rule_display_framework['cumulativeCouponPricing']['select']['tabindex']; ?>" >          
                       <?php
                       for($i=0; $i < sizeof($vtprd_rule_display_framework['cumulativeCouponPricing']['option']); $i++) { 
                       ?>                             
                          <option id="<?php echo $vtprd_rule_display_framework['cumulativeCouponPricing']['option'][$i]['id']; ?>"  class="<?php echo $vtprd_rule_display_framework['cumulativeCouponPricing']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_rule_display_framework['cumulativeCouponPricing']['option'][$i]['value']; ?>"   <?php if ($vtprd_rule_display_framework['cumulativeCouponPricing']['option'][$i]['value'] == $vtprd_rule->cumulativeCouponPricing )  { echo $selected; } ?> >  <?php echo $vtprd_rule_display_framework['cumulativeCouponPricing']['option'][$i]['title']; ?> </option>
                       <?php } ?> 
                     </select>
                     
                   </span>           
                   <span class="shortIntro  shortIntro2" >
                      <em>
                      <?php _e('Does this Rule apply its discount', 'vtprd');?>
                      </em><br>
                      <em>
                      <?php _e('in addition to other Coupons?', 'vtprd');?>
                      </em>                   
                    &nbsp;
                      <img  class="hasHoverHelp2" width="11px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                     <?php vtprd_show_object_hover_help ('cumulativeCouponPricing', 'small') ?>
                   </span>                               

               </span> 
            </div>
                 
            <div class="screen-box dropdown cumulativeSalePricing_area clear-left" id="cumulativeSalePricing_areaID">              
               <span class="left-column  left-column-less-padding-top">
                   <span class="title  hasWizardHelpRight" >
                     <span class="cumulativeRulePricing_lit" id="cumulativeSalePricing_label"><?php _e('Product', 'vtprd'); echo '&nbsp;<br>'; _e('Sale Pricing', 'vtprd');?></span>
                   </span> 
                   <?php vtprd_show_object_hover_help ('cumulativeSalePricing', 'wizard') ?>                
               </span>
               <span class="right-column">
                   
                   <select id="<?php echo $vtprd_rule_display_framework['cumulativeSalePricing']['select']['id'];?>" class="<?php echo$vtprd_rule_display_framework['cumulativeSalePricing']['select']['class']; ?>" name="<?php echo $vtprd_rule_display_framework['cumulativeSalePricing']['select']['name'];?>" tabindex="<?php //echo $vtprd_rule_display_framework['cumulativeSalePricing']['select']['tabindex']; ?>" >          
                     <?php
                     for($i=0; $i < sizeof($vtprd_rule_display_framework['cumulativeSalePricing']['option']); $i++) { 
                     ?>                             
                        <option id="<?php echo $vtprd_rule_display_framework['cumulativeSalePricing']['option'][$i]['id']; ?>"  class="<?php echo $vtprd_rule_display_framework['cumulativeSalePricing']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_rule_display_framework['cumulativeSalePricing']['option'][$i]['value']; ?>"   <?php if ($vtprd_rule_display_framework['cumulativeSalePricing']['option'][$i]['value'] == $vtprd_rule->cumulativeSalePricing )  { echo $selected; } ?> >  <?php echo $vtprd_rule_display_framework['cumulativeSalePricing']['option'][$i]['title']; ?> </option>
                     <?php } ?> 
                   </select> 
                   
                        
                   <span class="shortIntro  shortIntro2 shortIntro3" >
                      <em>
                      <?php _e('Does this Rule discount apply at all,', 'vtprd');?>
                      </em><br>
                      <em>
                      <?php _e('over top or in place of Sale Price?', 'vtprd');?>
                      </em>                   
                    &nbsp;
                      <img  class="hasHoverHelp2" width="11px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                      <?php vtprd_show_object_hover_help ('cumulativeSalePricing', 'small') ?>
                   </span>                                                 
               </span>
               <?php if (VTPRD_PARENT_PLUGIN_NAME == 'WP E-Commerce') { vtprd_show_help_tooltip($context = 'cumulativeSalePricingLimitation');  } ?> 
            </div>
          </div>  <?php //end cumulativeRulePricing_dropdown ?>  
       </div> <?php //end cumulativePricing box ?>  

      </div> <?php //end advanced-data-area ?>
            
      </div> <?php //lower-screen-wrapper ?>
      
      <?php 
          
    //lots of selects change their values between standard and 'discounted' titles.
    //This is where we supply the HIDEME alternative titles
    $this->vtprd_print_alternative_title_selects();  
    
//echo '$vtprd_rule= <pre>'.print_r($vtprd_rule, true).'</pre>' ; 
         
  }  //end vtprd_deal
      
   public    function vtprd_buy_action_groups() {      
       $this->vtprd_buy_group_cntl();
       $this->vtprd_action_group_cntl();                
}
      
  
    public    function vtprd_buy_group_cntl() {   
       global $post, $vtprd_info, $vtprd_rule, $vtprd_rule_display_framework, $vtprd_rules_set;
       $selected = 'selected="selected"';
       $checked = 'checked="checked"';  
     ?>
                          
        <span class="amt-field" id="singleChoiceIn-span">                                  
          <span class="amt-field-label" id="singleProdID-in-label"><span class="showBuyAsBuy"><?php _e('Buy Product ID Number', 'vtprd');?></span><span class="showBuyAsDiscount"><?php _e('Discount Product ID Number', 'vtprd');?></span></span>
          <input id="<?php echo $vtprd_rule_display_framework['inPop_singleProdID']['id']; ?>" class="<?php echo $vtprd_rule_display_framework['inPop_singleProdID']['class']; ?>" type="<?php echo $vtprd_rule_display_framework['inPop_singleProdID']['type']; ?>" name="<?php echo $vtprd_rule_display_framework['inPop_singleProdID']['name']; ?>" value="<?php echo $vtprd_rule->inPop_singleProdID; ?>" />
          <?php vtprd_show_help_tooltip($context = 'pop-prod-id', $location = 'title'); ?>
          
          <?php if ($vtprd_rule->inPop_singleProdID['value'] > ' ' ) { ?>           
              <span class="" id="singleProdID-in-name-area">
                <span class="amt-field-label" id="singleProdID-in-name-label"><?php _e('Product Name', 'vtprd');?></span>
                <span id="singleProdID-in-name" ><?php echo $vtprd_rule->inPop_singleProdID_name; ?></span>
              </span>
          <?php } ?>                                                     
        </span> 
               
         
        <div id="inPop-varProdID-cntl">            

          <div id="inPopVarBox">
              <h3 id="inPopVarBox_label"><?php _e('Enter Product ID', 'vtprd');?>
                  <?php vtprd_show_help_tooltip($context = 'pop-prod-id', $location = 'title'); ?>
              </h3>
              <div id="inPopVarProduct">                                    
                  <input id="<?php echo $vtprd_rule_display_framework['inPop_varProdID']['id']; ?>" class="<?php echo $vtprd_rule_display_framework['inPop_varProdID']['class']; ?>" type="<?php echo $vtprd_rule_display_framework['inPop_varProdID']['type']; ?>" name="<?php echo $vtprd_rule_display_framework['inPop_varProdID']['name']; ?>" value="<?php echo $vtprd_rule->inPop_varProdID; ?>" />
                 <br />                            
              </div>
              <div id="inPopVarButton">
                 <?php
                    if ($vtprd_rule->inPop_varProdID) {
                      $product_ID = $vtprd_rule->inPop_varProdID; 
                      $product_variation_IDs = vtprd_get_variations_list($product_ID);
                    }  else {
                      $product_variation_IDs = array();  //v1.0.5
                    }
                    /* ************************************************
                    **   Get Variations Button for Rule screen
                    *     ==>>> get the product id from $_REQUEST['varProdID'];  in the receiving ajax routine. 
                    ************************************************ */                     
                 ?>
                                                        
                 <div class="inPopVar-loading-animation">
										<img title="Loading" alt="Loading" src="<?php echo VTPRD_URL;?>/admin/images/indicator.gif" />
										<?php _e('Getting Variations ...', 'vtprd'); ?>
								 </div>
                 
                 
                 <a id="ajaxVariationIn" href="javascript:void(0);">
                    <?php if ($product_ID > ' ') {   ?>
                      <?php _e('Refresh Variations', 'vtprd');?>                      
                    <?php } else {   ?>
                      <?php _e('Get Variations', 'vtprd');?> 
                    <?php } ?>
                  </a>
                 
              </div>
          </div>
          <div id="variations-in">
          <?php              
/*
           echo '$product_variation_IDs= '.$product_variation_IDs.'<br>' ;
           echo '$product_variation_IDs= '.$product_variation_IDs.'<br>' ;
           echo '$vtprd_rule <pre>'.print_r($vtprd_rule, true).'</pre>' ; 
*/           
           if ($product_variation_IDs) { //if product still has variations, expose them here
           ?>
              <h3><?php _e('Product Variations', 'vtprd');?></h3>                  
            <?php
              //********************************
              $this->vtprd_post_category_meta_box($post, array( 'args' => array( 'taxonomy' => 'variations', 'tax_class' => 'var-in', 'checked_list' => $vtprd_rule->var_in_checked, 'pop_in_out_sw' => 'in', 'product_ID' => $product_ID, 'product_variation_IDs' => $product_variation_IDs )));
              // ********************************                            
            }                               
          ?>
            <?php //output hidden count of all variation checkboxes.  Used on update to store info used in 'yousave' messaging?>
            <input type="hidden" id="checkbox_count-var-in" name="checkbox_count-var-in" value="<?php echo $vtprd_info['inpop_variation_checkbox_total']; ?>" />
           </div>  <?php //end variations-in ?>
        </div>  <?php //end inPopVarProdID ?> 

        <div class="" id="vtprd-pop-in-groups-cntl">             
        <div class="<?php //echo $groupPop_vis ?> " id="vtprd-pop-in-cntl">                                                           
        <div  class="clear-left" id="prodcat-in">
          <h3 id="prodcat-in-label"><?php _e('Product Categories', 'vtprd');?></h3>
          
          <?php
          // ********************************
          $this->vtprd_post_category_meta_box($post, array( 'args' => array( 'taxonomy' => $vtprd_info['parent_plugin_taxonomy'], 'tax_class' => 'prodcat-in', 'checked_list' => $vtprd_rule->prodcat_in_checked, 'pop_in_out_sw' => 'in')));
          // ********************************
          ?>
        
        </div>  <?php //end prodcat-in ?>
        <h4 class="and-or" id="and-or-in-label"><?php _e('Or', 'vtprd') //('And / Or', 'vtprd');?></h4>
        <div id="rulecat-in">
          <h3 id="rulecat-in-label"><?php _e('Pricing Deals Categories', 'vtprd');?></h3>
          
          <?php
          // ********************************
          $this->vtprd_post_category_meta_box($post, array( 'args' => array( 'taxonomy' => $vtprd_info['rulecat_taxonomy'], 'tax_class' => 'rulecat-in', 'checked_list' => $vtprd_rule->rulecat_in_checked , 'pop_in_out_sw' => 'in' )));
          // ********************************
          ?> 
                         
        </div>  <?php //end rulecat-in ?>
        
        
        <div id="and-or-role-div">
          <?php
           for($i=0; $i < sizeof($vtprd_rule_display_framework['role_and_or_in']); $i++) { 
           ?>                               
              <input id="<?php echo $vtprd_rule_display_framework['role_and_or_in'][$i]['id']; ?>" class="<?php echo $vtprd_rule_display_framework['role_and_or_in'][$i]['class']; ?>" type="<?php echo $vtprd_rule_display_framework['role_and_or_in'][$i]['type']; ?>" name="<?php echo $vtprd_rule_display_framework['role_and_or_in'][$i]['name']; ?>" value="<?php echo $vtprd_rule_display_framework['role_and_or_in'][$i]['value']; ?>" <?php if ( $vtprd_rule_display_framework['role_and_or_in'][$i]['value'] == $vtprd_rule->role_and_or_in) { echo $checked; } ?>    /><span id="<?php echo $vtprd_rule_display_framework['role_and_or_in'][$i]['id'] . '-label'; ?>"> <?php echo $vtprd_rule_display_framework['role_and_or_in'][$i]['label']; ?></span><br /> 
           <?php } 
           //if neither 'and' nor 'or' selected, select 'or'
         /*  if ( (!$vtprd_rule_display_framework['role_and_or_in'][0]['user_input'] == 's') && (!$vtprd_rule_display_framework['role_and_or_in'][1]['user_input'] == 's') )   {
               $vtprd_rule_display_framework['role_and_or_in'][1]['user_input'] = 's';
           }   */
                      
           ?>                 
          </div>
        
        <?php //v1.1  div role-in moved from here?>
        
       </div> <?php //end vtprd-pop-in-groups-cntl ?>

         
       </div> <?php //end vtprd-pop-in-cntl ?>  
       
       
      <?php //v1.1  div role-in moved here to apply to categories, products and to variations!!  also 'optional' titles added?>       
      <div id="role-in">
        <h3 id="role-in-label">
          <span class="role-in-label-optional"><?php _e('- And -', 'vtprd');?> &nbsp;&nbsp;&nbsp;</span>
          <?php _e('Logged-in Role', 'vtprd');?> 
          <span class="role-in-label-optional">&nbsp;&nbsp;&nbsp;<em><?php _e('(optional)', 'vtprd');?> </em></span></h3>
        
        <?php
        // ********************************
        $this->vtprd_post_category_meta_box($post, array( 'args' => array( 'taxonomy' => 'roles', 'tax_class' => 'role-in', 'checked_list' => $vtprd_rule->role_in_checked,  'pop_in_out_sw' => ' '  )));     //v1.0.7.9 'pop_in_out_sw' => ' '
        // ********************************
        ?>
      </div>

             
    <?php
 
}
      

                                                                            
    public    function vtprd_action_group_cntl() { 
       global $post, $vtprd_info, $vtprd_rule, $vtprd_rule_display_framework, $vtprd_rules_set;
       $selected = 'selected="selected"';
       $checked = 'checked="checked"';       
    ?>                                             

        <span class="amt-field" id="singleChoiceOut-span">                                  
          <span class="amt-field-label" id="singleProdID-out-label"><?php _e('Discount Product ID Number', 'vtprd');?></span>                    
            <input id="<?php echo $vtprd_rule_display_framework['actionPop_singleProdID']['id']; ?>" class="<?php echo $vtprd_rule_display_framework['actionPop_singleProdID']['class']; ?>" type="<?php echo $vtprd_rule_display_framework['actionPop_singleProdID']['type']; ?>" name="<?php echo $vtprd_rule_display_framework['actionPop_singleProdID']['name']; ?>" value="<?php echo $vtprd_rule->actionPop_singleProdID; ?>" />
            <?php vtprd_show_help_tooltip($context = 'pop-prod-id', $location = 'title'); ?>
          
          <?php if ($vtprd_rule->actionPop_singleProdID['value'] > ' ' ) { ?>           
              <span class="" id="singleProdID-out-name-area">
                <span class="amt-field-label"  id="singleProdID-out-name-label"><?php _e('Product Name', 'vtprd'); ?></span>
                <span id="singleProdID-out-name" ><?php echo $vtprd_rule->actionPop_singleProdID_name; ?></span>
              </span>
          <?php } ?>                                                
        </span>
          
        <!-- </div> -->       

         
        <div id="actionPop-varProdID-cntl">            

          <div id="actionPopVarBox">
              <h3 id="actionPopVarBox_label"><?php _e('Enter Product ID', 'vtprd');?>
                  <?php vtprd_show_help_tooltip($context = 'pop-prod-id', $location = 'title'); ?>
              </h3>
              <div id="actionPopVarProduct">                  
                  <input id="<?php echo $vtprd_rule_display_framework['actionPop_varProdID']['id']; ?>" class="<?php echo $vtprd_rule_display_framework['actionPop_varProdID']['class']; ?>" type="<?php echo $vtprd_rule_display_framework['actionPop_varProdID']['type']; ?>" name="<?php echo $vtprd_rule_display_framework['actionPop_varProdID']['name']; ?>" value="<?php echo $vtprd_rule->actionPop_varProdID; ?>" />
                 <br />                            
              </div>
              <div id="actionPopVarButton">
                 <?php
                    if ($vtprd_rule->actionPop_varProdID) {
                      $product_ID = $vtprd_rule->actionPop_varProdID; 
                      $product_variation_IDs = vtprd_get_variations_list($product_ID);
                    }   else {                           
                      $product_variation_IDs = array();  //v1.0.5
                    }                                    
                    /* ************************************************
                    **   Get Variations Button for Rule screen
                    *     ==>>> get the product id from $_REQUEST['varProdID'];  in the receiving ajax routine. 
                    ************************************************ */                     
                 ?>
                                                        
                 <div class="actionPopVar-loading-animation">
										<img title="Loading" alt="Loading" src="<?php echo VTPRD_URL;?>/admin/images/indicator.gif" />
										<?php _e('Getting Variations ...', 'vtprd'); ?>
								 </div>
                 
                 
                 <a id="ajaxVariationOut" href="javascript:void(0);">
                    <?php if ($product_ID > ' ') {   ?>
                      <?php _e('Refresh Variations', 'vtprd');?>                      
                    <?php } else {   ?>
                      <?php _e('Get Variations', 'vtprd');?> 
                    <?php } ?>
                  </a>
                 
              </div>
          </div>
          <div id="variations-out">
          <?php              
           if ($product_variation_IDs) { //if product still has variations, expose them here
           ?>
              <h3><?php _e('Product Variations', 'vtprd');?></h3>                  
            <?php
              //********************************
              $this->vtprd_post_category_meta_box($post, array( 'args' => array( 'taxonomy' => 'variations', 'tax_class' => 'var-out', 'checked_list' => $vtprd_rule->var_out_checked, 'pop_in_out_sw' => 'out', 'product_ID' => $product_ID, 'product_variation_IDs' => $product_variation_IDs )));
              // ********************************
            }                               
          ?>
           </div>  <?php //end variations-out ?>
        </div>  <?php //end actionPopVarProdID ?> 
        
 
        <div class="" id="vtprd-pop-out-cntl">                                                  
    
        <div class="clear-left" id="prodcat-out">
          <h3 id="prodcat-out-label"><?php _e('Product Categories', 'vtprd');?></h3>
          
          <?php
          // ********************************
          $this->vtprd_post_category_meta_box($post, array( 'args' => array( 'taxonomy' => $vtprd_info['parent_plugin_taxonomy'], 'tax_class' => 'prodcat-out', 'checked_list' => $vtprd_rule->prodcat_out_checked, 'pop_in_out_sw' => 'out')));
          // ********************************
          ?>
        
        </div>  <?php //end prodcat-out ?>
        <h4 class="and-or"><?php _e('Or', 'vtprd') //('And / Or', 'vtprd');?></h4>
        <div id="rulecat-out">
          <h3 id="rulecat-out-label"><?php _e('Pricing Deals Categories', 'vtprd');?></h3>
          
          <?php
          // ********************************
          $this->vtprd_post_category_meta_box($post, array( 'args' => array( 'taxonomy' => $vtprd_info['rulecat_taxonomy'], 'tax_class' => 'rulecat-out', 'checked_list' => $vtprd_rule->rulecat_out_checked , 'pop_in_out_sw' => 'out')));
          // ********************************
          ?> 
          
          <?php
            /*
            REMOVED and/or  ROLES for action area =>SUPERFLOUS
            */
          ?>
          
                         
        </div>  <?php //end rulecat-out ?>
        

      </div> <?php //end vtprd-pop-out-cntl ?> 
  
  <?php
    }  
      
  
    public    function vtprd_pop_in_specifics( ) {                     
       global $post, $vtprd_info, $vtprd_rule; $vtprd_rules_set;
       $checked = 'checked="checked"';  
  ?>
        
       <div class="column1" id="specDescrip">
          <h4><?php _e('How is the Rule applied to the search results?', 'vtprd');?></h4>
          <p><?php _e("Once we've figured out the population we're working on (cart only or specified groups),
          how do we apply the rule?  Do we look at each product individually and apply the rule to
          each product we find?  Or do we look at the population as a group, and apply the rule to the
          group as a tabulated whole?  Or do we apply the rule to any we find, and limit the application 
          of the rule to a certain number of products?", 'vtprd');?>           
          </p>
       </div>
       <div class="column2" id="specChoiceIn">
          <h3><?php _e('Select Rule Application Method', 'vtprd');?></h3>
          <div id="specRadio">
            <span id="Choice-input-span">
                <?php
               for($i=0; $i < sizeof($vtprd_rule->specChoice_in); $i++) { 
               ?>                 

                  <input id="<?php echo $vtprd_rule->specChoice_in[$i]['id']; ?>" class="<?php echo $vtprd_rule->specChoice_in[$i]['class']; ?>" type="<?php echo $vtprd_rule->specChoice_in[$i]['type']; ?>" name="<?php echo $vtprd_rule->specChoice_in[$i]['name']; ?>" value="<?php echo $vtprd_rule->specChoice_in[$i]['value']; ?>" <?php if ( $vtprd_rule->specChoice_in[$i]['user_input'] > ' ' ) { echo $checked; } ?> /><?php echo $vtprd_rule->specChoice_in[$i]['label']; ?><br />

               <?php
                }
               ?>  
            </span>
            <span class="" id="anyChoiceIn-span">
                <span><?php _e('*Any* applies to a *required*', 'vtprd');?></span><br />
                 <?php _e('Maximum of:', 'vtprd');?>                      
                 <input id="<?php echo $vtprd_rule->anyChoiceIn_max['id']; ?>" class="<?php echo $vtprd_rule->anyChoiceIn_max['class']; ?>" type="<?php echo $vtprd_rule->anyChoiceIn_max['type']; ?>" name="<?php echo $vtprd_rule->anyChoiceIn_max['name']; ?>" value="<?php echo $vtprd_rule->anyChoiceIn_max['value']; ?>" />
                 <?php _e('Products', 'vtprd');?>
            </span>           
          </div>                
       </div>                                                
       <div class="column3 specExplanation" id="allChoiceIn-chosen">
          <h4><?php _e('Treat the Selected Group as a Single Entity', 'vtprd');?><span> - <?php _e('explained', 'vtprd');?></span></h4>
          <p><?php _e("Using *All* as your method, you choose to look at all the products from your cart search results.  That means we add
          all the quantities and/or price across all relevant products in the cart, to test against the rule's requirements.", 'vtprd');?>           
          </p>
       </div>
       <div class="column3 specExplanation" id="eachChoiceIn-chosen">
          <h4><?php _e('Each in the Selected Group', 'vtprd');?><span> - <?php _e('explained', 'vtprd');?></span></h4>
          <p><?php _e("Using *Each* as your method, we apply the rule to each product from your cart search results.
          So if any of these products fail to meet the rule's requirements, the cart as a whole receives an error message.", 'vtprd');?>           
          </p>
       </div>
       <div class="column3 specExplanation" id="anyChoiceIn-chosen">
          <h4><?php _e('Apply the rule to any Individual Product in the Cart', 'vtprd');?><span> - <?php _e('explained', 'vtprd');?></span></h4>
          <p><?php _e("Using *Any*, we can apply the rule to any product in the cart from your cart search results, similar to *Each*.  However, there is a
          maximum number of products to which the rule is applied. The product group is checked to see if any of the group fail to reach the maximum amount
          threshhold.  If so, the error will be applied to products in the cart based on cart order, up to the maximum limit supplied.", 'vtprd');?>
          <br /> <br /> 
          <?php _e('For example, the rule might be something like:', 'vtprd');?>
          <br /> <br /> &nbsp;&nbsp;
          <?php _e('"You may buy a maximum of $10 for each of any of 2 products from this group."', 'vtprd');?>              
          </p>               
       </div> 
      
    <?php
  }  
                                                                           
    public    function vtprd_rule_id() {          
        global $post, $vtprd_rule;           
       
        if ($vtprd_rule->ruleInWords > ' ') { ?>
            <span class="ruleInWords" >              
               <span class="clear-left">  <?php echo $vtprd_rule->ruleInWords; ?></span><!-- /clear-left -->                              
            </span><!-- /ruleInWords -->              
        <?php } //end ruleInWords 
  } 
  
    public    function vtprd_rule_resources() {          
        echo '<a id="vtprd-rr-doc"  href="' . VTPRD_DOCUMENTATION_PATH . '"  title="Access Plugin Documentation">' . __('Plugin', 'vtprd'). '<br>' . __('Documentation', 'vtprd'). '</a>';
        //Back to the Top box, fixed at lower right corner!!!!!!!!!!
        echo '<a href="#" id="back-to-top-tab" class="show-tab">' . __('Back to Top', 'vtprd'). ' <strong>&uarr;</strong></a>';
  }   

      
    public    function vtprd_rule_scheduling() {             //periodicByDateRange
        global $vtprd_rule;
        
        //**********************************************************************************
        //script goes here, rather than in enqueued resources, due to timing issues 
        //**********************************************************************************
       ?>     
          <script type="text/javascript">
          jQuery.noConflict();
          jQuery(document).ready(function($) {
             //DatePicker                       
             // from  http://jquerybyexample.blogspot.com/2012/01/end-date-should-not-be-greater-than.html
                $("#date-begin-0").datepicker({
                  dateFormat : 'yy-mm-dd', 
                  minDate: 0,
                 // maxDate: "+60D",
                  numberOfMonths: 2,
                  onSelect: function(selected) {
                    $("#date-end-0").datepicker("option","minDate", selected)
                  }
              });
              $("#date-end-0").datepicker({ 
                  dateFormat : 'yy-mm-dd', 
                  minDate: 0,
                 // maxDate:"+60D",
                  numberOfMonths: 2,
                  onSelect: function(selected) {
                     $("#date-begin-0").datepicker("option","maxDate", selected)
                  }                             
              });

            });   
          </script>                            
     <?php       
     //load up default if no date range
     if ( sizeof($vtprd_rule->periodicByDateRange) == 0 ) {     
        $vtprd_rule->periodicByDateRange[0]['rangeBeginDate'] = date('Y-m-d');
        $vtprd_rule->periodicByDateRange[0]['rangeEndDate']   = (date('Y')+1) . date('-m-d') ;
     } 
     ?> 
        <span class="basic-begin-date-area blue-dropdown"> 
            <label class="begin-date first-in-line-label"><?php _e('Begin Date', 'vtprd');?></label> 
            <input type='text' id='date-begin-0' class='pickdate  clear-left' size='7' value="<?php echo $vtprd_rule->periodicByDateRange[0]['rangeBeginDate']; ?>" name='date-begin-0' readonly="readonly" />				
        </span>        
        <span class="basic-end-date-area blue-dropdown">          
          <label class="end-date first-in-line-label"><?php _e('End Date', 'vtprd');?></label>                      
          <input type='text' id='date-end-0'   class='pickdate   clear-left' size='7' value="<?php echo $vtprd_rule->periodicByDateRange[0]['rangeEndDate']; ?>"   name='date-end-0' readonly="readonly"  />          
        </span>        
        
    <?php      
       global $vtprd_setup_options;
       /* scaring the punters
       if ( $vtprd_setup_options['use_this_timeZone'] == 'none') {
          echo __('<span id="options-setup-error" style="color:red !important;">Scheduling requires setup: <a  href="/wp-admin/edit.php?post_type=vtprd-rule&page=vtprd_setup_options_page"  title="select">Please - Click Here - to Select the Store GMT Time Zone</a></span>', 'vtprd'); 
        }          
       */
  }   

  public  function vtprd_change_title_currency_symbol( $variable_name, $i, $currency_symbol ) {
     global $vtprd_deal_screen_framework;
      //replace $$ with setup currency!!                        
      $vtprd_deal_screen_framework[$variable_name]['option'][$i]['title'] = 
                str_replace('$$', $currency_symbol, $vtprd_deal_screen_framework[$variable_name]['option'][$i]['title'] );
  }    
       
  public  function vtprd_post_category_meta_box( $post, $box ) {
      $defaults = array('taxonomy' => 'category');
      if ( !isset($box['args']) || !is_array($box['args']) )
          $args = array();
      else
          $args = $box['args'];
      extract( wp_parse_args($args, $defaults), EXTR_SKIP );
      $tax = get_taxonomy($taxonomy);

      ?>
      <div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">
   
          <?php //v1.0.7.9  removed popular terms box, unused!! ?>

   
          <div id="<?php echo $taxonomy; ?>-all" class="tabs-panel">
              <?php
              $name = ( $taxonomy == 'category' ) ? 'post_category' : 'tax_input[' .  $tax_class . ']';     //vark replaced $taxonomy with $tax_class
              echo "<input type='hidden' name='{$name}[]' value='0' />"; // Allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
              ?>
              <ul id="<?php echo $taxonomy; ?>checklist" class="list:<?php echo $taxonomy?> categorychecklist form-no-clear">
      <?php    

            switch( $taxonomy ) {
              case 'roles': 
                  $vtprd_checkbox_classes = new VTPRD_Checkbox_classes; 
                  $vtprd_checkbox_classes->vtprd_fill_roles_checklist($tax_class, $checked_list);
                break;
              case 'variations':                  
                  vtprd_fill_variations_checklist($tax_class, $checked_list, $pop_in_out_sw, $product_ID, $product_variation_IDs);                            
                break;
              default:  //product category or vtprd category...
                  $this->vtprd_build_checkbox_contents ($taxonomy, $tax_class, $checked_list, $pop_in_out_sw);                             
                break;
            }
            
      ?>  
              </ul>
          </div>
     <?php //if ( current_user_can($tax->cap->edit_terms) && !($taxonomy == 'roles') && !($taxonomy == 'variations') ): ?>
      <?php if ( !($taxonomy == 'roles') && !($taxonomy == 'variations') ): ?>
              <div id="<?php echo $taxonomy; ?>-adder" class="wp-hidden-children">
                  <h4>
                      <a id="<?php echo $taxonomy; ?>-add-toggle" href="#<?php echo $taxonomy; ?>-add" class="hide-if-no-js" tabindex="3">
                          <?php
                              /* translators: %s: add new taxonomy label */
                              printf( __( '+ %s' ), $tax->labels->add_new_item );
                          ?>
                      </a>
                  </h4>
                  <p id="<?php echo $taxonomy; ?>-add" class="category-add wp-hidden-child">
                      <label class="screen-reader-text" for="new<?php echo $taxonomy; ?>"><?php echo $tax->labels->add_new_item; ?></label>
                      <input type="text" name="new<?php echo $taxonomy; ?>" id="new<?php echo $taxonomy; ?>" class="form-required form-input-tip" value="<?php echo esc_attr( $tax->labels->new_item_name ); ?>" tabindex="3" aria-required="true"/>
                      <label class="screen-reader-text" for="new<?php echo $taxonomy; ?>_parent">
                          <?php echo $tax->labels->parent_item_colon; ?>
                      </label>
                      <?php wp_dropdown_categories( array( 'taxonomy' => $taxonomy, 'hide_empty' => 0, 'name' => 'new'.$taxonomy.'_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => '&mdash; ' . $tax->labels->parent_item . ' &mdash;', 'tab_index' => 3 ) ); ?>
                      <input type="button" id="<?php echo $taxonomy; ?>-add-submit" class="add:<?php echo $taxonomy ?>checklist:<?php echo $taxonomy ?>-add button category-add-sumbit" value="<?php echo esc_attr( $tax->labels->add_new_item ); ?>" tabindex="3" />
                      <?php wp_nonce_field( 'add-'.$taxonomy, '_ajax_nonce-add-'.$taxonomy, false ); ?>
                      <span id="<?php echo $taxonomy; ?>-ajax-response"></span>
                  </p>
              </div>
          <?php endif; ?>
      </div>
      <?php
}


    function vtprd_load_forThePriceOf_literal($k) {
      global $vtprd_rule;
     if (($vtprd_rule->rule_deal_info[$k]['discount_amt_type'] =='forThePriceOf_Units') ||
         ($vtprd_rule->rule_deal_info[$k]['discount_amt_type'] =='forThePriceOf_Currency')) {
        switch ($vtprd_rule->rule_template) {
          case 'C-forThePriceOf-inCart':    //buy-x-action-forThePriceOf-same-group-discount              
              echo ' Buy ';
              echo $vtprd_rule->rule_deal_info[$k]['buy_amt_count'];
            break;
          case 'C-forThePriceOf-Next':  //buy-x-action-forThePriceOf-other-group-discount
              echo ' Get ';
              echo $vtprd_rule->rule_deal_info[$k]['action_amt_count'];
            break;
        }
      }
    }


    //remove conflict with all-in-one seo pack!!  
    //  from http://wordpress.stackexchange.com/questions/55088/disable-all-in-one-seo-pack-for-some-custom-post-types
    function vtprd_remove_all_in_one_seo_aiosp() {
        $cpts = array( 'vtprd-rule' );
        foreach( $cpts as $cpt ) {
            remove_meta_box( 'aiosp', $cpt, 'advanced' );
        }
    }


    
  /*
    *  taxonomy (r) - registered name of taxonomy
    *  tax_class (r) - name options => 'prodcat-in' 'prodcat-out' 'rulecat-in' 'rulecat-out'
    *             refers to product taxonomy on the candidate or action categories,
    *                       rulecat taxonomy on the candidate or action categories
    *                         :: as there are only these 4, they are unique   
    *  checked_list (o) - selection list from previous iteration of rule selection                              
    *                          
   */

  public function vtprd_build_checkbox_contents ($taxonomy, $tax_class, $checked_list = NULL, $pop_in_out_sw ) {
        global $wpdb, $vtprd_info;         
        $sql = "SELECT terms.`term_id`, terms.`name`  FROM `" . $wpdb->prefix . "terms` as terms, `" . $wpdb->prefix . "term_taxonomy` as term_taxonomy WHERE terms.`term_id` = term_taxonomy.`term_id` AND term_taxonomy.`taxonomy` = '" . $taxonomy . "' ORDER BY terms.`term_id` ASC";                         
		    $categories = $wpdb->get_results($sql,ARRAY_A) ;
        
        foreach ($categories as $category) {
            
            $term_id = $category['term_id'];
            
            $output  = '<li id='.$taxonomy.'-'.$term_id.'>' ;
            $output  .= '<label class="selectit  '.$taxonomy.'-list-checkbox">' ;
            $output  .= '<input id="'.$tax_class.'.'.$taxonomy.'-'.$term_id.' " ';
            $output  .= 'type="checkbox" name="tax-input-' .  $tax_class . '[]" ';
            $output  .= 'value="'.$term_id.'" ';
            $check_found = 'no';
            if ($checked_list) {
                if (in_array($term_id, $checked_list)) {   //if cat_id is in previously checked_list      if (in_array("Irix", $os)) {
                   $output  .= 'checked="checked"';
                   $check_found = 'yes';
                }               
            }
           /*
            if ( ($taxonomy == $vtprd_info['parent_plugin_taxonomy']) || ($taxonomy == $vtprd_info['rulecat_taxonomy']) )           {       
                  $output  .= ' disabled="disabled"';
            }
            */
            $output  .= ' />'; //end input statement
            $output  .= '&nbsp;' . $category['name'];
            $output  .= '</label>';            
            $output  .= '</li>';
              echo $output ;    
         }
         return;
    }



    /*
     *  ==========================
     *     AJAX Functions
     *  ==========================                                
     */

    public function vtprd_ajax_load_variations_in() {
      global $wpdb, $post, $vtprd_rule;
         /*  *********************************************
           USE exit rather than return
           as the return statement engerders a 0 return code in the ajax
           which displays as an errant '0' with the ajax display. 
          ********************************************* */
      $vtprd_rule->inPop_varProdID  = $_POST['inVarProdID'];  //from var *passed in from ajax js     
      $product_ID = $vtprd_rule->inPop_varProdID;
      $product_variation_IDs = $this->vtprd_ajax_edit_product($product_ID, 'in');
      
      if ( (isset($vtprd_rule->rule_error_message[0])) &&    //v1.0.7.9
           ($vtprd_rule->rule_error_message[0] > ' ') ) {    //v1.0.7.9
         echo '<div id="inVariationsError">';
         echo $vtprd_rule->rule_error_message[0];
         echo '</div>';
      } else {
         $this->vtprd_ajax_show_variations_in ($product_variation_IDs); 
      }
          
    exit;
  }   //end ajax_load_variations_in(

    public function vtprd_ajax_load_variations_out() {
      global $wpdb, $post, $vtprd_rule;
         /*  *********************************************
           USE exit rather than return
           as the return statement engerders a 0 return code in the ajax
           which displays as an errant '0' with the ajax display. 
          ********************************************* */
      $vtprd_rule->actionPop_varProdID  = $_POST['outVarProdID'];  //from var *passed in from ajax js     
      $product_ID = $vtprd_rule->actionPop_varProdID;
      $product_variation_IDs = $this->vtprd_ajax_edit_product($product_ID, 'out');
  
      if ( (isset($vtprd_rule->rule_error_message[0])) &&    //v1.0.7.9
           ($vtprd_rule->rule_error_message[0] > ' ') ) {    //v1.0.7.9      
         echo '<div id="outVariationsError">';
         echo $vtprd_rule->rule_error_message[0];
         echo '</div>';
      } else {
         $this->vtprd_ajax_show_variations_out ($product_variation_IDs);
      }
            
    exit;
  }   //end ajax_load_variations_out(
     
  public function vtprd_ajax_edit_product($product_ID, $inOrOut) {
    global $wpdb, $post, $vtprd_rule, $vtprd_setup_options;
    
    //edits copied from vtprd-rules-update.php
    if ($product_ID == ' '){
      $vtprd_rule->rule_error_message[] = __('No Product ID was supplied.', 'vtprd');
      return;
    } 
     
    if ( is_numeric($product_ID) === false ) {
       $vtprd_rule->rule_error_message[0] =  __('<br><br>Product ID in error = <span id="varProdID-error-ID">', 'vtprd')   .$product_ID .    __('</span>', 'vtprd') ;
               
       if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){
          $vtprd_rule->rule_error_message[0] =  __('<br><br>Product ID in error = <span id="varProdID-error-ID">', 'vtprd')   .$product_ID .    __('</span>', 'vtprd') ;
       }              
       return;
    } 
    
    $test_post = get_post($product_ID);
    if (!$test_post ) {
       $vtprd_rule->rule_error_message[] =  __('Product ID was not found. >', 'vtprd') ;                    
      if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){
          $vtprd_rule->rule_error_message[0] =  __('<br><br>Product ID in error = <span id="varProdID-error-ID">', 'vtprd')   .$product_ID .    __('</span>', 'vtprd') ; 
       }  
      return;
    }
    
    if ($inOrOut == 'in') {
      $vtprd_rule->inPop_varProdID_name      = $test_post->post_title;
    } else {
      $vtprd_rule->actionPop_varProdID_name  = $test_post->post_title;
    }
    
    
    $product_has_variations = vtprd_test_for_variations($product_ID);

    if ($product_has_variations == 'no') {
      $vtprd_rule->rule_error_message[] =  __('Product has no Variations. Product Name = ', 'vtprd') .$test_post->post_title.   __('<br><br> Please use "Single Product Only" option, above.', 'vtprd') ;
      if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){
          $vtprd_rule->rule_error_message[0] =  __('<br><br>Product ID in error = <span id="varProdID-error-ID">', 'vtprd')   .$product_ID .    __('</span>', 'vtprd') ;
       }  
      return;
    }
    
    $product_variation_IDs = vtprd_get_variations_list($product_ID);
    if (sizeof($product_variation_IDs) == 0) {     //v1.0.5
   // if ($product_variation_IDs <= ' ') {   //v1.0.5
      $vtprd_rule->rule_error_message[] = __('Product has no Variations. Product Name = ', 'vtprd') .$test_post->post_title.   __('<br><br> Please use "Single Product Only" option, above.', 'vtprd') ;
      if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){
          $vtprd_rule->rule_error_message[0] =  __('<br><br>Product ID in error = <span id="varProdID-error-ID">', 'vtprd')   .$product_ID .    __('</span>', 'vtprd') ;
       }  
      return;
    }
    
    return ($product_variation_IDs);
    
  } 
  
     
  public function vtprd_ajax_show_variations_in ($product_variation_IDs) {
     global $post, $vtprd_info, $vtprd_rule; $vtprd_rules_set;
          //v1.0.7.9 begin    - initialize array as necessary
          if (!isset( $vtprd_rule->var_in_checked[0])) {
             $vtprd_rule->var_in_checked = array();
          }
          //v1.0.7.9 end
     ?>             
          <h3><?php _e('Product Variations', 'vtprd');?></h3>                  
     <?php
            //********************************
            $this->vtprd_post_category_meta_box($post, array( 'args' => array( 'taxonomy' => 'variations', 'tax_class' => 'var-in', 'checked_list' => $vtprd_rule->var_in_checked, 'pop_in_out_sw' => 'in', 'product_ID' => $vtprd_rule->inPop_varProdID, 'product_variation_IDs' => $product_variation_IDs )));                  //v1.0.7.9  'pop_in_out_sw' => 'in'
            // ******************************** 
            //output hidden count of all variation checkboxes.  Used on update to store info used in 'yousave' messaging
            ?>
            <input type="hidden" id="checkbox_count-var-in" name="checkbox_count-var-in" value="<?php echo $vtprd_info['inpop_variation_checkbox_total']; ?>" />
            <?php                 
  } 
  
  public function vtprd_ajax_show_variations_out ($product_variation_IDs) {
     global $post, $vtprd_info, $vtprd_rule; $vtprd_rules_set;
     ?>             
          <h3><?php _e('Product Variations', 'vtprd');?></h3>                  
     <?php
            //********************************
            $this->vtprd_post_category_meta_box($post, array( 'args' => array( 'taxonomy' => 'variations', 'tax_class' => 'var-out', 'checked_list' => $vtprd_rule->var_out_checked,  'pop_in_out_sw' => 'out', 'product_ID' => $vtprd_rule->actionPop_varProdID, 'product_variation_IDs' => $product_variation_IDs )));         //v1.0.7.9  'pop_in_out_sw' => 'out'
            // ********************************                 
  } 

  //     END AJAX Functions


  // *********************************************************
  //   META BOX for PARENT PLUGIN PRODUCT SCREEN
  // *********************************************************          
  public  function vtprd_parent_product_meta_box_cntl() {
      global $post, $vtprd_info, $vtprd_rule, $vtprd_rules_set;        
      
      if(defined('VTPRD_PRO_DIRNAME')) {
        $metabox_title =  __('Pricing Deals: Product Include or Exclude', 'vtprd');
      } else {
        $metabox_title =  __('Pricing Deals: Product Include or Exclude', 'vtprd') . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . __('(Available with the Pro plugin)', 'vtprd') ;
      }
      
      add_meta_box('vtprd-pricing-deal-info', $metabox_title , array(&$this, 'vtprd_add_parent_product_meta_box'), $vtprd_info ['parent_plugin_cpt'], 'normal', 'low');

                        
  }                   
  /*
  // *********************************************************
     add a meta box to the PARENT PLUGIN'S PRODUCT SCREEN
       * Rule include/exclude info at the product level
       * anchor redirect in the category matabox, to this box, 
       *        inserts directly onto the page 
         from vtprd-admin-product-metabox-script.js
  // ********************************************************* 
  */      
  public  function vtprd_add_parent_product_meta_box() {
      global $post, $vtprd_info, $vtprd_rule, $vtprd_rules_set, $vtprd_rule_display_framework ;        
      $selected = 'selected="selected"';
      
      if ( get_post_meta($post->ID, $vtprd_info['product_meta_key_includeOrExclude'], true) ) {
        $vtprd_includeOrExclude = get_post_meta($post->ID, $vtprd_info['product_meta_key_includeOrExclude'], true);
      } else {
        $vtprd_includeOrExclude = array (
            'includeOrExclude_option'    => 'includeAll', //initialize the value...
            'includeOrExclude_checked_list'    => array() 
          );
      }
      
      ?>    
        <?php //pass literals up to JS, translated here if necessary ?>
        <input type="hidden" id="vtprd-sectionTitle" name="vtprd-sectionTitle" value="<?php _e('Pricing Deals Include or Exclude', 'vtprd');?>" />
        <input type="hidden" id="vtprd-urlTitle" name="vtprd-urlTitle" value="<?php _e('Product Include or Exclude', 'vtprd');?>" />      
        <input id="vtprd-pluginVersion" type="hidden" value="<?php if(defined('VTPRD_PRO_DIRNAME')) { echo "proVersion"; } else { echo "freeVersion"; } ?>" name="vtprd-pluginVersion" />       
        
        <h4 id="includeOrExclude-area-title"><?php _e('*Include or Exclude Product*', 'vtprd'); echo '&nbsp;'; _e(' in Pricing Deals Rule processing, based on the Options and Rule List below', 'vtprd');?></h4>                    
        <div class="dropdown includeOrExclude_area clear-left" id="includeOrExclude_areaID">              
           <span class="dropdown-label" id="includeOrExclude_label"><?php _e('Product Options:', 'vtprd');?></span>               
           <select id="<?php echo $vtprd_rule_display_framework['includeOrExclude']['select']['id'];?>" class="<?php echo$vtprd_rule_display_framework['includeOrExclude']['select']['class']; ?>" name="<?php echo $vtprd_rule_display_framework['includeOrExclude']['select']['name'];?>" tabindex="<?php //echo $vtprd_rule_display_framework['includeOrExclude']['select']['tabindex']; ?>" >          
             <?php
             for($i=0; $i < sizeof($vtprd_rule_display_framework['includeOrExclude']['option']); $i++) {            
             ?>                             
                <option id="<?php echo $vtprd_rule_display_framework['includeOrExclude']['option'][$i]['id']; ?>"  class="<?php echo $vtprd_rule_display_framework['includeOrExclude']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_rule_display_framework['includeOrExclude']['option'][$i]['value']; ?>" <?php if ($vtprd_rule_display_framework['includeOrExclude']['option'][$i]['value'] == $vtprd_includeOrExclude['includeOrExclude_option'] )  { echo $selected; } ?> >  <?php echo $vtprd_rule_display_framework['includeOrExclude']['option'][$i]['title']; ?> </option>
             <?php } ?> 
           </select> 
           <?php vtprd_show_help_tooltip($context = 'includeOrExclude', $location = 'title'); ?>
        </div>
 
          <div id="includeOrExclude-all" class="tabs-panel">
            <h3 id="includeOrExclude-title">Pricing Deal Rule List</h3>
            <p id="includeOrExclude-area-title2"><?php _e("These selections do not ", 'vtprd'); echo '<em>'; _e("force", 'vtprd'); echo '</em>'; 
                _e(" the product into a rule.  ", 'vtprd'); echo '<em>'; _e("Inclusion only applies if the product naturally falls
                into the specified rule populations already.", 'vtprd'); echo '</em>'; ?></p>
            <ul id="includeOrExclude-checklist" class="categorychecklist form-no-clear">   
                  <?php  vtprd_fill_include_exclude_lists ($vtprd_includeOrExclude['includeOrExclude_checked_list'])?>  
            </ul>
          </div>      
      
      
      <?php 
       
      return;
  }    
 
         
  public  function vtprd_build_new_rule() {
      global $post, $vtprd_info, $vtprd_rule, $vtprd_rules_set, $vtprd_deal_structure_framework; 
                    
        //initialize rule
        $vtprd_rule = new VTPRD_Rule;
 
         //fill in standard default values not already supplied
         
        //load the 1st iteration of deal info by default    => internal defaults set in vtprd_deal_structure_framework
        
        $vtprd_rule->rule_deal_info[] = $vtprd_deal_structure_framework;  

        $vtprd_rule->rule_deal_info[0]['buy_repeat_condition'] = 'none'; 
        $vtprd_rule->rule_deal_info[0]['buy_amt_type'] = 'none';
        $vtprd_rule->rule_deal_info[0]['buy_amt_mod'] = 'none';
        $vtprd_rule->rule_deal_info[0]['buy_amt_applies_to'] = 'all';
        $vtprd_rule->rule_deal_info[0]['action_repeat_condition'] = 'none'; 
        $vtprd_rule->rule_deal_info[0]['action_amt_type'] = 'none';  
        $vtprd_rule->rule_deal_info[0]['action_amt_mod'] = 'none';
        $vtprd_rule->rule_deal_info[0]['action_amt_applies_to'] = 'all';
        $vtprd_rule->rule_deal_info[0]['discount_amt_type'] = '0';
        $vtprd_rule->rule_deal_info[0]['discount_applies_to'] = 'each';
        $vtprd_rule->rule_deal_info[0]['discount_rule_max_amt_type'] = 'none';
        $vtprd_rule->rule_deal_info[0]['discount_lifetime_max_amt_type'] = 'none';
        $vtprd_rule->rule_deal_info[0]['discount_rule_cum_max_amt_type'] = 'none'; 
        $vtprd_rule->cumulativeRulePricing = 'yes';   
        $vtprd_rule->cumulativeSalePricing = 'addToSalePrice';   //v1.0.4 
        $vtprd_rule->cumulativeCouponPricing = 'yes';
               //discount occurs 5 times
        $vtprd_rule->ruleApplicationPriority_num = '10';         
        $vtprd_rule->rule_type_selected_framework_key =  'Title01'; //default 1st title for BOTH dropdowns
        
        $vtprd_rule->inPop = 'wholeStore';  //apply to all products
        $vtprd_rule->role_and_or_in = 'or';
        $vtprd_rule->actionPop = 'sameAsInPop' ; 
        $vtprd_rule->role_and_or_out = 'or';
        
        //new upper selects 
        $vtprd_rule->cart_or_catalog_select = 'choose';
        $vtprd_rule->pricing_type_select = 'choose';
        $vtprd_rule->minimum_purchase_select = 'none';
        $vtprd_rule->buy_group_filter_select = 'choose';
        $vtprd_rule->get_group_filter_select = 'choose';
        $vtprd_rule->rule_on_off_sw_select = 'onForever'; //v1.0.7.5 changed from 'on' 
        $vtprd_rule->wizard_on_off_sw_select = 'on';
        $vtprd_rule->rule_type_select = 'basic';          
         
    return;
  }        
     //lots of selects change their values between standard and 'discounted' titles.
    //This is where we supply the HIDEME alternative titles
  public  function vtprd_print_alternative_title_selects() {
      global $vtprd_rule_display_framework, $vtprd_deal_screen_framework;
      ?>          
             
           <?php 
           /* +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-
             Hidden Selects containing various versions of the Select Option texts.
             
                #1  = the default version of the titles
                #2  = the altenate (Discount) version of the titles
              
              Both are supplied, so the JS can toggle between these two sets,
              as needed by the Upper select choices
              +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-
           */ ?>  
             <?php //Upper  pricint_type_select?>  
              <select id="<?php echo $vtprd_rule_display_framework['pricing_type_select']['select']['id'] .'1';?>" class="<?php echo$vtprd_rule_display_framework['pricing_type_select']['select']['class'] .'1'; ?> hideMe" name="<?php echo $vtprd_rule_display_framework['pricing_type_select']['select']['name'] .'1';?>" tabindex="<?php //echo $vtprd_rule_display_framework['pricing_type_select']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['pricing_type_select']['option']); $i++) { 
                                             
                      //pick up the free/pro version of the title => in this case, title and title3
                      $title = $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['title'];
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['id'] .'1'; ?>"  class="<?php echo $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['class'] .'1'; ?>"  value="<?php echo $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['value']; ?>"    ><?php echo $title; ?></option>
                 <?php } ?> 
               </select>                                        
              <select id="<?php echo $vtprd_rule_display_framework['pricing_type_select']['select']['id'] .'-catalog';?>" class="<?php echo$vtprd_rule_display_framework['pricing_type_select']['select']['class'] .'-catalog'; ?> hideMe" name="<?php echo $vtprd_rule_display_framework['pricing_type_select']['select']['name'] .'-catalog';?>" tabindex="<?php //echo $vtprd_rule_display_framework['pricing_type_select']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['pricing_type_select']['option']); $i++) { 
                                             
                      //pick up the free/pro version of the title 
                      $title = $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['title-catalog'];
                
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['id'] .'-catalog'; ?>"  class="<?php echo $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['class'] .'-catalog'; ?>"  value="<?php echo $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['value']; ?>"    ><?php echo $title; ?></option>
                 <?php } ?> 
               </select>   
                          
             
             <?php //Upper  minimum_purchase_select?>  
              <select id="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['select']['id'] .'1';?>" class="<?php echo$vtprd_rule_display_framework['minimum_purchase_select']['select']['class'] .'1'; ?> hideMe" name="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['select']['name'] .'1';?>" tabindex="<?php //echo $vtprd_rule_display_framework['minimum_purchase_select']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['minimum_purchase_select']['option']); $i++) { 
                                             
                      //pick up the free/pro version of the title => in this case, title and title3
                      $title = $vtprd_rule_display_framework['minimum_purchase_select']['option'][$i]['title'];
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['option'][$i]['id'] .'1'; ?>"  class="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['option'][$i]['class'] .'1'; ?>"  value="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['option'][$i]['value']; ?>"    ><?php echo $title; ?></option>
                 <?php } ?> 
               </select>                                        
              <select id="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['select']['id'] .'-catalog';?>" class="<?php echo$vtprd_rule_display_framework['minimum_purchase_select']['select']['class'] .'-catalog'; ?> hideMe" name="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['select']['name'] .'-catalog';?>" tabindex="<?php //echo $vtprd_rule_display_framework['minimum_purchase_select']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['minimum_purchase_select']['option']); $i++) { 
                                             
                      //pick up the free/pro version of the title 
                      $title = $vtprd_rule_display_framework['minimum_purchase_select']['option'][$i]['title-catalog'];
                
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['option'][$i]['id'] .'-catalog'; ?>"  class="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['option'][$i]['class'] .'-catalog'; ?>"  value="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['option'][$i]['value']; ?>"    ><?php echo $title; ?></option>
                 <?php } ?> 
               </select>   
             
             <?php //Upper  buy_group_filter_select?>  
              <select id="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['select']['id'] .'1';?>" class="<?php echo$vtprd_rule_display_framework['buy_group_filter_select']['select']['class'] .'1'; ?> hideMe" name="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['select']['name'] .'1';?>" tabindex="<?php //echo $vtprd_rule_display_framework['buy_group_filter_select']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['buy_group_filter_select']['option']); $i++) { 
                                             
                      //pick up the free/pro version of the title => in this case, title and title3
                      $title = $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['title'];
                      if ( ( defined('VTPRD_PRO_DIRNAME') ) &&
                           ( $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['title3'] > ' ' ) ) {
                        $title = $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['title3'];                        
                      }
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['id'] .'1'; ?>"  class="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['class'] .'1'; ?>"  value="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['value']; ?>"    ><?php echo $title; ?></option>
                 <?php } ?> 
               </select>                                        
              <select id="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['select']['id'] .'2';?>" class="<?php echo$vtprd_rule_display_framework['buy_group_filter_select']['select']['class'] .'2'; ?> hideMe" name="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['select']['name'] .'2';?>" tabindex="<?php //echo $vtprd_rule_display_framework['buy_group_filter_select']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['buy_group_filter_select']['option']); $i++) { 
                                             
                      //pick up the free/pro version of the title 
                      $title = $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['title2'];
                      if ( ( defined('VTPRD_PRO_DIRNAME') ) &&
                           ( $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['title4'] > ' ' ) ) {
                        $title = $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['title4'];                        
                      }                
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['id'] .'2'; ?>"  class="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['class'] .'2'; ?>"  value="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['value']; ?>"    ><?php echo $title; ?></option>
                 <?php } ?> 
               </select>
              <select id="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['select']['id'] .'-catalog';?>" class="<?php echo$vtprd_rule_display_framework['buy_group_filter_select']['select']['class'] .'-catalog'; ?> hideMe" name="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['select']['name'] .'-catalog';?>" tabindex="<?php //echo $vtprd_rule_display_framework['buy_group_filter_select']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['buy_group_filter_select']['option']); $i++) { 
                                             
                      //pick up the free/pro version of the title 
                      $title = $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['title-catalog'];
                
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['id'] .'-catalog'; ?>"  class="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['class'] .'-catalog'; ?>"  value="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['value']; ?>"    ><?php echo $title; ?></option>
                 <?php } ?> 
               </select>                  
      
             <?php //buy_amt_type ?>  
              <select id="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['select']['id'] .'1';?>" class="<?php echo$vtprd_deal_screen_framework['buy_amt_type']['select']['class'] .'1'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['select']['name'] .'1';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['buy_amt_type']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_amt_type']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['id'] .'1'; ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['class'] .'1'; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['value']; ?>"    ><?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['title']; ?></option>
                 <?php } ?> 
               </select>                                        
              <select id="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['select']['id'] .'2';?>" class="<?php echo$vtprd_deal_screen_framework['buy_amt_type']['select']['class'] .'2'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['select']['name'] .'2';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['buy_amt_type']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_amt_type']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['id'] .'2'; ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['class'] .'2'; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['value']; ?>"    ><?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['title2']; ?></option>
                 <?php } ?> 
               </select>
              <select id="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['select']['id'] .'-catalog';?>" class="<?php echo$vtprd_deal_screen_framework['buy_amt_type']['select']['class'] .'-catalog'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['select']['name'] .'-catalog';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['buy_amt_type']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_amt_type']['option']); $i++) { 
                                             
                      //pick up the free/pro version of the title 
                      $title = $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['title-catalog'];
                
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['id'] .'-catalog'; ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['class'] .'-catalog'; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['value']; ?>"    ><?php echo $title; ?></option>
                 <?php } ?> 
               </select>                   
               
             <?php //buy_amt_applies_to ?>  
              <select id="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['select']['id'] .'1';?>" class="<?php echo$vtprd_deal_screen_framework['buy_amt_applies_to']['select']['class'] .'1'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['select']['name'] .'1';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['buy_amt_applies_to']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_amt_applies_to']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['id'] .'1'; ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['class'] .'1'; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['value']; ?>"    ><?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['title']; ?></option>
                 <?php } ?> 
               </select>                                        
              <select id="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['select']['id'] .'2';?>" class="<?php echo$vtprd_deal_screen_framework['buy_amt_applies_to']['select']['class'] .'2'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['select']['name'] .'2';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['buy_amt_applies_to']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_amt_applies_to']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['id'] .'2'; ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['class'] .'2'; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['value']; ?>"    ><?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['title2']; ?></option>
                 <?php } ?> 
               </select>  
               
             <?php //buy_amt_mod ?>  
              <select id="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['select']['id'] .'1';?>" class="<?php echo$vtprd_deal_screen_framework['buy_amt_mod']['select']['class'] .'1'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['select']['name'] .'1';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['buy_amt_mod']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_amt_mod']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['id'] .'1'; ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['class'] .'1'; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['value']; ?>"    ><?php echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['title']; ?></option>
                 <?php } ?> 
               </select>                                        
              <select id="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['select']['id'] .'2';?>" class="<?php echo$vtprd_deal_screen_framework['buy_amt_mod']['select']['class'] .'2'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['select']['name'] .'2';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['buy_amt_mod']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_amt_mod']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['id'] .'2'; ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['class'] .'2'; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['value']; ?>"    ><?php echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['title2']; ?></option>
                 <?php } ?> 
               </select>  
             
            <?php //buy_repeat_condition ?>  
              <select id="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['id'] .'1';?>" class="<?php echo$vtprd_deal_screen_framework['buy_repeat_condition']['select']['class'] .'1'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['name'] .'1';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_repeat_condition']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['id'] .'1'; ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['class'] .'1'; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['value']; ?>"    ><?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['title']; ?></option>
                 <?php } ?> 
               </select>                                        
              <select id="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['id'] .'2';?>" class="<?php echo$vtprd_deal_screen_framework['buy_repeat_condition']['select']['class'] .'2'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['name'] .'2';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_repeat_condition']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['id'] .'2'; ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['class'] .'2'; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['value']; ?>"    ><?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['title2']; ?></option>
                 <?php } ?> 
               </select>  
              <select id="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['id'] .'-catalog';?>" class="<?php echo$vtprd_deal_screen_framework['buy_repeat_condition']['select']['class'] .'-catalog'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['name'] .'-catalog';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_repeat_condition']['option']); $i++) { 
                                             
                      //pick up the free/pro version of the title 
                      $title = $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['title-catalog'];
                
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['id'] .'-catalog'; ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['class'] .'-catalog'; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['value']; ?>"    ><?php echo $title; ?></option>
                 <?php } ?> 
               </select>
      
             <?php //action_amt_type ?>  
              <select id="<?php echo $vtprd_deal_screen_framework['action_amt_type']['select']['id'] .'1';?>" class="<?php echo$vtprd_deal_screen_framework['action_amt_type']['select']['class'] .'1'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['action_amt_type']['select']['name'] .'1';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['action_amt_type']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['action_amt_type']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['id'] .'1'; ?>"  class="<?php echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['class'] .'1'; ?>"  value="<?php echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['value']; ?>"    ><?php echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['title']; ?></option>
                 <?php } ?> 
               </select>                                        
              <select id="<?php echo $vtprd_deal_screen_framework['action_amt_type']['select']['id'] .'2';?>" class="<?php echo$vtprd_deal_screen_framework['action_amt_type']['select']['class'] .'2'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['action_amt_type']['select']['name'] .'2';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['action_amt_type']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['action_amt_type']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['id'] .'2'; ?>"  class="<?php echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['class'] .'2'; ?>"  value="<?php echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['value']; ?>"    ><?php echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['title2']; ?></option>
                 <?php } ?> 
               </select> 
               
            <?php //inPop ?>  
              <select id="<?php echo $vtprd_rule_display_framework['inPop']['select']['id'] .'1';?>" class="<?php echo$vtprd_rule_display_framework['inPop']['select']['class'] .'1'; ?> hideMe" name="<?php echo $vtprd_rule_display_framework['inPop']['select']['name'] .'1';?>" tabindex="<?php //echo $vtprd_rule_display_framework['inPop']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['inPop']['option']); $i++) { 
                                             
                      //pick up the free/pro version of the title 
                      $title = $vtprd_rule_display_framework['inPop']['option'][$i]['title'];
                      if ( ( defined('VTPRD_PRO_DIRNAME') ) &&
                           ( $vtprd_rule_display_framework['inPop']['option'][$i]['title3'] > ' ' ) ) {
                        $title = $vtprd_rule_display_framework['inPop']['option'][$i]['title3'];                        
                      }                  
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['inPop']['option'][$i]['id'] .'1'; ?>"  class="<?php echo $vtprd_rule_display_framework['inPop']['option'][$i]['class'] .'1'; ?>"  value="<?php echo $vtprd_rule_display_framework['inPop']['option'][$i]['value']; ?>"    ><?php echo $title; ?></option>
                 <?php } ?> 
               </select>                                        
              <select id="<?php echo $vtprd_rule_display_framework['inPop']['select']['id'] .'2';?>" class="<?php echo$vtprd_rule_display_framework['inPop']['select']['class'] .'2'; ?> hideMe" name="<?php echo $vtprd_rule_display_framework['inPop']['select']['name'] .'2';?>" tabindex="<?php //echo $vtprd_rule_display_framework['inPop']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['inPop']['option']); $i++) { 
                                             
                      //pick up the free/pro version of the title 
                      $title = $vtprd_rule_display_framework['inPop']['option'][$i]['title2'];
                      if ( ( defined('VTPRD_PRO_DIRNAME') ) &&
                           ( $vtprd_rule_display_framework['inPop']['option'][$i]['title4'] > ' ' ) ) {
                        $title = $vtprd_rule_display_framework['inPop']['option'][$i]['title4'];                        
                      }                    
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['inPop']['option'][$i]['id'] .'2'; ?>"  class="<?php echo $vtprd_rule_display_framework['inPop']['option'][$i]['class'] .'2'; ?>"  value="<?php echo $vtprd_rule_display_framework['inPop']['option'][$i]['value']; ?>"    ><?php echo $title; ?></option>
                 <?php } ?> 
               </select>  
                 
             <?php //specChoice_in ?>  
              <select id="<?php echo $vtprd_rule_display_framework['specChoice_in']['select']['id'] .'1';?>" class="<?php echo$vtprd_rule_display_framework['specChoice_in']['select']['class'] .'1'; ?> hideMe" name="<?php echo $vtprd_rule_display_framework['specChoice_in']['select']['name'] .'1';?>" tabindex="<?php //echo $vtprd_rule_display_framework['specChoice_in']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['specChoice_in']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['specChoice_in']['option'][$i]['id'] .'1'; ?>"  class="<?php echo $vtprd_rule_display_framework['specChoice_in']['option'][$i]['class'] .'1'; ?>"  value="<?php echo $vtprd_rule_display_framework['specChoice_in']['option'][$i]['value']; ?>"    ><?php echo $vtprd_rule_display_framework['specChoice_in']['option'][$i]['title']; ?></option>
                 <?php } ?> 
               </select>                                        
              <select id="<?php echo $vtprd_rule_display_framework['specChoice_in']['select']['id'] .'2';?>" class="<?php echo$vtprd_rule_display_framework['specChoice_in']['select']['class'] .'2'; ?> hideMe" name="<?php echo $vtprd_rule_display_framework['specChoice_in']['select']['name'] .'2';?>" tabindex="<?php //echo $vtprd_rule_display_framework['specChoice_in']['select']['tabindex']; ?>" >          
                 <?php                                               
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['specChoice_in']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['specChoice_in']['option'][$i]['id'] .'2'; ?>"  class="<?php echo $vtprd_rule_display_framework['specChoice_in']['option'][$i]['class'] .'2'; ?>"  value="<?php echo $vtprd_rule_display_framework['specChoice_in']['option'][$i]['value']; ?>"    ><?php echo $vtprd_rule_display_framework['specChoice_in']['option'][$i]['title2']; ?></option>
                 <?php } ?> 
               </select>  
                          
   <?php         
  }          
      
} //end class
