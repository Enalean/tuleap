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
 */

declare(strict_types=1);

namespace Tuleap\Queue\TaskWorker;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Tuleap\CLI\DelayExecution\ConditionalTuleapCronEnvExecutionDelayer;
use Tuleap\DB\ReconnectAfterALongRunningProcess;
use Tuleap\Queue\WorkerEventContent;

final class TaskWorkerProcess implements TaskWorker
{
    private const PROCESS_TIMEOUT_SECONDS          = 15 * 60;
    private const TULEAP_SOURCE_ROOT               = __DIR__ . '/../../../../';
    private const CONNECTION_KEEP_ALIVE_NB_SECONDS = 15;

    public function __construct(
        private readonly ReconnectAfterALongRunningProcess $connection_to_keep_alive,
    ) {
    }

    #[\Override]
    public function run(WorkerEventContent $worker_event_content): void
    {
        $process = new Process(
            ['tuleap', TaskWorkerProcessCommand::NAME],
            self::TULEAP_SOURCE_ROOT,
            [
                'SHELL_VERBOSITY' => '1',
                ConditionalTuleapCronEnvExecutionDelayer::DELAY_ENV_VAR_NAME => '0',
            ]
        );
        $process->setTimeout(self::PROCESS_TIMEOUT_SECONDS);
        $encoded_worker_event_content = \Psl\Json\encode($worker_event_content);
        $process->setInput($encoded_worker_event_content);
        $process->start();

        $last_connection_check_elapsed_time = 0;

        while ($process->isRunning()) {
            $elapsed_time = microtime(true) - $process->getStartTime();
            if (($elapsed_time - $last_connection_check_elapsed_time) > 15) {
                $this->connection_to_keep_alive->reconnectAfterALongRunningProcess();
                $last_connection_check_elapsed_time = $elapsed_time;
            }
            if (self::PROCESS_TIMEOUT_SECONDS < $elapsed_time) {
                $process->stop(0);
                throw new TaskWorkerTimedOutException($encoded_worker_event_content, self::CONNECTION_KEEP_ALIVE_NB_SECONDS);
            }
            usleep(1000);
        }

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
