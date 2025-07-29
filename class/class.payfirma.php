<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Validate checkout checks for card token.
 */

add_action('woocommerce_checkout_process', 'payfirma_checkout_field_checks');

function payfirma_checkout_field_checks() {
    // Only run if Payfirma is the selected payment method
    if (!isset($_POST['payment_method']) || $_POST['payment_method'] !== 'payfirma_gateway') {
        return;
    }

    // Validate the card token
    if (!isset($_COOKIE['tempCardToken']) ||  strlen($_COOKIE['tempCardToken']) != 20){
        unset($_COOKIE['tempCardToken']); 
        wc_add_notice( 'Please check your <strong>Credit Card information</strong>.', $notice_type = 'error' );
    } 
}

/**
 * Place Order button for Payfirma
 */
add_filter('woocommerce_order_button_html', function(){
    return '<button class="payfirma_submit button alt">Place Order</button>';
});

/**
 * Strips input of inappropriate characters
 * @param $input
 * @return mixed
 */
function cleanInput($input) {

    $search = array(
        '@<script[^>]*?>.*?</script>@si',   // Strip out javascript
        '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
        '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
        '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments
    );

    $output = preg_replace($search, '', $input);
    return $output;
}

/**
 * Class WC_Gateway_Payfirma - Handles all Payfirma Payment Gateway functionality
 */
class WC_Gateway_Payfirma extends WC_Payment_Gateway
{
    public function __construct()
    {
        global $woocommerce;

        // define variables
        $this->id = 'payfirma_gateway';
        $this->has_fields = true; //  – puts payment form fields into payment options on payment page.
        $this->method_title = __('KORT Payments', 'woocommerce'); //– Title of the payment method shown on the admin page.
        $this->method_description = 'Use KORT Payments to process your payments.'; //– Description for the payment method shown on the admin page.
        // load the settings
        $this->init_form_fields();
        $this->init_settings();

        // define user set variables
        $this->title = $this->get_option('title');
        //$this->enabled = $this->get_option('enabled');
        $this->description = $this->get_option('description');

        $this->iframe_access_token = $this->get_option('iframe_access_token');
        $this->sending_receipt = $this->get_option('sending_receipt');
        
        $this->token_sales_endpoint = "https://apigateway.payfirma.com/transaction-service/sale/token";

        $this->keys_val = get_option('woocommerce_payfirma_gateway_keys_val');
        //$this->http_force = get_option('woocommerce_unforce_ssl_checkout');
        $this->http_force = 'no';
        
        $this->env_error = 'false';
        $this->disablegateway_js ='';

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // if force http after checkout is checked don't run SSL checkout page validation
        if ($this->http_force != true) {
            $this->sslcheck = check_ssl_checkoutpage();
        } else {
            $this->sslcheck = 'na';
        }

        // check if force ssl option is checked
        $this->forcesslchecked = force_ssl_checked();

        // check for existing client_secret key signature
        if (is_array($this->keys_val) && $this->keys_val['status'] == 'true'):
            if ($this->iframe_access_token == $this->keys_val['iframe_access_token']) :
                $this->api_info_valid = 'true';
            else:
                $this->api_info_valid = 'false';
                update_option( 'woocommerce_payfirma_gateway_keys_val', array('status'=>'false','iframe_access_token'=>$this->iframe_access_token));
            endif;
        else:
            $this->api_info_valid = 'false';
            update_option( 'woocommerce_payfirma_gateway_keys_val', array('status'=>'false','iframe_access_token'=>$this->iframe_access_token));
        endif;

        // check if currency is valid
        $this->currency_valid = $this->is_currency_valid();

        if ($this->sslcheck == 'false' || $this->forcesslchecked != 'true' || $this->api_info_valid != 'true' || $this->http_force !='no' || $this->currency_valid=='false') {

            // disable gateway
            $this->enabled = false;

            // include jquery to uncheck enabled box
            $this->disablegateway_js = '<script type="text/javascript">
            jQuery(document).ready(function ($) {
                $( "#woocommerce_payfirma_gateway_enabled" ).prop( "checked", false );
            });
            </script>';

            // process errors
            $this->env_error = $this->gen_payfirma_error();
        }
    }

