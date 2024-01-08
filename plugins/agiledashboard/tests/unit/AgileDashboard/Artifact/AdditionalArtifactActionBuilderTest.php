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

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Planning;
use PlanningFactory;
use PlanningPermissionsManager;
use Project;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\AgileDashboard\BlockScrumAccess;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\Planning\PlanningTrackerBacklogChecker;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalButtonAction;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class AdditionalArtifactActionBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private AdditionalArtifactActionBuilder $builder;
    private ExplicitBacklogDao|MockObject $explicit_backlog_dao;
    private PlanningFactory|MockObject $planning_factory;
    private PlanningPermissionsManager|MockObject $planning_permissions_manager;
    private MockObject|ArtifactsInExplicitBacklogDao $artifacts_explicit_backlog_dao;
    private PlannedArtifactDao|MockObject $planned_artifact_dao;
    private PlanningTrackerBacklogChecker|MockObject $planning_tracker_backlog_checker;
    private EventDispatcherInterface|MockObject $event_dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->explicit_backlog_dao             = $this->createMock(ExplicitBacklogDao::class);
        $this->planning_factory                 = $this->createMock(PlanningFactory::class);
        $this->planning_permissions_manager     = $this->createMock(PlanningPermissionsManager::class);
        $this->artifacts_explicit_backlog_dao   = $this->createMock(ArtifactsInExplicitBacklogDao::class);
        $this->planned_artifact_dao             = $this->createMock(PlannedArtifactDao::class);
        $this->planning_tracker_backlog_checker = $this->createMock(PlanningTrackerBacklogChecker::class);
        $this->event_dispatcher                 = $this->createMock(EventDispatcherInterface::class);

        $assets = $this->createMock(IncludeAssets::class);
        $assets->method('getFileURL');

        $this->builder = new AdditionalArtifactActionBuilder(
            $this->explicit_backlog_dao,
            $this->planning_factory,
            $this->planning_permissions_manager,
            $this->artifacts_explicit_backlog_dao,
            $this->planned_artifact_dao,
            new JavascriptAsset($assets, 'mock.js'),
            $this->planning_tracker_backlog_checker,
            $this->event_dispatcher,
        );

        $project = $this->createMock(Project::class);
        $project->method('getID')->willReturn('101');

        $tracker = TrackerTestBuilder::aTracker()->withId(148)->withProject($project)->build();

        $this->artifact = $this->createMock(Artifact::class);
        $this->artifact->method('getTracker')->willReturn($tracker);
        $this->artifact->method('getId')->willReturn('205');

        $this->user = $this->createMock(PFUser::class);

        $this->root_planning = $this->createMock(Planning::class);
        $this->root_planning->method('getId')->willReturn('1');
        $this->root_planning->method('getGroupId')->willReturn(101);
    }

    public function testItReturnsNullIfProjectDoesNotUseExplicitBacklog(): void
    {
        $this->explicit_backlog_dao
            ->expects(self::once())
            ->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(false);

        $this->assertNull($this->builder->buildArtifactAction($this->artifact, $this->user));
    }

    public function testItReturnsNullIfProjectDoesNotHaveARootPlanning(): void
    {
        $this->explicit_backlog_dao
            ->expects(self::once())
            ->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);
        $this->event_dispatcher->method('dispatch');

        $this->planning_factory
            ->expects(self::once())
            ->method('getRootPlanning')
            ->with($this->user, 101)
            ->willReturn(false);

        $this->assertNull($this->builder->buildArtifactAction($this->artifact, $this->user));
    }

    public function testItReturnsNullScrumAccessIsBlockedForThisProject(): void
    {
        $this->explicit_backlog_dao
            ->expects(self::once())
            ->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);
        $this->event_dispatcher
            ->method('dispatch')
            ->willReturnCallback(
                function (object $event) {
                    if ($event instanceof BlockScrumAccess) {
                        $event->disableScrumAccess();
                    }
                    return $event;
                }
            );

        self::assertNull($this->builder->buildArtifactAction($this->artifact, $this->user));
    }

    public function testItReturnsNullIfArtifactNotInBacklogTrackerOfRootPlanning(): void
    {
        $this->explicit_backlog_dao
            ->expects(self::once())
            ->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);
        $this->event_dispatcher->method('dispatch');

        $this->planning_factory
            ->expects(self::once())
            ->method('getRootPlanning')
            ->with($this->user, 101)
            ->willReturn($this->root_planning);

        $this->planning_tracker_backlog_checker
            ->expects(self::once())
            ->method('isTrackerBacklogOfProjectPlanning')
            ->willReturn(false);

        $this->assertNull($this->builder->buildArtifactAction($this->artifact, $this->user));
    }

    public function testItReturnsNullIfUserCannotChangePriorityOnTopLevelPlanning(): void
    {
        $this->explicit_backlog_dao
            ->expects(self::once())
            ->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);
        $this->event_dispatcher->method('dispatch');

        $this->planning_factory
            ->expects(self::once())
            ->method('getRootPlanning')
            ->with($this->user, 101)
            ->willReturn($this->root_planning);

        $this->planning_tracker_backlog_checker
            ->expects(self::once())
            ->method('isTrackerBacklogOfProjectPlanning')
            ->willReturn(true);

        $this->planning_permissions_manager
            ->expects(self::once())
            ->method('userHasPermissionOnPlanning')
            ->with('1', '101', $this->user, 'PLUGIN_AGILEDASHBOARD_PLANNING_PRIORITY_CHANGE')
            ->willReturn(false);

        $this->assertNull($this->builder->buildArtifactAction($this->artifact, $this->user));
    }

    public function testItReturnsNullIfArtifactIsPlannedInASubMilestone(): void
    {
        $this->explicit_backlog_dao
            ->expects(self::once())
            ->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);
        $this->event_dispatcher->method('dispatch');

        $this->planning_factory
            ->expects(self::once())
            ->method('getRootPlanning')
            ->with($this->user, 101)
            ->willReturn($this->root_planning);

        $this->planning_tracker_backlog_checker
            ->expects(self::once())
            ->method('isTrackerBacklogOfProjectPlanning')
            ->willReturn(true);

        $this->planning_permissions_manager
            ->expects(self::once())
            ->method('userHasPermissionOnPlanning')
            ->with('1', '101', $this->user, 'PLUGIN_AGILEDASHBOARD_PLANNING_PRIORITY_CHANGE')
            ->willReturn(true);

        $this->planned_artifact_dao
            ->expects(self::once())
            ->method('isArtifactPlannedInAMilestoneOfTheProject')
            ->with(205, 101)
            ->willReturn(true);

        $this->assertNull($this->builder->buildArtifactAction($this->artifact, $this->user));
    }

    public function testItReturnsTheLinkToAddOrRemoveInTopBacklog(): void
    {
        $this->explicit_backlog_dao
            ->expects(self::once())
            ->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);
        $this->event_dispatcher->method('dispatch');

        $this->planning_factory
            ->expects(self::once())
            ->method('getRootPlanning')
            ->with($this->user, 101)
            ->willReturn($this->root_planning);

        $this->planning_tracker_backlog_checker
            ->expects(self::once())
            ->method('isTrackerBacklogOfProjectPlanning')
            ->willReturn(true);

        $this->planning_permissions_manager
            ->expects(self::once())
            ->method('userHasPermissionOnPlanning')
            ->with('1', '101', $this->user, 'PLUGIN_AGILEDASHBOARD_PLANNING_PRIORITY_CHANGE')
            ->willReturn(true);

        $this->planned_artifact_dao
            ->expects(self::once())
            ->method('isArtifactPlannedInAMilestoneOfTheProject')
            ->with(205, 101)
            ->willReturn(false);

        $this->artifacts_explicit_backlog_dao
            ->expects(self::once())
            ->method('isArtifactInTopBacklogOfProject');

        $this->assertInstanceOf(
            AdditionalButtonAction::class,
            $this->builder->buildArtifactAction($this->artifact, $this->user)
        );
    }
}
