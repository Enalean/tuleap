<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../include/account.php';

session_require(array('isloggedin' => 1));

$csrf = new CSRFSynchronizerToken('/account/index.php');
$csrf->check();

$request           = HTTPRequest::instance();
$user              = UserManager::instance()->getCurrentUser();
$svn_token_handler = new SVN_TokenHandler(
    new SVN_TokenDao(),
    new RandomNumberGenerator(),
    PasswordHandlerFactory::getPasswordHandler()
);

if ($request->exist('delete-svn-tokens')
    && $request->exist('svn-tokens-selected')
    && is_array($request->get('svn-tokens-selected'))) {
    if ($svn_token_handler->deleteSVNTokensForUser($user, $request->get('svn-tokens-selected'))) {
        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('account_options', 'delete_svn_tokens_success'));
    } else {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_options', 'delete_svn_tokens_error'));
    }
}

if ($request->exist('generate-svn-token')) {
    $token = $svn_token_handler->generateSVNTokenForUser($user, $request->get('generate-svn-token-comment'));

    if ($token) {
        $_SESSION['last_svn_token'] = $token;
        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('account_options', 'generate_svn_token_success'));
    } else {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_options', 'generate_svn_token_error'));
    }
}

$GLOBALS['Response']->redirect('/account/');
