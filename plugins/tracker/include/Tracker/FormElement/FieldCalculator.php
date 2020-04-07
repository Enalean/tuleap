<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

class FieldCalculator
{
    /**
     * @var IProvideArtifactChildrenForComputedCalculation
     */
    private $children_computation_provider;

    public function __construct(IProvideArtifactChildrenForComputedCalculation $children_computation_provider)
    {
        $this->children_computation_provider = $children_computation_provider;
    }

    public function calculate(
        array $artifact_ids_to_fetch,
        $timestamp,
        $stop_on_manual_value,
        $target_field_name,
        $computed_field_id
    ) {
        $sum          = null;
        $already_seen = array();

        do {
            $children_list = $this->children_computation_provider->fetchChildrenAndManualValuesOfArtifacts(
                $artifact_ids_to_fetch,
                $timestamp,
                $stop_on_manual_value,
                $target_field_name,
                $computed_field_id
            );

            $manual_sum = $children_list['manual_sum'];
            $children   = $children_list['children'];

            $current_fetch_artifact = $artifact_ids_to_fetch;
            $artifact_ids_to_fetch  = array();
            $last_id                = null;
            if ($children) {
                foreach ($children as $row) {
                    if (
                        ! isset($already_seen[$row['id']]) &&
                        (! isset($row['parent_id']) || $last_id !== $row['parent_id'])
                    ) {
                        if (isset($row['value']) && $row['value'] !== null) {
                            $already_seen[$row['parent_id']] = true;
                            $last_id                         = $row['parent_id'];
                            $sum                             += $row['value'];
                        } elseif ($row['type'] === 'computed' && $row['artifact_link_id'] !== null) {
                            $artifact_ids_to_fetch[] = $row['artifact_link_id'];
                        } elseif (isset($row[$row['type'] . '_value'])) {
                            $already_seen[$row['id']] = true;
                            $sum                      += $row[$row['type'] . '_value'];
                        }
                    }
                }
                $children->freeMemory();
            }

            foreach ($current_fetch_artifact as $artifact_fetched) {
                $already_seen[$artifact_fetched] = true;
            }

            if ($manual_sum !== null) {
                $sum += $manual_sum;
            }
        } while (count($artifact_ids_to_fetch) > 0);

        return $sum;
    }
}
