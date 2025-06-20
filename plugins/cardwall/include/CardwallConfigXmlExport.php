<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\Cardwall\XML\XMLCardwall;
use Tuleap\Cardwall\XML\XMLCardwallColumn;
use Tuleap\Cardwall\XML\XMLCardwallMapping;
use Tuleap\Cardwall\XML\XMLCardwallMappingValue;
use Tuleap\Cardwall\XML\XMLCardwallTracker;
use Tuleap\Tracker\Tracker;

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
    public function export(SimpleXMLElement $root): void
    {
        $xml_cardwall = new XMLCardwall();

        $trackers = $this->tracker_factory->getTrackersByGroupId($this->project->getID());
        foreach ($trackers as $tracker) {
            $xml_tracker = $this->getXMLCardwallTracker($tracker);
            if ($xml_tracker !== null) {
                $xml_cardwall = $xml_cardwall->withTracker($xml_tracker);
            }
        }

        $cardwall_node = $xml_cardwall->export($root);

        $rng_path = realpath(__DIR__ . '/../resources/xml_project_cardwall.rng');
        $this->xml_validator->validate($cardwall_node, $rng_path);
    }

    private function getXMLCardwallTracker(Tracker $tracker): ?XMLCardwallTracker
    {
        $on_top_config = $this->config_factory->getOnTopConfig($tracker);
        if (! $on_top_config->isEnabled()) {
            return null;
        }

        $xml_tracker = (new XMLCardwallTracker('T' . $tracker->getId()));

        if (count($on_top_config->getDashboardColumns()) > 0) {
            foreach ($on_top_config->getDashboardColumns() as $column) {
                $xml_tracker = $xml_tracker->withColumn($this->getXMLCardwallColumn($column));
            }

            foreach ($on_top_config->getMappings() as $mapping) {
                $xml_mapping = $this->getXMLCardwallMapping($mapping);
                if ($xml_mapping !== null) {
                    $xml_tracker = $xml_tracker->withMapping($xml_mapping);
                }
            }
        }
        return $xml_tracker;
    }

    private function getXMLCardwallMapping(Cardwall_OnTop_Config_TrackerMapping $mapping): ?XMLCardwallMapping
    {
        if (! $mapping->isCustom()) {
            return null;
        }

        $field = $mapping->getField();
        if ($field === null) {
            return null;
        }

        $xml_mapping = (new XMLCardwallMapping($mapping->getTracker()->getXMLId(), $field->getXMLId()));

        foreach ($mapping->getValueMappings() as $value_mapping) {
            $xml_mapping = $xml_mapping
                ->withMappingValue((new XMLCardwallMappingValue($value_mapping->getXMLValueId(), 'C' . $value_mapping->getColumnId())));
        }

        return $xml_mapping;
    }

    private function getXMLCardwallColumn(Cardwall_Column $column): XMLCardwallColumn
    {
        $xml_column = (new XMLCardwallColumn($column->getLabel()))
            ->withId('C' . $column->getId());

        $bg_green = null;
        $bg_red   = null;
        $bg_blue  = null;

        $bg_colors = $column->getHeadercolor();

        if ($column->isHeaderATLPColor()) {
            $xml_column = $xml_column->withTLPColorName($bg_colors);
            return $xml_column;
        }

        if ($bg_colors) {
            $regexp  = '/^rgb\((\d{1,3}), (\d{1,3}), (\d{1,3})\)$/';
            $matches = [];
            if (preg_match($regexp, $bg_colors, $matches)) {
                $bg_red   = $matches[1];
                $bg_green = $matches[2];
                $bg_blue  = $matches[3];
            }
        }

        if ($bg_red !== null && $bg_green !== null && $bg_blue !== null) {
            $xml_column = $xml_column->withLegacyColorsName($bg_red, $bg_green, $bg_blue);
        }

        return $xml_column;
    }
}
