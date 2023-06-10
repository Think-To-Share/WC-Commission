<fieldset>
    <legend><?php esc_html_e( 'Update Bank Details', 'wc-commission' ); ?></legend>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="bank_account_name"><?php esc_html_e( 'Name of the Account Holder', 'wc-commission' ); ?></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="bank_account_name" id="bank_account_name" autocomplete="off" value="<?php echo esc_attr( $user->bank_account_name ); ?>" />
    </p>
    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="bank_account_number"><?php esc_html_e( 'Bank Account Number', 'wc-commission' ); ?></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="bank_account_number" id="bank_account_number" autocomplete="off" value="<?php echo esc_attr( $user->bank_account_number ); ?>" />
    </p>
    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="bank_account_ifsc"><?php esc_html_e( 'IFSC Code', 'wc-commission' ); ?></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="bank_account_ifsc" id="bank_account_ifsc" autocomplete="off" value="<?php echo esc_attr( $user->bank_account_ifsc ); ?>" />
    </p>
</fieldset>
<div class="clear"></div>
