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

namespace Tuleap\ProgramManagement\Adapter;

use Project;
use Tuleap\ProgramManagement\Domain\ProgramManagementProject;

final class ProgramManagementProjectAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsProjectData(): void
    {
        $project         = new Project(['group_id' => 101, 'group_name' => 'Team 1', 'unix_group_name' => 'team_1']);
        $project_manager = $this->createMock(\ProjectManager::class);
        $project_manager->expects(self::once())->method('getProject')->with(101)->willReturn($project);

        $project_data = new ProgramManagementProject($project->getID(), $project->getUnixName(), $project->getPublicName(), $project->getUrl());

        $adapter = new ProgramManagementProjectAdapter($project_manager);
        $this->assertEquals($project_data, $adapter->buildFromId(101));
    }
}
