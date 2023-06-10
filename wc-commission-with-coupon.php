<?php
/**
 * Plugin Name: WooCommerce Coupon Commission
 * Description: Create a new coupon during order placed
 * Author: Think To Share
 * Text Domain: wc-commission
 * Version: 1.2.1
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin path.
define( 'WC_COMMISSION_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// Define plugin Version
define( 'WC_COMMISSION_PLUGIN_VERSION', '1.2.1' );

/**
 * Check if WooCommerce is active.
 *
 * @return bool true if WooCommerce is active, false otherwise
 */
function wc_commission_check_woocommerce()
{
    include_once ABSPATH . 'wp-admin/includes/plugin.php';

    return is_plugin_active( 'woocommerce/woocommerce.php' );
}

/**
 * Create the commissions table.
 */
function wc_commission_create_commissions_table()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name      = $wpdb->prefix . 'commissions';

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        commission_amount NUMERIC(10, 2) NOT NULL DEFAULT 0,
        status VARCHAR(20) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

/**
 * Create the Referral table.
 */
function wc_commission_create_referrals_table()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name      = $wpdb->prefix . 'commissions_referrals';

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        referral_code TEXT NOT NULL,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        product_ids TEXT NOT NULL,
        expiration_date DATE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

/**
 * Run when the plugin is activated.
 */
function wc_commission_activate()
{
    if ( wc_commission_check_woocommerce() ) {
        wc_commission_create_commissions_table();
        wc_commission_create_referrals_table();
    } else {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( 'WC Commission requires WooCommerce to be installed and active.' );
    }
}

register_activation_hook( __FILE__, 'wc_commission_activate' );

/**
 * Enqueue JavaScript files
 */
function wc_commission_init()
{
    wp_enqueue_script( 'js-cookie', plugin_dir_url( __FILE__ ) . 'assets/js/js.cookie.min.js', [], '3.0.5', true );
    wp_enqueue_script( 'wc_commission_set_cookie', plugin_dir_url( __FILE__ ) . 'assets/js/set_cookie.js', [], WC_COMMISSION_PLUGIN_VERSION, true );
}

add_action( 'init', 'wc_commission_init' );

/**
 * Create the coupon & apply commission when order status changes to completed.
 *
 * @param int    $order_id   the order ID
 * @param string $old_status the old order status
 * @param string $new_status the new order status
 */
function wc_commission_order_status_changed( $order_id, $old_status, $new_status )
{
    if ( 'completed' === $new_status ) {
        $order = wc_get_order( $order_id );

        // Validate order object.
        if ( !$order && !is_a( $order, 'WC_Order' ) ) {
            return;
        }

        // Trigger wc_commission_create_referral_code filter.
        apply_filters( 'wc_commission_create_referral_code', $order );

        // Check and apply commission.
        if ( $commission_user = apply_filters( 'wc_commission_check_commission', $order ) ) {
            // Trigger wc_commission_apply_commission filter.
            apply_filters( 'wc_commission_apply_commission', $order, $commission_user );
        }
    }
}

add_action( 'woocommerce_order_status_changed', 'wc_commission_order_status_changed', 10, 3 );

/**
 * Check commission eligibility for the given WooCommerce Order.
 *
 * @param WC_Order $order the WooCommerce order object
 *
 * @return mixed the user ID for the commission or false if no matching meta key is found
 */
function wc_commission_check_commission( $order )
{
    $metas = $order->get_meta_data();

    foreach ( $metas as $meta ) {
        if ( $meta->key == 'referral_added' ) {
            return $meta->value;
        }
    }

    return false;
}

add_filter( 'wc_commission_check_commission', 'wc_commission_check_commission', 10, 1 );

/**
 * Create referral code for given WooCommerce Order.
 *
 * @param WC_Order $order the WooCommerce order object
 */
function wc_commission_create_referral_code( $order )
{
    global $wpdb;
    // Access order object.
    $referral_code  = 'REFF-' . strtoupper( wp_generate_password( 8, false ) );
    $product_ids    = [];
    $referral_links = [];

    foreach ( $order->get_items() as $item ) {
        $product_ids[]    = $item->get_product_id();
        $referral_links[] = [
            'ref_link'     => $item->get_product()->get_permalink() . '?ref=' . $referral_code,
            'product_name' => $item->get_product()->get_title(),
        ];
    }

    $table_name = $wpdb->prefix . 'commissions_referrals';
    $wpdb->insert(
        $table_name,
        [
            'referral_code'   => $referral_code,
            'user_id'         => $order->get_user_id(),
            'product_ids'     => implode( ',', $product_ids ),
            'expiration_date' => date( 'Y-m-d', strtotime( '+1 month' ) ),
        ],
        ['%s', '%d', '%s', '%s']
    );

    do_action( 'woocommerce_commission_referral_code_created_notification', $order->get_id(), $order, $referral_links );
}

