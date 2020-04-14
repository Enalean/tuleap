<?php
/**
 * Copyright (c) Enalean, 2013 – 2018. All Rights Reserved.
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

use Tuleap\AgileDashboard\Milestone\Pane\Details\DetailsPane;
use Tuleap\AgileDashboard\Milestone\Pane\Details\DetailsPaneInfo;
use Tuleap\AgileDashboard\Milestone\Pane\PaneInfo;
use Tuleap\AgileDashboard\Milestone\Pane\PanePresenterData;
use Tuleap\AgileDashboard\Milestone\Pane\Planning\PlanningV2PaneInfo;

/**
 * I build panes for a Planning_Milestone
 */
class Planning_MilestonePaneFactory
{
    /**
     * If PRELOAD_ENABLED is set to true, planning v2 data will be injected to the view.
     * If it's set to false, data will be asynchronously fetched via REST calls.
     */
    public const PRELOAD_ENABLED              = false;
    public const PRELOAD_PAGINATION_LIMIT     = 50;
    public const PRELOAD_PAGINATION_OFFSET    = 0;
    public const PRELOAD_PAGINATION_ORDER     = 'desc';
    public const PRELOAD_SUBMILESTONES_FIELDS = Tuleap\AgileDashboard\REST\v1\MilestoneRepresentation::SLIM;
    public const PRELOAD_MILESTONE_FIELDS     = Tuleap\AgileDashboard\REST\v1\MilestoneRepresentation::ALL_FIELDS;

    /** @var array<int, array<PaneInfo>> */
    private $list_of_pane_info = array();

    /** @var PaneInfo[] */
    private $list_of_default_pane_info = array();

    /** @var array<AgileDashboard_Pane|null> */
    private $active_pane = array();

    /** @var Codendi_Request */
    private $request;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory */
    private $pane_presenter_builder_factory;

    /** @var AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder */
    private $submilestone_finder;

    /** @var AgileDashboard_PaneInfoFactory */
    private $pane_info_factory;

    /** @var AgileDashboard_Milestone_MilestoneRepresentationBuilder */
    private $milestone_representation_builder;

    /** @var AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentationsBuilder */
    private $paginated_backlog_items_representations_builder;

    public function __construct(
        Codendi_Request $request,
        Planning_MilestoneFactory $milestone_factory,
        AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory $pane_presenter_builder_factory,
        AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder $submilestone_finder,
        AgileDashboard_PaneInfoFactory $pane_info_factory,
        AgileDashboard_Milestone_MilestoneRepresentationBuilder $milestone_representation_builder,
        AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentationsBuilder $paginated_backlog_items_representations_builder
    ) {
        $this->request                                         = $request;
        $this->milestone_factory                               = $milestone_factory;
        $this->pane_presenter_builder_factory                  = $pane_presenter_builder_factory;
        $this->submilestone_finder                             = $submilestone_finder;
        $this->pane_info_factory                               = $pane_info_factory;
        $this->milestone_representation_builder                = $milestone_representation_builder;
        $this->paginated_backlog_items_representations_builder = $paginated_backlog_items_representations_builder;
    }

    /** @return PanePresenterData */
    public function getPanePresenterData(Planning_Milestone $milestone)
    {
        return new PanePresenterData(
            $this->getActivePane($milestone),
            $this->getListOfPaneInfo($milestone)
        );
    }

    /** @return AgileDashboard_Pane */
    public function getActivePane(Planning_Milestone $milestone)
    {
        $artifact_id = $milestone->getArtifactId() ?? 0;
        if (! isset($this->list_of_pane_info[$artifact_id])) {
            $this->buildActivePane($milestone);
        }

        assert($this->active_pane[$artifact_id] !== null);

        return $this->active_pane[$artifact_id];
    }

    /** @return PaneInfo[] */
    public function getListOfPaneInfo(Planning_Milestone $milestone)
    {
        if (! isset($this->list_of_pane_info[$milestone->getArtifactId() ?? 0])) {
            $this->buildListOfPaneInfo($milestone);
        }

        return $this->list_of_pane_info[$milestone->getArtifactId() ?? 0];
    }

    /** @return string */
    public function getDefaultPaneIdentifier()
    {
        return DetailsPaneInfo::IDENTIFIER;
    }

    private function buildListOfPaneInfo(Planning_Milestone $milestone)
    {
        $this->active_pane[$milestone->getArtifactId() ?? 0] = null;

        $this->list_of_pane_info[$milestone->getArtifactId() ?? 0][] = $this->getDetailsPaneInfo($milestone);

        $planning_v2_pane_info = $this->getPlanningV2PaneInfo($milestone);
        if ($planning_v2_pane_info) {
            $this->list_of_pane_info[$milestone->getArtifactId() ?? 0][] = $planning_v2_pane_info;
        }

        $this->buildAdditionnalPanes($milestone);
        $this->list_of_pane_info[$milestone->getArtifactId() ?? 0] = array_values(array_filter($this->list_of_pane_info[$milestone->getArtifactId() ?? 0]));
    }

    private function buildActivePane(Planning_Milestone $milestone)
    {
        $this->buildListOfPaneInfo($milestone);
        if (! $this->active_pane[$milestone->getArtifactId() ?? 0]) {
            $this->buildDefaultPane($milestone);
        }
    }

