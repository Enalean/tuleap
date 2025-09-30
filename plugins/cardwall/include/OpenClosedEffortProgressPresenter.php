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

class Cardwall_OpenClosedEffortProgressPresenter implements Cardwall_EffortProgressPresenter //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    public const string COUNT_STYLE = 'cards';

    private $nb_total;
    private $nb_open;
    private $nb_closed;

    public function __construct($nb_open, $nb_closed)
    {
        $this->nb_open   = $nb_open;
        $this->nb_closed = $nb_closed;
        $this->nb_total  = $this->nb_open + $this->nb_closed;
    }

    #[\Override]
    public function initial_effort_completion() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->cannotBeDivided($this->nb_total)) {
            return 100;
        }

        $completion = ceil(
            ( $this->nb_closed ) / $this->nb_total * 100
        );

        return $this->returnRelevantProgressBarValue($completion);
    }

    #[\Override]
    public function milestone_capacity() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return '';
    }

    #[\Override]
    public function milestone_has_initial_effort() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return true;
    }

    #[\Override]
    public function milestone_initial_effort() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->nb_total;
    }

    #[\Override]
    public function milestone_initial_effort_value() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return dgettext('tuleap-cardwall', 'Some cards might be hidden');
    }

    #[\Override]
    public function milestone_points_to_go() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return dgettext('tuleap-cardwall', 'cards open');
    }

    #[\Override]
    public function milestone_remaining_effort() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->nb_open . '/' . $this->nb_total;
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
        return dgettext('tuleap-cardwall', 'At least one backlog element has no initial effort. Fallback on open/closed count.');
    }
}
