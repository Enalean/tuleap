<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

$LANG->loadLanguageMsg('svn/svn');


if (!$group_id) {
    exit_no_group(); // need a group_id !!!
}
$project = project_get_object($group_id);
$group_name = $project->getUnixName();


$result = svn_data_get_revision_detail($group_id, $commit_id, $rev_id, $order);

if (db_numrows($result) > 0) {
    svn_header(array ('title'=>$LANG->getText('svn_detail_revision','svn_rev',$revision),
			  'help' => 'SubversionBrowsingInterface.html'));
    svn_utils_show_revision_detail($result,$group_id,$group_name,$commit_id);
    svn_footer(array());
} else {
    exit_error($LANG->getText('global','error'),$LANG->getText('svn_detail_revision','id_not_found',$commit_id));
}

?>
