<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require_once('viewcvs_utils.php');
require_once('www/svn/svn_utils.php');

$Language->loadLanguageMsg('svn/svn');

if (user_isloggedin()) {

  $res_grp = db_query("SELECT * FROM groups WHERE unix_group_name='".$root."'");
  $row_grp = db_fetch_array($res_grp);
  $group_id = $row_grp['group_id'];

  if (!svn_utils_check_access(user_getname(), $root, viewcvs_utils_getfile("/svn/viewcvs.php"))) {
    exit_error($Language->getText('svn_viewcvs','access_denied'), 
	       $Language->getText('svn_viewcvs','acc_den_comment',session_make_url("/project/memberlist.php?group_id=$group_id")));
  }

  viewcvs_utils_track_browsing($group_id,'svn');

  $display_header_footer = viewcvs_utils_display_header();

  if ($display_header_footer) {
    svn_header(array ('title'=>$Language->getText('svn_utils','browse_tree')));
  }

  viewcvs_utils_passcommand();

  if ($display_header_footer) {
    site_footer(array());
  }

} else {
  exit_not_logged_in();
}

?>
