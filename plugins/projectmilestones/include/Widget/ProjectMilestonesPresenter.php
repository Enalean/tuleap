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

namespace Tuleap\ProjectMilestones\Widget;

use Project;

class ProjectMilestonesPresenter
{
    /**
     * @var int
     */
    public $project_id;
    /**
     * @var bool
     */
    public $is_IE11;
    /**
     * @var int
     */
    public $nb_upcoming_releases;
    /**
     * @var int
     */
    public $nb_backlog_items;
    /**
     * @var string
     */
    public $json_trackers_agile_dashboard;
    /**
     * @var string
     */
    public $label_tracker_planning;
    /**
     * @var bool
     */
    public $is_timeframe_duration;
    /**
     * @var string
     */
    public $label_start_date;
    /**
     * @var string
     */
    public $label_timeframe;
    /**
     * @var bool
     */
    public $user_can_view_sub_milestones_planning;
    /**
     * @var string
     */
    public $burnup_mode;
    /**
     * @var bool
     */
    public $project_milestone_activate_ttm;

    public function __construct(
        Project $project,
        int $nb_upcoming_releases,
        int $nb_backlog_items,
        array $trackers_id,
        string $label_tracker_planning,
        bool $is_timeframe_duration,
        string $label_start_date,
        string $label_timeframe,
        bool $user_can_view_sub_milestones_planning,
        string $burnup_mode,
        bool $activate_ttm
    ) {
        $this->project_id                            = $project->getID();
        $this->nb_upcoming_releases                  = $nb_upcoming_releases;
        $this->nb_backlog_items                      = $nb_backlog_items;
        $this->json_trackers_agile_dashboard         = (string) json_encode($trackers_id, JSON_THROW_ON_ERROR);
        $this->label_tracker_planning                = $label_tracker_planning;
        $this->is_timeframe_duration                 = $is_timeframe_duration;
        $this->label_start_date                      = $label_start_date;
        $this->label_timeframe                       = $label_timeframe;
        $this->user_can_view_sub_milestones_planning = $user_can_view_sub_milestones_planning;
        $this->burnup_mode                           = $burnup_mode;
        $this->project_milestone_activate_ttm        = $activate_ttm;
    }
}
