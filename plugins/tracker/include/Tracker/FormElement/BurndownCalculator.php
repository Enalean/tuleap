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

class BurndownCalculator implements IProvideArtifactChildrenForComputedCalculation
{
    /**
     * @var Tracker_FormElement_Field_ComputedDao
     */
    private $computed_dao;

    public function __construct(
        Tracker_FormElement_Field_ComputedDao $computed_dao
    ) {
        $this->computed_dao        = $computed_dao;
    }

    public function fetchChildrenAndManualValuesOfArtifacts(
        array $artifact_ids_to_fetch,
        $timestamp,
        $stop_on_manual_value,
        $target_field_name,
        $computed_field_id
    ) {
        $enhanced_dar = $this->getChildrenForBurndownWithComputedValuesAtGivenDate(
            $artifact_ids_to_fetch,
            $timestamp
        );

        $manual_sum = $enhanced_dar['manual_sum'];
        $dar        = $enhanced_dar['computed_values'];

        return array(
            'children'   => $dar,
            'manual_sum' => $manual_sum
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

            if ($manual_value['value'] !== null) {
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
}
