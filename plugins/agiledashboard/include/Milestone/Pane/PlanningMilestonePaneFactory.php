<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Pane;

use Codendi_Request;
use EventManager;
use PFUser;
use Planning_Milestone;
use Planning_MilestoneFactory;
use Tuleap\AgileDashboard\Milestone\Pane\Details\DetailsPane;
use Tuleap\AgileDashboard\Milestone\Pane\Details\DetailsPaneInfo;
use Tuleap\AgileDashboard\Milestone\Pane\Planning\PlanningV2Pane;
use Tuleap\AgileDashboard\Milestone\Pane\Planning\SubmilestoneFinder;
use Tuleap\AgileDashboard\Milestone\Pane\Planning\PlanningV2PaneInfo;
use Tuleap\AgileDashboard\Milestone\Pane\Planning\PlanningV2Presenter;
use Tuleap\AgileDashboard\Planning\AllowedAdditionalPanesToDisplayCollector;
use Tuleap\Tracker\Milestone\PaneInfo;

/**
 * I build panes for a Planning_Milestone
 */
class PlanningMilestonePaneFactory
{
    /** @var array<int, array<PaneInfo>> */
    private array $list_of_pane_info = [];

    /** @var DetailsPaneInfo[] */
    private array $list_of_default_pane_info = [];

    /** @var array<AgileDashboardPane|null> */
    private array $active_pane = [];


    public function __construct(
        private readonly Codendi_Request $request,
        private readonly Planning_MilestoneFactory $milestone_factory,
        private readonly PanePresenterBuilderFactory $pane_presenter_builder_factory,
        private readonly SubmilestoneFinder $submilestone_finder,
        private readonly PaneInfoFactory $pane_info_factory,
        private readonly EventManager $event_manager,
    ) {
    }

    public function getPanePresenterData(Planning_Milestone $milestone): PanePresenterData
    {
        return new PanePresenterData(
            $this->getActivePane($milestone, $this->request->getCurrentUser()),
            $this->getListOfPaneInfo($milestone, $this->request->getCurrentUser())
        );
    }

    public function getActivePane(Planning_Milestone $milestone, PFUser $user): AgileDashboardPane
    {
        $artifact_id = $milestone->getArtifactId() ?? 0;
        if (! isset($this->list_of_pane_info[$artifact_id])) {
            $this->buildActivePane($milestone, $user);
        }

        assert($this->active_pane[$artifact_id] !== null);

        return $this->active_pane[$artifact_id];
    }

    /** @return PaneInfo[] */
    public function getListOfPaneInfo(Planning_Milestone $milestone, PFUser $user): array
    {
        if (! isset($this->list_of_pane_info[$milestone->getArtifactId() ?? 0])) {
            $this->buildListOfPaneInfo($milestone, $user);
        }

        return $this->list_of_pane_info[$milestone->getArtifactId() ?? 0];
    }

    private function buildListOfPaneInfo(Planning_Milestone $milestone, PFUser $user): void
    {
        $this->active_pane[$milestone->getArtifactId() ?? 0] = null;

        $this->list_of_pane_info[$milestone->getArtifactId() ?? 0][] = $this->getDetailsPaneInfo($milestone);

        $planning_v2_pane_info = $this->getPlanningV2PaneInfo($user, $milestone);
        if ($planning_v2_pane_info) {
            $this->list_of_pane_info[$milestone->getArtifactId() ?? 0][] = $planning_v2_pane_info;
        }

        $this->buildAdditionnalPanes($milestone, $user);
        $this->list_of_pane_info[$milestone->getArtifactId() ?? 0] = array_values(array_filter($this->list_of_pane_info[$milestone->getArtifactId() ?? 0]));
    }

    private function buildActivePane(Planning_Milestone $milestone, PFUser $user): void
    {
        $this->buildListOfPaneInfo($milestone, $user);
        if (! $this->active_pane[$milestone->getArtifactId() ?? 0]) {
            $this->buildDefaultPane($milestone);
        }
    }

