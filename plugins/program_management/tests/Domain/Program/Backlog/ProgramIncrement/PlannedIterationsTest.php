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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\Workspace\ProgramBaseInfo;
use Tuleap\ProgramManagement\Domain\Workspace\ProgramFlag;
use Tuleap\ProgramManagement\Domain\Workspace\ProgramPrivacy;
use Tuleap\ProgramManagement\Tests\Builder\IterationsLabelsBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramBaseInfoStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramFlagsStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramIncrementInfoStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramPrivacyStub;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramUserPrivilegesStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

class PlannedIterationsTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuilds(): void
    {
        $planned_iterations = PlannedIterations::build(
            BuildProgramFlagsStub::withDefaults(),
            BuildProgramPrivacyStub::withPrivateAccess(),
            BuildProgramBaseInfoStub::withDefault(),
            BuildProgramIncrementInfoStub::withId(1260),
            RetrieveProgramUserPrivilegesStub::withProgramAdminUser(),
            ProgramIdentifierBuilder::build(),
            UserIdentifierStub::withId(666),
            ProgramIncrementIdentifierBuilder::buildWithId(1260),
            IterationsLabelsBuilder::buildWithLabels('Cycles', 'cycle')
        );

        self::assertEquals([
            ProgramFlag::fromLabelAndDescription('Top Secret', 'For authorized eyes only')
        ], $planned_iterations->getProgramFlag());

        self::assertEquals(
            ProgramPrivacy::fromPrivacy(
                false,
                false,
                true,
                false,
                false,
                'It is private, please go away',
                'Private',
                'Guinea Pig'
            ),
            $planned_iterations->getProgramPrivacy()
        );

        self::assertEquals(
            ProgramBaseInfo::fromBaseInfo(
                'Guinea Pig',
                'guinea-pig',
                '🐹'
            ),
            $planned_iterations->getProgramBaseInfo()
        );
        self::assertEquals(ProgramIncrementInfo::fromIncrementInfo(1260, 'Program increment #1260'), $planned_iterations->getProgramIncrementInfo());
        self::assertEquals(IterationsLabelsBuilder::buildWithLabels("Cycles", "cycle"), $planned_iterations->getIterationLabels());
        self::assertTrue($planned_iterations->isUserAdmin());
    }
}
