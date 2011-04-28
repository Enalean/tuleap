<?php
/*
 * Test ID: IM_07
 * Test description: Permet la performance la création de plusieurs mucs rooms et de plusieurs shared groups en même temps
 * Pre-conditions:
 * 		1 - Jabbex doit être bien configurer pour dialoguer avec le serveur openfire
 * 		2 - les pramètres $unix_name, $name, $desc, $user doivent être renségnés (se sont des string)
 * 			projet qui mappe le salon de discution à rendre inaccessible.
 * 		3 - le $user admin ici doit exister en tant que administrateur des projets correspondants qui existent déjà dans codendi. 
 * Post-conditions:
 * 		1 - les muc rooms unix-nameperf-test0,unix-nameperf-test1, unix-nameperf-test2 et unix-nameperf-test3 sont créés ainsi que les groupes partagés correspondants .avec les temps suivants :
 * 			->temps mis apres appel numéro 1 : 10.859273910522 s
 * 			->temps mis apres appel numéro 2 : 6.793231010437 s
 * 			->temps mis apres appel numéro 3 : 6.5583012104034 s
 * 			->temps mis apres appel numéro 4 : 7.0762600898743 s
 * 			-> Ce qui donne un temps total :31.287104129791 s
 * Pour confirmer les résultats on peut se rendre à l'url suivant (http://kilauea.grenoble.xrce.xerox.com:8017/plugins/IM/?view=codendi_im_admin ) ,avant et aprés l'exécution de ce script .
 */
 require_once("../include/jabbex_api/Jabbex.php");
$jabex = new Jabbex("res_3271989");
$unix_name="unix-nameperf-test";
$name="name-perf-test";
$desc="desc-perftest";
$user="admin";
$begin=microtime(true);
$deb=$begin;
$time=0;
echo'\n\nDébut : '.$begin;
for($i=0;$i<4;$i++){
	//create_muc_room($muc_room_short_name, $muc_room_full_name, $muc_room_description, $muc_room_owner_username)
	$jabex->create_muc_room($unix_name.$i, $name.$i,$desc.$i, $user);
	$jabex->create_shared_group($unix_name.$i,$name.$i);
	$time=microtime(true)-$deb;
	$deb=microtime(true);
	print('\n\n\n temps mis apres appel numéro '.($i+1).' : '.$time);
}
$end=microtime(true)-$begin;
print("\n\n\ntemps total :".$end."\n")
?>
