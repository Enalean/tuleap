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

final class Postfix
{
    private const ENV_RELAYHOST = 'TULEAP_EMAIL_RELAYHOST';
    private const ENV_ADMIN_EMAIL = 'TULEAP_EMAIL_ADMIN';

    /**
     * @var ProcessFactory
     */
    private $process_factory;

    public function __construct(ProcessFactory $process_factory)
    {
        $this->process_factory = $process_factory;
    }

    public function setup(OutputInterface $output, string $tuleap_fqdn): void
    {
        $output->writeln('Setup Postfix');

        touch('/etc/aliases.codendi');
        $this->process_factory->getProcessWithoutTimeout(['/usr/sbin/postconf', '-e', sprintf('myhostname = %s', $tuleap_fqdn)])->mustRun();
        $this->process_factory->getProcessWithoutTimeout(['/usr/sbin/postconf', '-e', 'inet_interfaces = all'])->mustRun();
        $this->process_factory->getProcessWithoutTimeout(['/usr/sbin/postconf', '-e', 'recipient_delimiter = +'])->mustRun();
        $this->process_factory->getProcessWithoutTimeout(['/usr/sbin/postconf', '-e', 'alias_maps = hash:/etc/aliases,hash:/etc/aliases.codendi'])->mustRun();
        $this->process_factory->getProcessWithoutTimeout(['/usr/sbin/postconf', '-e', 'alias_database = hash:/etc/aliases,hash:/etc/aliases.codendi'])->mustRun();

        $relayhost = getenv(self::ENV_RELAYHOST);
        if ($relayhost !== false) {
            $this->process_factory->getProcessWithoutTimeout(['/usr/sbin/postconf', '-e', sprintf('relayhost = %s', $relayhost)])->mustRun();
        }

        $admin_email = getenv(self::ENV_ADMIN_EMAIL);
        if ($admin_email !== false) {
            $fd = fopen('/etc/aliases', 'ab+');
            fwrite($fd, sprintf("\n%s: %s\n", \BackendAliases::ADMIN_ALIAS, $admin_email));
            fclose($fd);
        }


        $this->process_factory->getProcessWithoutTimeout(['/usr/bin/newaliases'])->mustRun();
    }
}
