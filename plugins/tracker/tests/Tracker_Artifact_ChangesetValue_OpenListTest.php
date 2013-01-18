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
Mock::generate('Tracker_FormElement_Field_OpenList');
Mock::generate('Tracker_FormElement_Field_List_OpenValue');

require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');


class Tracker_Artifact_ChangesetValue_OpenListTest extends Tracker_Artifact_ChangesetValue_ListTest {
    
    function __construct($name = 'Changeset Value Open List Test') {
        parent::__construct($name);
        $this->field_class          = 'MockTracker_FormElement_Field_OpenList';
        $this->changesetvalue_class = 'Tracker_Artifact_ChangesetValue_OpenList';
    }
    function testLists() {
        $bind_value = new MockTracker_FormElement_Field_List_BindValue();
        $bind_value->setReturnValue('getSoapValue', 'Reopen');
        $bind_value->setReturnValue('getId', 106);
        $bind_value->setReturnValue('getJsonId', 'b106');
        $field      = new $this->field_class();
        $value_list = new $this->changesetvalue_class(111, $field, false, array($bind_value));
        $this->assertEqual(count($value_list), 1);
        $this->assertEqual($value_list[0], $bind_value);
        $this->assertEqual($value_list->getSoapValue(), "Reopen");
        $this->assertEqual($value_list->getValue(), array('b106'));
    }
    
    function testDiff_setto() {
        $bind_value_1 = new MockTracker_FormElement_Field_List_BindValue();
        $bind_value_1->setReturnValue('__toString', 'Sandra');
        $bind_value_1->setReturnValue('getLabel', 'Sandra');
        $open_value_2 = new MockTracker_FormElement_Field_List_OpenValue();
        $open_value_2->setReturnValue('__toString', 'Manon');
        $open_value_2->setReturnValue('getLabel', 'Manon');
        $field  = new $this->field_class();
        $list_1 = new $this->changesetvalue_class(111, $field, false, array($bind_value_1, $open_value_2));
        $list_2 = new $this->changesetvalue_class(111, $field, false, array());
        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getText', 'set to', array('plugin_tracker_artifact','set_to'));
        $this->assertEqual($list_1->diff($list_2), ' set to Sandra, Manon');
    }
    
    function testDiff_changedfrom() {
        $bind_value_1 = new MockTracker_FormElement_Field_List_BindValue();
        $bind_value_1->setReturnValue('__toString', 'Sandra');
        $bind_value_1->setReturnValue('getLabel', 'Sandra');
        $open_value_2 = new MockTracker_FormElement_Field_List_OpenValue();
        $open_value_2->setReturnValue('__toString', 'Manon');
        $open_value_2->setReturnValue('getLabel', 'Manon');
        $field  = new $this->field_class();
        $list_1 = new $this->changesetvalue_class(111, $field, false, array($bind_value_1));
        $list_2 = new $this->changesetvalue_class(111, $field, false, array($open_value_2));
        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getText', 'changed from', array('plugin_tracker_artifact','changed_from'));
        $GLOBALS['Language']->setReturnValue('getText', 'to', array('plugin_tracker_artifact','to'));
        $this->assertEqual($list_1->diff($list_2), ' changed from Manon to Sandra');
        $this->assertEqual($list_2->diff($list_1), ' changed from Sandra to Manon');
    }
    
    function testDiff_added() {
        $bind_value_1 = new MockTracker_FormElement_Field_List_BindValue();
        $bind_value_1->setReturnValue('__toString', 'Sandra');
        $bind_value_1->setReturnValue('getLabel', 'Sandra');
        $open_value_2 = new MockTracker_FormElement_Field_List_OpenValue();
        $open_value_2->setReturnValue('__toString', 'Manon');
        $open_value_2->setReturnValue('getLabel', 'Manon');
        $field  = new $this->field_class();
        $list_1 = new $this->changesetvalue_class(111, $field, false, array($bind_value_1, $open_value_2));
        $list_2 = new $this->changesetvalue_class(111, $field, false, array($bind_value_1));
        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getText', 'added', array('plugin_tracker_artifact','added'));
        $this->assertEqual($list_1->diff($list_2), 'Manon added');
    }
    
    function testDiff_removed() {
        $bind_value_1 = new MockTracker_FormElement_Field_List_BindValue();
        $bind_value_1->setReturnValue('__toString', 'Sandra');
        $bind_value_1->setReturnValue('getLabel', 'Sandra');
        $open_value_2 = new MockTracker_FormElement_Field_List_OpenValue();
        $open_value_2->setReturnValue('__toString', 'Manon');
        $open_value_2->setReturnValue('getLabel', 'Manon');
        $field  = new $this->field_class();
        $list_1 = new $this->changesetvalue_class(111, $field, false, array($bind_value_1));
        $list_2 = new $this->changesetvalue_class(111, $field, false, array($bind_value_1, $open_value_2));
        $GLOBALS['Language'] = new MockBaseLanguage($this);
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
        $open_value_3 = new MockTracker_FormElement_Field_List_OpenValue();
        $open_value_3->setReturnValue('__toString', 'Marc');
        $open_value_3->setReturnValue('getLabel', 'Marc');
        $open_value_4 = new MockTracker_FormElement_Field_List_OpenValue();
        $open_value_4->setReturnValue('__toString', 'Nicolas');
        $open_value_4->setReturnValue('getLabel', 'Nicolas');
        $field  = new $this->field_class();
        $list_1 = new $this->changesetvalue_class(111, $field, false, array($open_value_3, $open_value_4));
        $list_2 = new $this->changesetvalue_class(111, $field, false, array($bind_value_1, $bind_value_2));
        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getText', 'removed', array('plugin_tracker_artifact','removed'));
        $GLOBALS['Language']->setReturnValue('getText', 'added', array('plugin_tracker_artifact','added'));
        $this->assertPattern('/Sandra, Manon removed/', $list_1->diff($list_2));
        $this->assertPattern('/Marc, Nicolas added/', $list_1->diff($list_2));
    }
}

?>