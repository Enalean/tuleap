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

use Account_TimezoneSelectorPresenter;
use CSRFSynchronizerToken;
use PFUser;

final class AccountInformationCollectionPresenter
{
    /**
     * @var string
     * @psalm-readonly
     */
    public $update_preferences_url = UpdateAccountInformationController::URL;
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
     * @var int
     * @psalm-readonly
     */
    public $user_id;
    /**
     * @var string
     * @psalm-readonly
     */
    public $user_name;
    /**
     * @var string
     * @psalm-readonly
     */
    public $real_name;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $can_change_real_name;
    /**
     * @var AccountInformationPresenter[]
     * @psalm-readonly
     */
    public $extra_information;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $has_avatar;
    /**
     * @var string
     * @psalm-readonly
     */
    public $avatar_url;
    /**
     * @var string
     * @psalm-readonly
     */
    public $email;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $can_change_email;
    /**
     * @var Account_TimezoneSelectorPresenter
     * @psalm-readonly
     */
    public $timezone;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $change_email_pending;

    public function __construct(AccountTabPresenterCollection $tabs, CSRFSynchronizerToken $csrf_token, PFUser $user, AccountInformationCollection $account_information_collection)
    {
        $this->tabs = $tabs;
        $this->csrf_token = $csrf_token;
        $this->user_id = (int) $user->getId();
        $this->user_name = $user->getUserName();
        $this->real_name = $user->getRealName();
        $this->email     = $user->getEmail();
        $this->has_avatar = (bool) $user->hasAvatar();
        $this->avatar_url = $user->getAvatarUrl();
        $this->can_change_real_name = $account_information_collection->isUserAllowedToCanChangeRealName();
        $this->can_change_email     = $account_information_collection->isUserAllowedToChangeEmail();
        $this->extra_information = $account_information_collection->getExtraInformation();
        $this->timezone = new Account_TimezoneSelectorPresenter($user->getTimezone());
        $this->change_email_pending = $user->getConfirmHash() != '';
    }
}
