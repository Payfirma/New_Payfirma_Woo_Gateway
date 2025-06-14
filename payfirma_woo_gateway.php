<?php
/**
 * Plugin Name: KORT Payments for WooCommerce
 * Plugin URI: https://github.com/Payfirma/New_Payfirma_Woo_Gateway
 * Description: KORT Payment's WooCommerce plugin has arrived for all of your payment needs. Start accepting credit cards on your WooCommerce 2.5+ site, with a valid SSL connection (sorry, non-self-signed only) and cURL activated on your server, you will be able to process payments using your PayHQ Merchant account.
 * Version: 4.2
 * Author: KORT Payments
 * Author URI: https://www.kortpayments.com
 * License: GPL2
 */


/**
 * Perform plugin environment checks before allowing KORT Gateway to be activated.
 */
function payfirma_woo_requires() {

    global $wp_version;

    global $woocommerce;

    $plugin = plugin_basename( __FILE__ );
    $plugin_data = get_plugin_data( __FILE__, false );
    $require_wp = "3.5";

    $plugin_to_check= 'woocommerce/woocommerce.php';
    $req_woocommerce_version ='2.0';

    $payfirma_curl = payfirma_curl_installed();

    /**
     * Automatically deactivates Payfirma Woo Gateway if WooCommerce is deactivated.
    */
    if ( version_compare( $wp_version, $require_wp, "<" ) ) {

        if( is_plugin_active($plugin) ) {
            deactivate_plugins( $plugin );
            wp_die( "<strong>".$plugin_data['Name']."</strong> requires <strong>WordPress ".$require_wp."</strong> or higher, and has been deactivated! Please upgrade WordPress and try again.<br /><br />Back to the WordPress <a href='".get_admin_url(null, 'plugins.php')."'>Plugins page</a>." );
        }
    }

    /**
     * Automatically deactivates Payfirma Woo Gateway if CURL cannot be used.
     */
    if( $payfirma_curl=='false'){

        deactivate_plugins( $plugin );
        wp_die( "<strong>".$plugin_data['Name']."</strong> requires <strong> cURL</strong> to be active, and has been deactivated! Please activate cURL to use ".$plugin_data['Name']." again.<br /><br />Back to the WordPress <a href='".get_admin_url(null, 'plugins.php')."'>Plugins page</a>." );

    }

    /**
     * Automatically deactivates Payfirma Woo Gateway if SSL is not valid.
     */
    if(check_ssl_valid() !='true'){
       
        deactivate_plugins( $plugin );
        wp_die( "<strong>".$plugin_data['Name']."</strong> requires a <strong> valid SSL certificate</strong> to be active, and has been deactivated! Please install a valid SSL certificate to use ".$plugin_data['Name']." again.<br /><br />Back to the WordPress <a href='".get_admin_url(null, 'plugins.php')."'>Plugins page</a>." );

    }

    /**
     * Automatically deactivates Payfirma Woo Gateway if parent plugin WooCommerce is not active.
     */
    if(!is_plugin_active($plugin_to_check)){

        deactivate_plugins( $plugin );
        wp_die( "<strong>".$plugin_data['Name']."</strong> requires <strong> WooCommerce</strong> to be active, and has been deactivated! Please activate WooCommerce to use ".$plugin_data['Name']." again.<br /><br />Back to the WordPress <a href='".get_admin_url(null, 'plugins.php')."'>Plugins page</a>." );

    }else{

        /**
         * Automatically deactivates Payfirma Woo Gateway if active WooCommerce is an unsupported version #.
         */

        if( get_woo_version() < $req_woocommerce_version){

            deactivate_plugins( $plugin );
            wp_die( "<strong>".$plugin_data['Name']."</strong> requires <strong> WooCommerce</strong> to be Version ".$req_woocommerce_version." or above, and has been deactivated! Please install and activate WooCommerce ".$req_woocommerce_version." or above to use ".$plugin_data['Name']." again.<br /><br />Back to the WordPress <a href='".get_admin_url(null, 'plugins.php')."'>Plugins page</a>." );

        }
    }

    if(is_plugin_active($plugin)) {

        add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'add_action_links');
 
        function add_action_links ( $actions ) {
        $settingLinks = array(
            '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=payfirma_gateway' ) . '">Settings</a>',
        );
        $actions = array_merge( $settingLinks , $actions);
        return $actions;
        }
    }
}
add_action( 'admin_init', 'payfirma_woo_requires' );


