<?php
// Copyright (c) Enalean, 2014. All Rights Reserved.
// This file is part of Tuleap
//
// Codendi
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com
//
//
//
//    Originally written by Laurent Julliard 2004, Codendi Team, Xerox
$project_manager = ProjectManager::instance();
$project         = $project_manager->getProject($group_id);
$project_svnroot = $project->getSVNRootPath();
$dao             = new SVN_AccessFile_DAO();
$path            = realpath(dirname(__FILE__) . '/../../../templates/svn/');
$renderer        = TemplateRendererFactory::build()->getRenderer($path);
$request         = HTTPRequest::instance();
$group_id        = $request->get('group_id');

$request->valid(new Valid_String('post_changes'));
$request->valid(new Valid_String('SUBMIT'));

if ($request->isPost() && $request->existAndNonEmpty('post_changes')) {
    $vAccessFile = new Valid_Text('form_accessfile');
    $vAccessFile->setErrorMessage($GLOBALS['Language']->getText('svn_admin_access_control', 'upd_fail'));

    if ($request->valid($vAccessFile)) {
        $saf             = new SVNAccessFile();
        $form_accessfile = null;
        //store the custom access file in db

        if ($request->exist('submit_new_version')) {
            $form_accessfile = trim(
                $saf->parseGroupLines($project, $request->get('form_accessfile'), true)
            );
            $dao->saveNewAccessFileVersionInProject($group_id, $form_accessfile);
        } else {
            $form_accessfile = $saf->parseGroupLines($project, $request->get('other_version_content'), true);
            $version_id      = $request->get('version_selected');
            $dao->updateAccessFileVersionInProject($group_id, $version_id);
        }

        require_once __DIR__ . '/../svn_utils.php';
        $ret = svn_utils_write_svn_access_file_with_defaults($project_svnroot, $form_accessfile);

        if ($ret) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('svn_admin_access_control', 'upd_success'));
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('svn_admin_access_control', 'upd_fail'));
        }
    }

    $GLOBALS['Response']->redirect('/svn/admin/?func=access_control&group_id=' . $group_id);
}

// Display the form
svn_header_admin(array ('title' => $GLOBALS['Language']->getText('svn_admin_access_control', 'access_ctrl'),
                        'help' => 'svn.html#subversion-access-control'));

if (svn_utils_svn_repo_exists($project_svnroot)) {
    $select_options = array();
    foreach ($dao->getAllVersions($group_id) as $row) {
        $select_options[] = array(
            'id'      => $row['id'],
            'version' => $row['version_number'],
            'date'    => format_date("Y-m-d", (float) $row['version_date'], '')
        );
    }

    $version_number = $dao->getCurrentVersionNumber($group_id);

    $current_version_title = '';

    if ($version_number != $dao->getLastVersionNumber($group_id)) {
        $current_version_title = $GLOBALS['Language']->getText(
            'svn_admin_access_control',
            'previous_version',
            array($version_number)
        );
    } else {
        $current_version_title = $GLOBALS['Language']->getText(
            'svn_admin_access_control',
            'last_version',
            array($version_number)
        );
    }

    $renderer->renderToPage(
        'access-file-form',
        new SVN_AccessFile_Presenter(
            $project,
            svn_utils_read_svn_access_file($project_svnroot),
            svn_utils_read_svn_access_file_defaults($project_svnroot, true),
            $select_options,
            $version_number,
            $current_version_title
        )
    );
} else {
    $renderer->renderToPage(
        'access-file-nofile',
        new SVN_AccessFile_NoFilePresenter()
    );
}

svn_footer(array());
