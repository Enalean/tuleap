<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This list is a part of Codendi.
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

Mock::generate('Tracker_FormElement_Field_List');
Mock::generate('Tracker_FormElement_Field_List_BindValue');

class Tracker_Artifact_ChangesetValue_ListTest extends TuleapTestCase {
    
    function __construct($name = 'Chageset Value List Test') {
        parent::__construct($name);
        $this->field_class          = 'MockTracker_FormElement_Field_List';
        $this->changesetvalue_class = 'Tracker_Artifact_ChangesetValue_List';
    }
    
    function testLists() {
        $bind_value = new MockTracker_FormElement_Field_List_BindValue();
        $bind_value->setReturnValue('getSoapValue', 'Reopen');
        $bind_value->setReturnValue('getId', 106);
        $field      = new $this->field_class();
        $value_list = new $this->changesetvalue_class(111, $field, false, array($bind_value));
        $this->assertEqual(count($value_list), 1);
        $this->assertEqual($value_list[0], $bind_value);
        $this->assertEqual($value_list->getSoapValue(), array('bind_value' => array(array('bind_value_id' => 106, 'bind_value_label' => "Reopen"))));
        $this->assertEqual($value_list->getValue(), array(106));
    }
    
    function testNoDiff() {
        $bind_value = new MockTracker_FormElement_Field_List_BindValue();
        $bind_value->setReturnValue('__toString', 'Value');
        $bind_value->setReturnValue('getLabel', 'Value');
        $field  = new $this->field_class();
        $list_1 = new $this->changesetvalue_class(111, $field, false, array($bind_value));
        $list_2 = new $this->changesetvalue_class(111, $field, false, array($bind_value));
        $this->assertFalse($list_1->diff($list_2));
        $this->assertFalse($list_2->diff($list_1));
    }
    
    function testDiff_cleared() {
        $bind_value = new MockTracker_FormElement_Field_List_BindValue();
        $bind_value->setReturnValue('__toString', 'Value');
        $bind_value->setReturnValue('getLabel', 'Value');
        $field  = new $this->field_class();
        $list_1 = new $this->changesetvalue_class(111, $field, false, array());
        $list_2 = new $this->changesetvalue_class(111, $field, false, array($bind_value));
        $GLOBALS['Language']->setReturnValue('getText', 'cleared', array('plugin_tracker_artifact','cleared'));
        $this->assertEqual($list_1->diff($list_2), ' cleared');
    }
    
    function testDiff_setto() {
        $bind_value_1 = new MockTracker_FormElement_Field_List_BindValue();
        $bind_value_1->setReturnValue('__toString', 'Sandra');
        $bind_value_1->setReturnValue('getLabel', 'Sandra');
        $bind_value_2 = new MockTracker_FormElement_Field_List_BindValue();
        $bind_value_2->setReturnValue('__toString', 'Manon');
        $bind_value_2->setReturnValue('getLabel', 'Manon');
        $field  = new $this->field_class();
        $list_1 = new $this->changesetvalue_class(111, $field, false, array($bind_value_1, $bind_value_2));
        $list_2 = new $this->changesetvalue_class(111, $field, false, array());
        $GLOBALS['Language']->setReturnValue('getText', 'set to', array('plugin_tracker_artifact','set_to'));
        $this->assertEqual($list_1->diff($list_2), ' set to Sandra, Manon');
    }
    
    function testDiff_changedfrom() {
        $bind_value_1 = new MockTracker_FormElement_Field_List_BindValue();
        $bind_value_1->setReturnValue('__toString', 'Sandra');
        $bind_value_1->setReturnValue('getLabel', 'Sandra');
        $bind_value_2 = new MockTracker_FormElement_Field_List_BindValue();
        $bind_value_2->setReturnValue('__toString', 'Manon');
        $bind_value_2->setReturnValue('getLabel', 'Manon');
        $field  = new $this->field_class();
        $list_1 = new $this->changesetvalue_class(111, $field, false, array($bind_value_1));
        $list_2 = new $this->changesetvalue_class(111, $field, false, array($bind_value_2));
        $GLOBALS['Language']->setReturnValue('getText', 'changed from', array('plugin_tracker_artifact','changed_from'));
        $GLOBALS['Language']->setReturnValue('getText', 'to', array('plugin_tracker_artifact','to'));
        $this->assertEqual($list_1->diff($list_2), ' changed from Manon to Sandra');
        $this->assertEqual($list_2->diff($list_1), ' changed from Sandra to Manon');
    }
    
