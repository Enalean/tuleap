<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\AI\Requestor;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\User\CurrentUserWithLoggedInInformation;

/**
 * @psalm-immutable
 */
final readonly class EndUserAIRequestor implements AIRequestorEntity
{
    private function __construct(
        private string $identifier,
    ) {
    }

    /**
     * @return Ok<self>|Err<Fault>
     */
    public static function fromCurrentUser(CurrentUserWithLoggedInInformation $current_user_with_logged_in_information): Ok|Err
    {
        if (! $current_user_with_logged_in_information->is_logged_in) {
            return Result::err(Fault::fromMessage('AI requests can only be made on behalf of authenticated users'));
        }

        return Result::ok(new self((string) $current_user_with_logged_in_information->user->getId()));
    }

    #[\Override]
    public function getType(): string
    {
        return 'end_user';
    }

    #[\Override]
    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}
