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

class Tracker_SharedFormElementFactory {
    /**
     * @var Tracker_FormElementFactory
     */
    protected $factory;
    /**
     * @var Tracker_FormElement_Field_List_BindFactory $boundValuesFactory 
     */
    private $boundValuesFactory;
            
    function __construct(Tracker_FormElementFactory $factory, Tracker_FormElement_Field_List_BindFactory $boundValuesFactory) {
        $this->boundValuesFactory = $boundValuesFactory;
        $this->factory = $factory;
    }
    public function createFormElement($tracker, $type, $formElement_data, User $user) {
        $field = $this->factory->getFormElementById($formElement_data['field_id']);
        if ($field->userCanRead($user) && $field->getTracker()->userCanView($user)) {
            $data = $this->populateFormElementDataForASharedField($field);
            $type = $data['type'];
            $id = $this->factory->createFormElement($tracker, $type, $data, $user);
            $this->boundValuesFactory->duplicateByReference($field->getId(), $id);
            return $id;
        }
        throw new Exception('Permission denied');
    }
    
    private function populateFormElementDataForASharedField($originField) {
        return array(
            'type'              => $this->factory->getType($originField),
            'label'             => $originField->getLabel(),
            'description'       => $originField->getDescription(),
            'label'             => $originField->getLabel(),
            'use_it'            => $originField->isUsed(),
            'scope'             => $originField->getScope(),
            'required'          => $originField->isRequired(),
            'notifications'     => $originField->hasNotifications(),
            'original_field_id' => $originField->getId(),
        );
    }
}

?>
