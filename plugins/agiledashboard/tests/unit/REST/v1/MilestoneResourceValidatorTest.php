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

namespace Tuleap\AgileDashboard\REST\v1;

use AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Planning_ArtifactMilestone;
use Tuleap\Tracker\REST\Helpers\IdsFromBodyAreNotUniqueException;

final class MilestoneResourceValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tuleap\Tracker\Artifact\Artifact
     */
    private $artifact2;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tuleap\Tracker\Artifact\Artifact
     */
    private $artifact1;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Planning_Milestone
     */
    private $milestone;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_ArtifactFactory
     */
    private $tracker_artifact_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PlanningFactory
     */
    private $planning_factory;
    /**
     * @var \AgileDashboard_BacklogItemPresenter|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $todo_item;
    /**
     * @var \AgileDashboard_BacklogItemPresenter|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $unplanned_item;
    /**
     * @var AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection
     */
    private $todo_collection;
    /**
     * @var AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection
     */
    private $unplanned_collection;
    /**
     * @var Int[]
     */
    private $ids;
    /** @var MilestoneResourceValidator */
    private $milestone_resource_validator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $this->user = Mockery::spy(\PFUser::class);

        $this->ids        = [102, 174];
        $parent_milestone = Mockery::mock(\Planning_Milestone::class);
        $this->milestone  = Mockery::mock(\Planning_Milestone::class);
        $this->milestone->shouldReceive('getParent')->andReturn($parent_milestone);

        $self_backlog = Mockery::spy(\AgileDashboard_Milestone_Backlog_Backlog::class);
        $backlog      = Mockery::spy(\AgileDashboard_Milestone_Backlog_Backlog::class);
        $planning     = Mockery::mock(\Planning::class);
        $planning->shouldReceive('getId')->andReturn(3);

        $this->artifact1 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact1->shouldReceive('getTrackerId')->andReturn(555);
        $this->artifact1->shouldReceive('getId')->andReturn(102);
        $this->artifact2 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact2->shouldReceive('getTrackerId')->andReturn(666);
        $this->artifact2->shouldReceive('getId')->andReturn(174);

        $this->unplanned_item = Mockery::mock(\AgileDashboard_BacklogItemPresenter::class);
        $this->unplanned_item->shouldReceive('id')->andReturn(102);

        $this->todo_item = Mockery::mock(\AgileDashboard_BacklogItemPresenter::class);
        $this->todo_item->shouldReceive('id')->andReturn(174);

        $this->unplanned_collection     = new AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection();
        $done_collection                = new AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection();
        $this->todo_collection          = new AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection();
        $this->planning_factory         = Mockery::spy(\PlanningFactory::class);
        $this->tracker_artifact_factory = Mockery::spy(\Tracker_ArtifactFactory::class);
        $backlog_factory                = Mockery::spy(\AgileDashboard_Milestone_Backlog_BacklogFactory::class);
        $milestone_factory              = Mockery::spy(\Planning_MilestoneFactory::class);
        $backlog_row_collection_factory = Mockery::spy(
            \AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory::class
        );

        $this->milestone->shouldReceive('getPlanning')->andReturn($planning);
        $backlog_factory->shouldReceive('getSelfBacklog')->with($this->milestone)->andReturn($self_backlog);
        $backlog_factory->shouldReceive('getBacklog')->andReturn($backlog);
        $backlog_row_collection_factory->shouldReceive('getDoneCollection')->andReturn($done_collection);
        $backlog_row_collection_factory->shouldReceive('getTodoCollection')->andReturn($this->todo_collection);
        $backlog_row_collection_factory->shouldReceive('getUnplannedOpenCollection')->andReturn(
            $this->unplanned_collection
        );

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

        $this->planning_factory->shouldReceive('getBacklogTrackersIds')
            ->with($this->milestone->getPlanning()->getId())
            ->andReturn([555, 666]);

        $this->tracker_artifact_factory->shouldReceive('getArtifactById')->with(102)->andReturn($this->artifact1);
        $this->tracker_artifact_factory->shouldReceive('getArtifactById')->with(174)->andReturn($this->artifact2);

        $validation = $this->milestone_resource_validator->validateArtifactsFromBodyContent(
            $this->ids,
            $this->milestone,
            $this->user
        );

        $this->assertTrue($validation);
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

        $this->planning_factory->shouldReceive('getBacklogTrackersIds')
            ->with($this->milestone->getPlanning()->getId())
            ->andReturn([1, 2, 3]);

        $this->milestone_resource_validator->validateArtifactsFromBodyContent(
            $this->ids,
            $this->milestone,
            $this->user
        );
    }

    public function testItThrowsAnExceptionIfArtifactIsNotInBacklogTracker(): void
    {
        $this->expectException(ArtifactIsNotInBacklogTrackerException::class);

        $this->planning_factory->shouldReceive('getBacklogTrackersIds')
            ->with($this->milestone->getPlanning()->getId())
            ->andReturn([1, 2, 3]);
        $this->tracker_artifact_factory->shouldReceive('getArtifactById')->with(102)->andReturn($this->artifact1);
        $this->tracker_artifact_factory->shouldReceive('getArtifactById')->with(174)->andReturn($this->artifact2);

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

        $this->planning_factory->shouldReceive('getBacklogTrackersIds')
            ->with($this->milestone->getPlanning()->getId())
            ->andReturn([555, 666]);
        $this->tracker_artifact_factory->shouldReceive('getArtifactById')->with(102)->andReturn($this->artifact1);
        $this->tracker_artifact_factory->shouldReceive('getArtifactById')->with(174)->andReturn($this->artifact2);

        $this->milestone_resource_validator->validateArtifactsFromBodyContent(
            $this->ids,
            $this->milestone,
            $this->user
        );
    }

    public function testItAllowsToRemoveFromContentWhenRemovedIdsArePartOfLinkedArtifacts(): void
    {
        $this->expectNotToPerformAssertions();
        $this->getMockedValidator()->getValidatedArtifactsIdsToAddOrRemoveFromContent(
            $this->user,
            $this->mockMilestoneWithArtifactLinks(),
            [112, 113],
            null
        );
    }

    public function testItForbidsToRemoveFromContentWhenRemovedIdsArePartOfLinkedArtifacts(): void
    {
        $this->expectException(\Tuleap\AgileDashboard\REST\v1\ArtifactIsNotInMilestoneContentException::class);
        $this->getMockedValidator()->getValidatedArtifactsIdsToAddOrRemoveFromContent(
            $this->user,
            $this->mockMilestoneWithArtifactLinks(),
            [566, 113],
            null
        );
    }

    public function testItReturnsTheValidIds(): void
    {
        $this->assertEquals(
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
        $resource_validator->shouldReceive('validateArtifactsFromBodyContentWithClosedItems')
            ->with([210], $milestone, $this->user)->once();

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
        $resource_validator->shouldReceive('validateArtifactsFromBodyContentWithClosedItems')->never();

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
        $resource_validator->shouldReceive('validateArtifactsFromBodyContentWithClosedItems')
            ->andReturn(null);

        $this->assertEquals(
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
        $resource_validator->shouldReceive('validateArtifactsFromBodyContentWithClosedItems')
            ->andReturn(null);

        $this->assertEquals(
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
        $resource_validator->shouldReceive('validateArtifactsFromBodyContentWithClosedItems')
            ->andReturn(null);

        $this->assertEquals(
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
        $resource_validator->shouldReceive('validateArtifactsFromBodyContentWithClosedItems')
            ->andReturn(null);

        $this->assertEquals(
            [112, 113, 114, 115],
            $resource_validator->getValidatedArtifactsIdsToAddOrRemoveFromContent(
                $this->user,
                $this->mockMilestoneWithArtifactLinks(),
                null,
                [113]
            )
        );
    }

    /**
     * @return Mockery\LegacyMockInterface|Mockery\MockInterface|Planning_ArtifactMilestone
     */
    private function mockMilestoneWithArtifactLinks()
    {
        $artifact112 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact112->shouldReceive('getId')->andReturn(112);
        $artifact113 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact113->shouldReceive('getId')->andReturn(113);
        $artifact114 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact114->shouldReceive('getId')->andReturn(114);
        $artifact115 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact115->shouldReceive('getId')->andReturn(115);


        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getLinkedArtifacts')
            ->andReturn([$artifact112, $artifact113, $artifact114, $artifact115]);

        $milestone = Mockery::mock(Planning_ArtifactMilestone::class);
        $milestone->shouldReceive('getArtifact')->andReturn($artifact);
        $milestone->shouldReceive('getArtifactId')->andReturn(1000);

        return $milestone;
    }

    /**
     * @return Mockery\Mock | MilestoneResourceValidator
     */
    protected function getMockedValidator()
    {
        return Mockery::mock(MilestoneResourceValidator::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();
    }
}
