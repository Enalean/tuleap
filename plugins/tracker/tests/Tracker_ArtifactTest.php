<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */


require_once(dirname(__FILE__).'/../include/constants.php');
require_once(dirname(__FILE__).'/builders/all.php');
require_once(dirname(__FILE__).'/../include/Tracker/Artifact/Tracker_Artifact.class.php');
Mock::generatePartial(
    'Tracker_Artifact',
    'Tracker_ArtifactTestVersion',
    array(
        'getChangesetDao',
        'getChangesetCommentDao',
        'getFormElementFactory',
        'getTracker',
        'getId',
        'getLastChangeset',
        'getReferenceManager',
        'getChangesets',
        'getChangeset',
        'getUserManager',
        'getArtifactFactory',
        'getWorkflow',
    )
);

Mock::generatePartial(
    'Tracker_Artifact',
    'Tracker_ArtifactTestPermissions',
    array(
        'getTracker',
        'getId',
        'getUserManager',
        'useArtifactPermissions',
        'permission_db_authorized_ugroups',
        'getValue',
        'getSubmittedBy'
    )
);

require_once(dirname(__FILE__).'/../include/Tracker/Artifact/Tracker_Artifact_Changeset.class.php');
Mock::generate('Tracker_Artifact_Changeset');
require_once(dirname(__FILE__).'/../include/Tracker/Artifact/Tracker_Artifact_ChangesetValue.class.php');
Mock::generate('Tracker_Artifact_ChangesetValue');
require_once(dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElement_Field_Date.class.php');
Mock::generate('Tracker_FormElement_Field_Date');
require_once(dirname(__FILE__).'/../include/Tracker/Artifact/Tracker_Artifact_ChangesetValue_Date.class.php');
Mock::generate('Tracker_Artifact_ChangesetValue_Date');
require_once(dirname(__FILE__).'/../include/Tracker/Artifact/dao/Tracker_Artifact_ChangesetDao.class.php');
Mock::generate('Tracker_Artifact_ChangesetDao');
require_once(dirname(__FILE__).'/../include/Tracker/Artifact/dao/Tracker_Artifact_Changeset_CommentDao.class.php');
Mock::generate('Tracker_Artifact_Changeset_CommentDao');
require_once(dirname(__FILE__).'/../include/Tracker/Tracker.class.php');
Mock::generate('Tracker');
require_once(dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElementFactory.class.php');
Mock::generate('Tracker_FormElementFactory');
require_once(dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElement_Field.class.php');
Mock::generatePartial('Tracker_FormElement_Field', 'MockTracker_FormElement_Field', array(
        'getId',
        'getLabel',
        'getName',
        'isValid',
        'fetchCriteriaValue',
        'fetchChangesetValue',
        'fetchRawValue',
        'getCriteriaFrom',
        'getCriteriaWhere',
        'getCriteriaDao',
        'fetchArtifactValue',
        'fetchArtifactValueReadOnly',
        'fetchSubmitValue',
        'fetchTooltipValue',
        'getValueDao',
        'fetchFollowUp',
        'fetchRawValueFromChangeset',
        'saveValue',
        'fetchAdminFormElement',
        'getFactoryLabel',
        'getFactoryDescription',
        'getFactoryIconUseIt',
        'getFactoryIconCreate',
        'getChangesetValue',
        'saveNewChangeset',
        'validate',
        'getSoapAvailableValues',
        'hasDefaultValue',
        'getDefaultValue',
        'isRequired',
        'userCanUpdate',
        'userCanSubmit',
        'setHasErrors',
        'fetchSubmitValueMasschange'
    )
);
require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');
require_once('common/user/User.class.php');
Mock::generate('User');
require_once('common/include/Response.class.php');
Mock::generate('Response');
require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once('common/reference/ReferenceManager.class.php');
Mock::generate('ReferenceManager');
require_once('common/user/UserManager.class.php');
Mock::generate('UserManager');
require_once(dirname(__FILE__).'/../include/Tracker/Artifact/Tracker_ArtifactFactory.class.php');
Mock::generate('Tracker_ArtifactFactory');
require_once(dirname(__FILE__).'/../include/Tracker/Rule/Tracker_RulesManager.class.php');
Mock::generate('Tracker_RulesManager');
/*Mock::generatePartial('Tracker_RulesManager', 'MockTracker_RulesManager', array(
        'validate'
    )
);*/

require_once(dirname(__FILE__).'/builders/aField.php');
require_once(dirname(__FILE__).'/builders/aTracker.php');


require_once dirname(__FILE__) .'/../include/Tracker/FormElement/Tracker_FormElement_Field_ArtifactLink.class.php';
Mock::generate('Tracker_FormElement_Field_ArtifactLink');
require_once dirname(__FILE__).'/builders/anArtifact.php';

require_once(dirname(__FILE__).'/../include/Tracker/TrackerManager.class.php');
Mock::generate('TrackerManager');

require_once(dirname(__FILE__).'/../include/workflow/Workflow.class.php');
Mock::generate('Workflow');

class MockWorkflow_Tracker_ArtifactTest_WorkflowNoPermsOnPostActionFields extends MockWorkflow {
    function before(&$fields_data, $submitter, $artifact) {
        $fields_data[102] = '456';
        return parent::before($fields_data, $submitter, $artifact);
    }
}

class Tracker_ArtifactTest extends TuleapTestCase {

    function setUp() {
        parent::setUp();
        $this->response = $GLOBALS['Response'];
        $this->language = $GLOBALS['Language'];

        $tracker     = new MockTracker();
        $factory     = new MockTracker_FormElementFactory();
        $this->field = new MockTracker_FormElement_Field();
        $this->field->setReturnValue('getId', 101);
        $this->field->setReturnValue('getLabel', 'Summary');
        $this->field->setReturnValue('getName', 'summary');
        $factory->setReturnValue('getUsedFields', array($this->field));
        $factory->setReturnValue('getAllFormElementsForTracker', array());
        
        $this->artifact = new Tracker_ArtifactTestVersion();
        $this->artifact->setReturnReference('getFormElementFactory', $factory);
        $this->artifact->setReturnReference('getTracker', $tracker);
        $this->artifact->setReturnValue('getLastChangeset', false); // no changeset => artifact submission

        $workflow = new MockWorkflow();
        $workflow->setReturnValue('validate', true);
        $workflow->setReturnValue('validateGlobalRules', true);

        $this->artifact->setReturnReference('getWorkflow', $workflow);
        $this->artifact_update = new Tracker_ArtifactTestVersion();
        $this->artifact_update->setReturnReference('getFormElementFactory', $factory);
        $this->artifact_update->setReturnReference('getTracker', $tracker);
        $this->artifact_update->setReturnReference('getWorkflow', $workflow);
        $this->changeset = new MockTracker_Artifact_Changeset();
        $this->changeset_value = new MockTracker_Artifact_ChangesetValue();
        $this->changeset->setReturnReference('getValue', $this->changeset_value, array($this->field));
        $this->artifact_update->setReturnReference('getLastChangeset', $this->changeset); // changeset => artifact modification

        $rules_manager = new MockTracker_RulesManager();
        $rules_manager->setReturnValue('validate', true);
        $tracker->setReturnReference('getRulesManager', $rules_manager);
    }

    function tearDown() {
        unset($this->field);
        unset($this->artifact);
        parent::tearDown();
    }

    function testGetValue() {
        $changeset = new MockTracker_Artifact_Changeset();
        $field     = new MockTracker_FormElement_Field_Date();
        $value     = new MockTracker_Artifact_ChangesetValue_Date();

        $changeset->setReturnReference('getValue', $value);

        $id = $tracker_id = $use_artifact_permissions = $submitted_by = $submitted_on = '';
        $artifact = new Tracker_Artifact($id, $tracker_id, $submitted_by, $submitted_on, $use_artifact_permissions);

        $this->assertEqual($artifact->getValue($field, $changeset), $value);
    }

    function testGetValue_without_changeset() {
        $changeset = new MockTracker_Artifact_Changeset();
        $field     = new MockTracker_FormElement_Field_Date();
        $value     = new MockTracker_Artifact_ChangesetValue_Date();

        $changeset->setReturnReference('getValue', $value);

        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getLastChangeset', $changeset);

        $this->assertEqual($artifact->getValue($field), $value);
    }

    function testValidateFields_basicvalid() {
        $tracker = new MockTracker();
        $factory = new MockTracker_FormElementFactory();

        $rules_manager = new MockTracker_RulesManager();
        $rules_manager->setReturnValue('validate', true);
        $tracker->setReturnReference('getRulesManager', $rules_manager);

        $field1  = new MockTracker_FormElement_Field();
        $field1->setReturnValue('getId', 101);
        $field1->setReturnValue('isValid', true);
        $field2  = new MockTracker_FormElement_Field();
        $field2->setReturnValue('getId', 102);
        $field2->setReturnValue('isValid', true);
        $field3  = new MockTracker_FormElement_Field();
        $field3->setReturnValue('getId', 103);
        $field3->setReturnValue('isValid', true);
        $factory->setReturnValue('getUsedFields', array($field1, $field2, $field3));
        $factory->setReturnValue('getAllFormElementsForTracker', array());
        $workflow = new MockWorkflow();
        $workflow->setReturnValue('validate', true);
        $workflow->setReturnValue('validateGlobalRules', true);

        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnReference('getWorkflow', $workflow);

        $fields_data = array();
        $this->assertTrue($artifact->validateFields($fields_data));
        $this->assertNotNull($fields_data);
        $this->assertFalse(isset($fields_data[101]));
        $this->assertFalse(isset($fields_data[102]));
        $this->assertFalse(isset($fields_data[103]));
    }

    // ARTIFACT SUBMISSION
    function testValidateSubmitFieldNotRequired() {

        $this->field->setReturnValue('isValid', true);
        $this->field->setReturnValue('userCanUpdate', true);
        $this->field->setReturnValue('isRequired', false);

        $fields_data = array('101' => 444);
        $this->assertTrue($this->artifact->validateFields($fields_data));
        $this->assertNotNull($fields_data[101]);
        $this->assertEqual($fields_data[101], 444);
    }

    function testValidateSubmitFieldNotRequiredNotSubmittedDefaultValue() {
        $this->field->setReturnValue('isValid', true);
        $this->field->setReturnValue('userCanUpdate', true);
        $this->field->setReturnValue('isRequired', false);
        $this->field->setReturnValue('hasDefaultValue', true);
        $this->field->setReturnValue('getDefaultValue', 'DefaultValue');

        $fields_data = array();
        $this->assertTrue($this->artifact->validateFields($fields_data));
        $this->assertFalse(isset($fields_data[101]));
    }

    function testValidateSubmitFieldNotRequiredNotSubmittedNoDefaultValue() {
        $this->field->setReturnValue('isValid', true);
        $this->field->setReturnValue('userCanUpdate', true);
        $this->field->setReturnValue('isRequired', false);
        $this->field->setReturnValue('hasDefaultValue', false);

        $fields_data = array();
        $this->assertTrue($this->artifact->validateFields($fields_data));
        $this->assertFalse(isset($fields_data[101]));
    }

    function testValidateSubmitFieldRequired() {
        $this->field->setReturnValue('isValid', true);
        $this->field->setReturnValue('userCanSubmit', true);
        $this->field->setReturnValue('userCanUpdate', true);
        $this->field->setReturnValue('isRequired', true);

        $fields_data = array('101' => 666);
        $this->assertTrue($this->artifact->validateFields($fields_data));
        $this->assertNotNull($fields_data[101]);
        $this->assertEqual($fields_data[101], 666);
    }

    function testValidateSubmitFieldRequiredNotSubmittedDefaultValue() {
        $this->field->setReturnValue('isValid', true);
        $this->field->setReturnValue('userCanSubmit', true);
        $this->field->setReturnValue('userCanUpdate', true);
        $this->field->setReturnValue('isRequired', true);
        $this->field->setReturnValue('hasDefaultValue', true);
        $this->field->setReturnValue('getDefaultValue', 'MyDefaultValue');

        $GLOBALS['Language']->expectOnce('getText', array('plugin_tracker_common_artifact', 'err_required', $this->field->getLabel() .' ('. $this->field->getName() .')'));

        $fields_data = array();
        $this->assertFalse($this->artifact->validateFields($fields_data));
        $this->assertFalse(isset($fields_data[101]));
    }

    function testValidateSubmitFieldRequiredNotSubmittedNoDefaultValue() {
        $this->field->setReturnValue('isValid', false);
        $this->field->setReturnValue('userCanSubmit', true);
        $this->field->setReturnValue('userCanUpdate', true);
        $this->field->setReturnValue('isRequired', true);
        $this->field->setReturnValue('hasDefaultValue', false);

        $GLOBALS['Language']->expectOnce('getText', array('plugin_tracker_common_artifact', 'err_required', $this->field->getLabel() .' ('. $this->field->getName() .')'));

        $fields_data = array();
        $this->assertFalse($this->artifact->validateFields($fields_data));
    }

    // ARTIFACT MODIFICATION
    function testValidateUpdateFieldSubmitted() {
        $this->field->setReturnValue('isValid', true);
        $this->field->setReturnValue('userCanSubmit', true);
        $this->field->setReturnValue('userCanUpdate', true);
        $this->field->setReturnValue('isRequired', true);

        $fields_data = array('101' => 666);
        $this->assertTrue($this->artifact_update->validateFields($fields_data));
        $this->assertNotNull($fields_data[101]);
        $this->assertEqual($fields_data[101], 666);
    }

    function testValidateUpdateFieldNotSubmitted() {
        $this->field->setReturnValue('isValid', true);
        $this->field->setReturnValue('userCanSubmit', true);
        $this->field->setReturnValue('userCanUpdate', true);
        $this->field->setReturnValue('isRequired', true);
        $this->changeset_value->setReturnValue('getValue', 999);

        $GLOBALS['Language']->expectNever('getText', array('plugin_tracker_common_artifact', 'err_required', $this->field->getLabel() .' ('. $this->field->getName() .')'));

        $fields_data = array();
        $this->assertTrue($this->artifact_update->validateFields($fields_data));
        $this->assertFalse(isset($fields_data[101]));
    }



    function testValidateFields_missing_fields_on_submission() {
        $tracker = new MockTracker();
        $factory = new MockTracker_FormElementFactory();

        $rules_manager = new MockTracker_RulesManager();
        $rules_manager->setReturnValue('validate', true);
        $tracker->setReturnReference('getRulesManager', $rules_manager);

        $field1  = new MockTracker_FormElement_Field();
        $field1->setReturnValue('getId', 101);
        $field1->setReturnValue('isValid', true);
        $field1->setReturnValue('userCanSubmit', true);
        $field1->setReturnValue('userCanUpdate', true);
        $field1->setReturnValue('isRequired', false);
        $field1->setReturnValue('hasDefaultValue', true);
        $field1->setReturnValue('getDefaultValue', 'default_value_field1');
        $field2  = new MockTracker_FormElement_Field();
        $field2->setReturnValue('getId', 102);
        $field2->setReturnValue('isValid', true);
        $field2->setReturnValue('userCanSubmit', true);
        $field2->setReturnValue('userCanUpdate', true);
        $field2->setReturnValue('isRequired', false);
        $field2->setReturnValue('hasDefaultValue', false);
        $field3  = new MockTracker_FormElement_Field();
        $field3->setReturnValue('getId', 103);
        $field3->setReturnValue('isValid', true);
        $field3->setReturnValue('isRequired', true);
        $field3->setReturnValue('userCanSubmit', true);
        $field3->setReturnValue('userCanUpdate', true);
        $factory->setReturnValue('getUsedFields', array($field1, $field2, $field3));
        $factory->setReturnValue('getAllFormElementsForTracker', array());

        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getLastChangeset', false); // changeset => artifact submission

        $workflow = new MockWorkflow();
        $workflow->setReturnValue('validate', true);
        $workflow->setReturnValue('validateGlobalRules', true);

        $artifact->setReturnReference('getWorkflow', $workflow);
        // field 101 and 102 are missing
        // 101 has a default value
        // 102 has no default value
        $fields_data = array('103' => 444);
        $this->assertTrue($artifact->validateFields($fields_data));
        $this->assertFalse(isset($fields_data[101]));
        $this->assertFalse(isset($fields_data[102]));
        $this->assertNotNull($fields_data[103]);
        $this->assertEqual($fields_data[103], 444);
    }

    function testValidateFields_missing_fields_on_update() {
        $tracker = new MockTracker();
        $factory = new MockTracker_FormElementFactory();

        $rules_manager = new MockTracker_RulesManager();
        $rules_manager->setReturnValue('validate', true);
        $tracker->setReturnReference('getRulesManager', $rules_manager);

        $field1  = new MockTracker_FormElement_Field();
        $field1->setReturnValue('getId', 101);
        $field1->setReturnValue('isValid', true);
        $field1->setReturnValue('userCanSubmit', true);
        $field1->setReturnValue('userCanUpdate', true);
        $field2  = new MockTracker_FormElement_Field();
        $field2->setReturnValue('getId', 102);
        $field2->setReturnValue('isValid', true);
        $field2->setReturnValue('userCanSubmit', true);
        $field2->setReturnValue('userCanUpdate', true);
        $field3  = new MockTracker_FormElement_Field();
        $field3->setReturnValue('getId', 103);
        $field3->setReturnValue('isValid', true);
        $field3->setReturnValue('userCanSubmit', true);
        $field3->setReturnValue('userCanUpdate', true);
        $factory->setReturnValue('getUsedFields', array($field1, $field2, $field3));
        $factory->setReturnValue('getAllFormElementsForTracker', array());

        $changeset = new MockTracker_Artifact_Changeset();
        $changeset_value1 = new MockTracker_Artifact_ChangesetValue();
        $changeset_value2 = new MockTracker_Artifact_ChangesetValue();
        $changeset_value2->setReturnValue('getValue', 987);
        $changeset_value3 = new MockTracker_Artifact_ChangesetValue();
        $changeset->setReturnReference('getValue', $changeset_value1, array($field1));
        $changeset->setReturnReference('getValue', $changeset_value2, array($field2));
        $changeset->setReturnReference('getValue', $changeset_value3, array($field3));

        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getLastChangeset', $changeset); // changeset => artifact update

        $workflow = new MockWorkflow();
        $workflow->setReturnValue('validate', true);
        $workflow->setReturnValue('validateGlobalRules', true);

        $artifact->setReturnReference('getWorkflow', $workflow);

        // field 102 is missing
        $fields_data = array('101' => 'foo',
                             '103' => 'bar');
        $this->assertTrue($artifact->validateFields($fields_data));
        $this->assertFalse(isset($fields_data[102]));
    }

    function testValidateFields_missing_fields_in_previous_changeset_on_update() {
        $tracker = new MockTracker();
        $factory = new MockTracker_FormElementFactory();

        $rules_manager = new MockTracker_RulesManager();
        $rules_manager->setReturnValue('validate', true);
        $tracker->setReturnReference('getRulesManager', $rules_manager);

        $field1  = new MockTracker_FormElement_Field();
        $field1->setReturnValue('getId', 101);
        $field1->setReturnValue('isValid', true);
        $field1->setReturnValue('userCanSubmit', true);
        $field1->setReturnValue('userCanUpdate', true);
        $field2  = new MockTracker_FormElement_Field();
        $field2->setReturnValue('getId', 102);
        $field2->setReturnValue('isValid', true);
        $field2->setReturnValue('userCanSubmit', true);
        $field2->setReturnValue('userCanUpdate', true);
        $field3  = new MockTracker_FormElement_Field();
        $field3->setReturnValue('getId', 103);
        $field3->setReturnValue('isValid', true);
        $field3->setReturnValue('userCanSubmit', true);
        $field3->setReturnValue('userCanUpdate', true);
        $factory->setReturnValue('getUsedFields', array($field1, $field2, $field3));
        $factory->setReturnValue('getAllFormElementsForTracker', array());

        $changeset = new MockTracker_Artifact_Changeset();
        $changeset_value1 = new MockTracker_Artifact_ChangesetValue();
        $changeset_value3 = new MockTracker_Artifact_ChangesetValue();
        $changeset->setReturnReference('getValue', $changeset_value1, array($field1));
        $changeset->setReturnValue('getValue', null, array($field2));
        $changeset->setReturnReference('getValue', $changeset_value3, array($field3));

        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getLastChangeset', $changeset); // changeset => artifact update

        $workflow = new MockWorkflow();
        $workflow->setReturnValue('validate', true);
        $workflow->setReturnValue('validateGlobalRules', true);

        $artifact->setReturnReference('getWorkflow', $workflow);

        // field 102 is missing
        $fields_data = array('101' => 'foo',
                             '103' => 'bar');
        $this->assertTrue($artifact->validateFields($fields_data));
        $this->assertFalse(isset($fields_data[102]));
    }

    function testValidateFields_basicnotvalid() {
        $tracker = new MockTracker();
        $factory = new MockTracker_FormElementFactory();

        $rules_manager = new MockTracker_RulesManager();
        $rules_manager->setReturnValue('validate', true);
        $tracker->setReturnReference('getRulesManager', $rules_manager);

        $field1  = new MockTracker_FormElement_Field();
        $field1->setReturnValue('getId', 101);
        $field1->setReturnValue('isValid', true);
        $field1->setReturnValue('userCanSubmit', true);
        $field1->setReturnValue('userCanUpdate', true);
        $field2  = new MockTracker_FormElement_Field();
        $field2->setReturnValue('getId', 102);
        $field2->setReturnValue('isValid', false);
        $field2->setReturnValue('isRequired', true);
        $field2->setReturnValue('userCanSubmit', true);
        $field2->setReturnValue('userCanUpdate', true);
        $field3  = new MockTracker_FormElement_Field();
        $field3->setReturnValue('getId', 103);
        $field3->setReturnValue('isValid', true);
        $field3->setReturnValue('userCanSubmit', true);
        $field3->setReturnValue('userCanUpdate', true);
        $factory->setReturnValue('getUsedFields', array($field1, $field2, $field3));
        $factory->setReturnValue('getAllFormElementsForTracker', array());

        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);

        $workflow = new MockWorkflow();
        $workflow->setReturnValue('validate', true);
        $workflow->setReturnValue('validateGlobalRules', true);

        $artifact->setReturnReference('getWorkflow', $workflow);

        $fields_data = array();
        $this->assertFalse($artifact->validateFields($fields_data));
        $this->assertFalse(isset($fields_data[101]));
        $this->assertFalse(isset($fields_data[102]));
        $this->assertFalse(isset($fields_data[103]));
    }

    function testValidateFields_valid() {
        $tracker = new MockTracker();
        $factory = new MockTracker_FormElementFactory();

        $rules_manager = new MockTracker_RulesManager();
        $rules_manager->setReturnValue('validate', true);
        $tracker->setReturnReference('getRulesManager', $rules_manager);

        $field1  = new MockTracker_FormElement_Field();
        $field1->setReturnValue('getId', 101);
        $field1->setReturnValue('isValid', true);
        $field1->setReturnValue('userCanSubmit', true);
        $field1->setReturnValue('userCanUpdate', true);
        $field2  = new MockTracker_FormElement_Field();
        $field2->setReturnValue('getId', 102);
        $field2->setReturnValue('userCanSubmit', true);
        $field2->setReturnValue('userCanUpdate', true);
        $field2->setReturnValue('isValid', true, array('*', '123'));
        $field2->setReturnValue('isValid', false, array('*', '456'));
        $field3  = new MockTracker_FormElement_Field();
        $field3->setReturnValue('getId', 103);
        $field3->setReturnValue('userCanSubmit', true);
        $field3->setReturnValue('userCanUpdate', true);
        $field3->setReturnValue('isValid', true);
        $factory->setReturnValue('getUsedFields', array($field1, $field2, $field3));
        $factory->setReturnValue('getAllFormElementsForTracker', array());

        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);

        $workflow = new MockWorkflow();
        $workflow->setReturnValue('validate', true);
        $workflow->setReturnValue('validateGlobalRules', true);

        $artifact->setReturnReference('getWorkflow', $workflow);

        $fields_data = array(
            102 => '123',
        );
        $this->assertTrue($artifact->validateFields($fields_data));
        $this->assertFalse(isset($fields_data[101]));
        $this->assertFalse(isset($fields_data[103]));

        $fields_data = array(
            102 => '456',
        );
        $this->assertFalse($artifact->validateFields($fields_data));
        $this->assertFalse(isset($fields_data[101]));
        $this->assertFalse(isset($fields_data[103]));
    }
}

class Tracker_Artifact_createInitialChangesetTest extends Tracker_ArtifactTest {

    function testCreateInitialChangeset() {
        $dao = new MockTracker_Artifact_ChangesetDao();
        $dao->setReturnValueAt(0, 'create', 1001, array(66, 1234, null));
        $dao->setReturnValueAt(1, 'create', 1002, array(66, 1234, null));
        $dao->expectCallCount('create', 1);

        $user = new MockUser();
        $user->setReturnValue('getId', 1234);
        $user->setReturnValue('isAnonymous', false);

        $tracker = new MockTracker();
        $factory = new MockTracker_FormElementFactory();

        $rules_manager = new MockTracker_RulesManager();
        $rules_manager->setReturnValue('validate', true);
        $tracker->setReturnReference('getRulesManager', $rules_manager);
        $tracker->setReturnValue('getFormElements', array());
        
        $field1  = new MockTracker_FormElement_Field();
        $field1->setReturnValue('getId', 101);
        $field1->setReturnValue('isValid', true);
        $field1->expectNever('saveNewChangeset');
        $field1->setReturnValue('userCanSubmit', true);
        $field1->setReturnValue('userCanUpdate', true);
        $field2  = new MockTracker_FormElement_Field();
        $field2->setReturnValue('getId', 102);
        $field2->setReturnValue('isValid', true, array('*', '123'));
        $field2->setReturnValue('isValid', false, array('*', '456'));
        $field2->setReturnValue('userCanSubmit', true);
        $field2->setReturnValue('userCanUpdate', true);
        $field2->expectOnce('saveNewChangeset');
        $field3  = new MockTracker_FormElement_Field();
        $field3->setReturnValue('getId', 103);
        $field3->setReturnValue('isValid', true);
        $field3->setReturnValue('userCanSubmit', true);
        $field3->setReturnValue('userCanUpdate', true);
        $field3->expectNever('saveNewChangeset');
        $factory->setReturnValue('getUsedFields', array($field1, $field2, $field3));
        $factory->setReturnValue('getAllFormElementsForTracker', array());

        $art_factory = new MockTracker_ArtifactFactory();

        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getChangesetDao', $dao);
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getId', 66);
        $artifact->setReturnValue('getLastChangeset', mock('Tracker_Artifact_Changeset_Null'));
        
        $artifact->setReturnReference('getArtifactFactory', $art_factory);

        $workflow = new MockWorkflow();
        $workflow->setReturnValue('validate', true);
        $workflow->setReturnValue('validateGlobalRules', true);

        $artifact->setReturnReference('getWorkflow', $workflow);

        $art_factory->expectOnce('save');

        $email = null; //not annonymous user

        // Valid
        $fields_data = array(
            102 => '123',
        );
        $this->assertEqual($artifact->createInitialChangeset($fields_data, $user, $email), 1001);
        $this->assertFalse(isset($fields_data[101]));
        $this->assertFalse(isset($fields_data[103]));

        // Not valid
        $fields_data = array(
            102 => '456',
        );
        $this->assertNull($artifact->createInitialChangeset($fields_data, $user, $email));
        $this->assertFalse(isset($fields_data[101]));
        $this->assertFalse(isset($fields_data[103]));
    }

    public function itCheckThatGlobalRulesAreValid() {
        $dao = new MockTracker_Artifact_ChangesetDao();
        $dao->setReturnValueAt(0, 'create', 1001, array(66, 1234, null));
        $dao->setReturnValueAt(1, 'create', 1002, array(66, 1234, null));
        $dao->expectNever('create');

        $user = new MockUser();
        $user->setReturnValue('getId', 1234);
        $user->setReturnValue('isAnonymous', false);

        $tracker = new MockTracker();
        $factory = new MockTracker_FormElementFactory();

        $rules_manager = new MockTracker_RulesManager();
        $rules_manager->setReturnValue('validate', true);
        $tracker->setReturnReference('getRulesManager', $rules_manager);
        $tracker->setReturnValue('getFormElements', array());

        $field1  = new MockTracker_FormElement_Field();
        $field1->setReturnValue('getId', 101);
        $field1->setReturnValue('isValid', true);
        $field1->expectNever('saveNewChangeset');
        $field1->setReturnValue('userCanSubmit', true);
        $field1->setReturnValue('userCanUpdate', true);
        $field2  = new MockTracker_FormElement_Field();
        $field2->setReturnValue('getId', 102);
        $field2->setReturnValue('isValid', true, array('*', '123'));
        $field2->setReturnValue('isValid', false, array('*', '456'));
        $field2->setReturnValue('userCanSubmit', true);
        $field2->setReturnValue('userCanUpdate', true);
        $field2->expectNever('saveNewChangeset');
        $field3  = new MockTracker_FormElement_Field();
        $field3->setReturnValue('getId', 103);
        $field3->setReturnValue('isValid', true);
        $field3->setReturnValue('userCanSubmit', true);
        $field3->setReturnValue('userCanUpdate', true);
        $field3->expectNever('saveNewChangeset');
        $factory->setReturnValue('getUsedFields', array($field1, $field2, $field3));
        $factory->setReturnValue('getAllFormElementsForTracker', array());

        $art_factory = new MockTracker_ArtifactFactory();

        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getChangesetDao', $dao);
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getId', 66);
        $artifact->setReturnReference('getArtifactFactory', $art_factory);
        $artifact->setReturnValue('getLastChangeset', mock('Tracker_Artifact_Changeset_Null'));

        $workflow = new MockWorkflow_Tracker_ArtifactTest_WorkflowNoPermsOnPostActionFields();
        $workflow->setReturnValue('validate', true);

        $artifact->setReturnReference('getWorkflow', $workflow);

        $art_factory->expectNever('save');

        $email = null; //not annonymous user

        // Valid
        $fields_data = array(
            101 => '123',
        );

        $updated_fields_data_by_workflow = array(
            101 => '123',
            102 => '456'
        );
        stub($workflow)->validateGlobalRules($updated_fields_data_by_workflow, $factory)->once()->returns(false);
        $this->assertFalse($artifact->createInitialChangeset($fields_data, $user, $email));
    }

    function testCreateInitialChangesetAnonymousNoEmail() {
        $dao = new MockTracker_Artifact_ChangesetDao();
        $dao->setReturnValueAt(0, 'create', 1001, array(66, 0, null));
        $dao->setReturnValueAt(1, 'create', 1002, array(66, 0, null));
        $dao->expectNever('create');

        $user = new MockUser();
        $user->setReturnValue('getId', 0);
        $user->setReturnValue('isAnonymous', true);
        $email = null; // anonymous user but no email...

        $tracker = new MockTracker();
        $factory = new MockTracker_FormElementFactory();
        $field1  = new MockTracker_FormElement_Field();
        $field1->setReturnValue('getId', 101);
        $field1->setReturnValue('isValid', true);
        $field1->expectNever('saveNewChangeset');
        $field1->setReturnValue('userCanSubmit', true);
        $field1->setReturnValue('userCanUpdate', true);
        $field2  = new MockTracker_FormElement_Field();
        $field2->setReturnValue('getId', 102);
        $field2->setReturnValue('isValid', true, array('*', '123'));
        $field2->setReturnValue('isValid', false, array('*', '456'));
        $field2->setReturnValue('userCanSubmit', true);
        $field2->setReturnValue('userCanUpdate', true);
        $field2->expectNever('saveNewChangeset');
        $field3  = new MockTracker_FormElement_Field();
        $field3->setReturnValue('getId', 103);
        $field3->setReturnValue('isValid', true);
        $field3->setReturnValue('userCanSubmit', true);
        $field3->setReturnValue('userCanUpdate', true);
        $field3->expectNever('saveNewChangeset');
        $factory->setReturnValue('getUsedFields', array($field1, $field2, $field3));
        $factory->setReturnValue('getAllFormElementsForTracker', array());
        $art_factory = new MockTracker_ArtifactFactory();

        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getChangesetDao', $dao);
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getId', 66);
        $artifact->setReturnReference('getArtifactFactory', $art_factory);

        $art_factory->expectNever('save');
        $this->response->expectCallCount('addFeedback', 1);

        // Valid
        $fields_data = array(
            102 => '123',
        );
        $this->assertNull($artifact->createInitialChangeset($fields_data, $user, $email));
    }

    function testCreateInitialChangesetAnonymousWithEmail() {
        $dao = new MockTracker_Artifact_ChangesetDao();
        $dao->setReturnValueAt(0, 'create', 1001, array(66, 0, 'anonymous@codendi.org'));
        $dao->expectCallCount('create', 1);

        $user = new MockUser();
        $user->setReturnValue('getId', 0);
        $user->setReturnValue('isAnonymous', true);
        $email = 'anonymous@codendi.org'; // anonymous user with email

        $tracker = new MockTracker();
        $factory = new MockTracker_FormElementFactory();
        $factory->setReturnValue('getAllFormElementsForTracker', array());

        $rules_manager = new MockTracker_RulesManager();
        $rules_manager->setReturnValue('validate', true);
        $tracker->setReturnReference('getRulesManager', $rules_manager);
        $tracker->setReturnValue('getFormElements', array());

        $field1  = new MockTracker_FormElement_Field();
        $field1->setReturnValue('getId', 101);
        $field1->setReturnValue('isValid', true);
        $field1->expectNever('saveNewChangeset');
        $field1->setReturnValue('userCanSubmit', true);
        $field1->setReturnValue('userCanUpdate', true);
        $field2  = new MockTracker_FormElement_Field();
        $field2->setReturnValue('getId', 102);
        $field2->setReturnValue('isValid', true, array('*', '123'));
        $field2->setReturnValue('isValid', false, array('*', '456'));
        $field2->setReturnValue('userCanSubmit', true);
        $field2->setReturnValue('userCanUpdate', true);
        $field2->expectOnce('saveNewChangeset');
        $field3  = new MockTracker_FormElement_Field();
        $field3->setReturnValue('getId', 103);
        $field3->setReturnValue('isValid', true);
        $field3->setReturnValue('userCanSubmit', true);
        $field3->setReturnValue('userCanUpdate', true);
        $field3->expectNever('saveNewChangeset');
        $factory->setReturnValue('getUsedFields', array($field1, $field2, $field3));
        $factory->setReturnValue('getAllFormElementsForTracker', array());

        $art_factory = new MockTracker_ArtifactFactory();

        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getChangesetDao', $dao);
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getId', 66);
        $artifact->setReturnReference('getArtifactFactory', $art_factory);
        $artifact->setReturnValue('getLastChangeset', mock('Tracker_Artifact_Changeset_Null'));

        $workflow = new MockWorkflow();
        $workflow->setReturnValue('validate', true);
        $workflow->setReturnValue('validateGlobalRules', true);

        $artifact->setReturnReference('getWorkflow', $workflow);

        $art_factory->expectOnce('save');
        $this->response->expectNever('addFeedback');

        // Valid
        $fields_data = array(
            102 => '123',
        );
        $this->assertEqual($artifact->createInitialChangeset($fields_data, $user, $email), 1001);
        $this->assertFalse(isset($fields_data[101]));
        $this->assertFalse(isset($fields_data[103]));
    }

    function testCreateInitialChangesetWithWorkflowAndNoPermsOnPostActionField() {

        $dao = new MockTracker_Artifact_ChangesetDao();
        $dao->setReturnValueAt(0, 'create', 1001, array(66, 1234, null));
        $dao->expectCallCount('create', 1);

        $user = new MockUser();
        $user->setReturnValue('getId', 1234);
        $user->setReturnValue('isAnonymous', false);

        $tracker = new MockTracker();
        $factory = new MockTracker_FormElementFactory();

        $rules_manager = new MockTracker_RulesManager();
        $rules_manager->setReturnValue('validate', true);
        $tracker->setReturnReference('getRulesManager', $rules_manager);
        $tracker->setReturnValue('getFormElements', array());

        $artifact = new Tracker_ArtifactTestVersion();
        $workflow = new MockWorkflow_Tracker_ArtifactTest_WorkflowNoPermsOnPostActionFields();
        $workflow->expectOnce('before');
        $workflow->setReturnValue('validate', true);
        $workflow->setReturnValue('validateGlobalRules', true);
        $artifact->setReturnValue('getWorkflow', $workflow);

        $field1  = new MockTracker_FormElement_Field();
        $field1->setReturnValue('getId', 101);
        $field1->setReturnValue('isValid', true);
        $workflow->setReturnValue('bypassPermissions', false, array($field1));
        $field1->expectOnce('saveNewChangeset');
        $field1->setReturnValue('userCanSubmit', true);

        $field2  = new MockTracker_FormElement_Field();
        $field2->setReturnValue('getId', 102);
        $field2->setReturnValue('isValid', true);
        $field2->setReturnValue('userCanSubmit', false);
        $workflow->setReturnValue('bypassPermissions', true, array($field2));
        $field2->expectOnce('saveNewChangeset', array('*', '*', '*', '*', $user, true, true));
        $factory->setReturnValue('getUsedFields', array($field1, $field2));
        $factory->setReturnValue('getAllFormElementsForTracker', array());

        $art_factory = new MockTracker_ArtifactFactory();

        $artifact->setReturnReference('getChangesetDao', $dao);
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getId', 66);
        $artifact->setReturnReference('getArtifactFactory', $art_factory);
        $artifact->setReturnValue('getLastChangeset', mock('Tracker_Artifact_Changeset_Null'));

        $art_factory->expectOnce('save');

        $email = null; //not annonymous user

        // Valid
        $fields_data = array(
            101 => '123',
        );

        $this->assertEqual($artifact->createInitialChangeset($fields_data, $user, $email), 1001);
    }

    function testCreateNewChangesetWithWorkflowAndNoPermsOnPostActionField() {
        $email   = null; //not anonymous user
        $comment = '';

        $comment_dao = new MockTracker_Artifact_Changeset_CommentDao();
        $comment_dao->expectCallCount('createNewVersion', 1);

        $dao = new MockTracker_Artifact_ChangesetDao();
        $dao->setReturnValueAt(0, 'create', 1001, array(66, 1234, null));
        $dao->setReturnValueAt(1, 'create', 1002, array(66, 1234, null));
        $dao->expectCallCount('create', 1);

        $user = new MockUser();
        $user->setReturnValue('getId', 1234);
        $user->setReturnValue('isAnonymous', false);

        $tracker = new MockTracker();
        $tracker->setReturnValue('getGroupId', 666);
        $tracker->setReturnValue('getItemName', 'foobar');
        $tracker->setReturnValue('getFormElements', array());

        $factory = new MockTracker_FormElementFactory();

        $rules_manager = new MockTracker_RulesManager();
        $rules_manager->setReturnValue('validate', true);
        $tracker->setReturnReference('getRulesManager', $rules_manager);

        $artifact = partial_mock('Tracker_Artifact', array(
            'getChangesetDao',
            'getChangesetCommentDao',
            'getFormElementFactory',
            'getTracker',
            'getId',
            'getLastChangeset',
            'getReferenceManager',
            'getChangesets',
            'getChangeset',
            'getUserManager',
            'getArtifactFactory',
            'getWorkflow',
            'validateFields',
            )
        );
        $workflow = new MockWorkflow();
        $workflow->expectCallCount('before', 2);
        $workflow->setReturnValue('validate', true);
        $workflow->setReturnValue('validateGlobalRules', true);
        $artifact->setReturnValue('getWorkflow', $workflow);

        $field1  = new MockTracker_FormElement_Field();
        $field1->setReturnValue('getId', 101);
        $field1->setReturnValue('isValid', true);
        $field1->setReturnValue('userCanUpdate', true);
        $workflow->setReturnValue('bypassPermissions', false, array($field1));
        $field1->expectOnce('saveNewChangeset');

        $field2  = new MockTracker_FormElement_Field();
        $field2->setReturnValue('getId', 102);
        $field2->setReturnValue('isValid', true);
        $field2->setReturnValue('userCanUpdate', false);
        $workflow->setReturnValue('bypassPermissions', true, array($field2));
        $field2->expectOnce('saveNewChangeset', array('*', '*', '*', '*', $user, false, true));
        $factory->setReturnValue('getUsedFields', array($field1, $field2));
        $factory->setReturnValue('getAllFormElementsForTracker', array());

        $new_changeset = new MockTracker_Artifact_Changeset();
        $new_changeset->expect('notify', array());

        $changeset = new MockTracker_Artifact_Changeset();
        $changeset->setReturnValue('getValues', array());
        $changeset->setReturnValue('hasChanges', true);
        $changeset_value1 = new MockTracker_Artifact_ChangesetValue();
        $changeset->setReturnReference('getValue', $changeset_value1, array($field1));

        $reference_manager = new MockReferenceManager();
        $reference_manager->expect('extractCrossRef', array(
            $comment,
            66,
            'plugin_tracker_artifact',
            666,
            $user->getId(),
            'foobar',
        ));

        $art_factory = new MockTracker_ArtifactFactory();

        $artifact->setReturnReference('getChangesetDao', $dao);
        $artifact->setReturnReference('getChangesetCommentDao', $comment_dao);
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getId', 66);
        $artifact->setReturnValue('validateFields', true);
        $artifact->setReturnReference('getLastChangeset', $changeset);
        $artifact->setReturnReference('getChangeset', $new_changeset);
        $artifact->setReturnReference('getReferenceManager', $reference_manager);
        $artifact->setReturnReference('getArtifactFactory', $art_factory);

        $art_factory->expectOnce('save');

        // Valid
        $fields_data = array(
            101 => '123',
            102 => '456'
        );

        $artifact->createNewChangeset($fields_data, $comment, $user, $email);
    }
}

class Tracker_Artifact_createNewChangesetTest extends Tracker_ArtifactTest {

    function testCreateNewChangeset() {
        $email   = null; //not annonymous user
        $comment = 'It did solve my problem, I let you close the artifact.';

        $this->response->expectCallCount('addFeedback', 0);

        $comment_dao = new MockTracker_Artifact_Changeset_CommentDao();
        $comment_dao->expectCallCount('createNewVersion', 1);

        $dao = new MockTracker_Artifact_ChangesetDao();
        $dao->setReturnValueAt(0, 'create', 1001, array(66, 1234, null));
        $dao->setReturnValueAt(1, 'create', 1002, array(66, 1234, null));
        $dao->expectCallCount('create', 1);

        $user = new MockUser();
        $user->setReturnValue('getId', 1234);
        $user->setReturnValue('isAnonymous', false);

        $tracker = new MockTracker();
        $tracker->setReturnValue('getGroupId', 666);
        $tracker->setReturnValue('getItemName', 'foobar');
        $tracker->setReturnValue('getFormElements', array());

        $factory = new MockTracker_FormElementFactory();

        $rules_manager = new MockTracker_RulesManager();
        $rules_manager->setReturnValue('validate', true);
        $tracker->setReturnReference('getRulesManager', $rules_manager);

        $field1  = new MockTracker_FormElement_Field();
        $field1->setReturnValue('getId', 101);
        $field1->setReturnValue('isValid', true);
        $field1->setReturnValue('userCanUpdate', true);
        $field1->expectOnce('saveNewChangeset');
        $field2  = new MockTracker_FormElement_Field();
        $field2->setReturnValue('getId', 102);
        $field2->setReturnValue('isValid', true, array('*', '123'));
        $field2->setReturnValue('isValid', false, array('*', '456'));
        $field2->setReturnValue('userCanUpdate', true);
        $field2->expectOnce('saveNewChangeset');
        $field3  = new MockTracker_FormElement_Field();
        $field3->setReturnValue('getId', 103);
        $field3->setReturnValue('isValid', true);
        $field3->expectOnce('saveNewChangeset');
        $field3->setReturnValue('userCanUpdate', true);
        $factory->setReturnValue('getUsedFields', array($field1, $field2, $field3));
        $factory->setReturnValue('getAllFormElementsForTracker', array());

        $new_changeset = new MockTracker_Artifact_Changeset();
        $new_changeset->expect('notify', array());

        $changeset = new MockTracker_Artifact_Changeset();
        $changeset->setReturnValue('hasChanges', true);
        $changeset->setReturnValue('getValues', array());
        $changeset_value1 = new MockTracker_Artifact_ChangesetValue();
        $changeset_value2 = new MockTracker_Artifact_ChangesetValue();
        $changeset_value3 = new MockTracker_Artifact_ChangesetValue();
        $changeset->setReturnReference('getValue', $changeset_value1, array($field1));
        $changeset->setReturnReference('getValue', $changeset_value2, array($field2));
        $changeset->setReturnReference('getValue', $changeset_value3, array($field3));

        $reference_manager = new MockReferenceManager();
        $reference_manager->expect('extractCrossRef', array(
            $comment,
            66,
            'plugin_tracker_artifact',
            666,
            $user->getId(),
            'foobar',
        ));

        $art_factory = new MockTracker_ArtifactFactory();

        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getChangesetDao', $dao);
        $artifact->setReturnReference('getChangesetCommentDao', $comment_dao);
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getId', 66);
        $artifact->setReturnReference('getLastChangeset', $changeset);
        $artifact->setReturnReference('getChangeset', $new_changeset);
        $artifact->setReturnReference('getReferenceManager', $reference_manager);
        $artifact->setReturnReference('getArtifactFactory', $art_factory);

        $art_factory->expectOnce('save');

        $workflow = new MockWorkflow();
        $workflow->expectCallCount('before', 2);
        $workflow->setReturnValue('validate', true);
        $workflow->setReturnValue('validateGlobalRules', true);
        $artifact->setReturnValue('getWorkflow', $workflow);

        // Valid
        $fields_data = array(
            102 => '123',
        );

        $artifact->createNewChangeset($fields_data, $comment, $user, $email);

        // Not valid
        $fields_data = array(
            102 => '456',
        );
        
        $this->expectException('Tracker_Exception');
        
        $artifact->createNewChangeset($fields_data, $comment, $user, $email);

    }

    public function itCheckThatGlobalRulesAreValid() {
        $email   = null; //not annonymous user
        $comment = 'It did solve my problem, I let you close the artifact.';

        $this->response->expectNever('addFeedback');

        $comment_dao = new MockTracker_Artifact_Changeset_CommentDao();
        $comment_dao->expectNever('createNewVersion');

        $dao = new MockTracker_Artifact_ChangesetDao();
        $dao->setReturnValueAt(0, 'create', 1001, array(66, 1234, null));
        $dao->setReturnValueAt(1, 'create', 1002, array(66, 1234, null));
        $dao->expectNever('create');

        $user = new MockUser();
        $user->setReturnValue('getId', 1234);
        $user->setReturnValue('isAnonymous', false);

        $tracker = new MockTracker();
        $tracker->setReturnValue('getGroupId', 666);
        $tracker->setReturnValue('getItemName', 'foobar');
        $tracker->setReturnValue('getFormElements', array());

        $factory = new MockTracker_FormElementFactory();
        
        $rules_manager = new MockTracker_RulesManager();
        $rules_manager->setReturnValue('validate', true);
        $tracker->setReturnReference('getRulesManager', $rules_manager);

        $field1  = new MockTracker_FormElement_Field();
        $field1->setReturnValue('getId', 101);
        $field1->setReturnValue('isValid', true);
        $field1->setReturnValue('userCanUpdate', true);
        $field1->expectNever('saveNewChangeset');
        $field2  = new MockTracker_FormElement_Field();
        $field2->setReturnValue('getId', 102);
        $field2->setReturnValue('isValid', true, array('*', '123'));
        $field2->setReturnValue('isValid', false, array('*', '456'));
        $field2->setReturnValue('userCanUpdate', true);
        $field2->expectNever('saveNewChangeset');
        $field3  = new MockTracker_FormElement_Field();
        $field3->setReturnValue('getId', 103);
        $field3->setReturnValue('isValid', true);
        $field3->expectNever('saveNewChangeset');
        $field3->setReturnValue('userCanUpdate', true);
        $factory->setReturnValue('getUsedFields', array($field1, $field2, $field3));
        $factory->setReturnValue('getAllFormElementsForTracker', array());

        $new_changeset = new MockTracker_Artifact_Changeset();
        $new_changeset->expectNever('notify');

        $changeset = new MockTracker_Artifact_Changeset();
        $changeset->setReturnValue('hasChanges', true);
        $changeset_value1 = new MockTracker_Artifact_ChangesetValue();
        $changeset_value2 = new MockTracker_Artifact_ChangesetValue();
        $changeset_value3 = new MockTracker_Artifact_ChangesetValue();
        $changeset->setReturnReference('getValue', $changeset_value1, array($field1));
        $changeset->setReturnReference('getValue', $changeset_value2, array($field2));
        $changeset->setReturnReference('getValue', $changeset_value3, array($field3));
        $changeset->setReturnValue('getValues', array());

        $reference_manager = new MockReferenceManager();
        $reference_manager->expect('extractCrossRef', array(
            $comment,
            66,
            'plugin_tracker_artifact',
            666,
            $user->getId(),
            'foobar',
        ));

        $art_factory = new MockTracker_ArtifactFactory();

        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getChangesetDao', $dao);
        $artifact->setReturnReference('getChangesetCommentDao', $comment_dao);
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getId', 66);
        $artifact->setReturnReference('getLastChangeset', $changeset);
        $artifact->setReturnReference('getChangeset', $new_changeset);
        $artifact->setReturnReference('getReferenceManager', $reference_manager);
        $artifact->setReturnReference('getArtifactFactory', $art_factory);

        $workflow = new MockWorkflow_Tracker_ArtifactTest_WorkflowNoPermsOnPostActionFields();
        $workflow->setReturnValue('validate', true);
        $artifact->setReturnValue('getWorkflow', $workflow);

        $art_factory->expectNever('save');

        $email = null; //not annonymous user

        $fields_data = array(
            101 => '123',
        );

        $updated_fields_data_by_workflow = array(
            101 => '123',
            102 => '456'
        );
        stub($workflow)->validateGlobalRules($updated_fields_data_by_workflow, $factory)->once()->returns(false);
        
        $this->expectException('Tracker_Exception');
        $artifact->createNewChangeset($fields_data, $comment, $user, $email);
    }

    function testCreateNewChangesetWithoutNotification() {
        $email   = null; //not anonymous user
        $comment = '';

        $this->response->expectCallCount('addFeedback', 0);

        $comment_dao = new MockTracker_Artifact_Changeset_CommentDao();
        $comment_dao->expectCallCount('createNewVersion', 1);

        $dao = new MockTracker_Artifact_ChangesetDao();
        $dao->setReturnValueAt(0, 'create', 1001, array(66, 1234, null));
        $dao->setReturnValueAt(1, 'create', 1002, array(66, 1234, null));
        $dao->expectCallCount('create', 1);

        $user = new MockUser();
        $user->setReturnValue('getId', 1234);
        $user->setReturnValue('isAnonymous', false);

        $tracker = new MockTracker();
        $tracker->setReturnValue('getGroupId', 666);
        $tracker->setReturnValue('getItemName', 'foobar');
        $tracker->setReturnValue('getFormElements', array());

        $factory = new MockTracker_FormElementFactory();

        $rules_manager = new MockTracker_RulesManager();
        $rules_manager->setReturnValue('validate', true);
        $tracker->setReturnReference('getRulesManager', $rules_manager);

        $field1  = new MockTracker_FormElement_Field();
        $field1->setReturnValue('getId', 101);
        $field1->setReturnValue('isValid', true);
        $field1->setReturnValue('userCanUpdate', true);
        $field1->expectOnce('saveNewChangeset');
        $field2  = new MockTracker_FormElement_Field();
        $field2->setReturnValue('getId', 102);
        $field2->setReturnValue('isValid', true, array('*', '123'));
        $field2->setReturnValue('isValid', false, array('*', '456'));
        $field2->setReturnValue('userCanUpdate', true);
        $field2->expectOnce('saveNewChangeset');
        $field3  = new MockTracker_FormElement_Field();
        $field3->setReturnValue('getId', 103);
        $field3->setReturnValue('isValid', true);
        $field3->expectOnce('saveNewChangeset');
        $field3->setReturnValue('userCanUpdate', true);
        $factory->setReturnValue('getUsedFields', array($field1, $field2, $field3));
        $factory->setReturnValue('getAllFormElementsForTracker', array());

        $new_changeset = new MockTracker_Artifact_Changeset();
        $new_changeset->expectNever('notify', array());

        $changeset = new MockTracker_Artifact_Changeset();
        $changeset->setReturnValue('hasChanges', true);
        $changeset_value1 = new MockTracker_Artifact_ChangesetValue();
        $changeset_value2 = new MockTracker_Artifact_ChangesetValue();
        $changeset_value3 = new MockTracker_Artifact_ChangesetValue();
        $changeset->setReturnReference('getValue', $changeset_value1, array($field1));
        $changeset->setReturnReference('getValue', $changeset_value2, array($field2));
        $changeset->setReturnReference('getValue', $changeset_value3, array($field3));
        $changeset->setReturnValue('getValues', array());

        $reference_manager = new MockReferenceManager();
        $reference_manager->expect('extractCrossRef', array(
            $comment,
            66,
            'plugin_tracker_artifact',
            666,
            $user->getId(),
            'foobar',
        ));

        $art_factory = new MockTracker_ArtifactFactory();

        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getChangesetDao', $dao);
        $artifact->setReturnReference('getChangesetCommentDao', $comment_dao);
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getId', 66);
        $artifact->setReturnReference('getLastChangeset', $changeset);
        $artifact->setReturnReference('getChangeset', $new_changeset);
        $artifact->setReturnReference('getReferenceManager', $reference_manager);
        $artifact->setReturnReference('getArtifactFactory', $art_factory);

        $art_factory->expectOnce('save');

        $workflow = new MockWorkflow();
        $workflow->expectCallCount('before', 2);
        $workflow->setReturnValue('validate', true);
        $workflow->setReturnValue('validateGlobalRules', true);
        $artifact->setReturnValue('getWorkflow', $workflow);

        // Valid
        $fields_data = array(
            102 => '123',
        );

        $artifact->createNewChangeset($fields_data, $comment, $user, $email, false);

        // Not valid
        $fields_data = array(
            102 => '456',
        );
        $this->expectException('Tracker_Exception');
        $artifact->createNewChangeset($fields_data, $comment, $user, $email);
    }

    function testDontCreateNewChangesetIfNoCommentOrNoChanges() {
        $this->language->setReturnValue('getText', 'no changes', array('plugin_tracker_artifact', 'no_changes', '*'));
        $this->response->expectNever('addFeedback');

        $comment_dao = new MockTracker_Artifact_Changeset_CommentDao();
        $comment_dao->expectNever('createNewVersion');

        $dao = new MockTracker_Artifact_ChangesetDao();
        $dao->expectNever('create');

        $user = new MockUser();
        $user->setReturnValue('getId', 1234);
        $user->setReturnValue('isAnonymous', false);

        $tracker = new MockTracker();
        $tracker->setReturnValue('getFormElements', array());
        $factory = new MockTracker_FormElementFactory();

        $rules_manager = new MockTracker_RulesManager();
        $rules_manager->setReturnValue('validate', true);
        $tracker->setReturnReference('getRulesManager', $rules_manager);

        $field1  = new MockTracker_FormElement_Field();
        $field1->setReturnValue('getId', 101);
        $field1->setReturnValue('isValid', true);
        $field1->setReturnValue('userCanUpdate', true);
        $field1->expectNever('saveNewChangeset');
        $field2  = new MockTracker_FormElement_Field();
        $field2->setReturnValue('getId', 102);
        $field2->setReturnValue('isValid', true);
        $field2->setReturnValue('userCanUpdate', true);
        $field2->expectNever('saveNewChangeset');
        $field3  = new MockTracker_FormElement_Field();
        $field3->setReturnValue('getId', 103);
        $field3->setReturnValue('isValid', true);
        $field3->setReturnValue('userCanUpdate', true);
        $field3->expectNever('saveNewChangeset');
        $factory->setReturnValue('getUsedFields', array($field1, $field2, $field3));
        $factory->setReturnValue('getAllFormElementsForTracker', array());

        $changeset = new MockTracker_Artifact_Changeset();
        $changeset->setReturnValue('hasChanges', false);
        $changeset->setReturnValue('getValues', array());
        $changeset_value1 = new MockTracker_Artifact_ChangesetValue();
        $changeset_value2 = new MockTracker_Artifact_ChangesetValue();
        $changeset_value3 = new MockTracker_Artifact_ChangesetValue();
        $changeset->setReturnReference('getValue', $changeset_value1, array($field1));
        $changeset->setReturnReference('getValue', $changeset_value2, array($field2));
        $changeset->setReturnReference('getValue', $changeset_value3, array($field3));

        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getChangesetDao', $dao);
        $artifact->setReturnReference('getChangesetCommentDao', $comment_dao);
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getId', 66);
        $artifact->setReturnReference('getLastChangeset', $changeset);

        $workflow = new MockWorkflow();
        $workflow->expectNever('before');
        $workflow->setReturnValue('validate', true);
        $workflow->setReturnValue('validateGlobalRules', true);
        $artifact->setReturnValue('getWorkflow', $workflow);

        $email   = null; //not annonymous user
        $comment = ''; //empty comment

        // Valid
        $fields_data = array();
        $this->expectException('Tracker_NoChangeException');
        $artifact->createNewChangeset($fields_data, $comment, $user, $email);
    }

    function testGetCommentators() {
        $c1 = new MockTracker_Artifact_Changeset();
        $c2 = new MockTracker_Artifact_Changeset();
        $c3 = new MockTracker_Artifact_Changeset();
        $c4 = new MockTracker_Artifact_Changeset();

        $u1 = new MockUser(); $u1->setReturnValue('getUserName', 'sandrae');
        $u2 = new MockUser(); $u2->setReturnValue('getUserName', 'marc');

        $um = new MockUserManager();
        $um->setReturnReference('getUserById', $u1, array(101));
        $um->setReturnReference('getUserById', $u2, array(102));

        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnValue('getChangesets', array($c1, $c2, $c3, $c4));
        $artifact->setReturnValue('getUserManager', $um);

        $c1->setReturnValue('getSubmittedBy', 101);
        $c2->setReturnValue('getSubmittedBy', 102);
        $c2->setReturnValue('getEmail', 'titi@example.com');
        $c3->setReturnValue('getSubmittedBy', null);
        $c3->setReturnValue('getEmail', 'toto@example.com');
        $c4->setReturnValue('getSubmittedBy', null);
        $c4->setReturnValue('getEmail', '');

        $this->assertEqual($artifact->getCommentators(), array(
            'sandrae',
            'marc',
            'toto@example.com',
        ));
    }

    function testUserCanViewTrackerAccessSubmitter() {
        $ugroup_ass = 101;
        $ugroup_sub = 102;

        // $assignee and $u_ass are in the same ugroup (UgroupAss)
        // $submitter and $u_sub are in the same ugroup (UgroupSub)
        // $other and $u are neither in UgroupAss nor in UgroupSub

        //
        $u = new MockUser();
        $u->setReturnValue('getId', 120);
        $u->setReturnValue('isMemberOfUgroup',false);
        $u->setReturnValue('isSuperUser', false);
        //
        $assignee = new MockUser();
        $assignee->setReturnValue('getId', 121);
        $assignee->setReturnValue('isMemberOfUgroup', true,  array(101, 222));
        $assignee->setReturnValue('isMemberOfUgroup', false, array(102, 222));
        $assignee->setReturnValue('isSuperUser', false);
        //
        $u_ass = new MockUser();
        $u_ass->setReturnValue('getId', 122);
        $u_ass->setReturnValue('isMemberOfUgroup', true,  array(101, 222));
        $u_ass->setReturnValue('isMemberOfUgroup', false, array(102, 222));
        $u_ass->setReturnValue('isSuperUser', false);
        //
        $submitter = new MockUser();
        $submitter->setReturnValue('getId', 123);
        $submitter->setReturnValue('isMemberOfUgroup', false, array(101, 222));
        $submitter->setReturnValue('isMemberOfUgroup', true,  array(102, 222));
        $submitter->setReturnValue('isSuperUser', false);
        //
        $u_sub = new MockUser();
        $u_sub->setReturnValue('getId', 124);
        $u_sub->setReturnValue('isMemberOfUgroup', false, array(101, 222));
        $u_sub->setReturnValue('isMemberOfUgroup', true,  array(102, 222));
        $u_sub->setReturnValue('isSuperUser', false);
        //
        $other = new MockUser();
        $other->setReturnValue('getId', 125);
        $other->setReturnValue('isMemberOfUgroup', false);
        $other->setReturnValue('isSuperUser', false);

        $user_manager = new MockUserManager();
        $user_manager->setReturnReference('getUserById', $u, array(120));
        $user_manager->setReturnReference('getUserById', $assignee, array(121));
        $user_manager->setReturnReference('getUserById', $u_ass, array(122));
        $user_manager->setReturnReference('getUserById', $submitter, array(123));
        $user_manager->setReturnReference('getUserById', $u_sub, array(124));
        $user_manager->setReturnReference('getUserById', $other, array(125));

        // $artifact_submitter has been submitted by $submitter and assigned to $u
        // $submitter, $u_sub should have the right to see it.
        // $other, $assignee, $u_ass and $u should not have the right to see it

        $tracker = new MockTracker();
        $tracker->setReturnValue('getId', 666);
        $tracker->setReturnValue('getGroupId', 222);
        $permissions = array("PLUGIN_TRACKER_ACCESS_SUBMITTER" => array(0 => $ugroup_sub));
        $tracker->setReturnReference('getPermissionsAuthorizedUgroups', $permissions);

        $artifact_submitter = new Tracker_ArtifactTestPermissions();
        $artifact_submitter->setReturnReference('getUserManager', $user_manager);
        $artifact_submitter->setReturnReference('getTracker', $tracker);
        $artifact_submitter->setReturnValue('useArtifactPermissions', false);
        $artifact_submitter->setReturnValue('getSubmittedBy', 123);

        $this->assertTrue($artifact_submitter->userCanView($submitter));
        $this->assertTrue($artifact_submitter->userCanView($u_sub));
        $this->assertFalse($artifact_submitter->userCanView($other));
        $this->assertFalse($artifact_submitter->userCanView($u));
        $this->assertFalse($artifact_submitter->userCanView($assignee));
        $this->assertFalse($artifact_submitter->userCanView($u_ass));
    }

    function testUserCanViewTrackerAccessAssignee() {
        $ugroup_ass = 101;
        $ugroup_sub = 102;

        // $assignee and $u_ass are in the same ugroup (UgroupAss - ugroup_id=101)
        // $submitter and $u_sub are in the same ugroup (UgroupSub - ugroup_id=102)
        // $other and $u are neither in UgroupAss nor in UgroupSub
        //
        $u = new MockUser();
        $u->setReturnValue('getId', 120);
        $u->setReturnValue('isMemberOfUgroup',false);
        $u->setReturnValue('isSuperUser', false);
        //
        $assignee = new MockUser();
        $assignee->setReturnValue('getId', 121);
        $assignee->setReturnValue('isMemberOfUgroup', true,  array(101, 222));
        $assignee->setReturnValue('isMemberOfUgroup', false, array(102, 222));
        $assignee->setReturnValue('isSuperUser', false);
        //
        $u_ass = new MockUser();
        $u_ass->setReturnValue('getId', 122);
        $u_ass->setReturnValue('isMemberOfUgroup', true,  array(101, 222));
        $u_ass->setReturnValue('isMemberOfUgroup', false, array(102, 222));
        $u_ass->setReturnValue('isSuperUser', false);
        //
        $submitter = new MockUser();
        $submitter->setReturnValue('getId', 123);
        $submitter->setReturnValue('isMemberOfUgroup', false, array(101, 222));
        $submitter->setReturnValue('isMemberOfUgroup', true,  array(102, 222));
        $submitter->setReturnValue('isSuperUser', false);
        //
        $u_sub = new MockUser();
        $u_sub->setReturnValue('getId', 124);
        $u_sub->setReturnValue('isMemberOfUgroup', false, array(101, 222));
        $u_sub->setReturnValue('isMemberOfUgroup', true,  array(102, 222));
        $u_sub->setReturnValue('isSuperUser', false);
        //
        $other = new MockUser();
        $other->setReturnValue('getId', 125);
        $other->setReturnValue('isMemberOfUgroup', false);
        $other->setReturnValue('isSuperUser', false);

        $user_manager = new MockUserManager();
        $user_manager->setReturnReference('getUserById', $u, array(120));
        $user_manager->setReturnReference('getUserById', $assignee, array(121));
        $user_manager->setReturnReference('getUserById', $u_ass, array(122));
        $user_manager->setReturnReference('getUserById', $submitter, array(123));
        $user_manager->setReturnReference('getUserById', $u_sub, array(124));
        $user_manager->setReturnReference('getUserById', $other, array(125));

        // $artifact_assignee has been submitted by $u and assigned to $assignee
        // $assignee and $u_ass should have the right to see it.
        // $other, $submitter, $u_sub and $u should not have the right to see it
        $tracker = new MockTracker();
        $tracker->setReturnValue('getId', 666);
        $tracker->setReturnValue('getGroupId', 222);
        $permissions = array("PLUGIN_TRACKER_ACCESS_ASSIGNEE" => array(0 => $ugroup_ass));
        $tracker->setReturnReference('getPermissionsAuthorizedUgroups', $permissions);

        $contributor_field = new MockTracker_FormElement_Field();
        $tracker->setReturnReference('getContributorField', $contributor_field);
        $artifact_assignee = new Tracker_ArtifactTestPermissions();
        $artifact_assignee->setReturnReference('getUserManager', $user_manager);
        $artifact_assignee->setReturnReference('getTracker', $tracker);
        $artifact_assignee->setReturnValue('useArtifactPermissions', false);
        $artifact_assignee->setReturnValue('getSubmittedBy', 120);
        $user_changeset_value = new MockTracker_Artifact_ChangesetValue();
        $contributors = array(121);
        $user_changeset_value->setReturnReference('getValue', $contributors);
        $artifact_assignee->setReturnReference('getValue', $user_changeset_value, array($contributor_field));

        $this->assertTrue($artifact_assignee->userCanView($assignee));
        $this->assertTrue($artifact_assignee->userCanView($u_ass));
        $this->assertFalse($artifact_assignee->userCanView($submitter));
        $this->assertFalse($artifact_assignee->userCanView($u_sub));
        $this->assertFalse($artifact_assignee->userCanView($other));
        $this->assertFalse($artifact_assignee->userCanView($u));

    }

    function testUserCanViewTrackerAccessSubmitterOrAssignee() {
        $ugroup_ass = 101;
        $ugroup_sub = 102;

        // $assignee and $u_ass are in the same ugroup (UgroupAss - ugroup_id=101)
        // $submitter and $u_sub are in the same ugroup (UgroupSub - ugroup_id=102)
        // $other and $u are neither in UgroupAss nor in UgroupSub
        //
        $u = new MockUser();
        $u->setReturnValue('getId', 120);
        $u->setReturnValue('isMemberOfUgroup',false);
        $u->setReturnValue('isSuperUser', false);
        //
        $assignee = new MockUser();
        $assignee->setReturnValue('getId', 121);
        $assignee->setReturnValue('isMemberOfUgroup', true,  array(101, 222));
        $assignee->setReturnValue('isMemberOfUgroup', false, array(102, 222));
        $assignee->setReturnValue('isSuperUser', false);
        //
        $u_ass = new MockUser();
        $u_ass->setReturnValue('getId', 122);
        $u_ass->setReturnValue('isMemberOfUgroup', true,  array(101, 222));
        $u_ass->setReturnValue('isMemberOfUgroup', false, array(102, 222));
        $u_ass->setReturnValue('isSuperUser', false);
        //
        $submitter = new MockUser();
        $submitter->setReturnValue('getId', 123);
        $submitter->setReturnValue('isMemberOfUgroup', false, array(101, 222));
        $submitter->setReturnValue('isMemberOfUgroup', true,  array(102, 222));
        $submitter->setReturnValue('isSuperUser', false);
        //
        $u_sub = new MockUser();
        $u_sub->setReturnValue('getId', 124);
        $u_sub->setReturnValue('isMemberOfUgroup', false, array(101, 222));
        $u_sub->setReturnValue('isMemberOfUgroup', true,  array(102, 222));
        $u_sub->setReturnValue('isSuperUser', false);
        //
        $other = new MockUser();
        $other->setReturnValue('getId', 125);
        $other->setReturnValue('isMemberOfUgroup', false);
        $other->setReturnValue('isSuperUser', false);

        $user_manager = new MockUserManager();
        $user_manager->setReturnReference('getUserById', $u, array(120));
        $user_manager->setReturnReference('getUserById', $assignee, array(121));
        $user_manager->setReturnReference('getUserById', $u_ass, array(122));
        $user_manager->setReturnReference('getUserById', $submitter, array(123));
        $user_manager->setReturnReference('getUserById', $u_sub, array(124));
        $user_manager->setReturnReference('getUserById', $other, array(125));

        // $artifact_subass has been submitted by $submitter and assigned to $assignee
        // $assignee, $u_ass, $submitter, $u_sub should have the right to see it.
        // $other and $u should not have the right to see it
        $tracker = new MockTracker();
        $tracker->setReturnValue('getId', 666);
        $tracker->setReturnValue('getGroupId', 222);
        $permissions = array("PLUGIN_TRACKER_ACCESS_ASSIGNEE"  => array(0 => $ugroup_ass),
                             "PLUGIN_TRACKER_ACCESS_SUBMITTER" => array(0 => $ugroup_sub)
                            );
        $tracker->setReturnReference('getPermissionsAuthorizedUgroups', $permissions);

        $contributor_field = new MockTracker_FormElement_Field();
        $tracker->setReturnReference('getContributorField', $contributor_field);
        $artifact_subass = new Tracker_ArtifactTestPermissions();
        $artifact_subass->setReturnReference('getUserManager', $user_manager);
        $artifact_subass->setReturnReference('getTracker', $tracker);
        $artifact_subass->setReturnValue('useArtifactPermissions', false);
        $artifact_subass->setReturnValue('getSubmittedBy', 123);
        $user_changeset_value = new MockTracker_Artifact_ChangesetValue();
        $contributors = array(121);
        $user_changeset_value->setReturnReference('getValue', $contributors);
        $artifact_subass->setReturnReference('getValue', $user_changeset_value, array($contributor_field));

        $this->assertTrue($artifact_subass->userCanView($submitter));
        $this->assertTrue($artifact_subass->userCanView($u_sub));
        $this->assertTrue($artifact_subass->userCanView($assignee));
        $this->assertTrue($artifact_subass->userCanView($u_ass));
        $this->assertFalse($artifact_subass->userCanView($other));
        $this->assertFalse($artifact_subass->userCanView($u));
    }

    function testUserCanViewTrackerAccessFull() {
        $ugroup_ass = 101;
        $ugroup_sub = 102;
        $ugroup_ful = 103;

        // $assignee is in (UgroupAss - ugroup_id=101)
        // $submitter is in (UgroupSub - ugroup_id=102)
        // $u is in (UgroupFul - ugroup_id=103);
        // $other do not belong to any ugroup
        //
        $u = new MockUser();
        $u->setReturnValue('getId', 120);
        $u->setReturnValue('isMemberOfUgroup', true,  array(103, 222));
        $u->setReturnValue('isMemberOfUgroup', false, array(101, 222));
        $u->setReturnValue('isMemberOfUgroup', false, array(102, 222));
        $u->setReturnValue('isSuperUser', false);
        //
        $assignee = new MockUser();
        $assignee->setReturnValue('getId', 121);
        $assignee->setReturnValue('isMemberOfUgroup', true,  array(101, 222));
        $assignee->setReturnValue('isMemberOfUgroup', false, array(102, 222));
        $assignee->setReturnValue('isMemberOfUgroup', false, array(103, 222));
        $assignee->setReturnValue('isSuperUser', false);
        //
        $submitter = new MockUser();
        $submitter->setReturnValue('getId', 122);
        $submitter->setReturnValue('isMemberOfUgroup', false, array(101, 222));
        $submitter->setReturnValue('isMemberOfUgroup', true,  array(102, 222));
        $submitter->setReturnValue('isMemberOfUgroup', false,  array(103, 222));
        $submitter->setReturnValue('isSuperUser', false);
        //
        $other = new MockUser();
        $other->setReturnValue('getId', 123);
        $other->setReturnValue('isMemberOfUgroup', false);
        $other->setReturnValue('isSuperUser', false);

        $user_manager = new MockUserManager();
        $user_manager->setReturnReference('getUserById', $u, array(120));
        $user_manager->setReturnReference('getUserById', $assignee, array(121));
        $user_manager->setReturnReference('getUserById', $submitter, array(122));
        $user_manager->setReturnReference('getUserById', $other, array(123));

        // $artifact_subass has been submitted by $submitter and assigned to $assignee
        // $u should have the right to see it.
        // $other, $submitter and assigned should not have the right to see it
        $tracker = new MockTracker();
        $tracker->setReturnValue('getId', 666);
        $tracker->setReturnValue('getGroupId', 222);
        $permissions = array("PLUGIN_TRACKER_ACCESS_FULL" => array(0 => $ugroup_ful));
        $tracker->setReturnReference('getPermissionsAuthorizedUgroups', $permissions);

        $contributor_field = new MockTracker_FormElement_Field();
        $tracker->setReturnReference('getContributorField', $contributor_field);
        $artifact_subass = new Tracker_ArtifactTestPermissions();
        $artifact_subass->setReturnReference('getUserManager', $user_manager);
        $artifact_subass->setReturnReference('getTracker', $tracker);
        $artifact_subass->setReturnValue('useArtifactPermissions', false);
        $artifact_subass->setReturnValue('getSubmittedBy', 123);
        $user_changeset_value = new MockTracker_Artifact_ChangesetValue();
        $contributors = array(121);
        $user_changeset_value->setReturnReference('getValue', $contributors);
        $artifact_subass->setReturnReference('getValue', $user_changeset_value, array($contributor_field));

        $this->assertFalse($artifact_subass->userCanView($submitter));
        $this->assertFalse($artifact_subass->userCanView($assignee));
        $this->assertFalse($artifact_subass->userCanView($other));
        $this->assertTrue($artifact_subass->userCanView($u));
    }
}

class Tracker_Artifact_ParentAndAncestorsTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $this->hierarchy_factory = mock('Tracker_HierarchyFactory');

        $this->sprint = anArtifact()->build();
        $this->sprint->setHierarchyFactory($this->hierarchy_factory);
    }

    public function itReturnsTheParentArtifactFromAncestors() {
        $release = anArtifact()->withId(1)->build();
        $product = anArtifact()->withId(2)->build();

        stub($this->hierarchy_factory)->getAllAncestors()->returns(array($release, $product));

        $this->assertEqual($release, $this->sprint->getParent(aUser()->build()));
    }

    public function itReturnsNullWhenNoAncestors() {
        stub($this->hierarchy_factory)->getAllAncestors()->returns(array());

        $this->assertEqual(null, $this->sprint->getParent(aUser()->build()));
    }
}

