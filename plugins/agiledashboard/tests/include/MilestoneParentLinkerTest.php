<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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

require_once dirname(__FILE__).'/../bootstrap.php';

class MilestoneParentLinkerTest extends TuleapTestCase
{

    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;

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
     * @var AgileDashboard_Milestone_Backlog_BacklogFactory
     */
    private $backlog_factory;

    /**
     * @var AgileDashboard_Milestone_Backlog_Backlog
     */
    private $backlog;

    public function setUp()
    {
        parent::setUp();

        $this->milestone_factory       = mock('Planning_MilestoneFactory');
        $this->backlog_factory         = mock('AgileDashboard_Milestone_Backlog_BacklogFactory');
        $this->milestone_parent_linker = new MilestoneParentLinker(
            $this->milestone_factory,
            $this->backlog_factory
        );

        $this->milestone = mock('Planning_Milestone');
        $this->user      = mock('PFUser');
        $this->backlog   = mock('AgileDashboard_Milestone_Backlog_Backlog');

        stub($this->backlog_factory)->getBacklog()->returns($this->backlog);
    }

    public function itDoesNothingIfTheMilestoneHasNoParent()
    {
        $artifact_added            = anArtifact()->withId(101)->build();
        $parent_milestone_artifact = mock('Tracker_Artifact');

        stub($this->milestone)->getParent()->returns(null);

        expect($parent_milestone_artifact)->linkArtifact()->never();

        $this->milestone_parent_linker->linkToMilestoneParent(
            $this->milestone,
            $this->user,
            $artifact_added
        );
    }

    public function itDoesNothingIfTheArtifactTrackerIsNotInParentMilestoneBacklogTrackers()
    {
        $artifact_added            = anArtifact()->withId(101)->withTrackerId(201)->build();
        $parent_milestone_artifact = mock('Tracker_Artifact');
        $parent_milestone          = stub('Planning_Milestone')->getArtifact()->returns($parent_milestone_artifact);
        $descendant_tracker        = aTracker()->withId(202)->build();

        stub($this->backlog)->getDescendantTrackers()->returns(array($descendant_tracker));
        stub($this->milestone)->getParent()->returns($parent_milestone);

        expect($parent_milestone_artifact)->linkArtifact()->never();

        $this->milestone_parent_linker->linkToMilestoneParent(
            $this->milestone,
            $this->user,
            $artifact_added
        );
    }

    public function itDoesNothingIfTheParentIsLinkedToParentMilestone()
    {
        $artifact_added            = stub('Tracker_Artifact')->getTrackerId()->returns(201);
        $parent_milestone_artifact = mock('Tracker_Artifact');
        $parent_milestone          = stub('Planning_Milestone')->getArtifact()->returns($parent_milestone_artifact);
        $parent_linked_artifact    = stub('Tracker_Artifact')->getId()->returns(102);
        $descendant_tracker        = aTracker()->withId(201)->build();

        stub($this->backlog)->getDescendantTrackers()->returns(array($descendant_tracker));
        stub($artifact_added)->getParent()->returns($parent_linked_artifact);
        stub($parent_milestone_artifact)->getLinkedArtifacts($this->user)->returns(array($parent_linked_artifact));
        stub($this->milestone)->getParent()->returns($parent_milestone);

        expect($parent_milestone_artifact)->linkArtifact()->never();

        $this->milestone_parent_linker->linkToMilestoneParent(
            $this->milestone,
            $this->user,
            $artifact_added
        );
    }

    public function itLinksTheItemToParentMilestone()
    {
        $added_artifact            = stub('Tracker_Artifact')->getTrackerId()->returns(201);
        $parent_milestone_artifact = mock('Tracker_Artifact');
        $parent_milestone          = stub('Planning_Milestone')->getArtifact()->returns($parent_milestone_artifact);
        $parent_linked_artifact    = stub('Tracker_Artifact')->getId()->returns(102);
        $parent                    = stub('Tracker_Artifact')->getId()->returns(103);
        $descendant_tracker        = aTracker()->withId(201)->build();

        stub($this->backlog)->getDescendantTrackers()->returns(array($descendant_tracker));

        stub($added_artifact)->getId()->returns(101);
        stub($added_artifact)->getParent()->returns($parent);
        stub($parent_milestone_artifact)->getLinkedArtifacts($this->user)->returns(array($parent_linked_artifact));
        stub($this->milestone)->getParent()->returns($parent_milestone);

        expect($parent_milestone_artifact)->linkArtifact(101, $this->user)->once();

        $this->milestone_parent_linker->linkToMilestoneParent(
            $this->milestone,
            $this->user,
            $added_artifact
        );
    }

    public function itLinksTheItemToParentMilestoneIfTheItemHasNoParent()
    {
        $added_artifact            = stub('Tracker_Artifact')->getTrackerId()->returns(201);
        $parent_milestone_artifact = mock('Tracker_Artifact');
        $parent_milestone          = stub('Planning_Milestone')->getArtifact()->returns($parent_milestone_artifact);
        $parent_linked_artifact    = stub('Tracker_Artifact')->getId()->returns(102);
        $descendant_tracker        = aTracker()->withId(201)->build();

        stub($added_artifact)->getId()->returns(101);
        stub($added_artifact)->getParent()->returns(null);
        stub($this->backlog)->getDescendantTrackers()->returns(array($descendant_tracker));

        stub($parent_milestone_artifact)->getLinkedArtifacts($this->user)->returns(array($parent_linked_artifact));
        stub($this->milestone)->getParent()->returns($parent_milestone);

        expect($parent_milestone_artifact)->linkArtifact(101, $this->user)->once();

        $this->milestone_parent_linker->linkToMilestoneParent(
            $this->milestone,
            $this->user,
            $added_artifact
        );
    }
}
