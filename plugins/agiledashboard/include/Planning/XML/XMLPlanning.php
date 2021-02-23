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
use SimpleXMLElement;

final class XMLPlanning
{
    public const NODE_PLANNING = 'planning';
    public const NODE_BACKLOGS = 'backlogs';
    public const NODE_BACKLOG  = 'backlog';

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
     * @param array<int|string> $backlog_tracker_ids
     */
    public function __construct(
        string $name,
        string $plan_title,
        string $planning_tracker_id,
        string $backlog_title,
        array $backlog_tracker_ids
    ) {
        $this->name                = $name;
        $this->plan_title          = $plan_title;
        $this->planning_tracker_id = $planning_tracker_id;
        $this->backlog_title       = $backlog_title;
        $this->backlog_tracker_ids = $backlog_tracker_ids;
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

        return $planning_xml;
    }
}
