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

namespace Tuleap\Project;

class UgroupsPermissionsCache
{
    private static $instance;

    private $cache;

    public static function instance()
    {
        if (! self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get($group_id, $object_id, array $permission_types, $use_default_permissions)
    {
        $key = $this->getKey($group_id, $object_id, $permission_types, $use_default_permissions);
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        return null;
    }

    public function set($group_id, $object_id, array $permission_types, $use_default_permissions, $value)
    {
        $this->cache[$this->getKey($group_id, $object_id, $permission_types, $use_default_permissions)] = $value;
    }

    private function getKey($group_id, $object_id, array $permission_types, $use_default_permissions)
    {
        $use = $use_default_permissions ? 'true' : 'false';
        return $group_id . '-' . $object_id . '-' . implode('-', $permission_types) . '-' . $use;
    }
}
