#!/usr/bin/php -q
<?php

/*
 * Test ID: JBX_20
 * Description: Instatiates JabbeX with an invalid session id.
 * Pre-conditions:
 * - None
 * 
 * Post-Conditions
 * - Throws exception ("Invalid session argument. Unable to instantiate JabbeX.",3000)
 */
require_once("../../Jabbex.php");
$jabex = new Jabbex(" ");
$jabex->_jabber_connect();

?>