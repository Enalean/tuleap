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
     * @var string
     */
    public $json_encoded_columns;
    /**
     * @var string
     */
    public $json_encoded_trackers;
    /**
     * @var bool
     */
    public $has_content;
    /**
     * @var bool
     */
    public $is_ie_11;
    /**
     * @var bool
     */
    public $are_closed_items_displayed;

    public function __construct(
        AgileDashboard_MilestonePresenter $milestone_presenter,
        PFUser $user,
        Planning_Milestone $milestone,
        array $columns,
        array $tracker_structures,
        bool $has_content,
        bool $is_ie_11
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

        $this->json_encoded_columns  = (string) json_encode($columns, JSON_THROW_ON_ERROR);
        $this->json_encoded_trackers = (string) json_encode($tracker_structures, JSON_THROW_ON_ERROR);
        $this->has_content           = $has_content;
        $this->is_ie_11              = $is_ie_11;

        $hide_preference_name             = 'plugin_taskboard_hide_closed_items_' . $milestone->getArtifactId();
        $this->are_closed_items_displayed = empty($user->getPreference($hide_preference_name));
    }
}
