<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\User\Admin;

use DateHelper;
use PFUser;
use Tuleap\InviteBuddy\Admin\InvitedByPresenter;

class UserDetailsAccessPresenter
{
    /**
     * @var string
     * @psalm-readonly
     */
    public $purified_last_access_date_ago;
    /**
     * @var string | null
     * @psalm-readonly
     */
    public $purified_last_pwd_update_ago;
    /**
     * @var string
     * @psalm-readonly
     */
    public $purified_auth_attempt_last_success_ago;
    /**
     * @var string
     * @psalm-readonly
     */
    public $purified_auth_attempt_last_failure_ago;
    /**
     * @var string
     * @psalm-readonly
     */
    public $purified_auth_attempt_prev_success_ago;
    /**
     * @var string
     * @psalm-readonly
     */
    public $purified_member_since_ago;
    /**
     * @var InvitedByPresenter|null
     * @psalm-readonly
     */
    public $invited_by;
    /**
     * @var string
     * @psalm-readonly
     */
    public $last_access_date_label;
    /**
     * @var false|string
     * @psalm-readonly
     */
    public $last_access_date;
    /**
     * @var string
     * @psalm-readonly
     */
    public $last_pwd_update_label;
    /**
     * @var false|string
     * @psalm-readonly
     */
    public $last_pwd_update;
    /**
     * @var string
     * @psalm-readonly
     */
    public $auth_attempt_last_success_label;
    /**
     * @var false|string
     * @psalm-readonly
     */
    public $auth_attempt_last_success;
    /**
     * @var string
     * @psalm-readonly
     */
    public $auth_attempt_last_failure_label;
    /**
     * @var false|string
     * @psalm-readonly
     */
    public $auth_attempt_last_failure;
    /**
     * @var string
     * @psalm-readonly
     */
    public $auth_attempt_nb_failure_label;
    /**
     * @var mixed
     * @psalm-readonly
     */
    public $auth_attempt_nb_failure;
    /**
     * @var string
     * @psalm-readonly
     */
    public $auth_attempt_prev_success_label;
    /**
     * @var false|string
     * @psalm-readonly
     */
    public $auth_attempt_prev_success;
    /**
     * @var string
     * @psalm-readonly
     */
    public $member_since_label;
    /**
     * @var false|string
     * @psalm-readonly
     */
    public $member_since;

    public function __construct(PFUser $user, array $user_info, ?InvitedByPresenter $invited_by)
    {
        $this->last_access_date_label = $GLOBALS['Language']->getText('admin_usergroup', 'last_access_date');
        $this->purified_last_access_date_ago = $this->getDate((int) $user_info['last_access_date'], $user);
        $this->last_access_date = date($GLOBALS['Language']->getText('system', 'datefmt'), (int) $user_info['last_access_date']);

        $this->last_pwd_update_label = $GLOBALS['Language']->getText('admin_usergroup', 'last_pwd_update');

        $this->purified_last_pwd_update_ago = $this->getDate((int) $user->getLastPwdUpdate(), $user);
        $this->last_pwd_update = date($GLOBALS['Language']->getText('system', 'datefmt'), (int) $user->getLastPwdUpdate());

        $this->auth_attempt_last_success_label = $GLOBALS['Language']->getText('account_options', 'auth_attempt_last_success');
        $this->purified_auth_attempt_last_success_ago = $this->getDate((int) $user_info['last_auth_success'], $user);
        $this->auth_attempt_last_success = date($GLOBALS['Language']->getText('system', 'datefmt'), (int) $user_info['last_auth_success']);

        $this->auth_attempt_last_failure_label = $GLOBALS['Language']->getText('account_options', 'auth_attempt_last_failure');
        $this->purified_auth_attempt_last_failure_ago = $this->getDate((int) $user_info['last_auth_failure'], $user);
        $this->auth_attempt_last_failure       = date($GLOBALS['Language']->getText('system', 'datefmt'), (int) $user_info['last_auth_failure']);

        $this->auth_attempt_nb_failure_label = $GLOBALS['Language']->getText('account_options', 'auth_attempt_nb_failure');
        $this->auth_attempt_nb_failure       = $user_info['nb_auth_failure'];

        $this->auth_attempt_prev_success_label = $GLOBALS['Language']->getText('account_options', 'auth_attempt_prev_success');
        $this->purified_auth_attempt_prev_success_ago = $this->getDate((int) $user_info['last_auth_success'], $user);
        $this->auth_attempt_prev_success       = date($GLOBALS['Language']->getText('system', 'datefmt'), (int) $user_info['last_auth_success']);

        $this->member_since_label = $GLOBALS['Language']->getText('include_user_home', 'member_since');
        $this->purified_member_since_ago = $this->getDate((int) $user->getAddDate(), $user);
        $this->member_since       = date($GLOBALS['Language']->getText('system', 'datefmt'), (int) $user->getAddDate());
        $this->invited_by = $invited_by;
    }

    private function getDate(int $date, PFUser $user): string
    {
        if ($date !== 0) {
            return DateHelper::relativeDateInlineContext($date, $user);
        }

        return "-";
    }
}
