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


require_once __DIR__.'/../bootstrap.php';
require_once TRACKER_BASE_DIR.'/../tests/builders/all.php';

class ArtifactParentsSelectorTest extends TuleapTestCase
{

    protected $faq_id      = 13;
    protected $corp_id     = 42;
    protected $product_id  = 56;
    protected $product2_id = 57;
    protected $release_id  = 7777;
    protected $release2_id = 7778;
    protected $sprint_id   = 9001;
    protected $theme_id    = 750;
    protected $theme2_id   = 751;
    protected $epic_id     = 2;
    protected $epic2_id    = 3;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        '┝ corporation    ──────≫ theme
         │ ┝ product      ──┬───≫  ┕ epic
         │ │  ┕ release   ──┘   ┌─≫   ┕ story
         │ │     ┕ sprint ──────┘
         │ ┕ product2
         │    ┕ release2
         ┕ faq
        ';
        $this->corp_tracker    = aTracker()->build();
        $this->product_tracker = aTracker()->build();
        $this->release_tracker = aTracker()->build();
        $this->sprint_tracker  = aTracker()->build();
        $this->epic_tracker    = aTracker()->build();
        $this->theme_tracker   = aTracker()->build();
        $this->faq_tracker     = aTracker()->build();
        $this->story_tracker   = aTracker()->build();

        $hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        stub($hierarchy_factory)->getParent($this->product_tracker)->returns($this->corp_tracker);
        stub($hierarchy_factory)->getParent($this->release_tracker)->returns($this->product_tracker);
        stub($hierarchy_factory)->getParent($this->sprint_tracker)->returns($this->release_tracker);
        stub($hierarchy_factory)->getParent($this->epic_tracker)->returns($this->theme_tracker);
        stub($hierarchy_factory)->getParent($this->story_tracker)->returns($this->epic_tracker);

        $corp_planning    = mockery_stub(\Planning::class)->getBacklogTrackers()->returns(array($this->theme_tracker));
        $product_planning = mockery_stub(\Planning::class)->getBacklogTrackers()->returns(array($this->epic_tracker));
        $release_planning = mockery_stub(\Planning::class)->getBacklogTrackers()->returns(array($this->epic_tracker));
        $sprint_planning  = mockery_stub(\Planning::class)->getBacklogTrackers()->returns(array($this->story_tracker));

        $planning_factory = \Mockery::spy(\PlanningFactory::class);
        stub($planning_factory)->getPlanningByPlanningTracker($this->corp_tracker)->returns($corp_planning);
        stub($planning_factory)->getPlanningByPlanningTracker($this->product_tracker)->returns($product_planning);
        stub($planning_factory)->getPlanningByPlanningTracker($this->release_tracker)->returns($release_planning);
        stub($planning_factory)->getPlanningByPlanningTracker($this->sprint_tracker)->returns($sprint_planning);

        $this->user   = aUser()->build();

        $this->artifact_factory  = \Mockery::spy(\Tracker_ArtifactFactory::class);
        $this->milestone_factory = \Mockery::spy(\Planning_MilestoneFactory::class);

        list($this->faq,      $this->faq_milestone)      = $this->getArtifact($this->faq_id, $this->faq_tracker, array());
        list($this->corp,     $this->corp_milestone)     = $this->getArtifact($this->corp_id, $this->corp_tracker, array());
        list($this->product,  $this->product_milestone)  = $this->getArtifact($this->product_id, $this->product_tracker, array($this->corp));
        list($this->product2, $this->product2_milestone) = $this->getArtifact($this->product2_id, $this->product_tracker, array($this->corp));
        list($this->release,  $this->release_milestone)  = $this->getArtifact($this->release_id, $this->release_tracker, array($this->product, $this->corp));
        list($this->release2, $this->release2_milestone) = $this->getArtifact($this->release2_id, $this->release_tracker, array($this->product2, $this->corp));
        list($this->sprint,   $this->sprint_milestone)   = $this->getArtifact($this->sprint_id, $this->sprint_tracker, array($this->release, $this->product, $this->corp));
        list($this->theme,    $this->theme_milestone)    = $this->getArtifact($this->theme_id, $this->theme_tracker, array());
        list($this->theme2,   $this->theme2_milestone)   = $this->getArtifact($this->theme2_id, $this->theme_tracker, array());
        list($this->epic,     $this->epic_milestone)     = $this->getArtifact($this->epic_id, $this->epic_tracker, array($this->theme));
        list($this->epic2,    $this->epic2_milestone)    = $this->getArtifact($this->epic2_id, $this->epic_tracker, array($this->theme));