class Tracker_Artifact_DeleteArtifactTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $this->group_id    = 687;
        $tracker           = aTracker()->withProjectId($this->group_id)->build();
        $this->artifact_id = 12345;

        $this->artifact = partial_mock(
            'Tracker_Artifact',
            array('getChangesets', 'getDao', 'getPermissionsManager', 'getCrossReferenceManager'),
            array($this->artifact_id, null, null, null, null)
        );
        $this->artifact->setTracker($tracker);

        $this->user = aUser()->build();
    }

    public function itDeletesAllChangeset() {
        $changeset_1 = mock('Tracker_Artifact_Changeset');
        $changeset_1->expectOnce('delete', array($this->user));
        $changeset_2 = mock('Tracker_Artifact_Changeset');
        $changeset_2->expectOnce('delete', array($this->user));
        $changeset_3 = mock('Tracker_Artifact_Changeset');
        $changeset_3->expectOnce('delete', array($this->user));

        stub($this->artifact)->getChangesets()->returns(array($changeset_1, $changeset_2, $changeset_3));

        $dao = mock('Tracker_ArtifactDao');
        $dao->expectOnce('delete', array($this->artifact_id));
        $dao->expectOnce('deleteArtifactLinkReference', array($this->artifact_id));
        $dao->expectOnce('deletePriority', array($this->artifact_id));
        stub($this->artifact)->getDao()->returns($dao);

        $permissions_manager = mock('PermissionsManager');
        $permissions_manager->expectOnce('clearPermission', array('PLUGIN_TRACKER_ARTIFACT_ACCESS', $this->artifact_id));
        stub($this->artifact)->getPermissionsManager()->returns($permissions_manager);

        $cross_ref_mgr = mock('CrossReferenceManager');
        $cross_ref_mgr->expectOnce('deleteEntity', array($this->artifact_id, 'plugin_tracker_artifact', $this->group_id));
        stub($this->artifact)->getCrossReferenceManager()->returns($cross_ref_mgr);

        $this->artifact->delete($this->user);
    }
}

