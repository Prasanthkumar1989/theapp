<?php
include 'checksum.php';

/**
 * Plugin Name: WooCommerce airpay payment services
 * Plugin URI: http://airpay.co.in/
 * Description: Used to integrate the airpay payment services with your website.
 * Version: 1.2
 * Author: Airpay
 * Author URI: http://airpay.co.in/
 * Requires at least: 4.1
 * Tested up to: 4.5
 */

add_action('plugins_loaded', 'woocommerce_airpay_init', 0);

/**
 * Initialize the WooCommerce AirPay integration.
 *
 * This function sets up any necessary hooks or configurations for the AirPay payment gateway
 * to work seamlessly with WooCommerce.
 *
 * @since 1.0.0
 */
function woocommerce_airpay_init()
{

/**
 * Checks if the class has been defined.
 *
 * @param string $name The case-insensitive name of class/trait/interface
 * @param bool $autoload Whether to call spl_autoload()
 * @return bool
 */
if (!class_exists('WC_Payment_Gateway', $autoload = true)) {
    return;
}

/**
 * Localisation
 */
load_plugin_textdomain('wc-Airpay', false, dirname(plugin_basename(__FILE__)) . '/languages');

if (isset($_GET['msg'])) {
    if ($_GET['msg'] != '') {
        add_action('the_content', 'showMessage');
    }
}

/**
 * Gateway class
 */
class WC_Airpay extends WC_Payment_Gateway
{
    protected $msg = array();

    public function __construct()
    {
        // construct form //
        // Go wild in here
        $this->id = 'airpay';
        $this->method_title = 'Airpay';
        $this->icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/logo.png';
        $this->has_fields = false;
        $this->init_form_fields();
        $this->init_settings();
        $this->title = $this->settings['title'];
        $this->description = $this->settings['description'];
        $this->merchantIdentifier = $this->settings['merchantIdentifier'];
        $this->secret_key = $this->settings['secret_key'];
        $this->currency = $this->settings['currency'];
        $this->iso_currency = $this->settings['iso_currency'];
        $this->payment_mode = $this->settings['payment_mode'];
        $this->username = $this->settings['username'];
        $this->Password = $this->settings['Password'];
        $this->redirect_page_id = $this->settings['redirect_page_id'];
        $this->mode = isset($this->settings['mode']) ? $this->settings['mode'] : "";
        $this->log = $this->settings['log'];
        $this->liveurl = "https://payments.airpay.co.in/pay/index.php";
        $this->msg['message'] = "";
        $this->msg['class'] = "";

        add_action('init', array(&$this, 'check_Airpay_response'));
        //update for woocommerce >2.0

        add_action('woocommerce_api_' . strtolower(get_class($this)), array(&$this, 'check_Airpay_response'));
        add_action('valid-Airpay-request', array(&$this, 'successful_request')); // this save
        if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
        } else {
            add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
        }
        add_action('woocommerce_receipt_airpay', array(&$this, 'receipt_page'));
        //   add_action('woocommerce_thankyou_airpay',array(&$this, 'thankyou_page'));
    }
    public function init_form_fields()
    {

        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable'),
                'type' => 'checkbox',
                'label' => __('Enable Airpay Payment Module.'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title:'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.'),
                'default' => __('Airpay')
            ),
            'description' => array(
                'title' => __('Description:'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.'),
                'default' => __('The best payment gateway provider in India for e-payment through credit card, debit card & netbanking.')
            ),

            'merchantIdentifier' => array(
                'title' => __('Merchant Id'),
                'type' => 'text',
                'description' => __('This id(Merchant Id) given to Merchant by Airpay.')
            ),
            'username' => array(
                'title' => __('User Name'),
                'type' => 'text',
                'description' => __('Given to Merchant by Airpay'),
            ),
            'Password' => array(
                'title' => __('Password'),
                'type' => 'password',
                'description' => __('Given to Merchant by Airpay'),
            ),
            'secret_key' => array(
                'title' => __('Secret Key'),
                'type' => 'text',
                'description' => __('Given to Merchant by Airpay'),
            ),
            'currency' => array(
                'title' => __('Currency code'),
                'type' => 'text',
                'description' => __('Currency code'),
            ),
            'iso_currency' => array(
                'title' => __('ISO Currency'),
                'type' => 'text',
                'description' => __('ISO Currency'),
            ),
            'payment_mode' => array(
                'title' => __('Payment Mode'),
                'type' => 'text',
                'description' => __('chmod variable contains Payment Modes available for user. for e.g. If you want to show only Credit Card/Debit Card, then value of the chmod variable will be "pg". If you want Netbanking and Prepaid card then value of the chmod variable will be "nb_ppc".
                If you want to show all payment options activated for you at airpay, then leave this variable blank.

                '),
            ),
            'redirect_page_id' => array(
                'title' => __('Return Page'),
                'type' => 'select',
                'options' => $this->get_pages('Select Page'),
                'description' => "URL of success page",
            ),
            'log' => array(
                'title' => __('Do you want to log'),
                'type' => 'text',
                'options' => 'text',
                'description' => "(yes/no)",
            ),
        );

    }

    /**
     * Admin Panel Options
     * - Options for bits like 'title' and availability on a country-by-country basis
     **/
    public function admin_options()
    {
        echo '<h3>' . __('Airpay Payment Gateway') . '</h3>';
        echo '<p>' . __('India online payment solutions for all your transactions by Airpay') . '</p>';
        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';

    }
    /**
     *  There are no payment fields for Airpay, but we want to show the description if set.
     **/
    public function payment_fields()
    {
        if ($this->description) {
            echo wpautop(wptexturize($this->description));
        }

    }
    /**
     * Receipt Page
     **/
    public function receipt_page($order)
    {

        echo '<p>' . __('Thank you for your order, please click the button below to pay with Airpay.') . '</p>';
        echo $this->generate_airpay_form($order);

    }

    /**
     * Process the payment and return the result
     **/
    public function process_payment($order_id)
    {
        //if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
        $order = new WC_Order($order_id);
        //   echo '<pre>';print_r($order);exit;
        //} else {
        //$order = &new woocommerce_order($order_id);
        //}
        return array(
            'result' => 'success',
            'redirect' => add_query_arg(
                'order',
                $order->id,
                add_query_arg('key', $order->order_key, $order->get_checkout_payment_url(true))
            ),
        );
    }

    /**
     * Check for valid Airpay server callback // response processing //
     **/

    public function check_Airpay_response()
    {

        global $woocommerce;
        if (isset($_REQUEST['TRANSACTIONID']) && isset($_REQUEST['TRANSACTIONSTATUS'])) {

            $order_sent = $_REQUEST['TRANSACTIONID'];
            $TRANSACTIONID = trim($_POST['TRANSACTIONID']);
            $order = trim($_POST['TRANSACTIONID']);
            $responseDescription = $_REQUEST['MESSAGE'];
            if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                $order = new WC_Order($_REQUEST['TRANSACTIONID']);
            } else {
                $order = new woocommerce_order($_REQUEST['TRANSACTIONID']);
            }
            if ($this->log == "yes") {
                error_log("Response Code = " . $_REQUEST['TRANSACTIONSTATUS']);
            }
            $redirect_url = ($this->redirect_page_id == "" || $this->redirect_page_id == 0) ? get_site_url() . "/" : get_permalink($this->redirect_page_id);

            $APTRANSACTIONID = trim($_POST['APTRANSACTIONID']);
            $AMOUNT = trim($_POST['AMOUNT']);
            $TRANSACTIONSTATUS = trim($_POST['TRANSACTIONSTATUS']);
            $TRANSACTIONSTATUS = trim($_POST['TRANSACTIONSTATUS']);
            $responseDescription = trim($_POST['MESSAGE']);
            $ap_secure_hash = trim($_POST['ap_SecureHash']);
            $customervpa = trim($_POST['CUSTOMERVPA']);
            $chmod = trim($_POST['CHMOD']);
            $error_msg = '';
            if (empty($order) || empty($APTRANSACTIONID) || empty($AMOUNT) || empty($TRANSACTIONSTATUS) || empty($ap_secure_hash)) {
                // Reponse has been compromised. So treat this transaction as failed.
                if (empty($order)) {
                    $error_msg = 'TRANSACTIONID ';
                }
                if (empty($APTRANSACTIONID)) {
                    $error_msg .= ' APTRANSACTIONID';
                }
                if (empty($AMOUNT)) {
                    $error_msg .= ' AMOUNT';
                }
                if (empty($TRANSACTIONSTATUS)) {
                    $error_msg .= ' TRANSACTIONSTATUS';
                }
                if (empty($ap_SecureHash)) {
                    $error_msg .= ' ap_SecureHash';
                }
                $error_msg .= '<tr><td>Variable(s) ' . $error_msg . ' is/are empty.</td></tr>';
                //exit();
            }
            $this->msg['class'] = 'error';
            $this->msg['message'] = "Thank you for shopping with us. However, the transaction has been Failed For Reason  : " . $error_msg;

            $this->msg['class'] = 'error';
            $this->msg['message'] = "Thank you for shopping with us. However, the transaction has been Failed For Reason  : " . $responseDescription;
            if ($_REQUEST['TRANSACTIONSTATUS'] == 200) {
                // success

                $mercid = $this->merchantIdentifier;
                $username = $this->username;
                $order_amount = $order->order_total;
                $order_amount = sprintf('%01.2f', $order_amount); //number_format($order_amount,2);

                if (($_REQUEST['AMOUNT'] == $order_amount)) {
                    if ($this->log == "yes") {
                        error_log("amount matched");
                    }
                    $all = Checksum::getAllParams();
                    if ($this->log == "yes") {
                        error_log("received parameters = " . $all);
                    }

                    $newcheck = sprintf("%u", crc32($TRANSACTIONID . ':' . $APTRANSACTIONID . ':' . $AMOUNT . ':' . $TRANSACTIONSTATUS . ':' . $responseDescription . ':' . $mercid . ':' . $username));
                    if (trim($_REQUEST['CHMOD']) == 'upi') {
                        $cvar = trim($_REQUEST['CUSTOMERVPA']);
                        $newcheck = sprintf("%u", crc32($TRANSACTIONID . ':' . $APTRANSACTIONID . ':' . $AMOUNT . ':' . $TRANSACTIONSTATUS . ':' . $responseDescription . ':' . $mercid . ':' . $username . ':' . $cvar));
                    }
                    if ($this->log == "yes") {
                        error_log("calculated checksum = " . $newch . " and checksum received = " . $_REQUEST['checksum']);
                    }
                    if ($newcheck == $ap_SecureHash) {
                        if ($order->status !== 'completed') {
                            error_log("SUCCESS");
                            $this->msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful.";
                            $this->msg['class'] = 'success';
                            if ($order->status == 'processing') {

                            } else {
                                $order->payment_complete();
                                $order->add_order_note('Mobile Wallet payment successful');
                                $order->add_order_note($this->msg['message']);
                                $woocommerce->cart->empty_cart();
                                //echo '<pre>';print_r($order);exit;
                            }
                        }
                    } else {
                        // server to server failed while call//
                        //error_log("api process failed");
                        $this->msg['class'] = 'error';
                        $this->msg['message'] = "Severe Error Occur.";
                        $order->update_status('failed');
                        $order->add_order_note('Failed');
                        $order->add_order_note($this->msg['message']);
                    }

                } else {
                    // Order mismatch occur //
                    //error_log("order mismatch");
                    $this->msg['class'] = 'error';
                    $this->msg['message'] = "Order Mismatch Occur";
                    $order->update_status('failed');
                    $order->add_order_note('Failed');
                    $order->add_order_note($this->msg['message']);

                }
            } else {
                $order->update_status('failed');
                $order->add_order_note('Failed');
                $order->add_order_note($responseDescription);
                $order->add_order_note($this->msg['message']);

            }

            // add_action('the_content', array(&$this, 'showMessage'));

            // $redirect_url = ($this->redirect_page_id=="" || $this->redirect_page_id==0)?get_site_url() . "/":get_permalink($this->redirect_page_id);
            //             //For wooCoomerce 2.0
            //             $redirect_url = add_query_arg( array('msg'=> urlencode($this->msg['message']), 'type'=>$this->msg['class']), $redirect_url );
            //    echo '<pre>';print_r($this->get_return_url( $order ));exit;
            wp_redirect($this->get_return_url($order));
            // wp_redirect( $redirect_url );
            exit;
        } else {
            // echo get_site_url();
            wp_redirect(get_site_url() . "/checkout/");
            exit;
        }
    }

    /**
     * Generate Airpay button link
     **/
    public function generate_airpay_form($order_id)
    {
        global $woocommerce;
        $txnDate = date('Y-m-d');
        $milliseconds = (int) (1000 * (strtotime(date('Y-m-d'))));
        if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
            $order = new WC_Order($order_id);
        } else {
            $order = new woocommerce_order($order_id);
        }
        $redirect_url = ($this->redirect_page_id == "" || $this->redirect_page_id == 0) ? get_site_url() . "/" : get_permalink($this->redirect_page_id);
        // pretty url check //
        $a = strstr($redirect_url, "?");
        if ($a) {
            $redirect_url .= "&wc-api=WC_Airpay";
        } else {
            $redirect_url .= "?wc-api=WC_Airpay";
        }
        error_log("redirect url = this {$redirect_url}");
        //////////////
        $order_id = $order_id;
        $amt = $order->order_total;
        $amt = sprintf('%01.2f', $amt); //number_format($amt,2);
        $txntype = '1';
        $apayoption = '1';
        $currency = "INR";
        $purpose = "1";
        $productDescription = 'airpay';
        $ip = $_SERVER['REMOTE_ADDR'];
        $post_variables = array(
            "merchantIdentifier" => $this->merchantIdentifier,
            "orderId" => $order_id,
            "returnUrl" => $redirect_url,
            "buyerEmail" => $order->billing_email,
            "buyerFirstName" => $order->billing_first_name,
            "buyerLastName" => $order->billing_last_name,
            "buyerAddress" => $order->billing_address_1,
            "buyerCity" => $order->billing_city,
            "buyerState" => $order->billing_state,
            "buyerCountry" => $order->billing_country,
            "buyerPincode" => $order->billing_postcode,
            "buyerPhone" => $order->billing_phone,
            "txnType" => $txntype,
            "apPayOption" => $apayoption,
            "mode" => $this->mode,
            "currency" => $currency,
            "amount" => $amt, //Amount should be in paisa
            "merchantIpAddress" => $ip,
            "purpose" => $purpose,
            "productDescription" => $productDescription,
            "txnDate" => $txnDate,

        );

        $all = '';
        foreach ($post_variables as $name => $value) {
            if ($name != 'checksum') {
                $all .= "'";
                if ($name == 'returnUrl') {
                    $all .= Checksum::sanitizedURL($value);
                } else {

                    $all .= Checksum::sanitizedParam($value);
                }
                $all .= "'";
            }
        }
        if ($this->log == "yes") {
            error_log("AllParams : " . $all);
            error_log("Secret Key : " . $this->secret_key);
        }
        $alldata = Checksum::sanitizedParam($order->billing_email) . Checksum::sanitizedParam($order->billing_first_name) . Checksum::sanitizedParam($order->billing_last_name) . Checksum::sanitizedParam($order->billing_address_1) . Checksum::sanitizedParam($order->billing_city) . Checksum::sanitizedParam($order->billing_state) . Checksum::sanitizedParam($order->billing_country) . Checksum::sanitizedParam($amt) . "" . $order_id;
        $privatekey = Checksum::encrypt($this->username . ":|:" . $this->Password, $this->secret_key);

        $keySha256 = Checksum::encryptSha256($this->username . "~:~" . $this->Password);
        //$checksum = Checksum::calculateChecksum($this->secret_key, $all);
        // $checksum = Checksum::calculateChecksum($alldata.date('Y-m-d'),$privatekey);
        $checksum = Checksum::calculateChecksumSha256($alldata . date('Y-m-d'), $keySha256);

        $Airpay_args = array(
            'merchantIdentifier' => $this->merchantIdentifier,
            'orderId' => $order_id,
            'buyerEmail' => $order->billing_email,
            'buyerFirstName' => $order->billing_first_name,
            'buyerLastName' => $order->billing_last_name,
            'buyerAddress' => $order->billing_address_1,
            'buyerCity' => $order->billing_city,
            'buyerState' => $order->billing_state,
            'buyerCountry' => $order->billing_country,
            'buyerPincode' => $order->billing_postcode,
            'buyerPhone' => $order->billing_phone,
            'txnType' => $txntype,
            'mode' => $this->mode,
            'currency' => $currency,
            'amount' => $amt,
            'chmod' => $this->payment_mode,
            'purpose' => $purpose,
            'productDescription' => $productDescription,
            'txnDate' => $txnDate,
            'checksum' => $checksum,
        );
        foreach ($Airpay_args as $name => $value) {
            if ($name != 'checksum') {
                if ($name == 'returnUrl') {
                    $value = Checksum::sanitizedURL($value);

                } else {
                    $value = Checksum::sanitizedParam($value);

                }
            }
        }

        $Airpay_args_array = array();
        foreach ($Airpay_args as $key => $value) {
            if ($key != 'checksum') {
                if ($key == 'returnUrl') {
                    $Airpay_args_array[] = "<input type='hidden' name='$key' value='" . Checksum::sanitizedURL($value) . "'/>";
                } else {
                    $Airpay_args_array[] = "<input type='hidden' name='$key' value='" . Checksum::sanitizedParam($value) . "'/>";
                }
            } else {
                $Airpay_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
            }
        }
        return '<form action="' . $this->liveurl . '" method="post" id="Airpay_payment_form">
            ' . implode('', $Airpay_args_array) . '
                <input type="hidden" name="privatekey" value="' . $privatekey . '">
                <input type="hidden" name="mercid" value="' . $this->merchantIdentifier . '">
                <input type="hidden" name="orderid" value="' . $order_id . '">
                <input type="hidden" name="currency" value="' . $this->currency . '">
                <input type="hidden" name="isocurrency" value="' . $this->iso_currency . '">

            <input type="submit" class="button-alt" id="submit_Airpay_payment_form" value="' . __('Pay via Airpay') . '" /> <a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __('Cancel order &amp; restore cart') . '</a>
            <script type="text/javascript">
