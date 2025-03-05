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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Milestone\Pane\Planning;

use AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder;
use PHPUnit\Framework\MockObject\MockObject;
use Planning;
use Planning_ArtifactMilestone;
use Planning_Milestone;
use PlanningFactory;
use Tracker;
use Tracker_HierarchyFactory;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SubmilestoneFinderTest extends TestCase
{
    private AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder $finder;
    private Tracker $user_story_tracker;
    private Tracker $sprint_tracker;
    private Tracker $epic_tracker;
    private Tracker $theme_tracker;
    private Tracker $team_tracker;
    private Tracker $requirement_tracker;
    private PlanningFactory&MockObject $planning_factory;
    private Tracker_HierarchyFactory&MockObject $tracker_hierarchy_factory;
    private Planning $sprint_planning;
    private Planning&MockObject $release_planning;
    private Planning $requirement_planning;
    private Planning_Milestone $release_milestone;
    private Planning_Milestone $sprint_milestone;
    private int $user_story_tracker_id  = 1;
    private int $release_tracker_id     = 2;
    private int $sprint_tracker_id      = 3;
    private int $epic_tracker_id        = 4;
    private int $theme_tracker_id       = 5;
    private int $team_tracker_id        = 6;
    private int $requirement_tracker_id = 7;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->user_story_tracker  = TrackerTestBuilder::aTracker()->withId($this->user_story_tracker_id)->build();
        $this->sprint_tracker      = TrackerTestBuilder::aTracker()->withId($this->sprint_tracker_id)->build();
        $this->epic_tracker        = TrackerTestBuilder::aTracker()->withId($this->epic_tracker_id)->build();
        $this->theme_tracker       = TrackerTestBuilder::aTracker()->withId($this->theme_tracker_id)->build();
        $this->team_tracker        = TrackerTestBuilder::aTracker()->withId($this->team_tracker_id)->build();
        $this->requirement_tracker = TrackerTestBuilder::aTracker()->withId($this->requirement_tracker_id)->build();
        $release_tracker           = TrackerTestBuilder::aTracker()->withId($this->release_tracker_id)->build();

        $project = ProjectTestBuilder::aProject()->withId(101)->withAccessPrivate()->build();

        $this->sprint_planning      = PlanningBuilder::aPlanning(101)->withId(11)->withBacklogTrackers($this->user_story_tracker)->build();
        $this->requirement_planning = PlanningBuilder::aPlanning(101)->withId(13)->withBacklogTrackers($this->team_tracker)->build();
        $this->release_planning     = $this->createMock(Planning::class);
        $this->release_planning->method('getId')->willReturn(12);

        $this->release_milestone = new Planning_ArtifactMilestone(
            $project,
            $this->release_planning,
            ArtifactTestBuilder::anArtifact(1)->inTracker($release_tracker)->build(),
        );
        $this->sprint_milestone  = new Planning_ArtifactMilestone(
            $project,
            $this->sprint_planning,
            ArtifactTestBuilder::anArtifact(2)->inTracker($this->sprint_tracker)->build(),
        );

        $this->tracker_hierarchy_factory = $this->createMock(Tracker_HierarchyFactory::class);
        $this->planning_factory          = $this->createMock(PlanningFactory::class);

        $this->user = UserTestBuilder::buildWithDefaults();

        $this->finder = new AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder(
            $this->tracker_hierarchy_factory,
            $this->planning_factory,
        );
    }

    /**
     * user_story  ----> sprint*
     */
    public function testItReturnsNullIfThereIsNoChildTrackerForMultiMilestoneConfiguration(): void
    {
        $this->tracker_hierarchy_factory->method('getChildren')->with($this->sprint_tracker_id)->willReturn([]);

        self::assertNull($this->finder->findFirstSubmilestoneTracker($this->user, $this->sprint_milestone));
    }

    /**
     * user_story  ----> release*
     *              `-->  ` sprint
     */
    public function testItReturnsSprintWhenBothPlanningsHaveSameBacklogTrackerForMultiMilestoneConfiguration(): void
    {
        $this->release_planning->method('getBacklogTrackers')->willReturn([$this->user_story_tracker]);
        $this->tracker_hierarchy_factory->method('getChildren')->with($this->release_tracker_id)->willReturn([$this->sprint_tracker]);
        $this->planning_factory->method('getPlanningByPlanningTracker')->with($this->user, $this->sprint_tracker)->willReturn($this->sprint_planning);

        self::assertEquals($this->sprint_tracker, $this->finder->findFirstSubmilestoneTracker($this->user, $this->release_milestone));
    }

    /**
     * user_story  ----> release*
     *                    ` sprint
     */
    public function testItReturnsNullWhenChildHaveNoPlanningForMultiMilestoneConfiguration(): void
    {
        $this->release_planning->method('getBacklogTrackers')->willReturn([$this->user_story_tracker]);
        $this->tracker_hierarchy_factory->method('getChildren')->with($this->release_tracker_id)->willReturn([$this->sprint_tracker]);
        $this->planning_factory->method('getPlanningByPlanningTracker')->with($this->user, $this->sprint_tracker)->willReturn(null);

        self::assertNull($this->finder->findFirstSubmilestoneTracker($this->user, $this->release_milestone));
    }

    /**
     * epic          ----> release*
     *  ` user_story  `-->  ` sprint
     */
    public function testItReturnsSprintWhenTheBacklogTrackerIsParentForMultiMilestoneConfiguration(): void
    {
        $this->release_planning->method('getBacklogTrackers')->willReturn([$this->epic_tracker]);
        $this->tracker_hierarchy_factory->method('getChildren')->with($this->release_tracker_id)->willReturn([$this->sprint_tracker]);
        $this->tracker_hierarchy_factory->method('getAllParents')->with($this->user_story_tracker)->willReturn([$this->epic_tracker]);
        $this->planning_factory->method('getPlanningByPlanningTracker')->with($this->user, $this->sprint_tracker)->willReturn($this->sprint_planning);

        self::assertEquals($this->sprint_tracker, $this->finder->findFirstSubmilestoneTracker($this->user, $this->release_milestone));
    }

    /**
     * epic  ----> release*
     *              ` requirement <---- team
     *
     * (no hierarchy between epic and team)
     */
    public function testItReturnsNullWhenTheBacklogTrackerIsNotRelatedForMultiMilestoneConfiguration(): void
    {
        $this->release_planning->method('getBacklogTrackers')->willReturn([$this->epic_tracker]);
        $this->tracker_hierarchy_factory->method('getChildren')->with($this->release_tracker_id)->willReturn([$this->requirement_tracker]);
        $this->tracker_hierarchy_factory->method('getAllParents')->with($this->team_tracker)->willReturn([]);
        $this->planning_factory->method('getPlanningByPlanningTracker')->with($this->user, $this->requirement_tracker)->willReturn($this->requirement_planning);

        self::assertNull($this->finder->findFirstSubmilestoneTracker($this->user, $this->release_milestone));
    }

    /**
     * theme            ----> release*
     *  ` epic            ,->  ` sprint
     *     ` user_story -'
     */
    public function testItReturnsSprintWhenTheBacklogTrackerIsAncestorForMultiMilestoneConfiguration(): void
    {
        $this->release_planning->method('getBacklogTrackers')->willReturn([$this->theme_tracker]);
        $this->tracker_hierarchy_factory->method('getChildren')->with($this->release_tracker_id)->willReturn([$this->sprint_tracker]);
        $this->tracker_hierarchy_factory->method('getAllParents')->with($this->user_story_tracker)->willReturn([$this->epic_tracker, $this->theme_tracker]);
        $this->planning_factory->method('getPlanningByPlanningTracker')->with($this->user, $this->sprint_tracker)->willReturn($this->sprint_planning);

        self::assertEquals($this->sprint_tracker, $this->finder->findFirstSubmilestoneTracker($this->user, $this->release_milestone));
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
        $this->release_planning->method('getBacklogTrackers')->willReturn([$this->user_story_tracker]);
        $this->tracker_hierarchy_factory->method('getChildren')->with($this->release_tracker_id)->willReturn([$this->requirement_tracker, $this->sprint_tracker]);
        $this->tracker_hierarchy_factory->method('getAllParents')->with($this->team_tracker)->willReturn([]);
        $matcher = $this->exactly(2);
        $this->planning_factory->expects($matcher)->method('getPlanningByPlanningTracker')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($this->user, $parameters[0]);
                self::assertSame($this->requirement_tracker, $parameters[1]);
                return $this->requirement_planning;
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($this->user, $parameters[0]);
                self::assertSame($this->sprint_tracker, $parameters[1]);
                return $this->sprint_planning;
            }
        });

        self::assertEquals($this->sprint_tracker, $this->finder->findFirstSubmilestoneTracker($this->user, $this->release_milestone));
    }
}
