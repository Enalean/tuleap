<?php

// load initial configuration
require_once(dirname(__FILE__)."/jabbex_pre.php");
require_once(dirname(__FILE__)."/JabbexInterface.php");


class Jabbex implements JabbexInterface
{
	private $jabber_server_conf;	// Server configuration
	private $jab; 					// Jabber server 'translator'
	private $handler; 				// Event handler

	private $group_mng;				// Manage shared groups creation

	private $check_parameters;		// Parameters sanitize

	/*
	 * Initialize configuration
	 */
	public function __construct($session) {
		$session = trim($session);
		if(!isset($session) || !is_string($session) || empty($session)){
			throw new Exception("Invalid session argument. Unable to instantiate JabbeX.",3000);
		}

		//echo "JABBEX'S BEEN CREATED <br>";

		// create an instance of the Jabber class
		$display_debug_info = true; // Use this flag to __debug__
		$this->jab = new Jabber($display_debug_info);

		$this->check_parameters = new CheckParameters();

		// create an instance of the event handler class
		$this->handler = new EventHandler($this->jab);

		// set handlers for the events we wish to be notified about
		$this->jab->set_handler("connected",$this->handler,"handle_connected");
		$this->jab->set_handler("authenticated",$this->handler,"handle_authenticated");
		$this->jab->set_handler("authfailure",$this->handler,"handle_auth_failure");
		$this->jab->set_handler("disconnected",$this->handler,"handle_disconnected"); // connection lost unexpectedly
		$this->jab->set_handler("timeout",$this->handler,"handle_timeout"); // connection closed
		$this->jab->set_handler("error",$this->handler,"handle_error");
		//////////////////

		// Create resource based on session id md5 hash
		if( !defined("JABBER_RESOURCE") ) define("JABBER_RESOURCE", md5( $session.rand() ) );

		return true;

	}

	function __destruct() {
		// Check whether we're connected or not.
		if( isset($this->handler) && $this->handler->get_authenticated() ){
			// And if so... disconnect from the Jabber server
			$this->jab->disconnect();
		}

		return true;
	}

	/*
	 * Connects JabbeX to the jabber server.
	 */
	function _jabber_connect(){
		// Connects to the jabber server if we're not connected.
		if(!$this->handler->get_authenticated()){
			// connect to the Jabber server
			if (!$this->jab->connect(JABBER_SERVER_DNS)) {
				throw new Exception("Unable to connect to the Jabber server.",3001);
			}

			$this->jab->execute(CBK_FREQ,RUN_TIME);
		}

		return true;
	}

	/***
	 ***	Core methods
	 ***/


	/*
	 * Create a permanent Multi-User Chat Room on the default jabber server used by JabbeX.
	 * Returns true if success
	 *
	 * @param muc_room_short_name 	  string Used to create the room's JID. Mapped from project's "Short Name".
	 * @param muc_room_full_name 	  string Displayed on the Rooms Directory. Mapped from project's "Full Name".
	 * @param muc_room_description 	  string Short description of the Room. Mapped from project's "Short Description".
	 * @param muc_room_owner_username string The username of who's creating the room. Mapped from project owner.
	 */
	public function create_muc_room($muc_room_short_name, $muc_room_full_name, $muc_room_description, $muc_room_owner_username)
	{

		//echo "CREATE MUC HAS BEEN CALLED <br>";

		if( !$this->check_parameters->NotEmptyString($muc_room_short_name, $muc_room_full_name, $muc_room_description, $muc_room_owner_username) ){
			throw new Exception("Invalid string parameter.",3002);
		}

		$muc_room_short_name = strtolower($muc_room_short_name);
		$muc_room_owner_username = strtolower($muc_room_owner_username);

		// Check if the muc room already exists and if so call unlock.
		if($this->_muc_exists($muc_room_short_name)){
			$this->unlock_muc_room($muc_room_short_name);
		}
		// If it doesn't exist, let's create it!
		else{

			$this->_jabber_connect();

			$this->handler->set_muc_room_info($muc_room_short_name, $muc_room_full_name, $muc_room_description, $muc_room_owner_username);
			//		$this->jab->set_handler("authenticated",$this->handler,"create_muc_room");
			$this->handler->create_muc_room();
			// now, tell the Jabber class to begin its execution loop
			$this->jab->execute(CBK_FREQ,RUN_TIME);

			// Note that we will not reach this point (and the execute() method will not
			// return) until $jab->terminated is set to TRUE.  The execute() method simply
			// loops, processing data from (and to) the Jabber server, and firing events
			// (which are handled by our EventHandler class) until we tell it to terminate.


		}

		return true;
	}


