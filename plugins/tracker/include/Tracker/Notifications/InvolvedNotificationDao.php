<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Notifications;

use Tuleap\DB\DataAccessObject;

class InvolvedNotificationDao extends DataAccessObject
{
    public function doesUserIdHaveSubscribeForInvolvedNotification(int $user_id, int $tracker_id)
    {
        $sql = 'SELECT COUNT(*)
                  FROM plugin_tracker_involved_notification_subscribers
                  WHERE user_id = ?
                    AND tracker_id = ?';

        return $this->getDB()->exists($sql, $user_id, $tracker_id);
    }
}
