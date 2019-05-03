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

namespace TuleapCfg\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigureCommand extends Command
{
    /**
     * @var string
     */
    private $base_directory;

    public function __construct(?string $base_directory = null)
    {
        $this->base_directory = $base_directory ?: '/';

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('configure')
            ->setDescription('Configure Tuleap')
            ->addArgument('module', InputArgument::REQUIRED, 'Which module do you want to configure: only apache at the moment');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (! file_exists($this->base_directory.'/etc/httpd/conf/httpd.conf') && ! file_exists($this->base_directory . '/etc/httpd/conf.d/ssl.conf')) {
            $output->writeln('Nothing to do for Apache');
            return;
        }

        try {
            $configured = $this->configureHTTPDConf($this->base_directory.'/etc/httpd/conf/httpd.conf');
            $configured |= $this->configureSSLConf($this->base_directory . '/etc/httpd/conf.d/ssl.conf');

            if ($configured) {
                $output->writeln('Apache has been configured');
            } else {
                $output->writeln('Apache is already configured');
            }
            return 0;
        } catch (PermissionsDeniedException $exception) {
            $error_output = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
            $error_output->writeln($exception->getMessage());
            return 1;
        }
    }

    private function configureHTTPDConf(string $file_path) : bool
    {
        if (! file_exists($file_path)) {
            return false;
        }
        if (! is_writable($file_path)) {
            throw new PermissionsDeniedException($file_path.' is not writable by current user (uid '.posix_getuid().')');
        }

        $content = file_get_contents($file_path);
        $new_content = preg_replace(
            [
                '/^User.*$/m',
                '/^Group.*$/m',
                '/^Listen 80$/m',
            ],
            [
                'User codendiadm',
                'Group codendiadm',
                'Listen 127.0.0.1:8080',
            ],
            $content
        );
        if ($content !== $new_content) {
            file_put_contents($file_path, $new_content);
            return true;
        }
        return false;
    }

    private function configureSSLConf(string $file_path) : bool
    {
        if (! file_exists($file_path)) {
            return false;
        }
        if (! is_writable($file_path)) {
            throw new PermissionsDeniedException($file_path.' is not writable by current user (uid '.posix_getuid().')');
        }

        $content = file_get_contents($file_path);
        $new_content = preg_replace(
            [
                '/^Listen (.*)$/m',
                '/^SSLEngine .*/m',
            ],
            [
                '#Listen $1',
                'SSLEngine off',
            ],
            $content
        );
        if ($content !== $new_content) {
            file_put_contents($file_path, $new_content);
            return true;
        }
        return false;
    }
}
