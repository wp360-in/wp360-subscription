<?php

add_action('wp_ajax_wp360_cancel_subscription', 'wp360_cancel_subscription_handler');
function wp360_cancel_subscription_handler() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp360_cancel_subscription_nonce')) {
        wp_send_json_error('Invalid nonce.');
    }
    
    // Sanitize and validate input
    $subscriptionID = isset($_POST['subscriptionID']) ? sanitize_text_field($_POST['subscriptionID']) : '';
    $postID = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $cancelReason = isset($_POST['cancel_reason']) ? sanitize_text_field($_POST['cancel_reason']) : "Cancelled By admin";

    if (empty($subscriptionID)) {
        wp_send_json_error('Missing subscription ID.');
    }
    
    try {
        $stripe = new \Stripe\StripeClient(ciGetStripeSK());
        
        // First update the subscription with metadata and cancellation details
        $updatedSubscription = $stripe->subscriptions->update($subscriptionID, [
            'metadata' => [
                'Cancelled By' => 'admin',
                'Cancellation Date' => date('Y-m-d H:i:s'),
                'Reason' => $cancelReason
            ]
        ]);
        
        // Then cancel the subscription
        $canceled = $stripe->subscriptions->cancel($subscriptionID);
        
        if ($canceled && $canceled->status === 'canceled') {
            error_log('Cancelled Request: ' . print_r($canceled, true));
            $subscriptionData = [];
            $subscriptionData[$subscriptionID] = (array) $canceled;
            
            update_post_meta($postID, '_wp360_subscription_data', $subscriptionData);
            wp_send_json_success([
                'message' => 'Subscription canceled successfully.',
                'reason' => $cancelReason
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to cancel subscription.']);
        }
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log('Error cancelling subscription: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Stripe API error: ' . $e->getMessage()]);
    }
    wp_die();
}