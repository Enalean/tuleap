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

namespace Tuleap\AgileDashboard\FormElement;

use DateTime;

class BurnupManualValuesAndChildrenListRetriever
{
    /**
     * @var BurnupDao
     */
    private $burnup_dao;

    public function __construct(
        BurnupDao $burnup_dao
    ) {
        $this->burnup_dao = $burnup_dao;
    }

    public function getChildrenForBurnupWithComputedValuesAtGivenDate(
        array $artifact_ids_to_fetch,
        $timestamp,
        $only_done_artifacts
    ) {
        $computed_artifacts = array();
        $manual_sum         = null;

        $selected_day       = new DateTime();
        $selected_day->setTimestamp($timestamp);
        $selected_day->setTime(23, 59, 59);

        foreach ($artifact_ids_to_fetch as $artifact_id) {
            $manual_value = $this->burnup_dao->getBurnupManualValueAtGivenTimestamp(
                $artifact_id,
                $selected_day->getTimestamp(),
                $only_done_artifacts
            );

            if ($manual_value['value'] !== null) {
                $manual_sum += $manual_value['value'];
            } else {
                $computed_artifacts[] = $artifact_id;
            }
        }

        if (count($computed_artifacts) > 0) {
            return array(
                'children' => $this->burnup_dao->getBurnupComputedValueAtGivenTimestamp(
                    $computed_artifacts,
                    $selected_day->getTimestamp(),
                    $only_done_artifacts
                ),
                'manual_sum' => $manual_sum
            );
        } else {
            return array(
                'children'   => false,
                'manual_sum' => $manual_sum
            );
        }
    }
}
