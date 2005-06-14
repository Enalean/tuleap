<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
//
//
//
//  Written for CodeX by Stephane Bouhet
//

require_once('pre.php');
require_once('common/tracker/ArtifactTypeFactory.class');

$Language->loadLanguageMsg('tracker/tracker');
?>
<html>
<head>
<title><? echo $Language->getText('tracker_selection','tracker_sel') ?></title>
<link rel="stylesheet" type="text/css" href="<? echo util_get_css_theme(); ?>">
<script language="JavaScript">

function doSelection(form) {
	if ( form.artifact_type_id.value != "" ) {
		window.opener.document.<? echo $opener_form; ?>.<? echo $opener_field; ?>.value = form.artifact_type_id.value;
	}
	close();
}

</script>
</head>
<body class="bg_help">
<form name="form_selection">
<table>
<tr valign="center"><td>
<div align="center">
<?
	//
	//	get the Group object
	//
	$group = group_get_object($group_id);
	if (!$group || !is_object($group) || $group->isError()) {
		exit_no_group();
	}

        $count = 0;
	$atf = new ArtifactTypeFactory($group);
	$trackers_array = $atf->getArtifactTypesFromId($group_id);
	if ( $trackers_array !== false) {
            echo '<select name="artifact_type_id" size="5">';	
            
            foreach($trackers_array as $tracker) {
                echo '<option value="'.$tracker->getId().'">'.$tracker->getName().'</option>';
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
</body>
</html>
