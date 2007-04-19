<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: viewvc.php 3755 2006-09-26 15:51:31Z guerin $

require_once('pre.php');
require_once('viewvc_utils.php');
require_once('www/cvs/commit_utils.php');

$Language->loadLanguageMsg('cvs/cvs');

if (user_isloggedin()) {
  // be backwards compatible with old viewvc.cgi links that are now redirected
  if (!$root) $root = $cvsroot;

  $res_grp = db_query("SELECT * FROM groups WHERE unix_group_name='".$root."'");
  $row_grp = db_fetch_array($res_grp);
  $group_id = $row_grp['group_id'];
  
  if (!check_cvs_access(user_getname(), $root, viewvc_utils_getfile("/cvs/viewvc.php"))) {
      exit_error($Language->getText('cvs_viewvc', 'error_noaccess'),
		 $Language->getText('cvs_viewvc', 'error_noaccess_msg',session_make_url("/project/memberlist.php?group_id=$group_id")));
  }

  viewvc_utils_track_browsing($group_id,'cvs');

  $display_header_footer = viewvc_utils_display_header();

  if ($display_header_footer) {
    commits_header(array ('title'=>$Language->getText('cvs_viewvc', 'title'),'stylesheet'=>(array('/viewvc-static/styles.css'))));
  }

  viewvc_utils_passcommand();

  if ($display_header_footer) {
    site_footer(array());
  }

} else {
  exit_not_logged_in();
}


?>
