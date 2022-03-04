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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\Config\ConfigKeyLegacyBool;
use Tuleap\Config\ConfigurationVariables;
use Tuleap\Config\ConfigSerializer;
use Tuleap\ServerHostname;

final class SetupTuleapCommand extends Command
{
    private const OPT_TULEAP_FQDN = 'tuleap-fqdn';
    private const OPT_FORCE       = 'force';

    private string $base_directory;

    public function __construct(?string $base_directory = null)
    {
        $this->base_directory = $base_directory ?: '/';
        parent::__construct('setup:tuleap');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Initial configuration of Tuleap')
            ->addOption(self::OPT_TULEAP_FQDN, '', InputOption::VALUE_REQUIRED, 'Fully qualified domain name of the tuleap server (eg. tuleap.example.com)')
            ->addOption(self::OPT_FORCE, '', InputOption::VALUE_NONE, 'Force redeploy');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fqdn  = $input->getOption(self::OPT_TULEAP_FQDN);
        $force = (bool) $input->getOption(self::OPT_FORCE);

        $local_inc = $this->base_directory . '/etc/tuleap/conf/local.inc';
        $output->writeln(sprintf("Write initial configuration in %s", $local_inc));
        if (file_exists($local_inc)) {
            if (! $force) {
                $output->writeln(sprintf("%s already exists, skip configuration", $local_inc));
                return 0;
            }
            $backup = $this->base_directory . '/etc/tuleap/conf/local.inc.' . date('Y-m-d_H-i-s');
            $output->writeln(sprintf("%s already exists, backup as %s before overwrite", $local_inc, $backup));
            rename($local_inc, $backup);
        }

        return $this->configureAndSave($fqdn) ? Command::SUCCESS : Command::FAILURE;
    }

    private function configureAndSave(string $fqdn): bool
    {
        return \ForgeConfig::wrapWithCleanConfig(function () use ($fqdn) {
            \ForgeConfig::set(ServerHostname::DEFAULT_DOMAIN, $fqdn);
            \ForgeConfig::set(ServerHostname::LIST_HOST, 'lists.' . $fqdn);
            \ForgeConfig::set(ServerHostname::FULL_NAME, $fqdn);
            \ForgeConfig::set(ConfigurationVariables::EMAIL_ADMIN, 'codendi-admin@' . $fqdn);
            \ForgeConfig::set(ConfigurationVariables::EMAIL_CONTACT, 'codendi-contact@' . $fqdn);
            \ForgeConfig::set(ConfigurationVariables::NOREPLY, sprintf('"Tuleap" <noreply@%s>', $fqdn));
            \ForgeConfig::set(ConfigurationVariables::ORG_NAME, 'Tuleap');
            \ForgeConfig::set(ConfigurationVariables::LONG_ORG_NAME, 'Tuleap');
            \ForgeConfig::set(ConfigurationVariables::HOMEDIR_PREFIX, '');
            \ForgeConfig::set(ConfigurationVariables::GRPDIR_PREFIX, '');
            \ForgeConfig::set(ConfigurationVariables::MAIL_SECURE_MODE, ConfigKeyLegacyBool::FALSE);
            \ForgeConfig::set(ConfigurationVariables::DISABLE_SUBDOMAINS, ConfigKeyLegacyBool::TRUE);

            return (new ConfigSerializer())->save(
                $this->base_directory . '/etc/tuleap/conf/local.inc',
                0640,
                'root',
                'codendiadm',
                ServerHostname::class,
                ConfigurationVariables::class
            );
        });
    }
}
