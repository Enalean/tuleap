<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
//
//
//  Written for CodeX by Stephane Bouhet
//

$ath->adminHeader(array('title'=>'Tracker Administration - Field Values Administration','help' => 'HELP_FIXME.html'));
echo "<H2>Tracker '<a href=\"/tracker?group_id=".$group_id."&atid=".$atid."\">".$ath->getName()."</a>' - Field '<a href=\"/tracker/admin/?group_id=".$group_id."&atid=".$atid."&func=display_field_update&field_id=".$field->getID()."\">".$field->getLabel()."</a>'<br>Field Values Administration</H2>";
if ( $field->getValueFunction() ) {
	$ath->displayValueFunctionForm($field_id,$field->getValueFunction());
} else {
	$ath->displayFieldValuesList($field_id);
	$ath->displayFieldValueForm("value_create",$field_id);
	$ath->displayValueFunctionForm($field_id,"","Or");
}
$ath->footer(array());

?>
