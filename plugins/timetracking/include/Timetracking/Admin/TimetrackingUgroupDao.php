<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use Tuleap\DB\DataAccessObject;

class TimetrackingUgroupDao extends DataAccessObject
{
    public function saveWriters($tracker_id, array $ugroup_ids): void
    {
        $this->getDB()->tryFlatTransaction(function () use ($tracker_id, $ugroup_ids) {
            $this->deleteWritersForTracker($tracker_id);
            $this->addWritersForTracker($tracker_id, $ugroup_ids);
        });
    }

    public function deleteWritersForTracker($tracker_id): void
    {
        $sql = 'DELETE FROM plugin_timetracking_writers
                WHERE tracker_id = ?';

        $this->getDB()->run($sql, $tracker_id);
    }

    private function addWritersForTracker($tracker_id, array $ugroup_ids): void
    {
        $data_to_insert = [];
        foreach ($ugroup_ids as $ugroup_id) {
            $data_to_insert[] = ['tracker_id' => $tracker_id, 'ugroup_id' => $ugroup_id];
        }

        $this->getDB()->insertMany('plugin_timetracking_writers', $data_to_insert);
    }

    public function saveReaders($tracker_id, array $ugroup_ids): void
    {
        $this->getDB()->tryFlatTransaction(function () use ($tracker_id, $ugroup_ids) {
            $this->deleteReadersForTracker($tracker_id);
            $this->addReadersForTracker($tracker_id, $ugroup_ids);
        });
    }

    public function deleteReadersForTracker($tracker_id): void
    {
        $sql = 'DELETE FROM plugin_timetracking_readers
                WHERE tracker_id = ?';

        $this->getDB()->run($sql, $tracker_id);
    }

    private function addReadersForTracker($tracker_id, array $ugroup_ids): void
    {
        $data_to_insert = [];
        foreach ($ugroup_ids as $ugroup_id) {
            $data_to_insert[] = ['tracker_id' => $tracker_id, 'ugroup_id' => $ugroup_id];
        }

        $this->getDB()->insertMany('plugin_timetracking_readers', $data_to_insert);
    }

    public function getWriters($tracker_id)
    {
        $sql = 'SELECT ugroup_id
                FROM plugin_timetracking_writers
                WHERE tracker_id = ?';

        return $this->getDB()->run($sql, $tracker_id);
    }

    public function getReaders($tracker_id)
    {
        $sql = 'SELECT ugroup_id
                FROM plugin_timetracking_readers
                WHERE tracker_id = ?';

        return $this->getDB()->run($sql, $tracker_id);
    }

    public function deleteByUgroupId($ugroup_id)
    {
        $sql = 'DELETE plugin_timetracking_writers, plugin_timetracking_readers
                FROM plugin_timetracking_writers
                  INNER JOIN plugin_timetracking_readers USING (ugroup_id)
                WHERE ugroup_id = ?';

        return $this->getDB()->run($sql, $ugroup_id);
    }
}