class Tracker_Artifact_SendCardInfoOnUpdate_BaseTest extends TuleapTestCase {

    /** @var Tracker_Artifact */
    protected $task;

    /** @var Tracker_Artifact */
    protected $user_story;

    /** @var int */
    protected $artifact_id = 123;

    /** @var int */
    protected $tracker_id = 101;

    /** @var Tracker_FormElement_Field_Computed */
    protected $computed_field;

    /** @var Tracker_FormElement_Field_Computed */
    protected $us_computed_field;

    public function setUp() {
        parent::setUp();
        $this->setUpAjaxRequestHeaders();

        $tracker_user_story_id     = 103;
        $user_story_id             = 107;
        $submitted_by              = 102;
        $submitted_on              = 1234567890;
        $use_artifact_permissions  = false;
        $tracker                   = aMockTracker()->withId($this->tracker_id)->build();
        $this->layout              = mock('Tracker_IDisplayTrackerLayout');
        $this->request             = aRequest()->with('func', 'artifact-update')->build();
        $this->user                = mock('User');
        $this->formelement_factory = mock('Tracker_FormElementFactory');
        $this->computed_field      = mock('Tracker_FormElement_Field_Computed');
        $this->us_computed_field   = mock('Tracker_FormElement_Field_Computed');
        $this->user_story          = mock('Tracker_Artifact');
        $tracker_user_story        = aMockTracker()->withId($tracker_user_story_id)->build();

        stub($this->user_story)->getTrackerId()->returns($tracker_user_story_id);
        stub($this->user_story)->getTracker()->returns($tracker_user_story);
        stub($this->user_story)->getId()->returns($user_story_id);

        $this->task = partial_mock(
            'Tracker_Artifact',
            array('createNewChangeset'),
            array($this->artifact_id, $this->tracker_id, $submitted_by, $submitted_on, $use_artifact_permissions)
        );
        $this->task->setTracker($tracker);
        $this->task->setFormElementFactory($this->formelement_factory);
        stub($this->task)->createNewChangeset()->returns(true);
        stub($this->formelement_factory)->getComputableFieldByNameForUser($tracker_user_story_id, Tracker::REMAINING_EFFORT_FIELD_NAME, $this->user)->returns($this->us_computed_field);

        stub($this->computed_field)->fetchCardValue($this->task)->returns(42);
        stub($this->us_computed_field)->fetchCardValue($this->user_story)->returns(23);
    }

