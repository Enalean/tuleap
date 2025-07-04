<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\TrackerDeletion;

use Tuleap\DB\DataAccessObject;

final class DeletedTrackerDao extends DataAccessObject implements RetrieveDeletedTracker, RestoreDeletedTracker
{
    public function retrieveTrackersMarkAsDeleted(): array
    {
        $sql = "SELECT tracker.*
                FROM tracker
                    INNER JOIN `groups` USING (group_id)
                WHERE tracker.deletion_date > 0
                    AND `groups`.status <> 'D'
                ORDER BY tracker.group_id";

        return $this->getDB()->q($sql);
    }

    public function restoreTrackerMarkAsDeleted(int $tracker_id): void
    {
        $sql = 'UPDATE tracker SET
                          deletion_date = NULL
                      WHERE id = ?';
        $this->getDB()->q($sql, $tracker_id);
    }
}
