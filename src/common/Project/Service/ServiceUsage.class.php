<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

/**
 * Value Object of a Service Usage
 */
class Project_Service_ServiceUsage
{

    private $id;
    private $short_name;
    private $is_used;

    public function __construct($id, $short_name, $is_used)
    {
        $this->id         = $id;
        $this->short_name = $short_name;
        $this->is_used    = $is_used;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getShortName()
    {
        return $this->short_name;
    }

    public function isUsed()
    {
        return $this->is_used;
    }
}
