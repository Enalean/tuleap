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
use TuleapCfg\Command\SetupMysql\ConnectionManager;
use TuleapCfg\Command\SetupMysql\ConnectionManagerInterface;
use TuleapCfg\Command\SetupMysql\DBWrapperInterface;
use TuleapCfg\Command\SetupMysql\InvalidSSLConfigurationException;
use TuleapCfg\Command\SetupMysql\MysqlCommandHelper;

final class SetupMysqlInitCommand extends Command
{
    private const OPT_ADMIN_USER     = 'admin-user';
    private const OPT_ADMIN_PASSWORD = 'admin-password';
    private const OPT_APP_DBNAME     = 'db-name';
    private const OPT_APP_USER       = 'app-user';
    private const OPT_APP_PASSWORD   = 'app-password';
    private const OPT_NSS_USER       = 'nss-user';
    private const OPT_NSS_PASSWORD   = 'nss-password';
    private const OPT_MEDIAWIKI      = 'mediawiki';
    private const OPT_MEDIAWIKI_VALUE_PER_PROJECT = 'per-project';
    private const OPT_MEDIAWIKI_VALUE_CENTRAL     = 'central';
    private const OPT_SKIP_DATABASE  = 'skip-database';
    private const OPT_GRANT_HOSTNAME = 'grant-hostname';
    private const OPT_LOG_PASSWORD   = 'log-password';
    private const OPT_AZURE_SUFFIX   = 'azure-suffix';
    private const ENV_AZURE_SUFFIX   = 'TULEAP_DB_AZURE_SUFFIX';

    /**
     * @var MysqlCommandHelper
     */
    private $command_helper;
    /**
     * @var ConnectionManagerInterface
     */
    private $connection_manager;
    /**
     * @var string
     */
    private $base_directory;

    public function __construct(ConnectionManagerInterface $connection_manager, ?string $base_directory = null)
    {
        $this->base_directory     = $base_directory ?: '/';
        $this->command_helper     = new MysqlCommandHelper($this->base_directory);
        $this->connection_manager = $connection_manager;

        parent::__construct('setup:mysql-init');
    }

    public function getHelp(): string
    {
        $ssl_opt       = MysqlCommandHelper::OPT_SSL;
        $ssl_disabled  = ConnectionManager::SSL_NO_SSL;
        $ssl_no_verify = ConnectionManager::SSL_NO_VERIFY;
        $ssl_verify_ca = ConnectionManager::SSL_VERIFY_CA;
        $ssl_ca_file   = MysqlCommandHelper::OPT_SSL_CA;
        return <<<EOT
        Initialize the database (MySQL > 5.7 or MariaDB 10.3) for use with Tuleap

        This command is idempotent so it's safe to be used several times (with same parameters...).

        By using --app-password option, it will create the tuleap DB (`tuleap` by default or --db-name),
        the database admin user (`tuleapadm` or --admin-user) with the required GRANTS.

        By using --nss-password., it will create the user to be used of lower level integration (used for subversion,
        cvs, etc). Please note that, unless you are using subversion, it's unlikely that you will need to use this
        option.

        Both --app-password and --nss-password can be used independently or together.

        The connection to the database can be encrypted and you can control the way it's done with ${ssl_opt} with:
        - ${ssl_disabled}: no usage of encryption (default)
        - ${ssl_no_verify}: connection will be encrypted by host won't be verified
        - ${ssl_verify_ca}: connection is encrypted and host is verified

        And encrypted connection requires a Certificate Authority (CA) file that must be provide with ${ssl_ca_file}.

        EOT;
    }

