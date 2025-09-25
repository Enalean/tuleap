<?php
/**
 * Copyright (c) Enalean, 2013 - present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\REST\v1;

use AgileDashboard_Milestone_Backlog_Backlog;
use AgileDashboard_Milestone_Backlog_BacklogFactory;
use AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Planning_ArtifactMilestone;
use Planning_Milestone;
use Planning_MilestoneFactory;
use Planning_VirtualTopMilestone;
use PlanningFactory;
use Tracker_ArtifactFactory;
use Tuleap\AgileDashboard\BacklogItemPresenter;
use Tuleap\AgileDashboard\Milestone\Backlog\BacklogItemCollectionFactory;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Helpers\IdsFromBodyAreNotUniqueException;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MilestoneResourceValidatorTest extends TestCase
{
    private Artifact $artifact1;
    private Artifact $artifact2;
    private Planning_Milestone $milestone;
    private Tracker_ArtifactFactory&MockObject $tracker_artifact_factory;
    private PlanningFactory&MockObject $planning_factory;
    private BacklogItemPresenter $todo_item;
    private BacklogItemPresenter $unplanned_item;
    private AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection $todo_collection;
    private AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection $unplanned_collection;
    /**
     * @var int[]
     */
    private array $ids;
    private MilestoneResourceValidator $milestone_resource_validator;
    private PFUser $user;

    #[\Override]
    protected function setUp(): void
    {
        $this->user = UserTestBuilder::buildWithDefaults();

        $this->ids        = [102, 174];
        $project          = ProjectTestBuilder::aProject()->build();
        $parent_milestone = new Planning_VirtualTopMilestone($project, PlanningBuilder::aPlanning(101)->build());
        $this->milestone  = new Planning_ArtifactMilestone(
            $project,
            PlanningBuilder::aPlanning(101)->withId(3)->build(),
            ArtifactTestBuilder::anArtifact(54)->build(),
        );
        $this->milestone->setAncestors([$parent_milestone]);

        $self_backlog = $this->createMock(AgileDashboard_Milestone_Backlog_Backlog::class);
        $backlog      = $this->createMock(AgileDashboard_Milestone_Backlog_Backlog::class);

        $this->artifact1 = ArtifactTestBuilder::anArtifact(102)
            ->inTracker(TrackerTestBuilder::aTracker()->withId(555)->build())
            ->build();
        $this->artifact2 = ArtifactTestBuilder::anArtifact(174)
            ->inTracker(TrackerTestBuilder::aTracker()->withId(666)->build())
            ->build();

        $this->unplanned_item = new BacklogItemPresenter($this->artifact1, '', false);
        $this->todo_item      = new BacklogItemPresenter($this->artifact2, '', false);

        $this->unplanned_collection     = new AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection();
        $done_collection                = new AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection();
        $this->todo_collection          = new AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection();
        $this->planning_factory         = $this->createMock(PlanningFactory::class);
        $this->tracker_artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
        $backlog_factory                = $this->createMock(AgileDashboard_Milestone_Backlog_BacklogFactory::class);
        $milestone_factory              = $this->createMock(Planning_MilestoneFactory::class);
        $backlog_row_collection_factory = $this->createMock(BacklogItemCollectionFactory::class);

        $backlog_factory->method('getSelfBacklog')->with($this->milestone)->willReturn($self_backlog);
        $backlog_factory->method('getBacklog')->willReturn($backlog);
        $backlog_row_collection_factory->method('getDoneCollection')->willReturn($done_collection);
        $backlog_row_collection_factory->method('getTodoCollection')->willReturn($this->todo_collection);
        $backlog_row_collection_factory->method('getUnplannedOpenCollection')->willReturn($this->unplanned_collection);

        $this->milestone_resource_validator = new MilestoneResourceValidator(
            $this->planning_factory,
            $this->tracker_artifact_factory,
            $backlog_factory,
            $milestone_factory,
            $backlog_row_collection_factory,
        );
    }

    public function testItReturnsTrueIfEverythingIsOk(): void
    {
        $this->unplanned_collection->push($this->unplanned_item);
        $this->todo_collection->push($this->todo_item);

        $this->planning_factory->method('getBacklogTrackersIds')
            ->with($this->milestone->getPlanning()->getId())
            ->willReturn([555, 666]);
        $matcher = $this->exactly(2);

        $this->tracker_artifact_factory->expects($matcher)->method('getArtifactById')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame(102, $parameters[0]);
                return $this->artifact1;
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame(174, $parameters[0]);
                return $this->artifact2;
            }
        });

        $validation = $this->milestone_resource_validator->validateArtifactsFromBodyContent(
            $this->ids,
            $this->milestone,
            $this->user
        );

        self::assertTrue($validation);
    }

    public function testItThrowsAnExceptionIfArtifactIdIsPassedSeveralTime(): void
    {
        $this->expectException(IdsFromBodyAreNotUniqueException::class);

        $ids = [102, 174, 102];

        $this->milestone_resource_validator->validateArtifactsFromBodyContent($ids, $this->milestone, $this->user);
    }

    public function testItThrowsAnExceptionIfArtifactIdDoesNotExist(): void
    {
        $this->expectException(ArtifactDoesNotExistException::class);

        $this->tracker_artifact_factory->method('getArtifactById')->willReturn(null);
        $this->planning_factory->method('getBacklogTrackersIds')
            ->with($this->milestone->getPlanning()->getId())
            ->willReturn([1, 2, 3]);

        $this->milestone_resource_validator->validateArtifactsFromBodyContent(
            $this->ids,
            $this->milestone,
            $this->user
        );
    }

    public function testItThrowsAnExceptionIfArtifactIsNotInBacklogTracker(): void
    {
        $this->expectException(ArtifactIsNotInBacklogTrackerException::class);

        $this->planning_factory->method('getBacklogTrackersIds')
            ->with($this->milestone->getPlanning()->getId())
            ->willReturn([1, 2, 3]);
        $this->tracker_artifact_factory->expects($this->atLeastOnce())->method('getArtifactById')->with(102)->willReturn($this->artifact1);

        $this->milestone_resource_validator->validateArtifactsFromBodyContent(
            $this->ids,
            $this->milestone,
            $this->user
        );
    }

    public function testItThrowsAnExceptionIfArtifactIsClosedOrAlreadyPlannedInAnotherMilestone(): void
    {
        $this->expectException(ArtifactIsClosedOrAlreadyPlannedInAnotherMilestone::class);

        $this->unplanned_collection->push($this->unplanned_item);

        $this->planning_factory->method('getBacklogTrackersIds')
            ->with($this->milestone->getPlanning()->getId())
            ->willReturn([555, 666]);
        $matcher = $this->exactly(2);
        $this->tracker_artifact_factory->expects($matcher)->method('getArtifactById')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame(102, $parameters[0]);
                return $this->artifact1;
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame(174, $parameters[0]);
                return $this->artifact2;
            }
        });

        $this->milestone_resource_validator->validateArtifactsFromBodyContent(
            $this->ids,
            $this->milestone,
            $this->user
        );
    }

    public function testItAllowsToRemoveFromContentWhenRemovedIdsArePartOfLinkedArtifacts(): void
    {
        self::expectNotToPerformAssertions();
        $this->getMockedValidator()->getValidatedArtifactsIdsToAddOrRemoveFromContent(
            $this->user,
            $this->mockMilestoneWithArtifactLinks(),
            [112, 113],
            null
        );
    }

    public function testItForbidsToRemoveFromContentWhenRemovedIdsArePartOfLinkedArtifacts(): void
    {
        $this->expectException(ArtifactIsNotInMilestoneContentException::class);
        $this->getMockedValidator()->getValidatedArtifactsIdsToAddOrRemoveFromContent(
            $this->user,
            $this->mockMilestoneWithArtifactLinks(),
            [566, 113],
            null
        );
    }

    public function testItReturnsTheValidIds(): void
    {
        self::assertEquals(
            [114, 115],
            $this->getMockedValidator()->getValidatedArtifactsIdsToAddOrRemoveFromContent(
                $this->user,
                $this->mockMilestoneWithArtifactLinks(),
                [112, 113],
                null
            )
        );
    }

    public function testItAllowsToAddArtifactsThatAreValidForContent(): void
    {
        $milestone          = $this->mockMilestoneWithArtifactLinks();
        $resource_validator = $this->getMockedValidator();
        $resource_validator->expects($this->once())->method('validateArtifactsFromBodyContentWithClosedItems')
            ->with([210], $milestone, $this->user);

        $resource_validator->getValidatedArtifactsIdsToAddOrRemoveFromContent(
            $this->user,
            $milestone,
            null,
            [210]
        );
    }

    public function testItDoesntAddWhenArrayIsEmpty(): void
    {
        $resource_validator = $this->getMockedValidator();
        $resource_validator->expects($this->never())->method('validateArtifactsFromBodyContentWithClosedItems');

        $resource_validator->getValidatedArtifactsIdsToAddOrRemoveFromContent(
            $this->user,
            $this->mockMilestoneWithArtifactLinks(),
            null,
            null
        );
    }

    public function testItReturnsTheAddedIdsPlusTheExistingOne(): void
    {
        $resource_validator = $this->getMockedValidator();
        $resource_validator->method('validateArtifactsFromBodyContentWithClosedItems')->willReturn(null);

        self::assertEquals(
            [112, 113, 114, 115, 210],
            $resource_validator->getValidatedArtifactsIdsToAddOrRemoveFromContent(
                $this->user,
                $this->mockMilestoneWithArtifactLinks(),
                null,
                [210]
            )
        );
    }

    public function testItAllowsToAddAndRemoveInSameTime(): void
    {
        $resource_validator = $this->getMockedValidator();
        $resource_validator->method('validateArtifactsFromBodyContentWithClosedItems')->willReturn(null);

        self::assertEquals(
            [112, 114, 210],
            $resource_validator->getValidatedArtifactsIdsToAddOrRemoveFromContent(
                $this->user,
                $this->mockMilestoneWithArtifactLinks(),
                [113, 115],
                [210]
            )
        );
    }

    public function testItSkipsWhenAnElementIsAddedAndRemovedAtSameTime(): void
    {
        $resource_validator = $this->getMockedValidator();
        $resource_validator->method('validateArtifactsFromBodyContentWithClosedItems')->willReturn(null);

        self::assertEquals(
            [112, 113, 115, 210],
            $resource_validator->getValidatedArtifactsIdsToAddOrRemoveFromContent(
                $this->user,
                $this->mockMilestoneWithArtifactLinks(),
                [114, 113],
                [113, 210]
            )
        );
    }

    public function testItDoesntAddAnElementAlreadyInContent(): void
    {
        $resource_validator = $this->getMockedValidator();
        $resource_validator->method('validateArtifactsFromBodyContentWithClosedItems')->willReturn(null);

        self::assertEquals(
            [112, 113, 114, 115],
            $resource_validator->getValidatedArtifactsIdsToAddOrRemoveFromContent(
                $this->user,
                $this->mockMilestoneWithArtifactLinks(),
                null,
                [113]
            )
        );
    }

    private function mockMilestoneWithArtifactLinks(): Planning_ArtifactMilestone&MockObject
    {
        $artifact112 = ArtifactTestBuilder::anArtifact(112)->build();
        $artifact113 = ArtifactTestBuilder::anArtifact(113)->build();
        $artifact114 = ArtifactTestBuilder::anArtifact(114)->build();
        $artifact115 = ArtifactTestBuilder::anArtifact(115)->build();

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getLinkedArtifacts')->willReturn([$artifact112, $artifact113, $artifact114, $artifact115]);

        $milestone = $this->createMock(Planning_ArtifactMilestone::class);
        $milestone->method('getArtifact')->willReturn($artifact);
        $milestone->method('getArtifactId')->willReturn(1000);

        return $milestone;
    }

    protected function getMockedValidator(): MilestoneResourceValidator&MockObject
    {
        return $this->createPartialMock(MilestoneResourceValidator::class, [
            'validateArtifactsFromBodyContentWithClosedItems',
        ]);
    }
}
