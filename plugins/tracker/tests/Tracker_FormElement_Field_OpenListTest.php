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

use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

require_once('bootstrap.php');

Mock::generatePartial(
    'Tracker_FormElement_Field_OpenList',
    'Tracker_FormElement_Field_OpenListTestVersion',
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
        'getOpenValueDao',
    )
);

Mock::generatePartial(
    'Tracker_FormElement_Field_OpenList',
    'Tracker_FormElement_Field_OpenListTestVersion_ForImport',
    array(
        'getBindFactory',
        'getFactoryLabel',
        'getFactoryDescription',
        'getFactoryIconUseIt',
        'getFactoryIconCreate',
    )
);
class Tracker_FormElement_Field_OpenListTestVersion_for_saveValue extends Tracker_FormElement_Field_OpenListTestVersion
{
    public function saveValue($artifact, $changeset_value_id, $value, ?Tracker_Artifact_ChangesetValue $previous_changesetvalue, CreatedFileURLMapping $url_mapping)
    {
        parent::saveValue($artifact, $changeset_value_id, $value, $previous_changesetvalue, $url_mapping);
    }
}
Mock::generate('Tracker_FormElement_Field_Value_OpenListDao');

Mock::generate('Tracker_Artifact_ChangesetValue_OpenList');

Mock::generate('Tracker_FormElement_Field_List_OpenValueDao');

class Tracker_FormElement_Field_OpenListTest extends TuleapTestCase
{

    function __construct($name = 'Open List test')
    {
        parent::__construct($name);
        $this->field_class            = 'Tracker_FormElement_Field_OpenListTestVersion';
        $this->field_class_for_import = 'Tracker_FormElement_Field_OpenListTestVersion_ForImport';
        $this->dao_class              = 'MockTracker_FormElement_Field_Value_OpenListDao';
        $this->cv_class               = 'Tracker_Artifact_ChangesetValue_OpenList';
        $this->mockcv_class           = 'MockTracker_Artifact_ChangesetValue_OpenList';
    }

    function testGetChangesetValue()
    {
        $open_value_dao = new MockTracker_FormElement_Field_List_OpenValueDao();
        $odar_10 = mock('DataAccessResult');
        $odar_10->setReturnValue('getRow', array('id' => '10', 'field_id' => '1', 'label' => 'Open_1'));
        $odar_20 = mock('DataAccessResult');
        $odar_20->setReturnValue('getRow', array('id' => '10', 'field_id' => '1', 'label' => 'Open_2'));
        $open_value_dao->setReturnReference('searchById', $odar_10, array(1, '10'));
        $open_value_dao->setReturnReference('searchById', $odar_20, array(1, '20'));

        $value_dao = new $this->dao_class();
        $dar = mock('DataAccessResult');
        $dar->setReturnValueAt(0, 'current', array('id' => '123', 'field_id' => '1', 'bindvalue_id' => '1000', 'openvalue_id' => null));
        $dar->setReturnValueAt(1, 'current', array('id' => '123', 'field_id' => '1', 'bindvalue_id' => '1001', 'openvalue_id' => null));
        $dar->setReturnValueAt(2, 'current', array('id' => '123', 'field_id' => '1', 'bindvalue_id' => null, 'openvalue_id' => '10'));
        $dar->setReturnValueAt(3, 'current', array('id' => '123', 'field_id' => '1', 'bindvalue_id' => null, 'openvalue_id' => '20'));
        $dar->setReturnValueAt(4, 'current', array('id' => '123', 'field_id' => '1', 'bindvalue_id' => '1002', 'openvalue_id' => null));

        $dar->setReturnValueAt(5, 'current', array('id' => '123', 'field_id' => '1', 'bindvalue_id' => '1000', 'openvalue_id' => null)); //two foreachs
        $dar->setReturnValueAt(6, 'current', array('id' => '123', 'field_id' => '1', 'bindvalue_id' => '1001', 'openvalue_id' => null)); //two foreachs
        $dar->setReturnValueAt(7, 'current', array('id' => '123', 'field_id' => '1', 'bindvalue_id' => null, 'openvalue_id' => '10'));   //two foreachs
        $dar->setReturnValueAt(8, 'current', array('id' => '123', 'field_id' => '1', 'bindvalue_id' => null, 'openvalue_id' => '20'));   //two foreachs
        $dar->setReturnValueAt(9, 'current', array('id' => '123', 'field_id' => '1', 'bindvalue_id' => '1002', 'openvalue_id' => null)); //two foreachs

        $dar->setReturnValue('valid', true);
        $dar->setReturnValueAt(5, 'valid', false);
        $dar->setReturnValueAt(11, 'valid', false);
        $value_dao->setReturnReference('searchById', $dar);

        $bind = mock('Tracker_FormElement_Field_List_Bind_Static');
        $bind_values = array(
            1000 => mock('Tracker_FormElement_Field_List_BindValue'),
            1001 => mock('Tracker_FormElement_Field_List_BindValue'),
            1002 => mock('Tracker_FormElement_Field_List_BindValue'),
        );
        $bind->setReturnValue('getBindValuesForIds', $bind_values, array(array('1000', '1001', '1002')));

        $list_field = new $this->field_class();
        $list_field->setReturnValue('getId', 1);
        $list_field->setReturnReference('getValueDao', $value_dao);
        $list_field->setReturnReference('getOpenValueDao', $open_value_dao);
        $list_field->setReturnReference('getBind', $bind);

        $changeset_value = $list_field->getChangesetValue(mock('Tracker_Artifact_Changeset'), 123, false);
        $this->assertIsA($changeset_value, $this->cv_class);
        $this->assertTrue(is_array($changeset_value->getListValues()));
        $this->assertEqual(count($changeset_value->getListValues()), 5);
        $list_values = $changeset_value->getListValues();
        $this->assertIsA($list_values[0], 'Tracker_FormElement_Field_List_BindValue');
        $this->assertIsA($list_values[1], 'Tracker_FormElement_Field_List_BindValue');
        $this->assertIsA($list_values[2], 'Tracker_FormElement_Field_List_OpenValue');
        $this->assertIsA($list_values[3], 'Tracker_FormElement_Field_List_OpenValue');
        $this->assertIsA($list_values[4], 'Tracker_FormElement_Field_List_BindValue');
    }

