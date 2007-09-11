<?php
require_once ('pre.php');
require_once ('www/project/admin/permissions.php');
require_once ('frsValidator.class.php');
require_once ('common/include/Feedback.class.php');
require_once ('common/frs/FRSFileFactory.class.php');

if ($_GET['action'] == 'permissions_frs_package') {

    permission_display_selection_frs("PACKAGE_READ", $_GET['package_id'], $_GET['group_id']);
} else {
    if ($_GET['action'] == 'permissions_frs_release') {

        permission_display_selection_frs("RELEASE_READ", $_GET['release_id'], $_GET['group_id']);
    } else {
        header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        if ($_GET['action'] == 'validator_frs_create') {
            $validator = new frsValidator();
            $release = array (
                'name' => $_GET['name'],
                'package_id' => $_GET['package_id'],
                'date' => $_GET['date']
            );
            if ($validator->isValidForCreation($release, $_GET['group_id'])) {
                //frs valid
                header("X-JSON: ({valid:true})");
            } else {
                //frs non valid
                $errors = $validator->getErrors();
                $feedback = new Feedback();
                $feedback->log('error', $errors[0]);
                header("X-JSON: ({valid:false, msg:'" . addslashes($feedback->fetch()) . "'})");
            }

        } else {
            if ($_GET['action'] == 'validator_frs_update') {
                $validator = new frsValidator();
                $release = array (
                    'name' => $_GET['name'],
                    'release_id' => $_GET['release_id'],
                    'package_id' => $_GET['package_id'],
                    'date' => $_GET['date']
                );
                if ($validator->isValidForUpdate($release, $_GET['group_id'])) {
                    //frs valid
                    header("X-JSON: ({valid:true})");
                } else {
                    //frs non valid
                    $errors = $validator->getErrors();
                    $feedback = new Feedback();
                    $feedback->log('error', $errors[0]);
                    header("X-JSON: ({valid:false, msg:'" . addslashes($feedback->fetch()) . "'})");
                }

            } else {
                if ($_GET['action'] == 'refresh_file_list'){
                    $frsff = new FRSFileFactory();
                    $file_list = $frsff->getUploadedFileNames();
                    $available_ftp_files = implode(",", $file_list);
                    echo "{valid:true, msg:'".$available_ftp_files."'}";
                }
            }
        }
    }
}
?>
