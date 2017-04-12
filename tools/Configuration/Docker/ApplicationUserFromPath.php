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
 *
 */

namespace Tuleap\Configuration\Docker;

use Tuleap\Configuration\Common;

class ApplicationUserFromPath
{

    private $application_user;
    private $path;
    private $exec;

    public function __construct($application_user, $path)
    {
        $this->application_user = $application_user;
        $this->path             = $path;
        $this->exec             = new Common\Exec();
    }

    public function configure()
    {
        $stats = stat($this->path);
        $this->addGroupIfNotExists($this->application_user, $stats['gid']);
        $this->addUserIfNotExists($this->application_user, $this->application_user, '/var/lib/tuleap', $stats['uid']);
    }

    private function addGroupIfNotExists($group_name, $gid)
    {
        $group = posix_getgrnam($group_name);
        if ($group === false) {
            $this->exec->command(sprintf('groupadd -g %d %s', $gid, escapeshellarg($group_name)));
        } elseif ($group['gid'] != $gid) {
            $this->exec->command(sprintf('groupmod -g %d %s', $gid, escapeshellarg($group_name)));
        }
    }

    private function addUserIfNotExists($user_name, $group_name, $home, $uid)
    {
        $user = posix_getpwnam($user_name);
        if ($user === false) {
            $this->exec->command(sprintf('useradd -g %s -M -d %s -u %d %s', escapeshellarg($group_name), escapeshellarg($home), $uid, escapeshellarg($user_name)));
        } elseif ($user['uid'] != $uid) {
            $this->exec->command(sprintf('usermod -u %d %s', $uid, escapeshellarg($user_name)));
        }
    }
}
