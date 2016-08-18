<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2011 - 2016. All Rights Reserved.
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

class Tracker_GlobalNotification {
    
    private $data;
	
    public function __construct($data) {
        $this->data = $data;
    }

    public function getId() {
        return $this->data['id'];
    }

    public function getTrackerId() {
        return $this->data['tracker_id'];
    }

    public function getAddresses($asArray=false) {
        $data = $this->data['addresses'];
        if ( $asArray )  {
            $data = preg_split('/[,;]/', $this->data['addresses']);
            $data = array_map('trim', $data);
        }
        return $data;
    }

    public function isAllUpdates() {
        return $this->data['all_updates'];
    }

    public function isCheckPermissions() {
        return $this->data['check_permissions'];
    }
}
