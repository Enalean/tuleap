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

namespace Tuleap\Configuration;

class DefaultPaths
{
    private $application_user;

    public function __construct($application_user)
    {
        $this->application_user = $application_user;
        $this->exec             = new Common\Exec();
    }

    public function configure()
    {
        $this->createDirectoryForAppUser('/var/tmp/tuleap_cache');
        $this->createDirectoryForAppUser('/var/tmp/tuleap_cache/php');
        $this->createDirectoryForAppUser('/var/tmp/tuleap_cache/php/session');
        $this->createDirectoryForAppUser('/var/lib/tuleap');
        $this->createDirectoryForAppUser('/var/log/tuleap');
        $this->exec->command(sprintf('chown -R %s:%s /var/tmp/tuleap_cache /var/log/tuleap', escapeshellarg($this->application_user), escapeshellarg($this->application_user)));
    }

    private function createDirectoryForAppUser($path)
    {
        if (! is_dir($path)) {
            mkdir($path, 0750);
        }
        chown($path, $this->application_user);
        chgrp($path, $this->application_user);
    }
}
