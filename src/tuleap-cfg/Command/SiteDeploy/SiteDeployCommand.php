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

namespace TuleapCfg\Command\SiteDeploy;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TuleapCfg\Command\SiteDeploy\FPM\SiteDeployFPMCommand;

final class SiteDeployCommand extends Command
{
    public const NAME = 'site-deploy';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this->setDescription('Execute all deploy actions needed at site update')
            ->addOption(SiteDeployFPMCommand::OPT_PHP_VERSION, '', InputOption::VALUE_REQUIRED, 'Target php version: `php81` (default), `php82`', SiteDeployFPMCommand::PHP81)
            ->addOption(SiteDeployFPMCommand::OPT_FORCE, '', InputOption::VALUE_NONE, 'Force files to be rewritten (by default existing files are not modified)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->executeAllCommandsInNamespace($input, $output);
    }

    private function executeAllCommandsInNamespace(InputInterface $input, OutputInterface $output): int
    {
        $application = $this->getApplication();
        if ($application === null) {
            return 0;
        }

        $input_options = $input->getOptions();

        foreach ($application->all(self::NAME) as $command) {
            $command_definition = $command->getDefinition();

            $subcommand_input = new ArrayInput([], $command_definition);
            $subcommand_input->setInteractive(false);

            foreach ($input_options as $name => $value) {
                if ($command_definition->hasOption($name)) {
                    $subcommand_input->setOption($name, $value);
                }
            }

            $status = $command->execute($subcommand_input, $output);

            if ($status !== 0) {
                return $status;
            }
        }

        return 0;
    }
}
