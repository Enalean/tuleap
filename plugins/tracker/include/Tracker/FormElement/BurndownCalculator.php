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

use DateTime;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDao;

class BurndownCalculator implements IProvideArtifactChildrenForComputedCalculation
{
    /**
     * @var ComputedFieldDao
     */
    private $computed_dao;

    public function __construct(
        ComputedFieldDao $computed_dao
    ) {
        $this->computed_dao        = $computed_dao;
    }

    public function fetchChildrenAndManualValuesOfArtifacts(
        array $artifact_ids_to_fetch,
        $timestamp,
        bool $stop_on_manual_value,
        string $target_field_name,
        string $computed_field_id,
        ArtifactsAlreadyProcessedDuringComputationCollection $already_seen
    ): array {
        $enhanced_dar = $this->getChildrenForBurndownWithComputedValuesAtGivenDate(
            $artifact_ids_to_fetch,
            $timestamp,
            $already_seen
        );

        $manual_sum = $enhanced_dar['manual_sum'];
        $dar        = $enhanced_dar['computed_values'];

        return [
            'children'   => $dar,
            'manual_sum' => $manual_sum
        ];
    }

    private function getChildrenForBurndownWithComputedValuesAtGivenDate(
        array $artifact_ids_to_fetch,
        int $timestamp,
        ArtifactsAlreadyProcessedDuringComputationCollection $already_seen
    ): array {
        $computed_artifacts = [];
        $manual_sum         = null;
        $selected_day       = new DateTime();
        $selected_day->setTimestamp($timestamp);
        $selected_day->setTime(23, 59, 59);

        foreach ($artifact_ids_to_fetch as $artifact_id) {
            if ($already_seen->hasArtifactBeenProcessedDuringComputation($artifact_id)) {
                continue;
            }
            $manual_value = $this->computed_dao->getBurndownManualValueAtGivenTimestamp(
                $artifact_id,
                $selected_day->getTimestamp()
            );

            if ($manual_value && $manual_value['value'] !== null) {
                $manual_sum += $manual_value['value'];
                $already_seen->addArtifactAsAlreadyProcessed($artifact_id);
            } else {
                $computed_artifacts[] = $artifact_id;
            }
        }

        if (count($computed_artifacts) > 0) {
            return [
                'computed_values' => $this->computed_dao->getBurndownComputedValueAtGivenTimestamp(
                    $computed_artifacts,
                    $selected_day->getTimestamp()
                ),
                'manual_sum'      => $manual_sum
            ];
        }

        return [
            'computed_values' => false,
            'manual_sum'      => $manual_sum
        ];
    }
}
