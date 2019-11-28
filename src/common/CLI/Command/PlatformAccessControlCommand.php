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
 */

namespace Tuleap\CLI\Command;

use ConfigDao;
use EventManager;
use ForgeAccess;
use ForgeAccess_ForgePropertiesManager;
use ForgeConfig;
use PermissionsManager;
use ProjectHistoryDao;
use ProjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\ForgeAccess\UnknownForgeAccessValueException;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\FRSPermissionDao;
use UGroupDao;

class PlatformAccessControlCommand extends Command
{
    public const NAME = '--platform-access-control';
    public const ACCESS_CONTROL_ARGUMENT = 'access_control_level';

    protected function configure()
    {
        $this->setName(self::NAME)->setDescription('Show or set the platform access control');
        $this->addArgument(self::ACCESS_CONTROL_ARGUMENT, InputArgument::OPTIONAL);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $current_platform_access_value = ForgeConfig::get(ForgeAccess::CONFIG);
        $new_access_value = $input->getArgument(self::ACCESS_CONTROL_ARGUMENT);
        if ($new_access_value === null) {
            $output->writeln($current_platform_access_value);
            return 0;
        }

        $forge_access_properties_manager = new ForgeAccess_ForgePropertiesManager(
            new ConfigDao(),
            ProjectManager::instance(),
            PermissionsManager::instance(),
            EventManager::instance(),
            new FRSPermissionCreator(
                new FRSPermissionDao(),
                new UGroupDao(),
                new ProjectHistoryDao()
            )
        );

        try {
            $forge_access_properties_manager->updateAccess($new_access_value, $current_platform_access_value);
        } catch (UnknownForgeAccessValueException $e) {
            throw new InvalidArgumentException($e->getMessage());
        }

        return 0;
    }
}
