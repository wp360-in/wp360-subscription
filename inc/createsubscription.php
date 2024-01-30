<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
function register_custom_post_type() {
    register_post_type('subscription_wp360', array(
        'labels' => array(
            'name' => __('Subscription Entries'),
            'singular_name' => __('Subscription Entry'),
        ),
        'public' => false, // Set to true if you want to make it publicly accessible
        'show_ui' => true, // Set to false if you don't want it to be displayed in the admin UI
        'supports' => array('title', 'editor', 'custom-fields'), // Add necessary supports
        'capability_type' => 'post', // Adjust if needed, e.g., 'page' or a custom capability type
        'map_meta_cap' => true, // Enable capability mapping for fine-grained control
        'publicly_queryable' => false, // Set to true if you want it to be publicly queryable
        'exclude_from_search' => true, // Set to false if you want it to be included in search results
        'has_archive' => false, // Set to true if you want to enable archives
        'rewrite' => false, // Set to an array to enable custom rewrite rules
    ));
}
add_action('init', 'register_custom_post_type');


function check_subscription_type_on_order_completion($order_id) {
    $order = wc_get_order($order_id);
    if ($order) {
        $subscription_products = false;
        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            $subscription_is =  get_post_meta($product_id, '_wp360_subscription_product', true);
            error_log( "subscription_is " . $subscription_is );
            if (get_post_meta($product_id, '_wp360_subscription_product', true) === 'yes') {
                error_log('6666');
                $subscription_products = true;
                break;
            }
        }
        if ($subscription_products) {
            create_subscription_entry($order_id);
        }
    }
}
add_action('woocommerce_order_status_processing', 'check_subscription_type_on_order_completion');
function create_subscription_entry($order_id) {
    $order = wc_get_order($order_id);
    $order_date = $order->get_date_created()->format('Y-m-d H:i:s');
    $user_id = $order->get_user_id(); // Get user ID
    $product_details = array();
    foreach ($order->get_items() as $item_id => $item) {
        $product_id = $item->get_product_id();
        $product = wc_get_product($product_id); // Get the product object
        // Get the price and time duration
        $price = $product->get_price();
        $time_duration = get_post_meta($product_id, '_wp360_selected_option', true);
        $product_details[] = array(
            'product_id'   => $product_id,
            'quantity'     => $item->get_quantity(),
            'price'        => $price,
            'time_duration' => $time_duration,
        );
    }
    $post_args = array(
        'post_title'   => 'Order #' . $order_id,
        'post_content' => '',
        'post_status'  => 'publish',
        'post_type'    => 'subscription_wp360', // Replace with your custom post type
        'meta_input'   => array(
            '_order_id'        => $order_id,
            '_order_date'      => $order_date,
            '_time_duration'   => $time_duration,
            '_user_id'         => $user_id,
            '_product_details' => $product_details,
        ),
    );
    $post_id = wp_insert_post($post_args);
}
