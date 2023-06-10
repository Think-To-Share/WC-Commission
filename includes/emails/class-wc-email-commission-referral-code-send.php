<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

class WC_Email_Commission_Referral_Code_Send extends WC_Email
{

    protected $referral_links;

    public function __construct()
    {
        $this->id             = 'referral_link_send_email';
        $this->customer_email = true;
        $this->title          = 'Referral Link send by email';
        $this->description    = 'None';
        $this->template_base  = WC_COMMISSION_PLUGIN_PATH . '/templates/';
        $this->template_html  = 'emails/referral_code_send.php';

        // Triggers for this email.
        add_action( 'woocommerce_commission_referral_code_created_notification', [ $this, 'trigger' ], 10, 3 );

        // Call parent constructor.
        parent::__construct();
    }

    public function trigger( $order_id, $order = false, $referral_links = null )
    {
        $this->setup_locale();

        if ( is_null( $referral_links ) ) {
            return;
        }

        $this->referral_links = $referral_links;

        if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
            $order = wc_get_order( $order_id );
        }

        if ( is_a( $order, 'WC_Order' ) ) {
            $this->object    = $order;
            $this->recipient = $this->object->get_billing_email();
        }

        if ( $this->is_enabled() && $this->get_recipient() ) {
            $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        }

        $this->restore_locale();
    }

    public function get_content_html()
    {
        return wc_get_template_html(
            $this->template_html,
            [
                'order'              => $this->object,
                'email_heading'      => $this->get_heading(),
                'sent_to_admin'      => false,
                'plain_text'         => false,
                'additional_content' => $this->get_additional_content(),
                'email'              => $this,
                'referral_links'     => $this->referral_links,
            ],
            '',
            $this->template_base
        );
    }

    public function get_default_subject()
    {
        return __( 'New Referral Link for Commission', 'wc-commission' );
    }

    public function get_default_heading()
    {
        return __( 'Congratulations! You have earned some referral link', 'wc-commission' );
    }

    public function get_default_additional_content()
    {
        return __( 'Thanks for shopping with us.', 'wc-commission' );
    }
}
