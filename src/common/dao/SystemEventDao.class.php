<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('include/DataAccessObject.class.php');
require_once('common/system_event/SystemEvent.class.php');

/**
 *  Data Access Object for SystemEvent 
 */
class SystemEventDao extends DataAccessObject {
    /** 
     * Create new SystemEvent and store it in the DB
     * @return true if there is no error
     */
    function store($type, $parameters, $priority,$status, $create_date, $owner) {
        $sql = sprintf("INSERT INTO system_event (type, parameters, priority, status, create_date, owner) VALUES (%s, %s, %d, %s, FROM_UNIXTIME(%d), %s)",
                       $this->da->quoteSmart($type),
                       $this->da->quoteSmart($parameters),
                       $this->da->escapeInt($priority),
                       $this->da->quoteSmart($status),
                       $this->da->escapeInt($create_date),
                       $this->da->quoteSmart($owner));
        
        return $this->updateAndGetLastId($sql);
    }

     /** 
     * Close SystemEvent: update status, log and end_date.
     * @param $sysevent : SystemEvent object
     * @return true if there is no error
     */
    function close($sysevent) {
        $now = time();
        $sql = sprintf("UPDATE system_event SET status=%s, log=%s, end_date=FROM_UNIXTIME(%d) WHERE id=%d",
                       $this->da->quoteSmart($sysevent->getStatus()),
                       $this->da->quoteSmart($sysevent->getLog()),
                       $this->da->escapeInt($now),
                       $this->da->escapeInt($sysevent->getId()));
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
    function checkOutNextEvent($owner) {
        // Get Id of next event to process
        $sql = "SELECT id FROM system_event WHERE status='".SystemEvent::STATUS_NEW."' and owner=".$this->da->quoteSmart($owner)." ORDER BY priority, create_date LIMIT 1";
        $dar = $this->retrieve($sql);
        if($dar && !$dar->isError()) {
            // Mark event as 'RUNNING'
            if ($row = $dar->getRow()) {
                $id = $row['id'];
                $upd_sql = "UPDATE system_event SET status='".SystemEvent::STATUS_RUNNING."', process_date=FROM_UNIXTIME(".time().") WHERE id=$id";
                $this->update($upd_sql);
                // Retrieve all event parameters
                $event_sql = "SELECT * FROM system_event WHERE id=$id";
                return $this->retrieve($event_sql);
            }
        }
        return null;
    }

    /**
     * Search n last status
     */
    public function searchLastEvents($offset, $limit, $filters_status, $filters_type) {
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
    public function searchWithParam($position, $val, $type, $status, $separator = SystemEvent::PARAMETER_SEPARATOR) {
        if ($position == 'head') {
            $stm    = $this->da->quoteSmart($val.$separator).'"%"';
        } else if ($position == 'tail') {
            $stm = '"%"'.$this->da->quoteSmart($separator.$val);
        } else {
            $stm    = '"%"'.$this->da->quoteSmart($separator.$val.$separator).'"%"';
        }
        
        $type   = $this->da->quoteSmartImplode(", ", $type);
        $status = $this->da->quoteSmartImplode(", ", $status);
        
        $sql = 'SELECT  * FROM system_event 
                 WHERE type   IN ('.$type.') 
                 AND status IN ('.$status.')
                 AND parameters LIKE '.$stm;

        return $this->retrieve($sql);
    }

    /**
     * @return DataAccessResult
     */
    public function resetStatus($id, $status) {
        $id     = $this->da->escapeInt($id);
        $status = $this->da->quoteSmart($status);
        $sql = "UPDATE system_event
                SET status = $status
                WHERE id = $id";
        return $this->update($sql);
    }

    /**
     * @return array of event id and parameters
     */
    public function searchNewGitRepoUpdateEvents() {
        $sql = "SELECT id, parameters FROM system_event
            WHERE status = 'NEW'
            AND type = 'GIT_REPO_UPDATE'";

        return $this->retrieve($sql);
    }

    /**
     * @param array $event_ids
     * @return boolean
     */
    public function markAsDone($event_ids) {
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
     * @return boolean
     */
    public function markAsRunning($event_ids) {
        $event_ids = $this->da->escapeIntImplode($event_ids);

        $sql = "UPDATE system_event
            SET status = 'RUNNING',
                process_date = NOW()
            WHERE id IN ($event_ids)";

        return $this->update($sql);
    }
}
?>
