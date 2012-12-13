<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
require_once dirname(__FILE__) .'/../../../../include/constants.php';
require_once TRACKER_BASE_DIR .'/Tracker/TrackerManager.class.php';
require_once TRACKER_BASE_DIR .'/workflow/Transition/Condition/FieldNotEmpty.class.php';
require_once TRACKER_BASE_DIR .'/Tracker/FormElement/Tracker_FormElement_Field.class.php';
require_once TRACKER_BASE_DIR .'/Tracker/FormElement/Tracker_FormElementFactory.class.php';
require_once TRACKER_BASE_DIR .'/Tracker/Artifact/Tracker_Artifact_ChangesetValue.class.php';


class FieldNotEmpty_BaseTest extends TuleapTestCase {

    protected $condition;
    protected $empty_data = '';
    protected $not_empty_data = 'coin';

    public function setUp() {
        parent::setUp();
        $factory = mock('Tracker_FormElementFactory');

        $this->field   = mock('Tracker_FormElement_Field_Selectbox');
        stub($this->field)->getId()->returns(123);
        stub($this->field)->isEmpty($this->not_empty_data)->returns(false);
        stub($this->field)->isEmpty($this->empty_data)->returns(true);
        stub($this->field)->isEmpty(null)->returns(true);
        stub($factory)->getUsedFormElementById(123)->returns($this->field);

        Tracker_FormElementFactory::setInstance($factory);
        $this->dao        = mock('Workflow_Transition_Condition_FieldNotEmpty_Dao');
        $this->transition = stub('Transition')->getId()->returns(42);
        $this->condition  = new Workflow_Transition_Condition_FieldNotEmpty($this->transition, $this->dao);
        $this->artifact   = mock('Tracker_Artifact');

        $this->changeset = mock('Tracker_Artifact_Changeset');
        stub($this->artifact)->getLastChangeset()->returns($this->changeset);

        $this->previous_value = mock('Tracker_Artifact_ChangesetValue');
    }

    public function tearDown() {
        Tracker_FormElementFactory::clearInstance();
        parent::tearDown();
    }
}

class FieldNotEmpty_saveTest extends FieldNotEmpty_BaseTest {

    public function itSavesUsingTheRealFieldObject() {
        $this->condition->setField($this->field);
        expect($this->dao)->create(42, 123)->once();
        $this->condition->saveObject();
    }
}
class FieldNotEmpty_validateTest extends FieldNotEmpty_BaseTest {

    public function itReturnsTrueWhenNoField() {
        $fields_data = array();
        $is_valid    = $this->condition->validate($fields_data, $this->artifact);
        $this->assertTrue($is_valid);
    }

    public function itReturnsTrueWhenNoFieldId() {
        $fields_data = array(1 => $this->not_empty_data);
        $is_valid    = $this->condition->validate($fields_data, $this->artifact);
        $this->assertTrue($is_valid);
    }

    public function itReturnsTrueWhenFieldNotEmpty() {
        $this->condition->setField($this->field);
        $fields_data = array(123 => $this->not_empty_data);
        $is_valid    = $this->condition->validate($fields_data, $this->artifact);
        $this->assertTrue($is_valid);
    }

    public function itReturnsTrueWhenFieldNotPresentInRequestButAlreadySetInTheLastChangeset() {
        $this->condition->setField($this->field);
        stub($this->changeset)->getValue($this->field)->returns($this->previous_value);
        stub($this->previous_value)->getValue()->returns($this->not_empty_data);
        $fields_data = array();
        $is_valid    = $this->condition->validate($fields_data, $this->artifact);
        $this->assertTrue($is_valid);
    }

    public function itReturnsFalseWhenFieldNotPresentInRequestAndNotSetInTheLastChangeset() {
        $this->condition->setField($this->field);
        stub($this->changeset)->getValue($this->field)->returns($this->previous_value);
        stub($this->previous_value)->getValue()->returns($this->empty_data);
        $fields_data = array();
        $is_valid    = $this->condition->validate($fields_data, $this->artifact);
        $this->assertFalse($is_valid);
    }

    public function itReturnsFalseWhenFieldNotPresentInRequestAndNotInTheLastChangeset() {
        $this->condition->setField($this->field);
        stub($this->changeset)->getValue($this->field)->returns(null);
        $fields_data = array();
        $is_valid    = $this->condition->validate($fields_data, $this->artifact);
        $this->assertFalse($is_valid);
    }

    public function itReturnsFalseWhenFieldNotPresentInRequestAndThereIsNoLastChangeset() {
        $this->condition->setField($this->field);
        $artifact_without_changeset = mock('Tracker_Artifact');
        $fields_data = array();
        $is_valid    = $this->condition->validate($fields_data, $artifact_without_changeset);
        $this->assertFalse($is_valid);
    }

    public function itReturnsFalseWhenTheFieldIsEmpty() {
        $this->condition->setField($this->field);
        $fields_data = array(123 => $this->empty_data);
        $is_valid    = $this->condition->validate($fields_data, $this->artifact);
        $this->assertFalse($is_valid);
    }
}
?>
