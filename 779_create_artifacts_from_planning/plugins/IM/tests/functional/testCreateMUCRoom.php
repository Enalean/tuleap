<?php
/*
 * Created on May 7, 2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 
 require_once("../include/jabbex_api/Jabbex.php");
$jabex = new Jabbex("res_3271989");
$unix_name="unix_name";
$name="name";
$desc="desc";
$user="testIM";
$begin=microtime(true);
$deb=$begin;
$time=0;
echo'Début :'.$begin;
for($i=0;$i<7;$i++){
	//create_muc_room($muc_room_short_name, $muc_room_full_name, $muc_room_description, $muc_room_owner_username)
	$jabex->create_muc_room($unix_name.$i, $name.$i,$desc.$i, $user);
	$time=microtime(true)-$deb;
	$deb=microtime(true);
	print('\n temps mis apres appel numéro '.($i+1).' : '.$time);
}
$end=microtime(true)-$begin;
print("temps total :".$end."\n")

?>
