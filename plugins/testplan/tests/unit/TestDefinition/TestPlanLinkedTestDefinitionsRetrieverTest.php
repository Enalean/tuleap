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

use Tracker;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\TestManagement\ArtifactDao;
use Tuleap\TestManagement\Config;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TestPlanLinkedTestDefinitionsRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Config
     */
    private $testmanagement_config;
    /**
     * @var ArtifactDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $artifact_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TestPlanTestDefinitionWithTestStatusRetriever
     */
    private $test_definition_with_test_status_retriever;

    private TestPlanLinkedTestDefinitionsRetriever $retriever;

    protected function setUp(): void
    {
        $this->testmanagement_config                      = $this->createMock(Config::class);
        $this->artifact_dao                               = $this->createMock(ArtifactDao::class);
        $this->artifact_factory                           = $this->createMock(\Tracker_ArtifactFactory::class);
        $this->test_definition_with_test_status_retriever = $this->createMock(TestPlanTestDefinitionWithTestStatusRetriever::class);

        $this->retriever = new TestPlanLinkedTestDefinitionsRetriever(
            $this->testmanagement_config,
            $this->artifact_dao,
            $this->artifact_factory,
            $this->test_definition_with_test_status_retriever,
        );
    }

    public function testRetrievesLinkedArtifact(): void
    {
        $this->testmanagement_config->method('getTestDefinitionTrackerId')->willReturn(102);

        $this->artifact_dao
            ->expects($this->once())
            ->method('searchPaginatedLinkedArtifactsByLinkTypeAndTrackerId')
            ->willReturn(
                [['mocked_artifact_row_1'], ['mocked_artifact_row_2']],
            );
        $this->artifact_dao->method('foundRows')->willReturn(2);

        $artifact_user_can_view = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_user_can_view->method('userCanView')->willReturn(true);
        $artifact_user_can_not_view = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_user_can_not_view->method('userCanView')->willReturn(false);
        $this->artifact_factory->expects($this->exactly(2))->method('getInstanceFromRow')->willReturnOnConsecutiveCalls(
            $artifact_user_can_view,
            $artifact_user_can_not_view,
        );

        $backlog_item = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $backlog_item->method('getId')->willReturn(789);
        $tracker = $this->createMock(Tracker::class);
        $project = $this->createMock(\Project::class);
        $tracker->method('getProject')->willReturn($project);
        $backlog_item->method('getTracker')->willReturn($tracker);
        $milestone = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $user      = UserTestBuilder::aUser()->build();

        $test_definition_with_test_status = TestPlanTestDefinitionWithTestStatus::unknownTestStatusForTheDefinition($artifact_user_can_view);
        $this->test_definition_with_test_status_retriever->method('retrieveTestDefinitionWithTestStatus')
            ->with($milestone, $user, [$artifact_user_can_view])
            ->willReturn([$test_definition_with_test_status]);

        $linked_artifacts = $this->retriever->getDefinitionsLinkedToAnArtifact(
            $backlog_item,
            $milestone,
            $user,
            512,
            0
        );

        self::assertEquals([$test_definition_with_test_status], $linked_artifacts->getRequestedLinkedTestDefinitions());
    }

    public function testNoArtifactsAreFoundWhenTheTestDefinitionTrackerIsNotSetInTheTestManagementConfig(): void
    {
        $this->testmanagement_config->method('getTestDefinitionTrackerId')->willReturn(false);

        $backlog_item = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $tracker      = $this->createMock(Tracker::class);
        $project      = $this->createMock(\Project::class);
        $tracker->method('getProject')->willReturn($project);
        $backlog_item->method('getTracker')->willReturn($tracker);

        self::assertEquals(
            TestPlanLinkedTestDefinitions::empty(),
            $this->retriever->getDefinitionsLinkedToAnArtifact(
                $backlog_item,
                $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class),
                UserTestBuilder::aUser()->build(),
                512,
                0
            )
        );
    }
}
