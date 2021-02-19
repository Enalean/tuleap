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

use Tracker;

class TrackerPrivateCommentUGroupPermissionRetriever
{
    /**
     * @var TrackerPrivateCommentUGroupPermissionDao
     */
    private $ugroup_permission_dao;
    /**
     * @var \UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var TrackerPrivateCommentUGroupEnabledDao
     */
    private $ugroup_enabled_dao;

    public function __construct(
        TrackerPrivateCommentUGroupPermissionDao $ugroup_permission_dao,
        TrackerPrivateCommentUGroupEnabledDao $ugroup_enabled_dao,
        \UGroupManager $ugroup_manager
    ) {
        $this->ugroup_permission_dao = $ugroup_permission_dao;
        $this->ugroup_manager        = $ugroup_manager;
        $this->ugroup_enabled_dao    = $ugroup_enabled_dao;
    }

    /**
     * @return \ProjectUGroup[]|null
     */
    public function getUGroupsCanSeePrivateComment(Tracker $tracker, int $comment_id): ?array
    {
        if (! $this->ugroup_enabled_dao->isTrackerEnabledPrivateComment($tracker->getId())) {
            return null;
        }

        $ugroups_id = $this->ugroup_permission_dao->getUgroupIdsOfPrivateComment($comment_id);

        if (count($ugroups_id) === 0) {
            return null;
        }

        $ugroups = [];
        foreach ($ugroups_id as $ugroup_id) {
            $ugroup = $this->ugroup_manager->getById($ugroup_id);
            if ($ugroup !== null) {
                $ugroups[] = $ugroup;
            }
        }

        return $ugroups;
    }
}
