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
    /**
     * @var TrackerPrivateCommentUGroupPermissionDao
     */
    private $private_comment_permission_dao;

    public function __construct(TrackerPrivateCommentUGroupPermissionDao $private_comment_permission_dao)
    {
        $this->private_comment_permission_dao = $private_comment_permission_dao;
    }

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

        $ugroups_id = $this->private_comment_permission_dao->getUgroupIdsOfPrivateComment((int) $comment->id);

        if (count($ugroups_id) === 0) {
            return true;
        }

        foreach ($ugroups_id as $ugroup_id) {
            if ($user->isMemberOfUGroup($ugroup_id, $project_id)) {
                return true;
            }
        }

        return false;
    }
}
