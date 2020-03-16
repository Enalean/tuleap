<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All rights reserved
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

use Tuleap\AgileDashboard\Planning\XML\XMLExporter;

/**
 * Transforms imported xml into php values
 */
class AgileDashboard_XMLImporter
{

    /**
     *
     * @param array $tracker_mappings
     *  These should be in the form of an array, e.g. :
     *    array(
     *      'T11' => 45,
     *      'T8'  => 695,
     *    )
     *   where the keys are XML tracker IDs and the values are existing tracker IDs
     *
     * @return array
     * @throw AgileDashboard_XMLImporterInvalidTrackerMappingsException
     */
    public function toArray(SimpleXMLElement $xml_object, array $tracker_mappings)
    {
        $plannings_node_name = XMLExporter::NODE_PLANNINGS;
        $plannings = array();
        $plannings[$plannings_node_name] = array();

        if (! $xml_object->$plannings_node_name) {
            return $plannings;
        }

        foreach ($xml_object->$plannings_node_name->children() as $planning) {
            $attributes = $planning->attributes();

            $planning_tracker_id = $this->getTrackerIdFromMappings(
                (string) $attributes[PlanningParameters::PLANNING_TRACKER_ID],
                $tracker_mappings
            );

            $planning_parameters = array(
                PlanningParameters::NAME                => (string) $attributes[PlanningParameters::NAME],
                PlanningParameters::BACKLOG_TITLE       => (string) $attributes[PlanningParameters::BACKLOG_TITLE],
                PlanningParameters::PLANNING_TITLE      => (string) $attributes[PlanningParameters::PLANNING_TITLE],
                PlanningParameters::PLANNING_TRACKER_ID => (string) $planning_tracker_id,
                PlanningParameters::BACKLOG_TRACKER_IDS => $this->toArrayBacklogIds($planning, $tracker_mappings)
            );

            foreach ($this->toArrayPermissions($planning) as $permission_name => $ugroups) {
                $planning_parameters[$permission_name] = $ugroups;
            }

            $plannings[$plannings_node_name][] = $planning_parameters;
        }

        return $plannings;
    }

    private function toArrayBacklogIds(SimpleXMLElement $planning_node, array $tracker_mappings)
    {
        $backlog_tracker_ids = array();
        foreach ($planning_node->{XMLExporter::NODE_BACKLOGS}->children() as $backlog) {
            $backlog_tracker_ids[] = $this->getTrackerIdFromMappings(
                (string) $backlog,
                $tracker_mappings
            );
        }
        return $backlog_tracker_ids;
    }

    private function toArrayPermissions(SimpleXMLElement $planning_node)
    {
        $permissions = array();

        if (! isset($planning_node->permissions)) {
            return $permissions;
        }

        foreach ($planning_node->permissions->children() as $permission) {
            $ugroup = (string) $permission['ugroup'];
            $type   = (string) $permission['type'];

            if (! isset($permissions[$type])) {
                $permissions[$type] = array();
            }

            if (isset($GLOBALS['UGROUPS'][$ugroup])) {
                $permissions[$type][] = $GLOBALS['UGROUPS'][$ugroup];
            }
        }

        return $permissions;
    }

    /**
     *
     * @param int $tracker_id
     * @param array $tracker_mappings
     * @return int
     */
    private function getTrackerIdFromMappings($tracker_id, array $tracker_mappings)
    {
        if (! isset($tracker_mappings[$tracker_id])) {
            throw new AgileDashboard_XMLImporterInvalidTrackerMappingsException('Missing data for key: ' . $tracker_id);
        }

        return (int) $tracker_mappings[$tracker_id];
    }
}
