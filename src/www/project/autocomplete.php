<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('pre.php');
//require_once('common/event/EventManager.class.php');
require_once('common/project/ProjectManager.class.php');

//
// Input treatment
//
$vName = new Valid_String('name');
$vName->required();
if ($request->valid($vName)) {
    $name = $request->get('name');
} else {
    // Finish script, no output
    exit;
}

// Number of user to display
$limit     = 15;
$list      = array();
$isMember  = false;
$isAdmin   = false;
$isPrivate = false;

$user = UserManager::instance()->getCurrentUser();
if ($user->isRestricted()) {
    $isMember = true;
}

$vPrivate = new Valid_Whitelist('private', array('1'));
$vPrivate->required();
// Allow the autocomplete to include private projects only to super user
if ($request->valid($vPrivate) && $user->isSuperUser()) {
    $isPrivate = true;
}

$prjManager     = ProjectManager::instance();
$nbProjectFound = 0;
$projects       = $prjManager->searchProjectsNameLike($name, $limit, $nbProjectFound, $user, $isMember, $isAdmin, $isPrivate);
foreach ($projects as $project) {
    $list[] = $project->getPublicName(). " (".$project->getUnixName().")";
}

$nbLeft = $nbProjectFound - $limit; 
if ($nbLeft > 0) {
    $list[] = '<strong>'.$nbLeft.' left ...</strong>';
}

//
// Display
//

echo "<ul>\n";
foreach ($list as $entry) {
    echo "  <li>$entry</li>\n";
}
echo "</ul>\n";

?>