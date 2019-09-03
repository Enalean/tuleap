<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Board;

use AgileDashboard_MilestonePresenter;
use PFUser;
use Planning_Milestone;
use Tuleap\Taskboard\Column\ColumnPresenter;

class BoardPresenter
{
    /**
     * @var AgileDashboard_MilestonePresenter
     */
    public $milestone_presenter;
    /**
     * @var bool
     */
    public $user_is_admin;
    /**
     * @var string
     */
    public $admin_url;
    /**
     * @var ColumnPresenter[]
     */
    public $columns;

    /**
     * @param AgileDashboard_MilestonePresenter $milestone_presenter
     * @param PFUser                            $user
     * @param Planning_Milestone                $milestone
     * @param ColumnPresenter[]                 $columns
     */
    public function __construct(
        AgileDashboard_MilestonePresenter $milestone_presenter,
        PFUser $user,
        Planning_Milestone $milestone,
        array $columns
    ) {
        $project = $milestone->getProject();

        $this->milestone_presenter = $milestone_presenter;
        $this->user_is_admin       = $user->isAdmin($project->getID());
        $this->admin_url           = AGILEDASHBOARD_BASE_URL . '/?'
            . http_build_query(
                [
                    'group_id'    => $project->getID(),
                    'planning_id' => $milestone->getPlanningId(),
                    'action'      => 'edit'
                ]
            );

        $this->columns = $columns;
    }
}
