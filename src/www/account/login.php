<?php
/**
 * Copyright (c) Enalean, 2015-2018. All rights reserved
 * Copyright 1999-2000 (c) The SourceForge Crew
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

header("Expires: Wed, 11 Nov 1998 11:11:11 GMT");
header("Cache-Control: no-cache, no-store, must-revalidate");

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../include/account.php';

$login_controller = new User_LoginController($request);

if ($request->get('confirm_hash')) {
    $login_controller->confirmHash();
}

$em = EventManager::instance();

// Validate input
// Clean variables
$_cVar = array();
// Raw variables
$_rVar = array();
$request = HTTPRequest::instance();

$_rVar['form_loginname'] = null;
if ($request->valid(new Valid_String('form_loginname'))) {
    $_rVar['form_loginname'] = $request->get('form_loginname');
}

$_rVar['form_pw'] = null;
if ($request->valid(new Valid_String('form_pw'))) {
    $_rVar['form_pw'] = $request->get('form_pw');
}

$_cVar['pv'] = null;
if ($request->valid(new Valid_Pv())) {
    $_cVar['pv'] = (int) $request->get('pv');
}

$_rVar['return_to'] = null;
if ($request->valid(new Valid_String('return_to'))) {
    $_rVar['return_to'] = $request->get('return_to');
}

// Application
$um         = UserManager::instance();
$login_csrf = new CSRFSynchronizerToken('/account/login.php');

// first check for valid login, if so, redirect
$success = false;
$status  = null;
$user    = null;
if ($request->isPost()) {
    $login_csrf->check();
    if (!$_rVar['form_loginname'] || !$_rVar['form_pw']) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('include_session', 'missing_pwd'));
    } else {
        $user = $um->login($_rVar['form_loginname'], $_rVar['form_pw']);
        $status = $user->getStatus();
    }
}

// Redirect user to the right page:
// If the user is valid either because is just succeeded to login or because
// she has a valid session, tries to redirect to the right value. This may happens
// if you receive a mail with 2 docs to read. You click on both link and you get
// 2 login forms. You identicate in the first tab and you reload the second one.
// The reload (a /account/login.php?return_to=... url) should redirect you to the
// doc instead of displaying login page again.
if ($user === null) {
    $user = $um->getCurrentUser();
}
if ($user->isLoggedIn()) {
    account_redirect_after_login($_rVar['return_to']);
}

// Display login page
// Display mode
$pvMode = false;
if ($_cVar['pv'] == 2) {
    $pvMode = true;
}

$presenter_builder = new User_LoginPresenterBuilder();
$presenter = $presenter_builder->build(
    $_rVar['return_to'],
    $_cVar['pv'],
    $_rVar['form_loginname'],
    $request->isSecure(),
    $login_csrf
);

if ($pvMode) {
    $GLOBALS['HTML']->pv_header(array('title' => $presenter->account_login_page_title()));
} else {
    $GLOBALS['HTML']->header(array('title' => $presenter->account_login_page_title(), 'body_class' => array('login-page')));
}

$login_controller->index($presenter);

if ($pvMode) {
    $GLOBALS['HTML']->pv_footer(array());
} else {
    $GLOBALS['HTML']->footer(array('without_content' => true));
}
