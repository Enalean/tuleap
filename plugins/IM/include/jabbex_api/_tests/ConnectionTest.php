#!/usr/bin/php -q
<?php

/*
 * Test ID: JBX_04
 * Test description: This test connects to the Jabber server and disconnect.
 * Pre-conditions:
 * 		1 - Jabbex must be properly configured to use your Jabber server;
 * 		2 - No other connection of the default JID that uses resource res_324863841 must be active.
 * 
 * Post-conditions:
 * 		1 - Jabbex must exchange the following sequence of messages with the server (check the jabber-class log):
 *			SEND xml header:: ex: <?xml version='1.0' encoding='UTF-8' ?>
 *
 * 			SEND message to open the stream:: ex: <stream:stream to='dhcp-62.grenoble.xrce.xerox.com' xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams'> 
 *			RECV confirmation:: ex: <stream:stream xmlns:stream="http://etherx.jabber.org/streams" xmlns="jabber:client" from="dhcp-62.grenoble.xrce.xerox.com" id="c9c5e72a" xml:lang="en">
 *
 *          At this point Jabber must call the handler for the event "connected"
 * 
 *          SEND request for auth methods: ex: <iq type='get' id='auth_f2ec7c795f958fc34902d7d99ca15291'>
 *													<query xmlns='jabber:iq:auth'>
 *														<username>imadmin</username>
 *													</query>
 *												</iq>
 *			RECV the availables auth methods:: ex:
 * 					 <iq type="result" id="auth_f2ec7c795f958fc34902d7d99ca15291">
 * 						<query xmlns="jabber:iq:auth">
 * 							<username>imadmin</username>
 * 							<password/>
 * 							<resource/>
 * 						</query>
 * 					</iq>
 *			
 *			SEND the auth message:: ex: 
 *				 <iq type='set' id='auth_f2ec7c795f958fc34902d7d99ca15291'>
 *					<query xmlns='jabber:iq:auth'>
 *							<username>imadmin</username>
 *							<password>imadmin</password>
 *							<resource>827ae8c188e6a8882410516ce4de597d</resource>
 *					</query>
 *				</iq>
 * 
 *			RECV the confirmation message:: ex: 
 * 				<iq type="result" id="auth_f2ec7c795f958fc34902d7d99ca15291" to="imadmin@dhcp-62.grenoble.xrce.xerox.com/827ae8c188e6a8882410516ce4de597d"/>
 *		
 * 			Call handler for the event "authenticated"
 * 
 * 			Call the handler for the event "terminated"
 *			
 * 			SEND a message to close the stream:: </stream:stream>
 */
require_once("../Jabbex.php");
$jabex = new Jabbex("res_324863841");
$jabex->_jabber_connect();

?>