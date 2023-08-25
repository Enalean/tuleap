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

namespace Tuleap\AgileDashboard\Artifact;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Planning;
use PlanningFactory;
use PlanningPermissionsManager;
use Project;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tracker;
use Tuleap\AgileDashboard\BlockScrumAccess;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\Planning\PlanningTrackerBacklogChecker;
use Tuleap\Kanban\CheckSplitKanbanConfiguration;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalButtonAction;
use Tuleap\Tracker\Artifact\Artifact;

final class AdditionalArtifactActionBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var AdditionalArtifactActionBuilder
     */
    private $builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningPermissionsManager
     */
    private $planning_permissions_manager;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArtifactsInExplicitBacklogDao
     */
    private $artifacts_explicit_backlog_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Artifact
     */
    private $artifact;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $root_planning;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlannedArtifactDao
     */
    private $planned_artifact_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningTrackerBacklogChecker
     */
    private $planning_tracker_backlog_checker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|EventDispatcherInterface
     */
    private $event_dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->explicit_backlog_dao             = Mockery::mock(ExplicitBacklogDao::class);
        $this->planning_factory                 = Mockery::mock(PlanningFactory::class);
        $this->planning_permissions_manager     = Mockery::mock(PlanningPermissionsManager::class);
        $this->artifacts_explicit_backlog_dao   = Mockery::mock(ArtifactsInExplicitBacklogDao::class);
        $this->planned_artifact_dao             = Mockery::mock(PlannedArtifactDao::class);
        $this->planning_tracker_backlog_checker = Mockery::mock(PlanningTrackerBacklogChecker::class);
        $this->event_dispatcher                 = Mockery::mock(EventDispatcherInterface::class);

        $this->builder = new AdditionalArtifactActionBuilder(
            $this->explicit_backlog_dao,
            $this->planning_factory,
            $this->planning_permissions_manager,
            $this->artifacts_explicit_backlog_dao,
            $this->planned_artifact_dao,
            new JavascriptAsset(Mockery::mock(IncludeAssets::class)->shouldReceive('getFileURL')->getMock(), 'mock.js'),
            $this->planning_tracker_backlog_checker,
            $this->event_dispatcher,
            new CheckSplitKanbanConfiguration()
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn('101');

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getProject')->andReturn($project);
        $tracker->shouldReceive('getId')->andReturn('148');

        $this->artifact = Mockery::mock(Artifact::class);
        $this->artifact->shouldReceive('getTracker')->andReturn($tracker);
        $this->artifact->shouldReceive('getId')->andReturn('205');

        $this->user = Mockery::mock(PFUser::class);

        $this->root_planning = Mockery::mock(Planning::class);
        $this->root_planning->shouldReceive('getId')->andReturn('1');
        $this->root_planning->shouldReceive('getGroupId')->andReturn('101');
    }

    public function testItReturnsNullIfProjectDoesNotUseExplicitBacklog(): void
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->with(101)
            ->andReturnFalse();

        $this->assertNull($this->builder->buildArtifactAction($this->artifact, $this->user));
    }

    public function testItReturnsNullIfProjectDoesNotHaveARootPlanning(): void
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->with(101)
            ->andReturnTrue();
        $this->event_dispatcher->shouldReceive('dispatch');

        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->with($this->user, 101)
            ->andReturnFalse();

        $this->assertNull($this->builder->buildArtifactAction($this->artifact, $this->user));
    }

    public function testItReturnsNullScrumAccessIsBlockedForThisProject(): void
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->with(101)
            ->andReturnTrue();
        $this->event_dispatcher->shouldReceive('dispatch')->andReturnUsing(function (object $event) {
            if ($event instanceof BlockScrumAccess) {
                $event->disableScrumAccess();
            }
            return $event;
        });

        self::assertNull($this->builder->buildArtifactAction($this->artifact, $this->user));
    }

    public function testItReturnsNullIfArtifactNotInBacklogTrackerOfRootPlanning(): void
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->with(101)
            ->andReturnTrue();
        $this->event_dispatcher->shouldReceive('dispatch');

        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->with($this->user, 101)
            ->andReturn($this->root_planning);

        $this->planning_tracker_backlog_checker->shouldReceive('isTrackerBacklogOfProjectPlanning')
            ->once()
            ->andReturnFalse();

        $this->assertNull($this->builder->buildArtifactAction($this->artifact, $this->user));
    }

    public function testItReturnsNullIfUserCannotChangePriorityOnTopLevelPlanning(): void
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->with(101)
            ->andReturnTrue();
        $this->event_dispatcher->shouldReceive('dispatch');

        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->with($this->user, 101)
            ->andReturn($this->root_planning);

        $this->planning_tracker_backlog_checker->shouldReceive('isTrackerBacklogOfProjectPlanning')
            ->once()
            ->andReturnTrue();

        $this->planning_permissions_manager->shouldReceive('userHasPermissionOnPlanning')
            ->once()
            ->with('1', '101', $this->user, 'PLUGIN_AGILEDASHBOARD_PLANNING_PRIORITY_CHANGE')
            ->andReturnFalse();

        $this->assertNull($this->builder->buildArtifactAction($this->artifact, $this->user));
    }

    public function testItReturnsNullIfArtifactIsPlannedInASubMilestone(): void
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->with(101)
            ->andReturnTrue();
        $this->event_dispatcher->shouldReceive('dispatch');

        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->with($this->user, 101)
            ->andReturn($this->root_planning);

        $this->planning_tracker_backlog_checker->shouldReceive('isTrackerBacklogOfProjectPlanning')
            ->once()
            ->andReturnTrue();

        $this->planning_permissions_manager->shouldReceive('userHasPermissionOnPlanning')
            ->once()
            ->with('1', '101', $this->user, 'PLUGIN_AGILEDASHBOARD_PLANNING_PRIORITY_CHANGE')
            ->andReturnTrue();

        $this->planned_artifact_dao->shouldReceive('isArtifactPlannedInAMilestoneOfTheProject')
            ->once()
            ->with(205, 101)
            ->andReturnTrue();

        $this->assertNull($this->builder->buildArtifactAction($this->artifact, $this->user));
    }

    public function testItReturnsTheLinkToAddOrRemoveInTopBacklog(): void
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->with(101)
            ->andReturnTrue();
        $this->event_dispatcher->shouldReceive('dispatch');

        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->with($this->user, 101)
            ->andReturn($this->root_planning);

        $this->planning_tracker_backlog_checker->shouldReceive('isTrackerBacklogOfProjectPlanning')
            ->once()
            ->andReturnTrue();

        $this->planning_permissions_manager->shouldReceive('userHasPermissionOnPlanning')
            ->once()
            ->with('1', '101', $this->user, 'PLUGIN_AGILEDASHBOARD_PLANNING_PRIORITY_CHANGE')
            ->andReturnTrue();

        $this->planned_artifact_dao->shouldReceive('isArtifactPlannedInAMilestoneOfTheProject')
            ->once()
            ->with(205, 101)
            ->andReturnFalse();

        $this->artifacts_explicit_backlog_dao->shouldReceive('isArtifactInTopBacklogOfProject')
            ->once();

        $this->assertInstanceOf(
            AdditionalButtonAction::class,
            $this->builder->buildArtifactAction($this->artifact, $this->user)
        );
    }
}
