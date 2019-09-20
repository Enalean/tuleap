<?php
/**
 * Copyright (c) Enalean 2015-2018. All rights reserved
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

class b201512211530_force_refresh_svnaccessfile extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Force refresh of SVNAccessFile to correct permissions';
    }

    public function up()
    {
        exec('/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/src/utils/svn/force_refresh_svnaccessfile.php');
    }
}
