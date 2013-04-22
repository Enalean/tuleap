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

require_once 'common/XmlValidator/XmlValidator.class.php';

class AgileDashboard_XMLExporter {

    /**  @var XmlValidator */
    private $xml_validator;

    const NODE_AGILEDASHBOARD = 'agiledashboard';
    const NODE_PLANNINGS      = 'plannings';
    const NODE_PLANNING       = 'planning';

    /**
     * @todo move me to tracker class
     */
    const TRACKER_ID_PREFIX = 'T';

    public function __construct(XmlValidator $xml_validator) {
        $this->xml_validator   = $xml_validator;
    }

    /**
     *
     * @param SimpleXMLElement $xml_element
     * @param array $plannings
     *
     * @throws AgileDashboard_XMLExporterUnableToGetValueException
     */
    public function export(SimpleXMLElement $xml_element, array $plannings) {
        $agiledashboard_node = $xml_element->addChild(self::NODE_AGILEDASHBOARD);
        $plannings_node      = $agiledashboard_node->addChild(self::NODE_PLANNINGS);

        foreach ($plannings as $planning) {
            /* @var $planning Planning */
            $planning_name                  = $planning->getName();
            $planning_title                 = $planning->getPlanTitle();
            $planning_tracker_id            = $this->getFormattedTrackerId($planning->getPlanningTrackerId());
            $planning_backlog_title         = $planning->getBacklogTitle();
            $planning_backlog_tracker_id    = $this->getFormattedTrackerId($planning->getBacklogTracker()->getId());
            
            $this->checkString($planning_name, PlanningParameters::NAME);
            $this->checkString($planning_title, PlanningParameters::PLANNING_TITLE);
            $this->checkString($planning_backlog_title, PlanningParameters::BACKLOG_TITLE);
            
            $this->checkId($planning_tracker_id, PlanningParameters::PLANNING_TRACKER_ID);
            $this->checkId($planning_backlog_tracker_id, PlanningParameters::BACKLOG_TRACKER_ID);

            $planning_node = $plannings_node->addChild(self::NODE_PLANNING);

            $planning_node->addAttribute(PlanningParameters::NAME, $planning_name);
            $planning_node->addAttribute(PlanningParameters::PLANNING_TITLE, $planning_title);
            $planning_node->addAttribute(PlanningParameters::PLANNING_TRACKER_ID, $planning_tracker_id);
            $planning_node->addAttribute(PlanningParameters::BACKLOG_TITLE, $planning_backlog_title);
            $planning_node->addAttribute(PlanningParameters::BACKLOG_TRACKER_ID, $planning_backlog_tracker_id);
        }

        if (! $this->xml_validator->nodeIsValid(
                 $agiledashboard_node,
                 realpath(dirname(__FILE__).'/../../www/resources/xml_project_agiledashboard.rng')
               )
         ) {
            throw new AgileDashboard_XMLExporterNodeNotValidException();
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

    private function checkString($value, $value_denomination) {
        if (! $value ||  (is_string($value) && $value == '')) {
            throw new AgileDashboard_XMLExporterUnableToGetValueException('Unable to get value for attribute: ' . $value_denomination);
        }
    }

    private function checkId($id, $value_denomination) {
        if ($id == self::TRACKER_ID_PREFIX) {
            throw new AgileDashboard_XMLExporterUnableToGetValueException('Unable to get value for attribute: ' . $value_denomination);
        }
    }
}
?>
