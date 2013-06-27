<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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

/**
 * System Event classes
 *
 */
class SystemEvent_ROOT_DAILY extends SystemEvent {

    /**
     * Verbalize the parameters so they are readable and much user friendly in 
     * notifications
     * 
     * @param bool $with_link true if you want links to entities. The returned 
     * string will be html instead of plain/text
     *
     * @return string
     */
    public function verbalizeParameters($with_link) {
        return '-';
    }

    /**
     * Process stored event
     */
    public function process() {
        // Re-dumping ssh keys should be done only once a day as:
        // - It's I/O intensive
        // - It's stress gitolite backend
        // - SSH keys should already be dumped via EDIT_SSH_KEY event
        $backend_system = Backend::instance('System');
        $backend_system->dumpSSHKeys();

        $this->_getEventManager()->processEvent('root_daily_start', array());
        $this->done();
        return true;
    }

    /**
     * Wrapper for EventManager
     * 
     * @return EventManager
     */
    protected function _getEventManager() {
        return EventManager::instance();
    }
}

?>