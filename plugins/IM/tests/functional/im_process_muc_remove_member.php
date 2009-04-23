<?php
/*
 * Test ID: IM_06
 * Test description: Permet la suppression d'un membre d'un salon de discution
 * Pre-conditions:
 * 		1 - Jabbex doit être bien configurer pour dialoguer avec le serveur openfire
 * 		2 - le tableau des paramétres $params doit contenir au moins le group_id du 
 * 			projet qui mappe le salon de discution et aussi le user_id (ici 9347) d'un utilisateur qui existe dans la base de données codendi et dans le muc room.
 * 		3 - l'identification ,$id,du Plugin IM doit être correcte il 10 ici
 * 		
 * 
 * Post-conditions:
 * 		1 - daniel va être supprimer au muc room du projet dont le group_id=252 .
 */
 
 ini_set('include_path',"/home/zdiallo/Codendi/dev_server/codendi/src/www/include:/home/zdiallo/Codendi/dev_server/codendi/src:.");
 require_once('pre.php');
 require_once("../include/IMPlugin.class.php");
 $id=10;
 
 $params=array("group_id"=>252,"user_id"=>9347);
 $plugin=new IMPlugin($id,IM_DEBUG_ON);
 echo "\n\nBEGIN\n\n\n##########################################################################\n\n\n\n\n\n";
 
 $plugin->im_process_muc_remove_member ($params);

 echo 'Message : '.$plugin->get_last_remove_member_of_once_muc_room ().'';
 
 echo "\n\n\n\n\n ###############################################################################\n\n\nEND\n ";


?>
