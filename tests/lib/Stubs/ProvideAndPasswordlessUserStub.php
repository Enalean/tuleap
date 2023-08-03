<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Test\Stubs;

use PFUser;
use Tuleap\User\ProvideCurrentUser;
use Tuleap\User\RetrievePasswordlessOnlyState;
use Tuleap\User\SwitchPasswordlessOnlyState;

final class ProvideAndPasswordlessUserStub implements ProvideCurrentUser, SwitchPasswordlessOnlyState, RetrievePasswordlessOnlyState
{
    private function __construct(
        private PFUser $current_user,
        private bool $passwordless_only,
    ) {
    }

    public static function build(PFUser $current_user, bool $passwordless_only = false): self
    {
        return new self($current_user, $passwordless_only);
    }

    public function getCurrentUser(): \PFUser
    {
        return $this->current_user;
    }

    public function switchPasswordlessOnly(PFUser $user, bool $passwordless_only): void
    {
        if ($user->getId() === $this->current_user->getId()) {
            $this->passwordless_only = $passwordless_only;
        }
    }

    public function isPasswordlessOnly(PFUser $user): bool
    {
        if ($user->getId() === $this->current_user->getId()) {
            return $this->passwordless_only;
        }

        return false;
    }
}
