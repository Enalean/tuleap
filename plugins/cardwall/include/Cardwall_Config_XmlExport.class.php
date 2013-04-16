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

class Cardwall_Config_XmlExport {

    private $project;
    private $tracker_factory;
    private $config_factory;

    public function __construct(Project $project, TrackerFactory $tracker_factory, Cardwall_OnTop_ConfigFactory $config_factory) {
        $this->project         = $project;
        $this->tracker_factory = $tracker_factory;
        $this->config_factory  = $config_factory;
    }

    public function exportToXml(SimpleXMLElement $root) {
        $trackers_node = $root->addChild('trackers');
        $trackers = $this->tracker_factory->getTrackersByGroupId($this->project->getId());
        foreach ($trackers as $tracker) {
            $this->addTrackerChildInXml($tracker, $trackers_node);
        }
    }

    private function addTrackerChildInXml(Tracker $tracker, SimpleXMLElement $trackers_node) {
        $on_top_config = $this->config_factory->getOnTopConfig($tracker);
        if ($on_top_config->getMappingFor($tracker)) {
            $tracker_node = $trackers_node->addChild('tracker');
            $tracker_node->addAttribute('id', $tracker->getId());
        }
    }
}
?>
