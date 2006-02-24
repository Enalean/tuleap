<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

  //$Language->loadLanguageMsg('include/include');

function exit_error($title,$text) {
    global $HTML,$Language;
    $GLOBALS['feedback'] .= $title;
    site_header(array('title'=>$Language->getText('include_exit','exit_error')));
    echo '<p>',$text,'</p>';
	$HTML->footer(array('showfeedback' => false));
	exit;
}

function exit_permission_denied() {
  global $feedback,$Language;
    exit_error($Language->getText('include_exit','perm_denied'),$Language->getText('include_exit','no_perm').'<p>'.$feedback);
}

function exit_not_logged_in() {
  global $REQUEST_URI,$Language;
    //instead of a simple error page, now take them to the login page
    header ("Location: /account/login.php?return_to=".urlencode($REQUEST_URI));
    //exit_error($Language->getText('include_exit','not_logged_in'),$Language->getText('include_exit','need_to_login'));
}

function exit_no_group() {
  global $feedback,$Language;
    exit_error($Language->getText('include_exit','choose_proj_err'),$Language->getText('include_exit','no_gid_err').'<p>'.$feedback);
}

function exit_missing_param() {
  global $feedback,$Language;
  // Display current $feedback normally, and replace feedback with error message
  $msg=$feedback;
  $feedback="";
  exit_error($Language->getText('include_exit','missing_param_err'),'<p>'.$msg);
}

?>
