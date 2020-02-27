<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\CookieManager;

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../include/utils.php';

$cookie_manager = new CookieManager();
$user = UserManager::instance()->getCurrentUser();

// Validate params
session_require(array('isloggedin'=>1));

$request = HTTPRequest::instance();

$csrf = new CSRFSynchronizerToken('/account/index.php');
$csrf->check();

$username_display = null;
if ($request->existAndNonEmpty('username_display')) {
    if ($request->valid(new Valid_WhiteList('username_display', array(UserHelper::PREFERENCES_NAME_AND_LOGIN,
                                                                     UserHelper::PREFERENCES_LOGIN_AND_NAME,
                                                                     UserHelper::PREFERENCES_LOGIN,
                                                                     UserHelper::PREFERENCES_REAL_NAME)))) {
        $username_display = $request->get('username_display');
    } else {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_preferences', 'error_username_display'));
    }
}

if ($username_display !== null) {
    user_set_preference("username_display", $username_display);
}

// Output
session_redirect("/account/index.php");
