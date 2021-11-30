<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Pane\Details;

use Tuleap\AgileDashboard\FormElement\BurnupFieldPresenter;
use Tuleap\Tracker\FormElement\BurndownFieldPresenter;

class DetailsChartPresenter
{
    public $has_burndown = false;
    public $burndown_label;
    public $burndown_presenter;

    public $has_burnup = false;
    public $burnup_label;
    public $burnup_presenter;
    public $has_charts = false;
    /**
     * @var array
     */
    public $escaped_charts;
    /**
     * @var bool
     */
    public $has_escaped_charts;

    public function __construct(
        $has_burndown,
        $burndown_label,
        $has_burnup,
        $burnup_label,
        ?BurndownFieldPresenter $burndown_presenter = null,
        ?BurnupFieldPresenter $burnup_presenter = null,
        ?array $escaped_charts = null,
    ) {
        $this->has_burndown       = $has_burndown;
        $this->burndown_label     = $burndown_label;
        $this->burndown_presenter = $burndown_presenter;
        $this->has_burnup         = $has_burnup;
        $this->burnup_label       = $burnup_label;
        $this->burnup_presenter   = $burnup_presenter;

        $this->escaped_charts     = $escaped_charts;
        $this->has_escaped_charts = count($escaped_charts) > 0;

        $this->has_charts = $this->has_burndown || $this->has_burnup || $this->has_escaped_charts;
    }
}
