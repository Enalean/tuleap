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
use Tuleap\ProgramManagement\Adapter\Workspace\ProjectProxy;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullProjectStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramManagementProjectAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsProjectData(): void
    {
        $project               = new Project(['group_id' => 101, 'group_name' => 'Team 1', 'unix_group_name' => 'team_1', 'icon_codepoint' => '']);
        $retrieve_full_project = RetrieveFullProjectStub::withProject($project);

        $project_data = ProjectProxy::buildFromProject($project);

        $adapter = new ProjectReferenceRetriever($retrieve_full_project);
        $this->assertEquals($project_data, $adapter->buildFromId(101));
    }
}
