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

Mock::generatePartial(
    'Tracker_FormElement_Field_List', 
    'Tracker_FormElement_Field_ListTestVersion', 
    array(
        'getBind',
        'getBindFactory',
        'getValueDao',
        'getFactoryLabel',
        'getFactoryDescription',
        'getFactoryIconUseIt',
        'getFactoryIconCreate',
        'fieldHasEnableWorkflow',
        'getWorkflow',
        'getListDao',
        'getId',
        'getTracker',
        'permission_is_authorized',
        'getCurrentUser',
        'getTransitionId',
        'isNone',
        'isRequired',
    )
);

Mock::generatePartial(
    'Tracker_FormElement_Field_List', 
    'Tracker_FormElement_Field_ListTestVersion_ForImport', 
    array(
        'getBindFactory',
        'getFactoryLabel',
        'getFactoryDescription',
        'getFactoryIconUseIt',
        'getFactoryIconCreate',
    )
);

Mock::generate('Tracker_FormElement_Field_List_BindFactory');

Mock::generate('Tracker_FormElement_Field_List_Bind');

Mock::generate('Tracker_FormElement_Field_List_BindValue');

Mock::generate('Tracker_FormElement_Field_Value_ListDao');

Mock::generate('Tracker_FormElement_Field_ListDao');

require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');

Mock::generate('Workflow');

Mock::generate('Tracker_Artifact_Changeset');

Mock::generate('Tracker_Artifact_Changeset_Null');

Mock::generate('Tracker_Artifact_ChangesetValue');

Mock::generate('Tracker_Artifact_ChangesetValue_List');

require_once('common/include/Response.class.php');
Mock::generate('Response');

require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

Mock::generate('Tracker');

Mock::generate('TransitionFactory');

require_once('common/user/User.class.php');
Mock::generate('PFUser');


class Tracker_FormElement_Field_ListTest extends UnitTestCase {

    function setUp() {
        $this->field_class            = 'Tracker_FormElement_Field_ListTestVersion';
        $this->field_class_for_import = 'Tracker_FormElement_Field_ListTestVersion_ForImport';
        $this->dao_class              = 'MockTracker_FormElement_Field_Value_ListDao';
        $this->cv_class               = 'Tracker_Artifact_ChangesetValue_List';
        $this->mockcv_class           = 'MockTracker_Artifact_ChangesetValue_List';
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Language'] = new MockBaseLanguage();
    }
    
    function tearDown() {
        unset($GLOBALS['Response']);
        unset($GLOBALS['Language']);
    }
    
    function testGetChangesetValue() {
        $value_dao = new $this->dao_class();
        $dar = new MockDataAccessResult();
        $dar->setReturnValueAt(0, 'current', array('id' => '123', 'field_id' => '1', 'bindvalue_id' => '1000'));
        $dar->setReturnValueAt(1, 'current', array('id' => '123', 'field_id' => '1', 'bindvalue_id' => '1001'));
        $dar->setReturnValueAt(2, 'current', array('id' => '123', 'field_id' => '1', 'bindvalue_id' => '1002'));
        $dar->setReturnValue('valid', true);
        $dar->setReturnValueAt(3, 'valid', false);
        $value_dao->setReturnReference('searchById', $dar);
        
        $bind = new MockTracker_FormElement_Field_List_Bind();
        $bind->setReturnValue('getBindValues', array_fill(0, 3, new MockTracker_FormElement_Field_List_BindValue()));
        
        $list_field = new $this->field_class();
        $list_field->setReturnReference('getValueDao', $value_dao);
        $list_field->setReturnReference('getBind', $bind);
        
        $changeset_value = $list_field->getChangesetValue(null, 123, false);
        $this->assertIsA($changeset_value, $this->cv_class);
        $this->assertTrue(is_array($changeset_value->getListValues()));
        $this->assertEqual(count($changeset_value->getListValues()), 3);
        foreach($changeset_value->getListValues() as $bv) {
            $this->assertIsA($bv, 'Tracker_FormElement_Field_List_BindValue');
        }
    }
    
    function testGetChangesetValue_doesnt_exist() {
        $value_dao = new $this->dao_class();
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('valid', false);
        $value_dao->setReturnReference('searchById', $dar);
        
        $list_field = new $this->field_class();
        $list_field->setReturnReference('getValueDao', $value_dao);
        
        $changeset_value = $list_field->getChangesetValue(null, 123, false);
        $this->assertIsA($changeset_value, $this->cv_class);
        $this->assertTrue(is_array($changeset_value->getListValues()));
        $this->assertEqual(count($changeset_value->getListValues()), 0);
    }
    
