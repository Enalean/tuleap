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

final class MediaWikiInstallAndUpdateScriptCaller implements MediaWikiInstallAndUpdateHandler
{
    private const MAX_ONGOING_PROCESSES = 2;

    public function __construct(
        private MediaWikiManagementCommandFactory $management_command_factory,
        private MainpageDeployer $mainpage_deployer,
        private LocalSettingsInstantiator $local_settings_instantiator,
        private ProjectMediaWikiServiceDAO $project_mediawiki_service_dao,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws MediaWikiInstallAndUpdateHandlerException
     */
    #[\Override]
    public function runInstallAndUpdate(): void
    {
        $this->mainpage_deployer->deployMainPages();
        $this->installFarmInstance();
        $this->updateFarmInstanceConfigurationFile();
        $this->logger->debug('Update MediaWiki standalone Tuleap managed LocalSettings file');
        $this->local_settings_instantiator->instantiateLocalSettings();
        $this->updateFarmInstance();
        $this->updateProjectInstances();
    }

    private function installFarmInstance(): void
    {
        $this->logger->info('Install MediaWiki standalone farm instance');
        $this->executeMediaWikiManagementCommandSynchronously($this->management_command_factory->buildInstallCommand());
    }

    private function updateFarmInstanceConfigurationFile(): void
    {
        $this->logger->debug('Updating MediaWiki standalone farm instance configuration file');
        $this->executeMediaWikiManagementCommandSynchronously($this->management_command_factory->buildFarmInstanceConfigurationUpdate());
    }

    private function updateFarmInstance(): void
    {
        $this->logger->debug('Updating MediaWiki standalone farm instance');
        $this->executeMediaWikiManagementCommandSynchronously($this->management_command_factory->buildUpdateFarmInstanceCommand());
    }

    private function updateProjectInstances(): void
    {
        $this->logger->debug('Updating MediaWiki standalone project instances');
        $ongoing_processes = [];
        $failures          = [];
        foreach ($this->project_mediawiki_service_dao->searchAllProjectsWithMediaWikiStandaloneServiceReady() as ['project_name' => $project_name]) {
            $this->logger->debug('Starting update of MediaWiki project instance of project ' . $project_name);
            $ongoing_processes[] = $this->management_command_factory->buildUpdateProjectInstanceCommand($project_name);
            if (count($ongoing_processes) >= self::MAX_ONGOING_PROCESSES) {
                $failures = [...$failures, ...self::waitOngoingProcesses($ongoing_processes)];
            }
        }
        $failures = [...$failures, ...self::waitOngoingProcesses($ongoing_processes)];

        if (count($failures) > 0) {
            throw MediaWikiInstallAndUpdateHandlerException::fromCommandFailures($failures);
        }
    }

    private function executeMediaWikiManagementCommandSynchronously(MediaWikiManagementCommand $management_command): void
    {
        $management_command->wait()->mapErr(function (MediaWikiManagementCommandFailure $err): void {
            throw MediaWikiInstallAndUpdateHandlerException::fromCommandFailures([$err]);
        });
    }

    /**
     * @param list<MediaWikiManagementCommand> $ongoing_processes
     * @return list<MediaWikiManagementCommandFailure>
     */
    private static function waitOngoingProcesses(array $ongoing_processes): array
    {
        $failures = [];
        foreach ($ongoing_processes as $ongoing_process) {
            $result = $ongoing_process->wait();
            $result->mapErr(function (MediaWikiManagementCommandFailure $failure) use (&$failures): void {
                $failures[] = $failure;
            });
        }
        return $failures;
    }
}