    private function getDetailsPaneInfo(Planning_Milestone $milestone)
    {
        $pane_info = $this->pane_info_factory->getDetailsPaneInfo($milestone);
        if ($this->request->get('pane') == DetailsPaneInfo::IDENTIFIER) {
            $pane_info->setActive(true);
            $this->active_pane[$milestone->getArtifactId() ?? 0] = $this->getDetailsPane($pane_info, $milestone);
        }

        return $pane_info;
    }

    private function getDetailsPane(DetailsPaneInfo $pane_info, Planning_Milestone $milestone)
    {
        return new DetailsPane(
            $pane_info,
            $this->getDetailsPresenterBuilder()->getMilestoneDetailsPresenter($this->request->getCurrentUser(), $milestone)
        );
    }

    private function buildDefaultPane(Planning_Milestone $milestone)
    {
        if (! isset($this->list_of_default_pane_info[$milestone->getArtifactId() ?? 0])) {
            $pane_info                                                    = $this->pane_info_factory->getDetailsPaneInfo($milestone);
            $this->list_of_default_pane_info[$milestone->getArtifactId() ?? 0] = $pane_info;
        } else {
            $pane_info = $this->list_of_default_pane_info[$milestone->getArtifactId() ?? 0];
            $pane_info->setActive(true);
        }
        $this->active_pane[$milestone->getArtifactId() ?? 0] = $this->getDetailsPane($pane_info, $milestone);
    }

    private function getPlanningV2PaneInfo(Planning_Milestone $milestone): ?PaneInfo
    {
        $submilestone_tracker = $this->submilestone_finder->findFirstSubmilestoneTracker($milestone);
        if (! $submilestone_tracker) {
            return null;
        }

        $pane_info = $this->pane_info_factory->getPlanningV2PaneInfo($milestone);
        if ($this->request->get('pane') == PlanningV2PaneInfo::IDENTIFIER) {
            $pane_info->setActive(true);
            $this->active_pane[$milestone->getArtifactId() ?? 0] = $this->getPlanningV2Pane($pane_info, $milestone);
        }

        return $pane_info;
    }

    private function getMilestoneRepresentation(Planning_Milestone $milestone, PFUser $user)
    {
        if (! self::PRELOAD_ENABLED) {
            return null;
        }

        return $this->milestone_representation_builder->getMilestoneRepresentation(
            $milestone,
            $user,
            self::PRELOAD_MILESTONE_FIELDS
        );
    }

    private function getPaginatedBacklogItemsRepresentationsForMilestone(Planning_Milestone $milestone, PFUser $user)
    {
        if (! self::PRELOAD_ENABLED) {
            return null;
        }

        return $this->paginated_backlog_items_representations_builder->getPaginatedBacklogItemsRepresentationsForMilestone(
            $user,
            $milestone,
            self::PRELOAD_PAGINATION_LIMIT,
            self::PRELOAD_PAGINATION_OFFSET
        );
    }

    private function getPaginatedSubMilestonesRepresentations(Planning_Milestone $milestone, PFUser $user)
    {
        if (! self::PRELOAD_ENABLED) {
            return null;
        }

        return $this->milestone_representation_builder->getPaginatedSubMilestonesRepresentations(
            $milestone,
            $user,
            self::PRELOAD_SUBMILESTONES_FIELDS,
            new Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusOpen(),
            self::PRELOAD_PAGINATION_LIMIT,
            self::PRELOAD_PAGINATION_OFFSET,
            self::PRELOAD_PAGINATION_ORDER
        );
    }

    /**
     * @return AgileDashboard_Milestone_Pane_Planning_PlanningV2Pane
     */
    private function getPlanningV2Pane(PlanningV2PaneInfo $info, Planning_Milestone $milestone)
    {
        return new AgileDashboard_Milestone_Pane_Planning_PlanningV2Pane(
            $info,
            new AgileDashboard_Milestone_Pane_Planning_PlanningV2Presenter(
                $this->request->getCurrentUser(),
                $this->request->getProject(),
                $milestone->getArtifactId(),
                $this->getMilestoneRepresentation($milestone, $this->request->getCurrentUser()),
                $this->getPaginatedBacklogItemsRepresentationsForMilestone($milestone, $this->request->getCurrentUser()),
                $this->getPaginatedSubMilestonesRepresentations($milestone, $this->request->getCurrentUser()),
                false
            )
        );
    }

    private function buildAdditionnalPanes(Planning_Milestone $milestone)
    {
        if ($milestone->getArtifact()) {
            $collector = new \Tuleap\AgileDashboard\Milestone\Pane\PaneInfoCollector(
                $milestone,
                $this->request,
                $this->request->getCurrentUser(),
                $this->milestone_factory,
                $this->list_of_pane_info[$milestone->getArtifactId() ?? 0],
                $this->active_pane[$milestone->getArtifactId() ?? 0]
            );

            EventManager::instance()->processEvent($collector);

            $this->list_of_pane_info[$milestone->getArtifactId() ?? 0] = $collector->getPanes();
            $this->active_pane[$milestone->getArtifactId() ?? 0] = $collector->getActivePane();
        }
    }

    private function getDetailsPresenterBuilder()
    {
        return $this->pane_presenter_builder_factory->getDetailsPresenterBuilder();
    }
}