add_filter( 'wc_commission_create_referral_code', 'wc_commission_create_referral_code', 10, 1 );

/**
 * Apply commission for the given WooCommerce Order.
 *
 * @param WC_Order $order       the WooCommerce order object
 * @param int      $referral_id the referral ID to apply the commission to
 */
function wc_commission_apply_commission( $order, $referral_id = null )
{
    global $wpdb;
    $table_name  = $wpdb->prefix . 'commissions_referrals';
    $total       = 0;
    $product_ids = [];
    $matched     = false;

    $referal_check_record = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $referral_id
        )
    );

    $referral_product_ids = explode( ',', $referal_check_record->product_ids );

    foreach ( $order->get_items() as $item ) {
        if ( in_array( $item->get_product_id(), $referral_product_ids ) ) {
            $total += $order->get_total();
        }
    }

    if ( $total > 0 ) {
        $commission = ( $total * 10 ) / 100;
        $table_name = $wpdb->prefix . 'commissions';

        // Check if the user has existing commissions.
        $commission_record = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $table_name WHERE user_id = %d", $referal_check_record->user_id )
        );

        // Update the existing commission or insert a new one.
        if ( $commission_record ) {
            $new_commission = $commission_record->commission_amount + $commission;

            $wpdb->update(
                $table_name,
                [
                    'commission_amount' => $new_commission,
                ],
                [
                    'user_id' => $referal_check_record->user_id,
                ],
                ['%f'],
                ['%d']
            );
        } else {
            $wpdb->insert(
                $table_name,
                [
                    'user_id'           => $referal_check_record->user_id,
                    'commission_amount' => $commission,
                ],
                ['%d', '%f']
            );
        }
    }
}

add_filter( 'wc_commission_apply_commission', 'wc_commission_apply_commission', 10, 2 );

/**
 * Unset commission cookie after order_complete
 */
function wc_commission_unset_commission_cookie()
{
    if ( !isset( $_COOKIE['wc_commission_ref_code'] ) ) {
        return;
    }

    unset( $_COOKIE['wc_commission_ref_code'] );
    setcookie( 'wc_commission_ref_code', '', time() - 3600, '/' );
}

add_action( 'woocommerce_thankyou', 'wc_commission_unset_commission_cookie' );

/**
 * Load WooCommerce addon email classes.
 *
 * @param array $email_classes the existing WooCommerce email classes
 *
 * @return array the updated WooCommerce email classes including our custom email class
 */
function wc_commission_woocommerce_email_classes( $email_classes )
{
    require_once WC_COMMISSION_PLUGIN_PATH . 'includes/emails/class-wc-email-commission-referral-code-send.php';
    $email_classes['WC_Email_Commission_Referral_Code_Send'] = new WC_Email_Commission_Referral_Code_Send();

    return $email_classes;
}

add_filter( 'woocommerce_email_classes', 'wc_commission_woocommerce_email_classes' );

/**
 * Process referral commission on order creation.
 *
 * @param WC_Order $order the WooCommerce order object
 */
function wc_commission_process_referral_commission_on_order( $order )
{
    global $wpdb;
    $current_user_id = get_current_user_id();
    $table_name      = $wpdb->prefix . 'commissions_referrals';

    if ( ! isset( $_COOKIE['wc_commission_ref_code'] ) ) {
        return;
    }

    $refCode = $_COOKIE['wc_commission_ref_code'];
    $result  = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE referral_code = %s",
            $refCode
        )
    );

    if ( ! $result ) {
        return;
    }

    $order_product_ids = [];

    foreach ( $order->get_items() as $item ) {
        $order_product_ids[] = $item->get_product_id();
    }
    $referral_product_ids = explode( ',', $result->product_ids );
    $common_elements      = array_intersect( $referral_product_ids, $order_product_ids );

    if ( empty( $common_elements ) ) {
        return;
    }

    if ( $result->user_id == $current_user_id ) {
        return;
    }

    $order->update_meta_data( 'referral_added', $result->id );
    $order->save();
}

add_action( 'woocommerce_checkout_order_created', 'wc_commission_process_referral_commission_on_order', 10, 1 );

// Include extra files.
require_once WC_COMMISSION_PLUGIN_PATH . 'includes/withdrawal.php';
