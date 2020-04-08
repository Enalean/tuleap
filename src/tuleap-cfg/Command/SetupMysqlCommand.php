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
use TuleapCfg\Command\SetupMysql\InvalidSSLConfigurationException;
use TuleapCfg\Command\SetupMysql\MysqlCommandHelper;
use TuleapCfg\Command\SetupMysql\StatementLoader;

final class SetupMysqlCommand extends Command
{
    /**
     * @var MysqlCommandHelper
     */
    private $command_helper;

    public function __construct()
    {
        $this->command_helper = new MysqlCommandHelper('/');
        parent::__construct('setup:mysql');
    }

    protected function configure(): void
    {
        $this->command_helper->addOptions($this);
        $this
            ->setDescription('Feed the database with core structure and values')
            ->addOption('host', '', InputOption::VALUE_REQUIRED, 'MySQL server host', 'localhost')
            ->addOption('user', '', InputOption::VALUE_REQUIRED, 'MySQL user for setup', 'tuleapadm')
            ->addOption('dbname', '', InputOption::VALUE_REQUIRED, 'Database name', 'tuleap')
            ->addOption('password', '', InputOption::VALUE_REQUIRED, 'User\'s password')
            ->addArgument('admin_password', InputArgument::REQUIRED, 'Site Administrator password')
            ->addArgument('domain_name', InputArgument::REQUIRED, 'Tuleap server public url');
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

        $user = $input->getOption('user');
        assert(is_string($user));
        $dbname = $input->getOption('dbname');
        assert(is_string($dbname));
        $password = $input->getOption('password');
        $admin_password = $input->getArgument('admin_password');
        assert(is_string($admin_password));
        $domain_name    = $input->getArgument('domain_name');
        assert(is_string($domain_name));

        if (! $password) {
            $io->getErrorStyle()->writeln(sprintf('<error>Missing mysql password for application user %s</error>', $user));
            return 1;
        }
        assert(is_string($password));

        $connexion_manager = new ConnectionManager();
        $db = $connexion_manager->getDBWithDBName($io, $host, $port, $ssl_mode, $ssl_ca_file, $user, $password, $dbname);
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
