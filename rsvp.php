<?php
/*
Plugin Name: Events Manager RSVP
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Adds RSVP option to Events Manager.
Version: 1.1
Author: Stef Williams
Author URI: http://URI_Of_The_Plugin_Author
License: GPL2
*/

/*WIDGET*/
include ('rsvp_functions.php');
include ('rsvp_widget.php');
include ('rsvp_list.php');
//add RSVP checkbox to user profile page - allow users to choose whether or not they are alerted about events with RSVP requests. Lifted from: http://blog.ftwr.co.uk/archives/2009/07/19/adding-extra-user-meta-fields/
//add RSVP checkbox to EM Admin page by:
//1) hooking into 'add_meta_boxes' action
add_action( 'add_meta_boxes', 'rsvp_add_custom_box' );
//2) setting up the Meta box on the admin screen
function rsvp_add_custom_box () {
	add_meta_box(
	'rsvp_box', 
	'RSVP Request', 
	'rsvp_box', 
	'event', 
	'side',
	'high'
	);
}

add_action( 'em_front_event_form_footer', 'rsvp_box' );

//3) inserting some HTML into the Meta box
function rsvp_box (){
	echo "<div class='rsvp_box'>";
	date_default_timezone_set('Europe/London');
	//set up global variables for Events and db connections
	global $EM_Event;
	global $wpdb;
	//get current event's ID
	$event_id = $EM_Event->id;
	$rsvp_check = NULL; 
//if there is an event number
if ($event_id !=NULL) {
	$rsvp_responses = rsvp_responses($event_id);
	$rsvp_check = $rsvp_responses['current_rsvp'];
	$users_yes = $rsvp_responses['yes_count'];
	$users_no = $rsvp_responses['no_count'];
	$users_maybe = $rsvp_responses['maybe_count'];
	$users_all = $users_yes + $users_no + $users_maybe;
	$rsvp_prevsends = get_post_meta( $event_id, 'rsvp_notcurrent', true );
}
	//convert date format
	$date_sent = date('D j M Y', $rsvp_check);
	$time_sent = date('g:i a', $rsvp_check);
	//if an RSVP has already been sent
		if ($rsvp_check!=NULL) {	
			echo '<p>RSVP Email sent for this event on <br /><strong>'.$date_sent.' at '.$time_sent.' GMT</strong></p>';
			echo '<p>So far, <strong>'.$users_all.' </strong>people have replied - </p><p>(<span style="color:green"><strong> '.$users_yes.'</strong> yes</span><span style="color:red;">, <strong>'.$users_no. '</strong> no</span><span style="color:gray;">, <strong>'.$users_maybe. '</strong> maybe</span>)</p>';
			//if any RSVPs were sent before the last one
			if ($rsvp_prevsends != NULL) {
			echo '<p>Previous RSVPs for this event were sent on:';
			echo '<ul>';
				foreach ($rsvp_prevsends as $prevsent) {
				echo '<li>'.date('D j M Y', $prevsent).' ('.date('g:i a', $prevsent).')</li>';
				}
			echo '</ul></p>';
			}
			//give the option to send another RSVP even if one has been sent already	
			echo '<p><input type="checkbox" name="rsvp_status" value="resend_rsvp" /> To re-send an RSVP, select this box.</p><p><strong>Warning:</strong> Only send a new RSVP if something significant has changed. Fight spam.</p>';
			}
		//if no RSVP has been sent
		else {
			echo '<input type="checkbox" name="rsvp_status" value="send_rsvp" /> Send an RSVP Email for this event';
		}
		echo "</div>";
		date_default_timezone_set('UTC');
}

// action to perform on Save
add_filter ('em_event_save', 'rsvp_processing',1,1);

