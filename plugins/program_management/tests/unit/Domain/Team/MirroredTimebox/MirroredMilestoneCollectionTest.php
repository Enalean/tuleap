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

namespace Tuleap\ProgramManagement\Domain\Team\MirroredTimebox;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrement;
use Tuleap\ProgramManagement\Domain\ProjectReference;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementBuilder;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchMirrorTimeboxesFromProgramStub;
use Tuleap\Test\PHPUnit\TestCase;

final class MirroredMilestoneCollectionTest extends TestCase
{
    /**
     * @var ProgramIncrement[]
     */
    private array $open_program_increments;
    /**
     * @var ProjectReference[]
     */
    private array $aggregated_teams;

    protected function setUp(): void
    {
        $this->open_program_increments = [ProgramIncrementBuilder::buildWithId(1), ProgramIncrementBuilder::buildWithId(2)];
        $this->aggregated_teams        = [ProjectReferenceStub::withId(102), ProjectReferenceStub::withId(103)];
    }

    public function testItBuildsACollectionOfTeamWithMissingMirrors(): void
    {
        $search_missing_mirror = SearchMirrorTimeboxesFromProgramStub::buildWithoutMissingMirror();
        $collection            = MirroredMilestoneCollection::buildCollectionFromProgramIdentifier($search_missing_mirror, $this->open_program_increments, $this->aggregated_teams);
        self::assertCount(2, $collection);
    }

    public function testItBuildsAnEmptyCollectionWhenTeamHasEveryMirror(): void
    {
        $search_missing_mirror = SearchMirrorTimeboxesFromProgramStub::buildWithMissingMirror();
        $collection            = MirroredMilestoneCollection::buildCollectionFromProgramIdentifier($search_missing_mirror, $this->open_program_increments, $this->aggregated_teams);
        self::assertCount(0, $collection);
    }
}
