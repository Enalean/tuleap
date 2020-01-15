<?php
/**
 * Copyright (c) Ericsson AB, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Notifications;

use Tuleap\DB\DataAccessObject;

/**
 * This class is responsible for interfacing custom email sender formats with the database
 * */
class ConfigNotificationEmailCustomSenderDao extends DataAccessObject
{

    /**
     * @return array array(array(format, enabled),...)
     * */
    public function searchCustomSender($tracker_id)
    {
        $sql = "SELECT
                    f.format,
                    f.enabled
                FROM plugin_tracker_notification_email_custom_sender_format as f
                WHERE f.tracker_id=?";
        return $this->getDB()->run($sql, $tracker_id);
    }


    public function create($tracker_id, $format, $enabled)
    {
        $sql = "INSERT INTO plugin_tracker_notification_email_custom_sender_format(tracker_id,format,enabled) VALUES (?,?,?)
                ON DUPLICATE KEY UPDATE tracker_id = tracker_id, format = ?, enabled = ?";
        $this->getDB()->run($sql, $tracker_id, $format, $enabled, $format, $enabled);
    }
}
