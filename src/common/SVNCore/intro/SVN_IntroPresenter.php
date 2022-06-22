<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\User\CurrentUserWithLoggedInInformation;

class SVN_IntroPresenter
{
    /**
     * @var bool
     */
    public $uses_ldap_info;

    /**
     * @var string
     */
    private $svn_url;

    /**
     * @param mixed
     */
    public $ldap_row;

    public function __construct(private CurrentUserWithLoggedInInformation $current_user, $uses_ldap_info, $ldap_row, $svn_url)
    {
        $this->ldap_row       = $ldap_row;
        $this->svn_url        = $svn_url;
        $this->uses_ldap_info = $uses_ldap_info;
    }

    public function svn_intro_title()
    {
        return $GLOBALS['Language']->getText('svn_intro', 'title');
    }

    public function svn_user_username()
    {
        if ($this->current_user->is_logged_in && ! $this->uses_ldap_info) {
            return $this->current_user->user->getName();
        } elseif ($this->current_user->is_logged_in && $this->uses_ldap_info && $this->ldap_row) {
            return strtolower($this->ldap_row->getLogin());
        } else {
            return $GLOBALS['Language']->getText('svn_intro', 'default_username');
        }
    }

    public function warning_ldap()
    {
        return $GLOBALS['Language']->getText('svn_intro', 'warning_ldap');
    }

    public function user_is_loggedin()
    {
        return $this->current_user->is_logged_in;
    }

    public function user_not_connected_username_helper()
    {
        return $GLOBALS['Language']->getText('svn_intro', 'username_helper');
    }

    public function username_helper_lowercase()
    {
        return $GLOBALS['Language']->getText('svn_intro', 'username_helper_lowercase');
    }

    public function svn_command()
    {
        return "svn checkout --username " . strtolower($this->svn_user_username()) . " " . $this->svn_url;
    }

    public function username_is_in_lowercase()
    {
        return strtolower($this->current_user->user->getName()) === $this->current_user->user->getName();
    }

    public function command_intro()
    {
        return $GLOBALS['Language']->getText('svn_intro', 'command_intro');
    }

    public function more_info()
    {
        return $GLOBALS['Language']->getText('svn_intro', 'more_info');
    }

    public function password()
    {
        if ($this->uses_ldap_info) {
            $password_content = $GLOBALS['Language']->getText('svn_intro', 'ldap_password');
        } else {
            $password_content = $GLOBALS['Language']->getText('svn_intro', 'password');
        }

        $password_content .= ' ' . $GLOBALS['Language']->getText('svn_intro', 'token');

        return $password_content;
    }
}