    public function tearDown() {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = $this->old_request_with;
        parent::tearDown();
    }

    private function setUpAjaxRequestHeaders() {
        $this->old_request_with           = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] : null;
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHTTPREQUEST';
    }
}

class Tracker_Artifact_SendCardInfoOnUpdate_WithoutRemainingEffortTest extends Tracker_Artifact_SendCardInfoOnUpdate_BaseTest {

    public function itDoesNotSendAnythingIfNoRemainingEffortFieldIsDefinedOnTask() {
        $this->task->setAllAncestors(array());

        $expected = array();
        expect($GLOBALS['Response'])->sendJSON($expected)->once();

        $this->task->process($this->layout, $this->request, $this->user);
    }

    public function itSendsParentsRemainingEffortEvenIfTaskDontHaveOne() {
        $this->task->setAllAncestors(array($this->user_story));

        $user_story_id = $this->user_story->getId();
        $expected = array($user_story_id => array('remaining_effort' => 23));
        expect($GLOBALS['Response'])->sendJSON($expected)->once();

        $this->task->process($this->layout, $this->request, $this->user);
    }

    public function itDoesNotSendParentWhenParentHasNoRemainingEffortField() {
        $tracker_user_story_id = 110;
        $tracker_user_story    = aMockTracker()->withId($tracker_user_story_id)->build();
        $user_story_id         = 111;
        $user_story            = mock('Tracker_Artifact');

        stub($user_story)->getTracker()->returns($tracker_user_story);
        stub($user_story)->getId()->returns($user_story_id);
        $this->task->setAllAncestors(array($user_story));

        $user_story_id = $this->user_story->getId();
        $expected      = array();
        expect($GLOBALS['Response'])->sendJSON($expected)->once();

        $this->task->process($this->layout, $this->request, $this->user);
    }

}

