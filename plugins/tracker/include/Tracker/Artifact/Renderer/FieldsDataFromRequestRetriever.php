<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Renderer;

use Codendi_Request;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Semantic\Status\StatusValuesCollection;
use Tuleap\Tracker\Workflow\FirstPossibleValueInListRetriever;
use Tuleap\Tracker\Workflow\NoPossibleValueException;

final class FieldsDataFromRequestRetriever
{
    public function __construct(
        private Tracker_FormElementFactory $form_element_factory,
        private FirstPossibleValueInListRetriever $first_possible_value_retriever,
    ) {
    }

    /**
     * @throws NoPossibleValueException
     */
    public function getAugmentedDataFromRequest(Artifact $artifact, Codendi_Request $request, \PFUser $user): array
    {
        $fields_data = $request->get('artifact');
        if (isset($fields_data['possible_values'], $fields_data['field_id'])) {
            return $this->getFirstPossibleValueFromPossibleValues($artifact, $fields_data, $user);
        }

        $fields_data['request_method_called'] = 'artifact-update';
        $artifact->getTracker()->augmentDataFromRequest($fields_data);
        unset($fields_data['request_method_called']);

        return $fields_data;
    }

    /**
     * @throws NoPossibleValueException
     */
    private function getFirstPossibleValueFromPossibleValues(Artifact $artifact, array $field_values, \PFUser $user): array
    {
        $fields_data      = [];
        $value_collection = new StatusValuesCollection(json_decode($field_values['possible_values']));
        $field            = $this->form_element_factory->getFieldById($field_values['field_id']);

        assert($field instanceof ListField);
        $fields_data[$field->getId()] = $this->first_possible_value_retriever->getFirstPossibleValue(
            $artifact,
            $field,
            $value_collection,
            $user
        );

        return $fields_data;
    }
}
