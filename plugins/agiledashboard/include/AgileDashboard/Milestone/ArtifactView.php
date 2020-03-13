<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone;

use Planning_Milestone;
use Codendi_Request;
use PFUser;
use Tracker_Artifact_View_View;

class ArtifactView extends Tracker_Artifact_View_View
{

    /**
     * @var Planning_Milestone
     */
    private $milestone;

    public function __construct(Planning_Milestone $milestone, Codendi_Request $request, PFUser $user)
    {
        parent::__construct($milestone->getArtifact(), $request, $user);

        $this->milestone = $milestone;
    }

    /** @see Tracker_Artifact_View_View::getTitle() */
    public function getTitle()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'Milestone')
            . ' <i class="fa fa-external-link"></i>';
    }

    /** @see Tracker_Artifact_View_View::getIdentifier() */
    public function getIdentifier()
    {
        return "milestone";
    }

    /** @see Tracker_Artifact_View_View::fetch() */
    public function fetch()
    {
        // Nothing to fetch as the tab is a redirect to the milestone
    }

    public function getURL()
    {
        return AGILEDASHBOARD_BASE_URL . '/?' . http_build_query(
            array(
                'group_id'    => $this->milestone->getGroupId(),
                'planning_id' => $this->milestone->getPlanningId(),
                'action'      => 'show',
                'aid'         => $this->milestone->getArtifactId(),
            )
        );
    }
}
