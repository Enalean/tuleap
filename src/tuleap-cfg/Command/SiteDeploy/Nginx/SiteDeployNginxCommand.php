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

namespace TuleapCfg\Command\SiteDeploy\Nginx;

use ForgeConfig;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

final class SiteDeployNginxCommand extends Command
{
    public const NAME = 'site-deploy:nginx';

    private const OPT_DEVELOPMENT = 'development';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription('Deploy nginx configuration')
            ->addOption(self::OPT_DEVELOPMENT, '', InputOption::VALUE_NONE, 'Deploy development version of the configuration files');
    }

    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        ForgeConfig::loadInSequence();

        $development    = $input->getOption(self::OPT_DEVELOPMENT) === true;
        $console_logger = new ConsoleLogger($output, [LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL]);
        $deploy         = new SiteDeployNginx(
            $console_logger,
            new NginxServerNamesHashBucketSizeCalculator(new CurrentCPUInformation()),
            __DIR__ . '/../../../../../',
            '/etc/nginx',
            ForgeConfig::get('sys_default_domain'),
            $development
        );
        $deploy->configure();

        return 0;
    }
}
