<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Stub;

use Tuleap\MediawikiStandalone\Configuration\MediaWikiManagementCommand;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiManagementCommandDoNothing;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiManagementCommandFactory;

final class MediaWikiManagementCommandFactoryStub implements MediaWikiManagementCommandFactory
{
    private \ArrayIterator $update_instance_commands_iterator;

    /**
     * @param MediaWikiManagementCommand[] $update_instance_commands
     */
    public function __construct(
        private MediaWikiManagementCommand $install_command,
        private readonly MediaWikiManagementCommand $farm_instance_configuration_update,
        private MediaWikiManagementCommand $update_farm_command,
        array $update_instance_commands,
    ) {
        $this->update_instance_commands_iterator = new \ArrayIterator($update_instance_commands);
    }

    public static function buildForUpdateInstancesCommandsOnly(array $update_instance_commands): self
    {
        return new self(
            new MediaWikiManagementCommandDoNothing(),
            new MediaWikiManagementCommandDoNothing(),
            new MediaWikiManagementCommandDoNothing(),
            $update_instance_commands
        );
    }

    #[\Override]
    public function buildInstallCommand(): MediaWikiManagementCommand
    {
        return $this->install_command;
    }

    #[\Override]
    public function buildFarmInstanceConfigurationUpdate(): MediaWikiManagementCommand
    {
        return $this->farm_instance_configuration_update;
    }

    #[\Override]
    public function buildUpdateFarmInstanceCommand(): MediaWikiManagementCommand
    {
        return $this->update_farm_command;
    }

    #[\Override]
    public function buildUpdateProjectInstanceCommand(string $project_name): MediaWikiManagementCommand
    {
        $command = $this->update_instance_commands_iterator->current();
        $this->update_instance_commands_iterator->next();
        return $command;
    }

    #[\Override]
    public function buildUpdateToMediaWiki135ProjectInstanceCommand(string $project_name): MediaWikiManagementCommand
    {
        return $this->buildUpdateProjectInstanceCommand($project_name);
    }
}
