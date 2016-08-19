<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
//

require_once('pre.php');
require_once('common/frs/FRSPackageFactory.class.php');
require_once('common/frs/FileModuleMonitorFactory.class.php');

if (user_isloggedin()) {
    $vFilemodule_id = new Valid_UInt('filemodule_id');
    $vFilemodule_id->required();
    if ($request->valid($vFilemodule_id)) {
        $filemodule_id = $request->get('filemodule_id');
        $pm            = ProjectManager::instance();
        $um            = UserManager::instance();
        $userHelper    = new UserHelper();
        $currentUser   = $um->getCurrentUser();
        $frspf         = new FRSPackageFactory();
        $fmmf          = new FileModuleMonitorFactory();
        if ($frspf->userCanRead($group_id, $filemodule_id, $currentUser->getId())) {
            $fmmf->processMonitoringActions($request, $currentUser, $group_id, $filemodule_id, $um, $userHelper);

            file_utils_header(array('title' => $Language->getText('file_showfiles', 'file_p_for', $pm->getProject($group_id)->getPublicName())));
            echo $fmmf->getMonitoringHTML($currentUser, $group_id, $filemodule_id, $um, $userHelper);
            file_utils_footer(array());
        } else {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor', 'no_permission'));
            $GLOBALS['Response']->redirect('showfiles.php?group_id='.$group_id);
        }
    } else {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor', 'choose_p'));
        $GLOBALS['Response']->redirect('showfiles.php?group_id='.$group_id);
    }
} else {
    exit_not_logged_in();
}
