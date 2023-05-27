<?php

if( isset($_POST['submit']) && strlen($_POST['withdrawal_amount'] !== null))
{
    if(wp_verify_nonce($_POST['withdrawal_nonce'], 'submit-amount'))
    {
        $withdrawal_amount = $_POST['withdrawal_amount'];
        if($commission_amount > 0 && $withdrawal_amount <= $commission_amount)
        {
            // Set the coupon data
            $withdrawal_post = array(
                'post_title' => 'withdrawal'.$user_id,
                'post_status' => 'publish',
                'post_name' => strtolower('withdrawal'.$user_id),
                'post_type' => 'withdrawal',
            );
            
            $withdrawal_id = wp_insert_post( $withdrawal_post );
            $withdrawal_guid = add_query_arg( array(
                'post_type' => 'withdrawal',
                'p' => $withdrawal_id
            ), get_site_url() );
            
            $wpdb->update($wpdb->posts, ['guid' => $withdrawal_guid], ['ID' => $withdrawal_id]);
        
            update_field('amount', $withdrawal_amount, $withdrawal_id);
            update_field('user_id', $user_id, $withdrawal_id);
            update_field('status', 'no action taken', $withdrawal_id);
        
            $commission_amount = ($commission_amount - $withdrawal_amount);
            $table_name = $wpdb->prefix . 'commissions';
            $wpdb->update($table_name, ['commission_amount' => $commission_amount], ['user_id' => $user_id]); 
        }
        else {
            echo "<b>You do not have sufficient money for withdrawal</b>";
        }	
    }else{
        die('security check');
    }
    
}
?>

<div><b>Current balance in your wallet: <?php echo $commission_amount; ?></b></div>

<br>

<form method="post" action="">
    <label for="withdrawal_amount">Withdrawal amount:</label>
    <input type="number" name="withdrawal_amount" id="withdrawal_amount" required>
    <input type="hidden" name="withdrawal_nonce" value="<?php echo wp_create_nonce('submit-amount') ?>" />
    <br>
    <br>
    <input type="submit" name="submit" value="Submit">
</form>

<center><h4>Withdrawal List</h4></center>


    <?php
    $the_query = new WP_Query( array( 'post_type' => 'withdrawal', 'author' => $user_id ) );

    // The Loop
    if ( $the_query->have_posts() ): ?>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Amount</th>
                <th>Status</th>
            </tr>
        </thead> 
        <tbody>
    <?php while ( $the_query->have_posts() ):  $the_query->the_post(); ?>

            <tr>
                <td><?php echo get_the_date(); ?></td>
                <td><?php echo get_field('amount'); ?></td>
                <td><?php echo get_field('status'); ?></td>
            </tr>
        </tbody>
    <?php endwhile; ?>
    </table>
    <?php
    
        /*if(function_exists('custom_pagination')) {
            custom_pagination($the_query->max_num_pages,"",$paged);
        }*/

        //wp_reset_postdata();

    else:
        echo "you did not apply for any withdrawal";
    endif;
        /* Restore original Post Data */
        wp_reset_postdata();
    ?>