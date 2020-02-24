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

class CardFieldsPresenterBuilder
{
    /**
     * @param Tracker_FormElement_Field[] $used_fields
     * @param Tracker_FormElement_Field[] $form_elements_fields
     *
     * @return FieldPresenter
     */
    public function build(array $used_fields, array $form_elements_fields)
    {
        return new FieldPresenter(
            $this->buildAlreadyUsedFields($used_fields),
            $this->buildFieldThatCanBeAddedToCard($used_fields, $form_elements_fields)
        );
    }

    /**
     * @param array                       $already_used_field
     * @param Tracker_FormElement_Field[] $tracker_field
     *
     * @return array
     */
    private function buildFieldThatCanBeAddedToCard(array $already_used_field, array $tracker_field)
    {
        $escpaed_options = [];

        foreach ($tracker_field as $field) {
            if (! isset($already_used_field[$field->getId()])) {
                $escpaed_options[] = $field->fetchAddTooltip($already_used_field);
            }
        }

        return $escpaed_options;
    }

    /**
     * @param array $form_elements_fields
     *
     * @return array
     */
    private function buildAlreadyUsedFields(array $form_elements_fields)
    {
        $formatted_field = [];

        foreach ($form_elements_fields as $field) {
            $formatted_field[] = [
                "id"   => $field->getId(),
                "name" => $field->getLabel()
            ];
        }

        return $formatted_field;
    }
}
