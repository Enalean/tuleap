<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Pane\Planning;

use Override;
use Planning_Milestone;
use Tuleap\Tracker\Milestone\PaneInfo;
use Tuleap\Tracker\Tracker;

class PlanningV2PaneInfo extends PaneInfo
{
    public const IDENTIFIER = 'planning-v2';

    /** @var Tracker */
    private $submilestone_tracker;

    public function __construct(private Planning_Milestone $milestone, Tracker $submilestone_tracker)
    {
        parent::__construct();
        $this->submilestone_tracker = $submilestone_tracker;
    }

    #[Override]
    public function getIdentifier()
    {
        return self::IDENTIFIER;
    }

    #[Override]
    public function getTitle()
    {
        return sprintf(dgettext('tuleap-agiledashboard', '%1$s Planning'), $this->submilestone_tracker->getName());
    }

    #[Override]
    public function getIconName(): string
    {
        return 'fa-solid fa-right-to-bracket';
    }

    #[Override]
    public function getUri(): string
    {
        return AGILEDASHBOARD_BASE_URL . '/?' .
               http_build_query(
                   [
                       'group_id'    => $this->milestone->getGroupId(),
                       'planning_id' => $this->milestone->getPlanningId(),
                       'action'      => $this->action,
                       'aid'         => $this->milestone->getArtifactId(),
                       'pane'        => $this->getIdentifier(),
                   ]
               );
    }
}