/**
 * Checks to make sure WooCommerce is active before loading the Payfirma Woo Gateway assets.
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )) {

    // if woocommerce is installed, add payment gateway
    include('payfirma_woo_go.php');

    add_action('admin_enqueue_scripts', 'add_my_js');
    function add_my_js(){
        wp_enqueue_script('my_validate', plugin_dir_url( __FILE__ ) .'js/jquery.validate.min.js', array('jquery'));
        wp_enqueue_script('payhq_iframe_library', 'https://iframe.payfirma.com/payhq-js/payhq-iframe-lib.min.js', array('jquery'));
    }

    add_action('init', 'add_my_style');
    function add_my_style(){
        wp_enqueue_style( 'Payfirma_Style', plugin_dir_url( __FILE__ ) .'css/payfirma.css' );
    }

    add_action( 'wp_enqueue_scripts', 'inputmask_scripts' );
    function inputmask_scripts() {
        wp_enqueue_script('inputmask', plugin_dir_url( __FILE__ ) .'js/jquery.inputmask.bundle.min.js', array('jquery'));
        wp_enqueue_script( 'payhq_iframe_library', 'https://iframe.payfirma.com/payhq-js/payhq-iframe-lib.min.js', array('jquery'));
    }
}

/**
 * Checks set website for valid SSL certificate
 * @return string
 */
function check_ssl_valid(){
    // * ============================================= * //
    // We don't check ssl valid anymore.
    // * ============================================= * //
    return 'true';
}

/**
 * Checks the set checkout page for SSL connection and '200' (ok) header response.
 *
 */

function check_ssl_checkoutpage(){
    // * ============================================= * //
    // We don't check ssl valid anymore.
    // * ============================================= * //
    return 'true';
}

/**
 * Checks the for the "Force secure checkout" WooCommerce option to be checked.
 * @return string
 */
function force_ssl_checked(){
    // * ============================================= * //
    //  We don't check ssl valid anymore.
    // * ============================================= * //
    return 'true';
}

/**
 * Checks the status of cURL on the server.
 *
 */
function payfirma_curl_installed() {

    if(!function_exists('curl_exec'))
    {
        return 'false';
    }
}

/**
 * get woo commerce version number
 * @return mixed
 */
function get_woo_version(){

    $plugin_to_check= 'woocommerce/woocommerce.php';
    if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $plugins = get_plugins();
    $plugin_data = $plugins[$plugin_to_check];

    return $plugin_data['Version'];
}

/**
 * Class Payfirma_Logger
 *
 * Logger class so any issues can be logged in the same place.
 *
 * The logfile is located in the logs folder within the Payfirma_Woo_Gateway plugin folder.
 * 
 *  $this->log = new Payfirma_Logger();
 *  $this->log->add_to_logfile( 'Payfirma_Gateway', 'Authentication Failed:');
 */

class Payfirma_Logger{

    /**
     * Creates the necessary gateway log file in the Payfirma Woo Gateway plugin "logs" folder.
     * If file creation fails, plugin is deactivated.
     *
     * @access public
     */

    public function create_logfile($plugin=''){

        //$newFileName2 = ABSPATH.'wp-content/plugins/Payfirma_Woo_Gateway/logs/logfile.php';
        $newFileName2 = plugin_dir_url( __DIR__ ).'/logs/logfile.php';
        $newFileContent2 = " <?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>".PHP_EOL;

        if(!file_exists($newFileName2)):
            if(file_put_contents($newFileName2,$newFileContent2)===false):
                deactivate_plugins( $plugin );
                wp_die( '<strong>Cannot create file '.basename($newFileName2).'.  Please check your server settings to make sure the "Payfirma_Woo_Gateway" plugin folder is writable.</strong><br /> <br />Back to the WordPress <a href="'.get_admin_url(null, "plugins.php").'">Plugins page</a>.');

            endif;
        endif;

        return 'true';
    }

    /**
     * Stores the error string in the Payfirma Woo Gateway logfile.  Log file is .php to prevent online access to contents.
     * If directly accessed, blank page loads.
     * Read via FTP download and open like a text file.
     * @access public
     * @param string $type
     * @param string $error
     */

    public function add_to_logfile($type='',$error=''){

        $date = date('Y-m-d H:i:s');
        $content = '+ '.$type.' Error Occured: '.$date.': '.$error.PHP_EOL;
        $file = plugin_dir_url( __DIR__ ).'/logs/logfile.php';

        file_put_contents($file, $content, FILE_APPEND | LOCK_EX);
    }
}