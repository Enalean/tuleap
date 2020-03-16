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

declare(strict_types=1, ticks=1);

namespace Tuleap\CLI\Command;

use ForgeConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Process\Process;
use Tuleap\Queue\Worker;
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

    public function __construct(ProcessFactory $process_factory, LockFactory $lock_factory)
    {
        parent::__construct(self::NAME);
        $this->process_factory = $process_factory;
        $this->lock_factory = $lock_factory;
    }

    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Start (and restart) workers according to configuration')
            ->addArgument('action', InputArgument::REQUIRED, 'Possible actions: start|stop|status')
            ->addOption('daemon', 'd', InputOption::VALUE_NONE, 'No output on stdout, should be used with & to run in background. Option only valid for `start`');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        switch ($input->getArgument('action')) {
            case 'start':
                $is_daemon = $input->getOption('daemon');
                assert(is_bool($is_daemon));
                return $this->start($output, $is_daemon);
                break;

            case 'status':
                return $this->status($output);
                break;

            case 'stop':
                return $this->stop($output);
                break;

            default:
                $output->writeln('<error>Invalid action</error>');
                return 1;
        }
    }

    private function start(OutputInterface $output, bool $is_daemon): int
    {
        $this->asserWhoIsRunning();

        $this->registerSignalHandler();

        $this->lock = $this->lock_factory->createLock(self::NAME);
        if (! $this->lock->acquire()) {
            $output->writeln('<error>Supervisor is already running</error>');
            return 1;
        }

        file_put_contents(self::PID_FILE_PATH, getmypid());

        $worker_count = $this->getBackendWorkerCount();
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

    private function asserWhoIsRunning(): void
    {
        $user = posix_getpwuid(posix_geteuid());
        if ($user['name'] !== ForgeConfig::get('sys_http_user')) {
            throw new \RuntimeException(sprintf('Command must be run by %s', ForgeConfig::get('sys_http_user')));
        }
    }

    private function status(OutputInterface $output): int
    {
        $worker_count = $this->getBackendWorkerCount();
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

    private function getBackendWorkerCount(): int
    {
        if (ForgeConfig::get('sys_nb_backend_workers') !== false) {
            return abs((int) ForgeConfig::get('sys_nb_backend_workers'));
        }
        if (ForgeConfig::get('sys_async_emails') !== false) {
            return 1;
        }
        return 0;
    }
}
