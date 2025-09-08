<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

use PlanningParameters;
use PlanningPermissionsManager;
use SimpleXMLElement;

final class XMLPlanning
{
    public const string NODE_PLANNING = 'planning';
    public const string NODE_BACKLOGS = 'backlogs';
    public const string NODE_BACKLOG  = 'backlog';

    private const string NODE_PERMISSIONS = 'permissions';
    private const string NODE_PERMISSION  = 'permission';

    /**
     * @var string
     * @readonly
     */
    private $name;

    /**
     * @var string
     * @readonly
     */
    private $plan_title;

    /**
     * @var string
     * @readonly
     */
    private $planning_tracker_id;

    /**
     * @var string
     * @readonly
     */
    private $backlog_title;

    /**
     * @var array<int|string>
     * @readonly
     */
    private $backlog_tracker_ids;

    /**
     * @var string[]
     * @readonly
     */
    private $priority_change_permissions = [];

    /**
     * @param array<int|string> $backlog_tracker_ids
     */
    public function __construct(
        string $name,
        string $plan_title,
        string $planning_tracker_id,
        string $backlog_title,
        array $backlog_tracker_ids,
    ) {
        $this->name                = $name;
        $this->plan_title          = $plan_title;
        $this->planning_tracker_id = $planning_tracker_id;
        $this->backlog_title       = $backlog_title;
        $this->backlog_tracker_ids = $backlog_tracker_ids;
    }

    /**
     * @psalm-mutation-free
     * @return static
     */
    public function withPriorityChangePermission(string ...$ugroup_name): self
    {
        $new                              = clone $this;
        $new->priority_change_permissions = array_merge($this->priority_change_permissions, $ugroup_name);
        return $new;
    }

    public function export(SimpleXMLElement $plannings_xml): SimpleXMLElement
    {
        $planning_xml = $plannings_xml->addChild(self::NODE_PLANNING);

        $planning_xml->addAttribute(PlanningParameters::NAME, $this->name);
        $planning_xml->addAttribute(PlanningParameters::PLANNING_TITLE, $this->plan_title);
        $planning_xml->addAttribute(PlanningParameters::PLANNING_TRACKER_ID, $this->planning_tracker_id);
        $planning_xml->addAttribute(PlanningParameters::BACKLOG_TITLE, $this->backlog_title);

        $backlogs_xml = $planning_xml->addChild(self::NODE_BACKLOGS);
        foreach ($this->backlog_tracker_ids as $backlog_tracker_id) {
            $backlogs_xml->addChild(self::NODE_BACKLOG, (string) $backlog_tracker_id);
        }

        if (count($this->priority_change_permissions) > 0) {
            $xml_permissions = $planning_xml->addChild(self::NODE_PERMISSIONS);
            foreach ($this->priority_change_permissions as $permission) {
                $xml_permission = $xml_permissions->addChild(self::NODE_PERMISSION);
                $xml_permission->addAttribute('ugroup', $permission);
                $xml_permission->addAttribute('type', PlanningPermissionsManager::PERM_PRIORITY_CHANGE);
            }
        }

        return $planning_xml;
    }
}
