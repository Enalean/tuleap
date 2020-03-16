<?php
// Copyright (c) Enalean SAS, 2017. All rights reserved
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com
//
//
//
//    Originally written by Laurent Julliard 2001, 2002, Codendi Team, Xerox
require_once __DIR__ . '/../include/pre.php';

// Inherited from old .htaccess (needed for reports, linked artifact view, etc)
ini_set('max_execution_time', 1800);

$id          = $request->get('id');
$artifact_id = $request->get('artifact_id');

// We have the artifact id, but not the tracker id
$sql = "SELECT group_artifact_id, group_id FROM artifact INNER JOIN artifact_group_list USING (group_artifact_id) WHERE artifact_id= " . db_ei($artifact_id);
$result = db_query($sql);
if (db_numrows($result) > 0) {
    $row = db_fetch_array($result);
    $atid = $row['group_artifact_id'];
    $pm = ProjectManager::instance();
    $group = $pm->getProject($row['group_id']);

    $at = new ArtifactType($group, $atid);
    if ($at->userCanView()) {
        $art_field_fact = new ArtifactFieldFactory($at); // Grrr! don't use global >_<
        $a = new Artifact($at, $artifact_id);
        if ($a->userCanView()) {
            $sql = "SELECT description,bin_data,filename,filesize,filetype FROM artifact_file WHERE id='" . db_ei($id) . "' AND artifact_id ='" . db_ei($artifact_id) . "'";
            //echo $sql;
            $result = db_query($sql);

            if ($result && db_numrows($result) > 0) {
                if (db_result($result, 0, 'filesize') == 0) {
                    exit_error($Language->getText('global', 'error'), $Language->getText('tracker_download', 'file_is_null'));
                } else {
                    // Download the patch with the correct filetype
                    $http = Codendi_HTTPPurifier::instance();
                    header('X-Content-Type-Options: nosniff');
                    header('Content-Type: ' . $http->purify(db_result($result, 0, 'filetype')));
                    header('Content-Length: ' . $http->purify(db_result($result, 0, 'filesize')));
                    header('Content-Disposition: attachment; filename="' . $http->purify(db_result($result, 0, 'filename')) . '"');
                    header('Content-Description: ' . $http->purify(db_result($result, 0, 'description')));

                    $attachment_path = ArtifactFile::getPathOnFilesystem($a, $id);
                    if (is_file($attachment_path)) {
                        if (ob_get_level()) {
                            ob_end_clean();
                        }
                        readfile($attachment_path);
                    } else {
                        echo db_result($result, 0, 'bin_data');
                    }
                    exit();
                }
            }
        } else {
            exit_error($Language->getText('global', 'error'), $Language->getText('global', 'perm_denied'));
        }
    } else {
        exit_error($Language->getText('global', 'error'), $Language->getText('global', 'perm_denied'));
    }
}
exit_error($Language->getText('global', 'error'), $Language->getText('tracker_download', 'file_not_found', $id));
