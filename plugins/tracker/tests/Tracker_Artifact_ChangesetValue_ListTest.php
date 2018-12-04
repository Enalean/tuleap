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

class Tracker_Artifact_ChangesetValue_ListTest extends TuleapTestCase {

    private $field;
    private $user;

    public function setUp() {
        parent::setUp();

        $base_language = mock('BaseLanguage');
        stub($base_language)->getText('plugin_tracker_artifact','changed_from')->returns('changed from');
        stub($base_language)->getText('plugin_tracker_artifact','to')->returns('to');
        stub($base_language)->getText('plugin_tracker_artifact','cleared')->returns('cleared');
        stub($base_language)->getText('plugin_tracker_artifact','set_to')->returns('set to');
        stub($base_language)->getText('plugin_tracker_artifact','added')->returns('added');
        stub($base_language)->getText('plugin_tracker_artifact','removed')->returns('removed');

        $GLOBALS['Language'] = $base_language;

        $this->field = stub('Tracker_FormElement_Field_List')->getName()->returns('field_list');
        $this->user  = mock('PFUser');

        $this->changeset = mock('Tracker_Artifact_Changeset');
    }

    public function tearDown() {
        unset($GLOBALS['Language']);

        parent::tearDown();
    }

    public function testLists() {
        $bind_value = mock('Tracker_FormElement_Field_List_BindValue');
        $bind_value->setReturnValue('getAPIValue', 'Reopen');
        $bind_value->setReturnValue('getId', 106);
        $value_list = new Tracker_Artifact_ChangesetValue_List(111, $this->changeset, $this->field, false, array($bind_value));
        $this->assertEqual(count($value_list), 1);
        $this->assertEqual($value_list[0], $bind_value);
        $this->assertEqual($value_list->getValue(), array(106));
    }

    public function testNoDiff() {
        $bind_value = mock('Tracker_FormElement_Field_List_BindValue');
        $bind_value->setReturnValue('__toString', 'Value');
        $bind_value->setReturnValue('getLabel', 'Value');
        $list_1 = new Tracker_Artifact_ChangesetValue_List(111, $this->changeset, $this->field, false, array($bind_value));
        $list_2 = new Tracker_Artifact_ChangesetValue_List(111, $this->changeset, $this->field, false, array($bind_value));
        $this->assertFalse($list_1->diff($list_2));
        $this->assertFalse($list_2->diff($list_1));
    }

    public function testDiff_cleared() {
        $bind_value = mock('Tracker_FormElement_Field_List_BindValue');
        $bind_value->setReturnValue('__toString', 'Value');
        $bind_value->setReturnValue('getLabel', 'Value');
        $list_1 = new Tracker_Artifact_ChangesetValue_List(111, $this->changeset, $this->field, false, array());
        $list_2 = new Tracker_Artifact_ChangesetValue_List(111, $this->changeset, $this->field, false, array($bind_value));
        $this->assertEqual($list_1->diff($list_2), ' cleared');
    }

    public function testDiff_setto() {
        $bind_value_1 = mock('Tracker_FormElement_Field_List_BindValue');
        $bind_value_1->setReturnValue('__toString', 'Sandra');
        $bind_value_1->setReturnValue('getLabel', 'Sandra');
        $bind_value_2 = mock('Tracker_FormElement_Field_List_BindValue');
        $bind_value_2->setReturnValue('__toString', 'Manon');
        $bind_value_2->setReturnValue('getLabel', 'Manon');
        $list_1 = new Tracker_Artifact_ChangesetValue_List(111, $this->changeset, $this->field, false, array($bind_value_1, $bind_value_2));
        $list_2 = new Tracker_Artifact_ChangesetValue_List(111, $this->changeset, $this->field, false, array());
        $this->assertEqual($list_1->diff($list_2), ' set to Sandra, Manon');
    }

    public function testDiff_changedfrom() {
        $bind_value_1 = mock('Tracker_FormElement_Field_List_BindValue');
        $bind_value_1->setReturnValue('__toString', 'Sandra');
        $bind_value_1->setReturnValue('getLabel', 'Sandra');
        $bind_value_2 = mock('Tracker_FormElement_Field_List_BindValue');
        $bind_value_2->setReturnValue('__toString', 'Manon');
        $bind_value_2->setReturnValue('getLabel', 'Manon');
        $list_1 = new Tracker_Artifact_ChangesetValue_List(111, $this->changeset, $this->field, false, array($bind_value_1));
        $list_2 = new Tracker_Artifact_ChangesetValue_List(111, $this->changeset, $this->field, false, array($bind_value_2));
        $this->assertEqual($list_1->diff($list_2), ' changed from Manon to Sandra');
        $this->assertEqual($list_2->diff($list_1), ' changed from Sandra to Manon');
    }

