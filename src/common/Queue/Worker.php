<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Logger;
use BackendLogger;
use TruncateLevelLogger;
use BrokerLogger;
use Log_ConsoleLogger;
use ForgeConfig;
use Exception;
use EventManager;
use Tuleap\Queue\RabbitMQ\RabbitMQManager;
use Tuleap\System\DaemonLocker;

class Worker
{
    const PID_FILE_PATH = '/var/run/tuleap/worker.pid';

    const LOG_FILE_PATH = '/var/log/tuleap/worker_log';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var DaemonLocker
     */
    private $locker;

    public function __construct()
    {
        $this->makesAllWarningsFatal();
        $this->setLogger(
            new TruncateLevelLogger(
                new BackendLogger(self::LOG_FILE_PATH),
                ForgeConfig::get('sys_logger_level')
            )
        );
    }

    public function main()
    {
        try {
            $options = getopt('vh', array('help'));
            $this->showHelp($options);
            $this->checkWhoIsRunning();
            $this->configureLogger($options);

            $this->locker = new DaemonLocker(self::PID_FILE_PATH);
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
        $this->logger->info("Wait for messages");

        $rabbitmq_manager = new RabbitMQManager($this->logger);
        $channel = $rabbitmq_manager->connect();
        $worker_queue_event = new WorkerGetQueue($this->logger, $this->locker, $channel);
        EventManager::instance()->processEvent($worker_queue_event);
        $rabbitmq_manager->wait();
        $this->logger->info("No messages to process, is RabbitMQ configured and running ?");
    }

    private function configureLogger(array $options)
    {
        if (isset($options['v'])) {
            $this->setLogger(
                new BrokerLogger(
                    array(
                        new Log_ConsoleLogger(),
                        new BackendLogger(self::LOG_FILE_PATH),
                    )
                )
            );
        }
    }

    private function showHelp($options)
    {
        $pid_file_path = self::PID_FILE_PATH;
        $log_file_path = self::LOG_FILE_PATH;
        if (isset($options['h']) || isset($options['help'])) {
            echo <<<"EOT"
Usage: /usr/share/tuleap/src/utils/worker.php [-v] [-h] [--help]

DESCRIPTION

    Handle background jobs for Tuleap.

    Logs are available in {$log_file_path}
    On start pid is registered in {$pid_file_path}

OPTIONS
    -v          Turn logging verbose (logger level to debug) and print on stdout
    -h|--help   Show this help message

EOT;
            exit(0);
        }
    }

    private function makesAllWarningsFatal()
    {
        set_error_handler(
            function ($errno, $errstr, $errfile, $errline) {
                die("$errstr $errfile L$errline Errno $errno\n");
            },
            $this->getCaughtErrorTypes()
        );
    }

    private function setLogger(Logger $logger)
    {
        $this->logger = $logger;
        $this->makesAllWarningsFatalLogger();
        $this->setSignalHandler();
    }

    private function makesAllWarningsFatalLogger()
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

    private function getCaughtErrorTypes()
    {
        return E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED;
    }

    private function setSignalHandler()
    {
        $logger = $this->logger;
        $pcntlHandler = function ($signal) use ($logger) {
            switch ($signal) {
                case \SIGTERM:
                case \SIGUSR1:
                case \SIGINT:
                    // some stuff before stop consumer e.g. delete lock etc
                    if ($this->locker !== null) {
                        unlink(self::PID_FILE_PATH);
                    }
                    $logger->info("Received stop signal, aborting");
                    pcntl_signal($signal, SIG_DFL); // restore handler
                    posix_kill(posix_getpid(), $signal); // kill self with signal, see https://www.cons.org/cracauer/sigint.html
                case \SIGHUP:
                    // some stuff to restart consumer
                    break;
                default:
                    // do nothing
            }
        };

        pcntl_signal(\SIGTERM, $pcntlHandler);
        pcntl_signal(\SIGINT, $pcntlHandler);
        pcntl_signal(\SIGUSR1, $pcntlHandler);
        pcntl_signal(\SIGHUP, $pcntlHandler);
    }

    private function checkWhoIsRunning()
    {
        $user = posix_getpwuid(posix_geteuid());
        if ($user['name'] !== ForgeConfig::get('sys_http_user')) {
            fwrite(STDERR, "This must be run by ".ForgeConfig::get('sys_http_user')."\n");
            exit(255);
        }
    }
}
