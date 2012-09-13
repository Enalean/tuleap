#!/usr/bin/php -q
<?php
/*
 * Test ID: JBX_10
 * Test description: This test deletes an existing MUC room named "stubroom".
 * Pre-conditions:
 * 		1 - Jabbex must be properly configured to use your Jabber server;
 * 		2 - The room "stubroom" must exist;
 * 		3 - The default Jabbex JID must have permission to delete a MUC room;
 * 		4 - No other connection of the default JID that uses resource res_3271989 must be active.
 * 
 * Post-conditions:
 * 		1 - The room "stubroom" is deleted.
 * 		2 - Jabbex closes the connection to the Jabber server.
 */

require_once("../Jabbex.php");
$jabex = new Jabbex("res_3271989");
$jabex->delete_muc_room("stubroom");

?>