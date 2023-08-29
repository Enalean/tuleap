<?php
/*
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\Planning\Admin\PlanningWarningPossibleMisconfigurationPresenter;

class Planning_FormPresenter extends PlanningPresenter
{
    /**
     * @var PlanningPermissionsManager
     */
    private $planning_permissions_manager;

    /**
     * @var int
     */
    public $planning_id;

    /**
     * @var int
     */
    public $group_id;

    /**
     * @var Planning_TrackerPresenter[]
     */
    public $available_backlog_trackers;

    /**
     * @var Planning_TrackerPresenter[]
     */
    public $available_planning_trackers;

    /**
     * @var string HTML string that allows for the cardwall configuration on a planning
     */
    public $cardwall_admin;

    /**
     * @var String
     */
    public $planning_allows_assignment_intro;

    /**
     * @var String
     */
    public $planning_allows_assignment_to;
    /**
     * @var bool
     */
    public $has_warning;
    /**
     * @var PlanningWarningPossibleMisconfigurationPresenter[]
     */
    public $warning_list;

    public function __construct(
        PlanningPermissionsManager $planning_permissions_manager,
        Planning $planning,
        array $available_backlog_trackers,
        array $available_planning_trackers,
        $cardwall_admin,
        array $warning_list,
        private readonly Project $project,
        private readonly \Tuleap\Kanban\SplitKanbanConfigurationChecker $split_kanban_configuration_checker,
    ) {
        parent::__construct($planning);

        $this->planning_permissions_manager = $planning_permissions_manager;
        $this->planning_id                  = $planning->getId();
        $this->group_id                     = $planning->getGroupId();
        $this->available_backlog_trackers   = $available_backlog_trackers;
        $this->available_planning_trackers  = $available_planning_trackers;
        $this->cardwall_admin               = $cardwall_admin;

        $this->planning_allows_assignment_intro = dgettext('tuleap-agiledashboard', 'This planning allows assignment of');

        $this->planning_allows_assignment_to = dgettext('tuleap-agiledashboard', 'to');

        $this->warning_list = $warning_list;
        $this->has_warning  = count($warning_list) > 0;
    }

    public function adminTitle()
    {
        if ($this->split_kanban_configuration_checker->isProjectAllowedToUseSplitKanban($this->project)) {
            return dgettext('tuleap-agiledashboard', 'Backlog administration');
        }

        return dgettext('tuleap-agiledashboard', 'Agile Dashboard Administration');
    }

    public function createPlanning()
    {
        return dgettext('tuleap-agiledashboard', 'Create a new planning');
    }

    public function planningNameFieldLabel()
    {
        return dgettext('tuleap-agiledashboard', 'Name');
    }

    public function planningBacklogTitleFieldLabel()
    {
        return dgettext('tuleap-agiledashboard', 'Backlog title');
    }

    public function planningPlanTitleFieldLabel()
    {
        return dgettext('tuleap-agiledashboard', 'Plan title');
    }

    public function planningPriorityChangePermissionFieldLabel()
    {
        return dgettext('tuleap-agiledashboard', 'Who can prioritize items?');
    }

    public function priority_change_permission()
    {
        return $this->planning_permissions_manager->getPlanningPermissionForm($this->planning_id, $this->group_id, PlanningPermissionsManager::PERM_PRIORITY_CHANGE, 'planning[' . PlanningPermissionsManager::PERM_PRIORITY_CHANGE . ']');
    }

    public function btnSubmit()
    {
        return $GLOBALS['Language']->getText('global', 'btn_submit');
    }
}
