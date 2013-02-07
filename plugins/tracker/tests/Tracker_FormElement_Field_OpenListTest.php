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
class Tracker_FormElement_Field_OpenListTestVersion_for_saveValue extends Tracker_FormElement_Field_OpenListTestVersion {
    public function saveValue($artifact, $changeset_value_id, $value, Tracker_Artifact_ChangesetValue $previous_changesetvalue = null) {
        parent::saveValue($artifact, $changeset_value_id, $value, $previous_changesetvalue);
    }
}
Mock::generate('Tracker_FormElement_Field_Value_OpenListDao');

Mock::generate('Tracker_Artifact_ChangesetValue_OpenList');

Mock::generate('Tracker_FormElement_Field_List_OpenValueDao');

class Tracker_FormElement_Field_OpenListTest extends UnitTestCase {
    
    function __construct($name = 'Open List test') {
        parent::__construct($name);
        $this->field_class            = 'Tracker_FormElement_Field_OpenListTestVersion';
        $this->field_class_for_import = 'Tracker_FormElement_Field_OpenListTestVersion_ForImport';
        $this->dao_class              = 'MockTracker_FormElement_Field_Value_OpenListDao';
        $this->cv_class               = 'Tracker_Artifact_ChangesetValue_OpenList';
        $this->mockcv_class           = 'MockTracker_Artifact_ChangesetValue_OpenList';
    }
    
    function testGetChangesetValue() {
        $open_value_dao = new MockTracker_FormElement_Field_List_OpenValueDao();
        $odar_10 = new MockDataAccessResult();
        $odar_10->setReturnValue('getRow', array('id' => '10', 'field_id' => '1', 'label' => 'Open_1'));
        $odar_20 = new MockDataAccessResult();
        $odar_20->setReturnValue('getRow', array('id' => '10', 'field_id' => '1', 'label' => 'Open_2'));
        $open_value_dao->setReturnReference('searchById', $odar_10, array(1, '10'));
        $open_value_dao->setReturnReference('searchById', $odar_20, array(1, '20'));
        
        $value_dao = new $this->dao_class();
        $dar = new MockDataAccessResult();
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
        
        $bind = new MockTracker_FormElement_Field_List_Bind();
        $bind_values = array(
            1000 => new MockTracker_FormElement_Field_List_BindValue(),
            1001 => new MockTracker_FormElement_Field_List_BindValue(),
            1002 => new MockTracker_FormElement_Field_List_BindValue(),
        );
        $bind->setReturnValue('getBindValues', $bind_values, array(array('1000', '1001', '1002')));
        
        $list_field = new $this->field_class();
        $list_field->setReturnValue('getId', 1);
        $list_field->setReturnReference('getValueDao', $value_dao);
        $list_field->setReturnReference('getOpenValueDao', $open_value_dao);
        $list_field->setReturnReference('getBind', $bind);
        
        $changeset_value = $list_field->getChangesetValue(null, 123, false);
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
    
    function testSaveValue() {
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
        
        $list_field->saveValue($artifact, $changeset_id, $submitted_value);
    }
    
    function testGetFieldData() {
        $bind = new MockTracker_FormElement_Field_List_Bind();
        $bind->setReturnValue('getFieldData', '115', array('existing value', '*'));
        $bind->setReturnValue('getFieldData', '118', array('yet another existing value', '*'));
        $bind->setReturnValue('getFieldData', null, array('new value', '*'));
        $bind->setReturnValue('getFieldData', null, array('yet another new value', '*'));
        $bind->setReturnValue('getFieldData', null, array('existing open value', '*'));
        $bind->setReturnValue('getFieldData', null, array('yet another existing open value', '*'));
        $bind->setReturnValue('getFieldData', null, array('', '*'));
        
        $odar = new MockDataAccessResult();
        $odar->setReturnValue('getRow', false);
        
        $open_value_dao = new MockTracker_FormElement_Field_List_OpenValueDao();
        
        $odar_30 = new MockDataAccessResult();
        $odar_30->setReturnValue('getRow', array('id' => '30', 'field_id' => '1', 'label' => 'existing open value'));
        $odar_40 = new MockDataAccessResult();
        $odar_40->setReturnValue('getRow', array('id' => '40', 'field_id' => '1', 'label' => 'yet another existing open value'));
        $open_value_dao->setReturnReference('searchByExactLabel', $odar_30, array(1, 'existing open value'));
        $open_value_dao->setReturnReference('searchByExactLabel', $odar_40, array(1, 'yet another existing open value'));
        $open_value_dao->setReturnReference('searchByExactLabel', $odar, array(1, 'new value'));
        $open_value_dao->setReturnReference('searchByExactLabel', $odar, array(1, 'yet another new value'));
        $open_value_dao->setReturnReference('searchByExactLabel', $odar, array(1, ''));
        
        $f = new Tracker_FormElement_Field_OpenListTestVersion();
        $f->setReturnReference('getOpenValueDao', $open_value_dao);
        $f->setReturnReference('getBind', $bind);
        $f->setReturnValue('getId', 1);
        
        $this->assertEqual("!new value,!yet another new value", $f->getFieldData('new value,yet another new value', true));
        $this->assertEqual("!new value,b115", $f->getFieldData('new value,existing value', true));
        $this->assertEqual("!new value,o30,b115", $f->getFieldData('new value,existing open value,existing value', true));
        $this->assertNull($f->getFieldData('', true));
    }

}
?>
