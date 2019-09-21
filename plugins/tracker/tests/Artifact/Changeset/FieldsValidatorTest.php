<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

require_once __DIR__.'/../../bootstrap.php';

class Tracker_Artifact_Changeset_FieldsValidatorTest extends TuleapTestCase
{
    /** @var Tracker_Artifact_Changeset_NewChangesetFieldsValidator */
    private $new_changeset_fields_validator;

    /** @var Tracker_Artifact_Changeset_InitialChangesetFieldsValidator */
    private $initial_changeset_fields_validator;

    /** @var Tracker_FormElementFactory */
    private $factory;

    /** @var Workflow */
    private $workflow;

    /** @var Tracker_Artifact */
    private $artifact;

    /** @var Tracker_Artifact */
    private $artifact_update;

    /** @var Tracker_FormElement_Field */
    private $field1;

    /** @var Tracker_FormElement_Field */
    private $field2;

    /** @var Tracker_FormElement_Field */
    private $field3;

    /** @var Tracker_Artifact_Changeset */
    private $changeset;

    /** @var Tracker_Artifact_ChangesetValue */
    private $changeset_value1;

    /** @var Tracker_Artifact_ChangesetValue */
    private $changeset_value2;

    /** @var Tracker_Artifact_ChangesetValue */
    private $changeset_value3;

    public function setUp()
    {
        parent::setUp();
        $this->factory = mock('Tracker_FormElementFactory');
        $workflow_checker = \Mockery::mock(\Tuleap\Tracker\Workflow\WorkflowUpdateChecker::class);
        $workflow_checker->shouldReceive('canFieldBeUpdated')->andReturnTrue();
        $this->new_changeset_fields_validator = new Tracker_Artifact_Changeset_NewChangesetFieldsValidator($this->factory, $workflow_checker);
        $this->initial_changeset_fields_validator = new Tracker_Artifact_Changeset_InitialChangesetFieldsValidator($this->factory);

        $this->field1 = $this->getFieldWithId(101);
        $this->field2 = $this->getFieldWithId(102);
        $this->field3 = $this->getFieldWithId(103);

        $this->factory->setReturnValue('getAllFormElementsForTracker', array());
        $this->factory->setReturnValue('getUsedFields', array($this->field1, $this->field2, $this->field3));

        $this->workflow = mock('Workflow');

        $this->artifact = aMockArtifact()
            ->withTracker(mock('Tracker'))
            ->build();
        stub($this->artifact)->getWorkflow()->returns($this->workflow);

        $this->changeset        = mock('Tracker_Artifact_Changeset');
        $this->changeset_value1 = mock('Tracker_Artifact_ChangesetValue');
        $this->changeset_value2 = mock('Tracker_Artifact_ChangesetValue');
        $this->changeset_value3 = mock('Tracker_Artifact_ChangesetValue');
        stub($this->changeset)->getValue($this->field1)->returns($this->changeset_value1);

        $this->artifact_update = aMockArtifact()
            ->withTracker(mock('Tracker'))
            ->withlastChangeset($this->changeset)
            ->build();

        stub($this->artifact_update)->getWorkflow()->returns($this->workflow);
    }

    private function getFieldWithId($id)
    {
        $mocked_methods = array(
            'isValid',
            'isRequired',
            'hasDefaultValue',
            'getDefaultValue',
            'userCanUpdate',
            'userCanSubmit'
        );

        return partial_mock(
            'Tracker_FormElement_Field_Text',
            $mocked_methods,
            array($id, null, null, "name $id", "label $1", null, null, null, null, null, null)
        );
    }

    public function testValidateFields_basicvalid()
    {
        stub($this->field1)->isValid()->returns(true);
        stub($this->field2)->isValid()->returns(true);
        stub($this->field3)->isValid()->returns(true);
        stub($this->workflow)->validate()->returns(true);

        $user = mock(\PFUser::class);
        $fields_data = array();
        $this->assertTrue($this->initial_changeset_fields_validator->validate($this->artifact, $user, $fields_data));
        $this->assertNotNull($fields_data);
        $this->assertFalse(isset($fields_data[101]));
        $this->assertFalse(isset($fields_data[102]));
        $this->assertFalse(isset($fields_data[103]));
    }

    public function testValidateSubmitFieldNotRequired()
    {
        stub($this->field1)->isValid()->returns(true);
        stub($this->field1)->userCanSubmit()->returns(true);
        stub($this->field2)->isValid()->returns(true);
        stub($this->field3)->isValid()->returns(true);
        stub($this->workflow)->validate()->returns(true);

        $user = mock(\PFUser::class);
        $fields_data = array('101' => 444);
        $this->assertTrue($this->initial_changeset_fields_validator->validate($this->artifact, $user, $fields_data));
        $this->assertNotNull($fields_data[101]);
        $this->assertEqual($fields_data[101], 444);
    }