    function testDiff_added() {
        $bind_value_1 = new MockTracker_FormElement_Field_List_BindValue();
        $bind_value_1->setReturnValue('__toString', 'Sandra');
        $bind_value_1->setReturnValue('getLabel', 'Sandra');
        $bind_value_2 = new MockTracker_FormElement_Field_List_BindValue();
        $bind_value_2->setReturnValue('__toString', 'Manon');
        $bind_value_2->setReturnValue('getLabel', 'Manon');
        $field  = new $this->field_class();
        $list_1 = new $this->changesetvalue_class(111, $field, false, array($bind_value_1, $bind_value_2));
        $list_2 = new $this->changesetvalue_class(111, $field, false, array($bind_value_1));
        $GLOBALS['Language']->setReturnValue('getText', 'added', array('plugin_tracker_artifact','added'));
        $this->assertEqual($list_1->diff($list_2), 'Manon added');
    }
    
    function testDiff_removed() {
        $bind_value_1 = new MockTracker_FormElement_Field_List_BindValue();
        $bind_value_1->setReturnValue('__toString', 'Sandra');
        $bind_value_1->setReturnValue('getLabel', 'Sandra');
        $bind_value_2 = new MockTracker_FormElement_Field_List_BindValue();
        $bind_value_2->setReturnValue('__toString', 'Manon');
        $bind_value_2->setReturnValue('getLabel', 'Manon');
        $field  = new $this->field_class();
        $list_1 = new $this->changesetvalue_class(111, $field, false, array($bind_value_1));
        $list_2 = new $this->changesetvalue_class(111, $field, false, array($bind_value_1, $bind_value_2));
        $GLOBALS['Language']->setReturnValue('getText', 'removed', array('plugin_tracker_artifact','removed'));
        $this->assertEqual($list_1->diff($list_2), 'Manon removed');
    }
    
    function testDiff_added_and_removed() {
        $bind_value_1 = new MockTracker_FormElement_Field_List_BindValue();
        $bind_value_1->setReturnValue('__toString', 'Sandra');
        $bind_value_1->setReturnValue('getLabel', 'Sandra');
        $bind_value_2 = new MockTracker_FormElement_Field_List_BindValue();
        $bind_value_2->setReturnValue('__toString', 'Manon');
        $bind_value_2->setReturnValue('getLabel', 'Manon');
        $bind_value_3 = new MockTracker_FormElement_Field_List_BindValue();
        $bind_value_3->setReturnValue('__toString', 'Marc');
        $bind_value_3->setReturnValue('getLabel', 'Marc');
        $bind_value_4 = new MockTracker_FormElement_Field_List_BindValue();
        $bind_value_4->setReturnValue('__toString', 'Nicolas');
        $bind_value_4->setReturnValue('getLabel', 'Nicolas');
        $field  = new $this->field_class();
        $list_1 = new $this->changesetvalue_class(111, $field, false, array($bind_value_3, $bind_value_4));
        $list_2 = new $this->changesetvalue_class(111, $field, false, array($bind_value_1, $bind_value_2));
        $GLOBALS['Language']->setReturnValue('getText', 'removed', array('plugin_tracker_artifact','removed'));
        $GLOBALS['Language']->setReturnValue('getText', 'added', array('plugin_tracker_artifact','added'));
        $this->assertPattern('/Sandra, Manon removed/', $list_1->diff($list_2));
        $this->assertPattern('/Marc, Nicolas added/', $list_1->diff($list_2));
    }
    
    function testSoapValue() {
        $bv_1 = new MockTracker_FormElement_Field_List_BindValue();
        $bv_1->setReturnValue('getSoapValue', 'Jenny');
        $bv_2 = new MockTracker_FormElement_Field_List_BindValue();
        $bv_2->setReturnValue('getSoapValue', 'Bob');
        $bv_3 = new MockTracker_FormElement_Field_List_BindValue();
        $bv_3->setReturnValue('getSoapValue', 'Rob');
        $bv_4 = new MockTracker_FormElement_Field_List_BindValue();
        $bv_4->setReturnValue('getSoapValue', 'Anne');
        
        $field      = new $this->field_class();
        $value_list = new $this->changesetvalue_class(111, $field, false, array($bv_1, $bv_2, $bv_3, $bv_4));
        $this->assertEqual(
            $value_list->getSoapValue(),
            array('bind_value' =>
                array(
                    array(
                        'bind_value_id'    => '',
                        'bind_value_label' => "Jenny",
                    ),
                    array(
                        'bind_value_id'    => '',
                        'bind_value_label' => "Bob",
                    ),
                    array(
                        'bind_value_id'    => '',
                        'bind_value_label' => "Rob",
                    ),
                    array(
                        'bind_value_id'    => '',
                        'bind_value_label' => "Anne",
                    ),
                )
            )
        );
    }
    
}

?>