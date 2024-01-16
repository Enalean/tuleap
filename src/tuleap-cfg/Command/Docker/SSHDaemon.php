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
use Symfony\Component\Process\Process;
use TuleapCfg\Command\ProcessFactory;

final class SSHDaemon
{
    private const SSHD_DATA_DIRECTORY          = '/data/etc/ssh';
    private const SUPPORTED_SSHD_HOST_KEY_TYPE = ['ed25519', 'ecdsa', 'rsa'];

    private ?Process $process;

    public function __construct(private readonly ProcessFactory $process_factory)
    {
    }

    public function startDaemon(OutputInterface $output): void
    {
        $output->writeln("Start SSH Daemon");
        $this->generateSSHServerKeys();
        $this->ensureHostPermissionsAndOwnership();
        $this->process = $this->process_factory->getProcess(['/usr/sbin/sshd', '-E', '/dev/stderr', '-D']);
        $this->process->start();
    }

    public function shutdownDaemon(OutputInterface $output): void
    {
        if ($this->process) {
            $output->writeln("Shutdown SSH Daemon");
            $this->process->stop(0, SIGTERM);
        }
    }

    private function generateSSHServerKeys(): void
    {
        \Psl\Filesystem\create_directory(self::SSHD_DATA_DIRECTORY, 0755);

        foreach (self::SUPPORTED_SSHD_HOST_KEY_TYPE as $key_type) {
            $host_key_path = self::SSHD_DATA_DIRECTORY . '/ssh_host_' . $key_type . '_key';

            if (is_file($host_key_path)) {
                continue;
            }

            $this->process_factory->getProcess(
                ['/usr/bin/ssh-keygen', '-t', $key_type, '-f', $host_key_path, '-N', '', '-C', '']
            )->mustRun();
        }
    }

    private function ensureHostPermissionsAndOwnership(): void
    {
        foreach (self::SUPPORTED_SSHD_HOST_KEY_TYPE as $key_type) {
            $host_key_path_private = self::SSHD_DATA_DIRECTORY . '/ssh_host_' . $key_type . '_key';
            $host_key_path_public  = self::SSHD_DATA_DIRECTORY . '/ssh_host_' . $key_type . '_key.pub';

            foreach ([$host_key_path_private, $host_key_path_public] as $key_part) {
                \Psl\Filesystem\change_permissions($key_part, 0600);
                \Psl\Filesystem\change_owner($key_part, 0);
                \Psl\Filesystem\change_group($key_part, 0);
            }
        }
    }
}
