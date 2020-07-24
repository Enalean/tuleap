<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

declare(strict_types=1);

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
        $already_seen = new ArtifactsAlreadyProcessedDuringComputationCollection();

        do {
            $children_list = $this->children_computation_provider->fetchChildrenAndManualValuesOfArtifacts(
                $artifact_ids_to_fetch,
                $timestamp,
                $stop_on_manual_value,
                $target_field_name,
                $computed_field_id,
                $already_seen
            );

            $manual_sum = $children_list['manual_sum'];
            $children   = $children_list['children'];

            $current_fetch_artifact = $artifact_ids_to_fetch;
            $artifact_ids_to_fetch  = [];
            $last_id                = null;
            if ($children) {
                foreach ($children as $row) {
                    if (
                        ! $already_seen->hasArtifactBeenProcessedDuringComputation($row['id']) &&
                        (! isset($row['parent_id']) || $last_id !== $row['parent_id'])
                    ) {
                        if (isset($row['value']) && $row['value'] !== null) {
                            $already_seen->addArtifactAsAlreadyProcessed((string) $row['parent_id']);
                            $last_id                         = $row['parent_id'];
                            $sum                             += $row['value'];
                        } elseif ($row['type'] === 'computed' && $row['artifact_link_id'] !== null) {
                            $artifact_ids_to_fetch[] = $row['artifact_link_id'];
                        } elseif (isset($row[$row['type'] . '_value'])) {
                            $already_seen->addArtifactAsAlreadyProcessed((string) $row['id']);
                            $sum += $row[$row['type'] . '_value'];
                        }
                    }
                }
                $children->freeMemory();
            }

            foreach ($current_fetch_artifact as $artifact_fetched) {
                $already_seen->addArtifactAsAlreadyProcessed((string) $artifact_fetched);
            }

            if ($manual_sum !== null) {
                $sum += $manual_sum;
            }
        } while (count($artifact_ids_to_fetch) > 0);

        return $sum;
    }
}
