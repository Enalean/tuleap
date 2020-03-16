<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

require_once __DIR__ . '/../../include/user.php';
require_once __DIR__ . '/../../include/utils_soap.php';

if (defined('NUSOAP')) {
    $server->register(
        'checkUsersExistence',
        array('sessionKey' => 'xsd:string',
        'users' => 'tns:ArrayOfstring'
        ),
        array('return' => 'tns:ArrayOfUserInfo'),
        $uri,
        $uri . '#checkUsersExistence',
        'rpc',
        'encoded',
        'Returns the users that exist with their user name'
    );

    $server->register(
        'getUserInfo',
        array('sessionKey' => 'xsd:string',
          'user_id'    => 'xsd:int'
        ),
        array('return' => 'tns:UserInfo'),
        $uri,
        $uri . '#getUserInfo',
        'rpc',
        'encoded',
        'Returns the user info matching the given id'
    );
} else {

    function getUserInfo($sessionKey, $user_id)
    {
        if (! session_continue($sessionKey)) {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session ', 'getUserInfo');
        }

        $user_manager = UserManager::instance();
        $current_user = $user_manager->getCurrentUser();

        try {
            $user      = $user_manager->getUserById($user_id);
            $user_info = user_to_soap($user_id, $user, $current_user);

            if (! $user_info) {
                return new SoapFault('0', "Invalid user id: $user_id", 'getUserInfo');
            }

            return $user_info;
        } catch (Exception $e) {
            return new SoapFault('0', $e->getMessage(), 'getUserInfo');
        }
    }

    function checkUsersExistence($sessionKey, $users)
    {
        if (session_continue($sessionKey)) {
            try {
                $existingUsers         = array();
                $user_manager          = UserManager::instance();
                $currentUser           = $user_manager->getCurrentUser();
                $email_identifier_type = 'email:';

                foreach ($users as $userIdentifier) {
                    if (strpos($userIdentifier, $email_identifier_type) === 0) {
                        $user_email = substr($userIdentifier, strlen($email_identifier_type));
                        $users      = $user_manager->getAllUsersByEmail($user_email);
                    } else {
                        $users = array($user_manager->getUserByIdentifier($userIdentifier));
                    }

                    foreach ($users as $user) {
                        $user_info = user_to_soap($userIdentifier, $user, $currentUser);
                        if ($user_info) {
                            $existingUsers[] = $user_info;
                        }
                    }
                }

                return $existingUsers;
            } catch (Exception $e) {
                return new SoapFault('0', $e->getMessage(), 'checkUsersExistence');
            }
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session ', 'checkUsersExistence');
        }
    }


    $server->addFunction(
        array(
            'getUserInfo',
            'checkUsersExistence',
        )
    );
}
