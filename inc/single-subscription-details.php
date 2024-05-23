<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if (!class_exists('Wp360_Subscription_Details')) {
    class Wp360_Subscription_Details {
        public function __construct() {
            add_action( 'woocommerce_account_wp360_subscription_detail_endpoint', array($this, 'wp360_subscription_detail_content') );
            $this->$subsID = isset($_GET['subscription_id']) ? absint($_GET['subscription_id']) : '';
        }

        public function validateUser(){
            if(!empty($this->$subsID) && get_post_type($this->$subsID) === 'subscription_wp360'){
                $sub_order_id = get_post_meta($this->$subsID, '_order_id', true);
                if (current_user_can('administrator') || (get_post_meta($sub_order_id, '_customer_user', true) == get_current_user_id())) {
                    return true;
                }
            }
            return false;
        }
        public function wp360_subscription_detail_content() {
            if($this->validateUser()){
                $subscriptionID = $this->$subsID;
                $subscription_details = get_post_meta($subscriptionID, '_product_details', true);
                $stripeSubscription = get_post_meta($subscriptionID, '_wp360_subscription', true);
                $order_id = get_post_meta($subscriptionID, '_order_id', true);
                $order = wc_get_order($order_id);
                $order_date = $order->get_date_created();
                $user_id = get_post_meta($subscriptionID, '_user_id', true);
                $user_data = get_userdata( $user_id );
                // echo '<br>';
                // echo '<pre>'; print_r($stripeSubscription);
                
                echo '<div id="wp360_notices_wrapper"></div>';
                echo '<div class="subscription_detail_header">
                    <div class="detail_title"><h2>'.get_the_title($subscription_details[0]['product_id']).'<span class="status '.strtolower($stripeSubscription->object->status).'">'.esc_html__($stripeSubscription->object->status, 'text-domain').'</span></h2></div>';
                if($stripeSubscription->object->status === 'active' && !in_array(get_post_meta($subscriptionID, 'wp360_subscription_status', true), array('_cancellation_requested', 'canceled'))){
                    echo '<button type="button" class="subs_cancel">Cancel</button>';
                }
                echo '</div>';

                echo '<div id="sub_invoice_details" class="flex">
                    <div>'.esc_html__('Started', 'text-domain').'<span>'.esc_html__($order_date->date_i18n(get_option('date_format')), 'text-domain').'</span></div>';

                    $nextInvoiceDate = 'N/A';
                    if($stripeSubscription->object->status === 'active'){
                        $startDate = $stripeSubscription->object->current_period_start;
                        $startDate = date(get_option('date_format'), $startDate);
                        $endDate = $stripeSubscription->object->current_period_end;
                        $endDate = date(get_option('date_format'), $endDate);
                        echo '<div>'.esc_html__('Current Period', 'text-domain').'<span>'.esc_html__($startDate, 'text-domain').' - '.esc_html__($endDate, 'text-domain').'</span></div>';
                        if(isset($stripeSubscription->object->current_period_end)){
                            $nextInvoiceDate = date(get_option('date_format'), $stripeSubscription->object->current_period_end);
                        }
                    }
                    else{
                        if(isset($stripeSubscription->object->ended_at)){
                            $nextInvoiceDate = date(get_option('date_format'), $stripeSubscription->object->ended_at);
                        }                        
                    }
                    echo '<div>'.($stripeSubscription->object->status === 'active' ? esc_html__('Next invoice', 'text-domain') : esc_html__('Ended', 'text-domain')).'<span>'.$nextInvoiceDate.'</span></div>';
                echo '</div>';

                echo '<div class="subscription_details_block">
                    <div class="wp_sub_details_head">'.esc_html__('Subscription details', 'text-domain').'</div>
                    <div id="subscription_detail">
                        <span>'.esc_html__('Subscription ID', 'text-domain').'</span>
                        <span>#'.$subscriptionID.'</span>
                        <span>'.esc_html__('Customer', 'text-domain').'</span>
                        <span>'.$user_data->display_name.'</span>
                        <span>'.esc_html__('Duration', 'text-domain').'</span>
                        <span>'.ucfirst(esc_html__(get_post_meta($subscriptionID, '_time_duration', true), 'text-domain')).'</span>
                    </div>
                </div>';

                echo '<div class="invoice_details_block">
                    <div class="wp_sub_details_head">'.esc_html__('Invoices', 'text-domain').'</div>';
                    $wp360_invoices = get_post_meta($subscriptionID,'_wp360_subscription_invoices',true);
                    // echo '<pre>'; print_r(get_post_meta($subscriptionID,'_wp360_license_key',true));

                    // echo '<pre>'; print_r(get_post_meta($subscriptionID,'_order_id',true)); die;
                    if( !empty($wp360_invoices)){
                        // echo '<pre>'; print_r($wp360_invoices);
                        echo '<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive subcriptions_invoices_table">
                            <tbody>
                                <thead>
                                    <tr>
                                        <th>Amount</th>
                                        <th>Duration</th>
                                        <th>Payment Status</th>
                                    </tr>
                                </thead>';
                                foreach($wp360_invoices as $invoiceskeys=>$invoicesvals){
                                    // echo '<pre>'; print_r($invoicesvals);
                                    if($invoicesvals->status === 'draft' || $invoicesvals->status === 'paid'){
                                        $currency =  $invoicesvals->currency;
                                        $amount =  $currency.' '.$invoicesvals->amount_paid;
                                        $paidStatus = ($invoicesvals->status === 'paid') ? 'completed' : (($invoicesvals->status === 'draft') ? 'failed' : 'failed');
                                        echo '<tr>
                                            <td>'.strtoupper($amount).'</td>
                                            <td>'.date('M d, Y', $invoicesvals->lines->data[0]->period->start).' - '.date('M d, Y',  $invoicesvals->lines->data[0]->period->end).'</td>
                                            <td>'.ucwords($paidStatus).'</td>
                                        </tr>';
                                    } 
                                }                        
                        echo '</tbody></table>';
                    }
                    else{
                        wc_print_notice(esc_html__('No invoices generated.', 'text-domain'), 'notice');
                    }
                    
                echo '</div>';
                if($stripeSubscription->object->status === 'active'){
                    $this->cancel_subscription_modal(get_the_title($subscription_details[0]['product_id']), $subscriptionID);
                }
            }
            else{
                wc_print_notice( esc_html__( 'Invalid subcription ID.', 'text-domain' ), 'error' );
            }
        }
        public function cancel_subscription_modal($prdName, $subsID) {            
            $modalTitle = "Are you sure you want to cancel subcription for ".$prdName;
            echo '<div class="cancel_subscription_dialog">
                <div class="modal-overlay modal-toggle"></div>
                <div class="modal-wrapper modal-transition">
                <div class="wp360_modal_loader"><span class="wp360_loader"></span></div>
                <div class="modal-header">                    
                    <h3>'.esc_html__($modalTitle.' ?', 'text-domain').'</h3>
                </div>                
                <div class="modal-body">
                    <div class="modal-content">
                        <form id="cancel_subscription_form" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="subscription_id" value="'.$subsID.'">
                            <div class="row">';
                                $options = get_option('wp360_subscription_general_settings');
                                if(!empty($options['wp360_subs_cancel_subscription_dropdown'])){
                                    $opt = explode(',', $options['wp360_subs_cancel_subscription_dropdown']);
                                    echo '<select name="unsubscribe_dropdown">';
                                        foreach($opt as $op){
                                            echo '<option value="'.trim($op).'">'.esc_html__(trim($op), 'text-domain').'</option>';
                                        }
                                    echo '</select>';
                                }
                            echo '</div>
                            <div class="row">
                                <label for="unsubscribe_comments">'.esc_html__('Comments', 'text-domain').'</label>
                                <textarea name="unsubscribe_comments" placeholder="'.esc_html__('Tell us in brief', 'text-domain').'"></textarea>
                            </div>
                            <div class="flex modal_button_wrapper">
                                <button type="submit">'.esc_html__('Yes', 'text-domain').'</button>
                                <button type="button" class="modal-toggle">'.esc_html__('No', 'text-domain').'</button>
                            </div>                            
                        </form>
                    </div>
                </div>
                </div>
            </div>';
        }
    }
    $wp360_subscription_details = new Wp360_Subscription_Details();
}