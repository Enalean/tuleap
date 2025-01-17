<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
use Tuleap\Tracker\Artifact\View\TrackerArtifactView;

final readonly class ArtifactView extends TrackerArtifactView
{
    public function __construct(private Planning_Milestone $milestone, Codendi_Request $request, PFUser $user)
    {
        parent::__construct($milestone->getArtifact(), $request, $user);
    }

    /** @see TrackerArtifactView::getTitle() */
    public function getTitle(): string
    {
        return dgettext('tuleap-agiledashboard', 'Milestone')
            . ' <i class="fas fa-external-link-alt"></i>';
    }

    /** @see TrackerArtifactView::getIdentifier() */
    public function getIdentifier(): string
    {
        return 'milestone';
    }

    /** @see TrackerArtifactView::fetch() */
    public function fetch(): string
    {
        // Nothing to fetch as the tab is a redirect to the milestone
        return '';
    }

    public function getURL(): string
    {
        return AGILEDASHBOARD_BASE_URL . '/?' . http_build_query(
            [
                'group_id'    => $this->milestone->getGroupId(),
                'planning_id' => $this->milestone->getPlanningId(),
                'action'      => 'show',
                'aid'         => $this->milestone->getArtifactId(),
            ]
        );
    }
}
