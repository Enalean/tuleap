<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require_once('viewcvs_utils.php');
require_once('www/cvs/commit_utils.php');

$Language->loadLanguageMsg('cvs/cvs');

if (user_isloggedin()) {
  // be backwards compatible with old viewcvs.cgi links that are now redirected
  if (!$root) $root = $cvsroot;

  if (!check_cvs_access(user_getname(), $root, viewcvs_utils_getfile("/cvs/viewcvs.php"))) {
      exit_error($Language->getText('cvs_viewcvs', 'error_noaccess'),
		 $Language->getText('cvs_viewcvs', 'error_noaccess_msg'));
  }

  $res_grp = db_query("SELECT * FROM groups WHERE unix_group_name='".$root."'");
  $row_grp = db_fetch_array($res_grp);
  $group_id = $row_grp['group_id'];

  viewcvs_utils_track_browsing($group_id,'cvs');

  $display_header_footer = viewcvs_utils_display_header();

  if ($display_header_footer) {
    commits_header(array ('title'=>$Language->getText('cvs_viewcvs', 'title')));
  }

  viewcvs_utils_passcommand();

  if ($display_header_footer) {
    site_footer(array());
  }

} else {
  exit_not_logged_in();
}


?>
