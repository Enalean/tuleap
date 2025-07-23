<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementArtifactCreationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlanProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\ProgramIncrementChanged;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifierCollection;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementChangeTestBuilder;

final class PlanProgramIncrementsStub implements PlanProgramIncrements
{
    private function __construct(private int $call_count, private bool $should_throw)
    {
    }

    public static function build(): self
    {
        return new self(0, false);
    }

    public static function buildWithError(): self
    {
        return new self(0, true);
    }

    #[\Override]
    public function createProgramIncrementAndReturnPlanChange(ProgramIncrementCreation $creation, TeamIdentifierCollection $teams): ProgramIncrementChanged
    {
        if ($this->should_throw) {
            throw new ProgramIncrementArtifactCreationException($creation->getProgramIncrement()->getId());
        }

        $this->call_count++;

        return ProgramIncrementChangeTestBuilder::buildWithId(
            $creation->getProgramIncrement()->getId(),
            $creation->getProgramIncrementTracker()->getId(),
            $creation->getUser()->getId(),
            $creation->getChangeset()->getId()
        );
    }

    public function getCallCount(): int
    {
        return $this->call_count;
    }
}
