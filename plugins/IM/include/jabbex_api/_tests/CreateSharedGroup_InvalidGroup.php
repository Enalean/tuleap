#!/usr/bin/php -q
<?php
/*
 * Test ID: JBX_07
Test Description: Tries to create a shared group that maps to an invalid Codendi project.
Pre-conditions:
The Codendi project InvalidGroup_XXXAUTYS must NOT exist;

Post-conditions:
The fatal exception 3008 (The shared group you are trying to enable does not exist) must be thrown and the execution must be aborted.
 */
require_once("../Jabbex.php");
$jabex = new Jabbex("123654789");
$jabex->create_shared_group("InvalidGroup_XXXAUTYS","XGREXDtTXRt"); // An invalid group.

?>