<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * Originally written by Laurent Julliard 2004, Codendi Team, Xerox
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

use Tuleap\SVNCore\SvnCoreAccess;

require_once __DIR__ . '/../../include/pre.php';
require_once __DIR__ . '/../svn_data.php';

$vGroupId = new Valid_GroupId();
$vGroupId->required();

$request = HTTPRequest::instance();

// need a group_id !!!
if (! $request->valid($vGroupId)) {
    exit_no_group();
} else {
    $group_id = $request->get('group_id');
}

// Must be at least Project Admin to configure this
if (! user_ismember($group_id, 'A') && ! user_ismember($group_id, 'SVN_ADMIN')) {
    exit_permission_denied();
}

$project = ProjectManager::instance()->getProject($group_id);
if (! $project || $project->isError() || ! $project->isActive()) {
    exit_permission_denied();
}
$svn_core_access = EventManager::instance()->dispatch(new SvnCoreAccess($project, $_SERVER['REQUEST_URI'], $GLOBALS['Response']));
$svn_core_access->redirect();

$vFunc = new Valid_WhiteList('func', [
    'immutable_tags',
    'notification',
]);
$vFunc->required();
if ($request->valid($vFunc)) {
    $func = $request->get('func');

    switch ($func) {
        case 'immutable_tags':
            require('./immutable_tags.php');
            break;
        case 'notification':
            require('./notification.php');
            break;
    }
} else {
   // get project object
    $pm      = ProjectManager::instance();
    $project = $pm->getProject($group_id);
    if (! $project || ! is_object($project) || $project->isError()) {
        exit_no_group();
    }

    svn_header_admin($Language->getText('svn_admin_index', 'admin'));

    $purifier = Codendi_HTMLPurifier::instance();

    echo '<H2>' . $Language->getText('svn_admin_index', 'admin') . '</H2>';

    echo '<H3><a href="/svn/admin/?func=immutable_tags&group_id=' . $purifier->purify(urlencode($group_id)) . '">' . $Language->getText('svn_admin_index', 'immutable_tags') . '</a></H3>';
    echo '<p>' . $Language->getText('svn_admin_index', 'immutable_tags_description') . '</p>';

    echo '<H3><a href="/svn/admin/?func=notification&group_id=' . $purifier->purify(urlencode($group_id)) . '">' . $Language->getText('svn_admin_index', 'email_sett') . '</a></H3>';
    echo '<p>' . $Language->getText('svn_admin_index', 'email_comment') . '</P>';

    svn_footer([]);
}
