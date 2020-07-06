<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
use ServiceDao;

class ServiceCreator
{
    /** @var ServiceDao */
    private $dao;

    public function __construct(ServiceDao $dao)
    {
        $this->dao = $dao;
    }

    public function createService($arr, $group_id, $template, $force_enable = false)
    {
        // Convert link to real values
        // NOTE: if you change link variables here, change them also in ServicePOSTDataBuilder::substituteVariablesInLink and ProjectSidebarBuilder::getLink
        $link = $arr['link'];
        $pm   = ProjectManager::instance();
        if ($template['system']) {
            $link = str_replace('$projectname', $pm->getProject($group_id)->getUnixName(), $link);
            $link = str_replace('$sys_default_domain', \ForgeConfig::get('sys_default_domain'), $link);
            $link = str_replace('$group_id', $group_id, $link);
            $sys_default_protocol = 'http';
            if (\ForgeConfig::get('sys_https_host')) {
                $sys_default_protocol = 'https';
            }
            $link = str_replace('$sys_default_protocol', $sys_default_protocol, $link);
        } else {
            //for non-system templates
            $link = service_replace_template_name_in_link($link, $template, $pm->getProject($group_id));
        }

        $is_used   = isset($template['is_used'])   ? $template['is_used'] : $arr['is_used'];
        $is_active = isset($template['is_active']) ? $template['is_active'] : $arr['is_active'];
        $is_used   = $force_enable ? 1 : $is_used;

        $result = $this->dao->create(
            $group_id,
            $arr['label'],
            $arr['icon'],
            $arr['description'],
            $arr['short_name'],
            $link,
            $is_active,
            $is_used,
            $arr['scope'],
            $arr['rank'],
            $arr['is_in_new_tab']
        );

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
