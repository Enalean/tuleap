<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Configuration;

use Psr\Log\LoggerInterface;
use Tuleap\DB\DBConfig;

final class MediaWikiManagementCommandProcessFactory implements MediaWikiManagementCommandFactory
{
    private const LOCAL_SETTINGS_FILE_MANAGED_BY_MEDIAWIKI = 'LocalSettings.php';

    public function __construct(private LoggerInterface $logger, private string $path_setting_directory)
    {
    }

    public function buildInstallCommand(): MediaWikiManagementCommand
    {
        if (file_exists($this->path_setting_directory . '/' . self::LOCAL_SETTINGS_FILE_MANAGED_BY_MEDIAWIKI)) {
            $this->logger->debug('MediaWiki standalone farm instance is already installed');
            return new MediaWikiManagementCommandDoNothing();
        }

        return new MediaWikiManagementCommandProcess(
            $this->logger,
            [
                LocalSettingsRepresentation::MEDIAWIKI_PHP_CLI,
                '/usr/share/mediawiki-tuleap-flavor/maintenance/install.php',
                '--confpath',
                $this->path_setting_directory,
                '--dbserver',
                \ForgeConfig::get(DBConfig::CONF_HOST) . ':' . \ForgeConfig::getInt(DBConfig::CONF_PORT),
                '--dbname',
                'plugin_mediawiki_standalone_farm',
                '--dbuser',
                \ForgeConfig::get(DBConfig::CONF_DBUSER),
                '--dbpass',
                \ForgeConfig::get(DBConfig::CONF_DBPASSWORD),
                '--pass',
                base64_encode(random_bytes(32)),
                'TuleapFarmManagement',
                'tuleap_mediawikifarm_admin',
            ]
        );
    }

    public function buildUpdateFarmInstanceCommand(): MediaWikiManagementCommand
    {
        return new MediaWikiManagementCommandProcess(
            $this->logger,
            [LocalSettingsRepresentation::MEDIAWIKI_PHP_CLI, '/usr/share/mediawiki-tuleap-flavor/maintenance/update.php', '--quick']
        );
    }

    public function buildUpdateProjectInstanceCommand(string $project_name): MediaWikiManagementCommand
    {
        return new MediaWikiManagementCommandProcess(
            $this->logger,
            [LocalSettingsRepresentation::MEDIAWIKI_PHP_CLI, '/usr/share/mediawiki-tuleap-flavor/maintenance/update.php', '--quick', '--sfr', $project_name]
        );
    }
}
