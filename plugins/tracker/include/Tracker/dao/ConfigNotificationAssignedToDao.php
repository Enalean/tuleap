<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
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

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface;

class ConfigNotificationAssignedToDao extends DataAccessObject
{
    public function __construct(LegacyDataAccessInterface $da = null)
    {
        parent::__construct($da);
        $this->enableExceptionsOnError();
    }

    public function searchConfigurationAssignedTo($tracker_id)
    {
        $tracker_id = $this->getDa()->escapeInt($tracker_id);

        $sql = 'SELECT * FROM plugin_tracker_notification_assigned_to WHERE tracker_id = ' . $tracker_id;

        return $this->retrieve($sql);
    }

    public function create($tracker_id)
    {
        $tracker_id = $this->getDa()->escapeInt($tracker_id);

        $sql = "INSERT INTO plugin_tracker_notification_assigned_to(tracker_id) VALUES ($tracker_id)
                ON DUPLICATE KEY UPDATE tracker_id = tracker_id";

        $this->update($sql);
    }

    public function delete($tracker_id)
    {
        $tracker_id = $this->getDa()->escapeInt($tracker_id);

        $sql = 'DELETE FROM plugin_tracker_notification_assigned_to WHERE tracker_id = ' . $tracker_id;

        $this->update($sql);
    }
}
