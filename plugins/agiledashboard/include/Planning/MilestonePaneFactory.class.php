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

use Tuleap\AgileDashboard\Milestone\Pane\Details\DetailsPane;
use Tuleap\AgileDashboard\Milestone\Pane\Details\DetailsPaneInfo;
use Tuleap\AgileDashboard\Milestone\Pane\PanePresenterData;
use Tuleap\AgileDashboard\Milestone\Pane\Planning\PlanningV2Presenter;
use Tuleap\AgileDashboard\Milestone\Pane\Planning\PlanningV2PaneInfo;
use Tuleap\AgileDashboard\Planning\AllowedAdditionalPanesToDisplayCollector;
use Tuleap\Tracker\Milestone\PaneInfo;

/**
 * I build panes for a Planning_Milestone
 */
class Planning_MilestonePaneFactory // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    /** @var array<int, array<PaneInfo>> */
    private $list_of_pane_info = [];

    /** @var PaneInfo[] */
    private $list_of_default_pane_info = [];

    /** @var array<AgileDashboard_Pane|null> */
    private $active_pane = [];

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

    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(
        Codendi_Request $request,
        Planning_MilestoneFactory $milestone_factory,
        AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory $pane_presenter_builder_factory,
        AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder $submilestone_finder,
        AgileDashboard_PaneInfoFactory $pane_info_factory,
        EventManager $event_manager,
    ) {
        $this->request                        = $request;
        $this->milestone_factory              = $milestone_factory;
        $this->pane_presenter_builder_factory = $pane_presenter_builder_factory;
        $this->submilestone_finder            = $submilestone_finder;
        $this->pane_info_factory              = $pane_info_factory;
        $this->event_manager                  = $event_manager;
    }

    /** @return PanePresenterData */
    public function getPanePresenterData(Planning_Milestone $milestone)
    {
        return new PanePresenterData(
            $this->getActivePane($milestone, $this->request->getCurrentUser()),
            $this->getListOfPaneInfo($milestone, $this->request->getCurrentUser())
        );
    }

    public function getActivePane(Planning_Milestone $milestone, PFUser $user): AgileDashboard_Pane
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

        $planning_v2_pane_info = $this->getPlanningV2PaneInfo($milestone);
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

    private function getDetailsPaneInfo(Planning_Milestone $milestone)
    {
        $pane_info = $this->pane_info_factory->getDetailsPaneInfo($milestone);
        if ($this->request->get('pane') == DetailsPaneInfo::IDENTIFIER) {
            $pane_info->setActive(true);
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
            $pane_info                                                         = $this->pane_info_factory->getDetailsPaneInfo($milestone);
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

    private function getPlanningV2Pane(
        PlanningV2PaneInfo $info,
        Planning_Milestone $milestone,
    ): AgileDashboard_Milestone_Pane_Planning_PlanningV2Pane {
        $allowed_additional_panes_to_display_collector = new AllowedAdditionalPanesToDisplayCollector();
        $this->event_manager->processEvent($allowed_additional_panes_to_display_collector);

        $project = $this->request->getProject();

        return new AgileDashboard_Milestone_Pane_Planning_PlanningV2Pane(
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

    private function getDetailsPresenterBuilder()
    {
        return $this->pane_presenter_builder_factory->getDetailsPresenterBuilder();
    }
}
