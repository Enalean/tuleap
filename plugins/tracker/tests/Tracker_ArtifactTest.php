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

Mock::generate('Workflow');
class MockWorkflow_Tracker_ArtifactTest_WorkflowNoPermsOnPostActionFields extends MockWorkflow {
    function before(&$fields_data, $submitter) {
        $fields_data[102] = '456';
        return parent::before($fields_data, $submitter);
    }
}

class Tracker_ArtifactTest extends UnitTestCase {
    
    function setUp() {
        
        $this->response = new MockResponse();
        $GLOBALS['Response'] = $this->response;
        
        $this->language = new MockBaseLanguage();
        $GLOBALS['Language'] = $this->language;
        
        $tracker     = new MockTracker();
        $factory     = new MockTracker_FormElementFactory();
        $this->field = new MockTracker_FormElement_Field();
        $this->field->setReturnValue('getId', 101);
        $this->field->setReturnValue('getLabel', 'Summary');
        $this->field->setReturnValue('getName', 'summary');
        $factory->setReturnValue('getUsedFields', array($this->field));
        $this->artifact = new Tracker_ArtifactTestVersion();
        $this->artifact->setReturnReference('getFormElementFactory', $factory);
        $this->artifact->setReturnReference('getTracker', $tracker);
        $this->artifact->setReturnValue('getLastChangeset', false); // no changeset => artifact submission
        
        $this->artifact_update = new Tracker_ArtifactTestVersion();
        $this->artifact_update->setReturnReference('getFormElementFactory', $factory);
        $this->artifact_update->setReturnReference('getTracker', $tracker);
        $this->changeset = new MockTracker_Artifact_Changeset();
        $this->changeset_value = new MockTracker_Artifact_ChangesetValue();
        $this->changeset->setReturnReference('getValue', $this->changeset_value, array($this->field));
        $this->artifact_update->setReturnReference('getLastChangeset', $this->changeset); // changeset => artifact modification
        
        $rules_manager = new MockTracker_RulesManager();
        $rules_manager->setReturnValue('validate', true);
        $tracker->setReturnReference('getRulesManager', $rules_manager);
    }
    
    function tearDown() {
        unset($GLOBALS['Response']);
        unset($GLOBALS['Language']);
        unset($this->field);
        unset($this->artifact);
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
        
        
        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        
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
        
        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getLastChangeset', false); // changeset => artifact submission
        
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
        
        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        
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
        
        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        
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
        
        $art_factory = new MockTracker_ArtifactFactory();
        
        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getChangesetDao', $dao);
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getId', 66);
        $artifact->setReturnReference('getArtifactFactory', $art_factory);
        
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
        
        $rules_manager = new MockTracker_RulesManager();
        $rules_manager->setReturnValue('validate', true);
        $tracker->setReturnReference('getRulesManager', $rules_manager);
        
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
        
        $art_factory = new MockTracker_ArtifactFactory();
        
        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getChangesetDao', $dao);
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getId', 66);
        $artifact->setReturnReference('getArtifactFactory', $art_factory);
        
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
        
        $artifact = new Tracker_ArtifactTestVersion();
        $workflow = new MockWorkflow_Tracker_ArtifactTest_WorkflowNoPermsOnPostActionFields();
        $workflow->expectOnce('before');
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
        $field2->expectOnce('saveNewChangeset', array('*', '*', '*', '*', true, true));
        $factory->setReturnValue('getUsedFields', array($field1, $field2));
        
        $art_factory = new MockTracker_ArtifactFactory();
        
        $artifact->setReturnReference('getChangesetDao', $dao);
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getId', 66);
        $artifact->setReturnReference('getArtifactFactory', $art_factory);
        
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
        
        $factory = new MockTracker_FormElementFactory();
        
        $rules_manager = new MockTracker_RulesManager();
        $rules_manager->setReturnValue('validate', true);
        $tracker->setReturnReference('getRulesManager', $rules_manager);
        
        $artifact = new Tracker_ArtifactTestVersion();
        $workflow = new MockWorkflow_Tracker_ArtifactTest_WorkflowNoPermsOnPostActionFields();
        $workflow->expectOnce('before');
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
        $field2->expectOnce('saveNewChangeset', array('*', '*', '*', '*', false, true));
        $factory->setReturnValue('getUsedFields', array($field1, $field2));
        
        $new_changeset = new MockTracker_Artifact_Changeset();
        $new_changeset->expect('notify', array());
        
        $changeset = new MockTracker_Artifact_Changeset();
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
        $artifact->setReturnReference('getLastChangeset', $changeset);
        $artifact->setReturnReference('getChangeset', $new_changeset);
        $artifact->setReturnReference('getReferenceManager', $reference_manager);
        $artifact->setReturnReference('getArtifactFactory', $art_factory);
        
        $art_factory->expectOnce('save');
        
        // Valid
        $fields_data = array(
            101 => '123',
        );
        
        $artifact->createNewChangeset($fields_data, $comment, $user, $email);
    }
    
