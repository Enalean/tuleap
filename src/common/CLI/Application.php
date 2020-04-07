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

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\CLI\Command\ClearCachesCommand;
use Tuleap\CLI\Command\PlatformAccessControlCommand;
use Tuleap\CLI\Command\RestoreCachesCommand;

class Application extends \Symfony\Component\Console\Application
{
    public function __construct()
    {
        parent::__construct(
            'Tuleap',
            trim(file_get_contents(__DIR__ . '/../../../VERSION'))
        );
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        if ($input->hasParameterOption(['--version', '-V', '-v'], true)) {
            $output->writeln($this->getVersion());

            return 0;
        }

        if ($input->hasParameterOption(['-c', '--clear-caches'], true)) {
            $input = new ArrayInput(['command' => ClearCachesCommand::NAME]);
        }

        if ($input->hasParameterOption(['-r', '--restore-caches'], true)) {
            $input = new ArrayInput(['command' => RestoreCachesCommand::NAME]);
        }

        if ($input->hasParameterOption([PlatformAccessControlCommand::NAME], true)) {
            $parameters = ['command' => PlatformAccessControlCommand::NAME];
            if (isset($GLOBALS['argv'][2])) {
                $parameters[PlatformAccessControlCommand::ACCESS_CONTROL_ARGUMENT] = $GLOBALS['argv'][2];
            }
            $input = new ArrayInput(
                $parameters,
                new InputDefinition(
                    array_merge(
                        $this->getDefinition()->getArguments(),
                        [new InputArgument(PlatformAccessControlCommand::ACCESS_CONTROL_ARGUMENT, InputArgument::OPTIONAL)]
                    )
                )
            );
        }

        return parent::doRun($input, $output);
    }

    protected function getDefaultInputDefinition()
    {
        return new InputDefinition(array(
            new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),

            new InputOption('--help', '-h', InputOption::VALUE_NONE, 'Display this help message'),
            new InputOption('--quiet', '-q', InputOption::VALUE_NONE, 'Do not output any message'),
            new InputOption('--version', '-v', InputOption::VALUE_NONE, 'Display Tuleap version'),
            new InputOption('--ansi', '', InputOption::VALUE_NONE, 'Force ANSI output'),
            new InputOption('--no-ansi', '', InputOption::VALUE_NONE, 'Disable ANSI output'),
            new InputOption('--no-interaction', '-n', InputOption::VALUE_NONE, 'Do not ask any interactive question'),
        ));
    }

    protected function getDefaultCommands()
    {
        return array_merge(
            parent::getDefaultCommands(),
            [new ClearCachesCommand(), new RestoreCachesCommand(), new PlatformAccessControlCommand()]
        );
    }
}
