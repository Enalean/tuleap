<?php
/*
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

require_once('www/include/account.php');
require_once('common/dao/UserDao.class.php');

class UserImport {

    //the group our users is part of
    var $group_id;

    public function __construct($group_id) {
        $this->group_id = $group_id;    
    }

    /** 
     * Parse a file in simple text  format containing users to be imported into the project
     * 
     * @param string $user_filename (IN):  the complete file name of the file to be parsed
     * @param array  $parsed_users  (OUT): the users parsed in the import file
     *                                     array of the form (column_number => User object)
     * 
     * @return boolean true if at least one entry was successfully parsed
     */
    function parse($user_filename, &$parsed_users) {
        $um = UserManager::instance();

        $fileContent = file($user_filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($fileContent) {
            foreach ($fileContent as $line) {
                $line = trim($line);
                if ($line != "") {
                    $user = $um->findUser($line);
                    if (! $user) {
                        $users = $um->getAllUsersByEmail($line);
                        $users_number = count($users);
                        if ($users_number === 1) {
                            $user = $users[0];
                        } else if ($users_number > 1) {
                            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('project_admin_userimport','multiple_users', $line));
                            continue;
                        }
                    }
                    if ($user && ($user->isActive() || $user->isRestricted())) {
                        if (!$user->isMember($this->group_id)
                            && !isset($parsed_users[$user->getId()])) {
                            $parsed_users[$user->getId()] = $user;
                        }
                    } else {
                        $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('project_admin_userimport','invalid_mail_or_username', $line));
                    }
                }
            }
            return (count($parsed_users) > 0);
        } else {
            return false;
        }
    }

    /**
     * Insert the imported users into the db
     * @param array parsed_users: array of the form (column_number => user id) containing
     *                            all the users parsed from import file
     * @return true if parse ok, false if errors occurred
     */
    function updateDB($parsed_users)
    {
        $res = true;
        $um = UserManager::instance();
        foreach ($parsed_users as $user_id) {
            $user = $um->getUserById($user_id);
            if ($user) {
                $send_notifications = true;
                $check_user_status  = true;
                $res = $res & account_add_user_obj_to_group($this->group_id, $user, $check_user_status, $send_notifications);
            }
        }
        return $res;
    }
}
