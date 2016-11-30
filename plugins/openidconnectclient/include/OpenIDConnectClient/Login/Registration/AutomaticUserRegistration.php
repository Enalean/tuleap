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

namespace Tuleap\OpenIDConnectClient\Login\Registration;

use ForgeConfig;
use PFUser;
use UserManager;

class AutomaticUserRegistration
{
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var UsernameGenerator
     */
    private $username_generator;

    public function __construct(UserManager $user_manager, UsernameGenerator $username_generator)
    {
        $this->user_manager       = $user_manager;
        $this->username_generator = $username_generator;
    }

    /**
     * @return PFUser
     * @throws NotEnoughDataToRegisterUserException
     */
    public function register(array $user_information)
    {
        if (! isset($user_information['email'])) {
            throw new NotEnoughDataToRegisterUserException();
        }

        $username = $this->username_generator->getUsername($user_information);
        $fullname = $this->getFullname($user_information);
        $email    = $user_information['email'];

        $user = new PFUser();
        $user->setUserName($username);
        $user->setRealName($fullname);
        $user->setEmail($email);
        if (isset($user_information['zoneinfo'])) {
            $user->setTimezone($user_information['zoneinfo']);
        }
        $user->setStatus($this->getUserStatus());
        $user->setUnixStatus('S');

        return $this->user_manager->createAccount($user);
    }

    /**
     * @return string
     */
    private function getFullname(array $user_information)
    {
        if (isset($user_information['name'])) {
            return $user_information['name'];
        }

        if (isset($user_information['family_name']) && isset($user_information['given_name'])) {
            return $user_information['given_name'] . ' ' . $user_information['family_name'];
        }

        return $this->username_generator->getUsername($user_information);
    }

    /**
     * @return string
     */
    private function getUserStatus()
    {
        if (ForgeConfig::get('sys_user_approval')) {
            return PFUser::STATUS_PENDING;
        }

        return PFUser::STATUS_ACTIVE;
    }
}
