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
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Changeset\Validation\ChangesetWithFieldsValidationContext;
use Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext;
use Tuleap\Tracker\FormElement\ArtifactLinkValidator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Validation\ManualActionContext;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Workflow\WorkflowUpdateChecker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Artifact_Changeset_NewChangesetFieldsValidatorTest extends TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Tracker_Artifact_Changeset_NewChangesetFieldsValidator $new_changeset_fields_validator;

    private MockObject&ArtifactLinkValidator $artifact_link_validator;

    private MockObject&Tracker_FormElementFactory $factory;

    private MockObject&Workflow $workflow;

    private MockObject&Artifact $artifact;

    private MockObject&Tracker_FormElement_Field $field1;

    private MockObject&Tracker_FormElement_Field $field2;

    private MockObject&Tracker_FormElement_Field $field3;

    private MockObject&Tracker_Artifact_Changeset $changeset;

    private MockObject&Tracker_Artifact_ChangesetValue $changeset_value1;

    private MockObject&Tracker_Artifact_ChangesetValue $changeset_value2;

    private MockObject&Tracker_Artifact_ChangesetValue $changeset_value3;

    private MockObject&WorkflowUpdateChecker $workflow_checker;

    protected function setUp(): void
    {
        $this->factory                 = $this->createMock(Tracker_FormElementFactory::class);
        $this->artifact_link_validator = $this->createMock(ArtifactLinkValidator::class);
        $this->workflow_checker        = $this->createMock(WorkflowUpdateChecker::class);
        $this->workflow_checker->method('canFieldBeUpdated')->willReturn(true);
        $this->new_changeset_fields_validator = new Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
            $this->factory,
            $this->artifact_link_validator,
            $this->workflow_checker
        );

        $this->field1 = $this->getFieldWithId(101);
        $this->field2 = $this->getFieldWithId(102);
        $this->field3 = $this->getFieldWithId(103);

        $this->factory->method('getAllFormElementsForTracker')->willReturn([]);

        $this->workflow = $this->createMock(Workflow::class);

        $this->changeset        = $this->createMock(Tracker_Artifact_Changeset::class);
        $this->changeset_value1 = $this->createMock(Tracker_Artifact_ChangesetValue::class);
        $this->changeset_value2 = $this->createMock(Tracker_Artifact_ChangesetValue::class);
        $this->changeset_value3 = $this->createMock(Tracker_Artifact_ChangesetValue::class);

        $this->artifact = $this->createMock(Artifact::class);
        $this->artifact->method('getTracker')->willReturn(TrackerTestBuilder::aTracker()->build());
        $this->artifact->method('getWorkflow')->willReturn($this->workflow);
        $this->artifact->method('getLastChangeset')->willReturn($this->changeset);
    }

    private function getFieldWithId(int $id): MockObject&Tracker_FormElement_Field
    {
        $field = $this->createMock(Tracker_FormElement_Field::class);
        $field->method('getId')->willReturn($id);

        return $field;
    }

    public function testValidateUpdateFieldSubmitted(): void
    {
        $this->field1->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);
        $this->field2->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);
        $this->field3->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);

        $this->factory->method('getUsedFields')->willReturn([$this->field1]);

        $this->changeset->method('getValue')->with($this->field1)->willReturn($this->changeset_value1);

        $user        = UserTestBuilder::buildWithDefaults();
        $fields_data = ['101' => 666];
        self::assertTrue(
            $this->new_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new NullChangesetValidationContext()
            )
        );
        self::assertNotNull($fields_data[101]);
        self::assertEquals(666, $fields_data[101]);
    }

    public function testValidateUpdateFieldNotSubmitted(): void
    {
        $this->factory->method('getUsedFields')->willReturn([]);

        $user        = UserTestBuilder::buildWithDefaults();
        $fields_data = [];
        self::assertTrue(
            $this->new_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new NullChangesetValidationContext()
            )
        );
        self::assertFalse(isset($fields_data[101]));
    }

    public function testValidateFieldsMissingFieldsOnUpdate(): void
    {
        $this->field1->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);
        $this->field2->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);
        $this->field3->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);

        $this->factory->method('getUsedFields')
            ->willReturn([$this->field1, $this->field2, $this->field3]);
        $matcher = $this->exactly(3);

        $this->changeset->expects($matcher)->method('getValue')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($this->field1, $parameters[0]);
                return $this->changeset_value1;
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($this->field2, $parameters[0]);
                return $this->changeset_value2;
            }
            if ($matcher->numberOfInvocations() === 3) {
                self::assertSame($this->field3, $parameters[0]);
                return $this->changeset_value3;
            }
        });

        $user = UserTestBuilder::buildWithDefaults();
        // field 102 is missing
        $fields_data = [
            '101' => 'foo',
            '103' => 'bar',
        ];
        self::assertTrue(
            $this->new_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new NullChangesetValidationContext()
            )
        );
        self::assertFalse(isset($fields_data[102]));
    }

    public function testValidateFieldsMissingFieldsInPreviousChangesetOnUpdate(): void
    {
        $this->field1->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);
        $this->field2->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);
        $this->field3->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);

        $this->factory->method('getUsedFields')
            ->willReturn([$this->field1, $this->field2, $this->field3]);
        $matcher = $this->exactly(3);

        $this->changeset->expects($matcher)->method('getValue')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($this->field1, $parameters[0]);
                return $this->changeset_value1;
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($this->field2, $parameters[0]);
                return null;
            }
            if ($matcher->numberOfInvocations() === 3) {
                self::assertSame($this->field3, $parameters[0]);
                return $this->changeset_value3;
            }
        });

        $user = UserTestBuilder::buildWithDefaults();
        // field 102 is missing
        $fields_data = [
            '101' => 'foo',
            '103' => 'bar',
        ];
        self::assertTrue(
            $this->new_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new NullChangesetValidationContext()
            )
        );
        self::assertFalse(isset($fields_data[102]));
    }

    public function testItValidatesArtifactLinkField(): void
    {
        $this->artifact_link_validator->expects($this->once())->method('isValid')->willReturn(true);

        $artifact_link_field = $this->createMock(ArtifactLinkField::class);
        $artifact_link_field->method('getId')->willReturn(101);
        $artifact_link_field->method('userCanUpdate')->willReturn(true);
        $artifact_link_field->method('validateFieldWithPermissionsAndRequiredStatus')->willReturn(true);

        $this->changeset->method('getValue')->with($artifact_link_field)->willReturn($this->changeset_value1);

        $factory = $this->createMock(Tracker_FormElementFactory::class);
        $factory->method('getUsedFields')
            ->willReturn([$artifact_link_field]);

        $new_changeset_fields_validator = new Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
            $factory,
            $this->artifact_link_validator,
            $this->workflow_checker
        );

        $user        = UserTestBuilder::aUser()->build();
        $fields_data = [
            '101' => ['new_values' => '184'],
        ];
        $context     = new ChangesetWithFieldsValidationContext(new ManualActionContext());
        self::assertTrue(
            $new_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                $context
            )
        );
    }

    public function testItValidatesArtifactLinkFieldWhenNoChangesAndNoSubmitPermission(): void
    {
        $this->artifact_link_validator->expects($this->once())->method('isValid')->willReturn(true);

        $user                = UserTestBuilder::aUser()->build();
        $artifact_link_field = $this->createPartialMock(ArtifactLinkField::class, [
            'getId', 'userCanUpdate', 'hasChanges', 'canEditReverseLinks',
        ]);
        $artifact_link_field->method('getId')->willReturn(101);
        $artifact_link_field->expects($this->once())->method('canEditReverseLinks')->willReturn(true);
        $artifact_link_field->expects($this->once())->method('hasChanges')->willReturn(false);
        $artifact_link_field->expects($this->once())->method('userCanUpdate')->willReturn(false);

        $this->changeset->method('getValue')->with($artifact_link_field)->willReturn($this->changeset_value1);

        $factory = $this->createMock(Tracker_FormElementFactory::class);
        $factory->method('getUsedFields')->willReturn([$artifact_link_field]);

        $new_changeset_fields_validator = new Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
            $factory,
            $this->artifact_link_validator,
            $this->workflow_checker
        );

        $fields_data = [
            '101' => ['new_values' => '184'],
        ];
        $context     = new ChangesetWithFieldsValidationContext(new ManualActionContext());
        self::assertTrue(
            $new_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                $context
            )
        );
    }
}
