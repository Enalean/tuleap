<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\Timetracking\Admin;

use DataAccess;
use \DataAccessObject;
use Exception;

class TimetrackingUgroupDao extends DataAccessObject
{

    public function __construct(DataAccess $da = null)
    {
        parent::__construct($da);

        $this->enableExceptionsOnError();
    }

    public function saveWriters($tracker_id, array $ugroup_ids)
    {
        $this->startTransaction();

        try {
            $this->deleteWritersForTracker($tracker_id);
            $this->addWritersForTracker($tracker_id, $ugroup_ids);

            $this->commit();
            return true;
        } catch (Exception $exception) {
            $this->rollBack();
            return false;
        }
    }

    public function deleteWritersForTracker($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "DELETE FROM plugin_timetracking_writers
                WHERE tracker_id = $tracker_id";

        return $this->update($sql);
    }

    private function addWritersForTracker($tracker_id, array $ugroup_ids)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $values = array();
        foreach ($ugroup_ids as $ugroup_id) {
            $ugroup_id = $this->da->escapeInt($ugroup_id);

            $values[] = "($tracker_id, $ugroup_id)";
        }

        $value_statement = implode(', ', $values);

        $sql = "INSERT INTO plugin_timetracking_writers (tracker_id, ugroup_id)
                VALUES $value_statement";

        return $this->update($sql);
    }

    public function saveReaders($tracker_id, array $ugroup_ids)
    {
        $this->startTransaction();

        try {
            $this->deleteReadersForTracker($tracker_id);
            $this->addReadersForTracker($tracker_id, $ugroup_ids);

            $this->commit();
            return true;
        } catch (Exception $exception) {
            $this->rollBack();
            return false;
        }
    }

    public function deleteReadersForTracker($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "DELETE FROM plugin_timetracking_readers
                WHERE tracker_id = $tracker_id";

        return $this->update($sql);
    }

    private function addReadersForTracker($tracker_id, array $ugroup_ids)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $values = array();
        foreach ($ugroup_ids as $ugroup_id) {
            $ugroup_id = $this->da->escapeInt($ugroup_id);

            $values[] = "($tracker_id, $ugroup_id)";
        }

        $value_statement = implode(', ', $values);

        $sql = "INSERT INTO plugin_timetracking_readers (tracker_id, ugroup_id)
                VALUES $value_statement";

        return $this->update($sql);
    }

    public function getWriters($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "SELECT ugroup_id
                FROM plugin_timetracking_writers
                WHERE tracker_id = $tracker_id";

        return $this->retrieve($sql);
    }

    public function getReaders($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "SELECT ugroup_id
                FROM plugin_timetracking_readers
                WHERE tracker_id = $tracker_id";

        return $this->retrieve($sql);
    }

    public function deleteByUgroupId($ugroup_id)
    {
        $ugroup_id = $this->da->escapeInt($ugroup_id);

        $sql = "DELETE plugin_timetracking_writers, plugin_timetracking_readers
                FROM plugin_timetracking_writers
                  INNER JOIN plugin_timetracking_readers USING (ugroup_id)
                WHERE ugroup_id = $ugroup_id";

        return $this->update($sql);
    }
}
