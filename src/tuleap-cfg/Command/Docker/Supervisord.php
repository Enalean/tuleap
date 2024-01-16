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

final class Supervisord
{
    public const UNIT_CROND           = 'crond';
    public const UNIT_FPM             = 'fpm';
    public const UNIT_HTTPD           = 'httpd';
    public const UNIT_NGINX           = 'nginx';
    public const UNIT_POSTFIX         = 'postfix';
    public const UNIT_SSHD            = 'sshd';
    public const UNIT_BACKEND_WORKERS = 'backend_workers';
    public const UNIT_MEDIAWIKI_FPM   = 'mediawiki_fpm';
    private const UNIT_REALTIME       = 'realtime';
    private const UNIT_SMOKESCREEN    = 'smokescreen';

    private const BASE_DIR = __DIR__ . '/../../../../tools/docker/tuleap-aio-c7/supervisor.d';

    private const UNITS = [
        self::UNIT_CROND,
        self::UNIT_FPM,
        self::UNIT_HTTPD,
        self::UNIT_NGINX,
        self::UNIT_POSTFIX,
        self::UNIT_SSHD,
        self::UNIT_BACKEND_WORKERS,
        self::UNIT_MEDIAWIKI_FPM,
        self::UNIT_REALTIME,
        self::UNIT_SMOKESCREEN,
    ];

    /**
     * @var string[]
     */
    private array $units = [];

    public function __construct()
    {
        foreach (self::UNITS as $unit) {
            if ($unit === self::UNIT_MEDIAWIKI_FPM && ! is_dir(__DIR__ . '/../../../../plugins/mediawiki_standalone')) {
                break;
            }
            $this->units[] = $unit;
        }
    }

    public function configure(OutputInterface $output): void
    {
        $this->setupSupervisord($output);
        $this->deployCrondConfig();
    }

    public function exec(OutputInterface $output): void
    {
        $output->writeln('Let the place for Supervisord');
        pcntl_exec('/usr/bin/supervisord', ['--nodaemon', '--configuration', '/etc/supervisord.conf']);
        throw new \RuntimeException('Exec of /usr/bin/supervisord failed');
    }

    public function run(OutputInterface $output): void
    {
        $this->configure($output);
        $this->exec($output);
    }

    private function setupSupervisord(OutputInterface $output): void
    {
        $output->writeln('Setup Supervisord');
        foreach ($this->units as $unit) {
            $ini = $unit . '.ini';
            copy(self::BASE_DIR . '/' . $ini, '/etc/supervisord.d/' . $ini);
        }
        file_put_contents(
            '/etc/supervisord.d/supervisord-server-credentials.ini',
            $this->generateCredentialsConfigurationForSupervisordSocket()
        );
    }

    private function generateCredentialsConfigurationForSupervisordSocket(): string
    {
        $password = \sodium_bin2hex(\random_bytes(32));
        return <<<EOT
                [unix_http_server]
                username = tuleap
                password = $password

                # Override supervisord defaults. Avoid supervisord to scream because it runs as root
                [supervisord]
                user=root
                EOT;
    }

    private function deployCrondConfig(): void
    {
        copy(__DIR__ . '/../../../utils/cron.d/codendi', '/etc/cron.d/tuleap');
    }
}
