<?php
/**
 * Copyright (c) Enalean, 2015-Present. All rights reserved
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

use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\HeaderConfigurationBuilder;

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
$_cVar = [];
// Raw variables
$_rVar   = [];
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
$success      = false;
$status       = null;
$current_user = null;
if ($request->isPost()) {
    $login_csrf->check();
    if (! $_rVar['form_loginname'] || ! $_rVar['form_pw']) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('include_session', 'missing_pwd'));
    } else {
        $um->login($_rVar['form_loginname'], new \Tuleap\Cryptography\ConcealedString($_rVar['form_pw']));
        sodium_memzero($_rVar['form_pw']);
        $current_user = $um->getCurrentUserWithLoggedInInformation();
        $status       = $current_user->user->getStatus();
        $success      = true;
    }
}

// Redirect user to the right page:
// If the user is valid either because is just succeeded to login or because
// she has a valid session, tries to redirect to the right value. This may happens
// if you receive a mail with 2 docs to read. You click on both link and you get
// 2 login forms. You identicate in the first tab and you reload the second one.
// The reload (a /account/login.php?return_to=... url) should redirect you to the
// doc instead of displaying login page again.
if ($current_user === null) {
    $current_user = $um->getCurrentUserWithLoggedInInformation();
}
if ($current_user->is_logged_in && ($success === true || $request->get('prompt') !== 'login')) {
    account_redirect_after_login($current_user->user, $_rVar['return_to'] ?? '');
}

// Display login page
// Display mode
$pvMode = false;
if ($_cVar['pv'] == 2) {
    $pvMode = true;
}

$presenter_builder = new User_LoginPresenterBuilder($em);
$presenter         = $presenter_builder->build(
    (string) $_rVar['return_to'],
    (int) $_cVar['pv'],
    (string) $_rVar['form_loginname'],
    $login_csrf,
    (string) $request->get('prompt')
);

if ($pvMode) {
    $GLOBALS['HTML']->pv_header(['title' => $presenter->account_login_page_title()]);
} else {
    $GLOBALS['HTML']->header(
        HeaderConfigurationBuilder::get($presenter->account_login_page_title())
            ->withBodyClass(['login-page'])
            ->build()
    );
}

$login_controller->index($presenter);

if ($pvMode) {
    $GLOBALS['HTML']->pv_footer();
} else {
    $GLOBALS['HTML']->footer(FooterConfiguration::withoutContent());
}
