<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment;

use Tracker_Artifact_Changeset_Comment;

class PermissionChecker
{
    public function userCanSeeComment(
        \PFUser $user,
        Tracker_Artifact_Changeset_Comment $comment
    ): bool {
        if ($user->isSuperUser()) {
            return true;
        }

        $tracker    = $comment->getChangeset()->getTracker();
        $project_id = (int) $tracker->getGroupId();

        if ($user->isAdmin($project_id)) {
            return true;
        }

        if ($tracker->userIsAdmin($user)) {
            return true;
        }

        $ugroups = $comment->getUgroupsCanSeePrivateComment();

        if ($ugroups === null) {
            return true;
        }

        if (count($ugroups) === 0) {
            return false;
        }

        foreach ($ugroups as $ugroup) {
            if ($user->isMemberOfUGroup($ugroup->getId(), $project_id)) {
                return true;
            }
        }

        return false;
    }
}
