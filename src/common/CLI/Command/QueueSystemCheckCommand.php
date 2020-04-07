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

use Event;
use EventManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\CLI\DelayExecution\ExecutionDelayedLauncher;
use Tuleap\DB\DBConnection;

final class QueueSystemCheckCommand extends Command
{
    public const NAME = 'queue-system-check';

    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var DBConnection
     */
    private $db_connection;
    /**
     * @var ExecutionDelayedLauncher
     */
    private $execution_delayed_launcher;

    public function __construct(
        EventManager $event_manager,
        DBConnection $db_connection,
        ExecutionDelayedLauncher $execution_delayed_launcher
    ) {
        parent::__construct(self::NAME);
        $this->event_manager              = $event_manager;
        $this->db_connection              = $db_connection;
        $this->execution_delayed_launcher = $execution_delayed_launcher;
    }

    protected function configure(): void
    {
        $this->setDescription('Put a SYSTEM_CHECK event in processing queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->execution_delayed_launcher->execute(function () {
            $this->db_connection->reconnectAfterALongRunningProcess();
            $this->event_manager->processEvent(Event::SYSTEM_CHECK);
        });
        return 0;
    }
}
