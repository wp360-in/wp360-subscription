<?php
/**
 * Uninstall wp360 subscription.
 *
 * Remove:
 * - WP360  meta
 *
 * @since 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
// Delete post meta data
$wp360_subscription_post_ids = get_posts( array(
    'post_type' => 'subscription_wp360', 
    'fields'    => 'ids',
    'posts_per_page' => -1,
));
foreach ( $wp360_subscription_post_ids as $wp360_subscription_post_id ) {
    delete_post_meta( $wp360_subscription_post_id, '_wp360_subscription_product' );
    delete_post_meta( $wp360_subscription_post_id, '_wp360_selected_option' );
}
// Delete posts of a specific post type
$args = array(
	'post_type' => 'subscription_wp360',
	'posts_per_page' => -1,
);
$posts = get_posts( $args );
foreach ( $posts as $post ) {
    wp_delete_post( $post->ID, true ); 
}