    function testValidateSubmitFieldNotRequiredNotSubmittedDefaultValue()
    {
        stub($this->field1)->isValid()->returns(true);
        stub($this->field1)->hasDefaultValue()->returns(true);
        stub($this->field1)->getDefaultValue()->returns('DefaultValue');
        stub($this->field2)->isValid()->returns(true);
        stub($this->field3)->isValid()->returns(true);
        stub($this->workflow)->validate()->returns(true);

        $user = mock(\PFUser::class);
        $fields_data = array();
        $this->assertTrue($this->initial_changeset_fields_validator->validate($this->artifact, $user, $fields_data));
        $this->assertFalse(isset($fields_data[101]));
    }

    function testValidateSubmitFieldNotRequiredNotSubmittedNoDefaultValue()
    {
        stub($this->field1)->isValid()->returns(true);
        stub($this->field2)->isValid()->returns(true);
        stub($this->field3)->isValid()->returns(true);
        stub($this->workflow)->validate()->returns(true);

        $user = mock(\PFUser::class);
        $fields_data = array();
        $this->assertTrue($this->initial_changeset_fields_validator->validate($this->artifact, $user, $fields_data));
        $this->assertFalse(isset($fields_data[101]));
    }

    function testValidateSubmitFieldRequired()
    {
        stub($this->field1)->isValid()->returns(true);
        stub($this->field1)->isRequired()->returns(true);
        stub($this->field2)->isValid()->returns(true);
        stub($this->field3)->isValid()->returns(true);
        stub($this->workflow)->validate()->returns(true);

        $user = mock(\PFUser::class);
        $fields_data = array('101' => 666);
        $this->assertTrue($this->initial_changeset_fields_validator->validate($this->artifact, $user, $fields_data));
        $this->assertNotNull($fields_data[101]);
        $this->assertEqual($fields_data[101], 666);
    }

    function testValidateSubmitFieldRequiredNotSubmittedDefaultValue()
    {
        stub($this->field1)->isValid()->returns(true);
        stub($this->field1)->userCanSubmit()->returns(true);
        stub($this->field1)->isRequired()->returns(true);
        stub($this->field1)->hasDefaultValue()->returns(true);
        stub($this->field1)->getDefaultValue()->returns('DefaultValue');
        stub($this->field2)->isValid()->returns(true);
        stub($this->field3)->isValid()->returns(true);
        stub($this->workflow)->validate()->returns(true);

        $GLOBALS['Language']->expectOnce('getText', array('plugin_tracker_common_artifact', 'err_required', $this->field1->getLabel() .' ('. $this->field1->getName() .')'));

        $user = mock(\PFUser::class);
        $fields_data = array();
        $this->assertFalse($this->initial_changeset_fields_validator->validate($this->artifact, $user, $fields_data));
        $this->assertFalse(isset($fields_data[101]));
    }

    function testValidateSubmitFieldRequiredNotSubmittedNoDefaultValue()
    {
        stub($this->field1)->isValid()->returns(false);
        stub($this->field1)->userCanSubmit()->returns(true);
        stub($this->field1)->isRequired()->returns(true);
        stub($this->field1)->hasDefaultValue()->returns(false);
        stub($this->field2)->isValid()->returns(true);
        stub($this->field3)->isValid()->returns(true);
        stub($this->workflow)->validate()->returns(true);

        $GLOBALS['Language']->expectOnce('getText', array('plugin_tracker_common_artifact', 'err_required', $this->field1->getLabel() .' ('. $this->field1->getName() .')'));

        $user = mock(\PFUser::class);
        $fields_data = array();
        $this->assertFalse($this->initial_changeset_fields_validator->validate($this->artifact, $user, $fields_data));
    }

    // ARTIFACT MODIFICATION
    function testValidateUpdateFieldSubmitted()
    {
        stub($this->field1)->isValid()->returns(true);
        stub($this->field1)->userCanUpdate()->returns(true);
        stub($this->field2)->isValid()->returns(true);
        stub($this->field3)->isValid()->returns(true);
        stub($this->workflow)->validate()->returns(true);

        $user = mock(\PFUser::class);
        $fields_data = array('101' => 666);
        $this->assertTrue($this->new_changeset_fields_validator->validate($this->artifact_update, $user, $fields_data));
        $this->assertNotNull($fields_data[101]);
        $this->assertEqual($fields_data[101], 666);
    }

    function testValidateUpdateFieldNotSubmitted()
    {
        stub($this->field1)->isValid()->returns(true);
        stub($this->field1)->userCanUpdate()->returns(true);
        stub($this->field1)->isRequired()->returns(true);
        stub($this->field2)->isValid()->returns(true);
        stub($this->field3)->isValid()->returns(true);
        stub($this->workflow)->validate()->returns(true);
        $this->changeset_value1->setReturnValue('getValue', 999);

        $GLOBALS['Language']->expectNever('getText', array('plugin_tracker_common_artifact', 'err_required', '*'));

        $user = mock(\PFUser::class);
        $fields_data = array();
        $this->assertTrue($this->new_changeset_fields_validator->validate($this->artifact_update, $user, $fields_data));
        $this->assertFalse(isset($fields_data[101]));
    }

