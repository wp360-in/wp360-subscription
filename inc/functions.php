<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
if (!class_exists('Wp360_Subscription')) {
    class Wp360_Subscription {
        public function __construct() {
            // Hook to add the admin menu
            add_action('admin_menu', array($this, 'add_admin_menu'));
        }
        public function add_admin_menu() {
            // Add a submenu item
            add_submenu_page(
                'wp360_menu',      // Parent menu slug
                'Subscription',    // Page title
                'Subscription',    // Menu title
                'manage_options',  // Capability required to access the submenu
                'wp360_subscription',   // Menu slug
                array($this, 'render_subscription_page') // Callback function to render the page
            );
        }
        public function render_subscription_page() {
            ?>
            <div class="wrap">
                <h1><?php _e( 'WP360 Subscriptions', 'wp360-subscription' ); ?></h1>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e( 'Subscription Id', 'wp360-subscription' ); ?></th>
                            <th><?php _e( 'Order id', 'wp360-subscription' ); ?></th>
                            <th><?php _e( 'Username', 'wp360-subscription' ); ?></th>
                            <th><?php _e( 'Plan', 'wp360-subscription' ); ?></th>
                            <th><?php _e( 'Time duration', 'wp360-subscription' ); ?></th>
                            <th><?php _e( 'Start Date', 'wp360-subscription' ); ?></th>
                            <th><?php _e( 'Status', 'wp360-subscription' ); ?></th>
                            <th><?php _e( 'Next Payment Date', 'wp360-subscription' ); ?></th>
                 
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $args = array(
                            'post_type'      => 'subscription_wp360',
                            'posts_per_page' => -1,
                            'post_status'    => 'publish',
                        );
                        $subscriptions = new WP_Query($args);
                        $default_date_format = get_option('date_format');
        
                        if ($subscriptions->have_posts()) :
                            while ($subscriptions->have_posts()) : $subscriptions->the_post();
                                $order_id       = get_post_meta(get_the_ID(), '_order_id', true);
                                $order_date     = get_post_meta(get_the_ID(), '_order_date', true);
                                $time_duration  = get_post_meta(get_the_ID(), '_time_duration', true);
                                $user_id        = get_post_meta(get_the_ID(), '_user_id', true);
                                $user_info      = get_userdata($user_id);
                                if ($user_info) {
                                    $user_display_name = $user_info->display_name;
                                }

                                $product_details = get_post_meta(get_the_ID(), '_product_details', true);
                                $productID =  $product_details[0]['product_id'];
                                $stripeSubscription = get_post_meta(get_the_ID(), '_wp360_subscription', true);
                                $nextPaymentDate = 'N/A';
                                $status = 'N/A';
                                $startDate = 'N/A';
                                if(isset($stripeSubscription->object->current_period_start)){
                                    $startDate = $stripeSubscription->object->current_period_start;
                                    $startDate = date($default_date_format, $startDate);
                                }
                                if(isset($stripeSubscription->object->current_period_end)){
                                    $nextPaymentDate = $stripeSubscription->object->current_period_end;
                                    $nextPaymentDate = date($default_date_format, $nextPaymentDate);
                                }
                                if(isset($stripeSubscription->object->status)){
                                    $status = '<span style="color:green;">'.htmlspecialchars($stripeSubscription->object->status).'</span>';
                                }
                                ?>
                                <tr>
                                    <td><?php echo get_the_ID(); ?></td>
                                    <td><?php echo esc_html($order_id); ?></td>
                                    <td><?php echo esc_html($user_display_name); ?></td>
                                    <td><?php echo esc_html(get_the_title($productID)); ?></td>
                                    <td><?php echo esc_html($time_duration); ?></td>
                                    <td><?php echo esc_html($startDate);?></td>
                                    <td><?php echo $status;?></td>
                                    <td><?php echo esc_html($nextPaymentDate);?></td>
                                </tr>
                                <?php
                            endwhile;
                            wp_reset_postdata();
                        else :
                            ?>
                            <tr>
                                <td colspan="6"><?php __('No subscriptions found', 'wp360-subscription')?></td>
                            </tr>
                            <?php
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>
            <?php
        }
        
    }
    // Instantiate the class
    $Wp360_Subscription = new Wp360_Subscription();
}

add_action( 'admin_enqueue_scripts', 'pluginAdminScriptwp_subscription');
function pluginAdminScriptwp_subscription(){   
    wp_enqueue_style('wp360-subscription'.'_admin_style', plugin_dir_url(__DIR__).'admin/assets/css/admin_style.css','',WP360SUBSCRIPTIONVER);
    wp_enqueue_script('jquery');
    wp_enqueue_script('wp360-subscription'.'_admin_js', plugin_dir_url(__DIR__).'admin/assets/js/admin_script.js','',WP360SUBSCRIPTIONVER);
    wp_localize_script('wp360-subscription'.'_admin_js', 'dynamicObjects', 
        array( 
            'adminAjax' => admin_url('admin-ajax.php'),
        )
    );
}