    function testHasChangesNoChanges_reverseorder_MSB() {
        $list_field = new $this->field_class();
        $old_value = array('107', '108');
        $new_value = array('108', '107');
        $cv = new $this->mockcv_class();
        $cv->setReturnReference('getValue', $old_value);
        $this->assertFalse($list_field->hasChanges($cv, $new_value));
    }
    function testHasChangesNoChanges_same_order_MSB() {
        $list_field = new $this->field_class();
        $old_value = array('107', '108');
        $new_value = array('107', '108');
        $cv = new $this->mockcv_class();
        $cv->setReturnReference('getValue', $old_value);
        $this->assertFalse($list_field->hasChanges($cv, $new_value));
    }
    function testHasChangesNoChanges_empty_MSB() {
        $list_field = new $this->field_class();
        $old_value = array();
        $new_value = array();
        $cv = new $this->mockcv_class();
        $cv->setReturnReference('getValue', $old_value);
        $this->assertFalse($list_field->hasChanges($cv, $new_value));
    }
    function testHasChangesNoChanges_SB() {
        $list_field = new $this->field_class();
        $old_value = array('108');
        $new_value = '108';
        $cv = new $this->mockcv_class();
        $cv->setReturnReference('getValue', $old_value);
        $this->assertFalse($list_field->hasChanges($cv, $new_value));
    }
    function testHasChangesChanges_MSB() {
        $list_field = new $this->field_class();
        $old_value = array('107', '108');
        $new_value = array('107', '110');
        $cv = new $this->mockcv_class();
        $cv->setReturnReference('getValue', $old_value);
        $this->assertTrue($list_field->hasChanges($cv, $new_value));
    }
    function testHasChangesChanges_new_MSB() {
        $list_field = new $this->field_class();
        $old_value = array();
        $new_value = array('107', '110');
        $cv = new $this->mockcv_class();
        $cv->setReturnReference('getValue', $old_value);
        $this->assertTrue($list_field->hasChanges($cv, $new_value));
    }
    function testHasChangesChanges_SB() {
        $list_field = new $this->field_class();
        $old_value = array('107');
        $new_value = '110';
        $cv = new $this->mockcv_class();
        $cv->setReturnReference('getValue', $old_value);
        $this->assertTrue($list_field->hasChanges($cv, $new_value));
    }
    function testHasChangesChanges_new_SB() {
        $list_field = new $this->field_class();
        $old_value = array();
        $new_value = '110';
        $cv = new $this->mockcv_class();
        $cv->setReturnReference('getValue', $old_value);
        $this->assertTrue($list_field->hasChanges($cv, $new_value));
    }
    
    function testIsTransitionExist() {
        $artifact             = new MockTracker_Artifact();
        $changeset            = new MockTracker_Artifact_Changeset();
        $bind                 = new MockTracker_FormElement_Field_List_Bind();
        $changeset_value_list = new $this->mockcv_class();
        $workflow             = new MockWorkflow();
        $tracker              = new MockTracker();
        $user                 = mock('PFUser');
        
        $v1 = new MockTracker_FormElement_Field_List_BindValue();
        $v1->setReturnValue('__toString', '# 123');
        $v1->setReturnValue('getLabel','label1');
        $v2 = new MockTracker_FormElement_Field_List_BindValue();
        $v2->setReturnValue('__toString', '# 456');
        $v2->setReturnValue('getLabel','label2');
        $v3 = new MockTracker_FormElement_Field_List_BindValue();
        $v3->setReturnValue('__toString', '# 789');
        $v3->setReturnValue('getLabel','label3');
        $submitted_value_1 = '123'; // $v1
        $submitted_value_2 = '456'; // $v2
        $submitted_value_3 = '789'; // $v3
        
        $artifact->setReturnReference('getLastChangeset', $changeset);
        
        $bind->setReturnReference('getValue', $v1, array($submitted_value_1));
        $bind->setReturnReference('getValue', $v2, array($submitted_value_2));
        $bind->setReturnReference('getValue', $v3, array($submitted_value_3));
        
        // The previous value of the field was v2
        $changeset_value_list->setReturnValue('getListValues', array($v2));
        
        // null -> v1
        // v1 -> v2
        // v2 -> v3
        // other are invalid
        $workflow->setReturnValue('isTransitionExist', true, array(null, $v1));
        $workflow->setReturnValue('isTransitionExist', true, array($v1, $v2));
        $workflow->setReturnValue('isTransitionExist', true, array($v2, $v3));
        $workflow->setReturnValue('isTransitionExist', false);
        
        $field_list = new $this->field_class();
        $field_list->setReturnReference('getBind', $bind);
        $field_list->setReturnValue('fieldHasEnableWorkflow', true);
        $field_list->setReturnReference('getWorkflow', $workflow);
        $field_list->setReturnReference('getTracker', $tracker);
        $field_list->setReturnValue('permission_is_authorized', true); 
        $field_list->setReturnValue('getCurrentUser', $user);
        $field_list->setReturnValue('getTransitionId', 1);
        $field_list->setReturnValue('isNone', false);
        $field_list->setReturnValue('isRequired', false);
        $changeset->setReturnReference('getValue', $changeset_value_list, array($field_list));
        
        // We try to change the field from v2 to v1 => invalid
        $this->assertFalse($field_list->isValid($artifact, $submitted_value_1));
        // We try to change the field from v2 to v2 (no changes) => valid
        $this->assertTrue($field_list->isValid($artifact, $submitted_value_2));
        // We try to change the field from v2 to v3 => valid
        $this->assertTrue($field_list->isValid($artifact, $submitted_value_3));
        // We try to change the field from v2 to none => invalid
        $this->assertFalse($field_list->isValid($artifact, null));
    }
    
