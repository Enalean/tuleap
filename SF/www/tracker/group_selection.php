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

require('pre.php');
require($DOCUMENT_ROOT.'/../common/include/GroupFactory.class');

$gf = new GroupFactory();
?>
<html>
<head>
<title>Project Selection</title>
<link rel="stylesheet" type="text/css" href="<? echo util_get_css_theme(); ?>">
<script language="JavaScript">

function doSelection(form) {
	if ( form.group_id.value != "" ) {
		window.opener.document.<? echo $opener_form; ?>.<? echo $opener_field; ?>.value = form.group_id.value;
	}
	close();
}

function onChangeMemberFilter() {
	window.location = "/tracker/group_selection.php?opener_form=form_create&opener_field=group_id_template&filter=member";
}

function onChangeAllFilter() {
	window.location = "/tracker/group_selection.php?opener_form=form_create&opener_field=group_id_template&filter=all";
}

</script>
</head>
<body>
<form name="form_selection">
<table border="0" cellspacing="0" cellpadding="5">
  <tr valign="center">
    <td colspan="2">
<select name="group_id" size="8">
<?
	if ( $filter == "member" ) {
		$results = $gf->getMemberGroups();
	} else {
		$results = $gf->getAllGroups();
	}
    while ($groups_array = db_fetch_array($results)) {
    	echo '<option value="'.$groups_array["group_id"].'">'.$groups_array["group_name"].'</option>';
    }

?>
</select>
    </td>
  </tr>
  <tr>  
    <td><input type="radio" name="radiobutton" value="radiobutton"<? if ( $filter == "member" ) echo " checked"; ?> onClick="onChangeMemberFilter()"> my projects</td>
    <td><input type="radio" name="radiobutton" value="radiobutton"<? if ( $filter == "all" ) echo " checked"; ?> onClick="onChangeAllFilter()"> all projects</td>
  </tr>
  <tr>
    <td colspan="2">
    	<input type="button" name="selection" value="Select" onClick="doSelection(form_selection)">
    </td>
  </tr>
</table>

</form>
</body>
</html>
