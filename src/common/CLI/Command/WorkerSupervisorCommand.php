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
declare(ticks=1);

namespace Tuleap\CLI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Process\Process;
use Tuleap\CLI\AssertRunner;
use Tuleap\Queue\Worker;
use Tuleap\Queue\WorkerAvailability;
use TuleapCfg\Command\ProcessFactory;

class WorkerSupervisorCommand extends Command
{
    public const NAME = 'worker:supervisor';

    private const PID_FILE_PATH = '/var/run/tuleap/worker_supervisor.pid';

    private const EVENT_LOOP_SLEEP = 5;

    /**
     * @var Process[]
     */
    private $processes;
    /**
     * @var ProcessFactory
     */
    private $process_factory;
    /**
     * @var LockFactory
     */
    private $lock_factory;
    /**
     * @var LockInterface
     */
    private $lock;
    /**
     * @var WorkerAvailability
     */
    private $worker_availability;

    public function __construct(
        ProcessFactory $process_factory,
        LockFactory $lock_factory,
        WorkerAvailability $worker_availability,
        private AssertRunner $assert_runner,
    ) {
        parent::__construct(self::NAME);
        $this->process_factory     = $process_factory;
        $this->lock_factory        = $lock_factory;
        $this->worker_availability = $worker_availability;
    }

    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Start (and restart) workers according to configuration')
            ->addArgument('action', InputArgument::REQUIRED, 'Possible actions: start|stop|status')
            ->addOption('daemon', 'd', InputOption::VALUE_NONE, 'No output on stdout, should be used with & to run in background. Option only valid for `start`');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        switch ($input->getArgument('action')) {
            case 'start':
                $is_daemon = $input->getOption('daemon');
                assert(is_bool($is_daemon));
                return $this->start($output, $is_daemon);

            case 'status':
                return $this->status($output);

            case 'stop':
                return $this->stop($output);

            default:
                $output->writeln('<error>Invalid action</error>');
                return Command::FAILURE;
        }
    }

    private function start(OutputInterface $output, bool $is_daemon): int
    {
        $this->assert_runner->assertProcessIsExecutedByExpectedUser();

        $this->registerSignalHandler();

        $this->lock = $this->lock_factory->createLock(self::NAME);
        if (! $this->lock->acquire()) {
            $output->writeln('<error>Supervisor is already running</error>');
            return 1;
        }

        file_put_contents(self::PID_FILE_PATH, (string) getmypid());

        $worker_count = $this->worker_availability->getWorkerCount();
        if ($worker_count < 1) {
            $output->writeln('<info>Platform is not configured to use workers</info>');
            return 0;
        }

        $this->processes = [];
        for ($i = 0; $i < $worker_count; $i++) {
            $this->processes[$i] = $this->startNewWorker($i);
        }

        do {
            for ($i = 0; $i < $worker_count; $i++) {
                if ($this->processes[$i]->isRunning()) {
                    if (! $is_daemon) {
                        $this->outputWorkerLogs($this->processes[$i], $output);
                    }
                } else {
                    if (! $is_daemon) {
                        $output->writeln("Restart worker $i");
                    }
                    $this->processes[$i] = $this->startNewWorker($i);
                }
            }
            sleep(self::EVENT_LOOP_SLEEP);
        } while (true);

        $this->lock->release();

        return 0;
    }

    private function registerSignalHandler(): void
    {
        pcntl_signal(SIGTERM, $this->getIntSignalHandler());
        pcntl_signal(SIGINT, $this->getIntSignalHandler());
    }

    private function getIntSignalHandler(): callable
    {
        return function ($signo) {
            foreach ($this->processes as $process) {
                $process->stop();
            }
            if ($this->lock) {
                $this->lock->release();
            }
            unlink(self::PID_FILE_PATH);
            exit(0);
        };
    }

    private function startNewWorker(int $i): Process
    {
        $worker = $this->process_factory->getProcess(['/usr/share/tuleap/src/utils/worker.php', '-v', '--id=' . $i]);
        $worker->setTimeout(0);
        $worker->start();
        return $worker;
    }

    private function outputWorkerLogs(Process $process, OutputInterface $output): void
    {
        $str = $process->getIncrementalErrorOutput();
        if ($str === '') {
            return;
        }

        $pid = $process->getPid();
        if ($pid === null) {
            return;
        }

        foreach (explode("\n", $str) as $line) {
            if ($line === '') {
                continue;
            }
            $output->writeln(sprintf('%s [%d] %s', date('c'), $pid, OutputFormatter::escape($line)));
        }
    }

    private function status(OutputInterface $output): int
    {
        $worker_count = $this->worker_availability->getWorkerCount();
        for ($i = 0; $i < $worker_count; $i++) {
            if (! Worker::isWorkerRunning($i)) {
                $output->writeln(sprintf('<error>Worker %d (pid %d) is not running, try to restart</error>', $i, Worker::getWorkerPid($i)));
            } else {
                $output->writeln(sprintf('<info>Worker %d (pid %d) is running</info>', $i, Worker::getWorkerPid($i)));
            }
        }
        return 0;
    }

    private function stop(OutputInterface $output): int
    {
        $this->lock = $this->lock_factory->createLock(self::NAME);
        if ($this->lock->acquire()) {
            $output->writeln('<error>No supervisor to stop</error>');
            $this->lock->release();
            return 1;
        }

        if (! file_exists(self::PID_FILE_PATH)) {
            $output->writeln(sprintf('<error>No pid file found (%s) cannot stop supervisor</error>', self::PID_FILE_PATH));
            return 1;
        }

        $pid = (int) trim(file_get_contents(self::PID_FILE_PATH));
        if (posix_kill($pid, SIGTERM) !== true) {
            $output->writeln(sprintf('<error>Unable to send TERM to pid %d, maybe user mismatch ?</error>', $pid));
            return 1;
        }

        $output->writeln('<info>Supervisor stopped</info>');
        return 0;
    }
}