    function testTransitionIsValidOnSubmit() {
        $artifact             = new MockTracker_Artifact();
        $changeset            = new MockTracker_Artifact_Changeset_Null();
        $bind                 = new MockTracker_FormElement_Field_List_Bind();
        $workflow             = new MockWorkflow();
        $tracker              = new MockTracker();
        $user                 = mock('PFUser');
        
        $v1 = new MockTracker_FormElement_Field_List_BindValue();
        $v1->setReturnValue('__toString', '# 123');
        $v1->setReturnValue('getLabel','label1');
        $submitted_value_1 = '123'; // $v1
        
        $artifact->setReturnReference('getLastChangeset', $changeset);
        
        $bind->setReturnReference('getValue', $v1, array($submitted_value_1));
        
        // null -> v1
        // other are invalid
        $workflow->setReturnValue('isTransitionExist', true, array(null, $v1));
        $workflow->setReturnValue('isTransitionExist', false);
        
        $field_list = new $this->field_class();
        $field_list->setReturnReference('getBind', $bind);
        $field_list->setReturnValue('fieldHasEnableWorkflow', true);
        $field_list->setReturnReference('getWorkflow', $workflow);
        $field_list->setReturnReference('getTracker', $tracker);
        $field_list->setReturnValue('permission_is_authorized', true); 
        $field_list->setReturnValue('getCurrentUser', $user);
        $field_list->setReturnValue('getTransitionId', 1);
        $field_list->setReturnValue('isNone', false);
        $field_list->setReturnValue('isRequired', false);
        $changeset->setReturnReference('getValue', $changeset_value_list, array($field_list));
        
        // We try to change the field from null to v1 => valid
        $this->assertTrue($field_list->isValid($artifact, $submitted_value_1));
    }
    
    function testTransitionIsInvalidOnSubmit() {
        $artifact             = new MockTracker_Artifact();
        $changeset            = new MockTracker_Artifact_Changeset_Null();
        $bind                 = new MockTracker_FormElement_Field_List_Bind();
        $workflow             = new MockWorkflow();
        $tracker              = new MockTracker();
        $user                 = mock('PFUser');
        
        $v1 = new MockTracker_FormElement_Field_List_BindValue();
        $v1->setReturnValue('__toString', '# 123');
        $v1->setReturnValue('getLabel','label1');
        $submitted_value_1 = '123'; // $v1
        $v2 = new MockTracker_FormElement_Field_List_BindValue();
        $v2->setReturnValue('__toString', '# 456');
        $v2->setReturnValue('getLabel','label2');
        $submitted_value_2 = '456'; // $v2
        
        $artifact->setReturnReference('getLastChangeset', $changeset);
        
        $bind->setReturnReference('getValue', $v2, array($submitted_value_2));
        
        // null -> v1
        // v1 -> v2
        // other are invalid
        $workflow->setReturnValue('isTransitionExist', true, array(null, $v1));
        $workflow->setReturnValue('isTransitionExist', true, array($v1, $v2));
        $workflow->setReturnValue('isTransitionExist', false);
        
        $field_list = new $this->field_class();
        $field_list->setReturnReference('getBind', $bind);
        $field_list->setReturnValue('fieldHasEnableWorkflow', true);
        $field_list->setReturnReference('getWorkflow', $workflow);
        $field_list->setReturnReference('getTracker', $tracker);
        $field_list->setReturnValue('permission_is_authorized', true); 
        $field_list->setReturnValue('getCurrentUser', $user);
        $field_list->setReturnValue('getTransitionId', 1);
        $field_list->setReturnValue('isNone', false);
        $field_list->setReturnValue('isRequired', false);
        $changeset->setReturnReference('getValue', $changeset_value_list, array($field_list));
        
        // We try to change the field from null to v2 => invalid
        $this->assertFalse($field_list->isValid($artifact, $submitted_value_2));
    }
    
