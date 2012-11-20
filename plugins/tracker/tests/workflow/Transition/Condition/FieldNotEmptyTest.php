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


class FieldNotEmptyTests extends TuleapTestCase {

    private $condition;
    private $empty_data = '';
    private $not_empty_data = 'coin';

    public function setUp() {
        parent::setUp();
        $factory = mock('Tracker_FormElementFactory');

        $this->field   = mock('Tracker_FormElement_Field_Selectbox');
        stub($this->field)->getId()->returns(123);
        stub($this->field)->isEmpty($this->not_empty_data)->returns(false);
        stub($this->field)->isEmpty($this->empty_data)->returns(true);
        stub($factory)->getUsedFormElementById(123)->returns($this->field);

        Tracker_FormElementFactory::setInstance($factory);
        $this->dao        = mock('Workflow_Transition_Condition_FieldNotEmpty_Dao');
        $this->transition = stub('Transition')->getId()->returns(42);
        $this->condition  = new Workflow_Transition_Condition_FieldNotEmpty($this->transition, $this->dao);
        $this->artifact   = mock('Tracker_Artifact');
    }

    public function tearDown() {
        Tracker_FormElementFactory::clearInstance();
        parent::tearDown();
    }

    public function testValidateReturnsTrueWhenNoField() {
        $fields_data = array();
        $is_valid    = $this->condition->validate($fields_data, $this->artifact);
        $this->assertTrue($is_valid);
    }
    public function testValidateReturnsTrueWhenNoFieldId() {
        $fields_data = array(1 => $this->not_empty_data);
        $is_valid    = $this->condition->validate($fields_data, $this->artifact);
        $this->assertTrue($is_valid);
    }

    public function testValidateReturnsTrueWhenFieldNotEmpty() {
        $this->condition->setField($this->field);
        $fields_data = array(123 => $this->not_empty_data);
        $is_valid    = $this->condition->validate($fields_data, $this->artifact);
        $this->assertTrue($is_valid);
    }

    public function itReturnsFalseWhenTheFieldIsEmpty() {
        $this->condition->setField($this->field);
        $fields_data = array(123 => $this->empty_data);
        $is_valid    = $this->condition->validate($fields_data, $this->artifact);
        $this->assertFalse($is_valid);
    }

    public function itSavesUsingTheRealFieldObject() {
        $this->condition->setField($this->field);
        expect($this->dao)->create(42, 123)->once();
        $this->condition->saveObject();
    }
}
?>
