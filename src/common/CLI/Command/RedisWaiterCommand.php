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

use RedisException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\Redis\ClientFactory as RedisClientFactory;
use Tuleap\Redis\RedisNotConnectedException;

class RedisWaiterCommand extends Command
{
    public const NAME = 'wait-for-redis';

    protected function configure()
    {
        $this
            ->setHidden(true)
            ->setName(self::NAME)
            ->setDescription('Poll until redis is up and running');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (! RedisClientFactory::canClientBeBuiltFromForgeConfig()) {
            $output->writeln("No Redis configuration");
            return 1;
        }

        do {
            try {
                $client = RedisClientFactory::fromForgeConfig();
                if ($client->ping() === '+PONG') {
                    break;
                }
            } catch (RedisNotConnectedException $exception) {
                $output->writeln("Redis not connected");
            } catch (RedisException $exception) {
                $output->writeln("Redis not connected: " . $exception->getMessage());
            }
            sleep(1);
        } while (true);

        $output->writeln("Redis is here");
        return 0;
    }
}
