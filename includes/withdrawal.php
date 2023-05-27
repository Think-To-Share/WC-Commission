<?php
function wc_create_withdrawal_post_type() {
    $args = array(
        'labels' => array(
            'name' => __( 'Withdrawal' ),
            'singular_name' => __( 'Withdrawal' ),
        ),
        'public' => false,
        'show_ui' => true,
        'has_archive' => true,
        'supports' => [
            'title',
        ],
    );
     
    register_post_type( 'Withdrawal', $args);
}

add_action( 'init', 'wc_create_withdrawal_post_type' );

function wc_commission_add_withdrawal_endpoint() {
    add_rewrite_endpoint( 'withdrawal', EP_ROOT | EP_PAGES );
}
   
add_action( 'init', 'wc_commission_add_withdrawal_endpoint' );

function wc_commission_custom_endpoint_account_menu_item( $items ) {
    $logout = $items['customer-logout'];
    unset($items['customer-logout']);
    $items['withdrawal'] = __('Withdrawal', 'wc-commission');
    $items['customer-logout'] = $logout;

    return $items;
}

add_filter( 'woocommerce_account_menu_items', 'wc_commission_custom_endpoint_account_menu_item', 10, 1 );
   
function wc_commission_withdrawal_content() {
    my_custom_withdrawal_form();
}

add_action( 'woocommerce_account_withdrawal_endpoint', 'wc_commission_withdrawal_content' );

function my_custom_withdrawal_form() {
    // global $wpdb;
    // $user_id = get_current_user_id();
    // $commission_amount = $wpdb->get_var( $wpdb->prepare( "
    //     SELECT commission_amount
    //     FROM wp_commissions
    //     WHERE user_id = %d", $user_id ) );
    
    // include WC_COMMISSION_PLUGIN_PATH.'/templates/pages/withdrawal.php';
}