	/*
	 * Auxiliary function for lock_muc_room and unlock_muc_room.
	 */
	private function _muc_locker($muc_room_short_name, $lock = true)
	{
		if( !$this->check_parameters->NotEmptyString($muc_room_short_name) ){
			throw new Exception("Invalid string parameter.",3002);
		}

		// Check if the room exists.
		if(!$this->_muc_exists($muc_room_short_name)){
			throw new Exception("Invalid MUC room $muc_room_short_name.",3019);
		}


		$locker = $lock ? 1:0;
		$lockmuc_pwd = md5(JABBER_LOCKMUC_PWD);


		$this->handler->set_muc_room_info($muc_room_short_name, null, null, null);

		$this->_jabber_connect();

		if( !$this->jab->set_presence("","") ){
			throw new Exception("Unable to send presence to the Jabber server.",3004);
		}

		/*
		 * Send presence to the conference service to join
		 * the room.
		 */
		$from = JABBER_USERNAME."@".JABBER_SERVER_DNS."/".JABBER_RESOURCE;
		$to = $muc_room_short_name."@".CONFERENCE_SERVICE_NAME.".".JABBER_SERVER_DNS."/".JABBER_USERNAME;

		$payload = " <x xmlns='http://jabber.org/protocol/muc'>";
		$payload .= "<password>$lockmuc_pwd</password>";
		$payload .= "</x>";

		// We're unlocking the room
		if(!$lock){
			$lockmuc_pwd = "";
		}

		$xml = "<presence";
		$xml .= " from='$from'";
		$xml .= " to='$to'";
		$xml .= " type='MUC'";
		$xml .= ">\n";
		$xml .= "$payload \n";
		$xml .= "</presence>\n";

		/*
		 * When sending the presence to the room we receive the same response as when
		 * creating a new room.
		 */
		$this->jab->set_handler("muc_join",$this->handler,"handle_muc_room_join");


		if (!$this->jab->_send($xml)) {
			throw new Exception("Unable to communicate with the Jabber server.",3005);
			return false;
		}

		// Send config form to un/set a pwd
		////////////////////////////////
		$this->handler->set_muc_room_configured(true);
		$iq_id	= $this->jab->_unique_id("muc"); // get an uniq ID to the packet.
		$this->jab->_set_iq_handler("on_generic_iq",$iq_id); // define a method of Jabber class to treat the pck.
		$this->jab->set_handler("generic_iq",$this->handler,"handle_muc_room_config");


		$xml = "<iq type=\"set\" id=\"$iq_id\" from=\"$from\" to=\"$to\">";
		$xml .= "<query xmlns=\"http://jabber.org/protocol/muc#owner\"> <x xmlns=\"jabber:x:data\" type=\"submit\">";
		$xml .= "<field var=\"FORM_TYPE\" type=\"hidden\"> <value>http://jabber.org/protocol/muc#roomconfig</value> </field>";
		$xml .= "<field label=\"Password Required to Enter Room\" var=\"muc#roomconfig_passwordprotectedroom\" type=\"boolean\">";
		$xml .= "<value>$locker</value> </field>";
		$xml .= "<field label=\"Password\" var=\"muc#roomconfig_roomsecret\" type=\"text-private\">";
		$xml .= "<value>$lockmuc_pwd</value> </field>";
		$xml .= "</x></query></iq>";


		if (!$this->jab->_send($xml)) {
			throw new Exception("Unable to communicate with the Jabber server.",3005);
			return false;
		}

		$this->jab->execute(CBK_FREQ,RUN_TIME);

		return true;
	}

	/*
	 * Lock a given Multi-User Chat Room on the default jabber server used by JabbeX.
	 * When locked a MUC Room cannot be accessed by any user.
	 *
	 * Returns true if success
	 *
	 * @param muc_room_short_name 	 string Room's JID. Mapped from project's "Short Name".
	 */
	public function lock_muc_room($muc_room_short_name)
	{
		$muc_room_short_name = strtolower($muc_room_short_name);

		$this->handler->set_kick_muc_members(true);
		$ret_val = $this->_muc_locker($muc_room_short_name,true);
		$this->handler->set_kick_muc_members(false);
		return $ret_val;
	}



