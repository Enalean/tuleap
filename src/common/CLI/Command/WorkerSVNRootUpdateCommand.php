<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

use BackendLogger;
use ForgeConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TruncateLevelLogger;
use Tuleap\CLI\ConsoleLogger;
use Tuleap\Svn\SvnrootUpdater;
use Tuleap\System\DaemonLocker;

class WorkerSVNRootUpdateCommand extends Command
{
    public const NAME = 'worker:svnroot-update';

    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Listen to events and manage update of svnroot apache configuration')
            ->addOption('verbose', '', InputOption::VALUE_NONE, 'Output logs on stdout instead of log file');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $locker = new DaemonLocker('/var/run/svnroot_updater.pid');

        $is_verbose = (bool) $input->getOption('verbose');

        $logger_backend = BackendLogger::getDefaultLogger('svnroot_updater.log');
        if ($is_verbose) {
            $logger_backend = new ConsoleLogger($output);
        }

        $logger = new TruncateLevelLogger(
            $logger_backend,
            ForgeConfig::get('sys_logger_level')
        );

        $locker->isRunning();

        $logger->info("Start service");

        $updater = new SvnrootUpdater($logger);
        $updater->listen('backend-svn-1');

        $logger->info("Service terminated");

        return 0;
    }
}
