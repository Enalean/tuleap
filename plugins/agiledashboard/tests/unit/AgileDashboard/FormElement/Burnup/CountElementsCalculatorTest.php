<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement\Burnup;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetFactory;
use Tracker_Artifact_ChangesetValue_ArtifactLink;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_ArtifactLink;
use Tracker_FormElementFactory;
use Tuleap\AgileDashboard\FormElement\BurnupDao;
use Tuleap\Tracker\Artifact\Artifact;

final class CountElementsCalculatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var CountElementsCalculator
     */
    private $calculator;

    /**
     * @var Mockery\MockInterface|Tracker_Artifact_ChangesetFactory
     */
    private $changeset_factory;

    /**
     * @var Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $form_element_factory;

    /**
     * @var Mockery\MockInterface|BurnupDao
     */
    private $burnup_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->changeset_factory    = Mockery::mock(Tracker_Artifact_ChangesetFactory::class);
        $this->artifact_factory     = Mockery::mock(Tracker_ArtifactFactory::class);
        $this->form_element_factory = Mockery::mock(Tracker_FormElementFactory::class);
        $this->burnup_dao           = Mockery::mock(BurnupDao::class);

        $this->calculator = new CountElementsCalculator(
            $this->changeset_factory,
            $this->artifact_factory,
            $this->form_element_factory,
            $this->burnup_dao
        );
    }

    public function testItCountsDirectSubElementsOfMilestoneAndTheirChildren(): void
    {
        $artifact_id = 1;
        $timestamp   = 1565603104;

        $this->mockEpicUserStoriesAndTasks($artifact_id, $timestamp);

        $count_elements_cache_info = $this->calculator->getValue($artifact_id, $timestamp);

        $this->assertSame(5, $count_elements_cache_info->getTotalElements());
        $this->assertSame(3, $count_elements_cache_info->getClosedElements());
    }

    public function testItCountsDirectSubElementsOfMilestoneAndTheirChildrenWithoutCoutingTwiceElements(): void
    {
        $artifact_id = 1;
        $timestamp   = 1565603104;

        $this->mockEpicUserStoriesAndTasksWithMultipleLinksToTask($artifact_id, $timestamp);

        $count_elements_cache_info = $this->calculator->getValue($artifact_id, $timestamp);

        $this->assertSame(5, $count_elements_cache_info->getTotalElements());
        $this->assertSame(3, $count_elements_cache_info->getClosedElements());
    }

    public function testItOnlyCountsDirectSubElementsOfMilestoneIfTheyDontHaveArtLinkField(): void
    {
        $artifact_id = 1;
        $timestamp   = 1565603104;

        $this->mockUserStoriesWithoutArtLinkField($artifact_id, $timestamp);

        $count_elements_cache_info = $this->calculator->getValue($artifact_id, $timestamp);

        $this->assertSame(3, $count_elements_cache_info->getTotalElements());
        $this->assertSame(1, $count_elements_cache_info->getClosedElements());
    }

    public function testItOnlyCountsDirectSubElementsOfMilestoneIfTheyDontHaveChildren(): void
    {
        $artifact_id = 1;
        $timestamp   = 1565603104;

        $this->mockUserStoriesWithoutChildren($artifact_id, $timestamp);

        $count_elements_cache_info = $this->calculator->getValue($artifact_id, $timestamp);

        $this->assertSame(3, $count_elements_cache_info->getTotalElements());
        $this->assertSame(1, $count_elements_cache_info->getClosedElements());
    }

    private function mockEpicUserStoriesAndTasks(int $artifact_id, int $timestamp): void
    {
        $this->burnup_dao->shouldReceive('searchLinkedArtifactsAtGivenTimestamp')
            ->once()
            ->with($artifact_id, $timestamp)
            ->andReturn([
                ['id' => 2],
            ]);

        $epic_tracker = Mockery::mock(Tracker::class);

        $epic = Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with(2)
            ->andReturn($epic);

        $changeset_epic = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->changeset_factory->shouldReceive('getChangesetAtTimestamp')
            ->once()
            ->with($epic, $timestamp)
            ->andReturn($changeset_epic);

        $epic->shouldReceive('getId')->andReturn(2);

        $epic->shouldReceive('isOpenAtGivenChangeset')
            ->once()
            ->with($changeset_epic)
            ->andReturnTrue();

        $epic->shouldReceive('getTracker')->andReturn($epic_tracker);

        $epic_artifact_link_field = Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class);
        $this->form_element_factory->shouldReceive('getUsedArtifactLinkFields')
            ->once()
            ->with($epic_tracker)
            ->andReturn([
                $epic_artifact_link_field
            ]);

        $changeset_value_epic = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $changeset_epic->shouldReceive('getValue')
            ->once()
            ->with($epic_artifact_link_field)
            ->andReturn($changeset_value_epic);

        $changeset_value_epic->shouldReceive('getArtifactIds')->once()->andReturn([
            3,
            4
        ]);

        $user_story_tracker = Mockery::mock(Tracker::class);

        $user_story_01 = Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with(3)
            ->andReturn($user_story_01);

        $user_story_02 = Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with(4)
            ->andReturn($user_story_02);

        $changeset_user_story_01 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->changeset_factory->shouldReceive('getChangesetAtTimestamp')
            ->once()
            ->with($user_story_01, $timestamp)
            ->andReturn($changeset_user_story_01);

        $changeset_user_story_02 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->changeset_factory->shouldReceive('getChangesetAtTimestamp')
            ->once()
            ->with($user_story_02, $timestamp)
            ->andReturn($changeset_user_story_02);

        $user_story_01->shouldReceive('isOpenAtGivenChangeset')
            ->once()
            ->with($changeset_user_story_01)
            ->andReturnTrue();

        $user_story_02->shouldReceive('isOpenAtGivenChangeset')
            ->once()
            ->with($changeset_user_story_02)
            ->andReturnFalse();

        $user_story_01->shouldReceive('getId')->andReturn(3);
        $user_story_02->shouldReceive('getId')->andReturn(4);

        $user_story_01->shouldReceive('getTracker')->andReturn($user_story_tracker);
        $user_story_02->shouldReceive('getTracker')->andReturn($user_story_tracker);

        $user_story_01->shouldReceive('getParentWithoutPermissionChecking')->andReturn($epic);
        $user_story_02->shouldReceive('getParentWithoutPermissionChecking')->andReturn($epic);

        $user_story_artifact_link_field = Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class);
        $this->form_element_factory->shouldReceive('getUsedArtifactLinkFields')
            ->with($user_story_tracker)
            ->twice()
            ->andReturn([$user_story_artifact_link_field]);

        $changeset_value_user_story_01 = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $changeset_user_story_01->shouldReceive('getValue')
            ->once()
            ->with($user_story_artifact_link_field)
            ->andReturn($changeset_value_user_story_01);

        $changeset_value_user_story_02 = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $changeset_user_story_02->shouldReceive('getValue')
            ->once()
            ->with($user_story_artifact_link_field)
            ->andReturn($changeset_value_user_story_02);

        $changeset_value_user_story_01->shouldReceive('getArtifactIds')->once()->andReturn([]);
        $changeset_value_user_story_02->shouldReceive('getArtifactIds')->once()->andReturn([
            5,
            6
        ]);

        $task_tracker = Mockery::mock(Tracker::class);

        $task_01 = Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with(5)
            ->andReturn($task_01);

        $task_02 = Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with(6)
            ->andReturn($task_02);

        $changeset_task_01 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->changeset_factory->shouldReceive('getChangesetAtTimestamp')
            ->once()
            ->with($task_01, $timestamp)
            ->andReturn($changeset_task_01);

        $changeset_task_02 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->changeset_factory->shouldReceive('getChangesetAtTimestamp')
            ->once()
            ->with($task_02, $timestamp)
            ->andReturn($changeset_task_02);

        $task_01->shouldReceive('isOpenAtGivenChangeset')
            ->once()
            ->with($changeset_task_01)
            ->andReturnFalse();

        $task_02->shouldReceive('isOpenAtGivenChangeset')
            ->once()
            ->with($changeset_task_02)
            ->andReturnFalse();

        $task_01->shouldReceive('getTracker')->andReturn($task_tracker);
        $task_02->shouldReceive('getTracker')->andReturn($task_tracker);

        $task_01->shouldReceive('getParentWithoutPermissionChecking')->andReturn($user_story_02);
        $task_02->shouldReceive('getParentWithoutPermissionChecking')->andReturn($user_story_02);

        $task_01->shouldReceive('getId')->andReturn(5);
        $task_02->shouldReceive('getId')->andReturn(6);

        $this->form_element_factory->shouldReceive('getUsedArtifactLinkFields')
            ->with($task_tracker)
            ->twice()
            ->andReturn([]);
    }

    private function mockEpicUserStoriesAndTasksWithMultipleLinksToTask(int $artifact_id, int $timestamp): void
    {
        //This use case deal with the fact that epics and tasks can be planned into a Release.
        //So the tasks 01 must not be counted twice.

        $this->burnup_dao->shouldReceive('searchLinkedArtifactsAtGivenTimestamp')
            ->once()
            ->with($artifact_id, $timestamp)
            ->andReturn([
                ['id' => 2],
                ['id' => 5],
            ]);

        $epic_tracker = Mockery::mock(Tracker::class);

        $epic = Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with(2)
            ->andReturn($epic);

        $changeset_epic = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->changeset_factory->shouldReceive('getChangesetAtTimestamp')
            ->once()
            ->with($epic, $timestamp)
            ->andReturn($changeset_epic);

        $epic->shouldReceive('getId')->andReturn(2);

        $epic->shouldReceive('isOpenAtGivenChangeset')
            ->once()
            ->with($changeset_epic)
            ->andReturnTrue();

        $epic->shouldReceive('getTracker')->andReturn($epic_tracker);

        $epic_artifact_link_field = Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class);
        $this->form_element_factory->shouldReceive('getUsedArtifactLinkFields')
            ->once()
            ->with($epic_tracker)
            ->andReturn([
                $epic_artifact_link_field
            ]);

        $changeset_value_epic = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $changeset_epic->shouldReceive('getValue')
            ->once()
            ->with($epic_artifact_link_field)
            ->andReturn($changeset_value_epic);

        $changeset_value_epic->shouldReceive('getArtifactIds')->once()->andReturn([
            3,
            4
        ]);

        $user_story_tracker = Mockery::mock(Tracker::class);

        $user_story_01 = Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with(3)
            ->andReturn($user_story_01);

        $user_story_02 = Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with(4)
            ->andReturn($user_story_02);

        $changeset_user_story_01 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->changeset_factory->shouldReceive('getChangesetAtTimestamp')
            ->once()
            ->with($user_story_01, $timestamp)
            ->andReturn($changeset_user_story_01);

        $changeset_user_story_02 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->changeset_factory->shouldReceive('getChangesetAtTimestamp')
            ->once()
            ->with($user_story_02, $timestamp)
            ->andReturn($changeset_user_story_02);

        $user_story_01->shouldReceive('isOpenAtGivenChangeset')
            ->once()
            ->with($changeset_user_story_01)
            ->andReturnTrue();

        $user_story_02->shouldReceive('isOpenAtGivenChangeset')
            ->once()
            ->with($changeset_user_story_02)
            ->andReturnFalse();

        $user_story_01->shouldReceive('getId')->andReturn(3);
        $user_story_02->shouldReceive('getId')->andReturn(4);

        $user_story_01->shouldReceive('getTracker')->andReturn($user_story_tracker);
        $user_story_02->shouldReceive('getTracker')->andReturn($user_story_tracker);

        $user_story_01->shouldReceive('getParentWithoutPermissionChecking')->andReturn($epic);
        $user_story_02->shouldReceive('getParentWithoutPermissionChecking')->andReturn($epic);

        $user_story_artifact_link_field = Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class);
        $this->form_element_factory->shouldReceive('getUsedArtifactLinkFields')
            ->with($user_story_tracker)
            ->twice()
            ->andReturn([$user_story_artifact_link_field]);

        $changeset_value_user_story_01 = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $changeset_user_story_01->shouldReceive('getValue')
            ->once()
            ->with($user_story_artifact_link_field)
            ->andReturn($changeset_value_user_story_01);

        $changeset_value_user_story_02 = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $changeset_user_story_02->shouldReceive('getValue')
            ->once()
            ->with($user_story_artifact_link_field)
            ->andReturn($changeset_value_user_story_02);

        $changeset_value_user_story_01->shouldReceive('getArtifactIds')->once()->andReturn([]);
        $changeset_value_user_story_02->shouldReceive('getArtifactIds')->once()->andReturn([
            5,
            6
        ]);

        $task_tracker = Mockery::mock(Tracker::class);

        $task_01 = Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->twice()
            ->with(5)
            ->andReturn($task_01);

        $task_02 = Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with(6)
            ->andReturn($task_02);

        $changeset_task_01 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->changeset_factory->shouldReceive('getChangesetAtTimestamp')
            ->once()
            ->with($task_01, $timestamp)
            ->andReturn($changeset_task_01);

        $changeset_task_02 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->changeset_factory->shouldReceive('getChangesetAtTimestamp')
            ->once()
            ->with($task_02, $timestamp)
            ->andReturn($changeset_task_02);

        $task_01->shouldReceive('isOpenAtGivenChangeset')
            ->once()
            ->with($changeset_task_01)
            ->andReturnFalse();

        $task_02->shouldReceive('isOpenAtGivenChangeset')
            ->once()
            ->with($changeset_task_02)
            ->andReturnFalse();

        $task_01->shouldReceive('getTracker')->andReturn($task_tracker);
        $task_02->shouldReceive('getTracker')->andReturn($task_tracker);

        $task_01->shouldReceive('getParentWithoutPermissionChecking')->andReturn($user_story_02);
        $task_02->shouldReceive('getParentWithoutPermissionChecking')->andReturn($user_story_02);

        $task_01->shouldReceive('getId')->andReturn(5);
        $task_02->shouldReceive('getId')->andReturn(6);

        $this->form_element_factory->shouldReceive('getUsedArtifactLinkFields')
            ->with($task_tracker)
            ->twice()
            ->andReturn([]);
    }

    private function mockUserStoriesWithoutArtLinkField(int $artifact_id, int $timestamp): void
    {
        $this->burnup_dao->shouldReceive('searchLinkedArtifactsAtGivenTimestamp')
            ->once()
            ->with($artifact_id, $timestamp)
            ->andReturn([
                ['id' => 2],
                ['id' => 3],
                ['id' => 4],
            ]);

        $user_story_tracker = Mockery::mock(Tracker::class);

        $user_story_01 = Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with(2)
            ->andReturn($user_story_01);

        $user_story_02 = Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with(3)
            ->andReturn($user_story_02);

        $user_story_03 = Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with(4)
            ->andReturn($user_story_03);

        $changeset_user_story_01 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->changeset_factory->shouldReceive('getChangesetAtTimestamp')
            ->once()
            ->with($user_story_01, $timestamp)
            ->andReturn($changeset_user_story_01);

        $changeset_user_story_02 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->changeset_factory->shouldReceive('getChangesetAtTimestamp')
            ->once()
            ->with($user_story_02, $timestamp)
            ->andReturn($changeset_user_story_02);

        $changeset_user_story_03 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->changeset_factory->shouldReceive('getChangesetAtTimestamp')
            ->once()
            ->with($user_story_03, $timestamp)
            ->andReturn($changeset_user_story_03);

        $user_story_01->shouldReceive('isOpenAtGivenChangeset')
            ->once()
            ->with($changeset_user_story_01)
            ->andReturnTrue();

        $user_story_02->shouldReceive('isOpenAtGivenChangeset')
            ->once()
            ->with($changeset_user_story_02)
            ->andReturnFalse();

        $user_story_03->shouldReceive('isOpenAtGivenChangeset')
            ->once()
            ->with($changeset_user_story_03)
            ->andReturnTrue();

        $user_story_01->shouldReceive('getId')->andReturn(3);
        $user_story_02->shouldReceive('getId')->andReturn(4);
        $user_story_03->shouldReceive('getId')->andReturn(5);

        $user_story_01->shouldReceive('getTracker')->andReturn($user_story_tracker);
        $user_story_02->shouldReceive('getTracker')->andReturn($user_story_tracker);
        $user_story_03->shouldReceive('getTracker')->andReturn($user_story_tracker);

        $this->form_element_factory->shouldReceive('getUsedArtifactLinkFields')->andReturn([]);
    }

    private function mockUserStoriesWithoutChildren(int $artifact_id, int $timestamp): void
    {
        $this->burnup_dao->shouldReceive('searchLinkedArtifactsAtGivenTimestamp')
            ->once()
            ->with($artifact_id, $timestamp)
            ->andReturn([
                ['id' => 2],
                ['id' => 3],
                ['id' => 4],
            ]);

        $user_story_tracker = Mockery::mock(Tracker::class);

        $user_story_01 = Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with(2)
            ->andReturn($user_story_01);

        $user_story_02 = Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with(3)
            ->andReturn($user_story_02);

        $user_story_03 = Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with(4)
            ->andReturn($user_story_03);

        $changeset_user_story_01 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->changeset_factory->shouldReceive('getChangesetAtTimestamp')
            ->once()
            ->with($user_story_01, $timestamp)
            ->andReturn($changeset_user_story_01);

        $changeset_user_story_02 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->changeset_factory->shouldReceive('getChangesetAtTimestamp')
            ->once()
            ->with($user_story_02, $timestamp)
            ->andReturn($changeset_user_story_02);

        $changeset_user_story_03 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->changeset_factory->shouldReceive('getChangesetAtTimestamp')
            ->once()
            ->with($user_story_03, $timestamp)
            ->andReturn($changeset_user_story_03);

        $user_story_01->shouldReceive('isOpenAtGivenChangeset')
            ->once()
            ->with($changeset_user_story_01)
            ->andReturnTrue();

        $user_story_02->shouldReceive('isOpenAtGivenChangeset')
            ->once()
            ->with($changeset_user_story_02)
            ->andReturnFalse();

        $user_story_03->shouldReceive('isOpenAtGivenChangeset')
            ->once()
            ->with($changeset_user_story_03)
            ->andReturnTrue();

        $user_story_01->shouldReceive('getId')->andReturn(3);
        $user_story_02->shouldReceive('getId')->andReturn(4);
        $user_story_03->shouldReceive('getId')->andReturn(5);

        $user_story_01->shouldReceive('getTracker')->andReturn($user_story_tracker);
        $user_story_02->shouldReceive('getTracker')->andReturn($user_story_tracker);
        $user_story_03->shouldReceive('getTracker')->andReturn($user_story_tracker);

        $artifact_link_field = Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class);
        $this->form_element_factory->shouldReceive('getUsedArtifactLinkFields')->andReturn([
            $artifact_link_field
        ]);

        $changeset_value_user_story_01 = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $changeset_user_story_01->shouldReceive('getValue')
            ->once()
            ->with($artifact_link_field)
            ->andReturn($changeset_value_user_story_01);

        $changeset_value_user_story_02 = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $changeset_user_story_02->shouldReceive('getValue')
            ->once()
            ->with($artifact_link_field)
            ->andReturn($changeset_value_user_story_02);

        $changeset_value_user_story_03 = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $changeset_user_story_03->shouldReceive('getValue')
            ->once()
            ->with($artifact_link_field)
            ->andReturn($changeset_value_user_story_03);

        $changeset_value_user_story_01->shouldReceive('getArtifactIds')->once()->andReturn([]);
        $changeset_value_user_story_02->shouldReceive('getArtifactIds')->once()->andReturn([]);
        $changeset_value_user_story_03->shouldReceive('getArtifactIds')->once()->andReturn([]);
    }
}
