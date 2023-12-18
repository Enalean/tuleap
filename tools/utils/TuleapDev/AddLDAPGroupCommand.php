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
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class AddLDAPGroupCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('ldap:add-group')
            ->setDescription('Add or modify an LDAP group development directory')
            ->addArgument('group_name', InputArgument::REQUIRED, 'Group name')
            ->addArgument('members', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Members logins (separated by space)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $group_name = $input->getArgument('group_name');
        if (! $group_name) {
            $output->writeln('<error>Group name is missing</error>');
            return self::INVALID;
        }

        $members = $input->getArgument('members');
        if (count($members) === 0) {
            $output->writeln('<error>You must specify at least one member</error>');
            return self::INVALID;
        }

        \ForgeConfig::loadInSequence();
        require_once __DIR__ . '/../../../plugins/ldap/include/ldapPlugin.php';
        $ldap_plugin = new \LdapPlugin(-1);
        $ldap_plugin->setName('ldap');
        $ldap_plugin->getPluginInfo();

        $result = LDAPHelper::getLdapConnection()
            ->andThen(function (Connection $ds) use ($group_name, $members, $output) {
                $update       = false;
                $group_search = \ForgeConfig::get('sys_ldap_grp_cn') . '=' . ldap_escape($group_name, '', LDAP_ESCAPE_FILTER);
                $sr           = ldap_search($ds, \ForgeConfig::get('sys_ldap_grp_dn'), $group_search);
                $entries      = ldap_get_entries($ds, $sr);
                if ($entries['count'] > 0) {
                    $update = true;
                }

                return $this->getMembers($ds, $members)
                    ->andThen(function (array $members) use ($ds, $group_name, $update, $output) {
                        $wording = $update ? 'Updating' : 'Adding';

                        $output->writeln(sprintf("%s to %s: %s", $wording, $group_name, implode(' ', $members)));

                        $group_dn = sprintf("%s=%s,%s", \ForgeConfig::get('sys_ldap_grp_cn'), ldap_escape($group_name), \ForgeConfig::get('sys_ldap_grp_dn'));
                        $info     = [
                            "cn"             => $group_name,
                            'uniqueMember'   => $members,
                            "objectclass"    => [
                                "top",
                                "groupOfUniqueNames",
                            ],
                        ];

                        if ($update) {
                            if (! @ldap_mod_replace($ds, $group_dn, $info)) {
                                $error = '';
                                ldap_get_option($ds, LDAP_OPT_DIAGNOSTIC_MESSAGE, $error);
                                assert(is_string($error));
                                return Result::err(Fault::fromMessage(sprintf('Unable to modify group to LDAP: %s (%s)', ldap_error($ds), $error)));
                            }
                        } elseif (! @ldap_add($ds, $group_dn, $info)) {
                            $error = '';
                            ldap_get_option($ds, LDAP_OPT_DIAGNOSTIC_MESSAGE, $error);
                            assert(is_string($error));
                            return Result::err(Fault::fromMessage(sprintf('Unable to add group to LDAP: %s (%s)', ldap_error($ds), $error)));
                        }

                        return Result::ok(sprintf('Group `%s` (%s=%s,%s) updated into LDAP', $group_name, \ForgeConfig::get('sys_ldap_grp_cn'), $group_name, \ForgeConfig::get('sys_ldap_grp_dn')));
                    });
            });
        if (Result::isErr($result)) {
            $output->writeln(sprintf('<error>%s</error>', $result->error));
            return self::FAILURE;
        }
        assert($result instanceof Ok);
        $output->writeln($result->value);
        return self::SUCCESS;
    }

    /**
     * @return Ok<string[]>|Err<Fault>
     */
    private function getMembers(Connection $ds, array $members): Ok|Err
    {
        $members_dn = [];
        foreach ($members as $member) {
            $res_dn = $this->getUserDn($ds, $member);
            if (Result::isErr($res_dn)) {
                return $res_dn;
            }
            $members_dn[] = $res_dn->value;
        }
        return Result::ok($members_dn);
    }

    /**
     * @return Ok<string>|Err<Fault>
     */
    private function getUserDn(Connection $ds, string $login): Ok|Err
    {
        return LDAPHelper::getUser($ds, $login)->andThen(fn (array $user_info) => Result::ok($user_info['dn']));
    }
}
