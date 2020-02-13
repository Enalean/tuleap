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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TuleapCfg\Command\SetupMysql\ConnectionManager;

final class SetupMysqlInitCommand extends Command
{
    protected function configure()
    {
        $this->setName('setup:mysql-init')
            ->addOption('host', '', InputOption::VALUE_REQUIRED, 'MySQL server host', 'localhost')
            ->addOption('user', '', InputOption::VALUE_REQUIRED, 'MySQL user for setup', 'root')
            ->addOption('password', '', InputOption::VALUE_REQUIRED, 'User\'s password')
            ->addArgument('password', InputArgument::REQUIRED, 'Password for the dbuser')
            ->addArgument('dbname', InputArgument::OPTIONAL, 'Name of the DB name to host Tuleap tables (`tuleap` by default)', 'tuleap')
            ->addArgument('dbuser', InputArgument::OPTIONAL, 'Name of the DB user to be used for Tuleap (`tuleapadm`) by default', 'tuleapadm');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $host = (string) $input->getOption('host');
        $user = (string) $input->getOption('user');
        $password = $input->getOption('password');
        if (! $password) {
            $io->getErrorStyle()->writeln(sprintf('<error>Missing mysql password for admin user %s</error>', $user));
            return 1;
        }

        $target_dbname = $input->getArgument('dbname');
        $target_dbuser = $input->getArgument('dbuser');
        $target_password = $input->getArgument('password');

        $connexion_manager = new ConnectionManager();
        $db = $connexion_manager->getDBWithoutDBName($io, $host, $user, $password);
        if ($db === null) {
            $io->getErrorStyle()->writeln('<error>Unable to connect to mysql server</error>');
            return 1;
        }

        $io->writeln('<info>Successfully connected to the server !</info>');

        $connexion_manager->checkSQLModes($db);

        $existing_db = $db->single(sprintf('SHOW DATABASES LIKE "%s"', $db->escapeIdentifier($target_dbname, false)));
        if ($existing_db) {
            $io->writeln(sprintf('<info>Database %s already exists</info>', $target_dbname));
        } else {
            $io->writeln(sprintf('<info>Create database %s</info>', $target_dbname));
            $db->run(sprintf('CREATE DATABASE %s DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci', $db->escapeIdentifier($target_dbname)));
        }

        $io->writeln(sprintf('<info>Grant privileges to %s</info>', $target_dbuser));
        $db->run(sprintf(
            'GRANT ALL PRIVILEGES ON %s.* TO %s IDENTIFIED BY \'%s\'',
            $db->escapeIdentifier($target_dbname),
            $this->quoteDbUser($target_dbuser),
            $db->escapeIdentifier($target_password, false),
        ));
        $db->run('FLUSH PRIVILEGES');

        return 0;
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
}
