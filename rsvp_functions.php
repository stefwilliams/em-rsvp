<?php
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

			// if(($key = array_search($user_id, $rsvp_meta['rsvp_'.$not_answer])) !== false) {
   //  			unset($rsvp_meta['rsvp_'.$not_answer][$key]);
			// }
	}
		$return = update_post_meta( $event_id, $meta_key, $rsvp_meta );
		if ($return == true) {
			return true;
		}
		else {
			return false;
		}
};


//Function to return simple array of users who should receive RSVP alerts    
function rsvp_get_users() {

//This gets users based on a BuddyPress xprofile field -> other methods could be inserted if necessary
//Should also tie this in to an options page
	global $wpdb;// need this to be able to do the custom query below - without it, it fails hard.
$rsvp_field_id = xprofile_get_field_id_from_name('RSVP Requests?');
// all users who have chosen to receive RSVPs
$rsvp_users = $wpdb->get_results( 
		"
		SELECT user_id 
		FROM sg_bp_xprofile_data 
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