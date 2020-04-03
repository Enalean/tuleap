<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Planning\XML;

use AgileDashboard_XMLExporterUnableToGetValueException;
use Planning;
use PlanningParameters;
use PlanningPermissionsManager;
use SimpleXMLElement;

class XMLExporter
{
    public const NODE_PLANNINGS    = 'plannings';
    public const NODE_PLANNING     = 'planning';
    public const NODE_BACKLOGS     = 'backlogs';
    private const NODE_BACKLOG     = 'backlog';
    private const NODE_PERMISSIONS = 'permissions';
    private const NODE_PERMISSION  = 'permission';

    /**
     * @todo move me to tracker class
     */
    public const TRACKER_ID_PREFIX = 'T';

    /**
     * @var PlanningPermissionsManager
     */
    private $planning_permissions_manager;

    public function __construct(
        PlanningPermissionsManager $planning_permissions_manager
    ) {
        $this->planning_permissions_manager  = $planning_permissions_manager;
    }

    /**
     * @throws AgileDashboard_XMLExporterUnableToGetValueException
     */
    public function exportPlannings(SimpleXMLElement $agiledashboard_node, array $plannings): void
    {
        $plannings_node = $agiledashboard_node->addChild(self::NODE_PLANNINGS);
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
    }

    /**
     * @throws AgileDashboard_XMLExporterUnableToGetValueException
     */
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
                    } else {
                        $permission_nodes = $planning_node->permissions;
                    }

                    $permission_node = $permission_nodes->addChild(self::NODE_PERMISSION);
                    $permission_node->addAttribute('ugroup', $ugroup);
                    $permission_node->addAttribute('type', PlanningPermissionsManager::PERM_PRIORITY_CHANGE);

                    unset($permission_node);
                }
            }
        }
    }

    private function getFormattedTrackerId($tracker_id): string
    {
        if (! $tracker_id) {
            return self::TRACKER_ID_PREFIX;
        }
        return self::TRACKER_ID_PREFIX . $tracker_id;
    }

    /**
     * @throws AgileDashboard_XMLExporterUnableToGetValueException
     */
    private function checkString($value, $value_denomination): void
    {
        if (! $value ||  (is_string($value) && $value == '')) {
            throw new AgileDashboard_XMLExporterUnableToGetValueException('Unable to get value for attribute: ' . $value_denomination);
        }
    }

    /**
     * @throws AgileDashboard_XMLExporterUnableToGetValueException
     */
    private function checkId($id, $value_denomination): void
    {
        if ($id == self::TRACKER_ID_PREFIX) {
            throw new AgileDashboard_XMLExporterUnableToGetValueException('Unable to get value for attribute: ' . $value_denomination);
        }
    }
}
