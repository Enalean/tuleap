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

final class TestPlanTestDefinitionWithTestStatusRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TestPlanTestDefinitionsTestStatusDAO
     */
    private $dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TestStatusPerTestDefinitionsInformationForUserRetriever
     */
    private $information_retriever;

    /**
     * @var TestPlanTestDefinitionWithTestStatusRetriever
     */
    private $retriever;

    protected function setUp(): void
    {
        $this->dao                   = \Mockery::mock(TestPlanTestDefinitionsTestStatusDAO::class);
        $this->information_retriever = \Mockery::mock(TestStatusPerTestDefinitionsInformationForUserRetriever::class);

        $this->retriever = new TestPlanTestDefinitionWithTestStatusRetriever(
            $this->dao,
            $this->information_retriever,
        );
    }

    public function testRetrieveTestDefinitionWithTestStatusAndAllTestsWithUnknownStatusAtTheEnd(): void
    {
        $milestone         = \Mockery::mock(\Tracker_Artifact::class)->shouldReceive('getId')->andReturn('132')->getMock();
        $test_definition_1 = \Mockery::mock(\Tracker_Artifact::class)->shouldReceive('getId')->andReturn('456')->getMock();
        $test_definition_2 = \Mockery::mock(\Tracker_Artifact::class)->shouldReceive('getId')->andReturn('457')->getMock();
        $test_definitions  = [$test_definition_1, $test_definition_2];

        $information = new TestPlanMilestoneInformationNeededToRetrieveTestStatusPerTestDefinition(
            $milestone,
            $test_definitions,
            ['741', 4],
            369,
            444,
            555
        );
        $this->information_retriever->shouldReceive('getInformationNeededToRetrieveTestStatusPerTestDefinition')
            ->andReturn($information);

        $this->dao->shouldReceive('searchTestStatusPerTestDefinitionInAMilestone')
            ->andReturn([457 => 'passed']);

        $milestone = \Mockery::mock(\Tracker_Artifact::class);
        $user      = UserTestBuilder::aUser()->build();

        $test_definitions_with_test_status = $this->retriever->retrieveTestDefinitionWithTestStatus(
            $milestone,
            $user,
            $test_definitions
        );

        $this->assertEquals(
            [
                TestPlanTestDefinitionWithTestStatus::knownTestStatusForTheDefinition($test_definition_2, 'passed'),
                TestPlanTestDefinitionWithTestStatus::unknownTestStatusForTheDefinition($test_definition_1),
            ],
            $test_definitions_with_test_status
        );
    }

    public function testTestDefinitionsHaveAnUnknownStatusWhenInformationNeededToAccessTheInformationCannotBeRetrievedForTheUser(): void
    {
        $this->information_retriever->shouldReceive('getInformationNeededToRetrieveTestStatusPerTestDefinition')
            ->andReturn(null);

        $test_definition_1 = \Mockery::mock(\Tracker_Artifact::class)->shouldReceive('getId')->andReturn('456')->getMock();
        $test_definition_2 = \Mockery::mock(\Tracker_Artifact::class)->shouldReceive('getId')->andReturn('457')->getMock();
        $test_definitions  = [$test_definition_1, $test_definition_2];

        $test_definitions_with_test_status = $this->retriever->retrieveTestDefinitionWithTestStatus(
            \Mockery::mock(\Tracker_Artifact::class),
            UserTestBuilder::aUser()->build(),
            $test_definitions
        );

        $this->assertEquals(
            [
                TestPlanTestDefinitionWithTestStatus::unknownTestStatusForTheDefinition($test_definition_1),
                TestPlanTestDefinitionWithTestStatus::unknownTestStatusForTheDefinition($test_definition_2),
            ],
            $test_definitions_with_test_status
        );
    }
}
