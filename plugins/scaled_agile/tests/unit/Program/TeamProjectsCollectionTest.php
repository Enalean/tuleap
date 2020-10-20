<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program;

use PHPUnit\Framework\TestCase;

final class TeamProjectsCollectionTest extends TestCase
{
    public function testGetTeamProjectsReturnsProjects(): void
    {
        $first_team_project = new \Project(['group_id' => '103']);
        $second_team_project = new \Project(['group_id' => '125']);
        $collection = new TeamProjectsCollection(
            [$first_team_project, $second_team_project]
        );
        $this->assertContains($first_team_project, $collection->getTeamProjects());
        $this->assertContains($second_team_project, $collection->getTeamProjects());
    }

    public function testIsEmptyReturnsTrue(): void
    {
        $collection = new TeamProjectsCollection([]);
        $this->assertTrue($collection->isEmpty());
    }

    public function testIsEmptyReturnsFalse()
    {
        $collection = new TeamProjectsCollection([\Project::buildForTest()]);
        $this->assertFalse($collection->isEmpty());
    }
}
