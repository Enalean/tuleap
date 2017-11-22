<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mohamed CHAARI, 2006. STMicroelectronics.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\User\UserImportCollection;

class UserImport
{
    private $project_id;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var UserHelper
     */
    private $user_helper;

    public function __construct($group_id, UserManager $user_manager, UserHelper $user_helper)
    {
        $this->project_id      = $group_id;
        $this->user_manager    = $user_manager;
        $this->user_helper = $user_helper;
    }

    public function parse($user_filename)
    {
        $user_collection = new UserImportCollection($this->user_helper);
        if (! $user_filename) {
            return;
        }

        $file_content = file($user_filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($file_content as $line) {
            $line = trim($line);
            if ($line === "") {
                continue;
            }

            $user = $this->user_manager->findUser($line);
            if (! $user) {
                $users        = $this->user_manager->getAllUsersByEmail($line);
                $users_number = count($users);

                if ($users_number > 1) {
                    $user_collection->addWarningMultipleUsers($line);
                    continue;
                }

                if ($users_number === 0) {
                    $user_collection->addWarningsInvalidUsers($line);
                    continue;
                }

                $user = $users[0];
            }

            if (! $user || ($user && ! $user->isActive() && ! $user->isRestricted())) {
                $user_collection->addWarningsInvalidUsers($line);
                continue;
            }

            if (! $user->isMember($this->project_id)) {
                $user_collection->addUser($user);
            }
        }

        return $user_collection;
    }

    public function updateDB(array $parsed_users)
    {
        $res                = true;
        $send_notifications = true;
        $check_user_status  = true;

        foreach ($parsed_users as $user) {
            $res &= account_add_user_obj_to_group($this->project_id, $user, $check_user_status, $send_notifications);
        }
        return $res;
    }
}
