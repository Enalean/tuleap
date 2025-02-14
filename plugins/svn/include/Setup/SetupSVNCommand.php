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

namespace Tuleap\SVN\Setup;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Tuleap\ServerHostname;

final class SetupSVNCommand extends Command
{
    public const NAME = 'setup:svn';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $httpd_vhost = '/etc/httpd/conf.d/tuleap-vhost.conf';
        if (! file_exists($httpd_vhost)) {
            $content = file_get_contents(__DIR__ . '/../../../../src/etc/tuleap-vhost.conf.dist');
            $content = str_replace(
                [
                    '%sys_default_domain%',
                ],
                [
                    ServerHostname::rawHostname(),
                ],
                $content
            );
            file_put_contents($httpd_vhost, $content);
            (new Process(['/usr/bin/tuleap-cfg', 'systemctl', 'restart', 'httpd.service']))->mustRun();
            (new Process(['/usr/bin/tuleap-cfg', 'systemctl', 'enable', 'httpd.service']))->mustRun();

            $output->writeln('Plugin SVN is configured');
        } else {
            $output->writeln('Plugin SVN is already configured');
        }

        return self::SUCCESS;
    }
}
