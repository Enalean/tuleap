<?php
/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CLI\Command;

use PasswordHandlerFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\DB\DBFactory;
use Tuleap\NeverThrow\Fault;
use TuleapCfg\Command\SetupMysql\ConnectionManager;
use TuleapCfg\Command\SetupMysql\DatabaseConfigurator;
use TuleapCfg\Command\SetupMysql\EasyDBWrapper;

final class SanityCheckCommand extends Command
{
    public const NAME = 'sanity-check';

    #[\Override]
    protected function configure(): void
    {
        $this->setName(self::NAME)->setDescription('Check server\'s ability to run Tuleap');
    }

    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $stderr = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        $database_configurator = new DatabaseConfigurator(
            PasswordHandlerFactory::getPasswordHandler(),
            new ConnectionManager(),
        );
        $result                = $database_configurator->sanityCheck(new EasyDBWrapper(DBFactory::getMainTuleapDBConnection()->getDB()));
        return $result->match(
            function () use ($output) {
                $output->writeln('<info>Sanity check OK</info>');
                return self::SUCCESS;
            },
            function (Fault $fault) use ($stderr) {
                $stderr->writeln(sprintf('<error>%s</error>', OutputFormatter::escape((string) $fault)));
                return self::FAILURE;
            }
        );
    }
}
