<?php
namespace EM_RSVP\Ticklist;

Class Ticklist {

	public $debug = false;
	public $debug_users = array(
		'5' => 'stefwilliams@gmail.com',
	);

	public function __construct() {
		Post_Type::hookup();
	}
}


