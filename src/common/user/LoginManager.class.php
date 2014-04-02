<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class User_LoginManager {

    /** @var EventManager */
    private $event_manager;

    /** @var UserManager */
    private $user_manager;

    /** @var User_PasswordExpirationChecker */
    private $password_expiration_checker;

    public function __construct(EventManager $event_manager, UserManager $user_manager, User_PasswordExpirationChecker $password_expiration_checker) {
        $this->event_manager = $event_manager;
        $this->user_manager  = $user_manager;
        $this->password_expiration_checker = $password_expiration_checker;
    }

    /**
     * Set user as a current if they are valid
     *
     * @param PFUser $user
     * @throws User_StatusDeletedException
     * @throws User_StatusSuspendedException
     * @throws User_StatusInvalidException
     * @throws User_StatusPendingException
     * @throws User_PasswordExpiredException
     */
    public function validateAndSetCurrentUser(PFUser $user) {
        $status_manager = new User_UserStatusManager();
        $status_manager->checkStatus($user);
        $this->password_expiration_checker->checkPasswordLifetime($user);
        $this->user_manager->setCurrentUser($user);
    }

    /**
     * Authenticate user but doesn't verify if they are valid
     *
     * @param String $name
     * @param String $password
     * @return PFUser
     * @throws User_InvalidPasswordWithUserException
     * @throws User_InvalidPasswordException
     * @throws User_PasswordExpiredException
     */
    public function authenticate($name, $password) {
        $auth_success     = false;
        $auth_user_id     = null;
        $auth_user_status = null;

        $this->event_manager->processEvent(
            Event::SESSION_BEFORE_LOGIN,
            array(
                'loginname'        => $name,
                'passwd'           => $password,
                'auth_success'     => &$auth_success,
                'auth_user_id'     => &$auth_user_id,
                'auth_user_status' => &$auth_user_status,
            )
        );

        if ($auth_success) {
            $user = $this->user_manager->getUserById($auth_user_id);
        } else {
            $user = $this->user_manager->getUserByUserName($name);
            if ($user && $user->getUserPw() == md5($password)) {
                $user->setPassword($password);
                $auth_success = true;
                $this->event_manager->processEvent(
                    Event::SESSION_AFTER_LOGIN,
                    array(
                        'user'                => $user,
                        'allow_codendi_login' => &$auth_success
                    )
                );
            }
        }

        if ($auth_success) {
            return $user;
        } else {
            if ($user) {
                throw new User_InvalidPasswordWithUserException($user);
            } else {
                throw new User_InvalidPasswordException();
            }
        }
    }
}
