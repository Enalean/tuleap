<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('viewvc_utils.php');
require_once('www/svn/svn_utils.php');

$Language->loadLanguageMsg('svn/svn');
if (user_isloggedin()) {
    $vRoot = new Valid_String('root');
    $vRoot->required();
    if(!$request->valid($vRoot)) {
        exit_no_group();
    }
    $root = $request->get('root');
    $group_id = group_getid_by_name($root);
    if($group_id === false) {
        exit_no_group();
    }

    $vRootType = new Valid_WhiteList('roottype', array('svn'));
    $vRootType->setErrorMessage($Language->getText('svn_viewvc','bad_roottype'));
    $vRootType->required();
    if($request->valid($vRootType)) {

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
        svn_header(array ('title'=>$Language->getText('svn_utils','browse_tree')));
        site_footer(array());
    }
} else {
  exit_not_logged_in();
}

?>
