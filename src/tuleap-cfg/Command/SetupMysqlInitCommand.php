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

    public function getHelp()
    {
        return <<<EOT
        Initialize the database (MySQL > 5.7 or MariaDB 10.3) for use with Tuleap

        By using --app-password option, it will create the tuleap DB (`tuleap` by default or --db-name),
        the database admin user (`tuleapadm` or --admin-user) with the required GRANTS.

        By using --nss-password., it will create the user to be used of lower level integration (used for subversion,
        cvs, etc). Please note that, unless you are using subversion, it's unlikely that you will need to use this
        option.

        Both --app-password and --nss-password can be used independently or together.

        This command is idempotent so it's safe to be used several times (with same parameters...).
        EOT;
    }

    protected function configure()
    {
        $this->setName('setup:mysql-init')
            ->setDescription('Initialize database (users, database, permissions)')
            ->addOption('host', '', InputOption::VALUE_REQUIRED, 'MySQL server host', 'localhost')
            ->addOption(self::OPT_ADMIN_USER, '', InputOption::VALUE_REQUIRED, 'MySQL admin user', 'root')
            ->addOption(self::OPT_ADMIN_PASSWORD, '', InputOption::VALUE_REQUIRED, 'MySQL admin password')
            ->addOption(self::OPT_APP_DBNAME, '', InputOption::VALUE_REQUIRED, 'Name of the DB name to host Tuleap tables (`tuleap` by default)', 'tuleap')
            ->addOption(self::OPT_APP_USER, '', InputOption::VALUE_REQUIRED, 'Name of the DB user to be used for Tuleap (`tuleapadm`) by default', 'tuleapadm')
            ->addOption(self::OPT_APP_PASSWORD, '', InputOption::VALUE_REQUIRED, 'Password for the application dbuser (typically tuleapadm)')
            ->addOption(self::OPT_NSS_USER, '', InputOption::VALUE_REQUIRED, 'Name of the DB user that will be used for libnss-mysql or http authentication', 'dbauthuser')
            ->addOption(self::OPT_NSS_PASSWORD, '', InputOption::VALUE_REQUIRED, 'Password for nss-user')
            ->addOption(self::OPT_MEDIAWIKI, '', InputOption::VALUE_REQUIRED, 'Grant permissions for mediawiki. Possible values: `per-project` or `central`');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $host = (string) $input->getOption('host');
        $user = (string) $input->getOption(self::OPT_ADMIN_USER);
        $password = $input->getOption(self::OPT_ADMIN_PASSWORD);
        if (! $password) {
            $io->getErrorStyle()->writeln(sprintf('<error>Missing mysql password for admin user `%s`</error>', $user));
            return 1;
        }

        $connexion_manager = new ConnectionManager();
        $db = $connexion_manager->getDBWithoutDBName($io, $host, $user, $password);
        if ($db === null) {
            $io->getErrorStyle()->writeln('<error>Unable to connect to mysql server</error>');
            return 1;
        }
        $io->writeln('<info>Successfully connected to the server !</info>');

        $connexion_manager->checkSQLModes($db);

        $app_password = $input->getOption(self::OPT_APP_PASSWORD);
        if ($app_password) {
            $this->setUpApplication(
                $io,
                $db,
                $input->getOption(self::OPT_APP_DBNAME),
                $input->getOption(self::OPT_APP_USER),
                $app_password,
            );
        }

        $nss_password = $input->getOption(self::OPT_NSS_PASSWORD);
        if ($nss_password) {
            $this->setUpNss(
                $io,
                $db,
                $input->getOption(self::OPT_APP_DBNAME),
                $input->getOption(self::OPT_NSS_USER),
                $nss_password,
            );
        }

        $mediawiki = $input->getOption(self::OPT_MEDIAWIKI);
        if ($mediawiki) {
            $this->setUpMediawiki($io, $db, $mediawiki, $input->getOption(self::OPT_APP_USER));
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
    private function setUpNss(SymfonyStyle $io, EasyDB $db, string $target_dbname, string $nss_user, string $nss_password)
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

    private function createUser(EasyDB $db, string $user, string $password)
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
}
