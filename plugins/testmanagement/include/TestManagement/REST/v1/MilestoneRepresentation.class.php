<?php
/**
 * Copyright (c) Enalean, 2013-2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\TestManagement\REST\v1;

use Tuleap\AgileDashboard\Milestone\Pane\Details\DetailsPaneInfo;
use Tuleap\REST\JsonCast;

/**
 * Minimal representation of a milestone
 */
class MilestoneRepresentation
{
    /** @var int */
    public $id;

    /** @var String */
    public $label;

    /** @var String */
    public $last_modified;

    /** @var String */
    public $uri;

    public function __construct(\Planning_Milestone $milestone)
    {
        $this->id    = JsonCast::toInt($milestone->getArtifactId());
        $this->label = $milestone->getArtifactTitle() ?? '';
        $this->uri   = AGILEDASHBOARD_BASE_URL . '/?' .
            http_build_query(
                [
                    'pane'        => DetailsPaneInfo::IDENTIFIER,
                    'action'      => 'show',
                    'group_id'    => $milestone->getGroupId(),
                    'planning_id' => $milestone->getPlanningId(),
                    'aid'         => $this->id
                ]
            );
    }
}
