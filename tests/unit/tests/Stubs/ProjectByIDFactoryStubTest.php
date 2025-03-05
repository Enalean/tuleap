<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Test\Stubs;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectByIDFactoryStubTest extends TestCase
{
    public function testItHasOneProject(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        self::assertSame($project, ProjectByIDFactoryStub::buildWith($project)->getProjectById((int) $project->getID()));
    }

    public function testItHasTwoProjects(): void
    {
        $project_101 = ProjectTestBuilder::aProject()->withId(101)->build();
        $project_102 = ProjectTestBuilder::aProject()->withId(102)->build();

        $factory = ProjectByIDFactoryStub::buildWith($project_101, $project_102);

        self::assertSame($project_101, $factory->getValidProjectById(101));
        self::assertSame($project_102, $factory->getValidProjectById(102));
    }

    public function testGetValidWillThrowWithDeletedProjects(): void
    {
        $project_101 = ProjectTestBuilder::aProject()->withId(101)->withStatusDeleted()->build();

        $this->expectException(\Project_NotFoundException::class);

        ProjectByIDFactoryStub::buildWith($project_101)->getValidProjectById(101);
    }
}
