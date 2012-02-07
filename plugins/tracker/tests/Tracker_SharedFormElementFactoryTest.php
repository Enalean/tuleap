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
require_once dirname(__FILE__) .'/../include/Tracker/FormElement/Tracker_FormElement_Field_String.class.php';
require_once dirname(__FILE__) .'/../include/Tracker/FormElement/Tracker_FormElement_Field_List_BindFactory.class.php';
Mock::generate('Tracker_FormElementFactory');
Mock::generate('Tracker');
Mock::generate('User');
Mock::generate('Tracker_FormElement_Field_String');
Mock::generate('Tracker_FormElement_Field_List_BindFactory');
class Tracker_SharedFormElementFactoryTest extends UnitTestCase {
    public function testCreateFormElementExtractsDataFromOriginalFieldThenForwardsToFactory() {
        $originField = $this->GivenAFieldString();
        list($decorator, $factory, $tracker, $user) = $this->GivenASharedFormELementFactory($originField, 'string');
        $originField->setReturnValue('userCanRead', true, array($user));
        $originField->getTracker()->setReturnValue('userCanView', true, array($user));

        $formElement_data = array(
            'field_id' => $originField->getId(),
        );

        $factory->expectOnce(
            'createFormElement', 
            array(
                $tracker, 
                'string', 
                array(
                    'type'              => 'string',
                    'label'             => $originField->getLabel(),
                    'description'       => $originField->getDescription(),
                    'use_it'            => $originField->isUsed(),
                    'scope'             => $originField->getScope(),
                    'required'          => $originField->isRequired(),
                    'notifications'     => $originField->hasNotifications(),
                    'original_field_id' => $originField->getId(),
                ),
                $user
            )
        );

        $decorator->createFormElement($tracker, 'shared', $formElement_data, $user);
    }

    public function testUnreadableFieldCannotBeCopied() {
        $field = $this->GivenAFieldString();
        list($decorator, $factory, $tracker, $user) = $this->GivenASharedFormELementFactory($field, 'string');
        $field->setReturnValue('userCanRead', false, array($user));
        $field->getTracker()->setReturnValue('userCanView', true, array($user));

        $this->expectException();
        $decorator->createFormElement($tracker, 'shared', array('field_id' => $field->getId()), $user);
    }

    public function testFieldInUnaccessibleTrackerCannotBeCopied() {
        $field = $this->GivenAFieldString();
        list($decorator, $factory, $tracker, $user) = $this->GivenASharedFormELementFactory($field, 'string');
        $field->setReturnValue('userCanRead', true, array($user));
        $field->getTracker()->setReturnValue('userCanView', false, array($user));

        $this->expectException();
        $decorator->createFormElement($tracker, 'shared', array('field_id' => $field->getId()), $user);
    }
    public function testDuplicatesAnyValuesThatAreBoundToTheOriginalField() {
        $originField = $this->GivenAFieldString();
        list($decorator, $factory, $tracker, $user, $boundValuesFactory) = $this->GivenASharedFormELementFactory($originField, 'string');
        $originField->setReturnValue('userCanRead', true, array($user));
        $originField->getTracker()->setReturnValue('userCanView', true, array($user));
        $newFieldId = 999;
        $factory->setReturnValue('createFormElement', $newFieldId);
        $boundValuesFactory->expectOnce('duplicateByReference', 
                array($originField->getId(), $newFieldId));
        $decorator->createFormElement($tracker, 'shared', array('field_id' => $originField->getId()), $user);

    }

    private function GivenAFieldString() {
        $tracker = new MockTracker();
        $field = new MockTracker_FormElement_Field_String();
        $field->setReturnValue('getId', 321);
        $field->setReturnValue('getTrackerId', 101);
        $field->setReturnValue('getTracker', $tracker);
        $field->setReturnValue('getParentId', 12);
        $field->setReturnValue('getName', 'NAME');
        $field->setReturnValue('getLabel', 'Label');
        $field->setReturnValue('getDescription', 'Description');
        $field->setReturnValue('isUsed', 1);
        $field->setReturnValue('getScope', 'P');
        $field->setReturnValue('isRequired', '1');
        $field->setReturnValue('hasNotifications', '1');
        $field->setReturnValue('getRank', '145');
        $field->setReturnValue('getOriginalFieldId', 321);
        return $field;
    }
    private function GivenASharedFormElementFactory($field, $type) {
        $user = new MockUser();
        $tracker = new MockTracker();
        $factory = new MockTracker_FormElementFactory();
        $boundValuesFactory = new MockTracker_FormElement_Field_List_BindFactory();
        $decorator = new Tracker_SharedFormElementFactory($factory, $boundValuesFactory);
        $factory->setReturnValue('getType', 'string', array($field));
        $factory->setReturnValue('getFormElementById', $field, array($field->getId()));
        return array($decorator, $factory, $tracker, $user, $boundValuesFactory);
    }

}
?>
