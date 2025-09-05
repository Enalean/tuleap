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

namespace TuleapCfg\Command\SiteDeploy\Plugins;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\Plugin\PluginLoader;
use TuleapCfg\Command\ProcessFactory;

final class SiteDeployPluginsCommand extends Command
{
    public const NAME = 'site-deploy:plugins-update';

    public function __construct(private ProcessFactory $process_factory)
    {
        parent::__construct(self::NAME);
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription('Deploy/call plugin updates');
    }

    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        PluginLoader::invalidateCache();
        $process = $this->process_factory->getProcessWithoutTimeout(
            ['sudo', '-u', 'codendiadm', 'tuleap', 'plugins_administration:update_hook']
        );
        $process->run();
        $output->write($process->getIterator());

        return $process->getExitCode() ?? 0;
    }
}
