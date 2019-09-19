<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;

require_once __DIR__.'/../../bootstrap.php';

class Tracker_Artifact_Changeset_InitialChangesetCreator_BaseTest extends TuleapTestCase
{
    protected $changeset_dao;

    /** @var Tracker_Artifact_Changeset_InitialChangesetCreator */
    protected $creator;

    /** @var Tracker_FormElementFactory */
    protected $factory;

    public function setUp()
    {
        parent::setUp();
        $this->fields_data = array();
        $this->submitter   = aUser()->withId(74)->build();

        $this->changeset_dao  = mock('Tracker_Artifact_ChangesetDao');
        $this->factory     = mock('Tracker_FormElementFactory');

        $fields = $this->getFields();
        stub($this->factory)->getAllFormElementsForTracker()->returns($fields);
        stub($this->factory)->getUsedFields()->returns($fields);

        $this->artifact_factory = mock('Tracker_ArtifactFactory');
        $this->workflow = mock('Workflow');
        $this->changeset_factory  = mock('Tracker_Artifact_ChangesetFactory');
        stub($this->changeset_factory)->getChangeset()->returns(new Tracker_Artifact_Changeset_Null());
        $tracker        = stub('Tracker')->getWorkflow()->returns($this->workflow);
        $this->artifact = partial_mock('Tracker_Artifact', array('getChangesetDao','getChangesetCommentDao', 'getReferenceManager', 'getChangesetFactory'));
        $this->artifact->setId(42);
        $this->artifact->setTracker($tracker);
        stub($this->artifact)->getChangesetFactory()->returns($this->changeset_factory);

        $fields_validator = mock('Tracker_Artifact_Changeset_InitialChangesetFieldsValidator');
        stub($fields_validator)->validate()->returns(true);

        $this->url_mapping = Mockery::mock(\Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping::class);

        $this->creator = new Tracker_Artifact_Changeset_InitialChangesetCreator(
            $fields_validator,
            new FieldsToBeSavedInSpecificOrderRetriever($this->factory),
            $this->changeset_dao,
            $this->artifact_factory,
            \Mockery::spy(\EventManager::class),
            new Tracker_Artifact_Changeset_ChangesetDataInitializator($this->factory),
            new Log_NoopLogger()
        );

        $this->submitted_on = $_SERVER['REQUEST_TIME'];
    }

    protected function getFields()
    {
        return array();
    }
}

class Tracker_Artifact_Changeset_InitialChangesetCreator_WorkflowTest extends Tracker_Artifact_Changeset_InitialChangesetCreator_BaseTest
{

    public function itCallsTheAfterMethodOnWorkflowWhenCreateInitialChangeset()
    {
        stub($this->changeset_dao)->create()->returns(5667);
        stub($this->artifact_factory)->save()->returns(true);
        stub($this->workflow)->validate()->returns(true);

        expect($this->workflow)->after($this->fields_data, new IsAExpectation('Tracker_Artifact_Changeset'), null)->once();

        $this->creator->create($this->artifact, $this->fields_data, $this->submitter, $this->submitted_on, $this->url_mapping);
    }

    public function itDoesNotCallTheAfterMethodOnWorkflowWhenSaveOfInitialChangesetFails()
    {
        stub($this->changeset_dao)->create()->returns(false);
        stub($this->workflow)->validate()->returns(true);

        expect($this->workflow)->after()->never();

        $this->creator->create($this->artifact, $this->fields_data, $this->submitter, $this->submitted_on, $this->url_mapping);
    }

    public function itDoesNotCallTheAfterMethodOnWorkflowWhenSaveOfArtifactFails()
    {
        stub($this->changeset_dao)->create()->returns(123);
        stub($this->artifact_factory)->save()->returns(false);
        stub($this->workflow)->validate()->returns(true);

        expect($this->workflow)->after()->never();

        $this->creator->create($this->artifact, $this->fields_data, $this->submitter, $this->submitted_on, $this->url_mapping);
    }

    public function itDoesNotCreateTheChangesetIfTheWorkflowValidationFailed()
    {
        $transition = mock(Transition::class);

        stub($this->workflow)->validate()->throws(
            new Tracker_Workflow_Transition_InvalidConditionForTransitionException($transition)
        );

        expect($this->changeset_dao)->create()->never();
        expect($this->artifact_factory)->save()->never();
        expect($this->workflow)->after()->never();

        $creation = $this->creator->create($this->artifact, $this->fields_data, $this->submitter, $this->submitted_on, $this->url_mapping);

        $this->assertFalse($creation);
    }
}

class Tracker_Artifact_Changeset_InitialChangesetCreator_DefaultValueTest extends Tracker_Artifact_Changeset_InitialChangesetCreator_BaseTest
{

    private $field;

    public function setUp()
    {
        $this->field = mock('Tracker_FormElement_Field_Selectbox');
        stub($this->field)->getId()->returns(123);
        stub($this->field)->isSubmitable()->returns(true);

        parent::setUp();

        stub($this->changeset_dao)->create()->returns(123);
    }

    protected function getFields()
    {
        return array($this->field);
    }

    public function itSavesTheDefaultValueWhenFieldIsSubmittedButCannotSubmit()
    {
        stub($this->field)->userCanSubmit()->returns(false);
        stub($this->field)->getDefaultValue()->returns('default value');

        $this->fields_data[123] = 'value';

        expect($this->field)->saveNewChangeset('*', '*', '*', 'default value', '*', '*', '*', '*')->once();

        $this->creator->create($this->artifact, $this->fields_data, $this->submitter, $this->submitted_on, $this->url_mapping);
    }

    public function itIgnoresTheDefaultValueWhenFieldIsSubmittedAndCanSubmit()
    {
        stub($this->field)->userCanSubmit()->returns(true);
        stub($this->field)->getDefaultValue()->returns('default value');

        $this->fields_data[123] = 'value';

        expect($this->field)->saveNewChangeset('*', '*', '*', 'value', '*', '*', '*', '*')->once();

        $this->creator->create($this->artifact, $this->fields_data, $this->submitter, $this->submitted_on, $this->url_mapping);
    }

    public function itBypassPermsWhenWorkflowBypassPerms()
    {
        stub($this->field)->userCanSubmit()->returns(false);
        stub($this->field)->getDefaultValue()->returns('default value');
        stub($this->workflow)->bypassPermissions($this->field)->returns(true);

        $this->fields_data[123] = 'value';

        expect($this->field)->saveNewChangeset('*', '*', '*', 'value', '*', '*', '*', '*')->once();

        $this->creator->create($this->artifact, $this->fields_data, $this->submitter, $this->submitted_on, $this->url_mapping);
    }
}
