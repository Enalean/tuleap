<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation;

use Tuleap\DB\DataAccessObject;

class ActionsRunnerDao extends DataAccessObject
{
    public function addNewPostCreationEvent($changeset_id)
    {
        $sql = 'INSERT INTO tracker_post_creation_event_log(changeset_id, create_date)
                VALUES(?, UNIX_TIMESTAMP())';

        $this->getDB()->run($sql, $changeset_id);
    }

    public function addStartDate($changeset_id)
    {
        $sql = 'UPDATE tracker_post_creation_event_log SET start_date = UNIX_TIMESTAMP() WHERE changeset_id = ?';

        $this->getDB()->run($sql, $changeset_id);
    }

    public function addEndDate($changeset_id)
    {
        $sql = 'UPDATE tracker_post_creation_event_log SET end_date = UNIX_TIMESTAMP() WHERE changeset_id = ?';

        $this->getDB()->run($sql, $changeset_id);
    }

    public function getLastEndDate()
    {
        $sql = 'SELECT MAX(end_date) AS max
          FROM tracker_post_creation_event_log';

        return $this->getDB()->single($sql);
    }

    public function searchPostCreationEventsAfter($create_date)
    {
        $sql = 'SELECT count(*) as nb FROM tracker_post_creation_event_log WHERE start_date IS NULL AND create_date > ?';

        return $this->getDB()->single($sql, $create_date);
    }

    public function deleteLogsOlderThan($delay)
    {
        $sql = 'DELETE FROM tracker_post_creation_event_log WHERE end_date IS NOT NULL AND end_date < UNIX_TIMESTAMP() - ?';

        return $this->getDB()->run($sql, $delay);
    }
}