    /**
     * Create the settings page for the Payfirma Payment Gateway.
     *
     * @access public
     */
    public function admin_options()
    {
    ?>
        <script type="text/javascript">
            const SALE_API_END_POINT = "<?php echo $this->token_sales_endpoint; ?>"
            const environment = 'LIVE'
            const apiKey = "<?php echo $this->iframe_access_token; ?>"
            const sending_receipt_flag = "<?php echo $this->sending_receipt; ?>"
            
            var options = {
                fields: {
                    cardNumber: {
                    selector: "#cardNumber_container",
                    placeholder: "Credit Card *",
                    },
                    cardExpiry: {
                    selector: "#cardExpiry_container",
                    placeholder: "Expires (MM/YY) *"
                    },
                    cardCvv: {
                    selector: "#cardCvv_container",
                    placeholder: "CVV *"
                    }
                },
                // set the CSS styles to apply to the payment fields
                // camelCases instead dash-separated CSS attributes  
                style: {
                    input: {
                            "color": "#ff0000",
                            "backgroundColor": "#ffffff",
                    }
                } 
            };

            // 4.4 init payfirma object
            const payfirmaObject= Payfirma.payfirmaInit(environment, apiKey,options);

            payfirmaObject.NumberField().render(options.fields.cardNumber.selector);
            payfirmaObject.ExpiryField().render(options.fields.cardExpiry.selector);
            payfirmaObject.CVVField().render(options.fields.cardCvv.selector);

            // validate the form to prevent activating the gateway without the data needed for it to work.
            jQuery(document).ready(function () {

                jQuery("#mainform").validate({
                    rules: {
                        woocommerce_payfirma_gateway_iframe_access_token: {
                            required: {
                                depends: function(element) {
                                    return jQuery("input[name='woocommerce_payfirma_gateway_enabled']:checked").length == 1
                                }
                            }
                        }
                    },
                    messages: {
                        woocommerce_payfirma_gateway_iframe_access_token: {
                            required: "Please enter your Payhq Iframe access token"
                        }
                    }
                });

                jQuery("#test_access_token").click(function(){
                    payfirmaObject.tokenize().then((response)=>{
                        // Test sale transaction with card token.
                        let saleEndpoint = SALE_API_END_POINT;
                        let data = {
                            amount: '1',
                            payment_token: response.payment_token,
                            first_name: 'Payfirma',
                            last_name:'Woo Plugin',
                            sending_receipt: sending_receipt_flag == 'yes'? true: false,
                            city: 'Vancouver',
                            province: 'BC',
                            postal_code: 'V6E 4E6',
                            country: 'CA',
                            currency: '<?php echo get_woocommerce_currency()?>',
                            description: 'Test transaction from WooCommerce Plugin',
                            test_mode:true
                        }

                        jQuery.ajax({
                            url:saleEndpoint,
                            type:"POST",
                            headers: { 
                                'Authorization': 'Bearer ' + apiKey,
                                'Content-Type': 'application/json'
                            },
                            data:JSON.stringify(data),
                            success:function(response) {
                                jQuery( "#woocommerce_payfirma_gateway_enabled" ).prop( "checked", true );
                                <?php
                                    $this->enabled = true;
                                    update_option( 'woocommerce_payfirma_gateway_keys_val', array('status'=>'true','iframe_access_token'=>$this->iframe_access_token));
                                ?>
                                document.querySelector('#cardtoken-result-error').innerText = "";
                                document.querySelector('#cardtoken-result-success').innerText = "Test Sale: Success to validate access token with a Test transaction.";
                                alert('Success to validate access token with a Test transaction\nPlease, Save Changes');
                            },
                            error:function(error){
                                <?php
                                    $this->enabled = false;
                                ?>
                                document.querySelector('#cardtoken-result-success').innerText = "";
                                document.querySelector('#cardtoken-result-error').innerText = "Test Sale: Failed to validate your iframe access token.";
                               
                            }
                        });

                    }).catch(error => {
                        // There was an error tokenizing your card data
                        document.querySelector('#cardtoken-result-success').innerText = "";
                        document.querySelector('#cardtoken-result-error').innerText = "Generate Card: Failed to validate your iframe access token.";
                        <?php
                            $this->enabled = false;
                        ?>
                    })
                });
            });

        </script>
        <?php

        // load disabling jquery if gateway disabled
        echo $this->disablegateway_js;

        ?>
        <h3><?php _e('KORT Payments for WooCommerce', 'woocommerce'); ?></h3>
        <p><?php _e('KORT Payments works by processing the <strong>credit card</strong> payment via your PayHQ account.', 'woocommerce'); ?></p>
            <table class="form-table">
                <?php

                // load error message if errors
                if($this->env_error !='false'){
                    echo $this->env_error;
                }

                // Generate the HTML For the settings form.
                $this->generate_settings_html();
                ?>
            </table><!--/.form-table-->

            <!-- New for validation access token -->
            <table class="form-table">
            <tr valign="top">
            <th class="titledesc"></th>
            <td>
                <div style="background-color:#dddddd;padding: 10px 20px;">
                <h3>Test Transaction</h3>
                <p style="color:#2271b1;"><b>Please, Save your changes, before running a test transaction.</b></p>
                <h4>Test Card</h4>
                <ul>
                    <li>Card number : 4111 1111 1111 1111</li>
                    <li>Expiry : 11/25 (Any future date)</li>
                    <li>CVV : 123 (Any three digits)</li>
                </ul>

                <br/>
                <p>*Your test transaction can be found via the <a href="https://hq.payfirma.com" target="_blank">My Transactions of PayHQ</a>.</p>
                </div>

                <br/>
                <div id="cardNumber_container" class="inputFieldContainer" style="height:40px;"></div> 
                <div id="cardExpiry_container" class="inputFieldContainer" style="height:40px;"></div> 
                <div id="cardCvv_container" class="inputFieldContainer" style="height:40px;"></div>

                <br/>
                <div id="cardtoken-result-error" style="color:red;"></div>
                <div id="cardtoken-result-success" style="color:green;"></div>
                <br/>
                <div id="test_access_token" style="text-align:center;color:white; padding:10px; cursor: pointer; font-Weight:600; background-color:black">Test Transaction</div>
            </td>
            </tr>
            </table>
            <!-- End New for validation access token -->
        <?php
    }

