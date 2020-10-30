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

use Tuleap\SVN\SvnCoreAccess;

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
assert($svn_core_access instanceof SvnCoreAccess);
$svn_core_access->redirect();

$vFunc = new Valid_WhiteList('func', [
    'general_settings',
    'immutable_tags',
    'access_control',
    'notification',
    'access_control_version'
]);
$vFunc->required();
if ($request->valid($vFunc)) {
    $func = $request->get('func');

    switch ($func) {
        case 'immutable_tags':
            require('./immutable_tags.php');
            break;
        case 'general_settings':
            require('./general_settings.php');
            break;
        case 'access_control':
            require('./access_control.php');
            break;
        case 'access_control_version':
            if (! $request->exist('accessfile_history_id')) {
                break;
            }
            $version_id = $request->get('accessfile_history_id');
            $dao = new SVN_AccessFile_DAO();
            $result = $dao->getVersionContent($version_id);

            $GLOBALS['Response']->sendJSON(['content' => $result]);

            break;
        case 'notification':
            require('./notification.php');
            break;
    }
} else {
   // get project object
    $pm = ProjectManager::instance();
    $project = $pm->getProject($group_id);
    if (! $project || ! is_object($project) || $project->isError()) {
        exit_no_group();
    }

    svn_header_admin([
        'title' => $Language->getText('svn_admin_index', 'admin'),
        'help' => 'svn.html#subversion-administration-interface'
       ]);

    $purifier = Codendi_HTMLPurifier::instance();

    echo '<H2>' . $Language->getText('svn_admin_index', 'admin') . '</H2>';
    echo '<H3><a href="/svn/admin/?func=general_settings&group_id=' . $purifier->purify(urlencode($group_id)) . '">' . $Language->getText('svn_admin_index', 'gen_sett') . '</a></H3>';
    echo '<p>' . $Language->getText('svn_admin_index', 'welcome') . '</p>';

    echo '<H3><a href="/svn/admin/?func=immutable_tags&group_id=' . $purifier->purify(urlencode($group_id)) . '">' . $Language->getText('svn_admin_index', 'immutable_tags') . '</a></H3>';
    echo '<p>' . $Language->getText('svn_admin_index', 'immutable_tags_description') . '</p>';

    echo '<H3><a href="/svn/admin/?func=access_control&group_id=' . $purifier->purify(urlencode($group_id)) . '">' . $Language->getText('svn_admin_index', 'access') . '</a></H3>';
    echo '<P>' . $Language->getText('svn_admin_index', 'access_comment') . '</P>';
    echo '<H3><a href="/svn/admin/?func=notification&group_id=' . $purifier->purify(urlencode($group_id)) . '">' . $Language->getText('svn_admin_index', 'email_sett') . '</a></H3>';
    echo '<p>' . $Language->getText('svn_admin_index', 'email_comment') . '</P>';

    svn_footer([]);
}
