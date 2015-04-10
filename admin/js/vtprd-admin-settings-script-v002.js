     jQuery(document).ready(function($) {
            
            //v1.0.9.0 begin             
            //****************************
            // Show Discount Where
            //****************************  
                        unitPriceOrCoupon_Control();      
                        $("#discount_taken_where").change(function(){ //v1.0.9.3 use 'change' rather than 'click'
                             //v1.0.9.3 begin
                             //set crossout to 'yes' as default, only on change
                             if ($("#discount_taken_where").val() == 'discountUnitPrice') {                                                              
                                $("#show_unit_price_cart_discount_crossout").val('yes');
                                $('#discountYesCrossout').attr('selected', true);
                                $('#discountNoCrossout').attr('selected', false);                                  
                             }
                             //v1.0.9.3 end
                             
                             unitPriceOrCoupon_Control();                           
                         });
                                
                          function unitPriceOrCoupon_Control() {                     
                            switch( $("#discount_taken_where").val() ) {
                               case 'discountUnitPrice':
                                  //whole bunch of switches
                                  $(".unitPriceOrCoupon").hide();
                                  $(".unitPriceOnly").show("slow");
                                 break; 
                               default:
                                  //whole bunch of switches
                                  $(".unitPriceOrCoupon").show("slow");
                                  $(".unitPriceOnly").hide();
                                 break;                                                                  
                            }; 
                        };                       
             //v1.0.9.0 end 
       
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
                            })          

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

                            $("#pricing-deal-examples-more").click(function(){
                                $(".selection-panel-5").show("slow"); 
                                $("#pricing-deal-examples-more").hide();
                                $("#pricing-deal-examples-less").show("slow");                            
                            });
                            $("#pricing-deal-examples-less").click(function(){
                                $(".selection-panel-5").hide("slow");  //hide all selection-panels
                                $("#pricing-deal-examples-less").hide();
                                $("#pricing-deal-examples-more").show("slow");                            
                            });
                            $("#pricing-deal-examples-more2").click(function(){   //in help panel 0
                                $(".selection-panel-5").show("slow"); 
                                $("#pricing-deal-examples-more2").hide();
                                $("#pricing-deal-examples-less2").show("slow");                            
                            });
                            $("#pricing-deal-examples-less2").click(function(){  //in help panel 0
                                $(".selection-panel-5").hide("slow");  //hide all selection-panels
                                $("#pricing-deal-examples-less2").hide();
                                $("#pricing-deal-examples-more2").show("slow");                            
                            });                            
                            $(".selection-panel-close-5").click(function(){
                                $(".selection-panel-5").hide("slow");  //hide all selection-panels 
                                $("#pricing-deal-examples-less").hide(); 
                                $("#pricing-deal-examples-more").show("slow");
                                $("#pricing-deal-examples-less2").hide();
                                $("#pricing-deal-examples-more2").show("slow");  
                            });


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
                        
                        
                        $("#vtprd-shortcode-details1-help").click(function(){
                            $("#vtprd-shortcode-details1").toggle("slow");                           
                        });                                                          
                        $("#vtprd-shortcode-details2-help").click(function(){
                            $("#vtprd-shortcode-details2").toggle("slow");                           
                        });  
                        $("#vtprd-shortcode-details3-help").click(function(){
                            $("#vtprd-shortcode-details3").toggle("slow");                           
                        }); 
                         
                        $("#vtprd-cartWidget-details1-help").click(function(){
                            $("#vtprd-cartWidget-details1").toggle("slow");                           
                        });

                        //**************     
                        //  Examples FAQ Toggles
                        //**************  
                        $("#vtprd-panel-5-help1-more").click(function(){
                            $("#vtprd-panel-5-help1-text").show("slow"); 
                            $("#vtprd-panel-5-help1-more").hide();
                            $("#vtprd-panel-5-help1-less").show("slow");                            
                        });
                        $("#vtprd-panel-5-help1-less").click(function(){
                            $("#vtprd-panel-5-help1-text").hide("slow");  
                            $("#vtprd-panel-5-help1-less").hide();
                            $("#vtprd-panel-5-help1-more").show("slow");                            
                        });
                        $("#vtprd-panel-5-help2-more").click(function(){
                            $("#vtprd-panel-5-help2-text").show("slow"); 
                            $("#vtprd-panel-5-help2-more").hide();
                            $("#vtprd-panel-5-help2-less").show("slow");                            
                        });
                        $("#vtprd-panel-5-help2-less").click(function(){
                            $("#vtprd-panel-5-help2-text").hide("slow");  
                            $("#vtprd-panel-5-help2-less").hide();
                            $("#vtprd-panel-5-help2-more").show("slow");                            
                        });
                        $("#vtprd-panel-5-help3-more").click(function(){
                            $("#vtprd-panel-5-help3-text").show("slow"); 
                            $("#vtprd-panel-5-help3-more").hide();
                            $("#vtprd-panel-5-help3-less").show("slow");                            
                        });
                        $("#vtprd-panel-5-help3-less").click(function(){
                            $("#vtprd-panel-5-help3-text").hide("slow");  
                            $("#vtprd-panel-5-help3-less").hide();
                            $("#vtprd-panel-5-help3-more").show("slow");                            
                        });                        
                        $("#vtprd-panel-5-help4-more").click(function(){
                            $("#vtprd-panel-5-help4-text").show("slow"); 
                            $("#vtprd-panel-5-help4-more").hide();
                            $("#vtprd-panel-5-help4-less").show("slow");                            
                        });
                        $("#vtprd-panel-5-help4-less").click(function(){
                            $("#vtprd-panel-5-help4-text").hide("slow");  
                            $("#vtprd-panel-5-help4-less").hide();
                            $("#vtprd-panel-5-help4-more").show("slow");                            
                        });
                        $("#vtprd-panel-5-help5-more").click(function(){
                            $("#vtprd-panel-5-help5-text").show("slow"); 
                            $("#vtprd-panel-5-help5-more").hide();
                            $("#vtprd-panel-5-help5-less").show("slow");                            
                        });
                        $("#vtprd-panel-5-help5-less").click(function(){
                            $("#vtprd-panel-5-help5-text").hide("slow");  
                            $("#vtprd-panel-5-help5-less").hide();
                            $("#vtprd-panel-5-help5-more").show("slow");                            
                        });                        
                        $("#vtprd-panel-5-help6-more").click(function(){
                            $("#vtprd-panel-5-help6-text").show("slow"); 
                            $("#vtprd-panel-5-help6-more").hide();
                            $("#vtprd-panel-5-help6-less").show("slow");                            
                        });
                        $("#vtprd-panel-5-help6-less").click(function(){
                            $("#vtprd-panel-5-help6-text").hide("slow");  
                            $("#vtprd-panel-5-help6-less").hide();
                            $("#vtprd-panel-5-help6-more").show("slow");                            
                        });
                        $("#vtprd-panel-5-help7-more").click(function(){
                            $("#vtprd-panel-5-help7-text").show("slow"); 
                            $("#vtprd-panel-5-help7-more").hide();
                            $("#vtprd-panel-5-help7-less").show("slow");                            
                        });
                        $("#vtprd-panel-5-help7-less").click(function(){
                            $("#vtprd-panel-5-help7-text").hide("slow");  
                            $("#vtprd-panel-5-help7-less").hide();
                            $("#vtprd-panel-5-help7-more").show("slow");                            
                        });
                        $("#vtprd-panel-5-help8-more").click(function(){
                            $("#vtprd-panel-5-help8-text").show("slow"); 
                            $("#vtprd-panel-5-help8-more").hide();
                            $("#vtprd-panel-5-help8-less").show("slow");                            
                        });
                        $("#vtprd-panel-5-help8-less").click(function(){
                            $("#vtprd-panel-5-help8-text").hide("slow");  
                            $("#vtprd-panel-5-help8-less").hide();
                            $("#vtprd-panel-5-help8-more").show("slow");                            
                        });


          //**************     
          //  Settings More Info Toggles
          //**************                                                  
                                  
          $("#help-all").click(function(){
              $(".help-text").toggle("slow");                         
          });
          $("#help0").click(function(){
              $("#help0-text").toggle("slow");                           
          });
          $("#help1").click(function(){
              $("#help1-text").toggle("slow");                           
          });          
          $("#help2").click(function(){
              $("#help2-text").toggle("slow");                           
          });
          $("#help3").click(function(){
              $("#help3-text").toggle("slow");                           
          });  
          $("#help4").click(function(){
              $("#help4-text").toggle("slow");                           
          });
          $("#help5").click(function(){
              $("#help5-text").toggle("slow");                           
          });
          $("#help6").click(function(){
              $("#help6-text").toggle("slow");                           
          }); 
          $("#help7").click(function(){
              $("#help7-text").toggle("slow");                           
          }); 
          $("#help8").click(function(){
              $("#help8-text").toggle("slow");                           
          }); 
          $("#help9").click(function(){
              $("#help9-text").toggle("slow");                           
          }); 
          $("#help10").click(function(){
              $("#help10-text").toggle("slow");                           
          });
          $("#help11").click(function(){
              $("#help11-text").toggle("slow");                           
          });
          $("#help12").click(function(){
              $("#help12-text").toggle("slow");                           
          });
         $("#help13").click(function(){
              $("#help13-text").toggle("slow");                           
          });
          $("#help14").click(function(){
              $("#help14-text").toggle("slow");                           
          });
          $("#help15").click(function(){
              $("#help15-text").toggle("slow");                           
          });
          $("#help16").click(function(){
              $("#help16-text").toggle("slow");                           
          });
          $("#help17").click(function(){
              $("#help17-text").toggle("slow");                           
          });
          $("#help18").click(function(){
              $("#help18-text").toggle("slow");                           
          });
          $("#help19").click(function(){
              $("#help19-text").toggle("slow");                           
          });
          $("#help20").click(function(){
              $("#help20-text").toggle("slow");                           
          });
          $("#help21").click(function(){
              $("#help21-text").toggle("slow");                           
          });          
          $("#help22").click(function(){
              $("#help22-text").toggle("slow");                           
          });          
          $("#help23").click(function(){
              $("#help23-text").toggle("slow");                           
          });
          $("#help24").click(function(){
              $("#help24-text").toggle("slow");                           
          });          
          $("#help25").click(function(){
              $("#help25-text").toggle("slow");                           
          });          
          $("#help26").click(function(){
              $("#help26-text").toggle("slow");                           
          });          
          $("#help27").click(function(){
              $("#help27-text").toggle("slow");                           
          });
          $("#help28").click(function(){
              $("#help28-text").toggle("slow");                           
          });  
          $("#help29").click(function(){
              $("#help29-text").toggle("slow");                           
          }); 
           $("#help30").click(function(){
              $("#help30-text").toggle("slow");                           
          });          
          $("#help31").click(function(){
              $("#help31-text").toggle("slow");                           
          });           
          $("#help32").click(function(){
              $("#help32-text").toggle("slow");                           
          });           
          $("#help33").click(function(){
              $("#help33-text").toggle("slow");                           
          });           
          $("#help34").click(function(){
              $("#help34-text").toggle("slow");                           
          });
          $("#help35").click(function(){
              $("#help35-text").toggle("slow");                           
          });          
          $("#help36").click(function(){
              $("#help36-text").toggle("slow");                           
          });
          $("#help37").click(function(){
              $("#help37-text").toggle("slow");                           
          });
          $("#help38").click(function(){
              $("#help38-text").toggle("slow");                           
          });
          $("#help39").click(function(){
              $("#help39-text").toggle("slow");                           
          });
          $("#help40").click(function(){
              $("#help40-text").toggle("slow");                           
          });
          $("#help41").click(function(){
              $("#help41-text").toggle("slow");                           
          });
           $("#help41a").click(function(){
              $("#help41a-text").toggle("slow");                           
          });                    
          $("#help42").click(function(){
              $("#help42-text").toggle("slow");                           
          });
          $("#help43").click(function(){
              $("#help43-text").toggle("slow");                           
          });
          $("#help44").click(function(){
              $("#help44-text").toggle("slow");                           
          }); 
          $("#help45").click(function(){
              $("#help45-text").toggle("slow");                           
          }); 
          $("#help46").click(function(){
              $("#help46-text").toggle("slow");                           
          });   
           $("#help47").click(function(){
              $("#help47-text").toggle("slow");                           
          }); 
          //v1.0.9.0 begin 
           $("#help48").click(function(){
              $("#help48-text").toggle("slow");                           
          }); 
           $("#help49").click(function(){
              $("#help49-text").toggle("slow");                           
          }); 
           $("#help50").click(function(){
              $("#help50-text").toggle("slow");                           
          });
          //v1.0.9.0 end 
          //v1.0.9.3 begin 
           $("#help51").click(function(){
              $("#help51-text").toggle("slow");                           
          });          
           $("#help52").click(function(){
              $("#help52-text").toggle("slow");                           
          });          
           $("#help53").click(function(){
              $("#help53-text").toggle("slow");                           
          });          
           $("#help54").click(function(){
              $("#help54-text").toggle("slow");                           
          });          
           $("#help55").click(function(){
              $("#help55-text").toggle("slow");                           
          });          
          //v1.0.9.3 bend                         
      });  
  
