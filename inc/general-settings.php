<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if (!class_exists('Wp360_Subscription_General_Settings')) {
    class Wp360_Subscription_General_Settings {
        public function __construct() {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'wp360_subscription_general_settings_save'));
            add_action('admin_init', array($this, 'wp360_subscription_register_settings'));
        }
        public function add_admin_menu() {
            add_submenu_page(
                null,
                'Wp360 Subscription General Settings',
                'Wp360 Subscription General Settings',
                'manage_options',
                'wp360-subscription-general-settings',
                array($this, 'render_subscription_settings_page')
            );
        }
        public function render_subscription_settings_page(){
            echo Wp360_Subscription_tabs(); ?>
            <div class="wrap">
                <div class="wp360_sub_email_conf_con">
                    <form method="post" action="options.php">
                        <?php
                            wp_nonce_field('wp360_subscription_general_settings_nonce', 'wp360_subscription_general_settings_nonce');
                            settings_fields('wp360_subscription_general_settings');
                            do_settings_sections('wp360_subscription_general_settings');
                            $settings  = array( 'media_buttons' => false );
                            $content = get_option('wp360_subscription_general_settings');
                            $cancelOptions = '';
                            if(isset($content['wp360_subs_cancel_subscription_dropdown'])){
                                $cancelOptions = $content['wp360_subs_cancel_subscription_dropdown'];
                            }
                        ?>   
                        <input type="hidden" name="wp360_subscription_general_settings[enable]">                
                        <div class="wp360_sub_email_conf_con_inner">
                            <div class="col">
                                <h2>Subscription Cancellation Dropdown Options</h2>
                                <p>Enter values separated by comma for the dropdown.</p>
                                <textarea type="text" name="wp360_subs_cancel_subscription_dropdown" id="wp360_subs_cancel_subscription_dropdown"><?php echo $cancelOptions; ?></textarea>
                            </div>                            
                        </div>
                        <?php submit_button(); ?>
                    </form>
                </div>
            </div> <?php
        }
        public function wp360_subscription_general_settings_save() {
            if (isset($_POST['submit']) && current_user_can('manage_options')) {
                if (isset($_POST['wp360_subscription_general_settings_nonce']) && wp_verify_nonce($_POST['wp360_subscription_general_settings_nonce'], 'wp360_subscription_general_settings_nonce')) {

                    if (isset($_POST['wp360_subscription_general_settings'])) {
                        if(isset($_POST['wp360_subs_cancel_subscription_dropdown'])){

                            $_POST['wp360_subscription_general_settings']['wp360_subs_cancel_subscription_dropdown'] = $_POST['wp360_subs_cancel_subscription_dropdown'];                            
                        }
                        update_option('wp360_subscription_general_settings', $_POST['wp360_subscription_general_settings']);
                    }

                }
            }
        }
        public function wp360_subscription_register_settings() {
            register_setting('wp360_subscription_general_settings', 'wp360_subscription_general_settings');
        }
    }
    $wp360_subscription_general_settings = new Wp360_Subscription_General_Settings();
}