	/*
	 * Unlock a given Multi-User Chat Room on the default jabber server used by JabbeX
	 * which was previously locked with lockMUCRoom.
	 *
	 * Returns true if success
	 *
	 * @param muc_room_short_name 	 string Room's JID. Mapped from project's "Short Name".
	 */
	public function unlock_muc_room($muc_room_short_name)
	{
		$muc_room_short_name = strtolower($muc_room_short_name);
		return $this->_muc_locker($muc_room_short_name,false);
	}



	/*
	 * Delete a given Multi-User Chat Room on the default jabber server used by JabbeX.
	 *
	 * Returns true if success
	 *
	 * @param muc_room_short_name 	 string Room's JID. Mapped from project's "Short Name".
	 */
	public function delete_muc_room($muc_room_short_name)
	{
		if( !$this->check_parameters->NotEmptyString($muc_room_short_name) ){
			throw new Exception("Invalid string parameter.",3002);
		}

		$muc_room_short_name = strtolower($muc_room_short_name);

		// Check if the room exists.
		if(!$this->_muc_exists($muc_room_short_name)){
			throw new Exception("Invalid MUC room $muc_room_short_name.",3019);
		}


		$this->handler->set_muc_room_info($muc_room_short_name, null, null, null);
		$lockmuc_pwd = md5(JABBER_LOCKMUC_PWD);


		$this->_jabber_connect();

		if( !$this->jab->set_presence("","") ){
			throw new Exception("Unable to send presence to the Jabber server.",3004);
		}

		/*
		 * Send presence to the conference service to join
		 * the room.
		 */
		$from = JABBER_USERNAME."@".JABBER_SERVER_DNS."/".JABBER_RESOURCE;
		$to = $muc_room_short_name."@".CONFERENCE_SERVICE_NAME.".".JABBER_SERVER_DNS."/".JABBER_USERNAME;

		$payload = " <x xmlns='http://jabber.org/protocol/muc'>";
		$payload .= "<password>$lockmuc_pwd</password>";
		$payload .= "</x>";


		$xml = "<presence";
		$xml .= " from='$from'";
		$xml .= " to='$to'";
		$xml .= " type='MUC'";
		$xml .= ">\n";
		$xml .= "$payload \n";
		$xml .= "</presence>\n";

		/*
		 * When sending the presence to the room we receive the same response as when
		 * creating a new room.
		 */
		$this->jab->set_handler("muc_join",$this->handler,"handle_muc_room_join");


		if (!$this->jab->_send($xml)) {
			throw new Exception("Unable to communicate with the Jabber server.",3005);
			return false;
		}

		$this->handler->set_muc_room_configured(true);
		$iq_id	= $this->jab->_unique_id("muc"); // get an uniq ID to the packet.
		$this->jab->_set_iq_handler("on_generic_iq",$iq_id); // define a method of Jabber class to treat the pck.
		$this->jab->set_handler("generic_iq",$this->handler,"handle_muc_room_config");


		// Send msg to destroy the room
		$xml = "<iq from='$from' id='$iq_id' to='$to' type='set'>";
		$xml .= "<query xmlns='http://jabber.org/protocol/muc#owner'>";
		$xml .= "<destroy jid='$to'>";
		$xml .= "<reason>The project associated to this room has been deleted.</reason> </destroy></query></iq>";

		if (!$this->jab->_send($xml)) {
			throw new Exception("Unable to communicate with the Jabber server.",3005);
			return false;
		}

		$this->jab->execute(CBK_FREQ,RUN_TIME);

		return true;
	}