    private function getDetailsPaneInfo(Planning_Milestone $milestone): DetailsPaneInfo
    {
        $pane_info = $this->pane_info_factory->getDetailsPaneInfo($milestone);
        if ($this->request->get('pane') == DetailsPaneInfo::IDENTIFIER) {
            $pane_info->setActive(true);
        }

        return $pane_info;
    }

    private function getDetailsPane(DetailsPaneInfo $pane_info, Planning_Milestone $milestone): DetailsPane
    {
        return new DetailsPane(
            $pane_info,
            $this->getDetailsPresenterBuilder()->getMilestoneDetailsPresenter($this->request->getCurrentUser(), $milestone)
        );
    }

    private function buildDefaultPane(Planning_Milestone $milestone): void
    {
        if (! isset($this->list_of_default_pane_info[$milestone->getArtifactId() ?? 0])) {
            $pane_info                                                         = $this->pane_info_factory->getDetailsPaneInfo($milestone);
            $this->list_of_default_pane_info[$milestone->getArtifactId() ?? 0] = $pane_info;
        } else {
            $pane_info = $this->list_of_default_pane_info[$milestone->getArtifactId() ?? 0];
            $pane_info->setActive(true);
        }
        $this->active_pane[$milestone->getArtifactId() ?? 0] = $this->getDetailsPane($pane_info, $milestone);
    }

    private function getPlanningV2PaneInfo(PFUser $user, Planning_Milestone $milestone): ?PaneInfo
    {
        $submilestone_tracker = $this->submilestone_finder->findFirstSubmilestoneTracker($user, $milestone);
        if (! $submilestone_tracker) {
            return null;
        }

        $pane_info = $this->pane_info_factory->getPlanningV2PaneInfo($user, $milestone);
        if ($this->request->get('pane') == PlanningV2PaneInfo::IDENTIFIER && $pane_info !== null) {
            $pane_info->setActive(true);
            $this->active_pane[$milestone->getArtifactId() ?? 0] = $this->getPlanningV2Pane($pane_info, $milestone);
        }

        return $pane_info;
    }

    private function getPlanningV2Pane(
        PlanningV2PaneInfo $info,
        Planning_Milestone $milestone,
    ): PlanningV2Pane {
        $allowed_additional_panes_to_display_collector = new AllowedAdditionalPanesToDisplayCollector();
        $this->event_manager->processEvent($allowed_additional_panes_to_display_collector);

        $project = $this->request->getProject();

        return new PlanningV2Pane(
            $info,
            new PlanningV2Presenter(
                $this->request->getCurrentUser(),
                $project,
                (string) $milestone->getArtifactId(),
                false,
                $allowed_additional_panes_to_display_collector->getIdentifiers(),
            )
        );
    }

    private function buildAdditionnalPanes(Planning_Milestone $milestone, PFUser $user): void
    {
        if ($milestone->getArtifact()) {
            $collector = new \Tuleap\AgileDashboard\Milestone\Pane\PaneInfoCollector(
                $milestone,
                new \Tuleap\AgileDashboard\Milestone\Pane\ActivePaneContext(
                    $this->request,
                    $this->request->getCurrentUser(),
                    $this->milestone_factory,
                ),
                $this->list_of_pane_info[$milestone->getArtifactId() ?? 0],
                $this->active_pane[$milestone->getArtifactId() ?? 0],
                $user,
            );

            EventManager::instance()->processEvent($collector);

            $this->list_of_pane_info[$milestone->getArtifactId() ?? 0] = $collector->getPanes();
            $this->active_pane[$milestone->getArtifactId() ?? 0]       = $collector->getActivePane();
        }
    }

    private function getDetailsPresenterBuilder(): Details\DetailsPresenterBuilder
    {
        return $this->pane_presenter_builder_factory->getDetailsPresenterBuilder();
    }
}
