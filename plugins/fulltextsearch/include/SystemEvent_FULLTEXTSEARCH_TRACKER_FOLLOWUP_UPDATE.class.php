<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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

require_once 'common/system_event/SystemEvent.class.php';

class SystemEvent_FULLTEXTSEARCH_TRACKER_FOLLOWUP_UPDATE extends SystemEvent {

    /**
     * Process the system event
     *
     * @return Boolean
     */
    public function process() {
        $groupId     = (int)$this->getRequiredParameter(0);
        $artifactId  = (int)$this->getRequiredParameter(1);
        $changesetId = (int)$this->getRequiredParameter(2);
        $text        = $this->getRequiredParameter(3);
        
        $this->done();
        return true;
    }

    /**
     * Verbalize parameters
     *
     * @param Boolean $withLink
     *
     * @return String
     */
    public function verbalizeParameters($withLink) {
        $groupId     = (int)$this->getRequiredParameter(0);
        $artifactId  = (int)$this->getRequiredParameter(1);
        $changesetId = (int)$this->getRequiredParameter(2);
        $text        = $this->getRequiredParameter(3);
        // @TODO: verbalize artifact id & changeset id would look like /plugins/tracker/?aid=$artifactId#followup_$changesetId
        return 'Project: '.$this->verbalizeProjectId($groupId, $withLink).', Artifact: '.$artifactId.', Changeset: '.$changesetId.', Text: '.$text;
    }

}

?>