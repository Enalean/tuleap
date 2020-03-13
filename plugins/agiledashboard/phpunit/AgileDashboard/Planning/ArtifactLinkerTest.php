<?php
/**
 * Copyright (c) Enalean, 2012-present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

final class Planning_ArtifactLinkerTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use MockeryPHPUnitIntegration;
    /**
     * @var Planning_ArtifactLinker
     */
    private $linker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $epic;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $theme;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $release;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $product;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $corp;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var PFUser
     */
    private $user;
    private $group_id   = 666;
    private $corp_id    = 42;
    private $product_id = 56;
    private $release_id = 7777;
    private $theme_id   = 750;
    private $epic_id    = 2;

    protected function setUp(): void
    {
        // group
        // `- corporation     -----> theme
        //    `- product      -----> epic
        //       `- release   -----> epic
        //          `- sprint
        // faq
        $group_tracker   = Mockery::mock(Tracker::class);
        $corp_tracker    = Mockery::mock(Tracker::class);
        $product_tracker = Mockery::mock(Tracker::class);
        $release_tracker = Mockery::mock(Tracker::class);
        $epic_tracker    = Mockery::mock(Tracker::class);
        $theme_tracker   = Mockery::mock(Tracker::class);

        $corp_planning = Mockery::mock(Planning::class);
        $corp_planning->shouldReceive('getBacklogTrackers')->andReturns([$theme_tracker]);
        $product_planning = Mockery::mock(Planning::class);
        $product_planning->shouldReceive('getBacklogTrackers')->andReturns([$epic_tracker]);
        $release_planning = Mockery::mock(Planning::class);
        $release_planning->shouldReceive('getBacklogTrackers')->andReturns([$epic_tracker]);

        $planning_factory = \Mockery::spy(\PlanningFactory::class);
        $planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($corp_tracker)->andReturns($corp_planning);
        $planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($product_tracker)->andReturns($product_planning);
        $planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($release_tracker)->andReturns($release_planning);

        $this->user   = new PFUser(['language_id' => 'en']);

        $this->artifact_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);

        $group   = $this->getArtifact($this->group_id, $group_tracker, []);
        $this->corp    = $this->getArtifact($this->corp_id, $corp_tracker, [$group]);
        $this->product = $this->getArtifact($this->product_id, $product_tracker, [$this->corp, $group]);
        $this->release = $this->getArtifact($this->release_id, $release_tracker, [$this->product, $group]);

        $this->theme   = $this->getArtifact($this->theme_id, $theme_tracker, []);
        // Epic has no parent yet because the whole thing is to manage Theme creation after Epic
        $this->epic = $this->getArtifact($this->epic_id, $epic_tracker, []);

        $this->linker = new Planning_ArtifactLinker($this->artifact_factory, $planning_factory);
    }

    private function getArtifact($id, Tracker $tracker, array $ancestors)
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn($id);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $artifact->shouldReceive('getAllAncestors')->with($this->user)->andReturns($ancestors);
        $this->artifact_factory->shouldReceive('getArtifactById')->with($id)->andReturns($artifact);
        return $artifact;
    }

    public function testItDoesntLinkWhenItWasLinkedToAParent(): void
    {
        $story_id = 5698;
        $story    = Mockery::mock(Tracker_Artifact::class);
        $task     = Mockery::mock(Tracker_Artifact::class);

        $task->shouldReceive('getAllAncestors')->with($this->user)->andReturns([$story])->once();

        $story->shouldReceive('getId')->andReturn($story_id);
        $story->shouldReceive('linkArtifact')->never();

        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturn($this->user);
        $request->shouldReceive('exist')->with('link-artifact-id')->andReturn($this->release_id);

        $this->linker->linkBacklogWithPlanningItems($request, $task);
    }

    public function testItLinksWithAllHierarchyWhenItWasLinkedToAnAssociatedTracker(): void
    {
        $this->epic->shouldReceive('linkArtifact')->never();
        $this->release->shouldReceive('linkArtifact')->never();
        $this->product->shouldReceive('linkArtifact')->withArgs([$this->epic_id, $this->user])->once();
        $this->corp->shouldReceive('linkArtifact')->never();

        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturn($this->user);
        $request->shouldReceive('exist')->with('link-artifact-id')->andReturn($this->release_id);
        $request->shouldReceive('getValidated')->withArgs(['link-artifact-id', 'uint', 0])->andReturn(
            $this->release_id
        );

        $this->linker->linkBacklogWithPlanningItems($request, $this->epic);
    }

    public function testItLinksTheThemeWithCorpWhenCorpIsParentOfProduct(): void
    {
        $this->corp->shouldReceive('linkArtifact')->with($this->theme_id, $this->user)->once();
        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturn($this->user);
        $request->shouldReceive('exist')->with('child_milestone')->andReturn($this->product_id);
        $request->shouldReceive('getValidated')->withArgs(['child_milestone', 'uint', 0])->andReturn($this->product_id);
        $request->shouldReceive('exist')->with('link-artifact-id')->andReturnFalse();

        $this->linker->linkBacklogWithPlanningItems($request, $this->theme);
    }

    public function testItLinksTheEpicWithAllMilestonesParentOfSprintThatCanPlanEpics(): void
    {
        $sprint_tracker  = Mockery::mock(Tracker::class);
        $this->getArtifact(9001, $sprint_tracker, [$this->release, $this->product]);

        $this->product->shouldReceive('linkArtifact')->withArgs([$this->epic_id, $this->user])->once();
        $this->release->shouldReceive('linkArtifact')->withArgs([$this->epic_id, $this->user])->once();

        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturn($this->user);
        $request->shouldReceive('exist')->with('child_milestone')->andReturn(9001);
        $request->shouldReceive('getValidated')->withArgs(['child_milestone', 'uint', 0])->andReturn(9001);
        $request->shouldReceive('exist')->with('link-artifact-id')->andReturnFalse();
        $this->linker->linkBacklogWithPlanningItems($request, $this->epic);
    }

    public function testItDoesntLinkTheEpicWithCorpPlanningWhenCorpPlanningDoesntManageEpics(): void
    {
        $this->corp->shouldReceive('linkArtifact')->never();
        $this->product->shouldReceive('linkArtifact')->with($this->epic_id, $this->user)->once();
        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturn($this->user);
        $request->shouldReceive('exist')->with('child_milestone')->andReturn($this->release_id);
        $request->shouldReceive('getValidated')->withArgs(['child_milestone', 'uint', 0])->andReturn($this->release_id);
        $request->shouldReceive('exist')->with('link-artifact-id')->andReturnFalse();
        $this->linker->linkBacklogWithPlanningItems($request, $this->epic);
    }

    public function testItReturnsNullWhenNotLinkedToAnyArtifacts(): void
    {
        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturn($this->user);
        $request->shouldReceive('exist')->with('link-artifact-id')->andReturnFalse();
        $request->shouldReceive('getValidated')->withArgs(['child_milestone', 'uint', 0])->andReturn(null);

        $this->product->shouldReceive('linkArtifact');

        $latest_milestone_artifact = $this->linker->linkBacklogWithPlanningItems($request, $this->theme);
        $this->assertNull($latest_milestone_artifact);
    }

    public function testItReturnsTheAlreadyLinkedMilestoneByDefault(): void
    {
        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturn($this->user);
        $request->shouldReceive('exist')->with('link-artifact-id')->andReturn($this->corp_id);
        $request->shouldReceive('getValidated')->withArgs(['link-artifact-id', 'uint', 0])->andReturn($this->corp_id);

        $latest_milestone_artifact = $this->linker->linkBacklogWithPlanningItems($request, $this->theme);
        $this->assertEquals($this->corp, $latest_milestone_artifact);
    }

    public function testItReturnsTheLatestMilestoneThatHasBeenLinkedWithLinkArtifactId(): void
    {
        $this->product->shouldReceive('linkArtifact')->with($this->epic_id, $this->user)->once();

        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturn($this->user);
        $request->shouldReceive('exist')->with('link-artifact-id')->andReturn($this->release_id);
        $request->shouldReceive('getValidated')->withArgs(['link-artifact-id', 'uint', 0])->andReturn($this->release_id);

        $latest_milestone_artifact = $this->linker->linkBacklogWithPlanningItems($request, $this->epic);
        $this->assertEquals($this->product, $latest_milestone_artifact);
    }

    public function testItReturnsTheLatestMilestoneThatHasBeenLinkedWithChildMilestone(): void
    {
        $this->product->shouldReceive('linkArtifact')->with($this->epic_id, $this->user)->once();

        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturn($this->user);
        $request->shouldReceive('exist')->with('link-artifact-id')->andReturnFalse();
        $request->shouldReceive('exist')->with('child_milestone')->andReturn($this->release_id);
        $request->shouldReceive('getValidated')->withArgs(['child_milestone', 'uint', 0])->andReturn($this->release_id);

        $latest_milestone_artifact = $this->linker->linkBacklogWithPlanningItems($request, $this->epic);
        $this->assertEquals($this->product, $latest_milestone_artifact);
    }
}
