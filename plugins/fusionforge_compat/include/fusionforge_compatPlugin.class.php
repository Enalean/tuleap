<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__));

class fusionforge_compatPlugin extends Plugin {
    
    public function loaded() {
        require_once 'fusionforge/forge_get_config.php';
        require_once 'fusionforge/forge_check_perm.php';
        require_once 'fusionforge/plugin_hook_by_reference.php';
        require_once 'fusionforge/utils.php';
        require_once 'fusionforge/get_public_active_projects_asc.php';
        require_once 'fusionforge/FusionForge.php';
        require_once 'fusionforge/user_get_object_by_name.php';
        require_once 'fusionforge/sortRoleList.php';
        require_once 'fusionforge/sortProjectList.php';
        require_once 'fusionforge/RBAC.php';
    }
}

