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

use ParagonIE\EasyDB\EasyDB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TuleapCfg\Command\SetupMysql\ConnectionManager;
use TuleapCfg\Command\SetupMysql\ConnectionManagerInterface;
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

    /**
     * @var MysqlCommandHelper
     */
    private $command_helper;
    /**
     * @var ConnectionManagerInterface
     */
    private $connection_manager;
    /**
     * @var string|null
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
            ->addOption(self::OPT_APP_PASSWORD, '', InputOption::VALUE_REQUIRED, 'Password for the application dbuser (typically tuleapadm)')
            ->addOption(self::OPT_NSS_USER, '', InputOption::VALUE_REQUIRED, 'Name of the DB user that will be used for libnss-mysql or http authentication', 'dbauthuser')
            ->addOption(self::OPT_NSS_PASSWORD, '', InputOption::VALUE_REQUIRED, 'Password for nss-user')
            ->addOption(self::OPT_MEDIAWIKI, '', InputOption::VALUE_REQUIRED, 'Grant permissions for mediawiki. Possible values: `per-project` or `central`');
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

        if ($initialize_db) {
            $return_value = $this->initializeDatabase(
                $input,
                $io,
                $host,
                $port,
                $ssl_mode,
                $ssl_ca_file,
                $user,
                $password,
                $app_dbname,
                $app_user,
                $app_password
            );
            if ($return_value !== 0) {
                return $return_value;
            }
        }

        return $this->writeConfigurationFile($host, $port, $ssl_mode, $ssl_ca_file, $app_dbname, $app_user, $app_password);
    }

    /**
     * @psalm-param value-of<ConnectionManagerInterface::ALLOWED_SSL_MODES> $ssl_mode
     */
    private function initializeDatabase(
        InputInterface $input,
        SymfonyStyle $output,
        string $host,
        int $port,
        string $ssl_mode,
        string $ssl_ca_file,
        string $user,
        string $password,
        string $app_dbname,
        string $app_user,
        ?string $app_password
    ): int {
        $db = $this->connection_manager->getDBWithoutDBName($output, $host, $port, $ssl_mode, $ssl_ca_file, $user, $password);
        if ($db === null) {
            $output->getErrorStyle()->writeln('<error>Unable to connect to mysql server</error>');
            return 1;
        }
        $output->writeln('<info>Successfully connected to the server !</info>');

        $this->connection_manager->checkSQLModes($db);

        if ($app_password && $app_user) {
            $this->setUpApplication(
                $output,
                $db,
                $app_dbname,
                $app_user,
                $app_password,
            );
        }

        $nss_password = $input->getOption(self::OPT_NSS_PASSWORD);
        if ($nss_password) {
            assert(is_string($nss_password));
            $nss_user = $input->getOption(self::OPT_NSS_USER);
            assert(is_string($nss_user));
            $this->setUpNss(
                $output,
                $db,
                $app_dbname,
                $nss_user,
                $nss_password,
            );
        }

        $mediawiki = $input->getOption(self::OPT_MEDIAWIKI);
        if ($mediawiki) {
            assert(is_string($mediawiki));
            $app_user = $input->getOption(self::OPT_APP_USER);
            assert(is_string($app_user));
            $this->setUpMediawiki($output, $db, $mediawiki, $app_user);
        }

        $db->run('FLUSH PRIVILEGES');

        return 0;
    }

    private function setUpApplication(OutputInterface $io, EasyDB $db, string $target_dbname, string $target_dbuser, string $target_password): void
    {
        $existing_db = $db->single(sprintf('SHOW DATABASES LIKE "%s"', $db->escapeIdentifier($target_dbname, false)));
        if ($existing_db) {
            $io->writeln(sprintf('<info>Database %s already exists</info>', $target_dbname));
        } else {
            $io->writeln(sprintf('<info>Create database %s</info>', $target_dbname));
            $db->run(sprintf('CREATE DATABASE %s DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci', $db->escapeIdentifier($target_dbname)));
        }

        $io->writeln(sprintf('<info>Grant privileges on %s to %s</info>', $target_dbname, $target_dbuser));
        $this->createUser($db, $target_dbuser, $target_password);
        $db->run(sprintf(
            'GRANT ALL PRIVILEGES ON %s.* TO %s',
            $db->escapeIdentifier($target_dbname),
            $this->quoteDbUser($target_dbuser),
        ));
    }

    /**
     * @see https://bugs.mysql.com/bug.php?id=80379
     */
    private function setUpNss(SymfonyStyle $io, EasyDB $db, string $target_dbname, string $nss_user, string $nss_password): void
    {
        $io->writeln(sprintf('<info>Grant privileges to %s</info>', $nss_user));

        $this->createUser($db, $nss_user, $nss_password);

        $this->grantOn($db, ['SELECT'], $target_dbname, 'user', $nss_user);
        $this->grantOn($db, ['SELECT'], $target_dbname, 'groups', $nss_user);
        $this->grantOn($db, ['SELECT'], $target_dbname, 'user_group', $nss_user);

        $this->grantOn($db, ['SELECT', 'UPDATE'], $target_dbname, 'svn_token', $nss_user);
        $this->grantOn($db, ['SELECT'], $target_dbname, 'plugin_ldap_user', $nss_user);
    }

    private function grantOn(EasyDB $db, array $grants, string $db_name, string $table_name, string $user): void
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
            $this->quoteDbUser($user),
        ));
        $db->run(sprintf(
            'REVOKE CREATE ON %s.%s FROM %s',
            $db->escapeIdentifier($db_name),
            $db->escapeIdentifier($table_name),
            $this->quoteDbUser($user),
        ));
    }

    private function createUser(EasyDB $db, string $user, string $password): void
    {
        $db->run(sprintf(
            'CREATE USER IF NOT EXISTS %s IDENTIFIED BY \'%s\'',
            $this->quoteDbUser($user),
            $db->escapeIdentifier($password, false),
        ));
    }

    private function quoteDbUser(string $user_identifier): string
    {
        return implode(
            '@',
            array_map(
                static function ($str) {
                    return sprintf("'%s'", $str);
                },
                explode('@', $user_identifier)
            )
        );
    }

    private function setUpMediawiki(SymfonyStyle $io, EasyDB $db, string $mediawiki, string $app_user): void
    {
        if ($mediawiki !== self::OPT_MEDIAWIKI_VALUE_CENTRAL && $mediawiki !== self::OPT_MEDIAWIKI_VALUE_PER_PROJECT) {
            throw new \RuntimeException(sprintf('Invalid --mediawiki value. Valid values are `%s` or `%s`', self::OPT_MEDIAWIKI_VALUE_PER_PROJECT, self::OPT_MEDIAWIKI_VALUE_CENTRAL));
        }
        if ($mediawiki === self::OPT_MEDIAWIKI_VALUE_PER_PROJECT) {
            $io->writeln(sprintf('<info>Configure mediawiki per-project permissions on %s to %s</info>', 'plugin_mediawiki_%', $app_user));
            $db->run(
                sprintf(
                    'GRANT ALL PRIVILEGES ON `plugin_mediawiki_%%`.* TO %s',
                    $this->quoteDbUser($app_user),
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
                    'GRANT ALL PRIVILEGES on %s.* TO %s',
                    $db->escapeIdentifier($mediawiki_database),
                    $this->quoteDbUser($app_user)
                )
            );
        }
    }

    private function writeConfigurationFile(
        string $host,
        int $port,
        string $ssl_mode,
        string $ssl_ca_file,
        string $dbname,
        string $user,
        ?string $password
    ): int {
        if ($password === null) {
            return 0;
        }
        $template = file_get_contents(__DIR__ . '/../../etc/database.inc.dist');

        $user_parts = explode('@', $user);

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
                $user_parts[0],
                $password,
            ],
            $template,
        );

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
}
