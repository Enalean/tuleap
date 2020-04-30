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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\Config\ConfigSet;
use Tuleap\Config\InvalidConfigKeyException;

class ConfigSetCommand extends Command
{
    public const NAME = 'config-set';

    /**
     * @var ConfigSet
     */
    private $config_set;

    public function __construct(ConfigSet $config_set)
    {
        parent::__construct(self::NAME);
        $this->config_set = $config_set;
    }

    protected function configure()
    {
        $this->setDescription('Set configuration values')
            ->addArgument('key', InputArgument::REQUIRED, 'Variable key')
            ->addArgument('value', InputArgument::REQUIRED, 'Variable value');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $key = $input->getArgument('key');
            assert(is_string($key));
            $value = $input->getArgument('value');
            assert(is_string($value));

            $this->config_set->set($key, $value);
        } catch (InvalidConfigKeyException $exception) {
            $keys = $exception->getWhiteListedKeys();
            sort($keys, SORT_STRING);
            throw new InvalidArgumentException(self::NAME . " only supports a subset of keys:\n* " . implode("\n* ", $keys));
        }
        return 0;
    }
}