//MY account Dashboard
// Add a new tab to My Account page
function wp360_subscription_tab( $menu_links  ) {
    $menu_links = array_slice( $menu_links, 0, 2, true ) +
    array( 'wp360_subscription' => 'Subscription' ) +
    array_slice( $menu_links, 2, null, true );
    return $menu_links;
}
add_filter( 'woocommerce_account_menu_items', 'wp360_subscription_tab', 20 );

function wp360_subscription_endpoint() {
    add_rewrite_endpoint( 'wp360_subscription', EP_ROOT | EP_PAGES );
}
add_action( 'init', 'wp360_subscription_endpoint' );

function wp360_subscription_detail_endpoint() {
    add_rewrite_endpoint( 'wp360_subscription_detail', EP_ROOT | EP_PAGES );
}
add_action( 'init', 'wp360_subscription_detail_endpoint' );


// Display content on the custom endpoint
function wp360_subscription_content() {
    $current_user_id = get_current_user_id();
    $args = array(
        'post_type'      => 'subscription_wp360',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => array(
            array(
                'key'   => '_user_id',
                'value' => $current_user_id,
            ),
        ),
    );
    $subscriptions = new WP_Query($args);
    do_action('wp360_before_subscription_details','customerID', 'membershipID');
    // Display subscription details
    if ($subscriptions->have_posts()) :
        echo '<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">';
        echo '<thead><tr>
            <th>' . __('Subscription ID', 'wp360-subscription') . '</th>
            <th>' . __('Plan', 'wp360-subscription') . '</th>
            <th>' . __('Order Date', 'wp360-subscription') . '</th>
            <th>' . __('Next renew date', 'wp360-subscription') . '</th>
            <th>' . __('Status', 'wp360-subscription') . '</th>
            <th style="display:none;">' . __('View', 'wp360-subscription') . '</th>
        </tr>
        </thead>';
        echo '<tbody>';
        while ($subscriptions->have_posts()) : $subscriptions->the_post();
            $wp_date_format = get_option('date_format');
            $order_id       = get_post_meta(get_the_ID(), '_order_id', true);            
            $order          = wc_get_order($order_id);
            $order_date     = $order->get_date_created();
            $order_date     = $order_date->date_i18n($wp_date_format);           
            $time_duration          = get_post_meta(get_the_ID(), '_time_duration', true);
            $user_id                = get_post_meta(get_the_ID(), '_user_id', true);
            $productDetail          = get_post_meta(get_the_ID(), '_product_details', true);
            $productName            = get_the_title($productDetail[0]['product_id']);


            $stripeSubscription = get_post_meta(get_the_ID(), '_wp360_subscription', true);
            $nextPaymentDate = 'N/A';
            $status = 'N/A';
            $startDate = 'N/A';            
            if(isset($stripeSubscription->object->current_period_end)){
                $nextPaymentDate = $stripeSubscription->object->current_period_end;
                $nextPaymentDate = date($wp_date_format, $nextPaymentDate);
            }
            if(isset($stripeSubscription->object->status)){
                $status = '<span style="color:green;">'.htmlspecialchars($stripeSubscription->object->status).'</span>';
            }
            ?>
            <tr>
                <td>#<?php echo esc_html(get_the_ID()); ?></td>
                <td><?php  echo $productName; ?></td>
                <td><?php  echo esc_html($order_date); ?></td>
                <td><?php  echo esc_html($nextPaymentDate); ?></td>
                <td><?php  echo $status; ?></td>
                <td style="display:none;"><a href="<?php echo get_permalink( get_option('woocommerce_myaccount_page_id') ); ?>/wp360_subscription_detail?subscription_id=<?php echo get_the_ID();?>"><?php  _e('View', 'wp360-subscription')?></a></td>
            </tr>
            <?php
        endwhile;
        echo '</tbody>';
        echo '</table>';
        wp_reset_postdata();

    else :
        echo '<p>'.__('No subscriptions found.','wp360-subscription').'</p>';
    endif;

    do_action('wp360_after_subscription_details','customerID', 'membershipID');

}
add_action( 'woocommerce_account_wp360_subscription_endpoint', 'wp360_subscription_content' );



add_action( 'woocommerce_account_wp360_subscription_detail_endpoint', 'wp360_subscription_detail_content' );

