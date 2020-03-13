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

use ConfigDao;
use EventManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\CLI\Events\GetWhitelistedKeys;

class ConfigSetCommand extends Command
{
    public const NAME = 'config-set';

    /**
     * @var ConfigDao
     */
    private $config_dao;

    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(ConfigDao $config_dao, EventManager $event_manager)
    {
        parent::__construct(self::NAME);
        $this->config_dao    = $config_dao;
        $this->event_manager = $event_manager;
    }

    protected function configure()
    {
        $this->setDescription('Set configuration values')
            ->addArgument('key', InputArgument::REQUIRED, 'Variable key')
            ->addArgument('value', InputArgument::REQUIRED, 'Variable value');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $key = $input->getArgument('key');

        $event = new GetWhitelistedKeys();
        $this->event_manager->processEvent($event);

        $white_listed_keys = $event->getWhiteListedKeys();

        if (! $this->keyIsWhitelisted($white_listed_keys, $key)) {
            throw new InvalidArgumentException(self::NAME . " only supports a subset of keys:\n* " . implode("\n* ", array_keys($white_listed_keys)));
        }

        $value = $input->getArgument('value');

        $this->config_dao->save($key, $value);
        return 0;
    }

    /**
     * @return bool
     */
    private function keyIsWhitelisted(array $white_listed_keys, $key)
    {
        return isset($white_listed_keys[$key]);
    }
}
