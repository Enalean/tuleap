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
require_once dirname(__FILE__).'/../../../tracker/include/constants.php';
require_once dirname(__FILE__).'/../builders/aPlanning.php';
require_once dirname(__FILE__).'/../../include/Planning/ArtifactLinker.class.php';
require_once TRACKER_BASE_DIR.'/../tests/builders/all.php';

class Planning_ArtifactLinkerTest extends TuleapTestCase {

    protected $faq_id     = 13;
    protected $group_id   = 666;
    protected $corp_id    = 42;
    protected $product_id = 56;
    protected $release_id = 7777;
    protected $sprint_id  = 9001;
    protected $theme_id   = 750;
    protected $epic_id    = 2;

    public function setUp() {
        parent::setUp();
        // group
        // `- corporation     -----> theme
        //    `- product      -----> epic
        //       `- release   -----> epic
        //          `- sprint
        // faq
        $group_tracker   = aTracker()->build();
        $corp_tracker    = aTracker()->build();
        $product_tracker = aTracker()->build();
        $release_tracker = aTracker()->build();
        $sprint_tracker  = aTracker()->build();
        $epic_tracker    = aTracker()->build();
        $theme_tracker   = aTracker()->build();
        $faq_tracker     = aTracker()->build();

        $corp_planning    = stub('Planning')->getBacklogTracker()->returns($theme_tracker);
        $product_planning = stub('Planning')->getBacklogTracker()->returns($epic_tracker);
        $release_planning = stub('Planning')->getBacklogTracker()->returns($epic_tracker);

        $planning_factory = mock('PlanningFactory');
        stub($planning_factory)->getPlanningByPlanningTracker($corp_tracker)->returns($corp_planning);
        stub($planning_factory)->getPlanningByPlanningTracker($product_tracker)->returns($product_planning);
        stub($planning_factory)->getPlanningByPlanningTracker($release_tracker)->returns($release_planning);

        $this->user   = aUser()->build();

        $this->artifact_factory = mock('Tracker_ArtifactFactory');

        $this->faq     = $this->getArtifact($this->faq_id,     $faq_tracker,     array());
        $this->group   = $this->getArtifact($this->group_id,   $group_tracker,   array());
        $this->corp    = $this->getArtifact($this->corp_id,    $corp_tracker,    array($this->group));
        $this->product = $this->getArtifact($this->product_id, $product_tracker, array($this->corp, $this->group));
        $this->release = $this->getArtifact($this->release_id, $release_tracker, array($this->product, $this->corp, $this->group));
        $this->sprint  = $this->getArtifact($this->sprint_id,  $sprint_tracker,  array($this->release, $this->product, $this->corp, $this->group));
        $this->theme   = $this->getArtifact($this->theme_id,   $theme_tracker,   array());
        // Epic has no parent yet because the whole thing is to manage Theme creation after Epic
        $this->epic    = $this->getArtifact($this->epic_id,    $epic_tracker,    array());

        $this->linker = new Planning_ArtifactLinker($this->artifact_factory, $planning_factory);
    }

    private function getArtifact($id, Tracker $tracker, array $ancestors) {
        $artifact = aMockArtifact()->withId($id)->withTracker($tracker)->build();
        stub($artifact)->getAllAncestors($this->user)->returns($ancestors);
        stub($this->artifact_factory)->getArtifactById($id)->returns($artifact);
        return $artifact;
    }
}

class Planning_ArtifactLinker_linkWithParentsTest extends Planning_ArtifactLinkerTest {

    public function itDoesntLinkWhenItWasLinkedToAParent() {
        $story_id = 5698;
        $story = aMockArtifact()->withId($story_id)->build();
        $task = aMockArtifact()->withId(2)->build();
        stub($task)->getAllAncestors($this->user)->returns(array($story));

        $story->expectNever('linkArtifact');

        $this->request = aRequest()->with('link-artifact-id', "$this->release_id")->withUser($this->user)->build();
        $this->linker->linkBacklogWithPlanningItems($this->request, $task);
    }

