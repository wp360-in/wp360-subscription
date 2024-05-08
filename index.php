<?php
/*
  Plugin Name: Wp360 SUBSCRIPTION
  Description: The plugin is a SUBSCRIPTION addon for wp360 suite. Which enable one time(standard) and recurring(subscription) payment feature within wp360 suite
  Requires at least: WP 5.2.0
  Tested up to: WP 6.3
  Author: wp360
  Author URI: https://wp360.in/
  Version: 1.0.0
  Requires PHP: 7.3
  Tags: woocommerce
  Text Domain: wp360-subscription
  Domain Path: /languages
  WC requires at least: 5.2.0
  WC tested up to: 8.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
define( 'WP360SUBSCRIPTIONNAME', 'SUBSCRIPTION' );
define( 'WP360SUBSCRIPTIONSLUG', 'wp360_SUBSCRIPTION' );
define( 'WP360SUBSCRIPTIONFOLDER', 'wp360-SUBSCRIPTION' );
define( 'WP360SUBSCRIPTIONVER', time() );
define( 'WP360_SUBSCRIPTION_SLUG', 'wp360-subscription' );
require_once('suite/index.php');
require_once('inc/functions.php');
require_once('inc/productmeta.php');
require_once('inc/createsubscription.php');
require_once('wp360_update.php');
function wp360_subscriptions_plugin_version() {
    $plugin_data = get_plugin_data(plugin_dir_path(__FILE__) . 'index.php');
    return $plugin_data['Version'];
}
