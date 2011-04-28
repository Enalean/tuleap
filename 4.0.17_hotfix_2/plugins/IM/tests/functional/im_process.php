<?php
/*
 * Test ID: IM_01
 * Test description: Permet la création d'un muc room et d'un shared group un projet qui n'est pas sinchronisé avec le service IM'.
 * Pre-conditions:
 * 		1 - Jabbex doit être bien configurer pour dialoguer avec le serveur openfire
 * 		2 - le tableau des paramétres $params doit contenir au moins le group_id du 
 * 			projet à synchroniser avec le IM .ici group_id=225.Pour cela il faut 
 * 			approuver un projet lorsque le serveur openfire est arréte,reccupérer 
 * 			le group_id de ce projet et enfin remettre le serveur en marche avant d'exécuter ce script. 
 * 		3 - l'identification ,$id,du Plugin IM doit être correcte il 10 ici
 * 		
 * 
 * Post-conditions:
 * 		1 - un muc room et un sharedgroup sont créés sans erreur .
 */
 ini_set('include_path',"/home/zdiallo/Codendi/dev_server/codendi/src/www/include:/home/zdiallo/Codendi/dev_server/codendi/src:.");
 require_once('pre.php');
 require_once("../include/IMPlugin.class.php");
 $id=10;
 
 $params=array("group_id"=>225);
 $plugin=new IMPlugin($id,IM_DEBUG_ON);
 echo "Begin##########################################################################";
 
 $plugin->im_process ($params);
 echo 'Nom du groupe créé'.$plugin->get_last_grp_name();
 
 echo "End###############################################################################<br> "
?>
