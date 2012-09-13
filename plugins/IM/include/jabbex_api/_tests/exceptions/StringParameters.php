#!/usr/bin/php -q
<?php

/*
 * Test ID: JBX_21
 * Description: Call a JabbeX function with an invalid string parameter.
 * Pre-conditions:
 * - None
 * 
 * Post-Conditions
 * - Throws exception ("Invalid string parameter.",3002)
 */
require_once("../../Jabbex.php");
$jabbex = new Jabbex("35715928654");
$jabbex->_jabber_connect();
$jabbex->create_muc_room(" "," ","blahasodnud","daniel");

?>