class Tracker_Artifact_SendCardInfoOnUpdate_WithRemainingEffortTest extends Tracker_Artifact_SendCardInfoOnUpdate_BaseTest {

    public function setUp() {
        parent::setUp();
        stub($this->formelement_factory)->getComputableFieldByNameForUser($this->tracker_id, Tracker::REMAINING_EFFORT_FIELD_NAME, $this->user)->returns($this->computed_field);
    }

    public function itSendsTheRemainingEffortOfTheArtifactAndItsParent() {
        $this->task->setAllAncestors(array($this->user_story));

        $user_story_id = $this->user_story->getId();
        $expected      = array(
            $this->artifact_id => array('remaining_effort' => 42),
            $user_story_id     => array('remaining_effort' => 23)
        );
        expect($GLOBALS['Response'])->sendJSON($expected)->once();

        $this->task->process($this->layout, $this->request, $this->user);
    }

    public function itDoesNotSendParentsRemainingEffortWhenThereIsNoParent() {
        $this->task->setAllAncestors(array());

        $expected = array(
            $this->artifact_id => array('remaining_effort' => 42),
        );
        expect($GLOBALS['Response'])->sendJSON($expected)->once();

        $this->task->process($this->layout, $this->request, $this->user);
    }

    public function itDoesNotSendParentWhenParentHasNoRemainingEffortField() {
        $tracker_user_story_id = 110;
        $tracker_user_story    = aMockTracker()->withId($tracker_user_story_id)->build();
        $user_story_id         = 111;
        $user_story            = mock('Tracker_Artifact');

        stub($user_story)->getTracker()->returns($tracker_user_story);
        stub($user_story)->getId()->returns($user_story_id);
        $this->task->setAllAncestors(array($user_story));

        $expected = array(
            $this->artifact_id => array('remaining_effort' => 42),
        );
        expect($GLOBALS['Response'])->sendJSON($expected)->once();

        $this->task->process($this->layout, $this->request, $this->user);
    }
}

