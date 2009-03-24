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
* System Event classes
*
*/
class SystemEvent_USER_DELETE extends SystemEvent {


    /**
     * Constructor
     * @param $id        : SystemEvent DB ID
     * @param $parameters: Event Parameter 
     * @param $priority  : Event priority
     * @param $status    : Event status
     */
    function SystemEvent_USER_DELETE($id, $parameters, $priority, $status ) {
        $this->id        = $id;
        $this->type      = SystemEvent::USER_DELETE;
        $this->parameters= $parameters;
        $this->priority  = $priority;
        $this->status    = $status;
    }



    /** 
     * Process stored event
     */
    function process() {
        // Check parameters
        $user_id=$this->getIdFromParam($this->parameters);

        if ($user_id == 0) {
            return $this->setErrorBadParam();
        }

        // Need to remove user alias
        BackendAliases::instance()->setNeedUpdateMailAliases();

        // Archive user home directory
        if (!BackendSystem::instance()->archiveUserHome($user_id)) {
            $this->setStatus(SystemEvent::STATUS_ERROR);
            $this->setLog("Could not archive user home");
            return false;
        }

        $this->setStatus(SystemEvent::STATUS_DONE);
        $this->setLog("OK");
        return true;
    }

}

?>
