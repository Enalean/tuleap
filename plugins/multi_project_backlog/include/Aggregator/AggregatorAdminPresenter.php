<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Aggregator;

use ForgeConfig;
use Project;
use Tuleap\MultiProjectBacklog\Aggregator\PlannableItems\Presenter\PlannableItemsPerContributorPresenterCollection;

/**
 * @psalm-immutable
 */
class AggregatorAdminPresenter
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
     * @var PlannableItems\Presenter\PlannableItemsPerContributorPresenter[]
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
        Project $project,
        PlannableItemsPerContributorPresenterCollection $collection,
        string $planning_name
    ) {
        $this->project_id               = (int) $project->getID();
        $this->project_name             = (string) $project->getPublicName();
        $this->plannable_items          = $collection->getPlannableItemsPerContributorPresenters();
        $this->can_burnup_be_configured = (bool) ForgeConfig::get('use_burnup_count_elements');
        $this->planning_name            = $planning_name;
    }
}
