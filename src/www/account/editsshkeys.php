<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once 'pre.php';
require_once 'account.php';

session_require(array('isloggedin'=>1));

$user_manager = UserManager::instance();
$user         = $user_manager->getCurrentUser();

$request = HTTPRequest::instance();

if ($request->isPost()
    && $request->exist('submit')
    && $request->exist('form_authorized_keys')) {

    $user_manager->addSSHKey($user, $request->get('form_authorized_keys'));

}

$GLOBALS['Response']->redirect('/account/');