//Process RSVPs depending on results of $_POST value
function rsvp_processing ($result) {

	if ($result) { // checks whether the event was actually saved, before proceeding, otherwise missing location or date fields will still result in RSVP being sent
		//time which will be used to differentiate rsvps and resent rsvps. This will also be the main key to identify current RSVP to users and link to rsvp results
		$timestamp = time();
		$old_timestamp = ''; // <-- gets set only if resend_rsvp is true
		//global variables again for Events
		//global $wpdb;
		global $EM_Event;
		//current event'd ID
		$event_id = $EM_Event->id;
		//check value returned from checkboxes in rsvp_box
		$rsvp_status = $_POST['rsvp_status'];

			if ($rsvp_status == 'resend_rsvp') {
				//find the eventmeta for the RSVP record that is being replaced
				$old_timestamp = get_post_meta( $event_id, 'rsvp_current', true ); // <-- gets set only if resend_rsvp is true
				$old_meta_key = 'rsvp_'.$old_timestamp;
				$old_rsvp_eventmeta = get_post_meta( $event_id, $old_meta_key, true );
				//change its 'current' status to 'no'
				$old_rsvp_eventmeta['current'] ='no';
				//and write it back to the DB
				update_post_meta( $event_id, $old_meta_key, $old_rsvp_eventmeta );

				//insert old rsvp timestamp into 'notcurrent' list;
				$notcurrent = array();
				$notcurrent = get_post_meta( $event_id, 'rsvp_notcurrent', true );
				$notcurrent[] = $old_timestamp;
				update_post_meta( $event_id, 'rsvp_notcurrent', $notcurrent );
			}
			
			//if no rsvp has been sent yet, and one is due to be sent
			if ($rsvp_status == 'send_rsvp' || $rsvp_status == 'resend_rsvp') {
				//mark this timestamp as the current rsvp for this event
				//create meta key and insert post_meta for the current RSVP
				$meta_key = 'rsvp_'.$timestamp;
				$rsvp_eventmeta = array(
					'event_id'			=> $event_id,
					'current' 			=> 'yes',
					'replaces_rsvp' 	=> $old_timestamp, //empty if not a resent rsvp
					'event_name' 		=> $EM_Event->event_name,
					'event_date' 		=> $EM_Event->event_start_date,
					'event_start_time' 	=> $EM_Event->event_start_time,
					'event_location' 	=> $EM_Event->location_id,
					'rsvp_maybe' 		=> array(),
					'rsvp_yes' 			=> array(),
					'rsvp_no' 			=> array()
				);
				//send the eventmeta for the mail function to use
				// SEND THE MAILS BEFORE updating the post_meta, otherwise it will send to the wrong people :)
				rsvp_email($rsvp_eventmeta, $rsvp_status, $timestamp);
				update_post_meta( $event_id, $meta_key, $rsvp_eventmeta );
				update_post_meta( $event_id, 'rsvp_current', $timestamp );
			}
	}	//end if ($result)
	return $result;
}

