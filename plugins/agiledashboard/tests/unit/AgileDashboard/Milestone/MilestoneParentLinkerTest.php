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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Milestone;

use AgileDashboard_Milestone_Backlog_Backlog;
use AgileDashboard_Milestone_Backlog_BacklogFactory;
use MilestoneParentLinker;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Planning_ArtifactMilestone;
use Planning_Milestone;
use Planning_MilestoneFactory;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Artifact\Link\ArtifactLinker;
use Tuleap\Tracker\Artifact\Link\ForwardLinkProxy;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MilestoneParentLinkerTest extends TestCase
{
    private MilestoneParentLinker $milestone_parent_linker;
    private Planning_Milestone&MockObject $milestone;
    private ArtifactLinker&MockObject $artifact_linker;
    private PFUser $user;
    private AgileDashboard_Milestone_Backlog_Backlog&MockObject $backlog;

    protected function setUp(): void
    {
        $milestone_factory             = $this->createMock(Planning_MilestoneFactory::class);
        $backlog_factory               = $this->createMock(AgileDashboard_Milestone_Backlog_BacklogFactory::class);
        $this->artifact_linker         = $this->createMock(ArtifactLinker::class);
        $this->milestone_parent_linker = new MilestoneParentLinker(
            $milestone_factory,
            $backlog_factory,
            $this->artifact_linker,
        );

        $this->milestone = $this->createMock(Planning_Milestone::class);
        $this->backlog   = $this->createMock(AgileDashboard_Milestone_Backlog_Backlog::class);
        $this->user      = UserTestBuilder::buildWithDefaults();

        $backlog_factory->method('getBacklog')->willReturn($this->backlog);
        $milestone_factory->method('addMilestoneAncestors');
    }

    public function testItDoesNothingIfTheMilestoneHasNoParent(): void
    {
        $artifact_added            = ArtifactTestBuilder::anArtifact(1)->build();
        $parent_milestone_artifact = $this->createMock(Artifact::class);

        $this->milestone->method('getParent')->willReturn(null);

        $parent_milestone_artifact->expects($this->never())->method('linkArtifact');

        $this->milestone_parent_linker->linkToMilestoneParent(
            $this->milestone,
            $this->user,
            $artifact_added
        );
    }

    public function testItDoesNothingIfTheArtifactTrackerIsNotInParentMilestoneBacklogTrackers(): void
    {
        $artifact_added            = ArtifactTestBuilder::anArtifact(1)
            ->inTracker(TrackerTestBuilder::aTracker()->withId(201)->build())
            ->build();
        $parent_milestone_artifact = $this->createMock(Artifact::class);
        $parent_milestone          = new Planning_ArtifactMilestone(
            ProjectTestBuilder::aProject()->withId(101)->build(),
            PlanningBuilder::aPlanning(101)->build(),
            $parent_milestone_artifact,
        );
        $descendant_tracker        = TrackerTestBuilder::aTracker()->withId(202)->build();

        $this->backlog->method('getDescendantTrackers')->willReturn([$descendant_tracker]);
        $this->milestone->method('getParent')->willReturn($parent_milestone);

        $parent_milestone_artifact->expects($this->never())->method('linkArtifact');

        $this->milestone_parent_linker->linkToMilestoneParent(
            $this->milestone,
            $this->user,
            $artifact_added
        );
    }

    public function testItDoesNothingIfTheParentIsLinkedToParentMilestone(): void
    {
        $parent_linked_artifact    = ArtifactTestBuilder::anArtifact(102)->build();
        $artifact_added            = ArtifactTestBuilder::anArtifact(1)
            ->inTracker(TrackerTestBuilder::aTracker()->withId(201)->build())
            ->withParent($parent_linked_artifact)
            ->build();
        $parent_milestone_artifact = $this->createMock(Artifact::class);
        $parent_milestone          = new Planning_ArtifactMilestone(
            ProjectTestBuilder::aProject()->withId(101)->build(),
            PlanningBuilder::aPlanning(101)->build(),
            $parent_milestone_artifact,
        );
        $descendant_tracker        = TrackerTestBuilder::aTracker()->withId(201)->build();

        $this->backlog->method('getDescendantTrackers')->willReturn([$descendant_tracker]);
        $parent_milestone_artifact->method('getLinkedArtifacts')->with($this->user)->willReturn([$parent_linked_artifact]);
        $this->milestone->method('getParent')->willReturn($parent_milestone);

        $parent_milestone_artifact->expects($this->never())->method('linkArtifact');

        $this->milestone_parent_linker->linkToMilestoneParent(
            $this->milestone,
            $this->user,
            $artifact_added
        );
    }

    public function testItLinksTheItemToParentMilestone(): void
    {
        $descendant_tracker        = TrackerTestBuilder::aTracker()->withId(201)->build();
        $parent                    = ArtifactTestBuilder::anArtifact(103)->build();
        $added_artifact            = ArtifactTestBuilder::anArtifact(101)
            ->inTracker($descendant_tracker)
            ->withParent($parent)
            ->build();
        $parent_milestone_artifact = $this->createMock(Artifact::class);
        $parent_milestone          = new Planning_ArtifactMilestone(
            ProjectTestBuilder::aProject()->withId(101)->build(),
            PlanningBuilder::aPlanning(101)->build(),
            $parent_milestone_artifact,
        );
        $parent_linked_artifact    = ArtifactTestBuilder::anArtifact(102)->build();

        $this->backlog->method('getDescendantTrackers')->willReturn([$descendant_tracker]);

        $parent_milestone_artifact->method('getLinkedArtifacts')->with($this->user)->willReturn([$parent_linked_artifact]);
        $this->milestone->method('getParent')->willReturn($parent_milestone);

        $this->artifact_linker->expects($this->once())->method('linkArtifact')->with($parent_milestone_artifact, new CollectionOfForwardLinks([
            ForwardLinkProxy::buildFromData(101, ArtifactLinkField::NO_TYPE),
        ]), $this->user);

        $this->milestone_parent_linker->linkToMilestoneParent(
            $this->milestone,
            $this->user,
            $added_artifact
        );
    }

    public function testItLinksTheItemToParentMilestoneIfTheItemHasNoParent(): void
    {
        $descendant_tracker        = TrackerTestBuilder::aTracker()->withId(201)->build();
        $added_artifact            = ArtifactTestBuilder::anArtifact(101)
            ->inTracker($descendant_tracker)
            ->withParent(null)
            ->build();
        $parent_milestone_artifact = $this->createMock(Artifact::class);
        $parent_milestone          = new Planning_ArtifactMilestone(
            ProjectTestBuilder::aProject()->withId(101)->build(),
            PlanningBuilder::aPlanning(101)->build(),
            $parent_milestone_artifact,
        );
        $parent_linked_artifact    = ArtifactTestBuilder::anArtifact(102)->build();

        $this->backlog->method('getDescendantTrackers')->willReturn([$descendant_tracker]);

        $parent_milestone_artifact->method('getLinkedArtifacts')->with($this->user)->willReturn([$parent_linked_artifact]);
        $this->milestone->method('getParent')->willReturn($parent_milestone);

        $this->artifact_linker->expects($this->once())->method('linkArtifact')->with($parent_milestone_artifact, new CollectionOfForwardLinks([
            ForwardLinkProxy::buildFromData(101, ArtifactLinkField::NO_TYPE),
        ]), $this->user);

        $this->milestone_parent_linker->linkToMilestoneParent(
            $this->milestone,
            $this->user,
            $added_artifact
        );
    }
}