    function testCreateNewChangeset() {
        $email   = null; //not annonymous user
        $comment = 'It did solve my problem, I let you close the artifact.';

        $this->response->expectCallCount('addFeedback', 1);
        
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
        
        $new_changeset = new MockTracker_Artifact_Changeset();
        $new_changeset->expect('notify', array());
        
        $changeset = new MockTracker_Artifact_Changeset();
        $changeset->setReturnValue('hasChanges', true);
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
        $workflow->expectOnce('before');
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
        $artifact->createNewChangeset($fields_data, $comment, $user, $email);
    }
    
    function testCreateNewChangesetWithoutNotification() {
        $email   = null; //not anonymous user
        $comment = '';

        $this->response->expectCallCount('addFeedback', 1);
        
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
        $workflow->expectOnce('before');
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
        $artifact->createNewChangeset($fields_data, $comment, $user, $email, false);
    }
    
    function testDontCreateNewChangesetIfNoCommentOrNoChanges() {
        $this->language->setReturnValue('getText', 'no changes', array('plugin_tracker_artifact', 'no_changes', '*'));
        $this->response->expectOnce('addFeedback', array('info', 'no changes', CODENDI_PURIFIER_LIGHT));
        
        $comment_dao = new MockTracker_Artifact_Changeset_CommentDao();
        $comment_dao->expectNever('createNewVersion');
        
        $dao = new MockTracker_Artifact_ChangesetDao();
        $dao->expectNever('create');
        
        $user = new MockUser();
        $user->setReturnValue('getId', 1234);
        $user->setReturnValue('isAnonymous', false);
        
        $tracker = new MockTracker();
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
        
        $changeset = new MockTracker_Artifact_Changeset();
        $changeset->setReturnValue('hasChanges', false);
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
        $artifact->setReturnValue('getWorkflow', $workflow);
        
        $email   = null; //not annonymous user
        $comment = ''; //empty comment
        
        // Valid
        $fields_data = array();
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
        $perms_tracker_access_full = false;
        $perms_tracker_access_assignee = false;
        $perms_tracker_access_submitter = array(
                    array('ugroup_id' => $ugroup_sub)
                );
        $tracker->setReturnReference('permission_db_authorized_ugroups', $perms_tracker_access_full,      array('PLUGIN_TRACKER_ACCESS_FULL'));
        $tracker->setReturnReference('permission_db_authorized_ugroups', $perms_tracker_access_assignee,  array('PLUGIN_TRACKER_ACCESS_ASSIGNEE'));
        $tracker->setReturnReference('permission_db_authorized_ugroups', $perms_tracker_access_submitter, array('PLUGIN_TRACKER_ACCESS_SUBMITTER'));
        
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
        $perms_tracker_access_full = false;
        $perms_tracker_access_assignee = array(
                    array('ugroup_id' => $ugroup_ass)
                );
        $perms_tracker_access_submitter = false;
        $tracker->setReturnReference('permission_db_authorized_ugroups', $perms_tracker_access_full,      array('PLUGIN_TRACKER_ACCESS_FULL'));
        $tracker->setReturnReference('permission_db_authorized_ugroups', $perms_tracker_access_assignee,  array('PLUGIN_TRACKER_ACCESS_ASSIGNEE'));
        $tracker->setReturnReference('permission_db_authorized_ugroups', $perms_tracker_access_submitter, array('PLUGIN_TRACKER_ACCESS_SUBMITTER'));
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
        $perms_tracker_access_full = false;
        $perms_tracker_access_assignee = array(
                    array('ugroup_id' => $ugroup_ass)
                );
        $perms_tracker_access_submitter = array(
                    array('ugroup_id' => $ugroup_sub)
                );
        $tracker->setReturnReference('permission_db_authorized_ugroups', $perms_tracker_access_full,      array('PLUGIN_TRACKER_ACCESS_FULL'));
        $tracker->setReturnReference('permission_db_authorized_ugroups', $perms_tracker_access_assignee,  array('PLUGIN_TRACKER_ACCESS_ASSIGNEE'));
        $tracker->setReturnReference('permission_db_authorized_ugroups', $perms_tracker_access_submitter, array('PLUGIN_TRACKER_ACCESS_SUBMITTER'));
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
}

class Tracker_Artifact_Process_AssociateArtifactTo extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->user = new MockUser();
        $this->request = new Codendi_Request(array(
            'func'               => 'associate-artifact-to', 
            'linked-artifact-id' => 987));
    }

    public function itCreatesANewChangesetWithdrawingAnExistingAssociation() {
        $this->request = new Codendi_Request(array(
            'func'               => 'unassociate-artifact-to', 
            'linked-artifact-id' => 987));

        $tracker = aTracker()->withId(456)->build();
        
        $artifact = $this->GivenAnArtifact($tracker);
        
        $field = anArtifactLinkField()->withId(1002)->build();
        $factory = $this->GivenAFactoryThatReturns(array($field))->whenCalledWith($tracker);
        $artifact->setFormElementFactory($factory);

        $expected_field_data = array($field->getId() => array('new_values' => '',
                                                              'removed_values' => array(987 => 1)));
        $no_comment = $no_email = '';
        
        $artifact->expectOnce('createNewChangeset', array($expected_field_data, $no_comment, $this->user, $no_email));
        
        $artifact->process(new MockTrackerManager(), $this->request, $this->user);
    }
    
    public function itCreatesANewChangesetWithANewAssociation() {
        $tracker = aTracker()->withId(456)->build();
        
        $artifact = $this->GivenAnArtifact($tracker);
        
        $field = anArtifactLinkField()->withId(1002)->build();
        $factory = $this->GivenAFactoryThatReturns(array($field))->whenCalledWith($tracker);
        $artifact->setFormElementFactory($factory);

        $expected_field_data = array($field->getId() => array('new_values' => 987));
        $no_comment = $no_email = '';
        
        $artifact->expectOnce('createNewChangeset', array($expected_field_data, $no_comment, $this->user, $no_email));
        
        $artifact->process(new MockTrackerManager(), $this->request, $this->user);
    }
    
    public function itReturnsAnErrorCodeWhenItHasNoArtifactLinkField() {
        $tracker = aTracker()->withId(456)->build();
        
        $artifact = $this->GivenAnArtifact($tracker);
        
        $factory = $this->GivenAFactoryThatReturns(array())->whenCalledWith($tracker);
        $artifact->setFormElementFactory($factory);
        
        $artifact->expectNever('createNewChangeset');
        $GLOBALS['Response']->expectOnce('sendStatusCode', array(400));
        $GLOBALS['Language']->setReturnValue('getText', 'The destination artifact must have a artifact link field.', array('plugin_tracker', 'must_have_artifact_link_field'));
        $this->expectFeedback('error', 'The destination artifact must have a artifact link field.');
        
        $artifact->process(new MockTrackerManager(), $this->request, $this->user);
    }

    private function GivenAFactoryThatReturns($artifactLinkFields) {
        return new FormElementFactory_PendingMock($artifactLinkFields);
    }

    private function GivenAnArtifact($tracker) {
        $artifact = TestHelper::getPartialMock('Tracker_Artifact', array('createNewChangeset'));
        $artifact->setTracker($tracker);
        return $artifact;
    }
}

