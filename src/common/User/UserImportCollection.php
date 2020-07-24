<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\User;

use PFUser;
use UserHelper;

class UserImportCollection
{
    /**
     * @var array
     */
    private $warnings_multiple_users = [];

    /**
     * @var array
     */
    private $warnings_invalid_users = [];

    /**
     * @var PFUser[]
     */
    private $users = [];
    /**
     * @var UserHelper
     */
    private $user_helper;

    public function __construct(UserHelper $user_helper)
    {
        $this->user_helper = $user_helper;
    }

    /**
     * @return array
     */
    public function getWarningsMultipleUsers()
    {
        return $this->warnings_multiple_users;
    }

    public function addWarningMultipleUsers($warning)
    {
        $this->warnings_multiple_users[] = [
            "warning" => sprintf(_('%s has multiple corresponding users.'), $warning)
        ];
    }

    /**
     * @return PFUser[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    public function addUser(PFUser $user)
    {
        if (! $this->isUserPresent($user)) {
            $this->users[$user->getId()] = $user;
        }
    }

    private function isUserPresent(PFUser $user)
    {
        return isset($this->users[$user->getId()]);
    }

    /**
     * @return array
     */
    public function getWarningsInvalidUsers()
    {
        return $this->warnings_invalid_users;
    }

    public function addWarningsInvalidUsers($warning_invalid_user)
    {
        $this->warnings_invalid_users[] = [
            "warning" => sprintf(_("User '%s' does not exist"), $warning_invalid_user)
        ];
    }

    public function getFormattedUsers()
    {
        $users = [];
        foreach ($this->getUsers() as $user) {
            $formatted_user = [];
            $formatted_user['has_avatar']       = $user->hasAvatar();
            $formatted_user['avatar_url']       = $user->getAvatarUrl();
            $formatted_user['user_name']        = $user->getUserName();
            $formatted_user['email']            = $user->getEmail();
            $formatted_user['profile_page_url'] = "/users/" . urlencode($user->getUserName()) .  "/";
            $formatted_user['username_display'] = $this->user_helper->getDisplayName(
                $user->getUserName(),
                $user->getRealName()
            );

            $users[] = $formatted_user;
        }

        return $users;
    }
}
