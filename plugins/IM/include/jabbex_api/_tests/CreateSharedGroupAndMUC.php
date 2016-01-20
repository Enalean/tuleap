#!/usr/bin/php -q
<?php

/*
 * Test ID: JBX_09
 * Test description: This test creates a MUC room and a shared group named "Crossing Alabama".
 * Pre-conditions:
 * 		1 - Jabbex must be properly configured to use your Jabber server;
 * 		2 - The room id xingalab must NOT exist;
 * 		3 - The default Jabbex JID must have permission to create a MUC room;
 * 		4 - No other connection of the default JID that uses resource res_398671989 must be active.
 * 		5 - The user "bbking" must exist in the server.
 * 		6 - The project id xingalab must exist in the Codendi environment;
 *		7 - The default Jabbex JID must have permission to create a shared group with the Helga plugin;
 * 		8 - The Helga plugin must be installed and JabbeX must be configured to properly use it; 
 * Post-conditions:
 * 		1 - The room xingalab is created with the name "Crossing Alabama" 
 * 			and the description "Here I put the description of my room (Projetc short description)" (no error or warning messages must be printed).
 * 		2 - The default JabbeX JID must be set as room owner and the user bbking must be set as room admin.
 * 		3 - The shared group corresponding to the project stub is set as "shared" with the name "Crossing Alabama";
 * 		4 - Jabbex closes the connection to the Jabber server.
 */

require_once("../Jabbex.php");
$jabex = new Jabbex("res_398671989");
$jabex->create_shared_group("xingalab","Crossing Alabama");
$jabex->create_muc_room("xingalab", "Crossing Alabama", "Here I put the description of my room (Projetc short description)", "bbking");

?>