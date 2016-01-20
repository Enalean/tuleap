#!/usr/bin/php -q
<?php
/*
 * Test ID: JBX_17
 * 
 * Description: Unlocks a locked MUC room.
 * 
 * Pre-conditions:
 * - The MUC room stubroom must exist and must be locked with the password defined in the jabbex_conf.xml configuration file.
 * 
 * Pre-condition:
 * - The MUC room stubroom is unlocked.
 * 
 */
 
require_once("../Jabbex.php");
$jabex = new Jabbex("res_1934568934756");
$jabex->unlock_muc_room("stubroom");

?>
