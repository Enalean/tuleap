<?php
/**
 * Copyright (c) Enalean 2019 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Masschange;

use Codendi_Request;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Tracker;
use Tuleap\AgileDashboard\Artifact\PlannedArtifactDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedArtifactsAdder;
use Tuleap\GlobalResponseMock;

class AdditionalMasschangeActionProcessorTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalResponseMock;

    /**
     * @var AdditionalMasschangeActionProcessor
     */
    private $processor;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $tracker;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlannedArtifactDao
     */
    private $planned_artifact_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UnplannedArtifactsAdder
     */
    private $unplanned_artifacts_adder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artifacts_in_explicit_backlog_dao = Mockery::mock(ArtifactsInExplicitBacklogDao::class);
        $this->planned_artifact_dao              = Mockery::mock(PlannedArtifactDao::class);
        $this->unplanned_artifacts_adder         = Mockery::mock(UnplannedArtifactsAdder::class);

        $this->processor = new AdditionalMasschangeActionProcessor(
            $this->artifacts_in_explicit_backlog_dao,
            $this->planned_artifact_dao,
            $this->unplanned_artifacts_adder
        );

        $this->user    = Mockery::mock(PFUser::class);
        $this->tracker = Mockery::mock(Tracker::class);

        $project = Mockery::mock(Project::class)->shouldReceive('getID')->andReturn('101')->getMock();
        $this->tracker->shouldReceive('getProject')->andReturn($project);
    }

    public function testItDoesNothingIfUserIsNotTrackerAdmin(): void
    {
        $request = new Codendi_Request([]);

        $this->tracker->shouldReceive('userIsAdmin')->once()->andReturnFalse();

        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive('removeItemsFromExplicitBacklogOfProject');
        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive('addArtifactToProjectBacklog');
        $this->planned_artifact_dao->shouldNotReceive('isArtifactPlannedInAMilestoneOfTheProject');

        $this->processor->processAction(
            $this->user,
            $this->tracker,
            $request,
            ['125', '144']
        );
    }

    public function testItDoesNothingIfMasschangeActionIsNotInRequest(): void
    {
        $request = new Codendi_Request([]);

        $this->tracker->shouldReceive('userIsAdmin')->once()->andReturnTrue();

        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive('removeItemsFromExplicitBacklogOfProject');
        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive('addArtifactToProjectBacklog');
        $this->planned_artifact_dao->shouldNotReceive('isArtifactPlannedInAMilestoneOfTheProject');

        $this->processor->processAction(
            $this->user,
            $this->tracker,
            $request,
            ['125', '144']
        );
    }

    public function testItDoesNothingIfMasschangeActionIsUnchanged(): void
    {
        $request = new Codendi_Request(['masschange-action-explicit-backlog' => 'unchanged']);

        $this->tracker->shouldReceive('userIsAdmin')->once()->andReturnTrue();

        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive('removeItemsFromExplicitBacklogOfProject');
        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive('addArtifactToProjectBacklog');
        $this->planned_artifact_dao->shouldNotReceive('isArtifactPlannedInAMilestoneOfTheProject');

        $this->processor->processAction(
            $this->user,
            $this->tracker,
            $request,
            ['125', '144']
        );
    }

    public function testItRemovesArtifactsFromBacklogIfMasschangeActionIsRemove(): void
    {
        $request = new Codendi_Request(['masschange-action-explicit-backlog' => 'remove']);

        $this->tracker->shouldReceive('userIsAdmin')->once()->andReturnTrue();

        $this->artifacts_in_explicit_backlog_dao->shouldReceive('removeItemsFromExplicitBacklogOfProject')
            ->once()
            ->with(101, ['125', '144']);

        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive('addArtifactToProjectBacklog');

        $this->planned_artifact_dao->shouldReceive('isArtifactPlannedInAMilestoneOfTheProject')->andReturnFalse();

        $this->processor->processAction(
            $this->user,
            $this->tracker,
            $request,
            ['125', '144']
        );
    }

    public function testItAsksForRemovalFromBacklogEvenIfArtifactAreAlreadyPlanned(): void
    {
        $request = new Codendi_Request(['masschange-action-explicit-backlog' => 'remove']);

        $this->tracker->shouldReceive('userIsAdmin')->once()->andReturnTrue();

        $this->artifacts_in_explicit_backlog_dao->shouldReceive('removeItemsFromExplicitBacklogOfProject')
            ->once()
            ->with(101, ['125', '144']);

        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive('addArtifactToProjectBacklog');

        $this->planned_artifact_dao->shouldReceive('isArtifactPlannedInAMilestoneOfTheProject')->andReturnTrue();

        $this->processor->processAction(
            $this->user,
            $this->tracker,
            $request,
            ['125', '144']
        );
    }

    public function testItAddsArtifactsFromBacklogIfMasschangeActionIsAdd(): void
    {
        $request = new Codendi_Request(['masschange-action-explicit-backlog' => 'add']);

        $this->tracker->shouldReceive('userIsAdmin')->once()->andReturnTrue();

        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive('removeItemsFromExplicitBacklogOfProject');
        $this->unplanned_artifacts_adder->shouldReceive('addArtifactToTopBacklogFromIds')
            ->with(125, 101)
            ->once();

        $this->unplanned_artifacts_adder->shouldReceive('addArtifactToTopBacklogFromIds')
            ->with(144, 101)
            ->once();

        $this->processor->processAction(
            $this->user,
            $this->tracker,
            $request,
            ['125', '144']
        );
    }

    public function testItDoesNothingIfMasschangeActionIsNotKnown(): void
    {
        $request = new Codendi_Request(['masschange-action-explicit-backlog' => 'whatever']);

        $this->tracker->shouldReceive('userIsAdmin')->once()->andReturnTrue();

        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive('removeItemsFromExplicitBacklogOfProject');
        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive('addArtifactToProjectBacklog');
        $this->planned_artifact_dao->shouldNotReceive('isArtifactPlannedInAMilestoneOfTheProject');

        $this->processor->processAction(
            $this->user,
            $this->tracker,
            $request,
            ['125', '144']
        );
    }
}
