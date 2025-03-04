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

use Tuleap\ProgramManagement\Domain\Workspace\ProgramFlag;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullProjectStub;
use Tuleap\Project\Flags\ProjectFlagPresenter;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramFlagsBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsProgramFlagsList(): void
    {
        $external_flags_builder = $this->createStub(\Tuleap\Project\Flags\ProjectFlagsBuilder::class);
        $external_flags_builder->method('buildProjectFlags')->willReturn([
            new ProjectFlagPresenter('Flag 1', 'Description of Flag 1'),
            new ProjectFlagPresenter('Flag 2', 'Description of Flag 2'),
            new ProjectFlagPresenter('Flag 3', 'Description of Flag 3'),
        ]);

        $flags = (new ProgramFlagsBuilder(
            $external_flags_builder,
            RetrieveFullProjectStub::withProject(ProjectTestBuilder::aProject()->withId(193)->build())
        ))->build(ProgramIdentifierBuilder::buildWithId(193));

        self::assertCount(3, $flags);
        self::assertEquals(ProgramFlag::fromLabelAndDescription('Flag 1', 'Description of Flag 1'), $flags[0]);
        self::assertEquals(ProgramFlag::fromLabelAndDescription('Flag 2', 'Description of Flag 2'), $flags[1]);
        self::assertEquals(ProgramFlag::fromLabelAndDescription('Flag 3', 'Description of Flag 3'), $flags[2]);
    }
}
