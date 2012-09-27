#!/usr/bin/php -q
<?php

/*
 * Test ID: JBX_03
 * Description: Fails to connect to the Jabber server.
 * Pre-conditions:
 * - The Jabber server is down or inaccessible by JabbeX.
 * 
 * Post-Conditions
 * - Throws exception 'Unable to connect to the Jabber server.'
 */
require_once("../../Jabbex.php");
$jabex = new Jabbex("res_324863841");
$jabex->_jabber_connect();

?>