<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../../tracker/include/constants.php';
require_once TRACKER_BASE_DIR.'/../tests/builders/all.php';

class Planning_ArtifactLinkerTest extends TuleapTestCase
{

    protected $faq_id     = 13;
    protected $group_id   = 666;
    protected $corp_id    = 42;
    protected $product_id = 56;
    protected $release_id = 7777;
    protected $sprint_id  = 9001;
    protected $theme_id   = 750;
    protected $epic_id    = 2;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
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

        $corp_planning    = mockery_stub(\Planning::class)->getBacklogTrackers()->returns(array($theme_tracker));
        $product_planning = mockery_stub(\Planning::class)->getBacklogTrackers()->returns(array($epic_tracker));
        $release_planning = mockery_stub(\Planning::class)->getBacklogTrackers()->returns(array($epic_tracker));

        $planning_factory = \Mockery::spy(\PlanningFactory::class);
        stub($planning_factory)->getPlanningByPlanningTracker($corp_tracker)->returns($corp_planning);
        stub($planning_factory)->getPlanningByPlanningTracker($product_tracker)->returns($product_planning);
        stub($planning_factory)->getPlanningByPlanningTracker($release_tracker)->returns($release_planning);

        $this->user   = aUser()->build();

        $this->artifact_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);

        $this->faq     = $this->getArtifact($this->faq_id, $faq_tracker, array());
        $this->group   = $this->getArtifact($this->group_id, $group_tracker, array());
        $this->corp    = $this->getArtifact($this->corp_id, $corp_tracker, array($this->group));
        $this->product = $this->getArtifact($this->product_id, $product_tracker, array($this->corp, $this->group));
        $this->release = $this->getArtifact($this->release_id, $release_tracker, array($this->product, $this->corp, $this->group));
        $this->sprint  = $this->getArtifact($this->sprint_id, $sprint_tracker, array($this->release, $this->product, $this->corp, $this->group));
        $this->theme   = $this->getArtifact($this->theme_id, $theme_tracker, array());
        // Epic has no parent yet because the whole thing is to manage Theme creation after Epic
        $this->epic    = $this->getArtifact($this->epic_id, $epic_tracker, array());

        $this->linker = new Planning_ArtifactLinker($this->artifact_factory, $planning_factory);
    }

    private function getArtifact($id, Tracker $tracker, array $ancestors)
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn($id);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        stub($artifact)->getAllAncestors($this->user)->returns($ancestors);
        stub($this->artifact_factory)->getArtifactById($id)->returns($artifact);
        return $artifact;
    }
}

class Planning_ArtifactLinker_linkWithParentsTest extends Planning_ArtifactLinkerTest
{

    public function itDoesntLinkWhenItWasLinkedToAParent()
    {
        $story_id = 5698;
        $story = Mockery::mock(Tracker_Artifact::class);
        $task = aMockArtifact()->withId(2)->build();
        stub($task)->getAllAncestors($this->user)->returns(array($story));

        $story->shouldReceive('getId')->andReturn($story_id);
        $story->shouldReceive('linkArtifact')->never();

        $this->request = aRequest()->with('link-artifact-id', "$this->release_id")->withUser($this->user)->build();
        $this->linker->linkBacklogWithPlanningItems($this->request, $task);
    }

    public function itLinksWithAllHierarchyWhenItWasLinkedToAnAssociatedTracker()
    {
        $this->epic->shouldReceive('linkArtifact')->never();
        $this->release->shouldReceive('linkArtifact')->never();
        $this->product->shouldReceive('linkArtifact')->with($this->epic_id, $this->user)->once();
        $this->corp->shouldReceive('linkArtifact')->never();

        $this->request = aRequest()->with('link-artifact-id', "$this->release_id")->withUser($this->user)->build();
        $this->linker->linkBacklogWithPlanningItems($this->request, $this->epic);
    }
}

class Planning_ArtifactLinker_LinkWithPlanningTest extends Planning_ArtifactLinkerTest
{

    public function itLinksTheThemeWithCorpWhenCorpIsParentOfProduct()
    {
        $this->corp->shouldReceive('linkArtifact')->with($this->theme_id, $this->user)->once();
        $this->request = aRequest()->with('child_milestone', "$this->product_id")->withUser($this->user)->build();
        $this->linker->linkBacklogWithPlanningItems($this->request, $this->theme);
    }

    public function itLinksTheEpicWithAllMilestonesParentOfSprintThatCanPlanEpics()
    {
        $this->product->shouldReceive('linkArtifact')->with($this->epic_id, $this->user)->once();
        $this->release->shouldReceive('linkArtifact')->with($this->epic_id, $this->user)->once();
        $this->request = aRequest()->with('child_milestone', "$this->sprint_id")->withUser($this->user)->build();
        $this->linker->linkBacklogWithPlanningItems($this->request, $this->epic);
    }

    public function itDoesntLinkTheEpicWithCorpPlanningWhenCorpPlanningDoesntManageEpics()
    {
        $this->corp->shouldReceive('linkArtifact')->never();
        $this->product->shouldReceive('linkArtifact')->with($this->epic_id, $this->user)->once();
        $this->request = aRequest()->with('child_milestone', "$this->release_id")->withUser($this->user)->build();
        $this->linker->linkBacklogWithPlanningItems($this->request, $this->epic);
    }
}

class Planning_ArtifactLinker_linkBacklogWithPlanningItemsTest extends Planning_ArtifactLinkerTest
{

    public function itReturnsNullWhenNotLinkedToAnyArtifacts()
    {
        $this->request = aRequest()->withUser($this->user)->build();

        $latest_milestone_artifact = $this->linker->linkBacklogWithPlanningItems($this->request, $this->theme);
        $this->assertEqual(null, $latest_milestone_artifact);
    }

    public function itReturnsTheAlreadyLinkedMilestoneByDefault()
    {
        $this->request = aRequest()->with('link-artifact-id', "$this->corp_id")->withUser($this->user)->build();

        $latest_milestone_artifact = $this->linker->linkBacklogWithPlanningItems($this->request, $this->theme);
        $this->assertEqual($this->corp, $latest_milestone_artifact);
    }

    public function itReturnsTheLatestMilestoneThatHasBeenLinkedWithLinkArtifactId()
    {
        $this->product->shouldReceive('linkArtifact')->with($this->epic_id, $this->user)->once();

        $this->request = aRequest()->with('link-artifact-id', "$this->release_id")->withUser($this->user)->build();

        $latest_milestone_artifact = $this->linker->linkBacklogWithPlanningItems($this->request, $this->epic);
        $this->assertEqual($this->product, $latest_milestone_artifact);
    }

    public function itReturnsTheLatestMilestoneThatHasBeenLinkedWithChildMilestone()
    {
        $this->product->shouldReceive('linkArtifact')->with($this->epic_id, $this->user)->once();

        $this->request = aRequest()->with('child_milestone', "$this->release_id")->withUser($this->user)->build();

        $latest_milestone_artifact = $this->linker->linkBacklogWithPlanningItems($this->request, $this->epic);
        $this->assertEqual($this->product, $latest_milestone_artifact);
    }
}
