<?php
/**
 * Example Widget Class
 */
class rsvp_widget extends WP_Widget {


  /** constructor -- name this the same as the class above */
  function rsvp_widget() {
    parent::WP_Widget(false, $name = 'RSVP Widget');  
  }

  /** @see WP_Widget::widget -- do not rename this */
  function widget($args, $instance) { 

    
    extract( $args );
    global $EM_Event;
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $roles = $current_user->roles;
    $rsvp_roles = array('samba_admin', 'samba_player', 'samba_editor');
    $rsvp_user_check = array_intersect($roles, $rsvp_roles);

    if (!empty($rsvp_user_check)) {
      $is_rsvp_user = 'yes';
    }
    else {
      $is_rsvp_user = 'no';
    }
    

    $title    = apply_filters('widget_title', $instance['title']);
    $message  = $instance['message'];
    ?>
    <?php echo $before_widget; ?>
    <?php if ( $title )
    echo $before_title . $title . $after_title; ?>
    <?php if ($is_rsvp_user=='yes') {
      echo '<p>'.$message.'</p>';
      
      }
      else {
        echo '<span style="color:orange;">Your user type does not get to tick ticklists. If you think this is wrong, please let the weblords know.</span>';
      }
    ?>
    

    <?php $e_l = EM_Events::get(); 
    $nonce = wp_create_nonce( 'rsvp-nonce' );

    ?>
    <table cellpadding="0" cellspacing="0" class="events-table table table-striped table-hover">
<!--     <thead>
        <tr>
      <th class="event-time" width="*">Event Details</th>
      <th class="event-description" width="10">RSVP</th>
    </tr>
  </thead> -->
  <tbody>
    <?php foreach ($e_l as $event) {
        // $event_id = $event['event_id'];
        // $event_info = EM_Events::get(array ('event'=>$event_id));
        // $event_info = $event_info[0];
      $event_location=$event->get_location();

      $event_start = $event->start;
      $event_start_date = date('l jS M Y', $event_start);

      if ($event->event_all_day == "1") {
        $event_start_time = "All Day";
      }
      else {
      $event_start_time = date('g:i a', $event_start);
      }

      $rsvp_sent = get_post_meta( $event->id, 'rsvp_current', true );

      $response = rsvp_user_response($event->event_id, $user_id);
      ?>
      <tr>
        <td>
          <p><a href="<?php echo $event->guid; ?>" title="<?php echo $event->event_name; ?>"><?php echo $event->event_name; ?></a></p>
          <i><?php if ($event_location->location_name) {
            echo $event_location->location_name.', '.$event_location->location_town;
          }
          else { echo '[No location defined]';}
          ?></i><br />
          <strong>Starts: </strong><span class="pull-right"><?php echo $event_start_time; ?> </span><br />
          <strong>On: </strong><span class="pull-right"><?php echo $event_start_date; ?> </span>
       <br /><br />
 <?php if ($is_rsvp_user == 'yes') {
   
       if ($rsvp_sent) {  ?>
          <p><strong>Can you come?</strong><a class="rsvp_user <?php echo $response; ?> canedit pull-right rsvp_widget" data-eventid="<?php echo $event->id; ?>" data-userid="<?php echo $user_id; ?>" data-sentdate="<?php echo $rsvp_sent; ?>" data-nonce="<?php echo $nonce; ?>"
            >&nbsp;</a></p>
            <?php } 
            else { echo '<span class="pull-right"><em>no RSVP required</em></span>';} 

        }
            ?>

          </td>
        </tr>

        <?php
      }
      ?>
        </tbody></table>
        <?php echo $after_widget; ?>
        <?php
      }

      /** @see WP_Widget::update -- do not rename this */
      function update($new_instance, $old_instance) {   
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['message'] = $new_instance['message'];
        return $instance;
      }

      /** @see WP_Widget::form -- do not rename this */
      function form($instance) {  

        $title    = esc_attr($instance['title']);
        $message  = esc_attr($instance['message']);
        ?>
        <p>
          <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> 
          <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
        <p>
          <label for="<?php echo $this->get_field_id('message'); ?>"><?php _e('Simple Message'); ?></label> 
          <textarea class="widefat" id="<?php echo $this->get_field_id('message'); ?>" name="<?php echo $this->get_field_name('message'); ?>" type="text" rows="10"><?php echo $message; ?></textarea>
        </p>
        <?php 
      }


} // end class rsvp_widget
add_action('widgets_init', create_function('', 'return register_widget("rsvp_widget");'));
?>