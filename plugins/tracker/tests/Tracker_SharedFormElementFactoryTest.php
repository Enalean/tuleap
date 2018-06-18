<?php
/**
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
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
require_once 'common/dao/include/DataAccessObject.class.php';

Mock::generate('Tracker_FormElementFactory');
Mock::generate('Tracker');
Mock::generate('PFUser');
Mock::generate('Tracker_FormElement_Field_String');
Mock::generate('Tracker_FormElement_Field_Selectbox');
Mock::generate('Tracker_FormElement_Field_List_BindFactory');
Mock::generate('Tracker_FormElement_Field_List_Bind_Static');
Mock::generate('Tracker_FormElement_Field_List_Bind_Users');
Mock::generate('Tracker_FormElement_FieldDao');

class Tracker_SharedFormElementFactoryTest extends TuleapTestCase {

    public function testCreateFormElementExtractsDataFromOriginalFieldThenForwardsToFactory() {
        $originField = $this->GivenAFieldSelectbox(321, null);
        list($decorator, $factory, $tracker, $user) = $this->GivenASharedFormElementFactory($originField, 'string');
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
                false,
                false,
            )
        );

        $decorator->createFormElement($tracker, $formElement_data, $user, false, false);
    }

    public function testUnreadableFieldCannotBeCopied() {
        $field = $this->GivenAFieldSelectbox(321, null);
        list($decorator, $factory, $tracker, $user) = $this->GivenASharedFormElementFactory($field, 'string');
        $field->setReturnValue('userCanRead', false, array($user));
        $field->getTracker()->setReturnValue('userCanView', true, array($user));

        $this->expectException();
        $decorator->createFormElement($tracker, array('field_id' => $field->getId()), $user, false, false);
    }

    public function testFieldInUnaccessibleTrackerCannotBeCopied() {
        $field = $this->GivenAFieldSelectbox(321, null);
        list($decorator, $factory, $tracker, $user) = $this->GivenASharedFormElementFactory($field, 'string');
        $field->setReturnValue('userCanRead', true, array($user));
        $field->getTracker()->setReturnValue('userCanView', false, array($user));

        $this->expectException();
        $decorator->createFormElement($tracker, array('field_id' => $field->getId()), $user, false, false);
    }

    public function testDuplicatesAnyValuesThatAreBoundToTheOriginalField() {
        $originField = $this->GivenAFieldSelectbox(321, null);
        list($decorator, $factory, $tracker, $user, $boundValuesFactory) = $this->GivenASharedFormElementFactory($originField, 'string');
        $originField->setReturnValue('userCanRead', true, array($user));
        $originField->getTracker()->setReturnValue('userCanView', true, array($user));
        $newFieldId = 999;
        $factory->setReturnValue('createFormElement', $newFieldId);
        $boundValuesFactory->expectOnce('duplicateByReference', 
                array($originField->getId(), $newFieldId));
        $decorator->createFormElement($tracker, array('field_id' => $originField->getId()), $user, false, false);

    }

    public function testSharedFieldsShouldRespectChaslesTheorem() {
        list($originalField, $originalFieldOfOriginalField, $tracker, $decorator, $factory, $user) = $this->GivenTwoFieldsThatAreShared();
        $formElement_data = $this->WhenIShareTheCopy($originalField);
        $this->ThenTheOriginalShouldBeUsed($factory, $originalFieldOfOriginalField, $decorator, $tracker, $formElement_data, $user);
    }

    private function GivenTwoFieldsThatAreShared() {
        $originalFieldOfOriginalField = $this->GivenAFieldSelectbox(123, null);
        $originalFieldOfOriginalField->setReturnValue('userCanRead', true);
        $originalFieldOfOriginalField->getTracker()->setReturnValue('userCanView', true);

        $originalField                = $this->GivenAFieldSelectbox(456, $originalFieldOfOriginalField);
        $originalField->setReturnValue('userCanRead', true);
        $originalField->getTracker()->setReturnValue('userCanView', true);

        list($decorator, $factory, $tracker, $user, $boundValuesFactory) = $this->GivenASharedFormElementFactory($originalField, 'string');
        $factory->setReturnValue('getType', 'string', array($originalFieldOfOriginalField));

        return array($originalField, $originalFieldOfOriginalField, $tracker, $decorator, $factory, $user);
    }
    
    private function WhenIShareTheCopy($originalField) {
        $formElement_data = array(
            'field_id' => $originalField->getId(),
        );
        return $formElement_data;
    }

    private function ThenTheOriginalShouldBeUsed($factory, $originalFieldOfOriginalField, $decorator, $tracker, $formElement_data, $user) {
        $factory->expectOnce(
            'createFormElement', 
            array(
                $tracker, 
                'string', 
                array(
                    'type'              => 'string',
                    'label'             => $originalFieldOfOriginalField->getLabel(),
                    'description'       => $originalFieldOfOriginalField->getDescription(),
                    'use_it'            => $originalFieldOfOriginalField->isUsed(),
                    'scope'             => $originalFieldOfOriginalField->getScope(),
                    'required'          => $originalFieldOfOriginalField->isRequired(),
                    'notifications'     => $originalFieldOfOriginalField->hasNotifications(),
                    'original_field_id' => $originalFieldOfOriginalField->getId(),
                ),
                false,
                false,
            )
        );
        $decorator->createFormElement($tracker, $formElement_data, $user, false, false);
    }

    private function GivenAFieldSelectbox($id, $originalField) {
        $tracker = new MockTracker();
        $field = new MockTracker_FormElement_Field_Selectbox();
        $field->setReturnValue('getId', $id);
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
        $field->setReturnValue('getBind', new MockTracker_FormElement_Field_List_Bind_Static());
        $field->setReturnValue('getOriginalField', $originalField);
        if ($originalField) {
            $field->setReturnValue('getOriginalFieldId', $originalField->getId());
        }
        return $field;
    }

    private function GivenASharedFormElementFactory($field, $type) {
        $user = mock('PFUser');
        $tracker = new MockTracker();
        $factory = new MockTracker_FormElementFactory();
        $boundValuesFactory = new MockTracker_FormElement_Field_List_BindFactory();
        $decorator = TestHelper::getPartialMock('Tracker_SharedFormElementFactory', array('getDao'));
        $decorator->__construct($factory, $boundValuesFactory);
        $factory->setReturnValue('getType', 'string', array($field));
        $factory->setReturnValue('getFormElementById', $field, array($field->getId()));
        return array($decorator, $factory, $tracker, $user, $boundValuesFactory);
    }
    
    public function testCreateSharedFieldNotPossibleIfFieldNotSelectbox() {
        $field = $this->GivenAFieldString();
        list($decorator, $factory, $tracker, $user) = $this->GivenASharedFormElementFactory($field, 'sb');
        $field->setReturnValue('userCanRead', true);
        $field->getTracker()->setReturnValue('userCanView', true);

        $this->expectException();
        $decorator->createFormElement($tracker, array('field_id' => $field->getId()), $user, false, false);
    }
    
    public function testCreateSharedFieldNotPossibleIfFieldNotStaticSelectbox() {
        $field = $this->GivenAUserSelectbox();
        list($decorator, $factory, $tracker, $user) = $this->GivenASharedFormElementFactory($field, 'sb');
        $field->setReturnValue('userCanRead', true);
        $field->getTracker()->setReturnValue('userCanView', true);

        $this->expectException();
        $decorator->createFormElement($tracker, array('field_id' => $field->getId()), $user, false, false);
    }
    
    private function GivenAFieldString() {
        $tracker = new MockTracker();
        $field = new MockTracker_FormElement_Field_String();
        $field->setReturnValue('getTracker', $tracker);
        return $field;
    }
    
    private function GivenAUserSelectbox() {
        $tracker = new MockTracker();
        $field = new MockTracker_FormElement_Field_Selectbox();
        $field->setReturnValue('getTracker', $tracker);
        $field->setReturnValue('getBind', new MockTracker_FormElement_Field_List_Bind_Users());
        return $field;
    }
    
    
}

?>
