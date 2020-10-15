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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Test\Builders\UserTestBuilder;

final class TestPlanTestDefinitionWithTestStatusTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testBuildWhenTestStatusIsNotKnown(): void
    {
        $test_def = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);

        $test_def_with_status = TestPlanTestDefinitionWithTestStatus::unknownTestStatusForTheDefinition($test_def);

        $this->assertEquals($test_def, $test_def_with_status->getTestDefinition());
        $this->assertEquals(null, $test_def_with_status->getStatus());
        $this->assertEquals(null, $test_def_with_status->getTestExecutionIdUsedToDefineStatus());
        $this->assertEquals(null, $test_def_with_status->getTestExecutionDate());
        $this->assertEquals(null, $test_def_with_status->getTestExecutionSubmittedBy());
        $this->assertEquals(null, $test_def_with_status->getTestCampaignIdDefiningTheStatus());
    }

    public function testBuildWhenTestStatusIsKnown(): void
    {
        $test_def     = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $submitted_by = UserTestBuilder::aUser()->build();

        $test_def_with_status = TestPlanTestDefinitionWithTestStatus::knownTestStatusForTheDefinition($test_def, 'passed', 852, 10, $submitted_by, 14);

        $this->assertEquals($test_def, $test_def_with_status->getTestDefinition());
        $this->assertEquals("passed", $test_def_with_status->getStatus());
        $this->assertEquals(852, $test_def_with_status->getTestExecutionIdUsedToDefineStatus());
        $this->assertEquals(10, $test_def_with_status->getTestExecutionDate());
        $this->assertEquals($submitted_by, $test_def_with_status->getTestExecutionSubmittedBy());
        $this->assertEquals(14, $test_def_with_status->getTestCampaignIdDefiningTheStatus());
    }
}
