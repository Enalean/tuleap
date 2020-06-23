<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);


namespace Tuleap\TestManagement;

use Tuleap\AgileDashboard\Milestone\Pane\Details\DetailsPaneInfo;
use Tuleap\Event\Dispatchable;

class GetURIForMilestoneFromTTM implements Dispatchable
{
    public const NAME = 'getURIForMilestoneFromTTM';

    /**
     * @var \Planning_Milestone
     */
    private $milestone;

    /**
     * @var string
     */
    private $uri = "";
    /**
     * @var \PFUser
     */
    private $current_user;

    public function __construct(\Planning_Milestone $milestone, \PFUser $current_user)
    {
        $this->milestone    = $milestone;
        $this->current_user = $current_user;
    }

    public function getMilestone(): \Planning_Milestone
    {
        return $this->milestone;
    }

    public function setURI(string $uri): void
    {
        $this->uri = $uri;
    }

    public function getURI(): string
    {
        if ($this->uri) {
            return $this->uri;
        }

        return AGILEDASHBOARD_BASE_URL . '/?' .
            http_build_query(
                [
                    'pane'        => DetailsPaneInfo::IDENTIFIER,
                    'action'      => 'show',
                    'group_id'    => $this->milestone->getGroupId(),
                    'planning_id' => $this->milestone->getPlanningId(),
                    'aid'         => $this->milestone->getArtifactId(),
                ]
            );
    }

    public function getCurrentUser(): \PFUser
    {
        return $this->current_user;
    }
}
