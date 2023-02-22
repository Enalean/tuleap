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

final class UsersToNotifyDuplicationDao extends DataAccessObject
{
    public function duplicate(int $source_notification_id, int $target_notification_id): void
    {
        $sql = <<<EOSQL
        INSERT INTO tracker_global_notification_users(notification_id, user_id)
        SELECT ?, user_id
        FROM tracker_global_notification_users
        WHERE notification_id = ?
        EOSQL;
        $this->getDB()->run($sql, $target_notification_id, $source_notification_id);
    }
}
