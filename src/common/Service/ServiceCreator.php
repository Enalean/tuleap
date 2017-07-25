<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Service;

use ProjectManager;
use ReferenceManager;

class ServiceCreator
{
    public function createService($arr, $group_id, $template, $force_enable = false)
    {
        // Convert link to real values
        // NOTE: if you change link variables here, change them also in src/www/project/admin/servicebar.php and src/www/include/Layout.class.php
        $link = $arr['link'];
        $pm   = ProjectManager::instance();
        if ($template['system']) {
            $link = str_replace('$projectname', $pm->getProject($group_id)->getUnixName(), $link);
            $link = str_replace('$sys_default_domain', $GLOBALS['sys_default_domain'], $link);
            $link = str_replace('$group_id', $group_id, $link);
            if ($GLOBALS['sys_force_ssl']) {
                $sys_default_protocol = 'https';
            } else {
                $sys_default_protocol = 'http';
            }
            $link = str_replace('$sys_default_protocol', $sys_default_protocol, $link);
        } else {
            //for non-system templates
            $link = service_replace_template_name_in_link($link, $template, $pm->getProject($group_id));
        }

        $is_used   = isset($template['is_used']) ? $template['is_used'] : $arr['is_used'];
        $server_id = isset($template['server_id']) ? $template['server_id'] : $arr['server_id'];
        $sql       = "INSERT INTO service (group_id, label, description, short_name, link, is_active, is_used, scope, rank, location, server_id, is_in_iframe) VALUES (" . db_ei($group_id) . ", '" . db_es($arr['label']) . "', '" . db_es($arr['description']) . "', '" . db_es($arr['short_name']) . "', '" . db_es($link) . "', " . db_ei($arr['is_active']) . ", " . ($force_enable ? 1 : db_ei($is_used)) . ", '" . db_es($arr['scope']) . "', " . db_ei($arr['rank']) . ",  '" . db_es($arr['location']) . "', " . db_ei($server_id) . ", " . db_ei($arr['is_in_iframe']) . ")";
        $result    = db_query($sql);

        if ($result) {
            // activate corresponding references
            $reference_manager = ReferenceManager::instance();
            if ($arr['short_name'] != "") {
                $reference_manager->addSystemReferencesForService($template['id'], $group_id, $arr['short_name']);
            }
            return true;
        } else {
            return false;
        }
    }
}
