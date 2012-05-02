<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
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
 */

require_once('pre.php');

$pluginManager = PluginManager::instance();
$ldapPlugin = $pluginManager->getPluginByName('ldap');
if ($ldapPlugin && $pluginManager->isPluginAvailable($ldapPlugin)) {
    $pluginPath = $ldapPlugin->getPluginPath();
} else {
    return;
}

$groupList = array();

$vGroupName = new Valid_String('ldap_group_name');
$vGroupName->required();
if($request->valid($vGroupName)) {
    $ldap = $ldapPlugin->getLdap();
    $lri  = $ldap->searchGroupAsYouType($request->get('ldap_group_name'), 15);
    if($lri !== false) {
        while($lri->valid()) {
            $lr = $lri->current();
            $groupList[] = $lr->getCommonName();
            $lri->next();
        }
        if($ldap->getErrno() == LDAP::ERR_SIZELIMIT) {
            $groupList[] = "<strong>...</strong>";
        }
    }
}


echo "<ul>\n";
foreach($groupList as $group) {
	echo "    <li>".$group."</li>\n";
}
echo "</ul>\n";
?>