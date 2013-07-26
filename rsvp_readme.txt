to install on a new server, create tables as follows where xx is your DB prefix

Table: xx_em_rsvprcvd 

id		int(11) 	Auto Increment	 
event		int(11)	 
timestamp	int(20)	 
user		int(11)	 
attendance	tinyint(4)

Table: xx_em_rsvprcvd

id		int(11) 	Auto Increment	 
event		int(11)	 
sent_date	int(20)	 
resent		tinyint(4)	 


In rsvp.php, rsvp_list.php, rsvp_list_handler.php, rsvp_handler.php
Search and replace wp_em_rsvp for xx_em_rsvp

In rsvp_list.php
Replace wp_users with xx_users

In rsvp_list_handler.php
Make sure path is correct. Going directly to this page should return 'OK'.
require_once($_SERVER['DOCUMENT_ROOT'].'/wp-blog-header.php');