	/*
	 * Grant membership in a muc room for a user.
	 *
	 * Returns true if success
	 *
	 * @param muc_room_short_name 	 string Room's JID. Mapped from project's "Short Name".
	 * @param username				 string username of the user you want to add.
	 */
	public function muc_add_member($muc_room_short_name, $username, $affiliation = "member"){

		if( !$this->check_parameters->NotEmptyString($muc_room_short_name, $username) ){
			throw new Exception("Invalid string parameter.",3002);
		}

		$muc_room_short_name = strtolower($muc_room_short_name);
		$username = strtolower($username);

		// Check if the room exists.
		if(!$this->_muc_exists($muc_room_short_name)){
			throw new Exception("Invalid MUC room $muc_room_short_name.",3019);
		}


		$this->_jabber_connect();

		/*
		 * Load the handler with the only information we have about the room.
		 */
		$this->handler->set_muc_room_info($muc_room_short_name, NULL, NULL, NULL);

		/*
		 * Setting a handler to the response
		 */
		$this->jab->set_handler("generic_iq",$this->handler,"handle_muc_room_config");
		// Indicating that the room we're communicating with is already configured
		$this->handler->set_muc_room_configured(true);

		/*
		 * Set global presence
		 */
		if( !$this->jab->set_presence("","") ){
			throw new Exception("Unable to send presence to the server.",3004);
		}

		/*
		 * Send the presence to the room.
		 */
		$from = JABBER_USERNAME."@".JABBER_SERVER_DNS."/".$this->jab->_resource;
		$to = $muc_room_short_name."@".CONFERENCE_SERVICE_NAME.".".JABBER_SERVER_DNS."/".JABBER_USERNAME;
		$payload = " <x xmlns='http://jabber.org/protocol/muc'/>";

		$xml = "<presence";
		$xml .= " from='$from'";
		$xml .= " to='$to'";
		$xml .= " type='MUC'";
		$xml .= ">\n";
		$xml .= "$payload \n";
		$xml .= "</presence>\n";

		$this->jab->set_handler("muc_join",$this->handler,"handle_muc_room_join");

		if ($this->jab->_send($xml)) {

			/*
			 * Send an iq to the room to add a new member.
			 * After that, we just need to check whether the response was positive or not
			 */
			$payload = "<item affiliation='$affiliation' ";
			$payload .= "jid='".$username."@".JABBER_SERVER_DNS."'/>";
			$iq_id	= $this->jab->_unique_id("muc"); // get an uniq ID to the packet.
			$this->jab->_set_iq_handler("on_generic_iq",$iq_id); // define a method of Jabber class to treat the pck.

			if( !$this->jab->_send_iq($muc_room_short_name."@".CONFERENCE_SERVICE_NAME.".".JABBER_SERVER_DNS ,"set",$iq_id,"http://jabber.org/protocol/muc#admin",$payload,JABBER_USERNAME."@".JABBER_SERVER_DNS."/".JABBER_RESOURCE ) ){
				throw new Exception("Unable to send iq package to the Jabber server.",3006);
			}

			// Fire in the hole!!!!!
			$this->jab->execute(CBK_FREQ,RUN_TIME);
		}
		else{
			throw new Exception("Unable to communicate with the Jabber server.",3005);
		}

		return true;
	}

	/*
	 * Revoke membership of a user in a muc room
	 */
	public function muc_remove_member($muc_room_short_name, $username){
		$muc_room_short_name = strtolower($muc_room_short_name);
		$username = strtolower($username);

		$this->muc_add_member($muc_room_short_name,$username,"none");
	}

	/*
	 * This functions creates a shared group on the server and configures it to
	 * show up on its members' roster.
	 *
	 * Returns true if success
	 *
	 * @param group_short_name 	  	Index of the group. Mapped from project's "Short Name".
	 * @param group_full_name 	  	string Displayed on the Members' roster. Mapped from project's "Full Name".
	 */
	function create_shared_group($group_short_name, $group_full_name)
	{
		if( !$this->check_parameters->NotEmptyString($group_short_name, $group_full_name) ){
			throw new Exception("Invalid string parameter.",3002);
		}

		if(JABBER_GROUP_MNG_ACTIVE){
			//echo "CREATE SHARED GROUP HAS BEEN CALLED <br>";

			$this->_jabber_connect();

			$this->group_mng = new GroupManager();
			$this->group_mng->set_group_full_name($group_full_name);
			$this->group_mng->set_group_short_name($group_short_name);
			$this->group_mng->set_jab_connector($this->jab);

			$this->group_mng->create_shared_group();

			$this->jab->execute(CBK_FREQ,RUN_TIME);

			/*
			 * Note that we will not reach this point (and the execute() method will not
			 * return) until $jab->terminated is set to TRUE.
			 *
			 * ...disconnect from the Jabber server...
			 */
			//		$this->jab->disconnect();
		}
		return true;
	}


