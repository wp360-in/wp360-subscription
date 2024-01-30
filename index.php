<?php
//Plugin Name: Wp360 SUBSCRIPTION 
//Description: The plugin is a SUBSCRIPTION addon for wp360 suite. Which enable one time(standard) and recurring(subscription) payment feature within wp360 suite
//Author: wp360
//Author URI: https://wp360.in/
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
define( 'WP360SUBSCRIPTIONNAME', 'SUBSCRIPTION' );
define( 'WP360SUBSCRIPTIONSLUG', 'wp360_SUBSCRIPTION' );
define( 'WP360SUBSCRIPTIONFOLDER', 'wp360-SUBSCRIPTION' );
define( 'WP360SUBSCRIPTIONVER', time() );
require_once('suite/index.php');
require_once('inc/functions.php');
require_once('inc/productmeta.php');
require_once('inc/createsubscription.php');

