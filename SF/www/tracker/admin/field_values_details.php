<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
//
//
//  Written for CodeX by Stephane Bouhet
//

$LANG->loadLanguageMsg('tracker/tracker');

if ( !user_isloggedin() ) {
	exit_not_logged_in();
	return;
}

if ( !$ath->userIsAdmin() ) {
	exit_permission_denied();
	return;
}

// Check if this tracker is valid (not deleted)
if ( !$ath->isValid() ) {
	exit_error($LANG->getText('global','error'),$LANG->getText('tracker_add','invalid'));
}

$ath->adminHeader(array('title'=>$LANG->getText('tracker_admin_field_usage','tracker_admin').$LANG->getText('tracker_admin_field_values_details','values_admin'),
			'help' => 'TrackerAdministration.html#TrackerBrowsingTrackerFieldValues'));

echo "<H2>".$LANG->getText('tracker_import_admin','tracker')." <a href=\"/tracker/admin/?group_id=".$group_id."&atid=".$atid."\">".$ath->getName()."</a>'".
$LANG->getText('tracker_admin_field_values_details','manage_for',$field->getLabel())."</H2>";

if ( !$field->isSelectBox() && !$field->isMultiSelectBox() ) {
	$ath->displayDefaultValueForm($field_id,$field->getDefaultValue());
} else {
	if ( $field->getValueFunction() ) {
	  // MLS have to add here the choose default value
		$ath->displayValueFunctionForm($field_id,$field->getValueFunction());
		$ath->displayDefaultValueFunctionForm($field_id,$field->getDefaultValue(),$field->getValueFunction());
	} else {
		$ath->displayFieldValuesList($field_id);
		$ath->displayDefaultValueForm($field_id,$field->getDefaultValue());
		// For severity field, we don't display the Bing form or the Create Form
		if ( $field->getName() != "severity" ) {
			$ath->displayFieldValueForm("value_create",$field_id);
			$ath->displayValueFunctionForm($field_id,"","Or");
		}
	}
}

$ath->footer(array());

?>
