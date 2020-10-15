<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
use PHPUnit\Framework\TestCase;
use Tracker;
use Tuleap\AgileDashboard\Artifact\PlannedArtifactDao;
use Tuleap\Tracker\Artifact\Artifact;

class UnplannedArtifactsAdderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var UnplannedArtifactsAdder
     */
    private $adder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlannedArtifactDao
     */
    private $planned_artifact_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Artifact
     */
    private $artifact;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artifacts_in_explicit_backlog_dao = Mockery::mock(ArtifactsInExplicitBacklogDao::class);
        $this->planned_artifact_dao              = Mockery::mock(PlannedArtifactDao::class);
        $this->explicit_backlog_dao              = Mockery::mock(ExplicitBacklogDao::class);

        $this->adder = new UnplannedArtifactsAdder(
            $this->explicit_backlog_dao,
            $this->artifacts_in_explicit_backlog_dao,
            $this->planned_artifact_dao
        );

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturn(101);

        $this->artifact = Mockery::mock(Artifact::class);
        $this->artifact->shouldReceive('getId')->andReturn(1);
        $this->artifact->shouldReceive('getTracker')->andReturn($tracker);
    }

    public function testItDoesNotAddArtifactIfProjectDoesNotUsesExplicitBacklog(): void
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->with(101)
            ->andReturnFalse();

        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive('addArtifactToProjectBacklog');

        $this->adder->addArtifactToTopBacklog($this->artifact);
    }

    public function testItDoesNotAddArtifactIfArtifactAlreadyPlanned(): void
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->with(101)
            ->andReturnTrue();

        $this->planned_artifact_dao->shouldReceive('isArtifactPlannedInAMilestoneOfTheProject')
            ->with(1, 101)
            ->andReturnTrue();

        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive('addArtifactToProjectBacklog');

        $this->expectException(ArtifactAlreadyPlannedException::class);

        $this->adder->addArtifactToTopBacklog($this->artifact);
    }

    public function testItAddsArtifactInTopBacklog(): void
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->with(101)
            ->andReturnTrue();

        $this->planned_artifact_dao->shouldReceive('isArtifactPlannedInAMilestoneOfTheProject')
            ->with(1, 101)
            ->andReturnFalse();

        $this->artifacts_in_explicit_backlog_dao->shouldReceive('addArtifactToProjectBacklog')->once();

        $this->adder->addArtifactToTopBacklog($this->artifact);
    }

    public function testItAddsArtifactInTopBacklogFromIds(): void
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->with(101)
            ->andReturnTrue();

        $this->planned_artifact_dao->shouldReceive('isArtifactPlannedInAMilestoneOfTheProject')
            ->with(1, 101)
            ->andReturnFalse();

        $this->artifacts_in_explicit_backlog_dao->shouldReceive('addArtifactToProjectBacklog')->once();

        $this->adder->addArtifactToTopBacklogFromIds(1, 101);
    }
}