    public function testDiff_changedfromWithPurification() {
        $bind_value_1 = mock('Tracker_FormElement_Field_List_BindValue');
        $bind_value_1->setReturnValue('__toString', 'Sandra <b>');
        $bind_value_1->setReturnValue('getLabel', 'Sandra <b>');
        $bind_value_2 = mock('Tracker_FormElement_Field_List_BindValue');
        $bind_value_2->setReturnValue('__toString', 'Manon <i>');
        $bind_value_2->setReturnValue('getLabel', 'Manon <i>');
        $list_1 = new Tracker_Artifact_ChangesetValue_List(111, $this->changeset, $this->field, false, array($bind_value_1));
        $list_2 = new Tracker_Artifact_ChangesetValue_List(111, $this->changeset, $this->field, false, array($bind_value_2));
        $this->assertEqual($list_1->diff($list_2), ' changed from Manon &lt;i&gt; to Sandra &lt;b&gt;');
        $this->assertEqual($list_2->diff($list_1), ' changed from Sandra &lt;b&gt; to Manon &lt;i&gt;');
    }

    public function testDiff_added() {
        $bind_value_1 = mock('Tracker_FormElement_Field_List_BindValue');
        $bind_value_1->setReturnValue('__toString', 'Sandra');
        $bind_value_1->setReturnValue('getLabel', 'Sandra');
        $bind_value_2 = mock('Tracker_FormElement_Field_List_BindValue');
        $bind_value_2->setReturnValue('__toString', 'Manon');
        $bind_value_2->setReturnValue('getLabel', 'Manon');
        $list_1 = new Tracker_Artifact_ChangesetValue_List(111, $this->changeset, $this->field, false, array($bind_value_1, $bind_value_2));
        $list_2 = new Tracker_Artifact_ChangesetValue_List(111, $this->changeset, $this->field, false, array($bind_value_1));
        $this->assertEqual($list_1->diff($list_2), 'Manon added');
    }

    public function testDiff_removed() {
        $bind_value_1 = mock('Tracker_FormElement_Field_List_BindValue');
        $bind_value_1->setReturnValue('__toString', 'Sandra');
        $bind_value_1->setReturnValue('getLabel', 'Sandra');
        $bind_value_2 = mock('Tracker_FormElement_Field_List_BindValue');
        $bind_value_2->setReturnValue('__toString', 'Manon');
        $bind_value_2->setReturnValue('getLabel', 'Manon');
        $list_1 = new Tracker_Artifact_ChangesetValue_List(111, $this->changeset, $this->field, false, array($bind_value_1));
        $list_2 = new Tracker_Artifact_ChangesetValue_List(111, $this->changeset, $this->field, false, array($bind_value_1, $bind_value_2));
        $this->assertEqual($list_1->diff($list_2), 'Manon removed');
    }

    public function testDiff_added_and_removed() {
        $bind_value_1 = mock('Tracker_FormElement_Field_List_BindValue');
        $bind_value_1->setReturnValue('__toString', 'Sandra');
        $bind_value_1->setReturnValue('getLabel', 'Sandra');
        $bind_value_2 = mock('Tracker_FormElement_Field_List_BindValue');
        $bind_value_2->setReturnValue('__toString', 'Manon');
        $bind_value_2->setReturnValue('getLabel', 'Manon');
        $bind_value_3 = mock('Tracker_FormElement_Field_List_BindValue');
        $bind_value_3->setReturnValue('__toString', 'Marc');
        $bind_value_3->setReturnValue('getLabel', 'Marc');
        $bind_value_4 = mock('Tracker_FormElement_Field_List_BindValue');
        $bind_value_4->setReturnValue('__toString', 'Nicolas');
        $bind_value_4->setReturnValue('getLabel', 'Nicolas');
        $list_1 = new Tracker_Artifact_ChangesetValue_List(111, $this->changeset, $this->field, false, array($bind_value_3, $bind_value_4));
        $list_2 = new Tracker_Artifact_ChangesetValue_List(111, $this->changeset, $this->field, false, array($bind_value_1, $bind_value_2));
        $this->assertPattern('/Sandra, Manon removed/', $list_1->diff($list_2));
        $this->assertPattern('/Marc, Nicolas added/', $list_1->diff($list_2));
    }
}
