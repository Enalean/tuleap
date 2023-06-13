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
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\DB\DBConfig;
use Tuleap\DB\DBFactory;
use Tuleap\ForgeUpgrade\ForgeUpgrade;
use Tuleap\Option\Option;
use Tuleap\System\ServiceControl;
use TuleapCfg\Command\Configure\ConfigureApache;
use TuleapCfg\Command\ProcessFactory;
use TuleapCfg\Command\SetupMysql\DatabaseConfigurator;
use TuleapCfg\Command\SetupMysql\DBSetupParameters;
use TuleapCfg\Command\SetupTuleap\SetupTuleap;

final class Tuleap
{
    private const TULEAP_FQDN         = 'TULEAP_FQDN';
    private const DB_ADMIN_USER       = 'DB_ADMIN_USER';
    private const DB_ADMIN_PASSWORD   = 'DB_ADMIN_PASSWORD';
    private const SITE_ADMIN_PASSWORD = 'SITE_ADMINISTRATOR_PASSWORD';

    public function __construct(private ProcessFactory $process_factory, private DatabaseConfigurator $database_configurator)
    {
    }

    /**
     * @psalm-param Option<Closure():void> $post_install
     */
    public function setupOrUpdate(SymfonyStyle $output, DataPersistence $data_persistence, VariableProviderInterface $variable_provider, Option $post_install): string
    {
        if (! $data_persistence->isThereAnyData()) {
            $tuleap_fqdn = $this->installTuleap($output, $variable_provider, $post_install);
            $data_persistence->store($output);
            $data_persistence->restore($output);
            return $tuleap_fqdn;
        } else {
            $data_persistence->restore($output);
            \ForgeConfig::loadInSequence();
            return $this->update($output);
        }
    }

    /**
     * @psalm-param Option<Closure():void> $post_install
     */
    private function installTuleap(SymfonyStyle $output, VariableProviderInterface $variable_provider, Option $post_install): string
    {
        $fqdn = $variable_provider->get(self::TULEAP_FQDN);

        ForgeConfig::wrapWithCleanConfig(function () use ($output, $fqdn, $variable_provider) {
            ForgeConfig::loadForInitialSetup($fqdn);
            if (ForgeConfig::get(DBConfig::CONF_DBPASSWORD) === false) {
                throw new \RuntimeException(sprintf('No variable named `%s` found in environment', DBConfig::CONF_DBPASSWORD));
            }

            $output->writeln("Setup database");
            $this->database_configurator
                ->setupDatabase(
                    $output,
                    DBSetupParameters::fromAdminCredentials(
                        $variable_provider->get(self::DB_ADMIN_USER),
                        $variable_provider->get(self::DB_ADMIN_PASSWORD)
                    )
                    ->withSiteAdminPassword(new ConcealedString($variable_provider->get(self::SITE_ADMIN_PASSWORD)))
                    ->withTuleapFQDN($fqdn)
                );

            $output->writeln("Configure local.inc");
            (new SetupTuleap())->setup();
        });

        \ForgeConfig::loadInSequence();
        $output->writeln("Register buckets in forgeupgrade");
        $forge_upgrade = new ForgeUpgrade(
            DBFactory::getMainTuleapDBConnection()->getDB()->getPdo(),
            new ConsoleLogger($output, [LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL]),
        );
        $forge_upgrade->recordOnlyCore();

        $output->writeln("Generate platform secret");
        $secret_key = new \Tuleap\Cryptography\SecretKeyFileOnFileSystem();
        $secret_key->initAndGetEncryptionKeyPath();
        $secret_key->restoreOwnership(new ConsoleLogger($output, [LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL]));

        $output->writeln("Start SSH daemon for initial setup (git)");
        $ssh_daemon = new SSHDaemon($this->process_factory);
        $ssh_daemon->startDaemon($output);

        $this->setup(
            $output,
            $fqdn,
            ForgeConfig::get(DBConfig::CONF_HOST),
            $variable_provider->get(self::DB_ADMIN_USER),
            $variable_provider->get(self::DB_ADMIN_PASSWORD),
        );

        $post_install->apply(fn (callable $post_install_call) => $post_install_call());

        $ssh_daemon->shutdownDaemon($output);

        return $fqdn;
    }

    private function setup(OutputInterface $output, string $tuleap_fqdn, string $db_host, string $db_admin_user, string $db_admin_password): void
    {
        $output->writeln('Configure Tuleap');
        $process = $this->process_factory->getProcessWithoutTimeout(
            [
                '/bin/bash',
                '/usr/share/tuleap/tools/setup.el7.sh',
                '--assumeyes',
                '--configure',
                '--server-name=' . $tuleap_fqdn,
                '--mysql-server=' . $db_host,
                '--mysql-user=' . $db_admin_user,
                '--mysql-password=' . $db_admin_password,
            ]
        );
        $process->mustRun(function (string $type, string $buffer) use ($output) {
            $output->writeln($buffer);
        });
        $this->regenerateConfigurations($output);
    }

    private function update(OutputInterface $output): string
    {
        $tuleap_fqdn = $this->regenerateConfigurations($output);
        $this->queueSystemCheck($output);
        return $tuleap_fqdn;
    }

    private function regenerateConfigurations(OutputInterface $output): string
    {
        $logger = new ConsoleLogger($output, [LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL]);

        $server_name = ForgeConfig::get('sys_default_domain', null);
        if (! $server_name) {
            throw new \RuntimeException('No `sys_default_domain` defined, abort');
        }

        $output->writeln('<info>Ensure Tuleap knows it\'s under supervisord control</info>');
        $this->process_factory->getProcess(['/usr/bin/tuleap', 'config-set', ServiceControl::FORGECONFIG_INIT_MODE, ServiceControl::SUPERVISORD])->mustRun();

        $output->writeln('<info>Run Site Deploy</info>');
        $this->process_factory->getProcessWithoutTimeout(['/usr/bin/tuleap-cfg', 'site-deploy'])->mustRun(
            /** @param Process::ERR|Process::OUT $type */
            fn (string $type, string $data): mixed => match ($type) {
                Process::ERR => $output->writeln('<error>' . trim($data) . '</error>'),
                Process::OUT => $output->writeln('<info>' . trim($data) . '</info>'),
            }
        );

        $output->writeln('<info>Regenerate configuration for apache</info>');
        $configure_apache = new ConfigureApache('/');
        $configure_apache->configure();

        $ca_trust = new CATrust($this->process_factory, $logger);
        $ca_trust->update();

        return $server_name;
    }

    private function queueSystemCheck(OutputInterface $output): void
    {
        $output->writeln('<info>Queue a system check</info>');
        $this->process_factory->getProcess(['/usr/bin/tuleap', 'queue-system-check'])->mustRun();
    }
}
