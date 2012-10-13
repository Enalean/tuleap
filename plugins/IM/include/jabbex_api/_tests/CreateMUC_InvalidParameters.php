#!/usr/bin/php -q
<?php
/*
 * Test ID: JBX_05
 * Test description: This test creates a MUC room named stubroom.
 * Pre-conditions:
 * 		- Check CreateMUC pre-conditions.
 * 
 * Post-conditions:
 * 		- The fatal exception 3002 (Invalid string parameter.) must be thrown and the execution must be aborted.
 * 
 */

require_once("../Jabbex.php");
$jabex = new Jabbex("res_3271989");
$jabex->create_muc_room("stubroom", "", "Here I put the description of my room (Projetc short description)", "bbking");

?>