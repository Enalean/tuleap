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

use PasswordHandlerFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TuleapCfg\Command\SetupMysql\ConnectionManager;
use TuleapCfg\Command\SetupMysql\StatementLoader;

final class SetupMysqlCommand extends Command
{
    protected function configure()
    {
        $this->setName('setup:mysql')
            ->addOption('host', '', InputOption::VALUE_REQUIRED, 'MySQL server host', 'localhost')
            ->addOption('user', '', InputOption::VALUE_REQUIRED, 'MySQL user for setup', 'tuleapadm')
            ->addOption('dbname', '', InputOption::VALUE_REQUIRED, 'Database name', 'tuleap')
            ->addOption('password', '', InputOption::VALUE_REQUIRED, 'User\'s password')
            ->addArgument('admin_password', InputArgument::REQUIRED, 'Site Administrator password')
            ->addArgument('domain_name', InputArgument::REQUIRED, 'Tuleap server public url');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $host = (string) $input->getOption('host');
        $user = (string) $input->getOption('user');
        $dbname = (string) $input->getOption('dbname');
        $password = $input->getOption('password');
        $admin_password = (string) $input->getArgument('admin_password');
        $domain_name    = (string) $input->getArgument('domain_name');

        if (! $password) {
            $io->getErrorStyle()->writeln(sprintf('<error>Missing mysql password for application user %s</error>', $user));
            return 1;
        }

        $connexion_manager = new ConnectionManager();
        $db = $connexion_manager->getDBWithDBName($io, $host, $user, $password, $dbname);
        if ($db === null) {
            $io->getErrorStyle()->writeln('<error>Unable to connect to mysql server</error>');
            return 1;
        }

        $output->writeln('<info>Successfully connected to the database !</info>');

        $connexion_manager->checkSQLModes($db);

        $password_handler = PasswordHandlerFactory::getPasswordHandler();

        $row = $db->run('SHOW TABLES');
        if (count($row) === 0) {
            $statement_loader = new StatementLoader($db);
            $statement_loader->loadFromFile(__DIR__ . '/../../db/mysql/database_structure.sql');
            $statement_loader->loadFromFile(__DIR__ . '/../../db/mysql/database_initvalues.sql');
            $statement_loader->loadFromFile('/usr/share/forgeupgrade/db/install-mysql.sql');

            $tuleap_version = trim(file_get_contents(__DIR__ . '/../../../VERSION'));
            $db->run('INSERT INTO tuleap_installed_version VALUES (?)', $tuleap_version);

            $db->run(
                'UPDATE user SET password=?, unix_pw=?, email=?, add_date=? WHERE user_id=101',
                $password_handler->computeHashPassword($admin_password),
                $password_handler->computeUnixPassword($admin_password),
                'codendi-admin@' . $domain_name,
                time(),
            );
            $db->run('UPDATE user SET email=? WHERE user_id = 100', 'noreply@' . $domain_name);
        }
        return 0;
    }
}
