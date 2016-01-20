#!/usr/bin/php -q
<?php
/*
 * Test ID: JBX_15
 * Test description: This test removes the user eclapton from the stubroom's members list.
 * Pre-conditions:
 * 		1 - Jabbex must be properly configured to use your Jabber server;
 * 		2 - The room stubroom must exist;
 * 		3 - The default Jabbex JID must be an admin of the stubroom;
 * 		4 - No other connection of the default JID that uses resource res_3271981 must be active.
 * 		5 - The user eclapton must exist and must be a member of the stubroom.
 * 
 * Post-conditions:
 * 		1 - The user eclapton is removed from stubroom's members list (no error or warning messages must be printed).
 * 		2 - Jabbex closes the connection to the Jabber server.
 */

require_once("../Jabbex.php");
$jabex = new Jabbex("res_3271981");
$jabex->muc_remove_member("stubroom", "eclapton");

?>