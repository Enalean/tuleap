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

class AgileDashboard_XMLExporter {

    const NODE_PLANNINGS = 'plannings';
    const NODE_PLANNING  = 'planning';

    const ATTRIBUTE_PLANNING_NAME                   = 'name';
    const ATTRIBUTE_PLANNING_TITLE                  = 'plan_title';
    const ATTRIBUTE_PLANNING_ITEM_TRACKER_ID        = 'item_tracker';
    const ATTRIBUTE_PLANNING_BACKLOG_TITLE          = 'backlog_title';
    const ATTRIBUTE_PLANNING_MILESTONE_TRACKER_ID   = 'milestone_tracker';

    /**
     * @todo move me to tracker class
     */
    const TRACKER_ID_PREFIX                         = 'T';

    /**
     *
     * @param SimpleXMLElement $xml_element
     * @param array $planning_short_access_set
     */
    public function export(SimpleXMLElement $xml_element, array $planning_short_access_set) {
        $plannings_node = $xml_element->addChild(self::NODE_PLANNINGS);

        foreach ($planning_short_access_set as $planning_short_access) {
            /* @var $planning Planning */
            $planning = $planning_short_access->getPlanning();

            $planning_name              = $planning->getName();
            $planning_title             = $planning->getPlanTitle();
            $planning_item_tracker      = $this->getFormattedTrackerId($planning->getPlanningTrackerId());
            $planning_backlog_title     = $planning->getBacklogTitle();
            $planning_milestone_tracker = $this->getFormattedTrackerId(
                $planning_short_access->getCurrentMilestone()->getTrackerId()
            );

            $planning_node = $plannings_node->addChild(self::NODE_PLANNING);

            $planning_node->addAttribute(self::ATTRIBUTE_PLANNING_NAME, $planning_name);
            $planning_node->addAttribute(self::ATTRIBUTE_PLANNING_TITLE, $planning_title);
            $planning_node->addAttribute(self::ATTRIBUTE_PLANNING_ITEM_TRACKER_ID, $planning_item_tracker);
            $planning_node->addAttribute(self::ATTRIBUTE_PLANNING_BACKLOG_TITLE, $planning_backlog_title);
            $planning_node->addAttribute(self::ATTRIBUTE_PLANNING_MILESTONE_TRACKER_ID, $planning_milestone_tracker);
        }
    }

    /**
     *
     * @param int $tracker_id
     * @return string
     */
    private function getFormattedTrackerId($tracker_id) {
        return self::TRACKER_ID_PREFIX . (string) $tracker_id ;
    }


}
?>
