<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use DateTime;
use Tracker_FormElement_Field_ComputedDao;

class ComputedFieldCalculator
{
    /**
     * @var Tracker_FormElement_Field_ComputedDao
     */
    private $computed_dao;

    public function __construct(Tracker_FormElement_Field_ComputedDao $computed_dao)
    {
        $this->computed_dao = $computed_dao;
    }


    public function calculateForComputedFields(
        array $artifact_ids_to_fetch,
        $timestamp,
        $stop_on_manual_value,
        $target_field_name,
        $computed_field_id
    ) {
        return $this->calculate(
            $artifact_ids_to_fetch,
            $timestamp,
            $stop_on_manual_value,
            $target_field_name,
            $computed_field_id,
            true
        );
    }

    public function calculateForBurndown(
        array $artifact_ids_to_fetch,
        $timestamp,
        $stop_on_manual_value,
        $target_field_name,
        $computed_field_id
    ) {
        return $this->calculate(
            $artifact_ids_to_fetch,
            $timestamp,
            $stop_on_manual_value,
            $target_field_name,
            $computed_field_id,
            false
        );
    }

    private function fetchChildrenAndManualValuesOfArtifacts(
        array $artifact_ids_to_fetch,
        $timestamp,
        $stop_on_manual_value,
        $target_field_name,
        $computed_field_id,
        $for_computed_field
    ) {
        $manual_sum = null;

        if ($timestamp !== null && $for_computed_field === true) {
            $dar = $this->fetchChildrenForComputedFieldWithCache(
                $artifact_ids_to_fetch,
                $timestamp,
                $target_field_name,
                $computed_field_id
            );
        } else if ($timestamp !== null && $for_computed_field === false) {
            $enhanced_dar = $this->getChildrenForBurndownWithComputedValuesAtGivenDate(
                $artifact_ids_to_fetch,
                $timestamp
            );

            $manual_sum = $enhanced_dar['manual_sum'];
            $dar        = $enhanced_dar['computed_values'];
        } else {
            $dar = $this->fetchChildrenOfComputedFieldForToday(
                $artifact_ids_to_fetch,
                $target_field_name,
                $computed_field_id,
                $stop_on_manual_value
            );
        }

        return array(
            'children'   => $dar,
            'manual_sum' => $manual_sum
        );
    }

    private function fetchChildrenOfComputedFieldForToday(
        array $artifact_ids_to_fetch,
        $target_field_name,
        $computed_field_id,
        $stop_on_manual_value
    ) {
        return $this->computed_dao->getComputedFieldValues(
            $artifact_ids_to_fetch,
            $target_field_name,
            $computed_field_id,
            $stop_on_manual_value
        );
    }

    private function getChildrenForBurndownWithComputedValuesAtGivenDate(array $artifact_ids_to_fetch, $timestamp)
    {
        $computed_artifacts = array();
        $manual_sum         = null;
        $selected_day       = new DateTime();
        $selected_day->setTimestamp($timestamp);
        $selected_day->setTime(23, 59, 59);

        foreach ($artifact_ids_to_fetch as $artifact_id) {
            $manual_value = $this->computed_dao->getBurndownManualValueAtGivenTimestamp(
                $artifact_id,
                $selected_day->getTimestamp()
            );

            if ($manual_value) {
                $manual_sum += $manual_value['value'];
            } else {
                $computed_artifacts[] = $artifact_id;
            }
        }

        if (count($computed_artifacts) > 0) {
            return array(
                'computed_values' => $this->computed_dao->getBurndownComputedValueAtGivenTimestamp(
                    $computed_artifacts,
                    $selected_day->getTimestamp()
                ),
                'manual_sum'      => $manual_sum
            );
        } else {
            return array(
                'computed_values' => false,
                'manual_sum'      => $manual_sum
            );
        }
    }

    private function fetchChildrenForComputedFieldWithCache(
        array $artifact_ids_to_fetch,
        $timestamp,
        $target_field_name,
        $computed_field_id
    ) {
        return $this->computed_dao->getFieldValuesAtTimestamp(
            $artifact_ids_to_fetch,
            $target_field_name,
            $timestamp,
            $computed_field_id
        );
    }

    private function calculate(
        array $artifact_ids_to_fetch,
        $timestamp,
        $stop_on_manual_value,
        $target_field_name,
        $computed_field_id,
        $for_computed_field
    ) {
        $sum          = null;
        $already_seen = array();

        do {
            $children_list = $this->fetchChildrenAndManualValuesOfArtifacts(
                $artifact_ids_to_fetch,
                $timestamp,
                $stop_on_manual_value,
                $target_field_name,
                $computed_field_id,
                $for_computed_field
            );

            $manual_sum = $children_list['manual_sum'];
            $children   = $children_list['children'];

            $current_fetch_artifact = $artifact_ids_to_fetch;
            $artifact_ids_to_fetch  = array();
            $last_id                = null;
            if ($children) {
                foreach ($children as $row) {
                    if (! isset($already_seen[$row['id']]) &&
                        (! isset($row['parent_id']) || $last_id !== $row['parent_id'])
                    ) {
                        if (isset($row['value']) && $row['value'] !== null) {
                            $already_seen[$row['parent_id']] = true;
                            $last_id                         = $row['parent_id'];
                            $sum                            += $row['value'];
                        } elseif ($row['type'] === 'computed') {
                            $artifact_ids_to_fetch[] = $row['artifact_link_id'];
                        } elseif (isset($row[$row['type'] . '_value'])) {
                            $already_seen[$row['id']] = true;
                            $sum                     += $row[$row['type'] . '_value'];
                        }
                    }
                }
                $children->freeMemory();
            }

            foreach ($current_fetch_artifact as $artifact_fetched) {
                $already_seen[$artifact_fetched] = true;
            }

            if ($manual_sum) {
                $sum += $manual_sum;
            }
        } while (count($artifact_ids_to_fetch) > 0);

        return $sum;
    }
}
