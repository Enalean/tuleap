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

    public function __construct(
            Cardwall_PaneInfo $info,
            Planning_Milestone $milestone,
            $enable_qr_code,
            Cardwall_OnTop_Config $config,
            PFUser $user,
            Planning_MilestoneFactory $milestone_factory
            ) {
        $this->info           = $info;
        $this->milestone      = $milestone;
        $this->enable_qr_code = $enable_qr_code;
        $this->config         = $config;
        $this->user           = $user;
        $this->milestone_factory = $milestone_factory;
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
        $columns = $this->config->getDashboardColumns();
        $renderer  = TemplateRendererFactory::build()->getRenderer(dirname(__FILE__).'/../templates');
        return $renderer->renderToString($template, $this->getPresenterUsingMappedFields($columns));
        // TODO what if no semantic status and no mapping????
    }

    /**
     * @return Cardwall_PaneContentPresenter
     */
    private function getPresenterUsingMappedFields(Cardwall_OnTop_Config_ColumnCollection $columns) {
        $board_factory      = new Cardwall_BoardFactory();
        $this->milestone_factory->updateMilestoneWithPlannedArtifacts($this->user, $this->milestone);
        $planned_artifacts  = $this->milestone->getPlannedArtifacts();

        $field_retriever    = new Cardwall_OnTop_Config_MappedFieldProvider($this->config,
                                new Cardwall_FieldProviders_SemanticStatusFieldRetriever());

        $board              = $board_factory->getBoard($field_retriever, $columns, $planned_artifacts, $this->config, $this->user);
        $backlog_title      = $this->milestone->getPlanning()->getBacklogTracker()->getName();
        $redirect_parameter = 'cardwall[agile]['. $this->milestone->getPlanning()->getId() .']='. $this->milestone->getArtifactId();

        return new Cardwall_PaneContentPresenter($board, $this->getQrCode(), $redirect_parameter, $backlog_title, $this->canConfigure(), $this->userIsNotAnonymous());
    }
    
    private function canConfigure() {        
        $project = $this->milestone->getProject();
        if ($project->userIsAdmin($this->user)){
            $configure_url      = TRACKER_BASE_URL .'/?tracker='. $this->milestone->getTrackerId() .'&func=admin-cardwall';
            return $configure_url;
        }
        return false;
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

    private function isUserNotAnonymous() {
        if ($this->user->isAnonymous()) {
            return false;
        }

        $switch_display_username_url = "http://google.fr";
        return $switch_display_username_url;
    }
}
?>
