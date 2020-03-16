<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

namespace Tuleap\Cardwall\REST\v1;

use Luracast\Restler\RestException;
use Tracker_Semantic_Title;
use Tracker_FormElement_Field;
use CardResourceBadValueFormatException;
use Cardwall_FieldNotOnCardException;
use Cardwall_SingleCard;
use PFUser;

class CardValidator
{

    public function getFieldsDataFromREST(PFUser $user, Cardwall_SingleCard $single_card, $label, array $values, $column_id = null)
    {
        $fields_data  = $this->getLabelFieldData($single_card, $label);
        $fields_data += $this->getValuesFieldData($user, $values, $single_card);
        if ($column_id !== null) {
            $fields_data += $this->getColumnIdFieldData($single_card, $column_id);
        }
        return $fields_data;
    }

    private function getLabelFieldData(Cardwall_SingleCard $single_card, $label)
    {
        $semantic_title = Tracker_Semantic_Title::load($single_card->getArtifact()->getTracker());
        if ($semantic_title) {
            return array(
                $semantic_title->getFieldId() => $label
            );
        }
        return array();
    }

    private function getColumnIdFieldData(Cardwall_SingleCard $single_card, $column_id)
    {
        $mapping = $single_card->getMapping();
        if (! $mapping) {
            return [];
        }
        foreach ($mapping->getValueMappings() as $value_mapping) {
            if ($value_mapping->getColumnId() == $column_id) {
                return array(
                    $mapping->getField()->getId() => $value_mapping->getValueId()
                );
            }
        }
        return array();
    }

    private function getValuesFieldData(PFUser $user, $values, Cardwall_SingleCard $single_card)
    {
        $new_values = array();
        foreach ($values as $value) {
            try {
                $field                       = $this->getField($user, $single_card, $value);
                $new_values[$field->getId()] = $this->getFieldValue($single_card, $field, $value);
            } catch (CardResourceBadValueFormatException $exception) {
                throw new RestException(400, $exception->getMessage());
            } catch (Cardwall_FieldNotOnCardException $exception) {
                throw new RestException(400, $exception->getMessage());
            }
        }
        return $new_values;
    }

    private function getFieldValue(Cardwall_SingleCard $single_card, Tracker_FormElement_Field $field, $value)
    {
        $artifact = $single_card->getArtifact();
        return $field->getFieldDataFromRESTValue($value, $artifact);
    }

    private function getField(PFUser $user, Cardwall_SingleCard $single_card, $value)
    {
        if (! array_key_exists('field_id', $value)) {
            throw new CardResourceBadValueFormatException('field_id');
        }

        $field = $single_card->getFieldById($value['field_id']);
        if (! $field) {
            throw new RestException(404, 'Field ' . $value['field_id'] . ' doesn\'t belongs to card');
        }
        if (! $field->userCanUpdate($user)) {
            throw new RestException(403, 'Field "' . $field->getLabel() . '" (' . $field->getId() . ') cannot be modified or you don\'t have permission to update it');
        }
        return $field;
    }
}
