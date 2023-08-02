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
use Tuleap\User\BeforeStandardLogin;
use Tuleap\User\PasswordVerifier;
use Tuleap\User\RetrievePasswordlessOnlyState;
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
        private readonly RetrievePasswordlessOnlyState $passwordless_only_state,
        PasswordVerifier $password_verifier,
        User_PasswordExpirationChecker $password_expiration_checker,
        PasswordHandler $password_handler,
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
        $this->user_manager->setCurrentUser(\Tuleap\User\CurrentUserWithLoggedInInformation::fromLoggedInUser($user));
    }

    /**
     * Authenticate user but doesn't verify if they are valid
     *
     * @param null|callable(string,\Tuleap\Cryptography\ConcealedString):\Tuleap\User\BeforeLogin $before_login_event_builder
     * @param null|callable(\PFUser):\Tuleap\User\AfterLocalLogin $after_local_login_event_builder
     *
     * @throws User_InvalidPasswordWithUserException
     * @throws User_InvalidPasswordException
     */
    public function authenticate(
        string $name,
        ConcealedString $password,
        ?callable $before_login_event_builder = null,
        ?callable $after_local_login_event_builder = null,
    ): PFUser {
        $user = $this->user_manager->getUserByUserName($name);
        if ($user !== null && $this->passwordless_only_state->isPasswordlessOnly($user)) {
            throw new User_InvalidPasswordWithUserException($user, _('Password authentication is disabled, please use your passkey instead'));
        }

        if ($before_login_event_builder === null) {
            $before_login_event_builder = fn (string $name, ConcealedString $password): BeforeStandardLogin => new BeforeStandardLogin($name, $password);
        }
        $beforeLogin = $this->event_dispatcher->dispatch($before_login_event_builder($name, $password));
        $user        = $beforeLogin->getUser();

        if ($user === null) {
            $user = $this->user_manager->getUserByUserName($name);
            if (! $user) {
                throw new User_InvalidPasswordException();
            }

            $this->authenticateFromDatabase($user, $password, $after_local_login_event_builder);
        }

        $auth_succeeded = $this->event_dispatcher->dispatch(new UserAuthenticationSucceeded($user));
        if (! $auth_succeeded->isLoginAllowed()) {
            throw new User_InvalidPasswordWithUserException($user, $auth_succeeded->getFeedbackMessage());
        }

        return $user;
    }

    /**
     * @param null|callable(\PFUser):\Tuleap\User\AfterLocalLogin $after_local_login_event_builder
     *
     * @throws User_InvalidPasswordWithUserException
     */
    private function authenticateFromDatabase(PFUser $user, ConcealedString $password, ?callable $after_local_login_event_builder = null)
    {
        if (! $this->password_verifier->verifyPassword($user, $password)) {
            throw new User_InvalidPasswordWithUserException($user);
        }

        $user->setPassword($password);
        $this->checkPasswordStorageConformity($user);

        if ($after_local_login_event_builder === null) {
            $after_local_login_event_builder = fn(\PFUser $user): \Tuleap\User\AfterLocalStandardLogin => new \Tuleap\User\AfterLocalStandardLogin($user);
        }

        $afterLogin = $this->event_dispatcher->dispatch($after_local_login_event_builder($user));
        if (! $afterLogin->isIsLoginAllowed()) {
            throw new User_InvalidPasswordWithUserException($user, $afterLogin->getFeedbackMessage());
        }
    }

    private function checkPasswordStorageConformity(PFUser $user): void
    {
        $hashed_password = $user->getUserPw();

        if (
            $this->isPasswordUpdatingNeeded($hashed_password)
        ) {
            $this->user_manager->updateDb($user);
        }
    }

    private function isPasswordUpdatingNeeded($hashed_password)
    {
        return $this->password_handler->isPasswordNeedRehash($hashed_password);
    }
}
