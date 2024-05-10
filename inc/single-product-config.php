<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if (!class_exists('Wp360_Single_Product_Config')) {
    class Wp360_Single_Product_Config {
        public function __construct() {
            add_filter( 'woocommerce_quantity_input_args', array($this, 'hide_quantity'), 10, 2 );
            add_action( 'woocommerce_get_price_html', array($this, 'display_subscription_type'), 5 );
            add_filter('woocommerce_add_to_cart_validation', array($this, 'restrict_product_to_be_sold_alone'), 10, 3);
            add_filter( 'woocommerce_is_sold_individually', array($this, 'wp360_sold_individually_check'), 10, 3);
            add_action('woocommerce_before_calculate_totals', array($this, 'limit_subscription_product_quantity'), 10, 5);
            add_filter('woocommerce_cart_item_name', array($this, 'add_subscription_duration_to_product_name'), 10, 3);
        }
        
        public function hide_quantity($args, $product){
            if ( is_product() && get_post_meta(get_the_ID(), '_wp360_subscription_product', true) == 'yes') {
                $args['input_value'] = 1;
                $args['min_value'] = 1;
                $args['max_value'] = 1;
                $args['style'] = 'display: none;';
            }
            return $args;
        }

        public function display_subscription_type($price_html) {
            if ( is_product() && get_post_meta(get_the_ID(), '_wp360_subscription_product', true) == 'yes') {
                $subscription_type = '';
                if(!empty(get_post_meta(get_the_ID(), '_wp360_selected_option', true))){
                    $subscription_type = ' / '.get_post_meta(get_the_ID(), '_wp360_selected_option', true);
                }
                $price_html = $price_html.$subscription_type;
                return $price_html;
            }
        }
        public function restrict_product_to_be_sold_alone($passed, $product_id, $quantity){
            $product = wc_get_product($product_id);
            if ($product && get_post_meta($product_id, '_wp360_subscription_product', true) == 'yes') {
                $product_cart_id = WC()->cart->generate_cart_id( $product_id );
                $in_cart = WC()->cart->find_product_in_cart( $product_cart_id );
                if (!$in_cart && WC()->cart->get_cart_contents_count() > 0) {
                    wc_add_notice(__('This product can only be bought individually. Please empty your cart to purchase this product.', 'your-text-domain'), 'error');
                    $passed = false;
                }                
            }           
            else if($product && !get_post_meta($product_id, '_wp360_subscription_product', true)){
                foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                    $product = $cart_item['data'];
                    if ($product && $product->get_meta('_wp360_subscription_product') === 'yes') {
                        wc_add_notice(__('This product cannot be bought with '. $product->get_name().'. Please remove items from your cart.'), 'error');
                        $passed = false;
                    }
                }
            } 
            return $passed;
        }
        public function wp360_sold_individually_check($is_sold_individually, $product){
            if ($product && get_post_meta($product->get_id(), '_wp360_subscription_product', true) == 'yes') {
                $is_sold_individually = true;
            }
            return $is_sold_individually;
        }
        public function limit_subscription_product_quantity($cart) {   
            if ( is_admin() && ! defined( 'DOING_AJAX' ) )
                return;

            if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 )
                return;

            foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
                $product = $cart_item['data'];
                if ($product && $product->get_meta('_wp360_subscription_product') === 'yes') {
                    if (count(WC()->cart->get_cart()) === 1) {
                        $cart->set_quantity($cart_item_key, 1);
                    }
                    else {
                        $cart->remove_cart_item($cart_item_key);
                    }
                }
            }
        }
        public function add_subscription_duration_to_product_name($product_name, $cart_item, $cart_item_key) {
            $product = $cart_item['data'];
            $subscription_type = '';        
            if (get_post_meta($product->get_id(), '_wp360_subscription_product', true) === 'yes') {
                $subscription_option = get_post_meta($product->get_id(), '_wp360_selected_option', true);
                if (!empty($subscription_option)) {
                    $subscription_type = ' / ' . $subscription_option;
                }
                $product_name .= $subscription_type;
            }        
            return $product_name;
        }
    }
    $wp360_single_product_config = new Wp360_Single_Product_Config();
}
