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

use AgileDashboard_BacklogItemDao;
use AgileDashboard_Milestone_Backlog_BacklogFactory;
use AgileDashboard_Milestone_Backlog_BacklogItemBuilder;
use AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory;
use HTTPRequest;
use Planning;
use Planning_MilestoneFactory;
use PlanningFactory;
use PlanningPermissionsManager;
use Tracker_Artifact_PriorityDao;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use TrackerFactory;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\AgileDashboard\FormElement\Burnup\ProjectsCountModeDao;
use Tuleap\AgileDashboard\Planning\PlanningDao;
use Tuleap\AgileDashboard\RemainingEffortValueRetriever;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBrokenConfigurationException;
use Project;
use Tuleap\Project\ProjectAccessChecker;
use Planning_VirtualTopMilestone;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Project_AccessPrivateException;
use Tuleap\Project\ProjectAccessSuspendedException;
use Project_AccessRestrictedException;
use Project_AccessProjectNotFoundException;
use Project_AccessDeletedException;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use AgileDashboardPlugin;

class ProjectMilestonesPresenterBuilder
{
    private const COUNT_ELEMENTS_MODE = "count";
    private const EFFORT_MODE         = "effort";

    /**
     * @var HTTPRequest
     */
    private $request;
    /**
     * @var AgileDashboard_Milestone_Backlog_BacklogFactory
     */
    private $agile_dashboard_milestone_backlog_backlog_factory;
    /**
     * @var AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory
     */
    private $agile_dashboard_milestone_backlog_backlog_item_collection_factory;
    /**
     * @var Planning_MilestoneFactory
     */
    private $planning_milestone_factory;
    /**
     * @var \Planning_VirtualTopMilestone
     */
    private $planning_virtual_top_milestone;
    /**
     * @var \PFUser
     */
    private $current_user;
    /**
     * @var ExplicitBacklogDao
     */
    private $explicit_backlog_dao;
    /**
     * @var ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;
    /**
     * @var Planning
     */
    private $root_planning;
    /**
     * @var SemanticTimeframe
     */
    private $semantic_timeframe;
    /**
     * @var CountElementsModeChecker
     */
    private $count_elements_mode_checker;
    /**
     * @var Project
     */
    private $project;
    /**
     * @var ProjectAccessChecker
     */
    private $project_access_checker;
    /**
     * @var SemanticTimeframeBuilder
     */
    private $semantic_timeframe_builder;

    public function __construct(
        HTTPRequest $request,
        AgileDashboard_Milestone_Backlog_BacklogFactory $agile_dashboard_milestone_backlog_backlog_factory,
        AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory $agile_dashboard_milestone_backlog_backlog_item_collection_factory,
        Planning_MilestoneFactory $planning_milestone_factory,
        ExplicitBacklogDao $explicit_backlog_dao,
        ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao,
        SemanticTimeframeBuilder $semantic_timeframe_builder,
        CountElementsModeChecker $count_elements_mode_checker,
        ProjectAccessChecker $project_access_checker,
    ) {
        $this->request                                                           = $request;
        $this->agile_dashboard_milestone_backlog_backlog_factory                 = $agile_dashboard_milestone_backlog_backlog_factory;
        $this->agile_dashboard_milestone_backlog_backlog_item_collection_factory = $agile_dashboard_milestone_backlog_backlog_item_collection_factory;
        $this->planning_milestone_factory                                        = $planning_milestone_factory;
        $this->current_user                                                      = $this->request->getCurrentUser();
        $this->explicit_backlog_dao                                              = $explicit_backlog_dao;
        $this->artifacts_in_explicit_backlog_dao                                 = $artifacts_in_explicit_backlog_dao;
        $this->semantic_timeframe_builder                                        = $semantic_timeframe_builder;
        $this->count_elements_mode_checker                                       = $count_elements_mode_checker;
        $this->project_access_checker                                            = $project_access_checker;
    }

    public static function build(): ProjectMilestonesPresenterBuilder
    {
        $artifact_factory           = Tracker_ArtifactFactory::instance();
        $semantic_timeframe_builder = SemanticTimeframeBuilder::build();

        $planning_factory = new PlanningFactory(
            new PlanningDao(),
            TrackerFactory::instance(),
            new PlanningPermissionsManager()
        );


        $milestone_factory = Planning_MilestoneFactory::build();

        return new self(
            HTTPRequest::instance(),
            new AgileDashboard_Milestone_Backlog_BacklogFactory(
                new AgileDashboard_BacklogItemDao(),
                $artifact_factory,
                $planning_factory,
            ),
            new AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory(
                new AgileDashboard_BacklogItemDao(),
                $artifact_factory,
                $milestone_factory,
                $planning_factory,
                new AgileDashboard_Milestone_Backlog_BacklogItemBuilder(),
                new RemainingEffortValueRetriever(Tracker_FormElementFactory::instance()),
                new ArtifactsInExplicitBacklogDao(),
                new Tracker_Artifact_PriorityDao()
            ),
            $milestone_factory,
            new ExplicitBacklogDao(),
            new ArtifactsInExplicitBacklogDao(),
            $semantic_timeframe_builder,
            new CountElementsModeChecker(new ProjectsCountModeDao()),
            new ProjectAccessChecker(
                new RestrictedUserCanAccessProjectVerifier(),
                \EventManager::instance()
            )
        );
    }

