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

use Tuleap\Tracker\Tracker;

class TrackerPrivateCommentUGroupPermissionRetriever
{
    /**
     * @var TrackerPrivateCommentUGroupPermissionDao
     */
    private $ugroup_permission_dao;
    /**
     * @var RetrieveTrackerPrivateCommentInformation
     */
    private $tracker_private_comment_information_retriever;
    /**
     * @var \UGroupManager
     */
    private $ugroup_manager;

    public function __construct(
        TrackerPrivateCommentUGroupPermissionDao $ugroup_permission_dao,
        RetrieveTrackerPrivateCommentInformation $tracker_private_comment_information_retriever,
        \UGroupManager $ugroup_manager,
    ) {
        $this->ugroup_permission_dao                         = $ugroup_permission_dao;
        $this->tracker_private_comment_information_retriever = $tracker_private_comment_information_retriever;
        $this->ugroup_manager                                = $ugroup_manager;
    }

    /**
     * @return \ProjectUGroup[]|null
     */
    public function getUGroupsCanSeePrivateComment(Tracker $tracker, int $comment_id): ?array
    {
        if (! $this->tracker_private_comment_information_retriever->doesTrackerAllowPrivateComments($tracker)) {
            return null;
        }

        $ugroups_id = $this->ugroup_permission_dao->getUgroupIdsOfPrivateComment($comment_id);

        if (count($ugroups_id) === 0) {
            return null;
        }

        $ugroups = [];
        foreach ($ugroups_id as $ugroup_id) {
            $ugroups[] = $this->ugroup_manager->getById($ugroup_id);
        }

        return $ugroups;
    }
}
