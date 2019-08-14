<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;

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
        'accept'
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
        'isNone',
        'accept'
    )
);

Mock::generate('Tracker_FormElement_Field_List_BindFactory');

Mock::generate('Tracker_FormElement_Field_List_Bind_Static');

Mock::generate('Tracker_FormElement_Field_List_BindValue');

Mock::generate('Tracker_FormElement_Field_Value_ListDao');

Mock::generate('Tracker_FormElement_Field_ListDao');

Mock::generate('DataAccessResult');

Mock::generate('Workflow');

Mock::generate('Tracker_Artifact_Changeset');

Mock::generate('Tracker_Artifact_Changeset_Null');

Mock::generate('Tracker_Artifact_ChangesetValue');

Mock::generate('Tracker_Artifact_ChangesetValue_List');

Mock::generate('Response');

Mock::generate('BaseLanguage');

Mock::generate('Tracker');

Mock::generate('TransitionFactory');

Mock::generate('PFUser');


class Tracker_FormElement_Field_ListTest extends TuleapTestCase
{

    private $transition_factory_test;

    public function setUp()
    {
        parent::setUp();
        $this->field_class            = 'Tracker_FormElement_Field_ListTestVersion';
        $this->field_class_for_import = 'Tracker_FormElement_Field_ListTestVersion_ForImport';
        $this->dao_class              = 'MockTracker_FormElement_Field_Value_ListDao';
        $this->cv_class               = 'Tracker_Artifact_ChangesetValue_List';
        $this->mockcv_class           = 'MockTracker_Artifact_ChangesetValue_List';
        $GLOBALS['Response'] = new MockResponse();

        $this->transition_factory_test = new class extends TransitionFactory {
            public function __construct()
            {
                parent::$_instance = \Mockery::spy(TransitionFactory::class);
            }

            public function clearInstance() : void
            {
                parent::$_instance = null;
            }
        };
    }

    public function tearDown() : void
    {
        parent::tearDown();
        $this->transition_factory_test->clearInstance();
    }

    function testGetChangesetValue()
    {
        $value_dao = new $this->dao_class();
        $dar = new MockDataAccessResult();
        $dar->setReturnValueAt(0, 'current', array('id' => '123', 'field_id' => '1', 'bindvalue_id' => '1000'));
        $dar->setReturnValueAt(1, 'current', array('id' => '123', 'field_id' => '1', 'bindvalue_id' => '1001'));
        $dar->setReturnValueAt(2, 'current', array('id' => '123', 'field_id' => '1', 'bindvalue_id' => '1002'));
        $dar->setReturnValue('valid', true);
        $dar->setReturnValueAt(3, 'valid', false);
        $value_dao->setReturnReference('searchById', $dar);

        $bind = new MockTracker_FormElement_Field_List_Bind_Static();
        $bind->setReturnValue('getBindValues', array_fill(0, 3, new MockTracker_FormElement_Field_List_BindValue()));

        $list_field = new $this->field_class();
        $list_field->setReturnReference('getValueDao', $value_dao);
        $list_field->setReturnReference('getBind', $bind);

        $changeset_value = $list_field->getChangesetValue(mock('Tracker_Artifact_Changeset'), 123, false);
        $this->assertIsA($changeset_value, $this->cv_class);
        $this->assertTrue(is_array($changeset_value->getListValues()));
        $this->assertEqual(count($changeset_value->getListValues()), 3);
        foreach ($changeset_value->getListValues() as $bv) {
            $this->assertIsA($bv, 'Tracker_FormElement_Field_List_BindValue');
        }
    }

    function testGetChangesetValue_doesnt_exist()
    {
        $value_dao = new $this->dao_class();
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('valid', false);
        $value_dao->setReturnReference('searchById', $dar);

        $list_field = new $this->field_class();
        $list_field->setReturnReference('getValueDao', $value_dao);

        $changeset_value = $list_field->getChangesetValue(mock('Tracker_Artifact_Changeset'), 123, false);
        $this->assertIsA($changeset_value, $this->cv_class);
        $this->assertTrue(is_array($changeset_value->getListValues()));
        $this->assertEqual(count($changeset_value->getListValues()), 0);
    }

