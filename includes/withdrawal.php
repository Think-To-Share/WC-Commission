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
            echo get_field('status', $post_id);
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

    $withdrawal_amount = floatval( wc_clean( wp_unslash( $_POST['withdrawal_amount'] ) ) );

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

function wc_commission_edit_bank_details() {
    $user = wp_get_current_user();

    wc_get_template( 'pages/update-bank-details.php', [
        'user' => $user,
    ], '', WC_COMMISSION_PLUGIN_PATH.'/templates/' );
}

add_action( 'woocommerce_edit_account_form', 'wc_commission_edit_bank_details' );

function wc_commission_update_bank_details( $user_id ) {
    $has_error = false;

    if ( isset( $_POST['bank_account_name'] ) ) {
        $bank_account_name = sanitize_text_field( $_POST['bank_account_name'] );
        if ( empty( $bank_account_name ) ) {
            wc_add_notice( __( 'Please provide a valid account name.', 'wc-commission' ), 'error' );
        } else {
            update_user_meta( $user_id, 'bank_account_name', $bank_account_name );
        }
    }

    if ( isset( $_POST['bank_account_number'] ) ) {
        $bank_account_number = sanitize_text_field( $_POST['bank_account_number'] );
        if ( !ctype_digit( $bank_account_number ) || strlen( $bank_account_number ) < 5 ) {
            wc_add_notice( __( 'Please provide a valid account number.', 'wc-commission' ), 'error' );
        } else {
            update_user_meta( $user_id, 'bank_account_number', sanitize_text_field( $_POST['bank_account_number'] ) );
        }
    }

    if ( isset( $_POST['bank_account_ifsc'] ) ) {
        $bank_account_ifsc = sanitize_text_field( $_POST['bank_account_ifsc'] );
        if ( !preg_match( "/^[A-Za-z]{4}[0-9]{7}$/", $bank_account_ifsc ) ) {
            wc_add_notice( __( 'Please provide a valid IFSC code.', 'wc-commission' ), 'error' );
        }else {
            update_user_meta( $user_id, 'bank_account_ifsc', sanitize_text_field( $_POST['bank_account_ifsc'] ) );
        }
    }   
}

add_action( 'woocommerce_save_account_details', 'wc_commission_update_bank_details', 12, 1 );
