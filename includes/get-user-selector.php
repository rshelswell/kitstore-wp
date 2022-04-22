<?php

add_action( 'wp_enqueue_scripts', 'kitstore_userselector_scripts');
add_action( 'wp_ajax_ajax-kitstoreUserSelector', 'kitstore_userselector');

function kitstore_userselector_scripts() {
    wp_enqueue_script( 'kitstore_userselector',
        plugins_url( '/js/kitstore_userselector.js' , __FILE__ ),
        array( 'jQuery' ) );
    wp_localize_script( 'kitstore_userselector', 'KWP_Ajax', array(
        'ajaxurl'   => admin_url( 'admin-ajax.php' ),
        'nextNonce' => wp_create_nonce( 'kitstore-next-nonce' )));
}

function kitstore_userselector()
{
    // check nonce
    $nonce = $_POST['nextNonce'];
    if (! wp_verify_nonce($nonce, 'kitstore-next-nonce')) {
        die ( 'Busted!' );
    }

    //generate data for response
    global $wpdb;

    $opt = (!empty($_GET['q']) && $_GET['q'] != 0) ? sanitize_text_field($_GET['q']) : null;
    /* old approach
    $query = "SELECT id ";
    $query .= "FROM {$wpdb->users} u";
    if (!is_null($opt)) {
        $query .= " INNER JOIN $wpdb->usermeta m ON m.user_id = u.ID
                    WHERE m.dofegroup = %s";
        $query .= "ORDER BY m.dofegroup, ";
    } else {
        $query .= " INNER JOIN $wpdb->usermeta m ON m.user_id = u.ID
                    WHERE m.dofegroup <> ''
                    ORDER BY ";
    }
    $query .= "u.displayname";
    */

    if (!is_null($opt)) {
        $args = array(
            'meta_query' => array(
                array(
                    'key' => 'dofegroup',
                    'value' => $opt,
                    'compare' => '='
                )
            ),
            'orderby' => 'display_name'
        );

        // $query = $wpdb->prepare($query, $opt));
    } else {
        $args = array(
            'meta_query' => array(
                array(
                    'key' => 'dofegroup',
                    'value' => '',
                    'compare' => '!='
                )
            ),
            'orderby' => 'display_name'
        );

    }

    //foreach ($wpdb->get_results($query) as $userid) {

    // Create the WP_User_Query object
    $wp_user_query = new WP_User_Query($args);

    // Get the results
    $user_arr = $wp_user_query->get_results();

    $response = json_encode($user_arr);

    header( "Content-Type: application/json");
    echo $response;
    exit;
    /* move following into js
    $htmloutput = "<section>
    ";

    foreach ($users_arr as $user) {
        // create a card styled radio button for each option
        $htmloutput .= "
        <div>
              <input type="radio" id="control_{
            $user->ID}" name="select" value="{
            $user->ID}">
              <label for="control_{
            $user->ID}">
                <p>{$user->display_name}</p>
              </label>
        </div>
        "; 

    }
    // end the row after the last item.
    $htmloutput .= '</section>';
    */
}
