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

use Mockery;
use Tracker_Artifact_Changeset_ChangesetDataInitializator;
use Tracker_Artifact_Changeset_Null;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElementFactory;
use Tracker_Workflow_Transition_InvalidConditionForTransitionException;
use Transition;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValueSaver;
use Tuleap\Tracker\Artifact\XMLImport\TrackerNoXMLImportLoggedConfig;
use Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext;
use Tuleap\Tracker\Test\Stub\RetrieveWorkflowStub;
use Tuleap\Tracker\Test\Stub\SaveArtifactStub;
use Workflow;

final class InitialChangesetCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalResponseMock;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ArtifactChangesetSaver
     */
    private $changeset_saver;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_Selectbox
     */
    private $field;
    /**
     * @var array
     */
    private $fields_data = [];
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $factory;
    /**
     * @var InitialChangesetValueSaver
     */
    private $creator;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Workflow
     */
    private $workflow;
    private SaveArtifactStub $artifact_saver;

    protected function setUp(): void
    {
        $this->factory = \Mockery::spy(\Tracker_FormElementFactory::class);

        $this->artifact_saver  = SaveArtifactStub::withSuccess();
        $this->workflow        = \Mockery::spy(\Workflow::class);
        $this->changeset_saver = Mockery::mock(ArtifactChangesetSaver::class);

        $this->field = \Mockery::spy(\Tracker_FormElement_Field_Selectbox::class);
        $this->field->shouldReceive('getId')->andReturns(123);
        $this->field->shouldReceive('isSubmitable')->andReturns(true);
    }

    private function create(): ?int
    {
        $changeset_factory = \Mockery::spy(\Tracker_Artifact_ChangesetFactory::class);
        $changeset_factory->shouldReceive('getChangeset')->andReturns(new Tracker_Artifact_Changeset_Null());

        $tracker = \Mockery::spy(\Tracker::class)
            ->shouldReceive('getWorkflow')
            ->andReturns($this->workflow)
            ->getMock();
        $tracker->shouldReceive('getId')->andReturns(888);

        $artifact = \Mockery::mock(Artifact::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $artifact->setId(42);
        $artifact->setTracker($tracker);
        $artifact->shouldReceive('getChangesetFactory')->andReturns($changeset_factory);

        $submitter = UserTestBuilder::aUser()->withId(102)->build();

        $fields_validator = \Mockery::spy(\Tracker_Artifact_Changeset_InitialChangesetFieldsValidator::class);
        $fields_validator->shouldReceive('validate')->andReturns(true);
        $fields_retriever = new FieldsToBeSavedInSpecificOrderRetriever($this->factory);

        $workflow_retriever = RetrieveWorkflowStub::withWorkflow($this->workflow);
        $creator            = new InitialChangesetCreator(
            $fields_validator,
            $fields_retriever,
            new Tracker_Artifact_Changeset_ChangesetDataInitializator($this->factory),
            new \Psr\Log\NullLogger(),
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
            Mockery::mock(\Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping::class),
            new TrackerNoXMLImportLoggedConfig(),
            new NullChangesetValidationContext()
        );
    }

    public function testItCallsTheAfterMethodOnWorkflowWhenCreateInitialChangeset(): void
    {
        $this->setFields([]);
        $this->workflow->shouldReceive('validate')->andReturns(true);
        $this->changeset_saver->shouldReceive('saveChangeset')->once()->andReturns(5667);

        $this->workflow->shouldReceive('after')->with($this->fields_data, Mockery::any(), null)->once();

        $this->create();
    }

    public function testItDoesNotCallTheAfterMethodOnWorkflowWhenSaveOfInitialChangesetFails(): void
    {
        $this->setFields([]);
        $this->workflow->shouldReceive('validate')->andReturns(true);
        $this->changeset_saver->shouldReceive('saveChangeset')
            ->once()
            ->andThrows(new \Tracker_Artifact_Exception_CannotCreateNewChangeset());

        $this->workflow->shouldReceive('after')->never();

        $this->create();
    }

    public function testItDoesNotCallTheAfterMethodOnWorkflowWhenSaveOfArtifactFails(): void
    {
        $this->setFields([]);
        $this->artifact_saver = SaveArtifactStub::withFailure();
        $this->workflow->shouldReceive('validate')->andReturns(true);
        $this->changeset_saver->shouldReceive('saveChangeset')->once()->andReturns(123);

        $this->workflow->shouldReceive('after')->never();

        $this->create();
    }

    public function testItDoesNotCreateTheChangesetIfTheWorkflowValidationFailed(): void
    {
        $this->setFields([]);
        $transition = new Transition(
            1,
            $this->workflow->getId(),
            null,
            new \Tracker_FormElement_Field_List_Bind_StaticValue(1, 'field', "", 1, false)
        );

        $this->workflow->shouldReceive('validate')->andThrows(
            new Tracker_Workflow_Transition_InvalidConditionForTransitionException($transition)
        );

        $this->workflow->shouldReceive('after')->never();
        $this->changeset_saver->shouldReceive('saveChangeset')->never();

        $creation = $this->create();

        $this->assertEquals(null, $creation);
    }

    public function testItSavesTheDefaultValueWhenFieldIsSubmittedButCannotSubmit(): void
    {
        $this->setFields([$this->field]);

        $this->field->shouldReceive('userCanSubmit')->andReturns(false);
        $this->field->shouldReceive('getDefaultValue')->andReturns('default value');
        $this->changeset_saver->shouldReceive('saveChangeset')->once()->andReturns(123);

        $this->fields_data[123] = 'value';

        $this->field->shouldReceive('saveNewChangeset')->with(
            \Mockery::any(),
            \Mockery::any(),
            \Mockery::any(),
            'default value',
            \Mockery::any(),
            \Mockery::any(),
            \Mockery::any(),
            \Mockery::any()
        )->once();

        $this->create();
    }

    public function testItIgnoresTheDefaultValueWhenFieldIsSubmittedAndCanSubmit(): void
    {
        $this->setFields([$this->field]);

        $this->field->shouldReceive('userCanSubmit')->andReturns(true);
        $this->field->shouldReceive('getDefaultValue')->andReturns('default value');
        $this->changeset_saver->shouldReceive('saveChangeset')->once()->andReturns(123);

        $this->fields_data[123] = 'value';

        $this->field->shouldReceive('saveNewChangeset')->with(
            \Mockery::any(),
            \Mockery::any(),
            \Mockery::any(),
            'value',
            \Mockery::any(),
            \Mockery::any(),
            \Mockery::any(),
            \Mockery::any()
        )->once();

        $this->create();
    }

    public function testItBypassPermsWhenWorkflowBypassPerms(): void
    {
        $this->setFields([$this->field]);

        $this->field->shouldReceive('userCanSubmit')->andReturns(false);
        $this->field->shouldReceive('getDefaultValue')->andReturns('default value');
        $this->workflow->shouldReceive('bypassPermissions')->with($this->field)->andReturns(true);
        $this->changeset_saver->shouldReceive('saveChangeset')->once()->andReturns(123);

        $this->fields_data[123] = 'value';

        $this->field->shouldReceive('saveNewChangeset')->with(
            \Mockery::any(),
            \Mockery::any(),
            \Mockery::any(),
            'value',
            \Mockery::any(),
            \Mockery::any(),
            \Mockery::any(),
            \Mockery::any()
        )->once();

        $this->create();
    }

    private function setFields(array $fields): void
    {
        $this->factory->shouldReceive('getAllFormElementsForTracker')->andReturns($fields);
        $this->factory->shouldReceive('getUsedFields')->andReturns($fields);
    }
}
