<?php
/**
 * Copyright (c) STMicroelectronics 2014. All rights reserved
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

use Tuleap\Tracker\Artifact\Artifact;

class Tracker_Permission_PermissionRetrieveAssignee
{
    public function __construct(private readonly \Tuleap\User\RetrieveUserById $user_manager)
    {
    }

    private function getAssigneeIds(Artifact $artifact)
    {
        $contributor_field = $artifact->getTracker()->getContributorField();
        if ($contributor_field) {
            $assignee = $artifact->getValue($contributor_field);
            if ($assignee) {
                return $assignee->getValue();
            }
        }
        return [];
    }

    /**
     * Retrieve users who are assigned to a given artifact
     *
     * @return PFUser[]
     */
    public function getAssignees(Artifact $artifact)
    {
        $user_collection = [];
        foreach ($this->getAssigneeIds($artifact) as $user_id) {
            $user = $this->user_manager->getUserById($user_id);
            if ($user) {
                $user_collection[$user_id] = $user;
            }
        }
        return $user_collection;
    }
}
