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
use Symfony\Component\Process\Process;
use Tuleap\DB\DBConfig;

final class MediaWikiUpdateScriptCaller
{
    private const LOCAL_SETTINGS_FILE_MANAGED_BY_MEDIAWIKI = 'LocalSettings.php';

    public function __construct(
        private string $path_setting_directory,
        private LocalSettingsInstantiator $local_settings_instantiator,
        private LoggerInterface $logger,
    ) {
    }

    public function runUpdate(): void
    {
        $this->installRootInstance();
        $this->logger->debug('Update MediaWiki standalone Tuleap managed LocalSettings file');
        $this->local_settings_instantiator->instantiateLocalSettings();
        $this->updateRootInstance();
    }

    private function installRootInstance(): void
    {
        if (file_exists($this->path_setting_directory . '/' . self::LOCAL_SETTINGS_FILE_MANAGED_BY_MEDIAWIKI)) {
            $this->logger->debug('MediaWiki standalone root instance is already installed');
            return;
        }

        $this->logger->info('Install MediaWiki standalone root instance');
        $this->executeMediaWikiManagementCommand(
            [
                LocalSettingsRepresentation::MEDIAWIKI_PHP_CLI,
            '/usr/share/mediawiki-tuleap-flavor/maintenance/install.php',
                '--confpath',
                $this->path_setting_directory,
                '--dbserver',
                \ForgeConfig::get(DBConfig::CONF_HOST),
                '--dbname',
                'plugin_mediawiki_standalone_root',
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

    private function updateRootInstance(): void
    {
        $this->logger->debug('Updating MediaWiki standalone root instance');
        $this->executeMediaWikiManagementCommand(
            [LocalSettingsRepresentation::MEDIAWIKI_PHP_CLI, '/usr/share/mediawiki-tuleap-flavor/maintenance/update.php', '--quick']
        );
    }

    private function executeMediaWikiManagementCommand(array $command): void
    {
        $process = new Process($command);
        $process->setTimeout(null);
        $process->mustRun();
        $this->logger->debug($process->getOutput());
    }
}