    /**
     * @throws ProjectMilestonesException
     * @throws TimeframeBrokenConfigurationException
     */
    public function getProjectMilestonePresenter(?Project $project, ?Planning $root_planning): ProjectMilestonesPresenter
    {
        if (! $project) {
            throw ProjectMilestonesException::buildProjectDontExist();
        }

        try {
            $this->project_access_checker->checkUserCanAccessProject($this->request->getCurrentUser(), $project);
        } catch (Project_AccessPrivateException $e) {
            throw ProjectMilestonesException::buildUserNotAccessToPrivateProject();
        } catch (Project_AccessDeletedException | Project_AccessProjectNotFoundException | Project_AccessRestrictedException | ProjectAccessSuspendedException $e) {
            throw ProjectMilestonesException::buildUserNotAccessToProject();
        }

        if (! $project->usesService(AgileDashboardPlugin::PLUGIN_SHORTNAME)) {
            throw ProjectMilestonesException::buildNoAgileDashboardPlugin();
        }

        if (! $root_planning) {
            throw ProjectMilestonesException::buildRootPlanningDontExist();
        }

        $this->project       = $project;
        $this->root_planning = $root_planning;

        return new ProjectMilestonesPresenter(
            $project,
            $this->getNumberUpcomingReleases(),
            $this->getNumberBacklogItems(),
            $this->getTrackersIdAgileDashboard(),
            $this->getLabelTrackerPlanning(),
            $this->isTimeframeDurationField(),
            $this->getLabelStartDateField(),
            $this->getLabelTimeframeField(),
            $this->userCanViewSubMilestonesPlanning(),
            $this->getBurnupMode()
        );
    }

    private function getNumberUpcomingReleases(): int
    {
        $futures_milestones = $this->planning_milestone_factory->getAllFutureMilestones($this->current_user, $this->root_planning);

        return count($futures_milestones);
    }

    private function getNumberBacklogItems(): int
    {
        $project_id = (int) $this->project->getID();

        if ($this->explicit_backlog_dao->isProjectUsingExplicitBacklog($project_id)) {
            return $this->artifacts_in_explicit_backlog_dao->getNumberOfItemsInExplicitBacklog($project_id);
        }

        $backlog = $this->agile_dashboard_milestone_backlog_backlog_item_collection_factory
            ->getUnassignedOpenCollection(
                $this->current_user,
                $this->getVirturalTopMilestone(),
                $this->agile_dashboard_milestone_backlog_backlog_factory->getSelfBacklog($this->getVirturalTopMilestone()),
                false
            );

        return $backlog->count();
    }

    private function getTrackersIdAgileDashboard(): array
    {
        $trackers_agile_dashboard = [];
        $backlog_milestones       = $this->agile_dashboard_milestone_backlog_backlog_factory->getBacklog($this->getVirturalTopMilestone());
        $trackers_backlogs        = $backlog_milestones->getDescendantTrackers();

        foreach ($trackers_backlogs as $tracker_backlog) {
            $tracker_agile_dashboard = [
                'id' => (int) $tracker_backlog->getId(),
                'color_name' => $tracker_backlog->getColor()->getName(),
                'label' => $tracker_backlog->getName(),
            ];

            $trackers_agile_dashboard[] = $tracker_agile_dashboard;
        }

        return $trackers_agile_dashboard;
    }

    private function getLabelTrackerPlanning(): string
    {
        return $this->root_planning->getPlanningTracker()->getName();
    }

    private function userCanViewSubMilestonesPlanning(): bool
    {
        if (count($this->root_planning->getPlanningTracker()->getChildren()) === 0) {
            return false;
        }

        return $this->root_planning->getPlanningTracker()->getChildren()[0]->userCanView();
    }

    private function isTimeframeDurationField(): bool
    {
        return $this->getTimeframeSemantic()->getDurationField() !== null;
    }

    /**
     * @throws TimeframeBrokenConfigurationException
     */
    private function getLabelTimeframeField(): string
    {
        $duration_field = $this->getTimeframeSemantic()->getDurationField();
        $end_date_field = $this->getTimeframeSemantic()->getEndDateField();

        if ($duration_field) {
            return $duration_field->getLabel();
        }
        if ($end_date_field) {
            return $end_date_field->getLabel();
        }

        throw new TimeframeBrokenConfigurationException($this->getTimeframeSemantic()->getTracker());
    }

    private function getLabelStartDateField(): string
    {
        $start_date_field = $this->getTimeframeSemantic()->getStartDateField();

        if ($start_date_field) {
            return $start_date_field->getLabel();
        }

        return 'start date';
    }

    private function getBurnupMode(): string
    {
        if ($this->count_elements_mode_checker->burnupMustUseCountElementsMode($this->project)) {
            return self::COUNT_ELEMENTS_MODE;
        }

        return self::EFFORT_MODE;
    }

    private function getVirturalTopMilestone(): Planning_VirtualTopMilestone
    {
        if (! isset($this->planning_virtual_top_milestone)) {
            $this->planning_virtual_top_milestone = $this->planning_milestone_factory->getVirtualTopMilestone($this->current_user, $this->project);
        }

        return $this->planning_virtual_top_milestone;
    }

    private function getTimeframeSemantic(): SemanticTimeframe
    {
        if (! isset($this->semantic_timeframe)) {
            $this->semantic_timeframe = $this->semantic_timeframe_builder->getSemantic($this->root_planning->getPlanningTracker());
        }
        return $this->semantic_timeframe;
    }
}
