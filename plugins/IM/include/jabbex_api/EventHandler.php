<?php

require_once("jabbex_pre.php");

// This class handles all events fired by the Jabbex client class.
class EventHandler {

	private $jab; // Connector. We gonna receive it from the Jabbex class.

	private $connected = false;
	private $authenticated = false;

	/***/
	private $muc_room_info = null; // Used when creating a new MUC.
	private $muc_room_configured = false; // Used when creating a new MUC.
	private $muc_room_members = array(); // Users logged into a MUC room
	private $kick_muc_members = false; // Set to true when locking a MUC, so users are kicked from the room.
	/***/

	function __construct(&$jab) {
		$this->jab = &$jab;
		$this->first_roster_update = true;

		//if(DEBUG_ON) echo "Created!\n";
		$this->countdown = 0;
	}

	function get_connected(){
		return $this->connected;
	}

	function get_authenticated(){
		return $this->authenticated;
	}

	function set_muc_room_configured($bool=false){
		$this->muc_room_configured = $bool;
	}

	function set_kick_muc_members($bool=true){
		$this->kick_muc_members = $bool;
	}

	/*****************************************
	 ********* Basic methods
	 *****************************************/



	// called when a connection to the Jabber server is established
	function handle_connected() {
			
		$this->connected = true;

		// now that we're connected, tell the Jabber class to login

		$this->jab->login(JABBER_USERNAME, JABBER_PASSWORD, JABBER_RESOURCE);

	}

	// called after a login to indicate that the login was NOT successful
	function handle_authenticated() {
		$this->authenticated = true;
		$this->jab->terminated = true;
	}

	// called when the authentication process ends successfully
	function handle_auth_failure($code,$error) {
		// set terminated to TRUE in the Jabber class to tell it to exit
		$this->jab->terminated = true;

		throw new Exception("Authentication failed with code $code." ,3003);
	}


	function handle_disconnected(){
		$this->connected = false;
		$this->authenticated = false;
	}

	function handle_timeout(){
		throw new Exception("Timeout error." , 3018);
	}
	
	function handle_error($code,$msg,$xmlns,$packet){
		var_dump($packet);
		throw new Exception("Error $code received from the Jabber server (msg: $msg ;; xmlns: $xmlns ;; packet: ".serialize($packet) , 3012);

	}

	/**************************************
	 ******* MUC Rooms
	 **************************************/
	function set_muc_room_info($muc_room_short_name, $muc_room_full_name, $muc_room_description, $muc_room_owner_username){
		$this->muc_room_info = array (
			"muc_room_short_name" => $muc_room_short_name,
			"muc_room_full_name" => $muc_room_full_name,
			"muc_room_description" => $muc_room_description,
			"muc_room_owner_username" => $muc_room_owner_username
		);
	}
	function create_muc_room(){
		//echo date("Ymd::G:i:s - ")." Starting create_muc_room\n";

		if( !$this->jab->set_presence("","") ){
			throw new Exception("Unable to send presence to the Jabber server.",3004);
		}

		$this->jab->set_handler("muc_join",$this,"handle_muc_room_join");
		$this->jab->set_handler("generic_iq",$this,"handle_muc_room_config");


		/*
		 * Send the presence to the conference service to create
		 * the room.
		 */
		$from = JABBER_USERNAME."@".JABBER_SERVER_DNS."/".$this->jab->_resource;
		$to = $this->muc_room_info["muc_room_short_name"]."@".CONFERENCE_SERVICE_NAME.".".JABBER_SERVER_DNS."/".JABBER_USERNAME;
		$payload = " <x xmlns='http://jabber.org/protocol/muc'/>";

		$xml = "<presence";
		$xml .= " from='$from'";
		$xml .= " to='$to'";
		$xml .= " type='MUC'";
		$xml .= ">\n";
		$xml .= "$payload \n";
		$xml .= "</presence>\n";

		if ($this->jab->_send($xml)) {

			/*
			 * Send an iq to get the room configuration form.
			 */
			$iq_id	= $this->jab->_unique_id("muc"); // get an uniq ID to the packet.
			$this->jab->_set_iq_handler("on_generic_iq",$iq_id); // define a method of Jabber class to treat the pck.
			if( !$this->jab->_send_iq($this->muc_room_info["muc_room_short_name"]."@".CONFERENCE_SERVICE_NAME.".".JABBER_SERVER_DNS ,"get",$iq_id,"http://jabber.org/protocol/muc#owner",NULL,JABBER_USERNAME."@".JABBER_SERVER_DNS."/".JABBER_RESOURCE ) ){
				throw new Exception("Unable to send iq package to the Jabber server.",3006);
			}

			//echo date("Ymd::G:i:s - ")." End create_muc_room\n";

			return true;
		} else {
			$this->jab->_log("ERROR: _send #1");
			throw new Exception("Unable to communicate with the Jabber server.",3005);
			return false;
		}
		/*********************************************/


	}

