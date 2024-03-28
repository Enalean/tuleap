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

class TrackerPrivateCommentUGroupEnabledDao extends DataAccessObject
{
    public function disabledPrivateCommentOnTracker(int $tracker_id): void
    {
        $sql = 'INSERT INTO plugin_tracker_private_comment_disabled_tracker (tracker_id) VALUES (?);';

        $this->getDB()->run($sql, $tracker_id);
    }

    public function isTrackerEnabledPrivateComment(int $tracker_id): bool
    {
        $statement = 'SELECT TRUE FROM plugin_tracker_private_comment_disabled_tracker WHERE tracker_id = ?';
        $row       = $this->getDB()->run($statement, $tracker_id);

        return count($row) === 0;
    }
}
