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

use PFUser;
use Tuleap\Date\DateHelper;
use Tuleap\Event\Dispatchable;

class AccountInformationCollection implements Dispatchable
{
    public const NAME = 'accountInformationCollection';
    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var bool
     */
    private $is_user_allowed_to_change_real_name = true;
    /**
     * @var bool
     */
    private $is_user_allowed_to_change_email = true;
    /**
     * @var AccountInformationPresenter[]
     */
    private $extra_information;

    public function __construct(PFUser $user)
    {
        $this->user              = $user;
        $this->extra_information = [
            new AccountInformationPresenter(
                _('Member since'),
                DateHelper::formatForLanguage($user->getLanguage(), (int) $user->getAddDate(), true),
            ),
        ];
    }

    public function getUser(): PFUser
    {
        return $this->user;
    }

    public function disableChangeRealName(): void
    {
        $this->is_user_allowed_to_change_real_name = false;
    }

    public function isUserAllowedToCanChangeRealName(): bool
    {
        return $this->is_user_allowed_to_change_real_name;
    }

    public function disableChangeEmail(): void
    {
        $this->is_user_allowed_to_change_email = false;
    }

    public function isUserAllowedToChangeEmail(): bool
    {
        return $this->is_user_allowed_to_change_email;
    }

    public function addInformation(AccountInformationPresenter $extra_information): void
    {
        $this->extra_information[] = $extra_information;
    }

    /**
     * @return AccountInformationPresenter[]
     */
    public function getExtraInformation(): array
    {
        return $this->extra_information;
    }
}
