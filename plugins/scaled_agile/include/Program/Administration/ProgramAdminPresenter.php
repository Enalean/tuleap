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

namespace Tuleap\ScaledAgile\Program\Administration;

use ForgeConfig;
use Tuleap\ScaledAgile\Program\Administration\PlannableItems\Presenter\PlannableItemsPerTeamPresenter;
use Tuleap\ScaledAgile\Program\Administration\PlannableItems\Presenter\PlannableItemsPerTeamPresenterCollection;
use Tuleap\ScaledAgile\ProjectData;

/**
 * @psalm-immutable
 */
class ProgramAdminPresenter
{
    /**
     * @var int
     */
    public $project_id;

    /**
     * @var string
     */
    public $project_name;

    /**
     * @var PlannableItemsPerTeamPresenter[]
     */
    public $plannable_items;

    /**
     * @var bool
     */
    public $can_burnup_be_configured;

    /**
     * @var string
     */
    public $planning_name;

    public function __construct(
        ProjectData $project,
        PlannableItemsPerTeamPresenterCollection $collection,
        string $planning_name
    ) {
        $this->project_id               = $project->getId();
        $this->project_name             = $project->getPublicName();
        $this->plannable_items          = $collection->getPlannableItemsPerTeamPresenters();
        $this->can_burnup_be_configured = (bool) ForgeConfig::get('use_burnup_count_elements');
        $this->planning_name            = $planning_name;
    }
}