    function testGetChangesetValue_doesnt_exist()
    {
        $value_dao = new $this->dao_class();
        $dar = mock('DataAccessResult');
        $dar->setReturnValue('valid', false);
        $value_dao->setReturnReference('searchById', $dar);

        $list_field = new $this->field_class();
        $list_field->setReturnReference('getValueDao', $value_dao);

        $changeset_value = $list_field->getChangesetValue(mock('Tracker_Artifact_Changeset'), 123, false);
        $this->assertIsA($changeset_value, $this->cv_class);
        $this->assertTrue(is_array($changeset_value->getListValues()));
        $this->assertEqual(count($changeset_value->getListValues()), 0);
    }

    function testSaveValue()
    {
        $artifact = null;
        $changeset_id = 666;
        $submitted_value = array();
        $submitted_value[] = 'b101';   //exisiting bind value
        $submitted_value[] = 'b102 ';  //existing bind value
        $submitted_value[] = ' o301';  //existing open value
        $submitted_value[] = 'o302';   //existing open value
        $submitted_value[] = 'b103';   //existing bind value
        $submitted_value[] = '';       //bidon
        $submitted_value[] = 'bidon';  //bidon
        $submitted_value[] = '!new_1'; //new open value
        $submitted_value[] = '!new_2'; //new open value
        $submitted_value = implode(',', $submitted_value);

        $open_value_dao = new MockTracker_FormElement_Field_List_OpenValueDao();
        $open_value_dao->setReturnValue('create', 901, array(1, 'new_1'));
        $open_value_dao->setReturnValue('create', 902, array(1, 'new_2'));

        $value_dao = new $this->dao_class();
        $value_dao->expect(
            'create',
            array(
                $changeset_id,
                array(
                    array('bindvalue_id' => 101, 'openvalue_id' => null),
                    array('bindvalue_id' => 102, 'openvalue_id' => null),
                    array('bindvalue_id' => null, 'openvalue_id' => 301),
                    array('bindvalue_id' => null, 'openvalue_id' => 302),
                    array('bindvalue_id' => 103, 'openvalue_id' => null),
                    array('bindvalue_id' => null, 'openvalue_id' => 901),
                    array('bindvalue_id' => null, 'openvalue_id' => 902),
                ),
            )
        );

        $list_field = new Tracker_FormElement_Field_OpenListTestVersion_for_saveValue();
        $list_field->setReturnValue('getId', 1);
        $list_field->setReturnReference('getValueDao', $value_dao);
        $list_field->setReturnReference('getOpenValueDao', $open_value_dao);

        $list_field->saveValue($artifact, $changeset_id, $submitted_value, null, Mockery::mock(CreatedFileURLMapping::class));
    }
}

class Tracker_FormElement_Field_OpenList_getFieldDataTest extends TuleapTestCase
{

    private $dao;
    private $bind;
    private $field;

    public function setUp()
    {
        parent::setUp();

        $this->dao   = mock('Tracker_FormElement_Field_List_OpenValueDao');
        $this->bind  = mock('Tracker_FormElement_Field_List_Bind_Static');
        $this->field = partial_mock('Tracker_FormElement_Field_OpenList', array('getOpenValueDao', 'getBind', 'getId'));

        stub($this->field)->getOpenValueDao()->returns($this->dao);
        stub($this->field)->getBind()->returns($this->bind);
        stub($this->field)->getId()->returns(1);
    }

    function itResetsTheFieldValueWhenSubmittedValueIsEmpty()
    {
        $this->assertIdentical('', $this->field->getFieldData('', true));
    }

    function itCreatesOneValue()
    {
        expect($this->bind)->getFieldData()->count(1);
        stub($this->bind)->getFieldData('new value', '*')->returns(null);

        expect($this->dao)->searchByExactLabel()->count(1);
        stub($this->dao)->searchByExactLabel(1, 'new value')->returnsEmptyDar();

        $this->assertEqual("!new value", $this->field->getFieldData('new value', true));
    }

