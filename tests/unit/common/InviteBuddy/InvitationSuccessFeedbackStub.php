<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\InviteBuddy;

use Tuleap\User\Account\Register\RegisterFormContext;

final class InvitationSuccessFeedbackStub implements InvitationSuccessFeedback
{
    private ?\PFUser $has_been_called_with = null;

    private function __construct()
    {
    }

    public static function buildSelf(): self
    {
        return new self();
    }

    public function accountHasJustBeenCreated(\PFUser $just_created_user, RegisterFormContext $context): void
    {
        $this->has_been_called_with = $just_created_user;
    }

    public function hasBeenCalled(): bool
    {
        return $this->has_been_called_with !== null;
    }

    public function hasBeenCalledWith(\PFUser $user): bool
    {
        return $this->has_been_called_with === $user;
    }
}
