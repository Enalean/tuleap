<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Cardwall_OpenClosedEffortProgressPresenter implements Cardwall_EffortProgressPresenter
{
    public const COUNT_STYLE = 'cards';

    private $nb_total;
    private $nb_open;
    private $nb_closed;

    public function __construct($nb_open, $nb_closed)
    {
        $this->nb_open   = $nb_open;
        $this->nb_closed = $nb_closed;
        $this->nb_total  = $this->nb_open + $this->nb_closed;
    }

    public function initial_effort_completion()
    {
        if ($this->cannotBeDivided($this->nb_total)) {
            return 100;
        }

        $completion = ceil(
            ( $this->nb_closed ) / $this->nb_total * 100
        );

        return $this->returnRelevantProgressBarValue($completion);
    }

    public function milestone_capacity()
    {
        return '';
    }

    public function milestone_has_initial_effort()
    {
        return true;
    }

    public function milestone_initial_effort()
    {
        return $this->nb_total;
    }

    public function milestone_initial_effort_value()
    {
        return $GLOBALS['Language']->getText('plugin_cardwall', 'milestone_open_closed_progress_info');
    }

    public function milestone_points_to_go()
    {
        return $GLOBALS['Language']->getText('plugin_cardwall', 'milestone_open_closed_open_items');
    }

    public function milestone_remaining_effort()
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

    public function milestone_count_style()
    {
        return self::COUNT_STYLE;
    }

    public function count_style_helper()
    {
        return $GLOBALS['Language']->getText('plugin_cardwall', 'count_style_helper');
    }
}
