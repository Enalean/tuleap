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

use Psr\Log\LoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\Cron\EventCronJobEveryMinute;
use Tuleap\DB\DBConnection;

final class LaunchEveryMinuteJobCommand extends Command
{
    public const NAME = 'launch-every-minute-job';

    /**
     * @var EventDispatcherInterface
     */
    private $event_dispatcher;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(EventDispatcherInterface $event_dispatcher, LoggerInterface $logger, DBConnection $db_connection)
    {
        parent::__construct(self::NAME);
        $this->event_dispatcher = $event_dispatcher;
        $this->logger           = $logger;
    }

    protected function configure(): void
    {
        $this->setDescription('Trigger an event that is supposed to be launched every minute by Cron or SystemD timers');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->event_dispatcher->dispatch(new EventCronJobEveryMinute($this->logger));
        return 0;
    }
}
