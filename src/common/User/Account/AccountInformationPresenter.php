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

final class AccountInformationPresenter
{
    public $update_preferences_url = DisplayAccountInformationController::URL;
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
    public $member_since;
    /**
     * @var string
     * @psalm-readonly
     */
    public $user_name;

    public function __construct(AccountTabPresenterCollection $tabs, CSRFSynchronizerToken $csrf_token, PFUser $user)
    {
        $this->tabs = $tabs;
        $this->csrf_token = $csrf_token;
        $this->user_id = (int) $user->getId();
        $this->user_name = $user->getUserName();
        $this->member_since = \DateHelper::formatForLanguage($user->getLanguage(), (int) $user->getAddDate());
    }
}
