<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Project\REST;

use Tuleap\Project\HeartbeatsEntry;
use Tuleap\Project\HeartbeatsEntryCollection;

class HeartbeatsRepresentation
{
    /**
     * @var HeartbeatsEntryRepresentation[]
     */
    public $entries;

    /**
     * @var bool
     */
    public $are_there_activities_user_cannot_see;

    public function build(HeartbeatsEntryCollection $heartbeats)
    {
        $this->entries = array();
        foreach ($heartbeats->getLatestEntries() as $entry) {
            $representation = new HeartbeatsEntryRepresentation();
            $representation->build($entry);

            $this->entries[] = $representation;
        }

        $this->are_there_activities_user_cannot_see = $heartbeats->areThereActivitiesUserCannotSee();
    }
}
