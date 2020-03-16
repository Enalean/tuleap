<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
/**
 * Simple API script available through HTTP
 *
 * input parameters:
 *    group_id : project where references are defined
 *    text     : HTML input text
 * output: HTML text with embedded references (links to goto script)
*/

header('Content-type: text/html');

$reference_manager = ReferenceManager::instance();
$request = HTTPRequest::instance();


if (!$request->getValidated('group_id', 'GroupId')) {
    if (!$request->get('group_name')) {
        $group_id = 100;
    } else {
        $pm       = ProjectManager::instance();
        $project  = $pm->getProjectByUnixName($request->get('group_name'));
        $group_id = false;
        if ($project) {
            $group_id = $project->getID();
        }
    }
} else {
    $group_id = $request->get('group_id');
}

if (!$request->getValidated('text', 'text')) {
    // Empty string? return empty string...
    exit;
}
if ($request->get('help')) {
    echo $GLOBALS['Language']->getText('project_reference', 'insert_syntax');
    exit;
}
$text = $request->get('text');
$purifier = Codendi_HTMLPurifier::instance();
echo $purifier->purify($text . "\n", CODENDI_PURIFIER_BASIC, $group_id);
exit;
