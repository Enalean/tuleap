<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\User\AfterLocalLogin;
use Tuleap\User\BeforeLogin;
use Tuleap\User\PasswordVerifier;
use Tuleap\User\UserAuthenticationSucceeded;

class User_LoginManager // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    /** @var EventDispatcherInterface */
    private $event_dispatcher;

    /** @var UserManager */
    private $user_manager;
    /**
     * @var PasswordVerifier
     */
    private $password_verifier;
    /** @var User_PasswordExpirationChecker */
    private $password_expiration_checker;
    /** @var PasswordHandler */
    private $password_handler;

    public function __construct(
        EventDispatcherInterface $event_dispatcher,
        UserManager $user_manager,
        PasswordVerifier $password_verifier,
        User_PasswordExpirationChecker $password_expiration_checker,
        PasswordHandler $password_handler
    ) {
        $this->event_dispatcher            = $event_dispatcher;
        $this->user_manager                = $user_manager;
        $this->password_verifier           = $password_verifier;
        $this->password_expiration_checker = $password_expiration_checker;
        $this->password_handler            = $password_handler;
    }

    /**
     * Set user as a current if they are valid
     *
     * @throws User_StatusDeletedException
     * @throws User_StatusSuspendedException
     * @throws User_StatusInvalidException
     * @throws User_StatusPendingException
     * @throws User_PasswordExpiredException
     */
    public function validateAndSetCurrentUser(PFUser $user)
    {
        $status_manager = new User_UserStatusManager();
        $status_manager->checkStatus($user);
        $this->password_expiration_checker->checkPasswordLifetime($user);
        $this->user_manager->setCurrentUser($user);
    }

    /**
     * Authenticate user but doesn't verify if they are valid
     *
     * @throws User_InvalidPasswordWithUserException
     * @throws User_InvalidPasswordException
     */
    public function authenticate(string $name, ConcealedString $password): PFUser
    {
        $beforeLogin = $this->event_dispatcher->dispatch(new BeforeLogin($name, $password));
        assert($beforeLogin instanceof BeforeLogin);
        $user = $beforeLogin->getUser();

        if ($user === null) {
            $user = $this->user_manager->getUserByUserName($name);
            if (! $user) {
                throw new User_InvalidPasswordException();
            }

            $this->authenticateFromDatabase($user, $password);
        }

        $auth_succeeded = $this->event_dispatcher->dispatch(new UserAuthenticationSucceeded($user));
        assert($auth_succeeded instanceof UserAuthenticationSucceeded);
        if (! $auth_succeeded->isLoginAllowed()) {
            throw new User_InvalidPasswordWithUserException($user, $auth_succeeded->getFeedbackMessage());
        }

        return $user;
    }

    /**
     * @throws User_InvalidPasswordWithUserException
     */
    private function authenticateFromDatabase(PFUser $user, ConcealedString $password)
    {
        if (! $this->password_verifier->verifyPassword($user, $password)) {
            throw new User_InvalidPasswordWithUserException($user);
        }

        $user->setPassword($password);
        $this->checkPasswordStorageConformity($user);

        $afterLogin = $this->event_dispatcher->dispatch(new AfterLocalLogin($user));
        assert($afterLogin instanceof AfterLocalLogin);
        if (! $afterLogin->isIsLoginAllowed()) {
            throw new User_InvalidPasswordWithUserException($user, $afterLogin->getFeedbackMessage());
        }
    }

    private function checkPasswordStorageConformity(PFUser $user)
    {
        $hashed_password        = $user->getUserPw();
        $legacy_hashed_password = $user->getLegacyUserPw();

        if (
            $this->isPasswordUpdatingNeeded($hashed_password) ||
            $this->isLegacyPasswordRemovalNeeded($legacy_hashed_password)
        ) {
            $this->user_manager->updateDb($user);
        }
    }

    private function isPasswordUpdatingNeeded($hashed_password)
    {
        return $this->password_handler->isPasswordNeedRehash($hashed_password);
    }

    private function isLegacyPasswordRemovalNeeded($legacy_hashed_password)
    {
        return ! empty($legacy_hashed_password);
    }
}
