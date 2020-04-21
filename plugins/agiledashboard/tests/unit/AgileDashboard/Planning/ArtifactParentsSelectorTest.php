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


final class ArtifactParentsSelectorTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Planning_ArtifactParentsSelector
     */
    private $selector;
    /**
     * @var array
     */
    private $subreleases_of_corp;
    /**
     * @var array
     */
    private $themes_associated_to_corp;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning_Milestone
     */
    private $faq_milestone;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning_Milestone
     */
    private $product_milestone;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning_Milestone
     */
    private $product2_milestone;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning_Milestone
     */
    private $release2_milestone;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning_Milestone
     */
    private $sprint_milestone;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning_Milestone
     */
    private $theme_milestone;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning_Milestone
     */
    private $theme2_milestone;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning_Milestone
     */
    private $epic_milestone;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning_Milestone
     */
    private $epic2_milestone;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $faq;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $corp;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $product;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $release;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $sprint;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $epic;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning_MilestoneFactory
     */
    private $milestone_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $corp_tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $product_tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $release_tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $epic_tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $theme_tracker;


    private $faq_id = 13;
    private $corp_id = 42;
    private $product_id = 56;
    private $product2_id = 57;
    private $release_id = 7777;
    private $release2_id = 7778;
    private $sprint_id = 9001;
    private $theme_id = 750;
    private $theme2_id = 751;
    private $epic_id = 2;
    private $epic2_id = 3;

    protected function setUp(): void
    {
        parent::setUp();

        '┝ corporation    ──────≫ theme
         │ ┝ product      ──┬───≫  ┕ epic
         │ │  ┕ release   ──┘   ┌─≫   ┕ story
         │ │     ┕ sprint ──────┘
         │ ┕ product2
         │    ┕ release2
         ┕ faq
        ';
        $this->corp_tracker    = Mockery::mock(Tracker::class);
        $this->corp_tracker->shouldReceive('getId')->andReturn(100);
        $this->product_tracker = Mockery::mock(Tracker::class);
        $this->product_tracker->shouldReceive('getId')->andReturn(101);
        $this->release_tracker = Mockery::mock(Tracker::class);
        $this->release_tracker->shouldReceive('getId')->andReturn(102);
        $sprint_tracker  = Mockery::mock(Tracker::class);
        $sprint_tracker->shouldReceive('getId')->andReturn(103);
        $this->epic_tracker    = Mockery::mock(Tracker::class);
        $this->epic_tracker->shouldReceive('getId')->andReturn(104);
        $this->theme_tracker   = Mockery::mock(Tracker::class);
        $this->theme_tracker->shouldReceive('getId')->andReturn(105);
        $faq_tracker     = Mockery::mock(Tracker::class);
        $faq_tracker->shouldReceive('getId')->andReturn(106);
        $story_tracker   = Mockery::mock(Tracker::class);

        $hierarchy_factory = \Mockery::mock(\Tracker_HierarchyFactory::class);
        $hierarchy_factory->shouldReceive('getParent')->with($this->product_tracker)->andReturns($this->corp_tracker);
        $hierarchy_factory->shouldReceive('getParent')->with($this->release_tracker)->andReturns(
            $this->product_tracker
        );
        $hierarchy_factory->shouldReceive('getParent')->with($sprint_tracker)->andReturns($this->release_tracker);
        $hierarchy_factory->shouldReceive('getParent')->with($this->epic_tracker)->andReturns($this->theme_tracker);
        $hierarchy_factory->shouldReceive('getParent')->with($story_tracker)->andReturns($this->epic_tracker);
        $hierarchy_factory->shouldReceive('getParent')->with($faq_tracker)->andReturns($this->theme_tracker);
        $hierarchy_factory->shouldReceive('getParent')->with($this->theme_tracker)->andReturns($this->epic_tracker);
        $hierarchy_factory->shouldReceive('getParent')->with($this->corp_tracker)->andReturns($faq_tracker);

        $corp_planning = Mockery::mock(\Planning::class);
        $corp_planning->shouldReceive('getId')->andReturn(1);
        $corp_planning->shouldReceive('getBacklogTrackers')->andReturns([$this->theme_tracker]);
        $corp_planning->shouldReceive('getBacklogTrackersIds')->andReturns([105]);
        $product_planning = Mockery::mock(\Planning::class);
        $product_planning->shouldReceive('getId')->andReturn(2);
        $product_planning->shouldReceive('getBacklogTrackers')->andReturns([$this->epic_tracker]);
        $product_planning->shouldReceive('getBacklogTrackersIds')->andReturns([104]);
        $release_planning = Mockery::mock(\Planning::class);
        $release_planning->shouldReceive('getId')->andReturn(3);
        $release_planning->shouldReceive('getBacklogTrackers')->andReturns([$this->epic_tracker]);
        $release_planning->shouldReceive('getBacklogTrackersIds')->andReturns([104]);
        $sprint_planning = Mockery::mock(\Planning::class);
        $sprint_planning->shouldReceive('getId')->andReturn(4);
        $sprint_planning->shouldReceive('getBacklogTrackers')->andReturns([$story_tracker]);
        $sprint_planning->shouldReceive('getBacklogTrackersIds')->andReturns([106]);
        $faq_planning = Mockery::mock(\Planning::class);
        $faq_planning->shouldReceive('getId')->andReturn(5);
        $faq_planning->shouldReceive('getBacklogTrackers')->andReturns([$faq_tracker]);
        $faq_planning->shouldReceive('getBacklogTrackersIds')->andReturns([106]);

        $planning_factory = \Mockery::mock(\PlanningFactory::class);
        $planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($this->corp_tracker)->andReturns(
            $corp_planning
        );
        $planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($this->product_tracker)->andReturns(
            $product_planning
        );
        $planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($this->release_tracker)->andReturns(
            $release_planning
        );
        $planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($sprint_tracker)->andReturns(
            $sprint_planning
        );
        $planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($faq_tracker)->andReturns(
            null
        );
        $planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($this->epic)->andReturns(
            null
        );
        $planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($this->theme_tracker)->andReturns(
            null
        );
        $planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($this->epic_tracker)->andReturns(
            null
        );

        $this->user = new PFUser(['language_id' => 'en']);

        $this->artifact_factory  = \Mockery::mock(\Tracker_ArtifactFactory::class);
        $this->milestone_factory = \Mockery::mock(\Planning_MilestoneFactory::class);

        [$this->faq, $this->faq_milestone] = $this->getArtifact($this->faq_id, $faq_tracker, []);
        [$this->corp, $corp_milestone] = $this->getArtifact($this->corp_id, $this->corp_tracker, []);
        [$this->product, $this->product_milestone] = $this->getArtifact(
            $this->product_id,
            $this->product_tracker,
            [$this->corp]
        );
        [$product2, $this->product2_milestone] = $this->getArtifact(
            $this->product2_id,
            $this->product_tracker,
            [$this->corp]
        );
        [$this->release, $release_milestone] = $this->getArtifact(
            $this->release_id,
            $this->release_tracker,
            [$this->product, $this->corp]
        );
        [$release2, $this->release2_milestone] = $this->getArtifact(
            $this->release2_id,
            $this->release_tracker,
            [$product2, $this->corp]
        );
        [$this->sprint, $this->sprint_milestone] = $this->getArtifact(
            $this->sprint_id,
            $sprint_tracker,
            [$this->release, $this->product, $this->corp]
        );
        [$theme, $this->theme_milestone] = $this->getArtifact($this->theme_id, $this->theme_tracker, []);
        [$theme2, $this->theme2_milestone] = $this->getArtifact($this->theme2_id, $this->theme_tracker, []);
        [$this->epic, $this->epic_milestone] = $this->getArtifact($this->epic_id, $this->epic_tracker, [$theme]);
        [$this->epic2, $this->epic2_milestone] = $this->getArtifact(
            $this->epic2_id,
            $this->epic_tracker,
            [$theme]
        );

        $corp_planned_artifact_node = new TreeNode();
        $product_node               = new TreeNode();
        $product_node->setObject($this->product);
        $product2_node = new TreeNode();
        $product2_node->setObject($product2);
        $theme_node = new TreeNode();
        $theme_node->setObject($theme);
        $theme2_node = new TreeNode();
        $theme2_node->setObject($theme2);

        $corp_planned_artifact_node->setChildren(
            [
                $product_node,
                $product2_node,
                $theme_node,
                $theme2_node,
            ]
        );
        $corp_milestone->shouldReceive('getPlannedArtifacts')->andReturns($corp_planned_artifact_node);

        $this->themes_associated_to_corp = [$theme, $theme2];
        $this->subreleases_of_corp       = [$this->release, $release2];

        $release_planned_artifact_node = new TreeNode();
        $sprint_node                   = new TreeNode();
        $sprint_node->setObject($this->sprint);
        $epic_node = new TreeNode();
        $epic_node->setObject($this->epic);
        $epic2_node = new TreeNode();
        $epic2_node->setObject($this->epic2);

        $release_planned_artifact_node->setChildren(
            [
                $sprint_node,
                $epic_node,
                $epic2_node
            ]
        );

        $release_milestone->shouldReceive('getPlannedArtifacts')->andReturns($release_planned_artifact_node);

        $this->corp->shouldReceive('getLinkedArtifactsOfHierarchy')->andReturns([$this->product, $product2]);
        $this->product->shouldReceive('getLinkedArtifactsOfHierarchy')->andReturns([$this->release]);
        $product2->shouldReceive('getLinkedArtifactsOfHierarchy')->andReturns([$release2]);
        $this->release->shouldReceive('getLinkedArtifactsOfHierarchy')->andReturns([$this->sprint]);
        $release2->shouldReceive('getLinkedArtifactsOfHierarchy')->andReturns([]);
        $theme->shouldReceive('getLinkedArtifactsOfHierarchy')->andReturns([$this->epic, $this->epic2]);
        $theme2->shouldReceive('getLinkedArtifactsOfHierarchy')->andReturns([]);
        $this->faq->shouldReceive('getLinkedArtifactsOfHierarchy')->andReturns([]);
        $this->epic->shouldReceive('getLinkedArtifactsOfHierarchy')->andReturns([]);

        $this->selector = new Planning_ArtifactParentsSelector(
            $this->artifact_factory,
            $planning_factory,
            $this->milestone_factory,
            $hierarchy_factory
        );
    }

    private function getArtifact($id, Tracker $tracker, array $ancestors)
    {
        reset($ancestors);
        $parent   = current($ancestors);
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn($id);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $artifact->shouldReceive('getParent')->andReturn($parent);
        $milestone = \Mockery::mock(\Planning_ArtifactMilestone::class);
        $artifact->shouldReceive('getAllAncestors')->with($this->user)->andReturns($ancestors);
        $this->artifact_factory->shouldReceive('getArtifactById')->with($id)->andReturns($artifact);
        $this->milestone_factory->shouldReceive('getMilestoneFromArtifactWithPlannedArtifacts')
            ->with($artifact, $this->user)->andReturns($milestone);

        return [$artifact, $milestone];
    }

    private function assertPossibleParentsEqual(
        array $expected,
        Tracker $parent_tracker,
        Tracker_Artifact $source_artifact
    ) {
        $this->assertEquals(
            $expected,
            $this->selector->getPossibleParents($parent_tracker, $source_artifact, $this->user)
        );
    }

    // nominal cases
    public function testItProvidesEpicsAssociatedToTheReleaseOfTheSprintWhenStoryIsLinkedToASprint(): void
    {
        $this->assertPossibleParentsEqual([$this->epic, $this->epic2], $this->epic_tracker, $this->sprint);
    }

    public function testItProvidesThemesAssociatedToTheCorpOfTheReleaseOfTheSprintWhenEpicIsLinkedToASprint(): void
    {
        $this->assertPossibleParentsEqual($this->themes_associated_to_corp, $this->theme_tracker, $this->sprint);
    }

    public function testItProvidesThemesAssociatedToACorpWhenEpicIsLinkedToACorp(): void
    {
        $this->assertPossibleParentsEqual($this->themes_associated_to_corp, $this->theme_tracker, $this->corp);
    }

    public function testItProvidesNothingWhenTheReleaseIsLinkedToAFaq(): void
    {
        $this->assertPossibleParentsEqual([], $this->release_tracker, $this->faq);
    }

    // edge cases
    public function testItProvidesItselfWhenReleaseIsLinkedToAProduct(): void
    {
        $this->assertPossibleParentsEqual([$this->product], $this->product_tracker, $this->product);
    }

    public function testItProvidesSubReleasesOfTheCorpWhenSprintIsLinkedToACorp(): void
    {
        $this->assertPossibleParentsEqual($this->subreleases_of_corp, $this->release_tracker, $this->corp);
    }

    public function testItProvidesTheCorpOfTheProductOfTheReleaseWhenProductIsLinkedToARelease(): void
    {
        $this->assertPossibleParentsEqual([$this->corp], $this->corp_tracker, $this->release);
    }

    public function testItProvidesNothingWhenTheReleaseIsLinkedToAnEpic(): void
    {
        $this->assertPossibleParentsEqual([], $this->release_tracker, $this->epic);
    }
}
