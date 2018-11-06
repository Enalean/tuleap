<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Plugin;

class PluginCacheInfo
{
    public $id;
    public $name;
    public $is_restricted;
    public $class;
    public $path;

    public function __construct($id, $name, $is_restricted, $class, $path)
    {
        $this->id            = $id;
        $this->name          = $name;
        $this->is_restricted = $is_restricted;
        $this->class         = $class;
        $this->path          = $path;
    }

    public static function __set_state(array $array)
    {
        return new self($array['id'], $array['name'], $array['is_restricted'], $array['class'], $array['path']);
    }
}
