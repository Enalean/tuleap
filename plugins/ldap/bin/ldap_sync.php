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

ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);

require_once 'pre.php';
require_once dirname(__FILE__).'/../include/LDAP_DirectorySynchronization.class.php';

$time_start = microtime(true);

// First: check if LDAP plugin is active
$pluginManager = PluginManager::instance();
$ldapPlugin    = $pluginManager->getPluginByName('ldap');
if ($pluginManager->isPluginAvailable($ldapPlugin)) {

    $ldapQuery = new LDAP_DirectorySynchronization($ldapPlugin->getLdap());
    $ldapQuery->syncAll();

    $time_end = microtime(true);
    $time = $time_end - $time_start;

    echo "Time elapsed: ".$time."\n";
    echo "LDAP time: ".$ldapQuery->getElapsedLdapTime()."\n";
}
?>
