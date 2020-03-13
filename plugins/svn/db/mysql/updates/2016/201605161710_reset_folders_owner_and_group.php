<?php
/**
 * Copyright (c) Enalean 2016. All rights reserved
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

class b201605161710_reset_folders_owner_and_group extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Reset HTTPUser as owner of svn_plugins folders';
    }

    public function up()
    {
        include('/etc/tuleap/conf/local.inc');
        $svn_plugin_folder = $sys_data_dir . '/svn_plugin/';

        echo 'Checking ' . $svn_plugin_folder . PHP_EOL;

        chown($svn_plugin_folder, $sys_http_user);
        chgrp($svn_plugin_folder, $sys_http_user);

        $project_dirs      = new DirectoryIterator($svn_plugin_folder);

        foreach ($project_dirs as $project_dir) {
            if (! $project_dir->isDot() && $project_dir->isDir()) {
                echo 'Checking ' . $project_dir->getPathname() . PHP_EOL;

                chown($project_dir->getPathname(), $sys_http_user);
                chgrp($project_dir->getPathname(), $sys_http_user);
            }
        }
    }
}
