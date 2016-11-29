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

class BurndownCalculator
{
    /**
     * @var ComputedFieldCalculator
     */
    private $computed_calculator;

    public function __construct(
        ComputedFieldCalculator $computed_calculator
    ) {
        $this->computed_calculator = $computed_calculator;
    }

    public function calculateBurndownValueAtTimestamp(array $burndown_infos, $timestamp)
    {
        return $this->computed_calculator->calculateForBurndown(
            array($burndown_infos['id']),
            $timestamp,
            true,
            'remaining_effort',
            $burndown_infos['remaining_effort_field_id']
        );
    }
}
