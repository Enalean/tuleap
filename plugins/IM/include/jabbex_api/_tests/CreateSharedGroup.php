#!/usr/bin/php -q
<?php
/*
 * Test ID: JBX_08
 * Test description: This test makes available a shared group named "My little stub project" with id "stub".
 * Pre-conditions:
 * 		1 - Jabbex must be properly configured to use your Jabber server;
 * 		2 - The project id stub must exist (and state = A) in the Codendi environment;
 * 		3 - The default Jabbex JID must have permission to create a shared group with the Helga plugin;
 * 		4 - The Helga plugin must be installed and JabbeX must be configured to properly use it;
 * 		5 - No other connection of the default JID that uses resource res_32723989 must be active.
 * 
 * Post-conditions:
 * 		1 - The shared group corresponding to the project stub is set as "shared" with the name "My little stub project";
 * 		3 - Jabbex closes the connection to the Jabber server.
 */

require_once("../Jabbex.php");
$jabex = new Jabbex("res_32723989");
$jabex->create_shared_group("stub","My little stub project");

?>