<?php
// Code PHP
# on affiche l'heure en cours.
require_once ('pre.php');
require_once ('www/project/admin/permissions.php');
if($_GET['action']=='permissions_frs'){
	permission_display_selection_frs("PACKAGE_READ", $_GET['package_id'], $_GET['group_id']);
}

?>
