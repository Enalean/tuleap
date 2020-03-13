<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

use EventManager;
use PFUser;

class UserDetailsFormatter
{
    /**
     * @var UserStatusBuilder
     */
    private $user_status_builder;

    public function __construct(UserStatusBuilder $user_status_builder)
    {
        $this->user_status_builder = $user_status_builder;
    }

    public function getStatus(PFUser $user)
    {
        return $this->user_status_builder->getStatus($user);
    }

    public function getUnixStatus(PFUser $user)
    {
        $labels = array(
            'N' => $GLOBALS['Language']->getText('admin_usergroup', 'no_account'),
            'A' => $GLOBALS['Language']->getText('admin_usergroup', 'active'),
            'S' => $GLOBALS['Language']->getText('admin_usergroup', 'suspended'),
            'D' => $GLOBALS['Language']->getText('admin_usergroup', 'deleted')
        );

        $unix_status = array();
        foreach ($labels as $key => $status) {
            $unix_status[] = array(
                'key'        => $key,
                'status'     => $status,
                'is_current' => $user->getUnixStatus() === $key
            );
        }

        return $unix_status;
    }

    public function getShells(PFUser $user)
    {
        $shells        = array();
        $current_shell = $user->getShell();
        if (! $current_shell) {
            $current_shell = '/sbin/nologin';
        }
        foreach (PFUser::getAllUnixShells() as $shell) {
            $shells[] = array(
                'shell'      => $shell,
                'is_current' => $current_shell === $shell
            );
        }

        return $shells;
    }

    public function getMore(PFUser $user)
    {
        $links = array(
            array(
                'href'  => '/users/' . urlencode($user->getUserName()),
                'label' => $GLOBALS['Language']->getText('admin_usergroup', 'user_public_profile')
            )
        );

        EventManager::instance()->processEvent(
            'usergroup_data',
            array(
                'user'  => $user,
                'links' => &$links
            )
        );

        return $links;
    }
}