function rsvp_email($rsvp_eventmeta, $rsvp_status, $timestamp) {
$EM_Event = em_get_event($rsvp_eventmeta['event_id']);
$event_id = $rsvp_eventmeta['event_id'];
$event_name = $EM_Event->output('#_EVENTLINK');
$event_location = $EM_Event->output('#_LOCATIONNAME');
$event_dates = $EM_Event->output('#_EVENTDATES');
$event_time = $EM_Event->output('#_EVENTTIMES');
$event_category = $EM_Event->output('#_CATEGORYNAME');
$event_owner = $EM_Event->post_author;
	$event_owner_object = get_userdata($event_owner);
	$event_owner_email = $event_owner_object->user_email;
	$event_contact = $event_owner_object->display_name;
$event_notes = $EM_Event->output('#_EVENTNOTES');
$event_excerpt = $EM_Event->post_excerpt;
$event_url = $EM_Event->output('#_EVENTURL');
$rsvp_url = site_url( 'thanks-for-your-reply');
$rsvp_headers = 'From:'.$event_contact.'<'.$event_owner_email.'>'."\r\n";

//get all users who have opted to receive RSVPs
$rsvp_all_users = rsvp_get_users();

//insert header for HTML emails
add_filter('wp_mail_content_type',create_function('', 'return "text/html";'));

if ($rsvp_status == 'send_rsvp') {

	$rsvp_users = implode(",",$rsvp_all_users);
	$rsvp_users = get_users('include='.$rsvp_users);	
	$debug_users = serialize($rsvp_users);


	foreach ($rsvp_users as $rsvp_user) {
		$md5 = md5($rsvp_user->ID.$event_id.$timestamp);
	//message for first RSVP being sent
		$rsvp_subject = '[SG] A new event has been listed. Can you make it?';

$debug_user_meta = serialize($rsvp_user);

$rsvp_msg = 
<<<MSG
<p>A new event has been listed on the Samba website. Please let us know if you can make it.</p>
<h3>Event Name: $event_name</h3>
<h4>Date: $event_dates</h4>
<h4>Time: $event_time</h4>
<h4>Location: $event_location</h4>
<h4>Category: $event_category</h4>
<br/>
<p>
<a class="$rsvp_status" href="$rsvp_url/?event_id=$event_id&amp;timestamp=$timestamp&amp;md5=$md5&amp;user_id=$rsvp_user->ID&amp;attendance=1">Yes! I'm in!</a>
</p>
<p>
<a class="$rsvp_status" href="$rsvp_url/?event_id=$event_id&amp;timestamp=$timestamp&amp;md5=$md5&amp;user_id=$rsvp_user->ID&amp;attendance=0">Nope, sorry got better things to do.</a>
</p>
<p>
<a class="$rsvp_status" href="$rsvp_url/?event_id=$event_id&amp;timestamp=$timestamp&amp;md5=$md5&amp;user_id=$rsvp_user->ID&amp;attendance=2">I'm not sure. Will confirm later.</a>
</p>
<p>Note: If you don't reply to this email or change your mind later, you can always update your status on the event's <a href="$event_url">web page.</a></p>
<br/>
<p>Event Details: $event_notes</p>
<br/>
<p>Band-member info: $event_excerpt</p>

MSG;
		wp_mail ($rsvp_user->user_email, $rsvp_subject, $rsvp_msg, $rsvp_headers);
	}
}
elseif ($rsvp_status == 'resend_rsvp') {
	//get user ids of those who have replied to previous RSVPs
	// from yes, no and maybe list
	$rsvp_responses = rsvp_responses($event_id);
	$debug_responses = var_export($rsvp_responses, true);
	$rsvp_yes = $rsvp_responses['yes'];
	$rsvp_no = $rsvp_responses['no'];
	$rsvp_maybe = $rsvp_responses['maybe'];
	//merge the list of all replies	
	$users_replied = array_merge((array)$rsvp_yes, (array)$rsvp_no, (array)$rsvp_maybe);
	$users_not_replied = array_diff($rsvp_all_users, $users_replied);
	$userlistsimple=implode(",",$users_not_replied);
	$userlistspecial=implode(",",$users_replied);
	$rsvp_users_resend_simple = get_users('include='.$userlistsimple);

	foreach ($rsvp_users_resend_simple as $rsvp_user) {
		$md5 = md5($rsvp_user->ID.$event_id.$timestamp);
	//message for RSVP being resent
		$rsvp_resend_subject = '[SG] An event\'s details have changed. Please ignore previous emails';
$rsvp_resend_msg = 
<<<MSG
<p>This event's details have changed. Please ignore previous RSVP requests for this event.</p>
<br/>
<h3>Event Name: $event_name</h3>
<h4>Date: $event_dates</h4>
<h4>Time: $event_time</h4>
<h4>Location: $event_location</h4>
<h4>Category: $event_category</h4>
<br/>
<p>
<a class="$rsvp_status" href="$rsvp_url/?event_id=$event_id&amp;timestamp=$timestamp&amp;md5=$md5&amp;user_id=$rsvp_user->ID&amp;attendance=1">Yes! I'm in!</a>
</p>
<p>
<a class="$rsvp_status" href="$rsvp_url/?event_id=$event_id&amp;timestamp=$timestamp&amp;md5=$md5&amp;user_id=$rsvp_user->ID&amp;attendance=0">Nope, sorry got better things to do.</a>
</p>
<p>
<a class="$rsvp_status" href="$rsvp_url/?event_id=$event_id&amp;timestamp=$timestamp&amp;md5=$md5&amp;user_id=$rsvp_user->ID&amp;attendance=2">I'm not sure. Will confirm later.</a>
</p>
<p>Note: If you don't reply to this email or change your mind later, you can always update your status on the event's <a href="$event_url">web page.</a></p>
<br/>
<p>Event Details: $event_notes</p>
<br/>
<p>Band-member info: $event_excerpt</p>
MSG;
		wp_mail ($rsvp_user->user_email, $rsvp_resend_subject, $rsvp_resend_msg, $rsvp_headers);
}

//get user details for those who HAVE already replied

	$rsvp_users_resend_special = get_users('include='.$userlistspecial);

	foreach ($rsvp_users_resend_special as $rsvp_user) {
		$md5 = md5($rsvp_user->ID.$event_id.$timestamp);
//message for RSVP being resent. Special alert for people who have already replied
		$rsvp_special_subject = '[SG]Event changed! Please update your RSVP status';
$rsvp_special_msg =
<<<MSG
<p>This event's details have changed. Please ignore previous RSVP requests for this event and update your status.</p>
<p><strong>Important:</strong>If you've previously RSVP'd to this event, you will need to do so again.</p>
<br/>
<h3>Event Name: $event_name</h3>
<h4>Date: $event_dates</h4>
<h4>Time: $event_time</h4>
<h4>Location: $event_location</h4>
<h4>Category: $event_category</h4>
<br/>
<p>
<a class="$rsvp_status" href="$rsvp_url/?event_id=$event_id&amp;timestamp=$timestamp&amp;md5=$md5&amp;user_id=$rsvp_user->ID&amp;attendance=1">Yes! I'm in!</a>
</p>
<p>
<a class="$rsvp_status" href="$rsvp_url/?event_id=$event_id&amp;timestamp=$timestamp&amp;md5=$md5&amp;user_id=$rsvp_user->ID&amp;attendance=0">Nope, sorry got better things to do.</a>
</p>
<p>
<a class="$rsvp_status" href="$rsvp_url/?event_id=$event_id&amp;timestamp=$timestamp&amp;md5=$md5&amp;user_id=$rsvp_user->ID&amp;attendance=2">I'm not sure. Will confirm later.</a>
</p>
<p>Note: If you don't reply to this email or change your mind later, you can always update your status on the event's <a href="$event_url">web page.</a></p>
<br/>
<p>Event Details: $event_notes</p>
<br/>
<p>Band-member info: $event_excerpt</p>
MSG;
		wp_mail ($rsvp_user->user_email, $rsvp_special_subject, $rsvp_special_msg, $rsvp_headers);
}

}

}



?>