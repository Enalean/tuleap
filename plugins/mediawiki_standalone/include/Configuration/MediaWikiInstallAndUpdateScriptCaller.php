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

final class MediaWikiInstallAndUpdateScriptCaller implements MediaWikiInstallAndUpdateHandler
{
    private const LOCAL_SETTINGS_FILE_MANAGED_BY_MEDIAWIKI = 'LocalSettings.php';

    public function __construct(
        private string $path_setting_directory,
        private LocalSettingsInstantiator $local_settings_instantiator,
        private ProjectMediaWikiServiceDAO $project_mediawiki_service_dao,
        private LoggerInterface $logger,
    ) {
    }

    public function runInstallAndUpdate(): void
    {
        $this->installFarmInstance();
        $this->logger->debug('Update MediaWiki standalone Tuleap managed LocalSettings file');
        $this->local_settings_instantiator->instantiateLocalSettings();
        $this->updateFarmInstance();
        $this->updateProjectInstances();
    }

    private function installFarmInstance(): void
    {
        if (file_exists($this->path_setting_directory . '/' . self::LOCAL_SETTINGS_FILE_MANAGED_BY_MEDIAWIKI)) {
            $this->logger->debug('MediaWiki standalone farm instance is already installed');
            return;
        }

        $this->logger->info('Install MediaWiki standalone farm instance');
        $this->executeMediaWikiManagementCommand(
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

    private function updateFarmInstance(): void
    {
        $this->logger->debug('Updating MediaWiki standalone farm instance');
        $this->executeMediaWikiManagementCommand(
            [LocalSettingsRepresentation::MEDIAWIKI_PHP_CLI, '/usr/share/mediawiki-tuleap-flavor/maintenance/update.php', '--quick']
        );
    }

    private function updateProjectInstances(): void
    {
        $this->logger->debug('Updating MediaWiki standalone project instances');
        foreach ($this->project_mediawiki_service_dao->searchAllProjectsWithMediaWikiStandaloneServiceEnabled() as ['project_name' => $project_name]) {
            $this->logger->debug('Updating MediaWiki project instance of project ' . $project_name);
            $this->executeMediaWikiManagementCommand(
                [LocalSettingsRepresentation::MEDIAWIKI_PHP_CLI, '/usr/share/mediawiki-tuleap-flavor/maintenance/update.php', '--quick', '--sfr', $project_name]
            );
        }
    }

    private function executeMediaWikiManagementCommand(array $command): void
    {
        $process = new Process($command);
        $process->setTimeout(null);
        $process->mustRun();
        $this->logger->debug($process->getOutput());
    }
}