    function itUsesOneValueDefinedByAdmin()
    {
        expect($this->bind)->getFieldData()->count(1);
        stub($this->bind)->getFieldData('existing value', '*')->returns(115);

        expect($this->dao)->searchByExactLabel()->never();

        $this->assertEqual("b115", $this->field->getFieldData('existing value', true));
    }

    function itUsesOneOpenValueDefinedPreviously()
    {
        expect($this->bind)->getFieldData()->count(1);
        stub($this->bind)->getFieldData('existing open value', '*')->returns(null);

        expect($this->dao)->searchByExactLabel()->count(1);
        stub($this->dao)->searchByExactLabel(1, 'existing open value')->returnsDar(array('id' => '30', 'field_id' => '1', 'label' => 'existing open value'));

        $this->assertEqual("o30", $this->field->getFieldData('existing open value', true));
    }

    function itCreatesTwoNewValues()
    {
        expect($this->bind)->getFieldData()->count(2);
        stub($this->bind)->getFieldData('new value', '*')->returns(null);
        stub($this->bind)->getFieldData('yet another new value', '*')->returns(null);

        expect($this->dao)->searchByExactLabel()->count(2);
        stub($this->dao)->searchByExactLabel(1, 'new value')->returnsEmptyDar();
        stub($this->dao)->searchByExactLabel(1, 'yet another new value')->returnsEmptyDar();

        $this->assertEqual("!new value,!yet another new value", $this->field->getFieldData('new value,yet another new value', true));
    }

    function itIgnoresEmptyValues()
    {
        stub($this->bind)->getFieldData('new value', '*')->returns(null);
        stub($this->bind)->getFieldData('yet another new value', '*')->returns(null);

        stub($this->dao)->searchByExactLabel(1, 'new value')->returnsEmptyDar();
        stub($this->dao)->searchByExactLabel(1, 'yet another new value')->returnsEmptyDar();

        $this->assertEqual("!new value,!yet another new value", $this->field->getFieldData('new value,,yet another new value', true));
    }

    function itCreatesANewValueAndReuseABindValueSetByAdmin()
    {
        expect($this->bind)->getFieldData()->count(2);
        stub($this->bind)->getFieldData('new value', '*')->returns(null);
        stub($this->bind)->getFieldData('existing value', '*')->returns(115);

        expect($this->dao)->searchByExactLabel()->count(1);
        stub($this->dao)->searchByExactLabel(1, 'new value')->returnsEmptyDar();

        $this->assertEqual("!new value,b115", $this->field->getFieldData('new value,existing value', true));
    }

    function itCreatesANewValueAndReuseABindValueAndCreatesAnOpenValue()
    {
        expect($this->bind)->getFieldData()->count(3);
        stub($this->bind)->getFieldData('new value', '*')->returns(null);
        stub($this->bind)->getFieldData('existing open value', '*')->returns(null);
        stub($this->bind)->getFieldData('existing value', '*')->returns(115);

        expect($this->dao)->searchByExactLabel()->count(2);
        stub($this->dao)->searchByExactLabel(1, 'new value')->returnsEmptyDar();
        stub($this->dao)->searchByExactLabel(1, 'existing open value')->returnsDar(array('id' => '30', 'field_id' => '1', 'label' => 'existing open value'));

        $this->assertEqual("!new value,o30,b115", $this->field->getFieldData('new value,existing open value,existing value', true));
    }
}

class Tracker_FormElement_Field_OpenList_RESTTests extends TuleapTestCase
{

    public function itThrowsAnExceptionWhenReturningValueIndexedByFieldName()
    {
        $field = new Tracker_FormElement_Field_OpenList(
            1,
            101,
            null,
            'field_openlist',
            'Field OpenList',
            '',
            1,
            'P',
            true,
            '',
            1
        );

        $this->expectException('Tracker_FormElement_RESTValueByField_NotImplementedException');

        $value = ['some_value'];

        $field->getFieldDataFromRESTValueByField($value);
    }
}

class Tracker_FormElement_Field_OpenList_Validate_Values extends TuleapTestCase
{
    private $artifact;
    private $bind;
    private $field;

    public function setUp()
    {
        parent::setUp();
        $this->artifact = mock('Tracker_Artifact');
        $this->bind     = mock('Tracker_FormElement_Field_List_Bind_Static');
        $this->field    = partial_mock('Tracker_FormElement_Field_OpenList', array('getBind', 'validate'));
        stub($this->field)->getBind()->returns($this->bind);
        stub($this->field)->validate()->returns(true);
        stub($this->bind)->getAllValues()->returns(array(
                101 => null,
                102 => null,
                103 => null
            ));
    }

    public function itAcceptsValidValues()
    {
        $this->assertTrue($this->field->isValid($this->artifact, ''));
        $this->assertTrue($this->field->isValid($this->artifact, 'b101'));
        $this->assertTrue($this->field->isValid($this->artifact, Tracker_FormElement_Field_OpenList::BIND_PREFIX .
            Tracker_FormElement_Field_OpenList::NONE_VALUE));
        $this->assertTrue($this->field->isValid($this->artifact, array('b101', 'b102')));
    }
}
