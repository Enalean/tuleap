<?php
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net


$request = HTTPRequest::instance();

$vGroupId = new Valid_GroupId();
$vGroupId->required();
if (! $request->valid($vGroupId)) {
    exit_no_group(); // need a group_id !!!
} else {
    $group_id = $request->get('group_id');

    $vCommitId = new Valid_UInt('commit_id');
    $vCommitId->required();
    if ($request->valid($vCommitId)) {
        $commit_id = $request->get('commit_id');
    } else {
        $commit_id = 0;
    }

    $vRevId = new Valid_UInt('rev_id');
    $vRevId->required();
    if ($request->valid($vRevId)) {
        $rev_id = $request->get('rev_id');
    } else {
        $rev_id = 0;
    }

    $vOrder = new Valid_WhiteList('order', ['filename', 'type']);
    $vOrder->required();
    if ($request->valid($vOrder)) {
        $order = $request->get('order');
    } else {
        $order = '';
    }

    $pm         = ProjectManager::instance();
    $project    = $pm->getProject($group_id);
    $group_name = $project->getUnixName(false);
    $result     = svn_data_get_revision_detail($group_id, $commit_id, $rev_id, $order);
    if (db_numrows($result) > 0) {
        $title = $Language->getText('svn_detail_revision', 'svn_rev', db_result($result, 0, 'revision'));
        svn_header(
            $project,
            \Tuleap\Layout\HeaderConfigurationBuilder::get($title)
                ->inProject($project, Service::SVN)
                ->build(),
            null,
        );
        svn_utils_show_revision_detail($result, $group_id, $group_name, $commit_id);
        svn_footer([]);
    } else {
        exit_error($Language->getText('global', 'error'), $Language->getText('svn_detail_revision', 'id_not_found', $commit_id));
    }
}
