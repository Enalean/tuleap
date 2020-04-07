<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
 */

require_once __DIR__ . '/../../../src/www/include/pre.php';

/**
 * Extract parameters from user input
 *
 * This function reassamble user submitted values splited by PHP. PHP transform
 * user input in an array, the cut is done on spaces (each space create a new
 * entry, even when string is encapsulated between double quotes).
 * The separator is -- and each argument must be like "--argname="
 *
 * @param array $argv
 * @return array
 */
function extract_params($argv)
{
    $arguments = array();
    for ($i = 1; $i < count($argv); ++$i) {
        $arg = $argv[$i];
        // If arg start by "--" this is the beginning of a new option
        if (strpos($arg, "--") === 0) {
            $eqpos = strpos($arg, "=");
            $argname = substr($arg, 2, $eqpos - 2);
            $arguments[$argname] = substr($arg, $eqpos + 1);
        } else {
            $arguments[$argname] .= " " . $arg;
        }
    }
    return $arguments;
}

// First: check if LDAP plugin is active
// Ensure LDAP plugin is active
$pluginManager = PluginManager::instance();
$ldapPlugin    = $pluginManager->getPluginByName('ldap');
if ($ldapPlugin && $pluginManager->isPluginAvailable($ldapPlugin)) {
// -h --help help
// --ldapid="" ldap_id(required)
// --realname="" realname (required)
// --email="" email (required)
// --uid="" uid (required);

//print_r($_SERVER['argv']);
    $arg = extract_params($_SERVER['argv']);
//print_r($arg);
    if (
        isset($arg['ldapid'])
        && isset($arg['realname'])
        && isset($arg['email'])
        && isset($arg['uid'])
    ) {
        //  Check if user exists
        $user = UserManager::instance()->getUserByLdapId($arg['ldapid']);
        if ($user) {
            echo "Error: ldap id already in the database\n";
            exit;
        } else {
            $ldapUm = $ldapPlugin->getLdapUserManager();
            $user = $ldapUm->createAccount($arg['ldapid'], $arg['uid'], $arg['realname'], $arg['email']);
            if ($user) {
                echo "ID=" . $user->getId() . ":STATUS=" . $user->getStatus() . "\n";
                return 0;
            }
        }
    }
}
echo "Error\n";
return 1;
//phpinfo();
