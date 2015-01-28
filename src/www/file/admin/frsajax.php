<?php
require_once ('pre.php');
require_once ('www/project/admin/permissions.php');
require_once ('frsValidator.class.php');
require_once ('common/include/Feedback.class.php');
require_once ('common/frs/FRSFileFactory.class.php');
require_once ('json.php');

$vAction = new Valid_WhiteList('action',array('permissions_frs_package','permissions_frs_release','validator_frs_create','validator_frs_update','refresh_file_list'));
if ($request->valid($vAction)) {
    $action = $request->get('action');
} else {
    exit_error('', '');
}

if ($action == 'permissions_frs_package') {
    $vPackageId = new Valid_UInt('package_id');
    $vPackageId->required();
    $vGroupId = new Valid_GroupId();
    $vGroupId->required();
    if ($request->valid($vPackageId) && $request->valid($vGroupId)) {
        $package_id = $request->get('package_id');
        $group_id   = $request->get('group_id');
        permission_display_selection_frs("PACKAGE_READ", $package_id, $group_id);
    }
} else {
    if ($action == 'permissions_frs_release') {

   	    $vReleaseId = new Valid_UInt('release_id');
        $vReleaseId->required();
	    $vGroupId = new Valid_GroupId();
        $vGroupId->required();
        if ($request->valid($vReleaseId) && $request->valid($vGroupId)) {
            $group_id   = $request->get('group_id');
            $release_id = $request->get('release_id');    
            permission_display_selection_frs("RELEASE_READ", $release_id, $group_id);
        }
    } else {
        header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        if ($action == 'validator_frs_create') {
            $vName = new Valid_String('name');
            $vDate = new Valid_String('date');
            $vDate->required();
            $vPackageId = new Valid_UInt('package_id');
            $vPackageId->required();    	          
            $vGroupId = new Valid_GroupId();
            $vGroupId->required();
            if ($request->valid($vName) &&
                $request->valid($vDate) &&
                $request->valid($vGroupId) &&
                $request->valid($vPackageId)) {
                $name = $request->get('name');
                $package_id = $request->get('package_id');
                $date       = $request->get('date');
                $group_id   = $request->get('group_id');
                $validator = new frsValidator();
                $release = array (
                    'name' => $name,
                    'package_id' => $package_id,
                    'date' => $date
                );
                if ($validator->isValidForCreation($release, $group_id)) {
                    //frs valid
                    $header = array('valid' => true);
                } else {
                    //frs non valid
                    $errors = $validator->getErrors();
                    $feedback = new Feedback();
                    $feedback->log('error', $errors[0]);
                    $header = array('valid' => false, 'msg' => $feedback->fetch());
                }
                header(json_header($header));
            }
        } else {
            if ($action == 'validator_frs_update') {
                $vName = new Valid_String('name');
 	            $vDate = new Valid_String('date');
 	            $vDate->required();
                $vPackageId = new Valid_UInt('package_id');
    	        $vPackageId->required();
                $vReleaseId = new Valid_UInt('release_id');
                $vReleaseId->required();    	          
                $vGroupId = new Valid_GroupId();
                $vGroupId->required();
                if ($request->valid($vName) &&
                    $request->valid($vDate) &&
                    $request->valid($vGroupId) &&
                    $request->valid($vPackageId) &&
                    $request->valid($vReleaseId)) {
                    $name       = $request->get('name');
                    $package_id = $request->get('package_id');
                    $date       = $request->get('date');
                    $group_id   = $request->get('group_id');
                    $release_id = $request->get('release_id');
                    $validator = new frsValidator();
                    $release = array (
                        'name' => $name,
                        'release_id' => $release_id,
                        'package_id' => $package_id,
                        'date' => $date
                    );
                    if ($validator->isValidForUpdate($release, $group_id)) {
                        //frs valid
                        $header = array('valid' => true);
                    } else {
                        //frs non valid
                        $errors = $validator->getErrors();
                        $feedback = new Feedback();
                        $feedback->log('error', $errors[0]);
                        $header = array('valid' => false, 'msg' => $feedback->fetch());
                    }
                    header(json_header($header));
                }
            } else {
                if ($action == 'refresh_file_list') {
                    $project = $request->getProject();
                    $frsff = new FRSFileFactory();
                    $file_list = $frsff->getUploadedFileNames($project);
                    $available_ftp_files = implode(",", $file_list);
                    $purifier = Codendi_HTMLPurifier::instance();
                    $available_ftp_files = $purifier->purify($available_ftp_files, CODENDI_PURIFIER_JS_DQUOTE);
                    echo '{"valid":true, "msg":"'.$available_ftp_files.'"}';
                }
            }
        }
    }
}
