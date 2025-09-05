<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\CLI\Command;

use ForgeConfig;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\Config\GetConfigKeys;

class ConfigGetCommand extends Command
{
    public const NAME = 'config-get';

    public function __construct(private EventDispatcherInterface $event_dispatcher)
    {
        parent::__construct(self::NAME);
    }

    #[\Override]
    protected function configure()
    {
        $this->setDescription('Get configuration values')
            ->addOption('reveal-secret', '', InputOption::VALUE_NONE, 'If configuration variable hold a secret, this switch will decrypt it.')
            ->addArgument('key', InputArgument::REQUIRED, 'Variable key');
    }

    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $key = $input->getArgument('key');
        if (! ForgeConfig::exists($key)) {
            throw new InvalidArgumentException("Invalid key $key");
        }

        $config_keys  = $this->event_dispatcher->dispatch(new GetConfigKeys());
        $key_metadata = $config_keys->getKeyMetadata($key);

        if ($input->getOption('reveal-secret')) {
            if (! $key_metadata->is_secret) {
                 throw new InvalidArgumentException("Misusage of --reveal-secret: $key is not a secret.");
            }
            $value = ForgeConfig::getSecretAsClearText($key);
        } else {
            if ($key_metadata->is_secret) {
                 throw new InvalidArgumentException("Unable to display secret $key. Please use --reveal-secret option to decrypt it.");
            }
            $value = ForgeConfig::get($key);
        }
        $output->write($value);
        return 0;
    }
}