    function testValidateFields_missing_fields_on_submission()
    {
        stub($this->field1)->isValid()->returns(true);
        stub($this->field1)->userCanSubmit()->returns(true);
        stub($this->field2)->isValid()->returns(true);
        stub($this->field2)->userCanSubmit()->returns(true);
        stub($this->field3)->isValid()->returns(true);
        stub($this->field3)->userCanSubmit()->returns(true);
        stub($this->workflow)->validate()->returns(true);

        $user = mock(\PFUser::class);
        // field 101 and 102 are missing
        // 101 has a default value
        // 102 has no default value
        $fields_data = array('103' => 444);
        $this->assertTrue($this->initial_changeset_fields_validator->validate($this->artifact, $user, $fields_data));
        $this->assertFalse(isset($fields_data[101]));
        $this->assertFalse(isset($fields_data[102]));
        $this->assertNotNull($fields_data[103]);
        $this->assertEqual($fields_data[103], 444);
    }

    function testValidateFields_missing_fields_on_update()
    {
        stub($this->field1)->isValid()->returns(true);
        stub($this->field1)->userCanUpdate()->returns(true);
        stub($this->field2)->isValid()->returns(true);
        stub($this->field2)->userCanUpdate()->returns(true);
        stub($this->field3)->isValid()->returns(true);
        stub($this->field3)->userCanUpdate()->returns(true);
        stub($this->workflow)->validate()->returns(true);

        stub($this->changeset)->getValue($this->field2)->returns($this->changeset_value2);
        stub($this->changeset)->getValue($this->field3)->returns($this->changeset_value3);

        $user = mock(\PFUser::class);
        // field 102 is missing
        $fields_data = array('101' => 'foo',
                             '103' => 'bar');
        $this->assertTrue($this->new_changeset_fields_validator->validate($this->artifact_update, $user, $fields_data));
        $this->assertFalse(isset($fields_data[102]));
    }

    function testValidateFields_missing_fields_in_previous_changeset_on_update()
    {
        stub($this->field1)->isValid()->returns(true);
        stub($this->field1)->userCanUpdate()->returns(true);
        stub($this->field2)->isValid()->returns(true);
        stub($this->field2)->userCanUpdate()->returns(true);
        stub($this->field3)->isValid()->returns(true);
        stub($this->field3)->userCanUpdate()->returns(true);
        stub($this->workflow)->validate()->returns(true);

        stub($this->changeset)->getValue($this->field2)->returns(null);
        stub($this->changeset)->getValue($this->field3)->returns($this->changeset_value3);

        $user = mock(\PFUser::class);
        // field 102 is missing
        $fields_data = array('101' => 'foo',
                             '103' => 'bar');
        $this->assertTrue($this->new_changeset_fields_validator->validate($this->artifact_update, $user, $fields_data));
        $this->assertFalse(isset($fields_data[102]));
    }

    function testValidateFields_basicnotvalid()
    {
        stub($this->field1)->isValid()->returns(true);
        stub($this->field1)->userCanSubmit()->returns(true);
        stub($this->field2)->isValid()->returns(false);
        stub($this->field2)->isRequired()->returns(true);
        stub($this->field2)->userCanSubmit()->returns(true);
        stub($this->field3)->isValid()->returns(true);
        stub($this->field3)->userCanSubmit()->returns(true);
        stub($this->workflow)->validate()->returns(true);

        $user = mock(\PFUser::class);
        // field 102 is missing
        $fields_data = array();
        $this->assertFalse($this->initial_changeset_fields_validator->validate($this->artifact, $user, $fields_data));
        $this->assertFalse(isset($fields_data[101]));
        $this->assertFalse(isset($fields_data[102]));
        $this->assertFalse(isset($fields_data[103]));
    }

    function testValidateFields_valid()
    {
        stub($this->field1)->isValid()->returns(true);
        stub($this->field1)->userCanSubmit()->returns(true);
        stub($this->field2)->isValid('*', '123')->returns(true);
        stub($this->field2)->isValid('*', '456')->returns(false);
        stub($this->field2)->userCanSubmit()->returns(true);
        stub($this->field3)->isValid()->returns(true);
        stub($this->field3)->userCanSubmit()->returns(true);
        stub($this->workflow)->validate()->returns(true);

        $user = mock(\PFUser::class);
        $fields_data = array(
            102 => '123',
        );
        $this->assertTrue($this->initial_changeset_fields_validator->validate($this->artifact, $user, $fields_data));
        $this->assertFalse(isset($fields_data[101]));
        $this->assertFalse(isset($fields_data[103]));

        $fields_data = array(
            102 => '456',
        );
        $this->assertFalse($this->initial_changeset_fields_validator->validate($this->artifact, $user, $fields_data));
        $this->assertFalse(isset($fields_data[101]));
        $this->assertFalse(isset($fields_data[103]));
    }
}
