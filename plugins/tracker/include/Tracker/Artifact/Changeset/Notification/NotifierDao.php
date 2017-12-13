<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\Notification;

use DataAccessObject;

class NotifierDao extends DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->enableExceptionsOnError();
    }

    public function addNewNotification($changeset_id)
    {
        $changeset_id = (int) $changeset_id;

        $sql = "INSERT INTO tracker_email_notification_log(changeset_id, create_date)
                VALUES($changeset_id, UNIX_TIMESTAMP())";
        $this->update($sql);
    }

    public function addStartDate($changeset_id)
    {
        $changeset_id = (int) $changeset_id;

        $sql = "UPDATE tracker_email_notification_log SET start_date = UNIX_TIMESTAMP() WHERE changeset_id = $changeset_id";
        $this->update($sql);
    }

    public function addEndDate($changeset_id)
    {
        $changeset_id = (int) $changeset_id;

        $sql = "UPDATE tracker_email_notification_log SET end_date = UNIX_TIMESTAMP() WHERE changeset_id = $changeset_id";
        $this->update($sql);
    }

    public function getLastEndDate()
    {
        $sql = "SELECT MAX(end_date) AS max
          FROM tracker_email_notification_log";
        $row = $this->retrieveFirstRow($sql);
        return (int) $row['max'];
    }

    public function searchPendingNotificationsAfter($create_date)
    {
        $create_date = (int) $create_date;
        $sql = "SELECT count(*) as nb FROM tracker_email_notification_log WHERE start_date IS NULL AND create_date > $create_date";
        $row = $this->retrieveFirstRow($sql);
        return (int) $row['nb'];
    }
}