class Tracker_Artifact_getWorkflowTest extends TuleapTestCase {

    private $workflow;
    private $artifact;

    public function setUp() {
        $tracker_id = 123;
        $this->workflow = new Workflow(1, $tracker_id, 0, 0);
        $tracker = aMockTracker()->withId($tracker_id)->build();
        stub($tracker)->getWorkflow()->returns($this->workflow);
        $this->artifact = anArtifact()->build();
        $this->artifact->setTracker($tracker);
    }

    public function itGetsTheWorkflowFromTheTracker() {
        $workflow = $this->artifact->getWorkflow();
        $this->assertEqual($workflow, $this->workflow);
    }

    public function itInjectsItselfInTheWorkflow() {
        $workflow = $this->artifact->getWorkflow();
        $this->assertEqual($workflow->getArtifact(), $this->artifact);
    }
}

class Tracker_Artifact_SOAPTest extends TuleapTestCase {

    private $changeset_without_comments;
    private $changeset_with_submitted_by1;
    private $changeset_with_submitted_by2;
    private $changeset_without_submitted_by;

    private $tracker_id;
    private $email;

    private $timestamp1;
    private $timestamp2;
    private $timestamp3;

    private $body1;
    private $body2;
    private $body3;

    private $submitted_by1;
    private $submitted_by2;

