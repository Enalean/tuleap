<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Notifications;

use Tuleap\DB\DataAccessObject;

final class GlobalNotificationDuplicationDao extends DataAccessObject
{
    public function getByTrackerId(int $tracker_id): array
    {
        return $this->getDB()->column('SELECT id FROM tracker_global_notification WHERE tracker_id = ?', [$tracker_id]);
    }

    public function duplicate(int $from_notification_id, int $to_tracker_id): int
    {
        $sql = 'INSERT INTO tracker_global_notification (tracker_id, addresses, all_updates, check_permissions)
                SELECT ?, addresses, all_updates, check_permissions
                FROM tracker_global_notification
                WHERE id = ?';
        $db  = $this->getDB();
        $db->run($sql, $to_tracker_id, $from_notification_id);
        return (int) $db->lastInsertId();
    }
}
