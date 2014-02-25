<?php

function rsvp_ticklist_handler() {
	// $wp_root = site_url();
	// include_once($wp_root.'/wp-load.php');
	// global $EM_Event;
	// global $wpdb;

	$user_id = $_POST ['userid'];
	// $user_bits = explode('user', $user_id);
	// $user_id = $user_bits [1];
	$event_id = $_POST ['e_id'];
	$rsvp_date = $_POST['sent'];
	$attendance = $_POST ['state'];
	$nonce = $_POST ['nonce'];
	$noncecheck = check_ajax_referer( 'rsvp-nonce', 'nonce', false );



	if ( $noncecheck == false) {
		wp_die( 'something ain\'t right' );

	} else {

		$return = rsvp_answer_current($attendance, $user_id, $event_id);

		if ($return == false) {
			error_log("returned false");
			echo "there was an error";
			die();
		}

		else {
			error_log("returned true");
			echo "OK";
			die();
		}

	}
}
add_action('wp_ajax_rsvp_ticklist_handler', 'rsvp_ticklist_handler');

function rsvp_handler() {

$url = $_SERVER['REQUEST_URI'];
	$url_parts = parse_url($url);
parse_str($url_parts['query'],$query_vars);


global $EM_Event;

global $wpdb;
$rsvp_url = $url_parts ['path'];

$user_id = $query_vars ['user_id'];

$event_id = $query_vars ['event_id'];

$timestamp =  $query_vars ['timestamp'];

$attendance = $query_vars ['attendance'];

$md5_sent = $query_vars ['md5'];

$md5 = md5($user_id.$event_id.$timestamp);


if ($md5_sent != $md5) {
	echo '<h3>It looks like something went wrong.</h3> <p>Are you sure you clicked the link in your email?</p>';
	goto rsvp_end;
}



if ($attendance == 1) {
	$rsvp_answer = 'yes';
}
elseif ($attendance == 0) {
	$rsvp_answer = 'no';
}
elseif ($attendance == 2) {
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
$event_notes = wpautop($event_info->notes);

if ($event_location->location_postcode) {
	$postcode = $event_location->location_postcode;
	$postcode = ' '.$postcode;
}

if ($event_start_date == $event_start_date) {
	$event_end_date = NULL;
}

$event_display = 

<<<EVT
<h4>$event_info->event_name</h4>
<p><strong>Date: </strong>$event_start_date $event_end_date</p>
<p><strong>Time: </strong>$event_start_time $event_end_time</p>
<p><strong>Location: </strong>$event_location->location_name, $event_location->location_address $postcode</p>
<h4>Event Details</h4>
<p>$event_notes</p>
<h4>Band Member Info</h4>
<p>$event_info->post_excerpt</p>
<p>Visit the web site for <a href="$event_info->guid">full details</a> of this event.</p>
EVT;


//Check if RSVP is current based on timestamp
$rsvp_iscurrent = false;
$rsvp_check = get_post_meta( $event_id, 'rsvp_current', true );

if ($rsvp_check == $timestamp) {
	$rsvp_iscurrent = true;
}

	//Case 1 - RSVP is out-of-date. Timestamp has resent flag

	if (/*$rsvp_resent == 1*/ $rsvp_iscurrent == false) {

	//Alert user to problem.

	echo '<p><strong>The RSVP email link you just used is not current. Some details may have changed. </strong></p> <p>The latest event details are below. Please double-check whether you can make it.</p>';

	//Show current event details.

	echo $event_display;
	//Provide new link to respond with.
	echo '<p>Can you make the event as detailed above?</p>';
	echo '<p><a href="'.$rsvp_url.'?event_id='.$event_id.'&timestamp='.$rsvp_check.'&user_id='.$user_id.'&attendance=1">Yes, I can!</a></p>';
	echo '<p><a href="'.$rsvp_url.'?event_id='.$event_id.'&timestamp='.$rsvp_check.'&user_id='.$user_id.'&attendance=0">No, sorry...</a></p>';
	echo '<p><a href="'.$rsvp_url.'?event_id='.$event_id.'&timestamp='.$rsvp_check.'&user_id='.$user_id.'&attendance=2">Not sure, I\'ll have to think about it</a></p>';

	}

	//Case 2 - RSVP is current. Timestamp does not have resent flag

	elseif ($rsvp_iscurrent == true) {

		if ($rsvp_answer == 'yes') {
			rsvp_answer_current($rsvp_answer, $user_id, $event_id);
			echo '<h3>Yay! You can make it!</h3> <p>  See you there...</p><hr />';
			echo "<h3>Event Details</h3>";
			echo $event_display;
		}		

		elseif ($rsvp_answer == 'no') {
			rsvp_answer_current($rsvp_answer, $user_id, $event_id);
			echo '<h3>Aww! It\'s a shame you can\'t come!</h3> <p> If anything changes, please update the ticklist on the event page</p><hr />';
			echo "<h3>Event Details</h3>";
			echo $event_display;
		}

		elseif ($rsvp_answer == 'maybe') {
			rsvp_answer_current($rsvp_answer, $user_id, $event_id);
			echo '<h3>Not sure, huh?</h3> <p>Please update the ticklist when you know for sure</p><hr />';
			echo "<h3>Event Details</h3>";
			echo $event_display;
		}

	}
rsvp_end: //goto marker if md5 check fails.
}

add_shortcode( 'rsvp_handler', 'rsvp_handler' );


function rsvp_answer_current($answer, $user_id, $event_id){
	//Check current RSVP for timestamp
	$timestamp = get_post_meta( $event_id, 'rsvp_current', true );
	//get the meta of the current RSVP
	$meta_key = 'rsvp_'.$timestamp;
	$rsvp_meta = get_post_meta( $event_id, $meta_key, true);
	$the_answer = array($answer);
	$all_answers = array('yes', 'no', 'maybe');
	$not_answers = array_diff($all_answers, $the_answer);
	$rsvp_meta['rsvp_'.$answer][] = $user_id;

	foreach ($not_answers as $not_answer) {
		$rsvp_meta['rsvp_'.$not_answer] = array_diff($rsvp_meta['rsvp_'.$not_answer], array($user_id));
	}
		$return = update_post_meta( $event_id, $meta_key, $rsvp_meta );
		if ($return == true) {
			return true;
		}
		else {
			return false;
		}
};

function rsvp_get_users() {

//This gets users who should receive RSVPs, based on a BuddyPress xprofile field -> other methods could be inserted if necessary
//Should also tie this in to an options page
	global $wpdb;// need this to be able to do the custom query below - without it, it fails hard.
	$prefix = $wpdb->prefix;
	$table_name = $prefix.'bp_xprofile_data';
	$rsvp_field_id = xprofile_get_field_id_from_name('RSVP Requests?');
// all users who have chosen to receive RSVPs
$rsvp_users = $wpdb->get_results( 
		"
		SELECT user_id 
		FROM $table_name 
		WHERE field_id = $rsvp_field_id 
		AND value = 'Yes';
		", ARRAY_A
	);
$rsvp_user_ids = array();
	foreach ($rsvp_users as $rsvp_user) {   
		$rsvp_user_id  = $rsvp_user['user_id'];
		array_push($rsvp_user_ids, $rsvp_user_id);
	}
return $rsvp_user_ids;
}

// Get a particular user's response to an event's current RSVP
function rsvp_user_response ($event_id, $user) {
	$rsvp_responses = rsvp_responses ($event_id);
	if ($rsvp_responses) {
		$yes = array_search($user, $rsvp_responses['yes']);
		$no = array_search($user, $rsvp_responses['no']);
		$maybe = array_search($user, $rsvp_responses['maybe']);

		if ($yes > -1) {
			$attendance = 'yes';
		}
		elseif ($no > -1) {
			$attendance = 'no';
		}
		else {
			$attendance = 'maybe';
		}
		return $attendance;
	}
	else {return NULL;}
}

// Get array of responses received for the current RSVP request for a given event
function rsvp_responses ($event_id) {
	//check if there's an entry in postmeta -> 'rsvp_current' that corresponds to this event number
	//$rsvp_check is the timestamp for the last sent RSVP, ie replaces $rsvp_sent
	$rsvp_check = get_post_meta( $event_id, 'rsvp_current', true );
	//only do these queries if an rsvp has been sent already
	if ($rsvp_check != NULL) {
		//get record for current RSVP
		$rsvp_current = get_post_meta( $event_id, 'rsvp_'.$rsvp_check, true );
		//get number of people who have replied to latest RSVP for this event
		$users_y = $rsvp_current['rsvp_yes'];
		$users_yes = count($users_y);
		//
		$users_n = $rsvp_current['rsvp_no'];
		$users_no = count($users_n);
		//
		$users_m = $rsvp_current['rsvp_maybe'];
		$users_maybe = count($users_m);
		//
		$rsvp_responses = array(
			'current_rsvp' => $rsvp_check,
			'yes' => $users_y,
			'yes_count' => $users_yes,
			'no' => $users_n,
			'no_count' => $users_no,
			'maybe' => $users_m,
			'maybe_count' => $users_maybe,          
			);
		return $rsvp_responses;
	}
	else {
		return NULL;
	}
}
/**
	 * Sorts an array of objects by the value of one of the object properties or array keys
	 *
	 * @param array $array
	 * @param key value $id
	 * @param boolean $sort_ascending
	 * @param boolean $is_object_array
	 * @return array
	 * lifted from http://php.net/manual/en/function.sort.php (david wh thomas at gm at 1l dot c0m). Function renamed to arraysort from vsort
	 */
//Function to return simple array of users who should receive RSVP alerts    

function arraysort($array, $id="id", $sort_ascending=true, $is_object_array = false) {
		$temp_array = array();
		while(count($array)>0) {
			$lowest_id = 0;
			$index=0;
			if($is_object_array){
				foreach ($array as $item) {
					if (isset($item->$id)) {
										if ($array[$lowest_id]->$id) {
						if ($item->$id<$array[$lowest_id]->$id) {
							$lowest_id = $index;
						}
						}
									}
					$index++;
				}
			}else{
				foreach ($array as $item) {
					if (isset($item[$id])) {
						if ($array[$lowest_id][$id]) {
						if ($item[$id]<$array[$lowest_id][$id]) {
							$lowest_id = $index;
						}
						}
									}
					$index++;
				}                              
			}
			$temp_array[] = $array[$lowest_id];
			$array = array_merge(array_slice($array, 0,$lowest_id), array_slice($array, $lowest_id+1));
		}
				if ($sort_ascending) {
			return $temp_array;
				} else {
					return array_reverse($temp_array);
				}
	}
?>