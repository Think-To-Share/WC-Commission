<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Email_Commission_Coupon_Code_Send extends WC_Email {

    public function __construct() {

		$this->id  = 'coupon_send_email';
		$this->customer_email = true;
		$this->title          = 'Coupon send by email';
		$this->description    = 'None';
		$this->template_base  = WC_COMMISSION_PLUGIN_PATH.'/templates/';
		$this->template_html  = 'emails/coupon_code_send.php';
		$this->placeholders   = array(
			'{order_date}'   => '',
			'{order_number}' => '',
		);
		
		// Triggers for this email.
		add_action( 'woocommerce_commission_coupon_created_notification', array( $this, 'trigger' ), 10, 3);

		// Call parent constructor.
		parent::__construct();
    }

	
	public function trigger($order_id, $order = false, $coupon = null) 
	{
		$this->setup_locale();

		if(is_null($coupon)) {
			return;
		}

		$this->coupon = $coupon;

		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( is_a( $order, 'WC_Order' ) ) {
			$this->object                         = $order;
			$this->recipient                      = $this->object->get_billing_email();
			$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
			$this->placeholders['{order_number}'] = $this->object->get_order_number();
		}

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}
	
		$this->restore_locale();
	}


	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html, array(
				'order'         	 => $this->object,
				'email_heading' 	 => $this->get_heading(),
				'sent_to_admin' 	 => false,
				'plain_text'    	 => false,
				'additional_content' => $this->get_additional_content(),
				'email'         	 => $this,
				'coupon'			 => $this->coupon,
			), '', $this->template_base
		);
	}

	public function get_default_heading() {
		return __( 'Congratulations! You Earned a Coupon', 'wc_commission' );
	}

	public function get_default_additional_content() {
		return __( 'Thanks for shopping with us.', 'wc_commission' );
	}
} 

