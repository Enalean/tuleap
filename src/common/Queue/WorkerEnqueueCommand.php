<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Queue;

use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

final class WorkerEnqueueCommand extends Command
{
    public const NAME = 'worker:enqueue';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    #[\Override]
    public function configure(): void
    {
        $this
            ->setDescription('Send arbitrary payload to queue')
            ->addArgument('topic', InputArgument::REQUIRED, 'Topic on which message should be posted')
            ->addArgument('message', InputArgument::REQUIRED, 'Json encoded message');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $topic = $input->getArgument('topic');
        if (! is_string($topic) || $topic === '') {
            $output->writeln('Topic is missing, not valid string or empty');
            return self::INVALID;
        }
        $message = $input->getArgument('message');
        if (! $message || ! is_string($message)) {
            $output->writeln('Message is missing or not valid string');
            return self::INVALID;
        }

        try {
            $payload = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $output->writeln('Message is missing or not valid string');
            return self::INVALID;
        }

        $logger = new ConsoleLogger($output, [LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL]);

        (new EnqueueTask($logger))->enqueue(new GenericQueueTask($topic, $payload, 'Enqueue from CLI'));

        return self::SUCCESS;
    }
}
