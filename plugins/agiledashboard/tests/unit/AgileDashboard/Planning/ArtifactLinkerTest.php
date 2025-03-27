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

namespace Tuleap\AgileDashboard\Planning;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Planning_ArtifactLinker;
use PlanningFactory;
use Tracker;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\RetrieveArtifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactLinkerTest extends TestCase
{
    private Planning_ArtifactLinker $linker;
    private Artifact&MockObject $epic;
    private Artifact&MockObject $theme;
    private Artifact&MockObject $release;
    private Artifact&MockObject $product;
    private Artifact&MockObject $corp;
    private RetrieveArtifact $artifact_factory;
    private PFUser $user;
    private int $group_id   = 666;
    private int $corp_id    = 42;
    private int $product_id = 56;
    private int $release_id = 7777;
    private int $theme_id   = 750;
    private int $epic_id    = 2;

    protected function setUp(): void
    {
        // group
        // `- corporation     -----> theme
        //    `- product      -----> epic
        //       `- release   -----> epic
        //          `- sprint
        // faq
        $group_tracker   = TrackerTestBuilder::aTracker()->build();
        $corp_tracker    = TrackerTestBuilder::aTracker()->build();
        $product_tracker = TrackerTestBuilder::aTracker()->build();
        $release_tracker = TrackerTestBuilder::aTracker()->build();
        $epic_tracker    = TrackerTestBuilder::aTracker()->build();
        $theme_tracker   = TrackerTestBuilder::aTracker()->build();

        $corp_planning    = PlanningBuilder::aPlanning(101)->withBacklogTrackers($theme_tracker)->build();
        $product_planning = PlanningBuilder::aPlanning(101)->withBacklogTrackers($epic_tracker)->build();
        $release_planning = PlanningBuilder::aPlanning(101)->withBacklogTrackers($epic_tracker)->build();

        $planning_factory = $this->createMock(PlanningFactory::class);
        $planning_factory->method('getPlanningByPlanningTracker')->willReturnCallback(static fn(PFUser $user, Tracker $tracker) => match ($tracker) {
            $corp_tracker    => $corp_planning,
            $product_tracker => $product_planning,
            $release_tracker => $release_planning,
            default          => null,
        });

        $this->user = new PFUser(['language_id' => 'en']);

        $this->artifact_factory = RetrieveArtifactStub::withArtifactAndOrderedById();

        $group         = $this->getArtifact($this->group_id, $group_tracker, []);
        $this->corp    = $this->getArtifact($this->corp_id, $corp_tracker, [$group]);
        $this->product = $this->getArtifact($this->product_id, $product_tracker, [$this->corp, $group]);
        $this->release = $this->getArtifact($this->release_id, $release_tracker, [$this->product, $group]);

        $this->theme = $this->getArtifact($this->theme_id, $theme_tracker, []);
        // Epic has no parent yet because the whole thing is to manage Theme creation after Epic
        $this->epic = $this->getArtifact($this->epic_id, $epic_tracker, []);

        $this->linker = new Planning_ArtifactLinker($this->artifact_factory, $planning_factory);
    }

    private function getArtifact($id, Tracker $tracker, array $ancestors): Artifact&MockObject
    {
        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getId')->willReturn($id);
        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('getAllAncestors')->with($this->user)->willReturn($ancestors);
        $this->artifact_factory->addArtifact($artifact);
        return $artifact;
    }

    public function testItDoesntLinkWhenItWasLinkedToAParent(): void
    {
        $story_id = 5698;
        $story    = $this->createMock(Artifact::class);
        $task     = ArtifactTestBuilder::anArtifact(2)->withAncestors([$story])->build();

        $story->method('getId')->willReturn($story_id);
        $story->expects(self::never())->method('linkArtifact');

        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->withParam('link-artifact-id', $this->release_id)
            ->build();

        $this->linker->linkBacklogWithPlanningItems($request, $task, null);
    }

    public function testItLinksWithAllHierarchyWhenItWasLinkedToAnAssociatedTracker(): void
    {
        $this->epic->expects(self::never())->method('linkArtifact');
        $this->release->expects(self::never())->method('linkArtifact');
        $this->product->expects($this->once())->method('linkArtifact')->with($this->epic_id, $this->user);
        $this->corp->expects(self::never())->method('linkArtifact');

        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->withParam('link-artifact-id', $this->release_id)
            ->build();

        $this->linker->linkBacklogWithPlanningItems($request, $this->epic, null);
    }

    public function testItLinksTheThemeWithCorpWhenCorpIsParentOfProduct(): void
    {
        $this->corp->expects($this->once())->method('linkArtifact')->with($this->theme_id, $this->user);
        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->withParam('child_milestone', $this->product_id)
            ->build();

        $this->linker->linkBacklogWithPlanningItems($request, $this->theme, null);
    }

    public function testItLinksTheEpicWithAllMilestonesParentOfSprintThatCanPlanEpics(): void
    {
        $sprint_tracker = TrackerTestBuilder::aTracker()->build();
        $this->getArtifact(9001, $sprint_tracker, [$this->release, $this->product]);

        $this->product->expects($this->once())->method('linkArtifact')->with($this->epic_id, $this->user);
        $this->release->expects($this->once())->method('linkArtifact')->with($this->epic_id, $this->user);

        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->withParam('child_milestone', 9001)
            ->build();

        $this->linker->linkBacklogWithPlanningItems($request, $this->epic, null);
    }

    public function testItDoesntLinkTheEpicWithCorpPlanningWhenCorpPlanningDoesntManageEpics(): void
    {
        $this->corp->expects(self::never())->method('linkArtifact');
        $this->product->expects($this->once())->method('linkArtifact')->with($this->epic_id, $this->user);
        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->withParam('child_milestone', $this->release_id)
            ->build();
        $this->linker->linkBacklogWithPlanningItems($request, $this->epic, null);
    }

    public function testItReturnsNullWhenNotLinkedToAnyArtifacts(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->build();

        $this->product->method('linkArtifact');

        $latest_milestone_artifact = $this->linker->linkBacklogWithPlanningItems($request, $this->theme, null);
        self::assertNull($latest_milestone_artifact);
    }

    public function testItReturnsTheAlreadyLinkedMilestoneByDefault(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->withParam('link-artifact-id', $this->corp_id)
            ->build();

        $latest_milestone_artifact = $this->linker->linkBacklogWithPlanningItems($request, $this->theme, null);
        self::assertEquals($this->corp, $latest_milestone_artifact);
    }

    public function testItReturnsTheLatestMilestoneThatHasBeenLinkedWithLinkArtifactId(): void
    {
        $this->product->expects($this->once())->method('linkArtifact')->with($this->epic_id, $this->user);

        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->withParam('link-artifact-id', $this->release_id)
            ->build();

        $latest_milestone_artifact = $this->linker->linkBacklogWithPlanningItems($request, $this->epic, null);
        self::assertEquals($this->product, $latest_milestone_artifact);
    }

    public function testItReturnsTheLatestMilestoneThatHasBeenLinkedWithChildMilestone(): void
    {
        $this->product->expects($this->once())->method('linkArtifact')->with($this->epic_id, $this->user);

        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->withParam('child_milestone', $this->release_id)
            ->build();

        $latest_milestone_artifact = $this->linker->linkBacklogWithPlanningItems($request, $this->epic, null);
        self::assertEquals($this->product, $latest_milestone_artifact);
    }

    public function testItLinksToCurrentMilestone(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->withParam('link-to-milestone', 1)
            ->build();

        $this->release->expects($this->once())
            ->method('linkArtifact')
            ->with($this->epic_id, $this->user);

        $this->product->expects($this->once())
            ->method('linkArtifact')
            ->with($this->epic_id, $this->user);

        $this->linker->linkBacklogWithPlanningItems(
            $request,
            $this->epic,
            [
                'pane'        => 'details',
                'planning_id' => 666,
                'aid'         => $this->release_id,
                'action'      => 'show',
            ]
        );
    }
}