    protected function configure(): void
    {
        $this->command_helper->addOptions($this);

        $this
            ->setDescription('Initialize database (users, database, permissions)')
            ->addOption(self::OPT_SKIP_DATABASE, '', InputOption::VALUE_NONE, 'Will skip database initialization (when you only want to re-write database.inc)')
            ->addOption(self::OPT_ADMIN_USER, '', InputOption::VALUE_REQUIRED, 'MySQL admin user', 'root')
            ->addOption(self::OPT_ADMIN_PASSWORD, '', InputOption::VALUE_REQUIRED, 'MySQL admin password')
            ->addOption(self::OPT_APP_DBNAME, '', InputOption::VALUE_REQUIRED, 'Name of the DB name to host Tuleap tables (`tuleap` by default)', 'tuleap')
            ->addOption(self::OPT_APP_USER, '', InputOption::VALUE_REQUIRED, 'Name of the DB user to be used for Tuleap (`tuleapadm`) by default', 'tuleapadm')
            ->addOption(self::OPT_GRANT_HOSTNAME, '', InputOption::VALUE_REQUIRED, 'Hostname value for mysql grant. This is the right hand side of `user`@`hostname`. Default is `%`', '%')
            ->addOption(self::OPT_APP_PASSWORD, '', InputOption::VALUE_REQUIRED, 'Password for the application dbuser (typically tuleapadm)')
            ->addOption(self::OPT_NSS_USER, '', InputOption::VALUE_REQUIRED, 'Name of the DB user that will be used for libnss-mysql or http authentication', 'dbauthuser')
            ->addOption(self::OPT_NSS_PASSWORD, '', InputOption::VALUE_REQUIRED, 'Password for nss-user')
            ->addOption(self::OPT_MEDIAWIKI, '', InputOption::VALUE_REQUIRED, 'Grant permissions for mediawiki. Possible values: `per-project` or `central`')
            ->addOption(self::OPT_LOG_PASSWORD, '', InputOption::VALUE_REQUIRED, 'Write user & password into given file')
            ->addOption(self::OPT_AZURE_SUFFIX, '', InputOption::VALUE_REQUIRED, 'Value to add to user\'s name to comply with Microsoft Azure rules');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $host        = $this->command_helper->getHost($input);
            $port        = $this->command_helper->getPort($input);
            $ssl_mode    = $this->command_helper->getSSLMode($input);
            $ssl_ca_file = $this->command_helper->getSSLCAFile($input, $ssl_mode);
        } catch (InvalidSSLConfigurationException $exception) {
            $io->getErrorStyle()->writeln(sprintf('<error>%s</error>', $exception->getMessage()));
            return 1;
        }

        $initialize_db = ! (bool) $input->getOption(self::OPT_SKIP_DATABASE);

        $user = $input->getOption(self::OPT_ADMIN_USER);
        assert(is_string($user));

        $password = $input->getOption(self::OPT_ADMIN_PASSWORD);
        if (! $password) {
            $io->getErrorStyle()->writeln(sprintf('<error>Missing mysql password for admin user `%s`</error>', $user));
            return 1;
        }
        assert(is_string($password));

        $app_dbname = $input->getOption(self::OPT_APP_DBNAME);
        assert(is_string($app_dbname));

        $app_password = $input->getOption(self::OPT_APP_PASSWORD);
        if ($app_password && ! is_string($app_password)) {
            $io->getErrorStyle()->writeln(sprintf('<error>%s must be a string</error>', self::OPT_APP_PASSWORD));
            return 1;
        }
        assert($app_password === null || is_string($app_password));

        $app_user = $input->getOption(self::OPT_APP_USER);
        if (! is_string($app_user)) {
            $io->getErrorStyle()->writeln(sprintf('<error>%s must be a string</error>', self::OPT_APP_USER));
        }
        assert(is_string($app_user));

        if (getenv(self::ENV_AZURE_SUFFIX) !== false) {
            $azure_suffix = getenv(self::ENV_AZURE_SUFFIX);
        } else {
            $azure_suffix = $input->getOption(self::OPT_AZURE_SUFFIX);
            if (! $azure_suffix) {
                $azure_suffix = '';
            }
        }
        assert(is_string($azure_suffix));

        $grant_hostname = $input->getOption(self::OPT_GRANT_HOSTNAME);
        assert(is_string($grant_hostname));

        $nss_user = $input->getOption(self::OPT_NSS_USER);
        assert(is_string($nss_user));

