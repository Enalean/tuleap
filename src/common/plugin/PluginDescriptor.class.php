<?php
/**
 * Copyright (c) Enalean, 2020-present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * PluginDescriptor
 */
class PluginDescriptor
{

    public $name;
    public $version;
    public $description;
    /**
     * @var string
     */
    public $icon_name;

    /**
     * @param string|false $version
     */
    public function __construct(string $name = '', $version = '', string $description = '')
    {
        $this->name        = $name;
        $this->version     = $version;
        $this->description = $description;
        $this->icon_name   = '';
    }

    public function getFullName()
    {
        return $this->name;
    }
    public function getVersion()
    {
        return $this->version;
    }
    public function getDescription()
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
