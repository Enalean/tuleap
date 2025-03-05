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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Workspace;

use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectReferenceStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramBaseInfoBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsProgramBaseInfo(): void
    {
        $builder = new ProgramBaseInfoBuilder(
            RetrieveProjectReferenceStub::withProjects(
                ProjectReferenceStub::buildGeneric()
            )
        );

        $program_base_info = $builder->build(ProgramIdentifierBuilder::build());

        self::assertEquals('My project', $program_base_info->program_label);
        self::assertEquals('my_project', $program_base_info->program_shortname);
        self::assertEquals('', $program_base_info->program_icon);
    }
}
