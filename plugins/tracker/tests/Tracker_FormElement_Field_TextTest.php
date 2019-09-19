<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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

Mock::generatePartial(
    'Tracker_FormElement_Field_Text',
    'Tracker_FormElement_Field_TextTestVersion',
    array('getValueDao', 'getRuleString', 'isRequired', 'getProperty', 'getCriteriaDao')
);

Mock::generate('Tracker_Artifact_ChangesetValue_Text');

Mock::generate('Tracker_FormElement_Field_Value_TextDao');

Mock::generate('DataAccessResult');

Mock::generate('Tracker_Artifact');

Mock::generate('Rule_String');

class Tracker_FormElement_Field_TextTestVersion_Expose_ProtectedMethod extends Tracker_FormElement_Field_TextTestVersion
{
    public function buildMatchExpression($a, $b)
    {
        return parent::buildMatchExpression($a, $b);
    }
}

class Tracker_FormElement_Field_TextTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->user = mock('PFUser');

        $user_manager = stub('UserManager')->getCurrentUser()->returns($this->user);
        UserManager::setInstance($user_manager);
    }

    public function tearDown()
    {
        UserManager::clearInstance();

        parent::tearDown();
    }

    public function testNoDefaultValue()
    {
        $str_field = new Tracker_FormElement_Field_TextTestVersion();
        $this->assertFalse($str_field->hasDefaultValue());
    }

    public function testDefaultValue()
    {
        stub($this->user)->getPreference(PFUser::EDITION_DEFAULT_FORMAT)->returns('text');

        $str_field = new Tracker_FormElement_Field_TextTestVersion();
        $str_field->setReturnValue('getProperty', 'foo bar long text with nice stories', array('default_value'));

        $this->assertTrue($str_field->hasDefaultValue());
        $this->assertEqual($str_field->getDefaultValue(), array(
            'content' => 'foo bar long text with nice stories',
            'format'  => 'text'
        ));
    }

    public function testGetChangesetValue()
    {
        $value_dao = new MockTracker_FormElement_Field_Value_TextDao();
        $dar = new MockDataAccessResult();
        $dar->setReturnValueAt(0, 'getRow', array('id' => 123, 'field_id' => 1, 'value' => 'My Text', 'body_format' => 'text'));
        $dar->setReturnValue('getRow', false);
        $value_dao->setReturnReference('searchById', $dar);

        $text_field = new Tracker_FormElement_Field_TextTestVersion();
        $text_field->setReturnReference('getValueDao', $value_dao);

        $this->assertIsA($text_field->getChangesetValue(mock('Tracker_Artifact_Changeset'), 123, false), 'Tracker_Artifact_ChangesetValue_Text');
    }

    public function testGetChangesetValue_doesnt_exist()
    {
        $value_dao = new MockTracker_FormElement_Field_Value_TextDao();
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('getRow', false);
        $value_dao->setReturnReference('searchById', $dar);

        $text_field = new Tracker_FormElement_Field_TextTestVersion();
        $text_field->setReturnReference('getValueDao', $value_dao);

        $this->assertNull($text_field->getChangesetValue(null, 123, false));
    }

    public function testSpecialCharactersInCSVExport()
    {
        $whatever_report = mock('Tracker_Report');
        $text_field      = new Tracker_FormElement_Field_TextTestVersion();
        $this->assertEqual("Une chaine sans accent", $text_field->fetchCSVChangesetValue(null, null, "Une chaine sans accent", $whatever_report));
        $this->assertEqual("Lé chaîne avé lê àccent dô où ça", $text_field->fetchCSVChangesetValue(null, null, "Lé chaîne avé lê àccent dô où ça", $whatever_report));
        $this->assertEqual("This, or that", $text_field->fetchCSVChangesetValue(null, null, "This, or that", $whatever_report));
        $this->assertEqual("This; or that", $text_field->fetchCSVChangesetValue(null, null, "This; or that", $whatever_report));
        $this->assertEqual("This thing is > that thing", $text_field->fetchCSVChangesetValue(null, null, "This thing is > that thing", $whatever_report));
        $this->assertEqual("This thing & that thing", $text_field->fetchCSVChangesetValue(null, null, "This thing & that thing", $whatever_report));
    }

    public function testIsValid()
    {
        $artifact = new MockTracker_Artifact();

        $rule_string = new MockRule_String();
        $rule_string->setReturnValue('isValid', true);

        $text = new Tracker_FormElement_Field_TextTestVersion();
        $text->setReturnReference('getRuleString', $rule_string);

        $this->assertTrue($text->isValid($artifact, "Du texte"));
    }

    public function testHasChanges()
    {
        $value = new MockTracker_Artifact_ChangesetValue_Text();
        $value->setReturnValue('getText', 'v1');

        $text = new Tracker_FormElement_Field_TextTestVersion();

        $this->assertTrue($text->hasChanges(mock('Tracker_Artifact'), $value, array('content' => 'v2')));
    }

    public function testIsValidRequiredField()
    {
        $rule_string = new MockRule_String();
        $rule_string->expectCallCount('isValid', 3);

        $f = new Tracker_FormElement_Field_TextTestVersion();
        $f->setReturnValue('isRequired', true);
        $f->setReturnReference('getRuleString', $rule_string);

        $a = new MockTracker_Artifact();
        $f->isValid($a, 'This is a text');
        $f->isValid($a, '2009-08-45');
        $f->isValid($a, 25);
        $this->assertFalse($f->isValidRegardingRequiredProperty($a, ''));
        $this->assertFalse($f->isValidRegardingRequiredProperty($a, null));
    }

    public function testIsValidNotRequiredField()
    {
        $rule_string = new MockRule_String();
        $rule_string->expectCallCount('isValid', 5);

        $f = new Tracker_FormElement_Field_TextTestVersion();
        $f->setReturnValue('isRequired', false);
        $f->setReturnReference('getRuleString', $rule_string);

        $value_1 = array(
            'content' => 'This is a text',
            'format'  => 'text'
        );

        $value_2 = array(
            'content' => '2009-08-45',
            'format'  => 'text'
        );

        $value_3 = array(
            'content' => 25,
            'format'  => 'text'
        );

        $value_4 = array(
            'content' => '',
            'format'  => 'text'
        );

        $value_5 = array(
            'content' => null,
            'format'  => 'text'
        );

        $a = new MockTracker_Artifact();
        $f->isValid($a, $value_1);
        $f->isValid($a, $value_2);
        $f->isValid($a, $value_3);
        $f->isValid($a, $value_4);
        $f->isValid($a, $value_5);
    }

    public function testGetFieldData()
    {
        $f = new Tracker_FormElement_Field_TextTestVersion();
        $this->assertEqual('this is a text value', $f->getFieldData('this is a text value'));
    }

    public function testBuildMatchExpression()
    {
        $field = new Tracker_FormElement_Field_TextTestVersion_Expose_ProtectedMethod();

        $data_access = stub(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class)->quoteLikeValueSurround('tutu')->returns("'%tutu%'");
        stub($data_access)->quoteLikeValueSurround('toto')->returns("'%toto%'");
        stub($data_access)->quoteSmart('regexp')->returns("'regexp'");

        $dao = stub('Tracker_Report_Criteria_Text_ValueDao')->getDa()->returns($data_access);
        stub($field)->getCriteriaDao()->returns($dao);

        $this->assertEqual($field->buildMatchExpression('field', 'tutu'), "field LIKE '%tutu%'");
        $this->assertEqual($field->buildMatchExpression('field', 'tutu toto'), "field LIKE '%tutu%' AND field LIKE '%toto%'");
        $this->assertEqual($field->buildMatchExpression('field', '/regexp/'), "field RLIKE 'regexp'");
        $this->assertEqual($field->buildMatchExpression('field', '!/regexp/'), "field NOT RLIKE 'regexp'");
    }

    /**
     * @see https://tuleap.net/plugins/tracker?aid=6435
     */
    public function itIsEmptyWhenThereIsNoContent()
    {
        $artifact = new MockTracker_Artifact();
        $field    = aTextField()->build();
        $this->assertTrue($field->isEmpty(
            array(
                'format' => 'text',
                'content' => ''
            ),
            $artifact
        ));
    }

    /**
     * @see https://tuleap.net/plugins/tracker?aid=6435
     */
    public function itIsEmptyWhenThereIsOnlyWhitespaces()
    {
        $artifact = new MockTracker_Artifact();
        $field    = aTextField()->build();
        $this->assertTrue($field->isEmpty(
            array(
                'format' => 'text',
                'content' => '   '
            ),
            $artifact
        ));
    }

    /**
     * @see https://tuleap.net/plugins/tracker?aid=6435
     */
    public function itIsNotEmptyWhenThereIsContent()
    {
        $artifact = new MockTracker_Artifact();
        $field    = aTextField()->build();
        $this->assertFalse($field->isEmpty(
            array(
                'format' => 'text',
                'content' => 'bla'
            ),
            $artifact
        ));
    }

    public function itIsEmptyWhenValueIsAnEmptyString()
    {
        $artifact = new MockTracker_Artifact();
        $field    = aTextField()->build();
        $this->assertTrue($field->isEmpty('', $artifact));
    }

    public function itIsNotEmptyWhenValueIsAStringWithContent()
    {
        $artifact = new MockTracker_Artifact();
        $field    = aTextField()->build();
        $this->assertFalse($field->isEmpty('aaa', $artifact));
    }
}

