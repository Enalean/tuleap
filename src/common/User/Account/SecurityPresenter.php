<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\User\Account;

use CSRFSynchronizerToken;
use Tuleap\Date\DateHelper;
use Tuleap\User\Password\PasswordValidatorPresenter;

final class SecurityPresenter
{
    public $change_password_url = UpdatePasswordController::URL;
    public $session_update_url  = UpdateSessionPreferencesController::URL;

    /**
     * @var AccountTabPresenterCollection
     * @psalm-readonly
     */
    public $tabs;
    /**
     * @var CSRFSynchronizerToken
     * @psalm-readonly
     */
    public $csrf_token;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $remember_me_activated;
    /**
     * @var string
     * @psalm-readonly
     */
    public $username;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $user_can_change_password;
    /**
     * @var PasswordValidatorPresenter[]
     * @psalm-readonly
     */
    public $passwords_validators;
    /**
     * @var string
     * @psalm-readonly
     */
    public $json_password_strategy_keys;
    /**
     * @var string
     * @psalm-readonly
     */
    public $last_successful_login = '-';
    /**
     * @var string
     * @psalm-readonly
     */
    public $last_login_failure = '-';
    /**
     * @var string
     * @psalm-readonly
     */
    public $previous_successful_login = '-';

    /**
     * @param PasswordValidatorPresenter[]  $password_validator_presenter
     * @psalm-param array{last_auth_success: string, last_auth_failure: string, nb_auth_failure: string, prev_auth_success: string} $user_access
     */
    public function __construct(AccountTabPresenterCollection $tabs, CSRFSynchronizerToken $csrf_token, \PFUser $user, PasswordPreUpdateEvent $password_pre_update_event, array $password_validator_presenter, array $user_access)
    {
        $this->tabs                     = $tabs;
        $this->csrf_token               = $csrf_token;
        $this->remember_me_activated    = (int) $user->getStickyLogin() === 1;
        $this->username                 = $user->getUserName();
        $this->user_can_change_password = $password_pre_update_event->areUsersAllowedToChangePassword();
        $this->passwords_validators     = $password_validator_presenter;
        $password_strategy_keys         = [];
        foreach ($this->passwords_validators as $passwords_validator) {
            $password_strategy_keys[] = (int) $passwords_validator->regexp;
        }
        $this->json_password_strategy_keys = json_encode($password_strategy_keys, JSON_THROW_ON_ERROR);
        if ($user_access['last_auth_success']) {
            $this->last_successful_login = DateHelper::formatForLanguage(
                $user->getLanguage(),
                (int) $user_access['last_auth_success']
            );
        }
        if ($user_access['last_auth_failure']) {
            $this->last_login_failure = DateHelper::formatForLanguage(
                $user->getLanguage(),
                (int) $user_access['last_auth_failure']
            );
        }
        if ($user_access['prev_auth_success']) {
            $this->previous_successful_login = DateHelper::formatForLanguage(
                $user->getLanguage(),
                (int) $user_access['prev_auth_success']
            );
        }
    }
}
