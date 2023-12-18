<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

use LDAP\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class ModLdapUserCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('ldap:mod-user')
            ->setAliases(['mod-ldap-user'])
            ->setDescription('Modify a LDAP user into development directory')
            ->addArgument('login', InputArgument::REQUIRED, 'Login name (uid)')
            ->addOption('realname', '', InputOption::VALUE_REQUIRED, 'New Realname')
            ->addOption('login', '', InputOption::VALUE_REQUIRED, 'New login (uid)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $login = $input->getArgument('login');
        if (! $login) {
            $output->writeln('<error>Login is missing</error>');
            return Command::INVALID;
        }
        $real_name = $input->getOption('realname');
        $new_uid   = $input->getOption('login');

        if (! $real_name && ! $new_uid) {
            $output->writeln('<error>You must change at least one thing (realname, login, ...)</error>');
            return Command::INVALID;
        }

        \ForgeConfig::loadInSequence();
        require_once __DIR__ . '/../../../plugins/ldap/include/ldapPlugin.php';
        $ldap_plugin = new \LdapPlugin(-1);
        $ldap_plugin->setName('ldap');
        $ldap_plugin->getPluginInfo();

        $result = LDAPHelper::getLdapConnection()
            ->andThen(fn (Connection $ds) => LDAPHelper::getUser($ds, $login)
                ->andThen(function (array $entries) use ($ds, $login, $real_name, $new_uid) {
                    $info         = $this->getExistingUserInfo($entries);
                    $info_changed = false;
                    if ($real_name && $info['cn'] !== $real_name) {
                        $info['cn']          = $real_name;
                        $info['sn']          = $real_name;
                        $info['displayName'] = $real_name;
                        $info_changed        = true;
                    }

                    if ($new_uid && $new_uid !== $login) {
                        $info['uid']  = $new_uid;
                        $info_changed = true;
                    }

                    if ($new_uid && $new_uid !== $login) {
                        $existing_full_user_dn = 'uid=' . ldap_escape($login) . ',' . \ForgeConfig::get('sys_ldap_people_dn');
                        $new_user_dn           = 'uid=' . ldap_escape($new_uid);
                        $parent_dn             = \ForgeConfig::get('sys_ldap_people_dn');
                        if (! ldap_rename($ds, $existing_full_user_dn, $new_user_dn, $parent_dn, true)) {
                            return Result::err(Fault::fromMessage('Unable to modify user in LDAP: ' . ldap_error($ds)));
                        }
                        $login = $new_uid;
                    }

                    if ($info_changed) {
                        $user_dn = 'uid=' . ldap_escape($login) . ',' . \ForgeConfig::get('sys_ldap_people_dn');
                        if (! ldap_mod_replace($ds, $user_dn, $info)) {
                            return Result::err(Fault::fromMessage('Unable to modify user in LDAP: ' . ldap_error($ds)));
                        }
                    }

                    return Result::ok('User modified in LDAP');
                }));
        if (Result::isErr($result)) {
            $output->writeln((string) $result->error);
            return self::FAILURE;
        }
        assert($result instanceof Ok);
        $output->writeln($result->value);
        return self::SUCCESS;
    }

    private function getExistingUserInfo(array $entries): array
    {
        $info = [];
        foreach ($entries as $key => $entry) {
            if (is_int($key) || $key === 'count' || $key === 'dn') {
                continue;
            }
            if ($entry['count'] === 1) {
                $info[$key] = $entry[0];
            } else {
                $info[$key] = [];
                for ($i = 0; $i < $entry['count']; $i++) {
                    $info[$key][] = $entry[$i];
                }
            }
        }
        return $info;
    }
}
