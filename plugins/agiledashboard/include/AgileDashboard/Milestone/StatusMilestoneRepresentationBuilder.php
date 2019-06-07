<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone;

use AgileDashboard_Milestone_MilestoneRepresentationBuilder;
use AgileDashboard_Milestone_PaginatedMilestonesRepresentations;
use PFUser;
use Project;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\ISearchOnStatus;

class StatusMilestoneRepresentationBuilder implements MilestoneRepresentationBuilderInterface
{
    /**
     * @var AgileDashboard_Milestone_MilestoneRepresentationBuilder
     */
    private $builder;
    /**
     * @var ISearchOnStatus
     */
    private $status;

    public function __construct(
        AgileDashboard_Milestone_MilestoneRepresentationBuilder $builder,
        ISearchOnStatus $status
    ) {
        $this->builder = $builder;
        $this->status  = $status;
    }

    public function getPaginatedTopMilestonesRepresentations(
        Project $project,
        PFUser $user,
        $representation_type,
        $limit,
        $offset,
        $order
    ): AgileDashboard_Milestone_PaginatedMilestonesRepresentations {
        return $this->builder->getPaginatedTopMilestonesRepresentations(
            $project,
            $user,
            $representation_type,
            $this->status,
            $limit,
            $offset,
            $order
        );
    }
}
