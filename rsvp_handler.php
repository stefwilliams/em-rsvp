<?php



require_once($_SERVER['DOCUMENT_ROOT'].'/wp-blog-header.php');

global $EM_Event;

global $wpdb;

$user_id = $_GET [user_id];

$event_id = $_GET [event_id];

$timestamp =  $_GET [timestamp];

$attendance = $_GET [attendance];

if ($attendance == 1) {
	$rsvp_answer = 'yes';
}
elseif ($attendance == 0) {
	$rsvp_answer = 'no';
}
else {
	$rsvp_answer = 'maybe';
}



// gets the details of the event and formats the text to display and hides info if fields are empty.

$event_info = EM_Events::get(array ('event'=>$event_id));

$event_info = $event_info[0];

$event_location=$event_info->get_location();



$event_start = $event_info->start;

$event_end = $event_info->end;



$event_start_date = date('l jS M Y', $event_start);

$event_start_time = date('g:i a', $event_start);

$event_end_date = date('- l jS M Y', $event_end);

$event_end_time = date('- g:i a', $event_end);

if ($event_location->location_postcode) {

	$postcode = $event_location->location_postcode;

	$postcode = ' '.$postcode;

}

if ($event_start_date == $event_start_date) {

	$event_end_date = NULL;

}

$event_display = 

<<<EVT

<h3>$event_info->event_name</h3>

<p><strong>Date: </strong>$event_start_date $event_end_date</p>

<p><strong>Time: </strong>$event_start_time $event_end_time</p>

<p><strong>Location: </strong>$event_location->location_name, $event_location->location_address $postcode</p>

<h4>Brief</h4>

<p>$event_info->post_excerpt</p>

<h4>Full Description</h4>

<p>$event_info->notes</p>

Visit the web site for <a href="$event_info->guid">full details</a> of this event.

EVT;


//Check if RSVP is current based on timestamp
$rsvp_iscurrent = false;

$rsvp_check = get_post_meta( $event_id, 'rsvp_current', true );


if ($rsvp_check == $timestamp) {
	$rsvp_iscurrent = true;
}

//get the meta of the current RSVP

$meta_key = 'rsvp_'.$timestamp;
$rsvp_meta = get_post_meta( $event_id, $meta_key, true);


//get the users who have already replied to the RSVP
// $replies_maybe = $rsvp_meta->'rsvp_maybe';
// $replies_yes = $rsvp_meta->'rsvp_yes';
// $replies_no = $rsvp_meta->'rsvp_no';

$rsvp_url = plugins_url('em-rsvp');

//$rsvp_event = $wpdb->get_row( "SELECT * FROM sg_em_rsvpsent WHERE event=$event_id AND sent_date=$timestamp" );

//$rsvp_replied = $wpdb->get_row("SELECT * FROM sg_em_rsvprcvd WHERE event=$event_id AND user=$user_id AND timestamp=$timestamp ");



//$rsvp_resent = $rsvp_event -> resent;	







	//Case 1 - RSVP is out-of-date. Timestamp has resent flag

	if (/*$rsvp_resent == 1*/ $rsvp_iscurrent == false) {

	//Alert user to problem.

	echo '<p><strong>The RSVP email link you just used is not current. Some details may have changed. </strong></p> <p>The latest event details are below. Please double-check whether you can make it.</p>';

	//Show current event details.

	echo $event_display;

	echo '<p>Can you make the event as detailed above?</p>';
	echo '<p><a href="'.$rsvp_url.'/rsvp_handler.php?event_id='.$event_id.'&timestamp='.$timestamp.'&user_id='.$user_id.'&attendance=1">Yes, I can!</a></p>';
	echo '<p><a href="'.$rsvp_url.'/rsvp_handler.php?event_id='.$event_id.'&timestamp='.$timestamp.'&user_id='.$user_id.'&attendance=0">No, sorry...</a></p>';
	echo '<p><a href="'.$rsvp_url.'/rsvp_handler.php?event_id='.$event_id.'&timestamp='.$timestamp.'&user_id='.$user_id.'&attendance=">Not sure, I\'ll have to think about it</a></p>';
	//Provide new link to respond with.

	//if attendance = 1, say thanks, 

	//if attendance = 0, say boo

	//After response, show event details

	}

	//Case 2 - RSVP is current. Timestamp does not have resent flag

	elseif (/*$rsvp_resent == 0 && $rsvp_replied == NULL*/ $rsvp_iscurrent == true) {

	echo 'RSVP is current';echo 'no reply yet';

	//add user rsvp to db. 

				// $wpdb->insert (

				// 'sg_em_rsvprcvd',

				// array (

				// 	'user' => $user_id,

				// 	'event' => $event_id,

				// 	'timestamp' => $timestamp,

				// 	'attendance' => $attendance

				// 	)

				// );

	//if attendance = 1, say thanks, 

		if ($rsvp_answer == 'yes') {

			$rsvp_meta['rsvp_yes'][] = $user_id;

			if(($key = array_search($user_id, $rsvp_meta['rsvp_no'])) !== false) {
    			unset($rsvp_meta['rsvp_no'][$key]);
			}
			if(($key = array_search($user_id, $rsvp_meta['rsvp_maybe'])) !== false) {
    			unset($rsvp_meta['rsvp_maybe'][$key]);
			}


		echo 'Yay! You can make it! See you there...';

		}		

		elseif ($rsvp_answer == 'no') {

			$rsvp_meta['rsvp_no'][] = $user_id;

		echo 'Aww! It\'s a shame you can\'t come! If anything changes, please update the ticklist on the event page';

		}

		elseif ($rsvp_answer == 'maybe') {
			# code...
		}

	}	//if attendance = 0, say boo

	elseif ($rsvp_resent == 0 && $rsvp_replied != NULL) {

		echo 'It looks like you\'ve already replied to this RSVP.';

	}



	else {

	echo 'WTF? How did you get here? Tell Stef you saw this messsage';

	}

//Case 3 - User has already responded to current RSVP. 

//If Changing status, thanks for letting us know

//if attendance = 1, say thanks, 

//if attendance = 0, say boo

//If repeating status, we knew already, but thanks for letting us know again!

echo '<pre>'; print_r($event_location); echo '</pre>';

echo '<pre>'; print_r($event_info); echo '</pre>';

?>