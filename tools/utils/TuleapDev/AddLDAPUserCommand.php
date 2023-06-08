<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace TuleapDev\TuleapDev;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Tuleap\Cryptography\ConcealedString;

final class AddLDAPUserCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('add-ldap-user')
            ->setDescription('Add a LDAP user into development directory')
            ->addArgument('login', InputArgument::REQUIRED, 'Login name (tuleap username)')
            ->addArgument('realname', InputArgument::REQUIRED, 'Real name')
            ->addArgument('email', InputArgument::REQUIRED, 'Email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $login = $input->getArgument('login');
        if (! $login) {
            $output->writeln('<error>Login is missing</error>');
            return Command::INVALID;
        }
        $real_name = $input->getArgument('realname');
        if (! $real_name) {
            $output->writeln('<error>Real name is missing</error>');
            return Command::INVALID;
        }
        $email = $input->getArgument('email');
        if (! $email) {
            $output->writeln('<error>Email is missing</error>');
            return Command::INVALID;
        }

        $question_helper = $this->getHelper('question');
        assert($question_helper instanceof QuestionHelper);
        $question = new Question('Please enter user password: ');
        $question->setHidden(true);
        $question->setHiddenFallback(true);
        $password = $question_helper->ask($input, $output, $question);
        if (! $password) {
            $output->writeln('<error>Password is missing</error>');
            return Command::INVALID;
        }

        $gid_number      = -1;
        $employee_number = -1;
        $uid_number      = -1;

        \ForgeConfig::loadInSequence();
        require_once __DIR__ . '/../../../plugins/ldap/include/ldapPlugin.php';
        $ldap_plugin = new \LdapPlugin(-1);
        $ldap_plugin->setName('ldap');
        $ldap_plugin->getPluginInfo();

        $ldap = $ldap_plugin->getLdap();
        $ldap->connect();
        $ldap->unbind();
        $ldap->bind('cn=Manager,dc=tuleap,dc=local', new ConcealedString(getenv('LDAP_MANAGER_PASSWORD')));


        $search_login_iterator = $ldap->searchLogin($login);
        if ($search_login_iterator === false || count($search_login_iterator) !== 0) {
            $output->writeln('<error>LDAP error or Login already used</error>');
            return Command::INVALID;
        }

        $search_email_iterator = $ldap->searchEmail($email);
        if ($search_email_iterator === false || count($search_email_iterator) !== 0) {
            $output->writeln('<error>LDAP error or Email already used</error>');
            return Command::INVALID;
        }

        $result_iterator = $ldap->search(\ForgeConfig::get('sys_ldap_people_dn'), '(objectclass=inetOrgPerson)', \LDAP::SCOPE_ONELEVEL, ['employeeNumber', 'gidNumber', 'uidNumber']);

        foreach ($result_iterator as $result) {
            if ((int) $result->get('employeeNumber') > $employee_number) {
                $employee_number = (int) $result->get('employeeNumber');
            }
            if ((int) $result->get('gidNumber') > $gid_number) {
                $gid_number = (int) $result->get('gidNumber');
            }
            if ((int) $result->get('uidNumber') > $uid_number) {
                $uid_number = (int) $result->get('uidNumber');
            }
        }

        ++$employee_number;
        ++$uid_number;
        ++$gid_number;

        $user_dn = 'uid=' . ldap_escape($login) . ',' . \ForgeConfig::get('sys_ldap_people_dn');
        $info    = [
            "employeeNumber" => $employee_number,
            "cn"             => $real_name,
            "sn"             => $real_name,
            "displayName"    => $real_name,
            "mail"           => $email,
            "uid"            => $login,
            'gidNumber'      => $gid_number,
            'uidNumber'      => $uid_number,
            'homeDirectory'  => '/home/' . $login,
            'userPassword'   => $this->getEncryptedPassword($password),
            "objectclass"    => [
                "posixAccount",
                "inetOrgPerson",
            ],
        ];

        $ds = ldap_connect(\ForgeConfig::get('sys_ldap_server'));
        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
        if (! @ldap_bind($ds, 'cn=Manager,dc=tuleap,dc=local', getenv('LDAP_MANAGER_PASSWORD'))) {
            $output->writeln('Unable to bind to LDAP server for Manager: ' . ldap_error($ds));
            return self::FAILURE;
        }

        if (! @ldap_add($ds, $user_dn, $info)) {
            $output->writeln('Unable to add user to LDAP: ' . ldap_error($ds));
            return self::FAILURE;
        }

        $output->writeln('User `' . $login . '` (`' . $employee_number . '`) added into LDAP');

        return self::SUCCESS;
    }

    private function getEncryptedPassword(string $password): string
    {
        return '{CRYPT}' . crypt($password, '$6$rounds=50000$' . bin2hex(random_bytes(25) . '$'));
    }
}
