<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace TuleapCfg\Command\Docker;

use BackendLogger;
use Psr\Log\LoggerInterface;
use Tuleap\Config\ConfigDao;
use Tuleap\Config\ConfigSet;
use Tuleap\Config\GetConfigKeys;
use Tuleap\Log\LogToSyslog as LogToSyslogAlias;
use Webimpress\SafeWriter\FileWriter;

final class LogToSyslog
{
    private const ENV_LOGGER = 'TULEAP_LOGGER';

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function configure(): void
    {
        if (getenv(self::ENV_LOGGER) !== LogToSyslogAlias::CONFIG_LOGGER_SYSLOG) {
            return;
        }
        \ForgeConfig::loadInSequence();

        $this->tuleap();
        $this->nginx();
    }

    private function tuleap(): void
    {
        $this->logger->info('Syslog: configure tuleap');
        $config_keys = \EventManager::instance()->dispatch(new GetConfigKeys());
        assert($config_keys instanceof GetConfigKeys);
        $config_set = new ConfigSet($config_keys, new ConfigDao());
        $config_set->set(BackendLogger::CONFIG_LOGGER, LogToSyslogAlias::CONFIG_LOGGER_SYSLOG);
    }

    private function nginx(): void
    {
        $this->logger->info('Syslog: configure nginx');
        $conf_file  = '/etc/nginx/nginx.conf';
        $nginx_conf = preg_replace(
            [
                '/error_log .*/',
                '/access_log .*/',
            ],
            [
                'error_log syslog:server=unix:/dev/log,tag=nginx;',
                'access_log syslog:server=unix:/dev/log,tag=nginx main;',
            ],
            file_get_contents($conf_file)
        );
        FileWriter::writeFile($conf_file, $nginx_conf, 0644);
    }
}
