<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team;

use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Stub\ProgramStoreStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class TeamProjectsCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testGetTeamProjectsReturnsProjects(): void
    {
        $collection = TeamProjectsCollection::fromProgramIdentifier(
            ProgramStoreStub::buildTeams(103, 125),
            new BuildProjectStub(),
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
        );
        self::assertSame(103, $collection->getTeamProjects()[0]->getId());
        self::assertSame(125, $collection->getTeamProjects()[1]->getId());
    }

    public function testIsEmptyReturnsTrue(): void
    {
        $collection = TeamProjectsCollection::fromProgramIdentifier(
            ProgramStoreStub::buildTeams(),
            new BuildProjectStub(),
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
        );
        self::assertTrue($collection->isEmpty());
    }

    public function testIsEmptyReturnsFalse()
    {
        $collection = TeamProjectsCollection::fromProgramIdentifier(
            ProgramStoreStub::buildTeams(101, 102),
            new BuildProjectStub(),
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
        );
        self::assertFalse($collection->isEmpty());
    }
}
