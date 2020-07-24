<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

class FieldDataBuilder
{
    public function getDataLikeWebUI(array $new_values, array $removed_values, array $submitted_values)
    {
        return [
            'new_values'     => $this->formatNewValuesLikeWebUI($new_values),
            'removed_values' => $this->formatRemovedValuesLikeWebUI($removed_values),
            'natures'        => $this->formatNaturesLikeWebUI($submitted_values)
        ];
    }

    private function formatNaturesLikeWebUI(array $new_values)
    {
        $natures = [];

        foreach ($new_values as $value) {
            if (is_array($value) && isset($value['type'])) {
                $natures[$value['id']] = $value['type'];
            }
        }

        return $natures;
    }

    private function formatNewValuesLikeWebUI(array $new_values)
    {
        $artifact_ids = [];
        foreach ($new_values as $value) {
            $artifact_ids[] = $value;
        }

        return implode(',', $artifact_ids);
    }

    private function formatRemovedValuesLikeWebUI(array $removed_values)
    {
        $values = [];
        foreach ($removed_values as $value) {
            $values[$value] = [$value];
        }

        return $values;
    }

    /**
     * @param array $new_value
     *
     * @return array
     */
    public function getArrayOfIdsFromArray(array $new_value)
    {
        $artifact_ids = [];

        foreach ($new_value as $value) {
            $artifact_ids[] = (int) $value['id'];
        }

        return $artifact_ids;
    }

    public function getArrayOfIdsFromString($new_value)
    {
        return array_filter(array_map('intval', explode(',', $new_value)));
    }

    public function buildFieldDataFromREST(array $link)
    {
        $id     = null;
        $nature = null;
        if (array_key_exists('id', $link)) {
            $id = $link['id'];
        }
        if (array_key_exists('type', $link)) {
            $nature = $link['type'];
        }

        if ($id) {
            return [
                "id"     => $id,
                "nature" => $nature
            ];
        }

        return null;
    }
}
