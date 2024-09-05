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

namespace Tuleap\ProgramManagement\Adapter\Workspace;

use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class ProjectManagerAdapterTest extends TestCase
{
    public function testItProxiesToProjectManager(): void
    {
        $project         = ProjectTestBuilder::aProject()->withId(101)->build();
        $project_manager = $this->createMock(\ProjectManager::class);
        $project_manager->expects(self::once())
            ->method('getProject')
            ->with(101)
            ->willReturn($project);

        $adapter = new ProjectManagerAdapter($project_manager, RetrieveUserStub::withUser(UserTestBuilder::buildWithDefaults()));
        self::assertSame((int) $project->getID(), $adapter->getProjectWithId(101)->getId());
    }
}
