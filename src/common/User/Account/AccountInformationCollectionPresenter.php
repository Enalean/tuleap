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
use PFUser;

/**
 * @psalm-immutable
 */
final class AccountInformationCollectionPresenter
{
    public $update_preferences_url = UpdateAccountInformationController::URL;
    /**
     * @var AccountTabPresenterCollection
     */
    public $tabs;
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;
    /**
     * @var int
     */
    public $user_id;
    /**
     * @var string
     */
    public $user_name;
    /**
     * @var string
     */
    public $real_name;
    /**
     * @var bool
     */
    public $can_change_real_name;
    /**
     * @var AccountInformationPresenter[]
     */
    public $extra_information;

    public function __construct(AccountTabPresenterCollection $tabs, CSRFSynchronizerToken $csrf_token, PFUser $user, AccountInformationCollection $account_information_collection)
    {
        $this->tabs = $tabs;
        $this->csrf_token = $csrf_token;
        $this->user_id = (int) $user->getId();
        $this->user_name = $user->getUserName();
        $this->real_name = $user->getRealName();
        $this->can_change_real_name = $account_information_collection->isUserAllowedToCanChangeRealName();
        $this->extra_information = $account_information_collection->getExtraInformation();
    }
}
