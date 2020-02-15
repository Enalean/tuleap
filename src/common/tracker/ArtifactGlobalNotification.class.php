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
class ArtifactGlobalNotification
{

    public $data;

    /**
    * Constructor
    */
    public function __construct($data)
    {
        $this->data = $data;
    }
    public function getId()
    {
        return $this->data['id'];
    }
    public function getTrackerId()
    {
        return $this->data['tracker_id'];
    }
    public function getAddresses()
    {
        return $this->data['addresses'];
    }
    public function isAllUpdates()
    {
        return $this->data['all_updates'];
    }
    public function isCheckPermissions()
    {
        return $this->data['check_permissions'];
    }
}
