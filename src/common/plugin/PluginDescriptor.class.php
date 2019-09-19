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

/**
 * PluginDescriptor
 */
class PluginDescriptor
{

    var $name;
    var $version;
    var $description;

    function __construct($name = '', $version = '', $description = '')
    {
        $this->name        = $name;
        $this->version     = $version;
        $this->description = $description;
        $this->icon_name   = '';
    }

    function getFullName()
    {
        return $this->name;
    }
    function getVersion()
    {
        return $this->version;
    }
    function getDescription()
    {
        return $this->description;
    }

    public function setVersionFromFile($path)
    {
        if (is_file($path)) {
            $this->version = trim(file_get_contents($path));
        }
    }
}
