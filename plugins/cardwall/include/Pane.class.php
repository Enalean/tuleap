<?php
/**
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

use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\RemainingEffortValueRetriever;
use Tuleap\Cardwall\Agiledashboard\CardwallPaneInfo;
use Tuleap\Tracker\Artifact\Artifact;

/**
 * A pane to be displayed in AgileDashboard
 */
class Cardwall_Pane extends AgileDashboard_Pane
{
    /**
     * @var CardwallPaneInfo
     */
    private $info;

    /**
     * @var Planning_Milestone
     */
    private $milestone;

    /**
     * @var Cardwall_OnTop_Config
     */
    private $config;

    /**
     * @var PFUser
     */
    private $user;

    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var Tracker_FormElementFactory
     */
    private $tracker_form_element_factory;

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    public function __construct(
        CardwallPaneInfo $info,
        Planning_Milestone $milestone,
        Cardwall_OnTop_Config $config,
        PFUser $user,
        Planning_MilestoneFactory $milestone_factory,
    ) {
        $this->info                         = $info;
        $this->milestone                    = $milestone;
        $this->config                       = $config;
        $this->user                         = $user;
        $this->milestone_factory            = $milestone_factory;
        $this->artifact_factory             = Tracker_ArtifactFactory::instance();
        $this->tracker_form_element_factory = Tracker_FormElementFactory::instance();
        $this->user_manager                 = UserManager::instance();
        $this->planning_factory             = PlanningFactory::build();
    }

    public function getIdentifier()
    {
        return $this->info->getIdentifier();
    }

    /**
     * @see AgileDashboard_Pane::getFullContent()
     */
    public function getFullContent()
    {
        return $this->getPaneContent('agiledashboard-fullpane');
    }

    /**
     * @see AgileDashboard_Pane::getMinimalContent()
     */
    public function getMinimalContent()
    {
        return $this->getPaneContent('agiledashboard-minimalpane');
    }

    /**
     * @return list<string>
     */
    public function getBodyClass(): array
    {
        return ['agiledashboard-body'];
    }

    private function getPaneContent($template)
    {
        $columns  = $this->config->getDashboardColumns();
        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(__FILE__) . '/../templates');
        $html     = $renderer->renderToString($template, $this->getPresenterUsingMappedFields($columns));
        // TODO what if no semantic status and no mapping????

        return $html;
    }

    /**
     * @return Cardwall_PaneContentPresenter
     */
    private function getPresenterUsingMappedFields(Cardwall_OnTop_Config_ColumnCollection $columns)
    {
        $planning = $this->milestone->getPlanning();

        $raw_board_builder   = new Cardwall_RawBoardBuilder();
        $display_preferences = $raw_board_builder->getDisplayPreferences($this->milestone, $this->user);
        $column_preferences  = new Cardwall_UserPreferences_Autostack_AutostackDashboard($this->user, $this->config->getTracker());
        $column_autostack    = new Cardwall_UserPreferences_UserPreferencesAutostackFactory();
        $column_autostack->setAutostack($columns, $column_preferences);

        $redirect_parameter = 'cardwall[agile][' . $planning->getId() . ']=' . $this->milestone->getArtifactId();

        $this->milestone = $this->milestone_factory->updateMilestoneContextualInfo($this->user, $this->milestone);
        $board           = $raw_board_builder->buildBoardUsingMappedFields($this->user, $this->artifact_factory, $this->milestone, $this->config, $columns);

        return new Cardwall_PaneContentPresenter(
            $board,
            $redirect_parameter,
            $this->getSwitchDisplayAvatarsURL(),
            $display_preferences->shouldDisplayAvatars(),
            $planning,
            $this->milestone,
            $this->getProgressPresenter()
        );
    }

    /**
     * We display an effort based progress bar if and only if all backlog elements
     * have an initial effort. Otherwise, you might ends with a progress bar at
     * 100% done with cards "not done".
     *
     * @return Cardwall_EffortProgressPresenter
     */
    private function getProgressPresenter()
    {
        try {
            return new Cardwall_RemainingEffortProgressPresenter(
                $this->getInitialEffort(),
                $this->milestone->getCapacity(),
                $this->milestone->getRemainingEffort()
            );
        } catch (InitialEffortNotDefinedException $exception) {
            $status_count = $this->milestone_factory->getMilestoneStatusCount($this->user, $this->milestone);
            return new Cardwall_OpenClosedEffortProgressPresenter(
                $status_count[Artifact::STATUS_OPEN],
                $status_count[Artifact::STATUS_CLOSED]
            );
        }
    }

    private function getInitialEffort()
    {
        $milestone_initial_effort = 0;

        foreach ($this->getMilestoneContentItems() as $content) {
            $milestone_initial_effort = $this->addInitialEffort($milestone_initial_effort, $content->getInitialEffort());
        }

        return $milestone_initial_effort;
    }

    /**
     * This method ensures that initial effort is correctly defined
     * for all the milestone's backlog items
     *
     * @param type $milestone_initial_effort
     * @param type $backlog_item_initial_effort
     * @return float
     *
     * @throws InitialEffortNotDefinedException
     */
    private function addInitialEffort($milestone_initial_effort, $backlog_item_initial_effort)
    {
        if (! is_null($backlog_item_initial_effort) && $backlog_item_initial_effort !== '' && $backlog_item_initial_effort >= 0) {
            return $milestone_initial_effort + floatval($backlog_item_initial_effort);
        }

        throw new InitialEffortNotDefinedException();
    }

    private function getMilestoneContentItems()
    {
        $backlog_item_collection_factory = new AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory(
            new AgileDashboard_BacklogItemDao(),
            $this->artifact_factory,
            $this->milestone_factory,
            $this->planning_factory,
            new AgileDashboard_Milestone_Backlog_BacklogItemBuilder(),
            new RemainingEffortValueRetriever(
                $this->tracker_form_element_factory
            ),
            new ArtifactsInExplicitBacklogDao(),
            new Tracker_Artifact_PriorityDao()
        );

        $backlog_factory = new AgileDashboard_Milestone_Backlog_BacklogFactory(
            new AgileDashboard_BacklogItemDao(),
            $this->artifact_factory,
            $this->planning_factory,
        );

        return $backlog_item_collection_factory->getOpenAndClosedCollection(
            $this->user_manager->getCurrentUser(),
            $this->milestone,
            $backlog_factory->getSelfBacklog($this->milestone),
            ''
        );
    }

    private function getSwitchDisplayAvatarsURL()
    {
        if ($this->user->isAnonymous()) {
            return false;
        }

        $group_id    = $this->milestone->getGroupId();
        $planning_id = $this->milestone->getPlanningId();
        $tracker_id  = $this->milestone->getTrackerId();
        $artifact_id = $this->milestone->getArtifactId();

        $action = 'toggle_user_display_avatar';

        $switch_display_username_url =
            CARDWALL_BASE_URL
            . '/?group_id='   . $group_id
            . '&planning_id=' . $planning_id
            . '&tracker_id='  . $tracker_id
            . '&aid='         . $artifact_id
            . '&action='      . $action;

        return $switch_display_username_url;
    }
}