        $nss_password = $input->getOption(self::OPT_NSS_PASSWORD);
        assert($nss_password === null || is_string($nss_password));
        $local_inc_file = $this->base_directory . '/etc/tuleap/conf/local.inc';
        if ($nss_password !== null && ! file_exists($local_inc_file)) {
            $io->getErrorStyle()->writeln(sprintf('<error>Setting NSS user/password requires to have %s file first</error>', $local_inc_file));
            return 1;
        }

        if ($initialize_db) {
            $db = $this->connection_manager->getDBWithoutDBName($io, $host, $port, $ssl_mode, $ssl_ca_file, $user, $password);
            $output->writeln('<info>Successfully connected to the server !</info>');

            $this->connection_manager->checkSQLModes($db);

            $this->initializeDatabase($input, $io, $db, $app_dbname, $app_user, $grant_hostname, $app_password);
            $this->initializeNss($input, $io, $db, $app_dbname, $grant_hostname, $nss_user, $nss_password);
            $this->initializeMediawiki($input, $io, $db, $app_user, $grant_hostname);

            $db->run('FLUSH PRIVILEGES');
        }

        $return_value = $this->writeDatabaseIncFile($host, $port, $ssl_mode, $ssl_ca_file, $app_dbname, $app_user, $azure_suffix, $app_password);
        if ($return_value !== 0) {
            return $return_value;
        }

