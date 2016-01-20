#!/usr/bin/php -q
<?php
/*
 * Test ID: JBX_01
 * Test description: This test tries to add the user eclapton as a member of the invalid room invalid_muc_012x68dc.
 * Pre-conditions:
 * 		1 - Jabbex must be properly configured to use your Jabber server;
 * 		2 - The room invalid_muc_012x68dc must NOT exist;
 * 		3 - The default Jabbex JID must be an admin of the stubroom;
 * 		4 - No other connection of the default JID that uses resource res_3271981 must be active.
 * 		5 - The user eclapton must exist.
 * 
 * Post-conditions:
 * 		1 - An exception is thrown with message 'Invalid MUC room invalid_muc_012x68dc...'
 * 		2 - Jabbex closes the connection to the Jabber server.
 */

require_once("../Jabbex.php");
$jabex = new Jabbex("res_3271981");
$jabex->muc_add_member("invalid_muc_012x68dc", "eclapton");

?>