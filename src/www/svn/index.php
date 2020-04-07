<?php
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../svn/svn_data.php';

$vFunc = new Valid_WhiteList('func', array('detailrevision', 'browse','info'));

$vGroupId = new Valid_UInt('group_id');
$vGroupId->required();

$there_are_specific_permissions = true;
$project_svnroot = '';
if ($request->valid($vGroupId)) {
    $pm = ProjectManager::instance();
    $project                        = $pm->getProject($request->get('group_id'));
    $project_svnroot                     = $project->getSVNRootPath();
}

if ($request->valid($vFunc) && $request->get('func') === 'detailrevision' && user_isloggedin()) {
    $there_are_specific_permissions = svn_utils_is_there_specific_permission($project_svnroot);

    require('./detail_revision.php');
} elseif (
    user_isloggedin() &&                                                             //We'll browse
            (
             ($request->valid($vFunc) && $request->get('func') === 'browse')     //if user ask for it
             || $request->existAndNonEmpty('rev_id')     //or if user set rev_id
             )
) {
    $there_are_specific_permissions = svn_utils_is_there_specific_permission($project_svnroot);

    require('./browse_revision.php');
} else {
    require('./svn_intro.php');
}
