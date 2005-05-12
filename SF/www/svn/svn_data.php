<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2004. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
//      Originally written by Laurent Julliard 2004 CodeX Team, Xerox
//


function svn_data_get_technicians($group_id) {

    // FIXME: Ideally this should be a merge of the current project member list
    // with the list of all people who once committed something in the CVS
    // and may have been removed from the project since then.
    // problem is: the structure of the cvs tracker does not allow for a quick 
    // search. 
    $sql="SELECT distinct user.user_name, user.user_name ".
	"FROM user, user_group ".
	"WHERE (user_group.group_id='$group_id' ".
	"AND user.user_id=user_group.user_id) ".
	"ORDER BY user.user_name ASC";

	return db_query($sql);
}

function svn_data_update_general_settings($group_id,$svn_tracked,$svn_preamble) {
    
    $query = "update groups set svn_tracker='".$svn_tracked.
	"', svn_preamble='".htmlspecialchars($svn_preamble).
	"' where group_id='".$group_id."'";
    $result = db_query($query);
    return ($result ? true : false);
}
   
function svn_data_update_notification($group_id,$svn_mailing_list,$svn_mailing_header) {
    
    $query = "update groups set svn_events_mailing_list='".$svn_mailing_list.
	"', svn_events_mailing_header='".$svn_mailing_header.
	"' where group_id='".$group_id."'";
    $result = db_query($query);
    return ($result ? true : false);
}
   
// list the number of commits by user either since the beginning of
// history if the period argument is not given or if it is given then
// over the last "period" of time.
// period is expressed in seconds
function svn_data_get_svn_history($group_id, $period=false) {
    $date_clause = '';
    if ($period) {
	// All times in svn tables are stored in UTC!!!
	$date_clause = " AND date >= ".(gmdate('U')-$period)." ";
    }
    $query = "SELECT whoid, user.user_name, count(id) as commits ".
	"FROM svn_commits, user ".
	"WHERE svn_commits.whoid=user.user_id AND svn_commits.group_id=$group_id ".
	$date_clause.
	"GROUP BY whoid ORDER BY user_name";
    $result = db_query($query);
    return($result);
}

function svn_data_get_revision_detail($group_id, $commit_id, $rev_id=0, $order='') {
    $order_str = "";
    if ($order) {
	if ($order != 'filename')
	    $order_str = " ORDER BY ".$order;
	else
	    $order_str = " ORDER BY dir, file";
    }

    // if the subversion revision id is given then it akes precedence on 
    // the internal commit_id (this is to make it easy for users to build
    // URL to access a revision
    if ($rev_id) {
	// To be done -> get the commit ID from the svn-commit table
      $sql="SELECT svn_commits.description, svn_commits.date, svn_commits.revision, svn_checkins.type,svn_checkins.commitid,svn_dirs.dir,svn_files.file ".
	"FROM svn_dirs, svn_files, svn_checkins, svn_commits ".
	"WHERE svn_checkins.fileid=svn_files.id ".
	"AND svn_checkins.dirid=svn_dirs.id ".
	"AND svn_checkins.commitid=svn_commits.id ".
	"AND svn_commits.revision=$rev_id ".
	"AND svn_commits.group_id=$group_id ".
	$order_str;
    } else {

      $sql="SELECT svn_commits.description, svn_commits.date, svn_commits.revision, svn_checkins.type,svn_checkins.commitid,svn_dirs.dir,svn_files.file ".
	"FROM svn_dirs, svn_files, svn_checkins, svn_commits ".
	"WHERE svn_checkins.fileid=svn_files.id ".
	"AND svn_checkins.dirid=svn_dirs.id ".
	"AND svn_checkins.commitid=svn_commits.id ".
	"AND svn_commits.id=$commit_id ".
	$order_str;
    }

    $result=db_query($sql);
    return $result;
}

?>
