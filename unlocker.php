<?php
/**
Plugin name: Unlocker
Plugin URI: https://deblocage-telephone-mobile.com/
Description: Unlocker provides licence or code for unlocking selected models of mobile phone
Author: Kavya Sagar
Author URI: http://www.kavs.me
Version: 1.0
**/
// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}
if (! function_exists( 'json_decode' ) ) {
    esc_html_e('This plugin requires the JSON PHP extension');
    return false;
}
define( 'UNL__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'UNL__PLUGIN_INC', plugin_dir_path( __FILE__ ).'inc/' );
define( 'UNL__PLUGIN_ASSET', plugin_dir_url(__FILE__).'assets/');

require_once( UNL__PLUGIN_INC . 'class.unlocker-admin.php' );
require_once( UNL__PLUGIN_INC . 'class.unlocker-rest-api.php' );
require_once( UNL__PLUGIN_INC . 'class.unlocker-user-page.php' );

$ula = new Unlocker_Admin();

register_activation_hook( __FILE__, array( $ula, 'ula_plugin_activation' ) );
register_deactivation_hook( __FILE__, array( $ula, 'ula_plugin_deactivation' ) );
?>