class Tracker_FormElement_Field_Text_RESTTests extends TuleapTestCase
{

    public function itReturnsTheValueIndexedByFieldName()
    {
        $field = aTextField()->build();
        $value = array(
            "field_id" => 873,
            "value"    => array(
                'content' => 'My awesome content',
                'format'  => 'text',
            )
        );

        $fields_data = $field->getFieldDataFromRESTValueByField($value);

        $this->assertEqual($fields_data['content'], 'My awesome content');
        $this->assertEqual($fields_data['format'], 'text');
    }
}

class Tracker_FormElement_Field_Text_Changes extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->field = aTextField()->build();
        $this->previous_value = stub('Tracker_Artifact_ChangesetValue_Text')->getText()->returns('1');
    }

    public function itReturnsTrueIfThereIsAChange()
    {
        $new_value = array(
            'content' => '1.0',
            'format'  => 'text'
        );

        $this->assertTrue($this->field->hasChanges(mock('Tracker_Artifact'), $this->previous_value, $new_value));
    }

    public function itReturnsFalseIfThereIsNoChange()
    {
        $new_value = array(
            'content' => '1',
            'format'  => 'text'
        );

        $this->assertFalse($this->field->hasChanges(mock('Tracker_Artifact'), $this->previous_value, $new_value));
    }

    public function itReturnsFalseIfOnlyTheFormatChanged()
    {
        $new_value = array(
            'content' => '1',
            'format'  => 'html'
        );

        $this->assertFalse($this->field->hasChanges(mock('Tracker_Artifact'), $this->previous_value, $new_value));
    }
}
