<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/TreeNode/TreeNodeMapper.class.php';
require_once 'common/templating/TemplateRendererFactory.class.php';

/**
 * A pane to be displayed in AgileDashboard
 */
class Cardwall_Pane extends AgileDashboard_Pane {

    /**
     * @var Cardwall_PaneInfo
     */
    private $info;

    /**
     * @var Planning_Milestone
     */
    private $milestone;

    /**
     * @var bool
     */
    private $enable_qr_code;

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
            Cardwall_PaneInfo $info,
            Planning_Milestone $milestone,
            $enable_qr_code,
            Cardwall_OnTop_Config $config,
            PFUser $user,
            Planning_MilestoneFactory $milestone_factory
    ) {
        $this->info                         = $info;
        $this->milestone                    = $milestone;
        $this->enable_qr_code               = $enable_qr_code;
        $this->config                       = $config;
        $this->user                         = $user;
        $this->milestone_factory            = $milestone_factory;
        $this->artifact_factory             = Tracker_ArtifactFactory::instance();
        $this->tracker_form_element_factory = Tracker_FormElementFactory::instance();
        $this->user_manager                 = UserManager::instance();
        $this->planning_factory             = PlanningFactory::build();
    }

    public function getIdentifier() {
        return $this->info->getIdentifier();
    }

    public function getUriForMilestone(Planning_Milestone $milestone) {
        return $this->info->getUriForMilestone($milestone);
    }


    /**
     * @see AgileDashboard_Pane::getFullContent()
     */
    public function getFullContent() {
        return $this->getPaneContent('agiledashboard-fullpane');
    }

    /**
     * @see AgileDashboard_Pane::getMinimalContent()
     */
    public function getMinimalContent() {
        return $this->getPaneContent('agiledashboard-minimalpane');
    }

    private function getPaneContent($template) {
        $event_manager = EventManager::instance();
        $columns = $this->config->getDashboardColumns();
        $renderer  = TemplateRendererFactory::build()->getRenderer(dirname(__FILE__).'/../templates');
        $html = $renderer->renderToString($template, $this->getPresenterUsingMappedFields($columns));
        // TODO what if no semantic status and no mapping????

        $event_manager->processEvent(CARDWALL_EVENT_DISPLAYED, array('html' => &$html));

        return $html;
    }

    /**
     * @return Cardwall_PaneContentPresenter
     */
    private function getPresenterUsingMappedFields(Cardwall_OnTop_Config_ColumnCollection $columns) {
        $planning            = $this->milestone->getPlanning();

        $raw_board_builder   = new Cardwall_RawBoardBuilder();
        $display_preferences = $raw_board_builder->getDisplayPreferences($this->milestone, $this->user);
        $column_preferences  = new Cardwall_UserPreferences_Autostack_AutostackDashboard($this->user, $this->config->getTracker());
        $column_autostack    = new Cardwall_UserPreferences_UserPreferencesAutostackFactory();
        $column_autostack->setAutostack($columns, $column_preferences);

        $redirect_parameter  = 'cardwall[agile]['. $planning->getId() .']='. $this->milestone->getArtifactId();

        $this->milestone = $this->milestone_factory->updateMilestoneContextualInfo($this->user, $this->milestone);
        $board = $raw_board_builder->buildBoardUsingMappedFields($this->user, $this->artifact_factory,$this->milestone, $this->config, $columns);

        return new Cardwall_PaneContentPresenter(
            $board,
            $this->getQrCode(),
            $redirect_parameter,
            $this->getSwitchDisplayAvatarsURL(),
            $display_preferences->shouldDisplayAvatars(),
            $planning,
            $this->milestone,
            $this->getMilestoneContentItems()
        );
    }

    private function getMilestoneContentItems() {
        $backlog_item_collection_factory = new AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory(
            new AgileDashboard_BacklogItemDao(),
            $this->artifact_factory,
            $this->tracker_form_element_factory,
            $this->milestone_factory,
            $this->planning_factory,
            new AgileDashboard_Milestone_Backlog_BacklogItemBuilder()
        );

        $strategy_factory = new AgileDashboard_Milestone_Backlog_BacklogStrategyFactory(
            new AgileDashboard_BacklogItemDao(),
            $this->artifact_factory,
            $this->planning_factory
        );

        return $backlog_item_collection_factory->getAllCollection(
            $this->user_manager->getCurrentUser(),
            $this->milestone,
            $strategy_factory->getSelfBacklogStrategy($this->milestone),
            ''
        );
    }

    /**
     * @return Cardwall_QrCode
     */
    private function getQrCode() {
        if ($this->enable_qr_code) {
            return new Cardwall_QrCode($_SERVER['REQUEST_URI'] .'&pv=2');
        }
        return false;
    }

    private function getSwitchDisplayAvatarsURL() {
        if ($this->user->isAnonymous()) {
            return false;
        }

        $group_id    = $this->milestone->getGroupId();
        $planning_id = $this->milestone->getPlanningId();
        $tracker_id  = $this->milestone->getTrackerId();
        $artifact_id = $this->milestone->getArtifactId();

        $action      = 'toggle_user_display_avatar';

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
?>
