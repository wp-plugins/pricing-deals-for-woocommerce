 /*
 $.browser.chrome = $.browser.webkit && !!window.chrome;
$.browser.safari = $.browser.webkit && !window.chrome;

if ($.browser.chrome) alert("You are using Chrome!");
if ($.browser.safari) alert("You are using Safari!");

Need to alert Safari that JS might not work...
DITTO ie10!!
 */
 
      
                       jQuery.noConflict();

                        jQuery(document).ready(function($) {                                                        

                          //v1.0.5 changed to 2 buttons
                          //Include/Exclude Redirect , inserted into the PUBLISH box
                          newHtml  =  '<span class="box-border-line2" id="">&nbsp;</span>'            
                          newHtml +=  '<div class="vtprd-redirect">';
                          newHtml +=  '<a href="http://www.varktech.com/documentation/pricing-deals/introrule"  target="_blank" class="vtprd-redirect-anchor">';
                          newHtml +=  $("#vtprd-moreInfo").val();        //pick up the literals passed up in the html...  
                          newHtml +=  '</a></div>';           
                          newHtml +=  '<div class="vtprd-redirect vtprd-redirect2">';
                          newHtml +=  '<a href="http://www.varktech.com/support/"  target="_blank" class="vtprd-redirect-anchor">';
                          newHtml +=  $("#vtprd-docTitle").val();        //pick up the literals passed up in the html...  
                          newHtml +=  '</a></div>';                            
                          $(newHtml).insertAfter('div#publishing-action');                                     	                        						

                                                                                                                
            //*************************************
            // At init time, these routines run to present the data loaded in php
            //*************************************  

                           //First time through, test for existing data.  If none, set up new rule setup.                           
                           firstTimeThroughControl(); 

                        
            //*************************************
            // NEW DROPDOWNS test routine 
            //*************************************                             
                          $("#cart-or-catalog-select").change(function(){
                              cartOrCatalogTest();
                          });
                          $("#pricing-type-select").change(function(){
                              pricingTypeTest();
                          });
                          $("#minimum-purchase-select").change(function(){
                              minimumTest();
                          
                            //mwnnew                             
                            activateDates_and_lowerArea();                         
                            setRuleTemplate();                       
                            ruleTemplateChanged();             
                          });
                                    
                          //basic/advanced radio buttons
                          $("#basicSelected").change(function(){
                              basicSelected();
                          });                          
                          $("#advancedSelected").change(function(){
                              advancedSelected();
                          });                          
          
                          //***************************************
                          //   Control upper selects!!
                          //***************************************
                          function firstTimeThroughControl() {                     
                             //VERSION TEST free vs pro, dropdown option labels, etc...
                              switch( $("#pluginVersion").val() ) {
                               case 'freeVersion':
                                  $(".freeVersion-labels").show();
                                  $(".proVersion-labels").hide();                                 
                                  disableAllDiscountLimits();
                                 break; 
                               default:
                                  $(".freeVersion-labels").hide();
                                  $(".proVersion-labels").show();
                                  enableAllDiscountLimits();
                                 break;                                                                  
                              }; 

                            //protect all of the Headings in the dropdowns!                               
                            disableDropdownTitleEntries(); 

                             /* 
                             ****************************************************
                             TWO (hidden) Upper Selects first time switches in use:
                             ****************************************************
                             
                             *upperSelectsHaveDataFirstTime has values from 0 => 5                                                                                             
                                 value = 0  no previous data saved / not a first time run
                                 value = 1  last run got to:  cart_or_catalog_select
                                 value = 2  last run got to:  pricing_type_select
                                 value = 3  last run got to:  minimum_purchase_select
                                 value = 4  last run got to:  buy_group_filter_select
                                 value = 5  last run got to:  get_group_filter_select
                                  $("#upperSelectsHaveDataFirstTime").val()
                                  
                              *upperSelectsFirstTime - values of 'yes' or 'no'
                              *                                
                             */

                             if ($("#upperSelectsHaveDataFirstTime").val() > 0 ) {
                                ruleTemplateTest();  //master set if data came back from the server
                                changeCumulativeSwitches();                              
                             } else {
                                jQuery('.top-box select #cart-or-catalog-select').css('font-style', 'italic');
                                jQuery('.top-box select #cart-or-catalog-select').css('color', 'grey');
                                //Set the cursor for the 3 LIMIT switches
                             };
                              
                             //If the saved msg = the default, user hasn't entered a msg, make it italic!
                             if ($("#discount_product_full_msg").val() == $("#fullMsg").val() ) {
                               jQuery('#discount_product_full_msg').css('color', '#666 !important').css("font-style","italic");                                                     
                             };                                                          
                             if ($("#discount_product_short_msg").val() == $("#shortMsg").val() ) {
                               jQuery('#discount_product_short_msg').css('color', '#666 !important').css("font-style","italic");                                                     
                             };  
                                                                  
                             //HIDE the FUTURE enhancement UPCHARGE option
                             $("#pricing-type-Upcharge").hide();
                            
                             $("#select_status_sw").val('no');
                             cartOrCatalogTest();                   
                             if ( $("#select_status_sw").val() == 'no') {
                               resetUpperFirstTimeSwitches();                            
                               return;        
                             }; 
                            
                             $("#select_status_sw").val('no');
                             pricingTypeTest(); 
                             if ($("#select_status_sw").val() == 'no') {
                               resetUpperFirstTimeSwitches();
                               return;
                             }; 
                             
                             $("#select_status_sw").val('no');
                             minimumTest(); 
                             if ($("#select_status_sw").val() == 'no') {
                               resetUpperFirstTimeSwitches();
                               return;
                             }; 
                             
                             /*  mwnnew
                             $("#select_status_sw").val('no');
                             buyGroupTest(); 
                             if ($("#select_status_sw").val() == 'no') {
                               resetUpperFirstTimeSwitches();
                               return;
                             };
                            
                             //test to see if get group test should be run                        
                             switch( $("#pricing-type-select").val() ) {
                               case "all":
                               case "simple":
                                 break; 
                               default:
                                   $("#select_status_sw").val('no');
                                   getGroupTest(); 
                                   if ($("#select_status_sw").val() == 'no') {
                                     resetUpperFirstTimeSwitches();                                                                          
                                     return;
                                   };                                 
                                 break;                                 
                             };                          
                             */
 
                             
                             //mwnnew
                             //release the rest of the screen EXCEPT the get group 
                             activateDates_and_lowerArea(); 
                             
                             
                                                        
                             //all good, expose the lower screen!
                             $("#lower-screen-wrapper").show("slow");                              
                            
                             //test the on-off switch settings
                             //  done here so the lower opacity setting will take, if sw is off...
                             rule_on_off_sw_select_test();
                           //  wizard_on_off_sw_select_test();

                             //reset upper level server switches prior to exit
                             resetUpperFirstTimeSwitches(); 
                             
                             //used in rules-update.php
                             $("#upperSelectsDoneSw").val('yes'); 
                             
                                                                                         
                          };



                          function resetUpperFirstTimeSwitches() {
                            //reset upper level server switches - only valid for 1st run
                             $("#upperSelectsHaveDataFirstTime").val('0');
                             $("#upperSelectsFirstTime").val('no'); 
                             //reset the switch, only used 1st time in from server, in hidden html field...
                             $("#firstTimeBackFromServer").val('no');                            
                          };
                          
                          function cartOrCatalogTest() {     
                             $("#upperSelectsDoneSw").val('no');
                             
                             $(".select-subTitle").hide("slow");
                             
                             $("#select_status_sw").val('ok');
                                                          
                             //reset these options to 'choose'
                             if ($("#upperSelectsFirstTime").val() == 'yes') {
                                reset_Min_to_Choose();
                                //if buy set as discount, reset...
                                setBuyGroupAsBuy();
                             } else {
                                $('#minimum-purchase-Choose').attr('selected', true); 
                             }; 
                                                          
                             switch( $("#cart-or-catalog-select").val() ) {
                               case "cart":
                                   //once a choice has been made, disable the question
                                  $('#cart-or-catalog-Choose').attr("disabled", "disabled");
                                  jQuery('#cart-or-catalog-select').css('font-style', 'normal');
                                  jQuery('#cart-or-catalog-select').css('color', '#0077BB');
                                  
                                  reload_PricingType_Titles1();
                                  reload_BuyGroupFilterSelect_Titles1();
                                  reload_buy_amt_type_Titles1();
                                  reload_buy_repeat_condition_Titles1();                                   
                                 
                                  if ($("#upperSelectsFirstTime").val() == 'yes') {
                                    $('#pricing-type-select').removeAttr('disabled');
                                    showAllPricingTypeOptions();
                                  } else {
                                    resetPricingType_and_DisableBelow();
                                    showAllPricingTypeOptions();
                                  } ;
                                                                                                                        
                                   //reset the Minimum Select titles to default
                                   reload_MinimumSelect_Titles1();
                                   //restore 'next' title
                                   $('#minimum-purchase-Next').removeAttr('disabled');                                   
                                   
                                 break; 
                               case "catalog":
                                  $//once a choice has been made, disable the question
                                  $('#cart-or-catalog-Choose').attr("disabled", "disabled");
                                  jQuery('#cart-or-catalog-select').css('font-style', 'normal');
                                  jQuery('#cart-or-catalog-select').css('color', '#0077BB');                                  
                                  
                                  reload_PricingType_Titles_catalog();
                                  reload_BuyGroupFilterSelect_Titles_catalog();
                                  reload_buy_amt_type_Titles_catalog();
                                  reload_buy_repeat_condition_Titles_catalog();                                   
                                 
                                  if ($("#upperSelectsFirstTime").val() == 'yes') {
                                    $('#pricing-type-select').removeAttr('disabled');
                                    restrictPricingTypeOptions();
                                  } else {
                                    resetPricingType_and_DisableBelow();
                                    restrictPricingTypeOptions();
                                  }; 
                                                         
                                   //reset the Minimum Select titles to 'catalog'
                                   reload_MinimumSelect_Titles_catalog();
                                   //don't allow 'Next' title to be selected
                                   $('#minimum-purchase-Next').attr("disabled", "disabled");
                                   
                                 break;
                               default:  //case "choose"                                 
                                  resetPricingType_and_DisableBelow();
                                  //disable pricing type
                                  $('#pricing-type-select').attr("disabled", "disabled"); 
                                  $("#select_status_sw").val('no');
                                 break;
                             }; 
                                                         
                                                        
                          };
                          
                          function pricingTypeTest() {                              
                             $("#upperSelectsDoneSw").val('no');
                             
                             $(".select-subTitle").hide("slow");
                             
                             //reset these options to 'choose'
                             if ($("#upperSelectsFirstTime").val() == 'yes') {
                               if ( $("#pricing-type-select").val() == 'choose' ) {
                                     $("#select_status_sw").val('no');
                                     return;  //exit stage left!
                               };
                             };
                             
                             $('#pricing-type-Choose').attr("disabled", "disabled");
                             jQuery('#pricing-type-select').css('font-style', 'normal');
                             jQuery('#pricing-type-select').css('color', '#0077BB');                             
                             
                             //set to 'no' only if 'choose' still set
                             $("#select_status_sw").val('yes');
                             
                             switch( $("#pricing-type-select").val() ) {
                               case "all":
                                     setWholeStore(); //resets
                                     $(".select-subTitle-all").show("slow");                                    
                                 break;
                               case "simple":
                                     setSimpleDiscount();
                                     $(".select-subTitle-simple").show("slow"); 
                                 break; 
                               case "bogo":
                                     setComplexDiscount();
                                     $(".select-subTitle-bogo").show("slow"); 
                                 break;
                               case "group":
                                     setComplexDiscount();
                                     $(".select-subTitle-group").show("slow"); 
                                 break;                                 
                               case "cheapest":
                                     setComplexDiscount();
                                     $(".select-subTitle-cheapest").show("slow"); 
                                 break;                                                                  
                               case "nth":   //nth must have min selected
                                     setComplexDiscount();                                     
                                     $(".select-subTitle-nth").show("slow"); 
                                     $('#minimum-purchase-None').attr('disabled', true)
                               // don't need this??     if ($("#upperSelectsFirstTime").val() == 'yes') {
                                       $('#minimum-purchase-None').attr('selected', false);
                                       $('#minimum-purchase-Minimum').attr('selected', true);
                                //     }; 
                                     jQuery('#minimum-purchase-select').css('color', '#0077BB !important');
                                 break;                                 
                             };                         
                             
                             //  if minimum-purchase-Next is disabled, there's only one choice for the dropdown, from the upper switch above
                             if ($('#minimum-purchase-Next').prop("disabled") == true) {   
                                $('#minimum-purchase-select').removeAttr('disabled');
                                $('#minimum-purchase-None').attr('selected', true);
                                $('#minimum-purchase-Choose').attr('disabled', true);
                                $('#minimum-purchase-Choose').attr('selected', false);
                                $('#minimum-purchase-Next').attr('selected', false);
                                 jQuery('#minimum-purchase-select').css('font-style', 'normal');
                                 jQuery('#minimum-purchase-select').css('color', '#0077BB');
                                activateDates_and_lowerArea();
                                setRuleTemplate();                             
                                ruleTemplateChanged();
                                return;
                             }
                             if ($('#minimum-purchase-None').prop("disabled") == true) {   
                                $('#minimum-purchase-select').removeAttr('disabled');
                                $('#minimum-purchase-Next').attr('selected', true);
                                $('#minimum-purchase-Choose').attr('disabled', true);
                                $('#minimum-purchase-Choose').attr('selected', false);
                                 jQuery('#minimum-purchase-select').css('font-style', 'normal');
                                 jQuery('#minimum-purchase-select').css('color', '#0077BB');
                                activateDates_and_lowerArea();
                                setRuleTemplate();    
                                ruleTemplateChanged();
                                return;
                             }                             
                             
                             
                             //enable min, disable the rest of the upper selects
                             $('#minimum-purchase-select').removeAttr('disabled');  //min options remain protected until next select option chosen 
                             $('#rule-on-off-sw-select').attr("disabled", "disabled");
                             
                             //IF we're in the 1st time thru and there upper selects had data coming in,
                             //   let the pre-loaded switch setting carry on.  
                             //     "== 0" means neither of these conditions is true.
                             if ( $("#upperSelectsHaveDataFirstTime").val() == 0) {
                                $("#rule-on-off-sw-select").val('onForever')               //v1.0.7.5 
                                rule_on_off_sw_select_test();                              //v1.0.7.5 
                             }                             
                             
                             jQuery('#date-begin-0').css('text-decoration', 'none');
                             jQuery('#date-end-0').css('text-decoration', 'none');
                            
                             disableDates_lowerScreen();
                                                                                     
                          };
                          
                          function minimumTest() {
                             $("#upperSelectsDoneSw").val('no');
                             
                             $("#select_status_sw").val('yes');
                             
                             //test for get group controls
                             switch( $("#minimum-purchase-select").val() ) {                                
                               case "choose":   
                                     $("#select_status_sw").val('no');
                                     disableDates_lowerScreen();
                                     return;
                                 break;                                 
                             };                             
                             //once a choice is made, disable 'choose' here
                             $('#minimum-purchase-Choose').attr('disabled', true);
                             jQuery('#minimum-purchase-select').css('font-style', 'normal');
                             jQuery('#minimum-purchase-select').css('color', '#0077BB');
                             
                          };

                       
                          function activateDates_and_lowerArea() {     
                             //activate dates
                             $('#date-begin-0').removeAttr('disabled');
                             $('#date-end-0').removeAttr('disabled');
                             $('#rule-on-off-sw-select').removeAttr('disabled');                             
                                                          
                             //IF we're in the 1st time thru and there upper selects had data coming in,
                             //   let the pre-loaded switch setting carry on.  
                             //     "== 0" means neither of these conditions is true.
                             if ( $("#upperSelectsHaveDataFirstTime").val() == 0) {                            
                                $("#rule-on-off-sw-select").val('onForever')               //v1.0.7.5 
                                rule_on_off_sw_select_test();                              //v1.0.7.5 
                             } 
                       
                             //activate lower screen
                             $("#lower-screen-wrapper").show("slow");
                             
                             //used in rules-update.php
                             $("#upperSelectsDoneSw").val('yes');
                             
                             
                                                          
                             //ENable and lighten basic/advanced ...
                             jQuery('#rule-type-info').css('opacity', '1').css('filter', 'alpha(opacity=100)');
                             $('#basicSelected').attr('disabled', false);        
                             $('#advancedSelected').attr('disabled', false);
                             jQuery('#basicSelected-label').css('color', '#444'); 
                             jQuery('#advancedSelected-label').css('color', '#444'); 
                            
                             
                              //basic/advanced radio buttons, hide or show basic/advanced
                              if($('#basicSelected').prop('checked')) {
                                  basicSelected();
                              } else {                         
                                  advancedSelected();
                              }; 
                            
                                                              
                          };                          
                                                                              
                          function resetPricingType_and_DisableBelow() {     
                             //enable pricing type select
                             $('#pricing-type-select').removeAttr('disabled');
                             $('#pricing-type-Choose').attr('selected', true);
                             jQuery('#pricing-type-select').css('font-style', 'italic');
                             jQuery('#pricing-type-select').css('color', 'grey');
                             
                             //disable the rest of the upper selects
                             $('#minimum-purchase-select').attr("disabled", "disabled");
                                                         
                             //hide the buy/get group subtitles
                             $(".select-subTitle").hide("slow");
                             
                             disableDates_lowerScreen();

                          };                            
                          
                              
                          function disableDates_lowerScreen() {          
                             
                             //disable begin/end 
                             $('#date-begin-0').attr('disabled', true);
                             $('#date-end-0').attr('disabled', true);
                             $('#rule-on-off-sw-select').attr("disabled", "disabled");
                                                          
                             //IF we're in the 1st time thru and there upper selects had data coming in,
                             //   let the pre-loaded switch setting carry on.  
                             //     "== 0" means neither of these conditions is true.
                             if ( $("#upperSelectsHaveDataFirstTime").val() == 0) {
                                 $("#rule-on-off-sw-select").val('onForever')               //v1.0.7.5 
                                 rule_on_off_sw_select_test();                              //v1.0.7.5 
                             }                                 
                             
                             //Disable and darken basic/advanced ...
                             jQuery('#rule-type-info').css('opacity', '0.7').css('filter', 'alpha(opacity=70)');
                             $('#basicSelected').attr('disabled', true);        
                             $('#advancedSelected').attr('disabled', true);
                             jQuery('#basicSelected-label').css('color', '#AAAAAA'); 
                             jQuery('#advancedSelected-label').css('color', '#AAAAAA'); 
                             
                             //hide the lower screen 
                             $("#lower-screen-wrapper").hide("slow");                                                 
                          };

                          function showAllPricingTypeOptions() {    //'cart' chosen
                             //Pricing Type
                             $('#pricing-type-All').attr('disabled', false);
                             $('#pricing-type-Simple').attr('disabled', false);
                             $('#pricing-type-Bogo').attr('disabled', false);
                             $('#pricing-type-Group').attr('disabled', false);
                             $('#pricing-type-Cheapest').attr('disabled', false);
                             $('#pricing-type-Nth').attr('disabled', false);
                          };
                          function restrictPricingTypeOptions() {    //'cart' chosen
                             //Pricing Type
                             $('#pricing-type-All').attr('disabled', false);
                             $('#pricing-type-Simple').attr('disabled', false);
                             $('#pricing-type-Bogo').attr('disabled', true);
                             $('#pricing-type-Group').attr('disabled', true);
                             $('#pricing-type-Cheapest').attr('disabled', true);
                             $('#pricing-type-Nth').attr('disabled', true);
                             //min options remain protected until Pricing Type chosen 
                             $('#minimum-purchase-None').attr('disabled', false);
                             $('#minimum-purchase-Minimum').attr('disabled', true);                                             
                          };                          


                          function setWholeStore() { 
                                                         
                             //don't allow 'Next' title to be selected
                             $('#minimum-purchase-Next').attr("disabled", "disabled");
                             $('#minimum-purchase-None').removeAttr('disabled');
                             

                             //opaque the lower screen (to mimic disabled)
                             $("#lower-screen-wrapper").hide("slow");
                              
                             //Buy group title
                             setBuyGroupAsDiscount();
                             $(".buyShortIntro").hide();

                             //reset these options to 'choose'
                             reset_Min_to_Choose();
                             
                          };

                          function setSimpleDiscount() { 
                                                         
                             //don't allow 'Next' title to be selected
                             $('#minimum-purchase-Next').attr("disabled", "disabled");
                             $('#minimum-purchase-None').removeAttr('disabled');;
                                                          
                             //Buy group title
                             setBuyGroupAsDiscount();
                             $(".buyShortIntro").hide("slow");

                             //reset these options to 'choose'
                             reset_Min_to_Choose();
                          };                          
 
                          function setComplexDiscount() { 
                                                                                      
                             //restore 'next' title
                             $('#minimum-purchase-Next').removeAttr('disabled');
                                                          
                             //min option
                             $('#minimum-purchase-None').attr('disabled', false);
                             $('#minimum-purchase-Choose').attr('disabled', false);
                             $('#minimum-purchase-Minimum').attr('disabled', false);
                             //reset these options to 'choose'
                             reset_Min_to_Choose();

                             //Buy group title
                             setBuyGroupAsBuy();
                             $(".buyShortIntro").show("slow");
                             
                             enableAllDiscountLimits();
                          };                         
 
                          function setBuyGroupAsBuy() {                              
 
                             //Show the Buy literals as Buy
                             $(".showBuyAsBuy").show();
                             $(".showBuyAsDiscount").hide();
                             //show Get literals as Discount
                             $(".showGetAsGet").hide();
                             $(".showGetAsDiscount").show();
                             //UNhide whole get area and line above, in case it was previously hidden...   
                             $("#action_info_0").show();   
                          };   
 
                          function setBuyGroupAsDiscount() {                              
  
                             //show the Buy literals as Discount
                             $(".showBuyAsBuy").hide();
                             $(".showBuyAsDiscount").show();
                             //show Get literals as Inactive/Get
                             $(".showGetAsGet").show();
                             $(".showGetAsDiscount").hide(); 
                             //hide whole get area and line above...   
                             $("#action_info_0").hide();
                          }; 
                          
                          function reset_Min_to_Choose() {                              
                             //Act on next selects based on whether the upper selects had data coming in
                             switch( $("#upperSelectsHaveDataFirstTime").val() ) {
                               case '5' :  //data up to  get_group_filter_select
                               case '4' :  //data up to  buy_group_filter_select
                               case '3' :  //data up to  minimum_purchase_select;
                                 break; 
                               default:
                                   $('#minimum-purchase-Choose').attr('selected', true);
                                   $('#minimum-purchase-Choose').attr('disabled', false);
                                   jQuery('#minimum-purchase-select').css('font-style', 'italic');
                                   jQuery('#minimum-purchase-select').css('color', 'grey');
                                 break;                                                              
                             }
                            
                          }; 
                        
                          
                         $("#rule-on-off-sw-select").click(function(){
                             rule_on_off_sw_select_test();                            
                         });
                                                           
                         function rule_on_off_sw_select_test() {                                                         
                          switch( $("#rule-on-off-sw-select").val() ) {                                
                            case "on": 
                                //set to standard blue
                                jQuery('#rule-on-off-sw-select').css('color', '#0077BB !important');
                                jQuery('#date-begin-0').css('color', '#0077BB !important').css('text-decoration', 'none');
                                jQuery('#date-end-0').css('color', '#0077BB !important').css('text-decoration', 'none');
                                $('#date-begin-0').removeAttr('disabled');
                                $('#date-end-0').removeAttr('disabled');                               
                                jQuery('#lower-screen-wrapper').css('opacity', '1').css('filter', 'alpha(opacity=100)');           
                                if ($("#firstTimeBackFromServer").val() != 'yes') {   
                                   jQuery('#cart-or-catalog-select').css('color', '#0077BB !important').css('opacity', '1.0').css('filter', 'alpha(opacity=100)');
                                   jQuery('#pricing-type-select').css('color', '#0077BB !important').css('opacity', '1.0').css('filter', 'alpha(opacity=100)');
                                   jQuery('#minimum-purchase-select').css('color', '#0077BB !important').css('opacity', '1.0').css('filter', 'alpha(opacity=100)');                                  
                                 };
                              break;
                            case "onForever":    
                                //set to green and strikethrough
                                jQuery('#rule-on-off-sw-select').css('color', '#1F861F !important');
                                jQuery('#date-begin-0').css('color', '#1F861F !important').css('text-decoration', 'line-through');
                                jQuery('#date-end-0').css('color', '#1F861F !important').css('text-decoration', 'line-through');
                                $('#date-begin-0').attr("disabled", "disabled");
                                $('#date-end-0').attr("disabled", "disabled");                          
                                jQuery('#lower-screen-wrapper').css('opacity', '1').css('filter', 'alpha(opacity=100)');
                                if ($("#firstTimeBackFromServer").val() != 'yes') {
                                   jQuery('#cart-or-catalog-select').css('color', '#0077BB !important').css('opacity', '1.0').css('filter', 'alpha(opacity=100)');
                                   jQuery('#pricing-type-select').css('color', '#0077BB !important').css('opacity', '1.0').css('filter', 'alpha(opacity=100)');
                                   jQuery('#minimum-purchase-select').css('color', '#0077BB !important').css('opacity', '1.0').css('filter', 'alpha(opacity=100)');                                   
                                };
                              break;
                            case "off": 
                                //set to red, strikethrough and protect everything else!
                                jQuery('#rule-on-off-sw-select').css('color', '#E32525 !important');
                                jQuery('#date-begin-0').css('color', '#E32525 !important').css('text-decoration', 'line-through');
                                jQuery('#date-end-0').css('color', '#E32525 !important').css('text-decoration', 'line-through');
                                $('#date-begin-0').attr("disabled", "disabled");
                                $('#date-end-0').attr("disabled", "disabled");                            
                                jQuery('#lower-screen-wrapper').css('opacity', '0.5').css('filter', 'alpha(opacity=50)');
                               
                                   //do this EVERY time...
                                   jQuery('#cart-or-catalog-select').css('color', '#999999 !important').css('opacity', '0.65').css('filter', 'alpha(opacity=65)');
                                   jQuery('#pricing-type-select').css('color', '#999999 !important').css('opacity', '0.65').css('filter', 'alpha(opacity=65)');
                                   jQuery('#minimum-purchase-select').css('color', '#999999 !important').css('opacity', '0.65').css('filter', 'alpha(opacity=65)');                                
                              break;                                                         
                          };
                        }
                       
                        function disableDropdownTitleEntries() {                              
                           $('#buy_amt_type_heading_0').attr('disabled', true);  
                           $('#buy_amt_type_heading_0').attr('selected', false);
                           $('#buy_amt_applies_to_heading_0').attr('disabled', true);  
                           $('#buy_amt_applies_to_heading_0').attr('selected', false);
                           $('#buy_amt_mod_heading_0').attr('disabled', true);  
                           $('#buy_amt_mod_heading_0').attr('selected', false);
                           $('#buy_repeat_condition_heading_0').attr('disabled', true);  
                           $('#buy_repeat_condition_heading_0').attr('selected', false);
                           $('#action_repeat_condition_heading_0').attr('disabled', true);  
                           $('#action_repeat_condition_heading_0').attr('selected', false);
                           $('#action_amt_type_heading_0').attr('disabled', true);  
                           $('#action_amt_type_heading_0').attr('selected', false);
                           $('#action_amt_applies_to_heading_0').attr('disabled', true);  
                           $('#action_amt_applies_to_heading_0').attr('selected', false);
                           $('#action_amt_mod_heading_0').attr('disabled', true);  
                           $('#action_amt_mod_heading_0').attr('selected', false);
                          //Need this to be active! User needs to be prompted to choose this important option!
                          // $('#discount_amt_type_heading_0').attr('disabled', true);  
                          // $('#discount_amt_type_heading_0').attr('selected', false);
                           $('#discount_applies_to_heading_0').attr('disabled', true);  
                           $('#discount_applies_to_heading_0').attr('selected', false);
                           $('#discount_rule_max_amt_type_heading_0').attr('disabled', true);  
                           $('#discount_rule_max_amt_type_heading_0').attr('selected', false);                          
                           $('#discount_lifetime_max_amt_type_heading_0').attr('disabled', true);  
                           $('#discount_lifetime_max_amt_type_heading_0').attr('selected', false);
                           $('#discount_rule_cum_max_amt_type_heading_0').attr('disabled', true);  
                           $('#discount_rule_cum_max_amt_type_heading_0').attr('selected', false);

                           $('#popChoiceInTitle').attr('disabled', true);  
                           $('#popChoiceInTitle').attr('selected', false);
                           $('#popChoiceOutTitle').attr('disabled', true);  
                           $('#popChoiceOutTitle').attr('selected', false);

                        };                            
             //*************************************
            // NEW DROPDOWNS End
            //*************************************                           
                          
                          
                           
                          
            //***********************************************************************
            //Evaluate the Upper Dropdowns and set the Rule Template Type
            //***********************************************************************
                          function setRuleTemplate() { 
                             cartOrCatalogSelect_sw = $("#cart-or-catalog-select").val();
                             minPurchaseSelect_sw   = $("#minimum-purchase-select").val();

                             switch( $("#pricing-type-select").val() ) {
                                case "all":                       
                                     if (cartOrCatalogSelect_sw == 'cart') {
                                       $("#rule_template_framework").val('C-storeWideSale');
                                     } else {
                                       $("#rule_template_framework").val('D-storeWideSale');
                                     };
                                  break;
                                case "simple":                       
                                     if (cartOrCatalogSelect_sw == 'cart') {
                                       $("#rule_template_framework").val('C-simpleDiscount');
                                     } else {
                                       $("#rule_template_framework").val('D-simpleDiscount');
                                     };
                                  break;
                                case "bogo":                       
                                     if (minPurchaseSelect_sw == 'none') {
                                       $("#rule_template_framework").val('C-discount-inCart');
                                     } else {
                                       $("#rule_template_framework").val('C-discount-Next');
                                     };
                                  break;
                                case "group":                       
                                     if (minPurchaseSelect_sw == 'none') {
                                       $("#rule_template_framework").val('C-forThePriceOf-inCart');
                                     } else {
                                       $("#rule_template_framework").val('C-forThePriceOf-Next');
                                     };
                                  break; 
                                case "cheapest":                       
                                     if (minPurchaseSelect_sw == 'none') {
                                       $("#rule_template_framework").val('C-cheapest-inCart');
                                     } else {
                                       $("#rule_template_framework").val('C-cheapest-Next');
                                     };
                                  break;
                                case "nth":                       
                                       $("#rule_template_framework").val('C-nth-Next');
                                  break;                                                                                                                                                                         
                             };

                          };                          

                          //basic click function
                          function basicSelected() {   
                              $(".box-border-line").hide("slow");
                              $("#buy_amt_mod_box_0").hide("slow"); 
                          //    $("#buy_repeat_box_0").hide("slow"); 
                              $("#action_amt_mod_box_0").hide("slow"); 
                              $("#action_repeat_condition_box_0").hide("slow"); 
                              $("#discount_applies_to_box_0").hide("slow"); 
                              $("#discount_lifetime_max_amt_type_box_0").hide("slow");    
                              $(".advanced-area").hide("slow"); 
                              $("#advanced-data-area").hide("slow");  
                             /* $(".discount_product_full_msg_area").hide("slow");  */
                              $(".show-in-adanced-mode-only").hide("slow");
                              /*$(".buy_group_title-area").hide("slow");
                              $(".get_group_title-area").hide("slow");
                              $("#discount_amt_title_0").hide("slow");
                              
                              jQuery('#basicSelected-label').css('color', '#0077BB');
                              jQuery('#advancedSelected-label').css('color', '#2D4F5D');*/
                              
                              $("#discount_msgs_title").hide("slow"); 
                              jQuery('.buy_group_title-area').css('opacity', '0.4').css('filter', 'alpha(opacity=40)');
                              jQuery('.get_group_title-area').css('opacity', '0.4').css('filter', 'alpha(opacity=40)');
                              jQuery('#discount_amt_title_0').css('opacity', '0.4').css('filter', 'alpha(opacity=40)');

                          };
                                                                           
                          //advanced click function
                          function advancedSelected() {   
                              $(".box-border-line").show("slow");
                              $("#buy_amt_mod_box_0").show("slow"); 
                          //    $("#buy_repeat_box_0").show("slow"); 
                              $("#action_amt_mod_box_0").show("slow"); 
                              $("#action_repeat_condition_box_0").show("slow"); 
                              $("#discount_applies_to_box_0").show("slow"); 
                              $("#discount_lifetime_max_amt_type_box_0").show("slow"); 
                              $(".advanced-area").show("slow"); 
                              $("#advanced-data-area").show("slow");
                             /* $(".discount_product_full_msg_area").show("slow"); */
                              $(".show-in-adanced-mode-only").show("slow");
                             /* $(".buy_group_title-area").show("slow");
                              $(".get_group_title-area").show("slow");
                              $("#discount_amt_title_0").show("slow");
                              
                              jQuery('#basicSelected-label').css('color', '#2D4F5D');
                              jQuery('#advancedSelected-label').css('color', '#0077BB'); */
                                                            
                              $("#discount_msgs_title").show("slow"); 
                              jQuery('.buy_group_title-area').css('opacity', '1').css('filter', 'alpha(opacity=100)');
                              jQuery('.get_group_title-area').css('opacity', '1').css('filter', 'alpha(opacity=100)');
                              jQuery('#discount_amt_title_0').css('opacity', '1').css('filter', 'alpha(opacity=100)');                                  
                          };
                                                   
                         //***********************************************
                         //  Reload Select Option Titles area
                         //***********************************************
                         
                         /*
                         Control the replacement of the text literals associated with selects
                         throughout the whole screen
                         
                         Set1 = default set
                         Set2 = change titles to be 'Discount' rather than 'Buy' or 'Get'
                         
                         Working off of hidden selects which contain these alternate titles
                          - Framework.php has the alternate titles labeled as 'title2'
                          - rules-ui.ph creates these hidden selects on the fly, based on the 
                            existence of these 'title2' entries.
                        
                        (Both title sets are needed, as we toggle between them...)
                         */ 
                          function reload_PricingType_Titles1() {   
                              var selectobject=document.getElementById("buy-group-filter-select1");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=pricing-type-select1]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=pricing-type-select1]').find('option').eq(i).text();
                                     $('select[name=pricing-type-select]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };
                          function reload_PricingType_Titles_catalog() {   
                              var selectobject=document.getElementById("pricing-type-select-catalog");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=pricing-type-select-catalog]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=pricing-type-select-catalog]').find('option').eq(i).text();
                                     $('select[name=pricing-type-select]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };                          
                          function reload_MinimumSelect_Titles1() {   
                              var selectobject=document.getElementById("buy-group-filter-select1");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=minimum-purchase-select1]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=minimum-purchase-select1]').find('option').eq(i).text();
                                     $('select[name=minimum-purchase-select]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };
                          function reload_MinimumSelect_Titles_catalog() {   
                              var selectobject=document.getElementById("minimum-purchase-select-catalog");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=minimum-purchase-select-catalog]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=minimum-purchase-select-catalog]').find('option').eq(i).text();
                                     $('select[name=minimum-purchase-select]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };
                          function reload_BuyGroupFilterSelect_Titles1() {   
                              var selectobject=document.getElementById("buy-group-filter-select1");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=buy-group-filter-select1]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=buy-group-filter-select1]').find('option').eq(i).text();
                                     $('select[name=buy-group-filter-select]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };                          
                          function reload_BuyGroupFilterSelect_Titles2() {    
                              var selectobject=document.getElementById("buy-group-filter-select2");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=buy-group-filter-select2]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=buy-group-filter-select2]').find('option').eq(i).text();
                                     $('select[name=buy-group-filter-select]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };
                          function reload_BuyGroupFilterSelect_Titles_catalog() {    
                              var selectobject=document.getElementById("buy-group-filter-select-catalog");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=buy-group-filter-select-catalog]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=buy-group-filter-select-catalog]').find('option').eq(i).text();
                                     $('select[name=buy-group-filter-select]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };                          
                          function reload_buy_amt_type_Titles1() {   
                              var selectobject=document.getElementById("buy_amt_type1");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=buy_amt_type1]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=buy_amt_type1]').find('option').eq(i).text();
                                     $('select[name=buy_amt_type_0]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };                          
                          function reload_buy_amt_type_Titles2() {    
                              var selectobject=document.getElementById("buy_amt_type2");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=buy_amt_type2]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=buy_amt_type2]').find('option').eq(i).text();
                                     $('select[name=buy_amt_type_0]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };
                          function reload_buy_amt_type_Titles_catalog() {    
                              var selectobject=document.getElementById("buy_amt_type-catalog");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=buy_amt_type-catalog]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=buy_amt_type-catalog]').find('option').eq(i).text();
                                     $('select[name=buy_amt_type_0]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };                          
                          function reload_buy_amt_applies_to_Titles1() {   
                              var selectobject=document.getElementById("buy_amt_applies_to1");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=buy_amt_applies_to1]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=buy_amt_applies_to1]').find('option').eq(i).text();
                                     $('select[name=buy_amt_applies_to_0]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };                          
                          function reload_buy_amt_applies_to_Titles2() {    
                              var selectobject=document.getElementById("buy_amt_applies_to2");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=buy_amt_applies_to2]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=buy_amt_applies_to2]').find('option').eq(i).text();
                                     $('select[name=buy_amt_applies_to_0]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };
                          function reload_buy_amt_mod_Titles1() {   
                              var selectobject=document.getElementById("buy_amt_mod1");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=buy_amt_mod1]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=buy_amt_mod1]').find('option').eq(i).text();
                                     $('select[name=buy_amt_mod_0]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };                          
                          function reload_buy_amt_mod_Titles2() {    
                              var selectobject=document.getElementById("buy_amt_mod2");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=buy_amt_mod2]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=buy_amt_mod2]').find('option').eq(i).text();
                                     $('select[name=buy_amt_mod_0]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };      
                          function reload_buy_repeat_condition_Titles1() {   
                              var selectobject=document.getElementById("buy_repeat_condition1");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=buy_repeat_condition1]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=buy_repeat_condition1]').find('option').eq(i).text();
                                     $('select[name=buy_repeat_condition_0]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };                          
                          function reload_buy_repeat_condition_Titles2() {    
                              var selectobject=document.getElementById("buy_repeat_condition2");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=buy_repeat_condition2]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=buy_repeat_condition2]').find('option').eq(i).text();
                                     $('select[name=buy_repeat_condition_0]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };
                          function reload_buy_repeat_condition_Titles_catalog() {    
                              var selectobject=document.getElementById("buy_repeat_condition-catalog");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=buy_repeat_condition-catalog]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=buy_repeat_condition-catalog]').find('option').eq(i).text();
                                     $('select[name=buy_repeat_condition_0]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };                          
                          function reload_action_amt_type_Titles1() {   
                              var selectobject=document.getElementById("action_amt_type1");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=action_amt_type1]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=action_amt_type1]').find('option').eq(i).text();
                                     $('select[name=action_amt_type_0]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };                          
                          function reload_action_amt_type_Titles2() {    
                              var selectobject=document.getElementById("action_amt_type2");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=action_amt_type2]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=action_amt_type2]').find('option').eq(i).text();
                                     $('select[name=action_amt_type_0]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };
                                         
                          function reload_popChoiceIn_Titles1() {   
                              var selectobject=document.getElementById("popChoiceIn1");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=popChoiceIn1]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=popChoiceIn1]').find('option').eq(i).text();
                                     $('select[name=popChoiceIn]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };                          
                          function reload_popChoiceIn_Titles2() {    
                              var selectobject=document.getElementById("popChoiceIn2");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=popChoiceIn2]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=popChoiceIn2]').find('option').eq(i).text();
                                     $('select[name=popChoiceIn]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };
                          function reload_specChoice_in_Titles1() {   
                              var selectobject=document.getElementById("specChoice_in1");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=specChoice_in1]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=specChoice_in1]').find('option').eq(i).text();
                                     $('select[name=specChoice_in]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };                          
                          function reload_specChoice_in_Titles2() {    
                              var selectobject=document.getElementById("specChoice_in2");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=specChoice_in2]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=specChoice_in2]').find('option').eq(i).text();
                                     $('select[name=specChoice_in]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };                          
                         //END  Reload Select Option Titles area                               
                                                       
                                                        
            //*************************************
            // MASTER TEMPLATE choice routine
            //*************************************  
                            
                            // React to Template choices in the upper DROPDOWNs
                            function ruleTemplateChanged() {                           
                                //SKIP if 1st time through!!  
                                if ($("#firstTimeBackFromServer").val() == 'yes') {  
                                	return;
                                } 
                                
                                //get rid of all values on template change only!!
                                $("#templateChanged").val('yes');
                                resetAllDropdowns();
                                resetAllSelections();
                                initAllValues();
                     //don't do this twice...           hideRemainderOfAllBoxes();
                                ruleTemplateTest();
                                changeCumulativeSwitches();                                  
                             //   $("#templateChanged").val('no');
                            };                                                           
                                                                       
                            function ruleTemplateTest() { 
                              hideRemainderOfAllBoxes();
                              switch( $("#rule_template_framework").val() ) {
                                case "0":                       noTemplateChoiceYet();              break;
                                case "D-storeWideSale":         storeWideSale();                    break;
                                case "D-simpleDiscount":        simpleMembershipDiscount();
                                                                disableAllDiscountLimits();   //v1.0.4  moved here to prevent interference with cart simple discount usage...          
                                                                                                    break;
                                case "C-storeWideSale":         process_C_storeWideSale();          break;
                                case "C-simpleDiscount":        process_C_simpleDiscount();         break;
                                case "C-discount-inCart":       process_C_discount_inCart();        break;
                                case "C-forThePriceOf-inCart":  process_C_forThePriceOf_inCart();   break;
                                case "C-cheapest-inCart":       process_C_cheapest_inCart();        break;
                                case "C-discount-Next":         process_C_discount_Next();          break;
                                case "C-forThePriceOf-Next":    process_C_forThePriceOf_Next();     break;
                                case "C-cheapest-Next":         process_C_cheapest_Next();          break;
                                case "C-nth-Next":              process_C_nth_Next();               break;                              
                              };
                            }; 
                              
                            function noTemplateChoiceYet() { 
                              //keep all these for 1st time through
                              resetAllDropdowns();
                              resetAllSelections();
                              initAllValues();
                              hideRemainderOfAllBoxes();  
                            };
                               
                            function process_C_storeWideSale() { 
                              storeWideSale(); 
                              //  override  discount_appliesTo_protect1() done in storeWideSale() 
                              discount_appliesTo_protect2(); //allow 'each' or 'all'
                              enableAllDiscountLimits();
                              buyAmtType_repeat_reset(); //mwntest  
                            };
                            function process_C_simpleDiscount() { 
                              simpleMembershipDiscount(); 
                            //  buyAmtType_qualifier_reset();
                              buyAmtType_qualifier_protect1();
                              buyAmtType_repeat_reset();
                              //  override  discount_appliesTo_protect1() done in simpleMembershipDiscount() 
                              discount_appliesTo_protect2(); //allow 'each' or 'all'
                              enableAllDiscountLimits();
                               
                            };                                                     
                            function storeWideSale() {    //Store-Wide Sale  -Display
                              setDropdownsToInitalDefaults();
                              actionAmtType_change_text_to_remove_Next();
                              setWholeStoreOrCartContentsIn();//only on whole store template                           
                              discount_appliesTo_protect1();// set 'each' only 
                              buyAmtType_appliesTo_protect2(); //set each only
                         //     blockAllButSimpleOptions();                       
                              disableAllDiscountLimits();
                              testForExistingData();
                     //         $("#buy_group_box").show("slow");
                     //         $("#action_group_box").show("slow");  
                            };
                            function simpleMembershipDiscount() {    //Membership Discount   -Display
                              setDropdownsToInitalDefaults();                     
                            //  disableAllDiscountLimits();   //v1.0.4  moved to a different location, interfered with cart simple discount usage...  
                              actionAmtType_change_text_to_remove_Next();
                              discountAmtType_protect1();
                              setSameAsBuyGroupOnly();                                                            
                              discount_appliesTo_protect1();// set 'each' only
                              buyAmtType_appliesTo_protect2(); //set each only
                              testForExistingData(); 
                            };
                             
                            function process_C_discount_inCart() {    //Buy 5/$500, get a discount for Some/All 5      -Cart
                              enableAllDiscountLimits();
                              actionAmtType_change_text_to_remove_Next();
                              setSameAsBuyGroupOnly();
                              actionAmtType_protect4();
                              discountAmtType_protect1();                                                            
                              discount_appliesTo_protect2(); //set 'each' and 'all' as valid only
                              testForExistingData();
                              buy_amt_line_remainder_chg(); //expose rest of buy line... 
                              action_amt_line_remainder_chg(); //expose rest of action line... 
                            };
                            function process_C_forThePriceOf_inCart() {    //Buy 5, get them for the price of 4/$400       -Cart
                              enableAllDiscountLimits();
                              actionAmtType_change_text_to_remove_Next();
                              setAttribsFor_ForThePriceOf();
                              testForExistingData();
                              setSameAsBuyGroupOnly();
                            };
                            function process_C_cheapest_inCart() {    //Buy 5/$500, get the cheapest/most expensive at a discount     -Cart
                              enableAllDiscountLimits();
                              actionAmtType_change_text_to_remove_Next();                              
                              setSameAsBuyGroupOnly();                              
                              setAttribsFor_Cheapest();
                              testForExistingData(); 
                            };
                            //This option has the most breadth....
                            function process_C_discount_Next() {    // Buy 5/$500, get a discount on Next 4/$400 - Cart
                              enableAllDiscountLimits();
                              setAttribsFor_nextNumOrCurrency(); 
                              actionAmtType_change_text_to_include_Next();                             
                              testForExistingData();
                              
                            };
                            function process_C_forThePriceOf_Next() {    // Buy 5/$500, get next 3 for the price of 2/$200 - Cart
                              enableAllDiscountLimits();
                              setAttribsFor_nextForThePriceOf();
                              actionAmtType_change_text_to_include_Next();
                              testForExistingData();
                              
                            };
                            function process_C_cheapest_Next() {    // Buy 5/$500, get a discount on the cheapest/most expensive when next 5/$500 purchased - Cart
                              enableAllDiscountLimits();
                              setAttribsFor_NextCheapest();
                              actionAmtType_change_text_to_include_Next();                             
                              testForExistingData();
                              
                            };
                            function process_C_nth_Next() {    // Buy 5/$500, get the following Nth at a discount - Cart
                              enableAllDiscountLimits();
                              setAttribsFor_NextNth();
                              actionAmtType_change_text_to_include_Next();                             
                              testForExistingData();
                              
                            };
                                                
                            function resetAllDropdowns() {
                              buyAmtType_reset();
                              buyAmtType_appliesTo_reset();
                              buyAmtType_qualifier_reset();
                              buyAmtType_repeat_reset();                              
                              actionAmtType_reset();
                              actionAmtType_appliesTo_reset();
                              actionAmtType_qualifier_reset();
                              actionAmtType_repeat_reset();                               
                              discountAmtType_reset();
                              discount_appliesTo_reset();
                              popChoiceIn_reset();
                              popChoiceOut_reset();
                              // Also hide help panels, pop options, from previous runs
                              $(".selection-panel").hide("slow");  //hide all selection-panels
                              hideChoiceIn(); 
                              hideChoiceOut();                              
                            };                      
                            
                            function initAllValues() {      
                              $('.buy_amt_count').val(' ');
                              $('.buy_amt_mod_count').val(' ');
                              $('.buy_repeat_count').val(' ');
                              $('.action_amt_count').val(' ');
                              $('.action_amt_mod_count').val(' ');
                              $('.action_repeat_count').val(' ');
                              $('.discount_amt_count').val(' ');
                              $('.forThePriceOf-amt-literal-inserted').val(' '); 
                              $('.discount_auto_add_free_product').removeAttr('checked');                               
                              $('#discount_rule_max_amt_count_0').val(' ');
                              $('#discount_rule_max_amt_msg').val(' ');
                              $('#discount_lifetime_max_amt_count_0').val(' ');
                              $('#discount_lifetime_max_amt_msg').val(' ');
                              $('#discount_rule_cum_max_amt_count_0').val(' ');
                              $('#discount_rule_cum_max_amt_msg').val(' ');
                              $(".vtprd-error").hide("slow");  //hide all previous errors
                              $(".vtprd-error2").hide("slow");  //hide all previous errors
                              $(".ruleInWords").hide("slow");  //hide previous word summary         outVarProdID
                              /*Group fields*/
                              $('#singleProdID_in').val(' ');
                              $('#singleProdID_out').val(' ');
                              $('#inVarProdID').val(' ');
                              $('#outVarProdID').val(' ');
                              $("#ruleApplicationPriority_num").val('10'); 
                              
                              var elem = document.getElementById("discount_product_full_msg");
                              elem.value = $("#fullMsg").val();//hidden field with lit
                              jQuery('#discount_product_full_msg').css('color', '#666 !important').css("font-style","italic");
                              
                              var elem = document.getElementById("discount_product_short_msg");
                              if ( $("#cart-or-catalog-select").val()  ==  "cart" ) {                                 
                                elem.value = $("#shortMsg").val();//hidden field with lit
                                jQuery('#discount_product_short_msg').css('color', '#666 !important').css("font-style","italic");
                              } else {
                                //set Checkout Message  =  "Unused for Catalog Discount"
                                elem.value = $("#catalogCheckoutMsg").val();//hidden field with lit
                              }
                              
                            };                                                      

                            function blockAllButSimpleOptions() { 
                              //disable Discount Limits Options
                              $('#discount_rule_max_amt_type_percent_0').attr('disabled', true);
                              $('#discount_rule_max_amt_type_qty_0').attr('disabled', true);
                              $('#discount_rule_max_amt_type_currency_0').attr('disabled', true);
                              $('#discount_lifetime_max_amt_type_quantity_0').attr('disabled', true);
                              $('#discount_lifetime_max_amt_type_currency_0').attr('disabled', true);

                              // force cum_max switches change to 'no'
                              $('#discount_rule_cum_max_amt_type_percent_0').attr('disabled', true);
                              $('#discount_rule_cum_max_amt_type_qty_0').attr('disabled', true);
                              $('#discount_rule_cum_max_amt_type_currency_0').attr('disabled', true);

                              //disable Discount Limits Options
                              jQuery('#discount_rule_max_amt_type_percent_0').css('color', 'black');
                              jQuery('#discount_rule_max_amt_type_qty_0').css('color', 'black');
                              jQuery('#discount_rule_max_amt_type_currency_0').css('color', 'black');
                              jQuery('#discount_lifetime_max_amt_type_quantity_0').css('color', 'black');
                              jQuery('#discount_lifetime_max_amt_type_currency_0').css('color', 'black');
                              jQuery('#discount_rule_cum_max_amt_type_percent_0').css('color', 'black');
                              jQuery('#discount_rule_cum_max_amt_type_qty_0').css('color', 'black');
                              jQuery('#discount_rule_cum_max_amt_type_currency_0').css('color', 'black');                              
                            }; 

                            function disableAllDiscountLimits() { 
                              //disable Discount Limits Options
                              $('#discount_rule_max_amt_type_percent_0').attr('disabled', true);
                              $('#discount_rule_max_amt_type_qty_0').attr('disabled', true);
                              $('#discount_rule_max_amt_type_currency_0').attr('disabled', true);
                              $('#discount_lifetime_max_amt_type_quantity_0').attr('disabled', true);
                              $('#discount_lifetime_max_amt_type_currency_0').attr('disabled', true);
                              $('#discount_rule_cum_max_amt_type_percent_0').attr('disabled', true);
                              $('#discount_rule_cum_max_amt_type_qty_0').attr('disabled', true);
                              $('#discount_rule_cum_max_amt_type_currency_0').attr('disabled', true);
                              
                              //disable Discount Limits Options
                              jQuery('#discount_rule_max_amt_type_percent_0').css('color', 'black');
                              jQuery('#discount_rule_max_amt_type_qty_0').css('color', 'black');
                              jQuery('#discount_rule_max_amt_type_currency_0').css('color', 'black');
                              jQuery('#discount_lifetime_max_amt_type_quantity_0').css('color', 'black');
                              jQuery('#discount_lifetime_max_amt_type_currency_0').css('color', 'black');
                              jQuery('#discount_rule_cum_max_amt_type_percent_0').css('color', 'black');
                              jQuery('#discount_rule_cum_max_amt_type_qty_0').css('color', 'black');
                              jQuery('#discount_rule_cum_max_amt_type_currency_0').css('color', 'black'); 
                              
                              //disable selected values:  discount_lifetime_max_amt_type_none_0
                              $('#discount_rule_max_amt_type_percent_0').attr('selected', false);
                              $('#discount_rule_max_amt_type_qty_0').attr('selected', false);
                              $('#discount_rule_max_amt_type_currency_0').attr('selected', false);
                              $('#discount_lifetime_max_amt_type_quantity_0').attr('selected', false);
                              $('#discount_lifetime_max_amt_type_currency_0').attr('selected', false);
                              $('#discount_rule_cum_max_amt_type_percent_0').attr('selected', false);
                              $('#discount_rule_cum_max_amt_type_qty_0').attr('selected', false);
                              $('#discount_rule_cum_max_amt_type_currency_0').attr('selected', false); 
                              
                              //set selected values:  
                              $('#discount_lifetime_max_amt_type_none_0').attr('selected', true);
                              $('#discount_rule_max_amt_type_none_0').attr('selected', true);
                              $('#discount_rule_cum_max_amt_type_none_0').attr('selected', true);
                                                         
                            };                                                                                                            
                            
                            function enableAllDiscountLimits() {
                              
                              //only do this in the PRO version...
                              switch( $("#pluginVersion").val() ) {
                               case 'freeVersion':
                                  return;
                                 break; 
                               default:
                                 break;                                                                  
                              };
                              
                              //enable Discount Limits Options
                              $('#discount_rule_max_amt_type_percent_0').attr('disabled', false);
                              $('#discount_rule_max_amt_type_qty_0').attr('disabled', false);
                              $('#discount_rule_max_amt_type_currency_0').attr('disabled', false);
                              $('#discount_lifetime_max_amt_type_quantity_0').attr('disabled', false);
                              $('#discount_lifetime_max_amt_type_currency_0').attr('disabled', false);
                              $('#discount_rule_cum_max_amt_type_percent_0').attr('disabled', false);
                              $('#discount_rule_cum_max_amt_type_qty_0').attr('disabled', false);
                              $('#discount_rule_cum_max_amt_type_currency_0').attr('disabled', false);

                              jQuery('#discount_rule_max_amt_type_percent_0').css('color', '#0077BB');
                              jQuery('#discount_rule_max_amt_type_qty_0').css('color', '#0077BB');
                              jQuery('#discount_rule_max_amt_type_currency_0').css('color', '#0077BB');
                              jQuery('#discount_lifetime_max_amt_type_quantity_0').css('color', '#0077BB');
                              jQuery('#discount_lifetime_max_amt_type_currency_0').css('color', '#0077BB');
                              jQuery('#discount_rule_cum_max_amt_type_percent_0').css('color', '#0077BB');
                              jQuery('#discount_rule_cum_max_amt_type_qty_0').css('color', '#0077BB');
                              jQuery('#discount_rule_cum_max_amt_type_currency_0').css('color', '#0077BB');                              
                            };

                            //RESET ALL VALUES TO DEFAULT  
                            function resetAllSelections() {
                              $('#buy_amt_type_none_0').attr('selected', true);
                              $('#cartChoiceIn').attr('selected', true);
                              $('#buy_amt_applies_to_each_0').attr('selected', true);
                              $('#buy_amt_mod_none_0').attr('selected', true);
                              //$('#buy_repeat_condition_none_0').attr('selected', true);
                              $('#buy_repeat_condition_unlimited_0').attr('selected', true);
                              $('#action_amt_type_none_0').attr('selected', true);
                              $('#sameChoiceOut').attr('selected', true);
                              $('#action_amt_mod_none_0').attr('selected', true);
                              $('#action_amt_applies_to_all_0').attr('selected', true);
                              $('#action_repeat_condition_none_0').attr('selected', true);
                              $('#discount_amt_type_heading_0').attr('selected', true); 
                              $('#discount_applies_to_each_0').attr('selected', true);
                              //$('#').attr('selected', true);
                              $('#discount_lifetime_max_amt_type_none_0').attr('selected', true);
                              $('#discount_rule_max_amt_type_none_0').attr('selected', true);
                              $('#discount_rule_cum_max_amt_type_none_0').attr('selected', true);
                              $('#cumulativeRulePricingNo').attr('selected', true);
                              $('#cumulativeCouponPricingNo').attr('selected', true);
                              $('#cumulativeSalePricingAddTo').attr('selected', true);        
                            //  $('#discount_rule_max_amt_type_0').prop('selectedIndex',1);        
                            //  $('#discount_lifetime_max_amt_type_0').prop('selectedIndex',1);
                            //  $('#cumulativeRulePricing').prop('selectedIndex',1);
                            //  $('#cumulativeSalePricing').prop('selectedIndex',1);
                            //  $('#cumulativeCouponPricing').prop('selectedIndex',1);
                            // $('#discount_rule_cum_max_amt_type_0').prop('selectedIndex',1);
                              
                              $('.forThePriceOf-amt-literal-inserted').remove();
 
                             // $('#popChoiceOut').prop('selectedIndex',0);   
                            };       
                            
                            
                            function setDropdownsToInitalDefaults() {
                              buyAmtType_protect1();
                              buyAmtType_appliesTo_protect1();
                              buyAmtType_qualifier_protect1();
                              buyAmtType_repeat_protect1();                              
                              actionAmtType_protect1();
                              actionAmtType_appliesTo_protect1();
                              actionAmtType_qualifier_protect1();
                              actionAmtType_repeat_protect1();                               
                              discountAmtType_protect1();  //also does the reset..
                              discount_appliesTo_protect1();
                            };
                                                                                   
                            //testForExistingData, only done on return from server
                            function testForExistingData() {  
                              if ($("#firstTimeBackFromServer").val() != 'yes') {
                                 return;
                              };
                              
                              //MWN moved to "firstTimeThroughControl()" ==> "resetUpperFirstTimeSwitches()"
                              //reset the switch, only used 1st time in from server, in hidden html field...
                              //$("#firstTimeBackFromServer").val('no');
                              
                              //don't do this if template yet to be chosen, on data from server
                              if ($("#rule_template_framework").val() != "0") {
                                  buy_amt_line_remainder_chg();
                                  buy_amt_mod_count_area_chg();
                                  buy_repeat_count_area_chg();
                                  action_amt_line_remainder_chg();
                                  action_amt_mod_count_area_chg();
                                  action_repeat_count_area_chg();
                                  discount_amt_line_remainder_chg();
                                  discount_rule_max_amt_count_area_chg();
                                  discount_lifetime_max_amt_count_area_chg();
                                  cumulativeRulePricing_chg();
                                  discount_rule_cum_max_amt_count_area_chg();
                                  popChoiceInTest();   // Tests for 'selected...' 
                                  popChoiceOutTest();  // Tests for 'selected...' 
                              };
                              
                               
                            }; 
                            
                            function hideRemainderOfAllBoxes() { 
                              $(".buy_amt_line_remainder").hide();
                              $("#action_amt_line_remainder_0").hide();
                              /*$(".discount_amt_line_remainder").hide();*/ 
                              $("#buy_amt_mod_count_area_0").hide();
                              $("#buy_repeat_count_area_0").hide();
                              $("#action_amt_mod_count_area_0").hide();
                              $("#action_repeat_count_area_0").hide();
                              $("#discount_amt_count_area_0").hide();  
                              $("#discount_for_the_price_of_area_0").hide();
                              $("#discount_auto_add_free_product_label_0").hide();
                              $(".hide-discount-help").show("slow");                               
                          //    $("#priority_num").hide();   Handled elsewhere
                              $("#advanced-settings-anchor-plus").hide();    
                              $("#advanced-settings-anchor-minus").hide();
                              $("#advanced-settings-info").hide();  
                              $("#discount_rule_max_amt_msg").hide();
                              $("#discount_rule_max_amt_count_area").hide();
                              $("#discount_lifetime_max_amt_msg").hide();
                              $("#discount_lifetime_max_amt_count_area").hide();
                              $("#discount_rule_cum_max_amt_msg").hide();
                              $("#discount_rule_cum_max_amt_count_area").hide();
                            }; 

                            
                            /*  Buy Amount Type   */                                                         
                            //    protect all except *'none'*      
                            function buyAmtType_protect1() { 
                              $('.buy_amt_type_none').attr('disabled', false);
                              $('.buy_amt_type_none').attr('selected', true);
                              $('.buy_amt_type_one').attr('disabled', true);
                              $('.buy_amt_type_qty').attr('disabled', true);
                              $('.buy_amt_type_currency').attr('disabled', true);
                              $('.buy_amt_type_nthQty').attr('disabled', true);
                            }; 
                            //    protect all except *'nthQty'*      
                            function buyAmtType_protect2() { 
                              $('.buy_amt_type_none').attr('disabled', true);
                              $('.buy_amt_type_one').attr('disabled', true);
                              $('.buy_amt_type_qty').attr('disabled', true);
                              $('.buy_amt_type_currency').attr('disabled', true);
                              $('.buy_amt_type_nthQty').attr('disabled', false);
                              $('.buy_amt_type_nthQty').attr('selected', true);
                            };                                 
                            //     reset the attributs and selections
                            function buyAmtType_reset() { 
                              $('.buy_amt_type_none').attr('disabled', false);
                              if ($("#templateChanged").val() == 'yes') {
                                $('.buy_amt_type_none').attr('selected', true);
                              };    
                              $('.buy_amt_type_one').attr('disabled', false);
                              $('.buy_amt_type_qty').attr('disabled', false);
                              $('.buy_amt_type_currency').attr('disabled', false);
                              $('.buy_amt_type_nthQty').attr('disabled', false);                              
                            };
                             
                             //buyAmtType_appliesTo
                            function buyAmtType_appliesTo_protect1() { 
                              $('.buy_amt_applies_to_all').attr('disabled', false);
                              if ($("#templateChanged").val() == 'yes') {
                                $('.buy_amt_applies_to_all').attr('selected', true);
                              };                              
                              $('.buy_amt_applies_to_each').attr('disabled', true);
                            };
                            function buyAmtType_appliesTo_protect2() { 
                              $('.buy_amt_applies_to_all').attr('disabled', true);
                              $('.buy_amt_applies_to_each').attr('disabled', false);
                              $('.buy_amt_applies_to_each').attr('selected', true);                              
                              
                            };
                             function buyAmtType_appliesTo_reset() { 
                              $('.buy_amt_applies_to_all').attr('disabled', false);    
                              if ($("#templateChanged").val() == 'yes') {
                                $('.buy_amt_applies_to_all').attr('selected', true);
                              }; 
                              $('.buy_amt_applies_to_each').attr('disabled', false);
                            };
                             
                            function buyAmtType_qualifier_protect1() { 
                              $('.buy_amt_mod_none').attr('disabled', false);
                              $('.buy_amt_mod_none').attr('selected', true);
                              $('.buy_amt_mod_minCurrency').attr('disabled', true);
                              $('.buy_amt_mod_maxCurrency').attr('disabled', true);
                            };
                            function buyAmtType_qualifier_reset() { 
                              $('.buy_amt_mod_none').attr('disabled', false)        
                              if ($("#templateChanged").val() == 'yes') {
                                $('.buy_amt_mod_none').attr('selected', true);
                              };
                              $('.buy_amt_mod_minCurrency').attr('disabled', false);
                              $('.buy_amt_mod_maxCurrency').attr('disabled', false);
                            };
                            
                            function buyAmtType_repeat_protect1() { 
                              
                              switch( $("#rule_template_framework").val() ) {
                                case "0":                    
                                case "D-storeWideSale":   
                                case "D-simpleDiscount":                                                            
                                    $('.buy_repeat_condition_none').attr('disabled', false);
                                    
                                    //MWNTEST
                                    //$('.buy_repeat_condition_none').attr('selected', true);
                                    if ($("#templateChanged").val() == 'yes') {
                                      $('.buy_repeat_condition_none').attr('selected', true);
                                    };
                                    
                                    $('.buy_repeat_condition_unlimited').attr('disabled', true);
                                    $('.buy_repeat_condition_count').attr('disabled', true);
                                  break;
                                default:
                                  break;
                              };
                            };
                            function buyAmtType_repeat_reset() { 
                              $('.buy_repeat_condition_none').attr('disabled', false);
                              if ($("#templateChanged").val() == 'yes') {
                                $('.buy_repeat_condition_unlimited').attr('selected', true);
                              };
                              $('.buy_repeat_condition_unlimited').attr('disabled', false);
                              $('.buy_repeat_condition_count').attr('disabled', false);
                            };
                            function buyAmtType_repeat_reset2() { 
                              $('.buy_repeat_condition_none').attr('disabled', false);
                              $('.buy_repeat_condition_unlimited').attr('disabled', false);
                              $('.buy_repeat_condition_count').attr('disabled', false);
                            };                            
                                                         
                             /*  Action Amount Type   */                                                         
                            //    protect all except *'zero'*      
                            function actionAmtType_protect1() { 
                              $('.action_amt_type_none').attr('disabled', false);
                              $('.action_amt_type_none').attr('selected', true);
                            //  $('.action_amt_type_zero').attr('disabled', true);
                              $('.action_amt_type_one').attr('disabled', true);
                              $('.action_amt_type_qty').attr('disabled', true);
                              $('.action_amt_type_currency').attr('disabled', true);
                              $('.action_amt_type_nthQty').attr('disabled', true);
                            }; 
                            //    protect all except *'nthQty'*      
                            function actionAmtType_protect2() { 
                              $('.action_amt_type_none').attr('disabled', true);
                            //  $('.action_amt_type_zero').attr('disabled', true);
                              $('.action_amt_type_one').attr('disabled', true);
                              $('.action_amt_type_qty').attr('disabled', true);
                              $('.action_amt_type_currency').attr('disabled', true);
                              $('.action_amt_type_nthQty').attr('disabled', false);
                              if ($("#templateChanged").val() == 'yes') {
                                $('.action_amt_type_nthQty').attr('selected', true);
                              };
                            };
                            function actionAmtType_protect3() { 
                              $('.action_amt_type_none').attr('disabled', true);
                            //  $('.action_amt_type_zero').attr('disabled', true);
                              $('.action_amt_type_one').attr('disabled', true);
                              $('.action_amt_type_qty').attr('disabled', false);
                              if ($("#templateChanged").val() == 'yes') {
                                $('.action_amt_type_qty').attr('selected', true);
                              };                              
                              $('.action_amt_type_currency').attr('disabled', false);
                              $('.action_amt_type_nthQty').attr('disabled', true);
                            };  
                            function actionAmtType_protect4() { 
                              $('.action_amt_type_none').attr('disabled', true);
                            //  $('.action_amt_type_zero').attr('disabled', false);
                              if ($("#templateChanged").val() == 'yes') {
                               // $('.action_amt_type_zero').attr('selected', true);
                                $('.action_amt_type_qty').attr('selected', true);
                              };
                              $('.action_amt_type_one').attr('disabled', true);
                              $('.action_amt_type_qty').attr('disabled', false);                              
                              $('.action_amt_type_currency').attr('disabled', false);
                              $('.action_amt_type_nthQty').attr('disabled', true);
                            };
                            function actionAmtType_protect5() {  //standard 'next' behavior  
                              $('.action_amt_type_none').attr('disabled', true);
                            //  $('.action_amt_type_zero').attr('disabled', true);
                              $('.action_amt_type_one').attr('disabled', false);
                              $('.action_amt_type_qty').attr('disabled', false);                              
                              $('.action_amt_type_currency').attr('disabled', false);
                              $('.action_amt_type_nthQty').attr('disabled', true);
                              if ($("#templateChanged").val() == 'yes') {
                                $('.action_amt_type_one').attr('selected', true);
                              };
                            };                                
                            //     reset the attributs and selections
                            function actionAmtType_reset() { 
                              $('.action_amt_type_none').attr('disabled', false);     
                              if ($("#templateChanged").val() == 'yes') {
                                $('.action_amt_type_none').attr('selected', true);
                              };
                           //   $('.action_amt_type_zero').attr('disabled', false);
                              $('.action_amt_type_one').attr('disabled', false);
                              $('.action_amt_type_qty').attr('disabled', false);
                              $('.action_amt_type_currency').attr('disabled', false);
                              $('.action_amt_type_nthQty').attr('disabled', false);
                            };
                            
                            //********************************************************************************************************
                            //  If template type is 'next', change the action type dropdown wording to include 'Next on the fly'
                            //********************************************************************************************************
                            function actionAmtType_change_text_to_include_Next() { 
                              reload_action_amt_type_Titles2(); 
                            };
                            function actionAmtType_change_text_to_remove_Next() {  
                              reload_action_amt_type_Titles1();  
                            };

                             
                             //actionAmtType_appliesTo
                             function actionAmtType_appliesTo_protect1() { 
                              $('.action_amt_applies_to_all').attr('disabled', false);
                              $('.action_amt_applies_to_all').attr('selected', true);
                              $('.action_amt_applies_to_each').attr('disabled', true);
                            };
                             function actionAmtType_appliesTo_reset() { 
                              $('.action_amt_applies_to_all').attr('disabled', false);  
                              if ($("#templateChanged").val() == 'yes') {
                                $('.action_amt_applies_to_all').attr('selected', true);
                              };
                              $('.action_amt_applies_to_each').attr('disabled', false);
                            };                            
                                                         
                            //actionAmtType_qualifier_protect1
                            function actionAmtType_qualifier_protect1() { 
                              $('.action_amt_mod_none').attr('disabled', false);
                              $('.action_amt_mod_none').attr('selected', true);
                              $('.action_amt_mod_minCurrency').attr('disabled', true);
                              $('.action_amt_mod_maxCurrency').attr('disabled', true);
                            };
                            function actionAmtType_qualifier_reset() { 
                              $('.action_amt_mod_none').attr('disabled', false);
                              if ($("#templateChanged").val() == 'yes') {
                                $('.action_amt_mod_none').attr('selected', true);
                              };
                              $('.action_amt_mod_minCurrency').attr('disabled', false);
                              $('.action_amt_mod_maxCurrency').attr('disabled', false);
                            };                            
  
                            //actionAmtType_qualifier_protect1
                            function actionAmtType_repeat_protect1() { 
                              $('.action_repeat_condition_none').attr('disabled', false);
                              $('.action_repeat_condition_none').attr('selected', true); 
                              $('.action_repeat_condition_unlimited').attr('disabled', true);
                              $('.action_repeat_condition_count').attr('disabled', true);
                            };                             
                            function actionAmtType_repeat_reset() { 
                              $('.action_repeat_condition_none').attr('disabled', false); 
                              if ($("#templateChanged").val() == 'yes') {
                                $('.action_repeat_condition_none').attr('selected', true);
                              };
                              $('.action_repeat_condition_unlimited').attr('disabled', false);
                              $('.action_repeat_condition_count').attr('disabled', false);
                            };  
                            
                             /*  Discount Amount Type   */                                                         
                            //    protect none except *'discount_amt_type_forThePriceOf_Units'*      
                            function discountAmtType_protect1() {
                              $('.discount_amt_type_heading').attr('disabled', false); 
                              if ($("#templateChanged").val() == 'yes') {
                                $('.discount_amt_type_heading').attr('selected', true);
                              };
                              $('.discount_amt_type_percent').attr('disabled', false);
                              $('.discount_amt_type_currency').attr('disabled', false);
                              $('.discount_amt_type_fixedPrice').attr('disabled', false);
                              $('.discount_amt_type_free').attr('disabled', false);    
                              $('.discount_amt_type_forThePriceOf_Units').attr('disabled', true);
                              $('.discount_amt_type_forThePriceOf_Currency').attr('disabled', true);
                            }; 
                            function discountAmtType_protect2() {
                              $('.discount_amt_type_heading').attr('disabled', true);
                              $('.discount_amt_type_percent').attr('disabled', true);
                              $('.discount_amt_type_currency').attr('disabled', true);
                              $('.discount_amt_type_fixedPrice').attr('disabled', true);
                              $('.discount_amt_type_free').attr('disabled', true);    
                              $('.discount_amt_type_forThePriceOf_Units').attr('disabled', false);
                              $('.discount_amt_type_forThePriceOf_Currency').attr('disabled', false);
                              /*  default reversed, //v1.0.7.6 begin
                              switch( $("#discount_amt_type_0").val() ) {                                    
                                  case "forThePriceOf_Currency":
                                     $('.discount_amt_type_forThePriceOf_Currency').attr('selected', true); break;                                                                                   
                                  default:  //case "forThePriceOf_Units":  set as default in case no choice as yet for discount_amt_type 
                                     $('.discount_amt_type_forThePriceOf_Units').attr('selected', true); break;
                              };
                              */
                              switch( $("#discount_amt_type_0").val() ) {     //v1.0.7.6 switched defaults                                
                                  case "forThePriceOf_Units":
                                     $('.discount_amt_type_forThePriceOf_Units').attr('selected', true); break;                                                                                   
                                  default:  //case "forThePriceOf_Units":  set as default in case no choice as yet for discount_amt_type        
                                     $('.discount_amt_type_forThePriceOf_Currency').attr('selected', true);  break;
                              };
                              // //v1.0.7.6 end                              
                            }; 
                            function discountAmtType_reset() { 
                              $('.discount_amt_type_heading').attr('disabled', false);
                              if ($("#templateChanged").val() == 'yes') {
                                $('.discount_amt_type_heading').attr('selected', true);
                              };
                              $('.discount_amt_type_percent').attr('disabled', false);
                              $('.discount_amt_type_currency').attr('disabled', false);
                              $('.discount_amt_type_fixedPrice').attr('disabled', false);
                              $('.discount_amt_type_free').attr('disabled', false);
                              $('.discount_amt_type_forThePriceOf_Units').attr('disabled', false);
                              $('.discount_amt_type_forThePriceOf_Currency').attr('disabled', false);
                            };

                             
                             //discount_appliesTo
                             function discount_appliesTo_protect1() { 
                              $('.discount_applies_to_title').attr('disabled', true);
                              $('.discount_applies_to_each').attr('disabled', false);
                              //$('.discount_applies_to_each').attr('selected', true);
                              if ($("#templateChanged").val() == 'yes') {
                                $('.discount_applies_to_each').attr('selected', true);
                              };                             
                              $('.discount_applies_to_all').attr('disabled', true);
                              $('.discount_applies_to_cheapest').attr('disabled', true);
                              $('.discount_applies_to_most_expensive').attr('disabled', true);
                            };
                             function discount_appliesTo_protect2() { 
                              $('.discount_applies_to_title').attr('disabled', true);
                              $('.discount_applies_to_each').attr('disabled', false); 
                              if ($("#templateChanged").val() == 'yes') {
                                $('.discount_applies_to_each').attr('selected', true);
                              };
                              $('.discount_applies_to_all').attr('disabled', false);
                              $('.discount_applies_to_cheapest').attr('disabled', true);
                              $('.discount_applies_to_most_expensive').attr('disabled', true);
                            };
                             function discount_appliesTo_protect3() { 
                              $('.discount_applies_to_title').attr('disabled', true);
                              $('.discount_applies_to_each').attr('disabled', true);
                              $('.discount_applies_to_all').attr('disabled', true);
                              $('.discount_applies_to_cheapest').attr('disabled', false);
                              if ($("#templateChanged").val() == 'yes') {
                                $('.discount_applies_to_cheapest').attr('selected', true);
                              };
                              $('.discount_applies_to_most_expensive').attr('disabled', false);
                            };
                             function discount_appliesTo_protect4() { //used only for 'forthepriceof'
                              $('.discount_applies_to_title').attr('disabled', true);
                              $('.discount_applies_to_each').attr('disabled', true);  
                              if ($("#templateChanged").val() == 'yes') {
                                $('.discount_applies_to_all').attr('selected', true);
                              };
                              $('.discount_applies_to_all').attr('disabled', false);
                              $('.discount_applies_to_cheapest').attr('disabled', true);
                              $('.discount_applies_to_most_expensive').attr('disabled', true);
                            };
                            //v1.0.7.6 function added
                            function discount_appliesTo_protect5() { 
                              $('.discount_applies_to_title').attr('disabled', true);
                              $('.discount_applies_to_each').attr('disabled', false);  
                              $('.discount_applies_to_all').attr('selected', true);
                              $('.discount_applies_to_all').attr('disabled', false);
                              $('.discount_applies_to_cheapest').attr('disabled', true);
                              $('.discount_applies_to_most_expensive').attr('disabled', true);
                            };
                             function discount_appliesTo_reset() { 
                              $('.discount_applies_to_title').attr('disabled',false);
                              if ($("#templateChanged").val() == 'yes') {
                                $('.discount_applies_to_title').attr('selected', true);
                              };
                              $('.discount_applies_to_each').attr('disabled', false);
                              $('.discount_applies_to_all').attr('disabled', false);
                              $('.discount_applies_to_cheapest').attr('disabled', false);
                              $('.discount_applies_to_most_expensive').attr('disabled', false);
                            };
                            
                            //  setAttribsFor_ForThePriceOf
                             function setAttribsFor_ForThePriceOf() {
                                /*  //v1.0.7.6  begin  -  values reversed
                                switch( $("#discount_amt_type_0").val() ) {                                    
                                  case "forThePriceOf_Currency":
                                     setAttribsFor_ForThePriceOf_Currency();     
                                     break;                                                                                   
                                  default:  //case "forThePriceOf_Units":  set as default in case no choice as yet for discount_amt_type 
                                     setAttribsFor_ForThePriceOf_Units();
                                     break;
                                };
                                */
                                switch( $("#discount_amt_type_0").val() ) {                                    
                                  case "forThePriceOf_Units":
                                     setAttribsFor_ForThePriceOf_Units();     
                                     break;                                                                                   
                                  default:  //set currency as default in case no choice as yet for discount_amt_type                                      
                                     setAttribsFor_ForThePriceOf_Currency();
                                     break;
                                };
                                //v1.0.7.6 end                                
                             };
                              
                             function setAttribsFor_ForThePriceOf_Currency() {  
                              $('#buy_amt_type_none_0').attr('disabled', true);              
                              $('#buy_amt_type_one_0').attr('disabled', true);
                              $('#buy_amt_type_qty_0').attr('disabled', false);
                              $('#buy_amt_type_qty_0').attr('selected', true);
                              $('#buy_amt_type_currency_0').attr('disabled', true);
                              $('#buy_amt_type_nthQty_0').attr('disabled', true);                               
                              buy_amt_line_remainder_chg();
                              buyAmtType_appliesTo_protect1();
                              actionAmtType_protect1();
                              discountAmtType_protect2();  
                              $("#discount_amt_count_area_0").show("slow");
                              $(".discount_amt_count_literal_0").hide("slow");
                              $("#discount_amt_count_literal_forThePriceOf_buyAmt_0").show("slow"); 
                              $("#discount_amt_count_literal_forThePriceOf_0").show("slow");
                              $("#discount_amt_count_literal_forThePriceOf_Currency_0").show("slow"); 
                              discount_appliesTo_protect4(); 
                              $("#discount_amt_count_literal_units_area_0").hide("slow");  
                            };
                            
                             function setAttribsFor_ForThePriceOf_Units() {  
                              $('#buy_amt_type_none_0').attr('disabled', true);              
                              $('#buy_amt_type_one_0').attr('disabled', true);
                              $('#buy_amt_type_qty_0').attr('disabled', false);
                              $('#buy_amt_type_qty_0').attr('selected', true);
                              $('#buy_amt_type_currency_0').attr('disabled', true);
                              $('#buy_amt_type_nthQty_0').attr('disabled', true);                               
                              buy_amt_line_remainder_chg();
                              buyAmtType_appliesTo_protect1();                              
                              actionAmtType_protect1();
                              discountAmtType_protect2(); 
                              $("#discount_amt_count_area_0").show("slow");
                              $(".discount_amt_count_literal_0").hide("slow");
                              $("#discount_amt_count_literal_forThePriceOf_buyAmt_0").show("slow"); 
                              $("#discount_amt_count_literal_forThePriceOf_0").show("slow");
                              $("#discount_amt_count_literal_forThePriceOf_Currency_0").hide("slow");   
                              $("#discount_amt_count_literal_units_area_0").show("slow");
                              discount_appliesTo_protect4();  
                            };
                            
                            //  setAttribsFor_Cheapest
                             function setAttribsFor_Cheapest() { 
                              $('#buy_amt_type_one_0').attr('disabled', true);
                              $('#buy_amt_type_nthQty_0').attr('disabled', true);                               
                              buyAmtType_appliesTo_protect1();                             
                              actionAmtType_protect1();
                              discountAmtType_protect1();   
                              discount_appliesTo_protect3();  
                            };
                            
                            //  setAttribsFor_nextNumOrCurrency
                             function setAttribsFor_nextNumOrCurrency() { 
                              discountAmtType_protect1();   
                              discount_appliesTo_protect2();
                              actionAmtType_protect5();  
                            };
                            
                            //  setAttribsFor_nextForThePriceOf
                             function setAttribsFor_nextForThePriceOf() { 
                              $('.action_amt_type_none').attr('disabled', true);
                          //    $('.action_amt_type_zero').attr('disabled', true);
                              $('.action_amt_type_one').attr('disabled', true);
                              $('.action_amt_type_qty').attr('disabled', false);
                              $('.action_amt_type_qty').attr('selected', true);
                              $('.action_amt_type_currency').attr('disabled', true);
                              $('.action_amt_type_nthQty').attr('disabled', true);
                              action_amt_line_remainder_chg();
                              discountAmtType_protect2();
                              $("#discount_amt_count_area_0").show("slow"); 
                              $(".discount_amt_count_literal_0").hide("slow"); 
                              $("#discount_amt_count_literal_forThePriceOf_buyAmt_0").show("slow");
                              $("#discount_amt_count_literal_forThePriceOf_0").show("slow");   
                              $("#discount_amt_count_literal_units_area_0").show("slow");  
                              discount_appliesTo_protect4(); 
                            };

                            //  setAttribsFor_NextCheapest
                             function setAttribsFor_NextCheapest() {                              
                              actionAmtType_protect3();
                              action_amt_line_remainder_chg();
                              actionAmtType_appliesTo_protect1()
                              discountAmtType_protect1();   
                              discount_appliesTo_protect3(); 
                            };

                            //  setAttribsFor_NextNth
                             function setAttribsFor_NextNth() {                               
                              actionAmtType_protect2();
                              action_amt_line_remainder_chg();
                              discountAmtType_protect1();   
                              discount_appliesTo_protect2(); 
                            };
                                                 
                            /*  PopChoiceIn/Out   */           
                            //    protect protect all except All Store ('cartChoiceIn')
                            function setWholeStoreOrCartContentsIn() {
                              //inPopRadio                             
                              $('#cartChoiceIn').attr('selected', true);
                              $('#groupChoiceIn').attr('disabled', true);
                              $('#varChoiceIn').attr('disabled', true);
                              $('#singleChoiceIn').attr('disabled', true);
                            };   
                            function setSameAsBuyGroupOnly() {
                              //outPopRadio                              
                              $('#sameChoiceOut').attr('selected', true);
                              $('#cartChoiceOut').attr('disabled', true);
                              $('#groupChoiceOut').attr('disabled', true);
                              $('#varChoiceOut').attr('disabled', true);
                              $('#singleChoiceOut').attr('disabled', true);
                            
                            };                                                    
                            function popChoiceIn_reset() {
                              //inPopRadio
                              $('#cartChoiceIn').attr('disabled', false);
                              $('#groupChoiceIn').attr('disabled', false);
                              $('#varChoiceIn').attr('disabled', false);
                              $('#singleChoiceIn').attr('disabled', false);                            
                            };
                            function popChoiceOut_reset() {
                              //inPopRadio
                              $('#sameChoiceOut').attr('disabled', false);
                              $('#cartChoiceOut').attr('disabled', false);
                              $('#groupChoiceOut').attr('disabled', false);
                              $('#varChoiceOut').attr('disabled', false);
                              $('#singleChoiceOut').attr('disabled', false);                            
                            };
                            //KEEP this
                            function changeCumulativeSwitches() {
                              /* always SET THESE TO 'YES' BY DEFAULT!!!!
                              switch( $("#rule_template_framework").val() ) {
                                case "0":                   
                                case "D-storeWideSale":     
                                case "D-simpleDiscount":    setCumulativeSwitchesNo();     break;
                                default:                    setCumulativeSwitchesYes();    break;                              
                              };
                              */
                              setCumulativeSwitchesYes();
                              
                            };                             
                            /*
                            function setCumulativeSwitchesNo() {                          
                              $("#cumulativeRulePricing").val('no');
                              $("#cumulativeCouponPricing").val('no');
                              $("#cumulativeSalePricing").val('no');
                              $('#cumulativeRulePricingYes').attr('disabled', true);
                              $('#cumulativeCouponPricingYes').attr('disabled', true);
                              $('#cumulativeSalePricingAddTo').attr('disabled', true);
                              $('#cumulativeSalePricingReplace').attr('disabled', true);
                              cumulativeRulePricing_chg();                           
                            };
                            */  
                            function setCumulativeSwitchesYes() {
                              //only do this if NOT 1st time and 1st time data present
                              if ($("#upperSelectsHaveDataFirstTime").val() == 0 ) {
                                $("#cumulativeRulePricing").val('yes');
                                $("#cumulativeCouponPricing").val('yes');
                                $("#cumulativeSalePricing").val('addToSalePrice');  //v1.0.3
                                $("#ruleApplicationPriority_num").val('10');
                              }

                              $('#cumulativeRulePricingYes').attr('disabled', false);
                              $('#cumulativeCouponPricingYes').attr('disabled', false);
                              $('#cumulativeSalePricingAddTo').attr('disabled', false);
                              $('#cumulativeSalePricingReplace').attr('disabled', false);
                              cumulativeRulePricing_chg();
                            };                                                     
                                                        
            // MASTER TEMPLATE choice routine END
            
           
            //Prompt messages for required fields        
                      	// input on focus  FUNCTION - REMOVE msg so they can type
                    		jQuery("#discount_product_full_msg[type=text], #discount_product_short_msg[type=text]").focus(function() {
                    			
                     //     var default_value = this.value;			
                    //			if(this.value === default_value) {
                    //				this.value = '';
                    //			}
                           
                          var id = jQuery(this).attr('id'); 
                          if (id == 'discount_product_full_msg') {
                    				if (this.value === $("#fullMsg").val()) {
                              this.value = '';
                            }
                    			}
                          if (id == 'discount_product_short_msg') {
                    				if (this.value === $("#shortMsg").val()) {
                              this.value = '';
                            }
                    			}                          
                    			//jQuery(this).removeClass('blur');
                    			//return css to normal!!
                          jQuery(this).css("color","#000").css("font-style","normal");
                    		});
                       
                        
                        //FUNCTION - put msg back if nothing is there!!!
                    		jQuery("#discount_product_full_msg[type=text], #discount_product_short_msg[type=text]").blur(function() {                    				
                          var id = jQuery(this).attr('id');
                    			if (id == 'discount_product_full_msg') {
                    				var default_value = $("#fullMsg").val();
                    			} 
                          if (id == 'discount_product_short_msg') {
                    				var default_value = $("#shortMsg").val();
                    			} 
                    			if(this.value === '') {
                    				this.value = default_value;
                    			//return css to normal!!
                          jQuery(this).css("color","#666666").css("font-style","italic");
                    			}                    			
                    		});          
             
            //****************************
            // LINE CONTROLS   Begin
            //****************************                                                                
                            //Buy Pool Amount Condition Type                            
                            $('#buy_amt_type_0').change(function(){
                                buy_amt_line_remainder_chg();                                                                                   
                            });                                     
                             function buy_amt_line_remainder_chg() {     
                              switch( $("#buy_amt_type_0").val() ) {                                
                                case "none":         $(".buy_amt_line_remainder").hide("slow");                                                   
                                case "one":          $(".buy_amt_line_remainder").hide("slow");  
                                                     break;
                                case "quantity":     $(".buy_amt_line_remainder").show("slow");
                                                     $(".buy_amt_count_literal_0").hide("slow"); 
                                                     $("#buy_amt_count_literal_quantity_0").show("slow");   
                                                     break;
                                case "currency":     $(".buy_amt_line_remainder").show("slow");
                                                     $(".buy_amt_count_literal_0").hide("slow"); 
                                                     $("#buy_amt_count_literal_currency_0").show("slow");   
                                                     break;
                                case "nthQuantity":  $(".buy_amt_line_remainder").show("slow");
                                                     $(".buy_amt_count_literal_0").hide("slow"); 
                                                     $("#buy_amt_count_literal_nthQuantity_0").show("slow");   
                                                     break;                                                               
                              };
                            }; 

                            //Buy Pool Amount Mod Type
                            $('#buy_amt_mod_0').change(function(){
                                buy_amt_mod_count_area_chg();                                                   
                            });                                     
                             function buy_amt_mod_count_area_chg() {     
                              switch( $("#buy_amt_mod_0").val() ) {                                
                                case "none":         $("#buy_amt_mod_count_area_0").hide("slow");  break;                                                               
                                case "minCurrency":  $("#buy_amt_mod_count_area_0").show("slow");  break;
                                case "maxCurrency":  $("#buy_amt_mod_count_area_0").show("slow");  break;                                                         
                              };
                            };
                            
                             //Buy Repeat Condition Type
                            $('#buy_repeat_condition_0').change(function(){
                                buy_repeat_count_area_chg();                                                   
                            });                                     
                             function buy_repeat_count_area_chg() {  
                              switch( $("#buy_repeat_condition_0").val() ) {                                
                                case "none":       $("#buy_repeat_count_area_0").hide("slow");  break;                                                               
                                case "unlimited":  $("#buy_repeat_count_area_0").hide("slow");  break;
                                case "count":      $("#buy_repeat_count_area_0").show("slow");  break;                                                         
                              };
                            };
                            
                            //Action Pool Amount Condition Type
                            $('#action_amt_type_0').change(function(){
                                action_amt_line_remainder_chg();                                                                                   
                            });                                     
                             function action_amt_line_remainder_chg() {     
                              switch( $("#action_amt_type_0").val() ) {                                
                                case "none":         $(".action_amt_line_remainder").hide("slow");  
                                                      action_amt_subBoxes_test();  break;
                                case "zero":         $(".action_amt_line_remainder").hide("slow");  
                                                     action_amt_subBoxes_test();  break;                                                               
                                case "one":          $(".action_amt_line_remainder").hide("slow");  
                                                     action_amt_subBoxes_test();  break;
                                case "quantity":     $(".action_amt_line_remainder").show("slow");
                                                     $(".action_amt_count_literal_0").hide("slow"); 
                                                     $("#action_amt_count_literal_quantity_0").show("slow");   
                                                     action_amt_subBoxes_test();  break;
                                case "currency":     $(".action_amt_line_remainder").show("slow");
                                                     $(".action_amt_count_literal_0").hide("slow"); 
                                                     $("#action_amt_count_literal_currency_0").show("slow");   
                                                     action_amt_subBoxes_test();  break;
                                case "nthQuantity":  $(".action_amt_line_remainder").show("slow");
                                                     $(".action_amt_count_literal_0").hide("slow"); 
                                                     $("#action_amt_count_literal_nthQuantity_0").show("slow");   
                                                     action_amt_subBoxes_test();  break;                               
                              };
                            };
                             function action_amt_subBoxes_test() {     
                              switch( $("#action_amt_type_0").val() ) {                                
                                case "none":         $('.action_amt_mod').attr("disabled", "disabled");
                                                     $('.action_repeat_condition').attr("disabled", "disabled");                                                               
                                default:             $('.action_amt_mod').removeAttr('disabled');
                                                     $('.action_repeat_condition').removeAttr('disabled')                                                              
                              };
                            };
                                                        
                            //Action Pool Amount Mod Type
                            $('#action_amt_mod_0').change(function(){
                                action_amt_mod_count_area_chg();                                                   
                            });                                     
                             function action_amt_mod_count_area_chg() {     
                              switch( $("#action_amt_mod_0").val() ) {                                
                                case "none":         $("#action_amt_mod_count_area_0").hide("slow");  break;                                                               
                                case "minCurrency":  $("#action_amt_mod_count_area_0").show("slow");  break;
                                case "maxCurrency":  $("#action_amt_mod_count_area_0").show("slow");  break;                                                         
                              };
                            };
                            
                             //Action Repeat Condition Type
                            $('#action_repeat_condition_0').change(function(){
                                action_repeat_count_area_chg();                                                   
                            });                                     
                             function action_repeat_count_area_chg() {     
                              switch( $("#action_repeat_condition_0").val() ) {                                
                                case "none":       $("#action_repeat_count_area_0").hide("slow");  break;                                                               
                                case "unlimited":  $("#action_repeat_count_area_0").hide("slow");  break;
                                case "count":      $("#action_repeat_count_area_0").show("slow");  break;                                                         
                              };
                            };
                                                        
                            //Discount Amount Condition Type
                            $('#discount_amt_type_0').change(function(){
                                $("#chg_detected_sw").val('yes');  //v1.0.7.6
                                discount_amt_line_remainder_chg();                                                                                                                                                   
                            });                                     
                             
                             function discount_amt_line_remainder_chg() {     
                                                          
                              switch( $("#discount_amt_type_0").val() ) {                                
                                case "0":            $("#discount_amt_count_area_0").hide("slow"); 
                                                     $("#discount_for_the_price_of_area_0").hide("slow");
                                                     $("#discount_auto_add_free_product_label_0").hide("slow");
                                                     $(".hide-discount-help").show("slow");                                                    
                                                      //catch the two 'cheapest' and process differently...
                                                      switch( $("#rule_template_framework").val() ) {
                                                        case "C-cheapest-inCart":       
                                                        case "C-cheapest-Next":  
                                                            discount_appliesTo_protect3();
                                                          break;
                                                        default: 
                                                            discount_appliesTo_protect1();
                                                          break;                            
                                                      };
                                                     $('.discount_auto_add_free_product').removeAttr('checked');                                                                                                      
                                                     break; 
                                                                                                                   
                                case "percent":      $("#discount_amt_count_area_0").show("slow"); 
                                                     $(".discount_amt_count_literal_0").hide("slow"); 
                                                     $("#discount_amt_count_literal_percent_0").show("slow");                                  
                                                     $("#discount_for_the_price_of_area_0").hide("slow");
                                                     $("#discount_auto_add_free_product_label_0").hide("slow"); 
                                                     $(".hide-discount-help").show("slow");
                                                     $('.discount_auto_add_free_product').removeAttr('checked');
                                                     //turn back on in case previous selection was fixedPrice 
                                                     
                                                     //v1.0.7.7  begin
                                                     //$('.discount_applies_to_all').attr('disabled', false);
                                                     if ($("#chg_detected_sw").val() == 'yes') {
                                                        if ($("#cart-or-catalog-select").val() == 'cart') {
                                                          discount_appliesTo_protect5(); //  set 'all' as default
                                                        } else {   //catalog requires each!!
                                                          discount_appliesTo_protect1(); //  set 'each' as default
                                                        }
                                                        
                                                        $("#chg_detected_sw").val('no');
                                                     }
                                                     //v1.0.7.7  end
                                                      
                                                     break;
                                                                                     
                                case "currency":     $("#discount_amt_count_area_0").show("slow"); 
                                                     $(".discount_amt_count_literal_0").hide("slow"); 
                                                     $("#discount_amt_count_literal_currency_0").show("slow");                                  
                                                     $("#discount_for_the_price_of_area_0").hide("slow");
                                                     $("#discount_auto_add_free_product_label_0").hide("slow");
                                                     $(".hide-discount-help").show("slow"); 
                                                     $('.discount_auto_add_free_product').removeAttr('checked');
                                                     //turn back on in case previous selection was fixedPrice 
                                                     $('.discount_applies_to_all').attr('disabled', false);
                                                     break;
                                                      
                                case "fixedPrice":   $("#discount_amt_count_area_0").show("slow");   
                                                     $(".discount_amt_count_literal_0").hide("slow"); 
                                                     $("#discount_amt_count_literal_currency_0").show("slow");                                  
                                                     $("#discount_for_the_price_of_area_0").hide("slow"); 
                                                     $("#discount_auto_add_free_product_label_0").hide("slow");
                                                     $(".hide-discount-help").show("slow");
                                                     $('.discount_auto_add_free_product').removeAttr('checked');
                                                     //disallow here!!  only allow 'applies to each' 
                                                     $('.discount_applies_to_all').attr('disabled', true);
                                                     break;
                                                     
                                case "free":         $("#discount_amt_count_area_0").hide("slow");   
                                                     $("#discount_for_the_price_of_area_0").hide("slow");
                                                     switch( $("#rule_template_framework").val() ) {
                                                        case "D-storeWideSale":
                                                        case "C-storeWideSale":                    
                                                        case "D-simpleDiscount":
                                                        case "C-storeWideSale":
                                                            $("#discount_auto_add_free_product_label_0").hide("slow");
                                                            $(".hide-discount-help").show("slow");
                                                          break;               
                                                        default:  //rest of the templates...
                                                            $(".hide-discount-help").hide("slow");
                                                            $("#discount_auto_add_free_product_label_0").show("slow");                                                              
                                                          break;                               
                                                     };
                                                     //disallow here!!  only allow 'applies to each' 
                                                     $('.discount_applies_to_all').attr('disabled', true); 
                                                     break;
                                                                                                          
                                case "forThePriceOf_Units":   
                                                     $("#discount_amt_count_area_0").show("slow");  
                                                     $(".discount_amt_count_literal_0").hide("slow");    
                                                     $("#discount_amt_count_literal_forThePriceOf_buyAmt_0").show("slow");
                                                     $("#discount_amt_count_literal_forThePriceOf_0").show("slow");   
                                                     $("#discount_amt_count_literal_units_area_0").show("slow");
                                                     $(".hide-discount-help").show("slow");
                                                     $("#discount_auto_add_free_product_label_0").hide("slow");
                                                     $(".hide-discount-help").show("slow"); 
                                                     $('.discount_auto_add_free_product').removeAttr('checked');
                                                     //allready removes discount_applies_to_all 
                                                     break;
                                                      
                                case "forThePriceOf_Currency":   
                                                     $("#discount_amt_count_area_0").show("slow");  
                                                     $(".discount_amt_count_literal_0").hide("slow");    
                                                     $("#discount_amt_count_literal_forThePriceOf_buyAmt_0").show("slow");
                                                     $("#discount_amt_count_literal_forThePriceOf_0").show("slow");
                                                     $("#discount_amt_count_literal_forThePriceOf_Currency_0").show("slow");   
                                                     $("#discount_amt_count_literal_units_area_0").hide("slow");
                                                     $(".hide-discount-help").show("slow");
                                                     $("#discount_auto_add_free_product_label_0").hide("slow");
                                                     $(".hide-discount-help").show("slow"); 
                                                     $('.discount_auto_add_free_product').removeAttr('checked');
                                                     //allready removes discount_applies_to_all
                                                     break;                                                                                   
                              };
                            }; 

                            //Discount Maximum for Rule across the Cart Type
                            $('#discount_rule_max_amt_type_0').change(function(){
                                discount_rule_max_amt_count_area_chg();
                            });                                     
                             function discount_rule_max_amt_count_area_chg() {                                  
                              switch( $("#discount_rule_max_amt_type_0").val() ) {                                
                                case "none":      $("#discount_rule_max_amt_count_area").hide("slow");  
                                                  ruleMaxMsgTest();  break;                                                               
                                case "quantity":  $("#discount_rule_max_amt_count_area").show("slow");                   
                                                  $(".discount_rule_max_amt_count_literal").hide("slow");  
                                                  $("#discount_rule_max_amt_count_literal_quantity").show("slow"); 
                                                  ruleMaxMsgTest();  break;
                                case "currency":  $("#discount_rule_max_amt_count_area").show("slow");                   
                                                  $(".discount_rule_max_amt_count_literal").hide("slow");  
                                                  $("#discount_rule_max_amt_count_literal_currency").show("slow"); 
                                                  ruleMaxMsgTest();  break; 
                                case "percent":   $("#discount_rule_max_amt_count_area").show("slow");                   
                                                  $(".discount_rule_max_amt_count_literal").hide("slow");  
                                                  $("#discount_rule_max_amt_count_literal_percent").show("slow"); 
                                                  ruleMaxMsgTest();  break;                                                                                                                                            
                              };
                            };
                            function ruleMaxMsgTest () {     
                              /*switch( $("#discount_rule_max_amt_type_0").val() ) {
                                case "none":    $("#discount_rule_max_amt_msg").hide("slow");    break;
                                default:        $("#discount_rule_max_amt_msg").delay(1500).show("slow");    break;                             
                              };
                              */
                            };
                                                          
                            //Customer Discount Maximum Lifetime Limit for Rule Type
                            $('#discount_lifetime_max_amt_type_0').change(function(){
                                discount_lifetime_max_amt_count_area_chg();                                                      
                            });                                     
                             function discount_lifetime_max_amt_count_area_chg() {     
                              switch( $("#discount_lifetime_max_amt_type_0").val() ) {                                
                                case "none":      $("#discount_lifetime_max_amt_count_area").hide("slow");  
                                              //    lifetimeMaxMsgTest();  
                                                  break;                                                               
                                case "quantity":  $("#discount_lifetime_max_amt_count_area").show("slow");                   
                                                  $(".discount_lifetime_max_amt_count_literal").hide("slow");  
                                                  $("#discount_lifetime_max_amt_count_literal_quantity").show("slow"); 
                                               //   lifetimeMaxMsgTest();  
                                                  break;
                                case "currency":  $("#discount_lifetime_max_amt_count_area").show("slow");                   
                                                  $(".discount_lifetime_max_amt_count_literal").hide("slow");  
                                                  $("#discount_lifetime_max_amt_count_literal_currency").show("slow"); 
                                               //   lifetimeMaxMsgTest();  
                                                  break;                                                                                                                                      
                              };
                            };
                            function lifetimeMaxMsgTest () {     
                              switch( $("#discount_lifetime_max_amt_type_0").val() ) {
                                case "none":    $("#discount_lifetime_max_amt_msg").hide("slow");    break;
                                default:        $("#discount_lifetime_max_amt_msg").delay(1500).show("slow");     break;                             
                              };
                            };
                                                          
                             // Cumulative Pricing Settings:  Apply this Rule Discount in Addition to Other Rule Discounts
                            $('#cumulativeRulePricing').change(function(){
                                cumulativeRulePricing_chg();                                                   
                            });                                     
                             function cumulativeRulePricing_chg() {                                   
                              switch( $("#cumulativeRulePricing").val() ) {                                
                                /*  v1.0.7.4  allow priority to show at all times
                                case "no":   $("#priority_num").hide("slow");  
                                             $("#ruleApplicationPriority_num").val('10');  //clear out the priority numbreak;                                                               
                                   break; 
                                */
                                case "yes":  $("#priority_num").show("slow");
                                /*
                                small bug - if the delete or back key was used by the user to clear out the priority num field,
                                            that key value sticks around and overrides the display of the '10' value in FF
                                            however the value is still there, and will be processed correctly... 
                                */
                                             if ( $("#ruleApplicationPriority_num").val() <= 0 ) {                          
                                                $("#ruleApplicationPriority_num").val('10');  //init the priority num to '10'                      
                                             }; 
                                  break;                                                                                                                                 
                              };
                            };
                                                        
                            //Discount Cumulative Product Maximum for all Discounts
                            $('#discount_rule_cum_max_amt_type_0').change(function(){
                                discount_rule_cum_max_amt_count_area_chg();
                            });                                     
                             function discount_rule_cum_max_amt_count_area_chg() {     
                              switch( $("#discount_rule_cum_max_amt_type_0").val() ) {                                
                                case "none":      $("#discount_rule_cum_max_amt_count_area").hide("slow");  
                                                  cumMaxMsgTest();  break;                                                               
                                case "quantity":  $("#discount_rule_cum_max_amt_count_area").show("slow");                   
                                                  $(".discount_rule_cum_max_amt_count_literal").hide("slow");  
                                                  $("#discount_rule_cum_max_amt_count_literal_quantity").show("slow"); 
                                                  cumMaxMsgTest();  break;
                                case "currency":  $("#discount_rule_cum_max_amt_count_area").show("slow");                   
                                                  $(".discount_rule_cum_max_amt_count_literal").hide("slow");  
                                                  $("#discount_rule_cum_max_amt_count_literal_currency").show("slow"); 
                                                  cumMaxMsgTest();  break; 
                                case "percent":   $("#discount_rule_cum_max_amt_count_area").show("slow");                   
                                                  $(".discount_rule_cum_max_amt_count_literal").hide("slow");  
                                                  $("#discount_rule_cum_max_amt_count_literal_percent").show("slow"); 
                                                  cumMaxMsgTest();  break;                                                                                                                                            
                              };
                            };
                            function cumMaxMsgTest() {     
                             /* switch( $("#discount_rule_cum_max_amt_type_0").val() ) {
                                case "none":    $("#discount_rule_cum_max_amt_msg").hide("slow");    break;
                                default:        $("#discount_rule_cum_max_amt_msg").delay(1500).show("slow");     break;                             
                              };*/
                            };                                                                             
                            
                                                                                  
                            $('#buy_amt_count_0').change(function(){
                                frameWork = $("#rule_template_framework").val();
                                if (frameWork == "C-forThePriceOf-inCart") {    //forThePriceOf-same-group-discount
                                  insertBuyAmt(); 
                                };
                            });
                            function insertBuyAmt() { 
                              $('.forThePriceOf-amt-literal-inserted').remove();
                              insertVal  = '<span class="forThePriceOf-amt-literal-inserted discount_amt_count_literal  discount_amt_count_literal_0 " id="discount_amt_count_literal_forThePriceOf_buyAmt_0">';                              
                              insertVal += ' Buy ';
                              insertVal += $('#buy_amt_count_0').val();
                              insertVal += ' </span>';
                              $(insertVal).insertBefore('#discount_amt_count_literal_forThePriceOf_0');
                            };
                            
                            $('#action_amt_count_0').change(function(){
                                frameWork = $("#rule_template_framework").val();
                                if (frameWork == "C-forThePriceOf-Next") {    //forThePriceOf-other-group-discount
                                  insertGetAmt(); 
                                };
                            });
                            function insertGetAmt() { 
                              $('.forThePriceOf-amt-literal-inserted').remove();
                              insertVal  = '<span class="forThePriceOf-amt-literal-inserted discount_amt_count_literal  discount_amt_count_literal_0 " id="discount_amt_count_literal_forThePriceOf_buyAmt_0">';
                              insertVal += ' Get ';
                              insertVal += $('#action_amt_count_0').val();
                              insertVal += ' </span>';
                              $(insertVal).insertBefore('#discount_amt_count_literal_forThePriceOf_0');
                            };

            
            // LINE CONTROLS   eND
             
             
            //****************************
            // HELP PANELS "drop-ups/downs"   Begin  (with more/less anchors)
            //****************************  
                        
                            //Show special documentation panels.  Only show OnClick, not on rule display...
                            
                            $("#pricing-deal-title-more").click(function(){
                                $(".selection-panel-0").show("slow"); 
                                $("#pricing-deal-title-more").hide();
                                $("#pricing-deal-title-more2").hide();
                                $("#pricing-deal-title-less").show("slow");
                                $("#pricing-deal-title-less2").show("slow");                            
                            });
                            $("#pricing-deal-title-less").click(function(){
                                $(".selection-panel-0").hide("slow");  //hide all selection-panels
                                $("#pricing-deal-title-less").hide();
                                $("#pricing-deal-title-less2").hide();
                                $("#pricing-deal-title-more").show("slow");
                                $("#pricing-deal-title-more2").show("slow");                            
                            });
                            $("#pricing-deal-title-more2").click(function(){
                                $(".selection-panel-0").show("slow"); 
                                $("#pricing-deal-title-more").hide();
                                $("#pricing-deal-title-more2").hide();
                                $("#pricing-deal-title-less").show("slow");
                                $("#pricing-deal-title-less2").show("slow");                            
                            });
                            $("#pricing-deal-title-less2").click(function(){
                                $(".selection-panel-0").hide("slow");  //hide all selection-panels
                                $("#pricing-deal-title-less").hide();
                                $("#pricing-deal-title-less2").hide();
                                $("#pricing-deal-title-more").show("slow");
                                $("#pricing-deal-title-more2").show("slow");                            
                            });
                            $(".selection-panel-close-0").click(function(){
                                $(".selection-panel-0").hide("slow");  //hide all selection-panels 
                                $("#pricing-deal-title-less").hide();
                                $("#pricing-deal-title-more").show("slow");
                                $("#pricing-deal-title-less2").hide();
                                $("#pricing-deal-title-more2").show("slow");                          
                            });
                            
                            $("#discount-amt-info-more").click(function(){
                                $(".selection-panel-1").show("slow"); 
                                $("#discount-amt-info-more").hide();
                                $("#discount-amt-info-less").show("slow");                            
                            });
                            $("#discount-amt-info-less").click(function(){
                                $(".selection-panel-1").hide("slow");  //hide all selection-panels
                                $("#discount-amt-info-less").hide();
                                $("#discount-amt-info-more").show("slow");                            
                            });
                            $("#discount-amt-info-more2").click(function(){  //in help panel 0
                                $(".selection-panel-1").show("slow"); 
                                $("#discount-amt-info-more2").hide();
                                $("#discount-amt-info-less2").show("slow");                            
                            });
                            $("#discount-amt-info-less2").click(function(){   //in help panel 0
                                $(".selection-panel-1").hide("slow");  //hide all selection-panels
                                $("#discount-amt-info-less2").hide();
                                $("#discount-amt-info-more2").show("slow");                            
                            });
                            $(".selection-panel-close-1").click(function(){
                                $(".selection-panel-1").hide("slow");  //hide all selection-panels 
                                $("#discount-amt-info-less").hide();
                                $("#discount-amt-info-more").show("slow");
                                $("#discount-amt-info-less2").hide();
                                $("#discount-amt-info-more2").show("slow");                          
                            });
                            
                            $("#discount-msgs-info-more").click(function(){
                                $(".selection-panel-2").show("slow"); 
                                $("#discount-msgs-info-more").hide();
                                $("#discount-msgs-info-less").show("slow");                            
                            });
                            $("#discount-msgs-info-less").click(function(){
                                $(".selection-panel-2").hide("slow");  //hide all selection-panels
                                $("#discount-msgs-info-less").hide();
                                $("#discount-msgs-info-more").show("slow");                            
                            });
                            $("#discount-msgs-info-more2").click(function(){   //in help panel 0
                                $(".selection-panel-2").show("slow"); 
                                $("#discount-msgs-info-more2").hide();
                                $("#discount-msgs-info-less2").show("slow");                            
                            });
                            $("#discount-msgs-info-less2").click(function(){  //in help panel 0
                                $(".selection-panel-2").hide("slow");  //hide all selection-panels
                                $("#discount-msgs-info-less2").hide();
                                $("#discount-msgs-info-more2").show("slow");                            
                            });                            
                            $(".selection-panel-close-2").click(function(){
                                $(".selection-panel-2").hide("slow");  //hide all selection-panels 
                                $("#discount-msgs-info-less").hide(); 
                                $("#discount-msgs-info-more").show("slow");
                                $("#discount-msgs-info-less2").hide();
                                $("#discount-msgs-info-more2").show("slow");                          
                            });

                            $("#discount-msgs-install-more").click(function(){
                                $(".selection-panel-3").show("slow"); 
                                $("#discount-msgs-install-more").hide();
                                $("#discount-msgs-install-less").show("slow");                            
                            });
                            $("#discount-msgs-install-less").click(function(){
                                $(".selection-panel-3").hide("slow");  //hide all selection-panels
                                $("#discount-msgs-install-less").hide();
                                $("#discount-msgs-install-more").show("slow");                            
                            });
                            $("#discount-msgs-install-more2").click(function(){   //in help panel 0
                                $(".selection-panel-3").show("slow"); 
                                $("#discount-msgs-install-more2").hide();
                                $("#discount-msgs-install-less2").show("slow");                            
                            });
                            $("#discount-msgs-install-less2").click(function(){  //in help panel 0
                                $(".selection-panel-3").hide("slow");  //hide all selection-panels
                                $("#discount-msgs-install-less2").hide();
                                $("#discount-msgs-install-more2").show("slow");                            
                            });                            
                            $(".selection-panel-close-3").click(function(){
                                $(".selection-panel-3").hide("slow");  //hide all selection-panels 
                                $("#discount-msgs-install-less").hide(); 
                                $("#discount-msgs-install-more").show("slow");
                                $("#discount-msgs-install-less2").hide();
                                $("#discount-msgs-install-more2").show("slow");                          
                            });

                            $("#discount-shortcodes-more").click(function(){
                                $(".selection-panel-4").show("slow"); 
                                $("#discount-shortcodes-more").hide();
                                $("#discount-shortcodes-less").show("slow");                            
                            });
                            $("#discount-shortcodes-less").click(function(){
                                $(".selection-panel-4").hide("slow");  //hide all selection-panels
                                $("#discount-shortcodes-less").hide();
                                $("#discount-shortcodes-more").show("slow");                            
                            });
                            $("#discount-shortcodes-more2").click(function(){   //in help panel 0
                                $(".selection-panel-4").show("slow"); 
                                $("#discount-shortcodes-more2").hide();
                                $("#discount-shortcodes-less2").show("slow");                            
                            });
                            $("#discount-shortcodes-less2").click(function(){  //in help panel 0
                                $(".selection-panel-4").hide("slow");  //hide all selection-panels
                                $("#discount-shortcodes-less2").hide();
                                $("#discount-shortcodes-more2").show("slow");                            
                            });                            
                            $(".selection-panel-close-4").click(function(){
                                $(".selection-panel-4").hide("slow");  //hide all selection-panels 
                                $("#discount-shortcodes-less").hide(); 
                                $("#discount-shortcodes-more").show("slow");
                                $("#discount-shortcodes-less2").hide();
                                $("#discount-shortcodes-more2").show("slow");  
                            });

                            //show help panel when auto add checked!
                            $("#discount_auto_add_free_product_0").click(function(){
                                if($('#discount_auto_add_free_product_0').prop('checked')) {
                                  $(".selection-panel-6").show("slow");
                                } else {
                                  $(".selection-panel-6").hide("slow");
                                };  
                            });                            
                            $(".selection-panel-close-6").click(function(){
                                $(".selection-panel-6").hide("slow");  //hide all selection-panels 
                            });
                            
                            $("#pricing-deal-examples-more").click(function(){
                                $(".selection-panel-wrapper .selection-panel-5").show("slow"); 
                                $("#pricing-deal-examples-more").hide();
                                $("#pricing-deal-examples-less").show("slow");                       
                            });
                            $("#pricing-deal-examples-less").click(function(){
                                $(".selection-panel-wrapper .selection-panel-5").hide("slow");  //hide all selection-panels
                                $("#pricing-deal-examples-less").hide();
                                $("#pricing-deal-examples-more").show("slow");                      
                            });
                            $("#pricing-deal-examples-more2").click(function(){
                                $(".selection-panel-0 .selection-panel-5").show("slow"); 
                                $(".selection-panel-0 #pricing-deal-examples-more2").hide();
                                $(".selection-panel-0 #pricing-deal-examples-less2").show("slow");                            
                            });
                            $("#pricing-deal-examples-less2").click(function(){
                                $(".selection-panel-0 .selection-panel-5").hide("slow");  //hide all selection-panels
                                $(".selection-panel-0 #pricing-deal-examples-less2").hide();
                                $(".selection-panel-0 #pricing-deal-examples-more2").show("slow");                            
                            });
                            $(".selection-panel-close-5").click(function(){
                                $(".selection-panel-5").hide("slow");  //hide all selection-panels 
                                $("#pricing-deal-examples-less").hide();
                                $("#pricing-deal-examples-more").show("slow");
                                $(".selection-panel-0 #pricing-deal-examples-less2").hide();
                                $(".selection-panel-0 #pricing-deal-examples-more2").show("slow");                          
                            });
                                                                              
                            //Show documentation for Framework, based on selection dropdown.  Only shows OnClick, not on rule display...
                            
                            $("#deal-type-title-more").click(function(){
                                show_selection_panel_A();
                                $("#deal-type-title-more").hide();
                                $("#deal-type-title-less").show("slow");                            
                            });
                            $("#deal-type-title-less").click(function(){
                                $(".selection-panel-A").hide("slow");  //hide all selection-panels
                                $("#deal-type-title-less").hide();
                                $("#deal-type-title-more").show("slow");                            
                            });
                                   
                             function show_selection_panel_A() {     
                              $(".selection-panel-A").hide("slow");  //hide all selection-panels
                              switch( $("#rule_template_framework").val() ) {                                
                                case "0":                       $(".selection-panel-A-0").show("slow");   break;    //please enter....
                                case "D-storeWideSale":         $(".selection-panel-A-1").show("slow");   break;
                                case "D-simpleDiscount":        $(".selection-panel-A-2").show("slow");   break;
                                case "C-storeWideSale":         $(".selection-panel-A-3").show("slow");   break;
                                case "C-simpleDiscount":        $(".selection-panel-A-4").show("slow");   break;
                                case "C-discount-inCart":       $(".selection-panel-A-5").show("slow");   break;
                                case "C-forThePriceOf-inCart":  $(".selection-panel-A-6").show("slow");   break;
                                case "C-cheapest-inCart":       $(".selection-panel-A-7").show("slow");   break;
                                case "C-discount-Next":         $(".selection-panel-A-8").show("slow");   break;
                                case "C-forThePriceOf-Next":    $(".selection-panel-A-9").show("slow");   break;
                                case "C-cheapest-Next":  $(".selection-panel-A-10").show("slow");  break;
                                case "C-nth-Next":              $(".selection-panel-A-11").show("slow");  break;                               
                              };
                            };       
                            $(".selection-panel-close-A").click(function(){
                                $(".selection-panel-A").hide("slow");  //hide all selection-panels 
                                $("#deal-type-title-less").hide();
                                $("#deal-type-title-more").show("slow");                          
                            });
                                                              
                            //Buy Pool Amount Condition Type
		                        $("#buy-amt-title-more-0").click(function(){
                                show_selection_panel_B();
                                show_selection_panel_C();
                                $("#buy-amt-title-more-0").hide();
                                $("#buy-amt-title-less-0").show("slow");                            
                            });
                            $("#buy-amt-title-less-0").click(function(){
                                $(".selection-panel-B").hide("slow");  //hide all selection-panels
                                $(".selection-panel-C").hide("slow");  //hide all selection-panels
                                $("#buy-amt-title-less-0").hide();
                                $("#buy-amt-title-more-0").show("slow");                            
                            });
                                 
                             function show_selection_panel_B () {     
                              $(".selection-panel-B").hide("slow");  //hide all selection-panels
                              switch( $("#buy_amt_type_0").val() ) {                                
                                case "none":         $(".selection-panel-B-0").show("slow");   break;
                                case "one":          $(".selection-panel-B-1").show("slow");   break;
                                case "quantity":     $(".selection-panel-B-2").show("slow");   break;
                                case "currency":     $(".selection-panel-B-3").show("slow");   break;
                                case "nthQuantity":  $(".selection-panel-B-4").show("slow");   break;                               
                              };
                            };       
                            $(".selection-panel-close-B").click(function(){
                                $(".selection-panel-B").hide("slow");  //hide all selection-panels
                                $(".selection-panel-C").hide("slow");  //hide all selection-panels
                                $("#buy-amt-title-less-0").hide();
                                $("#buy-amt-title-more-0").show("slow");                           
                            });
                                    
                             function show_selection_panel_C () {     
                              $(".selection-panel-C").hide("slow");  //hide all selection-panels
                              switch( $("#buy_amt_applies_to_0").val() ) {                                
                                case "all":   $(".selection-panel-C-0").show("slow");   break;
                                case "each":  $(".selection-panel-C-1").show("slow");   break;                             
                              };
                            };       
                            $(".selection-panel-close-C").click(function(){
                                $(".selection-panel-B").hide("slow");  //hide all selection-panels
                                $(".selection-panel-C").hide("slow");  //hide all selection-panels
                                $("#buy-amt-title-less-0").hide();
                                $("#buy-amt-title-more-0").show("slow");                         
                            });        
                
                                                                
                            //Action Pool Amount Condition Type
                            $("#action-amt-title-more-0").click(function(){
                                show_selection_panel_D();
                                show_selection_panel_E();
                                $("#action-amt-title-more-0").hide();
                                $("#action-amt-title-less-0").show("slow");                            
                            });
                            $("#action-amt-title-less-0").click(function(){
                                $(".selection-panel-D").hide("slow");  //hide all selection-panels
                                $(".selection-panel-E").hide("slow");  //hide all selection-panels
                                $("#action-amt-title-less-0").hide();
                                $("#action-amt-title-more-0").show("slow");                            
                            });                            
                                    
                             function show_selection_panel_D () {     
                              $(".selection-panel-D").hide("slow");  //hide all selection-panels
                              switch( $("#action_amt_type_0").val() ) {                                
                                case "none":         $(".selection-panel-D-0").show("slow");   break;
                                case "zero":         $(".selection-panel-D-1").show("slow");   break;
                                case "one":          $(".selection-panel-D-2").show("slow");   break;
                                case "quantity":     $(".selection-panel-D-3").show("slow");   break;
                                case "currency":     $(".selection-panel-D-4").show("slow");   break;
                                case "nthQuantity":  $(".selection-panel-D-5").show("slow");   break;                               
                              };
                            };       
                            $(".selection-panel-close-D").click(function(){
                                $(".selection-panel-D").hide("slow");  //hide all selection-panels
                                $(".selection-panel-E").hide("slow");  //hide all selection-panels
                                $("#action-amt-title-less-0").hide();
                                $("#action-amt-title-more-0").show("slow");                         
                            });
        
                            //Action Pool Amount Applies To                              
                             function show_selection_panel_E () {     
                              $(".selection-panel-E").hide("slow");  //hide all selection-panels
                              switch( $("#action_amt_applies_to_0").val() ) {                                
                                case "all":   $(".selection-panel-E-0").show("slow");   break;
                                case "each":  $(".selection-panel-E-1").show("slow");   break;                             
                              };
                            };       
                            $(".selection-panel-close-E").click(function(){
                                $(".selection-panel-D").hide("slow");  //hide all selection-panels
                                $(".selection-panel-E").hide("slow");  //hide all selection-panels
                                $("#action-amt-title-less-0").hide();
                                $("#action-amt-title-more-0").show("slow");                          
                            });
                
                                                                
                            //Discount Amount Type
                            $("#discount-amt-title-more-0").click(function(){
                                show_selection_panel_F();
                                $("#discount-amt-title-more-0").hide();
                                $("#discount-amt-title-less-0").show("slow");                            
                            });
                            $("#discount-amt-title-less-0").click(function(){
                                $(".selection-panel-F").hide("slow");  //hide all selection-panels
                                $("#discount-amt-title-less-0").hide();
                                $("#discount-amt-title-more-0").show("slow");                            
                            });
                                    
                             function show_selection_panel_F () {     
                              $(".selection-panel-F").hide("slow");  //hide all selection-panels 
                              switch( $("#discount_amt_type_0").val() ) {                                
                                case "0":                    $(".selection-panel-F-0").show("slow");   break; 
                                case "percent":              $(".selection-panel-F-1").show("slow");   break;  
                                case "currency":             $(".selection-panel-F-2").show("slow");   break;
                                case "fixedPrice":           $(".selection-panel-F-3").show("slow");   break;
                                case "free":                 $(".selection-panel-F-4").show("slow");   break;
                                case "forThePriceOf_Units":  $(".selection-panel-F-5").show("slow");   break;
                                case "forThePriceOf_Currency":  $(".selection-panel-F-6").show("slow");   break;                                
                              };
                            };       
                            $(".selection-panel-close-F").click(function(){
                                $(".selection-panel-F").hide("slow");  //hide all selection-panels
                                $("#discount-amt-title-less-0").hide();
                                $("#discount-amt-title-more-0").show("slow");                           
                            });

                                                                
                            //Discount Applies To

                            $("#discount-applies-to-title-more-0").click(function(){
                                show_selection_panel_G();
                                $("#discount-applies-to-title-more-0").hide();
                                $("#discount-applies-to-title-less-0").show("slow");                            
                            });
                            $("#discount-applies-to-title-less-0").click(function(){
                                $(".selection-panel-G").hide("slow");  //hide all selection-panels
                                $("#discount-applies-to-title-less-0").hide();
                                $("#discount-applies-to-title-more-0").show("slow");                            
                            });                           
                                  
                             function show_selection_panel_G () {     
                              $(".selection-panel-G").hide("slow");  //hide all selection-panels 
                              switch( $("#discount_applies_to_0").val() ) {                                
                                case "0":               $(".selection-panel-G-0").show("slow");   break;
                                case "each":            $(".selection-panel-G-1").show("slow");   break;  
                                case "all":             $(".selection-panel-G-2").show("slow");   break;
                                case "cheapest":        $(".selection-panel-G-3").show("slow");   break;
                                case "most_expensive":  $(".selection-panel-G-4").show("slow");   break;                               
                              };
                            };       
                            $(".selection-panel-close-G").click(function(){
                                $(".selection-panel-G").hide("slow");  //hide all selection-panels
                                $("#discount-applies-to-title-less-0").hide();
                                $("#discount-applies-to-title-more-0").show("slow");                          
                            });                                    

                                     
                            //Discount Max for Rule
                            $("#discount-rule-max-title-more-0").click(function(){
                                show_selection_panel_H();
                                $("#discount-rule-max-title-more-0").hide();
                                $("#discount-rule-max-title-less-0").show("slow");                            
                            });
                            $("#discount-rule-max-title-less-0").click(function(){
                                $(".selection-panel-H").hide("slow");  //hide all selection-panels
                                $("#discount-rule-max-title-less-0").hide();
                                $("#discount-rule-max-title-more-0").show("slow");                            
                            });                            
                                  
                             function show_selection_panel_H () {     
                              $(".selection-panel-H").hide("slow");  //hide all selection-panels 
                              switch( $("#discount_rule_max_amt_type_0").val() ) {                                
                                case "none":      $(".selection-panel-H-0").show("slow");   break; 
                                case "percent":   $(".selection-panel-H-1").show("slow");   break;
                                case "quantity":  $(".selection-panel-H-2").show("slow");   break;
                                case "currency":  $(".selection-panel-H-3").show("slow");   break;                               
                              };
                            };       
                            $(".selection-panel-close-H").click(function(){
                                $(".selection-panel-H").hide("slow");  //hide all selection-panels
                                $("#discount-rule-max-title-less-0").hide();
                                $("#discount-rule-max-title-more-0").show("slow");                           
                            });                                         
                                     
                            //Discount Lifetime Max for Rule
                            $("#discount-lifetime-max-title-more-0").click(function(){
                                show_selection_panel_I();
                                $("#discount-lifetime-max-title-more-0").hide();
                                $("#discount-lifetime-max-title-less-0").show("slow");                            
                            });
                            $("#discount-lifetime-max-title-less-0").click(function(){
                                $(".selection-panel-I").hide("slow");  //hide all selection-panels
                                $("#discount-lifetime-max-title-less-0").hide();
                                $("#discount-lifetime-max-title-more-0").show("slow");                            
                            });                            
                                 
                             function show_selection_panel_I () {     
                              $(".selection-panel-I").hide("slow");  //hide all selection-panels 
                              switch( $("#discount_lifetime_max_amt_type_0").val() ) {                                
                                case "none":      $(".selection-panel-I-0").show("slow");   break; 
                                case "quantity":  $(".selection-panel-I-1").show("slow");   break;
                                case "currency":  $(".selection-panel-I-2").show("slow");   break;                               
                              };
                            };       
                            $(".selection-panel-close-I").click(function(){
                                $(".selection-panel-I").hide("slow");  //hide all selection-panels 
                                $("#discount-lifetime-max-title-less-0").hide();
                                $("#discount-lifetime-max-title-more-0").show("slow");                          
                            });            
        
                                     
                            //Action Group General Help Pane
                            
                            $("#action-group-title-more").click(function(){
                                show_selection_panel_J();
                                $("#action-group-title-more").hide();
                                $("#action-group-title-less").show("slow");                            
                            });
                            $("#action-group-title-less").click(function(){
                                $(".selection-panel-J").hide("slow");  //hide all selection-panels
                                $("#action-group-title-less").hide();
                                $("#action-group-title-more").show("slow");                            
                            });
                                  
                             function show_selection_panel_J () {     
                              $(".selection-panel-J").hide("slow");  //hide all selection-panels 
                              switch( $("#popChoiceOut").val() ) {                                
                                case "appliesToBuy": $(".selection-panel-J-0").show("slow");   break;
                                case "sameAsInPop":  $(".selection-panel-J-0").show("slow");   break; 
                                case "wholeStore":   $(".selection-panel-J-0").show("slow");   break;
                                case "groups":       $(".selection-panel-J-0").show("slow");   break;
                                case "vargroup":     $(".selection-panel-J-0").show("slow");   break;
                                case "single":       $(".selection-panel-J-0").show("slow");   break;                               
                              };
                            };       
                            $(".selection-panel-close-J").click(function(){
                                $(".selection-panel-J").hide("slow");  //hide all selection-panels
                                $("#action-group-title-less").hide();
                                $("#action-group-title-more").show("slow");                           
                            });
 
                            //groupsPop-in General Help Panel
                            
                            $("#groupsPop-in-more").click(function(){
                                $(".selection-panel-K-0").show("slow");  //hide all selection-panels
                                $("#groupsPop-in-more").hide();
                                $("#groupsPop-in-less").show("slow");                            
                            });
                            $("#groupsPop-in-less").click(function(){
                                $(".selection-panel-K").hide("slow");  //hide all selection-panels
                                $("#groupsPop-in-less").hide();
                                $("#groupsPop-in-more").show("slow");                            
                            });
                            $(".selection-panel-close-K").click(function(){
                                $(".selection-panel-K").hide("slow");  //hide all selection-panels
                                $("#groupsPop-in-less").hide();
                                $("#groupsPop-in-more").show("slow");                           
                            }); 
 
 
                            //groupsPop-out General Help Panel
                            
                            $("#groupsPop-out-more").click(function(){
                                $(".selection-panel-L-0").show("slow");  //hide all selection-panels
                                $("#groupsPop-out-more").hide();
                                $("#groupsPop-out-less").show("slow");                            
                            });
                            $("#groupsPop-out-less").click(function(){
                                $(".selection-panel-L").hide("slow");  //hide all selection-panels
                                $("#groupsPop-out-less").hide();
                                $("#groupsPop-out-more").show("slow");                            
                            });
                            $(".selection-panel-close-L").click(function(){
                                $(".selection-panel-L").hide("slow");  //hide all selection-panels
                                $("#groupsPop-out-less").hide();
                                $("#groupsPop-out-more").show("slow");
                            });  
 
            // HELP PANELS End
          

                           //OLD STUFF, NEEDS TO BE REPLACED...
                            //Population Handling Specifics
                            $("#allChoiceIn").click(function(){
                                $("#allChoiceIn-chosen").show("slow");
                                $("#anyChoiceIn-chosen").hide("slow");
                                $("#anyChoiceIn-span").hide("slow"); 
                                $("#eachChoiceIn-chosen").hide("slow");                                         
                            });
                            if($('#allChoiceIn').prop('checked')) {
                                $("#allChoiceIn-chosen").show("slow");
                                $("#anyChoiceIn-chosen").hide();
                                $("#anyChoiceIn-span").hide(); 
                                $("#eachChoiceIn-chosen").hide();                              
                                };
                                
                            $("#anyChoiceIn").click(function(){
                                $("#allChoiceIn-chosen").hide("slow");
                                $("#anyChoiceIn-chosen").show("slow");
                                $("#anyChoiceIn-span").show("slow"); 
                                $("#eachChoiceIn-chosen").hide("slow");
                            });
                            if($('#anyChoiceIn').prop('checked')) {
                                $("#allChoiceIn-chosen").hide();
                                $("#anyChoiceIn-chosen").show("slow");
                                $("#anyChoiceIn-span").show("slow"); 
                                $("#eachChoiceIn-chosen").hide();                                
                                };
                                 
                            $("#eachChoiceIn").click(function(){
                                $("#allChoiceIn-chosen").hide("slow");
                                $("#anyChoiceIn-chosen").hide("slow");
                                $("#anyChoiceIn-span").hide("slow"); 
                                $("#eachChoiceIn-chosen").show("slow");
                            });
                            if($('#eachChoiceIn').prop('checked')) {
                                $("#allChoiceIn-chosen").hide();
                                $("#anyChoiceIn-chosen").hide();
                                $("#anyChoiceIn-span").hide(); 
                                $("#eachChoiceIn-chosen").show("slow");                                
                                };
                                
                            $("#qtySelectedIn").click(function(){
                                $("#qtyChoice-chosen").show("slow");
                                $("#amtChoice-chosen").hide("slow");                              
                            });
                            if($('#qtySelectedIn').prop('checked')) {
                                $("#qtyChoice-chosen").show("slow");
                                $("#amtChoice-chosen").hide();                             
                                };
                               
                            $("#amtSelectedIn").click(function(){
                                $("#amtChoice-chosen").show("slow");
                                $("#qtyChoice-chosen").hide("slow");                           
                            });
                            if($('#amtSelectedIn').prop('checked')) {
                                $("#amtChoice-chosen").show("slow");
                                $("#qtyChoice-chosen").hide();
                                };
     //end old stuff


                                                        
                            //toggle "more info" areas
                            $("#pop-in-more").click(function(){
                                $("#pop-in-descrip").toggle("slow");                           
                            });
                            $("#inPopDescrip-more").click(function(){
                                $("#inPopDescrip-descrip").toggle("slow");                           
                            });
                            $("#inPop-varProdID-more").click(function(){
                                $("#inPop-varProdID-descrip").toggle("slow");                           
                            });
                            $("#actionPop-varProdID-more").click(function(){
                                $("#actionPop-varProdID-descrip").toggle("slow");                           
                            });
  
           
            //********************************************
            // POP Controls - Buy/Action Population handling   Begin
            //********************************************                                                                  
                        // popChoiceIn
                             
                        $('#popChoiceIn').change(function(){
                            popChoiceInTest();
                            mirrorPopChoiceInChange();
                        });

                        function popChoiceInTest() {
                           switch( $("#rule_template_framework").val() ) {
                            //Don't show PopChoice for the WholeStore versions
                            case "D-storeWideSale": 
                            case "C-storeWideSale": 
                                hideChoiceIn();                                 
                              break; 
                            default:             
                                popChoiceInProcess();
                              break;                               
                           };                             

                        }; 
                        function popChoiceInProcess() {
                          switch( $("#popChoiceIn").val() ) {
                            case "wholeStore": hideChoiceIn();    break;
                            //case "cart":     cartChoiceIn();    break;
                            case "groups":     groupChoiceIn();   break;
                            case "vargroup":   varChoiceIn();     break;
                            case "single":     singleChoiceIn();  break;
                          };
                        };                       
                        function cartChoiceIn() {
                          $("#vtprd-pop-in-cntl").hide("slow");
                          $("#inPop-varProdID-cntl").hide("slow");
                          $("#singleChoiceIn-span").hide("slow");          
                          $(".groupsPop-help-in").hide(); //help panel cntrls    
                          $(".selection-panel-K").hide(); //help panels
                          $("#vtprd-pop-in-groups-cntl").hide(); 
                          $("#vtprd-pop-in-groups-cntl-help").hide(); 
                          $("#buy_group_line_remainder").hide();   
                        };
                        function groupChoiceIn() {
                          $("#buy_group_line_remainder").show("slow"); //mwntest
                          $("#vtprd-pop-in-groups-cntl").show("slow"); //mwntest
                          $("#vtprd-pop-in-groups-cntl-help").show("slow");
                          $("#vtprd-pop-in-cntl").show("slow");
                          $("#inPop-varProdID-cntl").hide("slow");
                          $("#singleChoiceIn-span").hide("slow");
                          $("#groupsPop-in-more").show("slow"); //help panel cntrls   
                        };
                        function varChoiceIn() {
                          $("#buy_group_line_remainder").show("slow"); //mwntest
                          $("#vtprd-pop-in-groups-cntl").hide("slow"); //mwntest
                          $("#vtprd-pop-in-groups-cntl-help").hide("slow");
                          $("#vtprd-pop-in-cntl").hide("slow");
                          $("#inPop-varProdID-cntl").show("slow");
                          $("#singleChoiceIn-span").hide("slow");
                          $(".groupsPop-help-in").hide(); //help panel cntrls    
                          $(".selection-panel-K").hide(); //help panels
                        };
                        function singleChoiceIn() {
                          $("#buy_group_line_remainder").show("slow"); //mwntest
                          $("#vtprd-pop-in-groups-cntl").hide("slow"); //mwntest
                          $("#vtprd-pop-in-groups-cntl-help").hide("slow");
                          $("#vtprd-pop-in-cntl").hide("slow");
                          $("#inPop-varProdID-cntl").hide("slow");
                          $("#singleChoiceIn-span").show("slow");
                          $(".groupsPop-help-in").hide(); //help panel cntrls    
                          $(".selection-panel-K").hide(); //help panels    
                        };
                        function hideChoiceIn() {
                          $("#vtprd-pop-in-cntl").hide("slow");
                          $("#inPop-varProdID-cntl").hide("slow");
                          $("#singleChoiceIn-span").hide("slow");
                          $(".groupsPop-help-in").hide("slow"); //help panel cntrls    
                          $(".selection-panel-K").hide("slow"); //help panels
                          $("#vtprd-pop-in-groups-cntl").hide("slow"); //mwntest
                          $("#vtprd-pop-in-groups-cntl-help").hide("slow");
                          $("#buy_group_line_remainder").hide("slow"); //mwntest    
                        };

                                                                               
                        // popChoiceOut
                           
                        $('#popChoiceOut').change(function(){
                            popChoiceOutTest();
                            mirrorPopChoiceOutChange();
                        });                                

                        function popChoiceOutTest() {     
                           switch( $("#rule_template_framework").val() ) {
                            //Don't show PopChoice for the WholeStore versions
                            case "D-storeWideSale": 
                            case "C-storeWideSale": 
                                hideChoiceOut();                                
                              break; 
                            default:             
                                popChoiceOutProcess();
                              break;                               
                           }; 
                        }; 
                        function popChoiceOutProcess() {
                          switch( $("#popChoiceOut").val() ) {
                            case "appliesToBuy": hideChoiceOut();    break;
                            case "sameAsInPop":  hideChoiceOut();    break;
                            case "wholeStore":   hideChoiceOut();    break;
                            case "cart":         cartChoiceOut();    break;
                            case "groups":       groupChoiceOut();   break;
                            case "vargroup":     varChoiceOut();     break;
                            case "single":       singleChoiceOut();  break;
                          };                        
                        }                       
                        function cartChoiceOut() {                            
                          $("#vtprd-pop-out-cntl").hide("slow");
                          $("#vtprd-pop-out-cntl-help").hide("slow");
                          $("#actionPop-varProdID-cntl").hide("slow");
                          $("#singleChoiceOut-span").hide("slow"); 
                          $("#action_group_line_remainder").hide("slow"); 
                        };
                        function groupChoiceOut() {
                          $("#action_group_line_remainder").show("slow");                            
                          $("#vtprd-pop-out-cntl").show("slow");
                          $("#vtprd-pop-out-cntl-help").show("slow");
                          $("#actionPop-varProdID-cntl").hide("slow");
                          $("#singleChoiceOut-span").hide("slow");
                          $("#groupsPop-out-more").show("slow"); //help panel cntrls  
                        };
                        function varChoiceOut() {
                          $("#action_group_line_remainder").show("slow"); 
                          $("#vtprd-pop-out-cntl").hide("slow"); 
                          $("#vtprd-pop-out-cntl-help").hide("slow"); 
                          $("#actionPop-varProdID-cntl").show("slow");
                          $("#singleChoiceOut-span").hide("slow");
                        };
                        function singleChoiceOut() {
                          $("#action_group_line_remainder").show("slow"); 
                          $("#vtprd-pop-out-cntl").hide("slow"); 
                          $("#vtprd-pop-out-cntl-help").hide("slow");
                          $("#actionPop-varProdID-cntl").hide("slow");
                          $("#singleChoiceOut-span").show("slow");    
                        };
                        function hideChoiceOut() {
                          $("#vtprd-pop-out-cntl").hide("slow");
                          $("#vtprd-pop-out-cntl-help").hide("slow");
                          $("#actionPop-varProdID-cntl").hide("slow");
                          $("#singleChoiceOut-span").hide("slow");
                          $("#action_group_line_remainder").hide("slow");      
                        };


            // POP CONTROLS   eND
             
             
            //****************************
            // SCROLL UP
            //****************************  
                                         
                       $(window).scroll(function() {
                          if ($(this).scrollTop()) {
                              $('#back-to-top-tab').fadeIn();
                          } else {
                              $('#back-to-top-tab').fadeOut();
                          };
                      });
                      
                      $("#back-to-top-tab").click(function() {
                          $("html, body").animate({scrollTop: 0}, 1000);
                       });
            
            // SCROLL UP End

            
            // Buy/Action Population handling END

            //http://stackoverflow.com/questions/14346695/send-multiple-values-from-ajax-to-php-in-url-with-get
              
              //**************************************************************************************************
              //   Ajax variations on Button click
              //**************************************************************************************************                            
                        $("#ajaxVariationIn").click(function(){
                            //turn on loader animation
                            jQuery('div.inPopVar-loading-animation').css('visibility', 'visible');
                            
                            //hide slowly, then clean out existing variations/messages
                            //don't need the inVariationsArea statement, following statement is sufficient.
                            $('div#variations-in').hide("slow");
                            
                            var VarProdIDin = $('#inVarProdID').val();  //parent product ID from screen
                                                                                     
                            jQuery.ajax({
                               type : "post",
                               dataType : "html",
                               url : variationsInAjax.ajaxurl,  
                               data :  {action: "vtprd_ajax_load_variations_in", inVarProdID: VarProdIDin } ,
                               //                                             inVarProdID = name referenced in PHP => refers to this variable declaration, not the original html element.
                               success: function(response) {                                        
                                    //load the html output into #variations and show slowly
                                    $('div#variations-in').html(response).show("slow");
                                    //turn off loader animation
                                    jQuery('div.inPopVar-loading-animation').css('visibility', 'hidden');
                                }
                                  /*
                                    SEE ECHOES IN THE ALERT BOX:   
                                      success: function(response) {                                                                               
                                        alert('Got this from the server: ' + response);
                                      } ,
                                  */                                
                            }) ;  
    
                         }); 
                        $("#ajaxVariationOut").click(function(){
                            jQuery('div.actionPopVar-loading-animation').css('visibility', 'visible');
                            $('div#variations-out').hide("slow");                               
                            var VarProdIDout = $('#outVarProdID').val();  //parent product ID from screen
                                                                                     
                            jQuery.ajax({
                               type : "post",
                               dataType : "html",
                               url : variationsOutAjax.ajaxurl,  
                               data :  {action: "vtprd_ajax_load_variations_out", outVarProdID: VarProdIDout } ,
                              success: function(response) {                                        
                                    $('div#variations-out').html(response).show("slow");
                                    jQuery('div.actionPopVar-loading-animation').css('visibility', 'hidden');
                                }
                            }) ;         
                         });                                  
                        //**************     
                        //  end Ajax
                        //**************                      
 

                        //**************     
                        //  Info Panel Toggles
                        //**************                         
                        $("#vtprd-info1-help-all").click(function(){
                            $(".vtprd-info1-help-text").toggle("slow");                         
                        });
                        $("#vtprd-info1-help0").click(function(){
                            $("#vtprd-info1-help0-text").toggle("slow");                           
                        });
                        $("#vtprd-info1-help1").click(function(){
                            $("#vtprd-info1-help1-text").toggle("slow");                           
                        }); 
                        $("#vtprd-info1-help2").click(function(){
                            $("#vtprd-info1-help2-text").toggle("slow");                           
                        }); 
                        $("#vtprd-info1-help3").click(function(){
                            $("#vtprd-info1-help3-text").toggle("slow");                           
                        }); 
                        $("#vtprd-info1-help4").click(function(){
                            $("#vtprd-info1-help4-text").toggle("slow");                           
                        }); 
                        $("#vtprd-info1-help5").click(function(){
                            $("#vtprd-info1-help5-text").toggle("slow");                           
                        });                       
                        $("#vtprd-info1-help6").click(function(){
                            $("#vtprd-info1-help6-text").toggle("slow");                           
                        });                       
                        
                                                
                        /*since there are 2 occurrences of each of these panels, must use Class!*/
                        $(".vtprd-shortcode-details1-help").click(function(){
                            $(".vtprd-shortcode-details1").toggle("slow");                           
                        });                                                          
                        $(".vtprd-shortcode-details2-help").click(function(){
                            $(".vtprd-shortcode-details2").toggle("slow");                           
                        });  
                        $(".vtprd-shortcode-details3-help").click(function(){
                            $(".vtprd-shortcode-details3").toggle("slow");                           
                        }); 
                         
                        $(".vtprd-cartWidget-details1-help").click(function(){
                            $(".vtprd-cartWidget-details1").toggle("slow");                           
                        });


                        //**************     
                        //  Examples FAQ Toggles - panel 5 in the HELP Panel
                        //**************  
                        $("#selection-panel-0 #vtprd-panel-5-help1-more").click(function(){
                            $("#selection-panel-0 #vtprd-panel-5-help1-text").show("slow"); 
                            $("#selection-panel-0 #vtprd-panel-5-help1-more").hide();
                            $("#selection-panel-0 #vtprd-panel-5-help1-less").show("slow");                            
                        });
                        $("#selection-panel-0 #vtprd-panel-5-help1-less").click(function(){
                            $("#selection-panel-0 #vtprd-panel-5-help1-text").hide("slow");  
                            $("#selection-panel-0 #vtprd-panel-5-help1-less").hide();
                            $("#selection-panel-0 #vtprd-panel-5-help1-more").show("slow");                            
                        });
                        $("#selection-panel-0 #vtprd-panel-5-help2-more").click(function(){
                            $("#selection-panel-0 #vtprd-panel-5-help2-text").show("slow"); 
                            $("#selection-panel-0 #vtprd-panel-5-help2-more").hide();
                            $("#selection-panel-0 #vtprd-panel-5-help2-less").show("slow");                            
                        });
                        $("#selection-panel-0 #vtprd-panel-5-help2-less").click(function(){
                            $("#selection-panel-0 #vtprd-panel-5-help2-text").hide("slow");  
                            $("#selection-panel-0 #vtprd-panel-5-help2-less").hide();
                            $("#selection-panel-0 #vtprd-panel-5-help2-more").show("slow");                            
                        });
                        $("#selection-panel-0 #vtprd-panel-5-help3-more").click(function(){
                            $("#selection-panel-0 #vtprd-panel-5-help3-text").show("slow"); 
                            $("#selection-panel-0 #vtprd-panel-5-help3-more").hide();
                            $("#selection-panel-0 #vtprd-panel-5-help3-less").show("slow");                            
                        });
                        $("#selection-panel-0 #vtprd-panel-5-help3-less").click(function(){
                            $("#selection-panel-0 #vtprd-panel-5-help3-text").hide("slow");  
                            $("#selection-panel-0 #vtprd-panel-5-help3-less").hide();
                            $("#selection-panel-0 #vtprd-panel-5-help3-more").show("slow");                            
                        });                        
                        $("#selection-panel-0 #vtprd-panel-5-help4-more").click(function(){
                            $("#selection-panel-0 #vtprd-panel-5-help4-text").show("slow"); 
                            $("#selection-panel-0 #vtprd-panel-5-help4-more").hide();
                            $("#selection-panel-0 #vtprd-panel-5-help4-less").show("slow");                            
                        });
                        $("#selection-panel-0 #vtprd-panel-5-help4-less").click(function(){
                            $("#selection-panel-0 #vtprd-panel-5-help4-text").hide("slow");  
                            $("#selection-panel-0 #vtprd-panel-5-help4-less").hide();
                            $("#selection-panel-0 #vtprd-panel-5-help4-more").show("slow");                            
                        });
                        $("#selection-panel-0 #vtprd-panel-5-help5-more").click(function(){
                            $("#selection-panel-0 #vtprd-panel-5-help5-text").show("slow"); 
                            $("#selection-panel-0 #vtprd-panel-5-help5-more").hide();
                            $("#selection-panel-0 #vtprd-panel-5-help5-less").show("slow");                            
                        });
                        $("#selection-panel-0 #vtprd-panel-5-help5-less").click(function(){
                            $("#selection-panel-0 #vtprd-panel-5-help5-text").hide("slow");  
                            $("#selection-panel-0 #vtprd-panel-5-help5-less").hide();
                            $("#selection-panel-0 #vtprd-panel-5-help5-more").show("slow");                            
                        });
                        $("#selection-panel-0 #vtprd-panel-5-help6-more").click(function(){
                            $("#selection-panel-0 #vtprd-panel-5-help6-text").show("slow"); 
                            $("#selection-panel-0 #vtprd-panel-5-help6-more").hide();
                            $("#selection-panel-0 #vtprd-panel-5-help6-less").show("slow");                            
                        });
                        $("#selection-panel-0 #vtprd-panel-5-help6-less").click(function(){
                            $("#selection-panel-0 #vtprd-panel-5-help6-text").hide("slow");  
                            $("#selection-panel-0 #vtprd-panel-5-help6-less").hide();
                            $("#selection-panel-0 #vtprd-panel-5-help6-more").show("slow");                            
                        });
                        $("#selection-panel-0 #vtprd-panel-5-help7-more").click(function(){
                            $("#selection-panel-0 #vtprd-panel-5-help7-text").show("slow"); 
                            $("#selection-panel-0 #vtprd-panel-5-help7-more").hide();
                            $("#selection-panel-0 #vtprd-panel-5-help7-less").show("slow");                            
                        });
                        $("#selection-panel-0 #vtprd-panel-5-help7-less").click(function(){
                            $("#selection-panel-0 #vtprd-panel-5-help7-text").hide("slow");  
                            $("#selection-panel-0 #vtprd-panel-5-help7-less").hide();
                            $("#selection-panel-0 #vtprd-panel-5-help7-more").show("slow");                            
                        });
                        $("#selection-panel-0 #vtprd-panel-5-help8-more").click(function(){
                            $("#selection-panel-0 #vtprd-panel-5-help8-text").show("slow"); 
                            $("#selection-panel-0 #vtprd-panel-5-help8-more").hide();
                            $("#selection-panel-0 #vtprd-panel-5-help8-less").show("slow");                            
                        });
                        $("#selection-panel-0 #vtprd-panel-5-help8-less").click(function(){
                            $("#selection-panel-0 #vtprd-panel-5-help8-text").hide("slow");  
                            $("#selection-panel-0 #vtprd-panel-5-help8-less").hide();
                            $("#selection-panel-0 #vtprd-panel-5-help8-more").show("slow");                            
                        });


                        //**************     
                        //  Examples FAQ Toggles - standalone Panel-5
                        //**************  
                        $(".selection-panel-wrapper #vtprd-panel-5-help1-more").click(function(){
                            $(".selection-panel-wrapper #vtprd-panel-5-help1-text").show("slow"); 
                            $(".selection-panel-wrapper #vtprd-panel-5-help1-more").hide();
                            $(".selection-panel-wrapper #vtprd-panel-5-help1-less").show("slow");                            
                        });
                        $(".selection-panel-wrapper #vtprd-panel-5-help1-less").click(function(){
                            $(".selection-panel-wrapper #vtprd-panel-5-help1-text").hide("slow");  
                            $(".selection-panel-wrapper #vtprd-panel-5-help1-less").hide();
                            $(".selection-panel-wrapper #vtprd-panel-5-help1-more").show("slow");                            
                        });
                        $(".selection-panel-wrapper #vtprd-panel-5-help2-more").click(function(){
                            $(".selection-panel-wrapper #vtprd-panel-5-help2-text").show("slow"); 
                            $(".selection-panel-wrapper #vtprd-panel-5-help2-more").hide();
                            $(".selection-panel-wrapper #vtprd-panel-5-help2-less").show("slow");                            
                        });
                        $(".selection-panel-wrapper #vtprd-panel-5-help2-less").click(function(){
                            $(".selection-panel-wrapper #vtprd-panel-5-help2-text").hide("slow");  
                            $(".selection-panel-wrapper #vtprd-panel-5-help2-less").hide();
                            $(".selection-panel-wrapper #vtprd-panel-5-help2-more").show("slow");                            
                        });
                        $(".selection-panel-wrapper #vtprd-panel-5-help3-more").click(function(){
                            $(".selection-panel-wrapper #vtprd-panel-5-help3-text").show("slow"); 
                            $(".selection-panel-wrapper #vtprd-panel-5-help3-more").hide();
                            $(".selection-panel-wrapper #vtprd-panel-5-help3-less").show("slow");                            
                        });
                        $(".selection-panel-wrapper #vtprd-panel-5-help3-less").click(function(){
                            $(".selection-panel-wrapper #vtprd-panel-5-help3-text").hide("slow");  
                            $(".selection-panel-wrapper #vtprd-panel-5-help3-less").hide();
                            $(".selection-panel-wrapper #vtprd-panel-5-help3-more").show("slow");                            
                        });                        
                        $(".selection-panel-wrapper #vtprd-panel-5-help4-more").click(function(){
                            $(".selection-panel-wrapper #vtprd-panel-5-help4-text").show("slow"); 
                            $(".selection-panel-wrapper #vtprd-panel-5-help4-more").hide();
                            $(".selection-panel-wrapper #vtprd-panel-5-help4-less").show("slow");                            
                        });
                        $(".selection-panel-wrapper #vtprd-panel-5-help4-less").click(function(){
                            $(".selection-panel-wrapper #vtprd-panel-5-help4-text").hide("slow");  
                            $(".selection-panel-wrapper #vtprd-panel-5-help4-less").hide();
                            $(".selection-panel-wrapper #vtprd-panel-5-help4-more").show("slow");                            
                        });
                        $(".selection-panel-wrapper #vtprd-panel-5-help5-more").click(function(){
                            $(".selection-panel-wrapper #vtprd-panel-5-help5-text").show("slow"); 
                            $(".selection-panel-wrapper #vtprd-panel-5-help5-more").hide();
                            $(".selection-panel-wrapper #vtprd-panel-5-help5-less").show("slow");                            
                        });
                        $(".selection-panel-wrapper #vtprd-panel-5-help5-less").click(function(){
                            $(".selection-panel-wrapper #vtprd-panel-5-help5-text").hide("slow");  
                            $(".selection-panel-wrapper #vtprd-panel-5-help5-less").hide();
                            $(".selection-panel-wrapper #vtprd-panel-5-help5-more").show("slow");                            
                        });
                        $(".selection-panel-wrapper #vtprd-panel-5-help6-more").click(function(){
                            $(".selection-panel-wrapper #vtprd-panel-5-help6-text").show("slow"); 
                            $(".selection-panel-wrapper #vtprd-panel-5-help6-more").hide();
                            $(".selection-panel-wrapper #vtprd-panel-5-help6-less").show("slow");                            
                        });
                        $(".selection-panel-wrapper #vtprd-panel-5-help6-less").click(function(){
                            $(".selection-panel-wrapper #vtprd-panel-5-help6-text").hide("slow");  
                            $(".selection-panel-wrapper #vtprd-panel-5-help6-less").hide();
                            $(".selection-panel-wrapper #vtprd-panel-5-help6-more").show("slow");                            
                        });
                        $(".selection-panel-wrapper #vtprd-panel-5-help7-more").click(function(){
                            $(".selection-panel-wrapper #vtprd-panel-5-help7-text").show("slow"); 
                            $(".selection-panel-wrapper #vtprd-panel-5-help7-more").hide();
                            $(".selection-panel-wrapper #vtprd-panel-5-help7-less").show("slow");                            
                        });
                        $(".selection-panel-wrapper #vtprd-panel-5-help7-less").click(function(){
                            $(".selection-panel-wrapper #vtprd-panel-5-help7-text").hide("slow");  
                            $(".selection-panel-wrapper #vtprd-panel-5-help7-less").hide();
                            $(".selection-panel-wrapper #vtprd-panel-5-help7-more").show("slow");                            
                        });
                        $(".selection-panel-wrapper #vtprd-panel-5-help8-more").click(function(){
                            $(".selection-panel-wrapper #vtprd-panel-5-help8-text").show("slow"); 
                            $(".selection-panel-wrapper #vtprd-panel-5-help8-more").hide();
                            $(".selection-panel-wrapper #vtprd-panel-5-help8-less").show("slow");                            
                        });
                        $(".selection-panel-wrapper #vtprd-panel-5-help8-less").click(function(){
                            $(".selection-panel-wrapper #vtprd-panel-5-help8-text").hide("slow");  
                            $(".selection-panel-wrapper #vtprd-panel-5-help8-less").hide();
                            $(".selection-panel-wrapper #vtprd-panel-5-help8-more").show("slow");                            
                        });                        
                        
                        
  
           
                //********************************************
                // ToolTip  QTip2   BEGIN  +  Wizard sw
                //********************************************                              

                     
                                              
                        $('.hasHoverHelp').each(function() { // Notice the .each() loop, discussed below
                            $(this).qtip({
                                content: {
                                    text: $(this).next('div') // Use the "div" element next to this for the content

                                }
                                ,position: {
                                    my: 'left center',  //location of pointer on tooltip
                                    at: 'right center' //points to where on the object
                                    
                                }
                                ,show: 'click'
                                ,hide: {
                                      fixed: true,
                                      delay: 300
                                  }

                            });              
                        });                          
                        
                      
                                              
                        $('.hasHoverHelp2').each(function() { // Notice the .each() loop, discussed below
                            $(this).qtip({
                                content: {
                                    text: $(this).next('div') // Use the "div" element next to this for the content

                                }
                                ,position: {
                                    my: 'top right',  //location of pointer on tooltip
                                    at: 'bottom center' //points to where on the object
                                    
                                }
                              //  ,show: 'click'
                                ,hide: {
                                      fixed: true,
                                      delay: 300
                                  }

                            });              
                        });                          
                        

                        $('.hasWizardHelpBelow').each(function() { // Notice the .each() loop, discussed below                         

                            $(this).qtip({
                                                        
                                content: {
                                    text: $(this).next('div') // Use the "div" element next to this for the content
                                   
                                    ,title: {
                              					button: true    //close button on box
                              				}
                                }
                          			/*
                                hide: { 
                          				event: 'click'   //only hides when close button is clicked!!
                          			}
                                */
  
                                //,hide: 'unfocus'   //keep displaying until next click elsewhere
                                ,hide: {
                                      fixed: true,
                                      delay: 300
                                  }
                                ,position: {
                                    //where the arrow pointing to the clicked-on object is located
                                    /*
                                    corner: {
                                        target: 'topLeft',
                                        tooltip: 'bottomLeft'
                                        },
                                        */
                                    //adjusts the location of the whole tooltip relative to the clicked-on object
                                   /* adjust: {
                                        x: 10,
                                        y: 150
                                        }
                                    ,  */
                                    my: 'top center',  //location of pointer on tooltip
                                    at: 'bottom center' //points to where on the object
                                    
                                }
                                    
                                
                                ,style: {
                              			classes: 'wideWizard' //activates this class for the tooltip, which changes the max-width and max-height, etc....
                              	}  
                                                                    
                            });  
                                                                                            
                        }); 
                        
 
                      
                        $('.hasWizardHelpRight').each(function() { // Notice the .each() loop, discussed below
                            $(this).qtip({
                                content: {
                                    text: $(this).next('div') // Use the "div" element next to this for the content
                                    ,title: {
                              					button: true    //close button on box
                                        }                                   
                                }
                                
                                ,hide: {       //brief time delay, then fade
                                      fixed: true,
                                      delay: 300
                                  }
                                  
                                //,hide: 'unfocus'   //keep displaying until next click elsewhere
                                      
                                ,position: {
                                    //where the arrow pointing to the clicked-on object is located
                                    //adjusts the location of the whole tooltip relative to the clicked-on object

                                //    my: 'left center',  //location of pointer on tooltip
                                //    at: 'bottom right' //points to where on the object
                                    
 
                                    my: 'top left',  //location of pointer on tooltip
                                    at: 'bottom right' //points to where on the object
                                                                        
                                }                                    
                                
                                ,style: {
                              			classes: 'wideWizard' //activates this class for the tooltip, which changes the max-width and max-height, etc....
                              	}
                                                                    
                            });              
                        }); 
                       
                        // Grab all elements with the class "hasTooltip"
                        $('.hasTooltip').each(function() { // Notice the .each() loop, discussed below
                            $(this).qtip({
                                content: {
                                    text: $(this).next('div') // Use the "div" element next to this for the content
                                    ,title: {
                              					button: true    //close button on box
                              				}
                                }
                                
                                ,show: {
                                    modal: {
                                        on: true
                                    }
                                }                                
                                
                            });              
                        });     
                        

                
                        //****************************************************************
                        //Pick up the WIZARD switch state 1st time, AFTER qtip has run!
                        //****************************************************************
                        
                        wizard_on_off_sw_select_test();
                        
                        //****************************************************************
                        
                        $('#wizard-on-off-sw-select').change(function(){
                            wizard_on_off_sw_select_test();
                        });

                        function wizard_on_off_sw_select_test() {
                           switch( $("#wizard-on-off-sw-select").val() ) {
                            case "on":                                 
                                  $('.hasWizardHelpBelow').qtip('enable');
                                  $('.hasWizardHelpRight').qtip('enable'); 
                              break;                              
                            case "off":                                                           
                                  $('.hasWizardHelpBelow').qtip('hide').qtip('disable');
                                  $('.hasWizardHelpRight').qtip('hide').qtip('disable');                                 
                              break;                               
                           };                             

                        }; 
                        
                        //switch on the tooltips themselves...
                        $('.wizard-turn-hover-help-off').click(function(){
                            //$("#wizard-on-off-sw-select").val('off');
                            $('#wizard-on-off-sw-on').attr('selected', false);
                            $('#wizard-on-off-sw-off').attr('selected', true); 
                            wizard_on_off_sw_select_test();
                        });               
                
                //********************************************
                // ToolTip  QTip2   END
                //********************************************  

                    }); //end ready function 
                   