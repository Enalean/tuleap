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
 
require_once('bootstrap.php');
Mock::generate('Tracker_Artifact');

Mock::generate('Tracker');

Mock::generate('Tracker_FormElementFactory');

Mock::generate('Tracker_Artifact_Changeset');

Mock::generatePartial(
    'Tracker_FormElement_Field', 
    'Tracker_FormElement_FieldTestVersion', 
    array(
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
        'isRequired',
        'validate',
        'getLabel',
        'getName',
        'getSoapAvailableValues',
        'fetchSubmitValueMasschange'
    )
);

Mock::generatePartial(
    'Tracker_FormElement_Field', 
    'Tracker_FormElement_FieldTestVersion2', 
    array(
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
        'isRequired',
        'validate',
        'getLabel',
        'getName',
        'getId',
        'getSoapAvailableValues',
        'isValid',
        'userCanUpdate',
        'setHasErrors',
        'fetchSubmitValueMasschange'
    )
);

require_once('common/include/Response.class.php');
Mock::generate('Response');

require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

Mock::generate('Tracker_Artifact_ChangesetValue');

class Tracker_FormElement_FieldTest extends UnitTestCase {
    function setUp() {
        $this->response = new MockResponse();
        $this->language = new MockBaseLanguage();
        $this->changeset_value = new MockTracker_Artifact_ChangesetValue();
        
        $GLOBALS['Response'] = $this->response;
        $GLOBALS['Language'] = $this->language;
    }
    
    function tearDown() {
        unset($GLOBALS['Response']);
        unset($GLOBALS['Language']);
    }
    
    function testValidateField() {
        // 0 => Field has value in last changeset
        // 1 => Field submitted in the request
        // 2 => User can update
        // 3 => Field is required
        //
        // 4 => Is valid? => 
        //      '-' => no need to check
        //      'R' => Error due to required
        //      'P' => Error due to perms
        //      'V' => Depends on field->isValid() (see next col)
        // 5 => Should we call field->isValid() ?
        // 6 => Value in new changeset =>
        //      '-' => keep the old value taken from last changeset
        //        0 => No value
        //        1 => Submitted value
        $matrix = array(
            array(0, 0, 0, 0, '-', 0,   0),
            array(0, 0, 0, 1, '-', 0,   0),
            array(0, 0, 1, 0, '-', 0,   0),
            array(0, 0, 1, 1, 'R', 0,   0),
                                        
            array(0, 1, 0, 0, 'P', 0,   0),
            array(0, 1, 0, 1, 'P', 0,   0),
            array(0, 1, 1, 0, 'V', 1,   1),
            array(0, 1, 1, 1, 'V', 1,   1),
            
            array(1, 0, 0, 0, '-', 0, '-'),
            array(1, 0, 0, 1, '-', 0, '-'),
            array(1, 0, 1, 0, '-', 0, '-'),
            array(1, 0, 1, 1, '-', 0, '-'),
            
            array(1, 1, 0, 0, 'P', 0, '-'),
            array(1, 1, 0, 1, 'P', 0, '-'),
            array(1, 1, 1, 0, 'V', 1,   1),
            array(1, 1, 1, 1, 'V', 1,   1),
        );
        
        $artifact_update = new MockTracker_Artifact();
        $changeset_value = new MockTracker_Artifact_ChangesetValue();
        
        foreach ($matrix as $case) {
            $this->setUp();
            
            $field = new Tracker_FormElement_FieldTestVersion2();
            $field->setReturnValue('getId', 101);
            $field->setReturnValue('getLabel', 'Summary');
            $field->setReturnValue('getName', 'summary');
            
            if ($case[0]) {
                $last_changeset_value = $changeset_value;
            } else {
                $last_changeset_value = null;
            }
            if ($case[1]) {
                $submitted_value = 'Toto';
            } else {
                $submitted_value = null; //null === no submitted value /!\ != from '' or '0' /!\
            }
            if ($case[2]) {
                $field->setReturnValue('userCanUpdate', true);
            } else {
                $field->setReturnValue('userCanUpdate', false);
            }
            if ($case[3]) {
                $field->setReturnValue('isRequired', true);
            } else {
                $field->setReturnValue('isRequired', false);
            }
            // 4 => Is valid?
            switch ((string)$case[4]) {
            // no need to check
            case '-':
                $field->expectNever('isValid');
                $field->expectNever('setHasErrors');
                $is_valid = true;
                break;
            // Error due to required
            case 'R':
                $field->expectNever('isValid');
                $field->expectOnce('setHasErrors', array(true));
                $GLOBALS['Language']->expectOnce('getText', array('plugin_tracker_common_artifact', 'err_required', $field->getLabel() .' ('. $field->getName() .')'));
                $GLOBALS['Response']->expectOnce('addFeedback', array('error', '*'));
                $is_valid = false;
                break;
            // Error due to perms
            case 'P':
                $field->expectNever('isValid');
                $field->expectOnce('setHasErrors', array(true));
                $GLOBALS['Language']->expectOnce('getText', array('plugin_tracker_common_artifact', 'bad_field_permission_update', $field->getLabel()));
                $GLOBALS['Response']->expectOnce('addFeedback', array('error', '*'));
                $is_valid = false;
                break;
            // Depends on field->isValid()
            case 'V':
                $field->expectOnce('isValid');
                $field->expectNever('setHasErrors');
                $field->setReturnValue('isValid', true);
                $is_valid = true;
                break;
            default:
                break;
            }
            
            $result = $field->validateField($artifact_update, $submitted_value, $last_changeset_value);
            $this->assertEqual($result, $is_valid);
            $this->tearDown();
            unset($field);
        }
    }
    
    function testIsValid_not_required() {
        $this->response->expectNever('addFeedback', array('error', 'Status is required'));
        $artifact = new MockTracker_Artifact();
        $field = new Tracker_FormElement_FieldTestVersion();
        $field->setReturnValue('getLabel', 'Status');
        $field->setReturnValue('isRequired', false);
        $field->setReturnValue('validate', true, array('*', ''));
        $field->setReturnValue('validate', false, array('*', '123'));
        
        $this->assertFalse($field->hasErrors());
        
        $this->assertTrue($field->isValid($artifact, ''));
        $this->assertFalse($field->hasErrors());
        
        $this->assertFalse($field->isValid($artifact, '123'));
        $this->assertTrue($field->hasErrors());
    }
    
    function testIsValid_required() {
             
        $artifact = new MockTracker_Artifact();
        $field = new Tracker_FormElement_FieldTestVersion();
        $field->setReturnValue('getLabel', 'Status');
        $field->setReturnValue('getName', 'status');
        $field->setReturnValue('isRequired', true);
        $field->expectCallCount('isRequired', 2);
        $field->setReturnValue('validate', true);
        $field->expectOnce('validate');
        
        $this->language->expect('getText', array('plugin_tracker_common_artifact', 'err_required', $field->getLabel() .' ('. $field->getName() .')'));
        $this->response->expectCallCount('addFeedback', 2);        
        
        $this->assertFalse($field->hasErrors());
        
        $this->assertFalse($field->isValid($artifact, ''));
        $this->assertTrue($field->hasErrors());
        
        $this->assertFalse($field->isValid($artifact, null));
        $this->assertTrue($field->hasErrors());
        
        $this->assertTrue($field->isValid($artifact, '123'));
        $this->assertFalse($field->hasErrors());
    }
}
?>
