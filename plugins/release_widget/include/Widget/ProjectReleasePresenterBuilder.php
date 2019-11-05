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

namespace Tuleap\ReleaseWidget\Widget;

use AgileDashboard_BacklogItemDao;
use AgileDashboard_Milestone_Backlog_BacklogFactory;
use AgileDashboard_Milestone_Backlog_BacklogItemBuilder;
use AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory;
use AgileDashboard_Milestone_MilestoneDao;
use AgileDashboard_Milestone_MilestoneStatusCounter;
use HTTPRequest;
use Planning_MilestoneFactory;
use PlanningDao;
use PlanningFactory;
use PlanningPermissionsManager;
use Tracker_Artifact_PriorityDao;
use Tracker_ArtifactDao;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use TrackerFactory;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\MonoMilestone\MonoMilestoneBacklogItemDao;
use Tuleap\AgileDashboard\MonoMilestone\MonoMilestoneItemsFinder;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao;
use Tuleap\AgileDashboard\Planning\MilestoneBurndownFieldChecker;
use Tuleap\AgileDashboard\RemainingEffortValueRetriever;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBuilder;

class ProjectReleasePresenterBuilder
{
    /**
     * @var HTTPRequest
     */
    private $request;
    /**
     * @var PlanningFactory
     */
    private $planning_factory;
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
     * @var TrackerFactory
     */
    private $tracker_factory;

    public function __construct(
        HTTPRequest $request,
        PlanningFactory $planning_factory,
        AgileDashboard_Milestone_Backlog_BacklogFactory $agile_dashboard_milestone_backlog_backlog_factory,
        AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory $agile_dashboard_milestone_backlog_backlog_item_collection_factory,
        Planning_MilestoneFactory $planning_milestone_factory,
        TrackerFactory $tracker_factory
    ) {
        $this->request                                                           = $request;
        $this->planning_factory                                                  = $planning_factory;
        $this->agile_dashboard_milestone_backlog_backlog_factory                 = $agile_dashboard_milestone_backlog_backlog_factory;
        $this->agile_dashboard_milestone_backlog_backlog_item_collection_factory = $agile_dashboard_milestone_backlog_backlog_item_collection_factory;
        $this->planning_milestone_factory                                        = $planning_milestone_factory;
        $this->current_user                                                      = $this->request->getCurrentUser();
        $this->planning_virtual_top_milestone                                    = $this->planning_milestone_factory->getVirtualTopMilestone($this->current_user, $this->request->getProject());
        $this->tracker_factory                                                   = $tracker_factory;
    }

    public static function build(): ProjectReleasePresenterBuilder
    {
        $planning_factory = new PlanningFactory(
            new PlanningDao(),
            TrackerFactory::instance(),
            new PlanningPermissionsManager()
        );

        $scrum_mono_milestone_checker = new ScrumForMonoMilestoneChecker(
            new ScrumForMonoMilestoneDao(),
            $planning_factory
        );

        $mono_milestone_items_finder = new MonoMilestoneItemsFinder(
            new MonoMilestoneBacklogItemDao(),
            Tracker_ArtifactFactory::instance()
        );

        $milestone_factory = new Planning_MilestoneFactory(
            $planning_factory,
            Tracker_ArtifactFactory::instance(),
            Tracker_FormElementFactory::instance(),
            TrackerFactory::instance(),
            new AgileDashboard_Milestone_MilestoneStatusCounter(new AgileDashboard_BacklogItemDao(), new Tracker_ArtifactDao(), Tracker_ArtifactFactory::instance()),
            new PlanningPermissionsManager(),
            new AgileDashboard_Milestone_MilestoneDao(),
            $scrum_mono_milestone_checker,
            new TimeframeBuilder(Tracker_FormElementFactory::instance(), new SemanticTimeframeBuilder(new SemanticTimeframeDao(), Tracker_FormElementFactory::instance()), new \BackendLogger()),
            new MilestoneBurndownFieldChecker(Tracker_FormElementFactory::instance())
        );

        return new self(
            HTTPRequest::instance(),
            $planning_factory,
            new AgileDashboard_Milestone_Backlog_BacklogFactory(
                new AgileDashboard_BacklogItemDao(),
                Tracker_ArtifactFactory::instance(),
                $planning_factory,
                $scrum_mono_milestone_checker,
                $mono_milestone_items_finder
            ),
            new AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory(
                new AgileDashboard_BacklogItemDao(),
                Tracker_ArtifactFactory::instance(),
                $milestone_factory,
                $planning_factory,
                new AgileDashboard_Milestone_Backlog_BacklogItemBuilder,
                new RemainingEffortValueRetriever(Tracker_FormElementFactory::instance()),
                new ArtifactsInExplicitBacklogDao(),
                new Tracker_Artifact_PriorityDao()
            ),
            $milestone_factory,
            TrackerFactory::instance()
        );
    }

    public function getProjectReleasePresenter(bool $is_IE11): ProjectReleasePresenter
    {
        return new ProjectReleasePresenter(
            $this->request->getProject(),
            $is_IE11,
            $this->getNumberUpcomingReleases(),
            $this->getNumberBacklogItems(),
            $this->getTrackersIdAgileDashboard()
        );
    }

    private function getNumberUpcomingReleases(): int
    {

        $root_planning = $this->planning_factory->getRootPlanning(
            $this->current_user,
            $this->request->getProject()->getID()
        );

        if (!$root_planning) {
            return 0;
        }

        $futures_milestones = $this->planning_milestone_factory->getAllFutureMilestones($this->current_user, $root_planning);

        return count($futures_milestones);
    }

    private function getNumberBacklogItems(): int
    {
        $backlog = $this->agile_dashboard_milestone_backlog_backlog_item_collection_factory
            ->getUnassignedOpenCollection(
                $this->current_user,
                $this->planning_virtual_top_milestone,
                $this->agile_dashboard_milestone_backlog_backlog_factory->getSelfBacklog($this->planning_virtual_top_milestone),
                false
            );

        return $backlog->count();
    }

    private function getTrackersIdAgileDashboard(): array
    {
        $trackers_agile_dashboard    = [];
        $trackers_id_agile_dashboard = $this->planning_virtual_top_milestone->getPlanning()->getBacklogTrackersIds();

        foreach ($trackers_id_agile_dashboard as $tracker_id) {
            $tracker                 = $this->tracker_factory->getTrackerById($tracker_id);
            $tracker_agile_dashboard = [
                'id' => (int)$tracker_id,
                'color_name' => $tracker->getColor()->getName(),
                'label' => $tracker->getName()
            ];

            $trackers_agile_dashboard[] = $tracker_agile_dashboard;
        }

        return $trackers_agile_dashboard;
    }
}
