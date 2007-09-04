<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');    
require_once('www/project/admin/permissions.php');    
require_once('www/file/file_utils.php');
require_once('common/mail/Mail.class.php');
require_once('www/forum/forum_utils.php');
require_once('common/frs/FRSFileFactory.class.php');
require_once('common/frs/FRSReleaseFactory.class.php');
require_once('common/frs/FRSPackageFactory.class.php');
require_once('common/frs/FileModuleMonitorFactory.class.php');
$Language->loadLanguageMsg('file/file');
$request =& HTTPRequest::instance();


/*

File release system rewrite, Tim Perdue, SourceForge, Aug, 2000


	Sorry this is a large, complex page but this is a very complex process


	If you pass just the group_id, you will be given a list of releases
	with the option to edit those releases or create a new release


	If you pass the group_id plus the package_id, you will get the list of 
		releases with just the releases of that package shown
*/

$group_id = $request->get('group_id');
if (!user_ismember($group_id,'R2')) {
    exit_permission_denied();
}

$frspf = new FRSPackageFactory();
$frsrf = new FRSReleaseFactory();
$frsff = new FRSFileFactory();

$existing_packages = array();
$res = $frspf->getFRSPackagesFromDb($group_id);
foreach($res as $p => $nop) {
    $existing_packages[] = array(
        'id'   => $res[$p]->getPackageId(),
        'name' => $res[$p]->getName(),
        'rank' => $res[$p]->getRank(),
    );
}

if ($request->exist('func')) {
    switch ($request->get('func')) {
        case 'delete': //Not yet
            break;
        case 'add':
            $package =& new FRSPackage(array('group_id' => $group_id));
            frs_display_package_form($package, $GLOBALS['Language']->getText('file_admin_editpackages', 'create_new_p'), '?group_id='. $group_id .'&amp;func=create', $existing_packages);
            break;
        case 'create':
            if (!$request->exist('submit')) {
                $GLOBALS['Response']->addFeedback('info', $Language->getText('file_admin_editpackages','create_canceled'));
                $GLOBALS['Response']->redirect('/file/?group_id='.$group_id);
            } else {
                $package_data = $request->get('package');
                $package_data['group_id'] = $group_id;
                if (isset($package_data['name']) && isset($package_data['rank']) && isset($package_data['status_id'])) {
                    $package_data['name'] = htmlspecialchars($package_data['name']);
                    if ($frspf->isPackageNameExist($package_data['name'], $group_id)) {
                        $GLOBALS['Response']->addFeedback('error', $Language->getText('file_admin_editpackages','p_name_exists'));
                        $package =& new FRSPackage($package_data);
                        frs_display_package_form($package, $GLOBALS['Language']->getText('file_admin_editpackages', 'create_new_p'), '?func=create&amp;group_id='. $group_id, $existing_packages);
                    } else {
                        //create a new package
                        $res_id = $frspf->create($package_data);
                        //add default permission on the new package (register users)
                        //TODO permissions @ creation
                        if($res_id){
                            $pm = & PermissionsManager::instance();
                            $pm->addPermission('PACKAGE_READ', $res_id, '2');
                        }
                        $GLOBALS['Response']->addFeedback('info', $Language->getText('file_admin_editpackages','p_added'));
                        $GLOBALS['Response']->redirect('/file/?group_id='.$group_id);
                    }
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'missing_parameters'));
                    frs_display_package_form($package, $GLOBALS['Language']->getText('file_admin_editpackages', 'create_new_p'), '?func=create&amp;group_id='. $group_id, $existing_packages);
                }
            }
            break;
        case 'edit':
            $package_id = $request->get('id');
            if ($package =& $frspf->getFRSPackageFromDb($package_id, $group_id)) {
                frs_display_package_form($package, $GLOBALS['Language']->getText('file_admin_editpackages', 'edit_package'), '?func=update&amp;group_id='. $group_id .'&amp;id='. $package_id, $existing_packages);
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editpackages', 'p_not_exists'));
                $GLOBALS['Response']->redirect('/file/?group_id='.$group_id);
            }
            break;
        case 'update':
            if (!$request->exist('submit')) {
                $GLOBALS['Response']->addFeedback('info', $Language->getText('file_admin_editpackages','update_canceled'));
                $GLOBALS['Response']->redirect('/file/?group_id='.$group_id);
            } else {
                $package_id = $request->get('id');
                if ($package =& $frspf->getFRSPackageFromDb($package_id, $group_id)) {
                    $package_data = $request->get('package');
                    // we check if the name already exist only if the name has changed
                    if ($package_data['name'] == html_entity_decode($package->getName()) || !$frspf->isPackageNameExist($package_data['name'], $group_id)) {
                        if ($package_data['status_id'] != 1) {
                            //if hiding a package, refuse if it has releases under it
                            // LJ Wrong SQL statement. It should only check for the existence of
                            // LJ active packages. If only hidden releases are in this package
                            // LJ then we can safely hide it.
                            // LJ $res=db_query("SELECT * FROM frs_release WHERE package_id='$package_id'");
                            if ($frsrf->isActiveReleases($package_id)) {
                                $GLOBALS['Response']->addFeedback('warning', $Language->getText('file_admin_editpackages','cannot_hide'));
                                $package_data['status_id'] = 1;
                            }
                        }
                        //update an existing package
                        $package->setName(htmlspecialchars($package_data['name']));
                        $package->setRank($package_data['rank']);
                        $package->setStatusId($package_data['status_id']);
                        $package->setApproveLicense($package_data['approve_license']);
                        $package_is_updated = $frspf->update($package);
                        
                        //Permissions
                        list ($return_code, $feedback) = permission_process_selection_form($group_id, 'PACKAGE_READ', $package->getPackageID(), $request->get('ugroups'));
                        if (!$return_code) {
                            $GLOBALS['Response']->addFeedback('error', $Language->getText('file_admin_editpackages','perm_update_err'));
                            $GLOBALS['Response']->addFeedback('error', $feedback);
                        } else {
                            $package_is_updated = true;
                        }
        
                        if ($package_is_updated) {
                            $GLOBALS['Response']->addFeedback('info', $Language->getText('file_admin_editpackages','p_updated'));
                        } else {
                            $GLOBALS['Response']->addFeedback('info', 'Package not updated');
                        }
                        $GLOBALS['Response']->redirect('/file/?group_id='.$group_id);
                    } else {
                    	    $GLOBALS['Response']->addFeedback('error', $Language->getText('file_admin_editpackages','p_name_exists'));
                        $GLOBALS['Response']->addFeedback('info', $Language->getText('file_admin_editpackages','update_canceled'));
                        $GLOBALS['Response']->redirect('/file/?group_id='.$group_id);
                    }
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editpackages', 'p_not_exists'));
                    $GLOBALS['Response']->redirect('/file/?group_id='.$group_id);
                }
            }
            break;
        default:
            break;
    }
}
?>
