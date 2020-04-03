<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

declare(strict_types=1);

namespace Tuleap\Project\Service;

use ForgeConfig;
use Project;

class ServiceLinkDataBuilder
{
    public function substituteVariablesInLink(Project $project, string $link): string
    {
        if ((int) $project->getID() !== 100) {
            // NOTE: if you change link variables here, change them also below, and
            // in src/common/Project/RegisterProjectStep_Confirmation.class.php and src/www/include/Layout.class.php
            if (strstr($link, '$projectname')) {
                // Don't check project name if not needed.
                // When it is done here, the service bar will not appear updated on the current page
                $link = str_replace('$projectname', $project->getUnixName(), $link);
            }
            $link                 = str_replace('$sys_default_domain', $GLOBALS['sys_default_domain'], $link);
            $sys_default_protocol = 'http';
            if (ForgeConfig::get('sys_https_host')) {
                $sys_default_protocol = 'https';
            }
            $link = str_replace('$sys_default_protocol', $sys_default_protocol, $link);
            $link = str_replace('$group_id', (string) $project->getID(), $link);
        }

        return $link;
    }
}
