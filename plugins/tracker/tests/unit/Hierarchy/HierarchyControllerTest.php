<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Hierarchy;

use Codendi_Request;
use EventManager;
use Mockery;
use Project;
use Tracker;
use Tracker_Hierarchy_HierarchicalTracker;
use Tracker_Hierarchy_HierarchicalTrackerFactory;
use Tracker_Workflow_Trigger_RulesDao;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class HierarchyControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalResponseMock;

    public function testTrackersImplicatedInTriggersRulesCanNotBeRemovedFromTheChild(): void
    {
        $request                  = Mockery::mock(Codendi_Request::class);
        $hierarchical_tracker     = Mockery::mock(Tracker_Hierarchy_HierarchicalTracker::class);
        $hierarchy_dao            = Mockery::mock(HierarchyDAO::class);
        $trigger_rules_dao        = Mockery::mock(Tracker_Workflow_Trigger_RulesDao::class);
        $artifact_links_usage_dao = Mockery::mock(ArtifactLinksUsageDao::class);
        $event_manager            = $this->createMock(EventManager::class);
        $history_dao              = $this->createMock(\ProjectHistoryDao::class);
        $controller               = new HierarchyController(
            $request,
            $hierarchical_tracker,
            Mockery::mock(Tracker_Hierarchy_HierarchicalTrackerFactory::class),
            $hierarchy_dao,
            $trigger_rules_dao,
            $artifact_links_usage_dao,
            $event_manager,
            $history_dao
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn(101);
        $hierarchical_tracker->shouldReceive('getProject')->andReturn($project);
        $hierarchical_tracker->shouldReceive('getUnhierarchizedTracker')->andReturn(
            TrackerTestBuilder::aTracker()->withProject(
                new Project(['group_id' => 101])
            )->build()
        );

        $request->shouldReceive('validArray')->andReturn(true);
        $request->shouldReceive('get')->andReturn(['147']);
        $request->shouldReceive('getCurrentUser')->andReturn(UserTestBuilder::aUser()->build());

        $hierarchical_tracker->shouldReceive('getId')->andReturn(789);

        $trigger_rules_dao->shouldReceive('searchTriggeringTrackersByTargetTrackerID')
            ->andReturn([['tracker_id' => 258]]);
        $child_tracker = Mockery::mock(Tracker::class);
        $child_tracker->shouldReceive('getId')->andReturn(258);
        $hierarchical_tracker->shouldReceive('getChildren')->andReturn([$child_tracker]);

        $artifact_links_usage_dao->shouldReceive('isProjectUsingArtifactLinkTypes')
            ->once()
            ->andReturnFalse();

        $hierarchy_dao->shouldNotReceive('changeTrackerHierarchy');
        $hierarchy_dao->shouldReceive('updateChildren')->with(
            789,
            Mockery::on(static function (array $children): bool {
                return count($children) === 2;
            })
        );

        $history_dao->expects(self::once())->method('addHistory');

        $event_manager->method('dispatch')->willReturn(
            new TrackerHierarchyUpdateEvent(
                $hierarchical_tracker->getUnhierarchizedTracker(),
                ['147'],
            )
        );

        $controller->update();
    }

    public function testItDoesRemoveExistingIsChildLinksIfProjectUsesArtifactLinksType(): void
    {
        $request                  = Mockery::mock(Codendi_Request::class);
        $hierarchical_tracker     = Mockery::mock(Tracker_Hierarchy_HierarchicalTracker::class);
        $hierarchy_dao            = Mockery::mock(HierarchyDAO::class);
        $trigger_rules_dao        = Mockery::mock(Tracker_Workflow_Trigger_RulesDao::class);
        $artifact_links_usage_dao = Mockery::mock(ArtifactLinksUsageDao::class);
        $event_manager            = $this->createMock(EventManager::class);
        $history_dao              = $this->createMock(\ProjectHistoryDao::class);
        $controller               = new HierarchyController(
            $request,
            $hierarchical_tracker,
            Mockery::mock(Tracker_Hierarchy_HierarchicalTrackerFactory::class),
            $hierarchy_dao,
            $trigger_rules_dao,
            $artifact_links_usage_dao,
            $event_manager,
            $history_dao
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn(101);
        $hierarchical_tracker->shouldReceive('getProject')->andReturn($project);
        $hierarchical_tracker->shouldReceive('getUnhierarchizedTracker')->andReturn(
            TrackerTestBuilder::aTracker()->withProject(
                new Project(['group_id' => 101])
            )->build()
        );

        $request->shouldReceive('validArray')->andReturn(true);
        $request->shouldReceive('get')->andReturn(['147']);
        $request->shouldReceive('getCurrentUser')->andReturn(UserTestBuilder::aUser()->build());

        $hierarchical_tracker->shouldReceive('getId')->andReturn(789);
        $hierarchical_tracker->shouldReceive('getChildren')->andReturn([]);

        $trigger_rules_dao->shouldReceive('searchTriggeringTrackersByTargetTrackerID')
            ->andReturn([]);

        $artifact_links_usage_dao->shouldReceive('isProjectUsingArtifactLinkTypes')
            ->once()
            ->andReturntrue();

        $hierarchy_dao->shouldReceive('changeTrackerHierarchy')->once();
        $hierarchy_dao->shouldNotReceive('updateChildren');

        $history_dao->expects(self::once())->method('addHistory');

        $event_manager->method('dispatch')->willReturn(
            new TrackerHierarchyUpdateEvent(
                $hierarchical_tracker->getUnhierarchizedTracker(),
                ['147'],
            )
        );

        $controller->update();
    }

    public function testItDoesUpdateHierarchyIfItIsNotPossible(): void
    {
        $project              = ProjectTestBuilder::aProject()->build();
        $hierarchical_tracker = new Tracker_Hierarchy_HierarchicalTracker(
            TrackerTestBuilder::aTracker()->withId(789)->withProject($project)->build(),
            [],
        );

        $request                  = $this->createMock(Codendi_Request::class);
        $hierarchy_dao            = $this->createMock(HierarchyDAO::class);
        $trigger_rules_dao        = $this->createMock(Tracker_Workflow_Trigger_RulesDao::class);
        $artifact_links_usage_dao = $this->createMock(ArtifactLinksUsageDao::class);
        $event_manager            = $this->createMock(EventManager::class);
        $history_dao              = $this->createMock(\ProjectHistoryDao::class);

        $controller = new HierarchyController(
            $request,
            $hierarchical_tracker,
            $this->createMock(Tracker_Hierarchy_HierarchicalTrackerFactory::class),
            $hierarchy_dao,
            $trigger_rules_dao,
            $artifact_links_usage_dao,
            $event_manager,
            $history_dao
        );

        $request->method('validArray')->willReturn(true);
        $request->method('get')->willReturn(['147']);

        $trigger_rules_dao->expects(self::never())->method('searchTriggeringTrackersByTargetTrackerID')->willReturn([]);
        $artifact_links_usage_dao->expects(self::never())->method('isProjectUsingArtifactLinkTypes');
        $hierarchy_dao->expects(self::never())->method('changeTrackerHierarchy');
        $hierarchy_dao->expects(self::never())->method('updateChildren');

        $event = new TrackerHierarchyUpdateEvent(
            $hierarchical_tracker->getUnhierarchizedTracker(),
            ['147'],
        );
        $event->setHierarchyCannotBeUpdated();
        $event->setErrorMessage('You cannot.');
        $event_manager->method('dispatch')->willReturn($event);

        $controller->update();
    }
}
