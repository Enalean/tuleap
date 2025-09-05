<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace TuleapCfg\Command;

use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\Cryptography\SecretKeyFile;
use TuleapCfg\Command\SetupTuleap\SetupTuleap;

final class SetupTuleapCommand extends Command
{
    private const OPT_TULEAP_FQDN = 'tuleap-fqdn';
    private const OPT_FORCE       = 'force';
    private const OPT_PHP_VERSION = 'php-version';

    private const SYSTEMD_UNITS = [
        'tuleap-process-system-events-default.timer',
        'tuleap-process-system-events-statistics.timer',
        'tuleap-process-system-events-tv3-tv5-migration.timer',
        'tuleap-launch-system-check.timer',
        'tuleap-launch-daily-event.timer',
        'tuleap-launch-plugin-job.timer',
        'nginx.service',
        'tuleap.service',
    ];

    private string $base_directory;

    /**
     * @param \Closure(\Psr\Log\LoggerInterface): \Tuleap\ForgeUpgrade\ForgeUpgradeRecordOnly $forge_upgrade_provider
     */
    public function __construct(private ProcessFactory $process_factory, private SecretKeyFile $secret_key_file, private \Closure $forge_upgrade_provider, ?string $base_directory = null)
    {
        $this->base_directory = $base_directory ?: '/';
        parent::__construct('setup:tuleap');
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setDescription('Initial configuration of Tuleap')
            ->addOption(self::OPT_TULEAP_FQDN, '', InputOption::VALUE_REQUIRED, 'Fully qualified domain name of the tuleap server (eg. tuleap.example.com)')
            ->addOption(self::OPT_FORCE, '', InputOption::VALUE_NONE, 'Force redeploy')
            ->addOption(self::OPT_PHP_VERSION, '', InputOption::VALUE_REQUIRED, 'Set required php version');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fqdn        = $input->getOption(self::OPT_TULEAP_FQDN);
        $force       = (bool) $input->getOption(self::OPT_FORCE);
        $php_version = $input->getOption(self::OPT_PHP_VERSION);

        $logger = new ConsoleLogger($output, [LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL]);

        $local_inc = $this->base_directory . '/etc/tuleap/conf/local.inc';
        $logger->info(sprintf('Write initial configuration in %s', $local_inc));
        if (file_exists($local_inc)) {
            if (! $force) {
                $logger->info(sprintf('%s already exists, skip configuration', $local_inc));
                return 0;
            }
            $backup = $this->base_directory . '/etc/tuleap/conf/local.inc.' . date('Y-m-d_H-i-s');
            $logger->info(sprintf('%s already exists, backup as %s before overwrite', $local_inc, $backup));
            rename($local_inc, $backup);
        }

        $forge_upgrade_callback = $this->forge_upgrade_provider;
        \ForgeConfig::wrapWithCleanConfig(function () use ($forge_upgrade_callback, $logger, $fqdn) {
            $logger->info('Configure local.inc');
            \ForgeConfig::loadForInitialSetup($fqdn);
            (new SetupTuleap($this->base_directory))->setup();

            $logger->info('Register buckets in forgeupgrade');
            $forge_upgrade_callback($logger)->recordOnlyCore();
        });

        \ForgeConfig::loadInSequence();

        $logger->info('Generate Secret');
        $this->secret_key_file->initAndGetEncryptionKeyPath();
        $this->secret_key_file->restoreOwnership($logger);

        $logger->info('Install and activate tracker plugin');
        $this->process_factory->getProcessWithoutTimeout([
            '/usr/bin/sudo',
            '-u',
            'codendiadm',
            '/usr/bin/tuleap',
            'plugin:install',
            'tracker',
        ])->mustRun();

        $logger->info('Redeploy configuration');
        $site_deploy = [
            __DIR__ . '/../tuleap-cfg.php',
            'site-deploy',
            '--force',
        ];
        if (is_string($php_version)) {
            $site_deploy[] = '--php-version';
            $site_deploy[] = $php_version;
        }
        $this->process_factory->getProcessWithoutTimeout($site_deploy)->mustRun();

        $logger->info('Enable and start systemd timers and services');
        $this->process_factory->getProcessWithoutTimeout(
            array_merge([__DIR__ . '/../tuleap-cfg.php', 'systemctl', 'enable'], self::SYSTEMD_UNITS)
        )->mustRun();
        $this->process_factory->getProcessWithoutTimeout(
            array_merge([__DIR__ . '/../tuleap-cfg.php', 'systemctl', 'start'], self::SYSTEMD_UNITS)
        )->mustRun();

        return Command::SUCCESS;
    }
}
