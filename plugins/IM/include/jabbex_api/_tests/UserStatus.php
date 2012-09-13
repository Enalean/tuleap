#!/usr/bin/php -q
<?php
/*
 * Test ID: JBX_18
 * Test description: Returns the current IM status of the the user bbking.
 * Pre-conditions: 
 * - The user bbking must exist.
 * 
 * Post-conditions: 
 * - The status returned must be coherent with the current user status (check it manually).
 */

require_once("../Jabbex.php");
$jabex = new Jabbex("res_3271981");
var_dump ( $jabex->user_status("bbking@dhcp-62.grenoble.xrce.xerox.com") );
?>