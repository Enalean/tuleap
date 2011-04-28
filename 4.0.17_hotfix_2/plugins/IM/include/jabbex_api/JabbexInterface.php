<?php
/*
 * File: JabbexInterface.php
 * Type: PHP5 interface
 * Description: Define public methods implemented by JabbeX middleware.
 * Author: Daniel Madruga
 *
 * ### Change Log ###
 * 2008-Feb-27 - Initial creation - By: Daniel Madruga
 *
 */

interface JabbexInterface
{

	/*
	 * Create a permanent Multi-User Chat Room on the default jabber server used by JabbeX.
	 * Returns 	0 if success
	 * 		1 if group short name already exists
	 *		!=0 & !=1 if other errors.
	 *
	 * @param muc_room_short_name 	  string Used to create the room's JID. Mapped from project's "Short Name".
	 * @param muc_room_full_name 	  string Displayed on the Rooms Directory. Mapped from project's "Full Name".
	 * @param muc_room_description 	  string Short description of the Room. Mapped from project's "Short Description".
	 * @param muc_room_owner_username string The username of who's creating the room. Mapped from project owner.
	 */
	function create_muc_room($muc_room_short_name, $muc_room_full_name, $muc_room_description, $muc_room_owner_username);



	/*
	 * Lock a given Multi-User Chat Room on the default jabber server used by JabbeX.
	 * When locked a MUC Room cannot be accessed by any user.
	 *
	 * Returns 	0 if success
	 * 		1 if group not found
	 *		!=0 & !=1 if other errors.
	 *
	 * @param muc_room_short_name 	 string Room's JID. Mapped from project's "Short Name".
	 */
	function lock_muc_room($muc_room_short_name);



	/*
	 * Unlock a given Multi-User Chat Room on the default jabber server used by JabbeX
	 * which was previously locked with lockMUCRoom.
	 *
	 * Returns 	0 if success
	 * 		1 if group not found
	 *              2 if group not locked
	 *		!=0 & !=1 & !=2 if other errors.
	 *
	 * @param muc_room_short_name 	 string Room's JID. Mapped from project's "Short Name".
	 */
	function unlock_muc_room($muc_room_short_name);



	/*
	 * Delete a given Multi-User Chat Room on the default jabber server used by JabbeX.
	 *
	 * Returns 	0 if success
	 * 		1 if group not found
	 *		!=0 & !=1 if other errors.
	 *
	 * @param muc_room_short_name 	 string Room's JID. Mapped from project's "Short Name".
	 */
	function delete_muc_room($muc_room_short_name);
	
	
	/*
	 * This functions creates a shared group on the server and configures it to
	 * show up on its members' roster.
	 *
	 * Returns 	0 if success
	 * 		1 if group short name already exists
	 *		!=0 & !=1 if other errors.
	 *
	 * @param group_short_name 	  	Index of the group. Mapped from project's "Short Name".
	 * @param group_full_name 	  	string Displayed on the Members' roster. Mapped from project's "Full Name".
	 */
	function create_shared_group($group_short_name, $group_full_name);

	/*
	 * Return an array with the followin server configuration info:
	 * "server_dns"
	 * "server_name"
	 * "username"
	 * "conference_service"
	 * "server_port"
	 * 
	 */
	function get_server_conf();
	
	function user_status($jid);
}
?>
