<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Queue;

use Psr\Log\LoggerInterface;
use TruncateLevelLogger;
use BrokerLogger;
use Log_ConsoleLogger;
use ForgeConfig;
use Exception;
use Tuleap\DB\ThereIsAnOngoingTransactionChecker;
use Tuleap\Queue\TaskWorker\TaskWorkerProcess;
use Tuleap\Queue\TaskWorker\TaskWorkerTimedOutException;
use Tuleap\System\DaemonLocker;

class Worker
{
    public const EVENT_QUEUE_NAME = 'app_user_events';

    public const DEFAULT_PID_FILE_PATH = '/var/run/tuleap/worker.pid';

    private $id = 0;
    private $pid_file;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DaemonLocker
     */
    private $locker;

    public function __construct()
    {
        $this->pid_file = self::DEFAULT_PID_FILE_PATH;
    }

    public function main()
    {
        try {
            $options = getopt('vh', ['help', 'id:']);
            $this->showHelp($options);
            $this->checkWhoIsRunning();
            $this->configureRunner($options);
            $this->configureLogger($options);

            $this->locker = new DaemonLocker($this->pid_file);
            $this->locker->isRunning();

            $this->logger->info("Start service");

            $this->listen();
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            exit(1);
        }
    }

    private function listen()
    {
        $this->logger->info('Wait for messages');

        $task_worker = new TaskWorkerProcess();

        $queue = (new QueueFactory($this->logger, new ThereIsAnOngoingTransactionChecker()))->getPersistentQueue(self::EVENT_QUEUE_NAME, QueueFactory::REDIS);
        $queue->listen($this->id, '*', function (string $event) use ($task_worker): void {
            $this->logger->info('Got message: ' . $event);
            try {
                $task_worker->run($event);
            } catch (TaskWorkerTimedOutException $exception) {
                $this->logger->error($exception->getMessage());
            }
        });
        $this->logger->info('All message processed, exiting');
        $this->locker->cleanExit();
    }

    private function configureRunner(array $options)
    {
        if (isset($options['id'])) {
            if (ctype_digit((string) $options['id']) && $options['id'] >= 0) {
                $this->id       = (int) $options['id'];
                $this->pid_file = self::getPidFilePath($this->id);
            } else {
                $this->cliError("Invalid 'id' it should be a positive integer\n");
            }
        }
    }

    private function configureLogger(array $options)
    {
        if (isset($options['v'])) {
            $this->setLogger(
                new BrokerLogger(
                    [
                        new Log_ConsoleLogger(),
                        WorkerLogger::get(),
                    ]
                )
            );
        } else {
            $this->setLogger(
                new TruncateLevelLogger(
                    WorkerLogger::get(),
                    ForgeConfig::get('sys_logger_level')
                )
            );
        }
    }

    private function showHelp($options)
    {
        if (isset($options['h']) || isset($options['help'])) {
            echo <<<"EOT"
Usage: /usr/share/tuleap/src/utils/worker.php [-v] [-h] [--help] [--id=X]

DESCRIPTION

    Handle background jobs for Tuleap.

    On start pid is registered in {$this->pid_file}

OPTIONS
    -v          Turn logging verbose (logger level to debug) and print on stdout
    -h|--help   Show this help message
    --id=X      Start worker with an alternate id (X being a positive integer)
                It's useful when you want to process more events in parallel.

EOT;
            exit(0);
        }
    }

    private function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->setErrorHandler();
    }

    private function setErrorHandler()
    {
        $logger = $this->logger;
        set_error_handler(
            function ($errno, $errstr, $errfile, $errline) use ($logger) {
                $logger->error("$errstr $errfile L$errline Errno $errno");
                exit(1);
            },
            $this->getCaughtErrorTypes()
        );
    }

    /**
     * List Error types that are fatal
     *
     * Unfortunately, Tuleap code is not robust enough yet to make everything fatal.
     * E_WARNING are needed because of mysql_query that returns are warning if mysql is gone. Without that we cannot
     * silently re-execute the query
     *
     * @return int
     */
    private function getCaughtErrorTypes()
    {
        return E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED & ~E_WARNING;
    }

    private function checkWhoIsRunning()
    {
        $user = posix_getpwuid(posix_geteuid());
        if ($user['name'] !== ForgeConfig::get('sys_http_user')) {
            $this->cliError("This must be run by " . ForgeConfig::get('sys_http_user') . "\n");
        }
    }

    private function cliError($error_msg)
    {
        fwrite(STDERR, $error_msg);
        exit(255);
    }

    public static function getWorkerPid(int $id): int
    {
        $pid_file = self::getPidFilePath($id);
        if (file_exists($pid_file)) {
            return (int) trim(file_get_contents($pid_file));
        }
        return -1;
    }

    public static function isWorkerRunning(int $id): bool
    {
        $pid = self::getWorkerPid($id);
        if ($pid > 0) {
            $ret = posix_kill($pid, SIG_DFL);
            return $ret === true;
        }
        return false;
    }

    private static function getPidFilePath(int $id): string
    {
        if ($id === 0) {
            return self::DEFAULT_PID_FILE_PATH;
        }
        return sprintf('/var/run/tuleap/worker_%d.pid', $id);
    }
}
