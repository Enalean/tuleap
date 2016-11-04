<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\User\Admin;

use DateHelper;
use PFUser;

class UserDetailsAccessPresenter
{
    public function __construct(PFUser $user, array $user_info)
    {
        $this->last_access_date_label = $GLOBALS['Language']->getText('admin_usergroup', 'last_access_date');
        $this->last_access_date_ago   = DateHelper::timeAgoInWords($user_info['last_access_date']);
        $this->last_access_date       = date($GLOBALS['Language']->getText('system', 'datefmt'), $user_info['last_access_date']);

        $this->last_pwd_update_label = $GLOBALS['Language']->getText('admin_usergroup', 'last_pwd_update');
        $this->last_pwd_update_ago   = DateHelper::timeAgoInWords($user->getLastPwdUpdate());
        $this->last_pwd_update       = date($GLOBALS['Language']->getText('system', 'datefmt'), $user->getLastPwdUpdate());

        $this->auth_attempt_last_success_label = $GLOBALS['Language']->getText('account_options', 'auth_attempt_last_success');
        $this->auth_attempt_last_success_ago   = DateHelper::timeAgoInWords($user_info['last_auth_success']);
        $this->auth_attempt_last_success       = date($GLOBALS['Language']->getText('system', 'datefmt'), $user_info['last_auth_success']);

        $this->auth_attempt_last_failure_label = $GLOBALS['Language']->getText('account_options', 'auth_attempt_last_failure');
        $this->auth_attempt_last_failure_ago   = DateHelper::timeAgoInWords($user_info['last_auth_failure']);
        $this->auth_attempt_last_failure       = date($GLOBALS['Language']->getText('system', 'datefmt'), $user_info['last_auth_failure']);

        $this->auth_attempt_nb_failure_label = $GLOBALS['Language']->getText('account_options', 'auth_attempt_nb_failure');
        $this->auth_attempt_nb_failure       = $user_info['nb_auth_failure'];

        $this->auth_attempt_prev_success_label = $GLOBALS['Language']->getText('account_options', 'auth_attempt_prev_success');
        $this->auth_attempt_prev_success_ago   = DateHelper::timeAgoInWords($user_info['last_auth_success']);
        $this->auth_attempt_prev_success       = date($GLOBALS['Language']->getText('system', 'datefmt'), $user_info['last_auth_success']);

        $this->member_since_label = $GLOBALS['Language']->getText('include_user_home', 'member_since');
        $this->member_since_ago   = DateHelper::timeAgoInWords($user->getAddDate());
        $this->member_since       = date($GLOBALS['Language']->getText('system', 'datefmt'), $user->getAddDate());
    }
}
