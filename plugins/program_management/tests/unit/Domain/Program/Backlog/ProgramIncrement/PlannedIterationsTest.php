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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\Workspace\ProgramBaseInfo;
use Tuleap\ProgramManagement\Domain\Workspace\ProgramFlag;
use Tuleap\ProgramManagement\Domain\Workspace\ProgramPrivacy;
use Tuleap\ProgramManagement\Tests\Builder\IterationTrackerConfigurationBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\UserPreferenceBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramBaseInfoStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramFlagsStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramIncrementInfoStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramPrivacyStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserIsProgramAdminStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PlannedIterationsTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ITERATION_TRACKER_ID = 101;
    private const ITERATION_LABEL      = 'Cycles';
    private const ITERATION_SUB_LABEL  = 'cycle';

    public function testItBuilds(): void
    {
        $planned_iterations = PlannedIterations::build(
            BuildProgramFlagsStub::withDefaults(),
            BuildProgramPrivacyStub::withPrivateAccess(),
            BuildProgramBaseInfoStub::withDefault(),
            BuildProgramIncrementInfoStub::withId(1260),
            VerifyUserIsProgramAdminStub::withProgramAdminUser(),
            ProgramIdentifierBuilder::build(),
            UserIdentifierStub::withId(666),
            ProgramIncrementIdentifierBuilder::buildWithId(1260),
            IterationTrackerConfigurationBuilder::buildWithIdAndLabels(
                self::ITERATION_TRACKER_ID,
                self::ITERATION_LABEL,
                self::ITERATION_SUB_LABEL
            ),
            UserPreferenceBuilder::withPreference('accessibility_mode', '1')
        );

        self::assertEquals([
            ProgramFlag::fromLabelAndDescription('Top Secret', 'For authorized eyes only'),
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
        self::assertEquals(
            ProgramIncrementInfo::fromIncrementInfo(
                1260,
                'Program increment #1260',
                'Oct 01',
                'Oct 31'
            ),
            $planned_iterations->getProgramIncrementInfo()
        );
        $labels = $planned_iterations->getIterationLabels();
        self::assertSame(self::ITERATION_LABEL, $labels->label);
        self::assertSame(self::ITERATION_SUB_LABEL, $labels->sub_label);
        self::assertTrue($planned_iterations->isUserAdmin());
        self::assertSame(self::ITERATION_TRACKER_ID, $planned_iterations->getIterationTrackerId());
        self::assertTrue($planned_iterations->isAccessibilityModeEnabled());
    }
}
