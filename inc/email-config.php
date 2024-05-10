<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
function wp360_subscription_email_confi(){
    echo Wp360_Subscription_tabs();
    ?>
    <div class="wp360_sub_email_conf_con">
        <form method="post" action="options.php">
            <?php 
                // Nonce field
                wp_nonce_field('wp360_subscription_email_settings_nonce', 'wp360_subscription_email_settings_nonce');
                // Settings fields
                settings_fields('wp360_subscription_settings');
                // Render settings sections
                do_settings_sections('wp360_subscription_settings');
                // Get existing option values
                $settings  = array( 'media_buttons' => false );
                $content = get_option('wp360_subscription_settings');
                $confirmEmail = '';
                if(isset($content['wp360_subs_email_confirm'])){
                    $confirmEmail = $content['wp360_subs_email_confirm'];
                }
                $renewEmail = '';
                if(isset($content['wp360_subs_renew_email_confirm'])){
                    $renewEmail = $content['wp360_subs_renew_email_confirm'];
                }
                $cancelEmail = '';
                if(isset($content['wp360_subs_cancel_email_confirm'])){
                    $cancelEmail = $content['wp360_subs_cancel_email_confirm'];
                }
                $expireEmail = '';
                if(isset($content['wp360_subs_expire_email_confirm'])){
                    $expireEmail = $content['wp360_subs_expire_email_confirm'];
                }
            ?>
            <input type="hidden" name="wp360_subscription_settings[enable]">
            <!-- <table>
                <tr>
                    <th>Confirmation email for new subscription</th>
                    <td>
                        <@?php wp_editor($confirmEmail, 'wp360_subs_email_confirm', $settings);?>
                    </td>
                </tr>
                <tr>
                    <th>Subscription renew confirmation email</th>
                    <td><@?php wp_editor($renewEmail, 'wp360_subs_renew_email_confirm', $settings);?></td>
                </tr>
                <tr>
                    <th></th>
                    <td><@?php submit_button(); ?></td>
                </tr>
            </table> -->
            <div class="wp360_sub_email_conf_con_inner">
                <div class="col">
                    <h2>new subscription</h2>
                    <?php wp_editor($confirmEmail, 'wp360_subs_email_confirm', $settings);?>
                </div>
                <div class="col">
                    <h2>renew subscription</h2>
                    <?php wp_editor($renewEmail, 'wp360_subs_renew_email_confirm', $settings);?>
                </div>
                <div class="col">
                    <h2>cancel subscription</h2>
                    <?php wp_editor($cancelEmail, 'wp360_subs_cancel_email_confirm', $settings);?>
                </div>
                <div class="col">
                    <h2>subscription expiration</h2>
                    <?php wp_editor($expireEmail, 'wp360_subs_expire_email_confirm', $settings);?>
                </div>
            </div>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
add_action('admin_init', 'wp360_subscription_email_confi_save');
function wp360_subscription_email_confi_save() {
    // Check if the form is submitted and the user has the necessary permissions
    if (isset($_POST['submit']) && current_user_can('manage_options')) {
        // Verify nonce
        if (isset($_POST['wp360_subscription_email_settings_nonce']) && wp_verify_nonce($_POST['wp360_subscription_email_settings_nonce'], 'wp360_subscription_email_settings_nonce')) {
            // echo '<pre>', print_r($_POST), '</pre>';
            // die;
            // Sanitize the input and update the option in the database
            if (isset($_POST['wp360_subscription_settings'])) {
                if(isset($_POST['wp360_subs_email_confirm'])){
                    $_POST['wp360_subscription_settings']['wp360_subs_email_confirm'] = $_POST['wp360_subs_email_confirm'];
                }
                if(isset($_POST['wp360_subs_renew_email_confirm'])){
                    $_POST['wp360_subscription_settings']['wp360_subs_renew_email_confirm'] = $_POST['wp360_subs_renew_email_confirm'];
                }
                if(isset($_POST['wp360_subs_cancel_email_confirm'])){
                    $_POST['wp360_subscription_settings']['wp360_subs_cancel_email_confirm'] = $_POST['wp360_subs_cancel_email_confirm'];
                }
                if(isset($_POST['wp360_subs_expire_email_confirm'])){
                    $_POST['wp360_subscription_settings']['wp360_subs_expire_email_confirm'] = $_POST['wp360_subs_expire_email_confirm'];
                }
                //echo '<pre>', print_r($_POST); die;
                update_option('wp360_subscription_settings', $_POST['wp360_subscription_settings']);
            }
        }
    }
}
add_action('admin_init', 'wp360_subscription_register_settings');
function wp360_subscription_register_settings() {
    register_setting('wp360_subscription_settings', 'wp360_subscription_settings');
}
