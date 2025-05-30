<?php
    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }

    if (!class_exists('Wp360_Updates')) {
        class Wp360_Updates {
            public function __construct() {
                add_action('wp_ajax_update_subscription_invoices_meta', array($this, 'updateSubscriptionMeta'));
                $this->checkForSubscriptionInvoices();
            }

            public function displayNotice(){
                $message = '<strong>WP360 Subscriptions: </strong>&nbsp; Database update required!';
                echo '<div class="notice notice-info wp360_update_invoice_db_notice" style="display: flex; flex-wrap: wrap;row-gap: 15px; column-gap: 20px; padding-block-start: 10px; padding-block-end: 10px;">
                    <p>'.__( $message, 'text-domain' ).'</p>
                    <button id="wp360_invoice_update" class="button-primary">'.esc_html__('Start Update', 'text-domain').'</button>
                </div>';
            }
            private function checkForSubscriptionInvoices(){
                $args = array(
                    'post_type' => 'subscription_wp360',
                    'posts_per_page' => 1,
                    'post_status' => 'publish',
                    'meta_query' => array(
                        // 'relation' => 'OR',
                        array(
                            'key'     => '_wp360_subscription_invoices',
                            'compare' => 'NOT EXISTS',
                        ),
                        // array(
                        //     'key'     => '_wp360_subscription_invoices',
                        //     'value'   => 'a:0:{}',
                        //     'compare' => '=',
                        // ),
                    ),
                );
                $query = new WP_Query($args);
                if($query->found_posts > 0){
                    add_action( 'admin_notices', array($this, 'displayNotice') );
                }
                wp_reset_postdata();
            }
            private function isWp360StripePluginActive() {
                if (!function_exists('is_plugin_active')) {
                    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
                }
                return is_plugin_active('wp360-stripe/wp360-stripe.php'); // Adjust the path as necessary
            }
            public function updateSubscriptionMeta() {
                if (!$this->isWp360StripePluginActive()) {
                    $response = esc_html__('Please enable WP360 Stripe plugin!', 'text-domain');
                    wp_send_json_error(array('messages' => $response));
                    return;
                }
                $paged = 1;
                if (isset($_POST['paged'])) {
                    $paged = intval($_POST['paged']);
                }
                // Initialize response variable
                $response = '';
            
                while (true) {
                    $subscription_ids = $this->getSubscriptions($paged);
                    if (empty($subscription_ids)) {
                        $response = esc_html__('Your subscriptions have been updated!', 'text-domain');
                        wp_send_json_success(array('paged' => $paged, 'messages' => $response, 'repeat' => false));
                        break;
                    } else {
                        $paged++;
                        $wp360_invoices = [];
                        $starting_after = null;
            
                        // Retrieve all invoices
                        do {
                            $invoices = $this->getStripeInvoices($starting_after);
                            if (!empty($invoices->data)) {
                                $wp360_invoices = array_merge($wp360_invoices, $invoices->data);
                                $starting_after = end($invoices->data)->id;
                            }
                        } while (!empty($invoices->data));

                        foreach ($subscription_ids as $sub_id) {
                            $subscriptionMeta = array();
                            foreach ($wp360_invoices as $invoice) {
                                $order_id = get_post_meta($sub_id, '_order_id', true);
                                if (!$order_id) {
                                    continue;
                                }
                                if (isset($invoice->subscription_details->metadata->order_id) && $invoice->subscription_details->metadata->order_id == $order_id) {
                                    $subscriptionMeta[] = $invoice;
                                }                                
                            }
                            try {
                                if(!empty($subscriptionMeta)){
                                    $updatePost = update_post_meta($sub_id, '_wp360_subscription_invoices', $subscriptionMeta);
                                }
                                else{
                                    $updatePost = update_post_meta($sub_id, '_wp360_subscription_invoices', null);
                                }
                                if ($updatePost !== false) {
                                    $response = esc_html__('Your subscriptions have been updated!', 'text-domain');
                                } else {
                                    $response = esc_html__('Error updating subscriptions!', 'text-domain');
                                    wp_send_json_error(array('paged' => $paged, 'messages' => $response, 'repeat' => false, 'invoices'=>$subscriptionMeta, 'ids'=>$sub_id));
                                }
                            } catch (Exception $e) {
                                $response = esc_html__('An error occurred while updating subscriptions: ', 'text-domain') . $e->getMessage();
                            }
                        }                        
                    }
                    wp_send_json_success(array('paged' => $paged, 'messages' => $response, 'repeat' => true, 'invoices'=>$subscriptionMeta));
                }
            }

            public function getSubscriptions($paged){
                $args = array(
                    'post_type' => 'subscription_wp360',
                    'posts_per_page' => 5,
                    'paged' => $paged,
                    'post_status' => 'publish',
                    'fields' => 'ids'
                );    
                $query = new WP_Query($args);
                $post_ids = array();
                if ($query->have_posts()) {
                    $post_ids = $query->posts;
                    wp_reset_postdata();
                }
                return $post_ids;
            }
            public function getStripeInvoices($offset) {
                try {                    
                    $stripe = new \Stripe\StripeClient(ciGetStripeSK());                    
                    if ($offset !== null) {
                        $invoices = $stripe->invoices->all(['limit' => 10, 'starting_after' => $offset]);
                    } else {
                        $invoices = $stripe->invoices->all(['limit' => 10]);
                    }
                    return $invoices;                    
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    $response = esc_html__('Error communicating with Stripe: ', 'text-domain') . $e->getMessage();
                    wp_send_json_error(array('messages' => $response));
                } catch (\Exception $e) {
                    $response = esc_html__('An unexpected error occurred: ', 'text-domain') . $e->getMessage();
                    wp_send_json_error(array('messages' => $response));
                }
            }
        }
        $wp360_updates = new Wp360_Updates();        
    }