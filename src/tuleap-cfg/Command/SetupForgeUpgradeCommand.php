<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace TuleapCfg\Command;

use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\DB\DBFactory;
use Tuleap\ForgeUpgrade\ForgeUpgrade;

final class SetupForgeUpgradeCommand extends Command
{
    public function __construct()
    {
        parent::__construct('setup:forgeupgrade');
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setDescription('Initialise forgeupgrade. Record existing migration buckets')
            ->setHidden(true); // This command is hidden ATM because we don't want to keep the backward compat if the setup evolves latter on
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        \ForgeConfig::loadInSequence();
        $forge_upgrade = new ForgeUpgrade(
            DBFactory::getMainTuleapDBConnection()->getDB()->getPdo(),
            new ConsoleLogger(
                $output,
                [
                    LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
                ],
            ),
            new \Tuleap\DB\DatabaseUUIDV7Factory(),
        );
        $forge_upgrade->recordOnlyCore();
        return 0;
    }
}
