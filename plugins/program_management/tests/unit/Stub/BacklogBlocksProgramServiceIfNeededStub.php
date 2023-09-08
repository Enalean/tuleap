<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\Option\Option;
use Tuleap\ProgramManagement\Domain\Workspace\BacklogBlocksProgramServiceIfNeeded;
use Tuleap\ProgramManagement\Domain\Workspace\ProjectIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use function Psl\Type\string;

final class BacklogBlocksProgramServiceIfNeededStub implements BacklogBlocksProgramServiceIfNeeded
{
    /**
     * @param Option<string> $blocked_message
     */
    private function __construct(private Option $blocked_message)
    {
    }

    public static function withBlocked(): self
    {
        return new self(Option::fromValue('Program service is blocked'));
    }

    public static function withNotBlocked(): self
    {
        return new self(Option::nothing(string()));
    }

    public function shouldProgramServiceBeBlocked(
        UserIdentifier $user_identifier,
        ProjectIdentifier $project_identifier,
    ): Option {
        return $this->blocked_message;
    }
}
