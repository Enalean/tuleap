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

namespace TuleapCfg\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\DB\DBConfig;
use TuleapCfg\Command\SetupMysql\ConnectionManagerInterface;
use TuleapCfg\Command\SetupMysql\DatabaseConfigurator;
use TuleapCfg\Command\SetupMysql\DBSetupParameters;
use TuleapCfg\Command\SetupMysql\DBWrapperInterface;
use TuleapCfg\Command\SetupMysql\MysqlCommandHelper;

final class SetupMysqlInitCommand extends Command
{
    private const OPT_ADMIN_USER          = 'admin-user';
    private const OPT_ADMIN_PASSWORD      = 'admin-password';
    private const OPT_APP_DBNAME          = 'db-name';
    private const OPT_APP_USER            = 'app-user';
    private const OPT_APP_PASSWORD        = 'app-password';
    private const OPT_MEDIAWIKI           = 'mediawiki';
    private const OPT_SKIP_DATABASE       = 'skip-database';
    private const OPT_GRANT_HOSTNAME      = 'grant-hostname';
    private const OPT_LOG_PASSWORD        = 'log-password';
    private const OPT_AZURE_SUFFIX        = 'azure-suffix';
    private const ENV_AZURE_SUFFIX        = 'TULEAP_DB_AZURE_SUFFIX';
    private const OPT_TULEAP_FQDN         = 'tuleap-fqdn';
    private const OPT_SITE_ADMIN_PASSWORD = 'site-admin-password';

    private MysqlCommandHelper $command_helper;
    private string $base_directory;

    public function __construct(
        private ConnectionManagerInterface $connection_manager,
        private DatabaseConfigurator $database_configurator,
        ?string $base_directory = null,
    ) {
        $this->base_directory = $base_directory ?: '/';
        $this->command_helper = new MysqlCommandHelper($this->base_directory);

        parent::__construct('setup:mysql-init');
    }

    public function getHelp(): string
    {
        $ssl_opt       = MysqlCommandHelper::OPT_SSL;
        $ssl_disabled  = MysqlCommandHelper::SSL_NO_SSL;
        $ssl_no_verify = MysqlCommandHelper::SSL_NO_VERIFY;
        $ssl_verify_ca = MysqlCommandHelper::SSL_VERIFY_CA;
        $ssl_ca_file   = MysqlCommandHelper::OPT_SSL_CA;
        return <<<EOT
        Initialize the database (MySQL 8.0) for use with Tuleap

        This command is idempotent so it's safe to be used several times (with same parameters...).

        By using --app-password option, it will create the tuleap DB (`tuleap` by default or --db-name),
        the database admin user (`tuleapadm` or --admin-user) with the required GRANTS.

        The connection to the database can be encrypted and you can control the way it's done with {$ssl_opt} with:
        - {$ssl_disabled}: no usage of encryption (default)
        - {$ssl_no_verify}: connection will be encrypted by host won't be verified
        - {$ssl_verify_ca}: connection is encrypted and host is verified

        And encrypted connection requires a Certificate Authority (CA) file that must be provide with {$ssl_ca_file}.

        EOT;
    }

