<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
require "trove.php";
require ($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');
session_require(array('group'=>$group_id,'admin_flags'=>'A'));

// Check for submission. If so, make changes and redirect

if ($GLOBALS['Submit'] && $root1) {
	group_add_history ('Changed Trove',$rm_id,$group_id);

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

project_admin_header(array('title'=>'Group Trove Information','group'=>$group_id));

print '<P>Select up to three locations for this project in each of the
Trove root categories. If the project does not require any or all of these
locations, simply select "None Selected".

<P>IMPORTANT: Projects should be categorized in the most specific locations
available in the map. Simulteneous categorization in a specific category
AND a parent category will result in only the more specific categorization
being accepted.
';

print "\n<FORM method=\"post\">";

$CATROOTS = trove_getallroots();
while (list($catroot,$fullname) = each($CATROOTS)) {
	print "\n<HR>\n<P><B>$fullname</B> ".help_button('trove_cat',$catroot)."\n";

	$res_grpcat = db_query('SELECT trove_cat_id FROM trove_group_link WHERE '
		.'group_id='.$group_id.' AND trove_cat_root='.$catroot);
	for ($i=1;$i<=$GLOBALS['TROVE_MAXPERROOT'];$i++) {
		// each drop down, consisting of all cats in each root
		$name= "root$i"."[$catroot]";
		// see if we have one for selection
		if ($row_grpcat = db_fetch_array($res_grpcat)) {
			$selected = $row_grpcat["trove_cat_id"];	
		} else {
			$selected = 0;
		}
		trove_catselectfull($catroot,$selected,$name);
	}
}

print '<P><INPUT type="submit" name="Submit" value="Submit All Category Changes">';
print '</FORM>';

project_admin_footer(array());
?>
