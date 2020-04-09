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

namespace TuleapCfg\Command\SetupMysql;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

final class MysqlCommandHelper
{
    public const OPT_HOST   = 'host';
    public const OPT_PORT   = 'port';
    public const OPT_SSL    = 'ssl-mode';
    public const OPT_SSL_CA = 'ssl-ca';

    public function addOptions(Command $command): Command
    {
        $command
            ->addOption(self::OPT_HOST, '', InputOption::VALUE_REQUIRED, 'MySQL server host', 'localhost')
            ->addOption(self::OPT_PORT, '', InputOption::VALUE_REQUIRED, 'MySQL server port', 3306)
            ->addOption(self::OPT_SSL, '', InputOption::VALUE_REQUIRED, sprintf('Use an encrypted connection. Possible values: `%s` (default), `%s` or `%s`', ConnectionManager::SSL_NO_SSL, ConnectionManager::SSL_NO_VERIFY, ConnectionManager::SSL_VERIFY_CA), ConnectionManager::SSL_NO_SSL)
            ->addOption(self::OPT_SSL_CA, '', InputOption::VALUE_REQUIRED, sprintf('When %s is set to %s or %s you should provide the path to CA file', self::OPT_SSL, ConnectionManager::SSL_NO_VERIFY, ConnectionManager::SSL_VERIFY_CA), ConnectionManager::DEFAULT_CA_FILE_PATH);
        return $command;
    }

    public function getHost(InputInterface $input): string
    {
        $host = $input->getOption(self::OPT_HOST);
        assert(is_string($host));
        return $host;
    }

    public function getPort(InputInterface $input): int
    {
        return (int) $input->getOption(self::OPT_PORT);
    }

    /**
     * @psalm-return ConnectionManager::SSL_*
     */
    public function getSSLMode(InputInterface $input): string
    {
        $ssl_mode = $input->getOption(self::OPT_SSL);
        if (! in_array($ssl_mode, [ConnectionManager::SSL_NO_SSL, ConnectionManager::SSL_NO_VERIFY, ConnectionManager::SSL_VERIFY_CA], true)) {
            assert(is_string($ssl_mode));
            throw new InvalidSSLConfigurationException(sprintf('Invalid `%s` value: %s', self::OPT_SSL, $ssl_mode));
        }
        return $ssl_mode;
    }

    /**
     * @psalm-param ConnectionManager::SSL_* $ssl_mode
     */
    public function getSSLCAFile(InputInterface $input, string $ssl_mode): string
    {
        $ssl_ca_file = '';
        if ($ssl_mode !== ConnectionManager::SSL_NO_SSL) {
            $ssl_ca_file = $input->getOption(self::OPT_SSL_CA);
            assert(is_string($ssl_ca_file));
            if (! is_file($ssl_ca_file)) {
                throw new InvalidSSLConfigurationException(sprintf('Invalid `%s` value: %s no such file', self::OPT_SSL_CA, $ssl_ca_file));
            }
        }
        return $ssl_ca_file;
    }
}
