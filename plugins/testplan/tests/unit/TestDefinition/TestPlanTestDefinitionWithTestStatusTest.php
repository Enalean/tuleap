<?php
/**
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

declare(strict_types=1);

namespace Tuleap\TestPlan\TestDefinition;

use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TestPlanTestDefinitionWithTestStatusTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testBuildWhenTestStatusIsNotKnown(): void
    {
        $test_def = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);

        $test_def_with_status = TestPlanTestDefinitionWithTestStatus::unknownTestStatusForTheDefinition($test_def);

        self::assertEquals($test_def, $test_def_with_status->getTestDefinition());
        self::assertEquals(null, $test_def_with_status->getStatus());
        self::assertEquals(null, $test_def_with_status->getTestExecutionIdUsedToDefineStatus());
        self::assertEquals(null, $test_def_with_status->getTestExecutionDate());
        self::assertEquals(null, $test_def_with_status->getTestExecutionSubmittedBy());
        self::assertEquals(null, $test_def_with_status->getTestCampaignIdDefiningTheStatus());
    }

    public function testBuildWhenTestStatusIsKnown(): void
    {
        $test_def     = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $submitted_by = UserTestBuilder::aUser()->build();

        $test_def_with_status = TestPlanTestDefinitionWithTestStatus::knownTestStatusForTheDefinition($test_def, 'passed', 852, 10, $submitted_by, 14);

        self::assertEquals($test_def, $test_def_with_status->getTestDefinition());
        self::assertEquals('passed', $test_def_with_status->getStatus());
        self::assertEquals(852, $test_def_with_status->getTestExecutionIdUsedToDefineStatus());
        self::assertEquals(10, $test_def_with_status->getTestExecutionDate());
        self::assertEquals($submitted_by, $test_def_with_status->getTestExecutionSubmittedBy());
        self::assertEquals(14, $test_def_with_status->getTestCampaignIdDefiningTheStatus());
    }
}
