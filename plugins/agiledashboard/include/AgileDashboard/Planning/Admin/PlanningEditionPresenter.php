<?php
/*
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

namespace Tuleap\AgileDashboard\Planning\Admin;

/**
 * @psalm-immutable
 */
final class PlanningEditionPresenter
{
    /**
     * @var string
     */
    public $planning_name;
    /**
     * @var int
     */
    public $project_id;
    /**
     * @var int
     */
    public $planning_id;
    /**
     * @var string
     */
    public $planning_plan_title;
    /**
     * @var string
     */
    public $planning_backlog_title;
    /**
     * @var string
     */
    public $priority_change_permission;
    /**
     * @var PlanningWarningPossibleMisconfigurationPresenter[]
     */
    public $warning_list;
    /**
     * @var bool
     */
    public $has_warning;
    /**
     * @var \Planning_TrackerPresenter[]
     */
    public $available_backlog_trackers;
    /**
     * @var \Planning_TrackerPresenter[]
     */
    public $available_planning_trackers;
    /**
     * @var string
     */
    public $cardwall_admin;
    /**
     * @var ?ModificationBanPresenter
     */
    public $milestone_tracker_modification_ban;

    /**
     * @var \Planning_TrackerPresenter[]                       $available_backlog_trackers
     * @var \Planning_TrackerPresenter[]                       $available_planning_trackers
     * @var PlanningWarningPossibleMisconfigurationPresenter[] $warning_list
     */
    public function __construct(
        \Planning $planning,
        string $priority_change_permission_html,
        array $available_backlog_trackers,
        array $available_planning_trackers,
        string $cardwall_admin,
        array $warning_list,
        ?ModificationBanPresenter $milestone_tracker_modification_ban
    ) {
        $planning_id                              = $planning->getId();
        $project_id                               = $planning->getGroupId();
        $this->planning_name                      = $planning->getName();
        $this->project_id                         = $project_id;
        $this->planning_id                        = $planning_id;
        $this->planning_plan_title                = $planning->getPlanTitle();
        $this->planning_backlog_title             = $planning->getBacklogTitle();
        $this->priority_change_permission         = $priority_change_permission_html;
        $this->available_backlog_trackers         = $available_backlog_trackers;
        $this->available_planning_trackers        = $available_planning_trackers;
        $this->cardwall_admin                     = $cardwall_admin;
        $this->warning_list                       = $warning_list;
        $this->has_warning                        = count($warning_list) > 0;
        $this->milestone_tracker_modification_ban = $milestone_tracker_modification_ban;
    }
}
