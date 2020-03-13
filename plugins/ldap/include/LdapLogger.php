<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\LDAP;

use BackendLogger;
use ForgeConfig;
use TruncateLevelLogger;

class LdapLogger extends TruncateLevelLogger
{

    public function __construct()
    {
        $file_path = ForgeConfig::get('codendi_log') . '/ldap_syslog';

        $this->createLogFileForAppUser($file_path);

        parent::__construct(
            new BackendLogger($file_path),
            ForgeConfig::get('sys_logger_level')
        );
    }
}
