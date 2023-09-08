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
namespace User\XML\Import;

use PFUser;
use Tuleap\Cryptography\ConcealedString;
use UserManager;
use Psr\Log\LoggerInterface;
use RandomNumberGenerator;

class WillBeCreatedUser implements ReadyToBeImportedUser
{
    public static $ALLOWED_STATUSES = [
        PFUser::STATUS_ACTIVE,
        PFUser::STATUS_RESTRICTED,
        PFUser::STATUS_SUSPENDED,
    ];

    /** @var string */
    private $username;

    /** @var string */
    private $realname;

    /** @var string */
    private $email;

    /** @var string */
    private $status;

    /** @var string */
    private $ldap_id;

    /** @var bool */
    private $use_lame_password;

    public function __construct(
        $username,
        $realname,
        $email,
        $status,
        $ldap_id,
        $use_lame_password = false,
    ) {
        $this->username          = $username;
        $this->realname          = $realname;
        $this->email             = $email;
        $this->status            = $status;
        $this->ldap_id           = $ldap_id;
        $this->use_lame_password = $use_lame_password;
    }

    public function getUserName()
    {
        return $this->username;
    }

    public function getRealName()
    {
        return $this->realname;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getStatus()
    {
        return $this->status;
    }

    private function getPassword(): ConcealedString
    {
        if ($this->use_lame_password) {
            return new ConcealedString('Correct Horse Battery Staple');
        }
        $random_generator = new RandomNumberGenerator();
        return new ConcealedString($random_generator->getNumber());
    }

    public function process(UserManager $user_manager, LoggerInterface $logger)
    {
        $fake_user = new PFUser();
        $fake_user->setUserName($this->username);
        $fake_user->setRealName($this->realname);
        $fake_user->setPassword($this->getPassword());
        $fake_user->setLdapId($this->ldap_id);
        $fake_user->setRegisterPurpose('Created by xml import');
        $fake_user->setEmail($this->email);
        $fake_user->setStatus($this->status);
        $fake_user->setConfirmHash('');
        $fake_user->setMailSiteUpdates(0);
        $fake_user->setMailVA(0);
        $fake_user->setTimezone('GMT');
        $fake_user->setLanguageID('en_US');
        $fake_user->setExpiryDate(0);

        $created_user = $user_manager->createAccount($fake_user);
        if ($created_user) {
            $logger->info($this->username . ' successfuly created ! It has id #' . $created_user->getId());
        } else {
            throw new UserCannotBeCreatedException('An error occured while creating ' . $this->username);
        }
    }

    public function getRealUser(UserManager $user_manager)
    {
        $user = $user_manager->getUserByUserName($this->username);

        if (! $user) {
            throw new UserNotFoundException('An error occured while retrieving previously created user ' . $this->username);
        }

        return $user;
    }
}
