<?php

/**
 * Copyright (c) Enalean, 2013. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

/**
 * Transforms imported xml into php values
 */
class AgileDashboard_XMLImporter {

    /**
     *
     * @param SimpleXMLElement $xml_object
     * @return array
     */
    public function toArray(SimpleXMLElement $xml_object) {
        $plannings = array();

        $plannings_node_name = AgileDashboard_XMLExporter::NODE_PLANNINGS;

        if (! $xml_object->$plannings_node_name) {
            return $plannings;
        }

        foreach ($xml_object->$plannings_node_name->children() as $planning) {
            $attributes = $planning->attributes();

            $plannings[] = array(
                PlanningParameters::NAME                => (string) $attributes[PlanningParameters::NAME],
                PlanningParameters::BACKLOG_TITLE       => (string) $attributes[PlanningParameters::BACKLOG_TITLE],
                PlanningParameters::PLANNING_TITLE      => (string) $attributes[PlanningParameters::PLANNING_TITLE],
                PlanningParameters::BACKLOG_TRACKER_ID  => (string) $attributes[PlanningParameters::BACKLOG_TRACKER_ID],
                PlanningParameters::PLANNING_TRACKER_ID => (string) $attributes[PlanningParameters::BACKLOG_TRACKER_ID],
            );
        }

        return $plannings;
    }
}
?>
