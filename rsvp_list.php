<?php

$plugins_url = plugins_url('em-rsvp');

wp_enqueue_style(				
	'rsvp', plugins_url('em-rsvp') . "/css/rsvp.css"
);

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


		//check if current event has RSVP request
		$rsvp_sent = $wpdb->get_row( "SELECT * FROM sg_em_rsvpsent WHERE event=$event_id AND resent = 0");
		//if so show RSVP table, else return to default behaviour
		$rsvp_sentdate = $rsvp_sent->sent_date;
		//if an RSVP request has been sent for this event
		if ($rsvp_sent != NULL) {
			//insert content BEFORE the table
			print_r($content);
			//check if current user has replied
			//$user_rsvp_status = $wpdb->get_row( "SELECT * FROM sg_em_rsvprcvd WHERE event=$event_id AND user = $user_id");

			//get the data we need to display RSVP info. User ID from wp_users, first and last name from usermeta, instrument form bp_xprofile and attendance from sg_em_rsvprcvd
			
			$rsvp_user_list = $wpdb->get_results( 
				$wpdb->prepare( 
					"SELECT u.ID AS 'id', um1.meta_value AS 'firstname', um2.meta_value AS 'lastname', CASE WHEN i.value IS NULL THEN '--not specified--' ELSE i.value END AS 'instrument', r.attendance, r.event, um3.meta_value AS 'role'
					FROM $wpdb->usermeta um3
					LEFT JOIN $wpdb->users u ON (u.ID = um3.user_id)
					LEFT JOIN $wpdb->usermeta um1 ON (u.ID = um1.user_id AND um1.meta_key = 'first_name')
					LEFT JOIN $wpdb->usermeta um2 ON (u.ID = um2.user_id AND um2.meta_key = 'last_name')
					LEFT JOIN sg_bp_xprofile_data i ON (u.ID = i.user_ID AND i.field_id = 2)
					LEFT JOIN sg_em_rsvprcvd r ON (u.ID = r.user and r.event = $event_id)
					WHERE um3.meta_key = 'sg_capabilities' AND um3.meta_value LIKE '%%samba%%'
					ORDER BY firstname
					"
			        )
			);
			//sorting function lives in rsvp_functions.php
			$rsvp_user_list_lastname = arraysort($rsvp_user_list, 'lastname', true, true);
			$rsvp_user_list_instrument = arraysort($rsvp_user_list, 'instrument', true, true);

				echo '<p>Hi '.$first_name.', have a look below to see whether you\'re marked as coming to this event or not. <br />Click your name to cycle through the options.</p>';

?>
<hr />
<p>Order by:</p><ul class="nav nav-tabs">
  <li class="active"><a href="#first" data-toggle="tab">First Name</a></li>
  <li><a href="#last" data-toggle="tab">Last Name</a></li>
  <li><a href="#instrument" data-toggle="tab">Instrument</a></li>
</ul>
<?php


echo '<div class="row">';
echo '<div class="tab-content span9">';
rsvp_list ($rsvp_user_list, 'first', 'active');
rsvp_list ($rsvp_user_list_lastname, 'last');
rsvp_list_instr ($rsvp_user_list_instrument, 'instrument');
echo '</div>';
echo '</div>';

//			echo '<p><pre>';
//			print_r($rsvp_user_list_instrument);
//			echo '</pre>';

?>
		

<script>
//javascript for changing user RSVP status

	var base_url ='<?php echo plugins_url('em-rsvp') ?>';

	jQuery(function (){
			jQuery('.rsvp_user').on('click',function(ev){
				var t = jQuery(this);
					if (t.hasClass('canedit')){
						if (t.hasClass('attend')){
							switchState(t,'attend','non-attend');
						} else if (t.hasClass('non-attend')){
							switchState(t,'non-attend','not-sure');
						} else if (t.hasClass('not-sure')){
							switchState(t,'not-sure','attend');
						}
					}
			});
		}
	);


function switchState(that,current,next){
		var id=that.attr('id');
		var e_id='<?php echo $event_id ?>';
		var sent_date='<?php echo $rsvp_sentdate ?>';
	jQuery.get(base_url+'/rsvp_list_handler.php?id='+id+'&e_id='+e_id+'&sent='+sent_date+'&state='+next,function(data, status){
		console.log('status',status);
		if (data==='OK')that.removeClass(current).addClass(next);
	})
}

</script>

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

function rsvp_list ($sortedarray, $tag, $active) {
			echo '<div class="tab-pane '.$active.'" id="'.$tag.'">';
			echo '<ul class="ticklist">';				
			foreach ($sortedarray as $muso) {
			rsvp_user_box ($muso);
			}
			
			echo '</ul></div>';

}

function rsvp_list_instr($sortedarray,$tag) {
//Make new arays of instruments, dancers, no_instrument users and unspecified instruments
				$instruments = array();
				$noinstrument = array();
				foreach ($sortedarray as $instrument_value) {
					if ($instrument_value->instrument=='Dancer'){
						continue;
					}
					if ($instrument_value->instrument=='Any instrument?' || $instrument_value->instrument=='--not specified--') {
						array_push($noinstrument, $instrument_value->instrument);
					}
					else {
					array_push($instruments, $instrument_value->instrument);
					}
				}
				$instruments = array_unique($instruments);
			echo '<div class="tab-pane '.$state.'" id="'.$tag.'">';
			echo '<ul class="ticklist">';
//No instrument selected loop
			if ($noinstrument != NULL) {
			echo '<h5>No instrument specified - Please select your instrument in your profile</h5>';
			    foreach($sortedarray as $muso){
			    	if(($muso->instrument)==('--not specified--')){
			            rsvp_user_box ($muso);
			        }
		   		 }
		   	}
//Main instrument loop
		foreach($instruments as $instrument){
		   	echo '<h5>'.$instrument.'</h5>';
			    foreach($sortedarray as $muso){
			    	if(($muso->instrument)==($instrument)){
			            rsvp_user_box ($muso);
			        }
		   		 }
		}
//Dancers loop
			echo '<h5>Dancers</h5>';
			foreach($sortedarray as $muso){
			    	if(($muso->instrument)==('Dancer')){
			            rsvp_user_box ($muso);
			        }
		   		 }		
}

function rsvp_user_box ($muso) {
			//foreach ($sortedarray as $rsvp_user) {
				$avatar = get_avatar( $muso->id, 30,'',$muso->firstname );
				if ($user_id==$muso->id || current_user_can( 'manage_options' )){
				$canedit = 'canedit';
				}
				else {
				$canedit = 'noedit';					
				}

				$att = $muso->attendance;
				$state='not-sure';
				if ($att=='0'){
					$state='non-attend';
				} elseif ($att=='1'){
					$state = 'attend';
				}
				echo '<li id="user'.$muso->id.'" class="well rsvp_user '.$state.' '.$canedit.'"><span class="avatar">'.$avatar.'</span><span class="username">'.$muso->firstname.' '.$muso->lastname.'</span><span class="instrument">'.$muso->instrument.'</span>';

				echo '</li>';
			//}

}

?>