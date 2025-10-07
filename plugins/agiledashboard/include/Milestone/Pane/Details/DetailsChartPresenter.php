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
    public bool $has_charts;
    public bool $has_escaped_charts;

    public function __construct(
        public bool $has_burndown,
        public ?string $burndown_label,
        public bool $has_burnup,
        public ?string $burnup_label,
        public ?BurndownFieldPresenter $burndown_presenter = null,
        public ?BurnupFieldPresenter $burnup_presenter = null,
        public ?array $escaped_charts = null,
    ) {
        $this->has_escaped_charts = $this->escaped_charts !== null && count($this->escaped_charts) > 0;
        $this->has_charts         = $this->has_burndown || $this->has_burnup || $this->has_escaped_charts;
    }
}