    public function setUp() {
        $this->tracker_id    = 123;
        $this->email         = 'martin.goyot@enalean.com';

        $this->timestamp1    = 1355896800;
        $this->timestamp2    = 1355896802;
        $this->timestamp3    = 1355896805;

        $this->body1         = 'coucou';
        $this->body2         = 'hibou';
        $this->body3         = 'forêt';

        $this->submitted_by1 = 101;
        $this->submitted_by2 = 102;
        $this->changeset_with_submitted_by1 = mock('Tracker_Artifact_Changeset');
        $this->changeset_with_submitted_by2 = mock('Tracker_Artifact_Changeset');
        $this->changeset_without_submitted_by = mock('Tracker_Artifact_Changeset');

        $comment1 = new Tracker_Artifact_Changeset_Comment(1, $this->changeset_with_submitted_by1, 2, 3, $this->submitted_by1,  $this->timestamp1, $this->body1, 'text', 0);
        $comment2 = new Tracker_Artifact_Changeset_Comment(1, $this->changeset_with_submitted_by2, 2, 3, $this->submitted_by2,  $this->timestamp2, $this->body2, 'text', 0);
        $comment3 = new Tracker_Artifact_Changeset_Comment(1, $this->changeset_without_submitted_by, 2, 3, null,  $this->timestamp3, $this->body3, 'text', 0);

        stub($this->changeset_with_submitted_by1)->getComment()->returns($comment1);
        stub($this->changeset_with_submitted_by2)->getComment()->returns($comment2);
        stub($this->changeset_without_submitted_by)->getComment()->returns($comment3);

        stub($this->changeset_without_submitted_by)->getEmail()->returns($this->email);

        $this->changeset_without_comments = stub('Tracker_Artifact_Changeset')->getComment()->returns(null);
    }

