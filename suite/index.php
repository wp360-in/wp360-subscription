<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
if (!class_exists('Wp360_Suite')) {
    class Wp360_Suite {
        public function __construct() {
            // Hook to add the admin menu
            add_action('admin_menu', array($this, 'add_admin_menu'));
        }
        public function add_admin_menu() {
            // Add the top-level menu item
            add_menu_page(
                'Wp360',           // Page title
                'Wp360',           // Menu title
                'manage_options',  // Capability required to access the menu
                'wp360_menu',      // Menu slug
                array($this, 'wp360_page'), // Callback function to display the page
                'dashicons-admin-generic', // Dashicon for the menu
                20                 // Position in the menu
            );           
            // You can add more submenus as needed
            // add_submenu_page(...);
        }
        public function wp360_page() {
            // Callback function for the top-level menu page
            echo '<div class="wrap"><h2>Wp360 Main Page</h2><p>Main content goes here.</p></div>';
        }
    }

    // Instantiate the class
    $wp360_suite = new Wp360_Suite();
}