    function testHasChangesNoChanges_reverseorder_MSB()
    {
        $list_field = new $this->field_class();
        $old_value = array('107', '108');
        $new_value = array('108', '107');
        $cv = new $this->mockcv_class();
        $cv->setReturnReference('getValue', $old_value);
        $this->assertFalse($list_field->hasChanges(mock('Tracker_Artifact'), $cv, $new_value));
    }
    function testHasChangesNoChanges_same_order_MSB()
    {
        $list_field = new $this->field_class();
        $old_value = array('107', '108');
        $new_value = array('107', '108');
        $cv = new $this->mockcv_class();
        $cv->setReturnReference('getValue', $old_value);
        $this->assertFalse($list_field->hasChanges(mock('Tracker_Artifact'), $cv, $new_value));
    }
    function testHasChangesNoChanges_empty_MSB()
    {
        $list_field = new $this->field_class();
        $old_value = array();
        $new_value = array();
        $cv = new $this->mockcv_class();
        $cv->setReturnReference('getValue', $old_value);
        $this->assertFalse($list_field->hasChanges(mock('Tracker_Artifact'), $cv, $new_value));
    }
    function testHasChangesNoChanges_SB()
    {
        $list_field = new $this->field_class();
        $old_value = array('108');
        $new_value = '108';
        $cv = new $this->mockcv_class();
        $cv->setReturnReference('getValue', $old_value);
        $this->assertFalse($list_field->hasChanges(mock('Tracker_Artifact'), $cv, $new_value));
    }
    function testHasChangesChanges_MSB()
    {
        $list_field = new $this->field_class();
        $old_value = array('107', '108');
        $new_value = array('107', '110');
        $cv = new $this->mockcv_class();
        $cv->setReturnReference('getValue', $old_value);
        $this->assertTrue($list_field->hasChanges(mock('Tracker_Artifact'), $cv, $new_value));
    }
    function testHasChangesChanges_new_MSB()
    {
        $list_field = new $this->field_class();
        $old_value = array();
        $new_value = array('107', '110');
        $cv = new $this->mockcv_class();
        $cv->setReturnReference('getValue', $old_value);
        $this->assertTrue($list_field->hasChanges(mock('Tracker_Artifact'), $cv, $new_value));
    }
    function testHasChangesChanges_SB()
    {
        $list_field = new $this->field_class();
        $old_value = array('107');
        $new_value = '110';
        $cv = new $this->mockcv_class();
        $cv->setReturnReference('getValue', $old_value);
        $this->assertTrue($list_field->hasChanges(mock('Tracker_Artifact'), $cv, $new_value));
    }
    function testHasChangesChanges_new_SB()
    {
        $list_field = new $this->field_class();
        $old_value = array();
        $new_value = '110';
        $cv = new $this->mockcv_class();
        $cv->setReturnReference('getValue', $old_value);
        $this->assertTrue($list_field->hasChanges(mock('Tracker_Artifact'), $cv, $new_value));
    }

