<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require($DOCUMENT_ROOT.'/include/viewcvs_utils.php');
require($DOCUMENT_ROOT.'/cvs/commit_utils.php');

$LANG->loadLanguageMsg('cvs/cvs');

if (user_isloggedin()) {
  if (!check_cvs_access(user_getname(), $root, viewcvs_utils_getfile("/cvs/viewcvs.php"))) {
      exit_error($LANG->getText('cvs_viewcvs', 'error_noaccess'),
		 $LANG->getText('cvs_viewcvs', 'error_noaccess_msg'));
  }

  $res_grp = db_query("SELECT * FROM groups WHERE unix_group_name='".$root."'");
  $row_grp = db_fetch_array($res_grp);
  $group_id = $row_grp['group_id'];

  track_cvs_browse($group_id);

  $display_header_footer = viewcvs_utils_display_header();

  if ($display_header_footer) {
    commits_header(array ('title'=>$LANG->getText('cvs_viewcvs', 'title')));
  }

  viewcvs_utils_passcommand();

  if ($display_header_footer) {
    site_footer(array());
  }

} else {
  exit_not_logged_in();
}


function track_cvs_browse($group_id) {
  $query_string = getStringFromServer('QUERY_STRING');
  $request_uri = getStringFromServer('REQUEST_URI');

  if (strpos($query_string,"view=markup") !== FALSE ||
      strpos($request_uri,"*checkout*") !== FALSE ||
      strpos($query_string,"annotate=") !== FALSE) {

    $user_id = user_getid();
    $year   = strftime("%Y");
    $mon    = strftime("%m");
    $day    = strftime("%d");
    $db_day = $year.$mon.$day;

    $sql = "SELECT cvs_browse FROM group_cvs_full_history WHERE group_id = $group_id AND user_id = $user_id AND day = '$db_day'";
    $res = db_query($sql);
    if (db_numrows($res) > 0) {
	db_query("UPDATE group_cvs_full_history SET cvs_browse=cvs_browse+1 WHERE group_id = $group_id AND user_id = $user_id AND day = '$db_day'");
    } else {
	db_query("INSERT INTO group_cvs_full_history (group_id,user_id,day,cvs_browse) VALUES ($group_id,$user_id,'$db_day',1)");
    }
  }
}
?>
