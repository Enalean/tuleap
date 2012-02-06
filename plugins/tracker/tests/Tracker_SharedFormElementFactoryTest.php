<?php
/**
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
require_once dirname(__FILE__) .'/../include/Tracker/FormElement/Tracker_SharedFormElementFactory.class.php';

Mock::generate('Tracker_FormElementFactory');
Mock::generate('Tracker');

class Tracker_SharedFormElementFactoryTest extends UnitTestCase {
    public function testCreateFormElementDispatchesToFactory() {
        $tracker = new MockTracker();
        $factory = new MockTracker_FormElementFactory();
        $decorator = new Tracker_SharedFormElementFactory($factory);
        $tracker_id         = 101;
        $parent_id          = 12;
        $name               = 'NAME';
        $label              = 'Label';
        $description        = 'Description';
        $use_it             = '1';
        $scope              = 'P';
        $required           = '1';
        $notifications      = '1';
        $rank               = '145';
        $original_field_id  = null;
        $id                 = 321;
        $originField = new Tracker_FormElement_Field_String($id, $tracker_id, $parent_id, $name, $label, $description, $use_it, $scope, $required, $notifications, $rank, $original_field_id);
        $factory->setReturnValue('getType', 'string', array($originField));
        $factory->setReturnValue('getFormElementById', $originField, array($id));
        
        $formElement_data = array(
            'field_id' => 321,
        );
        
        $factory->expectOnce(
            'createFormElement', 
            array(
                $tracker, 
                'string', 
                array(
                    'type'              => 'string',
                    'label'             => $label,
                    'description'       => $description,
                    'use_it'            => $use_it,
                    'scope'             => $scope,
                    'required'          => $required,
                    'notifications'     => $notifications,
                    'original_field_id' => $id,
                )
            )
        );
        
        $new_formElement_data = $decorator->createFormElement($tracker, 'shared', $formElement_data);
    }
}
?>
