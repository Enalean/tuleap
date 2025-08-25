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

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Changeset;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tracker_Artifact_Changeset_ChangesetDataInitializator;
use Tracker_Artifact_Changeset_InitialChangesetFieldsValidator;
use Tracker_Artifact_Changeset_Null;
use Tracker_Artifact_ChangesetFactory;
use Tracker_Artifact_Exception_CannotCreateNewChangeset;
use Tracker_FormElementFactory;
use Tracker_Workflow_Transition_InvalidConditionForTransitionException;
use Transition;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValueSaver;
use Tuleap\Tracker\Artifact\XMLImport\TrackerNoXMLImportLoggedConfig;
use Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext;
use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\List\SelectboxField;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveWorkflowStub;
use Tuleap\Tracker\Test\Stub\SaveArtifactStub;
use Workflow;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class InitialChangesetCreatorTest extends TestCase
{
    use GlobalResponseMock;

    private MockObject&ArtifactChangesetSaver $changeset_saver;
    private MockObject&SelectboxField $field;
    private array $fields_data = [];
    private MockObject&Tracker_FormElementFactory $factory;
    private InitialChangesetValueSaver $creator;
    private MockObject&Workflow $workflow;
    private SaveArtifactStub $artifact_saver;

    protected function setUp(): void
    {
        $this->factory = $this->createMock(Tracker_FormElementFactory::class);

        $this->artifact_saver = SaveArtifactStub::withSuccess();
        $this->workflow       = $this->createMock(Workflow::class);
        $this->workflow->method('getId')->willReturn(10);
        $this->changeset_saver = $this->createMock(ArtifactChangesetSaver::class);

        $this->field = $this->createMock(SelectboxField::class);
        $this->field->method('getId')->willReturn(123);
        $this->field->method('isSubmitable')->willReturn(true);
    }

    private function create(): ?int
    {
        $changeset_factory = $this->createMock(Tracker_Artifact_ChangesetFactory::class);
        $changeset_factory->method('getChangeset')->willReturn(new Tracker_Artifact_Changeset_Null());

        $tracker = TrackerTestBuilder::aTracker()->withId(888)->withWorkflow($this->workflow)->build();

        $artifact = $this->createPartialMock(Artifact::class, ['getChangesetFactory']);
        $artifact->setId(42);
        $artifact->setTracker($tracker);
        $artifact->method('getChangesetFactory')->willReturn($changeset_factory);

        $submitter = UserTestBuilder::aUser()->withId(102)->build();

        $fields_validator = $this->createMock(Tracker_Artifact_Changeset_InitialChangesetFieldsValidator::class);
        $fields_validator->method('validate')->willReturn(true);
        $fields_retriever = new FieldsToBeSavedInSpecificOrderRetriever($this->factory);

        $workflow_retriever = RetrieveWorkflowStub::withWorkflow($this->workflow);
        $creator            = new InitialChangesetCreator(
            $fields_validator,
            $fields_retriever,
            new Tracker_Artifact_Changeset_ChangesetDataInitializator($this->factory),
            new NullLogger(),
            $this->changeset_saver,
            new AfterNewChangesetHandler(
                $this->artifact_saver,
                $fields_retriever
            ),
            $workflow_retriever,
            new InitialChangesetValueSaver(),
        );

        return $creator->create(
            $artifact,
            $this->fields_data,
            $submitter,
            1234567890,
            $this->createMock(CreatedFileURLMapping::class),
            new TrackerNoXMLImportLoggedConfig(),
            new NullChangesetValidationContext()
        );
    }

    public function testItCallsTheAfterMethodOnWorkflowWhenCreateInitialChangeset(): void
    {
        $this->setFields([]);
        $this->workflow->expects($this->once())->method('before');
        $this->workflow->expects($this->once())->method('checkGlobalRules');

        $this->workflow->expects($this->once())->method('isDisabled')->willReturn(false);
        $this->workflow->expects($this->once())->method('validate');

        $this->changeset_saver->expects($this->once())->method('saveChangeset')->willReturn(5667);

        $this->workflow->expects($this->once())->method('after')->with($this->fields_data, self::anything(), null);

        $this->create();
    }

    public function testItDoesNotCallTheAfterMethodOnWorkflowWhenSaveOfInitialChangesetFails(): void
    {
        $this->setFields([]);
        $this->workflow->expects($this->once())->method('validate');
        $this->workflow->expects($this->once())->method('isDisabled')->willReturn(false);
        $this->workflow->expects($this->once())->method('before');
        $this->workflow->expects($this->once())->method('checkGlobalRules');

        $this->changeset_saver->expects($this->once())->method('saveChangeset')->willThrowException(
            new Tracker_Artifact_Exception_CannotCreateNewChangeset()
        );

        $this->workflow->expects($this->never())->method('after');

        $this->create();
    }

    public function testItDoesNotCallTheAfterMethodOnWorkflowWhenSaveOfArtifactFails(): void
    {
        $this->setFields([]);
        $this->artifact_saver = SaveArtifactStub::withFailure();
        $this->workflow->expects($this->once())->method('validate');
        $this->workflow->expects($this->once())->method('isDisabled')->willReturn(false);
        $this->workflow->expects($this->once())->method('before');
        $this->workflow->expects($this->once())->method('checkGlobalRules');

        $this->changeset_saver->expects($this->once())->method('saveChangeset')->willReturn(123);

        $this->workflow->expects($this->never())->method('after');

        $this->create();
    }

    public function testItDoesNotCreateTheChangesetIfTheWorkflowValidationFailed(): void
    {
        $this->setFields([]);
        $transition = new Transition(
            1,
            $this->workflow->getId(),
            null,
            ListStaticValueBuilder::aStaticValue('field')->build()
        );

        $this->workflow->method('validate')->willThrowException(
            new Tracker_Workflow_Transition_InvalidConditionForTransitionException($transition)
        );

        $this->workflow->expects($this->once())->method('isDisabled')->willReturn(false);
        $this->workflow->expects($this->never())->method('after');
        $this->changeset_saver->expects($this->never())->method('saveChangeset');
        $creation = $this->create();

        self::assertNull($creation);
    }

    public function testItSavesTheDefaultValueWhenFieldIsSubmittedButCannotSubmit(): void
    {
        $this->workflow->expects($this->once())->method('isDisabled')->willReturn(false);
        $this->workflow->expects($this->once())->method('validate');
        $this->workflow->expects($this->once())->method('before');
        $this->workflow->expects($this->once())->method('checkGlobalRules');
        $this->workflow->expects($this->once())->method('bypassPermissions')->willReturn(true);
        $this->workflow->expects($this->once())->method('after');


        $this->setFields([$this->field]);

        $this->field->method('userCanSubmit')->willReturn(false);
        $this->field->method('getDefaultValue')->willReturn('default value');
        $this->field->expects($this->once())->method('postSaveNewChangeset');
        $this->changeset_saver->expects($this->once())->method('saveChangeset')->willReturn(123);

        $this->fields_data[123] = 'value';

        $this->field->expects($this->once())->method('saveNewChangeset')->willReturn(
            self::anything(),
            self::anything(),
            self::anything(),
            'default value',
            self::anything(),
            self::anything(),
            self::anything(),
            self::anything()
        );

        $this->create();
    }

    public function testItIgnoresTheDefaultValueWhenFieldIsSubmittedAndCanSubmit(): void
    {
        $this->workflow->expects($this->once())->method('isDisabled')->willReturn(false);
        $this->workflow->expects($this->once())->method('validate');
        $this->workflow->expects($this->once())->method('before');
        $this->workflow->expects($this->once())->method('checkGlobalRules');
        $this->workflow->expects($this->once())->method('after');

        $this->setFields([$this->field]);

        $this->field->method('userCanSubmit')->willReturn(true);
        $this->field->method('getDefaultValue')->willReturn('default value');
        $this->field->expects($this->once())->method('postSaveNewChangeset');
        $this->changeset_saver->expects($this->once())->method('saveChangeset')->willReturn(123);

        $this->fields_data[123] = 'value';

        $this->field->expects($this->once())->method('saveNewChangeset')->with(
            self::anything(),
            self::anything(),
            self::anything(),
            'value',
            self::anything(),
            self::anything(),
            self::anything(),
            self::anything()
        );

        $this->create();
    }

    public function testItBypassPermsWhenWorkflowBypassPerms(): void
    {
        $this->workflow->expects($this->once())->method('isDisabled')->willReturn(false);
        $this->workflow->expects($this->once())->method('validate');
        $this->workflow->expects($this->once())->method('before');
        $this->workflow->expects($this->once())->method('checkGlobalRules');
        $this->workflow->expects($this->once())->method('after');

        $this->setFields([$this->field]);

        $this->field->method('userCanSubmit')->willReturn(false);
        $this->field->method('getDefaultValue')->willReturn('default value');
        $this->workflow->method('bypassPermissions')->with($this->field)->willReturn(true);
        $this->field->expects($this->once())->method('postSaveNewChangeset');
        $this->changeset_saver->expects($this->once())->method('saveChangeset')->willReturn(123);

        $this->fields_data[123] = 'value';

        $this->field->expects($this->once())->method('saveNewChangeset')->with(
            self::anything(),
            self::anything(),
            self::anything(),
            'value',
            self::anything(),
            self::anything(),
            self::anything(),
            self::anything()
        );

        $this->create();
    }

    public function testItBypassPermissionsWhenWorkflowIsDisabled(): void
    {
        $this->workflow->method('isDisabled')->willReturn(true);
        $this->workflow->expects($this->once())->method('after');
        $this->workflow->expects($this->never())->method('validate');
        $this->workflow->expects($this->never())->method('before');
        $this->workflow->expects($this->never())->method('checkGlobalRules');

        $this->setFields([$this->field]);

        $this->field->method('userCanSubmit')->willReturn(true);
        $this->field->method('getDefaultValue')->willReturn('default value');
        $this->changeset_saver->expects($this->once())->method('saveChangeset')->willReturn(123);

        $this->fields_data[123] = 'value';

        $this->field->expects($this->once())->method('postSaveNewChangeset');
        $this->field->expects($this->once())->method('saveNewChangeset')->with(
            self::anything(),
            self::anything(),
            self::anything(),
            'value',
            self::anything(),
            self::anything(),
            self::anything(),
            self::anything()
        );

        $this->create();
    }

    private function setFields(array $fields): void
    {
        $this->factory->method('getAllFormElementsForTracker')->willReturn($fields);
        $this->factory->method('getUsedFields')->willReturn($fields);
    }
}
