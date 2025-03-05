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
final class TestPlanTestDefinitionWithTestStatusRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TestPlanTestDefinitionsTestStatusDAO
     */
    private $dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TestStatusPerTestDefinitionsInformationForUserRetriever
     */
    private $information_retriever;
    /**
     * @var \UserManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $user_manager;

    private TestPlanTestDefinitionWithTestStatusRetriever $retriever;

    protected function setUp(): void
    {
        $this->dao                   = $this->createMock(TestPlanTestDefinitionsTestStatusDAO::class);
        $this->information_retriever = $this->createMock(TestStatusPerTestDefinitionsInformationForUserRetriever::class);
        $this->user_manager          = $this->createMock(\UserManager::class);

        $this->retriever = new TestPlanTestDefinitionWithTestStatusRetriever(
            $this->dao,
            $this->information_retriever,
            $this->user_manager,
        );
    }

    public function testRetrieveTestDefinitionWithTestStatusAndAllTestsWithUnknownStatusAtTheEnd(): void
    {
        $milestone = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $milestone->method('getId')->willReturn('132');

        $test_definition_1 = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $test_definition_1->method('getId')->willReturn('456');

        $test_definition_2 = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $test_definition_2->method('getId')->willReturn('457');

        $test_definitions = [$test_definition_1, $test_definition_2];

        $information = new TestPlanMilestoneInformationNeededToRetrieveTestStatusPerTestDefinition(
            $milestone,
            $test_definitions,
            ['741', 4],
            369,
            444,
            555
        );
        $this->information_retriever->method('getInformationNeededToRetrieveTestStatusPerTestDefinition')
            ->willReturn($information);

        $this->dao->method('searchTestStatusPerTestDefinitionInAMilestone')
            ->willReturn([457 => ['test_status' => 'passed', 'test_exec_id' => 95147, 'test_exec_submitted_on' => 10, 'test_exec_submitted_by' => 404, 'test_campaign_id' => 23]]);

        $milestone = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $user      = UserTestBuilder::aUser()->build();
        $this->user_manager->method('getUserById')->with(404)->willReturn(null);
        $this->user_manager->method('getUserAnonymous')->willReturn(UserTestBuilder::anAnonymousUser()->build());

        $test_definitions_with_test_status = $this->retriever->retrieveTestDefinitionWithTestStatus(
            $milestone,
            $user,
            $test_definitions
        );

        self::assertEquals(
            [
                TestPlanTestDefinitionWithTestStatus::knownTestStatusForTheDefinition($test_definition_2, 'passed', 95147, 10, UserTestBuilder::anAnonymousUser()->build(), 23),
                TestPlanTestDefinitionWithTestStatus::unknownTestStatusForTheDefinition($test_definition_1),
            ],
            $test_definitions_with_test_status
        );
    }

    public function testTestDefinitionsHaveAnUnknownStatusWhenInformationNeededToAccessTheInformationCannotBeRetrievedForTheUser(): void
    {
        $this->information_retriever->method('getInformationNeededToRetrieveTestStatusPerTestDefinition')
            ->willReturn(null);

        $test_definition_1 = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $test_definition_1->method('getId')->willReturn('456');

        $test_definition_2 = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $test_definition_2->method('getId')->willReturn('457');

        $test_definitions = [$test_definition_1, $test_definition_2];

        $test_definitions_with_test_status = $this->retriever->retrieveTestDefinitionWithTestStatus(
            $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class),
            UserTestBuilder::aUser()->build(),
            $test_definitions
        );

        self::assertEquals(
            [
                TestPlanTestDefinitionWithTestStatus::unknownTestStatusForTheDefinition($test_definition_1),
                TestPlanTestDefinitionWithTestStatus::unknownTestStatusForTheDefinition($test_definition_2),
            ],
            $test_definitions_with_test_status
        );
    }
}
