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

declare(strict_types=1);

namespace Tuleap\Taskboard\Tracker;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning_Milestone;
use Tracker;
use Tracker_FormElement_Field_Selectbox;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedFieldRetriever;

final class TrackerPresenterCollectionBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var TrackerPresenterCollectionBuilder */
    private $trackers_builder;
    /** @var M\LegacyMockInterface|M\MockInterface|TrackerCollectionRetriever */
    private $trackers_retriever;
    /** @var M\LegacyMockInterface|M\MockInterface|MappedFieldRetriever */
    private $mapped_field_retriever;
    /** @var \Tracker_Semantic_Title */
    private $semantic_title;
    /** @var AddInPlaceRetriever */
    private $add_in_place_tracker_retriever;
    /** @var M\LegacyMockInterface|M\MockInterface|\Tracker_Semantic_Contributor */
    private $semantic_contributor;

    protected function setUp(): void
    {
        $this->trackers_retriever             = M::mock(TrackerCollectionRetriever::class);
        $this->mapped_field_retriever         = M::mock(MappedFieldRetriever::class);
        $this->semantic_title                 = M::mock(\Tracker_Semantic_Title::class);
        $this->semantic_contributor           = M::mock(\Tracker_Semantic_Contributor::class);
        $this->add_in_place_tracker_retriever = M::mock(AddInPlaceRetriever::class);
        $this->trackers_builder               = new TrackerPresenterCollectionBuilder(
            $this->trackers_retriever,
            $this->mapped_field_retriever,
            $this->add_in_place_tracker_retriever
        );
    }

    protected function tearDown(): void
    {
        \Tracker_Semantic_Title::clearInstances();
        \Tracker_Semantic_Contributor::clearInstances();
    }

    public function testBuildCollectionReturnsEmptyArrayWhenNoTrackers(): void
    {
        $milestone = M::mock(Planning_Milestone::class);
        $user      = M::mock(PFUser::class);
        $this->trackers_retriever->shouldReceive('getTrackersForMilestone')
                                 ->with($milestone)
                                 ->once()
                                 ->andReturn(new TrackerCollection([]));
        $this->mapped_field_retriever->shouldNotReceive('getField');

        $result = $this->trackers_builder->buildCollection($milestone, $user);
        $this->assertSame(0, count($result));
    }

    public function testBuildCollectionReturnsCannotUpdateWhenNoMappedFieldAndCannotUpdateTitle(): void
    {
        $milestone         = M::mock(Planning_Milestone::class);
        $user              = M::mock(PFUser::class);
        $milestone_tracker = M::mock(Tracker::class);
        $tracker           = $this->mockTracker(27);
        $taskboard_tracker = new TaskboardTracker($milestone_tracker, $tracker);
        $this->trackers_retriever->shouldReceive('getTrackersForMilestone')
                                 ->with($milestone)
                                 ->once()
                                 ->andReturn(new TrackerCollection([$taskboard_tracker]));
        $this->mapped_field_retriever->shouldReceive('getField')
                                     ->with($taskboard_tracker)
                                     ->once()
                                     ->andReturnNull();

        $this->mockSemanticTitle($taskboard_tracker, true, false);
        $this->mockSemanticContributor($taskboard_tracker, true, true);
        $this->add_in_place_tracker_retriever
            ->shouldReceive('retrieveAddInPlace')
            ->with($taskboard_tracker, $user, \Mockery::any())
            ->once()
            ->andReturn(null);

        $result = $this->trackers_builder->buildCollection($milestone, $user);
        $this->assertFalse($result[0]->can_update_mapped_field);
        $this->assertNull($result[0]->title_field);
    }

    public function testBuildCollectionReturnsTrackerPresenters(): void
    {
        $milestone                = M::mock(Planning_Milestone::class);
        $user                     = M::mock(PFUser::class);
        $milestone_tracker        = M::mock(Tracker::class);
        $first_tracker            = $this->mockTracker(27);
        $second_tracker           = $this->mockTracker(85);
        $third_tracker            = $this->mockTracker(96);
        $fourth_tracker           = $this->mockTracker(99);
        $first_taskboard_tracker  = new TaskboardTracker($milestone_tracker, $first_tracker);
        $second_taskboard_tracker = new TaskboardTracker($milestone_tracker, $second_tracker);
        $third_taskboard_tracker  = new TaskboardTracker($milestone_tracker, $third_tracker);
        $fourth_taskboard_tracker = new TaskboardTracker($milestone_tracker, $fourth_tracker);

        $this->trackers_retriever
            ->shouldReceive('getTrackersForMilestone')
            ->with($milestone)
            ->once()
            ->andReturn(
                new TrackerCollection(
                    [
                        $first_taskboard_tracker,
                        $second_taskboard_tracker,
                        $third_taskboard_tracker,
                        $fourth_taskboard_tracker
                    ]
                )
            );

        $this->mockMappedField($user, $first_taskboard_tracker, true);
        $this->mockMappedField($user, $second_taskboard_tracker, false);
        $this->mockMappedField($user, $third_taskboard_tracker, false);
        $this->mockMappedField($user, $fourth_taskboard_tracker, false);

        $this->mockSemanticTitle($first_taskboard_tracker, false, true);
        $this->mockSemanticTitle($second_taskboard_tracker, true, true);
        $this->mockSemanticTitle($third_taskboard_tracker, true, true, \Tracker_FormElement_Field_String::class);
        $this->mockSemanticTitle($fourth_taskboard_tracker, true, true);

        $this->mockSemanticContributor($first_taskboard_tracker, false, false);
        $this->mockSemanticContributor($second_taskboard_tracker, true, false);
        $this->mockSemanticContributor($third_taskboard_tracker, true, true);
        $this->mockSemanticContributor($fourth_taskboard_tracker, true, true, \Tracker_FormElement_Field_MultiSelectbox::class);

        $this->add_in_place_tracker_retriever
            ->shouldReceive('retrieveAddInPlace')
            ->with($first_taskboard_tracker, $user, M::any())
            ->once()
            ->andReturn(null);
        $this->add_in_place_tracker_retriever
            ->shouldReceive('retrieveAddInPlace')
            ->with($second_taskboard_tracker, $user, M::any())
            ->once()
            ->andReturn(null);
        $this->add_in_place_tracker_retriever
            ->shouldReceive('retrieveAddInPlace')
            ->with($third_taskboard_tracker, $user, M::any())
            ->once()
            ->andReturn(null);

        $this->add_in_place_tracker_retriever
            ->shouldReceive('retrieveAddInPlace')
            ->with($fourth_taskboard_tracker, $user, M::any())
            ->once()
            ->andReturn(
                new AddInPlace(
                    M::mock(\Tracker::class)->shouldReceive(['getId' => 666])->getMock(),
                    M::mock(\Tracker_FormElement_Field_ArtifactLink::class)->shouldReceive(['getId' => 999])->getMock()
                )
            );

        $result = $this->trackers_builder->buildCollection($milestone, $user);
        $this->assertSame(27, $result[0]->id);
        $this->assertTrue($result[0]->can_update_mapped_field);
        $this->assertNull($result[0]->title_field);
        $this->assertSame(85, $result[1]->id);
        $this->assertFalse($result[1]->can_update_mapped_field);
        $this->assertEquals(1533, $result[1]->title_field->id);
        $this->assertFalse($result[1]->title_field->is_string_field);
        $this->assertTrue($result[2]->title_field->is_string_field);
        $this->assertNull($result[0]->add_in_place);
        $this->assertNull($result[1]->add_in_place);
        $this->assertNull($result[2]->add_in_place);
        $this->assertEquals(666, $result[3]->add_in_place->child_tracker_id);
        $this->assertEquals(999, $result[3]->add_in_place->parent_artifact_link_field_id);

        $this->assertNull($result[0]->assigned_to_field);
        $this->assertNull($result[1]->assigned_to_field);
        $this->assertEquals(1534, $result[2]->assigned_to_field->id);
        $this->assertFalse($result[2]->assigned_to_field->is_multiple);
        $this->assertEquals(1534, $result[3]->assigned_to_field->id);
        $this->assertTrue($result[3]->assigned_to_field->is_multiple);
    }

    private function mockMappedField(
        PFUser $user,
        TaskboardTracker $taskboard_tracker,
        bool $can_user_update
    ): void {
        $sb_field = M::mock(Tracker_FormElement_Field_Selectbox::class);
        $sb_field->shouldReceive('userCanUpdate')
                 ->with($user)
                 ->once()
                 ->andReturn($can_user_update);
        $this->mapped_field_retriever->shouldReceive('getField')
                                     ->with($taskboard_tracker)
                                     ->once()
                                     ->andReturn($sb_field);
    }

    /**
     * @return M\LegacyMockInterface|M\MockInterface|Tracker
     */
    private function mockTracker(int $id)
    {
        return M::mock(Tracker::class)->shouldReceive('getId')
                ->andReturn($id)
                ->getMock();
    }

    private function mockSemanticTitle(
        TaskboardTracker $taskboard_tracker,
        bool $is_semantic_set,
        bool $can_user_update,
        $classname = \Tracker_FormElement_Field_Text::class
    ): void {
        \Tracker_Semantic_Title::setInstance($this->semantic_title, $taskboard_tracker->getTracker());

        $title_field = null;

        if ($is_semantic_set) {
            $title_field = M::mock($classname);
            $title_field->shouldReceive('getId')->andReturn(1533);
            $title_field->shouldReceive('userCanUpdate')->andReturn($can_user_update);
        }

        $this->semantic_title->shouldReceive('getField')->andReturn($title_field)->once();
    }

    private function mockSemanticContributor(
        TaskboardTracker $taskboard_tracker,
        bool $is_semantic_set,
        bool $can_user_update,
        $classname = \Tracker_FormElement_Field_Selectbox::class
    ): void {
        \Tracker_Semantic_Contributor::setInstance($this->semantic_contributor, $taskboard_tracker->getTracker());

        $contributor_field = null;

        if ($is_semantic_set) {
            $contributor_field = M::mock($classname);
            $contributor_field->shouldReceive('getId')->andReturn(1534);
            $contributor_field->shouldReceive('userCanUpdate')->andReturn($can_user_update);
            $contributor_field->shouldReceive('isMultiple')->andReturn(
                $classname === \Tracker_FormElement_Field_MultiSelectbox::class || $classname === \Tracker_FormElement_Field_Checkbox::class
            );
        }

        $this->semantic_contributor->shouldReceive('getField')->andReturn($contributor_field)->once();
    }
}
