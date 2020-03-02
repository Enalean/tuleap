<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Test\Psalm;

use Psalm\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class PsalmCILauncher extends Command
{
    /**
     * @var ShellPassthrough
     */
    private $shell_passthrough;

    public function __construct(ShellPassthrough $shell_passthrough)
    {
        parent::__construct('psalm-ci-launcher');
        $this->shell_passthrough = $shell_passthrough;
    }

    protected function configure() : void
    {
        $this->setDescription('Launch Psalm on a contribution')
            ->addOption('config', null, InputOption::VALUE_REQUIRED, 'Path to the Psalm configuration file', 'tests/psalm/psalm.xml')
            ->addOption('base-dir', null, InputOption::VALUE_REQUIRED, 'Project root', __DIR__ . '/../../../')
            ->addOption('report-folder', null, InputOption::VALUE_REQUIRED, 'Folder containing the report', __DIR__ . '/../../../')
            ->addArgument('modified-files', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'List of files to inspect');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $config_path = (string) $input->getOption('config');
        $base_dir    = realpath((string) $input->getOption('base-dir')) . DIRECTORY_SEPARATOR;

        $config = Config::loadFromXML($base_dir, file_get_contents($config_path));

        $files_to_inspect = [];
        foreach ((array) $input->getArgument('modified-files') as $modified_file) {
            $modified_file_full_path = $base_dir . $modified_file;
            if (is_file($modified_file_full_path) && $config->isInProjectDirs($modified_file_full_path)) {
                $files_to_inspect[] = $modified_file_full_path;
            }
        }
        if (empty($files_to_inspect)) {
            $output->writeln('No files to inspect with Psalm');
            return 0;
        }

        $files_to_inspect_shell_escaped = [];
        foreach ($files_to_inspect as $file_to_inspect) {
            $files_to_inspect_shell_escaped[] = escapeshellarg($file_to_inspect);
        }

        $report_folder = (string) $input->getOption('report-folder');
        if (! is_dir($report_folder)) {
            mkdir($report_folder, 0777, true);
        }

        return ($this->shell_passthrough)(
            __DIR__ . '/../../../src/vendor/bin/psalm -c=' . escapeshellarg($config_path) . ' ' .
            '--show-info=false  --report-show-info=false ' .
            '--threads=1 ' .
            '--root=' . escapeshellarg($base_dir) . ' ' .
            '--report=' . escapeshellarg($report_folder . '/checkstyle.xml') . ' ' .
            implode(' ', $files_to_inspect_shell_escaped)
        );
    }
}
