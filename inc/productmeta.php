<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
function wps_sub_create_subscription_product_type($product_types) {
    global $post;
    $is_checkbox_checked = get_post_meta($post->ID, '_wp360_subscription_product', true);
    $default_value = $is_checkbox_checked ? 'yes' : '';
    $product_types['wp360_sub_product'] = array(
        'id'            => '_wp360_subscription_product',
        'wrapper_class' => 'show_if_simple',
        'label'         => __('WP360 Subscription', 'subscriptions-for-woocommerce'),
        'description'   => __('This is the Subscriptions type product.', 'subscriptions-for-woocommerce'),
        'default'       => $default_value,
    );
    return $product_types;
}
add_filter('product_type_options', 'wps_sub_create_subscription_product_type');



add_filter('woocommerce_product_data_tabs', 'wp360_sub_custom_product_tab_for_subscription');

function wp360_sub_custom_product_tab_for_subscription($tabs) {
    global $post;
    $is_checkbox_checked = get_post_meta($post->ID, '_wp360_subscription_product', true);
    $default_value = $is_checkbox_checked ? 'yes' : '';
    $show_class = $is_checkbox_checked ? 'show' : '';
    $tabs['wp360_sub_product'] = array(
        'label'    => __('WP360 Subscription Settings', 'subscriptions-for-woocommerce'),
        'target'   => 'wp360_sub_product_target_section',
        'class'    => implode(' ', array(apply_filters('wp360_sub_settings_tabs_class', ''), $show_class)),
        'priority' => 80,
    );
    return $tabs;
}


function wp360_sub_product_tab_content() {
    global $post;
    $selected_option        = get_post_meta($post->ID, '_wp360_selected_option', true);
    ?>
     <div id="wp360_sub_product_target_section" class="panel woocommerce_options_panel">
            <p class="form-field">
                <label for="wp360_selected_option"><?php esc_html_e('Select Option:', 'subscriptions-for-woocommerce'); ?></label>
                <select name="_wp360_selected_option" id="wp360_selected_option">
                    <option value="day" <?php selected($selected_option, 'day'); ?>>Daily</option>
                    <option value="week" <?php selected($selected_option, 'week'); ?>>Weekly</option>
                    <option value="month" <?php selected($selected_option, 'month'); ?>>Monthly</option>
                    <option value="quarter" <?php selected($selected_option, 'quarter'); ?>>Every 3 month</option>
                    <option value="semiannual" <?php selected($selected_option, 'semiannual'); ?>>Every six month</option>
                    <option value="year" <?php selected($selected_option, 'year'); ?>>Yearly</option>
                </select>
            </p>
      </div>
    <?php
}
add_action('woocommerce_product_data_panels', 'wp360_sub_product_tab_content');
function save_wp360_subscription_product_data($post_id) {
    if ('product' !== get_post_type($post_id)) {
        return;
    }
    $subscription_product_data = isset($_POST['_wp360_subscription_product']) ? 'yes' : '';
    update_post_meta($post_id, '_wp360_subscription_product', $subscription_product_data);

    if (isset($_POST['_wp360_selected_option'])) {
        update_post_meta($post_id, '_wp360_selected_option', sanitize_text_field($_POST['_wp360_selected_option']));
    }
}
add_action('save_post', 'save_wp360_subscription_product_data');

