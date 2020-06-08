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
     *
     * @psalm-readonly
     */
    public $milestone_presenter;
    /**
     * @var int
     *
     * @psalm-readonly
     */
    public $milestone_id;
    /**
     * @var int
     *
     * @psalm-readonly
     */
    public $project_id;

    /**
     * @var bool
     *
     * @psalm-readonly
     */
    public $user_can_create_campaign;

    public function __construct(
        AgileDashboard_MilestonePresenter $milestone_presenter,
        int $milestone_id,
        int $project_id,
        bool $user_can_create_campaign
    ) {
        $this->milestone_presenter      = $milestone_presenter;
        $this->milestone_id             = $milestone_id;
        $this->project_id               = $project_id;
        $this->user_can_create_campaign = $user_can_create_campaign;
    }
}
