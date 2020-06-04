<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\TestPlan;

use AgileDashboard_MilestonePresenter;

class TestPlanPresenter
{
    /**
     * @var AgileDashboard_MilestonePresenter
     */
    public $milestone_presenter;
    /**
     * @var int
     */
    public $milestone_id;
    /**
     * @var int
     */
    public $project_id;

    public function __construct(
        AgileDashboard_MilestonePresenter $milestone_presenter,
        int $milestone_id,
        int $project_id
    ) {
        $this->milestone_presenter = $milestone_presenter;
        $this->milestone_id        = $milestone_id;
        $this->project_id          = $project_id;
    }
}