jQuery(function(){
jQuery("body").block(
        {
            message: "<img src=\"' . $woocommerce->plugin_url() . '/assets/images/ajax-loader.gif\" alt=\"Redirecting…\" style=\"float:left; margin-right: 10px;\" />' . __('Thank you for your order. We are now redirecting you to Airpay to make payment.') . '",
                overlayCSS:
        {
            background: "#fff",
                opacity: 0.6
    },
    css: {
        padding:        20,
            textAlign:      "center",
            color:          "#555",
            border:         "3px solid #aaa",
            backgroundColor:"#fff",
            cursor:         "wait",
            lineHeight:"32px"
    }
    });
    jQuery("#submit_Airpay_payment_form").click();

    });
                </script>
            </form>';

    }

    /*
        * End Airpay Essential Functions
        **/
    // get all pages

    public function get_pages($title = false, $indent = true)
    {
        $wp_pages = get_pages('sort_column=menu_order');
        $page_list = array();
        if ($title) {
            $page_list[] = $title;
        }

        foreach ($wp_pages as $page) {
            $prefix = '';
            // show indented child pages?
            if ($indent) {
                $has_parent = $page->post_parent;
                while ($has_parent) {
                    $prefix .= ' - ';
                    $next_page = get_page($has_parent);
                    $has_parent = $next_page->post_parent;
                }
            }
            // add to page list array array
            $page_list[$page->ID] = $prefix . $page->post_title;
        }
        return $page_list;
    }

}

/**
 * Add the Gateway to WooCommerce
 **/
function woocommerce_add_airpay_gateway($methods)
{
    $methods[] = 'WC_Airpay';
    return $methods;
}

add_filter('woocommerce_payment_gateways', 'woocommerce_add_airpay_gateway');
}

?>