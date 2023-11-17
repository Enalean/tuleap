<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\PullRequest\Comment;

use Tuleap\DB\DataAccessObject;

final class ThreadCommentDao extends DataAccessObject implements CountThreads
{
    public function countAllThreadsOfPullRequest(int $id): int
    {
        $sql = "SELECT global.id
                FROM plugin_pullrequest_comments AS global
                WHERE global.pull_request_id = ? AND global.parent_id = 0 AND (global.color != '')
                UNION
                SELECT inline.id
                FROM plugin_pullrequest_inline_comments AS inline
                WHERE inline.pull_request_id = ? AND inline.parent_id = 0 AND (inline.color != '')";

        $rows = $this->getDB()->run($sql, $id, $id);
        return count($rows);
    }
}
