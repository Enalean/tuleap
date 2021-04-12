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
        $converted_steps = [];

        foreach ($steps as $step) {
            StepChecker::checkStepDataFromRESTPost($step);
            $converted_steps["description"][]             = $step["description"];
            $converted_steps["description_format"][]      = $step["description_format"];
            $converted_steps["expected_results"][]        = $step["expected_results"];
            $converted_steps["expected_results_format"][] = $step["expected_results_format"];
        }

        return $converted_steps;
    }
}
