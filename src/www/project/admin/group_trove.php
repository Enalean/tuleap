<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: group_trove.php 4953 2007-02-19 19:03:46 +0000 (Mon, 19 Feb 2007) nterray $

require_once('pre.php');    
require_once('trove.php');
require_once('www/project/admin/project_admin_utils.php');

require_once('common/include/HTTPRequest.class.php');
$request =& HTTPRequest::instance();

$Language->loadLanguageMsg('project/project');

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

// Check for submission. If so, make changes and redirect

if ($request->exist('Submit') && $request->exist('root1')) {
	group_add_history ('changed_trove',"",$group_id);

	// there is at least a $root1[xxx]
	while (list($rootnode,$value) = each($root1)) {
		// check for array, then clear each root node for group
		db_query('DELETE FROM trove_group_link WHERE group_id='.$group_id
			.' AND trove_cat_root='.$rootnode);
		
		for ($i=1;$i<=$GLOBALS['TROVE_MAXPERROOT'];$i++) {
			$varname = 'root'.$i;
			// check to see if exists first, then insert into DB
			if (${$varname}[$rootnode]) {
				trove_setnode($group_id,${$varname}[$rootnode],$rootnode);
			}
		}
	}
	session_redirect('/project/admin/?group_id='.$group_id);
}

project_admin_header(array('title'=>$Language->getText('project_admin_grouptrove','g_trove_info'),'group'=>$group_id));

// LJ New message added to explain that if a Topic category is not there
// LJ put the project unclassified and the CodeX team will create the
// Lj new entry
//
print '<P>'.$Language->getText('project_admin_grouptrove','select_3_classifs',$GLOBALS['sys_name']);

print "\n<FORM method=\"post\">";

// HTML select for all available categories for this group
print trove_get_html_allcat_selectfull($group_id);

print '<P><INPUT type="submit" name="Submit" value="'.$Language->getText('project_admin_grouptrove','submit_all_changes').'">';
print '</FORM>';

project_admin_footer(array());
?>
