<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require_once('viewvc_utils.php');
require_once('www/svn/svn_utils.php');

$Language->loadLanguageMsg('svn/svn');

if (user_isloggedin()) {

  $res_grp = db_query("SELECT * FROM groups WHERE unix_group_name='".$root."'");
  $row_grp = db_fetch_array($res_grp);
  $group_id = $row_grp['group_id'];

  if (!svn_utils_check_access(user_getname(), $root, viewvc_utils_getfile("/svn/viewvc.php"))) {
    exit_error($Language->getText('svn_viewvc','access_denied'), 
	       $Language->getText('svn_viewvc','acc_den_comment',session_make_url("/project/memberlist.php?group_id=$group_id")));
  }

  viewvc_utils_track_browsing($group_id,'svn');

  $display_header_footer = viewvc_utils_display_header();

  if ($display_header_footer) {
    svn_header(array ('title'=>$Language->getText('svn_utils','browse_tree'),'stylesheet'=>(array('/viewvc-static/styles.css'))));
  }

  viewvc_utils_passcommand();

  if ($display_header_footer) {
    site_footer(array());
  }

} else {
  exit_not_logged_in();
}

?>
