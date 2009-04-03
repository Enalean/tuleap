<?php
/*
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
    * Constructs the SystemEventDao
    * @param $da instance of the DataAccess class
    */
    function SystemEventDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    

    /** 
     * Create new SystemEvent and store it in the DB
     * @param $sysevent : SystemEvent object
     * @return true if there is no error
     */
    function store($sysevent) {
        $sql = sprintf("INSERT INTO system_event (type, parameters, priority, status, create_date) VALUES (%s, %s, %s, %s, FROM_UNIXTIME(%s))",
                       $this->da->quoteSmart($sysevent->getType()),
                       $this->da->quoteSmart($sysevent->getParameters()),
                       $this->da->quoteSmart($sysevent->getPriority()),
                       $this->da->quoteSmart($sysevent->getStatus()),
                       $this->da->quoteSmart(time()));
        return $this->update($sql);
    }

     /** 
     * Close SystemEvent: update status, log and end_date.
     * @param $sysevent : SystemEvent object
     * @return true if there is no error
     */
    function close($sysevent) {
        $sql = sprintf("UPDATE system_event SET status=%s, log=%s, end_date=FROM_UNIXTIME(%s) WHERE id=%s",
                       $this->da->quoteSmart($sysevent->getStatus()),
                       $this->da->quoteSmart($sysevent->getLog()),
                       $this->da->quoteSmart(time()),
                        $this->da->quoteSmart($sysevent->getId()));
        return $this->update($sql);
    }

   /**
     * Return next system event    
     * criteria: higer priority first, then most recent first
     * And set the event status to 'RUNNING'
     * @return DataAccessResult
    */
    function checkOutNextEvent() {
        // Get Id of next event to process
        $sql = "SELECT id FROM system_event WHERE status='".SystemEvent::STATUS_NEW."' ORDER BY priority, create_date LIMIT 1";
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
    public function searchLastEvents($nb) {
        $nb = $this->da->escapeInt($nb);
        $sql = "SELECT * 
                FROM system_event
                ORDER BY id DESC
                LIMIT $nb";
        return $this->retrieve($sql);
    }
}

?>
