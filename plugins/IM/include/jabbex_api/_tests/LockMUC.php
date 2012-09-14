#!/usr/bin/php -q
<?php
/*
 * Test ID: JBX_12
 * Description: Locks an existing MUC room.
 * 
 * Pre-conditions:
 * - The MUC room stubroom must exist and must not be locked.
 * 
 * Pre-condition:
 * - The MUC room stubroom is locked with the <lockmuc_pwd> password defined in the jabbex_conf.xml file.
 * 
 */
require_once("../Jabbex.php");
$jabex = new Jabbex("res_1934568934756");
$jabex->lock_muc_room("stubroom");

?>