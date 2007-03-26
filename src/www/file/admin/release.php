<?php


//
//SourceForge: Breaking Down the Barriers to Open Source Development
//Copyright 1999-2000 (c) The SourceForge Crew
//http://sourceforge.net
//
//$Id: qrs.php 4555 2006-12-21 11:11:49 +0000 (Thu, 21 Dec 2006) ahardyau $

require_once ('pre.php');
require_once ('www/file/file_utils.php');
require_once ('www/file/admin/frsValidator.class.php');
require_once ('common/include/SimpleSanitizer.class.php');
require_once ('common/include/Feedback.class.php');
require_once ('common/mail/Mail.class.php');
require_once ('www/forum/forum_utils.php');
require_once ('common/frs/FRSPackageFactory.class.php');
require_once ('common/frs/FRSReleaseFactory.class.php');
require_once ('common/frs/FRSFileFactory.class.php');
require_once ('common/frs/FileModuleMonitorFactory.class.php');
require_once ('www/project/admin/permissions.php');
require_once ('common/include/HTTPRequest.class.php');
$Language->loadLanguageMsg('file/file');
$Language->loadLanguageMsg('news/news');
$request = & HTTPRequest::instance();

/*
 Quick file release system , Darrell Brogdon, SourceForge, Aug, 2000
 
 With much code horked from editreleases.php
 */
$group_id = $request->get('group_id');
if (!user_ismember($group_id, 'R2')) {
    exit_permission_denied();
}
$GLOBALS['HTML']->includeJavascriptFile("/scripts/prototype/prototype.js");
$GLOBALS['HTML']->includeJavascriptFile("/scripts/scriptaculous/scriptaculous.js");
$GLOBALS['HTML']->includeJavascriptFile("/scripts/calendar.js");
$GLOBALS['HTML']->includeJavascriptFile("../scripts/frs.js");

$frspf = new FRSPackageFactory();
$frsrf = new FRSReleaseFactory();
$frsff = new FRSFileFactory();

if ($request->exist('func')) {
    $package_id = $request->get('package_id');
    if ($package =& $frspf->getFRSPackageFromDb($package_id, $group_id)) {
        switch($request->get('func')) {
            case 'delete':
                if ($release_id = $request->get('id')) {
                    /*
                         Delete a release with all the files included
                         Delete the corresponding row from the database
                         Delete the corresponding directory from the server
                    */
                    $res = $frsrf->delete_release($group_id, $release_id);
                    if ($res == 0) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases','rel_not_yours'));
                    } else {
                        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editreleases','rel_del'));
                    }
                }
                $GLOBALS['Response']->redirect('/file/?group_id='.$group_id);
                break;
            case 'add':
                $release =& new FRSRelease();
                $release->setPackageId($package_id);
                $release->setStatusId(1);
                $release->setReleaseDate(time());
                frs_display_release_form($is_update = false, $release, $group_id, $Language->getText('file_admin_editreleases', 'create_new_release'), '?func=create&amp;group_id='. $group_id .'&amp;package_id='. $package_id);
                break;
            case 'create':
                if ($request->exist('cancel')) {
                    $GLOBALS['Response']->addFeedback('info', $Language->getText('file_admin_editreleases', 'create_canceled'));
                    $GLOBALS['Response']->redirect('/file/?group_id='.$group_id);
                } else {
                    frs_process_release_form($is_update = false, $request, $group_id, $Language->getText('file_admin_editreleases', 'release_new_file_version'), '?func=create&amp;group_id='. $group_id .'&amp;package_id='. $package_id);
                }
                break;
            case 'edit':
                $release_id = $request->get('id');
                if ($release =& $frsrf->getFRSReleaseFromDb($release_id, $group_id)) {
                    frs_display_release_form($is_update = true, $release, $group_id, $Language->getText('file_admin_editreleases', 'edit_release'), '?func=update&amp;group_id='. $group_id .'&amp;package_id='. $package_id .'&amp;id='. $release_id);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $Language->getText('file_admin_editreleases', 'rel_id_not_found'));
                    $GLOBALS['Response']->redirect('/file/?group_id='.$group_id);
                }
                break;
            case 'update':
                if ($request->exist('cancel')) {
                    $GLOBALS['Response']->addFeedback('info', $Language->getText('file_admin_editreleases', 'Release update canceled'));
                    $GLOBALS['Response']->redirect('/file/?group_id='.$group_id);
                } else {
                    $release_id = $request->get('id');
                    if ($release =& $frsrf->getFRSReleaseFromDb($release_id, $group_id)) {
                        frs_process_release_form($is_update = true, $request, $group_id, $Language->getText('file_admin_editreleases', 'release_new_file_version'), '?func=update&amp;group_id='. $group_id .'&amp;package_id='. $package_id .'&amp;id='. $release_id);
                    } else {
                        $GLOBALS['Response']->addFeedback('error', $Language->getText('file_admin_editreleases', 'rel_id_not_found'));
                        $GLOBALS['Response']->redirect('/file/?group_id='.$group_id);
                    }
                }
                break;
            default:
                break;
        }
    } else {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editpackages', 'p_not_exists'));
        $GLOBALS['Response']->redirect('/file/?group_id='.$group_id);
    }
}

?>
