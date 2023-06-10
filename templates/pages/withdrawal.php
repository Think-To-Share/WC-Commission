<div>
    <b>Current balance in your wallet: <?php echo $commission_amount; ?></b>
</div>

<br>

<form method="POST">
    <label for="withdrawal_amount">Withdrawal amount:</label>
    <input type="number" name="withdrawal_amount" id="withdrawal_amount" required>
    <?php wp_nonce_field( 'withdrawal_form_action', 'withdrawal_form_nonce' ); ?>
    <br>
    <br>
    <input type="submit" name="submit" value="Submit">
</form>

<center><h4>Withdrawal List</h4></center>


<?php
    $the_query = new WP_Query( [ 'post_type' => 'withdrawal', 'author' => $user_id ] );

    // The Loop
    if ( $the_query->have_posts() ) { ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead> 
            <tbody>
                <?php while ( $the_query->have_posts() ) {
                    $the_query->the_post(); ?>
                    <tr>
                        <td><?php echo get_the_date(); ?></td>
                        <td><?php echo get_field( 'amount' ); ?></td>
                        <td><?php echo get_field( 'status' ); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
<?php
    } else {
        echo 'you did not apply for any withdrawal';
    }

    /* Restore original Post Data */
    wp_reset_postdata();
    ?>