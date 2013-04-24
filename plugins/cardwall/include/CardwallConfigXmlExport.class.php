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

require_once 'common/XmlValidator/XmlValidator.class.php';

class CardwallConfigXmlExport {

    /** @var Project */
    private $project;

    /**  @var TrackerFactory */
    private $tracker_factory;

    /**  @var Cardwall_OnTop_ConfigFactory */
    private $config_factory;

    /**  @var XmlValidator */
    private $xml_validator;

    const NODE_CARDWALL = 'cardwall';
    const NODE_TRACKERS = 'trackers';
    const NODE_TRACKER  = 'tracker';

    const ATTRIBUTE_TRACKER_ID = 'id';

    public function __construct(Project $project, TrackerFactory $tracker_factory, Cardwall_OnTop_ConfigFactory $config_factory, XmlValidator $xml_validator) {
        $this->project         = $project;
        $this->tracker_factory = $tracker_factory;
        $this->config_factory  = $config_factory;
        $this->xml_validator   = $xml_validator;
    }

    /**
     *
     * @param SimpleXMLElement $root
     * Export in XML the list of tracker with a cardwall
     */
    public function export(SimpleXMLElement $root) {
        $cardwall_node = $root->addChild(self::NODE_CARDWALL);
        $trackers_node = $cardwall_node->addChild(self::NODE_TRACKERS);
        $trackers      = $this->tracker_factory->getTrackersByGroupId($this->project->getId());
        foreach ($trackers as $tracker) {
            $this->addTrackerChild($tracker, $trackers_node);
        }

        $rng_path = realpath(CARDWALL_BASE_DIR.'/../www/resources/xml_project_cardwall.rng');
        if (! $this->xml_validator->nodeIsValid($cardwall_node, $rng_path)) {
            throw new CardwallConfigXmlExportNodeNotValidException();
        }
    }

    private function addTrackerChild(Tracker $tracker, SimpleXMLElement $trackers_node) {
        $on_top_config = $this->config_factory->getOnTopConfig($tracker);
        if ($on_top_config->isEnabled()) {
            $tracker_node = $trackers_node->addChild(self::NODE_TRACKER);
            $tracker_node->addAttribute(self::ATTRIBUTE_TRACKER_ID, 'T'.$tracker->getId());
        }
    }
}
?>