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

use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class AgileDashboard_BacklogItem_SubBacklogItemProviderTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_ArtifactDao
     */
    private $dao;
    /**
     * @var MileStone|\Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning_Milestone
     */
    private $milestone;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $task_tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $backlog_tracker;
    /**
     * @var AgileDashboard_Milestone_Backlog_BacklogFactory|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $backlog_factory;
    /**
     * @var AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $backlog_item_collection_factory;
    /**
     * @var PFUser
     */
    private $user;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ArtifactsInExplicitBacklogDao
     */
    private $artifact_in_explicit_backlog_dao;

    /**
     * @var AgileDashboard_BacklogItem_SubBacklogItemProvider
     */
    private $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backlog_tracker = Mockery::mock(Tracker::class);
        $this->backlog_tracker->shouldReceive("getId")->andReturn(35);

        $this->task_tracker = Mockery::mock(Tracker::class);
        $this->task_tracker->shouldReceive("getId")->andReturn(36);
        $this->task_tracker->shouldReceive("getParent")->andReturn($this->backlog_tracker);

        $sprint_tracker = Mockery::mock(Tracker::class);
        $sprint_tracker->shouldReceive("getId")->andReturn(106);
        $sprint_tracker->shouldReceive("getPplanning")->andReturn($sprint_tracker);
        $sprint_tracker->shouldReceive("getBacklogTracker")->andReturn($this->backlog_tracker);

        $sprint_planning = Mockery::mock(Planning::class);
        $sprint_planning->shouldReceive('getPlanningTracker')->andReturn($sprint_tracker);
        $sprint_planning->shouldReceive('getBacklogTracker')->andReturn($this->backlog_tracker);

        $this->milestone = Mockery::mock(Planning_Milestone::class);
        $this->milestone->shouldReceive('getArtifactId')->andReturn(3);
        $this->milestone->shouldReceive('getPlanning')->andReturn($sprint_planning);

        $this->dao = \Mockery::spy(\Tracker_ArtifactDao::class);

        $this->user                            = Mockery::mock(PFUser::class);
        $this->backlog_factory                 = \Mockery::spy(\AgileDashboard_Milestone_Backlog_BacklogFactory::class);
        $this->backlog_item_collection_factory = \Mockery::spy(
            \AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory::class
        );
        $this->planning_factory                = \Mockery::spy(PlanningFactory::class);

        $this->explicit_backlog_dao             = Mockery::mock(ExplicitBacklogDao::class);
        $this->artifact_in_explicit_backlog_dao = Mockery::mock(ArtifactsInExplicitBacklogDao::class);

        $this->provider = new AgileDashboard_BacklogItem_SubBacklogItemProvider(
            $this->dao,
            $this->backlog_factory,
            $this->backlog_item_collection_factory,
            $this->planning_factory,
            $this->explicit_backlog_dao,
            $this->artifact_in_explicit_backlog_dao
        );

        $this->planning_factory->shouldReceive('getSubPlannings')->andReturn($sprint_planning);
        $this->planning_factory->shouldReceive('isTrackerIdUsedInAPlanning')->with(35)->andReturn(false);
        $this->planning_factory->shouldReceive('isTrackerIdUsedInAPlanning')->with(36)->andReturn(false);
        $this->planning_factory->shouldReceive('isTrackerIdUsedInAPlanning')->with(105)->andReturn(true);
    }

    public function testItReturnsTheMatchingIds(): void
    {
        $this->dao->shouldReceive('getLinkedArtifactsByIds')->with([3], [3])
            ->andReturns(
                \TestHelper::arrayToDar(
                    ['id' => 7, 'tracker_id' => 35],
                    ['id' => 8, 'tracker_id' => 35],
                    ['id' => 11, 'tracker_id' => 35]
                )
            );
        $this->dao->shouldReceive('getLinkedArtifactsByIds')->with([7, 8, 11], [3, 7, 8, 11])->andReturns(
            \TestHelper::emptyDar()
        );

        $result = $this->provider->getMatchingIds($this->milestone, $this->backlog_tracker, $this->user);

        $this->assertEquals([7, 8, 11], array_keys($result));
    }

    public function testItReturnsTheMatchingIdsInExplicitTopBacklogContext(): void
    {
        $milestone           = Mockery::mock(Planning_VirtualTopMilestone::class)->shouldReceive(
            'getArtifactId'
        )->andReturnNull()->getMock();
        $top_backlog_tracker = Mockery::mock(Tracker::class);

        $project = Mockery::mock(Project::class)->shouldReceive('getID')->andReturn(101)->getMock();
        $milestone->shouldReceive('getProject')->andReturn($project);

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->andReturnTrue();
        $this->artifact_in_explicit_backlog_dao->shouldReceive(
            'getAllTopBacklogItemsForProjectSortedByRank'
        )->andReturn(
            [
                ['artifact_id' => 7],
                ['artifact_id' => 8],
                ['artifact_id' => 11],
            ]
        );

        $result = $this->provider->getMatchingIds($milestone, $top_backlog_tracker, $this->user);

        $this->assertEquals([7, 8, 11], array_keys($result));
    }

    public function testItReturnsAnEmptyResultIfThereIsNoMatchingId(): void
    {
        $this->dao->shouldReceive('getLinkedArtifactsByIds')->andReturns(\TestHelper::emptyDar());

        $result = $this->provider->getMatchingIds($this->milestone, $this->backlog_tracker, $this->user);
        $this->assertEquals([], $result);
    }

    public function testItDoesNotFilterFromArtifactsThatAreNotContentOfSubOrCurrentPlanning(): void
    {
        $this->dao->shouldReceive('getLinkedArtifactsByIds')->with([3], [3])->andReturns(
            \TestHelper::arrayToDar(
                ['id' => 7, 'tracker_id' => 35],
                ['id' => 8, 'tracker_id' => 35],
                ['id' => 11, 'tracker_id' => 35],
                ['id' => 158, 'tracker_id' => 105]
            )
        );

        $this->dao->shouldReceive('getLinkedArtifactsByIds')->with(
            [7, 8, 11],
            [3, 7, 8, 11, 158]
        )->andReturns(\TestHelper::emptyDar());

        $result = $this->provider->getMatchingIds($this->milestone, $this->backlog_tracker, $this->user);
        $this->assertEquals([7, 8, 11], array_keys($result));
    }

    public function testItFiltersFromArtifactsThatAreChildOfContentOfSubOrCurrentPlanning(): void
    {
        $this->dao->shouldReceive('getLinkedArtifactsByIds')->with([3], [3])->andReturns(
            \TestHelper::arrayToDar(
                ['id' => 7, 'tracker_id' => 35],
                ['id' => 8, 'tracker_id' => 35],
                ['id' => 11, 'tracker_id' => 35],
                ['id' => 158, 'tracker_id' => 105]
            )
        );

        $this->dao->shouldReceive('getLinkedArtifactsByIds')->with(
            [7, 8, 11],
            [3, 7, 8, 11, 158]
        )->andReturns(
            \TestHelper::arrayToDar(
                ['id' => 200, 'tracker_id' => 36],
                ['id' => 201, 'tracker_id' => 36],
                ['id' => 159, 'tracker_id' => 105]
            )
        );

        $this->dao->shouldReceive('getLinkedArtifactsByIds')->with(
            [200, 201],
            [3, 7, 8, 11, 158, 200, 201, 159]
        )->andReturns(\TestHelper::emptyDar());

        $result = $this->provider->getMatchingIds($this->milestone, $this->task_tracker, $this->user);
        $this->assertEquals([200, 201], array_keys($result));
    }
}
