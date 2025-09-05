<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ConfigDumpCommand extends Command
{
    public const NAME = 'config-dump';

    public function __construct(private EventDispatcherInterface $event_dispatcher)
    {
        parent::__construct(self::NAME);
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription('Dump Tuleap configuration for external re-use (in JSON)')
            ->addArgument('keys', InputArgument::IS_ARRAY, 'Name of the variables requested');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $keys = $input->getArgument('keys');
        if (count($keys) === 0) {
            $this->event_dispatcher->dispatch(new ConfigDumpEvent());
            $output->write(\json_encode(iterator_to_array(\ForgeConfig::getAll()), JSON_THROW_ON_ERROR));
            return 0;
        }

        $json = [];
        foreach ($keys as $key) {
            if (! \ForgeConfig::exists($key)) {
                continue;
            }
            $json[$key] = \ForgeConfig::get($key);
        }
        $output->write(\json_encode($json, JSON_THROW_ON_ERROR));

        return 0;
    }
}
