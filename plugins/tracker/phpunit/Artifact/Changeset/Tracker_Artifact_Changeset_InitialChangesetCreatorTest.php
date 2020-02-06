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

use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;

class Tracker_Artifact_Changeset_InitialChangesetCreatorTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, \Tuleap\GlobalLanguageMock;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_Selectbox
     */
    private $field;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_ChangesetFactory
     */
    private $changeset_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping
     */
    private $url_mapping;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $submitter;
    /**
     * @var \Mockery\Mock|Tracker_Artifact
     */
    private $artifact;
    /**
     * @var array
     */
    private $fields_data = [];
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $factory;
    /**
     * @var mixed
     */
    private $submitted_on;
    /**
     * @var Tracker_Artifact_Changeset_InitialChangesetCreator
     */
    private $creator;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Workflow
     */
    private $workflow;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_ChangesetDao
     */
    private $changeset_dao;

    protected function setUp(): void
    {
        $this->submitter   = Mockery::mock(PFUser::class);
        $this->submitter->shouldReceive('isAnonymous')->andReturnFalse();
        $this->submitter->shouldReceive('getId')->andReturn(102);

        $this->changeset_dao  = \Mockery::spy(\Tracker_Artifact_ChangesetDao::class);
        $this->factory     = \Mockery::spy(\Tracker_FormElementFactory::class);

        $this->artifact_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);
        $this->workflow = \Mockery::spy(\Workflow::class);
        $this->changeset_factory  = \Mockery::spy(\Tracker_Artifact_ChangesetFactory::class);
        $this->changeset_factory->shouldReceive('getChangeset')->andReturns(new Tracker_Artifact_Changeset_Null());
        $tracker        = \Mockery::spy(\Tracker::class)->shouldReceive('getWorkflow')->andReturns($this->workflow)->getMock();
        $tracker->shouldReceive('getId')->andReturns(888);
        $this->artifact = \Mockery::mock(\Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->artifact->setId(42);
        $this->artifact->setTracker($tracker);
        $this->artifact->shouldReceive('getChangesetFactory')->andReturns($this->changeset_factory);

        $fields_validator = \Mockery::spy(\Tracker_Artifact_Changeset_InitialChangesetFieldsValidator::class);
        $fields_validator->shouldReceive('validate')->andReturns(true);

        $this->url_mapping = Mockery::mock(\Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping::class);

        $this->creator = new Tracker_Artifact_Changeset_InitialChangesetCreator(
            $fields_validator,
            new FieldsToBeSavedInSpecificOrderRetriever($this->factory),
            $this->changeset_dao,
            $this->artifact_factory,
            \Mockery::spy(\EventManager::class),
            new Tracker_Artifact_Changeset_ChangesetDataInitializator($this->factory),
            new \Psr\Log\NullLogger()
        );

        $this->submitted_on = $_SERVER['REQUEST_TIME'];

        $this->field = \Mockery::spy(\Tracker_FormElement_Field_Selectbox::class);
        $this->field->shouldReceive('getId')->andReturns(123);
        $this->field->shouldReceive('isSubmitable')->andReturns(true);
    }

    public function testItCallsTheAfterMethodOnWorkflowWhenCreateInitialChangeset(): void
    {
        $this->setFields([]);
        $this->changeset_dao->shouldReceive('create')->andReturns(5667);
        $this->artifact_factory->shouldReceive('save')->andReturns(true);
        $this->workflow->shouldReceive('validate')->andReturns(true);

        $this->workflow->shouldReceive('after')->with($this->fields_data, Mockery::any(), null)->once();

        $this->creator->create($this->artifact, $this->fields_data, $this->submitter, $this->submitted_on, $this->url_mapping);
    }

    public function testItDoesNotCallTheAfterMethodOnWorkflowWhenSaveOfInitialChangesetFails(): void
    {
        $this->setFields([]);
        $this->changeset_dao->shouldReceive('create')->andReturns(false);
        $this->workflow->shouldReceive('validate')->andReturns(true);

        $this->workflow->shouldReceive('after')->never();

        $this->creator->create($this->artifact, $this->fields_data, $this->submitter, $this->submitted_on, $this->url_mapping);
    }

    public function testItDoesNotCallTheAfterMethodOnWorkflowWhenSaveOfArtifactFails(): void
    {
        $this->setFields([]);
        $this->changeset_dao->shouldReceive('create')->andReturns(123);
        $this->artifact_factory->shouldReceive('save')->andReturns(false);
        $this->workflow->shouldReceive('validate')->andReturns(true);

        $this->workflow->shouldReceive('after')->never();

        $this->creator->create($this->artifact, $this->fields_data, $this->submitter, $this->submitted_on, $this->url_mapping);
    }

    public function testItDoesNotCreateTheChangesetIfTheWorkflowValidationFailed(): void
    {
        $this->setFields([]);
        $transition = \Mockery::spy(Transition::class);

        $this->workflow->shouldReceive('validate')->andThrows(new Tracker_Workflow_Transition_InvalidConditionForTransitionException($transition));

        $this->changeset_dao->shouldReceive('create')->never();
        $this->artifact_factory->shouldReceive('save')->never();
        $this->workflow->shouldReceive('after')->never();

        $creation = $this->creator->create($this->artifact, $this->fields_data, $this->submitter, $this->submitted_on, $this->url_mapping);

        $this->assertEquals(null, $creation);
    }

    public function testItSavesTheDefaultValueWhenFieldIsSubmittedButCannotSubmit(): void
    {
        $this->setFields([$this->field]);
        $this->changeset_dao->shouldReceive('create')->andReturns(123);

        $this->field->shouldReceive('userCanSubmit')->andReturns(false);
        $this->field->shouldReceive('getDefaultValue')->andReturns('default value');

        $this->fields_data[123] = 'value';

        $this->field->shouldReceive('saveNewChangeset')->with(\Mockery::any(), \Mockery::any(), \Mockery::any(), 'default value', \Mockery::any(), \Mockery::any(), \Mockery::any(), \Mockery::any())->once();

        $this->creator->create($this->artifact, $this->fields_data, $this->submitter, $this->submitted_on, $this->url_mapping);
    }

    public function testItIgnoresTheDefaultValueWhenFieldIsSubmittedAndCanSubmit(): void
    {
        $this->setFields([$this->field]);
        $this->changeset_dao->shouldReceive('create')->andReturns(123);

        $this->field->shouldReceive('userCanSubmit')->andReturns(true);
        $this->field->shouldReceive('getDefaultValue')->andReturns('default value');

        $this->fields_data[123] = 'value';

        $this->field->shouldReceive('saveNewChangeset')->with(\Mockery::any(), \Mockery::any(), \Mockery::any(), 'value', \Mockery::any(), \Mockery::any(), \Mockery::any(), \Mockery::any())->once();

        $this->creator->create($this->artifact, $this->fields_data, $this->submitter, $this->submitted_on, $this->url_mapping);
    }

    public function testItBypassPermsWhenWorkflowBypassPerms(): void
    {
        $this->setFields([$this->field]);
        $this->changeset_dao->shouldReceive('create')->andReturns(123);

        $this->field->shouldReceive('userCanSubmit')->andReturns(false);
        $this->field->shouldReceive('getDefaultValue')->andReturns('default value');
        $this->workflow->shouldReceive('bypassPermissions')->with($this->field)->andReturns(true);

        $this->fields_data[123] = 'value';

        $this->field->shouldReceive('saveNewChangeset')->with(\Mockery::any(), \Mockery::any(), \Mockery::any(), 'value', \Mockery::any(), \Mockery::any(), \Mockery::any(), \Mockery::any())->once();

        $this->creator->create($this->artifact, $this->fields_data, $this->submitter, $this->submitted_on, $this->url_mapping);
    }

    private function setFields(array $fields): void
    {
        $this->factory->shouldReceive('getAllFormElementsForTracker')->andReturns($fields);
        $this->factory->shouldReceive('getUsedFields')->andReturns($fields);
    }
}