    /**
     * Run gateway error checks and if errors, create error message
     * @return string
     */
    public function gen_payfirma_error(){

        $errortext = '';
        $errors = array();

        // if force http after checkout is checked
        if($this->http_force !='no') {
            $errors[] = 'Please uncheck the "Force HTTP when leaving the checkout" option located on
                     the <a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=advanced">advanced Options</a> page.';
        }

        // if force ssl option is not checked
        if($this->forcesslchecked !='true'){
            $errors[] = 'Please check the "Force Secure Checkout" box on the
                <a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=advanced">Checkout Options</a> advanced tab.';
        }

        // if currency is invalid
        if ($this->currency_valid != 'true') {
            $errors[] = 'Supported currencies are CAD and USD. Please switch to a supported currency on the
                <a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=general">General Options</a> tab.';
        }

        // if form submitted run additional checks
        if($_POST) {

            // if SSL on checkout page check fails
            if ($this->sslcheck == 'false') {
                $errors[] = 'Please install a valid SSL certificate at your domain, for use with the checkout page';
            }

            // if Payfirma API credentials are invalid
            if($this->api_info_valid!='true'){
                //$errors[] = 'Please re-enter your Client ID and Client Secret.';
                $errors[] = 'Failed to validate your iframe access token.';
            }

        }

        // if error(s) found return error message
        if(!empty($errors)){
            $errortext = '<div class="inline error"><p><strong>' . __('Gateway Disabled', 'woocommerce') . '</strong>:<br />';

            foreach($errors as $error){
                $errortext .= __($error, 'woocommerce').'<br />';
            }
            $errortext.='</p></div>';
        }

        return $errortext;
    }

    /**
     * Check if the selected currency is valid for the Payfirma Gateway.
     *
     * @access public
     * @return bool
     */
    public function is_currency_valid()
    {
        if (!in_array(get_woocommerce_currency(), apply_filters('woocommerce_paypal_supported_currencies', array('CAD', 'USD')))) return 'false';

        return 'true';
    }

