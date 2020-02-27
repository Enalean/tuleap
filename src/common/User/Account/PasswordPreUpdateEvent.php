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
use Tuleap\Event\Dispatchable;

final class PasswordPreUpdateEvent implements Dispatchable
{
    public const NAME = 'passwordPreUpdateEvent';

    private $user_can_change_password = true;
    private $old_password_is_required_to_update_password = true;
    /**
     * @var PFUser
     */
    private $user;

    public function __construct(PFUser $user)
    {
        $this->user = $user;
    }

    public function forbidUserToChangePassword(): void
    {
        $this->user_can_change_password = false;
    }

    public function oldPasswordIsNotRequiredToUpdatePassword(): void
    {
        $this->old_password_is_required_to_update_password = false;
    }

    public function areUsersAllowedToChangePassword(): bool
    {
        return $this->user_can_change_password;
    }

    public function isOldPasswordRequiredToUpdatePassword(): bool
    {
        return $this->old_password_is_required_to_update_password;
    }

    public function getUser(): PFUser
    {
        return $this->user;
    }
}
