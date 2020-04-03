<?php
/**
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
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

use PHPUnit\Framework\TestCase;

final class MilestoneParentLinkerTest extends TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var MilestoneParentLinker
     */
    private $milestone_parent_linker;

    /**
     * @var Planning_Milestone
     */
    private $milestone;

    /**
     * @var PFUser
     */
    private $user;

    /**
     * @var AgileDashboard_Milestone_Backlog_Backlog
     */
    private $backlog;

    protected function setUp(): void
    {
        $milestone_factory       = Mockery::spy(\Planning_MilestoneFactory::class);
        $backlog_factory         = Mockery::spy(\AgileDashboard_Milestone_Backlog_BacklogFactory::class);
        $this->milestone_parent_linker = new MilestoneParentLinker(
            $milestone_factory,
            $backlog_factory
        );

        $this->milestone = Mockery::spy(\Planning_Milestone::class);
        $this->user      = Mockery::spy(\PFUser::class);
        $this->backlog   = Mockery::spy(\AgileDashboard_Milestone_Backlog_Backlog::class);

        $backlog_factory->shouldReceive('getBacklog')->andReturns($this->backlog);
    }

    public function testItDoesNothingIfTheMilestoneHasNoParent(): void
    {
        $artifact_added            = Mockery::mock(Tracker_Artifact::class);
        $parent_milestone_artifact = Mockery::spy(Tracker_Artifact::class);

        $this->milestone->shouldReceive('getParent')->andReturns(null);

        $parent_milestone_artifact->shouldReceive('linkArtifact')->never();

        $this->milestone_parent_linker->linkToMilestoneParent(
            $this->milestone,
            $this->user,
            $artifact_added
        );
    }

    public function testItDoesNothingIfTheArtifactTrackerIsNotInParentMilestoneBacklogTrackers(): void
    {
        $artifact_added            = Mockery::mock(Tracker_Artifact::class);
        $artifact_added->shouldReceive('getTRackerId')->andReturn(201);
        $parent_milestone_artifact = Mockery::spy(Tracker_Artifact::class);
        $parent_milestone          = Mockery::spy(\Planning_Milestone::class)->shouldReceive('getArtifact')->andReturns($parent_milestone_artifact)->getMock();
        $descendant_tracker        = Mockery::mock(Tracker::class);
        $descendant_tracker->shouldReceive('getId')->andReturn(202);

        $this->backlog->shouldReceive('getDescendantTrackers')->andReturns(array($descendant_tracker));
        $this->milestone->shouldReceive('getParent')->andReturns($parent_milestone);

        $parent_milestone_artifact->shouldReceive('linkArtifact')->never();

        $this->milestone_parent_linker->linkToMilestoneParent(
            $this->milestone,
            $this->user,
            $artifact_added
        );
    }

    public function testItDoesNothingIfTheParentIsLinkedToParentMilestone(): void
    {
        $artifact_added            = Mockery::spy(Tracker_Artifact::class)->shouldReceive('getTrackerId')->andReturns(201)->getMock();
        $parent_milestone_artifact = Mockery::spy(Tracker_Artifact::class);
        $parent_milestone          = Mockery::spy(\Planning_Milestone::class)->shouldReceive('getArtifact')->andReturns($parent_milestone_artifact)->getMock();
        $parent_linked_artifact    = Mockery::spy(Tracker_Artifact::class)->shouldReceive('getId')->andReturns(102)->getMock();
        $descendant_tracker        = Mockery::mock(Tracker::class);
        $descendant_tracker->shouldReceive('getId')->andReturn(201);

        $this->backlog->shouldReceive('getDescendantTrackers')->andReturns(array($descendant_tracker));
        $artifact_added->shouldReceive('getParent')->andReturns($parent_linked_artifact);
        $parent_milestone_artifact->shouldReceive('getLinkedArtifacts')->with($this->user)->andReturns(array($parent_linked_artifact));
        $this->milestone->shouldReceive('getParent')->andReturns($parent_milestone);

        $parent_milestone_artifact->shouldReceive('linkArtifact')->never();

        $this->milestone_parent_linker->linkToMilestoneParent(
            $this->milestone,
            $this->user,
            $artifact_added
        );
    }

    public function testItLinksTheItemToParentMilestone(): void
    {
        $added_artifact            = Mockery::spy(Tracker_Artifact::class)->shouldReceive('getTrackerId')->andReturns(201)->getMock();
        $parent_milestone_artifact = Mockery::spy(Tracker_Artifact::class);
        $parent_milestone          = Mockery::spy(\Planning_Milestone::class)->shouldReceive('getArtifact')->andReturns($parent_milestone_artifact)->getMock();
        $parent_linked_artifact    = Mockery::spy(Tracker_Artifact::class)->shouldReceive('getId')->andReturns(102)->getMock();
        $parent                    = Mockery::spy(Tracker_Artifact::class)->shouldReceive('getId')->andReturns(103)->getMock();
        $descendant_tracker        = Mockery::mock(Tracker::class);
        $descendant_tracker->shouldReceive('getId')->andReturn(201);

        $this->backlog->shouldReceive('getDescendantTrackers')->andReturns(array($descendant_tracker));

        $added_artifact->shouldReceive('getId')->andReturns(101);
        $added_artifact->shouldReceive('getParent')->andReturns($parent);
        $parent_milestone_artifact->shouldReceive('getLinkedArtifacts')->with($this->user)->andReturns(array($parent_linked_artifact));
        $this->milestone->shouldReceive('getParent')->andReturns($parent_milestone);

        $parent_milestone_artifact->shouldReceive('linkArtifact')->with(101, $this->user)->once();

        $this->milestone_parent_linker->linkToMilestoneParent(
            $this->milestone,
            $this->user,
            $added_artifact
        );
    }

    public function testItLinksTheItemToParentMilestoneIfTheItemHasNoParent(): void
    {
        $added_artifact            = Mockery::spy(Tracker_Artifact::class)->shouldReceive('getTrackerId')->andReturns(201)->getMock();
        $parent_milestone_artifact = Mockery::spy(Tracker_Artifact::class);
        $parent_milestone          = Mockery::spy(\Planning_Milestone::class)->shouldReceive('getArtifact')->andReturns($parent_milestone_artifact)->getMock();
        $parent_linked_artifact    = Mockery::spy(Tracker_Artifact::class)->shouldReceive('getId')->andReturns(102)->getMock();
        $descendant_tracker        = Mockery::mock(Tracker::class);
        $descendant_tracker->shouldReceive('getId')->andReturn(201);

        $added_artifact->shouldReceive('getId')->andReturns(101);
        $added_artifact->shouldReceive('getParent')->andReturns(null);
        $this->backlog->shouldReceive('getDescendantTrackers')->andReturns(array($descendant_tracker));

        $parent_milestone_artifact->shouldReceive('getLinkedArtifacts')->with($this->user)->andReturns(array($parent_linked_artifact));
        $this->milestone->shouldReceive('getParent')->andReturns($parent_milestone);

        $parent_milestone_artifact->shouldReceive('linkArtifact')->with(101, $this->user)->once();

        $this->milestone_parent_linker->linkToMilestoneParent(
            $this->milestone,
            $this->user,
            $added_artifact
        );
    }
}
