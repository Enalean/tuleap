<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Scrum;

use AgileDashboard_ConfigurationManager;
use EventManager;
use PFUser;
use Planning;
use Planning_PlanningAdminPresenter;
use Planning_PlanningOutOfHierarchyAdminPresenter;
use PlanningFactory;
use Project;
use Tuleap\AgileDashboard\AdminScrumPresenter;
use Tuleap\AgileDashboard\Event\GetAdditionalScrumAdminPaneContent;
use Tuleap\AgileDashboard\Event\GetAdditionalScrumAdminSection;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\Milestone\Sidebar\CheckMilestonesInSidebar;
use Tuleap\AgileDashboard\Planning\PlanningAdministrationDelegation;
use Tuleap\AgileDashboard\Workflow\AddToTopBacklogPostActionDao;

class ScrumPresenterBuilder
{
    /**
     * @var PlanningFactory
     */
    private $planning_factory;
    /**
     * @var AgileDashboard_ConfigurationManager
     */
    private $config_manager;
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    /**
     * @var AddToTopBacklogPostActionDao
     */
    private $add_to_top_backlog_post_action_dao;

    public function __construct(
        AgileDashboard_ConfigurationManager $config_manager,
        EventManager $event_manager,
        PlanningFactory $planning_factory,
        ExplicitBacklogDao $explicit_backlog_dao,
        AddToTopBacklogPostActionDao $add_to_top_backlog_post_action_dao,
        private readonly CheckMilestonesInSidebar $milestones_in_sidebar_config,
    ) {
        $this->config_manager                     = $config_manager;
        $this->event_manager                      = $event_manager;
        $this->planning_factory                   = $planning_factory;
        $this->explicit_backlog_dao               = $explicit_backlog_dao;
        $this->add_to_top_backlog_post_action_dao = $add_to_top_backlog_post_action_dao;
    }

    public function getAdminScrumPresenter(PFUser $user, Project $project, GetAdditionalScrumAdminSection $additional_scrum_sections)
    {
        $group_id                    = (int) $project->getID();
        $can_create_planning         = true;
        $root_planning_name          = '';
        $potential_planning_trackers = [];
        $root_planning               = $this->planning_factory->getRootPlanning($user, $group_id) ?: null;
        $scrum_activated             = $this->config_manager->scrumIsActivatedForProject($project);

        if ($root_planning) {
            $can_create_planning = count($this->planning_factory->getAvailablePlanningTrackers($user, $group_id)) > 0;

            $root_planning_name          = $root_planning->getName();
            $potential_planning_trackers = $this->planning_factory->getPotentialPlanningTrackers($user, $group_id);
        }

        $has_workflow_action_add_to_top_backlog_defined = $this->add_to_top_backlog_post_action_dao
            ->isAtLeastOnePostActionDefinedInProject($group_id);

        $planning_administration_delegation = new PlanningAdministrationDelegation($project);
        $this->event_manager->dispatch($planning_administration_delegation);

        $should_sidebar_display_last_milestones = $this->milestones_in_sidebar_config->shouldSidebarDisplayLastMilestones((int) $project->getID());

        return new AdminScrumPresenter(
            $this->getPlanningAdminPresenterList($user, $project, $root_planning),
            $group_id,
            $can_create_planning,
            $root_planning_name,
            $potential_planning_trackers,
            $scrum_activated,
            $can_create_planning,
            $this->getAdditionalContent(),
            $this->doesProjectUseExplicitBacklog($project),
            $has_workflow_action_add_to_top_backlog_defined,
            $additional_scrum_sections->getAdditionalSectionsControllers(),
            $planning_administration_delegation->isPlanningAdministrationDelegated(),
            $should_sidebar_display_last_milestones,
        );
    }

    private function getAdditionalContent(): string
    {
        $event = new GetAdditionalScrumAdminPaneContent();
        $this->event_manager->processEvent($event);

        return $event->getAdditionalContent();
    }

    /**
     * @return Planning_PlanningOutOfHierarchyAdminPresenter[] | Planning_PlanningAdminPresenter[]
     */
    private function getPlanningAdminPresenterList(PFUser $user, Project $project, ?Planning $root_planning): array
    {
        $plannings                 = [];
        $planning_out_of_hierarchy = [];
        $project_id                = (int) $project->getID();
        foreach ($this->planning_factory->getPlanningsOutOfRootPlanningHierarchy($user, $project_id) as $planning) {
            $planning_out_of_hierarchy[$planning->getId()] = true;
        }

        $use_explicit_backlog = $this->doesProjectUseExplicitBacklog($project);

        foreach ($this->planning_factory->getPlannings($user, $project_id) as $planning) {
            $is_planning_removal_dangerous = $root_planning && $use_explicit_backlog && $planning->getId() === $root_planning->getId();

            if (isset($planning_out_of_hierarchy[$planning->getId()])) {
                $plannings[] = new Planning_PlanningOutOfHierarchyAdminPresenter(
                    $planning,
                    $root_planning,
                    $is_planning_removal_dangerous
                );
            } else {
                $plannings[] = new Planning_PlanningAdminPresenter(
                    $planning,
                    $is_planning_removal_dangerous
                );
            }
        }

        return $plannings;
    }

    private function doesProjectUseExplicitBacklog(Project $project): bool
    {
        return $this->explicit_backlog_dao->isProjectUsingExplicitBacklog((int) $project->getID());
    }
}
