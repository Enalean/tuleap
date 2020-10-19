<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\ExplicitBacklog;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Tracker;
use Tracker_ArtifactFactory;
use Tuleap\AgileDashboard\Artifact\PlannedArtifactDao;
use Tuleap\Tracker\Artifact\Artifact;

final class UnplannedReportCriterionMatchingIdsRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var UnplannedReportCriterionMatchingIdsRetriever
     */
    private $retriever;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlannedArtifactDao
     */
    private $planned_artifact_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $tracker;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->explicit_backlog_dao              = Mockery::mock(ExplicitBacklogDao::class);
        $this->artifacts_in_explicit_backlog_dao = Mockery::mock(ArtifactsInExplicitBacklogDao::class);
        $this->planned_artifact_dao              = Mockery::mock(PlannedArtifactDao::class);
        $this->artifact_factory                  = Mockery::mock(Tracker_ArtifactFactory::class);

        $this->retriever = new UnplannedReportCriterionMatchingIdsRetriever(
            $this->explicit_backlog_dao,
            $this->artifacts_in_explicit_backlog_dao,
            $this->planned_artifact_dao,
            $this->artifact_factory
        );

        $this->user    = Mockery::mock(PFUser::class);
        $this->project = Mockery::mock(Project::class)->shouldReceive('getID')->andReturn(101)->getMock();
        $this->tracker = Mockery::mock(Tracker::class)->shouldReceive('getId')->andReturn(1)->getMock();

        $this->tracker->shouldReceive('getProject')->andReturn($this->project);
    }

    public function testItThrowsAnExceptionIfProjectIsNotInExplicitBacklog(): void
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->with(101)
            ->andReturnFalse();

        $this->expectException(ProjectNotUsingExplicitBacklogException::class);

        $this->retriever->getMatchingIds($this->tracker, $this->user);
    }

    public function testItReturnsTheMatchingIds(): void
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->with(101)
            ->andReturnTrue();

        $this->mockDataReturnedByDaos();
        $this->mockArtifactFactory();

        $expected = [
            142 => true,
        ];

        $this->assertSame(
            $expected,
            $this->retriever->getMatchingIds($this->tracker, $this->user)
        );
    }

    private function mockDataReturnedByDaos(): void
    {
        $this->artifacts_in_explicit_backlog_dao->shouldReceive('getAllArtifactNotInTopBacklogInTracker')
            ->once()
            ->with(1)
            ->andReturn([
                ['artifact_id' => 142],
                ['artifact_id' => 143],
                ['artifact_id' => 148],
                ['artifact_id' => 152],
            ]);

        $this->planned_artifact_dao->shouldReceive('gatAllPlannedArtifactsOfTheProject')
            ->once()
            ->with(101, 1)
            ->andReturn([
                ['artifact_id' => 143],
                ['artifact_id' => 152],
            ]);
    }

    private function mockArtifactFactory(): void
    {
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')
            ->with($this->user, 142)
            ->andReturn(Mockery::mock(Artifact::class));

        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')
            ->with($this->user, 148)
            ->andReturnNull();
    }
}
