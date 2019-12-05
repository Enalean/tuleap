<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Taskboard\Tracker;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Mockery as M;

class AddInPlaceTrackerRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var \Tracker_FormElementFactory */
    private $form_element_factory;
    /** @var AddInPlaceTrackerRetriever */
    private $add_in_place_tracker_retriever;
    /** @var \Tracker_Semantic_Title */
    private $semantic_title;

    private const SEMANTIC_TITLE_FIELD_ID = 1533;

    protected function setUp() : void
    {
        $this->semantic_title                 = M::mock(\Tracker_Semantic_Title::class);
        $this->form_element_factory           = M::mock(\Tracker_FormElementFactory::class);
        $this->add_in_place_tracker_retriever = new AddInPlaceTrackerRetriever(
            $this->form_element_factory
        );
    }

    protected function tearDown(): void
    {
        \Tracker_Semantic_Title::clearInstances();
    }

    public function testItReturnsNullWhenTrackerHasMoreThanOneChild(): void
    {
        $taskboard_tracker = $this->getTaskboardTracker();

        $user                  = M::mock(\PFUser::class);
        $tracker               = $taskboard_tracker->getTracker();
        $tracker->shouldReceive('getChildren')->andReturn([
            M::mock(Tracker::class),
            M::mock(Tracker::class)
        ]);

        $add_in_place_tracker = $this->add_in_place_tracker_retriever->retrieveAddInPlaceTracker(
            $taskboard_tracker,
            $user
        );

        $this->assertNull($add_in_place_tracker);
    }

    public function testItReturnsNullWhenTrackerHasNoChildren(): void
    {
        $taskboard_tracker = $this->getTaskboardTracker();

        $user                  = M::mock(\PFUser::class);
        $tracker               = $taskboard_tracker->getTracker();
        $tracker->shouldReceive('getChildren')->andReturn([]);

        $add_in_place_tracker = $this->add_in_place_tracker_retriever->retrieveAddInPlaceTracker(
            $taskboard_tracker,
            $user
        );

        $this->assertNull($add_in_place_tracker);
    }

    public function testItReturnsNullWhenTitleSemanticIsNotDefinedOnChildTracker(): void
    {
        $taskboard_tracker = $this->getTaskboardTracker();

        $user                  = M::mock(\PFUser::class);
        $tracker               = $taskboard_tracker->getTracker();

        $child_tracker = M::mock(Tracker::class);
        $child_tracker->shouldReceive('getId')->andReturn(42);

        $tracker->shouldReceive('getChildren')->andReturn([$child_tracker]);

        $this->mockSemanticTitle($child_tracker, false, false);

        $add_in_place_tracker = $this->add_in_place_tracker_retriever->retrieveAddInPlaceTracker(
            $taskboard_tracker,
            $user
        );

        $this->assertNull($add_in_place_tracker);
    }

    public function testItReturnsNullWhenUserCannotUpdateFieldBoundToSemanticTitle(): void
    {
        $taskboard_tracker = $this->getTaskboardTracker();

        $user                  = M::mock(\PFUser::class);
        $tracker               = $taskboard_tracker->getTracker();

        $child_tracker = M::mock(Tracker::class);
        $child_tracker->shouldReceive('getId')->andReturn(42);

        $tracker->shouldReceive('getChildren')->andReturn([$child_tracker]);

        $this->mockSemanticTitle($child_tracker, true, false);

        $add_in_place_tracker = $this->add_in_place_tracker_retriever->retrieveAddInPlaceTracker(
            $taskboard_tracker,
            $user
        );

        $this->assertNull($add_in_place_tracker);
    }

    public function testItReturnsNullWhenSemanticTitleFieldIsNotTheOnlyFieldRequiredAtArtifactSubmission(): void
    {
        $taskboard_tracker = $this->getTaskboardTracker();

        $user                  = M::mock(\PFUser::class);
        $tracker               = $taskboard_tracker->getTracker();

        $child_tracker = M::mock(Tracker::class);
        $child_tracker->shouldReceive('getId')->andReturn(42);

        $tracker->shouldReceive('getChildren')->andReturn([$child_tracker]);

        $this->mockSemanticTitle($child_tracker, true, true);
        $this->mockTrackerFields($child_tracker, true, true);

        $add_in_place_tracker = $this->add_in_place_tracker_retriever->retrieveAddInPlaceTracker(
            $taskboard_tracker,
            $user
        );

        $this->assertNull($add_in_place_tracker);
    }

    public function testItReturnsATracker(): void
    {
        $taskboard_tracker = $this->getTaskboardTracker();

        $user                  = M::mock(\PFUser::class);
        $tracker               = $taskboard_tracker->getTracker();

        $child_tracker = M::mock(Tracker::class);
        $child_tracker->shouldReceive('getId')->andReturn(42);

        $tracker->shouldReceive('getChildren')->andReturn([$child_tracker]);

        $this->mockSemanticTitle($child_tracker, true, true);
        $this->mockTrackerFields($child_tracker, true, false);

        $add_in_place_tracker = $this->add_in_place_tracker_retriever->retrieveAddInPlaceTracker(
            $taskboard_tracker,
            $user
        );

        $this->assertSame($child_tracker, $add_in_place_tracker);
    }

    private function mockSemanticTitle(\Tracker $child_tracker, bool $is_set, bool $user_can_submit): void
    {
        \Tracker_Semantic_Title::setInstance($this->semantic_title, $child_tracker);

        $title_field = null;

        if ($is_set) {
            $title_field = M::mock(\Tracker_FormElement_Field_String::class);
            $title_field->shouldReceive('userCanSubmit')->andReturn($user_can_submit);
            $title_field->shouldReceive('getId')->andReturn(self::SEMANTIC_TITLE_FIELD_ID);
        }

        $this->semantic_title->shouldReceive('getField')->andReturn($title_field)->once();
    }

    /**
     * @return array
     */
    private function getTaskboardTracker() : TaskboardTracker
    {
        $milestone_tracker = M::mock(Tracker::class);
        $tracker           = M::mock(Tracker::class)->shouldReceive('getId')->andReturn("12")->getMock();

        return new TaskboardTracker(
            $milestone_tracker,
            $tracker
        );
    }

    /**
     * @return array
     */
    private function mockTrackerFields(\Tracker $tracker, bool $is_title_field_required, bool $is_desc_field_required): void
    {
        $title_field = M::mock(\Tracker_FormElement_Field_String::class);
        $title_field->shouldReceive('isRequired')->andReturn($is_title_field_required);
        $title_field->shouldReceive('getId')->andReturn(self::SEMANTIC_TITLE_FIELD_ID);

        $desc_field = M::mock(\Tracker_FormElement_Field_Text::class);
        $desc_field->shouldReceive('isRequired')->andReturn($is_desc_field_required);
        $desc_field->shouldReceive('getId')->andReturn(1534);

        $this->form_element_factory->shouldReceive('getUsedFields')->with($tracker)->andReturn([$title_field, $desc_field]);
    }
}