class FormElementFactory_PendingMock {
    public function __construct($returnVal) {
        $this->returnVal = $returnVal;
    }
    
    public function whenCalledWith($argument) {
        $factory = new MockTracker_FormElementFactory();
        $factory->setReturnValue('getUsedArtifactLinkFields', $this->returnVal, array($argument));
        return $factory;
    }
}


class Tracker_Artifact_getArtifactLinks_Test extends TuleapTestCase {

    public function itReturnsAnEmptyListWhenThereIsNoArtifactLinkField() {
        $tracker = aTracker()->withId('101')->build();
        $user    = aUser()->build();

        $factory = $this->GivenAFactoryThatReturns(array())->whenCalledWith($tracker);
        $artifact = anArtifact()
                    ->withTracker($tracker)
                    ->withFormElementFactory($factory)
                    ->build();

        $links = $artifact->getLinkedArtifacts($user);
        $this->assertEqual(array(), $links);
    }
    
    public function itReturnsAlistOfTheLinkedArtifacts() {
        $user          = aUser()->build();
        $expected_list = array(
            new Tracker_Artifact(111, null, null, null, null),
            new Tracker_Artifact(222, null, null, null, null)
        );
        
        $changeset = new MockTracker_Artifact_Changeset();
        
        $field = mock('Tracker_FormElement_Field_ArtifactLink');
        stub($field)->getLinkedArtifacts($changeset, $user)->returns($expected_list);
        stub($field)->userCanRead($user)->returns(true);
        
        $tracker = aTracker()->build();
        $factory = $this->GivenAFactoryThatReturns(array($field))->whenCalledWith($tracker);
        
        $artifact = anArtifact()
                    ->withTracker($tracker)
                    ->withFormElementFactory($factory)
                    ->withChangesets(array($changeset))
                    ->build();
        
        $this->assertEqual($expected_list, $artifact->getLinkedArtifacts($user));
    }

