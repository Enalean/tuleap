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

use ForgeConfig;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\System\ServiceControl;
use TuleapCfg\Command\Configure\ConfigureApache;
use TuleapCfg\Command\ProcessFactory;
use TuleapCfg\Command\SiteDeploy\FPM\SiteDeployFPM;
use TuleapCfg\Command\SiteDeploy\Gitolite3\SiteDeployGitolite3;
use TuleapCfg\Command\SiteDeploy\Nginx\SiteDeployNginx;

final class Tuleap
{
    private const TULEAP_FQDN       = 'TULEAP_FQDN';
    private const DB_HOST           = 'DB_HOST';
    private const DB_ADMIN_USER     = 'DB_ADMIN_USER';
    private const DB_ADMIN_PASSWORD = 'DB_ADMIN_PASSWORD';

    private ProcessFactory $process_factory;

    public function __construct(ProcessFactory $process_factory)
    {
        $this->process_factory = $process_factory;
    }

    public function setupOrUpdate(OutputInterface $output, DataPersistence $data_persistence, VariableProviderInterface $variable_provider, ?\Closure $post_install = null): string
    {
        if (! $data_persistence->isThereAnyData()) {
            $tuleap_fqdn = $this->installTuleap($output, $variable_provider, $post_install);
            $data_persistence->store($output);
            $data_persistence->restore($output);
            return $tuleap_fqdn;
        } else {
            $data_persistence->restore($output);
            return $this->update($output);
        }
    }

    private function installTuleap(OutputInterface $output, VariableProviderInterface $variable_provider, ?\Closure $post_install = null): string
    {
        $ssh_daemon = new SSHDaemon($this->process_factory);

        $fqdn = $variable_provider->get(self::TULEAP_FQDN);

        $ssh_daemon->startDaemon($output);
        $this->setup(
            $output,
            $fqdn,
            $variable_provider->get(self::DB_HOST),
            $variable_provider->get(self::DB_ADMIN_USER),
            $variable_provider->get(self::DB_ADMIN_PASSWORD),
        );

        if ($post_install !== null) {
            $post_install();
        }

        $ssh_daemon->shutdownDaemon($output);

        return $fqdn;
    }

    public function setup(OutputInterface $output, string $tuleap_fqdn, string $db_host, string $db_admin_user, string $db_admin_password): void
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
            ]
        )->mustRun();
        $this->regenerateConfigurations($output);
    }

    public function update(OutputInterface $output): string
    {
        $tuleap_fqdn = $this->regenerateConfigurations($output);
        $this->runForgeUpgrade($output);
        $this->queueSystemCheck($output);
        return $tuleap_fqdn;
    }

    private function regenerateConfigurations(OutputInterface $output): string
    {
        $logger      = new ConsoleLogger($output, [LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL]);
        $server_name = $this->getServerFQDNFromConfiguration();
        if (! $server_name) {
            throw new \RuntimeException('No `sys_default_domain` defined, abort');
        }

        $output->writeln('<info>Ensure Tuleap knows it\'s under supervisord control</info>');
        $this->process_factory->getProcess(['/usr/bin/tuleap', 'config-set', ServiceControl::FORGECONFIG_INIT_MODE, ServiceControl::SUPERVISORD])->mustRun();


        $output->writeln('<info>Regenerate configurations for nginx</info>');
        $site_deploy_nginx = new SiteDeployNginx(
            $logger,
            __DIR__ . '/../../../../',
            '/etc/nginx',
            $server_name,
            false,
        );
        $site_deploy_nginx->configure();

        ForgeConfig::loadLocalInc();
        $output->writeln('<info>Regenerate configuration for fpm</info>');
        $site_deploy_fpm = SiteDeployFPM::buildForPHP74(
            new ConsoleLogger($output, [LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL]),
            'codendiadm',
            false
        );
        $site_deploy_fpm->forceDeploy();

        $output->writeln('<info>Regenerate configuration for gitolite3</info>');
        $site_deploy_gitolite3 = new SiteDeployGitolite3();
        $site_deploy_gitolite3->deploy($logger);

        $output->writeln('<info>Regenerate configuration for apache</info>');
        $configure_apache = new ConfigureApache('/');
        $configure_apache->configure();

        return $server_name;
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

    private function getServerFQDNFromConfiguration(): ?string
    {
        ForgeConfig::store();
        ForgeConfig::loadLocalInc();
        $fqdn = ForgeConfig::get('sys_default_domain', null);
        ForgeConfig::restore();
        return $fqdn;
    }
}
