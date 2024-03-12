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

use Psr\Log\LoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\Queue\FindWorkerEventProcessor;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Queue\WorkerEventProcessor;

final class TaskWorkerProcessCommand extends Command
{
    public const NAME = 'queue:task-worker-process';

    public function __construct(
        private readonly EventDispatcherInterface $event_dispatcher,
        private readonly LoggerInterface $logger,
        private readonly FindWorkerEventProcessor $find_worker_event_processor,
    ) {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this->setDescription('Process one async event');
        $this->setHidden(true);
        $this->addArgument(
            'input_file',
            InputArgument::OPTIONAL,
            'File containing the JSON serialized event',
            'php://stdin'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path_file_event = $input->getArgument('input_file');
        assert(is_string($path_file_event));
        $event_string = file_get_contents($path_file_event);
        $this->logger->debug('Starting to process message: ' . $event_string);
        $event              = json_decode($event_string, true, 512, JSON_THROW_ON_ERROR);
        $worker_queue_event = new WorkerEvent($this->logger, $event);
        $this->find_worker_event_processor
            ->findFromWorkerEvent($worker_queue_event)
            ->match(
                static fn (WorkerEventProcessor $event_processor) => $event_processor->process(),
                fn () => $this->event_dispatcher->dispatch($worker_queue_event),
            );

        return 0;
    }
}
