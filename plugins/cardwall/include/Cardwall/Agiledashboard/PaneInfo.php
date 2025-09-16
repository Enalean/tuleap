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

namespace Tuleap\Cardwall\Agiledashboard;

use Tuleap\Tracker\Milestone\PaneInfo;

class CardwallPaneInfo extends PaneInfo
{
    public const string IDENTIFIER = 'cardwall';

    public function __construct(private int $project_id, private int $planning_id, private int $artifact_id)
    {
        parent::__construct();
    }

    /**
     * @see AgileDashboard_Pane::getIdentifier()
     */
    #[\Override]
    public function getIdentifier()
    {
        return self::IDENTIFIER;
    }

    /**
     * @see AgileDashboard_Pane::getTitle()
     */
    #[\Override]
    public function getTitle()
    {
        return 'Card Wall';
    }

    #[\Override]
    public function getIconName(): string
    {
        return 'fa-solid fa-table';
    }

    #[\Override]
    public function getUri(): string
    {
        return '/plugins/agiledashboard/?' .
               http_build_query(
                   [
                       'group_id'    => $this->project_id,
                       'planning_id' => $this->planning_id,
                       'action'      => $this->action,
                       'aid'         => $this->artifact_id,
                       'pane'        => $this->getIdentifier(),
                   ]
               );
    }
}
