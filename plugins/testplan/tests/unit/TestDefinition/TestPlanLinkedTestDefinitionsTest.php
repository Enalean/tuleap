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

final class TestPlanLinkedTestDefinitionsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCreateEmptySetOfLinkedTestDefinitions(): void
    {
        $linked_test_definitions = TestPlanLinkedTestDefinitions::empty();

        $this->assertEmpty($linked_test_definitions->getRequestedLinkedTestDefinitions());
        $this->assertEquals(0, $linked_test_definitions->getTotalNumberOfLinkedTestDefinitions());
    }

    public function testStoreASubsetOfLinkedTestDefinitions(): void
    {
        $artifacts = [
            TestPlanTestDefinitionWithTestStatus::unknownTestStatusForTheDefinition(\Mockery::mock(
                \Tuleap\Tracker\Artifact\Artifact::class
            )),
            TestPlanTestDefinitionWithTestStatus::unknownTestStatusForTheDefinition(\Mockery::mock(
                \Tuleap\Tracker\Artifact\Artifact::class
            ))
        ];
        $linked_test_definitions = TestPlanLinkedTestDefinitions::subset($artifacts, 512);

        $this->assertEquals($artifacts, $linked_test_definitions->getRequestedLinkedTestDefinitions());
        $this->assertEquals(512, $linked_test_definitions->getTotalNumberOfLinkedTestDefinitions());
    }

    public function testCannotGiveASubsetBiggerThanTheTotalNumberOfTestDefinitions(): void
    {
        $artifacts = [
            TestPlanTestDefinitionWithTestStatus::unknownTestStatusForTheDefinition(\Mockery::mock(
                \Tuleap\Tracker\Artifact\Artifact::class
            )),
            TestPlanTestDefinitionWithTestStatus::unknownTestStatusForTheDefinition(\Mockery::mock(
                \Tuleap\Tracker\Artifact\Artifact::class
            ))
        ];

        $this->expectException(\LogicException::class);
        TestPlanLinkedTestDefinitions::subset($artifacts, 1);
    }
}
