<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\KanbanUserCantAddArtifactException;

final class AgileDashboard_KanbanActionsCheckerTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Tracker_FormElement_Field[]
     */
    private $used_fields;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Semantic_Title
     */
    private $semantic_title;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface |Tracker_FormElement_Field_List
     */
    private $field_list;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_Integer
     */
    private $field_int;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_Text
     */
    protected $field_text;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_String
     */
    private $field_string;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $tracker;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Semantic_Status
     */
    private $semantic_status;

    protected function setUp(): void
    {
        $this->field_string = \Mockery::spy(\Tracker_FormElement_Field_String::class)->shouldReceive('getId')->andReturns(201)->getMock();
        $this->field_text   = \Mockery::spy(\Tracker_FormElement_Field_Text::class)->shouldReceive('getId')->andReturns(20)->getMock();
        $this->field_int    = \Mockery::spy(\Tracker_FormElement_Field_Integer::class)->shouldReceive('getId')->andReturns(30)->getMock();
        $this->field_list   = \Mockery::spy(\Tracker_FormElement_Field_List::class)->shouldReceive('getId')->andReturns(40)->getMock();

        $this->used_fields = [
            $this->field_string,
            $this->field_text,
            $this->field_int,
            $this->field_list,
        ];

        $this->user    = \Mockery::spy(\PFUser::class);
        $this->tracker = Mockery::spy(Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn(888);
        $this->semantic_title  = \Mockery::spy(\Tracker_Semantic_Title::class);
        $this->semantic_status = \Mockery::spy(\Tracker_Semantic_Status::class);

        Tracker_Semantic_Title::setInstance($this->semantic_title, $this->tracker);
        Tracker_Semantic_Status::setInstance($this->semantic_status, $this->tracker);
    }

    protected function tearDown(): void
    {
        Tracker_Semantic_Title::clearInstances();
        Tracker_Semantic_Status::clearInstances();
        parent::tearDown();
    }

    public function testItRaisesAnExceptionIfAnotherFieldIsRequired(): void
    {
        $this->field_string->shouldReceive('isRequired')->andReturns(true);
        $this->field_text->shouldReceive('isRequired')->andReturns(false);
        $this->field_int->shouldReceive('isRequired')->andReturns(false);
        $this->field_list->shouldReceive('isRequired')->andReturns(true);

        $this->tracker->shouldReceive('userCanSubmitArtifact')->with($this->user)->andReturns(true);
        $tracker_factory                  = \Mockery::spy(\TrackerFactory::class)->shouldReceive('getTrackerById')->with(101)->andReturns($this->tracker)->getMock();
        $form_element_factory             = \Mockery::spy(\Tracker_FormElementFactory::class)->shouldReceive('getUsedFields')->andReturns($this->used_fields)->getMock();
        $kanban                           = \Mockery::spy(\Tuleap\Kanban\Kanban::class)->shouldReceive('getTrackerId')->andReturns(101)->getMock();
        $agiledasboard_permission_manager = \Mockery::spy(\AgileDashboard_PermissionsManager::class)->shouldReceive('userCanAdministrate')->andReturns(true)->getMock();
        $this->semantic_title->shouldReceive('getFieldId')->andReturns(201);
        $this->semantic_status->shouldReceive('getFieldId')->andReturns(202);
        $status_field = Mockery::mock(Tracker_FormElement_Field_List::class)->shouldReceive('userCanSubmit')->andReturnTrue()->getMock();
        $this->semantic_status->shouldReceive('getField')->andReturns($status_field);

        $this->expectException(\Kanban_UserCantAddInPlaceException::class);

        $checker = new AgileDashboard_KanbanActionsChecker($tracker_factory, $agiledasboard_permission_manager, $form_element_factory);
        $checker->checkUserCanAddInPlace($this->user, $kanban);
    }

    public function testItRaisesAnExceptionIfNoSemanticTitle(): void
    {
        $this->field_string->shouldReceive('isRequired')->andReturns(true);
        $this->field_text->shouldReceive('isRequired')->andReturns(false);
        $this->field_int->shouldReceive('isRequired')->andReturns(false);
        $this->field_list->shouldReceive('isRequired')->andReturns(false);

        $this->tracker->shouldReceive('userCanSubmitArtifact')->with($this->user)->andReturns(true);
        $tracker_factory                  = \Mockery::spy(\TrackerFactory::class)->shouldReceive('getTrackerById')->with(101)->andReturns($this->tracker)->getMock();
        $form_element_factory             = \Mockery::spy(\Tracker_FormElementFactory::class)->shouldReceive('getUsedFields')->andReturns($this->used_fields)->getMock();
        $kanban                           = \Mockery::spy(\Tuleap\Kanban\Kanban::class)->shouldReceive('getTrackerId')->andReturns(101)->getMock();
        $agiledasboard_permission_manager = \Mockery::spy(\AgileDashboard_PermissionsManager::class)->shouldReceive('userCanAdministrate')->andReturns(true)->getMock();
        $this->semantic_title->shouldReceive('getFieldId')->andReturns(null);
        $this->semantic_status->shouldReceive('getFieldId')->andReturns(202);
        $status_field = Mockery::mock(Tracker_FormElement_Field_List::class)->shouldReceive('userCanSubmit')->andReturnTrue()->getMock();
        $this->semantic_status->shouldReceive('getField')->andReturns($status_field);

        $this->expectException(\Kanban_SemanticTitleNotDefinedException::class);

        $checker = new AgileDashboard_KanbanActionsChecker($tracker_factory, $agiledasboard_permission_manager, $form_element_factory);
        $checker->checkUserCanAddInPlace($this->user, $kanban);
    }

    public function testItRaisesAnExceptionIfTheMandatoryFieldIsNotTheSemanticTitle(): void
    {
        $this->field_string->shouldReceive('isRequired')->andReturns(false);
        $this->field_text->shouldReceive('isRequired')->andReturns(false);
        $this->field_int->shouldReceive('isRequired')->andReturns(false);
        $this->field_list->shouldReceive('isRequired')->andReturns(true);

        $this->tracker->shouldReceive('userCanSubmitArtifact')->with($this->user)->andReturns(true);
        $tracker_factory                  = \Mockery::spy(\TrackerFactory::class)->shouldReceive('getTrackerById')->with(101)->andReturns($this->tracker)->getMock();
        $form_element_factory             = \Mockery::spy(\Tracker_FormElementFactory::class)->shouldReceive('getUsedFields')->andReturns($this->used_fields)->getMock();
        $kanban                           = \Mockery::spy(\Tuleap\Kanban\Kanban::class)->shouldReceive('getTrackerId')->andReturns(101)->getMock();
        $agiledasboard_permission_manager = \Mockery::spy(\AgileDashboard_PermissionsManager::class)->shouldReceive('userCanAdministrate')->andReturns(true)->getMock();
        $this->semantic_title->shouldReceive('getFieldId')->andReturns(201);
        $this->semantic_status->shouldReceive('getFieldId')->andReturns(202);
        $status_field = Mockery::mock(Tracker_FormElement_Field_List::class)->shouldReceive('userCanSubmit')->andReturnTrue()->getMock();
        $this->semantic_status->shouldReceive('getField')->andReturns($status_field);

        $this->expectException(\Kanban_UserCantAddInPlaceException::class);

        $checker = new AgileDashboard_KanbanActionsChecker($tracker_factory, $agiledasboard_permission_manager, $form_element_factory);
        $checker->checkUserCanAddInPlace($this->user, $kanban);
    }

    public function testItRaisesAnExceptionIfTheUserCannotSubmitArtifact(): void
    {
        $this->field_string->shouldReceive('isRequired')->andReturns(true);
        $this->field_text->shouldReceive('isRequired')->andReturns(false);
        $this->field_int->shouldReceive('isRequired')->andReturns(false);
        $this->field_list->shouldReceive('isRequired')->andReturns(false);

        $this->tracker->shouldReceive('userCanSubmitArtifact')->with($this->user)->andReturns(false);
        $tracker_factory                  = \Mockery::spy(\TrackerFactory::class)->shouldReceive('getTrackerById')->with(101)->andReturns($this->tracker)->getMock();
        $form_element_factory             = \Mockery::spy(\Tracker_FormElementFactory::class)->shouldReceive('getUsedFields')->andReturns($this->used_fields)->getMock();
        $kanban                           = \Mockery::spy(\Tuleap\Kanban\Kanban::class)->shouldReceive('getTrackerId')->andReturns(101)->getMock();
        $agiledasboard_permission_manager = \Mockery::spy(\AgileDashboard_PermissionsManager::class)->shouldReceive('userCanAdministrate')->andReturns(true)->getMock();
        $this->semantic_title->shouldReceive('getFieldId')->andReturns(201);
        $this->semantic_status->shouldReceive('getFieldId')->andReturns(202);
        $status_field = Mockery::mock(Tracker_FormElement_Field_List::class)->shouldReceive('userCanSubmit')->andReturnTrue()->getMock();
        $this->semantic_status->shouldReceive('getField')->andReturns($status_field);

        $this->expectException(KanbanUserCantAddArtifactException::class);

        $checker = new AgileDashboard_KanbanActionsChecker($tracker_factory, $agiledasboard_permission_manager, $form_element_factory);
        $checker->checkUserCanAddInPlace($this->user, $kanban);
    }

    public function testItRaisesAnExceptionIfTheUserCannotSubmitStatusField(): void
    {
        $this->field_string->shouldReceive('isRequired')->andReturns(true);
        $this->field_text->shouldReceive('isRequired')->andReturns(false);
        $this->field_int->shouldReceive('isRequired')->andReturns(false);
        $this->field_list->shouldReceive('isRequired')->andReturns(false);

        $this->tracker->shouldReceive('userCanSubmitArtifact')->with($this->user)->andReturns(true);
        $tracker_factory                  = \Mockery::spy(\TrackerFactory::class)->shouldReceive('getTrackerById')->with(101)->andReturns($this->tracker)->getMock();
        $form_element_factory             = \Mockery::spy(\Tracker_FormElementFactory::class)->shouldReceive('getUsedFields')->andReturns($this->used_fields)->getMock();
        $kanban                           = \Mockery::spy(\Tuleap\Kanban\Kanban::class)->shouldReceive('getTrackerId')->andReturns(101)->getMock();
        $agiledasboard_permission_manager = \Mockery::spy(\AgileDashboard_PermissionsManager::class)->shouldReceive('userCanAdministrate')->andReturns(true)->getMock();
        $this->semantic_title->shouldReceive('getFieldId')->andReturns(201);
        $this->semantic_status->shouldReceive('getFieldId')->andReturns(202);
        $status_field = Mockery::mock(Tracker_FormElement_Field_List::class)->shouldReceive('userCanSubmit')->andReturnFalse()->getMock();
        $this->semantic_status->shouldReceive('getField')->andReturns($status_field);

        $this->expectException(KanbanUserCantAddArtifactException::class);

        $checker = new AgileDashboard_KanbanActionsChecker($tracker_factory, $agiledasboard_permission_manager, $form_element_factory);
        $checker->checkUserCanAddInPlace($this->user, $kanban);
    }
}
