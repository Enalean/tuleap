<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Cardwall\Semantic;

use Tracker_FormElement_Field;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElementFactory;

class CardFieldsTrackerPresenterBuilder
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(Tracker_FormElementFactory $form_element_factory)
    {
        $this->form_element_factory = $form_element_factory;
    }

    /**
     * @param Tracker_FormElement_Field[] $form_elements_fields
     *
     * @return array
     */
    public function getTrackerFields(array $form_elements_fields)
    {
        $formatted_field = [];

        foreach ($form_elements_fields as $field) {
            if ($this->isFieldAListBoundToStaticValues($field)) {
                $formatted_field[] = [
                    "id"   => $field->getId(),
                    "name" => $field->getLabel()
                ];
            }
        }

        return $formatted_field;
    }

    private function isFieldAListBoundToStaticValues(Tracker_FormElement_Field $field)
    {
        return ($this->form_element_factory->getType($field) === 'sb'
            || $this->form_element_factory->getType($field) === 'rb')
            && $field->getBind()->getType() === Tracker_FormElement_Field_List_Bind_Static::TYPE
        ;
    }
}
