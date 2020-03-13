<?php
// Copyright (c) Enalean, 2016. All Rights Reserved.
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
$request  = HTTPRequest::instance();
$group_id = $request->get('group_id');

if (!$group_id) {
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
        exit_error('Error', $GLOBALS['Language']->getText('cvs_detail_commit', 'error_notfound', array($commit_id)));
    }

    commits_header(array(
        'title' => $GLOBALS['Language']->getText('cvs_detail_commit', 'title', array($commit_id)),
        'help'  => 'cvs.html#querying-cvs',
        'group' => $group_id
    ));

    show_commit_details($group_id, $commit_id, $result);

    commits_footer(array());
} else {
    exit_error('Error', $GLOBALS['Language']->getText('cvs_detail_commit', 'error_notfound', array($commit_id)));
}
