<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\TeamSynchronization\VerifyTeamSynchronizationHasError;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\ProjectReference;

final class VerifyTeamSynchronizationHasErrorStub implements VerifyTeamSynchronizationHasError
{
    private function __construct(private bool $has_error)
    {
    }

    public static function buildWithoutError(): self
    {
        return new self(false);
    }

    public static function buildWithError(): self
    {
        return new self(true);
    }

    #[\Override]
    public function hasASynchronizationError(ProgramIdentifier $program_identifier, ProjectReference $team_identifier): bool
    {
        return $this->has_error;
    }
}
