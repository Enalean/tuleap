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

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\Config\ConfigSet;
use Tuleap\Config\GetConfigKeys;
use Tuleap\Config\InvalidConfigKeyException;
use Tuleap\Config\InvalidConfigKeyValueException;
use Tuleap\Option\Option;

class ConfigSetCommand extends Command
{
    public const NAME = 'config-set';

    public function __construct(
        private readonly ConfigSet $config_set,
        private readonly EventDispatcherInterface $event_dispatcher,
    ) {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this->setDescription('Set configuration values')
            ->addArgument('key', InputArgument::REQUIRED, 'Variable key')
            ->addArgument('value', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Variable value');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $key = $input->getArgument('key');
        assert(is_string($key));

        $config_keys  = $this->event_dispatcher->dispatch(new GetConfigKeys());
        $key_metadata = $config_keys->getKeyMetadata($key);
        if ($key_metadata->is_secret) {
            $current_value = Option::nothing(\Psl\Type\string());
        } else {
            $current_value = Option::fromValue(\ForgeConfig::get($key));
        }

        $value = $input->getArgument('value');
        if (is_array($value)) {
            $value = implode(' ', $value);
        }
        assert(is_string($value));

        try {
            $this->config_set->set($key, $value);
        } catch (InvalidConfigKeyException $exception) {
            $keys = $exception->getConfigKeys();
            sort($keys, SORT_STRING);
            throw new InvalidArgumentException(self::NAME . " only supports a subset of keys:\n* " . implode("\n* ", $keys));
        } catch (InvalidConfigKeyValueException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $success_message = $current_value->mapOr(
            fn (mixed $current_value_raw): string => sprintf(
                "%s has been successfully updated from '%s' to '%s'",
                OutputFormatter::escape($key),
                OutputFormatter::escape($current_value_raw),
                OutputFormatter::escape($value),
            ),
            sprintf(
                "%s has been successfully updated",
                OutputFormatter::escape($key)
            ),
        );

        $output->writeln('<info>' . $success_message . '</info>');

        return self::SUCCESS;
    }
}
