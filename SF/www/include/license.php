<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX, 2001-2004. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
// Originally written by Laurent Julliard 2004, CodeX Team, Xerox
//

$FILE_ACCEPTED = getenv('SF_LOCAL_INC_PREFIX').'/etc/codex/CODEX_LICENSE_ACCEPTED';
$FILE_DECLINED = getenv('SF_LOCAL_INC_PREFIX').'/etc/codex/CODEX_LICENSE_DECLINED';

/**
 * Says whether the license terms have already been displayed or not
 *
 *
 * @return boolean
 */
function license_already_displayed() {
    global $FILE_ACCEPTED, $FILE_DECLINED;
    return file_exists($FILE_ACCEPTED) || file_exists($FILE_DECLINED);
}

/**
 * Says whether the license terms have already been declined
 *
 *
 * @return boolean
 */
function license_already_declined() {
    global $FILE_DECLINED;
    return file_exists($FILE_DECLINED);
}

/**
 * The license terms are accepted so create the file that says it was accepted
 *
 * @return true
 */
function license_accepted() {
    global $FILE_ACCEPTED, $sys_datefmt, $sys_lf;

    // Open the file and go to the end
    $fp = @fopen($FILE_ACCEPTED,'a+');

    if (!$fp) {
	exit_error('ERROR',"Cannot open license acceptance in file: $FILE_ACCEPTED");
    }

    // Write Date in file
    $msg = "CodeX Core Software License accepted on ".format_date($sys_datefmt,time()).$sys_lf;

    if (fwrite($fp, $msg) == FALSE) {
       exit_error('ERROR',"Cannot write license acceptance in file: $FILE_ACCEPTED");
   }

   fclose($fp);
   return true;
	
}

/**
 * The license terms were declined so remove the write it in the proper file
 *
 * @return true
 */
function license_declined() {
    global $FILE_DECLINED, $sys_datefmt, $sys_lf;

    // Open the file and go to the end
    $fp = @fopen($FILE_DECLINED,'a+');

    if (!$fp) {
	exit_error('ERROR',"Cannot open license declination file: $FILE_DECLINED");
    }

    // Write Date in file
    $msg = "CodeX Core Software License declined on ".
	format_date($sys_datefmt,time()).$sys_lf;

    if (fwrite($fp, $msg) == FALSE) {
       exit_error('ERROR',"Cannot write license declination in file: $FILE_DECLINED");
   }
   fclose($fp);

   return true;
}

function license_msg_accepted() {
    return 'Thanks for accepting the terms and conditions of the CodeX
        license. You can now enjoy the CodeX site';
}

function license_msg_declined() {
    return 'You have chosen to decline the terms and conditions of the
        CodeX Software License. You must return all the CodeX Software and
        Documentation to Xerox and remove it from all your machines';
}

?>
