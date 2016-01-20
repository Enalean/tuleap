#!/usr/bin/php -q
<?php
/*

Test ID: JBX_13
Test Description: Checks whether a MUC exists or not.

Pre-conditions:
The room stubroom must exist;
The room invalid_room_qoivf82347rr must NOT exist;

Post-conditions:
Two “PASS” are echoed, one for the test with an existing room and other for the test with an invalid room.

 */

require_once("../Jabbex.php");
$jabbex = new Jabbex("res_3271989");
if($jabbex->_muc_exists("stubroom")){
	echo "PASS\n";
}
else{
	echo "FAIL\n";
}

if($jabbex->_muc_exists("invalid_room_qoivf82347rr")){
	echo "FAIL\n";
}
else{
	echo "PASS\n";
}

?>