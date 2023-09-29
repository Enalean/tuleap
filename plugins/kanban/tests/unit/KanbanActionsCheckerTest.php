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

declare(strict_types=1);

namespace Tuleap\Kanban;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tracker_FormElement_Field_Integer;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_String;
use Tracker_FormElement_Field_Text;
use Tracker_Semantic_Status;
use Tracker_Semantic_Title;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;
use Tuleap\Tracker\Test\Stub\VerifySubmissionPermissionStub;

final class KanbanActionsCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Tracker_FormElement_Field_String&MockObject $field_string;
    private Tracker_FormElement_Field_Text&MockObject $field_text;
    private Tracker_FormElement_Field_Integer&MockObject $field_int;
    private Tracker_FormElement_Field_List&MockObject $field_list;
    private Tracker $tracker;
    private \Project $project;
    private MockObject&Tracker_Semantic_Title $semantic_title;
    private Tracker_Semantic_Status&MockObject $semantic_status;

    protected function setUp(): void
    {
        $this->field_string = $this->createMock(Tracker_FormElement_Field_String::class);
        $this->field_string->method('getId')->willReturn(201);
        $this->field_text = $this->createMock(Tracker_FormElement_Field_Text::class);
        $this->field_text->method('getId')->willReturn(20);
        $this->field_int = $this->createMock(Tracker_FormElement_Field_Integer::class);
        $this->field_int->method('getId')->willReturn(30);
        $this->field_list = $this->createMock(Tracker_FormElement_Field_List::class);
        $this->field_list->method('getId')->willReturn(40);

        $this->project = ProjectTestBuilder::aProject()->build();
        $this->tracker = TrackerTestBuilder::aTracker()->withId(888)->withProject($this->project)->build();

        $this->semantic_title  = $this->createMock(\Tracker_Semantic_Title::class);
        $this->semantic_status = $this->createMock(\Tracker_Semantic_Status::class);

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
        $this->field_string->method('isRequired')->willReturn(true);
        $this->field_text->method('isRequired')->willReturn(false);
        $this->field_int->method('isRequired')->willReturn(false);
        $this->field_list->method('isRequired')->willReturn(true);

        $tracker_factory      = RetrieveTrackerStub::withTracker($this->tracker);
        $form_element_factory = RetrieveUsedFieldsStub::withFields(
            $this->field_string,
            $this->field_text,
            $this->field_int,
            $this->field_list,
        );

        $kanban = new Kanban(123, $this->tracker, false, 'My kanban');
        $user   = UserTestBuilder::buildWithDefaults();

        $this->semantic_title->method('getFieldId')->willReturn(201);
        $this->semantic_status->method('getFieldId')->willReturn(202);
        $status_field = $this->createMock(Tracker_FormElement_Field_List::class);
        $status_field->method('userCanSubmit')->willReturn(true);
        $this->semantic_status->method('getField')->willReturn($status_field);

        $this->expectException(\Tuleap\Kanban\KanbanUserCantAddInPlaceException::class);

        $checker = new KanbanActionsChecker(
            $tracker_factory,
            new \Tuleap\Kanban\KanbanPermissionsManager(),
            $form_element_factory,
            VerifySubmissionPermissionStub::withSubmitPermission(),
        );
        $checker->checkUserCanAddInPlace($user, $kanban);
    }

    public function testItRaisesAnExceptionIfNoSemanticTitle(): void
    {
        $this->field_string->method('isRequired')->willReturn(true);
        $this->field_text->method('isRequired')->willReturn(false);
        $this->field_int->method('isRequired')->willReturn(false);
        $this->field_list->method('isRequired')->willReturn(false);

        $tracker_factory      = RetrieveTrackerStub::withTracker($this->tracker);
        $form_element_factory = RetrieveUsedFieldsStub::withFields(
            $this->field_string,
            $this->field_text,
            $this->field_int,
            $this->field_list,
        );

        $kanban = new Kanban(123, $this->tracker, false, 'My kanban');
        $user   = UserTestBuilder::buildWithDefaults();

        $this->semantic_title->method('getFieldId')->willReturn(null);
        $this->semantic_status->method('getFieldId')->willReturn(202);
        $status_field = $this->createMock(Tracker_FormElement_Field_List::class);
        $status_field->method('userCanSubmit')->willReturn(true);
        $this->semantic_status->method('getField')->willReturn($status_field);

        $this->expectException(\Tuleap\Kanban\KanbanSemanticTitleNotDefinedException::class);

        $checker = new KanbanActionsChecker(
            $tracker_factory,
            new \Tuleap\Kanban\KanbanPermissionsManager(),
            $form_element_factory,
            VerifySubmissionPermissionStub::withSubmitPermission(),
        );
        $checker->checkUserCanAddInPlace($user, $kanban);
    }

    public function testItRaisesAnExceptionIfTheMandatoryFieldIsNotTheSemanticTitle(): void
    {
        $this->field_string->method('isRequired')->willReturn(false);
        $this->field_text->method('isRequired')->willReturn(false);
        $this->field_int->method('isRequired')->willReturn(false);
        $this->field_list->method('isRequired')->willReturn(true);

        $tracker_factory      = RetrieveTrackerStub::withTracker($this->tracker);
        $form_element_factory = RetrieveUsedFieldsStub::withFields(
            $this->field_string,
            $this->field_text,
            $this->field_int,
            $this->field_list,
        );

        $kanban = new Kanban(123, $this->tracker, false, 'My kanban');
        $user   = UserTestBuilder::buildWithDefaults();

        $this->semantic_title->method('getFieldId')->willReturn(201);
        $this->semantic_status->method('getFieldId')->willReturn(202);
        $status_field = $this->createMock(Tracker_FormElement_Field_List::class);
        $status_field->method('userCanSubmit')->willReturn(true);
        $this->semantic_status->method('getField')->willReturn($status_field);

        $this->expectException(\Tuleap\Kanban\KanbanUserCantAddInPlaceException::class);

        $checker = new KanbanActionsChecker(
            $tracker_factory,
            new \Tuleap\Kanban\KanbanPermissionsManager(),
            $form_element_factory,
            VerifySubmissionPermissionStub::withSubmitPermission(),
        );
        $checker->checkUserCanAddInPlace($user, $kanban);
    }

    public function testItRaisesAnExceptionIfTheUserCannotSubmitArtifact(): void
    {
        $this->field_string->method('isRequired')->willReturn(true);
        $this->field_text->method('isRequired')->willReturn(false);
        $this->field_int->method('isRequired')->willReturn(false);
        $this->field_list->method('isRequired')->willReturn(false);

        $tracker_factory      = RetrieveTrackerStub::withTracker($this->tracker);
        $form_element_factory = RetrieveUsedFieldsStub::withFields(
            $this->field_string,
            $this->field_text,
            $this->field_int,
            $this->field_list,
        );

        $kanban = new Kanban(123, $this->tracker, false, 'My kanban');
        $user   = UserTestBuilder::buildWithDefaults();

        $this->semantic_title->method('getFieldId')->willReturn(201);
        $this->semantic_status->method('getFieldId')->willReturn(202);
        $status_field = $this->createMock(Tracker_FormElement_Field_List::class);
        $status_field->method('userCanSubmit')->willReturn(true);
        $this->semantic_status->method('getField')->willReturn($status_field);

        $this->expectException(KanbanUserCantAddArtifactException::class);

        $checker = new KanbanActionsChecker(
            $tracker_factory,
            new \Tuleap\Kanban\KanbanPermissionsManager(),
            $form_element_factory,
            VerifySubmissionPermissionStub::withoutSubmitPermission(),
        );
        $checker->checkUserCanAddInPlace($user, $kanban);
    }

    public function testItRaisesAnExceptionIfTheUserCannotSubmitStatusField(): void
    {
        $this->field_string->method('isRequired')->willReturn(true);
        $this->field_text->method('isRequired')->willReturn(false);
        $this->field_int->method('isRequired')->willReturn(false);
        $this->field_list->method('isRequired')->willReturn(false);

        $tracker_factory      = RetrieveTrackerStub::withTracker($this->tracker);
        $form_element_factory = RetrieveUsedFieldsStub::withFields(
            $this->field_string,
            $this->field_text,
            $this->field_int,
            $this->field_list,
        );

        $kanban = new Kanban(123, $this->tracker, false, 'My kanban');
        $user   = UserTestBuilder::buildWithDefaults();

        $this->semantic_title->method('getFieldId')->willReturn(201);
        $this->semantic_status->method('getFieldId')->willReturn(202);
        $status_field = $this->createMock(Tracker_FormElement_Field_List::class);
        $status_field->method('userCanSubmit')->willReturn(false);
        $this->semantic_status->method('getField')->willReturn($status_field);

        $this->expectException(KanbanUserCantAddArtifactException::class);

        $checker = new KanbanActionsChecker(
            $tracker_factory,
            new \Tuleap\Kanban\KanbanPermissionsManager(),
            $form_element_factory,
            VerifySubmissionPermissionStub::withSubmitPermission(),
        );
        $checker->checkUserCanAddInPlace($user, $kanban);
    }

    public function testUserCanAdministrate(): void
    {
        $tracker_factory      = RetrieveTrackerStub::withTracker($this->tracker);
        $form_element_factory = RetrieveUsedFieldsStub::withFields(
            $this->field_string,
            $this->field_text,
            $this->field_int,
            $this->field_list,
        );

        $kanban = new Kanban(123, $this->tracker, false, 'My kanban');
        $user   = UserTestBuilder::anActiveUser()
            ->withMemberOf($this->tracker->getProject())
            ->withAdministratorOf($this->tracker->getProject())
            ->build();

        $checker = new KanbanActionsChecker(
            $tracker_factory,
            new \Tuleap\Kanban\KanbanPermissionsManager(),
            $form_element_factory,
            VerifySubmissionPermissionStub::withSubmitPermission(),
        );

        $this->expectNotToPerformAssertions();
        $checker->checkUserCanAdministrate($user, $kanban);
    }

    public function testUserCannotAdministrate(): void
    {
        $tracker_factory      = RetrieveTrackerStub::withTracker($this->tracker);
        $form_element_factory = RetrieveUsedFieldsStub::withFields(
            $this->field_string,
            $this->field_text,
            $this->field_int,
            $this->field_list,
        );

        $kanban = new Kanban(123, $this->tracker, false, 'My kanban');
        $user   = UserTestBuilder::anActiveUser()
            ->withMemberOf($this->tracker->getProject())
            ->build();

        $checker = new KanbanActionsChecker(
            $tracker_factory,
            new \Tuleap\Kanban\KanbanPermissionsManager(),
            $form_element_factory,
            VerifySubmissionPermissionStub::withSubmitPermission(),
        );

        $this->expectException(KanbanUserNotAdminException::class);
        $checker->checkUserCanAdministrate($user, $kanban);
    }
}
