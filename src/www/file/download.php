<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id: download.php 5442 2007-03-21 10:15:59 +0000 (Wed, 21 Mar 2007) nterray $
require_once('pre.php');
require_once('www/project/admin/permissions.php');
require_once('common/frs/FRSFileFactory.class.php');
$Language->loadLanguageMsg('file/file');

if (user_isloggedin()) {

  list(,$group_id, $file_id) = explode('/', $PATH_INFO);

  // Must have a group_id and file_id otherwise
  // we cannot do much
  if (!$file_id || !$group_id) {
    exit_missing_param();
  }

  // Now make an innerjoin on the 4 tables to be sure
  // that the file_id we have belongs to the given group_id

  $frsff = new FRSFileFactory();
  $file =& $frsff->getFRSFileFromDb($file_id, $group_id);

  if (! $file || $file->isError()) {
    exit_error($Language->getText('file_download','incorrect_release_id'), $Language->getText('file_download','report_error',$GLOBALS['sys_name']));
  }

  // Check permissions for downloading the file, and check that the file has the active status 
  if (! $file->userCanDownload() || ! $file->isActive()) {
      exit_error($Language->getText('file_download','access_denied'), 
                $Language->getText('file_download','access_not_authorized',session_make_url("/project/memberlist.php?group_id=$group_id")));
  } 

  // Get the URL to download the file
  $file_location = $file->getFileLocation();

  if ($fp=fopen($file_location,"r")) {
      $size = filesize($file_location);

      // Log the download in the Log system
      $file->logDownload(user_getid());
      
      // Now transfer the file to the client
      // Make sure this URL is not cached anywhere otherwise download
      // would be wrong
      // (Don't send the no-cache if IE and SSL - see
      // http://support.microsoft.com/default.aspx?scid=kb;EN-US;q316431.
      if(!(browser_is_ie() && session_issecure() &&
	   (strcmp(browser_get_version(), '5.5') ||
	    strcmp(browser_get_version(), '5.01') ||
	    strcmp(browser_get_version(), '6'))) ) {
	  header("Cache-Control: no-cache");  // HTTP 1.1 - must be on 2 lines or IE 5.0 error
	  header("Cache-Control: must-revalidate");  // HTTP 1.1
	  header("Pragma: no-cache");  // HTTP 1.0
      }
      $bn = basename($file->getFileName());
      header("Content-Type: application/octet-stream");
	  header('Content-Disposition: attachment; filename="'. $bn .'"');
      header("Content-Length:  $size");
      header("Content-Transfer-Encoding: binary\n");
      fpassthru($fp);
  
  } else {
      // Can't open the file for download. There is a problem here !!
      exit_error($Language->getText('global','error'), $Language->getText('file_download','file_not_available'));
  }

} else {
  /*
    Not logged in
  */
  exit_not_logged_in();
}
?>
