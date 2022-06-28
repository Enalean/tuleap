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

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\Team;

use Tuleap\ProgramManagement\Tests\Builder\TeamProjectsCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;

final class TeamsPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testBuildPresenterWithAllTeam(): void
    {
        $team_A     = ProjectReferenceStub::withId(150);
        $team_B     = ProjectReferenceStub::withId(666);
        $collection = TeamProjectsCollectionBuilder::withProjects(
            $team_A,
            $team_B,
        );

        $teams_presenter = TeamsPresenterBuilder::buildTeamsPresenter($collection, [$team_A->getId()]);
        self::assertCount(2, $teams_presenter);
        self::assertSame($team_A->getId(), $teams_presenter[0]->id);
        self::assertTrue($teams_presenter[0]->has_configuration_error);
        self::assertSame($team_B->getId(), $teams_presenter[1]->id);
        self::assertFalse($teams_presenter[1]->has_configuration_error);
    }
}
