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

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus;
use Tuleap\Tracker\Semantic\Title\RetrieveSemanticTitleField;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;
use Tuleap\Tracker\Test\Stub\Semantic\Status\RetrieveSemanticStatusStub;
use Tuleap\Tracker\Test\Stub\Semantic\Title\RetrieveSemanticTitleFieldStub;
use Tuleap\Tracker\Test\Stub\VerifySubmissionPermissionStub;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class KanbanActionsCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const int STRING_FIELD_ID = 201;
    private const int LIST_FIELD_ID   = 40;
    private StringField $field_string;
    private ListField $field_list;
    private Tracker $tracker;
    private RetrieveSemanticTitleField $title_field_retriever;
    private RetrieveSemanticStatusStub $semantic_status_retriever;
    private VerifySubmissionPermissionStub $verify_submission_permissions;
    private \PFUser $user;

    #[\Override]
    protected function setUp(): void
    {
        $project            = ProjectTestBuilder::aProject()->build();
        $this->tracker      = TrackerTestBuilder::aTracker()->withId(888)->withProject($project)->build();
        $this->field_string = StringFieldBuilder::aStringField(self::STRING_FIELD_ID)
            ->inTracker($this->tracker)
            ->build();
        $this->field_list   = SelectboxFieldBuilder::aSelectboxField(self::LIST_FIELD_ID)
            ->inTracker($this->tracker)
            ->build();

        $this->title_field_retriever     = RetrieveSemanticTitleFieldStub::build();
        $this->semantic_status_retriever = RetrieveSemanticStatusStub::build();

        $this->verify_submission_permissions = VerifySubmissionPermissionStub::withSubmitPermission();

        $this->user = UserTestBuilder::anActiveUser()
            ->withMemberOf($this->tracker->getProject())
            ->withAdministratorOf($this->tracker->getProject())
            ->build();
    }

    /**
     * @throws KanbanUserCantAddArtifactException
     * @throws KanbanSemanticTitleNotDefinedException
     * @throws KanbanUserCantAddInPlaceException
     * @throws KanbanTrackerNotDefinedException
     */
    private function checkUserCanAddInPlace(): void
    {
        $field_text = TextFieldBuilder::aTextField(20)
            ->inTracker($this->tracker)
            ->build();
        $field_int  = IntegerFieldBuilder::anIntField(30)
            ->inTracker($this->tracker)
            ->build();

        $form_element_factory = RetrieveUsedFieldsStub::withFields(
            $this->field_string,
            $field_text,
            $field_int,
            $this->field_list,
        );

        $kanban  = new Kanban(123, $this->tracker, false, 'My kanban');
        $user    = UserTestBuilder::buildWithDefaults();
        $checker = new KanbanActionsChecker(
            RetrieveTrackerStub::withTracker($this->tracker),
            new \Tuleap\Kanban\KanbanPermissionsManager(),
            $form_element_factory,
            $this->verify_submission_permissions,
            $this->title_field_retriever,
            $this->semantic_status_retriever,
        );
        $checker->checkUserCanAddInPlace($user, $kanban);
    }

    public function testItRaisesAnExceptionIfAnotherFieldIsRequired(): void
    {
        $this->field_string = StringFieldBuilder::aStringField(self::STRING_FIELD_ID)
            ->inTracker($this->tracker)
            ->thatIsRequired()
            ->build();
        $this->field_list   = SelectboxFieldBuilder::aSelectboxField(self::LIST_FIELD_ID)
            ->inTracker($this->tracker)
            ->thatIsRequired()
            ->build();

        $this->title_field_retriever = RetrieveSemanticTitleFieldStub::build()->withTitleField($this->field_string);
        $status_field                = $this->createMock(ListField::class);
        $status_field->method('userCanSubmit')->willReturn(true);
        $this->semantic_status_retriever->withSemanticStatus(new TrackerSemanticStatus($this->tracker, $status_field));

        $this->expectException(\Tuleap\Kanban\KanbanUserCantAddInPlaceException::class);
        $this->checkUserCanAddInPlace();
    }

    public function testItRaisesAnExceptionIfNoSemanticTitle(): void
    {
        $this->field_string = StringFieldBuilder::aStringField(self::STRING_FIELD_ID)
            ->inTracker($this->tracker)
            ->thatIsRequired()
            ->build();

        $this->title_field_retriever = RetrieveSemanticTitleFieldStub::build();
        $status_field                = $this->createMock(ListField::class);
        $status_field->method('userCanSubmit')->willReturn(true);
        $this->semantic_status_retriever->withSemanticStatus(new TrackerSemanticStatus($this->tracker, $status_field));

        $this->expectException(\Tuleap\Kanban\KanbanSemanticTitleNotDefinedException::class);
        $this->checkUserCanAddInPlace();
    }

    public function testItRaisesAnExceptionIfTheMandatoryFieldIsNotTheSemanticTitle(): void
    {
        $this->field_list = SelectboxFieldBuilder::aSelectboxField(self::LIST_FIELD_ID)
            ->inTracker($this->tracker)
            ->thatIsRequired()
            ->build();

        $this->title_field_retriever = RetrieveSemanticTitleFieldStub::build()->withTitleField($this->field_string);
        $status_field                = $this->createMock(ListField::class);
        $status_field->method('userCanSubmit')->willReturn(true);
        $this->semantic_status_retriever->withSemanticStatus(new TrackerSemanticStatus($this->tracker, $status_field));

        $this->expectException(\Tuleap\Kanban\KanbanUserCantAddInPlaceException::class);
        $this->checkUserCanAddInPlace();
    }

    public function testItRaisesAnExceptionIfTheUserCannotSubmitArtifact(): void
    {
        $this->field_string                  = StringFieldBuilder::aStringField(self::STRING_FIELD_ID)
            ->inTracker($this->tracker)
            ->thatIsRequired()
            ->build();
        $this->verify_submission_permissions = VerifySubmissionPermissionStub::withoutSubmitPermission();

        $this->title_field_retriever = RetrieveSemanticTitleFieldStub::build()->withTitleField($this->field_string);
        $status_field                = $this->createMock(ListField::class);
        $status_field->method('userCanSubmit')->willReturn(true);
        $this->semantic_status_retriever->withSemanticStatus(new TrackerSemanticStatus($this->tracker, $status_field));

        $this->expectException(KanbanUserCantAddArtifactException::class);
        $this->checkUserCanAddInPlace();
    }

    public function testItRaisesAnExceptionIfTheUserCannotSubmitStatusField(): void
    {
        $this->field_string = StringFieldBuilder::aStringField(self::STRING_FIELD_ID)
            ->inTracker($this->tracker)
            ->thatIsRequired()
            ->build();

        $this->title_field_retriever = RetrieveSemanticTitleFieldStub::build()->withTitleField($this->field_string);
        $status_field                = $this->createMock(ListField::class);
        $status_field->method('userCanSubmit')->willReturn(false);
        $this->semantic_status_retriever->withSemanticStatus(new TrackerSemanticStatus($this->tracker, $status_field));

        $this->expectException(KanbanUserCantAddArtifactException::class);
        $this->checkUserCanAddInPlace();
    }

    /**
     * @throws KanbanUserNotAdminException
     */
    private function checkUserCanAdministrate(): void
    {
        $form_element_factory = RetrieveUsedFieldsStub::withFields(
            $this->field_string,
            $this->field_list,
        );

        $kanban  = new Kanban(123, $this->tracker, false, 'My kanban');
        $checker = new KanbanActionsChecker(
            RetrieveTrackerStub::withTracker($this->tracker),
            new \Tuleap\Kanban\KanbanPermissionsManager(),
            $form_element_factory,
            VerifySubmissionPermissionStub::withSubmitPermission(),
            $this->title_field_retriever,
            $this->semantic_status_retriever,
        );
        $checker->checkUserCanAdministrate($this->user, $kanban);
    }

    public function testUserCanAdministrate(): void
    {
        $this->expectNotToPerformAssertions();
        $this->checkUserCanAdministrate();
    }

    public function testUserCannotAdministrate(): void
    {
        $this->user = UserTestBuilder::anActiveUser()
            ->withMemberOf($this->tracker->getProject())
            ->build();

        $this->expectException(KanbanUserNotAdminException::class);
        $this->checkUserCanAdministrate();
    }
}
