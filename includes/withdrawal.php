<?php

function wc_commission_create_withdrawal_post_type() {
    $args = array(
        'labels' => array(
            'name' => __( 'Withdrawal', 'wc-commission' ),
            'singular_name' => __( 'Withdrawal', 'wc-commission' ),
        ),
        'public' => false,
        'show_ui' => true,
        'has_archive' => true,
        'supports' => [
            'title',
        ],
    );
     
    register_post_type( 'withdrawal', $args);
}

add_action( 'init', 'wc_commission_create_withdrawal_post_type' );

function wc_commission_withdrawal_column_headers($columns) {
    $columns['status'] = __( 'Status', 'wc-commission' );
    return $columns;
}

add_filter('manage_withdrawal_posts_columns', 'wc_commission_withdrawal_column_headers');

function wc_commission_withdrawal_custom_column_data($column_name, $post_id) {
    switch ($column_name) {
        case 'status':
            echo get_field('status', $post_id)
            break;
    }
}

add_action('manage_withdrawal_posts_custom_column', 'wc_commission_withdrawal_custom_column_data', 10, 2);

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
    global $wpdb;
    $user_id = get_current_user_id();
    $commission_amount = $wpdb->get_var( $wpdb->prepare( "
        SELECT commission_amount
        FROM wp_commissions
        WHERE user_id = %d", $user_id ) );

    wc_get_template('pages/withdrawal.php', [
        'commission_amount' => $commission_amount,
        'user_id' => $user_id,
    ], '', WC_COMMISSION_PLUGIN_PATH.'/templates/');
}

add_action( 'woocommerce_account_withdrawal_endpoint', 'wc_commission_withdrawal_content' );

function wc_commission_withdrawal_form_submit() {
    if ( ! isset( $_POST['withdrawal_form_nonce'] ) || ! wp_verify_nonce( $_POST['withdrawal_form_nonce'], 'withdrawal_form_action' ) ) {
        return;
    }

    if ( ! isset( $_POST['withdrawal_amount'] ) ) {
        return;
    }

    $withdrawal_amount = floatval( $_POST['withdrawal_amount'] );

    global $wpdb;
    $user_id = get_current_user_id();
    $commission_amount = $wpdb->get_var( $wpdb->prepare( "
        SELECT commission_amount
        FROM {$wpdb->prefix}commissions
        WHERE user_id = %d", $user_id ) );

    if ( $commission_amount <= 0 || $withdrawal_amount > $commission_amount) {
        wc_add_notice( __( 'You do not have sufficient money for withdrawal.', 'wc-commission' ), 'error' );
        return;
    }

    $withdrawal_post = array(
        'post_title'  => 'withdrawal' . $user_id,
        'post_status' => 'publish',
        'post_name'   => strtolower( 'withdrawal' . $user_id ),
        'post_type'   => 'withdrawal',
    );

    $withdrawal_id = wp_insert_post( $withdrawal_post );

    update_field( 'amount', $withdrawal_amount, $withdrawal_id );
    update_field( 'user_id', $user_id, $withdrawal_id );
    update_field( 'status', 'no action taken', $withdrawal_id );

    $commission_amount = $commission_amount - $withdrawal_amount;
    $table_name = $wpdb->prefix . 'commissions';
    $wpdb->update( $table_name, array( 'commission_amount' => $commission_amount ), array( 'user_id' => $user_id ) );

    wc_add_notice( __( 'Your withdrawal request has been submitted successfully.', 'wc-commission' ), 'success' );
}

add_action( 'template_redirect', 'wc_commission_withdrawal_form_submit' );