    function testIsTransitionExist()
    {
        $artifact             = new MockTracker_Artifact();
        $changeset            = new MockTracker_Artifact_Changeset();
        $bind                 = new MockTracker_FormElement_Field_List_Bind_Static();
        $changeset_value_list = new $this->mockcv_class();
        $workflow             = new MockWorkflow();
        $tracker              = new MockTracker();
        $user                 = mock('PFUser');

        $v1 = new MockTracker_FormElement_Field_List_BindValue();
        $v1->setReturnValue('__toString', '# 123');
        $v1->setReturnValue('getLabel', 'label1');
        $v2 = new MockTracker_FormElement_Field_List_BindValue();
        $v2->setReturnValue('__toString', '# 456');
        $v2->setReturnValue('getLabel', 'label2');
        $v3 = new MockTracker_FormElement_Field_List_BindValue();
        $v3->setReturnValue('__toString', '# 789');
        $v3->setReturnValue('getLabel', 'label3');
        $submitted_value_1 = '123'; // $v1
        $submitted_value_2 = '456'; // $v2
        $submitted_value_3 = '789'; // $v3
        stub($bind)->isExistingValue($submitted_value_1)->returns(true);
        stub($bind)->isExistingValue($submitted_value_2)->returns(true);
        stub($bind)->isExistingValue($submitted_value_3)->returns(true);

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

    function testTransitionIsValidOnSubmit()
    {
        $artifact             = new MockTracker_Artifact();
        $changeset            = new MockTracker_Artifact_Changeset_Null();
        $bind                 = new MockTracker_FormElement_Field_List_Bind_Static();
        $workflow             = new MockWorkflow();
        $tracker              = new MockTracker();
        $user                 = mock('PFUser');

        $v1 = new MockTracker_FormElement_Field_List_BindValue();
        $v1->setReturnValue('__toString', '# 123');
        $v1->setReturnValue('getLabel', 'label1');
        $submitted_value_1 = '123'; // $v1
        stub($bind)->isExistingValue($submitted_value_1)->returns(true);

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

    function testTransitionIsInvalidOnSubmit()
    {
        $artifact             = new MockTracker_Artifact();
        $changeset            = new MockTracker_Artifact_Changeset_Null();
        $bind                 = new MockTracker_FormElement_Field_List_Bind_Static();
        $workflow             = new MockWorkflow();
        $tracker              = new MockTracker();
        $user                 = mock('PFUser');

        $v1 = new MockTracker_FormElement_Field_List_BindValue();
        $v1->setReturnValue('__toString', '# 123');
        $v1->setReturnValue('getLabel', 'label1');
        $submitted_value_1 = '123'; // $v1
        $v2 = new MockTracker_FormElement_Field_List_BindValue();
        $v2->setReturnValue('__toString', '# 456');
        $v2->setReturnValue('getLabel', 'label2');
        $submitted_value_2 = '456'; // $v2

        stub($bind)->getAllValues()->returns(array(
                $submitted_value_1 => null,
                $submitted_value_2 => null
            ));

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

    //testing field import
    public function testImportFormElement()
    {

        $xml = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <formElement type="mon_type" ID="F0" rank="20" required="1">
                <name>field_name</name>
                <label>field_label</label>
                <description>field_description</description>
                <bind>
                </bind>
            </formElement>');

        $mapping = array();

        $bind            = \Mockery::mock(Tracker_FormElement_Field_List_Bind_Static::class);
        $factory         = \Mockery::mock(Tracker_FormElement_Field_List_BindFactory::class);
        $user_finder     = \Mockery::mock(User\XML\Import\IFindUserFromXMLReference::class);
        $feedback_collector = \Mockery::mock(TrackerXmlImportFeedbackCollector::class);

        $field = new $this->field_class_for_import();
        stub($field)->getBindFactory()->returns($factory);

        $factory->shouldReceive('getInstanceFromXML')->andReturn($bind);

        $field->continueGetInstanceFromXML($xml, $mapping, $user_finder, $feedback_collector);
        $this->assertEqual($field->getBind(), $bind);
    }

    public function test_afterSaveObject()
    {
        $tracker = new MockTracker();
        $bind    = new MockTracker_FormElement_Field_List_Bind_Static();
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

        $f->afterSaveObject($tracker, false, false);
    }

    public function testIsValidRequired()
    {
        $artifact   = new MockTracker_Artifact();
        $bind       = new MockTracker_FormElement_Field_List_Bind_Static();
        $field_list = new $this->field_class();
        $field_list->setReturnReference('getBind', $bind);
        $field_list->setReturnValue('isRequired', true);

        $value1 = '';
        $value2 = null;
        $value3 = '100';
        $value4 = 'value';

        stub($bind)->getAllValues()->returns(array(
                $value1 => null,
                $value2 => null,
                $value3 => null,
                $value4 => null

            ));

        $field_list->setReturnValue('isNone', true, array($value1));
        $field_list->setReturnValue('isNone', true, array($value2));
        $field_list->setReturnValue('isNone', true, array($value3));
        $field_list->setReturnValue('isNone', false, array($value4));

        $this->assertFalse($field_list->isValidRegardingRequiredProperty($artifact, $value1));
        $this->assertFalse($field_list->isValidRegardingRequiredProperty($artifact, $value2));
        $this->assertFalse($field_list->isValidRegardingRequiredProperty($artifact, $value3));
        $this->assertTrue($field_list->isValid($artifact, $value4));
    }
}

class Tracker_FormElement_Field_List_processGetValuesTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->layout  = mock('Tracker_IDisplayTrackerLayout');
        $this->user    = mock('PFUser');
        $this->request = aRequest()->with('func', 'get-values')->build();
        $this->bind    = mock('Tracker_FormElement_Field_List_Bind_Static');
        $this->list    = new Tracker_FormElement_Field_ListTestVersion();
        stub($this->list)->getBind()->returns($this->bind);
    }

    public function itDoesNothingIfTheRequestDoesNotContainTheParameter()
    {
        $request = aRequest()->with('func', 'whatever')->build();
        expect($GLOBALS['Response'])->sendJSON()->never();
        $this->list->process($this->layout, $request, $this->user);
    }

    public function itSendsWhateverBindReturns()
    {
        stub($this->bind)->fetchFormattedForJson()->returns('whatever');
        expect($GLOBALS['Response'])->sendJSON('whatever')->once();
        $this->list->process($this->layout, $this->request, $this->user);
    }
}

class Tracker_FormElement_Field_ListJsonFormattedTest extends TuleapTestCase
{

