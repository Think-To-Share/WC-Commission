<?php
/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php printf( esc_html__( 'Hi %s,', 'wc-commission' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
<p><?php esc_html_e( 'Referral links for commission.', 'wc-commission' ); ?></p>


<table class="td" cellspacing="0" cellpadding="6" border="1" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" width="100%">
		<thead>
			<tr>
				<th class="td" scope="col" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left;" align="left">Product</th>
				<th class="td" scope="col" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left;" align="left">Referral Link</th>
			</tr>
		</thead>
		<tbody>
            <?php foreach ( $referral_links as $referral_link ) { ?>
			<tr class="order_item">
		        <td class="td" style="color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;" align="left">
                   <?php echo wp_kses_post( sprintf( $referral_link['product_name'] ) ); ?>
                </td>
                <td class="td" style="color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;" align="left">
                  <a href="<?php echo wp_kses_post( sprintf( $referral_link['ref_link'] ) ); ?>"><?php echo wp_kses_post( sprintf( $referral_link['ref_link'] ) ); ?><a>
                </td>
            </tr>
	        <?php } ?>
        </tbody>
</table>


<?php

if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email ); ?>
