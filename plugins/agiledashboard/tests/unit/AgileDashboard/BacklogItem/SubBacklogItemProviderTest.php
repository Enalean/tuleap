<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\AgileDashboard\BacklogItem;

use AgileDashboard_Milestone_Backlog_BacklogFactory;
use AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Planning;
use Planning_Milestone;
use Planning_VirtualTopMilestone;
use PlanningFactory;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Dao\ArtifactDao;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SubBacklogItemProviderTest extends TestCase
{
    private SubBacklogItemProvider $provider;
    private Tracker $backlog_tracker;
    private Tracker&MockObject $task_tracker;
    private MockObject&Planning_Milestone $milestone;
    private ArtifactDao&MockObject $dao;
    private PFUser&MockObject $user;
    private ExplicitBacklogDao&MockObject $explicit_backlog_dao;
    private MockObject&ArtifactsInExplicitBacklogDao $artifact_in_explicit_backlog_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backlog_tracker = TrackerTestBuilder::aTracker()->withId(35)->build();

        $this->task_tracker = $this->createMock(Tracker::class);
        $this->task_tracker->method('getId')->willReturn(36);
        $this->task_tracker->method('getParent')->willReturn($this->backlog_tracker);

        $sprint_tracker = $this->createMock(Tracker::class);
        $sprint_tracker->method('getId')->willReturn(106);

        $sprint_planning = $this->createMock(Planning::class);
        $sprint_planning->method('getPlanningTrackerId')->willReturn($sprint_tracker->getId());

        $this->milestone = $this->createMock(Planning_Milestone::class);
        $this->milestone->method('getArtifactId')->willReturn(3);
        $this->milestone->method('getPlanning')->willReturn($sprint_planning);

        $this->dao = $this->createMock(ArtifactDao::class);

        $this->user                      = $this->createMock(PFUser::class);
        $backlog_factory                 = $this->createMock(AgileDashboard_Milestone_Backlog_BacklogFactory::class);
        $backlog_item_collection_factory = $this->createMock(AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory::class);
        $planning_factory                = $this->createMock(PlanningFactory::class);

        $this->explicit_backlog_dao             = $this->createMock(ExplicitBacklogDao::class);
        $this->artifact_in_explicit_backlog_dao = $this->createMock(ArtifactsInExplicitBacklogDao::class);

        $this->provider = new SubBacklogItemProvider(
            $this->dao,
            $backlog_factory,
            $backlog_item_collection_factory,
            $planning_factory,
            $this->explicit_backlog_dao,
            $this->artifact_in_explicit_backlog_dao
        );

        $planning_factory->method('getSubPlannings')->willReturn([$sprint_planning]);
        $planning_factory->method('isTrackerIdUsedInAPlanning')
            ->willReturnCallback(static fn(int $id) => match ($id) {
                35, 36   => false,
                105, 106 => true,
            });
    }

    public function testItReturnsTheMatchingIds(): void
    {
        $this->dao->method('getLinkedArtifactsByIds')->with([3], [3])
            ->willReturn([
                ['id' => 7, 'tracker_id' => 35],
                ['id' => 8, 'tracker_id' => 35],
                ['id' => 11, 'tracker_id' => 35],
            ]);
        $this->dao->method('getChildrenForArtifacts')->with([7, 8, 11])->willReturn([]);

        $result = $this->provider->getMatchingIds($this->milestone, $this->backlog_tracker, $this->user);

        self::assertEquals([7, 8, 11], array_keys($result));
    }

    public function testItReturnsTheMatchingIdsInExplicitTopBacklogContext(): void
    {
        $milestone           = $this->createConfiguredMock(
            Planning_VirtualTopMilestone::class,
            ['getArtifactId' => null]
        );
        $top_backlog_tracker = $this->createMock(Tracker::class);

        $project = ProjectTestBuilder::aProject()->build();
        $milestone->method('getProject')->willReturn($project);

        $this->explicit_backlog_dao->method('isProjectUsingExplicitBacklog')->willReturn(true);
        $this->artifact_in_explicit_backlog_dao->method(
            'getAllTopBacklogItemsForProjectSortedByRank'
        )->willReturn(
            [
                ['artifact_id' => 7],
                ['artifact_id' => 8],
                ['artifact_id' => 11],
            ]
        );

        $result = $this->provider->getMatchingIds($milestone, $top_backlog_tracker, $this->user);

        self::assertEquals([7, 8, 11], array_keys($result));
    }

    public function testItReturnsAnEmptyResultIfThereIsNoMatchingId(): void
    {
        $this->dao->method('getLinkedArtifactsByIds')->willReturn([]);

        $result = $this->provider->getMatchingIds($this->milestone, $this->backlog_tracker, $this->user);
        self::assertEquals([], $result);
    }

    public function testItDoesNotFilterFromArtifactsThatAreNotContentOfSubOrCurrentPlanning(): void
    {
        $this->dao->method('getLinkedArtifactsByIds')->with([3], [3])->willReturn([
            ['id' => 7, 'tracker_id' => 35],
            ['id' => 8, 'tracker_id' => 35],
            ['id' => 11, 'tracker_id' => 35],
            ['id' => 158, 'tracker_id' => 105],
        ]);

        $this->dao->method('getChildrenForArtifacts')->with(
            [7, 8, 11]
        )->willReturn([]);

        $result = $this->provider->getMatchingIds($this->milestone, $this->backlog_tracker, $this->user);
        self::assertEquals([7, 8, 11], array_keys($result));
    }

    public function testItFiltersFromArtifactsThatAreChildOfContentOfSubOrCurrentPlanning(): void
    {
        $this->dao->method('getLinkedArtifactsByIds')
            ->willReturnCallback(
                static fn(array $artifact_ids, array $excluded_ids) => match (true) {
                    $artifact_ids === [3] && $excluded_ids === [3]                       => [
                        ['id' => 7, 'tracker_id' => 35],
                        ['id' => 8, 'tracker_id' => 35],
                        ['id' => 11, 'tracker_id' => 35],
                        ['id' => 158, 'tracker_id' => 105],
                        ['id' => 148, 'tracker_id' => 106],
                    ],
                    $artifact_ids === [148] && $excluded_ids === [3, 7, 8, 11, 158, 148] => [],
                }
            );

        $this->dao->method('getChildrenForArtifacts')
            ->willReturnCallback(
                static fn(array $artifact_ids) => match ($artifact_ids) {
                    [7, 8, 11] => [
                        ['id' => 200, 'tracker_id' => 36],
                        ['id' => 201, 'tracker_id' => 36],
                        ['id' => 159, 'tracker_id' => 105],
                    ],
                    [200, 201] => [],
                }
            );

        $result = $this->provider->getMatchingIds($this->milestone, $this->task_tracker, $this->user);
        self::assertEquals([200, 201], array_keys($result));
    }
}
