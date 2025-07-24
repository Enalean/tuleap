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

use Tuleap\Authentication\SplitToken\SplitToken;

class InvitationByTokenRetrieverStub implements InvitationByTokenRetriever
{
    private function __construct(private ?Invitation $invitation, private bool $invitation_found)
    {
    }

    public static function withMatchingInvitation(Invitation $invitation): self
    {
        return new self($invitation, true);
    }

    public static function withoutMatchingInvitation(): self
    {
        return new self(null, false);
    }

    public static function withoutValidInvitation(): self
    {
        return new self(null, true);
    }

    #[\Override]
    public function searchBySplitToken(SplitToken $split_token): Invitation
    {
        if ($this->invitation) {
            return $this->invitation;
        }

        if ($this->invitation_found) {
            throw new InvalidInvitationTokenException(false);
        }

        throw new InvitationNotFoundException();
    }
}
