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
                <h1>WP360 Subscriptions</h1>
        
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Order ID</th>
                            <th>Order Date</th>
                            <th>Time Duration</th>
                            <th>User ID</th>
                            <th>Product Details</th>
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
        
                        if ($subscriptions->have_posts()) :
                            while ($subscriptions->have_posts()) : $subscriptions->the_post();
                                $order_id       = get_post_meta(get_the_ID(), '_order_id', true);
                                $order_date     = get_post_meta(get_the_ID(), '_order_date', true);
                                $time_duration  = get_post_meta(get_the_ID(), '_time_duration', true);
                                $user_id        = get_post_meta(get_the_ID(), '_user_id', true);
                               // $product_details = get_post_meta(get_the_ID(), '_product_details', true);
        
                                ?>
                                <tr>
                                    <td><?php echo get_the_ID(); ?></td>
                                    <td><?php echo esc_html($order_id); ?></td>
                                    <td><?php echo esc_html($order_date); ?></td>
                                    <td><?php echo esc_html($time_duration); ?></td>
                                    <td><?php echo esc_html($user_id); ?></td>
                                    <td><?php// echo //esc_html(json_encode($product_details)); ?></td>
                                </tr>
                                <?php
                            endwhile;
                            wp_reset_postdata();
                        else :
                            ?>
                            <tr>
                                <td colspan="6">No subscriptions found</td>
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
    wp_enqueue_style(WP360SUBSCRIPTIONSLUG.'_admin_style', plugin_dir_url(__DIR__).'admin/assets/css/admin_style.css','',WP360SUBSCRIPTIONVER);
    wp_enqueue_script('jquery');
    wp_enqueue_script(WP360SUBSCRIPTIONSLUG.'_admin_js', plugin_dir_url(__DIR__).'admin/assets/js/admin_script.js','',WP360SUBSCRIPTIONVER);
    wp_localize_script(WP360SUBSCRIPTIONSLUG.'_admin_js', 'dynamicObjects', 
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
            <th>' . __('Subscription ID', WP360SUBSCRIPTIONSLUG) . '</th>
            <th>' . __('Plan', WP360SUBSCRIPTIONSLUG) . '</th>
            <th>' . __('Order Date', WP360SUBSCRIPTIONSLUG) . '</th>
            <th>' . __('Next renew date', WP360SUBSCRIPTIONSLUG) . '</th>
            <th>' . __('Status', WP360SUBSCRIPTIONSLUG) . '</th>
        </tr>
        </thead>';
        echo '<tbody>';
        
        while ($subscriptions->have_posts()) : $subscriptions->the_post();
            $wp_date_format = get_option('date_format');
            $order_id      = get_post_meta(get_the_ID(), '_order_id', true);            
            $order = wc_get_order($order_id);
            $order_date = $order->get_date_created();
            $order_date = $order_date->date_i18n($wp_date_format);
            $order_status = $order->get_status();
            if ($order_status == 'completed') {
                $order_status = 'Active';
            }
            $time_duration = get_post_meta(get_the_ID(), '_time_duration', true);
            $user_id       = get_post_meta(get_the_ID(), '_user_id', true);
            $productDetail       = get_post_meta(get_the_ID(), '_product_details', true);
            $productName = get_the_title($productDetail[0]['product_id']);
            $subscriptionData = get_post_meta(get_the_ID(), '_wp360_subscription_data', true);

            $event = $subscriptionData[0];
            $endDate = $event->data->object->lines->data[0]->period->end;
            $endDate = date($wp_date_format, $endDate);
            ?>
            <tr>
                <td>#<?php echo get_the_ID(); ?></td>
                <td><?php echo $productName; ?></td>
                <td><?php echo esc_html($order_date); ?></td>
                <td><?php echo esc_html($endDate); ?></td>
                <td><?php echo esc_html($order_status); ?></td>
            </tr>
            <?php
        
        endwhile;
        
        echo '</tbody>';
        echo '</table>';
        

        wp_reset_postdata();

    else :
        echo '<p>'.__('No subscriptions found.',WP360SUBSCRIPTIONSLUG).'</p>';
    endif;

    do_action('wp360_after_subscription_details','customerID', 'membershipID');

}
add_action( 'woocommerce_account_wp360_subscription_endpoint', 'wp360_subscription_content' );


