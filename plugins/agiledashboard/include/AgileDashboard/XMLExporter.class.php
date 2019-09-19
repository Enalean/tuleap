<?php
/**
 * Copyright (c) Enalean, 2013 - 2016. All rights reserved
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

class AgileDashboard_XMLExporter
{

    /**  @var XML_RNGValidator */
    private $xml_validator;

    /**  @var PlanningPermissionsManager */
    private $planning_permissions_manager;

    public const NODE_AGILEDASHBOARD = 'agiledashboard';
    public const NODE_PLANNINGS      = 'plannings';
    public const NODE_PLANNING       = 'planning';
    public const NODE_BACKLOGS       = 'backlogs';
    public const NODE_BACKLOG        = 'backlog';
    public const NODE_PERMISSIONS    = 'permissions';
    public const NODE_PERMISSION     = 'permission';

    /**
     * @todo move me to tracker class
     */
    public const TRACKER_ID_PREFIX = 'T';

    public function __construct(XML_RNGValidator $xml_validator, PlanningPermissionsManager $planning_permissions_manager)
    {
        $this->xml_validator                = $xml_validator;
        $this->planning_permissions_manager = $planning_permissions_manager;
    }

    /**
     *
     * @param SimpleXMLElement $xml_element
     * @param array $plannings
     *
     * @throws AgileDashboard_XMLExporterUnableToGetValueException
     */
    public function export(SimpleXMLElement $xml_element, array $plannings)
    {
        $agiledashboard_node = $xml_element->addChild(self::NODE_AGILEDASHBOARD);
        $plannings_node      = $agiledashboard_node->addChild(self::NODE_PLANNINGS);

        foreach ($plannings as $planning) {
            /** @var Planning $planning */
            $planning_name                  = $planning->getName();
            $planning_title                 = $planning->getPlanTitle();
            $planning_tracker_id            = $this->getFormattedTrackerId($planning->getPlanningTrackerId());
            $planning_backlog_title         = $planning->getBacklogTitle();

            $this->checkString($planning_name, PlanningParameters::NAME);
            $this->checkString($planning_title, PlanningParameters::PLANNING_TITLE);
            $this->checkString($planning_backlog_title, PlanningParameters::BACKLOG_TITLE);

            $this->checkId($planning_tracker_id, PlanningParameters::PLANNING_TRACKER_ID);

            $planning_node = $plannings_node->addChild(self::NODE_PLANNING);

            $planning_node->addAttribute(PlanningParameters::NAME, $planning_name);
            $planning_node->addAttribute(PlanningParameters::PLANNING_TITLE, $planning_title);
            $planning_node->addAttribute(PlanningParameters::PLANNING_TRACKER_ID, $planning_tracker_id);
            $planning_node->addAttribute(PlanningParameters::BACKLOG_TITLE, $planning_backlog_title);

            $this->exportBacklogTrackers($planning_node, $planning);
            $this->exportPermissions($planning_node, $planning);
        }

        $rng_path = realpath(AGILEDASHBOARD_BASE_DIR.'/../www/resources/xml_project_agiledashboard.rng');
        $this->xml_validator->validate($agiledashboard_node, $rng_path);
    }

    private function exportBacklogTrackers(SimpleXMLElement $planning_node, Planning $planning)
    {
        $backlog_nodes = $planning_node->addChild(self::NODE_BACKLOGS);
        foreach ($planning->getBacklogTrackers() as $backlog_tracker) {
            $planning_backlog_tracker_id    = $this->getFormattedTrackerId($backlog_tracker->getId());
            $this->checkId($planning_backlog_tracker_id, self::NODE_BACKLOG);
            $backlog_nodes->addChild(self::NODE_BACKLOG, $this->getFormattedTrackerId($backlog_tracker->getId()));
        }
    }

    private function exportPermissions(SimpleXMLElement $planning_node, Planning $planning)
    {
        $ugroups = $this->planning_permissions_manager->getGroupIdsWhoHasPermissionOnPlanning(
            $planning->getId(),
            $planning->getGroupId(),
            PlanningPermissionsManager::PERM_PRIORITY_CHANGE
        );

        if (! empty($ugroups)) {
            foreach ($ugroups as $ugroup_id) {
                if (($ugroup = array_search($ugroup_id, $GLOBALS['UGROUPS'])) !== false && $ugroup_id < 100) {
                    if (! isset($planning_node->permissions)) {
                        $permission_nodes = $planning_node->addChild(self::NODE_PERMISSIONS);
                    }

                    $permission_node = $permission_nodes->addChild(self::NODE_PERMISSION);
                    $permission_node->addAttribute('ugroup', $ugroup);
                    $permission_node->addAttribute('type', PlanningPermissionsManager::PERM_PRIORITY_CHANGE);

                    unset($permission_node);
                }
            }
        }
    }

    /**
     *
     * @param int $tracker_id
     * @return string
     */
    private function getFormattedTrackerId($tracker_id)
    {
        return self::TRACKER_ID_PREFIX . (string) $tracker_id ;
    }

    private function checkString($value, $value_denomination)
    {
        if (! $value ||  (is_string($value) && $value == '')) {
            throw new AgileDashboard_XMLExporterUnableToGetValueException('Unable to get value for attribute: ' . $value_denomination);
        }
    }

    private function checkId($id, $value_denomination)
    {
        if ($id == self::TRACKER_ID_PREFIX) {
            throw new AgileDashboard_XMLExporterUnableToGetValueException('Unable to get value for attribute: ' . $value_denomination);
        }
    }
}
