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
class SystemEvent_PROJECT_CREATE extends SystemEvent {


    /**
     * Constructor
     * @param $type      : SystemeEvent type (const defined in this class)
     * @param $parameters: Event Parameter (e.g. group_id if event type is PROJECT_CREATE)
     * @param $priority  : Event priority
     */
    function SystemEvent_PROJECT_CREATE($id, $parameters, $priority, $status ) {
        $this->id        = $id;
        $this->type      = SystemEvent::PROJECT_CREATE;
        $this->parameters= $parameters;
        $this->priority  = $priority;
        $this->status    = $status;
    }



    /** 
     * Process stored event
     */
    function process() {
        $this->setStatus("DONE");
        $this->setLog("OK");
        return true;
    }

}

?>
