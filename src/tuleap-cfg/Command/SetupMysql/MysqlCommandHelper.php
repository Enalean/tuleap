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
use Tuleap\Config\ConfigKeyLegacyBool;
use Tuleap\DB\DBConfig;

final class MysqlCommandHelper
{
    public const OPT_HOST   = 'host';
    public const OPT_PORT   = 'port';
    public const OPT_SSL    = 'ssl-mode';
    public const OPT_SSL_CA = 'ssl-ca';

    public const SSL_NO_VERIFY = 'no-verify';
    public const SSL_NO_SSL    = 'disabled';
    public const SSL_VERIFY_CA = 'verify-ca';

    private const ALLOWED_SSL_MODES = [
        self::SSL_NO_SSL,
        self::SSL_NO_VERIFY,
        self::SSL_VERIFY_CA,
    ];

    public function __construct(private string $base_directory)
    {
    }

    public function addOptions(Command $command): Command
    {
        $command
            ->addOption(self::OPT_HOST, '', InputOption::VALUE_REQUIRED, 'MySQL server host (default: localhost)')
            ->addOption(self::OPT_PORT, '', InputOption::VALUE_REQUIRED, 'MySQL server port (default: 3306)')
            ->addOption(
                self::OPT_SSL,
                '',
                InputOption::VALUE_REQUIRED,
                sprintf(
                    'Use an encrypted connection. Possible values: `%s` (default), `%s` or `%s`',
                    self::SSL_NO_SSL,
                    self::SSL_NO_VERIFY,
                    self::SSL_VERIFY_CA
                ),
            )
            ->addOption(self::OPT_SSL_CA, '', InputOption::VALUE_REQUIRED, sprintf(
                'When %s is set to %s or %s you can provide a path to a custom CA file (default: %s)',
                self::OPT_SSL,
                self::SSL_NO_VERIFY,
                self::SSL_VERIFY_CA,
                DBConfig::DEFAULT_MYSQL_CA_FILE_PATH,
            ));
        return $command;
    }

    public function setHost(InputInterface $input): void
    {
        $host = $input->getOption(self::OPT_HOST);
        if ($host === null) {
            return;
        }
        assert(is_string($host));
        \ForgeConfig::set(DBConfig::CONF_HOST, $host);
    }

    public function setPort(InputInterface $input): void
    {
        $port = $input->getOption(self::OPT_PORT);
        if ($port === null) {
            return;
        }
        \ForgeConfig::set(DBConfig::CONF_PORT, (int) $port);
    }

    public function setSSLMode(InputInterface $input): void
    {
        $ssl_mode = $input->getOption(self::OPT_SSL);
        if ($ssl_mode === null) {
            return;
        }
        assert(is_string($ssl_mode));
        if (! in_array($ssl_mode, self::ALLOWED_SSL_MODES, true)) {
            throw new InvalidSSLConfigurationException(sprintf('Invalid `%s` value: %s', self::OPT_SSL, $ssl_mode));
        }
        $this->setSSLVariablesFromOptionsOrLegacyEnv($input, $ssl_mode);
    }

    /**
     * @psalm-param value-of<self::ALLOWED_SSL_MODES> $ssl_mode
     */
    private function setSSLVariablesFromOptionsOrLegacyEnv(InputInterface $input, string $ssl_mode): void
    {
        switch ($ssl_mode) {
            case self::SSL_NO_SSL:
                \ForgeConfig::set(DBConfig::CONF_ENABLE_SSL, ConfigKeyLegacyBool::FALSE);
                \ForgeConfig::set(DBConfig::CONF_SSL_VERIFY_CERT, ConfigKeyLegacyBool::FALSE);
                break;
            case self::SSL_NO_VERIFY:
                \ForgeConfig::set(DBConfig::CONF_ENABLE_SSL, ConfigKeyLegacyBool::TRUE);
                \ForgeConfig::set(DBConfig::CONF_SSL_VERIFY_CERT, ConfigKeyLegacyBool::FALSE);
                break;
            case self::SSL_VERIFY_CA:
                \ForgeConfig::set(DBConfig::CONF_ENABLE_SSL, ConfigKeyLegacyBool::TRUE);
                \ForgeConfig::set(DBConfig::CONF_SSL_VERIFY_CERT, ConfigKeyLegacyBool::TRUE);
                break;
        }

        $this->setSSLCAFile($input, $ssl_mode);
    }

    /**
     * @psalm-param value-of<self::ALLOWED_SSL_MODES> $ssl_mode
     */
    private function setSSLCAFile(InputInterface $input, string $ssl_mode): void
    {
        if ($ssl_mode === self::SSL_NO_SSL) {
            return;
        }

        $ssl_ca_file = $input->getOption(self::OPT_SSL_CA);
        if ($ssl_ca_file === null) {
            return;
        }
        assert(is_string($ssl_ca_file));
        $ca_file_path = $this->base_directory . '/' . $ssl_ca_file;
        if (! is_file($ca_file_path)) {
            throw new InvalidSSLConfigurationException(sprintf('Invalid `%s` value: %s no such file', self::OPT_SSL_CA, $ca_file_path));
        }
        \ForgeConfig::set(DBConfig::CONF_SSL_CA, $ssl_ca_file);
    }
}
