<?php
// Codendi
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com
//
//
//
//    Originally written by Laurent Julliard 2003, Codendi Team, Xerox
//
// Purpose: Display contextual help for artifact search criteria.
//              Help depends upon the field type.
require_once __DIR__ . '/../include/pre.php';

$request    = HTTPRequest::instance();
$helpid     = $request->get('helpid');
$field_info = $request->get('field_info');
// Get the group_id, group_artifact_id and field_name from the
// help_id argument
list($group_id, $artifact_type_id, $field_name) = explode('|', urldecode($helpid));

//    get the Group object
$pm = ProjectManager::instance();
$group = $pm->getProject($group_id);
if (!$group || !is_object($group) || $group->isError()) {
    exit_no_group();
}

//    Create the ArtifactType object
$at = new ArtifactType($group, $artifact_type_id);
if (!$at || !is_object($at)) {
    exit_error($Language->getText('global', 'error'), $Language->getText('help_browse_tracker_query_field', 'at_not_created'));
}
if ($at->isError()) {
    exit_error($Language->getText('global', 'error'), $at->getErrorMessage());
}

// Create field factory
$aff = new ArtifactFieldFactory($at);
if (!$aff || !is_object($aff)) {
    exit_error($Language->getText('global', 'error'), $Language->getText('help_browse_tracker_query_field', 'aff_not_created'));
}
if ($aff->isError()) {
    exit_error($Language->getText('global', 'error'), $aff->getErrorMessage());
}

$field = $aff->getFieldFromName($field_name);
if (!$field || !is_object($field)) {
    exit_error($Language->getText('global', 'error'), $Language->getText('help_browse_tracker_query_field', 'af_not_created'));
}
if ($field->isError()) {
    exit_error($Language->getText('global', 'error'), $field->getErrorMessage());
}

$field_type = $field->getLabelFieldType();

if ($field->isSelectBox()) {
    $cug_section = 'ArtifactSelectBoxField';
} elseif ($field->isMultiSelectBox()) {
    $cug_section = 'ArtifactMultiSelectBoxField';
} elseif ($field->isTextField()) {
    if ($field->data_type == $field->DATATYPE_TEXT) {
        $cug_section = 'ArtifactTextField';
    } elseif ($field->data_type == $field->DATATYPE_INT) {
        $cug_section = 'ArtifactIntegerField';
    } elseif ($field->data_type == $field->DATATYPE_FLOAT) {
        $cug_section = 'ArtifactFloatingPointNumberField';
    }
} elseif ($field->isDateField()) {
    $cug_section = 'ArtifactDateField';
} elseif ($field->isTextArea()) {
    $cug_section = 'ArtifactTextField';
}

// Display the customized help frame at the top with info for this specific field
if ($field_info) {
    // Show the artifact field info in the top frame
    help_header($Language->getText('help_browse_tracker_query_field', 'art_search_criteria'));
    print '<TABLE class="contenttable" cellpadding="0" cellspacing="0" border="0">'."\n";
    print '<TR><TD width="20%">'.$Language->getText('help_browse_bug_query_field', 'field_name').':</TD><TD><B>'.$field->getLabel()."</B></TD>\n";
    print '<TR><TD width="20%">'.$Language->getText('help_browse_bug_query_field', 'field_type').':</TD><TD><B>'.$field->getLabelFieldType()."</B></TD>\n";
    print "</TABLE>\n";
    print '<hr><u>'.$Language->getText('help_browse_bug_query_field', 'description').'</u>:<I>'.$field->getDescription().'</I>'."\n";
    help_footer();
} else {
    // send the frameset: at top we want the artifact description and
    // the relevant user guide section at the bottom
    echo '
    <HTML>
    <FRAMESET rows="30%,70%">
    <FRAME src="?helpid='.$helpid.'&field_info=1" frameborder="0">
    <FRAME src="/doc/'.$request->getCurrentUser()->getShortLocale().'/user-guide/ArtifactBrowsing.html#'.$cug_section.'" frameborder="0">
    </FRAMESET></HTML>';
}
