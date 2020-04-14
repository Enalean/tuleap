<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

class SystemEventDao extends DataAccessObject
{
    public function getElapsedTime(SystemEvent $system_event): int
    {
        $id = $this->da->escapeInt($system_event->getId());
        $sql = "SELECT TIMESTAMPDIFF(SECOND, process_date, end_date) AS seconds
                FROM system_event
                WHERE id = $id
                AND end_date IS NOT NULL";
        $dar = $this->retrieve($sql);
        if ($dar) {
            $row = $dar->getRow();
            return (int) $row['seconds'];
        }
        return -1;
    }

    /**
     * Create new SystemEvent and store it in the DB
     * @return true if there is no error
     */
    public function store($type, $parameters, $priority, $status, $create_date, $owner)
    {
        $sql = sprintf(
            "INSERT INTO system_event (type, parameters, priority, status, create_date, owner) VALUES (%s, %s, %d, %s, FROM_UNIXTIME(%d), %s)",
            $this->da->quoteSmart($type),
            $this->da->quoteSmart($parameters),
            $this->da->escapeInt($priority),
            $this->da->quoteSmart($status),
            $this->da->escapeInt($create_date),
            $this->da->quoteSmart($owner)
        );

        return $this->updateAndGetLastId($sql);
    }

     /**
     * Close SystemEvent: update status, log and end_date.
     * @param $sysevent : SystemEvent object
     * @return true if there is no error
     */
    public function close($sysevent)
    {
        $now = time();
        $sql = sprintf(
            "UPDATE system_event SET status=%s, log=%s, end_date=FROM_UNIXTIME(%d) WHERE id=%d",
            $this->da->quoteSmart($sysevent->getStatus()),
            $this->da->quoteSmart($sysevent->getLog()),
            $this->da->escapeInt($now),
            $this->da->escapeInt($sysevent->getId())
        );
        if ($updated = $this->update($sql)) {
            $sysevent->setEndDate($now);
        }
        return $updated;
    }

