<?php

require_once('pre.php');
require_once('common/backend/BackendCVS.class.php');


function cvs_loginfo_format_data($line) {
    $match=array();
    preg_match_all('/\/cvsroot\/([^\/]*?)\/CVSROOT\/loginfo/', $line, $match);  
    return $match[1][0];
}

$backendCVS = BackendCVS::instance();
$backendCVS->log(__FILE__." script execution start!");

$file_list    = glob('/cvsroot/*/CVSROOT/loginfo');
$project_list = array_map('cvs_loginfo_format_data', $file_list);

foreach ( $file_list as $key=>$filename ) {
    $output = '';
    if ( !$backendCVS->_RcsCheckout($filename, $output) ) {
        $backendCVS->log("Unable to checkout $filename, output=$output", Backend::LOG_ERROR);
        continue;
    }
    $lines = file($filename);
    $lines_to_write = array();
    $project_name = trim($project_list[$key]);
    foreach ($lines as $line) {
        if ( strpos($line, "ALL (cat;chgrp -R $project_name /var/lib/codendi/cvsroot/$project_name)>/dev/null 2>&1") !== false  ) {
            continue;
        } else {
            $lines_to_write[] = $line;
        }
    }
    if ( !empty($lines_to_write) ) {
        if( !$backendCVS->writeArrayToFile($lines_to_write, $filename) ) {
            $backendCVS->log("An error occured during file $filename was being written ... skipping commit!", Backend::LOG_ERROR); 
            continue;
        }
        if( !$backendCVS->_RcsCommit($filename, $output) ) {
            $backendCVS->log("Unable to commit $filename, output=$output", Backend::LOG_ERROR);
        } else {
            $backendCVS->log("File $filename successfully modified", Backend::LOG_INFO);
        }
    } else {
        $backendCVS->log("Nothing to do for file $filename", Backend::LOG_INFO);
    }
}    
$backendCVS->log(__FILE__." script execution done!");

?>