    public function itReturnsEmptyArrayIfUserCannotSeeArtifactLinkField() {
        $current_user = aUser()->build();
        
        $field        = new MockTracker_FormElement_Field_ArtifactLink();
        stub($field)->userCanRead($current_user)->returns(false);
        
        $tracker      = aTracker()->build();
        $factory      = $this->GivenAFactoryThatReturns(array($field))->whenCalledWith($tracker);
        $artifact     = anArtifact()
                        ->withTracker($tracker)
                        ->withFormElementFactory($factory)
                        ->build();

        $this->assertEqual($artifact->getAnArtifactLinkField($current_user), null);
    }
    
    /**
     * Artifact Links
     * - art 1
     *   - art 2
     *   - art 3
     * - art 2 (should be hidden)
     */
    public function itReturnsOnlyOneIfTwoLinksIdentical() {
        $user          = aUser()->build();
        $changeset = new MockTracker_Artifact_Changeset();
        
        $child_tracker  = aTracker()->withId(33)->build();
        $non_child_tracker = aTracker()->withId(44)->build();
        
        $factory = new MockTracker_FormElementFactory();
        
        $parent_tracker = aTracker()->withId(22)->build();
        $child_tracker  = aTracker()->withId(33)->build();
        $non_child_tracker = aTracker()->withId(44)->build();
        
        $artifact = anArtifact()
                    ->withTracker($parent_tracker)
                    ->withFormElementFactory($factory)
                    ->withChangesets(array($changeset))
                    ->build();
        
        $artifact2 = mock('Tracker_Artifact');
        stub($artifact2)->getLinkedArtifacts()->returns(array());
        
        $artifact3 = mock('Tracker_Artifact');
        stub($artifact3)->getLinkedArtifacts()->returns(array());
        
        $artifact1 = mock('Tracker_Artifact');
        stub($artifact1)->getLinkedArtifacts()->returns(array($artifact2, $artifact3));
        
        $expected_list = array($artifact1, $artifact2);
        
        $field = mock('Tracker_FormElement_Field_ArtifactLink');
        stub($field)->getLinkedArtifacts($changeset, $user)->returns($expected_list);
        stub($field)->userCanRead($user)->returns(true);
        $factory->setReturnValue('getUsedArtifactLinkFields', array($field));
        //$artifactLinks = $artifactLinkToBeKept->getLinkedArtifacts($user);
        
        $expected_result = array($artifact1);
        $this->assertEqual($expected_result, $artifact->getUniqueLinkedArtifacts($user));
    }
    
