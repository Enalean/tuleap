<?php
/**
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

require_once __DIR__ . '/../../../../bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder
     */
    private $finder;

    /**
     * @var Tracker
     */
    private $user_story_tracker;

    /**
     * @var Tracker
     */
    private $release_tracker;

    /**
     * @var Tracker
     */
    private $sprint_tracker;

    /**
     * @var Tracker
     */
    private $epic_tracker;

    /**
     * @var Tracker
     */
    private $theme_tracker;

    /**
     * @var Tracker
     */
    private $team_tracker;

    /**
     * @var Tracker
     */
    private $requirement_tracker;

    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    /**
     * @var Tracker_HierarchyFactory
     */
    private $tracker_hierarchy_factory;

    /**
     * @var Planning
     */
    private $sprint_planning;

    /**
     * @var Planning
     */
    private $release_planning;

    /**
     * @var Planning
     */
    private $requirement_planning;

    /**
     * @var Planning_Milestone
     */
    private $release_milestone;
    /**
     * @var Planning_Milestone
     */
    private $sprint_milestone;

    private $user_story_tracker_id  = 1;
    private $release_tracker_id     = 2;
    private $sprint_tracker_id      = 3;
    private $epic_tracker_id        = 4;
    private $theme_tracker_id       = 5;
    private $team_tracker_id        = 6;
    private $requirement_tracker_id = 7;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user_story_tracker  = \Mockery::spy(\Tracker::class)->shouldReceive('getId')->andReturns($this->user_story_tracker_id)->getMock();
        $this->release_tracker     = \Mockery::spy(\Tracker::class)->shouldReceive('getId')->andReturns($this->release_tracker_id)->getMock();
        $this->sprint_tracker      = \Mockery::spy(\Tracker::class)->shouldReceive('getId')->andReturns($this->sprint_tracker_id)->getMock();
        $this->epic_tracker        = \Mockery::spy(\Tracker::class)->shouldReceive('getId')->andReturns($this->epic_tracker_id)->getMock();
        $this->theme_tracker       = \Mockery::spy(\Tracker::class)->shouldReceive('getId')->andReturns($this->theme_tracker_id)->getMock();
        $this->team_tracker        = \Mockery::spy(\Tracker::class)->shouldReceive('getId')->andReturns($this->team_tracker_id)->getMock();
        $this->requirement_tracker = \Mockery::spy(\Tracker::class)->shouldReceive('getId')->andReturns($this->requirement_tracker_id)->getMock();

        $this->sprint_planning      = \Mockery::spy(\Planning::class)->shouldReceive('getId')->andReturns(11)->getMock();
        $this->release_planning     = \Mockery::spy(\Planning::class)->shouldReceive('getId')->andReturns(12)->getMock();
        $this->requirement_planning = \Mockery::spy(\Planning::class)->shouldReceive('getId')->andReturns(13)->getMock();

        $this->release_milestone = \Mockery::spy(\Planning_Milestone::class)->shouldReceive('getTrackerId')->andReturns($this->release_tracker_id)->getMock();
        $this->sprint_milestone  = \Mockery::spy(\Planning_Milestone::class)->shouldReceive('getTrackerId')->andReturns($this->sprint_tracker_id)->getMock();

        $this->release_milestone->shouldReceive('getPlanning')->andReturns($this->release_planning);
        $this->sprint_milestone->shouldReceive('getPlanning')->andReturns($this->sprint_planning);

        $this->tracker_hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        $this->planning_factory          = \Mockery::spy(\PlanningFactory::class);


        $this->finder = new AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder(
            $this->tracker_hierarchy_factory,
            $this->planning_factory,
        );

        $this->project = \Mockery::spy(\Project::class, ['getID' => 101, 'getUserName' => false, 'isPublic' => false]);
    }

    /**
     * user_story  ----> sprint*
     */
    public function testItReturnsNullIfThereIsNoChildTrackerForMultiMilestoneConfiguration(): void
    {
        $this->sprint_planning->shouldReceive('getBacklogTrackers')->andReturns([$this->user_story_tracker]);
        $this->tracker_hierarchy_factory->shouldReceive('getChildren')->with($this->sprint_tracker_id)->andReturns([]);

        $this->sprint_milestone->shouldReceive('getProject')->andReturns($this->project);
        $this->release_milestone->shouldReceive('getProject')->andReturns($this->project);

        $tracker = $this->finder->findFirstSubmilestoneTracker($this->sprint_milestone);

        $this->assertNull($tracker);
    }

    /**
     * user_story  ----> release*
     *              `-->  ` sprint
     */
    public function testItReturnsSprintWhenBothPlanningsHaveSameBacklogTrackerForMultiMilestoneConfiguration(): void
    {
        $this->release_planning->shouldReceive('getBacklogTrackers')->andReturns([$this->user_story_tracker]);
        $this->sprint_planning->shouldReceive('getBacklogTrackers')->andReturns([$this->user_story_tracker]);
        $this->tracker_hierarchy_factory->shouldReceive('getChildren')->with($this->release_tracker_id)->andReturns([$this->sprint_tracker]);
        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($this->sprint_tracker)->andReturns($this->sprint_planning);

        $this->release_milestone->shouldReceive('getProject')->andReturns($this->project);

        $tracker = $this->finder->findFirstSubmilestoneTracker($this->release_milestone);

        $this->assertEquals($this->sprint_tracker, $tracker);
    }

    /**
     * user_story  ----> release*
     *                    ` sprint
     */
    public function testItReturnsNullWhenChildHaveNoPlanningForMultiMilestoneConfiguration(): void
    {
        $this->release_planning->shouldReceive('getBacklogTrackers')->andReturns([$this->user_story_tracker]);
        $this->tracker_hierarchy_factory->shouldReceive('getChildren')->with($this->release_tracker_id)->andReturns([$this->sprint_tracker]);
        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($this->sprint_tracker)->andReturns(null);

        $this->release_milestone->shouldReceive('getProject')->andReturns($this->project);

        $tracker = $this->finder->findFirstSubmilestoneTracker($this->release_milestone);

        $this->assertNull($tracker);
    }

    /**
     * epic          ----> release*
     *  ` user_story  `-->  ` sprint
     */
    public function testItReturnsSprintWhenTheBacklogTrackerIsParentForMultiMilestoneConfiguration(): void
    {
        $this->release_planning->shouldReceive('getBacklogTrackers')->andReturns([$this->epic_tracker]);
        $this->sprint_planning->shouldReceive('getBacklogTrackers')->andReturns([$this->user_story_tracker]);
        $this->tracker_hierarchy_factory->shouldReceive('getChildren')->with($this->release_tracker_id)->andReturns([$this->sprint_tracker]);
        $this->tracker_hierarchy_factory->shouldReceive('getAllParents')->with($this->user_story_tracker)->andReturns([$this->epic_tracker]);
        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($this->sprint_tracker)->andReturns($this->sprint_planning);

        $this->release_milestone->shouldReceive('getProject')->andReturns($this->project);

        $tracker = $this->finder->findFirstSubmilestoneTracker($this->release_milestone);

        $this->assertEquals($this->sprint_tracker, $tracker);
    }

    /**
     * epic  ----> release*
     *              ` requirement <---- team
     *
     * (no hierarchy between epic and team)
     */
    public function testItReturnsNullWhenTheBacklogTrackerIsNotRelatedForMultiMilestoneConfiguration(): void
    {
        $this->release_planning->shouldReceive('getBacklogTrackers')->andReturns([$this->epic_tracker]);
        $this->requirement_planning->shouldReceive('getBacklogTrackers')->andReturns([$this->team_tracker]);
        $this->tracker_hierarchy_factory->shouldReceive('getChildren')->with($this->release_tracker_id)->andReturns([$this->requirement_tracker]);
        $this->tracker_hierarchy_factory->shouldReceive('getAllParents')->with($this->team_tracker)->andReturns([]);
        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($this->requirement_tracker)->andReturns($this->requirement_planning);

        $this->release_milestone->shouldReceive('getProject')->andReturns($this->project);

        $tracker = $this->finder->findFirstSubmilestoneTracker($this->release_milestone);

        $this->assertNull($tracker);
    }

    /**
     * theme            ----> release*
     *  ` epic            ,->  ` sprint
     *     ` user_story -'
      */
    public function testItReturnsSprintWhenTheBacklogTrackerIsAncestorForMultiMilestoneConfiguration(): void
    {
        $this->release_planning->shouldReceive('getBacklogTrackers')->andReturns([$this->theme_tracker]);
        $this->sprint_planning->shouldReceive('getBacklogTrackers')->andReturns([$this->user_story_tracker]);
        $this->tracker_hierarchy_factory->shouldReceive('getChildren')->with($this->release_tracker_id)->andReturns([$this->sprint_tracker]);
        $this->tracker_hierarchy_factory->shouldReceive('getAllParents')->with($this->user_story_tracker)->andReturns([$this->epic_tracker, $this->theme_tracker]);
        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($this->sprint_tracker)->andReturns($this->sprint_planning);

        $this->release_milestone->shouldReceive('getProject')->andReturns($this->project);

        $tracker = $this->finder->findFirstSubmilestoneTracker($this->release_milestone);

        $this->assertEquals($this->sprint_tracker, $tracker);
    }

    /**
     * user story  ----> release*
     *             \       + requirement <---- team
     *              '-->   ` sprint
     *
     * (no hierarchy between epic and team)
     * (requirement and sprint are siblings)
     */
    public function testItReturnsSprintEvenIfThereIsSiblingWithoutMatchingBacklogTrackerForMultiMilestoneConfiguration(): void
    {
        $this->release_planning->shouldReceive('getBacklogTrackers')->andReturns([$this->user_story_tracker]);
        $this->requirement_planning->shouldReceive('getBacklogTrackers')->andReturns([$this->team_tracker]);
        $this->sprint_planning->shouldReceive('getBacklogTrackers')->andReturns([$this->user_story_tracker]);
        $this->tracker_hierarchy_factory->shouldReceive('getChildren')->with($this->release_tracker_id)->andReturns([$this->requirement_tracker, $this->sprint_tracker]);
        $this->tracker_hierarchy_factory->shouldReceive('getAllParents')->with($this->team_tracker)->andReturns([]);
        $this->tracker_hierarchy_factory->shouldReceive('getAllParents')->with($this->user_story_tracker)->andReturns([]);
        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($this->requirement_tracker)->andReturns($this->requirement_planning);
        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($this->sprint_tracker)->andReturns($this->sprint_planning);

        $this->release_milestone->shouldReceive('getProject')->andReturns($this->project);

        $tracker = $this->finder->findFirstSubmilestoneTracker($this->release_milestone);

        $this->assertEquals($this->sprint_tracker, $tracker);
    }
}
