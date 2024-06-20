<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if (!class_exists('Wp360_Subscription_License_Key')) {
    class Wp360_Subscription_License_Key {
        public function __construct() {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'wp360_subscription_license_key_save'));
            add_action('admin_init', array($this, 'wp360_subscription_register_settings'));
        }
        public function add_admin_menu() {
            add_submenu_page(
                null,
                'Wp360 Subscription License Key',
                'Wp360 Subscription License Key',
                'manage_options',
                'wp360-subscription-license-key',
                array($this, 'render_subscription_license_key_page')
            );
        }
        public function render_subscription_license_key_page(){
            echo Wp360_Subscription_tabs(); ?>
            <div class="wrap">
                <div class="wp360_sub_email_conf_con">
                    <form method="post" action="options.php" id="wp360_license_key_form">
                        <?php
                            wp_nonce_field('wp360_subscription_license_key_nonce', 'wp360_subscription_license_key_nonce');
                            settings_fields('wp360_subscription_license_key');
                            do_settings_sections('wp360_subscription_license_key');
                            $settings  = array( 'media_buttons' => false );
                            $content = get_option('wp360_subscription_license_key');
                            $wp360_license_key = isset($content['_wp360_subscription_license_key']) ? $content['_wp360_subscription_license_key'] : '';
                            $wp360_license_key_status = isset($content['_wp360_subscription_license_key_status']) ? $content['_wp360_subscription_license_key_status'] : '';
                        ?>   
                        <input type="hidden" name="wp360_subscription_license_key[enable]">                
                        <div class="wp360_sub_email_conf_con_inner">
                            <div class="col">
                                <h2><?php echo __('Enter a valid license key.', 'text-domain'); ?></h2>
                                <input type="password" name="_wp360_subscription_license_key" id="_wp360_subscription_license_key" value="<?php echo $wp360_license_key; ?>">
                                <input type="hidden" name="_wp360_subscription_license_key_status" value="<?php echo !empty($wp360_license_key_status) ? $wp360_license_key_status : ''; ?>">
                                <?php
                                    if(!empty($wp360_license_key)){
                                        switch($wp360_license_key_status){
                                            case 'active': $message = __('Your license key has been successfully validated.', 'text-domain');
                                            break;
                                            case 'expired': $message = __('Your license key has expired.', 'text-domain');
                                            break;
                                            default: $message = __('Your license key is invalid.', 'text-domain');
                                        }
                                        echo '<div class="wp360_message message_'.$wp360_license_key_status.'">'.$message.'</div>';
                                    }                                    
                                ?>                              
                            </div>                            
                        </div>
                         <?php submit_button( __('Save settings'), 'button button-primary wp360_license_update' ); ?>
                    </form>
                </div>
            </div> <?php
        }
        public function wp360_subscription_license_key_save() {
            if (isset($_POST['submit']) && current_user_can('manage_options')) {
                if (isset($_POST['wp360_subscription_license_key_nonce']) && wp_verify_nonce($_POST['wp360_subscription_license_key_nonce'], 'wp360_subscription_license_key_nonce')) {
        
                    if (isset($_POST['wp360_subscription_license_key'])) {
                        if (isset($_POST['_wp360_subscription_license_key'])) {
                            $_POST['wp360_subscription_license_key']['_wp360_subscription_license_key'] = sanitize_text_field($_POST['_wp360_subscription_license_key']);
                        }
                        if (isset($_POST['_wp360_subscription_license_key_status'])) {
                            $_POST['wp360_subscription_license_key']['_wp360_subscription_license_key_status'] = sanitize_text_field($_POST['_wp360_subscription_license_key_status']);
                        }
                        update_option('wp360_subscription_license_key', $_POST['wp360_subscription_license_key']);
                    }
                }
            }
        }
        public function wp360_subscription_register_settings() {
            register_setting('wp360_subscription_license_key', 'wp360_subscription_license_key');
        }
    }
    $wp360_subscription_license_key = new Wp360_Subscription_License_Key();
}