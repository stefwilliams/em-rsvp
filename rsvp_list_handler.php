<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/wp-load.php');
global $EM_Event;
// global $wpdb;

$user_id = $_GET ['id'];
$user_bits = explode('user', $user_id);
$user_id = $user_bits [1];
$event_id = $_GET ['e_id'];
$rsvp_date = $_GET['sent'];
$attendance = $_GET ['state'];
$nonce = $_GET ['nonce'];

if ( ! wp_verify_nonce( $nonce, 'rsvp-nonce' )) {

	die( 'something ain\'t right' );

} else {


	$return = rsvp_answer_current($attendance, $user_id, $event_id);

	if ($return == false) {
		echo "there was an error";
	}

	else {
		echo "OK";
	}

}
?>