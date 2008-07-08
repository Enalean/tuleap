<?php
/*
 * Shared groups management class for Openfire.
 *
 */
require_once 'GroupManagerInterface.php';


class GroupManager implements GroupManagerInterface{
	private $group_short_name, $group_full_name, $jab;
	private $msg_handler;
	private $log = array('error' => false);

	public function create_shared_group(){
		
		$this->jab->set_presence("","BOT service");
		
		// When we receive messages, treat them with OpenfireMessageHandle.handleMessage
		$this->msg_handler = new OpenfireMessageHandler($this->jab, array('group_short_name' => $this->group_short_name), $this->log);
		$this->jab->set_handler("message_normal",$this->msg_handler,"handleMessage");
		$this->jab->set_handler("message_chat",$this->msg_handler,"handleMessage");

		/*
		 * Helga group update sintax
		 * group update -g "<GroupName>" [-t Type] [-s <"nobody" | "onlygroup" | "everybody">] [-n Displayname] [-a User] [-m User] [-G Grouplist] [-D Description]
		 */

		// Sharing the group with all members and defining the display name as the group's full name
		$this->log["log_msg"][] =  "sending: group update -g \"".$this->group_short_name."\" -n \"".$this->group_full_name."\" -s \"onlygroup\" :: to: ".JABBER_HELGA_JID."\n";
		// This message produces a response, so let's push it into the msg_queue.
		// $this->msg_queue->push("group",$this->group_short_name);

		// Ok... Let's send the msg.
		if( !$this->jab->message(JABBER_HELGA_JID,"chat",NULL,"group update -g \"".$this->group_short_name."\" -n \"".$this->group_full_name."\" -s \"onlygroup\"") ){
			throw new Exception("Unable to send a chat message to ".JABBER_HELGA_JID,3007);
		}

	}


	/*
	 * Sets and gets
	 */
	public function set_group_short_name($short_name){
		$this->group_short_name = $short_name;
	}
	public function set_group_full_name($full_name){
		$this->group_full_name = $full_name;
	}
	public function set_jab_connector(&$jab){
		$this->jab = &$jab;
	}

	public function get_group_short_name(){
		return $this->group_short_name;
	}
	public function get_group_full_name(){
		return $this->group_full_name;
	}
	public function get_log(){
		return $this->log;
	}


}

class OpenfireMessageHandler{
	private $jab;
	private $parameters;
	private $log;
	
	public function __construct(&$jab, $parameters, &$log) {
		$this->jab = &$jab;
		$this->parameters = $parameters;
		$this->log = &$log;
	}
	
	public function handleMessage($from,$to,$body,$subject,$thread,$id,$extended){
		
		// Message patterns
		$success_msg = "group '".$this->parameters["group_short_name"]."' was successful updated.";
		$err_group_not_found = "this group does not exist.";
		$err_command_not_found = "I don't understand your command. Send 'help' to get an commandlist or 'help [command]' for specific help.";
		$err_permission_denied = "Only admins have the permission to do this!";
		
		$this->log["log_msg"][] = date(JABBER_LOG_DATE_FORMAT)."Incoming message!\n"."From: $from\t\tTo: $to\n"."Subject: $subject\tThread; $thread\n"."Body: $body\n"."ID: $id\n".$extended."\n";

		// Check if the message comes from Helga bot
		if(strcmp($from,JABBER_HELGA_JID) == 0){
			// Let's check what kind of msg we've received
			
			// Beautiful! The server has acked our request. Giving control back to the calling method.
			if(strcmp($body,$success_msg) == 0){
				$this->log["error"] = false;
				//$this->log = date(JABBER_LOG_DATE_FORMAT)."The shared group was successfully enabled.";
			}

			/*
			 * Dang! The shared group doesn't exist. If this happens, there's probably an error
			 * with the GroupManager class variables.
			 *   
			 */ 
			else if(strcmp($body,$err_group_not_found) == 0){
				$this->log["log_msg"]["error"] ="The shared group you are trying to enable does not exist.";
				$this->log["error"] = true;
				$this->log["err_code"] = 3008;
			}
			
			/*
			 * GroupManager sent an invalid command to Helga plugin.
			 */
			else if(strcmp($body,$err_command_not_found) == 0){
				$this->log["log_msg"]["error"] = "The Jabber server returned an INVALID_COMMAND error.";
				$this->log["error"] = true;
				$this->log["err_code"] = 3009;
			}
			else if (strcmp($body,$err_permission_denied) == 0){
				$this->log["log_msg"]["error"] = "Permission denied. The user ".JABBER_USERNAME." does not have permission to create a shared group. Please check the Jabber server configuration.";
				$this->log["error"] = true;
				$this->log["err_code"] = 3010;
			}
			else{
				$this->log["log_msg"]["error"] = "Unknown error while creating shared group.";
				$this->log["error"] = true;
				$this->log["err_code"] = 3011;
			}
			
			// Terminate the proccess and throw give control back to the calling method.
			$this->jab->terminated = true;
			
			if($this->log["error"]){
				throw new Exception($this->log["log_msg"]["error"],$this->log["err_code"]);	
			}
			
		}
	}
}

?>