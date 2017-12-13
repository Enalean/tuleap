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

namespace Tuleap\Tracker\Artifact\Changeset;

use Tracker_ArtifactFactory;
use Logger;
use BackendLogger;
use TruncateLevelLogger;
use BrokerLogger;
use Log_ConsoleLogger;
use ForgeConfig;
use Exception;
use Tuleap\Queue\Factory;
use Tuleap\System\DaemonLocker;
use Tuleap\Tracker\Artifact\Changeset\Notification\Notifier;

class AsynchronousNotifier
{
    const MAX_MESSAGES = 1000;

    const QUEUE_PREFIX = 'update';

    const TOPIC = 'tuleap.tracker.artifact';

    private $log_file_path = '/var/log/tuleap/tuleap_tracker_notify_log';

    private $pid_file_path = '/var/run/tuleap/tracker_notify.pid';
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
                new BackendLogger($this->log_file_path),
                ForgeConfig::get('sys_logger_level')
            )
        );
    }

    public function main()
    {
        try {
            $options = getopt('vh', array('help'));
            $this->showHelp($options);
            $this->configureLogger($options);

            $this->locker = new DaemonLocker($this->pid_file_path);
            $this->locker->isRunning();

            $this->logger->info("Start service");

            $this->listen('notify-1');
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            exit(1);
        }
    }

    /**
     * @param string $server_id
     */
    private function listen($server_id)
    {
        $this->logger->info("Wait for messages");

        $notifier = Notifier::build($this->logger);

        $logger = $this->logger;
        $locker = $this->locker;
        $message_counter = 0;

        $queue  = Factory::getPersistentQueue($this->logger, self::QUEUE_PREFIX);
        $queue->listen('update_'.$server_id, self::TOPIC, function ($msg) use ($logger, &$message_counter, $notifier, $locker) {
            try {
                $logger->info("Received ".$msg->body);

                $message = json_decode($msg->body, true);
                $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($message['artifact_id']);
                $changeset = $artifact->getChangeset($message['changeset_id']);

                $notifier->processNotify($changeset);
                $message_counter++;

                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                $logger->info("Notification completed [$message_counter/".self::MAX_MESSAGES."]");

                if ($message_counter >= self::MAX_MESSAGES) {
                    $logger->info("Max messages reached, exiting...");
                    $locker->cleanExit();
                }
            } catch (Exception $e) {
                $logger->error("Caught exception ".get_class($e).": ".$e->getMessage());
            }
        });
    }

    private function configureLogger(array $options)
    {
        if (isset($options['v'])) {
            $this->setLogger(
                new BrokerLogger(
                    array(
                        new Log_ConsoleLogger(),
                        new BackendLogger($this->log_file_path),
                    )
                )
            );
        }
    }

    private function showHelp($options)
    {
        if (isset($options['h']) || isset($options['help'])) {
            echo <<<"EOT"
Usage: /usr/share/tuleap/plugins/tracker/bin/notify.php [-v] [-h] [--help]

DESCRIPTION
    Handle tracker email notification job.

    This fetches artifact updates stored by front-end in RabbitMQ, formats and sends emails to recipients.
    It will process 1000 messages before exiting to avoid memleak by PHP engine.

    Logs are available in {$this->log_file_path}
    On start pid is registered in {$this->pid_file_path}

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
                        unlink($this->pid_file_path);
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
}