    /**
     * Return next system event
     * criteria: higer priority first, then most recent first
     * And set the event status to 'RUNNING'
     * @return DataAccessResult
    */
    public function checkOutNextEvent($owner, $types)
    {
        $owner    = $this->da->quoteSmart($owner);
        $types    = $this->da->quoteSmartImplode(',', $types);

        // Get Id of next event to process
        $sql = "SELECT id FROM system_event
                WHERE status='" . SystemEvent::STATUS_NEW . "'
                    AND owner = $owner
                    AND type IN ($types)
                    ORDER BY priority, create_date LIMIT 1";
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError()) {
            // Mark event as 'RUNNING'
            if ($row = $dar->getRow()) {
                $id = $row['id'];
                $upd_sql = "UPDATE system_event SET status='" . SystemEvent::STATUS_RUNNING . "', process_date=FROM_UNIXTIME(" . time() . ") WHERE id=$id";
                $this->update($upd_sql);
                // Retrieve all event parameters
                $event_sql = "SELECT * FROM system_event WHERE id=$id";
                return $this->retrieve($event_sql);
            }
        }
        return null;
    }

    /** @return bool */
    public function hasThereAnyEventsRunning()
    {
        $status = $this->da->quoteSmart(SystemEvent::STATUS_RUNNING);

        $sql = "SELECT NULL
                FROM system_event
                WHERE status = $status
                LIMIT 1";

        return count($this->retrieve($sql)) > 0;
    }

    /**
     * Search n last status
     */
    public function searchLastEvents($offset, $limit, $filters_status, $filters_type)
    {
        $offset         = $this->da->escapeInt($offset);
        $limit          = $this->da->escapeInt($limit);
        $filters_status = $this->da->quoteSmartImplode(", ", $filters_status);
        $filters_type   = $this->da->quoteSmartImplode(", ", $filters_type);
        $sql = "SELECT SQL_CALC_FOUND_ROWS *
                FROM system_event
                WHERE status IN ($filters_status)
                  AND type   IN ($filters_type)
                ORDER BY id DESC
                LIMIT $offset, $limit";
        return $this->retrieve($sql);
    }

    public function searchQueueStatsForLastDay($types)
    {
        $types = $this->da->quoteSmartImplode(',', $types);

        $sql = "SELECT status, count(*) as nb
                FROM system_event
                WHERE type IN ($types)
                  AND create_date > DATE_SUB(NOW(), INTERVAL 2 day)
                GROUP BY status
                ";

        return $this->retrieve($sql);
    }

    /**
     *
     * The searched parameter may be at one of these positions:
     * $val::someThing (position == head)
     * someThing::$val (position == tail)
     * someThing::$val::someThing (position == middle)
     *
     * @param String $position
     * @param String $val
     * @param Array $type
     * @param Array $status
     * @return DataAccessResult
     */
    public function searchWithParam($position, $val, $type, $status, $separator = SystemEvent::PARAMETER_SEPARATOR)
    {
        if ($position === 'head') {
            $stm = $this->da->quoteLikeValueSuffix($val . $separator);
        } elseif ($position === 'tail') {
            $stm = $this->da->quoteLikeValuePrefix($separator . $val);
        } elseif ($position === 'all') {
             $stm = $this->da->quoteSmart($this->da->escapeLikeValue($val));
        } else {
            $stm = $this->da->quoteLikeValueSurround($separator . $val . $separator);
        }

        $type   = $this->da->quoteSmartImplode(", ", $type);
        $status = $this->da->quoteSmartImplode(", ", $status);

        $sql = 'SELECT  * FROM system_event
                WHERE type   IN (' . $type . ')
                AND status IN (' . $status . ')
                AND parameters LIKE ' . $stm;

        return $this->retrieve($sql);
    }

    /**
     * @return DataAccessResult|false
     */
    public function searchWithTypeAndStatus(array $type, array $status)
    {
        $type   = $this->da->quoteSmartImplode(", ", $type);
        $status = $this->da->quoteSmartImplode(", ", $status);

        $sql = 'SELECT  * FROM system_event
                WHERE type   IN (' . $type . ')
                AND status IN (' . $status . ')';

        return $this->retrieve($sql);
    }

    /**
     * @return DataAccessResult
     */
    public function resetStatus($id, $status)
    {
        $id     = $this->da->escapeInt($id);
        $status = $this->da->quoteSmart($status);
        $sql = "UPDATE system_event
                SET status = $status
                WHERE id = $id";
        return $this->update($sql);
    }

    /**
     * @return DataAccessResult|false array of event id and parameters
     */
    public function searchNewGitRepoUpdateEvents()
    {
        $sql = "SELECT id, parameters FROM system_event
            WHERE status = 'NEW'
            AND type = 'GIT_REPO_UPDATE'";

        return $this->retrieve($sql);
    }

    /**
     * @param array $event_ids
     * @return bool
     */
    public function markAsDone($event_ids)
    {
        $event_ids = $this->da->escapeIntImplode($event_ids);

        $sql = "UPDATE system_event
            SET status = 'DONE',
                log = 'OK',
                end_date = NOW()
            WHERE id IN ($event_ids)";

        return $this->update($sql);
    }

    /**
     * @param array $event_ids
     * @return bool
     */
    public function markAsRunning($event_ids)
    {
        $event_ids = $this->da->escapeIntImplode($event_ids);

        $sql = "UPDATE system_event
            SET status = 'RUNNING',
                process_date = NOW()
            WHERE id IN ($event_ids)";

        return $this->update($sql);
    }

    public function purgeDataOlderThanOneYear()
    {
        $one_year_ago_date = date('Y-m-d 00:00:00', strtotime('-1 year', time()));

        $sql = "DELETE FROM system_event
                WHERE create_date < '$one_year_ago_date'";
        $this->update($sql);

        $sql = "OPTIMIZE TABLE system_event";
        return $this->update($sql);
    }

    public function searchAllMatchingEvents($status, $limit, $offset)
    {
        $limit  = $this->da->escapeInt($limit);
        $offset = $this->da->escapeInt($offset);

        $where = '';
        if ($status) {
            $status = $this->da->quoteSmart(strtoupper($status));
            $where  = "WHERE status = $status";
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS *
                FROM system_event
                $where
                LIMIT $limit
                OFFSET $offset";

        return $this->retrieve($sql);
    }
}
