<?php



require_once($_SERVER['DOCUMENT_ROOT'].'/wp-blog-header.php');

global $EM_Event;

global $wpdb;

$user_id = $_GET [user_id];

$event_id = $_GET [event_id];

$the_time =  $_GET [the_time];

$attendance = $_GET [attendance];



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



$rsvp_event = $wpdb->get_row( "SELECT * FROM sg_em_rsvpsent WHERE event=$event_id AND sent_date=$the_time" );

$rsvp_replied = $wpdb->get_row("SELECT * FROM sg_em_rsvprcvd WHERE event=$event_id AND user=$user_id AND timestamp=$the_time ");

$rsvp_resent = $rsvp_event -> resent;	



	//Case 1 - RSVP is out-of-date. Timestamp has resent flag

	if ($rsvp_resent == 1) {

	//Alert user to problem.

	echo 'The RSVP email link you just used is not current. Some details may have changed. The latest event details are below. Please double-check whether you can make it.';

	//Show current event details.

	echo $event_display;



	//Provide new link to respond with.

	//if attendance = 1, say thanks, 

	//if attendance = 0, say boo

	//After response, show event details

	}

	//Case 2 - RSVP is current. Timestamp does not have resent flag

	elseif ($rsvp_resent == 0 && $rsvp_replied == NULL) {

	echo 'RSVP is current';echo 'no reply yet';

	//add user rsvp to db. 

				$wpdb->insert (

				'sg_em_rsvprcvd',

				array (

					'user' => $user_id,

					'event' => $event_id,

					'timestamp' => $the_time,

					'attendance' => $attendance

					)

				);

	//if attendance = 1, say thanks, 

		if ($attendance == 1) {

		echo 'Yay! You can make it!';

		}		

		elseif ($attendance == 0) {

		echo 'Aww! That\'s a shame! You can\'t come!';

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