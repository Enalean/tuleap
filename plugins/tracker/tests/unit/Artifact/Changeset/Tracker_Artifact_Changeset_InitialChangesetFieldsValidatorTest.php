<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Changeset\Validation\ChangesetWithFieldsValidationContext;
use Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext;
use Tuleap\Tracker\FormElement\ArtifactLinkValidator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Validation\ManualActionContext;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Workflow\WorkflowUpdateChecker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Artifact_Changeset_InitialChangesetFieldsValidatorTest extends TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use GlobalResponseMock;

    private Tracker_Artifact_Changeset_InitialChangesetFieldsValidator $initial_changeset_fields_validator;

    private MockObject&Tracker_FormElementFactory $factory;

    private MockObject&ArtifactLinkValidator $artifact_link_validator;

    private MockObject&Workflow $workflow;

    private MockObject&Artifact $artifact;

    protected function setUp(): void
    {
        $this->factory                 = $this->createMock(Tracker_FormElementFactory::class);
        $this->artifact_link_validator = $this->createMock(ArtifactLinkValidator::class);
        $workflow_checker              = $this->createMock(WorkflowUpdateChecker::class);
        $workflow_checker->method('canFieldBeUpdated')->willReturn(true);
        $this->initial_changeset_fields_validator = new Tracker_Artifact_Changeset_InitialChangesetFieldsValidator(
            $this->factory,
            $this->artifact_link_validator,
        );

        $this->factory->method('getAllFormElementsForTracker')->willReturn([]);

        $this->workflow = $this->createMock(Workflow::class);
        $this->workflow->method('validate');

        $this->artifact = $this->createMock(Artifact::class);
        $this->artifact->method('getTracker')->willReturn(TrackerTestBuilder::aTracker()->build());
        $this->artifact->method('getWorkflow')->willReturn($this->workflow);
        $this->artifact->method('getLastChangeset')->willReturn(new Tracker_Artifact_Changeset_Null());
    }

    private function getFieldWithId(int $id, bool $can_submit, bool $can_update, bool $is_valid): MockObject&TrackerField
    {
        $field = $this->createMock(TrackerField::class);
        $field->method('getId')->willReturn($id);
        $field->method('userCanUpdate')->willReturn($can_update);
        $field->method('userCanSubmit')->willReturn($can_submit);
        $field->method('isValid')->willReturn($is_valid);
        $field->method('isRequired')->willReturn(true);
        return $field;
    }

    public function testValidateFieldsBasicValid(): void
    {
        $field1 = $this->getFieldWithId(101, false, false, true);
        $field2 = $this->getFieldWithId(102, false, false, true);
        $field3 = $this->getFieldWithId(103, false, false, true);

        $this->factory->method('getUsedFields')->willReturn([$field1, $field2, $field3]);

        $user        = UserTestBuilder::aUser()->build();
        $fields_data = [];
        self::assertTrue(
            $this->initial_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new NullChangesetValidationContext()
            )
        );
        self::assertNotNull($fields_data);
        self::assertFalse(isset($fields_data[101]));
        self::assertFalse(isset($fields_data[102]));
        self::assertFalse(isset($fields_data[103]));
    }

    public function testValidateSubmitFieldNotRequired(): void
    {
        $field1 = $this->getFieldWithId(101, true, false, true);
        $field2 = $this->getFieldWithId(102, false, false, true);
        $field3 = $this->getFieldWithId(103, false, false, true);

        $field1->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);

        $this->factory->method('getUsedFields')->willReturn([$field1, $field2, $field3]);

        $user        = UserTestBuilder::aUser()->build();
        $fields_data = ['101' => 444];
        self::assertTrue(
            $this->initial_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new NullChangesetValidationContext()
            )
        );
        self::assertNotNull($fields_data[101]);
        self::assertEquals(444, $fields_data[101]);
    }

    public function testValidateSubmitFieldNotRequiredNotSubmittedDefaultValue(): void
    {
        $field1 = $this->getFieldWithId(101, true, false, true);
        $field1->method('hasDefaultValue')->willReturn(true);
        $field1->method('getDefaultValue')->willReturn('DefaultValue');
        $field1->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);

        $field2 = $this->getFieldWithId(102, false, false, true);
        $field3 = $this->getFieldWithId(103, false, false, true);

        $this->factory->method('getUsedFields')->willReturn([$field1, $field2, $field3]);
        $user        = UserTestBuilder::aUser()->build();
        $fields_data = [];
        self::assertTrue(
            $this->initial_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new NullChangesetValidationContext()
            )
        );
        self::assertFalse(isset($fields_data[101]));
    }

    public function testValidateSubmitFieldNotRequiredNotSubmittedNoDefaultValue(): void
    {
        $field1 = $this->getFieldWithId(101, true, false, true);
        $field2 = $this->getFieldWithId(102, false, false, true);
        $field3 = $this->getFieldWithId(103, false, false, true);

        $field1->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);

        $this->factory->method('getUsedFields')->willReturn([$field1, $field2, $field3]);

        $user        = UserTestBuilder::aUser()->build();
        $fields_data = [];
        self::assertTrue(
            $this->initial_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new NullChangesetValidationContext()
            )
        );
        self::assertFalse(isset($fields_data[101]));
    }

    public function testValidateSubmitFieldRequired(): void
    {
        $field1 = $this->getFieldWithId(101, true, false, true);
        $field1->method('isRequired')->willReturn(true);
        $field2 = $this->getFieldWithId(102, false, false, true);
        $field3 = $this->getFieldWithId(103, false, false, true);

        $field1->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);

        $this->factory->method('getUsedFields')->willReturn([$field1, $field2, $field3]);

        $user        = UserTestBuilder::aUser()->build();
        $fields_data = ['101' => 666];
        self::assertTrue(
            $this->initial_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new NullChangesetValidationContext()
            )
        );
        self::assertNotNull($fields_data[101]);
        self::assertEquals(666, $fields_data[101]);
    }

    public function testValidateSubmitFieldRequiredNotSubmittedDefaultValue(): void
    {
        $field1 = $this->getFieldWithId(101, true, false, true);
        $field1->method('isRequired')->willReturn(true);
        $field1->method('hasDefaultValue')->willReturn(true);
        $field1->method('getDefaultValue')->willReturn('DefaultValue');
        $field1->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(false);

        $field2 = $this->getFieldWithId(102, false, false, true);
        $field3 = $this->getFieldWithId(103, false, false, true);

        $this->factory->method('getUsedFields')->willReturn([$field1, $field2, $field3]);
        $GLOBALS['Response']->method('addFeedback')->with(Feedback::ERROR);

        $user        = UserTestBuilder::aUser()->build();
        $fields_data = [];
        self::assertFalse(
            $this->initial_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new NullChangesetValidationContext()
            )
        );
        self::assertFalse(isset($fields_data[101]));
    }

    public function testValidateSubmitFieldRequiredNotSubmittedNoDefaultValue(): void
    {
        $field1 = $this->getFieldWithId(101, true, false, false);
        $field1->method('hasDefaultValue')->willReturn(false);
        $field1->method('isRequired')->willReturn(true);
        $field1->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(false);

        $field2 = $this->getFieldWithId(102, false, false, true);
        $field3 = $this->getFieldWithId(103, false, false, true);

        $this->factory->method('getUsedFields')->willReturn([$field1, $field2, $field3]);

        $GLOBALS['Response']->method('addFeedback')->with(Feedback::ERROR);

        $user        = UserTestBuilder::aUser()->build();
        $fields_data = [];
        self::assertFalse(
            $this->initial_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new NullChangesetValidationContext()
            )
        );
    }

    public function testValidateFieldsMissingFieldsOnSubmission(): void
    {
        $field1 = $this->getFieldWithId(101, true, false, true);
        $field2 = $this->getFieldWithId(102, true, false, true);
        $field3 = $this->getFieldWithId(103, true, false, true);

        $field1->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);
        $field2->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);
        $field3->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);

        $this->factory->method('getUsedFields')->willReturn([$field1, $field2, $field3]);

        $user = UserTestBuilder::aUser()->build();
        // field 101 and 102 are missing
        // 101 has a default value
        // 102 has no default value
        $fields_data = ['103' => 444];
        self::assertTrue(
            $this->initial_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new NullChangesetValidationContext()
            )
        );
        self::assertFalse(isset($fields_data[101]));
        self::assertFalse(isset($fields_data[102]));
        self::assertNotNull($fields_data[103]);
        self::assertEquals(444, $fields_data[103]);
    }

    public function testValidateFieldsBasicNotValid(): void
    {
        $field1 = $this->getFieldWithId(101, false, false, false);
        $field2 = $this->getFieldWithId(102, true, false, true);
        $field3 = $this->getFieldWithId(103, true, false, true);

        $field1->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(false);
        $field2->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(false);
        $field3->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(false);

        $this->factory->method('getUsedFields')->willReturn([$field1, $field2, $field3]);

        $user = UserTestBuilder::aUser()->build();
        // field 102 is missing
        $fields_data = [];
        self::assertFalse(
            $this->initial_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new NullChangesetValidationContext()
            )
        );
        self::assertFalse(isset($fields_data[101]));
        self::assertFalse(isset($fields_data[102]));
        self::assertFalse(isset($fields_data[103]));
    }

    public function testItValidatesArtifactLinkField(): void
    {
        $this->artifact_link_validator->expects($this->once())->method('isValid')->willReturn(true);

        $artifact_link_field = $this->createMock(ArtifactLinkField::class);
        $artifact_link_field->method('getId')->willReturn(101);
        $artifact_link_field->method('userCanUpdate')->willReturn(true);
        $artifact_link_field->method('isRequired')->willReturn(false);
        $artifact_link_field->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);
        $this->factory->method('getUsedFields')->willReturn([$artifact_link_field]);

        $user        = UserTestBuilder::aUser()->build();
        $fields_data = [
            '101' => ['new_values' => '184'],
        ];
        $context     = new ChangesetWithFieldsValidationContext(new ManualActionContext());
        self::assertTrue(
            $this->initial_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                $context
            )
        );
    }
}
