<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\System;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\BuildVersion\FlavorFinderFromFilePresence;
use Tuleap\BuildVersion\VersionPresenter;
use Tuleap\Config\GetConfigKeys;
use Tuleap\ServerHostname;
use ZipArchive;

final class CollectSystemDataCommand extends Command
{
    public const NAME = 'collect-system-data';

    private const KEYS_THAT_SHOULD_NOT_BE_DISCLOSED = [
        'sys_dbpasswd',
    ];

    public function __construct(private EventDispatcherInterface $event_dispatcher)
    {
        parent::__construct(self::NAME);
    }

    public function configure(): void
    {
        $this
            ->setDescription('Collect system data to help troubleshooting')
            ->setHelp(<<<EOT
            This command will collect the following data:
            - /var/log/tuleap
            - /var/log/nginx
            - The version of Tuleap
            - The configuration of Tuleap (local.inc)

            If given path is a directory, the command will create a zip file in it. If it's not a directory, we assume
            it's a file that should be created and then it must ends with .zip

            In all cases, the created file will be given on the output of the command

            EOT)
            ->addArgument('path', InputArgument::REQUIRED, 'Path where data will be collected');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $current_date = new \DateTimeImmutable();
        $given_path   = $input->getArgument('path');
        if (is_dir($given_path)) {
            $filename  = ServerHostname::rawHostname() . '_' . $current_date->format('Y-m-d_H-i-s') . '.zip';
            $full_path = $given_path . '/' . $filename;
        } elseif (! str_ends_with($given_path, '.zip')) {
            $full_path = $given_path . '.zip';
        } else {
            $full_path = $given_path;
        }

        $output->writeln('Archive is located at ' . $full_path);
        $prefix = basename($full_path, '.zip');

        $archive = new \ZipArchive();
        $archive->open($full_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $archive->setArchiveComment(sprintf('Generated on %s at %s', ServerHostname::rawHostname(), date('Y-m-d H:i:s')));

        $this->gatherVersion($archive, $prefix);
        $this->gatherLogs($archive, $prefix);
        $this->gatherHardwareInformation($archive, $prefix);
        $this->gatherConfiguration($archive, $prefix);

        $archive->close();
        return self::SUCCESS;
    }

    private function gatherVersion(ZipArchive $archive, string $prefix): void
    {
        $this->addCompressedString($archive, $prefix, VersionPresenter::fromFlavorFinder(new FlavorFinderFromFilePresence())->getFullDescriptiveVersion(), 'VERSION');
    }

    private function gatherLogs(ZipArchive $archive, string $prefix): void
    {
        $archive->addPattern('/.*(_syslog|_log|_error)$/', '/var/log/tuleap', ['add_path' => $prefix . '/log/tuleap/', 'remove_all_path' => true, 'comp_method' => ZipArchive::CM_XZ]);
        $this->addCompressedFile($archive, $prefix, '/var/log/nginx/error.log', 'log/nginx/error.log');
        $this->addCompressedFile($archive, $prefix, '/var/log/nginx/access.log', 'log/nginx/access.log');
    }

    private function gatherHardwareInformation(ZipArchive $archive, string $prefix): void
    {
        $this->addCompressedString($archive, $prefix, file_get_contents('/proc/cpuinfo'), 'cpuinfo');
        $this->addCompressedString($archive, $prefix, file_get_contents('/proc/meminfo'), 'meminfo');
    }

    private function gatherConfiguration(ZipArchive $archive, string $prefix): void
    {
        $config_keys     = $this->event_dispatcher->dispatch(new GetConfigKeys());
        $config_metadata = $config_keys->getSortedKeysWithMetadata();

        $keys = [];
        foreach (\ForgeConfig::getAll() as $key => $value) {
            if (isset($config_metadata[$key]) && $config_metadata[$key]->is_secret) {
                $value = '...';
            }
            if (in_array($key, self::KEYS_THAT_SHOULD_NOT_BE_DISCLOSED, true)) {
                $value = '...';
            }
            $keys[$key] = $value;
        }
        $this->addCompressedString($archive, $prefix, \json_encode($keys, 512, JSON_THROW_ON_ERROR), 'config.json');
    }

    private function addCompressedString(ZipArchive $archive, string $prefix, string $source_string, string $destination_path): void
    {
        $archive_path = $prefix . '/' . $destination_path;
        $archive->addFromString($archive_path, $source_string);
        $archive->setCompressionName($archive_path, ZipArchive::CM_XZ);
    }

    private function addCompressedFile(ZipArchive $archive, string $prefix, string $source_path, string $destination_path): void
    {
        $archive_path = $prefix . '/' . $destination_path;
        $archive->addFile($source_path, $archive_path);
        $archive->setCompressionName($archive_path, ZipArchive::CM_XZ);
    }
}
