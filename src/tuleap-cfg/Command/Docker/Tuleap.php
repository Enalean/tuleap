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
use Tuleap\System\ServiceControl;
use TuleapCfg\Command\Configure\ConfigureApache;
use TuleapCfg\Command\ProcessFactory;

final class Tuleap
{
    /**
     * @var ProcessFactory
     */
    private $process_factory;

    public function __construct(ProcessFactory $process_factory)
    {
        $this->process_factory = $process_factory;
    }

    public function setup(OutputInterface $output, string $tuleap_fqdn, string $db_host, string $db_admin_user, string $db_admin_password, string $web_server_ip): void
    {
        $output->writeln('Install Tuleap');
        $this->process_factory->getProcessWithoutTimeout(
            [
                '/bin/bash',
                '/usr/share/tuleap/tools/setup.el7.sh',
                '--debug',
                '--assumeyes',
                '--configure',
                '--server-name=' . $tuleap_fqdn,
                '--mysql-server=' . $db_host,
                '--mysql-user=' . $db_admin_user,
                '--mysql-password=' . $db_admin_password,
                '--web-server-ip=' . $web_server_ip,
            ]
        )->mustRun();
        $this->process_factory->getProcess(['/usr/bin/tuleap', 'config-set', ServiceControl::FORGECONFIG_INIT_MODE, ServiceControl::SUPERVISORD])->mustRun();
    }

    public function update(OutputInterface $output): void
    {
        $this->regenerateConfigurations($output);
        $this->runForgeUpgrade($output);
        $this->queueSystemCheck($output);
    }

    private function regenerateConfigurations(OutputInterface $output): void
    {
        $output->writeln('<info>Regenerate configurations for nginx, fpm</info>');
        $this->process_factory->getProcess([__DIR__ . '/../../../../tools/utils/php73/run.php', '--module=nginx,fpm'])->mustRun();

        $output->writeln('<info>Regenerate configuration for apache</info>');
        $configure_apache = new ConfigureApache('/');
        $configure_apache->configure();
    }

    private function runForgeUpgrade(OutputInterface $output): void
    {
        $output->writeln('<info>Run forgeupgrade</info>');
        $this->process_factory->getProcessWithoutTimeout(['/usr/lib/forgeupgrade/bin/forgeupgrade', '--config=/etc/tuleap/forgeupgrade/config.ini', 'update'])->mustRun();
    }

    private function queueSystemCheck(OutputInterface $output): void
    {
        $output->writeln('<info>Queue a system check</info>');
        $this->process_factory->getProcess(['/usr/bin/tuleap', 'queue-system-check'])->mustRun();
    }
}
