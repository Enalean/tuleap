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

use Tuleap\AgileDashboard\FormElement\Burnup;
use Tuleap\AgileDashboard\Planning\RootPlanning\RootPlanningEditionEvent;
use Tuleap\AgileDashboard\Planning\ScrumPlanningFilter;

class PlanningEditionPresenterBuilder
{
    /**
     * @var \PlanningFactory
     */
    private $planning_factory;
    /**
     * @var \EventManager
     */
    private $event_manager;
    /**
     * @var ScrumPlanningFilter
     */
    private $scrum_planning_filter;
    /**
     * @var \PlanningPermissionsManager
     */
    private $planning_permissions_manager;
    /**
     * @var \Tracker_FormElementFactory
     */
    private $tracker_form_element_factory;

    public function __construct(
        \PlanningFactory $planning_factory,
        \EventManager $event_manager,
        ScrumPlanningFilter $scrum_planning_filter,
        \PlanningPermissionsManager $planning_permissions_manager,
        \Tracker_FormElementFactory $tracker_form_element_factory
    ) {
        $this->planning_factory             = $planning_factory;
        $this->event_manager                = $event_manager;
        $this->scrum_planning_filter        = $scrum_planning_filter;
        $this->planning_permissions_manager = $planning_permissions_manager;
        $this->tracker_form_element_factory = $tracker_form_element_factory;
    }

    public function build(\Planning $planning, \PFUser $user, \Project $project): PlanningEditionPresenter
    {
        $project_id = $planning->getGroupId();

        $cardwall_admin             = $this->getCardwallConfiguration($planning);
        $planning_trackers_filtered = $this->scrum_planning_filter->getPlanningTrackersFiltered(
            $planning,
            $user,
            $project_id
        );
        $available_trackers         = $this->planning_factory->getAvailableBacklogTrackers($user, $project_id);
        $backlog_trackers_filtered  = $this->scrum_planning_filter->getBacklogTrackersFiltered(
            $available_trackers,
            $planning
        );

        $planning_priority_change_permission = $this->planning_permissions_manager->getPlanningPermissionForm(
            $planning->getId(),
            $project_id,
            \PlanningPermissionsManager::PERM_PRIORITY_CHANGE,
            'planning[' . \PlanningPermissionsManager::PERM_PRIORITY_CHANGE . ']'
        );

        $milestone_tracker_modification_ban = null;
        if ($this->isRootPlanning($planning, $user)) {
            $event = new RootPlanningEditionEvent($project, $planning);
            $this->event_manager->dispatch($event);
            $ban = $event->getMilestoneTrackerModificationBan();
            if ($ban) {
                $milestone_tracker_modification_ban = new ModificationBanPresenter($ban);
            }
        }
        return new PlanningEditionPresenter(
            $planning,
            $planning_priority_change_permission,
            $backlog_trackers_filtered,
            $planning_trackers_filtered,
            $cardwall_admin,
            $this->getWarnings($planning),
            $milestone_tracker_modification_ban
        );
    }

    private function getCardwallConfiguration(\Planning $planning): string
    {
        $tracker = $planning->getPlanningTracker();
        $view    = null;

        $this->event_manager->processEvent(
            AGILEDASHBOARD_EVENT_PLANNING_CONFIG,
            [
                'tracker' => $tracker,
                'view'    => &$view,
            ]
        );

        return $view;
    }

    /**
     * @return PlanningWarningPossibleMisconfigurationPresenter[]
     */
    private function getWarnings(\Planning $planning): array
    {
        $milestone_tracker = $planning->getPlanningTracker();
        $warning_list      = [];
        $burnup_warning    = $this->getBurnupWarning($milestone_tracker);
        if ($burnup_warning) {
            $warning_list[] = $burnup_warning;
        }
        return array_values(array_merge($warning_list, $this->getOtherWarnings($milestone_tracker)));
    }

    private function getBurnupWarning(\Tracker $planning_tracker): ?PlanningWarningPossibleMisconfigurationPresenter
    {
        $burnup_fields = $this->tracker_form_element_factory->getFormElementsByType($planning_tracker, Burnup::TYPE);

        if ($burnup_fields && $burnup_fields[0]->isUsed()) {
            $semantic_url = '/plugins/tracker?' . http_build_query(
                [
                    "tracker" => $planning_tracker->getId(),
                    "func"    => "admin-formElements"
                ]
            );

            $semantic_name = dgettext('tuleap-agiledashboard', 'Burnup field');
            return new PlanningWarningPossibleMisconfigurationPresenter($semantic_url, $semantic_name);
        }

        return null;
    }

    /**
     * @return PlanningWarningPossibleMisconfigurationPresenter[]
     */
    private function getOtherWarnings(\Tracker $planning_tracker): array
    {
        $event = new AdditionalPlanningConfigurationWarningsRetriever($planning_tracker);
        $this->event_manager->processEvent($event);
        return $event->getAllWarnings();
    }

    private function isRootPlanning(\Planning $planning, \PFUser $user): bool
    {
        $root_planning = $this->planning_factory->getRootPlanning($user, (int) $planning->getGroupId());

        if (! $root_planning) {
            return false;
        }

        return (int) $planning->getId() === (int) $root_planning->getId();
    }
}
