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

namespace TuleapCfg\Command\Docker;

use Symfony\Component\Console\Output\OutputInterface;
use TuleapCfg\Command\ProcessFactory;

/**
 * We have to rely on unix rm & mv because php primitives doesn't handle properly the filesystem constraints set by
 * docker
 */
final class DataPersistence
{
    private const BASEDIR = '/data';

    /**
     * @var string[]
     */
    private $paths;
    /**
     * @var ProcessFactory
     */
    private $process_factory;

    public function __construct(ProcessFactory $process_factory, private readonly SSHDaemon $ssh_daemon, string ...$paths)
    {
        $this->process_factory = $process_factory;
        $this->paths           = $paths;
    }

    public function isThereAnyData(): bool
    {
        return is_dir(self::BASEDIR . '/var/lib/tuleap');
    }

    public function store(OutputInterface $output): void
    {
        if (! is_dir(self::BASEDIR) && ! mkdir(self::BASEDIR, 0755) && ! is_dir(self::BASEDIR)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', self::BASEDIR));
        }
        foreach ($this->paths as $path) {
            if (! is_file($path) && ! is_dir($path)) {
                continue;
            }
            $output->writeln("Move $path to persistent storage");
            $this->createBaseDir($path);
            $this->process_factory->getProcess(['/bin/mv', $path, self::BASEDIR . $path])->mustRun();
        }
    }

    private function createBaseDir(string $path): void
    {
        $dirname = self::BASEDIR . dirname($path);
        if (! is_dir($dirname) && ! mkdir($dirname, 0755, true) && ! is_dir($dirname)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dirname));
        }
    }

    public function restore(OutputInterface $output): void
    {
        foreach ($this->paths as $path) {
            if (! is_file(self::BASEDIR . $path) && ! is_dir(self::BASEDIR . $path)) {
                continue;
            }
            if (is_link($path)) {
                continue;
            }
            if (is_dir($path)) {
                $this->process_factory->getProcess(['/bin/rm', '-rf', $path])->mustRun();
            }
            if (is_file($path)) {
                unlink($path);
            }
            $output->writeln("Create link to persistent storage for $path");
            symlink(self::BASEDIR . $path, $path);
        }
        // Some SSH server host keys might have been created with improper rights/owner
        // We might also need to create SSH host keys with new cryptographic algorithms
        $this->ssh_daemon->generateSSHServerKeys();
    }
}
