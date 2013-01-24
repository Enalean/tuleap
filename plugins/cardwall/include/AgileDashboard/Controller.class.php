<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Cardwall_AgileDashboard_Controller {
    /**
     * @var Codendi_Request
     */
    private $request;

    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;

    /**
     * @var Cardwall_OnTop_ConfigFactory
     */
    private $config_factory;

    public function __construct(
            cardwallPlugin $plugin,
            Codendi_Request $request,
            Planning_MilestoneFactory $milestone_factory,
            Cardwall_OnTop_ConfigFactory $config_factory) {
        $this->plugin = $plugin;
        $this->request = $request;
        $this->milestone_factory = $milestone_factory;
        $this->config_factory = $config_factory;
    }

    public function show() {
        $milestone = $this->milestone_factory->getMilestoneWithPlannedArtifactsAndSubMilestones(
            $this->request->getCurrentUser(),
            ProjectManager::instance()->getProject($this->request->get('group_id')),
            $this->request->get('planning_id'),
            $this->request->get('aid')
        );
        $tracker = $milestone->getArtifact()->getTracker();
        $config = $this->config_factory->getOnTopConfig($tracker);
        $pane = new Cardwall_Pane(
            $milestone,
            $this->plugin->getPluginInfo()->getPropVal('display_qr_code'),
            $config,
            $this->request->getCurrentUser(),
            $this->plugin->getThemePath()
        );
        echo $pane->getFullContent();
        exit;
    }
}

?>
