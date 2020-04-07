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
use Symfony\Component\Lock\LockFactory;
use SystemEvent;
use SystemEventProcessor_Factory;
use SystemEventProcessorMutex;
use Tuleap\DB\DBConnection;

final class ProcessSystemEventsCommand extends Command
{
    public const NAME = 'process-system-events';

    /**
     * @var SystemEventProcessor_Factory
     */
    private $system_event_processor_factory;

    /**
     * @var DBConnection
     */
    private $db_connection;

    /**
     * @var LockFactory
     */
    private $lock_factory;

    public function __construct(
        SystemEventProcessor_Factory $system_event_processor_factory,
        DBConnection $db_connection,
        LockFactory $lock_factory
    ) {
        parent::__construct(self::NAME);

        $this->system_event_processor_factory = $system_event_processor_factory;
        $this->db_connection                  = $db_connection;
        $this->lock_factory                   = $lock_factory;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Process pending system events')
            ->addArgument('queue', InputArgument::REQUIRED, sprintf('Which queue should be run. Default queue is `%s`', SystemEvent::DEFAULT_QUEUE));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $request_queue = $input->getArgument('queue');

        $processor = $this->system_event_processor_factory->getProcessForQueue($request_queue);

        $mutex = new SystemEventProcessorMutex($processor, $this->lock_factory, $this->db_connection);
        $mutex->execute();
        return 0;
    }
}
