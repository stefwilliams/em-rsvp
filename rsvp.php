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
include 'rsvp_widget.php';
include 'rsvp_list.php';
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
	//check if there's an entry in sg_em_rsvp that corresponds to this event number
	$rsvp_check = $wpdb->get_results( 
		"
		SELECT * 
		FROM sg_em_rsvpsent 
		WHERE event=$event_id
		" 
	);

	//get the date the last RSVP was sent
	$rsvp_sent = $wpdb->get_var( 
		"
		SELECT sent_date 
		FROM sg_em_rsvpsent 
		WHERE event=$event_id 
		AND resent=0
		"  
	);

	//check if there were any RSVPs sent before the lates ones
	$rsvp_prevsends = $wpdb->get_results( 
		"
		SELECT sent_date 
		FROM sg_em_rsvpsent 
		WHERE event=$event_id 
		AND resent=1
		" 
	);

	//only do these queries if an rsvp has been sent already
	if ($rsvp_sent != NULL) {
	
		//gets number of people who have replied to latest RSVP for this event
		$users_y = $wpdb->get_results( 
			"
			SELECT * 
			FROM sg_em_rsvprcvd 
			WHERE event=$event_id 
			AND timestamp=$rsvp_sent 
			AND attendance=1
			" 
		);
		$users_yes = $wpdb->num_rows;
		
		$users_n = $wpdb->get_results( 
			"
			SELECT * 
			FROM sg_em_rsvprcvd 
			WHERE event=$event_id 
			AND timestamp=$rsvp_sent 
			AND attendance=0
			" 
		);
		$users_no = $wpdb->num_rows;
		
		$users_all = $users_yes + $users_no;
	}
}

	//convert date format
	$date_sent = date('D j M Y', $rsvp_sent);
	$time_sent = date('g:i a', $rsvp_sent);
	//if an RSVP has already been sent
		if ($rsvp_check!=NULL) {						
			echo '<p>RSVP Email sent for this event on <br /><strong>'.$date_sent.' at '.$time_sent.' GMT</strong></p>';
			echo '<p>So far, <strong>'.$users_all.' </strong>people have replied - </p><p>(<span style="color:green"><strong> '.$users_yes.'</strong> yes</span><span style="color:red;">, <strong>'.$users_no. '</strong> no</span>)</p>';

	$rsvp_users_replied = $wpdb -> get_results ("SELECT user from sg_em_rsvprcvd WHERE event=$event_id");

$userlist=array();
			foreach ($rsvp_users_replied as $resend_special) {
				$userlist[]=$resend_special->user;
			}

$csv=implode(",",$userlist);

			//if any RSVPs were sent before the last one
			if ($rsvp_prevsends != NULL) {
			echo '<p>Previous RSVPs for this event were sent on:';
			echo '<ul>';
				foreach ($rsvp_prevsends as $prevsent) {
				echo '<li>'.date('D j M Y', $prevsent->sent_date).' ('.date('g:i a', $prevsent->sent_date).')</li>';
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
}

// action to perform on Save
add_filter ('em_event_save', 'rsvp_processing',1,1);

//Process RSVPs depending on results of $_POST value
function rsvp_processing ($result) {
	//time which will be used to differentiate rsvps and resent rsvps. This will also be the main key to identify current RSVP to users and link to rsvp results table
	$the_time = time();
	//global variables again for DB connections and Events
	global $wpdb;
	global $EM_Event;
	//current event'd ID
	$event_id = $EM_Event->id;
	//check value returned from checkboxes in rsvp_box
	$rsvp_status = $_POST['rsvp_status'];
		
		//if no rsvp has been sent yet, adn one is dues to be sent
		if ($rsvp_status == 'send_rsvp') {
			//simply insert the details of this event and the timestamp
			$wpdb->insert (
			'sg_em_rsvpsent',
			array (
				'event' => $event_id,
				'sent_date' => $the_time,
				)
			);
			rsvp_email($event_id, $the_time, $rsvp_status, $users_y, $users_n);
		}
		//if an RSVP has already been sent and another one needs to be sent
		elseif ($rsvp_status == 'resend_rsvp') {
			//flag sg_em_rsvpsent resent to show that a new rsvp has been sent
			$wpdb->query (
			"
			UPDATE sg_em_rsvpsent
			SET resent = 1
			WHERE event = $event_id
			AND resent = 0
			"
			);
		// write new rsvp entry using current timestamp - same query as for first rsvp
			$wpdb->insert (
			'sg_em_rsvpsent',
			array (
				'event' => $event_id,
				'sent_date' => $the_time,
				)
			);
			rsvp_email($event_id, $the_time, $rsvp_status, $users_y, $users_n);
		}
	// go back to standard saving process	
	return $result;
}

function rsvp_email($event_id, $the_time, $rsvp_status, $users_y, $users_n) {
global $wpdb;
$EM_Event = em_get_event($event_id);
$event_name = $EM_Event->output('#_EVENTLINK');
$event_location = $EM_Event->output('#_LOCATIONNAME');
$event_dates = $EM_Event->output('#_EVENTDATES');
$event_time = $EM_Event->output('#_EVENTTIMES');
$event_category = $EM_Event->output('#_CATEGORYNAME');
$event_contact = $EM_Event->output('#_CONTACTNAME');
$event_notes = $EM_Event->output('#_EVENTNOTES');
$event_url = $EM_Event->output('#_EVENTURL');

$rsvp_url = plugins_url('em-rsvp');

/*
echo $event_id;
echo $the_time;
echo $rsvp_status;
*/
//insert header for HTML emails
add_filter('wp_mail_content_type',create_function('', 'return "text/html";'));

if ($rsvp_status == 'send_rsvp') {
//get email addresses and user_ids to send to for first RSVP
$rsvp_users = get_users ();

	foreach ($rsvp_users as $rsvp_user) {
	//message for first RSVP being sent
$rsvp_header = '[SG] A new event has been listed. Can you make it?';
$rsvp_msg = 
<<<MSG
<p>A new event has been listed on the Samba website. Please let us know if you can make it.</p>
<h3>Event Name: $event_name</h3>
<h4>Date: $event_dates</h4>
<h4>Time: $event_time</h4>
<h4>Location: $event_location</h4>
<h4>Category: $event_category</h4>
<br/>
<a class="$rsvp_status" href="$rsvp_url/rsvp_handler.php?event_id=$event_id&the_time=$the_time&user_id=$rsvp_user->ID&attendance=1">Yes! I'm in!</a><br />
<a class="$rsvp_status" href="$rsvp_url/rsvp_handler.php?event_id=$event_id&the_time=$the_time&user_id=$rsvp_user->ID&attendance=0">Nope, sorry got better things to do</a>
<br/>
<p>Note: If you don't reply to this email or change your mind later, you can always update your status on the event's <a href="$event_url">web page.</a></p>
<br/>
<p>Event Details: $event_notes</p>
<br/>
<p>(This event was sent by $event_contact.)</p>
MSG;
		wp_mail ($rsvp_user->user_email, $rsvp_header, $rsvp_msg);
	}
}
elseif ($rsvp_status == 'resend_rsvp') {
	//get user ids of those who have replied to previous RSVPs
	$rsvp_users_replied = $wpdb -> get_results ("SELECT user from sg_em_rsvprcvd WHERE event=$event_id");

	//create an array that just has the user ids
	$userlist=array();
			foreach ($rsvp_users_replied as $resend_special) {
				$userlist[]=$resend_special->user;
			}
	//implode the list and separate with commas so that we can insert it into the get_users argument
	$userlist=implode(",",$userlist);

//get user details for those who HAVE NOT already replied
	$rsvp_users_resend_simple = get_users ('exclude='.$userlist);
foreach ($rsvp_users_resend_simple as $rsvp_user_resend_simple) {
//message for RSVP being resent
$rsvp_resend_header = '[SG] An event\'s details have changed. Please ignore previous emails';
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
<a class="$rsvp_status" href="$rsvp_url/rsvp_handler.php?event_id=$event_id&the_time=$the_time&user_id=$rsvp_user->ID&attendance=1">Yes! I'm in!</a><br/>
<a class="$rsvp_status" href="$rsvp_url/rsvp_handler.php?event_id=$event_id&the_time=$the_time&user_id=$rsvp_user->ID&attendance=0">Nope, sorry got better things to do</a>
<br/>
<p>Note: If you don't reply to this email or change your mind later, you can always update your status on the event's <a href="$event_url">web page.</a></p>
<br/>
<p>Event Details: $event_notes</p>
<br/>
<p>(This event was sent by $event_contact.)</p>
MSG;
		wp_mail ($rsvp_user_resend_simple->user_email, $rsvp_resend_header, $rsvp_resend_msg);
}

//get user details for those who HAVE already replied

	$rsvp_users_resend_special = get_users ('include='.$userlist);

foreach ($rsvp_users_resend_special as $rsvp_user_resend_special) {
//message for RSVP being resent. Special alert for people who have already replied
$rsvp_special_header = '[SG]Event changed! Please update your RSVP status';
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
<a class="$rsvp_status" href="$rsvp_url/rsvp_handler.php?event_id=$event_id&the_time=$the_time&user_id=$rsvp_user->ID&attendance=1">Yes! I'm in!</a><br />
<a class="$rsvp_status" href="$rsvp_url/rsvp_handler.php?event_id=$event_id&the_time=$the_time&user_id=$rsvp_user->ID&attendance=0">Nope, sorry got better things to do</a>
<br/>
<p>Note: If you don't reply to this email or change your mind later, you can always update your status on the event's <a href="$event_url">web page.</a></p>
<br/>
<p>Event Details: $event_notes</p>
<br/>
<p>(This event was sent by $event_contact.)</p>
MSG;
		wp_mail ($rsvp_user_resend_special->user_email, $rsvp_special_header, $rsvp_special_msg);
}

}

}

?>