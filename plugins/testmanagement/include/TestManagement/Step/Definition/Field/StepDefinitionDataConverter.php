<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\TestManagement\Step\Definition\Field;

use Luracast\Restler\RestException;
use Tuleap\TestManagement\Step\StepChecker;

final class StepDefinitionDataConverter
{
    /**
     * @throws RestException
     */
    public static function convertStepDefinitionFromRESTPostFormatToDBCompatibleFormat(array $steps): array
    {
        if (count($steps) === 0) {
            return ['no_steps' => true];
        }
        $converted_steps = [];

        foreach ($steps as $step) {
            StepChecker::checkStepDataFromRESTPost($step);
            $converted_steps['description'][]             = $step['description'];
            $converted_steps['description_format'][]      = $step['description_format'];
            $converted_steps['expected_results'][]        = $step['expected_results'];
            $converted_steps['expected_results_format'][] = $step['expected_results_format'];
        }

        return $converted_steps;
    }

    /**
     * @throws RestException
     */
    public static function convertStepDefinitionFromRESTUpdateFormatToDBCompatibleFormat(array $steps): array
    {
        $steps_by_rank = [];

        foreach ($steps as $step) {
            $rank = $step['rank'] ?? null;
            if ($rank === null) {
                throw new RestException(400, 'All step definitions must have a rank');
            }

            if (! is_int($rank)) {
                throw new RestException(400, sprintf('The step definition rank can only be an integer, got %s', gettype($rank)));
            }

            if (isset($steps_by_rank[$rank])) {
                throw new RestException(400, sprintf('The rank %d can not be used multiple times', $rank));
            }

            $steps_by_rank[$rank] = $step;
        }

        ksort($steps_by_rank);
        return self::convertStepDefinitionFromRESTPostFormatToDBCompatibleFormat($steps_by_rank);
    }
}