    private $bind;
    private $list;

    public function setUp()
    {
        parent::setUp();
        $this->bind     = mock('Tracker_FormElement_Field_List_Bind_Static');
        $this->list     = new Tracker_FormElement_Field_ListTestVersion();
        $this->list->id = 234;
        stub($this->list)->getBind()->returns($this->bind);
    }

    public function itHasValuesInAdditionToCommonFormat()
    {
        expect($this->bind)->fetchFormattedForJson()->once();
        stub($this->bind)->fetchFormattedForJson()->returns(array());

        $json = $this->list->fetchFormattedForJson();
        $this->assertEqual($json['id'], 234);
        $this->assertIdentical($json['values'], array());
    }
}

class Tracker_FormElement_Field_ListsetCriteriaValueFromRESTTest extends TuleapTestCase
{

    private $bind;
    private $list;

    public function setUp()
    {
        parent::setUp();
        $this->bind     = mock('Tracker_FormElement_Field_List_Bind_Static');
        $this->list     = new Tracker_FormElement_Field_ListTestVersion();
        $this->list->id = 234;
        stub($this->list)->getBind()->returns($this->bind);
        stub($this->bind)->getAllValues()->returns(array(101 => 101, 102=> 102, 103 => 103));
    }

    public function itThrowsAnExceptionIfValueIsNotUsable()
    {
        $this->expectException('Tracker_Report_InvalidRESTCriterionException');

        $criteria             = mock('Tracker_Report_Criteria');
        $criteria->report     = mock('Tracker_Report');
        $criteria->report->id = 1;
        $rest_criteria_value  = array(
            Tracker_Report_REST::VALUE_PROPERTY_NAME => array(array(1234)),
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_CONTAINS
        );

        $this->list->setCriteriaValueFromREST($criteria, $rest_criteria_value);
    }

    public function itThrowsAnExceptionIfValueIsNotANumber()
    {
        $this->expectException('Tracker_Report_InvalidRESTCriterionException');

        $criteria             = mock('Tracker_Report_Criteria');
        $criteria->report     = mock('Tracker_Report');
        $criteria->report->id = 1;
        $rest_criteria_value  = array(
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => 'I am a string',
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_CONTAINS
        );

        $this->list->setCriteriaValueFromREST($criteria, $rest_criteria_value);
    }

