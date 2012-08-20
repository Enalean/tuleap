<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__).'/../../include/autoload.php';
require_once dirname(__FILE__).'/../builders/aPlanning.php';
require_once dirname(__FILE__).'/../../include/Planning/ArtifactLinker.class.php';
require_once dirname(__FILE__).'/../../../tracker/include/constants.php';
require_once TRACKER_BASE_DIR.'/../tests/builders/all.php';

class Planning_ArtifactLinkerTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        // corporation     -----> theme
        // `- product      -----> epic
        //    `- release   -----> epic
        $corp_tracker    = aTracker()->build();
        $product_tracker = aTracker()->build();
        $release_tracker = aTracker()->build();
        $epic_tracker    = aTracker()->build();
        $theme_tracker   = aTracker()->build();

        $corp_planning    = stub('Planning')->getBacklogTracker()->returns($theme_tracker);
        $product_planning = stub('Planning')->getBacklogTracker()->returns($epic_tracker);
        $release_planning = stub('Planning')->getBacklogTracker()->returns($epic_tracker);

        $planning_factory = mock('PlanningFactory');
        stub($planning_factory)->getPlanningByPlanningTracker($release_tracker)->returns($release_planning);
        stub($planning_factory)->getPlanningByPlanningTracker($product_tracker)->returns($product_planning);
        stub($planning_factory)->getPlanningByPlanningTracker($corp_tracker)->returns($corp_planning);

        $this->user   = aUser()->build();
        $this->epic_id = 2;
        $this->epic = aMockArtifact()->withId($this->epic_id)->withTracker($epic_tracker)->build();
        stub($this->epic)->getAllAncestors($this->user)->returns(array());

        $this->corp    = aMockArtifact()->withId(42)->withTracker($corp_tracker)->build();

        $this->product = aMockArtifact()->withId(56)->withTracker($product_tracker)->build();
        stub($this->product)->getAllAncestors($this->user)->returns(array($this->corp));

        $this->release_id = 7777;
        $this->release    = aMockArtifact()->withId($this->release_id)->withTracker($release_tracker)->build();
        stub($this->release)->getAllAncestors($this->user)->returns(array($this->product, $this->corp));

        $this->request = aRequest()->with('link-artifact-id', "$this->release_id")->withUser($this->user)->build();
        $this->artifact_factory = mock('Tracker_ArtifactFactory');
        $this->linker = new Planning_ArtifactLinker($this->artifact_factory, $planning_factory);
    }

    public function itDoesntLinkWhenItWasLinkedToAParent() {
        $story_id = 5698;
        $story = aMockArtifact()->withId($story_id)->build();
        $task = aMockArtifact()->withId(2)->build();
        stub($task)->getAllAncestors($this->user)->returns(array($story));

        $story->expectNever('linkArtifact');

        $this->linker->linkWithParents($this->request, $task);
    }

    public function itLinksWithAllHierarchyWhenItWasLinkedToAnAssociatedTracker() {
        $this->epic->expectNever('linkArtifact');
        $this->release->expectNever('linkArtifact');
        $this->product->expectOnce('linkArtifact', array($this->epic_id, $this->user));
        $this->corp->expectNever('linkArtifact');

        stub($this->artifact_factory)->getArtifactById($this->release_id)->returns($this->release);
        $this->linker->linkWithParents($this->request, $this->epic);
    }
}

class Planning_ArtifactLinker_LinkWithPlanningTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->user             = aUser()->build();
        $this->artifact_factory = mock('Tracker_ArtifactFactory');
        $this->planning_factory = mock('PlanningFactory');

        $this->linker = new Planning_ArtifactLinker($this->artifact_factory, $this->planning_factory);

        $this->epic_id      = 2;
        $this->epic_tracker = aTracker()->withId(200)->build();
        $this->epic         = aMockArtifact()->withId($this->epic_id)->withTracker($this->epic_tracker)->build();

        $this->sprint_id    = 32;
        $this->sprint       = aMockArtifact()->withId($this->sprint_id)->build();
        stub($this->artifact_factory)->getArtifactById($this->sprint_id)->returns($this->sprint);

        $this->request = aRequest()->with('child_milestone', "$this->sprint_id")->withUser($this->user)->build();
    }


    public function itLinksTheEpicWithReleaseWhenReleaseIsParentOfSprint() {
        $release_tracker  = aTracker()->withId(41)->build();
        $release_planning = aPlanning()->withPlanningTracker($release_tracker)->withBacklogTracker($this->epic_tracker)->build();
        $release          = aMockArtifact()->withId(31)->withTracker($release_tracker)->build();
        stub($this->planning_factory)->getPlanningByPlanningTracker($release_tracker)->returns($release_planning);

        stub($this->sprint)->getAllAncestors()->returns(array($release));

        $release->expectOnce('linkArtifact', array($this->epic_id, $this->user));

        $this->linker->linkWithPlanning($this->request, $this->epic);
    }

    public function itLinksTheEpicWithMilestonesCorrespondingToStoryPlanning() {
        $product_tracker  = aTracker()->withId(40)->build();
        $product_planning = aPlanning()->withPlanningTracker($product_tracker)->withBacklogTracker($this->epic_tracker)->build();
        $product          = aMockArtifact()->withId(30)->withTracker($product_tracker)->build();
        stub($this->planning_factory)->getPlanningByPlanningTracker($product_tracker)->returns($product_planning);

        $release_tracker  = aTracker()->withId(41)->build();
        $release_planning = aPlanning()->withPlanningTracker($release_tracker)->withBacklogTracker($this->epic_tracker)->build();
        $release          = aMockArtifact()->withId(31)->withTracker($release_tracker)->build();
        stub($this->planning_factory)->getPlanningByPlanningTracker($release_tracker)->returns($release_planning);

        stub($this->sprint)->getAllAncestors()->returns(array($release, $product));

        $product->expectOnce('linkArtifact', array($this->epic_id, $this->user));
        $release->expectOnce('linkArtifact', array($this->epic_id, $this->user));

        $this->linker->linkWithPlanning($this->request, $this->epic);
    }

    public function itDoesntLinkTheEpicWithProductPlanningWhenProductPlanningDoesntManageEpics() {
        $theme_tracker = aTracker()->withId(300)->build();

        $product_tracker  = aTracker()->withId(40)->build();
        $product_planning = aPlanning()->withPlanningTracker($product_tracker)->withBacklogTracker($theme_tracker)->build();
        $product          = aMockArtifact()->withId(30)->withTracker($product_tracker)->build();
        stub($this->planning_factory)->getPlanningByPlanningTracker($product_tracker)->returns($product_planning);

        $release_tracker  = aTracker()->withId(41)->build();
        $release_planning = aPlanning()->withPlanningTracker($release_tracker)->withBacklogTracker($this->epic_tracker)->build();
        $release          = aMockArtifact()->withId(31)->withTracker($release_tracker)->build();
        stub($this->planning_factory)->getPlanningByPlanningTracker($release_tracker)->returns($release_planning);

        stub($this->sprint)->getAllAncestors()->returns(array($release, $product));

        $product->expectNever('linkArtifact');
        $release->expectOnce('linkArtifact', array($this->epic_id, $this->user));

        $this->linker->linkWithPlanning($this->request, $this->epic);
    }
}

?>