function wp360_subscription_detail_content(){
    if(isset($_GET['subscription_id'])){
        $subscriptionID = $_GET['subscription_id'];
        $order_id       = esc_html(get_post_meta($subscriptionID, '_order_id', true));
        $order_date     = esc_html(get_post_meta($subscriptionID, '_order_date', true));
        $time_duration  = esc_html(get_post_meta($subscriptionID, '_time_duration', true));
        $user_id        = esc_html(get_post_meta($subscriptionID, '_user_id', true));
        $billing_email  = esc_html(get_post_meta($order_id, '_billing_email', true));
        $order          = wc_get_order($order_id);

        if ($order) {
            $amount = $order->get_total(); 
        }
        $payment_method_title   = esc_html($order->get_payment_method_title());
        $payment_date           = esc_html($order->get_date_paid());
        $formatted_payment_date = $payment_date ? $payment_date->date_i18n('F j, Y @ g:i a') : '';
        $customer_ip            = esc_html($order->get_customer_ip_address());
        $payment_status         = esc_html($order->get_status());



        echo '<div class="woocommerce">
            <div class="woocommerce-order">
                <ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">
                    <li class="woocommerce-order-overview__order order">'.__('Order number:','wp360-subscription').' <strong>'.$order_id.'</strong></li>
                    <li class="woocommerce-order-overview__email email">'.__('Email:','wp360-subscription').' <strong>'.$billing_email.'</strong></li>
                    <li class="woocommerce-order-overview__email email"> '.__('Paid On:','wp360-subscription').'<strong>'.$formatted_payment_date.'</strong></li>
                    <li class="woocommerce-order-overview__total total">
                        Total:
                        <strong>
                           '.$amount.'
                        </strong>
                    </li>
                    <li class="woocommerce-order-overview__payment-method method"> '.__('Payment method','wp360-subscription').'<strong>'.$payment_method_title.'</strong></li>
                    <li class="woocommerce-order-overview__payment-method method"> '.__('Payment status','wp360-subscription').'<strong>'.$payment_status.'</strong></li>
                </ul>
            </div>
        </div>';

        $renewpaymentHistory = get_post_meta($subscriptionID,'_wp360_subscription_data',true);
        if( $renewpaymentHistory  && is_array($renewpaymentHistory)){
            foreach($renewpaymentHistory as $rekeys=>$revals){
                foreach($revals->data->object->lines->data as $paykey=>$paymentvals){

                    $createdDate    =  esc_html($paymentvals->plan->created);
                    $currency       =  esc_html($paymentvals->currency);
                    $amount         =  esc_html($currency.' '.$paymentvals->amount);
                    $timeFrame      =  esc_html($paymentvals->period);
                    $interval       =  esc_html($paymentvals->plan->interval);
                    $paidStatus     =  esc_html(($paymentvals->status)) ? 'completed' : 'failed';

                    // echo '<div class="woocommerce">
                    //     <div class="woocommerce-order">
                          
                    //         <ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">
                    //             <li class="woocommerce-order-overview__order order">'.__('Plan:','wp360-subscription').' <strong>'.$paymentvals->description.'</strong></li>
                    //             <li class="woocommerce-order-overview__order order">'.__('Interval:','wp360-subscription').' <strong>'.$interval.'</strong></li>
                    //             <li class="woocommerce-order-overview__email email"> '.__('Paid On:','wp360-subscription').'<strong>'.dateformattedTimestamp($createdDate).'</strong></li>
                    //             <li class="woocommerce-order-overview__email email"> '.__('Start Date:','wp360-subscription').'<strong>'.dateformattedTimestamp($timeFrame->start).'</strong></li>
                    //             <li class="woocommerce-order-overview__email email"> '.__('End Date:','wp360-subscription').'<strong>'.dateformattedTimestamp($timeFrame->end).'</strong></li>
                    //             <li class="woocommerce-order-overview__total total">
                    //                 Total:
                    //                 <strong>
                    //                 '.$amount.'
                    //                 </strong>
                    //             </li>
                    //             <li class="woocommerce-order-overview__email email"> '.__('Payment status','wp360-subscription').'<strong>'.$paidStaus.'</strong></li>
                    //         </ul>
                    //     </div>
                    // </div>';
                }
            }
        }
    }
}


function togetformatedDate($order_id){
    $wp_date_format = get_option('date_format');     
    $order          = wc_get_order($order_id);
    $order_date     = $order->get_date_created();
    $order_date     = $order_date->date_i18n($wp_date_format);
    return $order_date;
}
function dateformattedTimestamp($createdDate){
    $wp_date_format = get_option('date_format');
    $formatted_date = date_i18n($wp_date_format, $createdDate);
    return $formatted_date;
}

// add_action('init', 'myStartSession', 1);
// add_action('wp_login', 'myEndSession');

// function myStartSession() {
//     if(!session_id() && isset($_GET['login_tmp'])) {
//         session_start();
//     }
//     if(!is_user_logged_in() && isset($_GET['login_tmp'])){
//         $user = get_user_by('login', 'careyconnections');
//         $user_id = $user->ID;
//         wp_set_current_user($user_id);
//         wp_set_auth_cookie($user_id);
//         do_action('wp_login', $user_login);
//     }
// }

