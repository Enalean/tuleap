<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
 * SourceForge: Breaking Down the Barriers to Open Source Development
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

use Tuleap\ConcurrentVersionsSystem\ServiceCVS;

$request  = HTTPRequest::instance();
$group_id = $request->get('group_id');

if (! $group_id) {
    exit_no_group(); // need a group_id !!!
}

$order     = $request->get('order');
$order_str = "";

if ($order) {
    if ($order === 'filename') {
        $order_str = " ORDER BY dir, file";
    }
}

$when_str = '';
$id_str   = "AND cvs_checkins.descid=" . db_ei($checkin_id) . " ";

$commit_id  = $request->get('commit_id');
if ($commit_id) {
    $id_str    = "AND cvs_checkins.commitid=" . db_ei($commit_id) . " ";
    if ($desc_id) {
        $desc_id = db_ei($desc_id);
        $id_str  = $id_str . "AND cvs_checkins.descid=$desc_id ";
    }
}

$when = $request->get('when');
if ($when) {
    $when     = db_es($when);
    $when_str = "AND cvs_checkins.ci_when='$when' ";
}

$tag = $request->get('tag');
if ($tag) {
    $tag      = db_es($tag);
    $when_str = $when_str . "AND cvs_checkins.stickytag='$tag' ";
}

$sql = "SELECT repository, cvs_commits.comm_when as c_when, repositoryid, description, file, fileid, dir, dirid, type, branch, revision, addedlines, removedlines " .
    "FROM cvs_dirs, cvs_descs, cvs_files, cvs_checkins, cvs_branches, cvs_repositories, cvs_commits " .
    "WHERE cvs_checkins.fileid=cvs_files.id " .
    "AND cvs_checkins.dirid=cvs_dirs.id " .
    "AND cvs_checkins.commitid=cvs_commits.id " .
    "AND cvs_checkins.branchid=cvs_branches.id " .
        "AND cvs_checkins.descid=cvs_descs.id " .
    "AND cvs_checkins.repositoryid=cvs_repositories.id " .
    $id_str .
        $when_str . $order_str;


$result = db_query($sql);

if (db_numrows($result) > 0) {
    if (get_group_id_from_repository(db_result($result, 0, 'repository')) != $group_id) {
        exit_error('Error', $GLOBALS['Language']->getText('cvs_detail_commit', 'error_notfound', [$commit_id]));
    }

    $pm = ProjectManager::instance();
    $project = $pm->getProject($group_id);
    $service = $project->getService(\Service::CVS);
    if (! ($service instanceof ServiceCVS)) {
        exit_error(
            $GLOBALS['Language']->getText('global', 'error'),
            $GLOBALS['Language']->getText('cvs_commit_utils', 'error_off')
        );
        return;
    }

    $service->displayCVSRepositoryHeader(
        $request->getCurrentUser(),
        $GLOBALS['Language']->getText('cvs_detail_commit', 'title', [$commit_id]),
        'query',
    );

    show_commit_details($group_id, $commit_id, $result);

    commits_footer([]);
} else {
    exit_error('Error', $GLOBALS['Language']->getText('cvs_detail_commit', 'error_notfound', [$commit_id]));
}
