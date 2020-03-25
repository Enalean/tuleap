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

final class Rsyslog
{
    /**
     * @see https://www.projectatomic.io/blog/2014/09/running-syslog-within-a-docker-container/
     *      https://github.com/rsyslog/rsyslog-docker/blob/master/base/centos7/Dockerfile
     */
    public function setup(OutputInterface $output): void
    {
        $output->writeln('Setup Rsyslog');
        unlink('/etc/rsyslog.d/listen.conf');
        unlink('/etc/rsyslog.conf');
        copy(__DIR__ . '/../../../../tools/docker/tuleap-aio-c7/rsyslog.conf', '/etc/rsyslog.conf');
    }
}
