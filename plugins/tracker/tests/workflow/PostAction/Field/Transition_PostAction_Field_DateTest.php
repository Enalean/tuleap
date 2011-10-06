<?php
/*
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

require_once(dirname(__FILE__).'/../../../../include/workflow/PostAction/Field/Transition_PostAction_Field_Date.class.php');
Mock::generate('Transition');
Mock::generate('BaseLanguage');

class Transition_PostAction_Field_DateTest extends UnitTestCase {
    
    public function setUp() {
        $GLOBALS['Language'] = new MockBaseLanguage();
        $GLOBALS['Language']->setReturnValue('getText', 'Y-m-d', array('system', 'datefmt_short'));
    }
    public function tearDown() {
        unset($GLOBALS['Language']);
    }
    public function testBeforeShouldSetTheDate() {
        $fields_data = array('field_id' => 'value');
        $transition  = new MockTransition();
        $field_id    = 102;
        $id          = 1;
        $value_type  = Transition_PostAction_Field_Date::FILL_CURRENT_TIME;
        $post_action = new Transition_PostAction_Field_Date($transition, $id, $field_id, $value_type);
        $post_action->before($fields_data);
        $this->assertEqual(date('Y-m-d', $_SERVER['REQUEST_TIME']), $fields_data[$field_id]);
    }
    
    public function testBeforeShouldClearTheDate() {
        $transition  = new MockTransition();
        $field_id    = 102;
        $id          = 1;
        $fields_data = array(
            'field_id' => 'value',
            $field_id  => '1317817376',
        );
        $value_type = Transition_PostAction_Field_Date::CLEAR_DATE;
        $post_action = new Transition_PostAction_Field_Date($transition, $id, $field_id, $value_type);
        $post_action->before($fields_data);
        $this->assertEqual('', $fields_data[$field_id]);
    }
}
?>
