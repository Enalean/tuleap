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

namespace Tuleap\AgileDashboard\Milestone\Pane\Details;

use Planning_Milestone;
use Tuleap\Tracker\Milestone\PaneInfo;

class DetailsPaneInfo extends PaneInfo
{
    public const IDENTIFIER = 'details';

    public function __construct(private Planning_Milestone $milestone)
    {
        parent::__construct();
    }

    /**
     * @return string eg: 'cardwall'
     */
    public function getIdentifier()
    {
        return self::IDENTIFIER;
    }

    /**
     * @return string eg: 'Card Wall'
     */
    public function getTitle()
    {
        return dgettext('tuleap-agiledashboard', 'Overview');
    }

    public function getIconName()
    {
        return 'fa-bar-chart';
    }

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
