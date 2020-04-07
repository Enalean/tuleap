<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\CLI;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;
use Tuleap\Event\Dispatchable;

class CLICommandsCollector implements Dispatchable
{
    public const NAME = 'collectCLICommands';

    /**
     * @var array<string,callable():\Symfony\Component\Console\Command\Command>
     */
    private $command_factories = [];

    /**
     * @psalm-param callable():\Symfony\Component\Console\Command\Command $command_factory
     */
    public function addCommand(string $command_name, callable $command_factory): void
    {
        $this->command_factories[$command_name] = $command_factory;
    }

    public function loadCommands(Application $application): void
    {
        $factory_command_loader = new FactoryCommandLoader($this->command_factories);
        $application->setCommandLoader($factory_command_loader);
    }
}