    protected function configure(): void
    {
        $this->command_helper->addOptions($this);

        $this
            ->setDescription('Initialize database (users, database, permissions)')
            ->addOption(self::OPT_ADMIN_USER, '', InputOption::VALUE_REQUIRED, 'MySQL admin user', 'root')
            ->addOption(self::OPT_ADMIN_PASSWORD, '', InputOption::VALUE_REQUIRED, 'MySQL admin password')
            ->addOption(self::OPT_APP_DBNAME, '', InputOption::VALUE_REQUIRED, 'Name of the DB name to host Tuleap tables (`tuleap` by default)', DBConfig::DEFAULT_MYSQL_TULEAP_DB_NAME)
            ->addOption(self::OPT_APP_USER, '', InputOption::VALUE_REQUIRED, 'Name of the DB user to be used for Tuleap (`tuleapadm`) by default', DBConfig::DEFAULT_MYSQL_TULEAP_USER_NAME)
            ->addOption(self::OPT_GRANT_HOSTNAME, '', InputOption::VALUE_REQUIRED, 'Hostname value for mysql grant. This is the right hand side of `user`@`hostname`. Default is `%`', '%')
            ->addOption(self::OPT_APP_PASSWORD, '', InputOption::VALUE_REQUIRED, 'Password for the application dbuser (typically tuleapadm)')
            ->addOption(self::OPT_MEDIAWIKI, '', InputOption::VALUE_REQUIRED, 'Grant permissions for mediawiki. Possible values: `per-project` or `central`')
            ->addOption(self::OPT_LOG_PASSWORD, '', InputOption::VALUE_REQUIRED, 'Write user & password into given file')
            ->addOption(self::OPT_AZURE_SUFFIX, '', InputOption::VALUE_REQUIRED, 'Value to add to user\'s name to comply with Microsoft Azure rules')
            ->addOption(self::OPT_TULEAP_FQDN, '', InputOption::VALUE_REQUIRED, 'Fully qualified domain name of the tuleap server (eg. tuleap.example.com)')
            ->addOption(self::OPT_SITE_ADMIN_PASSWORD, '', InputOption::VALUE_REQUIRED, 'Password for site administrator (`admin`) user ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $admin_user = $input->getOption(self::OPT_ADMIN_USER);
        assert(is_string($admin_user));

        $admin_password = $input->getOption(self::OPT_ADMIN_PASSWORD);
        if (! $admin_password) {
            $io->getErrorStyle()->writeln(sprintf('<error>Missing mysql password for admin user `%s`</error>', $admin_user));
            return 1;
        }
        assert(is_string($admin_password));

        $fqdn = $input->getOption(self::OPT_TULEAP_FQDN);
        if (! $fqdn) {
            $io->getErrorStyle()->writeln('<error>FQDN is mandatory</error>');
        }
        assert(is_string($fqdn));

        \ForgeConfig::loadForInitialSetup($fqdn);

        $this->command_helper->setHost($input);
        $this->command_helper->setPort($input);
        $this->command_helper->setSSLMode($input);

        $app_dbname = $input->getOption(self::OPT_APP_DBNAME);
        assert(is_string($app_dbname));
        \ForgeConfig::set(DBConfig::CONF_DBNAME, $app_dbname);

        $app_password = $input->getOption(self::OPT_APP_PASSWORD);
        if ($app_password && ! is_string($app_password)) {
            $io->getErrorStyle()->writeln(sprintf('<error>%s must be a string</error>', self::OPT_APP_PASSWORD));
            return 1;
        }
        assert($app_password === null || is_string($app_password));
        \ForgeConfig::set(DBConfig::CONF_DBPASSWORD, $app_password);

        $tuleap_user = $input->getOption(self::OPT_APP_USER);
        if (! is_string($tuleap_user)) {
            $io->getErrorStyle()->writeln(sprintf('<error>%s must be a string</error>', self::OPT_APP_USER));
        }
        assert(is_string($tuleap_user));
        \ForgeConfig::set(DBConfig::CONF_DBUSER, $tuleap_user);

        $db_params = DBSetupParameters::fromAdminCredentials($admin_user, $admin_password);

        if (getenv(self::ENV_AZURE_SUFFIX) !== false) {
            $azure_suffix = getenv(self::ENV_AZURE_SUFFIX);
        } else {
            $azure_suffix = $input->getOption(self::OPT_AZURE_SUFFIX);
            if (! $azure_suffix) {
                $azure_suffix = '';
            }
        }
        assert(is_string($azure_suffix));
        $db_params = $db_params->withAzurePrefix($azure_suffix);

        $grant_hostname = $input->getOption(self::OPT_GRANT_HOSTNAME);
        assert(is_string($grant_hostname));
        $db_params = $db_params->withGrantHostname($grant_hostname);

        $db = $this->connection_manager->getDBWithoutDBName(
            $io,
            \ForgeConfig::get(DBConfig::CONF_HOST),
            \ForgeConfig::getInt(DBConfig::CONF_PORT),
            \ForgeConfig::getStringAsBool(DBConfig::CONF_ENABLE_SSL),
            \ForgeConfig::getStringAsBool(DBConfig::CONF_SSL_VERIFY_CERT),
            \ForgeConfig::get(DBConfig::CONF_SSL_CA),
            $db_params->admin_user,
            $db_params->admin_password
        );
        $output->writeln('<info>Successfully connected to the server !</info>');

        $this->connection_manager->checkSQLModes($db);

        $this->initializeDatabase($input, $io, $db, $db_params);
        $this->initializeMediawiki($input, $io, $db, $tuleap_user, $db_params->grant_hostname);

        $db->run('FLUSH PRIVILEGES');

        $return_value = $this->database_configurator->writeDatabaseIncFile($db_params->azure_prefix, $this->base_directory);
        if ($return_value !== true) {
            return Command::FAILURE;
        }

        return self::SUCCESS;
    }

    private function getSiteAdminPassword(InputInterface $input): ?ConcealedString
    {
        $clear_site_admin_password = $input->getOption(self::OPT_SITE_ADMIN_PASSWORD);
        if (! is_string($clear_site_admin_password)) {
            return null;
        }

        $password = new ConcealedString($clear_site_admin_password);
        sodium_memzero($clear_site_admin_password);
        return $password;
    }

    private function initializeDatabase(
        InputInterface $input,
        SymfonyStyle $output,
        DBWrapperInterface $db,
        DBSetupParameters $db_params,
    ): void {
        $dbname = \ForgeConfig::get(DBConfig::CONF_DBNAME);
        if (! $dbname || ! \ForgeConfig::get(DBConfig::CONF_DBPASSWORD)) {
            return;
        }

        $db_params = $db_params->withTuleapFQDN($input->getOption(self::OPT_TULEAP_FQDN));
        $db_params = $db_params->withSiteAdminPassword($this->getSiteAdminPassword($input));

        if ($db_params->canSetup()) {
            $this->database_configurator->setupDatabase($output, $db_params, $this->base_directory);
        } else {
            $this->database_configurator->initializeDatabase($output, $db, $dbname, $db_params->grant_hostname);
        }

        $log_password = $input->getOption(self::OPT_LOG_PASSWORD);
        if (is_string($log_password)) {
            file_put_contents($log_password, sprintf("MySQL application user (%s): %s\n", \ForgeConfig::get(DBConfig::CONF_DBUSER), \ForgeConfig::get(DBConfig::CONF_DBPASSWORD)), FILE_APPEND);
        }
    }

    private function initializeMediawiki(
        InputInterface $input,
        SymfonyStyle $output,
        DBWrapperInterface $db,
        string $app_user,
        string $grant_hostname,
    ): void {
        $mediawiki = $input->getOption(self::OPT_MEDIAWIKI);
        if ($mediawiki) {
            assert(is_string($mediawiki));
            $this->database_configurator->setUpMediawiki($output, $db, $mediawiki, $app_user, $grant_hostname);
        }
    }
}
