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

namespace Tuleap\Plugin;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class PluginInstallCommand extends Command
{
    public const NAME = 'plugin:install';
    /**
     * @var \PluginManager
     */
    private $plugin_manager;

    public function __construct(\PluginManager $plugin_manager)
    {
        parent::__construct(self::NAME);
        $this->plugin_manager = $plugin_manager;
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription('Install and activate plugins with their dependencies')
            ->addOption('all', '', InputOption::VALUE_NONE, 'Install all plugins')
            ->addArgument('plugins', InputArgument::IS_ARRAY, 'List of plugins (space separated)');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (posix_getuid() === 0) {
            throw new \RuntimeException('Must be run by `codendiadm`');
        }

        $plugin_names = $input->getArgument('plugins');
        assert(is_array($plugin_names));
        if (count($plugin_names) !== 0) {
            foreach ($plugin_names as $plugin_name) {
                $this->installPlugin($output, $plugin_name);
            }
            return 0;
        }

        if ($input->getOption('all') === true) {
            foreach (array_merge($this->plugin_manager->getAllPlugins(), $this->plugin_manager->getNotYetInstalledPlugins()) as $plugin) {
                $this->installPlugin($output, $plugin->getName());
            }
            return 0;
        }

        $output->writeln('You must either list plugins or use `--all` option');
        return 1;
    }

    private function installPlugin(OutputInterface $output, string $plugin_name): void
    {
        $output->write("Install $plugin_name...");
        $this->plugin_manager->installAndEnable($plugin_name);
        $output->writeln('[OK]');
    }
}
