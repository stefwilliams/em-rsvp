<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/wp-load.php');
global $EM_Event;
global $wpdb;

$user_id = $_GET [id];
$user_bits = explode('user', $user_id);
$user_id = $user_bits [1];
$event_id = $_GET [e_id];
$rsvp_date = $_GET[sent];
$attendance = $_GET [state];


$err = $wpdb->query( 
	$wpdb->prepare( 
		"
		DELETE FROM sg_em_rsvprcvd
		WHERE user = %d 
		AND event = %s
		",
		$user_id, $event_id 
		)
	);
if ($err === false){
	echo 'ERR1';
}
else
{
	$state=2;
	if ($attendance==='attend') {
		$state=1;
	}
	elseif ($attendance==='non-attend') {
		$state=0;
	}
	if ($state<2){
		$err = $wpdb->query( 
			$wpdb->prepare( 
				"
				INSERT INTO sg_em_rsvprcvd
				(event,timestamp,user,attendance)
				VALUES (%d,%d,%d,%d)
				",
				$event_id, $rsvp_date, $user_id, $state
				)
			);
		if	($err === false){
			echo 'ERR2';
		}
		else {
			echo 'OK';
		}	
	} else {
		echo 'OK';
	}
}
?>