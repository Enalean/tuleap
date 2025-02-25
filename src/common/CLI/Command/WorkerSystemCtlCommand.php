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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\Queue\WorkerAvailability;
use TuleapCfg\Command\ProcessFactory;

class WorkerSystemCtlCommand extends Command
{
    public const NAME = 'worker:systemctl';
    /**
     * @var ProcessFactory
     */
    private $process_factory;
    /**
     * @var WorkerAvailability
     */
    private $worker_availability;

    public function __construct(ProcessFactory $process_factory, WorkerAvailability $worker_availability)
    {
        parent::__construct(self::NAME);
        $this->process_factory     = $process_factory;
        $this->worker_availability = $worker_availability;
    }

    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('systemctl wrapper to start (and stop) as many tuleap-worker units as needed')
            ->addArgument('action', InputArgument::REQUIRED, 'Possible actions: start|stop');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        switch ($input->getArgument('action')) {
            case 'start':
                return $this->start();

            case 'stop':
                return $this->stop($output);

            default:
                $output->writeln('<error>Invalid action</error>');
                return Command::FAILURE;
        }
    }

    private function start(): int
    {
        for ($i = 0; $i < $this->worker_availability->getWorkerCount(); $i++) {
            $this->process_factory->getProcess(['/usr/bin/systemctl', 'start', sprintf('tuleap-worker@%d.service', $i)])->mustRun();
        }
        return 0;
    }

    private function stop(OutputInterface $output): int
    {
        foreach ($this->getTuleapWorkers() as $worker_name) {
            $output->writeln("<info>Stopping $worker_name</info>");
            $this->process_factory->getProcess(['/usr/bin/systemctl', 'stop', $worker_name])->mustRun();
        }
        return 0;
    }

    private function getTuleapWorkers(): array
    {
        $names   = [];
        $process = $this->process_factory->getProcess(
            ['/usr/bin/systemctl', 'list-units', '--no-pager', '--no-legend', '--plain', '--type=service', '--state=active', '--full', 'tuleap-worker@*']
        );
        $process->mustRun();
        foreach (explode("\n", $process->getOutput()) as $line) {
            if ($line !== '') {
                [$name,] = explode(' ', $line);
                $names[] = $name;
            }
        }
        return $names;
    }
}
