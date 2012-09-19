#!/usr/bin/php -q
<?php
/*
 * Test ID: JBX_16
 * Test description: Test whether the values return by the function get_server_conf (server configuration parameters) are correct.
 * Pre-conditions:
 *  - None.
 * 
 * Post-conditions:
 * - Check whether the values returned match the values in the jabbex_conf.xml configuration file.
 */

require_once("../Jabbex.php");
$jabbex = new Jabbex("res_3271981");
var_dump($jabbex->get_server_conf());

?>