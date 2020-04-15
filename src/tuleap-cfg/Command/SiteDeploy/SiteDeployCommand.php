<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace TuleapCfg\Command\SiteDeploy;

use Config_LocalIncFinder;
use ForgeConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class SiteDeployCommand extends Command
{
    public const NAME = 'site-deploy';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this->setDescription('Execute all deploy actions needed at site update');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ForgeConfig::loadFromFile(__DIR__ . '/../../../etc/local.inc.dist');
        ForgeConfig::loadFromFile((new Config_LocalIncFinder())->getLocalIncPath());

        (new SiteDeployImages())->deploy($output);
        return 0;
    }
}
