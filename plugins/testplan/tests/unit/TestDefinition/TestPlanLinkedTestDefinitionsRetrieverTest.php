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
use Tracker;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\TestManagement\ArtifactDao;
use Tuleap\TestManagement\Config;

final class TestPlanLinkedTestDefinitionsRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Config
     */
    private $testmanagement_config;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ArtifactDao
     */
    private $artifact_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var TestPlanLinkedTestDefinitionsRetriever
     */
    private $retriever;

    protected function setUp(): void
    {
        $this->testmanagement_config = \Mockery::mock(Config::class);
        $this->artifact_dao          = \Mockery::mock(ArtifactDao::class);
        $this->artifact_factory      = \Mockery::mock(\Tracker_ArtifactFactory::class);

        $this->retriever = new TestPlanLinkedTestDefinitionsRetriever(
            $this->testmanagement_config,
            $this->artifact_dao,
            $this->artifact_factory,
        );
    }

    public function testRetrievesLinkedArtifact(): void
    {
        $this->testmanagement_config->shouldReceive('getTestDefinitionTrackerId')->andReturn(102);

        $this->artifact_dao->shouldReceive('searchPaginatedLinkedArtifactsByLinkNatureAndTrackerId')
            ->twice()
            ->andReturn(
                [['mocked_artifact_row_1']],
                [['mocked_artifact_row_1']],
            );

        $artifact_user_can_view     = \Mockery::mock(\Tracker_Artifact::class);
        $artifact_user_can_view->shouldReceive('userCanView')->andReturn(true);
        $artifact_user_can_not_view = \Mockery::mock(\Tracker_Artifact::class);
        $artifact_user_can_not_view->shouldReceive('userCanView')->andReturn(false);
        $this->artifact_factory->shouldReceive('getInstanceFromRow')->twice()->andReturn(
            $artifact_user_can_view,
            $artifact_user_can_not_view,
        );

        $backlog_item = \Mockery::mock(\Tracker_Artifact::class);
        $backlog_item->shouldReceive('getId')->andReturn(789);
        $tracker      = \Mockery::mock(Tracker::class);
        $project      = \Mockery::mock(\Project::class);
        $tracker->shouldReceive('getProject')->andReturn($project);
        $backlog_item->shouldReceive('getTracker')->andReturn($tracker);

        $linked_artifacts = $this->retriever->getDefinitionsLinkedToAnArtifact($backlog_item, UserTestBuilder::aUser()->build());

        $this->assertEquals([$artifact_user_can_view], $linked_artifacts);
    }

    public function testNoArtifactsAreFoundWhenTheTestDefinitionTrackerIsNotSetInTheTestManagementConfig(): void
    {
        $this->testmanagement_config->shouldReceive('getTestDefinitionTrackerId')->andReturn(false);

        $backlog_item = \Mockery::mock(\Tracker_Artifact::class);
        $tracker      = \Mockery::mock(Tracker::class);
        $project      = \Mockery::mock(\Project::class);
        $tracker->shouldReceive('getProject')->andReturn($project);
        $backlog_item->shouldReceive('getTracker')->andReturn($tracker);

        $this->assertEmpty(
            $this->retriever->getDefinitionsLinkedToAnArtifact($backlog_item, UserTestBuilder::aUser()->build())
        );
    }
}
