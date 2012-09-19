#!/usr/bin/php -q
<?php
/*
 * Test ID: JBX_06
 * Test description: This test creates a MUC room named stubroom.
 * Pre-conditions:
 * 		1 - Jabbex must be properly configured to use your Jabber server;
 * 		2 - The room stubroom must NOT exist;
 * 		3 - The default Jabbex JID must have permission to create a MUC room;
 * 		4 - No other connection of the default JID that uses resource res_3271989 must be active.
 * 		5 - The user "bbking" must exist in the server.
 * 
 * Post-conditions:
 * 		1 - The room stubroom is created with the name "My Stub Room (Room Name)" 
 * 			and the description "Here I put the description of my room (Projetc short description)" (no error or warning messages must be printed).
 * 		2 - The default JabbeX JID must be set as room owner and the user bbking must be set as room admin.
 * 		3 - Jabbex closes the connection to the Jabber server.
 */

require_once("../Jabbex.php");
$jabex = new Jabbex("res_3271989");
$jabex->create_muc_room("stubroom", "My Stub Room (Room Name)", "Here I put the description of my room (Projetc short description)", "bbking");

?>