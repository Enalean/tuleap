<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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
final class AgileDashboard_Milestone_MilestoneReportCriterionOptionsProviderTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $release_tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $sprint_tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var int
     */
    private $sprint_tracker_id;
    /**
     * @var int
     */
    private $release_tracker_id;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $task_tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_HierarchyFactory
     */
    private $hierarchy_factory;
    /**
     * @var AgileDashboard_Milestone_MilestoneDao|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $dao;
    /**
     * @var AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider
     */
    private $provider;

    /**
     * @var AgileDashboard_Planning_NearestPlanningTrackerProvider|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $nearest_planning_tracker_provider;

    protected function setUp(): void
    {
        /*
        Product          Epic
          Release  ----,-- Story
            Sprint ---'      Task
        */

        $this->user               = Mockery::mock(PFUser::class);
        $this->release_tracker_id = 101;
        $this->sprint_tracker_id  = 1001;

        $this->task_tracker = Mockery::mock(Tracker::class);
        $this->task_tracker->shouldReceive('getGroupID')->andReturn(111);
        $this->sprint_tracker = Mockery::mock(Tracker::class);
        $this->sprint_tracker->shouldReceive('getId')->andReturn($this->sprint_tracker_id);
        $this->sprint_tracker->shouldReceive('getGroupID')->andReturn(111);
        $this->release_tracker = Mockery::mock(Tracker::class);
        $this->release_tracker->shouldReceive('getId')->andReturn($this->release_tracker_id);
        $this->release_tracker->shouldReceive('getGroupID')->andReturn(111);

        $this->planning_factory = \Mockery::spy(\PlanningFactory::class);

        $this->hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        $this->hierarchy_factory->shouldReceive('getAllParents')->with($this->sprint_tracker)->andReturns(
            [$this->release_tracker]
        );

        $this->tracker_factory = \Mockery::spy(\TrackerFactory::class);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(1001)->andReturns($this->sprint_tracker);

        $this->artifact_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);

        $sprint_artifact_1231 = Mockery::mock(\Tracker_Artifact::class);
        $sprint_artifact_1231->shouldReceive('getId')->andReturn(1231);
        $sprint_artifact_1231->shouldReceive('userCanView')->with($this->user)->andReturnFalse();
        $this->artifact_factory->shouldReceive('getArtifactById')->with(1231)->andReturns($sprint_artifact_1231);

        $sprint_artifact_1232 = Mockery::mock(\Tracker_Artifact::class);
        $sprint_artifact_1232->shouldReceive('getId')->andReturn(1232);
        $sprint_artifact_1232->shouldReceive('userCanView')->with($this->user)->andReturnTrue();
        $this->artifact_factory->shouldReceive('getArtifactById')->with(1232)->andReturns($sprint_artifact_1232);

        $sprint_artifact_1241 = Mockery::mock(\Tracker_Artifact::class);
        $sprint_artifact_1241->shouldReceive('getId')->andReturn(1241);
        $sprint_artifact_1241->shouldReceive('userCanView')->with($this->user)->andReturnTrue();
        $this->artifact_factory->shouldReceive('getArtifactById')->with(1241)->andReturns($sprint_artifact_1241);

        $this->dao = \Mockery::spy(\AgileDashboard_Milestone_MilestoneDao::class);


        $this->nearest_planning_tracker_provider = \Mockery::spy(
            \AgileDashboard_Planning_NearestPlanningTrackerProvider::class
        );
        $this->provider                          = new AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider(
            $this->nearest_planning_tracker_provider,
            $this->dao,
            $this->hierarchy_factory,
            $this->planning_factory,
            $this->tracker_factory,
            $this->artifact_factory
        );
    }

    public function testItReturnsEmptyArrayWhenNoNearestPlanningTracker(): void
    {
        $this->nearest_planning_tracker_provider->shouldReceive('getNearestPlanningTracker')
            ->with($this->task_tracker, $this->hierarchy_factory)
            ->andReturns(null);

        $this->dao->shouldReceive('getAllMilestoneByTrackers')->never();

        $this->assertEmpty($this->provider->getSelectboxOptions($this->task_tracker, '*', $this->user));
    }

    public function testItDoesNotSearchOnProductTrackerSinceThereIsNoPlanning(): void
    {
        $this->nearest_planning_tracker_provider->shouldReceive('getNearestPlanningTracker')
            ->with($this->task_tracker, $this->hierarchy_factory)->andReturns($this->sprint_tracker);

        $this->release_tracker->shouldReceive('userCanView')->with($this->user)->andReturns(true);
        $this->sprint_tracker->shouldReceive('userCanView')->with($this->user)->andReturns(true);

        $this->dao->shouldReceive('getAllMilestoneByTrackers')
            ->with([$this->sprint_tracker_id])
            ->andReturns($this->getDarResults());

        $this->provider->getSelectboxOptions($this->task_tracker, '*', $this->user);
    }

    public function testItDoesNotSearchOnMilestonesUserCantView(): void
    {
        $this->nearest_planning_tracker_provider->shouldReceive('getNearestPlanningTracker')
            ->with($this->task_tracker, $this->hierarchy_factory)
            ->andReturns($this->sprint_tracker);

        $this->release_tracker->shouldReceive('userCanView')->with($this->user)->andReturns(false);
        $this->sprint_tracker->shouldReceive('userCanView')->with($this->user)->andReturns(true);

        $this->dao->shouldReceive('getAllMilestoneByTrackers')
            ->once()
            ->andReturns($this->getDarResults());

        $options = $this->provider->getSelectboxOptions($this->task_tracker, '*', $this->user);

        $this->assertNotRegExp('/Sprint 31/', implode('', $options));
    }

    private function getDarResults(): DataAccessResult
    {
        return \TestHelper::arrayToDar(
            [
                'm101_id'     => '123',
                'm101_title'  => 'Tuleap 6.5',
                'm1001_id'    => '1231',
                'm1001_title' => 'Sprint 31',
            ],
            [
                'm101_id'     => '123',
                'm101_title'  => 'Tuleap 6.5',
                'm1001_id'    => '1232',
                'm1001_title' => 'Sprint 32',
            ],
            [
                'm101_id'     => '124',
                'm101_title'  => 'Tuleap 6.6',
                'm1001_id'    => '1241',
                'm1001_title' => 'Sprint 33',
            ]
        );
    }
}
