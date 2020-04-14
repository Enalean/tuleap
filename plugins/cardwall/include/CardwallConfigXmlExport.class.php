<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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

class CardwallConfigXmlExport
{

    /** @var Project */
    private $project;

    /**  @var TrackerFactory */
    private $tracker_factory;

    /**  @var Cardwall_OnTop_ConfigFactory */
    private $config_factory;

    /**  @var XML_RNGValidator */
    private $xml_validator;

    public function __construct(Project $project, TrackerFactory $tracker_factory, Cardwall_OnTop_ConfigFactory $config_factory, XML_RNGValidator $xml_validator)
    {
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
    public function export(SimpleXMLElement $root)
    {
        $cardwall_node = $root->addChild(CardwallConfigXml::NODE_CARDWALL);
        $trackers_node = $cardwall_node->addChild(CardwallConfigXml::NODE_TRACKERS);
        $trackers      = $this->tracker_factory->getTrackersByGroupId($this->project->getID());
        foreach ($trackers as $tracker) {
            $this->addTrackerChild($tracker, $trackers_node);
        }

        $rng_path = realpath(__DIR__ . '/../resources/xml_project_cardwall.rng');
        $this->xml_validator->validate($cardwall_node, $rng_path);
    }

    private function addTrackerChild(Tracker $tracker, SimpleXMLElement $trackers_node)
    {
        $on_top_config = $this->config_factory->getOnTopConfig($tracker);
        if ($on_top_config->isEnabled()) {
            $tracker_node = $trackers_node->addChild(CardwallConfigXml::NODE_TRACKER);
            $tracker_node->addAttribute(CardwallConfigXml::ATTRIBUTE_TRACKER_ID, 'T' . $tracker->getId());
            if (count($on_top_config->getDashboardColumns()) > 0) {
                $columns_node = $tracker_node->addChild(CardwallConfigXml::NODE_COLUMNS);
                foreach ($on_top_config->getDashboardColumns() as $column) {
                    $this->exportColumn($columns_node, $column);
                }

                $mappings_node = $tracker_node->addChild(CardwallConfigXml::NODE_MAPPINGS);
                foreach ($on_top_config->getMappings() as $mapping) {
                    $this->exportMapping($mappings_node, $mapping);
                }
            }
        }
    }

    private function exportMapping(SimpleXMLElement $mappings_node, Cardwall_OnTop_Config_TrackerMapping $mapping)
    {
        if (! $mapping->isCustom()) {
            return;
        }

        $field = $mapping->getField();
        if ($field === null) {
            return;
        }
        $mapping_node = $mappings_node->addChild(CardwallConfigXml::NODE_MAPPING);
        $mapping_node->addAttribute('tracker_id', $mapping->getTracker()->getXMLId());
        $mapping_node->addAttribute('field_id', $field->getXMLId());

        $values_node = $mapping_node->addChild(CardwallConfigXml::NODE_VALUES);
        foreach ($mapping->getValueMappings() as $value_mapping) {
            $this->exportValueMapping($values_node, $value_mapping);
        }
    }

    private function exportValueMapping(
        SimpleXMLElement $values_node,
        Cardwall_OnTop_Config_ValueMapping $value_mapping
    ) {
        $value_node = $values_node->addChild(CardwallConfigXml::NODE_VALUE);
        $value_node->addAttribute('value_id', $value_mapping->getXMLValueId());
        $value_node->addAttribute('column_id', 'C' . $value_mapping->getColumnId());
    }

    private function exportColumn(SimpleXMLElement $columns_node, Cardwall_Column $column)
    {
        $column_node = $columns_node->addChild(CardwallConfigXml::NODE_COLUMN);
        $column_node->addAttribute(CardwallConfigXml::ATTRIBUTE_COLUMN_LABEL, $column->getLabel());
        $column_node->addAttribute(CardwallConfigXml::ATTRIBUTE_COLUMN_ID, 'C' . $column->getId());

        $this->exportColumnColors($column_node, $column);
    }

    private function exportColumnColors(SimpleXMLElement $column_node, Cardwall_Column $column)
    {
        $bg_green = null;
        $bg_red   = null;
        $bg_blue  = null;

        $bg_colors = $column->getHeadercolor();

        if ($column->isHeaderATLPColor()) {
            $column_node->addAttribute(CardwallConfigXml::ATTRIBUTE_COLUMN_TLP_COLOR_NAME, $bg_colors);
            return;
        }

        if ($bg_colors) {
            $regexp  = "/^rgb\((\d{1,3}), (\d{1,3}), (\d{1,3})\)$/";
            $matches = array();
            if (preg_match($regexp, $bg_colors, $matches)) {
                $bg_red   = $matches[1];
                $bg_green = $matches[2];
                $bg_blue  = $matches[3];
            }
        }

        if ($bg_red !== null && $bg_green !== null  && $bg_blue !== null) {
            $column_node->addAttribute(CardwallConfigXml::ATTRIBUTE_COLUMN_BG_RED, $bg_red);
            $column_node->addAttribute(CardwallConfigXml::ATTRIBUTE_COLUMN_BG_GREEN, $bg_green);
            $column_node->addAttribute(CardwallConfigXml::ATTRIBUTE_COLUMN_BG_BLUE, $bg_blue);
        }
    }
}
