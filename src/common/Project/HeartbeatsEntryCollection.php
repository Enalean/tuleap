<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Project;

use PFUser;
use Project;
use Tuleap\Event\Dispatchable;

class HeartbeatsEntryCollection implements Dispatchable
{
    public const NAME = 'collectHeartbeatsEntries';

    public const NB_MAX_ENTRIES = 30;

    /**
     * @var HeartbeatsEntry[]
     */
    private $entries = [];

    /**
     * @var Project
     */
    private $project;

    /**
     * @var PFUser
     */
    private $user;

    /**
     * @var bool
     */
    private $are_there_activities_user_cannot_see;

    public function __construct(Project $project, PFUser $user)
    {
        $this->project = $project;
        $this->user    = $user;

        $this->are_there_activities_user_cannot_see = false;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return PFUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return HeartbeatsEntry[]
     */
    public function getLatestEntries()
    {
        usort($this->entries, function (HeartbeatsEntry $a, HeartbeatsEntry $b) {
            return $b->getUpdatedAt() - $a->getUpdatedAt();
        });

        return $this->entries;
    }

    public function add(HeartbeatsEntry $entry)
    {
        $this->entries[] = $entry;
    }

    public function thereAreActivitiesUserCannotSee()
    {
        $this->are_there_activities_user_cannot_see = true;
    }

    /**
     * @return bool
     */
    public function areThereActivitiesUserCannotSee()
    {
        return $this->are_there_activities_user_cannot_see;
    }
}
