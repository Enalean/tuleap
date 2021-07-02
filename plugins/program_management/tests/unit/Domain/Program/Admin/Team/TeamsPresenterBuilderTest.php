<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Admin\Team;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Stub\SearchTeamsOfProgramStub;

final class TeamsPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testBuildPresenterWithTeamThatUserIsAdminOf(): void
    {
        $user = $this->createStub(\PFUser::class);
        $user->method('isAdmin')->willReturnOnConsecutiveCalls(true, false);

        $collection = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(150, 666),
            new BuildProjectStub(),
            ProgramIdentifier::fromId(
                BuildProgramStub::stubValidProgram(),
                101,
                $user
            )
        );

        $teams_presenter = TeamsPresenterBuilder::buildTeamsPresenter($collection, $user);
        self::assertCount(1, $teams_presenter);
        self::assertSame(150, $teams_presenter[0]->id);
    }
}
