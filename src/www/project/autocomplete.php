<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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

require_once __DIR__ . '/../include/pre.php';

// Input treatment
$vName = new Valid_String('name');
$vName->required();
if ($request->valid($vName)) {
    $name = $request->get('name');
} else {
    // Finish script, no output
    exit;
}

$json_format = false;

if ($request->get('return_type') === 'json_for_select_2') {
    $json_format = true;
}

// Number of user to display
$limit     = 15;
$page      = 1;

if ($request->get('page')) {
    $page = (int) $request->get('page');
}

$offset    = ($page - 1) * $limit;
$list      = array();
$isMember  = false;
$isAdmin   = false;
$isPrivate = false;

$user = UserManager::instance()->getCurrentUser();
if ($user->isRestricted()) {
    $isMember = true;
}

$vPrivate = new Valid_WhiteList('private', array('1'));
$vPrivate->required();
// Allow the autocomplete to include private projects only to super user
if ($request->valid($vPrivate) && $user->isSuperUser()) {
    $isPrivate = true;
}

$prjManager     = ProjectManager::instance();
$nbProjectFound = 0;
$projects       = $prjManager->searchProjectsNameLike($name, $limit, $nbProjectFound, $user, $isMember, $isAdmin, $isPrivate, $offset);
foreach ($projects as $project) {
    $list[] = $project->getPublicName() . " (" . $project->getUnixName() . ")";
}

$nbLeft = $nbProjectFound - $limit;
if ($nbLeft > 0 && ! $json_format) {
    $list[] = '<strong>' . $nbLeft . ' left ...</strong>';
}


$purifier = Codendi_HTMLPurifier::instance();

if ($json_format) {
    $json_entries = array();
    foreach ($list as $entry) {
        $json_entries[] = array(
            'id'   => $entry,
            'text' => $entry
        );
    }

    $more_results = ($offset + $limit) < $nbProjectFound;
    $output       = array(
        'results' => $json_entries,
        'pagination' => array(
            'more' => $more_results
        )
    );

    $GLOBALS['Response']->sendJSON($output);
} else {
    echo "<ul>\n";
    foreach ($list as $entry) {
        echo '<li>' . $purifier->purify($entry) . '</li>';
    }
    echo "</ul>\n";
}
