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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QueueSystemCheckCommand extends Command
{

    public const NAME = 'queue-system-check';
    /**
     * @var \EventManager
     */
    private $event_manager;

    public function __construct(\EventManager $event_manager)
    {
        parent::__construct(self::NAME);
        $this->event_manager = $event_manager;
    }

    protected function configure()
    {
        $this->setDescription('Put a SYSTEM_CHECK event in processing queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (getenv('TLP_DELAY_CRON_CMD') === '1') {
            try {
                sleep(random_int(0, 1799));
            } catch (\Exception $e) {
                $error_output = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
                $error_output->writeln('Unable to get a random time for delay, aborting');
                return 1;
            }
        }

        $this->event_manager->processEvent(Event::SYSTEM_CHECK);
    }
}
