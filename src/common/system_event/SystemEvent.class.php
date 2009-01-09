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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */


/**
* System Event class
*
*/
class SystemEvent {

    var $id;
    var $type;
    var $parameters;
    var $priority;
    // Status is one of: 'NEW', 'RUNNING', 'DONE', 'ERROR', 'WARNING' (see DB enum)
    var $status;

    // Define event types
    const PROJECT_CREATE="PROJECT_CREATE";
    const PROJECT_DELETE="PROJECT_DELETE";
    const USER_CREATE="USER_CREATE";
    const USER_DELETE="USER_DELETE";
    const USER_MODIFY="USER_MODIFY";
    const MEMBERSHIP_CREATE="MEMBERSHIP_CREATE";
    const MEMBERSHIP_DELETE="MEMBERSHIP_DELETE";
    const MEMBERSHIP_MODIFY="MEMBERSHIP_MODIFY";


    /**
     * Constructor
     * @param $type      : SystemeEvent type (const defined in this class)
     * @param $parameters: Event Parameter (e.g. group_id if event type is PROJECT_CREATE)
     * @param $priority  : Event priority
     */
    function SystemEvent($type, $parameters, $priority ) {
        $this->type      = $type;
        $this->parameters= $parameters;
        $this->priority  = $priority;
        $this->status    = "NEW";
    }

    // Getters

    function getId() {
        if (isset($this->id)) {
            return $this->id;
        } else return 0;
    }

    function getType() {
        return $this->type;
    }

    function getParameters() {
        return $this->parameters;
    }

    function getPriority() {
        return $this->priority;
    }

    function getStatus() {
        return $this->status;
    }

    function setStatus($status) {
        $this->status=$status;
    }

    /** 
     * Process stored event
     * Virtual method redeclared in children
     */
    function process() {
        return null;
    }

}

?>
