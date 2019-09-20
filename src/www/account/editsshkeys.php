<?php
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright (c) Enalean, 2015. All rights reserved
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../include/account.php';

session_require(array('isloggedin' => 1));

$user_manager = UserManager::instance();
$user         = $user_manager->getCurrentUser();

$csrf = new CSRFSynchronizerToken('/account/index.php');
$csrf->check();

$request = HTTPRequest::instance();

if ($request->isPost()
    && $request->exist('delete-keys')
    && $request->exist('ssh_key_selected')
    && is_array($request->get('ssh_key_selected'))) {
    $user_manager->deleteSSHKeys($user, $request->get('ssh_key_selected'));
}

if ($request->isPost()
    && $request->exist('add-keys')
    && $request->exist('form_authorized_keys')) {
    $user_manager->addSSHKeys($user, $request->get('form_authorized_keys'));
}

$GLOBALS['Response']->redirect('/account/');
