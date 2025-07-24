<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlanUserStoriesInMirroredProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\ProgramIncrementChanged;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredProgramIncrementIsNotVisibleException;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifier;

final class PlanUserStoriesInMirroredProgramIncrementsStub implements PlanUserStoriesInMirroredProgramIncrements
{
    private int $call_count = 0;

    private function __construct(private bool $has_error)
    {
    }

    public static function withCount(): self
    {
        return new self(false);
    }

    public static function withError(): self
    {
        return new self(true);
    }

    public function getCallCount(): int
    {
        return $this->call_count;
    }

    #[\Override]
    public function plan(ProgramIncrementChanged $program_increment_changed): void
    {
        if ($this->has_error) {
            throw new MirroredProgramIncrementIsNotVisibleException(
                $program_increment_changed->program_increment,
                $program_increment_changed->user
            );
        }
        $this->call_count++;
    }

    #[\Override]
    public function planForATeam(ProgramIncrementChanged $program_increment_changed, TeamIdentifier $team_identifier): void
    {
        $this->plan($program_increment_changed);
    }
}
