<?php
/*
 * Test ID: IM_03
 * Test description: Permet la suspension d'un muc room d'un projet qui a quitté l'état actif vers un autre état(suspended, pending)
 * Pre-conditions:
 * 		1 - Jabbex doit être bien configurer pour dialoguer avec le serveur openfire
 * 		2 - le tableau des paramétres $params doit contenir au moins le group_id du 
 * 			projet qui mappe le salon de discution à rendre inaccessible.
 * 		3 - l'identification ,$id,du Plugin IM doit être correcte il 10 ici
 * 		
 * 
 * Post-conditions:
 * 		1 - le muc room du projet dont le group_id=252 est fermer(protégé par un mot de passe ) dans le serveur openfire .
 */
 
 ini_set('include_path',"/home/zdiallo/Codendi/dev_server/codendi/src/www/include:/home/zdiallo/Codendi/dev_server/codendi/src:.");
 require_once('pre.php');
 require_once("../include/IMPlugin.class.php");
 $id=10;
 
 $params=array("group_id"=>252);
 $plugin=new IMPlugin($id,IM_DEBUG_ON);
 echo "\n\n\nBegin\n\n\n##########################################################################\n\n\n\n\n\n";
 
 $plugin->im_lock_muc_room ($params);

 echo 'muc name locked is : '.$plugin->get_last_muc_room_name_locked ().'';
 
 echo "\n\n\n\n\n\nEnd\n\n\n ###############################################################################\n\n\n ";

?>
