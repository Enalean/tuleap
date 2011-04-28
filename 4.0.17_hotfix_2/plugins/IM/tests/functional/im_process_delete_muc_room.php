<?php
/*
 * Test ID: IM_02
 * Test description: Permet la création d'un muc room et d'un shared group un projet qui n'est pas sinchronisé avec le service IM'.
 * Pre-conditions:
 * 		1 - Jabbex doit être bien configurer pour dialoguer avec le serveur openfire
 * 		2 - le tableau des paramétres $params doit contenir au moins le group_id du 
 * 			projet qui mappe le salon de discution à détruire.
 * 		3 - l'identification ,$id,du Plugin IM doit être correcte il 10 ici
 * 		
 * 
 * Post-conditions:
 * 		1 - le muc room du projet dont le group_id=225 est détruit dans lesrveur openfire .
 */
 
 ini_set('include_path',"/home/zdiallo/Codendi/dev_server/codendi/src/www/include:/home/zdiallo/Codendi/dev_server/codendi/src:.");
 require_once('pre.php');
 require_once("../include/IMPlugin.class.php");
 $id=10;
 
 $params=array("group_id"=>225);
 $plugin=new IMPlugin($id,true);
 echo "Begin##########################################################################";
 
 $plugin->im_process_delete_muc_room ($params);

 echo 'Nom du muc detruit : '.$plugin->_get_last_muc_room_name_delete();
 
 echo "End ###############################################################################<br> "
?>
