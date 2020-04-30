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
use Webimpress\SafeWriter\FileWriter;

final class Rsyslog
{
    private const ENV_LOG_SERVER = 'TULEAP_LOG_SERVER';

    /**
     * @see https://www.projectatomic.io/blog/2014/09/running-syslog-within-a-docker-container/
     *      https://github.com/rsyslog/rsyslog-docker/blob/master/base/centos7/Dockerfile
     */
    public function setup(OutputInterface $output, string $tuleap_fqdn): void
    {
        $output->writeln('Setup Rsyslog');
        if (file_exists('/etc/rsyslog.d/listen.conf')) {
            unlink('/etc/rsyslog.d/listen.conf');
        }
        if (file_exists('/etc/rsyslog.conf')) {
            unlink('/etc/rsyslog.conf');
        }

        $log_server = getenv(self::ENV_LOG_SERVER);
        if ($log_server !== false) {
            copy(__DIR__ . '/../../resources/rsyslog/rsyslog-logforward.conf', '/etc/rsyslog.conf');
            $logforward = str_replace(
                [
                    '%tuleap_fqdn%',
                    '%log-server%',
                ],
                [
                    $tuleap_fqdn,
                    $log_server
                ],
                file_get_contents(__DIR__ . '/../../resources/rsyslog/logforward.conf')
            );
            FileWriter::writeFile('/etc/rsyslog.d/logforward.conf', $logforward, 0644);
        } else {
            copy(__DIR__ . '/../../resources/rsyslog/rsyslog.conf', '/etc/rsyslog.conf');
        }
    }
}