    /**
     * Show the Payfirma Gateway Settings Form Fields in the Settings page
     *
     * @access public
     * @return void
     */
    public function init_form_fields()
    {

        // set the form fields array for the Payfirma Payment Gateway settings page.
        $this->form_fields = array(

            'title' => array(
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                'default' => __('Credit Card', 'woocommerce'),
            ),
            'description' => array(
                'title' => __('Description:', 'woocommerce'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
                'default' => __('Pay securely with your Credit Card.', 'woocommerce'),
            ),

            'iframe_access_token' => array(
                'title' => __('Iframe Access Token', 'woocommerce'),
                'type' => 'textarea',
                'description' => __('*Your Iframe Access Token can be found and changed via the <a href="https://hq.payfirma.com/#/settings/hpp">iframe Settings page of PayHQ.</a>', 'woocommerce'),
                'default' => ''
            ),

            'sending_receipt' => array(
                'title' => __('Sending Receipt', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Sending Receipt via KORT Payments', 'woocommerce'),
                'default' => 'yes'
            ),

            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable KORT Payment', 'woocommerce'),
                'default' => 'no'
            ),
        );

    }

    /**
     * Initialise the payment fields on the front-end payment form
     *
     * @access public
     */
    public function payment_fields()
    {
        // ** use payfirma form_field to create new form fields.
       global $woocommerce;

        echo'
            <div id="cardNumber_container" class="inputFieldContainer" style="height:40px;"></div> 
            <div id="cardExpiry_container" class="inputFieldContainer" style="height:40px;"></div> 
            <div id="cardCvv_container" class="inputFieldContainer" style="height:40px;"></div>
            <div id ="cardtoken-error" class="card-error"> </div>
            <br/>
            
            <div style="overflow: hidden; height: 15px; position: relative;">
                <img src="'.plugin_dir_url( __DIR__ ) .'/img/powered-by-kort.svg" style="position: absolute; top: -85px; height: 120px;" />
            </div>
        ';

        ?> 
            <script type="text/javascript">
              
                jQuery(document).ready(function () {
                    const environment = 'LIVE'
                    const apiKey = "<?php echo $this->iframe_access_token; ?>"
                    const sending_receipt_flag = "<?php echo $this->sending_receipt; ?>"
                    
                    var options = {
                        fields: {
                            cardNumber: {
                            selector: "#cardNumber_container",
                            placeholder: "Credit Card *",
                            },
                            cardExpiry: {
                            selector: "#cardExpiry_container",
                            placeholder: "Expires (MM/YY) *"
                            },
                            cardCvv: {
                            selector: "#cardCvv_container",
                            placeholder: "CVV *"
                            }
                        },
                        // set the CSS styles to apply to the payment fields
                        // camelCases instead dash-separated CSS attributes  
                        style: {
                            input: {
                                    "color": "#ff0000",
                                    "backgroundColor": "#ffffff",
                            }
                        } 
                    };

                    // 4.4 init payfirma object
                    const payfirmaObject= Payfirma.payfirmaInit(environment, apiKey,options);
                    payfirmaObject.NumberField().render(options.fields.cardNumber.selector);
                    payfirmaObject.ExpiryField().render(options.fields.cardExpiry.selector);
                    payfirmaObject.CVVField().render(options.fields.cardCvv.selector);

                    jQuery(document).on('click', '.payfirma_submit, #place_order', function(e){
                        e.preventDefault();      
                        if (jQuery('input[name="payment_method"]:checked').val() == 'payfirma_gateway') {
                            payfirmaObject.tokenize().then((response)=>{
                                document.cookie = 'tempCardToken=' + response.payment_token + '; path=/;';
                                document.querySelector('#cardtoken-error').innerText = "";
                                tokenData = response.payment_token;

                                let isPayfirmaSubmit = jQuery('.payfirma_submit').is(':focus');
                                let isPlaceOrder = jQuery('#place_order').is(':focus');
                                
                                if (isPayfirmaSubmit) {
                                    // console.log("Submitted via .payfirma_submit button");
                                    jQuery( 'form.checkout' ).submit();
                                    // Add your custom logic for .payfirma_submit here
                                } else if (isPlaceOrder) {
                                    // console.log("Submitted via #place_order button");
                                    jQuery('#order_review').submit();
                                    // Add your custom logic for #place_order here
                                } else {
                                    console.log("Form submitted without the specified buttons being clicked.");
                                }


                            }).catch(error => {
                                var resultError = Object.entries(error);

                                //Error: Request failed with status code 400
                                if(resultError.length > 0) {
                                    document.cookie = 'tempCardToken=' + '' + '; path=/;';
                                    document.querySelector('#cardtoken-error').innerText = "Please, check your card information.";
                                }

                                return false;
                            })
                        } else {
                            var noCardToken = "12345678901234567890"
                            document.cookie = 'tempCardToken=' + noCardToken + '; path=/;';
                            jQuery( 'form.checkout' ).submit();
                        }
                    })
                });
            </script>
    <?php
    
    }

    /**
     * Process Payfirma Payment
     *
     * @access public
     * @param $order_id
     * @return array
     */
    function process_payment($order_id)
    {
        global $woocommerce;

        // get the order by order_id
        $order = new WC_Order($order_id);

        //CHECK VALIDATION CARD TOKEN
        if (!isset($_COOKIE['tempCardToken']) ||  strlen($_COOKIE['tempCardToken']) != 20){
            unset($_COOKIE['tempCardToken']); 
            wc_add_notice( 'Invalid card token for payment.Please check your <strong>Credit Card information</strong>.', $notice_type = 'error' );
            return;
        }
        
        $paymentToken = $_COOKIE['tempCardToken'];
        // get all of the args  -> see get_paypal_args
        $payfirma_args = $this->get_payfirma_args($order, $paymentToken);

        // send data to Payfirma
        $payfirma_result = $this->post_to_payfirma($payfirma_args);

       if ($payfirma_result['transaction_result'] === 'APPROVED'):

            // mark payment as complete
            $order->payment_complete();

            // Remove cart
            $woocommerce->cart->empty_cart();

            // Return thankyou redirect
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );

       // payment declined
       elseif ($payfirma_result['transaction_result'] === 'DECLINED'):
           $error_message = 'Your payment declined.  Please enter your payment details again.';
           wc_add_notice( '<strong>Payment declined:</strong>Please enter your payment details again', $notice_type = 'error' );
           return;

       // API issue
       else:
           wc_add_notice( '<strong>Payment error:</strong>Please enter your payment details again', $notice_type = 'error' );
           return;
       endif;

    }