    private function getBuiltArtifact(array $changesets) {
        $artifact = anArtifact()->withTrackerId($this->tracker_id)->withChangesets($changesets)->build();
        return $artifact;
    }

    public function itReturnsAnEmptySoapArrayWhenThereIsNoComments() {
        $changesets = array($this->changeset_without_comments);
        $artifact   = $this->getBuiltArtifact($changesets);

        $result = $artifact->exportCommentsToSOAP();
        $this->assertArrayEmpty($result);
    }

    public function itReturnsASOAPArrayWhenThereIsOneCommentButTwoChangesets() {
        $changesets = array($this->changeset_without_comments, $this->changeset_with_submitted_by1);
        $artifact   = $this->getBuiltArtifact($changesets);

        $result = $artifact->exportCommentsToSOAP();
        $expected = array(array(
            'submitted_by' => $this->submitted_by1,
            'email'        => null,
            'submitted_on' => $this->timestamp1,
            'body'         => $this->body1,
        ));

        $this->assertEqual($expected, $result);
    }

    public function itReturnsASOAPArrayWhenThereAreTwoComments() {
        $changesets = array($this->changeset_with_submitted_by1, $this->changeset_with_submitted_by2);
        $artifact   = $this->getBuiltArtifact($changesets);

        $result = $artifact->exportCommentsToSOAP();
        $expected = array(
            array(
                'submitted_by' => $this->submitted_by1,
                'email'        => null,
                'submitted_on' => $this->timestamp1,
                'body'         => $this->body1,
            ),
            array(
                'submitted_by' => $this->submitted_by2,
                'email'        => null,
                'submitted_on' => $this->timestamp2,
                'body'         => $this->body2,
            )
        );

        $this->assertEqual($expected, $result);
    }

    public function itReturnsAnEmailInTheSOAPArrayWhenThereIsNoSubmittedBy() {
        $changesets = array($this->changeset_without_submitted_by);
        $artifact   = $this->getBuiltArtifact($changesets);

        $result = $artifact->exportCommentsToSOAP();
        $expected = array(array(
            'submitted_by' => null,
            'email'        => $this->email,
            'submitted_on' => $this->timestamp3,
            'body'         => $this->body3,
        ));

        $this->assertEqual($expected, $result);
    }
}
?>
