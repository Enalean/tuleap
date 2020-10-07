<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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

use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDao;

class ComputedFieldCalculator implements IProvideArtifactChildrenForComputedCalculation
{
    /**
     * @var ComputedFieldDao
     */
    private $dao;

    public function __construct(
        ComputedFieldDao $dao
    ) {
        $this->dao                 = $dao;
    }

    public function fetchChildrenAndManualValuesOfArtifacts(
        array $artifact_ids_to_fetch,
        $timestamp,
        bool $stop_on_manual_value,
        string $target_field_name,
        string $computed_field_id,
        ArtifactsAlreadyProcessedDuringComputationCollection $already_seen
    ): array {
        $dar = $this->dao->getComputedFieldValues(
            $artifact_ids_to_fetch,
            $target_field_name,
            $computed_field_id,
            $stop_on_manual_value
        );

        return [
            'children'   => $dar,
            'manual_sum' => null
        ];
    }
}
