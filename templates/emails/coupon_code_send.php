<?php
/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php printf( esc_html__( 'Hi %s,', 'wc_commission' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
<p><?php esc_html_e( 'You have earned a new Commission Coupon.', 'wc_commission' ); ?></p>

<h2>
    <?php echo wp_kses_post(sprintf(__('[Coupon Code: %s]'), $coupon)) ?>
</h2>

<?php 

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email ); ?>
