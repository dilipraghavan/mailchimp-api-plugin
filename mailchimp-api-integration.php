<?php

/**
 * 
 * Plugin Name: Plugin to integrate with Mailchimp
 * Description: Integrate WordPress websites with Mailchimp. Users can subscribe from a shortcode form. Admin can view status from dashboard and export reports. API keys are stored securely through env settings.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Plugin URI: https://github.com/dilipraghavan/mailchimp-api-plugin.git
 * Author: Dilip Raghavan
 * Author URI: https://www.wpshiftstudio.com
 * Domain Path: /languages
 * Text Domain: mailchimp-api-integration
 */

namespace MC_API;
if(! defined('ABSPATH')) exit;

define('MC_API_PLUGIN_DIR_PATH', plugin_dir_path(__FILE__ ));
define('MC_API_PLUGIN_URL_PATH', plugin_dir_url(__FILE__ ));
define('MC_API_PLUGIN_VERSION', '0.1.0');
define('MC_API_BASENAME', plugin_basename( __FILE__ ));
define('MC_API_SETTINGS_SLUG', 'mc-api-settings');
define('MC_API_ACTIONS_SLUG', 'mc-api-test-connection');

register_activation_hook( __FILE__, __NAMESPACE__ . '\mc_api_activate');
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\mc_api_deactivate');

include_once(MC_API_PLUGIN_DIR_PATH . '/includes/class-bootstrap.php');
include_once(MC_API_PLUGIN_DIR_PATH . '/includes/class-settings.php');
include_once(MC_API_PLUGIN_DIR_PATH . '/includes/class-subscribe-shortcode.php');
include_once(MC_API_PLUGIN_DIR_PATH . '/includes/class-rest-subscribe.php');
include_once(MC_API_PLUGIN_DIR_PATH . '/includes/class-admin-post-subscribe.php');

$mc_api = new MC_API();
Settings::init();
Shortcode::init();
Rest_Subscribe::init();
Admin_Post_Subscribe::init();

function mc_api_activate(){

}
 

function mc_api_deactivate(){
    
}