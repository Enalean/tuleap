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

    public function setUp() {
        parent::setUp();
        $this->dao         = mock('Workflow_Transition_Condition_FieldNotEmpty_Dao');

    }

    public function testValidateReturnsTrueWhenNoField() {

        $transition = mock('Transition');

        $field_not_empty = new Workflow_Transition_Condition_FieldNotEmpty($transition, $this->dao);

        $fields_data = array();

        $return = $field_not_empty->validate($fields_data);

        $this->assertTrue($return);
    }
    public function testValidateReturnsTrueWhenNoFieldId() {

        $transition = mock('Transition');

        $field_not_empty = new Workflow_Transition_Condition_FieldNotEmpty($transition, $this->dao);

        $fields_data = array(1 => 'test');

        $return = $field_not_empty->validate($fields_data);

        $this->assertTrue($return);
    }

    public function testValidateReturnsTrueWhenFieldNotEmpty() {
        $field = mock('Tracker_FormElement_Field_Selectbox');
        stub($field)->isEmpty()->returns(false);

        $factory = mock('Tracker_FormElementFactory');
        stub($factory)->getUsedFormElementById()->returns($field);

        Tracker_FormElementFactory::setInstance($factory);

        $transition = mock('Transition');

        $field_not_empty = new Workflow_Transition_Condition_FieldNotEmpty($transition, $this->dao);
        $field_not_empty->setFieldId(1);

        $fields_data = array(1 => 'test');

        $return = $field_not_empty->validate($fields_data);

        $this->assertTrue($return);
    }

    public function itSavesTheNewFieldNotEmpty() {
        $this->transition = stub('Transition')->getId()->returns(42);

        $field_not_empty = new Workflow_Transition_Condition_FieldNotEmpty($this->transition, $this->dao);
        $field_not_empty->setFieldId(123);
        expect($this->dao)->create(42, 123)->once();
        $field_not_empty->saveObject();

    }
}

?>
