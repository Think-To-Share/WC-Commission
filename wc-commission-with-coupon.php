<?php
/**
 * Plugin Name: WooCommerce Coupon Commission
 * Description: Create new coupon during order placed
 * Author: Think To Share
 * Text Domain: wc-commission
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Version: 1.0.0
 */


// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin path.
define('WC_COMMISSION_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Check if WooCommerce is active.
function wc_commission_check_woocommerce() {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    return is_plugin_active('woocommerce/woocommerce.php');
}

/**
 * Function to create commissions table.
 */
function wc_commission_create_commissions_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'commissions';

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        amount NUMERIC(10, 2) NOT NULL DEFAULT 0,
        status VARCHAR(20) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Function to run when the plugin is activated.
 */
function wc_commission_activate() {
    if (wc_commission_check_woocommerce()) {
        wc_commission_create_commissions_table();
    } else {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('WC Commission requires WooCommerce to be installed and active.');
    }
}

register_activation_hook(__FILE__, 'wc_commission_activate');

/**
 * Create the coupon & apply commission when order status changes to completed.
 */
function wc_commission_order_status_changed($order_id, $old_status, $new_status) {
    if ($new_status === 'completed') {
        // Access order object
        $order = wc_get_order($order_id);

        // Trigger wc_commission_create_coupon filter
        apply_filters('wc_commission_create_coupon', $order);
        
        // Check and apply commission
        if($commission_user = apply_filters('wc_commission_check_commission', $order)) {
            // Trigger wc_commission_create_coupon filter
            apply_filters('wc_commission_apply_commission', $order, $commission_user);
        }
    }
}

add_action('woocommerce_order_status_changed', 'wc_commission_order_status_changed', 10, 3);

/**
 * Function to create a new coupon when an order is completed.
 */
function wc_commission_create_coupon( $order ) {
    // You can add your custom logic for generating a coupon code here.
    $coupon_code = 'COMM-' . strtoupper( wp_generate_password( 8, false ) );

    $user_id = $order->get_user_id();
    $line_items = $order->get_items();
    $product_ids = [];
    foreach ( $line_items as $line_item ) {
        $product_ids[] = $line_item->get_product_id();
    }
    
    $coupon = new WC_Coupon();
    $coupon->set_code($coupon_code);
    $coupon->set_discount_type('percent');
    $coupon->set_amount( 10 );
    $coupon->set_individual_use(true);
    $coupon->set_product_ids($product_ids) ;
    $coupon->set_usage_limit_per_user(1);
    $coupon->set_date_expires( strtotime('+1 month') );
    $coupon_id = $coupon->save();
    
    update_post_meta( $coupon_id, 'commission_eligible', $user_id);

    do_action( 'woocommerce_commission_coupon_created_notification', $order->get_id(), $order, $coupon );

    return $coupon_code;
}

add_filter( 'wc_commission_create_coupon', 'wc_commission_create_coupon', 10, 1 );

/**
 * Check commission eligibility for the given WooCommerce Order
 */
function wc_commission_check_commission( $order ) {
    $applied_coupons = $order->get_coupon_codes();
    if(! count($applied_coupons)) {
        return;
    }

    $coupon = new WC_Coupon($applied_coupons[0]);
    $metas = $coupon->get_meta_data();
    foreach($metas as $meta) {
        if($meta->key === 'commission_eligible') {
            return $meta->value;
        }
    }

    return false;
}

add_filter( 'wc_commission_check_commission', 'wc_commission_check_commission', 10, 1 );

/**
 * Apply commission for the given WooCommerce Order
 */
function wc_commission_apply_commission( $order, $user_id ) {
    global $wpdb;

    // Calculate commission.
    $total = $order->get_total();
    $commission = $total * 10 / 100;

    // Prepare table name.
    $table_name = $wpdb->prefix . 'commissions';

    // Check if the user has existing commissions.
    $commission_record = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id)
    );

    // Update the existing commission or insert a new one.
    if ( $commission_record ) {
        $new_commission = $commission_record->commission_amount + $commission;

        $wpdb->update(
            $table_name,
            array(
                'commission_amount' => $new_commission,
            ),
            array(
                'user_id' => $user_id,
            ),
            array( '%f' ),
            array( '%d' )
        );
    } else {
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'commission_amount' => $commission,
            ),
            array( '%d', '%f' )
        );
    }
}

add_filter( 'wc_commission_apply_commission', 'wc_commission_apply_commission', 10, 2 );

// Including extra files
require_once(WC_COMMISSION_PLUGIN_PATH.'includes/withdrawal.php');
