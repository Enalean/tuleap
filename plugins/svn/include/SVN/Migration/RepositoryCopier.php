<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\SVN\Migration;

use Tuleap\SVNCore\Repository;

class RepositoryCopier
{
    /**
     * @var \System_Command
     */
    private $system_command;

    public function __construct(\System_Command $system_command)
    {
        $this->system_command = $system_command;
    }

    public function copy(Repository $repository)
    {
        $svn_core_folder   = escapeshellarg($repository->getProject()->getSVNRootPath());
        $svn_plugin_folder = escapeshellarg($repository->getSystemPath());

        $this->system_command->exec(
            "find $svn_core_folder -maxdepth 1 -mindepth 1 -not -iname 'hooks' -exec cp -pR {} $svn_plugin_folder ';'"
        );

        $system_user = escapeshellarg(\ForgeConfig::get('sys_http_user'));
        $this->system_command->exec(
            "chgrp -R $system_user $svn_plugin_folder"
        );
    }
}
