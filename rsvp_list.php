<?php

function rsvp_add_stylesheets() {
$plugins_url = plugins_url('em-rsvp');
	wp_register_style(				
		'rsvp',				
		plugins_url('em-rsvp') . "/css/rsvp.css",
		false,
		'all'
	);
	wp_enqueue_style(				
		'rsvp'
	);
}
add_action( 'wp_enqueue_scripts', 'rsvp_add_stylesheets' );

function add_ticklistscript(){
	$pluginsurl = plugins_url ('em-rsvp');
    wp_enqueue_script( 'rsvp_ticklist', $pluginsurl.'/js/rsvp_ticklist.js', array( 'jquery' ) );
    wp_localize_script( 'rsvp_ticklist', 'ajax_object',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' )) );
}
add_action( 'init', 'add_ticklistscript' );


function rsvp_ticklist ( $content ){
			global $post;
			global $wpdb;
			global $EM_Event;
			global $current_user;


	//check that page is a single EVENT and user is logged in (may want to change type of user too)
	if( is_single() && $post->post_type == 'event' && is_user_logged_in () ){

		$post_id = $post->ID;
		$event_info = EM_Events::get(array ('post_id'=>$post_id));
		$event_info = $event_info[0];
		$event_id = $event_info->event_id;

		get_currentuserinfo();
		$user_id = $current_user->ID;
		$first_name = $current_user->user_firstname;
		$user_name = $current_user->user_login;

		$rsvp_responses = rsvp_responses ($event_id);

		//if an RSVP request has been sent for this event
		if ($rsvp_responses != NULL) {
			echo '<p>Hi '.$first_name.', have a look below to see whether you\'re marked as coming to this event or not. <br />Click your name to cycle through the options.</p><hr />';


			// get the responses for the current event
			$rsvp_sent = $rsvp_responses['current_rsvp'];
			$rsvp_yes = $rsvp_responses['yes'];
			$rsvp_no = $rsvp_responses['no'];
			$rsvp_maybe = $rsvp_responses['maybe'];
			//insert content BEFORE the table
			print_r($content);

			$instrument_field_id = xprofile_get_field_id_from_name('Your instrument');
	
			// First get all the users that should be displayed (based on roles in this case)
			$roles = array('samba_admin', 'samba_player', 'samba_editor');
			$rsvp_user_list_roles = array();
			foreach ($roles as $role) {
				
				$userargs = array(
					'role' => $role,
					'fields' => array('ID')
					);
				$users = get_users( $userargs );

				foreach ($users as $user) {
					array_push($rsvp_user_list_roles, $user);
				}
			}

			$rsvp_user_list = array();

			// then get other metadata for them

			foreach ($rsvp_user_list_roles as $user) {
				// get instrument played
				$instruments_query = $wpdb->get_results( 
						"
						SELECT value
						FROM sg_bp_xprofile_data 
						WHERE field_id = $instrument_field_id 
						AND user_id = $user->ID
						", ARRAY_A
					);

					if (isset($instruments_query[0]['value']) == NULL) {
						$instrument = "--not specified--";
					}
					else {
						$instrument = $instruments_query[0]['value'];
					}

				// check whether user has RSVP'd, and what they have replied
				$yes = array_search($user->ID, $rsvp_yes);
				$no = array_search($user->ID, $rsvp_no);
				$maybe = array_search($user->ID, $rsvp_maybe);

				if ($yes > -1) {
					$attendance = 'yes';
				}
				elseif ($no > -1) {
					$attendance = 'no';
				}
				else {
					$attendance = 'maybe';
				}
				// get other usermeta
				$usermeta = get_userdata( $user->ID );	

				$nonce = wp_create_nonce( 'rsvp-nonce' );

				// make user_details array to insert into master array
				$user_details=array();
				$user_details['id'] = $user->ID;
				$user_details['firstname'] = $usermeta->user_firstname;
				$user_details['lastname'] = $usermeta->user_lastname;
				$user_details['instrument'] = $instrument;
				$user_details['attendance'] = $attendance;
				// $user_details['roles'] = $user->roles;

				array_push($rsvp_user_list, $user_details);
			}
			$rsvp_user_list_lastname = arraysort($rsvp_user_list, 'lastname', true, false);
			$rsvp_user_list_instrument = arraysort($rsvp_user_list, 'instrument', true, false);
			$rsvp_user_list = arraysort($rsvp_user_list, 'firstname', true, false);

				
?>
<hr />
<p>Order by:</p><ul class="nav nav-tabs">
  <li class="active"><a href="#first" data-toggle="tab">First Name</a></li>
  <li><a href="#last" data-toggle="tab">Last Name</a></li>
  <li><a href="#instrument" data-toggle="tab">Instrument</a></li>
</ul>



<div class="row">
	<div id="rsvp_ticklist" class="tab-content span9">
		<?php
		$rsvp_js_meta = array(
			'event_id' => $event_id,
			'rsvp_sent' => $rsvp_sent,
			'nonce' => $nonce
			);
		rsvp_list ($rsvp_user_list, 'first', 'active', $rsvp_js_meta);
		rsvp_list ($rsvp_user_list_lastname, 'last', '', $rsvp_js_meta);
		rsvp_list_instr ($rsvp_user_list_instrument, 'instrument', $rsvp_js_meta);
		?>
	</div>
</div>

<?php
		}
		// if NO RSVP has been requested
		else {
			return $content;
		}
	}
	// if NOT single event page
	else {
		return $content;
	}	
}
add_filter('the_content','rsvp_ticklist',11,1);

function rsvp_list ($sortedarray, $tag, $active, $rsvp_js_meta) {
			echo '<div class="tab-pane '.$active.'" id="'.$tag.'">';
			echo '<ul class="ticklist">';	

			foreach ($sortedarray as $muso) {
				rsvp_user_box ($muso, $rsvp_js_meta);
			}
			
			echo '</ul></div>';

}

function rsvp_list_instr($sortedarray,$tag, $rsvp_js_meta) {
//Make new arays of instruments, dancers, no_instrument users and unspecified instruments
				$instruments = array();
		
				$noinstrument = array();
				foreach ($sortedarray as $instrument_value) {
					if ($instrument_value['instrument']=='Dancer'){
						continue;
					}
					if ($instrument_value['instrument']=='--not specified--') {
						array_push($noinstrument, $instrument_value['instrument']);
					}
					else {
					array_push($instruments, $instrument_value['instrument']);
					}
				}
				$instruments = array_unique($instruments);
			echo '<div class="tab-pane '.$tag.'" id="'.$tag.'">';
			echo '<ul class="ticklist">';
//No instrument selected loop
			if ($noinstrument != NULL) {
			echo '<h5>No instrument specified - Please select your instrument in your profile</h5>';
			    foreach($sortedarray as $muso){
			    	if(($muso['instrument'])==('--not specified--')){
			            rsvp_user_box ($muso, $rsvp_js_meta);
			        }
		   		 }
		   	}
//Main instrument loop
		foreach($instruments as $instrument){
		   	echo '<h5>'.$instrument.'</h5>';
			    foreach($sortedarray as $muso){
			    	if(($muso['instrument'])==($instrument)){
			            rsvp_user_box ($muso, $rsvp_js_meta);
			        }
		   		 }
		}
//Dancers loop
			echo '<h5>Dancers</h5>';
			foreach($sortedarray as $muso){
			    	if(($muso['instrument'])==('Dancer')){
			            rsvp_user_box ($muso, $rsvp_js_meta);
			        }
		   		 }		
}

function rsvp_user_box ($muso, $rsvp_js_meta) {
			//foreach ($sortedarray as $rsvp_user) {
// print_r($muso);

			global $current_user;
			$user_id = $current_user->ID;
			$rsvp_sent = $rsvp_js_meta['rsvp_sent'];
			$event_id = $rsvp_js_meta['event_id'];
			$nonce = $rsvp_js_meta['nonce'];
				$avatar = get_avatar( $muso['id'], 30,'',$muso['firstname'] );
				if ($user_id==$muso['id'] || current_user_can( 'edit_events' )){
				$canedit = 'canedit';
				}
				else {
				$canedit = 'noedit';					
				}

				$att = $muso['attendance'];
				$state='maybe';
				if ($att=='no'){
					$state='no';
				} elseif ($att=='yes'){
					$state = 'yes';
				}
				echo '<li class="well rsvp_user '.$state.' '.$canedit.'" data-eventid="'.$event_id.'" data-userid="'.$muso['id'].'" data-sentdate="'.$rsvp_sent.'" data-nonce="'.$nonce.'"

				><span class="avatar">'.$avatar.'</span><span class="username">'.$muso['firstname'].' '.$muso['lastname'].'</span><span class="instrument">'.$muso['instrument'].'</span>';

				echo '</li>';
			//}

}

?>