	/*
	 * When joining a MUC room by sending a presence msg to room_name@conf_service
	 * the server replies with an ack message. Here we check whether this ack msg indicates
	 * success or not.
	 */
	function handle_muc_room_join($to,$from,$jid,$affiliation,$role,$status,$type){
		// Here we gonna check if everything worked fine during the MUC room creation.

		//		echo date("Ymd::G:i:s - ")." Start handle_muc_room_creation\n";

		$rec_from_room = explode('@',$from); // MUC room name
		$presence_from_user = explode('/',$rec_from_room[1]); // Presence regarding a given user ex: from="stubroom@conference.dhcp-62.grenoble.xrce.xerox.com/bbking"

		if(!strcmp($type,"error")){
			$this->jab->terminated = true;
			throw new Exception("Unable to join MUC room $rec_from_room[0]." , 3016);
			return false;
		}
			
		//		echo "\n".$to."\n".$from."\n".$jid."\n".$affiliation."\n".$role."\n".$status."\n";

		/*
		 * We received the presence of an user other than the user we're logged as.
		 * Just put this user in the array of logged users and do nothing else.
		 */
		if(strcmp($presence_from_user[1],JABBER_USERNAME)){
			$this->muc_room_members[$rec_from_room[0]][$jid] = $affiliation;
		}
		elseif(!strcmp($type,"unavailable")){
			//nop
		}
		else{
			// First of all, we check if the status code's a 'cool' status (check XEP 0045 to
			// know more about these numbers). Then we check other stuff just because we wanna be
			// as robust as a mountain!
			if( (!strcmp($status,"201") || !strcmp($status,"")) && !strcmp($rec_from_room[0], $this->muc_room_info["muc_room_short_name"]) && !strcmp($role,"moderator") && !strcmp($affiliation,"owner") ){

				//echo date("Ymd::G:i:s - ")." End handle_muc_room_creation\n";

				// Check whether the users must be kicked or not, and if so kick them!
				if($this->kick_muc_members && isset($this->muc_room_members[$rec_from_room[0]]) && is_array($this->muc_room_members[$rec_from_room[0]]) ){

					// Kick'em!
					$iq_id	= $this->jab->_unique_id("muc"); // get an uniq ID to the packet.
					$xml = "<iq from=\"$to\" to=\"$from\" id=\"$iq_id\" type='set'>";
					$xml .= "<query xmlns='http://jabber.org/protocol/muc#admin'>";
					foreach($this->muc_room_members[$rec_from_room[0]] as $key => $value){
						// Check if it's a member (not an admin nor an owner)
						if(!strcmp($value,"member")){
							$xml .= "<item jid='$key' role='none'>";
							$xml .= "<reason>The project associated to this room has been disabled</reason></item>";
						}
					}
					$xml .=  "</query></iq>";

					if (!$this->jab->_send($xml)) {
						throw new Exception("Unable to communicate with the Jabber server.",3005);
						return false;
					}
				}

				// Everything's alright! We basicly do nothing...
				return true;
			}
			else{
				//echo "nehhh";
				// Got an error... Stop the process.
				$this->jab->terminated = true;
				throw new Exception("Unable to join MUC room. Status $status received" , 3013);
				return false;
			}
		}

	}

	
	/*
	 * Called when a muc config form packet is received.
	 * We build a config array and call the pck mng to treat the pck
	 * and generate a response.
	 *
	 */
	function handle_muc_room_config($packet){

		if(!$this->muc_room_configured){
			//echo date("Ymd::G:i:s - ")." Start handle_muc_room_config\n";

			$packet_mng = new PacketMng();

			$this->jab->_set_iq_handler("on_generic_iq",$packet["iq"]["@"]["id"]); // define a method of Jabber class to treat responses to this pck.

			if( !strcmp($packet["iq"]["@"]["type"],"error") ){
				$this->jab->terminated = true;
				throw new Exception("Unable to create MUC room. The Jabber server refused to send the configuration packet." , 3014);
				return false;
			}

			// Build configuration vector, with the properties and their values.
			// We need the parameters passed when a user creates a MUC.
			$config_info = array(
			"muc#roomconfig_roomname" => $this->muc_room_info["muc_room_full_name"],
			"muc#roomconfig_roomdesc" => $this->muc_room_info["muc_room_description"],
			"muc#roomconfig_changesubject" => "1", // Allow users to change subject
			"muc#roomconfig_maxusers" => "0", // Max # of users = Unlimited
			"muc#roomconfig_publicroom" => "1", // List room in room's directory
			"muc#roomconfig_persistentroom" => "1", // Room is Persistent
			"muc#roomconfig_moderatedroom" => "1", // Room is Moderated
			"muc#roomconfig_membersonly" => "1", // Room is Members-only
			"muc#roomconfig_allowinvites" => "1", // Allow Occupants to Invite Others
			"muc#roomconfig_passwordprotectedroom" => "0", // Password Required to Enter Room
			"muc#roomconfig_whois" => "anyone", // Role that May Discover Real JIDs of Occupants
			"muc#roomconfig_enablelogging" => "1", // Log Room Conversations
			"x-muc#roomconfig_reservednick" => "0", // Only login with registered nickname
			"x-muc#roomconfig_canchangenick" => "1", // Allow Occupants to change nicknames
			"x-muc#roomconfig_registration" => "1", // Allow Users to register with the room
			"muc#roomconfig_roomadmins" => $this->muc_room_info["muc_room_owner_username"]."@".JABBER_SERVER_DNS,//$this->muc_room_info["muc_room_owner_username"]."@".JABBER_SERVER_DNS, // Room Admins
			"muc#roomconfig_roomowners" => JABBER_USERNAME."@".JABBER_SERVER_DNS// Room Owners
			);

			$config_xml = $packet_mng->fill_muc_config_form($packet,$config_info);

			if($this->jab->_send($config_xml)){
				$this->muc_room_configured = true;
			}
			else{
				throw new Exception("Unable to communicate with the Jabber server.",3005);
			}

			//echo date("Ymd::G:i:s - ")." End handle_muc_room_config\n";
			//		$this->jab->terminated = true;
		}
		// Check confirmation msg
		else{
			//			echo "Room configured \n";
			//			var_dump($packet);
			$rec_from_room = explode('@',$packet["iq"]["@"]["from"]); // MUC room name
			if( !strcmp($packet["iq"]["@"]["type"], "result") && !strcmp($rec_from_room[0], $this->muc_room_info["muc_room_short_name"]) && (sizeof($packet["iq"]["#"]) == 0) ){
				$this->jab->terminated = true;
				$this->muc_room_configured = false;
				return true;
			}
			else{
				throw new Exception("Unable to create MUC room. The Jabber server refused the configuration packet." , 3015);
			}
		}

		return true;

	}

	private $muc_room_discovery_ret; // Return value for muc room discovery
	function get_muc_room_discovery_ret(){return $this->muc_room_discovery_ret;}
	/*
	 * Handles the response received for a MUC room query.
	 */
	function handle_muc_room_discovery($packet){
		$this->muc_room_discovery_ret = $packet;
		$this->jab->terminated = true;
	}
}
?>