    public function itLinksWithAllHierarchyWhenItWasLinkedToAnAssociatedTracker() {
        $this->epic->expectNever('linkArtifact');
        $this->release->expectNever('linkArtifact');
        $this->product->expectOnce('linkArtifact', array($this->epic_id, $this->user));
        $this->corp->expectNever('linkArtifact');

        $this->request = aRequest()->with('link-artifact-id', "$this->release_id")->withUser($this->user)->build();
        $this->linker->linkBacklogWithPlanningItems($this->request, $this->epic);
    }
}

class Planning_ArtifactLinker_LinkWithPlanningTest extends Planning_ArtifactLinkerTest {

    public function itLinksTheThemeWithCorpWhenCorpIsParentOfProduct() {
        $this->corp->expectOnce('linkArtifact', array($this->theme_id, $this->user));
        $this->request = aRequest()->with('child_milestone', "$this->product_id")->withUser($this->user)->build();
        $this->linker->linkBacklogWithPlanningItems($this->request, $this->theme);
    }

    public function itLinksTheEpicWithAllMilestonesParentOfSprintThatCanPlanEpics() {
        $this->product->expectOnce('linkArtifact', array($this->epic_id, $this->user));
        $this->release->expectOnce('linkArtifact', array($this->epic_id, $this->user));
        $this->request = aRequest()->with('child_milestone', "$this->sprint_id")->withUser($this->user)->build();
        $this->linker->linkBacklogWithPlanningItems($this->request, $this->epic);
    }

    public function itDoesntLinkTheEpicWithCorpPlanningWhenCorpPlanningDoesntManageEpics() {
        $this->corp->expectNever('linkArtifact');
        $this->product->expectOnce('linkArtifact', array($this->epic_id, $this->user));
        $this->request = aRequest()->with('child_milestone', "$this->release_id")->withUser($this->user)->build();
        $this->linker->linkBacklogWithPlanningItems($this->request, $this->epic);
    }
}

class Planning_ArtifactLinker_linkBacklogWithPlanningItemsTest extends Planning_ArtifactLinkerTest {

    public function itReturnsNullWhenNotLinkedToAnyArtifacts() {
        $this->request = aRequest()->withUser($this->user)->build();

        $latest_milestone_artifact = $this->linker->linkBacklogWithPlanningItems($this->request, $this->theme);
        $this->assertEqual(null, $latest_milestone_artifact);
    }

    public function itReturnsNullWhenNotAPlanningItem() {
        //TODO: handle edge cases later
        /*$this->request = aRequest()->with('link-artifact-id', "$this->faq_id")->withUser($this->user)->build();

        $latest_milestone_artifact = $this->linker->linkBacklogWithPlanningItems($this->request, $this->theme);
        $this->assertEqual(null, $latest_milestone_artifact);*/
    }

    public function itBubblesUpWhenLinkingThemeToSprint() {
        //TODO: handle edge cases later
        /*$this->request = aRequest()->with('link-artifact-id', "$this->sprint_id")->withUser($this->user)->build();

        $latest_milestone_artifact = $this->linker->linkBacklogWithPlanningItems($this->request, $this->theme);
        $this->assertEqual($this->corp, $latest_milestone_artifact);*/
    }

    public function itReturnsTheAlreadyLinkedMilestoneByDefault() {
        $this->request = aRequest()->with('link-artifact-id', "$this->corp_id")->withUser($this->user)->build();

        $latest_milestone_artifact = $this->linker->linkBacklogWithPlanningItems($this->request, $this->theme);
        $this->assertEqual($this->corp, $latest_milestone_artifact);
    }

    public function itReturnsTheLatestMilestoneThatHasBeenLinkedWithLinkArtifactId() {
        $this->request = aRequest()->with('link-artifact-id', "$this->release_id")->withUser($this->user)->build();

        $latest_milestone_artifact = $this->linker->linkBacklogWithPlanningItems($this->request, $this->epic);
        $this->assertEqual($this->product, $latest_milestone_artifact);
    }

    public function itReturnsTheLatestMilestoneThatHasBeenLinkedWithChildMilestone() {
        $this->request = aRequest()->with('child_milestone', "$this->release_id")->withUser($this->user)->build();

        $latest_milestone_artifact = $this->linker->linkBacklogWithPlanningItems($this->request, $this->epic);
        $this->assertEqual($this->product, $latest_milestone_artifact);
    }
}

?>
