<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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

require_once('common/system_event/SystemEvent.class.php');

/**
 * SystemEvent_ARCHIVE_DELETED_ITEMS
 */
class SystemEvent_ARCHIVE_DELETED_ITEMS extends SystemEvent {

    /**
     * Process the system event
     *
     * @return Boolean
     */
    public function process() {
        $parameters   = $this->getParametersAsArray();

        if (!empty($parameters[0])) {
            $sourcePath = $parameters[0];
        } else {
            $this->error('Missing source path');
            return false;
        }
        if ( !empty($parameters[1]) ) {
            $archivePath = $parameters[1];
        } else {
            $this->error('Missing argument archive path');
            return false;
        }

        if (copy($sourcePath, $archivePath.basename($sourcePath))) {
            $this->done();
            return true;
        } else {
            $this->error('Archiving of "'.$sourcePath.'" failed');
            return false;
        }
    }

    /**
     * Verbalize params
     *
     * @param Boolean $withLink With link
     *
     * @return Array
     */
    public function verbalizeParameters($withLink) {
        return $this->parameters;
    }

}

?>