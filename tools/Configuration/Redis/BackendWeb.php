<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Configuration\Redis;

class BackendWeb
{
    private $conf_file = '/etc/tuleap/conf/redis.inc';
    private $application_user;

    public function __construct($application_user)
    {
        $this->application_user = $application_user;
    }

    public function configure()
    {
        if (! file_exists($this->conf_file)) {
            copy('/usr/share/tuleap/src/etc/redis.inc.dist', $this->conf_file);
            chmod($this->conf_file, 0600);
            chown($this->conf_file, $this->application_user);
            chgrp($this->conf_file, $this->application_user);
        }
    }
}