    private function GivenAFactoryThatReturns($artifactLinkFields) {
        return new FormElementFactory_PendingMock($artifactLinkFields);
    }
    
    private function GivenAHierarchyFactory($dao = null) {
        if (!$dao) {
            $dao = new MockTracker_Hierarchy_Dao();
        }
        return new Tracker_HierarchyFactory($dao, mock('TrackerFactory'));
    }
}

class Tracker_Artifact_RedirectUrlTest extends TuleapTestCase {
    public function itRedirectsToTheTrackerHomePageByDefault() {
        $request_data = array();
        $tracker_id   = 20;
        $redirect_uri = $this->getRedirectUrlFor($request_data, $tracker_id, null);
        $this->assertEqual(TRACKER_BASE_URL."/?tracker=$tracker_id", $redirect_uri);
    }
    
    public function itStaysOnTheCurrentArtifactWhen_submitAndStay_isSpecified() {
        $request_data = array('submit_and_stay' => true);
        $artifact_id  = 66;
        $redirect_uri = $this->getRedirectUrlFor($request_data, null, $artifact_id);
        $this->assertEqual(TRACKER_BASE_URL."/?aid=$artifact_id", $redirect_uri);
    }
    
    public function itReturnsToThePreviousArtifactWhen_fromAid_isGiven() {
        $from_aid     = 33;
        $request_data = array('from_aid' => $from_aid);
        $artifact_id  = 66;
        $redirect_uri = $this->getRedirectUrlFor($request_data, null, $artifact_id);
        $this->assertEqual(TRACKER_BASE_URL."/?aid=$from_aid", $redirect_uri);
    }

    public function testSubmitAndStayHasPrecedenceOver_fromAid() {
        $from_aid     = 33;
        $artifact_id  = 66;
        $request_data = array('from_aid' => $from_aid,
                              'submit_and_stay' => true);
        $redirect_uri = $this->getRedirectUrlFor($request_data, null, $artifact_id);
        $this->assertUriHasArgument($redirect_uri, "aid", $artifact_id);
        $this->assertUriHasArgument($redirect_uri, "from_aid", $from_aid);
    }

    public function testSubmitAndStayHasPrecedenceOver_returnToAid() {
        $artifact_id  = 66;
        $request_data = array('submit_and_stay' => true);
        $redirect_uri = $this->getRedirectUrlFor($request_data, null, $artifact_id);
        $this->assertUriHasArgument($redirect_uri, "aid", $artifact_id);
    }
    
    private function getRedirectUrlFor($request_data, $tracker_id, $artifact_id) {
        $request  = new Codendi_Request($request_data);
        $artifact = new Tracker_Artifact($artifact_id, $tracker_id, null, null, false);
        return $artifact->getRedirectUrlAfterArtifactUpdate($request);
        
    }

}


?>
