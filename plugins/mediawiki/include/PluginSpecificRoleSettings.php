<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

class PluginSpecificRoleSetting
{
    public $role;
    public $name           = '';
    public $section        = '';
    public $values         = [];
    public $default_values = [];
    public $global         = false;

    public function __construct(&$role, $name, $global = false)
    {
        $this->global = $global;
        $this->role   =& $role;
        $this->name   = $name;
    }

    public function SetAllowedValues($values)
    {
        $this->role->role_values = array_replace_recursive($this->role->role_values, [$this->name => $values]);
        if ($this->global) {
            $this->role->global_settings[] = $this->name;
        }
    }

    public function SetDefaultValues($defaults)
    {
        foreach ($defaults as $rname => $v) {
            $this->role->defaults[$rname][$this->name] = $v;
        }
    }

    public function setValueDescriptions($descs)
    {
        global $rbac_permission_names;
        foreach ($descs as $k => $v) {
            $rbac_permission_names[$this->name . $k] = $v;
        }
    }

    public function setDescription($desc)
    {
        global $rbac_edit_section_names;
        $rbac_edit_section_names[$this->name] = $desc;
    }
}
