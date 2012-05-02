<?php
//
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
// 
//
//
//
//  Written for Codendi by Stephane Bouhet
//

require_once('pre.php');
require_once('common/tracker/ArtifactTypeFactory.class.php');

?>
<html>
<head>
<title><? echo $Language->getText('tracker_selection','tracker_sel') ?></title>
<link rel="stylesheet" type="text/css" href="<? echo util_get_css_theme(); ?>">
<script language="JavaScript">

function doSelection(form) {
	if ( form.artifact_type_id.value != "" ) {
		window.opener.document.<? echo preg_replace('/[^a-z0-9\$_]/', '', $request->get('opener_form')); ?>.<? echo preg_replace('/[^a-z0-9\$_]/', '', $request->get('opener_field')); ?>.value = form.artifact_type_id.value;
	}
	close();
}

</script>
</head>
<body class="bg_help">
<center>
<form name="form_selection">
<table>
<tr valign="center"><td>
<div align="center">
<?
	//
	//	get the Group object
	//
    $group_id = $request->getValidated('group_id', 'GroupId');
	$pm = ProjectManager::instance();
    $group = $pm->getProject($group_id);
	if (!$group || !is_object($group) || $group->isError()) {
		exit_no_group();
	}

        $count = 0;
	$atf = new ArtifactTypeFactory($group);
	$trackers_array = $atf->getArtifactTypesFromId($group_id);
	if ( $trackers_array !== false) {
            echo '<select name="artifact_type_id" size="5">';	
            $hp = Codendi_HTMLPurifier::instance();
            
            foreach($trackers_array as $tracker) {
                echo '<option value="'. (int)$tracker->getId().'">'. $hp->purify($tracker->getName()) .'</option>';
                $count ++;
            }
        }

?>
<? if ( $count > 0 ) { ?>
</select>
</td>
<td>
<input type="button" name="selection" value="Select" onClick="doSelection(form_selection)">
<? } else { ?>
<b><? echo $Language->getText('tracker_selection','no_tracker_available')?></b>
<br><br><input type="button" value="<? echo $Language->getText('global','btn_close') ?>" onClick="window.close()">
</td>
<td>
<? } ?>
</div>
</td></tr>
<table>
</form>
</center>
</body>
</html>
