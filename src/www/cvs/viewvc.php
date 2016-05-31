<?php
//
// Copyright (c) Enalean, 2016. All Rights Reserved.
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('viewvc_utils.php');
require_once('www/cvs/commit_utils.php');


if (user_isloggedin()) {
  // be backwards compatible with old viewvc.cgi links that are now redirected
  $request = HTTPRequest::instance();
  $root    = $request->get('root');
  if (!$root) {
    $root = $cvsroot;
  }

  $root     = db_es($root);
  $res_grp  = db_query("SELECT * FROM groups WHERE unix_group_name='".$root."'");
  $row_grp  = db_fetch_array($res_grp);
  $group_id = $row_grp['group_id'];
  
  if (!check_cvs_access(user_getname(), $root, viewvc_utils_getfile("/cvs/viewvc.php"))) {
      exit_error($GLOBALS['Language']->getText('cvs_viewvc', 'error_noaccess'),
		 $GLOBALS['Language']->getText('cvs_viewvc', 'error_noaccess_msg',session_make_url("/project/memberlist.php?group_id=$group_id")));
  }

  viewvc_utils_track_browsing($group_id,'cvs');

  $display_header_footer = viewvc_utils_display_header();

  if ($display_header_footer) {
    commits_header(array(
        'title'     => $GLOBALS['Language']->getText('cvs_viewvc', 'title'),
        'stylesheet'=> (array('/viewvc-static/styles.css')),
        'group'     => $group_id
    ));
  }

  viewvc_utils_passcommand();

  if ($display_header_footer) {
    site_footer(array());
  }

} else {
  exit_not_logged_in();
}
