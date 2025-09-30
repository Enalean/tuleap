<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

class Cardwall_RemainingEffortProgressPresenter implements Cardwall_EffortProgressPresenter //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    public const string COUNT_STYLE = 'effort';

    private $initial_effort;
    private $capacity;
    private $remaining_effort;

    public function __construct($initial_effort, $capacity, $remaining_effort)
    {
        $this->initial_effort   = $initial_effort;
        $this->capacity         = $capacity;
        $this->remaining_effort = $remaining_effort;
    }

    #[\Override]
    public function milestone_capacity() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return sprintf(dgettext('tuleap-cardwall', 'Capacity: %1$s'), floatval($this->capacity));
    }

    public function milestone_progress_info() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return '';
    }

    #[\Override]
    public function milestone_initial_effort_value() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return sprintf(dgettext('tuleap-cardwall', 'Initial effort: %1$s'), $this->initial_effort);
    }

    #[\Override]
    public function milestone_initial_effort() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->initial_effort;
    }

    #[\Override]
    public function milestone_has_initial_effort() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->initial_effort != 0;
    }

    #[\Override]
    public function milestone_points_to_go() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->milestone_remaining_effort() <= 1) {
            return dgettext('tuleap-cardwall', 'point remaining');
        }

        return dgettext('tuleap-cardwall', 'points remaining');
    }

    #[\Override]
    public function milestone_remaining_effort() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->remaining_effort > 0) {
            return $this->remaining_effort;
        }

        return 0;
    }

    #[\Override]
    public function initial_effort_completion() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->cannotBeDivided($this->initial_effort)) {
            return 100;
        }

        $completion = ceil(
            ( $this->initial_effort - $this->remaining_effort ) / $this->initial_effort * 100
        );

        return $this->returnRelevantProgressBarValue($completion);
    }

    private function returnRelevantProgressBarValue($value)
    {
        if ($value < 0) {
            return 0;
        }

        return $value;
    }

    private function cannotBeDivided($number)
    {
        return $number === 0;
    }

    #[\Override]
    public function milestone_count_style() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return self::COUNT_STYLE;
    }

    #[\Override]
    public function count_style_helper() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return '';
    }
}
