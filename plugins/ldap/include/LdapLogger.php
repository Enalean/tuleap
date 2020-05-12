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

namespace Tuleap\LDAP;

use BackendLogger;
use ForgeConfig;
use Psr\Log\LoggerInterface;
use Tuleap\Log\LogForwarderTrait;

final class LdapLogger implements LoggerInterface
{
    use LogForwarderTrait;

    private const LOGGER_NAME = 'ldap_syslog';

    public function __construct()
    {
        if (BackendLogger::isLogHandlerToFiles()) {
            $this->createLogFileForAppUser(ForgeConfig::get('codendi_log') . '/' . self::LOGGER_NAME);
        }
        $this->logger = BackendLogger::getDefaultLogger(self::LOGGER_NAME);
    }

    private function createLogFileForAppUser(string $file_path): void
    {
        if (! is_file($file_path)) {
            $http_user = ForgeConfig::get('sys_http_user');
            touch($file_path);
            chown($file_path, $http_user);
            chgrp($file_path, $http_user);
        }
    }
}