	/*
	 * Returns an array with the values of the JabbeX configuration parameters.
	 * The valid array keys are: server_dns, sever_name, username, conference_service, and server_port.
	 */
	function get_server_conf(){
		$ret_val = array(	"server_dns" => JABBER_SERVER_DNS,
							"server_name" => JABBER_SERVER,
							"username" => JABBER_USERNAME,
							"conference_service" => CONFERENCE_SERVICE_NAME,
							"server_port" => JABBER_SERVER_PORT,
							"webadmin_sec_port" => JABBER_WEBADMIN_SEC_PORT,
							"webadmin_unsec_port" => JABBER_WEBADMIN_UNSEC_PORT);
		return $ret_val;
	}

	/*
	 * Get presence status of user identified by $jid.
	 * Return:
	 * - available if user is available for chat;
	 * - unavailable if user is/seems to be offline;
	 * - away
	 * - dnd if user is busy (do not disturb)
	 * - chat if user is chatty;
	 * - xa if user is extended away;
	 * - forbidden if you do not have permission to check user's status;
	 * - false on error.
	 */
	function user_status($jid){
		$filename = "http://".JABBER_SERVER_DNS.":".JABBER_WEBADMIN_UNSEC_PORT."/plugins/presence/status?jid=".$jid."&type=xml";
		if( ! $in_stream = file_get_contents($filename) ){
			return false;
		}

		if( ! $xml = simplexml_load_string($in_stream) ){
			return false;
		}
		$status = array('status' => NULL , 'message' => NULL);
		
		$valid = false;

		// Check if the status is unavailable
		foreach ($xml->attributes() as $attr => $value) {
			if($attr == "type" && $value == "unavailable"){
				return array('status' => "unavailable" , 'message' => NULL);
			}
			else if($attr == "type" && $value == "error"){
				return false;
			}
			// Check whether it's a valid response
			else if($attr == "from" && !empty($value)){
				$valid = true;
			}
		}

		if( isset($xml->status[0]) ){
			$status['message'] = (string) $xml->status[0];
		}

		// Check
		switch($xml->show[0]){
			case "dnd":
				$status['status'] = 'dnd';
				return $status;
			case "away";
				$status['status'] = "away";
				return $status;
			case "chat":
				$status['status'] = "chat";
				return $status;
			case "xa":
				$status['status'] = "xa";
				return $status;
			case "offline":
				$status['status'] = "unavailable";
				return $status;
			case "available":
				$status['status'] = "available";
				return $status;
			case "forbidden":
				$status['status'] = "forbidden";
				return $status;
		}

		if($valid){
			$status['status'] = "available";
			return $status;
		}
		else{
			return false;
		}
	}
	
	/*
	 * Check whether the short room name passed as parameter is an existing room or not.
	 * Return true if so and false if not.
	 */
	function _muc_exists($muc_room_short_name){
		if( !$this->check_parameters->NotEmptyString($muc_room_short_name) ){
			throw new Exception("Invalid string parameter.",3002);
		}

		$muc_room_short_name = strtolower($muc_room_short_name);

		$from = JABBER_USERNAME."@".JABBER_SERVER_DNS."/".JABBER_RESOURCE;
		$to = $muc_room_short_name."@".CONFERENCE_SERVICE_NAME.".".JABBER_SERVER_DNS;


		$this->_jabber_connect();

		$iq_id	= $this->jab->_unique_id("muc"); // get an uniq ID to the packet.
		$this->jab->_set_iq_handler("on_generic_iq",$iq_id); // define a method of Jabber class to treat the pck.
		$this->jab->set_handler("generic_iq",$this->handler,"handle_muc_room_discovery");


		$xml = "<iq type=\"get\" id=\"$iq_id\" from=\"$from\" to=\"$to\">";
		$xml .= "<query xmlns=\"http://jabber.org/protocol/disco#info\"/>";
		$xml .= "</iq>";

		if( !$this->jab->set_presence("","") ){
			throw new Exception("Unable to send presence to the server.",3004);
		}

		if (!$this->jab->_send($xml)) {
			throw new Exception("Unable to communicate with the Jabber server.",3005);
			return false;
		}

		$this->jab->execute(CBK_FREQ,RUN_TIME);

		$packet = $this->handler->get_muc_room_discovery_ret();
		if(!strcmp($packet["iq"]["@"]["type"],"result")){
			return true; // The room exists
		}
		else{
			return false; // The room does not exist.
		}

	}
}

?>
