<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
//	Originally written by Laurent Julliard 2003, CodeX Team, Xerox
//
// Purpose: Display contextual help for artifact search criteria. 
//              Help depends upon the field type.
//

require "pre.php";
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactType.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactFieldFactory.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactField.class');

// Get the group_id, group_artifact_id and field_name from the 
// help_id argument
list($group_id, $artifact_type_id, $field_name) = explode('|',urldecode($helpid));

//
//	get the Group object
//
$group = group_get_object($group_id);
if (!$group || !is_object($group) || $group->isError()) {
    exit_no_group();
}

//
//	Create the ArtifactType object
//
$at = new ArtifactType($group,$artifact_type_id);
if (!$at || !is_object($at)) {
    exit_error('Error','ArtifactType could not be created');
}
if ($at->isError()) {
    exit_error('Error',$ath->getErrorMessage());
}

// Create field factory
$aff = new ArtifactFieldFactory($at);
if (!$aff || !is_object($aff)) {
    exit_error('Error','ArtifactFieldFactory could not be created');
}
if ($aff->isError()) {
    exit_error('Error',$aff->getErrorMessage());
}

$field = $aff->getFieldFromName($field_name);
if (!$field || !is_object($field)) {
    exit_error('Error','ArtifactField could not be created');
}
if ($field->isError()) {
    exit_error('Error',$field->getErrorMessage());
}

$field_type = $field->getLabelFieldType();

if ( $field->isSelectBox() ) {
    $cug_section = 'ArtifactSelectBoxField';
} else if ($field->isMultiSelectBox() ) {
    $cug_section = 'ArtifactMultiSelectBoxField';
} else if ($field->isTextField() ) {

    if ($field->data_type == $field->DATATYPE_TEXT) {
	$cug_section = 'ArtifactTextField';
    } else if ($field->data_type == $field->DATATYPE_INT){
	$cug_section = 'ArtifactIntegerField';
    } else if ($field->data_type == $field->DATATYPE_FLOAT){
	$cug_section = 'ArtifactFloatingPointNumberField';
    } 
} else if ($field->isDateField() ) {
	$cug_section = 'ArtifactDateField';
} else if ($field->isTextArea() ) {
	$cug_section = 'ArtifactTextField';
}


// Display the customized help frame at the top with info for this specific field
if ($field_info) {

    // Show the artifact field info in the top frame
    help_header("Artifact Search -  Selection Criteria");
    print '<TABLE class="contenttable" cellpadding="0" cellspacing="0" border="0">'."\n";
    print '<TR><TD width="20%">Field Name:</TD><TD><B>'.$field->getLabel()."</B></TD>\n";
    print '<TR><TD width="20%">Field Type:</TD><TD><B>'.$field->getLabelFieldType()."</B></TD>\n";
    print "</TABLE>\n"; 
    print '<hr><u>Description</u>:<I>'.$field->getDescription().'</I>'."\n";
    help_footer();

} else {

    // send the frameset: at top we want the artifact description and
    // the relevant user guide section at the bottom
    echo '
    <HTML>
    <FRAMESET rows="30%,70%">
    <FRAME src="'.$PHP_SELF.'?helpid='.$helpid.'&field_info=1" frameborder="0">
    <FRAME src="/help/show_help.php?section=ArtifactBrowsing.html#'.$cug_section.'" frameborder="0">
    </FRAMESET></HTML>';
}
?>
