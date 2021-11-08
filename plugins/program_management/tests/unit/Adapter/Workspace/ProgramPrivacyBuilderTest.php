<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Workspace;

use Tuleap\ProgramManagement\Domain\Workspace\ProgramPrivacy;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;

class ProgramPrivacyBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsProgramPrivacy(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(101)
            ->withAccess(\Project::ACCESS_PUBLIC)
            ->build();

        $project_manager = $this->createStub(\ProjectManager::class);
        $project_manager->method('getProject')->willReturn($project);

        $builder = new ProgramPrivacyBuilder($project_manager);

        $program_privacy = $builder->build(
            ProgramIdentifierBuilder::build()
        );

        self::assertEquals(
            ProgramPrivacy::fromPrivacy(
                false,
                false,
                false,
                true,
                false,
                'Project privacy set to public. By default, its content is available to all authenticated, but not restricted, users. Please note that more restrictive permissions might exist on some items.',
                'Public',
                'The Test Project'
            ),
            $program_privacy
        );
    }
}
