<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if (!class_exists('Wp360_Subscription_Cancel')) {
    class Wp360_Subscription_Cancel {
        public function __construct() {
            add_action('wp_ajax_cancel_subscription_ajax', array($this, 'wp360_subscription_cancel_handler'));
        }
        public function wp360_subscription_cancel_ajaxify_script(){
            wp_localize_script('wp360-subscription-front-js', 'cancel_subscription_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
            ));
        }
        public function wp360_subscription_cancel_handler(){
            ob_start();
            wc_print_notice(esc_html__( 'Error cancelling subscription. Please try again or contact administrator.', 'text-domain' ), 'error');
            $msg = ob_get_clean();
            $response = array(
                'success' => false,
                'data' => array(
                    'message' => $msg
                )
            );
            if (isset($_POST['form_data'])) {
                parse_str($_POST['form_data'], $form_data);
                $subscription_id = isset($form_data['subscription_id']) ? absint($form_data['subscription_id']) : '';
                $admin_email = get_option('admin_email');
                $user_email = sanitize_email(wp_get_current_user()->user_email);
                $selectedOption = isset($form_data['unsubscribe_dropdown']) ? htmlspecialchars(trim($form_data['unsubscribe_dropdown'])) : '';
                $headers = 'From: ' . $user_email . "\r\n";
                $textareaValue = isset($form_data['unsubscribe_comments']) ? htmlspecialchars(trim($form_data['unsubscribe_comments'])) : '';
                $email_content = 'Cancellation requested for subscription ID: ' . $subscription_id . ' by user with email: ' . $user_email. "\r\n". 'Reason: '.$selectedOption."\r\n".'User Comments:'."\r\n".$textareaValue;
                $subject = 'Subscription Cancellation';                
                $update_result = update_post_meta($subscription_id, 'wp360_subscription_status', '_cancellation_requested');

                if($update_result !== false){
                    $send_email = wp_mail($admin_email, $subject, $email_content, $headers);
                    if ($send_email) {
                        ob_start();
                        wc_print_notice(esc_html__( 'Your request for cancellation has been submitted.', 'text-domain' ), 'notice');
                        $msg = ob_get_clean();
                        $response = array(
                            'success' => true,
                            'data' => array(
                                'message' => $msg
                            )
                        );
                    }
                }                                
            }
            echo json_encode($response); exit;
        }
    }
    $wp360_subscription_cancel = new Wp360_Subscription_Cancel();
}