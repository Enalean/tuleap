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
use Tuleap\User\Password\PasswordValidatorPresenter;

/**
 * @psalm-immutable
 */
final class SecurityPresenter
{
    public $change_password_url = UpdatePasswordController::URL;
    public $session_update_url  = UpdateSessionPreferencesController::URL;

    /**
     * @var AccountTabPresenterCollection
     */
    public $tabs;
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;
    /**
     * @var bool
     */
    public $remember_me_activated;
    /**
     * @var string
     */
    public $username;
    /**
     * @var bool
     */
    public $old_password_is_required;
    /**
     * @var bool
     */
    public $user_can_change_password;
    /**
     * @var PasswordValidatorPresenter[]
     */
    public $passwords_validators;

    /**
     * @param PasswordValidatorPresenter[]  $password_validator_presenter
     */
    public function __construct(AccountTabPresenterCollection $tabs, CSRFSynchronizerToken $csrf_token, \PFUser $user, PasswordPreUpdateEvent $password_pre_update_event, array $password_validator_presenter)
    {
        $this->tabs = $tabs;
        $this->csrf_token = $csrf_token;
        $this->remember_me_activated = (int) $user->getStickyLogin() === 1;
        $this->username = $user->getUserName();
        $this->old_password_is_required = $password_pre_update_event->isOldPasswordRequiredToUpdatePassword();
        $this->user_can_change_password = $password_pre_update_event->areUsersAllowedToChangePassword();
        $this->passwords_validators = $password_validator_presenter;
    }
}
