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

use PFUser;
use Tracker_Artifact_Changeset_Comment;
use Tuleap\Tracker\Tracker;

class PermissionChecker
{
    /**
     * @var RetrieveTrackerPrivateCommentInformation
     */
    private $tracker_private_comment_information_retriever;

    public function __construct(RetrieveTrackerPrivateCommentInformation $tracker_private_comment_information_retriever)
    {
        $this->tracker_private_comment_information_retriever = $tracker_private_comment_information_retriever;
    }

    public function isPrivateCommentForUser(
        \PFUser $user,
        Tracker_Artifact_Changeset_Comment $comment,
    ): bool {
        $tracker = $comment->getChangeset()->getTracker();
        if (! $this->privateCheckMustBeDoneForUser($user, $tracker)) {
            return false;
        }

        $ugroups = $comment->getUgroupsCanSeePrivateComment();

        if ($ugroups === null) {
            return false;
        }

        if (count($ugroups) === 0) {
            return true;
        }

        $project_id = (int) $tracker->getGroupId();
        foreach ($ugroups as $ugroup) {
            if ($user->isMemberOfUGroup($ugroup->getId(), $project_id)) {
                return false;
            }
        }

        return true;
    }

    public function privateCheckMustBeDoneForUser(PFUser $user, Tracker $tracker): bool
    {
        if (! $this->tracker_private_comment_information_retriever->doesTrackerAllowPrivateComments($tracker)) {
            return false;
        }

        if ($user->isSuperUser()) {
            return false;
        }

        $project_id = (int) $tracker->getGroupId();

        if ($user->isAdmin($project_id)) {
            return false;
        }

        if ($tracker->userIsAdmin($user)) {
            return false;
        }

        return true;
    }

    /**
     * @return \ProjectUGroup[]|UserIsNotAllowedToSeeUGroups
     */
    public function getUgroupsThatUserCanSeeOnComment(
        \PFUser $user,
        Tracker_Artifact_Changeset_Comment $comment,
    ) {
        $all_ugroups = $comment->getUgroupsCanSeePrivateComment();
        if ($all_ugroups === null) {
            return new UserIsNotAllowedToSeeUGroups();
        }

        if (count($all_ugroups) === 0) {
            return new UserIsNotAllowedToSeeUGroups();
        }

        $tracker    = $comment->getChangeset()->getTracker();
        $project_id = (int) $tracker->getGroupId();

        if ($user->isSuperUser() || $user->isAdmin($project_id) || $tracker->userIsAdmin($user)) {
            return $all_ugroups;
        }

        $ugroups_user_can_see = [];

        foreach ($all_ugroups as $ugroup) {
            if ($user->isMemberOfUGroup($ugroup->getId(), $project_id)) {
                $ugroups_user_can_see[] = $ugroup;
            }
        }

        return count($ugroups_user_can_see) === 0 ? new UserIsNotAllowedToSeeUGroups() : $ugroups_user_can_see;
    }
}
