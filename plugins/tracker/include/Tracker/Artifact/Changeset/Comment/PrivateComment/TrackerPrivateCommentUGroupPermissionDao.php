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

use Tuleap\DB\DataAccessObject;

class TrackerPrivateCommentUGroupPermissionDao extends DataAccessObject
{
    /**
     * @return int[]
     */
    public function getUgroupIdsOfPrivateComment(int $comment_id): array
    {
        $sql = 'SELECT ugroup_id FROM plugin_tracker_private_comment_permission
                WHERE comment_id = ?';

        return $this->getDB()->column($sql, [$comment_id]);
    }

    public function insertUGroupPermissionOnPrivateComment(int $comment_id, int $ugroup_id): void
    {
        $sql = 'INSERT INTO plugin_tracker_private_comment_permission (comment_id, ugroup_id) VALUES (?, ?);';

        $this->getDB()->run($sql, $comment_id, $ugroup_id);
    }

    public function deleteUgroupPermissionForPrivateComment(int $ugroup_id): void
    {
        $this->getDB()->delete('plugin_tracker_private_comment_permission', ['ugroup_id' => $ugroup_id]);
    }
}
