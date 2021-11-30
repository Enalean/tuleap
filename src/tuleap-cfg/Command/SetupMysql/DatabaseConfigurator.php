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

namespace TuleapCfg\Command\SetupMysql;

use Symfony\Component\Console\Style\SymfonyStyle;
use Tuleap\Cryptography\ConcealedString;

final class DatabaseConfigurator
{
    private const OPT_MEDIAWIKI_VALUE_PER_PROJECT = 'per-project';
    private const OPT_MEDIAWIKI_VALUE_CENTRAL     = 'central';
    private const DB_ALREADY_INIT                 = 1;
    private const DB_FRESH                        = 2;

    public function __construct(private \PasswordHandler $password_handler)
    {
    }

    /**
     * @psalm-param value-of<ConnectionManagerInterface::ALLOWED_SSL_MODES> $ssl_mode
     */
    public function initializeDatabase(
        SymfonyStyle $output,
        DBWrapperInterface $db,
        string $app_dbname,
        string $app_user,
        string $grant_hostname,
        string $app_password,
    ): int {
        $existing_db = $db->single(sprintf('SHOW DATABASES LIKE "%s"', $db->escapeIdentifier($app_dbname, false)));
        if ($existing_db) {
            $output->writeln(sprintf('<info>Database %s already exists</info>', $app_dbname));
            return self::DB_ALREADY_INIT;
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
        return self::DB_FRESH;
    }

    public function initializeDatabaseAndLoadValues(
        SymfonyStyle $output,
        DBWrapperInterface $db,
        string $app_dbname,
        string $app_user,
        string $grant_hostname,
        string $app_password,
        ConcealedString $site_admin_password,
        string $domain_name,
    ): void {
        $db_status = $this->initializeDatabase($output, $db, $app_dbname, $app_user, $grant_hostname, $app_password);
        if ($db_status === self::DB_ALREADY_INIT) {
            return;
        }
        $db->run('USE ' . $app_dbname);
        $this->loadInitValues($db, $site_admin_password, $domain_name);
    }

    public function loadInitValues(DBWrapperInterface $db, ConcealedString $admin_password, string $domain_name): void
    {
        $row = $db->run('SHOW TABLES');
        if (count($row) === 0) {
            $statement_loader = new StatementLoader($db);
            $statement_loader->loadFromFile(__DIR__ . '/../../../db/mysql/database_structure.sql');
            $statement_loader->loadFromFile(__DIR__ . '/../../../db/mysql/database_initvalues.sql');
            $statement_loader->loadFromFile(__DIR__ . '/../../../forgeupgrade/db/install-mysql.sql');

            $tuleap_version = trim(file_get_contents(__DIR__ . '/../../../../VERSION'));
            $db->run('INSERT INTO tuleap_installed_version VALUES (?)', $tuleap_version);

            $db->run(
                'UPDATE user SET password=?, unix_pw=?, email=?, add_date=? WHERE user_id=101',
                $this->password_handler->computeHashPassword($admin_password),
                $this->password_handler->computeUnixPassword($admin_password),
                'codendi-admin@' . $domain_name,
                time(),
            );
            $db->run('UPDATE user SET email=? WHERE user_id = 100', 'noreply@' . $domain_name);
        }
    }

    /**
     * @see https://bugs.mysql.com/bug.php?id=80379
     */
    public function setUpNss(SymfonyStyle $io, DBWrapperInterface $db, string $target_dbname, string $nss_user, string $nss_password, string $grant_hostname): void
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

    public function setUpMediawiki(SymfonyStyle $io, DBWrapperInterface $db, string $mediawiki, string $app_user, string $grant_hostname): void
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
}