    function testSoapAvailableValues() {
        $bind = new MockTracker_FormElement_Field_List_Bind();
        $f = new $this->field_class();
        $f->setReturnReference('getBind', $bind);
        $bind->expectOnce('getSoapAvailableValues');
        $f->getSoapAvailableValues();
    }
    
    //testing field import
    public function testImportFormElement() {
        
        $xml = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <formElement type="mon_type" ID="F0" rank="20" required="1">
                <name>field_name</name>
                <label>field_label</label>
                <description>field_description</description>
                <bind>
                </bind>
            </formElement>'
        );
        
        $mapping = array();
        
        $bind    = new MockTracker_FormElement_Field_List_Bind();
        $factory = new MockTracker_FormElement_Field_List_BindFactory();
        
        $f = new $this->field_class_for_import();
        $f->setReturnReference('getBindFactory', $factory);
        
        $factory->setReturnReference('getInstanceFromXML', $bind, array($xml->bind, '*', $mapping));
        
        $f->continueGetInstanceFromXML($xml, $mapping);
        
        $this->assertReference($f->getBind(), $bind);
    }
    
    public function test_afterSaveObject() {
        $tracker = new MockTracker();
        $bind    = new MockTracker_FormElement_Field_List_Bind();
        $factory = new MockTracker_FormElement_Field_List_BindFactory();
        $dao     = new MockTracker_FormElement_Field_ListDao();
        
        $f = new $this->field_class();
        $f->setReturnReference('getBindFactory', $factory);
        $f->setReturnReference('getBind', $bind);
        $f->setReturnReference('getListDao', $dao);
        $f->setReturnValue('getId', 66);
        
        $factory->setReturnValue('getType', 'users', array($bind));
        
        $bind->expectOnce('saveObject');
        
        $dao->expect('save', array(66, 'users'));
        
        $f->afterSaveObject($tracker);
    }
    
    public function testIsValidRequired() {
        $artifact  = new MockTracker_Artifact();
        $field_list = new $this->field_class();
        $field_list->setReturnValue('isRequired', true);
        
        $value1 = '';
        $value2 = null;
        $value3 = '100';
        $value4 = 'value';
        
        $field_list->setReturnValue('isNone', true, array($value1));
        $field_list->setReturnValue('isNone', true, array($value2));
        $field_list->setReturnValue('isNone', true, array($value3));
        $field_list->setReturnValue('isNone', false, array($value4));
        
        $this->assertFalse($field_list->isValid($artifact, $value1));
        $this->assertFalse($field_list->isValid($artifact, $value2));
        $this->assertFalse($field_list->isValid($artifact, $value3));
        $this->assertTrue($field_list->isValid($artifact, $value4));
    }
}

class Tracker_FormElement_Field_List_processGetValuesTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->layout  = mock('Tracker_IDisplayTrackerLayout');
        $this->user    = mock('PFUser');
        $this->request = aRequest()->with('func', 'get-values')->build();
        $this->bind    = mock('Tracker_FormElement_Field_List_Bind');
        $this->list    = new Tracker_FormElement_Field_ListTestVersion();
        stub($this->list)->getBind()->returns($this->bind);
    }

    public function itDoesNothingIfTheRequestDoesNotContainTheParameter() {
        $request = aRequest()->with('func', 'whatever')->build();
        expect($GLOBALS['Response'])->sendJSON()->never();
        $this->list->process($this->layout, $request, $this->user);
    }

    public function itSendsAnEmptyArrayInJSONFormatWhenNoValues() {
        stub($this->bind)->getAllValues()->returns(array());
        expect($GLOBALS['Response'])->sendJSON(array())->once();
        $this->list->process($this->layout, $this->request, $this->user);
    }

    public function itSendsTheValuesInJSONFormat() {
        $v1 = new Tracker_FormElement_Field_List_Bind_StaticValue(10, 'label1', 'desc1', 'rank', false);
        $v2 = new Tracker_FormElement_Field_List_Bind_StaticValue(11, 'label2', 'desc2', 'rank', false);

        stub($this->bind)->getAllValues()->returns(array($v1, $v2));

        expect($GLOBALS['Response'])->sendJSON(
                array(
                    10 => $v1->fetchValuesForJson(),
                    11 => $v2->fetchValuesForJson()
                ))->once();

        $this->list->process($this->layout, $this->request, $this->user);
    }
}

?>
