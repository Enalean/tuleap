<?php
/**
 * Copyright (c) STMicroelectronics, 2005. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2005
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

use Symfony\Component\Console\Formatter\OutputFormatter;
use Tuleap\Project\UserPermissionsDao;

ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);

require_once __DIR__ . '/../../../src/www/include/pre.php';

$console_output = new \Symfony\Component\Console\Output\ConsoleOutput();

$time_start = microtime(true);

// First: check if LDAP plugin is active
$pluginManager = PluginManager::instance();
$ldapPlugin    = $pluginManager->getEnabledPluginByName('ldap');
if ($ldapPlugin instanceof LdapPlugin) {
    $ldapQuery = new LDAP_DirectorySynchronization($ldapPlugin->getLdap(), $ldapPlugin->getLogger());
    //If script is executed with --dry-run option
    if (isset($argv[1]) && $argv[1] == '--dry-run') {
        $users_to_suspend     = $ldapQuery->getLdapUserManager()->getUsersToBeSuspended();
        $nbr_users_to_suspend = count($users_to_suspend);
        $nbr_active_users     = $ldapQuery->getLdapUserManager()->getNbrActiveUsers();
        if ($nbr_users_to_suspend == 0) {
            $console_output->writeln('No user will be suspended');
            return;
        }
        $percentage_users_to_suspend = ($nbr_users_to_suspend / $nbr_active_users) * 100;
        $console_output->writeln('Number of users that will be suspended     : ' . OutputFormatter::escape((string) $nbr_users_to_suspend));
        $console_output->writeln('Number of active users                     : ' . OutputFormatter::escape((string) $nbr_active_users));
        if (! $threshold_users_suspension = $ldapPlugin->getLdap()->getLDAPParam('threshold_users_suspension')) {
            $console_output->writeln('Threshold                                  : Is Not defined');
        } else {
            $console_output->writeln('Threshold                                  : ' . OutputFormatter::escape($threshold_users_suspension) . ' %');
        }
        $console_output->writeln('Percentage of users that will be suspended : ' . OutputFormatter::escape((string) $percentage_users_to_suspend) . ' %');
        $console_output->writeln('--------------------------------------------------- List of users that will be suspended :');
        foreach ($users_to_suspend as $user) {
            $console_output->writeln('id     : ' . OutputFormatter::escape((string) $user->getId()));
            $console_output->writeln('login  : ' . OutputFormatter::escape($user->getUserName()));
            $console_output->writeln('name   : ' . OutputFormatter::escape($user->getRealName()));
            $console_output->writeln('e-mail : ' . OutputFormatter::escape((string) $user->getEmail()));
            $console_output->writeln('---------------------------------------------------');
        }
    } else {
        $ldapQuery->syncAll();
        $retentionPeriod = $ldapPlugin->getLdap()->getLDAPParam('daily_sync_retention_period');
        if ($retentionPeriod != null && $retentionPeriod != '') {
            $user_remover = new \Tuleap\Project\UserRemover(
                ProjectManager::instance(),
                EventManager::instance(),
                new ArtifactTypeFactory(false),
                new \Tuleap\Project\UserRemoverDao(),
                UserManager::instance(),
                new ProjectHistoryDao(),
                new UGroupManager(),
                new UserPermissionsDao(),
            );

            $ldapCleanUpManager = new LDAP_CleanUpManager($user_remover, $retentionPeriod);
            $ldapCleanUpManager->cleanAll();
        }

        $time_end = microtime(true);
        $time     = $time_end - $time_start;

        $console_output->writeln('Time elapsed: ' . OutputFormatter::escape((string) $time));
        $console_output->writeln('LDAP time: ' . OutputFormatter::escape((string) $ldapQuery->getElapsedLdapTime()));
    }
}