    /**
     * Build array of args to send to Payfirma for processing
     *
     * @access private
     * @return array
     */
    private function get_payfirma_args($order, $paymentToken)
    {
        global $woocommerce;
        $version_num = get_woo_version();
 
        // payfirma required arguments
        $payfirma_args = json_encode(array(
            'first_name' => $order->billing_first_name,
            'last_name' => $order->billing_last_name,
            'company' => $order->billing_company,
            'address1' => $order->billing_address_1,
            'address2' => $order->billing_address_2,
            'city' => $order->billing_city,
            'province' => $order->billing_state,
            'postal_code' => $order->billing_postcode,
            'country' => $order->billing_country,
            'email' => $order->billing_email,
            'amount_tax' => $order->get_total_tax(),
            'amount' => $order->order_total,
            'order_id' => $order->id,
            'currency' => get_woocommerce_currency(),
            'telephone'=> $order->billing_phone,
            'payment_token' =>  $paymentToken,
            'sending_receipt'=> $this->sending_receipt == 'yes'? true: false,
            'description'=>'Order #'.$order->id.' from '.get_bloginfo('url'),
			'do_not_store'=>'true',
            'ecom_source'=>'WooCommercev'.$version_num
        ));

        // return the array of arguments
        return $payfirma_args;
    }

    /**
     * Open a cURL connection to Payfirma API, post values, return the result object
     *
     * @access private
     * @return object
     */
    private function post_to_payfirma($payfirma_args)
    {
        $SALE_API_END_POINT =  $this->token_sales_endpoint;
        $access_token = $this->iframe_access_token;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $SALE_API_END_POINT,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $payfirma_args,
            CURLOPT_HTTPHEADER => array(
            "authorization: Bearer ".$access_token,
            "cache-control: no-cache",
            "Content-Type:application/json"
            ),
        ));

        //execute post
        $result = curl_exec($curl);

        //close connection
        curl_close($curl);

        //parse the result into an object
        $json_feed_object = json_decode($result, true);
    
        return $json_feed_object;
    }
}