        stub($this->corp_milestone)->getPlannedArtifacts()->returns(aNode()->withChildren(
            aNode()->withObject($this->product),
            aNode()->withObject($this->product2),
            aNode()->withObject($this->theme),
            aNode()->withObject($this->theme2)
        )->build());
        $this->themes_associated_to_corp = array($this->theme, $this->theme2);
        $this->subreleases_of_corp       = array($this->release, $this->release2);

        stub($this->release_milestone)->getPlannedArtifacts()->returns(aNode()->withChildren(
            aNode()->withObject($this->sprint),
            aNode()->withObject($this->epic),
            aNode()->withObject($this->epic2)
        )->build());
        $this->epics_associated_to_release = array($this->epic, $this->epic2);

        stub($this->corp)->getLinkedArtifactsOfHierarchy()->returns(array($this->product, $this->product2));
        stub($this->product)->getLinkedArtifactsOfHierarchy()->returns(array($this->release));
        stub($this->product2)->getLinkedArtifactsOfHierarchy()->returns(array($this->release2));
        stub($this->release)->getLinkedArtifactsOfHierarchy()->returns(array($this->sprint));
        stub($this->release2)->getLinkedArtifactsOfHierarchy()->returns(array());
        stub($this->theme)->getLinkedArtifactsOfHierarchy()->returns(array($this->epic, $this->epic2));
        stub($this->theme2)->getLinkedArtifactsOfHierarchy()->returns(array());

        $this->selector = new Planning_ArtifactParentsSelector($this->artifact_factory, $planning_factory, $this->milestone_factory, $hierarchy_factory);
    }

    private function getArtifact($id, Tracker $tracker, array $ancestors)
    {
        reset($ancestors);
        $parent = current($ancestors);
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn($id);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $artifact->shouldReceive('getParent')->andReturn($parent);
        $milestone = \Mockery::spy(\Planning_ArtifactMilestone::class);
        stub($artifact)->getAllAncestors($this->user)->returns($ancestors);
        stub($this->artifact_factory)->getArtifactById($id)->returns($artifact);
        stub($this->milestone_factory)->getMilestoneFromArtifactWithPlannedArtifacts($artifact, $this->user)->returns($milestone);
        return array($artifact, $milestone);
    }

    private function assertPossibleParentsEqual(array $expected, Tracker $parent_tracker, Tracker_Artifact $source_artifact)
    {
        $this->assertEqual($expected, $this->selector->getPossibleParents($parent_tracker, $source_artifact, $this->user));
    }

    // nominal cases
    public function itProvidesEpicsAssociatedToTheReleaseOfTheSprintWhenStoryIsLinkedToASprint()
    {
        $this->assertPossibleParentsEqual($this->epics_associated_to_release, $this->epic_tracker, $this->sprint);
    }

    public function itProvidesThemesAssociatedToTheCorpOfTheReleaseOfTheSprintWhenEpicIsLinkedToASprint()
    {
        $this->assertPossibleParentsEqual($this->themes_associated_to_corp, $this->theme_tracker, $this->sprint);
    }

    public function itProvidesThemesAssociatedToACorpWhenEpicIsLinkedToACorp()
    {
        $this->assertPossibleParentsEqual($this->themes_associated_to_corp, $this->theme_tracker, $this->corp);
    }

    public function itProvidesNothingWhenTheReleaseIsLinkedToAFaq()
    {
        $this->assertPossibleParentsEqual(array(), $this->product_tracker, $this->faq);
    }

    // edge cases
    public function itProvidesItselfWhenReleaseIsLinkedToAProduct()
    {
        $this->assertPossibleParentsEqual(array($this->product), $this->product_tracker, $this->product);
    }

    public function itProvidesSubReleasesOfTheCorpWhenSprintIsLinkedToACorp()
    {
        $this->assertPossibleParentsEqual($this->subreleases_of_corp, $this->release_tracker, $this->corp);
    }

    public function itProvidesTheCorpOfTheProductOfTheReleaseWhenProductIsLinkedToARelease()
    {
        $this->assertPossibleParentsEqual(array($this->corp), $this->corp_tracker, $this->release);
    }

    public function itProvidesNothingWhenTheReleaseIsLinkedToAnEpic()
    {
        $this->assertPossibleParentsEqual(array(), $this->product_tracker, $this->epic);
    }
}
