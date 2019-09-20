<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
require_once('bootstrap.php');

Mock::generate('Tracker_Artifact_ChangesetValue_Integer');

Mock::generate('Tracker_Artifact');

Mock::generate('Tracker_FormElement_Field_Value_IntegerDao');

Mock::generate('DataAccessResult');

Mock::generate('Response');

Mock::generate('BaseLanguage');

Mock::generatePartial(
    'Tracker_FormElement_Field_Integer',
    'Tracker_FormElement_Field_IntegerTestVersion',
    array('getValueDao', 'isRequired', 'getProperty', 'isUsed', 'getCriteriaValue')
);

class Tracker_FormElement_Field_IntegerTestVersion_Expose_ProtectedMethod extends Tracker_FormElement_Field_IntegerTestVersion
{
    public function buildMatchExpression($a, $b)
    {
        return parent::buildMatchExpression($a, $b);
    }
}


class Tracker_FormElement_Field_IntegerTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $GLOBALS['Response'] = new MockResponse();
    }

    function testNoDefaultValue()
    {
        $int_field = new Tracker_FormElement_Field_IntegerTestVersion();
        $this->assertFalse($int_field->hasDefaultValue());
    }

    function testDefaultValue()
    {
        $int_field = new Tracker_FormElement_Field_IntegerTestVersion();
        $int_field->setReturnValue('getProperty', '12', array('default_value'));
        $this->assertTrue($int_field->hasDefaultValue());
        $this->assertEqual($int_field->getDefaultValue(), 12);
    }

    function testGetChangesetValue()
    {
        $value_dao = new MockTracker_FormElement_Field_Value_IntegerDao();
        $dar = new MockDataAccessResult();
        $dar->setReturnValueAt(0, 'getRow', array('id' => 123, 'field_id' => 1, 'value' => '42'));
        $dar->setReturnValue('getRow', false);
        $value_dao->setReturnReference('searchById', $dar);

        $integer_field = new Tracker_FormElement_Field_IntegerTestVersion();
        $integer_field->setReturnReference('getValueDao', $value_dao);

        $this->assertIsA($integer_field->getChangesetValue(mock('Tracker_Artifact_Changeset'), 123, false), 'Tracker_Artifact_ChangesetValue_Integer');
    }

    function testGetChangesetValue_doesnt_exist()
    {
        $value_dao = new MockTracker_FormElement_Field_Value_IntegerDao();
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('getRow', false);
        $value_dao->setReturnReference('searchById', $dar);

        $integer_field = new Tracker_FormElement_Field_IntegerTestVersion();
        $integer_field->setReturnReference('getValueDao', $value_dao);

        $this->assertNull($integer_field->getChangesetValue(null, 123, false));
    }

    function testIsValidRequiredField()
    {
        $f = new Tracker_FormElement_Field_IntegerTestVersion();
        $f->setReturnValue('isRequired', true);
        $a = new MockTracker_Artifact();
        $this->assertTrue($f->isValid($a, 2));
        $this->assertTrue($f->isValid($a, 789));
        $this->assertTrue($f->isValid($a, -1));
        $this->assertTrue($f->isValid($a, 0));
        $this->assertTrue($f->isValid($a, '56'));
        $this->assertFalse($f->isValid($a, 'toto'));
        $this->assertFalse($f->isValid($a, '12toto'));
        $this->assertFalse($f->isValid($a, 1.23));
        $this->assertFalse($f->isValid($a, array()));
        $this->assertFalse($f->isValid($a, array(1)));
        $this->assertFalse($f->isValidRegardingRequiredProperty($a, ''));
        $this->assertFalse($f->isValidRegardingRequiredProperty($a, null));
    }

    function testIsValidNotRequiredField()
    {
        $f = new Tracker_FormElement_Field_IntegerTestVersion();
        $f->setReturnValue('isRequired', false);
        $a = new MockTracker_Artifact();
        $this->assertTrue($f->isValid($a, ''));
        $this->assertTrue($f->isValid($a, null));
    }

    function testGetFieldData()
    {
        $f = new Tracker_FormElement_Field_IntegerTestVersion();
        $this->assertEqual('42', $f->getFieldData('42'));
    }

    function test_buildMatchExpression()
    {
        $f = new Tracker_FormElement_Field_IntegerTestVersion_Expose_ProtectedMethod();
        $this->assertEqual($f->buildMatchExpression('field', '12'), 'field = 12');
        $this->assertEqual($f->buildMatchExpression('field', '<12'), 'field < 12');
        $this->assertEqual($f->buildMatchExpression('field', '<=12'), 'field <= 12');
        $this->assertEqual($f->buildMatchExpression('field', '>12'), 'field > 12');
        $this->assertEqual($f->buildMatchExpression('field', '>=12'), 'field >= 12');
        $this->assertEqual($f->buildMatchExpression('field', '12-34'), 'field >= 12 AND field <= 34');
        $this->assertEqual($f->buildMatchExpression('field', ' <12'), 1); //Invalid syntax, we don't search against this field
        $this->assertEqual($f->buildMatchExpression('field', '<=toto'), 1); //Invalid syntax, we don't search against this field
    }

    public function testItSearchOnZeroValue()
    {
        $field    = new Tracker_FormElement_Field_IntegerTestVersion();
        $criteria = mock('Tracker_Report_Criteria');

        $field->setReturnValue('isUsed', true);
        $field->setReturnValue('getCriteriaValue', 0);

        $this->assertNotEqual($field->getCriteriaFrom($criteria), '');
    }

    public function testItSearchOnCustomQuery()
    {
        $field    = new Tracker_FormElement_Field_IntegerTestVersion();
        $criteria = mock('Tracker_Report_Criteria');

        $field->setReturnValue('isUsed', true);
        $field->setReturnValue('getCriteriaValue', '>1');

        $this->assertNotEqual($field->getCriteriaFrom($criteria), '');
    }

    public function testItDoesntSearchOnEmptyString()
    {
        $field    = new Tracker_FormElement_Field_IntegerTestVersion();
        $criteria = mock('Tracker_Report_Criteria');

        $field->setReturnValue('isUsed', true);
        $field->setReturnValue('getCriteriaValue', '');

        $this->assertEqual($field->getCriteriaFrom($criteria), '');
    }

    public function testItFetchCriteriaAndSetValueZero()
    {
        $field    = new Tracker_FormElement_Field_IntegerTestVersion();
        $criteria = mock('Tracker_Report_Criteria');

        $field->setId(1);
        $field->setReturnValue('getCriteriaValue', 0);

        $this->assertEqual(
            $field->fetchCriteriaValue($criteria),
            '<input type="text" name="criteria[1]" id="tracker_report_criteria_1" value="0" />'
        );
    }

    public function testItFetchCriteriaAndLeaveItEmptyValue()
    {
        $field    = new Tracker_FormElement_Field_IntegerTestVersion();
        $criteria = mock('Tracker_Report_Criteria');

        $field->setId(1);
        $field->setReturnValue('getCriteriaValue', '');

        $this->assertEqual(
            $field->fetchCriteriaValue($criteria),
            '<input type="text" name="criteria[1]" id="tracker_report_criteria_1" value="" />'
        );
    }
}

class Tracker_FormElement_Field_Integer_RESTTests extends TuleapTestCase
{

    public function itReturnsTheValueIndexedByFieldName()
    {
        $field = new Tracker_FormElement_Field_IntegerTestVersion();
        $value = array(
            "field_id" => 873,
            "value"    => 42
        );

        $this->assertEqual(42, $field->getFieldDataFromRESTValueByField($value));
    }
}
