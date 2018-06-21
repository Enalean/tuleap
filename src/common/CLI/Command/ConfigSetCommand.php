<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
use ForgeConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\Instrument\Collect;

class ConfigSetCommand extends Command
{
    const NAME = 'config-set';
    /**
     * @var ConfigDao
     */
    private $white_listed_keys = [
        \ProjectManager::CONFIG_PROJECT_APPROVAL => true,
        \ProjectManager::CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION_PER_USER => true,
        \ProjectManager::CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION => true,
        \ForgeAccess::ANONYMOUS_CAN_SEE_CONTACT => true,
        \ForgeAccess::ANONYMOUS_CAN_SEE_SITE_HOMEPAGE => true,
        \ForgeAccess::PROJECT_ADMIN_CAN_CHOOSE_VISIBILITY => true,
        Collect::CONFIG_PROMETHEUS_PLATFORM => true,
    ];
    /**
     * @var ConfigDao
     */
    private $config_dao;

    public function __construct(ConfigDao $config_dao)
    {
        parent::__construct(self::NAME);
        $this->config_dao = $config_dao;
    }

    protected function configure()
    {
        $this->setDescription('Set configuration values')
            ->addArgument('key', InputArgument::REQUIRED, 'Variable key')
            ->addArgument('value', InputArgument::REQUIRED, 'Variable value');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $key = $input->getArgument('key');

        if (! $this->keyIsWhitelisted($key)) {
            throw new InvalidArgumentException(self::NAME." only supports a subset of keys:\n* ".implode("\n* ", array_keys($this->white_listed_keys)));
        }

        $value = $input->getArgument('value');

        $this->config_dao->save($key, $value);
    }

    private function keyIsWhitelisted($key)
    {
        return isset($this->white_listed_keys[$key]);
    }
}
