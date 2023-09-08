<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
use Tuleap\Config\ConfigKey;
use User_UserStatusManager;
use UserManager;

class AutomaticUserRegistration
{
    #[ConfigKey("OpenID Connect `userinfo` attribute to be used as `ldap_id` (for instance `preferred_username` for `sAMAccountName`)")]
    public const CONFIG_LDAP_ATTRIBUTE = 'openidconnectclient_ldap_attribute';

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
     * @throws NotEnoughDataToRegisterUserException
     */
    public function canCreateAccount(array $user_information): bool
    {
        if (ForgeConfig::get(self::CONFIG_LDAP_ATTRIBUTE) !== false) {
            if (! isset($user_information[ForgeConfig::get(self::CONFIG_LDAP_ATTRIBUTE)])) {
                throw new NotEnoughDataToRegisterUserException(
                    sprintf(
                        '%s config is defined to %s however `userinfo` OIDC route only has: %s',
                        self::CONFIG_LDAP_ATTRIBUTE,
                        ForgeConfig::get(self::CONFIG_LDAP_ATTRIBUTE),
                        implode(', ', array_keys($user_information)),
                    )
                );
            }
            return count($this->user_manager->getAllUsersByLdapID($user_information[ForgeConfig::get(self::CONFIG_LDAP_ATTRIBUTE)])) === 0;
        }

        if (! isset($user_information['email'])) {
            throw new NotEnoughDataToRegisterUserException('No `email` in `userinfo`');
        }

        return count($this->user_manager->getAllUsersByEmail($user_information['email'])) === 0;
    }

    /**
     * @throws NotEnoughDataToRegisterUserException
     */
    public function register(array $user_information): ?PFUser
    {
        if (! isset($user_information['email'])) {
            throw new NotEnoughDataToRegisterUserException('No `email` in `userinfo`');
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
        if (ForgeConfig::get(self::CONFIG_LDAP_ATTRIBUTE) !== false) {
            if (! isset($user_information[ForgeConfig::get(self::CONFIG_LDAP_ATTRIBUTE)])) {
                throw new NotEnoughDataToRegisterUserException(
                    sprintf(
                        '%s config is defined to %s however `userinfo` OIDC route only has: %s',
                        self::CONFIG_LDAP_ATTRIBUTE,
                        ForgeConfig::get(self::CONFIG_LDAP_ATTRIBUTE),
                        implode(', ', array_keys($user_information)),
                    )
                );
            }
            $user->setLdapId($user_information[ForgeConfig::get(self::CONFIG_LDAP_ATTRIBUTE)]);
        }

        return $this->user_manager->createAccount($user);
    }

    private function getFullname(array $user_information): string
    {
        if (isset($user_information['name'])) {
            return $user_information['name'];
        }

        if (isset($user_information['family_name']) && isset($user_information['given_name'])) {
            return $user_information['given_name'] . ' ' . $user_information['family_name'];
        }

        return $this->username_generator->getUsername($user_information);
    }

    private function getUserStatus(): string
    {
        if (ForgeConfig::getInt(User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL) === 1) {
            return PFUser::STATUS_PENDING;
        }

        return PFUser::STATUS_ACTIVE;
    }
}