        return $this->writeLocalIncFile($local_inc_file, $nss_user, $azure_suffix, $nss_password);
    }

    /**
     * @psalm-param value-of<ConnectionManagerInterface::ALLOWED_SSL_MODES> $ssl_mode
     */
    private function initializeDatabase(
        InputInterface $input,
        SymfonyStyle $output,
        DBWrapperInterface $db,
        string $app_dbname,
        string $app_user,
        string $grant_hostname,
        ?string $app_password
    ): void {
        if (! $app_password || ! $app_user) {
            return;
        }

        $existing_db = $db->single(sprintf('SHOW DATABASES LIKE "%s"', $db->escapeIdentifier($app_dbname, false)));
        if ($existing_db) {
            $output->writeln(sprintf('<info>Database %s already exists</info>', $app_dbname));
        } else {
            $output->writeln(sprintf('<info>Create database %s</info>', $app_dbname));
            $db->run(sprintf('CREATE DATABASE %s DEFAULT CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci', $db->escapeIdentifier($app_dbname)));
        }

        $output->writeln(sprintf('<info>Grant privileges on %s to %s</info>', $app_dbname, $app_user));
        $this->createUser($db, $app_user, $app_password, $grant_hostname);
        $db->run(sprintf(
            'GRANT ALL PRIVILEGES ON %s.* TO %s',
            $db->escapeIdentifier($app_dbname),
            $this->quoteDbUser($app_user, $grant_hostname),
        ));

        $log_password = $input->getOption(self::OPT_LOG_PASSWORD);
        if (is_string($log_password)) {
            file_put_contents($log_password, sprintf("MySQL application user (%s): %s\n", $app_user, $app_password), FILE_APPEND);
        }
    }

    private function initializeNss(
        InputInterface $input,
        SymfonyStyle $output,
        DBWrapperInterface $db,
        string $app_dbname,
        string $grant_hostname,
        string $nss_user,
        ?string $nss_password
    ): void {
        if (! $nss_password) {
            return;
        }

        $this->setUpNss(
            $output,
            $db,
            $app_dbname,
            $nss_user,
            $nss_password,
            $grant_hostname,
        );

        $log_password = $input->getOption(self::OPT_LOG_PASSWORD);
        if (is_string($log_password)) {
            file_put_contents(
                $log_password,
                sprintf("MySQL dbauth user (%s): %s\n", $nss_user, $nss_password),
                FILE_APPEND
            );
        }
    }

    private function initializeMediawiki(
        InputInterface $input,
        SymfonyStyle $output,
        DBWrapperInterface $db,
        string $app_user,
        string $grant_hostname
    ): void {
        $mediawiki = $input->getOption(self::OPT_MEDIAWIKI);
        if ($mediawiki) {
            assert(is_string($mediawiki));
            $this->setUpMediawiki($output, $db, $mediawiki, $app_user, $grant_hostname);
        }
    }


    /**
     * @see https://bugs.mysql.com/bug.php?id=80379
     */
    private function setUpNss(SymfonyStyle $io, DBWrapperInterface $db, string $target_dbname, string $nss_user, string $nss_password, string $grant_hostname): void
    {
        $io->writeln(sprintf('<info>Grant privileges to %s</info>', $nss_user));

        $this->createUser($db, $nss_user, $nss_password, $grant_hostname);

        $this->grantOn($db, ['SELECT'], $target_dbname, 'user', $nss_user, $grant_hostname);
        $this->grantOn($db, ['SELECT'], $target_dbname, 'groups', $nss_user, $grant_hostname);
        $this->grantOn($db, ['SELECT'], $target_dbname, 'user_group', $nss_user, $grant_hostname);

        $this->grantOn($db, ['SELECT', 'UPDATE'], $target_dbname, 'svn_token', $nss_user, $grant_hostname);
        $this->grantOn($db, ['SELECT'], $target_dbname, 'plugin_ldap_user', $nss_user, $grant_hostname);
        $this->grantOn($db, ['SELECT'], $target_dbname, 'plugin_openidconnectclient_user_mapping', $nss_user, $grant_hostname);
    }

    private function grantOn(DBWrapperInterface $db, array $grants, string $db_name, string $table_name, string $user, string $grant_hostname): void
    {
        array_walk(
            $grants,
            static function (string $grant) {
                // List is not complete because no need for other type yet, feel free to add supported one if you feel
                // the need
                // @see https://dev.mysql.com/doc/refman/8.0/en/grant.html#grant-table-privileges
                if (! in_array($grant, ['SELECT', 'UPDATE', 'DELETE', 'INSERT'])) {
                    throw new \RuntimeException('Invalid grant type: ' . $grant);
                }
            },
        );
        $db->run(sprintf(
            'GRANT CREATE,%s ON %s.%s TO %s',
            implode(',', $grants),
            $db->escapeIdentifier($db_name),
            $db->escapeIdentifier($table_name),
            $this->quoteDbUser($user, $grant_hostname),
        ));
        $db->run(sprintf(
            'REVOKE CREATE ON %s.%s FROM %s',
            $db->escapeIdentifier($db_name),
            $db->escapeIdentifier($table_name),
            $this->quoteDbUser($user, $grant_hostname),
        ));
    }

    private function createUser(DBWrapperInterface $db, string $user, string $password, string $grant_hostname): void
    {
        $db->run(sprintf(
            'CREATE USER IF NOT EXISTS %s IDENTIFIED BY \'%s\'',
            $this->quoteDbUser($user, $grant_hostname),
            $db->escapeIdentifier($password, false),
        ));
    }

    private function quoteDbUser(string $user_identifier, string $grant_hostname): string
    {
        return sprintf("'%s'@'%s'", $user_identifier, $grant_hostname);
    }

    private function setUpMediawiki(SymfonyStyle $io, DBWrapperInterface $db, string $mediawiki, string $app_user, string $grant_hostname): void
    {
        if ($mediawiki !== self::OPT_MEDIAWIKI_VALUE_CENTRAL && $mediawiki !== self::OPT_MEDIAWIKI_VALUE_PER_PROJECT) {
            throw new \RuntimeException(sprintf('Invalid --mediawiki value. Valid values are `%s` or `%s`', self::OPT_MEDIAWIKI_VALUE_PER_PROJECT, self::OPT_MEDIAWIKI_VALUE_CENTRAL));
        }
        if ($mediawiki === self::OPT_MEDIAWIKI_VALUE_PER_PROJECT) {
            $io->writeln(sprintf('<info>Configure mediawiki per-project permissions on %s to %s</info>', 'plugin_mediawiki_%', $app_user));
            $db->run(
                sprintf(
                    'GRANT ALL PRIVILEGES ON `plugin_mediawiki_%%`.* TO %s',
                    $this->quoteDbUser($app_user, $grant_hostname),
                )
            );
        } else {
            $mediawiki_database = 'tuleap_mediawiki';
            $io->writeln(sprintf('<info>Configure mediawiki central permissions on %s to %s</info>', $mediawiki_database, $app_user));
            $existing_db = $db->single(sprintf('SHOW DATABASES LIKE "%s"', $db->escapeIdentifier($mediawiki_database, false)));
            if ($existing_db) {
                $io->writeln(sprintf('<info>Database %s already exists</info>', $mediawiki_database));
            } else {
                $db->run(
                    sprintf(
                        'CREATE DATABASE %s',
                        $db->escapeIdentifier($mediawiki_database)
                    )
                );
            }
            $db->run(
                sprintf(
                    'GRANT ALL PRIVILEGES ON %s.* TO %s',
                    $db->escapeIdentifier($mediawiki_database),
                    $this->quoteDbUser($app_user, $grant_hostname)
                )
            );
        }
    }

    private function writeDatabaseIncFile(
        string $host,
        int $port,
        string $ssl_mode,
        string $ssl_ca_file,
        string $dbname,
        string $user,
        string $azure_suffix,
        ?string $password
    ): int {
        if ($password === null) {
            return 0;
        }
        $template = file_get_contents(__DIR__ . '/../../etc/database.inc.dist');

        if ($azure_suffix !== '') {
            $user = sprintf('%s@%s', $user, $azure_suffix);
        }

        $conf_string = str_replace(
            [
                'localhost',
                '%sys_dbname%',
                '%sys_dbuser%',
                '%sys_dbpasswd%',
            ],
            [
                $host,
                $dbname,
                $user,
                $password,
            ],
            $template,
        );

        $conf_string = preg_replace('/\$sys_dbport.*/', '$sys_dbport = ' . $port . ';', $conf_string);

        if ($ssl_mode !== ConnectionManagerInterface::SSL_NO_SSL) {
            $verify_cert = $ssl_mode === ConnectionManagerInterface::SSL_VERIFY_CA ? 1 : 0;
            $conf_string = preg_replace(
                [
                    '/\$sys_enablessl.*/',
                    '/\$sys_db_ssl_ca.*/',
                    '/\$sys_db_ssl_verify_cert.*/',
                ],
                [
                    '$sys_enablessl = \'1\';',
                    sprintf('$sys_db_ssl_ca = \'%s\';', $ssl_ca_file),
                    sprintf('$sys_db_ssl_verify_cert = \'%d\';', $verify_cert),
                ],
                $conf_string,
            );
        }

        $target_file = $this->base_directory . '/etc/tuleap/conf/database.inc';
        if (! file_exists($target_file)) {
            touch($target_file);
        }
        chmod($target_file, 0640);
        chown($target_file, 'root');
        chgrp($target_file, 'codendiadm');

        if (file_put_contents($target_file, $conf_string) === strlen($conf_string)) {
            return 0;
        }
        return 1;
    }

    private function writeLocalIncFile(string $local_inc_file, string $user, string $azure_suffix, ?string $password): int
    {
        if (! $password) {
            return 0;
        }

        if ($azure_suffix !== '') {
            $user = sprintf('%s@%s', $user, $azure_suffix);
        }

        $conf_string = preg_replace(
            [
                '/\$sys_dbauth_user.*/',
                '/\$sys_dbauth_passwd.*/',
            ],
            [
                sprintf('$sys_dbauth_user = \'%s\';', $user),
                sprintf('$sys_dbauth_passwd = \'%s\';', $password),
            ],
            file_get_contents($local_inc_file),
        );

        if (file_put_contents($local_inc_file, $conf_string) === strlen($conf_string)) {
            return 0;
        }
        return 1;
    }
}
