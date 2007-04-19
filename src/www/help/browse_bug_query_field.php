<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
// http://codex.xerox.com
//
// $Id: browse_bug_query_field.php 1418 2005-04-08 13:17:03Z schneide $
//
//	Originally written by Laurent Julliard 2001, 2002, CodeX Team, Xerox
//

require_once('pre.php');
require_once('www/bugs/bug_utils.php');
require_once('www/bugs/bug_data.php');
$Language->loadLanguageMsg('help/help');

// Initialize the global data structure before anyhting else
bug_init($group_id);
$field = urldecode($helpid);

// get the SQL field type
$res_type = db_query("SHOW COLUMNS FROM bug LIKE '$field'");
    
if (db_numrows($res_type)<1) {
  print $Language->getText('help_browse_bug_query_field','no_field',$field);
    exit;
}
$sql_type = db_result($res_type,0,'Type');
    
// Adjust field type and set help msg according to field type
if (bug_data_is_date_field($field)) {
    $fld_type = 'Date';
    $cug_section = 'BugDateField';
} else if ( preg_match('/int/i',$sql_type) ) {
	
    if (bug_data_is_select_box($field)) {
	$fld_type = 'Select Box';
	$cug_section = 'BugSelectBoxField';
    } else {
	$fld_type = 'Integer';
	$cug_section = 'BugIntegerField';
    }
	
} else if ( preg_match('/float/i',$sql_type) ) {
    $fld_type = 'Floating Point Number';
    $cug_section = 'BugFloatingPointNumberField';
	
}  else if ( preg_match('/text|varchar|blob/i',$sql_type) ) {
    $fld_type = 'Text';
    $cug_section = 'BugTextField';
} else {
    $fld_type = 'Unknown';
}

// Display the customized help frame at the top with info for this specific field
if ($bug_info) {

    // Show the bug field info in the top frame
    help_header($Language->getText('help_browse_bug_query_field','bug_search_criteria'));
    print '<TABLE class="contenttable" cellpadding="0" cellspacing="0" border="0">'."\n";
    print '<TR><TD width="20%">'.$Language->getText('help_browse_bug_query_field','field_name').':</TD><TD><B>'.bug_data_get_label($field)."</B></TD>\n";
    print '<TR><TD width="20%">'.$Language->getText('help_browse_bug_query_field','field_type').':</TD><TD><B>'.$fld_type."</B></TD>\n";
    print "</TABLE>\n"; 
    print '<hr><u>'.$Language->getText('help_browse_bug_query_field','description').'</u>:<I>'.bug_data_get_description($field).'</I>'."\n";
    help_footer();

} else {

    // send the frameset: at top we want the bug description and
    // the relevant user guide section at the bottom
    echo '
    <HTML>
    <FRAMESET rows="30%,70%">
    <FRAME src="'.$PHP_SELF.'?helpid='.$helpid.'&bug_info=1" frameborder="0">
    <FRAME src="/help/show_help.php?section=BugBrowsing.html#'.$cug_section.'" frameborder="0">
    </FRAMESET></HTML>';
}
?>