    public function itIgnoresInvalidFieldValues()
    {
        $criteria             = mock('Tracker_Report_Criteria');
        $criteria->report     = mock('Tracker_Report');
        $criteria->report->id = 1;
        $rest_criteria_value  = array(
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => '106',
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_CONTAINS
        );

        $set = $this->list->setCriteriaValueFromREST($criteria, $rest_criteria_value);
        $this->assertFalse($set);

        $res = $this->list->getCriteriaValue($criteria);
        $this->assertCount($res, 0);
    }

    public function itAddsACriterion()
    {
        $criteria             = mock('Tracker_Report_Criteria');
        $criteria->report     = mock('Tracker_Report');
        $criteria->report->id = 1;
        $rest_criteria_value  = array(
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => '101',
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_CONTAINS
        );

        $set = $this->list->setCriteriaValueFromREST($criteria, $rest_criteria_value);
        $this->assertTrue($set);

        $res = $this->list->getCriteriaValue($criteria);

        $this->assertCount($res, 1);
        $this->assertTrue(in_array(101, $res));
    }

    public function itAddsCriteria()
    {
        $criteria             = mock('Tracker_Report_Criteria');
        $criteria->report     = mock('Tracker_Report');
        $criteria->report->id = 1;
        $rest_criteria_value  = array(
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => array('101', 103),
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_CONTAINS
        );

        $set = $this->list->setCriteriaValueFromREST($criteria, $rest_criteria_value);
        $this->assertTrue($set);

        $res = $this->list->getCriteriaValue($criteria);

        $this->assertCount($res, 2);
        $this->assertTrue(in_array(101, $res));
        $this->assertTrue(in_array(103, $res));
    }
}

class Tracker_FormElement_Field_List_RESTTests extends TuleapTestCase
{
    public function itThrowsAnExceptionWhenReturningValueIndexedByFieldName()
    {
        $field = new Tracker_FormElement_Field_ListTestVersion();

        $this->expectException('Tracker_FormElement_RESTValueByField_NotImplementedException');

        $value = ['some_value'];

        $field->getFieldDataFromRESTValueByField($value);
    }
}

class Tracker_FormElement_Field_List_Validate_Values extends TuleapTestCase
{

    private $artifact;
    private $bind;
    private $list;

    public function setUp()
    {
        parent::setUp();
        $this->bind     = mock('Tracker_FormElement_Field_List_Bind_Static');
        $this->list     = new Tracker_FormElement_Field_ListTestVersion();
        $this->artifact = new MockTracker_Artifact();
        stub($this->list)->getBind()->returns($this->bind);
        stub($this->bind)->isExistingValue(101)->returns(true);
        stub($this->bind)->isExistingValue(102)->returns(true);
        stub($this->bind)->isExistingValue(103)->returns(true);
    }

    public function itAcceptsValidValues()
    {
        $this->assertTrue($this->list->isValid($this->artifact, 101));
        $this->assertTrue($this->list->isValid($this->artifact, Tracker_FormElement_Field_List::NONE_VALUE));
        $this->assertTrue($this->list->isValid($this->artifact, strval(Tracker_FormElement_Field_List::NONE_VALUE)));
        $this->assertTrue($this->list->isValid($this->artifact, Tracker_FormElement_Field_List::NOT_INDICATED_VALUE));
        $this->assertTrue($this->list->isValid($this->artifact, array(101, 103)));
    }

    public function itDoesNotAcceptIncorrectValues()
    {
        $this->assertFalse($this->list->isValid($this->artifact, 9999));
        $this->assertFalse($this->list->isValid($this->artifact, array(9998, 9999)));
        $this->assertFalse($this->list->isValid($this->artifact, array(101, 9999)));
    }
}
