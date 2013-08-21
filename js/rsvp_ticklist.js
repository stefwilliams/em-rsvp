//javascript for changing user RSVP status

	
	// '<?php echo plugins_url('em-rsvp') ?>';

	jQuery(function (){
			jQuery('.rsvp_user').on('click',function(ev){
				var t = jQuery(this);
					if (t.hasClass('canedit')){
						if (t.hasClass('yes')){
							switchState(t,'yes','no');
						} else if (t.hasClass('no')){
							switchState(t,'no','maybe');
						} else if (t.hasClass('maybe')){
							switchState(t,'maybe','yes');
						}
					}
			});
		}
	);


function switchState(that,current,next){
	// var base_url = jQuery('#rsvp_ticklist').data('baseurl');
		var nonce=that.data('nonce');
		var id=that.attr('id');
		var e_id=that.data('eventid');
		var sent_date= that.data('sentdate');
		console.log(nonce);

    var data = {
    action: "rsvp_ticklist_handler",
    id: id,
    e_id: e_id,
    sent: sent_date,
    state: next,
    nonce: nonce
  };
  jQuery.post(ajax_object.ajax_url, data, function(response) {
  	console.log(that);
	if (response==='OK')that.removeClass(current).addClass(next);
  });
}