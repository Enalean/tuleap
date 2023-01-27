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

namespace Tuleap\User\Account\Register;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\InviteBuddy\Invitation;

/**
 * @psalm-immutable
 */
final class InvitationToEmail
{
    private function __construct(
        public int $id,
        public string $to_email,
        public ?int $created_user_id,
        public ?int $to_project_id,
        public ConcealedString $token,
    ) {
    }

    /**
     * @throws InvitationShouldBeToEmailException
     */
    public static function fromInvitation(Invitation $invitation, ConcealedString $token): self
    {
        if ($invitation->to_user_id) {
            throw new InvitationShouldBeToEmailException();
        }

        return new self(
            $invitation->id,
            $invitation->to_email,
            $invitation->created_user_id,
            $invitation->to_project_id,
            $token,
        );
    }
}
